<?php
// app/views/instructor/manage_content.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Content Library</h2>
            <p class="text-muted">Manage your course materials, videos, PDFs, and links</p>
        </div>
        <a href="?page=upload-content" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-2"></i> Upload New Content
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Content Title</th>
                            <th>Course</th>
                            <th>Type</th>
                            <th>Created</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contents)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                    </div>
                                    <p class="text-muted mb-0">No content uploaded yet.</p>
                                    <p class="text-muted small mb-0">Start by <a href="?page=upload-content">uploading new content</a></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contents as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div>
                                            <p class="fw-bold mb-0"><?php echo htmlspecialchars($item['ContentTitle']); ?></p>
                                            <small class="text-muted">
                                                <?php
                                                $typeIcons = [
                                                    'Video' => '<i class="bi bi-play-circle"></i>',
                                                    'PDF' => '<i class="bi bi-file-pdf"></i>',
                                                    'Link' => '<i class="bi bi-link-45deg"></i>',
                                                    'Text' => '<i class="bi bi-file-text"></i>'
                                                ];
                                                echo $typeIcons[$item['ContentType']] ?? '';
                                                ?> 
                                                <?php if (!empty($item['ContentURL'])): ?>
                                                    <a href="<?php echo htmlspecialchars($item['ContentURL']); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['ContentURL']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No URL provided</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-primary"><?php echo htmlspecialchars($item['CourseName']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($item['ContentType']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['ContentCreatedAt'])): ?>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($item['ContentCreatedAt'])); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Not recorded</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="?page=edit-content&id=<?php echo $item['ContentID']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="?page=delete-content" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this content?');">
                                                <input type="hidden" name="content_id" value="<?php echo $item['ContentID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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
