<?php

/**
 * DUET PDF Library - PDF Viewer
 * Embeds PDF.js to view PDF files securely
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book();

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('No file specified');
}

$filePath = $_GET['file'];

// Validate file path (basic security check)
if (strpos($filePath, '../') !== false || strpos($filePath, '..\\') !== false) {
    die('Invalid file path');
}

// Get file URL from Firebase or local storage
$fileUrl = $bookObj->getFileViewUrl($filePath);

// If no valid URL, show error
if (empty($fileUrl)) {
    die('File not found or inaccessible');
}

// Page title
$pageTitle = 'PDF Viewer - DUET PDF Library';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- PDF.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        #pdf-container {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            background-color: #525659;
            position: relative;
        }

        #pdf-viewer {
            width: 100%;
            height: 100%;
            overflow: auto;
        }

        #pdf-controls {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #pdf-controls button {
            margin: 0 5px;
        }

        #page-info {
            color: white;
            margin: 0 15px;
        }

        #zoom-controls {
            margin: 0 15px;
        }

        .page-canvas {
            display: block;
            margin: 10px auto;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .loading-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>
    <div id="pdf-container">
        <div id="pdf-viewer"></div>

        <div class="loading-indicator" id="loading-indicator">
            <div class="spinner-border text-light mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5>Loading PDF...</h5>
        </div>

        <div id="pdf-controls">
            <button id="prev-page" class="btn btn-sm btn-light">
                <i class="bi bi-chevron-left"></i> Previous
            </button>

            <div id="page-info" class="mx-3">
                Page <span id="current-page">0</span> of <span id="total-pages">0</span>
            </div>

            <button id="next-page" class="btn btn-sm btn-light">
                Next <i class="bi bi-chevron-right"></i>
            </button>

            <div id="zoom-controls" class="mx-3">
                <button id="zoom-out" class="btn btn-sm btn-light">
                    <i class="bi bi-zoom-out"></i>
                </button>
                <button id="zoom-in" class="btn btn-sm btn-light ms-2">
                    <i class="bi bi-zoom-in"></i>
                </button>
            </div>

            <?php if ($auth->isLoggedIn()): ?>
                <a href="book.php?id=<?php echo isset($_GET['id']) ? (int)$_GET['id'] : 0; ?>&download=true" class="btn btn-sm btn-primary">
                    <i class="bi bi-download"></i> Download
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // PDF.js initialization
        const pdfjsLib = window['pdfjs-dist/build/pdf'];

        // The workerSrc property needs to be specified
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        // PDF file URL
        const pdfUrl = '<?php echo $fileUrl; ?>';

        // Variables
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.0;
        const pdfViewer = document.getElementById('pdf-viewer');
        const loadingIndicator = document.getElementById('loading-indicator');

        /**
         * Get page info from document, resize canvas accordingly, and render page.
         * @param num Page number.
         */
        function renderPage(num) {
            pageRendering = true;

            // Remove any existing canvas
            const existingCanvas = document.getElementById(`page-${num}`);
            if (existingCanvas) {
                return; // Page already rendered
            }

            // Using promise to fetch the page
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({
                    scale: scale
                });
                const canvas = document.createElement('canvas');
                canvas.id = `page-${num}`;
                canvas.className = 'page-canvas';
                pdfViewer.appendChild(canvas);

                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // Render PDF page into canvas context
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                const renderTask = page.render(renderContext);

                // Wait for rendering to finish
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        // New page rendering is pending
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }

                    // Update current page display
                    document.getElementById('current-page').textContent = num;
                });
            });
        }

        /**
         * If another page rendering in progress, waits until the rendering is
         * finished. Otherwise, executes rendering immediately.
         */
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        /**
         * Displays previous page.
         */
        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            scrollToPage(pageNum);
        }

        /**
         * Displays next page.
         */
        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            scrollToPage(pageNum);
        }

        /**
         * Scroll to specific page
         */
        function scrollToPage(num) {
            const canvas = document.getElementById(`page-${num}`);
            if (canvas) {
                canvas.scrollIntoView({
                    behavior: 'smooth'
                });
                document.getElementById('current-page').textContent = num;
            } else {
                // If page not rendered yet, render it
                queueRenderPage(num);
            }
        }

        /**
         * Zoom in the PDF
         */
        function zoomIn() {
            scale += 0.25;
            reRenderAllPages();
        }

        /**
         * Zoom out the PDF
         */
        function zoomOut() {
            if (scale <= 0.5) return;
            scale -= 0.25;
            reRenderAllPages();
        }

        /**
         * Re-render all pages with new scale
         */
        function reRenderAllPages() {
            // Clear the viewer
            pdfViewer.innerHTML = '';

            // Render all pages
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                renderPage(i);
            }

            // Scroll to current page after re-rendering
            setTimeout(() => {
                scrollToPage(pageNum);
            }, 100);
        }

        /**
         * Load and render the PDF
         */
        function loadPDF() {
            // Asynchronously download PDF
            pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                pdfDoc = pdf;
                document.getElementById('total-pages').textContent = pdf.numPages;

                // Hide loading indicator
                loadingIndicator.style.display = 'none';

                // Initial render of first few pages
                const pagesToPreload = Math.min(3, pdf.numPages);
                for (let i = 1; i <= pagesToPreload; i++) {
                    renderPage(i);
                }

                // Add scroll event listener to detect current page
                pdfViewer.addEventListener('scroll', function() {
                    // Find which page is most visible
                    let maxVisibleHeight = 0;
                    let mostVisiblePage = pageNum;

                    for (let i = 1; i <= pdfDoc.numPages; i++) {
                        const canvas = document.getElementById(`page-${i}`);
                        if (canvas) {
                            const rect = canvas.getBoundingClientRect();
                            const visibleHeight = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);

                            if (visibleHeight > maxVisibleHeight) {
                                maxVisibleHeight = visibleHeight;
                                mostVisiblePage = i;
                            }
                        }
                    }

                    if (mostVisiblePage !== pageNum) {
                        pageNum = mostVisiblePage;
                        document.getElementById('current-page').textContent = pageNum;
                    }

                    // Lazy load pages as user scrolls
                    const visiblePageRange = 2; // Load 2 pages ahead and behind
                    for (let i = Math.max(1, pageNum - visiblePageRange); i <= Math.min(pdfDoc.numPages, pageNum + visiblePageRange); i++) {
                        if (!document.getElementById(`page-${i}`)) {
                            renderPage(i);
                        }
                    }
                });
            }).catch(function(error) {
                // Display error message
                loadingIndicator.innerHTML = `<div class="alert alert-danger">Error loading PDF: ${error.message}</div>`;
                console.error('Error loading PDF:', error);
            });
        }

        // Event listeners
        document.getElementById('prev-page').addEventListener('click', onPrevPage);
        document.getElementById('next-page').addEventListener('click', onNextPage);
        document.getElementById('zoom-in').addEventListener('click', zoomIn);
        document.getElementById('zoom-out').addEventListener('click', zoomOut);

        // Load the PDF
        loadPDF();
    </script>
</body>

</html>