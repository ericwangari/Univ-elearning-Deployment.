<?php
/* -----------------------------
   DEPARTMENTS
   Shared list + keyword-based classifier used to backfill existing
   courses and to suggest a department for new ones.
------------------------------*/

function getDefaultDepartments() {
    return [
        'Computer Science',
        'Information Technology',
        'Data Science & AI',
        'Software Engineering',
        'Design & Media',
        'Business',
    ];
}

function guessCourseDepartment($courseName, $description = '') {
    $text = strtolower($courseName . ' ' . $description);

    $rules = [
        'Data Science & AI'      => ['machine learning', 'artificial intelligence', ' ai ', 'data science', 'deep learning', 'analytics', 'statistics', 'neural'],
        'Information Technology' => ['network', 'cloud', 'cyber', 'security', 'devops', 'linux', 'system admin', 'infrastructure'],
        'Software Engineering'   => ['web', 'flutter', 'mobile app', 'android', 'ios', 'java', 'enterprise', 'software', 'testing', 'blockchain', 'unity', 'game'],
        'Design & Media'         => ['design', 'ui/ux', 'ux', 'graphic', 'media', 'animation', 'photography'],
        'Business'               => ['business', 'marketing', 'finance', 'accounting', 'management', 'entrepreneur', 'economics'],
        'Computer Science'       => ['python', 'algorithm', 'data structure', 'database', 'sql', 'operating system', ' os', 'programming', 'computer', 'theory'],
    ];

    $padded = ' ' . $text . ' ';
    foreach ($rules as $department => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($padded, $keyword) !== false) {
                return $department;
            }
        }
    }

    return 'Computer Science';
}

/**
 * Normalises a department submitted from a form: trims, limits length,
 * falls back to a guess when empty.
 */
function normalizeDepartmentInput($value, $courseName = '', $description = '') {
    $value = trim(preg_replace('/\s+/', ' ', (string) $value));
    if ($value === '') {
        return guessCourseDepartment($courseName, $description);
    }
    return function_exists('mb_substr') ? mb_substr($value, 0, 100) : substr($value, 0, 100);
}

/**
 * Returns the departments currently in use plus the defaults, de-duplicated.
 */
function getAllDepartments($pdo) {
    $departments = getDefaultDepartments();
    try {
        $stmt = $pdo->query("SELECT DISTINCT Department FROM courses WHERE Department IS NOT NULL AND Department <> ''");
        foreach ($stmt->fetchAll() as $row) {
            $name = $row['Department'] ?? $row['department'] ?? '';
            if ($name !== '' && !in_array($name, $departments, true)) {
                $departments[] = $name;
            }
        }
    } catch (Exception $e) {
        error_log('Department list warning: ' . $e->getMessage());
    }
    return $departments;
}
