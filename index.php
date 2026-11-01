git add .git add .<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/functions.php';

$page = $_GET['page'] ?? 'home';
$allowedPages = ['home', 'register', 'login', 'dashboard', 'assessment', 'results', 'explore', 'jobs', 'career', 'roadmap', 'saved'];
if (!is_string($page) || !in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'home';
    flash('That page could not be found.', 'error');
}

$user = currentUser($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $action = $_POST['action'] ?? '';

        if ($action === 'register') {
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $careerStage = (string) ($_POST['career_stage'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $validStages = [
                'secondary_student', 'college_student', 'university_student',
                'graduate', 'working_professional', 'career_changer',
            ];

            if ($firstName === '' || $lastName === '' || strlen($firstName) > 80 || strlen($lastName) > 80) {
                throw new RuntimeException('Please enter your first and last name.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid email address.');
            }
            if (!in_array($careerStage, $validStages, true)) {
                throw new RuntimeException('Please choose the option that best describes you.');
            }
            if (strlen($password) < 8 || strlen($password) > 200) {
                throw new RuntimeException('Use a password with at least 8 characters.');
            }
            if (!hash_equals($password, (string) ($_POST['password_confirmation'] ?? ''))) {
                throw new RuntimeException('Your passwords do not match.');
            }

            $statement = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, career_stage)
                VALUES (:first_name, :last_name, :email, :password_hash, :career_stage)'
            );
            try {
                $statement->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'career_stage' => $careerStage,
                ]);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                throw new RuntimeException('An account with that email address already exists.');
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            flash('Your account is ready. Let’s discover your career direction.');
            redirect('?page=assessment');
        }

        if ($action === 'login') {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $statement = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email');
            $statement->execute(['email' => $email]);
            $account = $statement->fetch();

            if ($account === false || !password_verify($password, $account['password_hash'])) {
                throw new RuntimeException('The email or password is not correct. Please try again.');
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            flash('Welcome back. Your career journey is ready.');
            redirect('?page=dashboard');
        }

        if ($action === 'logout') {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
            session_destroy();
            redirect('?page=home');
        }

        if ($user === null) {
            flash('Please sign in first.', 'error');
            redirect('?page=login');
        }

        if ($action === 'save_assessment') {
            $options = fieldOptions();
            $answers = [];
            foreach ($options as $field => $allowedValues) {
                $submitted = $_POST[$field] ?? [];
                if (!is_array($submitted)) {
                    throw new RuntimeException('Please review your assessment answers and try again.');
                }
                $selected = array_values(array_unique(array_filter(
                    $submitted,
                    static fn ($value): bool => is_string($value) && in_array($value, $allowedValues, true)
                )));
                if ($selected === []) {
                    throw new RuntimeException('Please choose at least one answer in every section.');
                }
                $answers[$field] = $selected;
            }

            $stage = (string) ($_POST['career_stage'] ?? '');
            $allowedStages = [
                'secondary_student', 'college_student', 'university_student',
                'graduate', 'working_professional', 'career_changer',
            ];
            if (!in_array($stage, $allowedStages, true)) {
                throw new RuntimeException('Please select your current career stage.');
            }

            $answers['career_stage'] = $stage;
            $saveAnswers = $pdo->prepare(
                'INSERT INTO assessments (user_id, answers_json, completed_at)
                VALUES (:user_id, :answers_json, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id) DO UPDATE SET
                    answers_json = excluded.answers_json,
                    completed_at = CURRENT_TIMESTAMP'
            );
            $saveAnswers->execute([
                'user_id' => $user['id'],
                'answers_json' => json_encode($answers, JSON_THROW_ON_ERROR),
            ]);
            $updateStage = $pdo->prepare('UPDATE users SET career_stage = :career_stage WHERE id = :id');
            $updateStage->execute(['career_stage' => $stage, 'id' => $user['id']]);
            calculateRecommendations($pdo, (int) $user['id'], $answers);
            flash('Your answers are saved. Here are career directions that connect with them.');
            redirect('?page=results');
        }

        if ($action === 'toggle_save') {
            $careerId = filter_var($_POST['career_id'] ?? null, FILTER_VALIDATE_INT);
            if ($careerId === false || $careerId === null) {
                throw new RuntimeException('That career could not be saved.');
            }
            $careerCheck = $pdo->prepare('SELECT id FROM careers WHERE id = :id');
            $careerCheck->execute(['id' => $careerId]);
            if ($careerCheck->fetch() === false) {
                throw new RuntimeException('That career could not be found.');
            }
            $saved = $pdo->prepare('SELECT 1 FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
            $saved->execute(['user_id' => $user['id'], 'career_id' => $careerId]);
            if ($saved->fetchColumn() !== false) {
                $delete = $pdo->prepare('DELETE FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
                $delete->execute(['user_id' => $user['id'], 'career_id' => $careerId]);
                flash('Career removed from your saved list.');
            } else {
                $insert = $pdo->prepare('INSERT INTO saved_careers (user_id, career_id) VALUES (:user_id, :career_id)');
                $insert->execute(['user_id' => $user['id'], 'career_id' => $careerId]);
                flash('Career saved to your list.');
            }
            $returnTo = (string) ($_POST['return_to'] ?? '?page=dashboard');
            if (!in_array($returnTo, ['?page=dashboard', '?page=results', '?page=explore', '?page=saved'], true)) {
                $returnTo = '?page=dashboard';
            }
            redirect($returnTo);
        }

        if ($action === 'toggle_progress') {
            $careerId = filter_var($_POST['career_id'] ?? null, FILTER_VALIDATE_INT);
            $stepNumber = filter_var($_POST['step_number'] ?? null, FILTER_VALIDATE_INT);
            if ($careerId === false || $careerId === null || $stepNumber === false || $stepNumber === null || $stepNumber < 0 || $stepNumber > 20) {
                throw new RuntimeException('That roadmap step could not be updated.');
            }
            $careerCheck = $pdo->prepare('SELECT roadmaps_json FROM careers WHERE id = :id');
            $careerCheck->execute(['id' => $careerId]);
            $roadmapJson = $careerCheck->fetchColumn();
            if (!is_string($roadmapJson) || !array_key_exists($stepNumber, decodeList($roadmapJson))) {
                throw new RuntimeException('That roadmap step could not be found.');
            }
            $remove = $pdo->prepare('DELETE FROM roadmap_progress WHERE user_id = :user_id AND career_id = :career_id AND step_number = :step_number');
            $remove->execute(['user_id' => $user['id'], 'career_id' => $careerId, 'step_number' => $stepNumber]);
            if ($remove->rowCount() === 0) {
                $insert = $pdo->prepare('INSERT INTO roadmap_progress (user_id, career_id, step_number) VALUES (:user_id, :career_id, :step_number)');
                $insert->execute(['user_id' => $user['id'], 'career_id' => $careerId, 'step_number' => $stepNumber]);
            }
            flash('Your roadmap progress has been updated.');
            redirect('?page=roadmap&career=' . rawurlencode((string) $careerId));
        }

        throw new RuntimeException('That action is not available.');
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
        if ($error === 'Your form expired. Refresh the page and try again.') {
            http_response_code(400);
        }
    } catch (PDOException $exception) {
        error_log('Career Road database error: ' . $exception->getMessage());
        $error = 'We could not save that just now. Please try again.';
    }
}

$user = currentUser($pdo);
if (in_array($page, ['dashboard', 'assessment', 'results', 'saved', 'roadmap'], true) && $user === null) {
    flash('Create an account or sign in to continue.', 'error');
    redirect('?page=register');
}
if (in_array($page, ['login', 'register'], true) && $user !== null) {
    redirect('?page=dashboard');
}

$flash = takeFlash();
$pageTitles = [
    'home' => 'Find your direction',
    'register' => 'Create your account',
    'login' => 'Welcome back',
    'dashboard' => 'Your dashboard',
    'assessment' => 'Career discovery assessment',
    'results' => 'Your career matches',
    'explore' => 'Explore careers',
    'jobs' => 'Job vacancies in Kenya',
    'career' => 'Career profile',
    'roadmap' => 'Your career roadmap',
    'saved' => 'Saved careers',
];
$title = $pageTitles[$page] ?? 'Careers React App';
$careerSlug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$career = null;
if (in_array($page, ['career', 'roadmap'], true)) {
    if ($page === 'career' && $careerSlug !== '') {
        $careerQuery = $pdo->prepare('SELECT * FROM careers WHERE slug = :slug');
        $careerQuery->execute(['slug' => $careerSlug]);
        $career = $careerQuery->fetch() ?: null;
    } elseif ($page === 'roadmap' && isset($_GET['career'])) {
        $careerId = filter_var($_GET['career'], FILTER_VALIDATE_INT);
        if ($careerId !== false && $careerId !== null) {
            $careerQuery = $pdo->prepare('SELECT * FROM careers WHERE id = :id');
            $careerQuery->execute(['id' => $careerId]);
            $career = $careerQuery->fetch() ?: null;
        }
    }
}

$answers = [];
$assessmentQuery = null;
if ($user !== null) {
    $assessmentQuery = $pdo->prepare('SELECT answers_json, completed_at FROM assessments WHERE user_id = :user_id');
    $assessmentQuery->execute(['user_id' => $user['id']]);
    $assessmentRow = $assessmentQuery->fetch();
    if ($assessmentRow !== false) {
        $decodedAnswers = json_decode($assessmentRow['answers_json'], true);
        $answers = is_array($decodedAnswers) ? $decodedAnswers : [];
    }
}
$options = fieldOptions();
$search = trim((string) ($_GET['search'] ?? ''));
$selectedCategory = trim((string) ($_GET['category'] ?? ''));

function renderCareerCard(array $career, string $returnTo, ?PDO $pdo = null, ?int $userId = null, ?array $recommendation = null): void
{
    $saved = false;
    if ($pdo !== null && $userId !== null) {
        $statement = $pdo->prepare('SELECT 1 FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
        $statement->execute(['user_id' => $userId, 'career_id' => $career['id']]);
        $saved = $statement->fetchColumn() !== false;
    }
    $icon = appIcon($career['category']);
    $matched = $recommendation !== null ? decodeList($recommendation['matched_json']) : [];
    ?>
    <article class="career-card">
        <div class="career-card-top">
            <span class="career-icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span>
            <span class="career-category"><?= e($career['category']) ?></span>
            <?php if ($recommendation !== null): ?><span class="match-score"><?= (int) $recommendation['score'] ?>% match</span><?php endif; ?>
        </div>
        <h3><a href="?page=career&amp;slug=<?= rawurlencode($career['slug']) ?>"><?= e($career['title']) ?></a></h3>
        <p><?= e($career['summary']) ?></p>
        <?php if ($matched !== []): ?>
            <div class="match-tags">
                <?php foreach (array_slice(array_merge(...array_values($matched)), 0, 3) as $match): ?>
                    <span><?= e((string) $match) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="career-card-actions">
            <a class="small-link" href="?page=career&amp;slug=<?= rawurlencode($career['slug']) ?>">View career <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <?php if ($pdo !== null && $userId !== null): ?>
                <form method="post" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_save">
                    <input type="hidden" name="career_id" value="<?= (int) $career['id'] ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <button class="save-button <?= $saved ? 'is-saved' : '' ?>" type="submit" aria-label="<?= $saved ? 'Remove saved career' : 'Save career' ?>"><i class="bi <?= $saved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>" aria-hidden="true"></i></button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14213d">
    <meta name="description" content="Discover career paths that match your interests, skills and ambitions with Careers React App.">
    <title><?= e($title) ?> | Careers React App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<header class="app-header">
    <div class="app-nav-wrap">
        <a class="app-brand" href="?page=home"><span class="brand-mark"><i class="bi bi-signpost-2-fill" aria-hidden="true"></i></span><span>careers <span>react app</span><small>FIND YOUR NEXT DIRECTION</small></span></a>
        <button class="mobile-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
        <nav class="app-nav" aria-label="Main navigation">
            <a href="?page=explore">Explore careers</a>
            <a href="?page=jobs">Job vacancies</a>
            <?php if ($user !== null): ?>
                <a href="?page=dashboard">My dashboard</a>
                <a href="?page=saved">Saved careers</a>
                <span class="nav-user">Hi, <?= e($user['first_name']) ?></span>
                <form method="post" class="inline-form"><?= csrfField() ?><input type="hidden" name="action" value="logout"><button class="nav-logout" type="submit">Sign out</button></form>
            <?php else: ?>
                <a href="?page=login">Sign in</a>
                <a class="nav-cta" href="?page=register">Get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page-main">
    <?php if ($flash !== null): ?><div class="flash-message <?= e($flash['type'] === 'error' ? 'flash-error' : 'flash-success') ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="flash-message flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <?php if ($page === 'home'): ?>
        <section class="home-hero">
            <div class="home-hero-glow"></div>
            <div class="home-hero-inner">
                <div class="home-copy">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> A clearer path starts with you</span>
                    <h1>Your interests<br>could be your <span>future.</span></h1>
                    <p>Discover careers that connect your interests, hobbies, education and abilities. Get personalised career matches and practical next steps for your future.</p>
                    <div class="hero-buttons"><a class="primary-button" href="?page=register">Discover my career <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><a class="secondary-button" href="?page=explore"><i class="bi bi-compass" aria-hidden="true"></i> Explore careers</a></div>
                    <div class="hero-note"><i class="bi bi-stars" aria-hidden="true"></i><span><strong>More than a job title.</strong><br>Guidance that starts with your story.</span></div>
                </div>
                <div class="home-art" aria-label="Careers React App compass illustration">
                    <div class="art-orbit orbit-outer"></div><div class="art-orbit orbit-inner"></div>
                    <div class="art-compass"><i class="bi bi-compass-fill" aria-hidden="true"></i></div>
                    <div class="art-float float-one"><i class="bi bi-lightbulb"></i><span>Your interests</span></div>
                    <div class="art-float float-two"><i class="bi bi-graph-up-arrow"></i><span>Your potential</span></div>
                    <div class="art-float float-three"><i class="bi bi-signpost-split"></i><span>Your next step</span></div>
                    <span class="art-label">YOUR FUTURE ISN'T ONE-SIZE-FITS-ALL</span>
                </div>
            </div>
            <div class="home-bottom"><span>INTERESTS</span><i class="bi bi-plus-lg"></i><span>STRENGTHS</span><i class="bi bi-plus-lg"></i><span>AMBITION</span><i class="bi bi-arrow-right"></i><strong>A DIRECTION THAT FEELS LIKE YOURS</strong></div>
        </section>
        <section class="content-section">
            <div class="section-title"><span class="eyebrow"><span class="eyebrow-dot"></span> A little clarity goes a long way</span><h2>Find your direction,<br><span>one step at a time.</span></h2><p>Your future doesn't have to be a mystery. Start with what makes you, you.</p></div>
            <div class="steps-grid">
                <article class="step-card"><span class="step-number">01</span><span class="step-icon blue-icon"><i class="bi bi-person-lines-fill"></i></span><h3>Tell us about you</h3><p>Share what interests you, what you're good at and what matters in your future.</p></article>
                <article class="step-card"><span class="step-number">02</span><span class="step-icon violet-icon"><i class="bi bi-stars"></i></span><h3>Explore your matches</h3><p>See career directions connected to your answers, and understand why they fit.</p></article>
                <article class="step-card"><span class="step-number">03</span><span class="step-icon green-icon"><i class="bi bi-signpost-split"></i></span><h3>Make a plan</h3><p>Turn inspiration into practical next steps for skills, learning and experience.</p></article>
            </div>
        </section>
        <section class="categories-section">
            <div class="section-title"><span class="eyebrow"><span class="eyebrow-dot"></span> THE WORLD OF WORK IS WIDE</span><h2>There is more than<br><span>one way forward.</span></h2><p>Explore career directions that connect with what you care about.</p></div>
            <div class="category-pills">
                <?php foreach (['Technology', 'Creative work', 'Business', 'Education', 'Entrepreneurship', 'Agriculture', 'Healthcare'] as $categoryName): ?>
                    <a href="?page=explore&amp;category=<?= rawurlencode($categoryName) ?>" class="category-pill"><i class="bi <?= e(appIcon($categoryName)) ?>" aria-hidden="true"></i><?= e($categoryName) ?><i class="bi bi-arrow-up-right pill-arrow" aria-hidden="true"></i></a>
                <?php endforeach; ?>
            </div>
            <div class="section-link"><a href="?page=explore" class="text-link">Explore the career library <i class="bi bi-arrow-right"></i></a></div>
        </section>
        <section class="jobs-preview">
            <div class="jobs-preview-copy"><span class="eyebrow"><span class="eyebrow-dot"></span> READY TO EXPLORE OPPORTUNITIES?</span><h2>Find current vacancies<br><span>across Kenya.</span></h2><p>Browse job listings from Kenyan recruitment and job-search websites. Vacancies are published and updated by each job board.</p></div>
            <a class="primary-button" href="?page=jobs">Explore job websites <i class="bi bi-arrow-up-right"></i></a>
        </section>
        <section class="home-cta"><div><span class="eyebrow eyebrow-on-dark"><span class="eyebrow-dot"></span> YOUR FIRST STEP STARTS HERE</span><h2>Curious about where<br>you could go?</h2><p>Create your free account, take the discovery assessment and start building your own career roadmap.</p><a class="white-button" href="?page=register">Start your journey <i class="bi bi-arrow-right"></i></a></div><i class="bi bi-signpost-split cta-watermark" aria-hidden="true"></i></section>
    <?php elseif ($page === 'register' || $page === 'login'): ?>
        <?php $isRegister = $page === 'register'; ?>
        <section class="auth-layout">
            <div class="auth-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR NEXT CHAPTER</span><h1><?= $isRegister ? 'Your story is a great place to start.' : 'Welcome back to your journey.' ?></h1><p><?= $isRegister ? 'Create your account to save your career discovery answers, personalised matches and roadmap progress.' : 'Sign in to pick up where you left off and see what your answers revealed.' ?></p><div class="auth-points"><span><i class="bi bi-check-circle"></i> Your own career discovery profile</span><span><i class="bi bi-check-circle"></i> A practical, personalised roadmap</span><span><i class="bi bi-check-circle"></i> Your progress saved as you go</span></div></div>
            <div class="form-card"><div class="form-card-heading"><span class="form-symbol"><i class="bi bi-person-lock"></i></span><h2><?= $isRegister ? 'Create your account' : 'Sign in' ?></h2><p><?= $isRegister ? 'It takes just a moment to get started.' : 'Enter the details you used to register.' ?></p></div>
                <form method="post" class="stack-form" novalidate><?= csrfField() ?><input type="hidden" name="action" value="<?= $isRegister ? 'register' : 'login' ?>">
                    <?php if ($isRegister): ?>
                        <div class="form-row"><label>First name<input name="first_name" type="text" autocomplete="given-name" maxlength="80" required value="<?= e((string) ($_POST['first_name'] ?? '')) ?>"></label><label>Last name<input name="last_name" type="text" autocomplete="family-name" maxlength="80" required value="<?= e((string) ($_POST['last_name'] ?? '')) ?>"></label></div>
                    <?php endif; ?>
                    <label>Email address<input name="email" type="email" autocomplete="email" maxlength="254" required value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
                    <?php if ($isRegister): ?><label>Where are you in your career journey?<select name="career_stage" required><option value="">Choose one</option><option value="secondary_student">Secondary school student</option><option value="college_student">College student</option><option value="university_student">University student</option><option value="graduate">Graduate</option><option value="working_professional">Working professional</option><option value="career_changer">Exploring a career change</option></select></label><?php endif; ?>
                    <label>Password<input name="password" type="password" autocomplete="<?= $isRegister ? 'new-password' : 'current-password' ?>" minlength="<?= $isRegister ? '8' : '1' ?>" required><?php if ($isRegister): ?><small>Use at least 8 characters.</small><?php endif; ?></label>
                    <?php if ($isRegister): ?><label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label><?php endif; ?>
                    <button class="primary-button full-button" type="submit"><?= $isRegister ? 'Create my account' : 'Sign in to Careers React App' ?><i class="bi bi-arrow-right"></i></button>
                </form>
                <p class="form-switch"><?= $isRegister ? 'Already have an account?' : 'New to Careers React App?' ?> <a href="?page=<?= $isRegister ? 'login' : 'register' ?>"><?= $isRegister ? 'Sign in' : 'Create your account' ?></a></p>
            </div>
        </section>
    <?php elseif ($page === 'dashboard'): ?>
        <?php
        $recQuery = $pdo->prepare('SELECT c.*, r.score, r.matched_json FROM recommendations r JOIN careers c ON c.id = r.career_id WHERE r.user_id = :user_id ORDER BY r.score DESC, c.title LIMIT 3');
        $recQuery->execute(['user_id' => $user['id']]);
        $topRecommendations = $recQuery->fetchAll();
        $savedCountQuery = $pdo->prepare('SELECT COUNT(*) FROM saved_careers WHERE user_id = :user_id');
        $savedCountQuery->execute(['user_id' => $user['id']]);
        $roadmapCountQuery = $pdo->prepare('SELECT COUNT(*) FROM roadmap_progress WHERE user_id = :user_id');
        $roadmapCountQuery->execute(['user_id' => $user['id']]);
        ?>
        <section class="dashboard-wrap">
            <div class="welcome-banner"><div><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR CAREER ROAD</span><h1>Welcome back, <?= e($user['first_name']) ?>.</h1><p>Your journey is yours. Here's your next step.</p></div><a class="primary-button" href="?page=assessment"><?= $answers !== [] ? 'Update my assessment' : 'Start my assessment' ?><i class="bi bi-arrow-right"></i></a></div>
            <div class="stat-grid"><article class="stat-card"><span class="stat-icon blue-icon"><i class="bi bi-clipboard2-check"></i></span><div><strong><?= $answers !== [] ? 'Complete' : 'Not started' ?></strong><small>Career assessment</small></div></article><article class="stat-card"><span class="stat-icon violet-icon"><i class="bi bi-stars"></i></span><div><strong><?= count($topRecommendations) > 0 ? 'Ready' : 'Discover' ?></strong><small>Personalised matches</small></div></article><article class="stat-card"><span class="stat-icon green-icon"><i class="bi bi-bookmark-heart"></i></span><div><strong><?= (int) $savedCountQuery->fetchColumn() ?></strong><small>Saved careers</small></div></article><article class="stat-card"><span class="stat-icon amber-icon"><i class="bi bi-check2-circle"></i></span><div><strong><?= (int) $roadmapCountQuery->fetchColumn() ?></strong><small>Steps completed</small></div></article></div>
            <?php if ($topRecommendations === []): ?>
                <div class="empty-state"><span class="empty-icon"><i class="bi bi-compass"></i></span><h2>Your career compass starts with you.</h2><p>Answer a few questions about your interests, skills and goals to get career matches made for you.</p><a class="primary-button" href="?page=assessment">Begin my discovery <i class="bi bi-arrow-right"></i></a></div>
            <?php else: ?>
                <div class="section-heading-row"><div><span class="eyebrow"><span class="eyebrow-dot"></span> BUILT AROUND YOUR ANSWERS</span><h2>Your top career directions</h2><p>These matches connect to the interests and abilities you shared.</p></div><a class="text-link" href="?page=results">See all matches <i class="bi bi-arrow-right"></i></a></div>
                <div class="career-grid"><?php foreach ($topRecommendations as $recommendedCareer): renderCareerCard($recommendedCareer, '?page=dashboard', $pdo, (int) $user['id'], $recommendedCareer); endforeach; ?></div>
            <?php endif; ?>
            <div class="dashboard-links"><a href="?page=explore"><i class="bi bi-search"></i><span><strong>Explore career library</strong><small>Look through all available career profiles.</small></span><i class="bi bi-arrow-right"></i></a><a href="?page=saved"><i class="bi bi-bookmark-heart"></i><span><strong>Review saved careers</strong><small>Return to the directions you want to explore.</small></span><i class="bi bi-arrow-right"></i></a></div>
        </section>
    <?php elseif ($page === 'assessment'): ?>
        <section class="assessment-wrap">
            <div class="assessment-heading"><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR CAREER DISCOVERY</span><h1>Let’s find your direction.</h1><p>There are no right or wrong answers. Choose what feels true for you.</p></div>
            <form method="post" id="assessment-form" class="assessment-card"><?= csrfField() ?><input type="hidden" name="action" value="save_assessment">
                <div class="progress-row"><div class="progress-copy"><strong>Step <span id="step-current">1</span> of 5</strong><span id="step-label">Your interests</span></div><span class="progress-percent" id="progress-percent">20%</span></div><div class="progress-track"><span id="progress-bar"></span></div>
                <section class="assessment-step is-active" data-step="0"><div class="question-heading"><span class="question-number">01</span><div><h2>What do you enjoy doing?</h2><p>Select all the things that genuinely interest you.</p></div></div><div class="choice-grid"><?php foreach ($options['interests'] as $option): ?><label class="choice-card"><input type="checkbox" name="interests[]" value="<?= e($option) ?>" <?= in_array($option, $answers['interests'] ?? [], true) ? 'checked' : '' ?>><span class="choice-check"><i class="bi bi-check2"></i></span><span><?= e($option) ?></span></label><?php endforeach; ?></div></section>
                <section class="assessment-step" data-step="1"><div class="question-heading"><span class="question-number">02</span><div><h2>What skills do you have or want to build?</h2><p>Include skills you are still developing. Growth counts.</p></div></div><div class="choice-grid"><?php foreach ($options['skills'] as $option): ?><label class="choice-card"><input type="checkbox" name="skills[]" value="<?= e($option) ?>" <?= in_array($option, $answers['skills'] ?? [], true) ? 'checked' : '' ?>><span class="choice-check"><i class="bi bi-check2"></i></span><span><?= e($option) ?></span></label><?php endforeach; ?></div></section>
                <section class="assessment-step" data-step="2"><div class="question-heading"><span class="question-number">03</span><div><h2>Which subjects appeal to you?</h2><p>Select subjects you have enjoyed or would like to explore.</p></div></div><div class="choice-grid"><?php foreach ($options['subjects'] as $option): ?><label class="choice-card"><input type="checkbox" name="subjects[]" value="<?= e($option) ?>" <?= in_array($option, $answers['subjects'] ?? [], true) ? 'checked' : '' ?>><span class="choice-check"><i class="bi bi-check2"></i></span><span><?= e($option) ?></span></label><?php endforeach; ?></div></section>
                <section class="assessment-step" data-step="3"><div class="question-heading"><span class="question-number">04</span><div><h2>What kind of work feels right to you?</h2><p>Choose environments where you think you could do your best.</p></div></div><div class="choice-grid"><?php foreach ($options['work_styles'] as $option): ?><label class="choice-card"><input type="checkbox" name="work_styles[]" value="<?= e($option) ?>" <?= in_array($option, $answers['work_styles'] ?? [], true) ? 'checked' : '' ?>><span class="choice-check"><i class="bi bi-check2"></i></span><span><?= e($option) ?></span></label><?php endforeach; ?></div></section>
                <section class="assessment-step" data-step="4"><div class="question-heading"><span class="question-number">05</span><div><h2>What would you like your work to help you do?</h2><p>Pick the ambitions that matter most to you right now.</p></div></div><div class="choice-grid"><?php foreach ($options['goals'] as $option): ?><label class="choice-card"><input type="checkbox" name="goals[]" value="<?= e($option) ?>" <?= in_array($option, $answers['goals'] ?? [], true) ? 'checked' : '' ?>><span class="choice-check"><i class="bi bi-check2"></i></span><span><?= e($option) ?></span></label><?php endforeach; ?></div><label class="stage-select">Your current stage<select name="career_stage" required><option value="">Choose one</option><option value="secondary_student" <?= $user['career_stage'] === 'secondary_student' ? 'selected' : '' ?>>Secondary school student</option><option value="college_student" <?= $user['career_stage'] === 'college_student' ? 'selected' : '' ?>>College student</option><option value="university_student" <?= $user['career_stage'] === 'university_student' ? 'selected' : '' ?>>University student</option><option value="graduate" <?= $user['career_stage'] === 'graduate' ? 'selected' : '' ?>>Graduate</option><option value="working_professional" <?= $user['career_stage'] === 'working_professional' ? 'selected' : '' ?>>Working professional</option><option value="career_changer" <?= $user['career_stage'] === 'career_changer' ? 'selected' : '' ?>>Exploring a career change</option></select></label></section>
                <div class="assessment-error" id="assessment-error" role="alert"></div><div class="assessment-controls"><button class="secondary-button" type="button" id="step-back" hidden><i class="bi bi-arrow-left"></i> Back</button><button class="primary-button" type="button" id="step-next">Next step <i class="bi bi-arrow-right"></i></button><button class="primary-button" type="submit" id="step-submit" hidden>See my career matches <i class="bi bi-stars"></i></button></div>
            </form>
        </section>
    <?php elseif ($page === 'results'): ?>
        <?php
        $resultsQuery = $pdo->prepare('SELECT c.*, r.score, r.matched_json FROM recommendations r JOIN careers c ON c.id = r.career_id WHERE r.user_id = :user_id ORDER BY r.score DESC, c.title LIMIT 5');
        $resultsQuery->execute(['user_id' => $user['id']]);
        $results = $resultsQuery->fetchAll();
        ?>
        <section class="content-page">
            <div class="page-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR PERSONAL CAREER MATCHES</span><h1>Possibilities that start with you.</h1><p>These career directions are ranked by how well their interests, skills, subjects, work styles and goals connect with your answers.</p><div class="notice-box"><i class="bi bi-info-circle"></i><span>A compatibility score is a starting point for exploration, not a qualification or promise of employment. Each career profile explains typical requirements.</span></div></div>
            <?php if ($results === []): ?><div class="empty-state"><h2>Take your assessment to see your matches.</h2><p>Your answers give us the details needed to personalise your career directions.</p><a class="primary-button" href="?page=assessment">Start assessment <i class="bi bi-arrow-right"></i></a></div><?php else: ?><div class="career-grid"><?php foreach ($results as $resultCareer): renderCareerCard($resultCareer, '?page=results', $pdo, (int) $user['id'], $resultCareer); endforeach; ?></div><?php endif; ?>
        </section>
    <?php elseif ($page === 'explore'): ?>
        <?php
        $query = 'SELECT * FROM careers WHERE featured = 1';
        $params = [];
        if ($search !== '') {
            $query .= ' AND (title LIKE :search OR summary LIKE :search OR category LIKE :search)';
            $params['search'] = '%' . mb_substr($search, 0, 80) . '%';
        }
        if ($selectedCategory !== '') {
            $query .= ' AND category = :category';
            $params['category'] = $selectedCategory;
        }
        $query .= ' ORDER BY category, title';
        $careerSearch = $pdo->prepare($query);
        $careerSearch->execute($params);
        $allCareers = $careerSearch->fetchAll();
        $categoryNames = $pdo->query('SELECT DISTINCT category FROM careers ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
        ?>
        <section class="content-page">
            <div class="page-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> CAREER LIBRARY</span><h1>Explore what’s possible.</h1><p>Learn what different careers involve, where opportunities may exist and how to start building relevant skills.</p></div>
            <form method="get" class="search-panel"><input type="hidden" name="page" value="explore"><label class="search-input"><i class="bi bi-search"></i><input name="search" value="<?= e($search) ?>" placeholder="Search careers or areas of work" maxlength="80" aria-label="Search careers"></label><select name="category" aria-label="Filter by career category"><option value="">All career areas</option><?php foreach ($categoryNames as $categoryName): ?><option value="<?= e((string) $categoryName) ?>" <?= $selectedCategory === $categoryName ? 'selected' : '' ?>><?= e((string) $categoryName) ?></option><?php endforeach; ?></select><button class="primary-button" type="submit">Search <i class="bi bi-arrow-right"></i></button></form>
            <?php if ($allCareers === []): ?><div class="empty-state"><h2>No matching careers found.</h2><p>Try a different search or clear the career-area filter.</p><a class="text-link" href="?page=explore">Clear filters <i class="bi bi-arrow-right"></i></a></div><?php else: ?><p class="results-count"><?= count($allCareers) ?> career<?= count($allCareers) === 1 ? '' : 's' ?> to explore</p><div class="career-grid"><?php foreach ($allCareers as $libraryCareer): renderCareerCard($libraryCareer, '?page=explore', $user === null ? null : $pdo, $user === null ? null : (int) $user['id']); endforeach; ?></div><?php endif; ?>
        </section>
    <?php elseif ($page === 'jobs'): ?>
        <section class="content-page jobs-page">
            <div class="page-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> KENYAN JOB SEARCH SITES</span><h1>Explore current vacancies.</h1><p>Visit these job boards to search vacancies in Kenya. Each website manages its own listings, applications and closing dates.</p></div>
            <div class="jobs-notice"><i class="bi bi-info-circle" aria-hidden="true"></i><span>These links take you to external job websites. Listings can change or close at any time, so confirm the details and application requirements on the original site.</span></div>
            <div class="job-board-grid">
                <article class="job-board-card">
                    <span class="job-board-icon"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                    <span class="job-board-label">JOB SEARCH PLATFORM</span>
                    <h2>Fuzu Kenya</h2>
                    <p>Explore jobs and career opportunities on Fuzu’s Kenya website.</p>
                    <a class="primary-button" href="https://www.fuzu.com/kenya" target="_blank" rel="noopener noreferrer">Visit Fuzu Kenya <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                    <span class="job-board-domain">fuzu.com/kenya</span>
                </article>
                <article class="job-board-card">
                    <span class="job-board-icon job-board-violet"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                    <span class="job-board-label">RECRUITMENT &amp; JOBS</span>
                    <h2>Corporate Staffing</h2>
                    <p>Browse job vacancies published by Corporate Staffing Services in Kenya.</p>
                    <a class="primary-button" href="https://www.corporatestaffing.co.ke/jobs/" target="_blank" rel="noopener noreferrer">Browse vacancies <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                    <span class="job-board-domain">corporatestaffing.co.ke/jobs</span>
                </article>
                <article class="job-board-card">
                    <span class="job-board-icon job-board-green"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <span class="job-board-label">KENYAN JOB LISTINGS</span>
                    <h2>MyJobsInKenya</h2>
                    <p>Search job opportunities listed on the MyJobsInKenya website.</p>
                    <a class="primary-button" href="https://www.myjobsinkenya.com/" target="_blank" rel="noopener noreferrer">Visit MyJobsInKenya <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                    <span class="job-board-domain">myjobsinkenya.com</span>
                </article>
            </div>
            <div class="jobs-career-cta"><span><strong>Not sure which roles to search for?</strong><small>Explore your interests and see career paths that may suit you.</small></span><a class="text-link" href="?page=explore">Explore career profiles <i class="bi bi-arrow-right"></i></a></div>
        </section>
    <?php elseif ($page === 'career'): ?>
        <?php if ($career === null): http_response_code(404); ?><section class="empty-state"><h1>Career not found</h1><p>That profile may have moved. Explore the career library to find another direction.</p><a class="primary-button" href="?page=explore">Explore careers <i class="bi bi-arrow-right"></i></a></section>
        <?php else: ?>
            <?php $careerSkills = decodeList($career['skills_json']); $careerInterests = decodeList($career['interests_json']); ?>
            <section class="career-profile">
                <a class="back-link" href="?page=explore"><i class="bi bi-arrow-left"></i> Career library</a>
                <div class="profile-hero"><span class="career-icon profile-icon"><i class="bi <?= e(appIcon($career['category'])) ?>"></i></span><span class="eyebrow"><span class="eyebrow-dot"></span> <?= e($career['category']) ?></span><h1><?= e($career['title']) ?></h1><p><?= e($career['summary']) ?></p><div class="hero-buttons"><a class="primary-button" href="<?= $user !== null ? '?page=assessment' : '?page=register' ?>">Find my career matches <i class="bi bi-arrow-right"></i></a><?php if ($user !== null): ?><a class="secondary-button" href="?page=roadmap&amp;career=<?= (int) $career['id'] ?>"><i class="bi bi-signpost-split"></i> View roadmap</a><form method="post" class="inline-form"><?= csrfField() ?><input type="hidden" name="action" value="toggle_save"><input type="hidden" name="career_id" value="<?= (int) $career['id'] ?>"><input type="hidden" name="return_to" value="?page=explore"><button class="secondary-button" type="submit"><i class="bi bi-bookmark"></i> Save career</button></form><?php endif; ?></div></div>
                <div class="profile-grid"><article class="profile-card profile-wide"><span class="eyebrow">THE ROLE</span><h2>What this career involves</h2><p><?= e($career['responsibilities']) ?></p></article><article class="profile-card"><span class="eyebrow">GETTING STARTED</span><h2>Typical qualifications</h2><p><?= e($career['qualifications']) ?></p></article><article class="profile-card"><span class="eyebrow">YOUR TRANSFERABLE STRENGTHS</span><h2>Useful skills</h2><div class="match-tags"><?php foreach ($careerSkills as $skill): ?><span><?= e((string) $skill) ?></span><?php endforeach; ?></div></article><article class="profile-card"><span class="eyebrow">WHERE TO LOOK</span><h2>Employment opportunities</h2><p><?= e($career['opportunities']) ?></p></article><article class="profile-card"><span class="eyebrow">REALISTIC EXPECTATIONS</span><h2>Challenges to consider</h2><p><?= e($career['challenges']) ?></p></article><article class="profile-card"><span class="eyebrow">NEXT CHAPTER</span><h2>Career growth</h2><p><?= e($career['growth']) ?></p></article><article class="profile-card"><span class="eyebrow">EXPLORE YOUR FIT</span><h2>Related interests</h2><div class="match-tags"><?php foreach ($careerInterests as $interest): ?><span><?= e((string) $interest) ?></span><?php endforeach; ?></div></article></div>
            </section>
        <?php endif; ?>
    <?php elseif ($page === 'saved'): ?>
        <?php $savedQuery = $pdo->prepare('SELECT c.* FROM saved_careers s JOIN careers c ON c.id = s.career_id WHERE s.user_id = :user_id ORDER BY s.saved_at DESC'); $savedQuery->execute(['user_id' => $user['id']]); $savedCareers = $savedQuery->fetchAll(); ?>
        <section class="content-page"><div class="page-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR SHORTLIST</span><h1>Saved careers.</h1><p>Keep interesting directions close while you explore what feels right for you.</p></div><?php if ($savedCareers === []): ?><div class="empty-state"><span class="empty-icon"><i class="bi bi-bookmark-heart"></i></span><h2>Your shortlist starts here.</h2><p>Save a career from its profile or explore the career library to find an interesting path.</p><a class="primary-button" href="?page=explore">Explore careers <i class="bi bi-arrow-right"></i></a></div><?php else: ?><div class="career-grid"><?php foreach ($savedCareers as $savedCareer): renderCareerCard($savedCareer, '?page=saved', $pdo, (int) $user['id']); endforeach; ?></div><?php endif; ?></section>
    <?php elseif ($page === 'roadmap'): ?>
        <?php if ($career === null): http_response_code(404); ?><div class="empty-state"><h1>Career roadmap not found</h1><a class="primary-button" href="?page=results">View your matches <i class="bi bi-arrow-right"></i></a></div>
        <?php else: $steps = decodeList($career['roadmaps_json']); $progressQuery = $pdo->prepare('SELECT step_number FROM roadmap_progress WHERE user_id = :user_id AND career_id = :career_id'); $progressQuery->execute(['user_id' => $user['id'], 'career_id' => $career['id']]); $completedSteps = array_map('intval', $progressQuery->fetchAll(PDO::FETCH_COLUMN)); $doneCount = count($completedSteps); ?>
            <section class="content-page"><a class="back-link" href="?page=career&amp;slug=<?= rawurlencode($career['slug']) ?>"><i class="bi bi-arrow-left"></i> <?= e($career['title']) ?></a><div class="page-intro"><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR DEVELOPMENT PLAN</span><h1>A practical roadmap to <?= e($career['title']) ?>.</h1><p>Work through these suggested steps at your own pace. Your progress is saved to your account.</p></div><div class="roadmap-summary"><strong><?= $doneCount ?> of <?= count($steps) ?> steps complete</strong><span class="roadmap-track"><i style="width: <?= count($steps) > 0 ? (int) round($doneCount * 100 / count($steps)) : 0 ?>%"></i></span></div><ol class="roadmap-list"><?php foreach ($steps as $stepNumber => $stepText): $isDone = in_array($stepNumber, $completedSteps, true); ?><li class="roadmap-item <?= $isDone ? 'is-complete' : '' ?>"><span class="roadmap-number"><?= sprintf('%02d', $stepNumber + 1) ?></span><div><h2><?= e((string) $stepText) ?></h2><p>Take this step when it makes sense for your goals and current experience.</p></div><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="toggle_progress"><input type="hidden" name="career_id" value="<?= (int) $career['id'] ?>"><input type="hidden" name="step_number" value="<?= (int) $stepNumber ?>"><button class="progress-toggle" type="submit"><?= $isDone ? '<i class="bi bi-check-circle-fill"></i> Completed' : '<i class="bi bi-circle"></i> Mark complete' ?></button></form></li><?php endforeach; ?></ol></section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<footer class="app-footer"><div class="footer-inner"><a class="app-brand footer-brand" href="?page=home"><span class="brand-mark"><i class="bi bi-signpost-2-fill"></i></span><span>careers <span>react app</span><small>FIND YOUR NEXT DIRECTION</small></span></a><p>Discover Your Interests. Identify Your Potential. Build Your Future.</p><span>Starting in Kenya. Open to the world.</span></div></footer>
<script src="assets/js/app.js" defer></script>
<script src="assets/js/assessment.js" defer></script>
</body>
</html>
