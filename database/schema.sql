CREATE DATABASE IF NOT EXISTS career_road
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE career_road;

CREATE TABLE IF NOT EXISTS education_levels (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    account_role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    account_status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    career_stage ENUM(
        'secondary_student',
        'college_student',
        'university_student',
        'graduate',
        'working_professional',
        'career_changer'
    ) NULL,
    education_level_id TINYINT UNSIGNED NULL,
    location VARCHAR(150) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'KE',
    bio VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_profiles_education
        FOREIGN KEY (education_level_id) REFERENCES education_levels (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS academic_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_education (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    education_level_id TINYINT UNSIGNED NOT NULL,
    institution_name VARCHAR(180) NULL,
    course_name VARCHAR(180) NULL,
    start_year YEAR NULL,
    completion_year YEAR NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_education_user (user_id),
    CONSTRAINT fk_user_education_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_education_level
        FOREIGN KEY (education_level_id) REFERENCES education_levels (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_subjects (
    user_id BIGINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    education_level_id TINYINT UNSIGNED NULL,
    grade VARCHAR(20) NULL,
    PRIMARY KEY (user_id, subject_id),
    CONSTRAINT fk_user_subjects_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_subjects_subject
        FOREIGN KEY (subject_id) REFERENCES academic_subjects (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_subjects_education
        FOREIGN KEY (education_level_id) REFERENCES education_levels (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS interests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(500) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_interests (
    user_id BIGINT UNSIGNED NOT NULL,
    interest_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, interest_id),
    CONSTRAINT fk_user_interests_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_interests_interest
        FOREIGN KEY (interest_id) REFERENCES interests (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    skill_type ENUM('technical', 'interpersonal', 'creative', 'other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_skills (
    user_id BIGINT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    proficiency_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, skill_id),
    CONSTRAINT chk_user_skill_proficiency CHECK (proficiency_level BETWEEN 1 AND 5),
    CONSTRAINT fk_user_skills_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS personality_traits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(500) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_personality_traits (
    user_id BIGINT UNSIGNED NOT NULL,
    trait_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, trait_id),
    CONSTRAINT chk_user_trait_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_user_traits_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_traits_trait
        FOREIGN KEY (trait_id) REFERENCES personality_traits (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_work_preferences (
    user_id BIGINT UNSIGNED NOT NULL,
    preference_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, preference_id),
    CONSTRAINT fk_user_work_preferences_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_work_preferences_preference
        FOREIGN KEY (preference_id) REFERENCES work_preferences (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS industries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_preferred_industries (
    user_id BIGINT UNSIGNED NOT NULL,
    industry_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, industry_id),
    CONSTRAINT fk_user_industries_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_industries_industry
        FOREIGN KEY (industry_id) REFERENCES industries (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS careers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    summary VARCHAR(600) NOT NULL,
    responsibilities TEXT NOT NULL,
    typical_qualifications TEXT NOT NULL,
    work_environment TEXT NOT NULL,
    employment_opportunities TEXT NOT NULL,
    challenges TEXT NOT NULL,
    growth_opportunities TEXT NOT NULL,
    professional_certifications TEXT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_careers_category_published (category_id, is_published),
    CONSTRAINT fk_careers_category
        FOREIGN KEY (category_id) REFERENCES career_categories (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_subject_requirements (
    career_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    importance TINYINT UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (career_id, subject_id),
    CONSTRAINT chk_career_subject_importance CHECK (importance BETWEEN 1 AND 5),
    CONSTRAINT fk_career_subjects_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_subjects_subject
        FOREIGN KEY (subject_id) REFERENCES academic_subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_interest_matches (
    career_id INT UNSIGNED NOT NULL,
    interest_id INT UNSIGNED NOT NULL,
    weight TINYINT UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (career_id, interest_id),
    CONSTRAINT chk_career_interest_weight CHECK (weight BETWEEN 1 AND 5),
    CONSTRAINT fk_career_interests_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_interests_interest
        FOREIGN KEY (interest_id) REFERENCES interests (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_skill_requirements (
    career_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    required_level TINYINT UNSIGNED NOT NULL DEFAULT 3,
    importance TINYINT UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (career_id, skill_id),
    CONSTRAINT chk_career_skill_level CHECK (required_level BETWEEN 1 AND 5),
    CONSTRAINT chk_career_skill_importance CHECK (importance BETWEEN 1 AND 5),
    CONSTRAINT fk_career_skills_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_skills_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_personality_requirements (
    career_id INT UNSIGNED NOT NULL,
    trait_id INT UNSIGNED NOT NULL,
    preferred_rating TINYINT UNSIGNED NOT NULL DEFAULT 3,
    importance TINYINT UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (career_id, trait_id),
    CONSTRAINT chk_career_trait_rating CHECK (preferred_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_career_trait_importance CHECK (importance BETWEEN 1 AND 5),
    CONSTRAINT fk_career_traits_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_traits_trait
        FOREIGN KEY (trait_id) REFERENCES personality_traits (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_work_preferences (
    career_id INT UNSIGNED NOT NULL,
    preference_id INT UNSIGNED NOT NULL,
    importance TINYINT UNSIGNED NOT NULL DEFAULT 3,
    PRIMARY KEY (career_id, preference_id),
    CONSTRAINT chk_career_work_importance CHECK (importance BETWEEN 1 AND 5),
    CONSTRAINT fk_career_work_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_work_preference
        FOREIGN KEY (preference_id) REFERENCES work_preferences (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_industries (
    career_id INT UNSIGNED NOT NULL,
    industry_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (career_id, industry_id),
    CONSTRAINT fk_career_industries_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_industries_industry
        FOREIGN KEY (industry_id) REFERENCES industries (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessment_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(60) NOT NULL,
    step_number TINYINT UNSIGNED NOT NULL,
    question_text VARCHAR(1000) NOT NULL,
    response_type ENUM('single_choice', 'multiple_choice', 'scale', 'text', 'number') NOT NULL,
    is_required BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    INDEX idx_assessment_questions_order (is_active, step_number, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessment_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_assessment_options_order (question_id, sort_order),
    CONSTRAINT fk_assessment_options_question
        FOREIGN KEY (question_id) REFERENCES assessment_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('in_progress', 'completed', 'abandoned') NOT NULL DEFAULT 'in_progress',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_assessments_user_date (user_id, started_at),
    CONSTRAINT fk_assessments_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessment_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    answer_number DECIMAL(8, 2) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assessment_response_question (assessment_id, question_id),
    CONSTRAINT fk_assessment_responses_assessment
        FOREIGN KEY (assessment_id) REFERENCES assessments (id) ON DELETE CASCADE,
    CONSTRAINT fk_assessment_responses_question
        FOREIGN KEY (question_id) REFERENCES assessment_questions (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessment_response_options (
    response_id BIGINT UNSIGNED NOT NULL,
    option_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (response_id, option_id),
    CONSTRAINT fk_response_options_response
        FOREIGN KEY (response_id) REFERENCES assessment_responses (id) ON DELETE CASCADE,
    CONSTRAINT fk_response_options_option
        FOREIGN KEY (option_id) REFERENCES assessment_options (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recommendations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    algorithm_version VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recommendations_user_date (user_id, created_at),
    CONSTRAINT fk_recommendations_assessment
        FOREIGN KEY (assessment_id) REFERENCES assessments (id) ON DELETE CASCADE,
    CONSTRAINT fk_recommendations_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recommended_careers (
    recommendation_id BIGINT UNSIGNED NOT NULL,
    career_id INT UNSIGNED NOT NULL,
    ranking_position TINYINT UNSIGNED NOT NULL,
    compatibility_score DECIMAL(5, 2) NOT NULL,
    qualification_eligibility ENUM('eligible', 'not_eligible', 'unknown') NOT NULL DEFAULT 'unknown',
    qualification_note VARCHAR(1000) NULL,
    explanation TEXT NOT NULL,
    matched_factors JSON NOT NULL,
    PRIMARY KEY (recommendation_id, career_id),
    UNIQUE KEY uq_recommendation_rank (recommendation_id, ranking_position),
    CONSTRAINT chk_recommendation_score CHECK (compatibility_score BETWEEN 0 AND 100),
    CONSTRAINT fk_recommended_careers_recommendation
        FOREIGN KEY (recommendation_id) REFERENCES recommendations (id) ON DELETE CASCADE,
    CONSTRAINT fk_recommended_careers_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roadmaps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    career_id INT UNSIGNED NOT NULL,
    version_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE KEY uq_career_roadmap_version (career_id, version_number),
    CONSTRAINT fk_roadmaps_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roadmap_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    roadmap_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    activity_type ENUM('learn', 'practice', 'portfolio', 'experience', 'apply', 'other') NOT NULL DEFAULT 'other',
    sort_order SMALLINT UNSIGNED NOT NULL,
    estimated_hours SMALLINT UNSIGNED NULL,
    UNIQUE KEY uq_roadmap_activity_order (roadmap_id, sort_order),
    CONSTRAINT fk_roadmap_activities_roadmap
        FOREIGN KEY (roadmap_id) REFERENCES roadmaps (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roadmap_progress (
    user_id BIGINT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, activity_id),
    CONSTRAINT fk_roadmap_progress_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_roadmap_progress_activity
        FOREIGN KEY (activity_id) REFERENCES roadmap_activities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_careers (
    user_id BIGINT UNSIGNED NOT NULL,
    career_id INT UNSIGNED NOT NULL,
    saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, career_id),
    CONSTRAINT fk_saved_careers_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_careers_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS learning_resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    resource_type ENUM('course', 'article', 'video', 'book', 'project', 'other') NOT NULL DEFAULT 'other',
    provider VARCHAR(150) NULL,
    url VARCHAR(2048) NULL,
    is_free BOOLEAN NOT NULL DEFAULT FALSE,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS career_learning_resources (
    career_id INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (career_id, resource_id),
    CONSTRAINT fk_career_resources_career
        FOREIGN KEY (career_id) REFERENCES careers (id) ON DELETE CASCADE,
    CONSTRAINT fk_career_resources_resource
        FOREIGN KEY (resource_id) REFERENCES learning_resources (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_date (user_id, created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;
