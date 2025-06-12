<?php

/**
 * DUET PDF Library - Add Book
 * Admin page to add a new book with PDF cover page selection
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';
require_once '../includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);
$categoryObj = new Category();

// Require admin access
$auth->requireAdmin();

// Get all categories for the form
$categories = $categoryObj->getAllCategories(true); // Include empty categories for admin

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publicationDate = trim($_POST['publication_date'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $selectedCoverPage = (int)($_POST['selected_cover_page'] ?? 1);

    // Validate form data
    if (empty($title)) {
        $errors[] = 'Book title is required';
    }

    if (empty($author)) {
        $errors[] = 'Author name is required';
    }

    if (empty($publicationDate)) {
        $errors[] = 'Publication date is required';
    } elseif (!strtotime($publicationDate)) {
        $errors[] = 'Invalid publication date format';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a category';
    }

    // Validate file uploads
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'PDF file is required';
    } else {
        // Check file type
        $pdfFileType = $_FILES['pdf_file']['type'];
        if ($pdfFileType !== 'application/pdf') {
            $errors[] = 'Only PDF files are allowed';
        }

        // Check file size (max 50MB)
        $pdfFileSize = $_FILES['pdf_file']['size'];
        if ($pdfFileSize > 50 * 1024 * 1024) {
            $errors[] = 'PDF file size must be less than 50MB';
        }
    }

    // Cover image is optional, but validate if provided
    $hasCoverImage = isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE;
    $useSelectedCoverPage = isset($_POST['use_pdf_cover']) && $_POST['use_pdf_cover'] === '1';

    if ($hasCoverImage && $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error uploading cover image';
    } elseif ($hasCoverImage) {
        // Check file type
        $coverImageType = $_FILES['cover_image']['type'];
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($coverImageType, $allowedImageTypes)) {
            $errors[] = 'Cover image must be JPEG, PNG, GIF, or WebP';
        }

        // Check file size (max 5MB)
        $coverImageSize = $_FILES['cover_image']['size'];
        if ($coverImageSize > 5 * 1024 * 1024) {
            $errors[] = 'Cover image size must be less than 5MB';
        }
    }

    // If no errors, add the book
    if (empty($errors)) {
        try {
            // Prepare cover data
            $coverData = null;
            if ($useSelectedCoverPage && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                // Use PDF page as cover
                $coverData = [
                    'type' => 'pdf_page',
                    'page' => $selectedCoverPage
                ];
            } elseif ($hasCoverImage) {
                // Use uploaded cover image
                $coverData = $_FILES['cover_image'];
            }

            // Add the book using the enhanced method
            $bookId = $bookObj->addBook([
                'title' => $title,
                'author' => $author,
                'edition' => $edition,
                'description' => $description,
                'published_year' => date('Y', strtotime($publicationDate)),
                'category_id' => $categoryId
            ], $_FILES['pdf_file'], $coverData);

            $success = true;

            // Set success message
            $_SESSION['flash_message'] = 'Book added successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to book page
            header('Location: ../book.php?id=' . $bookId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error adding book: ' . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Add New Book - DUET PDF Library';

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Flash Message Display -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Add New Book</h1>
            <p class="text-muted">Add a new book to the library</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Book added successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="add-book.php" enctype="multipart/form-data">
                <div class="row">
                    <!-- Book Details -->
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edition" class="form-label">Edition</label>
                            <input type="text" class="form-control" id="edition" name="edition" value="<?php echo htmlspecialchars($edition ?? ''); ?>" placeholder="e.g., 1st Edition, 2nd Edition">
                            <div class="form-text">Optional. Specify the edition of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Publication Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($publicationDate ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            <div class="form-text">Provide a brief description of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php if (empty($categories)): ?>
                                    <option value="" disabled>No categories available</option>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($categoryId) && $categoryId == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <div class="form-text text-danger">
                                    No categories found. <a href="add-category.php">Create a category first</a>.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- File Uploads -->
                    <div class="col-md-4">
                        <!-- PDF File Upload -->
                        <div class="mb-4">
                            <label class="form-label">PDF File <span class="text-danger">*</span></label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                                        <div class="form-text">Required. Max size: 50MB. Format: PDF only.</div>
                                    </div>

                                    <div id="pdf-file-error" class="alert alert-danger d-none"></div>
                                    <div id="selected-file-name" class="alert alert-info d-none"></div>

                                    <!-- PDF Preview and Page Selection -->
                                    <div id="pdf-preview-container" class="d-none">
                                        <div class="text-center mb-3">
                                            <h6>PDF Preview - Select Cover Page</h6>
                                            <div class="position-relative d-inline-block">
                                                <canvas id="pdf-preview-canvas" class="border rounded" style="max-width: 100%; height: auto;"></canvas>
                                                <div id="cover-page-indicator" class="position-absolute top-0 end-0 d-none">
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Selected as Cover
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Page Navigation -->
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <button type="button" id="prev-page-btn" class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </button>
                                            <div class="text-center">
                                                <div class="fw-semibold">Page <span id="current-page">1</span> of <span id="total-pages">1</span></div>
                                                <small class="text-muted">Navigate to select cover page</small>
                                            </div>
                                            <button type="button" id="next-page-btn" class="btn btn-sm btn-outline-secondary" disabled>
                                                Next <i class="bi bi-chevron-right"></i>
                                            </button>
                                        </div>

                                        <!-- Page Selection Input -->
                                        <div class="mb-3">
                                            <label for="page-jump" class="form-label small">Jump to page:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control" id="page-jump" min="1" max="1" value="1">
                                                <button type="button" class="btn btn-outline-secondary" id="jump-to-page-btn">Go</button>
                                            </div>
                                        </div>

                                        <!-- Use as Cover Option -->
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="use_pdf_cover" name="use_pdf_cover" value="1">
                                                    <label class="form-check-label fw-semibold" for="use_pdf_cover">
                                                        <i class="bi bi-image"></i> Use page <span id="cover-page-display">1</span> as book cover
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    The selected page will be extracted as a high-quality cover image.
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="selected_cover_page" name="selected_cover_page" value="1">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cover Image Upload -->
                        <div class="mb-4">
                            <label class="form-label">Alternative Cover Image</label>
                            <div class="card">
                                <div class="card-body">
                                    <div id="cover-preview-container" class="text-center mb-3 d-none">
                                        <img id="cover-preview" src="#" alt="Cover Preview" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>

                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">Optional. Max size: 5MB. Formats: JPEG, PNG, GIF, WebP.</div>
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle"></i>
                                            If you select "Use current page as book cover" above, this image will be ignored.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Global variables for PDF handling
    let currentPDF = null;
    let currentPage = 1;
    let totalPages = 1;
    let renderTask = null;

    document.addEventListener('DOMContentLoaded', function() {
        initializeFormHandlers();
    });

    function initializeFormHandlers() {
        // PDF file input handler
        const pdfInput = document.getElementById('pdf_file');
        const coverInput = document.getElementById('cover_image');
        const usePdfCoverCheckbox = document.getElementById('use_pdf_cover');

        if (pdfInput) {
            pdfInput.addEventListener('change', handlePDFUpload);
        }

        if (coverInput) {
            coverInput.addEventListener('change', handleCoverImageUpload);
        }

        // PDF navigation buttons
        const prevBtn = document.getElementById('prev-page-btn');
        const nextBtn = document.getElementById('next-page-btn');
        const pageJumpInput = document.getElementById('page-jump');
        const jumpToPageBtn = document.getElementById('jump-to-page-btn');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderPDFPage();
                    updatePageNavigation();
                    updateSelectedCoverPage();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPDFPage();
                    updatePageNavigation();
                    updateSelectedCoverPage();
                }
            });
        }

        // Page jump functionality
        if (jumpToPageBtn && pageJumpInput) {
            jumpToPageBtn.addEventListener('click', jumpToPage);
            pageJumpInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    jumpToPage();
                }
            });
        }

        // Use PDF cover checkbox handler
        if (usePdfCoverCheckbox) {
            usePdfCoverCheckbox.addEventListener('change', function() {
                updateCoverSelectionUI();
            });
        }
    }

    function jumpToPage() {
        const pageJumpInput = document.getElementById('page-jump');
        const targetPage = parseInt(pageJumpInput.value);

        if (targetPage >= 1 && targetPage <= totalPages && targetPage !== currentPage) {
            currentPage = targetPage;
            renderPDFPage();
            updatePageNavigation();
            updateSelectedCoverPage();
        }
    }

    function updateCoverSelectionUI() {
        const usePdfCoverCheckbox = document.getElementById('use_pdf_cover');
        const coverInput = document.getElementById('cover_image');
        const coverPageIndicator = document.getElementById('cover-page-indicator');

        if (usePdfCoverCheckbox && usePdfCoverCheckbox.checked) {
            // Show cover selection indicator
            if (coverPageIndicator) {
                coverPageIndicator.classList.remove('d-none');
            }

            // Disable cover image input when using PDF cover
            if (coverInput) {
                coverInput.disabled = true;
                coverInput.parentElement.classList.add('opacity-50');
            }
        } else {
            // Hide cover selection indicator
            if (coverPageIndicator) {
                coverPageIndicator.classList.add('d-none');
            }

            // Re-enable cover image input
            if (coverInput) {
                coverInput.disabled = false;
                coverInput.parentElement.classList.remove('opacity-50');
            }
        }
    }

    async function handlePDFUpload(event) {
        const file = event.target.files[0];
        const errorDiv = document.getElementById('pdf-file-error');
        const selectedFileDiv = document.getElementById('selected-file-name');
        const previewContainer = document.getElementById('pdf-preview-container');

        // Clear previous states
        errorDiv.classList.add('d-none');
        selectedFileDiv.classList.add('d-none');
        previewContainer.classList.add('d-none');

        if (!file) {
            return;
        }

        // Validate file type
        if (file.type !== 'application/pdf') {
            showError('Please select a valid PDF file.');
            return;
        }

        // Validate file size (50MB)
        if (file.size > 50 * 1024 * 1024) {
            showError('PDF file size must be less than 50MB.');
            return;
        }

        // Show selected file info
        selectedFileDiv.innerHTML = `<i class="bi bi-file-earmark-pdf"></i> ${file.name} (${formatFileSize(file.size)})`;
        selectedFileDiv.classList.remove('d-none');

        try {
            // Load PDF
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({
                data: arrayBuffer
            }).promise;

            currentPDF = pdf;
            totalPages = pdf.numPages;
            currentPage = 1;

            // Show preview container
            previewContainer.classList.remove('d-none');

            // Render first page
            await renderPDFPage();
            updatePageNavigation();
            updateSelectedCoverPage();

        } catch (error) {
            showError('Error loading PDF: ' + error.message);
            console.error('PDF loading error:', error);
        }
    }

    async function renderPDFPage() {
        if (!currentPDF) return;

        const canvas = document.getElementById('pdf-preview-canvas');
        const context = canvas.getContext('2d');

        try {
            // Cancel previous render task if it exists
            if (renderTask) {
                renderTask.cancel();
            }

            const page = await currentPDF.getPage(currentPage);
            const scale = 1.5;
            const viewport = page.getViewport({
                scale: scale
            });

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };

            renderTask = page.render(renderContext);
            await renderTask.promise;
            renderTask = null;

        } catch (error) {
            if (error.name !== 'RenderingCancelledException') {
                console.error('Error rendering PDF page:', error);
                showError('Error rendering PDF page: ' + error.message);
            }
        }
    }

    function updatePageNavigation() {
        const currentPageSpan = document.getElementById('current-page');
        const totalPagesSpan = document.getElementById('total-pages');
        const prevBtn = document.getElementById('prev-page-btn');
        const nextBtn = document.getElementById('next-page-btn');
        const pageJumpInput = document.getElementById('page-jump');

        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (totalPagesSpan) totalPagesSpan.textContent = totalPages;

        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }

        if (pageJumpInput) {
            pageJumpInput.value = currentPage;
            pageJumpInput.max = totalPages;
        }
    }

    function updateSelectedCoverPage() {
        const selectedCoverPageInput = document.getElementById('selected_cover_page');
        const coverPageDisplay = document.getElementById('cover-page-display');

        if (selectedCoverPageInput) {
            selectedCoverPageInput.value = currentPage;
        }

        if (coverPageDisplay) {
            coverPageDisplay.textContent = currentPage;
        }

        // Update the cover selection UI
        updateCoverSelectionUI();
    }

    function handleCoverImageUpload(event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById('cover-preview-container');
        const previewImage = document.getElementById('cover-preview');

        if (!file) {
            previewContainer.classList.add('d-none');
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showError('Cover image must be JPEG, PNG, GIF, or WebP.');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showError('Cover image size must be less than 5MB.');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    function showError(message) {
        const errorDiv = document.getElementById('pdf-file-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Set PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>

<?php
// Include footer
include '../includes/footer.php';
?>