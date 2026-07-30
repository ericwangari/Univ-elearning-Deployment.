<?php
require_once __DIR__ . '/../models/Course.php';

class CourseController {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function dashboard() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Get enrollment statistics
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE UserID = ?");
        $stmt->execute([$user_id]);
        $enrolled_count = $stmt->fetchColumn();

        $course_search = trim($_GET['course_search'] ?? '');

        // Get enrolled courses (with deterministic latest enrollment per course)
        $query = "SELECT c.*, e.CompletionStatus, e.EnrollmentDate,
                  (SELECT COUNT(*) FROM quizzes WHERE CourseID = c.CourseID) as TotalQuizzes,
                  (SELECT COUNT(DISTINCT q.QuizID)
                   FROM quizzes q
                   JOIN results r ON r.QuizID = q.QuizID
                   WHERE r.UserID = ?
                     AND q.CourseID = c.CourseID
                     AND q.TotalMarks > 0
                     AND ((r.Score / q.TotalMarks) * 100) >= 50) as CompletedQuizzes
                  FROM courses c
                  JOIN enrollments e ON c.CourseID = e.CourseID
                  JOIN (
                      SELECT CourseID, MAX(EnrollmentID) as EnrollmentID
                      FROM enrollments
                      WHERE UserID = ?
                      GROUP BY CourseID
                  ) latest_e ON e.EnrollmentID = latest_e.EnrollmentID
                  WHERE 1=1";
        $params = [$user_id, $user_id];

        if ($course_search !== '') {
            $query .= " AND (c.CourseName LIKE ? OR c.Description LIKE ?)";
            $params[] = "%$course_search%";
            $params[] = "%$course_search%";
        }

        $query .= " ORDER BY e.EnrollmentDate DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $enrolled_courses = $stmt->fetchAll();

