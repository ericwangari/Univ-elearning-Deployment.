<?php
// app/views/instructor/upload_content.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="?page=dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        <a href="?page=manage-content" class="btn btn-outline-primary btn-sm"><i class="bi bi-library"></i> View All Content</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 animate__animated animate__fadeInUp">
                <div class="card-header bg-primary text-white p-4 border-0">
                    <h3 class="mb-0 fw-bold"><i class="bi bi-cloud-upload me-2"></i> Upload New Content</h3>
                    <p class="mb-0 text-white-50">Add videos, PDFs, or links to your courses.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="index.php?page=upload-content" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Course</label>
                            <select name="course_id" class="form-select form-select-lg shadow-none border-2" required>
                                <option value="">-- Choose Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['CourseID']; ?>"><?php echo htmlspecialchars($course['CourseName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Content Title</label>
                            <input type="text" name="content_title" class="form-control form-control-lg shadow-none border-2" placeholder="e.g. Introduction to Physics" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Content Type</label>
                                <select name="content_type" class="form-select form-select-lg shadow-none border-2" required>
                                    <option value="Text">Text Article</option>
                                    <option value="Video">Video URL</option>
                                    <option value="PDF">PDF Document</option>
                                    <option value="Link">External Link</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">URL / Source</label>
                                <input type="text" name="content_url" class="form-control form-control-lg shadow-none border-2" placeholder="Optional link or reference">
                                <div class="form-text">Use a URL for videos, PDFs, and external links. You can also upload a file below.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Upload File</label>
                            <input type="file" name="content_file" class="form-control form-control-lg shadow-none border-2" accept=".pdf,.doc,.docx,.txt,.md,image/*">
                            <div class="form-text">Upload a PDF, article, image, or other material directly to the selected course.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Article / Notes</label>
                            <textarea name="content_text" rows="4" class="form-control form-control-lg shadow-none border-2" placeholder="Paste lecture notes, an article summary, or any text content here."></textarea>
                            <div class="form-text">This is useful for text-based content when you do not want to rely on a URL.</div>
                        </div>

                        <div class="d-grid mt-5">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Publish Content
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
