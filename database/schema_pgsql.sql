-- ============================================================
--  UNIV E-LEARNING  -  PostgreSQL Schema (Supabase)
--  Run this entire file in the Supabase SQL Editor.
--  Converted from MySQL schema:
--    - AUTO_INCREMENT  -  SERIAL
--    - ENGINE=InnoDB   -  removed
--    - ENUM(...)       -  VARCHAR + CHECK constraint
--    - BOOLEAN         -  BOOLEAN (native in pg)
--    - ON UPDATE CURRENT_TIMESTAMP  -  trigger function
-- ============================================================

-- --------------------------------------------------------
-- Helper: auto-update UpdatedAt via trigger
-- --------------------------------------------------------
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updatedat = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- --------------------------------------------------------
-- Table 1: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    userid           SERIAL PRIMARY KEY,
    username         VARCHAR(50)  NOT NULL UNIQUE,
    email            VARCHAR(100) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    usertype         VARCHAR(20)  NOT NULL DEFAULT 'Student'
                           CHECK (usertype IN ('Admin','Instructor','Student')),
    status           VARCHAR(20)  NOT NULL DEFAULT 'Approved'
                           CHECK (status IN ('Pending','Approved','Rejected')),
    emailverifiedat  TIMESTAMP    NULL DEFAULT NULL,
    createdat        TIMESTAMP    NOT NULL DEFAULT NOW(),
    lastactiveat     TIMESTAMP    NULL DEFAULT NULL
);

-- --------------------------------------------------------
-- Table 2: password_reset_tokens
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    tokenid    SERIAL PRIMARY KEY,
    userid     INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    tokenhash  CHAR(64) NOT NULL UNIQUE,
    expiresat  TIMESTAMP NOT NULL,
    usedat     TIMESTAMP NULL DEFAULT NULL,
    createdat  TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_password_reset_user    ON password_reset_tokens (userid);
CREATE INDEX IF NOT EXISTS idx_password_reset_expires ON password_reset_tokens (expiresat);

-- --------------------------------------------------------
-- Table 3: email_verification_tokens
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    tokenid    SERIAL PRIMARY KEY,
    userid     INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    tokenhash  CHAR(64) NOT NULL UNIQUE,
    expiresat  TIMESTAMP NOT NULL,
    usedat     TIMESTAMP NULL DEFAULT NULL,
    createdat  TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_email_verification_user    ON email_verification_tokens (userid);
CREATE INDEX IF NOT EXISTS idx_email_verification_expires ON email_verification_tokens (expiresat);

-- --------------------------------------------------------
-- Table 4: courses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
    courseid    SERIAL PRIMARY KEY,
    coursename  VARCHAR(100) NOT NULL,
    description TEXT,
    createdat   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- --------------------------------------------------------
-- Table 5: enrollments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
    enrollmentid       SERIAL PRIMARY KEY,
    userid             INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    courseid           INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    enrollmentdate     TIMESTAMP NOT NULL DEFAULT NOW(),
    completionstatus   VARCHAR(20) NOT NULL DEFAULT 'In Progress'
                             CHECK (completionstatus IN ('In Progress','Completed')),
    UNIQUE (userid, courseid)
);

