<?php
// app/views/instructor/edit_content.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="mb-4">
        <a href="?page=manage-content" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Content Library
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white p-4">
                    <h3 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Content</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <input type="hidden" name="content_id" value="<?php echo $content['ContentID']; ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Content Title</label>
                            <input type="text" name="content_title" class="form-control form-control-lg shadow-none border-2" value="<?php echo htmlspecialchars($content['ContentTitle']); ?>" placeholder="e.g. Introduction to Physics" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Content Type</label>
                                <select name="content_type" class="form-select form-select-lg shadow-none border-2" required>
                                    <option value="Text" <?php echo ($content['ContentType'] === 'Text') ? 'selected' : ''; ?>>Text Article</option>
                                    <option value="Video" <?php echo ($content['ContentType'] === 'Video') ? 'selected' : ''; ?>>Video URL</option>
                                    <option value="PDF" <?php echo ($content['ContentType'] === 'PDF') ? 'selected' : ''; ?>>PDF Document</option>
                                    <option value="Link" <?php echo ($content['ContentType'] === 'Link') ? 'selected' : ''; ?>>External Link</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">URL / Source</label>
                                <input type="text" name="content_url" class="form-control form-control-lg shadow-none border-2" value="<?php echo htmlspecialchars($content['ContentURL'] ?? ''); ?>" placeholder="Optional for text content">
                                <div class="form-text">Use a URL for videos, PDFs, and external links. Text articles can be saved without one.</div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Last updated: <?php echo date('F d, Y - g:i A', strtotime($content['UpdatedAt'])); ?></small>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3">
                                <i class="bi bi-check-circle me-2"></i> Update Content
                            </button>
                            <a href="?page=manage-content" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
