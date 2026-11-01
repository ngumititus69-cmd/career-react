USE career_road;

INSERT IGNORE INTO education_levels (name, sort_order) VALUES
    ('Secondary school', 1),
    ('Certificate', 2),
    ('Diploma', 3),
    ('Bachelor''s degree', 4),
    ('Postgraduate degree', 5),
    ('Other', 6);

INSERT IGNORE INTO career_categories (name, slug, description, sort_order) VALUES
    ('Technology', 'technology', 'Careers building and supporting digital products and systems.', 1),
    ('Business', 'business', 'Careers focused on organisations, markets, and operations.', 2),
    ('Finance', 'finance', 'Careers involving money, financial planning, and analysis.', 3),
    ('Healthcare', 'healthcare', 'Careers supporting health, treatment, and wellbeing.', 4),
    ('Engineering', 'engineering', 'Careers applying science and mathematics to practical problems.', 5),
    ('Education', 'education', 'Careers supporting learning, teaching, and training.', 6),
    ('Law', 'law', 'Careers involving legal advice, advocacy, and justice.', 7),
    ('Agriculture', 'agriculture', 'Careers improving food systems and agricultural production.', 8),
    ('Media', 'media', 'Careers creating and distributing information and stories.', 9),
    ('Creative industries', 'creative-industries', 'Careers in design, art, and creative production.', 10),
    ('Science', 'science', 'Careers using research and evidence to understand the world.', 11),
    ('Entrepreneurship', 'entrepreneurship', 'Careers focused on creating and growing ventures.', 12);

INSERT IGNORE INTO academic_subjects (name) VALUES
    ('Mathematics'),
    ('Computer Studies'),
    ('Physics'),
    ('Business Studies'),
    ('English'),
    ('Art and Design'),
    ('Biology'),
    ('Chemistry'),
    ('Geography');

INSERT IGNORE INTO interests (name, description) VALUES
    ('Solving problems', 'Finding logical solutions to challenging problems.'),
    ('Building technology', 'Creating software, websites, or digital tools.'),
    ('Working with data', 'Finding patterns and meaning in information.'),
    ('Helping people', 'Supporting other people to learn, grow, or solve problems.'),
    ('Creating visual designs', 'Communicating ideas through visual work.'),
    ('Starting projects', 'Taking an idea and turning it into something practical.'),
    ('Researching ideas', 'Asking questions and learning through investigation.'),
    ('Organising work', 'Planning tasks, people, and resources.'),
    ('Writing and communication', 'Sharing information clearly through words and conversation.'),
    ('Improving processes', 'Making systems and ways of working more effective.');

INSERT IGNORE INTO skills (name, skill_type) VALUES
    ('Analytical thinking', 'interpersonal'),
    ('Communication', 'interpersonal'),
    ('Problem solving', 'interpersonal'),
    ('Teamwork', 'interpersonal'),
    ('Attention to detail', 'interpersonal'),
    ('HTML and CSS', 'technical'),
    ('JavaScript', 'technical'),
    ('Programming fundamentals', 'technical'),
    ('Data analysis', 'technical'),
    ('Visual design', 'creative'),
    ('Project planning', 'other'),
    ('Research', 'other');

INSERT IGNORE INTO personality_traits (name, description) VALUES
    ('Curiosity', 'Enjoys asking questions and learning how things work.'),
    ('Persistence', 'Keeps working through difficult or unfamiliar tasks.'),
    ('Creativity', 'Enjoys generating original ideas and approaches.'),
    ('Empathy', 'Pays attention to other people''s needs and perspectives.'),
    ('Organisation', 'Prefers structured plans and orderly work.'),
    ('Adaptability', 'Can adjust when priorities or circumstances change.');

INSERT IGNORE INTO work_preferences (name) VALUES
    ('Independent focus time'),
    ('Collaborative teamwork'),
    ('Flexible working hours'),
    ('Clear structure and routines'),
    ('Practical hands-on work'),
    ('Remote or hybrid work');

