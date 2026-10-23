<?php

class AiTeacherController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        if (!isLoggedIn()) {
            redirect('?page=login');
        }

        $courses = $this->getRelevantCourses();
        $selectedCourseId = $_GET['course_id'] ?? '';
        if ($selectedCourseId === '' && !empty($courses)) {
            $selectedCourseId = (string)($courses[0]['CourseID'] ?? '');
        }

        $selectedCourse = $this->findCourse($courses, $selectedCourseId);
        $lessons = $selectedCourse ? $this->getCourseLessons($selectedCourse['CourseID'], $selectedCourse['CourseName']) : [];
        $selectedLesson = $_GET['lesson'] ?? '';

        require __DIR__ . '/../views/ai_teacher/index.php';
    }

    public function lessons() {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        header('Content-Type: application/json');
        $courseId = $_GET['course_id'] ?? '';
        $courses = $this->getRelevantCourses();
        $course = $this->findCourse($courses, $courseId);

        if (!$course) {
            echo json_encode(['lessons' => []]);
            exit;
        }

        $lessons = $this->getCourseLessons($course['CourseID'], $course['CourseName']);
        echo json_encode(['lessons' => $lessons]);
        exit;
    }

    public function chat() {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Please log in to chat with the AI Teacher.']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $message = trim((string)($data['message'] ?? ''));
        if ($message === '') {
            echo json_encode(['error' => 'Please provide a message or question.']);
            exit;
        }

        $courseId = (string)($data['course_id'] ?? '');
        $lessonTitle = trim((string)($data['lesson'] ?? ''));
        $mode = trim((string)($data['mode'] ?? 'explain'));
        $history = is_array($data['history'] ?? null) ? $data['history'] : [];

        $courses = $this->getRelevantCourses();
        $course = $courseId !== '' ? $this->findCourse($courses, $courseId) : null;
        $lessons = $course ? $this->getCourseLessons($course['CourseID'], $course['CourseName']) : [];

        // Build course context
        $courseContext = '';
        if ($course) {
            $courseContext .= "Current Course: " . $course['CourseName'] . "\n";
            if (!empty($course['Department'])) {
                $courseContext .= "Department: " . $course['Department'] . "\n";
            }
            if (!empty($course['Description'])) {
                $courseContext .= "Course Overview: " . $course['Description'] . "\n";
            }
            if ($lessonTitle !== '') {
                $courseContext .= "Focus Lesson: {$lessonTitle}\n";
                foreach ($lessons as $l) {
                    if (strcasecmp($l['title'], $lessonTitle) === 0 && !empty($l['content'])) {
                        $courseContext .= "Lesson Material:\n" . substr($l['content'], 0, 1500) . "\n";
                        break;
                    }
                }
            } else if (!empty($lessons)) {
                $courseContext .= "Available Course Lessons:\n";
                foreach (array_slice($lessons, 0, 6) as $l) {
                    $courseContext .= "- " . $l['title'] . "\n";
                }
            }
        }

        $reply = $this->generateResponse($message, $mode, $course, $courseContext, $history, $lessonTitle, $lessons);

        echo json_encode([
            'success' => true,
            'reply'   => $reply,
            'mode'    => $mode,
        ]);
        exit;
    }

    private function generateResponse($question, $mode, $course, $courseContext, array $history, $lessonTitle, array $lessons) {
        // 1. Try OpenAI / OpenAI-compatible API
        if (defined('AI_TEACHER_API_KEY') && AI_TEACHER_API_KEY !== '') {
            $response = $this->callOpenAI($question, $mode, $courseContext, $history);
            if ($response !== null) {
                return $response;
            }
        }

        // 2. Try Google Gemini API
        if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
            $response = $this->callGemini($question, $mode, $courseContext, $history);
            if ($response !== null) {
                return $response;
            }
        }

        // 3. Intelligent grounded fallback
        return $this->generateIntelligentFallback($question, $mode, $course, $lessonTitle, $lessons);
    }

    private function getSystemInstruction($mode, $courseContext) {
        $base = "You are the Univ E-Learning AI Teacher, an interactive, engaging, and supportive academic tutor for university students.
Your mission is to help learners understand complex concepts, retain knowledge, and apply what they learn.

Guidelines:
- Educational & encouraging: Be warm, structured, and pedagogical.
- Course-aligned: Whenever course context is provided, ground your explanations and examples in that curriculum.
- Formatting: Use clear Markdown with headings, bullet points, and code blocks where helpful.
- Academic integrity: Never do students' graded homework or write whole submissions for them. Guide them with hints, steps, and analogies so they learn the reasoning.";

        $modeInstructions = [
            'explain' => "\nMode: Explain. Break down the concept clearly from first principles, explain why it matters, and provide a concrete example.",
            'simplify' => "\nMode: Simplify (ELI5). Explain using simple language, clear everyday analogies, and avoid jargon unless clearly unpacked.",
            'example' => "\nMode: Real-World Example. Provide practical, illustrative examples or real-world code/case studies demonstrating this concept in action.",
            'quiz_me' => "\nMode: Interactive Quiz. If the student just answered a previous question, grade their answer with constructive feedback. Then ask ONE targeted multiple-choice or short-answer question and invite them to answer.",
            'practice' => "\nMode: Practice Exercise. Provide 3 thoughtful questions or a mini hands-on scenario for the learner to solve.",
            'plan' => "\nMode: Study Plan. Create an actionable day-by-day roadmap with specific milestones and checkpoints.",
            'summary' => "\nMode: Summary. Provide a crisp executive summary of key takeaways, main formulas/rules, and common pitfalls.",
        ];

        $instruction = $base . ($modeInstructions[$mode] ?? $modeInstructions['explain']);
        if ($courseContext !== '') {
            $instruction .= "\n\n--- COURSE CONTEXT ---\n" . $courseContext . "--- END CONTEXT ---";
        }

        return $instruction;
    }

    private function callOpenAI($question, $mode, $courseContext, array $history) {
        $system = $this->getSystemInstruction($mode, $courseContext);
        $messages = [['role' => 'system', 'content' => $system]];

        // Append recent conversation history (up to 8 turns)
        $cleanHistory = array_slice($history, -8);
        foreach ($cleanHistory as $h) {
            $role = ($h['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $content = trim((string)($h['content'] ?? ''));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        $payload = [
            'model' => AI_TEACHER_MODEL,
            'messages' => $messages,
            'temperature' => 0.5,
            'max_tokens' => 900,
        ];

        $baseUrl = defined('AI_TEACHER_BASE_URL') && AI_TEACHER_BASE_URL !== '' ? AI_TEACHER_BASE_URL : 'https://api.openai.com/v1';
        $url = rtrim($baseUrl, '/') . '/chat/completions';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . AI_TEACHER_API_KEY . "\r\n",
                'content' => json_encode($payload),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    private function callGemini($question, $mode, $courseContext, array $history) {
        $system = $this->getSystemInstruction($mode, $courseContext);
        $model = defined('GEMINI_MODEL') && GEMINI_MODEL !== '' ? GEMINI_MODEL : 'gemini-2.0-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode(GEMINI_API_KEY);

        $contents = [];
        $cleanHistory = array_slice($history, -8);
        foreach ($cleanHistory as $h) {
            $role = ($h['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $content = trim((string)($h['content'] ?? ''));
            if ($content !== '') {
                $contents[] = ['role' => $role, 'parts' => [['text' => $content]]];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 900,
            ],
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    private function generateIntelligentFallback($question, $mode, $course, $lessonTitle, array $lessons) {
        $courseName = $course['CourseName'] ?? 'your course';
        $topic = $this->extractTopic($question, $lessonTitle ?: $courseName);

        // Check if question matches any lesson content
        $matchedLesson = null;
        $matchedSnippet = '';
        foreach ($lessons as $l) {
            if (stripos($l['title'], $topic) !== false || stripos($topic, $l['title']) !== false) {
                $matchedLesson = $l;
                break;
            }
            if (!empty($l['content']) && stripos($l['content'], $topic) !== false) {
                $matchedLesson = $l;
                $matchedSnippet = substr($l['content'], 0, 300) . '...';
                break;
            }
        }

        if ($mode === 'quiz_me') {
            return "### 🎯 Quick Knowledge Check: **{$topic}**\n\n"
                . "Here is your first challenge to test your grasp of this topic in **{$courseName}**:\n\n"
                . "**Question:**\n"
                . "Explain how **{$topic}** functions and describe one scenario where an engineer or student would choose to apply it.\n\n"
                . "> **Tip:** Type your answer below and click Send! I'll analyze your response and guide you on what you got right and how to sharpen it.";
        }

        if ($mode === 'practice') {
            return "### 📝 Practice Exercises: **{$topic}**\n\n"
                . "Work through these targeted exercises for **{$courseName}**:\n\n"
                . "1. **Definition & Purpose:** In your own words, what core problem does {$topic} solve?\n"
                . "2. **Application:** Create a minimal, working example of {$topic} applied to a real scenario.\n"
                . "3. **Edge Case Analysis:** What could go wrong when implementing {$topic}, and how do you prevent common errors?\n"
                . "4. **Self-Reflection:** What aspect of {$topic} feels least intuitive to you right now?\n\n"
                . "💡 *Reply with your thoughts on any of these questions, and I'll review your answers!*";
        }

        if ($mode === 'simplify') {
            return "### 💡 Simplifying: **{$topic}** (Explained Simply)\n\n"
                . "Think of **{$topic}** like building blocks:\n\n"
                . "- **The Big Picture:** At its core, {$topic} is just a tool designed to make solving problems in {$courseName} predictable and manageable.\n"
                . "- **Everyday Analogy:** Imagine organizing a workshop. Instead of dumping every tool on the floor, you label designated drawers so you can find and use what you need immediately. {$topic} provides that exact structure for your knowledge.\n"
                . "- **Why it matters:** Once you master this building block, more advanced topics in {$courseName} become much easier to understand.\n\n"
                . "What specific part of {$topic} would you like me to clarify further?";
        }

        if ($mode === 'example') {
            return "### 🔍 Real-World Example: **{$topic}**\n\n"
                . "Here is how **{$topic}** comes alive in **{$courseName}**:\n\n"
                . "- **The Scenario:** Suppose you are building a modern system that needs to process and present information reliably.\n"
                . "- **How {$topic} is applied:** By applying the principles of {$topic}, you separate concerns, validate inputs, and ensure data flows smoothly without crashes.\n"
                . "- **Key Takeaway:** Always test with edge cases—such as empty input, unexpected types, or high volume—to verify your implementation holds up.\n\n"
                . "Would you like to try writing an example together?";
        }

        if ($mode === 'plan') {
            return "### 📅 5-Day Mastery Roadmap: **{$topic}**\n\n"
                . "Follow this structured plan to master {$topic} in **{$courseName}**:\n\n"
                . "- **Day 1: Foundations & Vocabulary**\n"
                . "  Read through the lesson material and write down the core concepts in your own words.\n"
                . "- **Day 2: Dissect Examples**\n"
                . "  Step through sample code or exercises line by line. Note how inputs turn into outputs.\n"
                . "- **Day 3: Active Recall & Practice**\n"
                . "  Solve 3 practice problems without looking at reference notes.\n"
                . "- **Day 4: Debugging & Edge Cases**\n"
                . "  Intentionally test corner cases and learn how errors are diagnosed.\n"
                . "- **Day 5: Synthesis**\n"
                . "  Summarize the topic out loud or explain it back to me here in chat.\n\n"
                . "Ready to start Day 1? Ask me any foundational question!";
        }

        if ($mode === 'summary') {
            return "### 📌 Core Summary: **{$topic}**\n\n"
                . "Here are the essential takeaways for **{$courseName}**:\n\n"
                . "- **Key Concept:** {$topic} is a cornerstone of this course.\n"
                . "- **Primary Rule:** Focus on clear structure, predictable execution, and validating results.\n"
                . "- **Common Pitfall:** Skipping fundamentals before attempting advanced implementations.\n"
                . ($matchedSnippet ? "\n**From Course Material:**\n> " . $matchedSnippet . "\n\n" : "")
                . "Feel free to ask a follow-up question about any detail above!";
        }

        // Default 'explain' mode
        return "### 📖 Concept Breakdown: **{$topic}**\n\n"
            . "To master **{$topic}** in **{$courseName}**, let's look at it through three perspectives:\n\n"
            . "1. **What is it?**\n"
            . "   {$topic} represents a fundamental mechanism for structuring ideas and solving problems systematically.\n\n"
            . "2. **Why does it matter?**\n"
            . "   Without {$topic}, complex tasks in {$courseName} become messy and error-prone. It gives you a reliable framework to reason about your work.\n\n"
            . "3. **How do you apply it?**\n"
            . "   Start by identifying the inputs, determine the rules or transformations that need to occur, and inspect the resulting output.\n\n"
            . ($matchedSnippet ? "💡 **Relevant Course Excerpt:**\n> " . $matchedSnippet . "\n\n" : "")
            . "**Your turn:** Would you like a simplified analogy, a concrete code example, or a quick practice quiz on this?";
    }

    private function extractTopic($question, $fallback) {
        $clean = trim(preg_replace('/\s+/', ' ', $question));
        if ($clean === '') {
            return $fallback;
        }

        // Remove filler prefixes
        $clean = preg_replace('/^(explain|what is|tell me about|how does|help me understand|can you explain)\s+/i', '', $clean);
        return strlen($clean) > 80 ? substr($clean, 0, 77) . '...' : $clean;
    }

    private function getRelevantCourses() {
        $userId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? 'Student';

        if ($userType === 'Student') {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT c.CourseID, c.CourseName, c.Description, c.Department
                FROM courses c
                JOIN enrollments e ON e.CourseID = c.CourseID
                WHERE e.UserID = ?
                ORDER BY c.CourseName
            ");
            $stmt->execute([$userId]);
            $enrolled = $stmt->fetchAll();
            if (!empty($enrolled)) {
                return $enrolled;
            }
        }

        if ($userType === 'Instructor') {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT c.CourseID, c.CourseName, c.Description, c.Department
                FROM courses c
                JOIN instructor_courses ic ON ic.CourseID = c.CourseID
                WHERE ic.InstructorID = ?
                ORDER BY c.CourseName
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query("SELECT CourseID, CourseName, Description, Department FROM courses ORDER BY CourseName");
        return $stmt->fetchAll();
    }

    private function findCourse(array $courses, $courseId) {
        foreach ($courses as $course) {
            if ((string)($course['CourseID'] ?? '') === (string)$courseId) {
                return $course;
            }
        }
        return null;
    }

    private function getCourseLessons($courseId, $courseName) {
        $stmt = $this->pdo->prepare("SELECT ContentTitle, ContentURL, ContentType FROM course_contents WHERE CourseID = ? ORDER BY ContentID");
        $stmt->execute([$courseId]);
        $rows = $stmt->fetchAll();

        $lessons = [];
        foreach ($rows as $row) {
            $title = $row['ContentTitle'] ?? $row['contenttitle'] ?? '';
            $url = $row['ContentURL'] ?? $row['contenturl'] ?? '';
            if ($title !== '') {
                $lessons[] = [
                    'title'   => $title,
                    'content' => $url,
                ];
            }
        }

        if (empty($lessons)) {
            // Use built-in course content if none is stored in DB
            $builtIn = $this->getBuiltInCourseLessons($courseName);
            foreach ($builtIn as $b) {
                $lessons[] = [
                    'title'   => $b[0],
                    'content' => $b[1],
                ];
            }
        }

        return $lessons;
    }

    private function getBuiltInCourseLessons($courseName) {
        $key = strtolower($courseName);
        $catalog = [
            'python' => [
                ['Python Variables and Control Flow', "Variables store values, conditionals choose between paths, and loops repeat work. Practice using a list of scores, looping through each score, and counting how many are above 70."],
                ['Functions and Modules', "A function packages a repeatable idea: input goes in, useful output comes out. Write small functions such as calculate_average(scores) or format_username(name)."],
                ['Working With Data', "Most useful Python programs read data, clean it, and produce a result. Practice opening CSV files, converting strings to numbers, and handling missing values."],
            ],
            'web' => [
                ['HTML, CSS, and Page Structure', "HTML gives content meaning: headings, paragraphs, forms, and buttons. CSS controls layout, spacing, and responsive design."],
                ['JavaScript Interactivity', "JavaScript makes the page respond: event listeners for clicks, DOM updates, form validation, and asynchronous requests."],
                ['Backend and APIs', "A backend route receives a request, validates it, talks to the database, and returns JSON or HTML."],
            ],
            'database' => [
                ['Relational Database Design', "Users, courses, enrollments, quizzes, and results are separate tables connected by foreign keys to prevent data duplication."],
                ['SQL Queries That Matter', "SELECT retrieves rows, JOIN combines related tables, WHERE filters, GROUP BY summarizes, and ORDER BY sorts."],
                ['Indexes and Data Integrity', "Indexes speed up lookups on common columns like UserID and CourseID. Foreign keys ensure referential integrity."],
            ],
            'algorithm' => [
                ['Big O and Efficiency', "Algorithms are judged by how they scale: O(1) constant, O(n) linear, O(log n) logarithmic, O(n^2) quadratic."],
                ['Core Data Structures', "Arrays for ordered data, hash tables for fast lookup, stacks for LIFO operations, queues for FIFO processing."],
                ['Problem Solving Patterns', "Break problem into smaller subproblems, write tests for edge cases, and analyze space/time complexity."],
            ],
            'cloud' => [
                ['Cloud Service Models', "IaaS provides raw infrastructure, PaaS provides managed application runtimes, and SaaS provides finished software."],
                ['Scalability and Reliability', "Horizontal scaling, load balancing, health checks, automated failover, and geographic redundancy."],
                ['Cloud Security Basics', "Principle of least privilege, IAM policies, encrypted secrets management, and network security groups."],
            ],
            'machine learning' => [
                ['Data, Features, and Labels', "Features are inputs, labels are ground-truth targets, and the model learns statistical associations between them."],
                ['Training and Evaluation', "Train/test splits, loss functions, metrics like accuracy/precision/recall, and guarding against overfitting."],
                ['Responsible AI', "Evaluating models for bias, fairness, transparency, and data privacy."],
            ],
            'cyber' => [
                ['Threats and Attack Surfaces', "Common vectors include phishing, SQL injection, cross-site scripting (XSS), and weak credential management."],
                ['Defense in Depth', "Layering firewalls, input sanitation, multi-factor authentication, and encrypted data in transit and at rest."],
                ['Incident Response', "Identification, containment, eradication, recovery, and post-incident analysis."],
            ],
        ];

        foreach ($catalog as $needle => $lessons) {
            if (strpos($key, $needle) !== false) {
                return $lessons;
            }
        }

        return [
            ['Core Foundations', "An introduction to the fundamental terminology, goals, and key tools of {$courseName}."],
            ['Key Methodologies & Techniques', "Practical methods, standard workflows, and problem-solving steps used in {$courseName}."],
            ['Practical Application & Review', "Real-world project scenarios, review questions, and preparation for assessments in {$courseName}."],
        ];
    }
}
