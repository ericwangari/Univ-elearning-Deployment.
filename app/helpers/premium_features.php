<?php

function premiumCourseMeta(array $course): array {
    $name = strtolower($course['CourseName'] ?? '');
    $description = strtolower($course['Description'] ?? '');
    $text = $name . ' ' . $description;

    $tracks = [
        'python' => ['Python Pro Track', 'bi-code-slash', ['Automation', 'Data practice', 'Backend basics'], 'Beginner friendly', '4 weeks'],
        'web' => ['Full-Stack Builder', 'bi-window-stack', ['Projects', 'APIs', 'Responsive UI'], 'Intermediate', '6 weeks'],
        'database' => ['Data Systems Pro', 'bi-database-check', ['SQL', 'Schema design', 'Indexes'], 'Intermediate', '5 weeks'],
        'machine learning' => ['Applied ML Lab', 'bi-cpu', ['Models', 'Evaluation', 'Responsible AI'], 'Advanced', '6 weeks'],
        'cyber' => ['Cyber Defense Track', 'bi-shield-check', ['Threats', 'Hardening', 'Response'], 'Intermediate', '5 weeks'],
        'cloud' => ['Cloud Career Track', 'bi-cloud-check', ['Deployment', 'Security', 'Reliability'], 'Intermediate', '5 weeks'],
        'devops' => ['DevOps Release Lab', 'bi-git', ['CI/CD', 'Containers', 'Monitoring'], 'Advanced', '6 weeks'],
        'design' => ['Product Design Studio', 'bi-bezier2', ['Research', 'Prototypes', 'Testing'], 'Beginner friendly', '4 weeks'],
    ];

    foreach ($tracks as $needle => $meta) {
        if (strpos($text, $needle) !== false) {
            return [
                'label' => $meta[0],
                'icon' => $meta[1],
                'skills' => $meta[2],
                'level' => $meta[3],
                'duration' => $meta[4],
            ];
        }
    }

    return [
        'label' => 'Premium Learning Path',
        'icon' => 'bi-stars',
        'skills' => ['Guided lessons', 'Practice checks', 'Certificate ready'],
        'level' => 'Self-paced',
        'duration' => '4 weeks',
    ];
}

function premiumNextMilestone(array $course): string {
    $progress = (int)($course['Progress'] ?? 0);

    if ($progress >= 100) {
        return 'Download certificate and keep building your portfolio.';
    }

    if ($progress >= 70) {
        return 'Finish the final assessment to unlock certificate readiness.';
    }

    if ($progress >= 35) {
        return 'Complete one more assessment and ask the AI Teacher for review questions.';
    }

    return 'Start with the first lesson and create a short study plan.';
}

function premiumCompletionLabel(int $progress): string {
    if ($progress >= 100) {
        return 'Certificate ready';
    }

    if ($progress >= 70) {
        return 'Almost certified';
    }

    if ($progress >= 35) {
        return 'Momentum building';
    }

    return 'Getting started';
}
