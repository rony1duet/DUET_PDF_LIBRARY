/**
 * Upload Progress Handler for ImageKit Integration
 * DUET PDF Library
 */

class UploadProgressHandler {
    constructor() {
        this.uploadForm = null;
        this.progressContainer = null;
        this.progressBar = null;
        this.statusText = null;
        this.detailsText = null;
        this.submitButton = null;
        this.uploadInterval = null;
        this.currentProgress = 0;

        this.init();
    }

    init() {
        // Find the upload form
        this.uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
        if (!this.uploadForm) return;

        // Create progress UI
        this.createProgressUI();

        // Attach event listeners
        this.attachEventListeners();
    }

    createProgressUI() {
        // Create progress container HTML
        const progressHTML = `
            <div id="upload-progress-container" class="card mt-4 d-none">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-cloud-upload me-2"></i>
                        Uploading to ImageKit
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span id="upload-status-text" class="fw-bold">Preparing upload...</span>
                            <span id="upload-percentage" class="text-muted">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="upload-details" class="small text-muted"></div>
                    <div id="upload-files-list" class="mt-2"></div>
                </div>
            </div>
        `;

        // Insert after the form
        this.uploadForm.insertAdjacentHTML('afterend', progressHTML);

        // Get references to progress elements
        this.progressContainer = document.getElementById('upload-progress-container');
        this.progressBar = document.getElementById('upload-progress-bar');
        this.statusText = document.getElementById('upload-status-text');
        this.detailsText = document.getElementById('upload-details');
        this.percentageText = document.getElementById('upload-percentage');
        this.filesListContainer = document.getElementById('upload-files-list');
    }

    attachEventListeners() {
        // Form submission handler
        this.uploadForm.addEventListener('submit', (e) => {
            this.handleFormSubmit(e);
        });

        // File input change handlers for preview
        const pdfInput = document.getElementById('pdf_file');
        const coverInput = document.getElementById('cover_image');

        if (pdfInput) {
            pdfInput.addEventListener('change', () => this.updateFilePreview());
        }

        if (coverInput) {
            coverInput.addEventListener('change', () => this.updateFilePreview());
        }
    }

    handleFormSubmit(e) {
        const pdfFile = document.getElementById('pdf_file')?.files[0];
        const coverFile = document.getElementById('cover_image')?.files[0];

        if (!pdfFile && !coverFile) return;

        // Show progress container
        this.showProgress();

        // Disable submit button
        this.disableSubmitButton();

        // Update file list
        this.updateFilesList(pdfFile, coverFile);

        // Start progress simulation (since we can't track real progress with form submission)
        this.startProgressSimulation();
    }

