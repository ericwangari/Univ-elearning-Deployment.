<?php
$course = $_GET['course'] ?? 'general';
$lesson = $_GET['lesson'] ?? 'overview';
$returnUrl = $_GET['return'] ?? '../index.php?page=dashboard';

if (strpos($returnUrl, 'http://') === 0 || strpos($returnUrl, 'https://') === 0 || strpos($returnUrl, '//') === 0) {
    $returnUrl = '../index.php?page=dashboard';
}

$materials = [
    'uiux' => [
        'research' => [
            'title' => 'UI/UX: User Research and Goals',
            'sections' => [
                ['Learning Goal', 'Understand who the users are, what they need to accomplish, and what makes a design successful.'],
                ['Core Lesson', 'Good UI/UX begins before visual design. Interview users, observe their workflow, and write down their goals, frustrations, and constraints. A learning platform user may need to find a course quickly, continue a lesson, check progress, or submit an assessment. The interface should reduce friction around those tasks.'],
                ['Example', 'If students keep missing quizzes, the solution may not be a prettier dashboard. It may be clearer deadlines, better reminders, and a direct path from course page to assessment.'],
                ['Practice', 'Choose one screen in this platform. List three user goals, two possible frustrations, and one improvement that would make the task easier.'],
            ],
        ],
        'layout' => [
            'title' => 'UI/UX: Layout, Hierarchy, and Contrast',
            'sections' => [
                ['Learning Goal', 'Use spacing, grouping, size, and contrast to help users scan a screen quickly.'],
                ['Core Lesson', 'Visual hierarchy tells users what matters first. Headings introduce sections, related controls stay near each other, and primary actions stand out. Contrast must be strong enough for comfortable reading. Avoid making important text pale or low contrast.'],
                ['Example', 'A course page should make the course title, curriculum, enroll button, and assessment access easy to find. Secondary details can be smaller, but they should still be readable.'],
                ['Practice', 'Redesign a course card on paper. Mark the first thing a student should see, the second thing, and the action they should take next.'],
            ],
        ],
        'testing' => [
            'title' => 'UI/UX: Prototyping and Testing',
            'sections' => [
                ['Learning Goal', 'Test designs with users before spending too much time building the wrong thing.'],
                ['Core Lesson', 'A prototype can be a sketch, clickable mockup, or simple working page. Give users a task, watch what they do, and notice where they hesitate. Do not explain the design while they test; the product should communicate for itself.'],
                ['Example', 'Ask a student to find a quiz from the dashboard. If they pause or click the wrong item, the navigation needs improvement.'],
                ['Practice', 'Create a three-step test for one feature: task, expected path, and what you will measure.'],
            ],
        ],
    ],
    'database' => [
        'design' => [
            'title' => 'Database Pro: Relational Database Design',
            'sections' => [
                ['Learning Goal', 'Design tables that represent real entities and relationships clearly.'],
                ['Core Lesson', 'A learning platform needs separate tables for users, courses, enrollments, quizzes, questions, and results. Each table should store one kind of thing. Relationships are represented with IDs, such as CourseID in enrollments.'],
                ['Example', 'Do not store a comma-separated list of course names inside a user row. Use an enrollments table so one student can join many courses and one course can have many students.'],
                ['Practice', 'Draw tables for users, courses, and enrollments. Add primary keys and foreign keys.'],
            ],
        ],
        'sql' => [
            'title' => 'Database Pro: SQL Queries That Matter',
            'sections' => [
                ['Learning Goal', 'Use SELECT, JOIN, WHERE, GROUP BY, and ORDER BY to answer real questions.'],
                ['Core Lesson', 'SQL turns stored data into useful information. JOIN connects tables, WHERE filters rows, GROUP BY summarizes, and ORDER BY sorts results.'],
                ['Example', 'To count students per course, join courses to enrollments, group by course, and count enrollment rows.'],
                ['Practice', 'Write a query that shows each course name and the number of enrolled students.'],
            ],
        ],
        'integrity' => [
            'title' => 'Database Pro: Indexes and Data Integrity',
            'sections' => [
                ['Learning Goal', 'Keep data valid and make common lookups fast.'],
                ['Core Lesson', 'Foreign keys prevent broken relationships. Unique constraints prevent duplicates. Indexes speed up searches on columns used in joins and filters.'],
                ['Example', 'An enrollment should not exist for a missing student or missing course. A foreign key enforces that rule automatically.'],
                ['Practice', 'Identify three columns in this platform that should be indexed and explain why.'],
            ],
        ],
    ],
];

$fallback = [
    'title' => $_GET['title'] ?? 'Course Learning Material',
    'sections' => [
        ['Learning Goal', 'Study the key concepts for this course and connect them to practical work.'],
        ['Core Lesson', 'Read the course topic, identify the main vocabulary, then apply each idea to a small example. Strong learning comes from using the concept, not just recognizing the words.'],
        ['Practice', 'Write a short summary of what you learned and one question you still have.'],
    ],
];

$material = $materials[$course][$lesson] ?? $fallback;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($material['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #1f2937; font-family: Arial, sans-serif; }
        main { max-width: 860px; margin: 0 auto; padding: 32px 18px; }
        .section { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        p { line-height: 1.75; }
    </style>
</head>
<body>
<main>
    <a href="<?php echo htmlspecialchars($returnUrl); ?>" class="btn btn-outline-secondary btn-sm mb-4">Back</a>
    <h1 class="fw-bold mb-4"><?php echo htmlspecialchars($material['title']); ?></h1>
    <?php foreach ($material['sections'] as $section): ?>
        <section class="section">
            <h2 class="h5 fw-bold"><?php echo htmlspecialchars($section[0]); ?></h2>
            <p class="mb-0"><?php echo htmlspecialchars($section[1]); ?></p>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