-- --------------------------------------------------------
-- Table 6: course_contents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_contents (
    contentid    SERIAL PRIMARY KEY,
    courseid     INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    contenttype  VARCHAR(20) NOT NULL
                       CHECK (contenttype IN ('Video','PDF','Link','Text')),
    contenttitle VARCHAR(255) NOT NULL,
    contenturl   VARCHAR(255),
    createdby    INT REFERENCES users(userid) ON DELETE SET NULL,
    createdat    TIMESTAMP NOT NULL DEFAULT NOW(),
    updatedat    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Auto-update UpdatedAt on course_contents
DROP TRIGGER IF EXISTS set_updated_at_course_contents ON course_contents;
CREATE TRIGGER set_updated_at_course_contents
    BEFORE UPDATE ON course_contents
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- --------------------------------------------------------
-- Table 7: quizzes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS quizzes (
    quizid      SERIAL PRIMARY KEY,
    quizname    VARCHAR(100) NOT NULL,
    courseid    INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    quiztype    VARCHAR(20) NOT NULL DEFAULT 'Quiz'
                      CHECK (quiztype IN ('Quiz','Midterm','Final','Assignment')),
    description TEXT,
    totalmarks  INT DEFAULT 100
);

-- --------------------------------------------------------
-- Table 8: results
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS results (
    resultid    SERIAL PRIMARY KEY,
    userid      INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    courseid    INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    quizid      INT NOT NULL REFERENCES quizzes(quizid) ON DELETE CASCADE,
    score       INT NOT NULL,
    submittedat TIMESTAMP NOT NULL DEFAULT NOW()
);

-- --------------------------------------------------------
-- Table 9: questions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS questions (
    questionid   SERIAL PRIMARY KEY,
    quizid       INT NOT NULL REFERENCES quizzes(quizid) ON DELETE CASCADE,
    questiontext TEXT NOT NULL,
    questiontype VARCHAR(30) NOT NULL DEFAULT 'Multiple Choice'
                       CHECK (questiontype IN ('Multiple Choice','True/False','Short Answer')),
    marks        INT DEFAULT 1,
    createdat    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- --------------------------------------------------------
-- Table 10: question_options
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS question_options (
    optionid   SERIAL PRIMARY KEY,
    questionid INT NOT NULL REFERENCES questions(questionid) ON DELETE CASCADE,
    optiontext TEXT NOT NULL,
    iscorrect  BOOLEAN NOT NULL DEFAULT FALSE
);

-- --------------------------------------------------------
-- Table 11: user_answers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_answers (
    answerid         SERIAL PRIMARY KEY,
    userid           INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    questionid       INT NOT NULL REFERENCES questions(questionid) ON DELETE CASCADE,
    selectedoptionid INT REFERENCES question_options(optionid) ON DELETE SET NULL,
    answertext       TEXT,
    iscorrect        BOOLEAN,
    submittedat      TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (userid, questionid)
);

-- --------------------------------------------------------
-- Table 12: quiz_attempts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_attempts (
    attemptid   SERIAL PRIMARY KEY,
    userid      INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    quizid      INT NOT NULL REFERENCES quizzes(quizid) ON DELETE CASCADE,
    startedat   TIMESTAMP NOT NULL DEFAULT NOW(),
    submittedat TIMESTAMP NULL DEFAULT NULL,
    score       INT,
    status      VARCHAR(20) NOT NULL DEFAULT 'In Progress'
                      CHECK (status IN ('In Progress','Submitted','Graded'))
);

-- --------------------------------------------------------
-- Table 13: instructor_courses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS instructor_courses (
    instructorcourseid SERIAL PRIMARY KEY,
    instructorid       INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    courseid           INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    assignedat         TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (instructorid, courseid)
);

-- --------------------------------------------------------
-- Table 14: course_progress
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS course_progress (
    progressid        SERIAL PRIMARY KEY,
    userid            INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    courseid          INT NOT NULL REFERENCES courses(courseid) ON DELETE CASCADE,
    completedlessons  INT DEFAULT 0,
    totallessons      INT DEFAULT 0,
    lastaccessedat    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- --------------------------------------------------------
-- Table 15: messages (for in-app chat)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    messageid  SERIAL PRIMARY KEY,
    senderid   INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    receiverid INT NOT NULL REFERENCES users(userid) ON DELETE CASCADE,
    messagetext    TEXT NOT NULL,
    sentat     TIMESTAMP NOT NULL DEFAULT NOW(),
    isread     BOOLEAN NOT NULL DEFAULT FALSE
);


-- ============================================================
--  SEED DATA - Schema + Admin User Only
-- ============================================================

-- Admin user  (password: admin123)
-- Hash: password_hash('admin123', PASSWORD_DEFAULT) from PHP
INSERT INTO users (username,email,password,usertype,status,emailverifiedat,createdat)
VALUES (
    'admin',
    'admin@univ.edu',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Approved',
    NOW(),
    NOW()
)
ON CONFLICT (username) DO NOTHING;

-- The application connects as the database user from the server. Keep the
-- public Supabase Data API locked down unless explicit policies are added.
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE password_reset_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_verification_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE courses ENABLE ROW LEVEL SECURITY;
ALTER TABLE enrollments ENABLE ROW LEVEL SECURITY;
ALTER TABLE course_contents ENABLE ROW LEVEL SECURITY;
ALTER TABLE quizzes ENABLE ROW LEVEL SECURITY;
ALTER TABLE results ENABLE ROW LEVEL SECURITY;
ALTER TABLE questions ENABLE ROW LEVEL SECURITY;
ALTER TABLE question_options ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_answers ENABLE ROW LEVEL SECURITY;
ALTER TABLE quiz_attempts ENABLE ROW LEVEL SECURITY;
ALTER TABLE instructor_courses ENABLE ROW LEVEL SECURITY;
ALTER TABLE course_progress ENABLE ROW LEVEL SECURITY;
ALTER TABLE messages ENABLE ROW LEVEL SECURITY;
