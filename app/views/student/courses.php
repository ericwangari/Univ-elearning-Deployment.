<?php
// app/views/student/courses.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$search     = $filters['search'] ?? '';
$department = $filters['department'] ?? '';
$sort       = $filters['sort'] ?? 'new';
$status     = $filters['status'] ?? 'all';
$view       = $filters['view'] ?? 'grid';
$isStudent  = ($_SESSION['user_type'] ?? '') === 'Student';

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Build a catalog URL that keeps current filters, with overrides.
$catalogUrl = function (array $overrides = []) use ($filters) {
    $params = array_merge($filters, $overrides);
    $params = array_filter($params, function ($v, $k) {
        $defaults = ['search' => '', 'department' => '', 'sort' => 'new', 'status' => 'all', 'view' => 'grid'];
        return $v !== '' && $v !== null && (!isset($defaults[$k]) || $defaults[$k] !== $v);
    }, ARRAY_FILTER_USE_BOTH);
    return 'index.php?' . http_build_query(array_merge(['page' => 'courses'], $params));
};

$departmentIcons = [
    'Computer Science'       => 'bi-cpu',
    'Information Technology' => 'bi-hdd-network',
    'Data Science & AI'      => 'bi-graph-up-arrow',
    'Software Engineering'   => 'bi-code-slash',
    'Design & Media'         => 'bi-palette',
    'Business'               => 'bi-briefcase',
];
$deptIcon = function ($name) use ($departmentIcons) {
    return $departmentIcons[$name] ?? 'bi-mortarboard';
};
$deptSlug = function ($name) {
    return 'dept-' . trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)), '-');
};

$sortLabels = [
    'new' => 'Newest first', 'popular' => 'Most popular', 'az' => 'Name A–Z',
    'za' => 'Name Z–A', 'department' => 'Department',
];
$statusLabels = ['all' => 'All courses', 'enrolled' => 'My courses', 'available' => 'Not enrolled'];

$hasActiveFilters = $search !== '' || $department !== '' || $status !== 'all';

