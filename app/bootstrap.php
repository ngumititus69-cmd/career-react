<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

$storageDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0750, true) && !is_dir($storageDirectory)) {
    throw new RuntimeException('Career Road could not create its private storage directory.');
}

require dirname(__DIR__) . '/config/database.php';
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL COLLATE NOCASE UNIQUE,
        password_hash TEXT NOT NULL,
        career_stage TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS assessments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        answers_json TEXT NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS careers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        summary TEXT NOT NULL,
        responsibilities TEXT NOT NULL,
        qualifications TEXT NOT NULL,
        skills_json TEXT NOT NULL,
        interests_json TEXT NOT NULL,
        subjects_json TEXT NOT NULL,
        work_styles_json TEXT NOT NULL,
        goals_json TEXT NOT NULL,
        opportunities TEXT NOT NULL,
        challenges TEXT NOT NULL,
        growth TEXT NOT NULL,
        roadmaps_json TEXT NOT NULL,
        featured INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS recommendations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        career_id INTEGER NOT NULL,
        score INTEGER NOT NULL,
        matched_json TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (user_id, career_id),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS saved_careers (
        user_id INTEGER NOT NULL,
        career_id INTEGER NOT NULL,
        saved_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, career_id),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS roadmap_progress (
        user_id INTEGER NOT NULL,
        career_id INTEGER NOT NULL,
        step_number INTEGER NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, career_id, step_number),
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
    );"
);

