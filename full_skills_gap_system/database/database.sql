
CREATE DATABASE IF NOT EXISTS skills_gap_db;
USE skills_gap_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS required_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    required_level INT NOT NULL, 
    FOREIGN KEY (skill_id) REFERENCES skills(id)
        ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    user_level INT NOT NULL,  
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id)
        ON DELETE CASCADE
);

INSERT INTO skills (skill_name) VALUES
('PHP'),
('JavaScript'),
('HTML'),
('CSS'),
('Database Management');


INSERT INTO required_skills (skill_id, required_level) VALUES
(1, 2),
(2, 2), 
(3, 3), 
(4, 3); 
CREATE TABLE skill_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255),
    option_b VARCHAR(255),
    option_c VARCHAR(255),
    option_d VARCHAR(255),
    correct_option CHAR(1),
    FOREIGN KEY (skill_id) REFERENCES skills(id)
);
CREATE TABLE skill_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    score INT,
    skill_level INT,
    tested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE skill_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    task_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE task_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    submission TEXT NOT NULL,
    score INT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE user_skill_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    current_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Mastered') DEFAULT 'Beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_skill_unique (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);
CREATE TABLE skill_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    level_name ENUM('Beginner','Intermediate','Advanced') NOT NULL,
    learning_resource VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);
<!--for inserting learning materials for ids replace with actual ids confirm database table skills


INSERT INTO skill_levels (skill_id, level_name, learning_resource) VALUES
(7, 'Beginner', 'https://www.w3schools.com/'),
(7, 'Intermediate', 'https://developer.mozilla.org/'),
(7, 'Advanced', 'https://www.udemy.com/');