$renderCourseCard = function ($course) use ($h, $deptIcon, $deptSlug, $isStudent) {
    $isEnrolled = !empty($course['IsEnrolled'] ?? false);
    $desc = trim((string)($course['Description'] ?? ''));
    $shortDesc = strlen($desc) > 110 ? substr($desc, 0, 107) . '…' : $desc;
    $dept = $course['Department'];
    ?>
    <div class="col-md-6 col-lg-4 col-xl-3 course-card-container">
        <div class="card h-100 border-0 shadow-sm course-card overflow-hidden">
            <div class="course-card-banner <?php echo $h($deptSlug($dept)); ?>">
                <i class="bi <?php echo $h($deptIcon($dept)); ?>" aria-hidden="true"></i>
                <?php if ($isEnrolled): ?>
                    <span class="badge course-enrolled-badge"><i class="bi bi-check2-circle me-1"></i>Enrolled</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                    <a href="?page=courses&department=<?php echo urlencode($dept); ?>" class="badge course-dept-badge text-decoration-none">
                        <?php echo $h($dept); ?>
                    </a>
                    <span class="small text-muted text-nowrap" title="Enrolled students">
                        <i class="bi bi-people me-1"></i><?php echo (int)($course['StudentCount'] ?? 0); ?>
                    </span>
                </div>
                <h5 class="card-title fw-bold mb-2"><?php echo $h($course['CourseName']); ?></h5>
                <div class="d-flex align-items-center small text-muted mb-2">
                    <i class="bi bi-person-video3 me-2 text-primary"></i>
                    <span><?php echo $h(($course['InstructorNames'] ?? '') ?: 'Instructor not assigned yet'); ?></span>
                </div>
                <p class="card-text text-muted small mb-0"><?php echo $h($shortDesc); ?></p>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0">
                <?php if ($isEnrolled): ?>
                    <div class="d-flex gap-2">
                        <a href="?page=course-details&id=<?php echo (int)$course['CourseID']; ?>" class="btn btn-soft-primary flex-grow-1 fw-bold">
                            <i class="bi bi-play-circle me-1"></i> Continue
                        </a>
                        <a href="?page=ai-teacher&course_id=<?php echo (int)$course['CourseID']; ?>" class="btn btn-light border" title="Ask the AI Teacher about this course">
                            <i class="bi bi-stars"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="?page=course-details&id=<?php echo (int)$course['CourseID']; ?>" class="btn btn-light border flex-grow-1 fw-bold">Details</a>
                        <?php if ($isStudent): ?>
                            <form action="?page=enroll" method="POST" class="flex-grow-1">
                                <input type="hidden" name="course_id" value="<?php echo (int)$course['CourseID']; ?>">
                                <input type="hidden" name="redirect" value="course-details">
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Enroll</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="container-fluid p-4 course-catalog">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
        <div>
            <h1 class="fw-bold mb-1">Course Catalog</h1>
            <p class="text-muted mb-0">
                <?php echo (int)$total_course_count; ?> courses across <?php echo count($departmentCounts); ?> departments ·
                <?php echo (int)$student_count; ?> active students
            </p>
        </div>
    </div>

    <!-- Filter bar -->
    <form id="catalogFilters" method="GET" action="index.php" class="card border-0 shadow-sm p-3 mb-3 catalog-filter-bar" role="search">
        <input type="hidden" name="page" value="courses">
        <input type="hidden" name="department" value="<?php echo $h($department); ?>">
        <input type="hidden" name="view" value="<?php echo $h($view); ?>">
        <div class="row g-2 align-items-center">
            <div class="col-lg-5">
                <label for="courseSearch" class="visually-hidden">Search courses</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" id="courseSearch" name="search" class="form-control border-start-0 ps-0 shadow-none"
                           placeholder="Search by course, topic or instructor…" value="<?php echo $h($search); ?>" autocomplete="off">
                </div>
            </div>
            <div class="col-sm-4 col-lg-2">
                <label for="courseStatus" class="visually-hidden">Enrollment status</label>
                <select id="courseStatus" name="status" class="form-select shadow-none js-auto-submit">
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-lg-3">
                <label for="courseSort" class="visually-hidden">Sort by</label>
                <select id="courseSort" name="sort" class="form-select shadow-none js-auto-submit">
                    <?php foreach ($sortLabels as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>Sort: <?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <div class="btn-group w-100" role="group" aria-label="Layout">
                    <a href="<?php echo $h($catalogUrl(['view' => 'grid'])); ?>" class="btn <?php echo $view === 'grid' ? 'btn-primary' : 'btn-light border'; ?>" title="Grid view">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </a>
                    <a href="<?php echo $h($catalogUrl(['view' => 'grouped'])); ?>" class="btn <?php echo $view === 'grouped' ? 'btn-primary' : 'btn-light border'; ?>" title="Group by department">
                        <i class="bi bi-collection"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- Department chips -->
    <nav class="dept-chip-row mb-3" aria-label="Filter by department">
        <a href="<?php echo $h($catalogUrl(['department' => ''])); ?>" class="dept-chip <?php echo $department === '' ? 'active' : ''; ?>">
            <i class="bi bi-grid"></i> All <span class="dept-chip-count"><?php echo (int)$total_course_count; ?></span>
        </a>
        <?php foreach ($departmentCounts as $name => $count): ?>
            <a href="<?php echo $h($catalogUrl(['department' => $name])); ?>" class="dept-chip <?php echo $department === $name ? 'active' : ''; ?>">
                <i class="bi <?php echo $h($deptIcon($name)); ?>"></i> <?php echo $h($name); ?>
                <span class="dept-chip-count"><?php echo (int)$count; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Active filters -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4 small">
        <span class="text-muted"><?php echo count($courses); ?> result<?php echo count($courses) === 1 ? '' : 's'; ?></span>
        <?php if ($search !== ''): ?>
            <a class="active-filter-pill" href="<?php echo $h($catalogUrl(['search' => ''])); ?>">“<?php echo $h($search); ?>” <i class="bi bi-x"></i></a>
        <?php endif; ?>
        <?php if ($department !== ''): ?>
            <a class="active-filter-pill" href="<?php echo $h($catalogUrl(['department' => ''])); ?>"><?php echo $h($department); ?> <i class="bi bi-x"></i></a>
        <?php endif; ?>
        <?php if ($status !== 'all'): ?>
            <a class="active-filter-pill" href="<?php echo $h($catalogUrl(['status' => 'all'])); ?>"><?php echo $h($statusLabels[$status]); ?> <i class="bi bi-x"></i></a>
        <?php endif; ?>
        <?php if ($hasActiveFilters): ?>
            <a class="ms-1 fw-semibold" href="index.php?page=courses">Clear all</a>
        <?php endif; ?>
    </div>

    <!-- Results -->
    <?php if (empty($courses)): ?>
        <div class="text-center py-5">
            <img src="/images/books-cartoon.svg?v=4" alt="" style="height: 180px; object-fit: contain;" class="mb-4">
            <h4 class="text-muted">No courses match these filters.</h4>
            <a href="index.php?page=courses" class="btn btn-primary mt-3">Reset filters</a>
        </div>
    <?php elseif ($view === 'grouped'): ?>
        <?php foreach ($coursesByDepartment as $deptName => $deptCourses): ?>
            <section class="mb-5" aria-labelledby="<?php echo $h($deptSlug($deptName)); ?>-heading">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 fw-bold mb-0 d-flex align-items-center gap-2" id="<?php echo $h($deptSlug($deptName)); ?>-heading">
                        <span class="dept-heading-icon <?php echo $h($deptSlug($deptName)); ?>"><i class="bi <?php echo $h($deptIcon($deptName)); ?>"></i></span>
                        <?php echo $h($deptName); ?>
                        <span class="text-muted fw-normal small">(<?php echo count($deptCourses); ?>)</span>
                    </h2>
                    <?php if ($department === ''): ?>
                        <a href="<?php echo $h($catalogUrl(['department' => $deptName, 'view' => 'grid'])); ?>" class="small fw-semibold">View only this department →</a>
                    <?php endif; ?>
                </div>
                <div class="row g-4">
                    <?php foreach ($deptCourses as $course) $renderCourseCard($course); ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="row g-4" id="coursesGrid">
            <?php foreach ($courses as $course) $renderCourseCard($course); ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('catalogFilters');
    const search = document.getElementById('courseSearch');
    let timer = null;
    let lastValue = search.value;

    form.querySelectorAll('.js-auto-submit').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    // Debounced live search (submits after typing pauses)
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const value = search.value.trim();
            if (value !== lastValue.trim() && (value.length === 0 || value.length >= 2)) {
                lastValue = value;
                form.submit();
            }
        }, 600);
    });

    // Keep the cursor at the end of the search box after reload
    if (search.value) {
        search.focus();
        const len = search.value.length;
        search.setSelectionRange(len, len);
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
