-- SkillSwap database
-- Run once:  mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS skillswap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skillswap;

DROP TABLE IF EXISTS exchange_requests;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id  INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100) NOT NULL,
    email    VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    course   VARCHAR(100) DEFAULT NULL,
    year     VARCHAR(30)  DEFAULT NULL,
    bio      TEXT         DEFAULT NULL,
    role     ENUM('student','admin') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB;

CREATE TABLE skills (
    skill_id   INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    category   VARCHAR(50)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE user_skills (
    user_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    skill_id      INT NOT NULL,
    type          ENUM('OFFER','WANT') NOT NULL,
    UNIQUE KEY uniq_user_skill_type (user_id, skill_id, type),
    FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exchange_requests (
    request_id       INT AUTO_INCREMENT PRIMARY KEY,
    sender_id        INT NOT NULL,
    receiver_id      INT NOT NULL,
    offered_skill_id INT NOT NULL,
    wanted_skill_id  INT NOT NULL,
    message          TEXT DEFAULT NULL,
    status           ENUM('PENDING','ACCEPTED','REJECTED','COMPLETED') NOT NULL DEFAULT 'PENDING',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)        REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (receiver_id)      REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (offered_skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
    FOREIGN KEY (wanted_skill_id)  REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Skills
INSERT INTO skills (skill_name, category) VALUES
('Web Development','Technology'),
('Python','Technology'),
('Java','Technology'),
('C#','Technology'),
('Data Analysis','Technology'),
('Excel','Academic'),
('Mathematics','Academic'),
('Academic Writing','Academic'),
('Graphic Design','Creative'),
('Photoshop','Creative'),
('Photography','Creative'),
('Video Editing','Creative'),
('Public Speaking','Lifestyle'),
('Guitar','Lifestyle'),
('Cooking','Lifestyle'),
('Fitness','Lifestyle');

-- Admin (email: admin@skillswap.com  password: admin123)
INSERT INTO users (name,email,password,course,year,bio,role) VALUES
('Admin','admin@skillswap.com','$2y$10$zu7jCt8njnYrsg9oqSGrpO1HclV1IwtSGjWoEvoanYoKKknUSjhZW',NULL,NULL,'Site administrator','admin');

-- Test students (password for all: student123)
INSERT INTO users (name,email,password,course,year,bio,role) VALUES
('Ruchit Ahuja','ruchit@test.com','$2y$10$5.P2BJvTiQRWskqvPLxz.uWX2sB4KutS9VahryTDD9dS/G7GhwfPi','BCA','2nd Year','I enjoy backend coding and want to learn design.','student'),
('Rahul Sharma','rahul@test.com','$2y$10$5.P2BJvTiQRWskqvPLxz.uWX2sB4KutS9VahryTDD9dS/G7GhwfPi','BSc Design','3rd Year','Designer who wants to pick up programming.','student'),
('Meera Kapoor','meera@test.com','$2y$10$5.P2BJvTiQRWskqvPLxz.uWX2sB4KutS9VahryTDD9dS/G7GhwfPi','BA Media','1st Year','Love visuals and storytelling.','student'),
('Diya Shah','diya@test.com','$2y$10$5.P2BJvTiQRWskqvPLxz.uWX2sB4KutS9VahryTDD9dS/G7GhwfPi','BCom','2nd Year','Spreadsheet nerd.','student');

-- Skill links (user_id 2=Ruchit, 3=Rahul, 4=Meera, 5=Diya)
INSERT INTO user_skills (user_id, skill_id, type) VALUES
(2,3,'OFFER'),(2,4,'OFFER'),(2,10,'WANT'),
(3,10,'OFFER'),(3,9,'OFFER'),(3,3,'WANT'),
(4,11,'OFFER'),(4,12,'OFFER'),(4,1,'WANT'),
(5,6,'OFFER'),(5,5,'OFFER'),(5,2,'WANT');
