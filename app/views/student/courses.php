<?php
// app/views/student/courses.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'new';
?>

<div class="container-fluid p-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h1 class="fw-bold mb-1">Course Catalog</h1>
            <p class="text-muted">Explore our collection of world-class learning content.</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <div class="input-group search-bar" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="courseSearch" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select id="courseSort" class="form-select shadow-none" style="width: auto;">
                <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>>Newest First</option>
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            </select>
        </div>
    </div>

    <!-- Stats Preview (Optional/Aesthetic) -->
    <div class="row g-4 mb-5 animate__animated animate__fadeIn">
        <div class="col-md-3">
            <div class="p-3 bg-soft-primary rounded-4 border-0 d-flex align-items-center">
                <div class="p-2 bg-white rounded-3 me-3"><i class="bi bi-book text-primary fs-5"></i></div>
                <div>
                    <h6 class="mb-0 fw-bold"><?php echo count($courses); ?></h6>
                    <small class="text-muted">Courses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-soft-success rounded-4 border-0 d-flex align-items-center">
                <div class="p-2 bg-white rounded-3 me-3"><i class="bi bi-people text-success fs-5"></i></div>
                <div>
                    <h6 class="mb-0 fw-bold">12k+</h6>
                    <small class="text-muted">Students</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Grid -->
    <div class="row g-4" id="coursesGrid">
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <div class="col-md-6 col-lg-4 col-xl-3 animate__animated animate__fadeInUp course-card-container">
                    <div class="card h-100 border-0 shadow-sm course-card overflow-hidden">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img-top" alt="Course" style="height: 160px; object-fit: cover;">
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-soft-primary text-primary px-2 py-1 mb-2" style="font-size: 0.7rem;">TOP RATED</span>
                                <div class="d-flex align-items-center small text-muted">
                                    <i class="bi bi-people me-1"></i> <?php echo $course['StudentCount']; ?>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($course['CourseName']); ?></h5>
                            <div class="d-flex align-items-center small text-muted mb-2">
                                <i class="bi bi-person-video3 me-2 text-primary"></i>
                                <span>Instructor: <?php echo htmlspecialchars($course['InstructorNames'] ?: 'Not assigned yet'); ?></span>
                            </div>
                            <p class="card-text text-muted small mb-4 line-clamp-2">
                                <?php echo htmlspecialchars(substr($course['Description'], 0, 100)); ?>...
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div class="d-flex align-items-center small text-warning">
                                    <i class="bi bi-star-fill me-1"></i>
                                    <i class="bi bi-star-fill me-1"></i>
                                    <i class="bi bi-star-fill me-1"></i>
                                    <i class="bi bi-star-fill me-1"></i>
                                    <i class="bi bi-star-half me-1"></i>
                                    <span class="text-muted ms-1">(4.8)</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 p-4 pt-0">
                            <?php if ($course['IsEnrolled']): ?>
                                <a href="?page=course-details&id=<?php echo $course['CourseID']; ?>" class="btn btn-soft-primary w-100 fw-bold">
                                    <i class="bi bi-play-circle me-2"></i> Continue Learning
                                </a>
                            <?php else: ?>
                                <div class="d-flex gap-2">
                                    <a href="?page=course-details&id=<?php echo $course['CourseID']; ?>" class="btn btn-light border flex-grow-1 fw-bold">Details</a>
                                    <form action="?page=enroll" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">
                                        <button type="submit" class="btn btn-primary w-100 fw-bold">Enroll</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <img src="https://illustrations.popsy.co/white/surreal-search.svg" alt="No courses" style="height: 200px;" class="mb-4">
                <h4 class="text-muted">No courses found matching your criteria.</h4>
                <a href="?page=courses" class="btn btn-primary mt-3">Reset Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('courseSearch');
    const sortSelect = document.getElementById('courseSort');

    function applyFilters() {
        const search = searchInput.value;
        const sort = sortSelect.value;
        window.location.href = `index.php?page=courses&search=${encodeURIComponent(search)}&sort=${sort}`;
    }

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyFilters();
    });

    sortSelect.addEventListener('change', applyFilters);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