INSERT IGNORE INTO industries (name) VALUES
    ('Information technology'),
    ('Financial services'),
    ('Healthcare'),
    ('Education'),
    ('Government and public service'),
    ('Agriculture and food'),
    ('Media and entertainment'),
    ('Retail and commerce'),
    ('Manufacturing'),
    ('Consulting');

INSERT IGNORE INTO careers (
    category_id,
    title,
    slug,
    summary,
    responsibilities,
    typical_qualifications,
    work_environment,
    employment_opportunities,
    challenges,
    growth_opportunities,
    professional_certifications,
    is_published
)
SELECT
    category.id,
    'Software Developer',
    'software-developer',
    'Designs, builds, tests, and maintains software used by people and organisations.',
    'Understand user needs; write and review code; test software; fix defects; collaborate with designers and other developers; document technical work.',
    'A relevant diploma or degree can help, but practical programming ability and a portfolio of projects are also important. Requirements differ between employers.',
    'Often office-based or remote, working independently on focused tasks and collaborating with a development team.',
    'Technology companies, banks, telecommunications firms, government, consultancies, and organisations with internal software teams.',
    'Technology changes quickly; debugging can take time; projects involve balancing quality, deadlines, and changing requirements.',
    'May progress to senior developer, technical lead, software architect, engineering manager, or independent consultant.',
    'Industry certifications are optional for many developer roles; practical projects and demonstrable skills are commonly valued.',
    TRUE
FROM career_categories AS category
WHERE category.slug = 'technology'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    is_published = VALUES(is_published);

INSERT IGNORE INTO careers (
    category_id,
    title,
    slug,
    summary,
    responsibilities,
    typical_qualifications,
    work_environment,
    employment_opportunities,
    challenges,
    growth_opportunities,
    professional_certifications,
    is_published
)
SELECT
    category.id,
    'Data Analyst',
    'data-analyst',
    'Turns data into useful findings that help organisations make informed decisions.',
    'Collect and clean data; analyse datasets; prepare reports and visualisations; explain findings; work with stakeholders to clarify questions.',
    'A background in statistics, mathematics, business, or information technology is useful. Employers often value spreadsheet, SQL, and data visualisation skills.',
    'Usually office-based or hybrid, combining focused analysis with discussions and presentations.',
    'Banks, research organisations, consultancies, retailers, public agencies, telecommunications, and technology companies.',
    'Data may be incomplete or inconsistent; explaining technical findings clearly to different audiences is essential.',
    'May progress to senior analyst, business intelligence specialist, data scientist, or analytics manager.',
    'Tool-specific data analytics certifications can support learning; requirements vary by employer.',
    TRUE
FROM career_categories AS category
WHERE category.slug = 'technology'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    is_published = VALUES(is_published);

INSERT IGNORE INTO careers (
    category_id,
    title,
    slug,
    summary,
    responsibilities,
    typical_qualifications,
    work_environment,
    employment_opportunities,
    challenges,
    growth_opportunities,
    professional_certifications,
    is_published
)
SELECT
    category.id,
    'Graphic Designer',
    'graphic-designer',
    'Creates visual materials that help brands and organisations communicate ideas.',
    'Develop design concepts; create digital and print layouts; prepare graphics; respond to feedback; maintain visual consistency across projects.',
    'A design qualification can help, but employers also assess a strong portfolio, visual communication, and familiarity with industry design tools.',
    'Work may be in a design studio, communications team, agency, or as a freelance designer, with regular feedback from clients and colleagues.',
    'Advertising agencies, publishers, technology companies, NGOs, small businesses, and in-house communications teams.',
    'Projects can involve tight deadlines, changing feedback, and the challenge of balancing creativity with a client brief.',
    'May progress to senior designer, art director, creative lead, or independent design business owner.',
    'A portfolio is often more important than a formal certification; tool certifications may be useful for specific roles.',
    TRUE
FROM career_categories AS category
WHERE category.slug = 'creative-industries'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    is_published = VALUES(is_published);

