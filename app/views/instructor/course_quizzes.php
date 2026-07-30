<?php
// app/views/instructor/course_quizzes.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <a href="?page=manage-courses" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Courses</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Assessments for <?php echo htmlspecialchars($course['CourseName']); ?></h2>
            <p class="text-muted">Create and manage quizzes, midterms, and finals</p>
        </div>
        <a href="?page=create-quiz&course_id=<?php echo $course['CourseID']; ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> New Assessment
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Assessment Name</th>
                            <th>Type</th>
                            <th>Total Marks</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quizzes)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <p class="text-muted mb-0">No assessments created for this course yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quizzes as $quiz): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($quiz['QuizName']); ?></td>
                                    <td>
                                        <?php 
                                        $type_class = 'bg-secondary';
                                        switch($quiz['QuizType']) {
                                            case 'Midterm': $type_class = 'bg-info'; break;
                                            case 'Final': $type_class = 'bg-dark'; break;
                                            case 'Assignment': $type_class = 'bg-primary'; break;
                                            case 'Quiz': $type_class = 'bg-warning text-dark'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $type_class; ?>"><?php echo $quiz['QuizType']; ?></span>
                                    </td>
                                    <td><?php echo $quiz['TotalMarks']; ?></td>
                                    <td class="pe-4 text-end">
                                        <a href="?page=manage-quiz&id=<?php echo $quiz['QuizID']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-gear"></i> Manage Questions
                                        </a>
                                        <form method="POST" action="?page=delete-quiz" class="d-inline">
                                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['QuizID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this assessment?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