        foreach ($enrolled_courses as &$course) {
            $total_quizzes = (int)$course['TotalQuizzes'];
            $completed_quizzes = (int)$course['CompletedQuizzes'];
            $course['Progress'] = ($total_quizzes > 0) ? round(($completed_quizzes / $total_quizzes) * 100) : 0;

            $old_status = $course['CompletionStatus'] ?? '';
            $new_status = ($completed_quizzes === $total_quizzes && $total_quizzes > 0) ? 'Completed' : 'In Progress';
            $course['CompletionStatus'] = $new_status;

            if ($old_status !== $new_status) {
                $update_stmt = $this->pdo->prepare("UPDATE enrollments SET CompletionStatus = ?
                                                   WHERE UserID = ? AND CourseID = ?");
                $update_stmt->execute([$new_status, $user_id, $course['CourseID']]);
            }
        }
        unset($course);

        // Get completed quizzes count from results table
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM results WHERE UserID = ?");
        $stmt->execute([$user_id]);
        $completed_quizzes = $stmt->fetchColumn();

        // Calculate average score from results
        $average_score = 0;
        if ($completed_quizzes > 0) {
            $stmt = $this->pdo->prepare("SELECT AVG((r.Score / q.TotalMarks) * 100) as avg_score
                                         FROM results r
                                         JOIN quizzes q ON r.QuizID = q.QuizID
                                         WHERE r.UserID = ? AND q.TotalMarks > 0");
            $stmt->execute([$user_id]);
            $score_data = $stmt->fetch();
            $average_score = ($score_data && isset($score_data['avg_score'])) ? round($score_data['avg_score'], 2) : 0;
        }

        // Get breakdown of completed assessments
        $assessment_stats = [
            'quizzes' => $this->countCompletedByType($user_id, 'Quiz'),
            'midterms' => $this->countCompletedByType($user_id, 'Midterm'),
            'finals' => $this->countCompletedByType($user_id, 'Final'),
            'assignments' => $this->countCompletedByType($user_id, 'Assignment'),
        ];

        // Get recent results
        $stmt = $this->pdo->prepare("SELECT r.*, q.QuizName, q.QuizType, q.TotalMarks 
                                     FROM results r 
                                     JOIN quizzes q ON r.QuizID = q.QuizID 
                                     WHERE r.UserID = ? 
                                     ORDER BY r.SubmittedAt DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_results = $stmt->fetchAll();

        require __DIR__ . '/../views/student/dashboard.php';
    }

    public function courses() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Get search and filter
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'new';

        $query = "SELECT c.*, 
                  CASE WHEN e.EnrollmentID IS NOT NULL THEN 1 ELSE 0 END as IsEnrolled,
                  COUNT(DISTINCT e2.EnrollmentID) as StudentCount,
                  GROUP_CONCAT(DISTINCT u.Username ORDER BY u.Username SEPARATOR ', ') as InstructorNames
                  FROM courses c 
                  LEFT JOIN enrollments e ON c.CourseID = e.CourseID AND e.UserID = ?
                  LEFT JOIN enrollments e2 ON c.CourseID = e2.CourseID
                  LEFT JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                  LEFT JOIN users u ON ic.InstructorID = u.UserID
                  WHERE 1=1";
        
        $params = [$user_id];

        if (!empty($search)) {
            $query .= " AND (c.CourseName LIKE ? OR c.Description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " GROUP BY c.CourseID";

        // Add sorting
        if ($sort === 'new') {
            $query .= " ORDER BY c.CreatedAt DESC";
        } elseif ($sort === 'popular') {
            $query .= " ORDER BY StudentCount DESC";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();

        require __DIR__ . '/../views/student/courses.php';
    }

    public function courseDetails() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $course_id = $_GET['id'] ?? null;

        // Get course
        $stmt = $this->pdo->prepare("SELECT c.*, COUNT(DISTINCT e.EnrollmentID) as StudentCount,
                                     GROUP_CONCAT(DISTINCT u.Username ORDER BY u.Username SEPARATOR ', ') as InstructorNames
                                     FROM courses c 
                                     LEFT JOIN enrollments e ON c.CourseID = e.CourseID 
                                     LEFT JOIN instructor_courses ic ON c.CourseID = ic.CourseID
                                     LEFT JOIN users u ON ic.InstructorID = u.UserID
                                     WHERE c.CourseID = ? 
                                     GROUP BY c.CourseID");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if (!$course) {
            header("Location: index.php?page=courses");
            exit;
        }

        // Check if user is enrolled
        $stmt = $this->pdo->prepare("SELECT * FROM enrollments WHERE UserID = ? AND CourseID = ?");
        $stmt->execute([$user_id, $course_id]);
        $is_enrolled = $stmt->fetch() ? true : false;

        // Get course content
        $stmt = $this->pdo->prepare("SELECT * FROM course_contents WHERE CourseID = ? ORDER BY ContentID");
        $stmt->execute([$course_id]);
        $contents = $stmt->fetchAll();

        // Get quizzes
        $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE CourseID = ? ORDER BY QuizID");
        $stmt->execute([$course_id]);
        $quizzes = $stmt->fetchAll();

        require __DIR__ . '/../views/student/course_details.php';
    }

    private function shouldUseBuiltInLearningContent($contents) {
        if (empty($contents)) {
            return true;
        }

        $placeholderCount = 0;

        foreach ($contents as $content) {
            $title = strtolower($content['ContentTitle'] ?? '');
            $body = strtolower($content['ContentURL'] ?? '');

            if (
                strpos($body, 'dqw4w9wgcq') !== false ||
                strpos($body, 'youtube.com/watch?v=example') !== false ||
                strpos($body, 'example.com/') !== false ||
                strpos($body, 'docs.example.com/') !== false ||
                strpos($body, 'refer to the textbook chapter 1') !== false ||
                strpos($title, 'lesson 1: foundations') !== false ||
                strpos($title, 'introduction lecture') !== false ||
                strpos($title, 'course slides week 1') !== false ||
                strpos($title, 'deep dive - core concepts') !== false ||
                strpos($title, 'assignment brief') !== false ||
                strpos($title, 'recommended reading') !== false
            ) {
                $placeholderCount++;
            }
        }

        return $placeholderCount > 0;
    }

    private function getBuiltInLearningContent($courseName) {
        $key = strtolower($courseName);
        $catalog = [
            'python' => [
                ['Python Variables and Control Flow', "Start by treating Python as a language for describing steps clearly. Variables store values, conditionals choose between paths, and loops repeat work. Example: use a list of scores, loop through each score, and count how many are above 70. This is the foundation for scripts, automation, data analysis, and web backends."],
                ['Functions and Modules', "A function packages a repeatable idea: input goes in, useful output comes out. Write small functions such as calculate_average(scores) or format_username(name). Modules let you split a bigger program into files so students can test and reuse code without rewriting it."],
                ['Working With Data', "Most useful Python programs read data, clean it, and produce a result. Practice opening CSV files, converting strings to numbers, handling missing values, and printing a short summary. The goal is not memorizing syntax; it is learning how to turn messy information into decisions."],
            ],
            'web' => [
                ['HTML, CSS, and Page Structure', "HTML gives content meaning: headings, paragraphs, forms, buttons, and navigation. CSS controls layout, spacing, color, and responsive behavior. A strong page starts with semantic HTML, then uses CSS grid or flexbox to make it usable on phones and desktops."],
                ['JavaScript Interactivity', "JavaScript makes the page respond. Use event listeners for clicks and form submissions, validate input before sending it, and update the DOM with clear feedback. Good interaction is predictable: users should always know what changed and why."],
                ['Backend and APIs', "Modern web apps send data between frontend and backend. A backend route receives a request, validates it, talks to the database, and returns a response. Practice building routes for login, listing courses, creating content, and showing student progress."],
            ],
            'database' => [
                ['Relational Database Design', "A relational database stores related facts in tables. Users, courses, enrollments, quizzes, and results should be separate tables connected by IDs. Good design avoids duplicate data and makes questions easy to answer, such as which students are enrolled in a course."],
                ['SQL Queries That Matter', "SQL is how you ask the database questions. SELECT retrieves rows, JOIN combines related tables, WHERE filters, GROUP BY summarizes, and ORDER BY sorts. Practice writing a query that counts enrollments per course and another that calculates average quiz scores."],
                ['Indexes and Data Integrity', "Indexes speed up lookups on common columns like UserID and CourseID. Foreign keys protect relationships, so an enrollment cannot point to a course that does not exist. Constraints are part of teaching the database to reject bad data."],
            ],
            'algorithm' => [
                ['Big O and Efficiency', "Algorithms are judged by how they grow as input grows. A loop through n items is O(n), nested loops are often O(n^2), and binary search is O(log n). The point is to predict whether a solution will still work when the dataset becomes large."],
                ['Core Data Structures', "Arrays are good for ordered lists, hash maps are good for fast lookup, stacks handle last-in-first-out tasks, and queues handle first-in-first-out tasks. Choosing the right structure often matters more than writing clever code."],
                ['Problem Solving Pattern', "Read the problem, write examples, identify inputs and outputs, choose a data structure, then code the simplest correct version. After that, test edge cases: empty input, one item, duplicates, and very large values."],
            ],
            'cloud' => [
                ['Cloud Service Models', "Cloud platforms provide infrastructure without buying physical servers. IaaS gives virtual machines, PaaS gives managed app hosting, and SaaS gives complete software. Students should understand which responsibility stays with the developer and which moves to the provider."],
                ['Scalability and Reliability', "A reliable cloud system uses load balancing, health checks, backups, and monitoring. Scaling means adding capacity when traffic grows. A good architecture handles failure by expecting it instead of pretending it will not happen."],
                ['Security Basics', "Cloud security starts with least privilege. Give users and services only the permissions they need. Protect secrets, use HTTPS, keep logs, and separate public resources from private databases."],
            ],
            'machine learning' => [
                ['Data, Features, and Labels', "Machine learning starts with examples. Features are the inputs, labels are the answers, and the model learns patterns between them. A student predicting house prices might use size, location, rooms, and age as features."],
                ['Training and Evaluation', "Training fits a model to data; evaluation checks whether it generalizes. Split data into training and test sets, measure accuracy or error, and watch for overfitting, where a model memorizes examples instead of learning patterns."],
                ['Responsible AI', "Models can reflect bias in their training data. Responsible AI means checking performance across groups, explaining limitations, protecting user data, and using human review for high-impact decisions."],
            ],
            'cyber' => [
                ['Threats and Attack Surfaces', "Cyber defense begins by identifying what can be attacked: accounts, networks, servers, databases, forms, and APIs. Common threats include phishing, weak passwords, injection attacks, malware, and unpatched software."],
                ['Defense in Depth', "No single control is enough. Use strong authentication, input validation, firewalls, least privilege, backups, logging, and monitoring together. If one layer fails, another should reduce the damage."],
                ['Incident Response', "When something goes wrong, respond in order: detect, contain, investigate, remove the threat, recover systems, and document lessons learned. Speed matters, but clear records matter too."],
            ],
            'flutter' => [
                ['Widgets and Layout', "Flutter apps are built from widgets. Text, images, rows, columns, buttons, and screens are all widgets. Layout comes from composing small widgets into larger interfaces that adapt to different screen sizes."],
                ['State and Navigation', "State is data that can change, such as selected tabs, form values, or loaded courses. Navigation moves users between screens. A good mobile app keeps state predictable and makes the back button behave naturally."],
                ['Connecting to APIs', "Real apps fetch and send data. Use HTTP requests to load courses, submit quiz answers, and update profiles. Always handle loading, success, empty, and error states so the app feels stable."],
            ],
            'mobile app' => [
                ['Mobile UX Fundamentals', "Mobile screens are small, touch-driven, and often used quickly. Prioritize readable text, large tap targets, simple navigation, and fast feedback. A good mobile learning app makes the next action obvious."],
                ['App Data Flow', "Most mobile apps move data from an API into local screens. Students should understand request, response, parsing, state update, and error handling. This flow powers login, course lists, lessons, and quiz submissions."],
                ['Testing on Devices', "Mobile apps must be tested on different screen sizes and network conditions. Check forms, scrolling, orientation, slow loading, and offline behavior. Real-device testing catches issues that desktop previews miss."],
            ],
            'devops' => [
                ['Continuous Integration', "CI runs checks automatically when code changes. A pipeline can install dependencies, run tests, lint code, and report failures before broken code reaches users."],
                ['Containers and Deployment', "Containers package an app with its runtime so it behaves consistently. Docker images can be deployed to servers or cloud platforms, making releases repeatable and easier to roll back."],
                ['Monitoring and Logs', "After deployment, teams need to know if the app is healthy. Logs explain what happened, metrics show trends, and alerts warn when users may be affected."],
            ],
            'design' => [
                ['User Research and Goals', "UI/UX starts by understanding users. Identify what they need to do, what frustrates them, and what success looks like. Design decisions should support real tasks, not just visual taste."],
                ['Layout, Hierarchy, and Contrast', "Visual hierarchy tells users what matters first. Use spacing, size, contrast, and grouping to guide attention. Good layouts make scanning easy and reduce confusion."],
                ['Prototyping and Testing', "A prototype lets users try a flow before full development. Test whether they can complete tasks, watch where they hesitate, and improve the design based on evidence."],
            ],
            'java' => [
                ['Object-Oriented Programming', "Java organizes code with classes and objects. Classes define structure and behavior; objects are real instances. Encapsulation keeps data safe, inheritance shares behavior, and interfaces define contracts."],
                ['Enterprise Layers', "Enterprise Java apps often separate controllers, services, repositories, and models. This separation keeps business rules away from database details and makes large systems easier to test."],
                ['Transactions and Reliability', "Enterprise systems must handle failures carefully. Transactions keep related database changes together so money, grades, orders, or enrollments do not end up half-saved."],
            ],
            'network' => [
                ['Network Foundations', "Networks move data between devices using protocols. IP addresses identify devices, DNS turns names into addresses, and TCP helps deliver data reliably."],
                ['Routing and Switching', "Switches move traffic inside a local network; routers move traffic between networks. Understanding both helps students diagnose why a device can or cannot reach a service."],
                ['Network Security Controls', "Firewalls, VLANs, VPNs, access control lists, and monitoring tools reduce risk. Security is strongest when network design limits unnecessary access."],
            ],
            'blockchain' => [
                ['Ledgers and Blocks', "A blockchain is a shared ledger where transactions are grouped into blocks. Each block references the previous one, making history difficult to alter without detection."],
                ['Consensus', "Consensus is how participants agree on the valid state of the ledger. Different systems use different methods, such as proof of work or proof of stake, with tradeoffs in speed, energy, and decentralization."],
                ['Smart Contracts', "Smart contracts are programs stored on a blockchain. They can manage tokens, voting, payments, or agreements, but bugs can be expensive because deployed code is hard to change."],
            ],
            'unity' => [
                ['Scenes, GameObjects, and Components', "Unity games are built from scenes containing GameObjects. Components add behavior, physics, rendering, audio, or scripts. This composition model lets students build complex behavior from small parts."],
                ['Game Loop and Input', "Games repeatedly read input, update state, and render frames. Students should connect keyboard, mouse, or touch input to player movement and game actions."],
                ['Physics and Feedback', "Physics handles collisions, gravity, and forces. Feedback such as sound, animation, particles, and score changes helps players understand the result of their actions."],
            ],
            'os' => [
                ['Processes and Threads', "An operating system runs programs as processes. Threads allow work inside a process to happen concurrently. Scheduling decides which work gets CPU time."],
                ['Memory Management', "Memory is divided and protected so programs do not overwrite each other. Concepts like stack, heap, paging, and virtual memory explain how large programs run safely."],
                ['Files and System Calls', "Programs ask the OS to open files, read input, allocate memory, and use devices through system calls. This boundary protects the machine while giving programs useful services."],
            ],
        ];

        foreach ($catalog as $needle => $lessons) {
            if (strpos($key, $needle) !== false) {
                return $this->formatBuiltInLessons($lessons, $courseName);
            }
        }

        return $this->formatBuiltInLessons([
            ['Course Roadmap', "This course is organized around the core ideas behind {$courseName}. Start by identifying the main vocabulary, then connect each concept to a practical example. Keep notes on what each idea helps you do."],
            ['Practice Activity', "Choose one real problem related to {$courseName}. Break it into steps, list the tools or concepts needed, and write a short explanation of your solution. Learning improves when you turn ideas into action."],
            ['Review Checklist', "Before taking the assessment, explain the main concepts in your own words, create one example, identify one common mistake, and write down one question you still have."],
        ], $courseName);
    }

    private function formatBuiltInLessons($lessons, $courseName) {
        $formatted = [];
        $id = 1;
        $courseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $courseName));

        foreach ($lessons as $lesson) {
            $titleSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $lesson[0]));
            $materialUrl = 'public/course_material.php?title=' . urlencode($courseName . ': ' . $lesson[0]);
            $pdfUrl = 'public/course_pdf.php?title=' . urlencode($courseName . ': ' . $lesson[0]) . '&body=' . urlencode($lesson[1]);