    showProgress() {
        this.progressContainer.classList.remove('d-none');
        this.progressContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    disableSubmitButton() {
        this.submitButton = this.uploadForm.querySelector('button[type="submit"]');
        if (this.submitButton) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Uploading to ImageKit...
            `;
        }
    }

    updateFilesList(pdfFile, coverFile) {
        let filesHTML = '<div class="row g-2">';

        if (pdfFile) {
            const sizeInMB = (pdfFile.size / 1024 / 1024).toFixed(1);
            filesHTML += `
                <div class="col-md-6">
                    <div class="d-flex align-items-center p-2 bg-light rounded">
                        <i class="bi bi-file-pdf text-danger fs-4 me-2"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-truncate" title="${pdfFile.name}">${pdfFile.name}</div>
                            <small class="text-muted">${sizeInMB} MB</small>
                        </div>
                        <div id="pdf-status" class="ms-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        if (coverFile) {
            const sizeInKB = (coverFile.size / 1024).toFixed(0);
            filesHTML += `
                <div class="col-md-6">
                    <div class="d-flex align-items-center p-2 bg-light rounded">
                        <i class="bi bi-image text-success fs-4 me-2"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-truncate" title="${coverFile.name}">${coverFile.name}</div>
                            <small class="text-muted">${sizeInKB} KB</small>
                        </div>
                        <div id="cover-status" class="ms-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        filesHTML += '</div>';
        this.filesListContainer.innerHTML = filesHTML;
    }

    startProgressSimulation() {
        this.currentProgress = 0;
        let stage = 0; // 0: preparing, 1: uploading, 2: processing, 3: finalizing

        this.uploadInterval = setInterval(() => {
            // Increment progress based on stage
            if (stage === 0) { // Preparing (0-15%)
                this.currentProgress += Math.random() * 3;
                if (this.currentProgress >= 15) {
                    stage = 1;
                    this.updateStatus('Uploading files to ImageKit...');
                    this.updateFileStatus('pdf-status', 'uploading');
                    this.updateFileStatus('cover-status', 'uploading');
                }
            } else if (stage === 1) { // Uploading (15-70%)
                this.currentProgress += Math.random() * 8;
                if (this.currentProgress >= 70) {
                    stage = 2;
                    this.updateStatus('Processing files...');
                    this.updateFileStatus('pdf-status', 'processing');
                    this.updateFileStatus('cover-status', 'processing');
                }
            } else if (stage === 2) { // Processing (70-90%)
                this.currentProgress += Math.random() * 4;
                if (this.currentProgress >= 90) {
                    stage = 3;
                    this.updateStatus('Finalizing upload...');
                }
            } else if (stage === 3) { // Finalizing (90-95%)
                this.currentProgress += Math.random() * 1;
                if (this.currentProgress >= 95) {
                    this.currentProgress = 95; // Cap at 95% until form actually submits
                }
            }

            // Update progress bar
            this.updateProgressBar(Math.min(this.currentProgress, 95));

        }, 200 + Math.random() * 300); // Variable interval for realistic feel
    }

    updateStatus(message) {
        if (this.statusText) {
            this.statusText.textContent = message;
        }
    }

    updateProgressBar(progress) {
        if (this.progressBar) {
            this.progressBar.style.width = progress + '%';
        }
        if (this.percentageText) {
            this.percentageText.textContent = Math.round(progress) + '%';
        }
    }

    updateFileStatus(elementId, status) {
        const statusElement = document.getElementById(elementId);
        if (!statusElement) return;

        let iconHTML = '';
        switch (status) {
            case 'uploading':
                iconHTML = '<div class="spinner-border spinner-border-sm text-warning" role="status"></div>';
                break;
            case 'processing':
                iconHTML = '<div class="spinner-border spinner-border-sm text-info" role="status"></div>';
                break;
            case 'complete':
                iconHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                break;
            case 'error':
                iconHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                break;
        }

        statusElement.innerHTML = iconHTML;
    }

    updateFilePreview() {
        const pdfFile = document.getElementById('pdf_file')?.files[0];
        const coverFile = document.getElementById('cover_image')?.files[0];

        if (pdfFile || coverFile) {
            let detailsHTML = '<strong>Selected files:</strong> ';
            const details = [];

            if (pdfFile) {
                details.push(`PDF: ${pdfFile.name} (${(pdfFile.size / 1024 / 1024).toFixed(1)}MB)`);
            }

            if (coverFile) {
                details.push(`Cover: ${coverFile.name} (${(coverFile.size / 1024).toFixed(0)}KB)`);
            }

            this.detailsText.innerHTML = detailsHTML + details.join(' | ');
        }
    }

    // Method to complete upload (call this when upload is actually finished)
    completeUpload() {
        if (this.uploadInterval) {
            clearInterval(this.uploadInterval);
        }

        this.updateProgressBar(100);
        this.updateStatus('Upload completed successfully!');
        this.updateFileStatus('pdf-status', 'complete');
        this.updateFileStatus('cover-status', 'complete');

        // Update progress bar to success state
        if (this.progressBar) {
            this.progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            this.progressBar.classList.add('bg-success');
        }
    }

    // Method to handle upload error
    handleUploadError(message) {
        if (this.uploadInterval) {
            clearInterval(this.uploadInterval);
        }

        this.updateStatus('Upload failed: ' + message);
        this.updateFileStatus('pdf-status', 'error');
        this.updateFileStatus('cover-status', 'error');

        // Update progress bar to error state
        if (this.progressBar) {
            this.progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            this.progressBar.classList.add('bg-danger');
        }

        // Re-enable submit button
        if (this.submitButton) {
            this.submitButton.disabled = false;
            this.submitButton.innerHTML = '<i class="bi bi-plus-circle me-2"></i> Add Book';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    // Only initialize on pages with file upload forms
    if (document.querySelector('form[enctype="multipart/form-data"]')) {
        new UploadProgressHandler();
    }
});

// Enhanced file validation with better UX
function validatePdfFile(input) {
    const file = input.files[0];
    const errorContainer = document.getElementById('pdf-file-error');
    const selectedFileName = document.getElementById('selected-file-name');

    if (!file) return;

    // Clear previous errors
    if (errorContainer) {
        errorContainer.style.display = 'none';
        errorContainer.textContent = '';
    }

    let isValid = true;
    let errorMessage = '';

    // Check file type
    if (file.type !== 'application/pdf') {
        errorMessage = 'Please select a valid PDF file.';
        isValid = false;
    }

    // Check file size (50MB limit)
    else if (file.size > 50 * 1024 * 1024) {
        errorMessage = 'PDF file size must be less than 50MB.';
        isValid = false;
    }

    if (!isValid) {
        if (errorContainer) {
            errorContainer.textContent = errorMessage;
            errorContainer.style.display = 'block';
        }
        input.value = '';
    } else {
        // Show selected file info
        if (selectedFileName) {
            const sizeInMB = (file.size / 1024 / 1024).toFixed(1);
            selectedFileName.innerHTML = `
                <i class="bi bi-file-pdf text-danger me-2"></i>
                <strong>${file.name}</strong> (${sizeInMB} MB)
            `;
            selectedFileName.style.display = 'block';
        }
    }

    return isValid;
}

function validateCoverImage(input) {
    const file = input.files[0];
    const errorContainer = document.getElementById('cover-image-error');
    const previewContainer = document.getElementById('cover-preview-container');
    const preview = document.getElementById('cover-preview');

    if (!file) return;

    // Clear previous errors
    if (errorContainer) {
        errorContainer.style.display = 'none';
        errorContainer.textContent = '';
    }

    let isValid = true;
    let errorMessage = '';

    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        errorMessage = 'Please select a valid image file (JPEG, PNG, GIF, or WebP).';
        isValid = false;
    }

    // Check file size (5MB limit)
    else if (file.size > 5 * 1024 * 1024) {
        errorMessage = 'Cover image size must be less than 5MB.';
        isValid = false;
    }

    if (!isValid) {
        if (errorContainer) {
            errorContainer.textContent = errorMessage;
            errorContainer.style.display = 'block';
        }
        input.value = '';
        if (previewContainer) {
            previewContainer.classList.add('d-none');
        }
    } else {
        // Show preview
        if (preview && previewContainer) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                previewContainer.classList.remove('d-none');

                // Add fade-in animation
                previewContainer.style.opacity = '0';
                previewContainer.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    previewContainer.style.transition = 'all 0.3s ease-out';
                    previewContainer.style.opacity = '1';
                    previewContainer.style.transform = 'scale(1)';
                }, 50);
            };
            reader.readAsDataURL(file);
        }
    }

    return isValid;
}