$careerCount = (int) $pdo->query('SELECT COUNT(*) FROM careers')->fetchColumn();
if ($careerCount === 0) {
    $careers = [
        [
            'slug' => 'software-developer',
            'title' => 'Software Developer',
            'category' => 'Technology',
            'summary' => 'Designs and builds software that solves practical problems for people and organisations.',
            'responsibilities' => 'Plan and build applications; write and test code; fix issues; collaborate with a team; improve software based on user feedback.',
            'qualifications' => 'A diploma or degree in computing can help. Many employers also value demonstrable programming ability, a practical project portfolio and continued learning.',
            'skills' => ['Programming', 'Problem solving', 'Communication', 'Analytical thinking'],
            'interests' => ['Building technology', 'Solving problems', 'Creating things'],
            'subjects' => ['Mathematics', 'Computer Studies', 'Physics'],
            'work_styles' => ['Independent focus', 'Collaborative teamwork', 'Flexible schedule', 'Remote or hybrid'],
            'goals' => ['Build digital products', 'Solve community problems', 'Start a business'],
            'opportunities' => 'Technology firms, banks, telecommunications, government, consultancies, and software teams across many industries.',
            'challenges' => 'Technology changes quickly; finding the cause of software bugs can take patience; teams must balance quality with deadlines.',
            'growth' => 'Junior developer → software developer → senior developer → technical lead, architect or engineering manager.',
            'roadmaps' => [
                'Practise programming foundations: variables, decisions, loops and functions.',
                'Build responsive pages with semantic HTML and accessible CSS.',
                'Add JavaScript interactions and validate real form input.',
                'Learn server-side PHP and connect forms to a relational database.',
                'Complete and document a small project for a portfolio.',
            ],
        ],
        [
            'slug' => 'data-analyst',
            'title' => 'Data Analyst',
            'category' => 'Technology',
            'summary' => 'Finds useful patterns in information and explains them so teams can make better decisions.',
            'responsibilities' => 'Collect and organise data; check its quality; find trends; create clear charts and reports; explain findings to stakeholders.',
            'qualifications' => 'Study in mathematics, statistics, business or computing is useful. Spreadsheet, SQL and data-visualisation skills are commonly requested.',
            'skills' => ['Data analysis', 'Analytical thinking', 'Problem solving', 'Communication'],
            'interests' => ['Working with data', 'Solving problems', 'Researching ideas'],
            'subjects' => ['Mathematics', 'Computer Studies', 'Business Studies'],
            'work_styles' => ['Independent focus', 'Collaborative teamwork', 'Structured work', 'Remote or hybrid'],
            'goals' => ['Work with data', 'Solve community problems', 'Build digital products'],
            'opportunities' => 'Banks, research organisations, retailers, public agencies, NGOs, telecommunications and technology companies.',
            'challenges' => 'Data may be incomplete or inconsistent; accurate analysis and clear communication are equally important.',
            'growth' => 'Analyst → senior analyst → business-intelligence specialist, data scientist or analytics manager.',
            'roadmaps' => [
                'Strengthen spreadsheet and basic statistics foundations.',
                'Practise cleaning and summarising a small real dataset.',
                'Learn SQL to query information stored in a database.',
                'Create useful charts and a short, clear findings report.',
                'Publish a documented analysis project in a portfolio.',
            ],
        ],
        [
            'slug' => 'ui-ux-designer',
            'title' => 'UI/UX Designer',
            'category' => 'Creative work',
            'summary' => 'Makes digital products easier to understand and more useful through research, structure and visual design.',
            'responsibilities' => 'Understand what people need; sketch ideas; design screen flows; prototype interfaces; gather feedback; work with developers.',
            'qualifications' => 'Design training is helpful, but employers also look for thoughtful case studies, a portfolio and user-centred problem solving.',
            'skills' => ['Visual design', 'Communication', 'Problem solving', 'Research'],
            'interests' => ['Creating visual designs', 'Helping people', 'Building technology', 'Creating things'],
            'subjects' => ['Art and Design', 'Computer Studies', 'English'],
            'work_styles' => ['Independent focus', 'Collaborative teamwork', 'Flexible schedule', 'Remote or hybrid'],
            'goals' => ['Build digital products', 'Help people directly', 'Start a business'],
            'opportunities' => 'Software companies, design studios, digital agencies, financial services, NGOs and in-house product teams.',
            'challenges' => 'Design must serve users and business needs; feedback and changing requirements are a normal part of the work.',
            'growth' => 'Junior designer → product designer → senior designer → design lead, researcher or product manager.',
            'roadmaps' => [
                'Observe an everyday task and write down where it feels difficult.',
                'Learn basic visual hierarchy, typography and colour contrast.',
                'Sketch a simpler screen flow that addresses the user problem.',
                'Make a clickable prototype and ask a few people to try it.',
                'Write up the problem, your decisions and feedback as a case study.',
            ],
        ],
        [
            'slug' => 'digital-marketer',
            'title' => 'Digital Marketer',
            'category' => 'Business',
            'summary' => 'Helps organisations reach the right people through useful content, campaigns and digital channels.',
            'responsibilities' => 'Plan campaigns; write and schedule content; understand audiences; review performance; improve communication across online channels.',
            'qualifications' => 'Marketing, business or communication studies can be useful. Employers value clear writing, digital-platform knowledge and examples of results.',
            'skills' => ['Communication', 'Content creation', 'Data analysis', 'Organisation'],
            'interests' => ['Writing and communication', 'Creating visual designs', 'Working with data', 'Starting projects'],
            'subjects' => ['Business Studies', 'English', 'Art and Design'],
            'work_styles' => ['Collaborative teamwork', 'Flexible schedule', 'Structured work', 'Remote or hybrid'],
            'goals' => ['Grow a business', 'Start a business', 'Build digital products'],
            'opportunities' => 'Businesses, creative agencies, ecommerce, non-profits, startups and communications teams.',
            'challenges' => 'Platforms and audience behaviour change; effective campaigns require testing, ethical communication and learning from results.',
            'growth' => 'Marketing assistant → digital marketer → campaign strategist, growth marketer or marketing lead.',
            'roadmaps' => [
                'Choose a community project or small business and understand its audience.',
                'Practise writing short, clear posts with an accessible visual.',
                'Learn basic search, email and social-media marketing concepts.',
                'Plan a small campaign with a clear goal and schedule.',
                'Review results and build a short portfolio case study.',
            ],
        ],
        [
            'slug' => 'teacher-educator',
            'title' => 'Teacher & Educator',
            'category' => 'Education',
            'summary' => 'Helps people learn by explaining ideas, adapting lessons and creating supportive learning environments.',
            'responsibilities' => 'Plan lessons; explain subject concepts; check understanding; give constructive feedback; support different learning needs.',
            'qualifications' => 'Teaching roles generally require recognised education qualifications and registration under the rules applicable to the role and country.',
            'skills' => ['Communication', 'Organisation', 'Research', 'Teamwork'],
            'interests' => ['Helping people', 'Researching ideas', 'Writing and communication'],
            'subjects' => ['English', 'Mathematics', 'Biology'],
            'work_styles' => ['Collaborative teamwork', 'Structured work', 'Hands-on practice'],
            'goals' => ['Help people directly', 'Solve community problems', 'Share knowledge'],
            'opportunities' => 'Schools, colleges, training organisations, community programmes and education-technology projects.',
            'challenges' => 'Learners have different needs; preparation, patience and supportive classroom communication matter every day.',
            'growth' => 'Classroom educator → subject specialist → department lead, instructional designer or education leader.',
            'roadmaps' => [
                'Explore the subjects and age groups you most enjoy supporting.',
                'Practise explaining a topic in a short lesson for a beginner.',
                'Research teacher-training and registration requirements where you plan to work.',
                'Ask an educator about placements, mentoring and classroom experience.',
                'Build a record of lesson plans, teaching practice and feedback.',
            ],
        ],
        [
            'slug' => 'entrepreneur',
            'title' => 'Entrepreneur',
            'category' => 'Entrepreneurship',
            'summary' => 'Turns a useful idea into a venture by understanding customers, testing solutions and managing uncertainty.',
            'responsibilities' => 'Spot a need; talk to potential customers; test ideas; manage finances and operations; learn from outcomes.',
            'qualifications' => 'There is no single required qualification for starting a venture. Business, financial and industry knowledge can help; local legal requirements still apply.',
            'skills' => ['Problem solving', 'Communication', 'Organisation', 'Data analysis'],
            'interests' => ['Starting projects', 'Solving problems', 'Improving processes', 'Creating things'],
            'subjects' => ['Business Studies', 'Mathematics', 'English'],
            'work_styles' => ['Flexible schedule', 'Independent focus', 'Hands-on practice'],
            'goals' => ['Start a business', 'Grow a business', 'Solve community problems'],
            'opportunities' => 'Self-employment, small business, social enterprise, creative ventures and technology startups.',
            'challenges' => 'Income may be uncertain; customer needs, costs and business assumptions should be tested before investing heavily.',
            'growth' => 'Idea validation → small pilot → sustainable operations → responsible growth or new ventures.',
            'roadmaps' => [
                'Choose a real problem experienced by people you can talk to.',
                'Interview potential customers before deciding on a solution.',
                'Estimate costs and test a small, low-cost version of the idea.',
                'Track feedback, spending and results in a simple business plan.',
                'Learn relevant licensing and tax requirements before scaling.',
            ],
        ],
        [
            'slug' => 'agricultural-entrepreneur',
            'title' => 'Agriculture & Agribusiness Specialist',
            'category' => 'Agriculture',
            'summary' => 'Applies practical, scientific and business knowledge to strengthen food and agricultural systems.',
            'responsibilities' => 'Support crop or livestock production; understand supply chains; apply relevant technology; monitor quality and costs; share practical advice.',
            'qualifications' => 'Agricultural training is useful for many specialist roles. Entry requirements differ between agribusiness, extension and technical positions.',
            'skills' => ['Research', 'Problem solving', 'Organisation', 'Data analysis'],
            'interests' => ['Working outdoors', 'Improving processes', 'Solving problems', 'Starting projects'],
            'subjects' => ['Biology', 'Chemistry', 'Mathematics', 'Geography'],
            'work_styles' => ['Hands-on practice', 'Collaborative teamwork', 'Flexible schedule'],
            'goals' => ['Solve community problems', 'Grow a business', 'Help people directly'],
            'opportunities' => 'Farms, cooperatives, food businesses, agricultural technology, research, public extension and development programmes.',
            'challenges' => 'Work is affected by weather, market conditions and local resources; practical knowledge must fit local conditions.',
            'growth' => 'Field assistant → technical or agribusiness specialist → operations lead, consultant or venture founder.',
            'roadmaps' => [
                'Explore local crops, food systems and challenges in your community.',
                'Learn safe and sustainable production practices from a trusted source.',
                'Visit a farm, cooperative or agribusiness and ask about daily work.',
                'Track a small production or market activity with simple records.',
                'Identify relevant training, mentors and local business requirements.',
            ],
        ],
        [
            'slug' => 'community-health-worker',
            'title' => 'Community Health Worker',
            'category' => 'Healthcare',
            'summary' => 'Connects communities with health information, preventive support and appropriate health services.',
            'responsibilities' => 'Share approved health information; listen to community needs; connect people with qualified services; maintain privacy and records.',
            'qualifications' => 'Training, certification and scope-of-practice requirements vary by location and employer. This role does not replace licensed clinical care.',
            'skills' => ['Communication', 'Teamwork', 'Organisation', 'Research'],
            'interests' => ['Helping people', 'Solving community problems', 'Researching ideas'],
            'subjects' => ['Biology', 'English', 'Chemistry'],
            'work_styles' => ['Collaborative teamwork', 'Hands-on practice', 'Structured work'],
            'goals' => ['Help people directly', 'Solve community problems', 'Share knowledge'],
            'opportunities' => 'Community health programmes, public health organisations, NGOs and local service networks.',
            'challenges' => 'The work requires confidentiality, cultural sensitivity, clear boundaries and referral to qualified professionals when needed.',
            'growth' => 'Community health worker → programme coordinator, public health training or further regulated health study.',
            'roadmaps' => [
                'Learn about local community health roles and their legal scope.',
                'Practise respectful listening and clear, non-judgmental communication.',
                'Find recognised training and safeguarding requirements in your area.',
                'Ask a reputable health organisation about supervised volunteering.',
                'Keep professional boundaries and refer clinical questions to licensed staff.',
            ],
        ],
        [
            'slug' => 'graphic-designer',
            'title' => 'Graphic Designer',
            'category' => 'Creative work',
            'summary' => 'Creates visual materials that help brands and organisations communicate their ideas clearly.',
            'responsibilities' => 'Develop design concepts; create digital or print layouts; respond to feedback; maintain visual consistency across projects.',
            'qualifications' => 'Design study can help; a strong portfolio, attention to detail and knowledge of design software are often valued.',
            'skills' => ['Visual design', 'Content creation', 'Communication', 'Organisation'],
            'interests' => ['Creating visual designs', 'Creating things', 'Writing and communication'],
            'subjects' => ['Art and Design', 'English', 'Business Studies'],
            'work_styles' => ['Independent focus', 'Collaborative teamwork', 'Flexible schedule'],
            'goals' => ['Build digital products', 'Grow a business', 'Start a business'],
            'opportunities' => 'Design agencies, publishers, technology companies, NGOs, small businesses and freelance work.',
            'challenges' => 'Projects can involve tight deadlines and changing feedback; the final design must meet the audience and brief.',
            'growth' => 'Junior designer → senior designer → art director, creative lead or independent studio owner.',
            'roadmaps' => [
                'Practise composition, accessible colour contrast and typography.',
                'Choose a local communication challenge and research its audience.',
                'Create a few alternatives and ask others which is clearest and why.',
                'Build a polished sample for digital and print use.',
                'Document your process and assemble a small portfolio.',
            ],
        ],
    ];

    $insertCareer = $pdo->prepare(
        'INSERT OR IGNORE INTO careers
        (slug, title, category, summary, responsibilities, qualifications, skills_json, interests_json, subjects_json, work_styles_json, goals_json, opportunities, challenges, growth, roadmaps_json)
        VALUES
        (:slug, :title, :category, :summary, :responsibilities, :qualifications, :skills, :interests, :subjects, :work_styles, :goals, :opportunities, :challenges, :growth, :roadmaps)'
    );

    foreach ($careers as $career) {
        $insertCareer->execute([
            'slug' => $career['slug'],
            'title' => $career['title'],
            'category' => $career['category'],
            'summary' => $career['summary'],
            'responsibilities' => $career['responsibilities'],
            'qualifications' => $career['qualifications'],
            'skills' => json_encode($career['skills'], JSON_THROW_ON_ERROR),
            'interests' => json_encode($career['interests'], JSON_THROW_ON_ERROR),
            'subjects' => json_encode($career['subjects'], JSON_THROW_ON_ERROR),
            'work_styles' => json_encode($career['work_styles'], JSON_THROW_ON_ERROR),
            'goals' => json_encode($career['goals'], JSON_THROW_ON_ERROR),
            'opportunities' => $career['opportunities'],
            'challenges' => $career['challenges'],
            'growth' => $career['growth'],
            'roadmaps' => json_encode($career['roadmaps'], JSON_THROW_ON_ERROR),
        ]);
    }
}
