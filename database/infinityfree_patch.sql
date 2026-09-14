USE if0_41928194_univ_elearning;

SET @user_status_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'Status'
);

SET @add_user_status_sql := IF(
    @user_status_exists = 0,
    'ALTER TABLE users ADD COLUMN Status ENUM(''Pending'', ''Approved'', ''Rejected'') NOT NULL DEFAULT ''Approved'' AFTER UserType',
    'SELECT ''Status already exists'' AS message'
);

PREPARE add_user_status_stmt FROM @add_user_status_sql;
EXECUTE add_user_status_stmt;
DEALLOCATE PREPARE add_user_status_stmt;

UPDATE users
SET Status = 'Approved'
WHERE Status IS NULL OR Status = '';

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

SET @answer_text_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_answers'
      AND COLUMN_NAME = 'AnswerText'
);

SET @add_answer_text_sql := IF(
    @answer_text_exists = 0,
    'ALTER TABLE user_answers ADD COLUMN AnswerText TEXT NULL AFTER SelectedOptionID',
    'SELECT ''AnswerText already exists'' AS message'
);

PREPARE add_answer_text_stmt FROM @add_answer_text_sql;
EXECUTE add_answer_text_stmt;
DEALLOCATE PREPARE add_answer_text_stmt;

DELETE ua_old
FROM user_answers ua_old
JOIN user_answers ua_new
  ON ua_old.UserID = ua_new.UserID
 AND ua_old.QuestionID = ua_new.QuestionID
 AND ua_old.AnswerID < ua_new.AnswerID;

SET @unique_answer_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_answers'
      AND INDEX_NAME = 'unique_user_question'
);

SET @add_unique_answer_sql := IF(
    @unique_answer_exists = 0,
    'ALTER TABLE user_answers ADD UNIQUE KEY unique_user_question (UserID, QuestionID)',
    'SELECT ''unique_user_question already exists'' AS message'
);

PREPARE add_unique_answer_stmt FROM @add_unique_answer_sql;
EXECUTE add_unique_answer_stmt;
DEALLOCATE PREPARE add_unique_answer_stmt;
