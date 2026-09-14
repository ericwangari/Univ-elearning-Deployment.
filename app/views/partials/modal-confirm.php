<?php
// app/views/partials/modal-confirm.php
// Confirmation modal for destructive actions
?>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="confirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle-fill" 
                       style="font-size: 3rem; color: #f59e0b; opacity: 0.8;"></i>
                </div>
                <h6 class="text-center fw-bold mb-2" id="confirmTitle">Confirm your action</h6>
                <p class="text-center text-muted" id="confirmMessage">
                    This action cannot be undone. Please confirm to proceed.
                </p>
            </div>
            
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x me-2"></i>Cancel
                </button>
                <form id="confirmForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger" id="confirmButton">
                        <i class="bi bi-trash me-2"></i><span id="confirmButtonText">Delete</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmModal = document.getElementById('confirmModal');
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmButton = document.getElementById('confirmButton');
    const confirmButtonText = document.getElementById('confirmButtonText');
    const confirmForm = document.getElementById('confirmForm');
    
    // Handle modal triggers
    document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#confirmModal"]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get data from trigger element
            const title = this.dataset.title || 'Confirm Action';
            const message = this.dataset.message || 'This action cannot be undone. Please confirm to proceed.';
            const buttonText = this.dataset.buttonText || 'Delete';
            const action = this.dataset.action || 'delete';
            const id = this.dataset.id;
            
            // Update modal content
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmButtonText.textContent = buttonText;
            
            // Update form action
            confirmForm.action = `?page=${action}&id=${id}`;
            
            // Update button style based on action
            confirmButton.className = 'btn btn-danger';
            if (action.includes('delete')) {
                confirmButton.className = 'btn btn-danger';
            } else if (action.includes('reject')) {
                confirmButton.className = 'btn btn-warning';
            }
            
            // Show loading state on submit
            confirmForm.addEventListener('submit', function() {
                confirmButton.disabled = true;
                confirmButton.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Processing...</span>
                `;
            }, { once: true });
        });
    });
});
</script>

<style>
    #confirmButton {
        min-width: 120px;
    }

    #confirmButton:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    #confirmButton .spinner-border {
        width: 1rem;
        height: 1rem;
    }
</style>