INSERT IGNORE INTO career_interest_matches (career_id, interest_id, weight)
SELECT career.id, interest.id, 5
FROM careers AS career
JOIN interests AS interest ON interest.name IN (
    'Solving problems',
    'Building technology',
    'Starting projects'
)
WHERE career.slug = 'software-developer';

INSERT IGNORE INTO career_interest_matches (career_id, interest_id, weight)
SELECT career.id, interest.id, 5
FROM careers AS career
JOIN interests AS interest ON interest.name IN (
    'Solving problems',
    'Working with data',
    'Researching ideas'
)
WHERE career.slug = 'data-analyst';

INSERT IGNORE INTO career_interest_matches (career_id, interest_id, weight)
SELECT career.id, interest.id, 5
FROM careers AS career
JOIN interests AS interest ON interest.name IN (
    'Creating visual designs',
    'Starting projects',
    'Writing and communication'
)
WHERE career.slug = 'graphic-designer';

INSERT IGNORE INTO career_skill_requirements (career_id, skill_id, required_level, importance)
SELECT career.id, skill.id, 3, 5
FROM careers AS career
JOIN skills AS skill ON skill.name IN (
    'Programming fundamentals',
    'Problem solving',
    'Communication'
)
WHERE career.slug = 'software-developer';

INSERT IGNORE INTO career_skill_requirements (career_id, skill_id, required_level, importance)
SELECT career.id, skill.id, 3, 5
FROM careers AS career
JOIN skills AS skill ON skill.name IN (
    'Data analysis',
    'Analytical thinking',
    'Communication'
)
WHERE career.slug = 'data-analyst';

INSERT IGNORE INTO career_skill_requirements (career_id, skill_id, required_level, importance)
SELECT career.id, skill.id, 3, 5
FROM careers AS career
JOIN skills AS skill ON skill.name IN (
    'Visual design',
    'Communication',
    'Attention to detail'
)
WHERE career.slug = 'graphic-designer';

INSERT IGNORE INTO career_personality_requirements (career_id, trait_id, preferred_rating, importance)
SELECT career.id, trait.id, 4, 3
FROM careers AS career
JOIN personality_traits AS trait ON trait.name IN ('Curiosity', 'Persistence')
WHERE career.slug = 'software-developer';

INSERT IGNORE INTO career_personality_requirements (career_id, trait_id, preferred_rating, importance)
SELECT career.id, trait.id, 4, 3
FROM careers AS career
JOIN personality_traits AS trait ON trait.name IN ('Curiosity', 'Organisation')
WHERE career.slug = 'data-analyst';

INSERT IGNORE INTO career_personality_requirements (career_id, trait_id, preferred_rating, importance)
SELECT career.id, trait.id, 4, 3
FROM careers AS career
JOIN personality_traits AS trait ON trait.name IN ('Creativity', 'Adaptability')
WHERE career.slug = 'graphic-designer';

INSERT IGNORE INTO career_work_preferences (career_id, preference_id, importance)
SELECT career.id, preference.id, 3
FROM careers AS career
JOIN work_preferences AS preference ON preference.name IN (
    'Independent focus time',
    'Collaborative teamwork',
    'Remote or hybrid work'
)
WHERE career.slug = 'software-developer';

INSERT IGNORE INTO career_work_preferences (career_id, preference_id, importance)
SELECT career.id, preference.id, 3
FROM careers AS career
JOIN work_preferences AS preference ON preference.name IN (
    'Independent focus time',
    'Collaborative teamwork',
    'Clear structure and routines'
)
WHERE career.slug = 'data-analyst';

INSERT IGNORE INTO career_work_preferences (career_id, preference_id, importance)
SELECT career.id, preference.id, 3
FROM careers AS career
JOIN work_preferences AS preference ON preference.name IN (
    'Independent focus time',
    'Collaborative teamwork',
    'Flexible working hours'
)
WHERE career.slug = 'graphic-designer';

INSERT IGNORE INTO career_industries (career_id, industry_id)
SELECT career.id, industry.id
FROM careers AS career
JOIN industries AS industry ON industry.name IN (
    'Information technology',
    'Financial services',
    'Government and public service'
)
WHERE career.slug = 'software-developer';

