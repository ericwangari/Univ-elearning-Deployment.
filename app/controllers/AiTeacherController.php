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

    public function getCourseLessons($courseId, $courseName) {
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

    /**
     * Generate assessment questions grounded in course materials for instructors.
     */
    public function generateAssessmentQuestions(array $course, $lessonTitle = '', $count = 5, $type = 'all', $difficulty = 'standard') {
        $courseId = $course['CourseID'] ?? '';
        $courseName = $course['CourseName'] ?? 'Course';
        $lessons = $this->getCourseLessons($courseId, $courseName);

        // Gather relevant curriculum context
        $context = "Course: {$courseName}\n";
        if (!empty($course['Department'])) {
            $context .= "Department: {$course['Department']}\n";
        }
        if (!empty($course['Description'])) {
            $context .= "Overview: {$course['Description']}\n";
        }

        if ($lessonTitle !== '') {
            $context .= "Target Focus Lesson: {$lessonTitle}\n";
            foreach ($lessons as $l) {
                if (strcasecmp($l['title'], $lessonTitle) === 0 && !empty($l['content'])) {
                    $context .= "Lesson Content:\n" . substr($l['content'], 0, 2500) . "\n";
                    break;
                }
            }
        } else if (!empty($lessons)) {
            $context .= "Available Course Lessons & Materials:\n";
            foreach (array_slice($lessons, 0, 8) as $l) {
                $context .= "• " . $l['title'] . ": " . substr($l['content'], 0, 350) . "\n";
            }
        }

        // 1. Try OpenAI / OpenAI-compatible API if configured
        if (defined('AI_TEACHER_API_KEY') && AI_TEACHER_API_KEY !== '') {
            $questions = $this->callOpenAIForQuestions($context, $count, $type, $difficulty);
            if (!empty($questions) && is_array($questions)) {
                return array_slice($questions, 0, $count);
            }
        }

        // 2. Try Google Gemini API if configured
        if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
            $questions = $this->callGeminiForQuestions($context, $count, $type, $difficulty);
            if (!empty($questions) && is_array($questions)) {
                return array_slice($questions, 0, $count);
            }
        }

        // 3. Grounded curriculum question generator (intelligent fallback)
        return $this->generateGroundedQuestions($course, $lessonTitle, $count, $type, $difficulty, $lessons);
    }

    private function callOpenAIForQuestions($context, $count, $type, $difficulty) {
        $url = defined('AI_TEACHER_BASE_URL') && AI_TEACHER_BASE_URL !== '' ? AI_TEACHER_BASE_URL . '/chat/completions' : 'https://api.openai.com/v1/chat/completions';
        $model = defined('AI_TEACHER_MODEL') && AI_TEACHER_MODEL !== '' ? AI_TEACHER_MODEL : 'gpt-4o-mini';

        $typeInstruction = "a balanced mix of Multiple Choice (with 4 options), True/False, and Short Answer questions";
        if ($type === 'multiple_choice') {
            $typeInstruction = "ONLY Multiple Choice questions (each with exactly 4 options and 1 correct)";
        } elseif ($type === 'true_false') {
            $typeInstruction = "ONLY True/False questions (with True and False options)";
        } elseif ($type === 'short_answer') {
            $typeInstruction = "ONLY Short Answer conceptual questions";
        }

        $systemPrompt = "You are the Univ E-Learning AI Teacher and Academic Assessment Specialist. Your job is to generate rigorous, fair, and curriculum-aligned test questions for university students based on provided course materials.";
        $userPrompt = "Generate {$count} {$difficulty}-level assessment questions based on this course material:\n\n{$context}\n\n"
            . "Question format requirements:\n"
            . "- Must be {$typeInstruction}.\n"
            . "- Return ONLY a valid JSON array of question objects (no markdown wrapping, no code block backticks).\n"
            . "- Schema:\n"
            . "[\n"
            . "  {\n"
            . "    \"question_text\": \"Clear question prompt\",\n"
            . "    \"question_type\": \"Multiple Choice\", // 'Multiple Choice', 'True/False', or 'Short Answer'\n"
            . "    \"marks\": 2,\n"
            . "    \"options\": [\n"
            . "      {\"text\": \"Option A text\", \"is_correct\": true},\n"
            . "      {\"text\": \"Option B text\", \"is_correct\": false},\n"
            . "      {\"text\": \"Option C text\", \"is_correct\": false},\n"
            . "      {\"text\": \"Option D text\", \"is_correct\": false}\n"
            . "    ]\n"
            . "  }\n"
            . "]\n"
            . "For True/False, options must contain 'True' and 'False' with one marked correct. For Short Answer, options should be an empty array [].";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.4,
            'max_tokens' => 2000,
        ];

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 22,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . AI_TEACHER_API_KEY . "\r\n",
                'content' => json_encode($payload),
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) return null;
        $decoded = json_decode($res, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        return $this->parseJsonQuestions($content);
    }

    private function callGeminiForQuestions($context, $count, $type, $difficulty) {
        $model = defined('GEMINI_MODEL') && GEMINI_MODEL !== '' ? GEMINI_MODEL : 'gemini-2.0-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode(GEMINI_API_KEY);

        $typeInstruction = "a balanced mix of Multiple Choice, True/False, and Short Answer questions";
        if ($type === 'multiple_choice') {
            $typeInstruction = "ONLY Multiple Choice questions (4 options, 1 correct)";
        } elseif ($type === 'true_false') {
            $typeInstruction = "ONLY True/False questions";
        } elseif ($type === 'short_answer') {
            $typeInstruction = "ONLY Short Answer questions";
        }

        $prompt = "You are the Univ E-Learning AI Teacher. Generate {$count} {$difficulty}-level assessment questions for university students grounded in this course syllabus:\n\n{$context}\n\n"
            . "Requirements:\n"
            . "- {$typeInstruction}\n"
            . "- Output ONLY a valid JSON array of objects adhering strictly to keys: question_text, question_type, marks, options (array of {text, is_correct}).\n"
            . "No surrounding markdown or explanation.";

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2000,
                'responseMimeType' => 'application/json',
            ],
        ];

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 22,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
            ],
        ]);

        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) return null;
        $decoded = json_decode($res, true);
        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $this->parseJsonQuestions($content);
    }

    private function parseJsonQuestions($content) {
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $clean = trim($content);
        // Strip markdown backticks if present
        if (preg_match('/```(?:json)?\s*(\[\s*\{.*?\}\s*\])\s*```/is', $clean, $matches)) {
            $clean = $matches[1];
        } elseif (preg_match('/\[\s*\{.*\}\s*\]/is', $clean, $matches)) {
            $clean = $matches[0];
        }

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return null;
        }

        $sanitized = [];
        foreach ($decoded as $item) {
            $text = trim((string)($item['question_text'] ?? $item['question'] ?? ''));
            if ($text === '') continue;

            $rawType = trim((string)($item['question_type'] ?? $item['type'] ?? 'Multiple Choice'));
            if (stripos($rawType, 'true') !== false || stripos($rawType, 'false') !== false) {
                $type = 'True/False';
            } elseif (stripos($rawType, 'short') !== false) {
                $type = 'Short Answer';
            } else {
                $type = 'Multiple Choice';
            }

            $marks = max(1, (int)($item['marks'] ?? ($type === 'Short Answer' ? 3 : ($type === 'True/False' ? 1 : 2))));

            $options = [];
            if ($type === 'Multiple Choice' && !empty($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $opt) {
                    $optText = is_array($opt) ? trim((string)($opt['text'] ?? $opt['option'] ?? '')) : trim((string)$opt);
                    $isCorrect = is_array($opt) ? (!empty($opt['is_correct']) || !empty($opt['correct'])) : false;
                    if ($optText !== '') {
                        $options[] = [
                            'text'       => $optText,
                            'is_correct' => (bool)$isCorrect,
                        ];
                    }
                }
                // Ensure at least one option is marked correct
                $hasCorrect = false;
                foreach ($options as $o) {
                    if ($o['is_correct']) { $hasCorrect = true; break; }
                }
                if (!$hasCorrect && !empty($options)) {
                    $options[0]['is_correct'] = true;
                }
            } elseif ($type === 'True/False') {
                $correctVal = true;
                if (!empty($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $opt) {
                        if (is_array($opt) && (!empty($opt['is_correct']) || !empty($opt['correct']))) {
                            if (stripos((string)($opt['text'] ?? ''), 'false') !== false) {
                                $correctVal = false;
                            }
                        }
                    }
                } elseif (isset($item['correct'])) {
                    $correctVal = (bool)$item['correct'];
                }
                $options = [
                    ['text' => 'True', 'is_correct' => $correctVal === true],
                    ['text' => 'False', 'is_correct' => $correctVal === false],
                ];
            }

            $sanitized[] = [
                'question_text' => $text,
                'question_type' => $type,
                'marks'         => $marks,
                'options'       => $options,
            ];
        }

        return !empty($sanitized) ? $sanitized : null;
    }

    private function generateGroundedQuestions(array $course, $lessonTitle, $count, $type, $difficulty, array $lessons) {
        $courseName = $course['CourseName'] ?? 'Course';
        $courseLower = strtolower($courseName);
        $generated = [];

        // Choose focus lesson or default to first
        $focus = null;
        if ($lessonTitle !== '') {
            foreach ($lessons as $l) {
                if (strcasecmp($l['title'], $lessonTitle) === 0) {
                    $focus = $l;
                    break;
                }
            }
        }
        if (!$focus && !empty($lessons)) {
            $focus = $lessons[0];
        }

        $focusTitle = $focus['title'] ?? $courseName;
        $focusContent = $focus['content'] ?? "Fundamental principles, techniques, and evaluation methods of {$courseName}.";

        // Domain knowledge bank for rich curriculum questions
        $curriculumBanks = [
            'python' => [
                [
                    'text' => 'In Python, which built-in function or syntax is best suited to iterate over both indices and items in a collection simultaneously?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'enumerate()', 'is_correct' => true],
                        ['text' => 'range(len())', 'is_correct' => false],
                        ['text' => 'zip_longest()', 'is_correct' => false],
                        ['text' => 'index_map()', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: In Python, strings and tuples are immutable sequences, meaning their contents cannot be modified in-place after creation.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'Explain the difference between a shallow copy and a deep copy in Python, giving an example scenario where a deep copy is necessary.',
                    'type' => 'Short Answer',
                    'marks' => 3,
                    'options' => []
                ],
                [
                    'text' => 'What is the primary benefit of using Python list comprehensions over traditional for loops?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Concise syntax and optimized C-level iteration performance', 'is_correct' => true],
                        ['text' => 'They automatically execute asynchronously across threads', 'is_correct' => false],
                        ['text' => 'They bypass memory allocation entirely', 'is_correct' => false],
                        ['text' => 'They prevent runtime exceptions from being raised', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: Python functions without an explicit return statement implicitly return None.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
            ],
            'web' => [
                [
                    'text' => 'Which HTTP response status code is returned when a client requests a resource that requires authentication and valid credentials were not provided?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => '401 Unauthorized', 'is_correct' => true],
                        ['text' => '403 Forbidden', 'is_correct' => false],
                        ['text' => '404 Not Found', 'is_correct' => false],
                        ['text' => '400 Bad Request', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: In modern web development, semantic HTML elements such as <article>, <section>, and <nav> enhance accessibility (a11y) and SEO without requiring extra scripting.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'Explain how the JavaScript Event Loop coordinates asynchronous callbacks, microtasks (Promises), and the rendering pipeline.',
                    'type' => 'Short Answer',
                    'marks' => 4,
                    'options' => []
                ],
                [
                    'text' => 'What is the primary purpose of CSS Flexbox compared to CSS Grid?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Flexbox specializes in one-dimensional linear layouts, while Grid excels at two-dimensional rows and columns', 'is_correct' => true],
                        ['text' => 'Flexbox only works on mobile viewports', 'is_correct' => false],
                        ['text' => 'Grid has been deprecated in favor of Flexbox', 'is_correct' => false],
                        ['text' => 'Flexbox requires JavaScript polyfills in modern browsers', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: Cross-Origin Resource Sharing (CORS) is a server-side browser enforcement mechanism that protects APIs against unauthorized client-side cross-domain reads.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
            ],
            'database' => [
                [
                    'text' => 'Which normal form guarantees that all non-key attributes are fully functionally dependent on the entire primary key, eliminating partial dependencies?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Second Normal Form (2NF)', 'is_correct' => true],
                        ['text' => 'First Normal Form (1NF)', 'is_correct' => false],
                        ['text' => 'Third Normal Form (3NF)', 'is_correct' => false],
                        ['text' => 'Boyce-Codd Normal Form (BCNF)', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: An index in a relational database speeds up SELECT queries with WHERE and JOIN filters, but introduces slight write overhead on INSERT, UPDATE, and DELETE operations.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'Explain the ACID properties of database transactions and describe what guarantees the Isolation property provides in concurrent systems.',
                    'type' => 'Short Answer',
                    'marks' => 4,
                    'options' => []
                ],
                [
                    'text' => 'What type of SQL JOIN returns all rows from the left table and matched rows from the right table, substituting NULLs for non-matches?',
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'LEFT OUTER JOIN', 'is_correct' => true],
                        ['text' => 'INNER JOIN', 'is_correct' => false],
                        ['text' => 'FULL OUTER JOIN', 'is_correct' => false],
                        ['text' => 'CROSS JOIN', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => 'True or False: Using prepared statements with parameterized queries is the primary defense against SQL injection attacks.',
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
            ],
        ];

        // Find best match in knowledge banks
        $bank = null;
        foreach ($curriculumBanks as $key => $qList) {
            if (strpos($courseLower, $key) !== false || stripos($focusTitle, $key) !== false) {
                $bank = $qList;
                break;
            }
        }

        if ($bank !== null) {
            $pool = $bank;
        } else {
            // Universal high-quality questions customized for this specific course & lesson
            $cleanTitle = htmlspecialchars($focusTitle);
            $pool = [
                [
                    'text' => "What is the primary objective of studying '{$cleanTitle}' in the context of {$courseName}?",
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => "To establish core theoretical foundations and practical methods applicable to {$courseName}", 'is_correct' => true],
                        ['text' => "To bypass foundational guidelines and rely solely on trial-and-error", 'is_correct' => false],
                        ['text' => "To replace systematic verification with informal estimation", 'is_correct' => false],
                        ['text' => "To eliminate the need for documentation and structural consistency", 'is_correct' => false],
                    ]
                ],
                [
                    'text' => "True or False: Mastery of '{$cleanTitle}' enables students to design and evaluate solutions systematically rather than relying on unstructured guesswork.",
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => "Describe the key principles covered in '{$cleanTitle}' and explain how an instructor or practitioner applies them to solve a complex challenge in {$courseName}.",
                    'type' => 'Short Answer',
                    'marks' => 3,
                    'options' => []
                ],
                [
                    'text' => "When applying '{$cleanTitle}', what is a critical consideration to prevent failure or poor performance?",
                    'type' => 'Multiple Choice',
                    'marks' => 2,
                    'options' => [
                        ['text' => "Validating inputs, understanding edge cases, and verifying expected outputs against requirements", 'is_correct' => true],
                        ['text' => "Ignoring constraints and assuming inputs will always follow the ideal happy path", 'is_correct' => false],
                        ['text' => "Skipping testing phases to shorten development cycles", 'is_correct' => false],
                        ['text' => "Relying on deprecated mechanisms without measuring impact", 'is_correct' => false],
                    ]
                ],
                [
                    'text' => "True or False: In {$courseName}, thorough documentation and consistent methodology significantly reduce troubleshooting time and system errors.",
                    'type' => 'True/False',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true],
                        ['text' => 'False', 'is_correct' => false],
                    ]
                ],
                [
                    'text' => "Provide two concrete examples of how concepts from '{$cleanTitle}' are utilized in real-world professional environments.",
                    'type' => 'Short Answer',
                    'marks' => 4,
                    'options' => []
                ],
            ];
        }

        // Filter pool by question type if requested
        $filtered = [];
        foreach ($pool as $q) {
            if ($type === 'multiple_choice' && $q['type'] !== 'Multiple Choice') continue;
            if ($type === 'true_false' && $q['type'] !== 'True/False') continue;
            if ($type === 'short_answer' && $q['type'] !== 'Short Answer') continue;
            $filtered[] = $q;
        }

        if (empty($filtered)) {
            $filtered = $pool;
        }

        // Fill up to requested count
        while (count($generated) < $count) {
            foreach ($filtered as $item) {
                $generated[] = $item;
                if (count($generated) >= $count) break 2;
            }
        }

        return array_slice($generated, 0, $count);
    }
}
