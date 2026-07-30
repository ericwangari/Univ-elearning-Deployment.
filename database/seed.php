<?php
require_once __DIR__ . '/../config/config.php';

// ─── Safety: only run from CLI or with ?run=1 ───
if (php_sapi_name() !== 'cli' && ($_GET['run'] ?? '') !== '1') {
    echo "<h2>Univ E-Learning Database Seeder</h2>";
    echo "<p>This script will seed the database with courses, quizzes, midterms, finals and assignments.</p>";
    echo "<p><strong style='color:red'>Warning:</strong> Run this only once or data may duplicate.</p>";
    echo "<a href='?run=1' style='padding:10px 20px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none'>▶ Run Seeder</a>";
    exit;
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// ─── PASSWORD HASH (password = 'pass1234') ───
$hash = password_hash('pass1234', PASSWORD_DEFAULT);

// ─── INSTRUCTORS ───
$instructors = [
    ['dr_alice',   'alice@univ.edu',   'Dr. Alice Mwangi'],
    ['prof_bob',   'bob@univ.edu',     'Prof. Bob Osei'],
    ['dr_carol',   'carol@univ.edu',   'Dr. Carol Njeri'],
    ['prof_david', 'david@univ.edu',   'Prof. David Kamau'],
    ['dr_eve',     'eve@univ.edu',     'Dr. Eve Achieng'],
];
$instructorIDs = [];
foreach ($instructors as $i) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (Username, Email, Password, UserType, EmailVerifiedAt) VALUES (?,?,?,'Instructor',NOW())");
    $stmt->execute([$i[0], $i[1], $hash]);
    $id = $pdo->query("SELECT UserID FROM users WHERE Username='{$i[0]}'")->fetchColumn();
    $instructorIDs[] = $id;
}

// ─── STUDENTS ───
$students = [
    ['john_doe',   'john@student.edu'],
    ['jane_doe',   'jane@student.edu'],
    ['mike_otieno','mike@student.edu'],
    ['sarah_wema', 'sarah@student.edu'],
    ['tom_banda',  'tom@student.edu'],
    ['lucy_njeri', 'lucy@student.edu'],
    ['peter_mwas', 'peter@student.edu'],
    ['grace_auma', 'grace@student.edu'],
];
$studentIDs = [];
foreach ($students as $s) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (Username, Email, Password, UserType, EmailVerifiedAt) VALUES (?,?,?,'Student',NOW())");
    $stmt->execute([$s[0], $s[1], $hash]);
    $id = $pdo->query("SELECT UserID FROM users WHERE Username='{$s[0]}'")->fetchColumn();
    $studentIDs[] = $id;
}

// ─── COURSES ───
$courses = [
    ['Introduction to Python Programming',        'Learn Python from scratch including variables, loops, functions and OOP.'],
    ['Data Structures & Algorithms',              'Study arrays, linked lists, trees, graphs, sorting and searching algorithms.'],
    ['Database Management Systems',               'Relational databases, SQL, normalization, transactions and indexing.'],
    ['Web Development Fundamentals',              'HTML, CSS, JavaScript and PHP for building dynamic web applications.'],
    ['Artificial Intelligence & Machine Learning','Supervised learning, neural networks, NLP and AI ethics.'],
    ['Computer Networks',                         'OSI model, TCP/IP, routing, switching, firewalls and network security.'],
    ['Software Engineering',                      'SDLC, Agile, design patterns, UML, testing and project management.'],
    ['Operating Systems',                         'Process management, memory, file systems, scheduling and concurrency.'],
    ['Cybersecurity Fundamentals',                'Threats, cryptography, ethical hacking, firewalls and compliance.'],
    ['Mobile App Development',                    'Building Android and iOS apps using Flutter and REST APIs.'],
];
$courseIDs = [];
foreach ($courses as $idx => $c) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO courses (CourseName, Description) VALUES (?,?)");
    $stmt->execute($c);
    $id = $pdo->query("SELECT CourseID FROM courses WHERE CourseName=" . $pdo->quote($c[0]))->fetchColumn();
    $courseIDs[] = $id;

    // Assign instructor (round-robin)
    $instrID = $instructorIDs[$idx % count($instructorIDs)];
    $stmt2 = $pdo->prepare("INSERT IGNORE INTO instructor_courses (InstructorID, CourseID) VALUES (?,?)");
    $stmt2->execute([$instrID, $id]);
}

