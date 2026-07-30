<?php
// app/views/admin/all_results.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">All Quiz Results</h2>
        <p class="text-muted">Monitor and evaluate student performance across the entire system.</p>
    </div>

    <?php 
    $stats = ['Quiz' => 0, 'Midterm' => 0, 'Final' => 0, 'Assignment' => 0];
    foreach ($results as $r) {
        if (isset($stats[$r['QuizType']])) {
            $stats[$r['QuizType']]++;
        }
    }
    ?>

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted mb-1 small">Quizzes Taken</h6>
                    <h4 class="mb-0 fw-bold text-warning"><?php echo $stats['Quiz']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted mb-1 small">Midterms Taken</h6>
                    <h4 class="mb-0 fw-bold text-info"><?php echo $stats['Midterm']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted mb-1 small">Finals Taken</h6>
                    <h4 class="mb-0 fw-bold text-dark"><?php echo $stats['Final']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-3">
                    <h6 class="text-muted mb-1 small">Assignments Taken</h6>
                    <h4 class="mb-0 fw-bold text-primary"><?php echo $stats['Assignment']; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="page" value="all-results">
                <select class="form-select" name="course" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['CourseID']; ?>" <?php echo (($_GET['course'] ?? '') == $course['CourseID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['CourseName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Assessment</th>
                            <th>Type</th>
                            <th>Course</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th class="pe-4">Submitted Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <p class="text-muted mb-0">No results recorded yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $result): ?>
                                <?php
                                    $total_marks = max((int)($result['TotalMarks'] ?? 100), 1);
                                    $percentage = round(($result['Score'] / $total_marks) * 100, 1);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($result['Username']); ?></div>
                                    </td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($result['QuizName']); ?></td>
                                    <td>
                                         <?php 
                                         $type_class = 'bg-secondary';
                                         switch($result['QuizType']) {
                                             case 'Midterm': $type_class = 'bg-info'; break;
                                             case 'Final': $type_class = 'bg-dark'; break;
                                             case 'Assignment': $type_class = 'bg-primary'; break;
                                             case 'Quiz': $type_class = 'bg-warning text-dark'; break;
                                         }
                                         ?>
                                         <span class="badge <?php echo $type_class; ?>"><?php echo $result['QuizType']; ?></span>
                                     </td>
                                    <td><?php echo htmlspecialchars($result['CourseName']); ?></td>
                                    <td>
                                        <span class="fw-bold">
                                            <?php echo $result['Score']; ?>/<?php echo $total_marks; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $percentage; ?>%</td>
                                    <td>
                                        <?php if ($percentage >= 50): ?>
                                            <span class="badge bg-success">Pass</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Fail</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4"><?php echo date('M d, Y, h:i A', strtotime($result['SubmittedAt'])); ?></td>
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

