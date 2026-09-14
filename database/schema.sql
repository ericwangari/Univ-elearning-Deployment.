CREATE DATABASE IF NOT EXISTS univ_elearning;
USE univ_elearning;

-- Table 1: User
CREATE TABLE IF NOT EXISTS users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    UserType ENUM('Admin', 'Instructor', 'Student') NOT NULL DEFAULT 'Student',
    Status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved',
    EmailVerifiedAt DATETIME NULL DEFAULT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastActiveAt DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    TokenID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TokenHash CHAR(64) NOT NULL UNIQUE,
    ExpiresAt DATETIME NOT NULL,
    UsedAt DATETIME NULL DEFAULT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user (UserID),
    INDEX idx_password_reset_expires (ExpiresAt),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    TokenID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TokenHash CHAR(64) NOT NULL UNIQUE,
    ExpiresAt DATETIME NOT NULL,
    UsedAt DATETIME NULL DEFAULT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_verification_user (UserID),
    INDEX idx_email_verification_expires (ExpiresAt),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 2: Course
CREATE TABLE IF NOT EXISTS courses (
    CourseID INT AUTO_INCREMENT PRIMARY KEY,
    CourseName VARCHAR(100) NOT NULL,
    Description TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 3: Enrollment
CREATE TABLE IF NOT EXISTS enrollments (
    EnrollmentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    CourseID INT NOT NULL,
    EnrollmentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CompletionStatus ENUM('In Progress', 'Completed') DEFAULT 'In Progress',
    UNIQUE KEY unique_user_course (UserID, CourseID),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 4: CourseContent
CREATE TABLE IF NOT EXISTS course_contents (
    ContentID INT AUTO_INCREMENT PRIMARY KEY,
    CourseID INT NOT NULL,
    ContentType ENUM('Video', 'PDF', 'Link', 'Text') NOT NULL,
    ContentTitle VARCHAR(255) NOT NULL,
    ContentURL VARCHAR(255),
    CreatedBy INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE,
    FOREIGN KEY (CreatedBy) REFERENCES users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table 5: Quiz
CREATE TABLE IF NOT EXISTS quizzes (
    QuizID INT AUTO_INCREMENT PRIMARY KEY,
    QuizName VARCHAR(100) NOT NULL,
    CourseID INT NOT NULL,
    QuizType ENUM('Quiz', 'Midterm', 'Final', 'Assignment') DEFAULT 'Quiz',
    Description TEXT,
    TotalMarks INT DEFAULT 100,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 6: Result
CREATE TABLE IF NOT EXISTS results (
    ResultID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    CourseID INT NOT NULL,
    QuizID INT NOT NULL,
    Score INT NOT NULL,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE,
    FOREIGN KEY (QuizID) REFERENCES quizzes(QuizID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 7: Questions
CREATE TABLE IF NOT EXISTS questions (
    QuestionID INT AUTO_INCREMENT PRIMARY KEY,
    QuizID INT NOT NULL,
    QuestionText TEXT NOT NULL,
    QuestionType ENUM('Multiple Choice', 'True/False', 'Short Answer') DEFAULT 'Multiple Choice',
    Marks INT DEFAULT 1,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (QuizID) REFERENCES quizzes(QuizID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 8: Question Options (for Multiple Choice)
CREATE TABLE IF NOT EXISTS question_options (
    OptionID INT AUTO_INCREMENT PRIMARY KEY,
    QuestionID INT NOT NULL,
    OptionText TEXT NOT NULL,
    IsCorrect BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (QuestionID) REFERENCES questions(QuestionID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 9: User Answers (track student responses)
CREATE TABLE IF NOT EXISTS user_answers (
    AnswerID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    QuestionID INT NOT NULL,
    SelectedOptionID INT,
    AnswerText TEXT,
    IsCorrect BOOLEAN,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_question (UserID, QuestionID),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (QuestionID) REFERENCES questions(QuestionID) ON DELETE CASCADE,
    FOREIGN KEY (SelectedOptionID) REFERENCES question_options(OptionID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table 10: Quiz Attempts (track quiz submissions)
CREATE TABLE IF NOT EXISTS quiz_attempts (
    AttemptID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    QuizID INT NOT NULL,
    StartedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    SubmittedAt TIMESTAMP NULL DEFAULT NULL,
    Score INT,
    Status ENUM('In Progress', 'Submitted', 'Graded') DEFAULT 'In Progress',
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (QuizID) REFERENCES quizzes(QuizID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 11: Instructor Courses
CREATE TABLE IF NOT EXISTS instructor_courses (
    InstructorCourseID INT AUTO_INCREMENT PRIMARY KEY,
    InstructorID INT NOT NULL,
    CourseID INT NOT NULL,
    AssignedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instructor_course (InstructorID, CourseID),
    FOREIGN KEY (InstructorID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 12: Course Progress Tracking
CREATE TABLE IF NOT EXISTS course_progress (
    ProgressID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    CourseID INT NOT NULL,
    CompletedLessons INT DEFAULT 0,
    TotalLessons INT DEFAULT 0,
    LastAccessedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (CourseID) REFERENCES courses(CourseID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Admin User (password: admin123)
-- Hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT IGNORE INTO users (Username, Email, Password, UserType, Status, EmailVerifiedAt) 
VALUES ('admin', 'admin@univ.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Approved', NOW());
