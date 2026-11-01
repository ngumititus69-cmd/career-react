<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $provided = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (!is_string($provided) || !is_string($stored) || $stored === '' || !hash_equals($stored, $provided)) {
        http_response_code(400);
        throw new RuntimeException('Your form expired. Refresh the page and try again.');
    }
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function currentUser(PDO $pdo): ?array
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId === null) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, first_name, last_name, email, career_stage FROM users WHERE id = :id');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    if ($user === false) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function decodeList(string $json): array
{
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function calculateRecommendations(PDO $pdo, int $userId, array $answers): void
{
    $careers = $pdo->query('SELECT * FROM careers WHERE featured = 1')->fetchAll();
    $weights = [
        'interests' => 40,
        'skills' => 25,
        'subjects' => 15,
        'work_styles' => 10,
        'goals' => 10,
    ];

    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare('DELETE FROM recommendations WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO recommendations (user_id, career_id, score, matched_json)
            VALUES (:user_id, :career_id, :score, :matched_json)'
        );

        foreach ($careers as $career) {
            $score = 0;
            $weightTotal = 0;
            $matched = [];

            foreach ($weights as $field => $weight) {
                $selected = $answers[$field] ?? [];
                $required = decodeList($career[$field . '_json']);
                if ($selected === [] || $required === []) {
                    continue;
                }

                $weightTotal += $weight;
                $matches = array_values(array_intersect($selected, $required));
                $score += (int) round($weight * count($matches) / count($required));
                if ($matches !== []) {
                    $matched[$field] = $matches;
                }
            }

            $normalizedScore = $weightTotal === 0 ? 0 : (int) round($score * 100 / $weightTotal);
            $insert->execute([
                'user_id' => $userId,
                'career_id' => $career['id'],
                'score' => $normalizedScore,
                'matched_json' => json_encode($matched, JSON_THROW_ON_ERROR),
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function appIcon(string $category): string
{
    return match ($category) {
        'Technology' => 'bi-code-slash',
        'Creative work' => 'bi-palette2',
        'Business' => 'bi-briefcase',
        'Education' => 'bi-mortarboard',
        'Entrepreneurship' => 'bi-lightbulb',
        'Agriculture' => 'bi-flower1',
        'Healthcare' => 'bi-heart-pulse',
        default => 'bi-compass',
    };
}

function fieldOptions(): array
{
    return [
        'interests' => [
            'Solving problems', 'Building technology', 'Working with data', 'Helping people',
            'Creating visual designs', 'Starting projects', 'Researching ideas',
            'Writing and communication', 'Improving processes', 'Creating things',
            'Working outdoors', 'Share knowledge',
        ],
        'skills' => [
            'Programming', 'Problem solving', 'Communication', 'Analytical thinking',
            'Visual design', 'Data analysis', 'Content creation', 'Organisation',
            'Research', 'Teamwork',
        ],
        'subjects' => [
            'Mathematics', 'Computer Studies', 'Physics', 'Business Studies',
            'English', 'Art and Design', 'Biology', 'Chemistry', 'Geography',
        ],
        'work_styles' => [
            'Independent focus', 'Collaborative teamwork', 'Flexible schedule',
            'Structured work', 'Hands-on practice', 'Remote or hybrid',
        ],
        'goals' => [
            'Build digital products', 'Work with data', 'Help people directly',
            'Grow a business', 'Start a business', 'Solve community problems',
            'Share knowledge',
        ],
    ];
}