// ─── COURSE CONTENTS ───
foreach ($courseIDs as $cid) {
    $contents = [
        ['Video', 'Introduction Lecture',        'https://www.youtube.com/watch?v=example'],
        ['PDF',   'Course Slides Week 1',         'https://example.com/slides1.pdf'],
        ['Video', 'Deep Dive - Core Concepts',    'https://www.youtube.com/watch?v=example2'],
        ['PDF',   'Assignment Brief',             'https://example.com/assignment.pdf'],
        ['Link',  'Recommended Reading',          'https://docs.example.com/reading'],
    ];
    foreach ($contents as $ct) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO course_contents (CourseID, ContentType, ContentTitle, ContentURL) VALUES (?,?,?,?)");
        $stmt->execute([$cid, $ct[0], $ct[1], $ct[2]]);
    }
}

// ─── ASSESSMENT BANK: questions per course type ───
// Format: [ [question, [wrong,wrong,wrong], correct], ... ]
function getQuestions($course, $type) {
    $bank = [
        'Introduction to Python Programming' => [
            ['Quiz' => [
                ['What keyword defines a function in Python?', ['class','def','func','define'], 'def'],
                ['Which data type is mutable?', ['tuple','string','list','int'], 'list'],
                ['What does len() return?', ['Sum of elements','Number of elements','Last index','First element'], 'Number of elements'],
                ['Which operator is used for exponentiation?', ['+','*','**','^'], '**'],
                ['How do you start a comment in Python?', ['//','#','/*','--'], '#'],
            ]],
            'Midterm' => [
                ['What is the output of print(type([]))?', ["<class 'dict'>","<class 'tuple'>","<class 'list'>","<class 'set'>"], "<class 'list'>"],
                ['Which method adds an item to a list?', ['add()','insert()','append()','push()'], 'append()'],
                ['What is a lambda function?', ['A named function','An anonymous function','A built-in function','A class method'], 'An anonymous function'],
                ['What does the "in" keyword check?', ['Iteration count','Membership in a sequence','Variable scope','Module import'], 'Membership in a sequence'],
                ['Which module is used for math operations?', ['os','sys','math','random'], 'math'],
            ],
            'Final' => [
                ['What is inheritance in OOP?', ['Hiding data','A class deriving from another','Overloading methods','Using global variables'], 'A class deriving from another'],
                ['Which exception handles division by zero?', ['ValueError','TypeError','ZeroDivisionError','NameError'], 'ZeroDivisionError'],
                ['What does __init__ do?', ['Destroys object','Initializes object attributes','Imports modules','Defines class variables'], 'Initializes object attributes'],
                ['What is a decorator?', ['A loop construct','A function that modifies another function','A data type','An exception handler'], 'A function that modifies another function'],
                ['What does "yield" do in Python?', ['Returns value and exits','Pauses function and returns a generator','Imports a module','Raises an exception'], 'Pauses function and returns a generator'],
            ],
            'Assignment' => [
                ['Which library is used for data manipulation in Python?', ['Flask','Django','Pandas','NumPy'], 'Pandas'],
                ['What does pip stand for?', ['Python Installer Package','Pip Installs Packages','Python Index Protocol','Package Import Program'], 'Pip Installs Packages'],
                ['What is a virtual environment used for?', ['Running Python faster','Isolating project dependencies','Storing passwords','Compiling Python'], 'Isolating project dependencies'],
                ['Which function reads a file line by line?', ['read()','readlines()','readline()','open()'], 'readlines()'],
                ['What is the purpose of requirements.txt?', ['Store passwords','List project dependencies','Configure the server','Run migrations'], 'List project dependencies'],
            ],
        ],
        'Database Management Systems' => [
            'Quiz' => [
                ['What does SQL stand for?', ['Structured Question Language','Simple Query Language','Structured Query Language','System Query Logic'], 'Structured Query Language'],
                ['Which command retrieves data from a table?', ['INSERT','UPDATE','SELECT','DELETE'], 'SELECT'],
                ['What is a primary key?', ['A duplicate key','A foreign reference','A unique identifier for each record','An index column'], 'A unique identifier for each record'],
                ['Which normalization form eliminates partial dependencies?', ['1NF','2NF','3NF','BCNF'], '2NF'],
                ['What does JOIN do in SQL?', ['Deletes tables','Combines rows from two or more tables','Creates indexes','Renames columns'], 'Combines rows from two or more tables'],
            ],
            'Midterm' => [
                ['What is a foreign key?', ['A primary key in another table','A unique key','An index','A duplicate record'], 'A primary key in another table'],
                ['Which SQL clause filters results?', ['ORDER BY','GROUP BY','WHERE','HAVING'], 'WHERE'],
                ['What is ACID in databases?', ['A chemical formula','Atomicity Consistency Isolation Durability','A query optimizer','An index type'], 'Atomicity Consistency Isolation Durability'],
                ['What does DDL stand for?', ['Data Definition Language','Data Display Logic','Database Design Layer','Dynamic Data Link'], 'Data Definition Language'],
                ['Which command creates a new table?', ['ALTER TABLE','DROP TABLE','CREATE TABLE','INSERT INTO'], 'CREATE TABLE'],
            ],
            'Final' => [
                ['What is a deadlock in a DBMS?', ['A broken index','Two transactions waiting on each other indefinitely','A corrupt database','A slow query'], 'Two transactions waiting on each other indefinitely'],
                ['What is an index used for?', ['Storing backups','Speeding up data retrieval','Encrypting data','Compressing rows'], 'Speeding up data retrieval'],
                ['Which join returns all rows from both tables?', ['INNER JOIN','LEFT JOIN','RIGHT JOIN','FULL OUTER JOIN'], 'FULL OUTER JOIN'],
                ['What does TRUNCATE do?', ['Deletes selected rows','Drops the table','Removes all rows but keeps structure','Renames a table'], 'Removes all rows but keeps structure'],
                ['What is a stored procedure?', ['A saved SELECT query','A set of SQL statements stored in the database','An index strategy','A table backup'], 'A set of SQL statements stored in the database'],
            ],
            'Assignment' => [
                ['What is an ER diagram?', ['A query result','Entity Relationship diagram for database design','An error report','An export format'], 'Entity Relationship diagram for database design'],
                ['What is denormalization?', ['Adding more foreign keys','Intentionally introducing redundancy for performance','Removing all indexes','Merging two databases'], 'Intentionally introducing redundancy for performance'],
                ['Which engine supports transactions in MySQL?', ['MyISAM','MEMORY','InnoDB','ARCHIVE'], 'InnoDB'],
                ['What is a view in SQL?', ['A physical table copy','A virtual table based on a query','A user interface element','A backup file'], 'A virtual table based on a query'],
                ['What does GROUP BY do?', ['Sorts data','Filters rows','Groups rows sharing a value for aggregate functions','Joins tables'], 'Groups rows sharing a value for aggregate functions'],
            ],
        ],
    ];

    // Generic fallback for other courses
    $generic = [
        'Quiz' => [
            ['Which principle is core to this subject?', ['Option A','Option B','Correct Answer','Option D'], 'Correct Answer'],
            ['What is the main purpose of this course?', ['Entertainment','Skill building','Option C','Option D'], 'Skill building'],
            ['Which tool is commonly used in this field?', ['Notepad','Spreadsheet','Specialized software','Calculator'], 'Specialized software'],
            ['What does a practitioner in this field primarily do?', ['Design','Analyze and solve problems','Cook','Travel'], 'Analyze and solve problems'],
            ['Which concept is foundational to this discipline?', ['Guessing','Theory and practice','Random work','Copying'], 'Theory and practice'],
        ],
        'Midterm' => [
            ['Midterm Q1: What is abstraction?', ['Hiding details','Showing everything','Duplicating data','Random access'], 'Hiding details'],
            ['Midterm Q2: Identify the best practice.', ['Skip testing','Document code','Ignore errors','Hard-code values'], 'Document code'],
            ['Midterm Q3: What does scalability mean?', ['Size of a monitor','System ability to handle growth','Network speed','File size'], 'System ability to handle growth'],
            ['Midterm Q4: Which phase comes first in a project?', ['Testing','Deployment','Requirements gathering','Maintenance'], 'Requirements gathering'],
            ['Midterm Q5: What is version control?', ['Controlling software versions','Tracking changes in code over time','Setting screen brightness','Password management'], 'Tracking changes in code over time'],
        ],
        'Final' => [
            ['Final Q1: What is the purpose of documentation?', ['Wasting time','Helping future developers understand the code','Filling pages','Legal compliance only'], 'Helping future developers understand the code'],
            ['Final Q2: Define modular design.', ['Using one large file','Breaking a system into independent components','Avoiding functions','Using global variables'], 'Breaking a system into independent components'],
            ['Final Q3: What is a design pattern?', ['A UI theme','A reusable solution to a common problem','A database schema','A programming language'], 'A reusable solution to a common problem'],
            ['Final Q4: What is the benefit of testing?', ['Slowing development','Catching bugs before deployment','Increasing file sizes','Adding complexity'], 'Catching bugs before deployment'],
            ['Final Q5: What does CI/CD stand for?', ['Code Inspection / Code Deployment','Continuous Integration / Continuous Delivery','Central Index / Central Data','None of the above'], 'Continuous Integration / Continuous Delivery'],
        ],
        'Assignment' => [
            ['Assignment Q1: What is peer review?', ['Grading by machines','Evaluation of work by colleagues','Self-assessment only','Ignoring feedback'], 'Evaluation of work by colleagues'],
            ['Assignment Q2: Why is planning important?', ['It is not important','It saves time and reduces errors','It adds bureaucracy','It replaces development'], 'It saves time and reduces errors'],
            ['Assignment Q3: What is a prototype?', ['Final product','Early model to test concepts','Documentation','A database'], 'Early model to test concepts'],
            ['Assignment Q4: What is debugging?', ['Writing new code','Finding and fixing errors','Deploying applications','Reviewing requirements'], 'Finding and fixing errors'],
            ['Assignment Q5: What is agile methodology?', ['Waterfall approach','Sequential model','Iterative and incremental development','Big bang approach'], 'Iterative and incremental development'],
        ],
    ];

    if (isset($bank[$course][$type])) return $bank[$course][$type];
    return $generic[$type];
}

