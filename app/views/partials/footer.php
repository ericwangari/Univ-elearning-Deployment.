<?php
// app/views/partials/footer.php
?>
    </div> <!-- / .container-fluid -->
    <footer class="mt-auto py-3 bg-white border-top text-center text-muted small">
        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Built for Excellence.
    </footer>
</div> <!-- /#page-content-wrapper -->
</div> <!-- /#wrapper -->

<?php if (isLoggedIn()): ?>
    <div class="student-feedback-widget" id="studentFeedbackWidget" data-hidden-key="studentFeedbackHidden">
        <button type="button" class="student-feedback-tab" id="studentFeedbackRestore" aria-label="Show feedback button">
            <i class="bi bi-chat-left-text"></i>
        </button>
        <div class="student-feedback-card">
            <button type="button" class="student-feedback-hide" id="studentFeedbackHide" aria-label="Hide feedback button" title="Slide away">
                <i class="bi bi-chevron-right"></i>
            </button>
            <button type="button" class="student-feedback-button" id="studentFeedbackOpen" data-bs-toggle="modal" data-bs-target="#studentFeedbackModal">
                <i class="bi bi-chat-heart"></i>
                <span>Feedback</span>
            </button>
        </div>
    </div>

    <div class="modal fade" id="studentFeedbackModal" tabindex="-1" aria-labelledby="studentFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-feedback-modal">
                <form id="studentFeedbackForm" action="feedback_submit.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="studentFeedbackModalLabel">Send Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="feedbackMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="feedbackMessage" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback">Please enter your feedback message.</div>
                        </div>

                        <div class="student-feedback-status small" id="studentFeedbackStatus" role="status" aria-live="polite"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="studentFeedbackSubmit">
                            <i class="bi bi-send me-1"></i> Send Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
<!-- Custom JS -->
<script src="public/js/main.js?v=9"></script>
</body>
</html>
