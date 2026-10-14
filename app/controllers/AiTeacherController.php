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
        $selectedCourseId = $_POST['course_id'] ?? ($_GET['course_id'] ?? '');
        $mode = $_POST['mode'] ?? 'explain';
        $question = trim($_POST['question'] ?? '');
        $ai_response = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($question === '') {
                $error = 'Ask the AI teacher a question or describe what you want to learn.';
            } else {
                $course = $this->findCourse($courses, $selectedCourseId);
                $ai_response = $this->generateTeacherResponse($question, $mode, $course);
            }
        }

        require __DIR__ . '/../views/ai_teacher/index.php';
    }

    private function getRelevantCourses() {
        $userId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? 'Student';

        if ($userType === 'Student') {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT c.CourseID, c.CourseName, c.Description
                FROM courses c
                JOIN enrollments e ON e.CourseID = c.CourseID
                WHERE e.UserID = ?
                ORDER BY c.CourseName
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        }

        if ($userType === 'Instructor') {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT c.CourseID, c.CourseName, c.Description
                FROM courses c
                JOIN instructor_courses ic ON ic.CourseID = c.CourseID
                WHERE ic.InstructorID = ?
                ORDER BY c.CourseName
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query("SELECT CourseID, CourseName, Description FROM courses ORDER BY CourseName");
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

    private function generateTeacherResponse($question, $mode, $course) {
        if (defined('AI_TEACHER_API_KEY') && AI_TEACHER_API_KEY !== '') {
            $apiResponse = $this->generateWithApi($question, $mode, $course);
            if ($apiResponse !== null) {
                return $apiResponse;
            }
        }

        return $this->generateFallbackResponse($question, $mode, $course);
    }

    private function generateWithApi($question, $mode, $course) {
        $courseName = $course['CourseName'] ?? 'the selected course';
        $courseDescription = $course['Description'] ?? '';
        $system = 'You are Univ Learning AI Teacher. Be concise, practical, encouraging, and educational. Avoid doing graded work for the student; explain concepts, create study plans, examples, and practice questions.';
        $user = "Mode: {$mode}\nCourse: {$courseName}\nCourse description: {$courseDescription}\nLearner request: {$question}";

        $payload = [
            'model' => AI_TEACHER_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.4,
            'max_tokens' => 700,
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 25,
                'ignore_errors' => true,
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . AI_TEACHER_API_KEY . "\r\n",
                'content' => json_encode($payload),
            ],
        ]);

        $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);
        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    private function generateFallbackResponse($question, $mode, $course) {
        $courseName = $course['CourseName'] ?? 'your course';
        $topic = $this->extractTopic($question, $courseName);

        if ($mode === 'practice') {
            return "Practice set for {$topic}\n\n"
                . "1. Define the main idea behind {$topic} in your own words.\n"
                . "2. Give one real-world example where {$topic} is useful.\n"
                . "3. List two common mistakes learners make with {$topic}.\n"
                . "4. Solve a small scenario: choose a problem from {$courseName} and explain how {$topic} helps solve it.\n"
                . "5. Reflection: what part of {$topic} still feels unclear?";
        }

        if ($mode === 'content') {
            return "Generated mini-lesson: {$topic}\n\n"
                . "Learning goal: Understand what {$topic} means, why it matters, and how to apply it in {$courseName}.\n\n"
                . "Overview: {$topic} is best learned by connecting the definition to a practical task. Start with the core vocabulary, then work through one example slowly.\n\n"
                . "Example activity: Write a short explanation of {$topic}, create a simple example, and compare your example with the course material.\n\n"
                . "Checkpoint: You are ready to move on when you can explain {$topic} without reading notes.";
        }

        if ($mode === 'plan') {
            return "Study plan for {$courseName}\n\n"
                . "Day 1: Review the core ideas around {$topic}. Write short notes in your own words.\n"
                . "Day 2: Work through examples and identify patterns.\n"
                . "Day 3: Try practice questions without checking notes first.\n"
                . "Day 4: Revisit mistakes and turn them into flashcards.\n"
                . "Day 5: Teach the topic out loud as if explaining it to a classmate.";
        }

        return "Explanation: {$topic}\n\n"
            . "Think of {$topic} as one building block inside {$courseName}. Start by asking three questions: what does it mean, when do you use it, and what result should it produce?\n\n"
            . "A useful way to study it is to write one definition, one example, and one non-example. The non-example is important because it helps you see the boundary of the idea.\n\n"
            . "Your next step: turn your question into a small exercise and try to solve it before checking the course notes.";
    }

    private function extractTopic($question, $fallback) {
        $clean = trim(preg_replace('/\s+/', ' ', $question));
        if ($clean === '') {
            return $fallback;
        }

        return strlen($clean) > 90 ? substr($clean, 0, 87) . '...' : $clean;
    }
}