INSERT IGNORE INTO career_industries (career_id, industry_id)
SELECT career.id, industry.id
FROM careers AS career
JOIN industries AS industry ON industry.name IN (
    'Financial services',
    'Consulting',
    'Retail and commerce',
    'Government and public service'
)
WHERE career.slug = 'data-analyst';

INSERT IGNORE INTO career_industries (career_id, industry_id)
SELECT career.id, industry.id
FROM careers AS career
JOIN industries AS industry ON industry.name IN (
    'Media and entertainment',
    'Retail and commerce',
    'Information technology'
)
WHERE career.slug = 'graphic-designer';

INSERT IGNORE INTO roadmaps (career_id, version_number, title, description, is_published)
SELECT
    career.id,
    1,
    'Software Developer Starter Roadmap',
    'A practical sequence for building foundations, creating projects, and preparing to apply for entry-level software roles.',
    TRUE
FROM careers AS career
WHERE career.slug = 'software-developer'
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    is_published = VALUES(is_published);

INSERT IGNORE INTO roadmap_activities (roadmap_id, title, description, activity_type, sort_order, estimated_hours)
SELECT roadmap.id, 'Learn programming fundamentals', 'Practise variables, conditions, loops, functions, and debugging with small exercises.', 'learn', 1, 25
FROM roadmaps AS roadmap
JOIN careers AS career ON career.id = roadmap.career_id
WHERE career.slug = 'software-developer' AND roadmap.version_number = 1;

INSERT IGNORE INTO roadmap_activities (roadmap_id, title, description, activity_type, sort_order, estimated_hours)
SELECT roadmap.id, 'Build a page with HTML and CSS', 'Create and publish a responsive multi-section web page using semantic HTML and CSS.', 'practice', 2, 20
FROM roadmaps AS roadmap
JOIN careers AS career ON career.id = roadmap.career_id
WHERE career.slug = 'software-developer' AND roadmap.version_number = 1;

INSERT IGNORE INTO roadmap_activities (roadmap_id, title, description, activity_type, sort_order, estimated_hours)
SELECT roadmap.id, 'Add JavaScript interactions', 'Build a small browser feature that responds to user input and validates a form.', 'practice', 3, 25
FROM roadmaps AS roadmap
JOIN careers AS career ON career.id = roadmap.career_id
WHERE career.slug = 'software-developer' AND roadmap.version_number = 1;

INSERT IGNORE INTO roadmap_activities (roadmap_id, title, description, activity_type, sort_order, estimated_hours)
SELECT roadmap.id, 'Learn backend and database basics', 'Create a small PHP application that reads and writes data using PDO and MySQL.', 'learn', 4, 35
FROM roadmaps AS roadmap
JOIN careers AS career ON career.id = roadmap.career_id
WHERE career.slug = 'software-developer' AND roadmap.version_number = 1;

INSERT IGNORE INTO roadmap_activities (roadmap_id, title, description, activity_type, sort_order, estimated_hours)
SELECT roadmap.id, 'Publish a portfolio project', 'Document and publish a complete project with a clear README and screenshots.', 'portfolio', 5, 20
FROM roadmaps AS roadmap
JOIN careers AS career ON career.id = roadmap.career_id
WHERE career.slug = 'software-developer' AND roadmap.version_number = 1;

INSERT IGNORE INTO learning_resources (title, description, resource_type, provider, url, is_free, is_published)
VALUES (
    'MDN Learn Web Development',
    'A structured introduction to HTML, CSS, JavaScript, and core web development concepts.',
    'course',
    'MDN Web Docs',
    'https://developer.mozilla.org/en-US/docs/Learn_web_development',
    TRUE,
    TRUE
);

INSERT IGNORE INTO career_learning_resources (career_id, resource_id)
SELECT career.id, resource.id
FROM careers AS career
JOIN learning_resources AS resource ON resource.title = 'MDN Learn Web Development'
WHERE career.slug = 'software-developer';