            if (strpos(strtolower($courseName), 'design') !== false || strpos(strtolower($courseName), 'ui/ux') !== false) {
                $uiuxMap = [
                    'user-research-and-goals' => 'research',
                    'layout-hierarchy-and-contrast' => 'layout',
                    'prototyping-and-testing' => 'testing',
                ];
                $materialUrl = 'public/course_material.php?course=uiux&lesson=' . ($uiuxMap[$titleSlug] ?? 'research');
            }

            $formatted[] = [
                'ContentID' => $id++,
                'ContentType' => 'Text',
                'ContentTitle' => $lesson[0],
                'ContentURL' => $lesson[1],
                'MaterialURL' => $materialUrl . '&course_name=' . urlencode($courseSlug),
                'PdfURL' => $pdfUrl,
            ];
        }

        return $formatted;
    }

    public function myEnrollments() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $search = trim($_GET['search'] ?? '');
        $course_id = $_GET['course_id'] ?? null;
        $selected_course_id = $course_id;

        $stmt = $this->pdo->prepare("SELECT DISTINCT c.CourseID, c.CourseName
                                     FROM enrollments e
                                     JOIN courses c ON e.CourseID = c.CourseID
                                     WHERE e.UserID = ?
                                     ORDER BY c.CourseName");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll();

        // Get enrollments with deterministic latest enrollment per course
        $sql = "SELECT c.*, latest_e.CompletionStatus, latest_e.EnrollmentDate,
                (SELECT COUNT(*) FROM quizzes WHERE CourseID = c.CourseID) as TotalQuizzes,
                (SELECT COUNT(DISTINCT q.QuizID)
                 FROM quizzes q
                 JOIN results r ON r.QuizID = q.QuizID
                 WHERE r.UserID = ?
                   AND q.CourseID = c.CourseID
                   AND q.TotalMarks > 0
                   AND ((r.Score / q.TotalMarks) * 100) >= 50) as CompletedQuizzes
                FROM courses c
                JOIN enrollments latest_e ON c.CourseID = latest_e.CourseID
                JOIN (
                    SELECT CourseID, MAX(EnrollmentID) as EnrollmentID
                    FROM enrollments
                    WHERE UserID = ?
                    GROUP BY CourseID
                ) latest_ids ON latest_e.EnrollmentID = latest_ids.EnrollmentID
                WHERE 1=1";
        $params = [$user_id, $user_id];

        if ($course_id) {
            $sql .= " AND c.CourseID = ?";
            $params[] = $course_id;
        }

        if ($search !== '') {
            $sql .= " AND (c.CourseName LIKE ? OR c.Description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY latest_e.EnrollmentDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $enrollments = $stmt->fetchAll();

        // Calculate progress for each enrollment
        foreach ($enrollments as &$enrollment) {
            $total_quizzes = $enrollment['TotalQuizzes'];
            $completed_quizzes = $enrollment['CompletedQuizzes'];
            
            if ($total_quizzes > 0) {
                $enrollment['Progress'] = round(($completed_quizzes / $total_quizzes) * 100);
            } else {
                $enrollment['Progress'] = 0;
            }
            
            $old_status = $enrollment['CompletionStatus'] ?? '';
            $new_status = ($completed_quizzes == $total_quizzes && $total_quizzes > 0) ? 'Completed' : 'In Progress';
            $enrollment['CompletionStatus'] = $new_status;

            if ($old_status !== $new_status) {
                $update_stmt = $this->pdo->prepare("UPDATE enrollments SET CompletionStatus = ?
                                                   WHERE UserID = ? AND CourseID = ?");
                $update_stmt->execute([$new_status, $user_id, $enrollment['CourseID']]);
            }
        }
        unset($enrollment);

        require __DIR__ . '/../views/student/my_enrollments.php';
    }

    public function myResults() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("SELECT r.*, c.CourseName, q.QuizName, q.QuizType, q.TotalMarks 
                                     FROM results r 
                                     JOIN courses c ON r.CourseID = c.CourseID 
                                     JOIN quizzes q ON r.QuizID = q.QuizID 
                                     WHERE r.UserID = ? 
                                     ORDER BY r.SubmittedAt DESC");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll();

        require __DIR__ . '/../views/student/results.php';
    }

    public function enroll() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'] ?? null;

        if ($course_id) {
            // Check if already enrolled
            $stmt = $this->pdo->prepare("SELECT * FROM enrollments WHERE UserID = ? AND CourseID = ?");
            $stmt->execute([$user_id, $course_id]);

            if (!$stmt->fetch()) {
                $stmt = $this->pdo->prepare("INSERT INTO enrollments (UserID, CourseID) VALUES (?, ?)");
                $stmt->execute([$user_id, $course_id]);
            }
        }

        $redirect = $_POST['redirect'] ?? 'courses';
        header("Location: index.php?page=$redirect&id=$course_id");
    }

    public function drop() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'] ?? null;

        if ($course_id) {
            $stmt = $this->pdo->prepare("DELETE FROM enrollments WHERE UserID = ? AND CourseID = ?");
            $stmt->execute([$user_id, $course_id]);
        }

        $redirect = $_POST['redirect'] ?? 'courses';
        header("Location: index.php?page=$redirect");
    }

    private function countCompletedByType($user_id, $type) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM results r 
                                     JOIN quizzes q ON r.QuizID = q.QuizID 
                                     WHERE r.UserID = ? AND q.QuizType = ?");
        $stmt->execute([$user_id, $type]);
        return $stmt->fetchColumn();
    }
}