// ─── INSERT ASSESSMENTS + QUESTIONS ───
$courseNames = array_column($courses, 0);
$types = ['Quiz', 'Midterm', 'Final', 'Assignment'];
$marks = ['Quiz' => 30, 'Midterm' => 30, 'Final' => 40, 'Assignment' => 20];

foreach ($courseIDs as $idx => $cid) {
    $cname = $courseNames[$idx];
    foreach ($types as $type) {
        $qname = "$type - $cname";
        $desc  = "$type assessment for $cname";
        $totalMarks = $marks[$type];

        // Insert quiz
        $stmt = $pdo->prepare("INSERT IGNORE INTO quizzes (QuizName, CourseID, QuizType, Description, TotalMarks) VALUES (?,?,?,?,?)");
        $stmt->execute([$qname, $cid, $type, $desc, $totalMarks]);
        $quizID = $pdo->query("SELECT QuizID FROM quizzes WHERE QuizName=" . $pdo->quote($qname) . " AND CourseID=$cid")->fetchColumn();

        // Insert questions
        $questions = getQuestions($cname, $type);
        foreach ($questions as $q) {
            [$qtext, $wrongOptions, $correct] = $q;
            $stmt2 = $pdo->prepare("INSERT INTO questions (QuizID, QuestionText, QuestionType, Marks) VALUES (?,?,'Multiple Choice',?)");
            $stmt2->execute([$quizID, $qtext, ceil($totalMarks / count($questions))]);
            $qid = $pdo->lastInsertId();

            // Insert all options (shuffle correct in)
            $allOptions = $wrongOptions;
            array_splice($allOptions, rand(0, 3), 0, [$correct]);
            $allOptions = array_slice($allOptions, 0, 4);
            foreach ($allOptions as $opt) {
                $isCorrect = ($opt === $correct) ? 1 : 0;
                $stmt3 = $pdo->prepare("INSERT INTO question_options (QuestionID, OptionText, IsCorrect) VALUES (?,?,?)");
                $stmt3->execute([$qid, $opt, $isCorrect]);
            }
        }
    }
}

// ─── ENROLL STUDENTS IN COURSES ───
foreach ($studentIDs as $sidx => $sid) {
    // Each student enrolled in 4-5 courses
    $myCoursesIDs = array_slice($courseIDs, ($sidx * 2) % count($courseIDs), 5);
    foreach ($myCoursesIDs as $cid) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO enrollments (UserID, CourseID, CompletionStatus) VALUES (?,?,'In Progress')");
        $stmt->execute([$sid, $cid]);
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "<h2 style='color:green'>✅ Seeding Complete!</h2><ul>";
echo "<li>" . count($courses)     . " courses added</li>";
echo "<li>" . count($instructors) . " instructors created (password: <strong>pass1234</strong>)</li>";
echo "<li>" . count($students)    . " students created (password: <strong>pass1234</strong>)</li>";
echo "<li>" . (count($courses) * count($types)) . " assessments created (Quiz + Midterm + Final + Assignment per course)</li>";
echo "<li>" . (count($courses) * count($types) * 5) . " questions inserted</li>";
echo "</ul>";
echo "<p><a href='../index.php?page=login'>→ Go to Login</a></p>";
