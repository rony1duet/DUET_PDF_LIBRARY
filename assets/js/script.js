/**
 * DUET PDF Library - Consolidated JavaScript
 * All functionality combined in a single optimized file
 */

document.addEventListener("DOMContentLoaded", function () {
  // Initialize all components
  initializeFlashMessages();
  initializeNavigation();
  initializeSearch();
  initializeAnimations();
  initializeScrollEffects();
  initializeFormHandlers();
  initializeTooltips();
  initializePagination();
  initializeFavorites();
  initializeImageLoading();
  initializeMobileEnhancements();
  initializeUploadProgress();
  initializeCategoryPage(); // Re-added category functionality

  // Auto-dismiss flash messages with enhanced animations
  function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll(".alert");
    if (flashMessages.length > 0) {
      setTimeout(function () {
        flashMessages.forEach(function (message) {
          if (message.classList.contains('alert-dismissible')) {
            message.style.opacity = "0";
            message.style.transform = "translateY(-20px)";
            setTimeout(function () {
              message.remove();
            }, 300);
          }
        });
      }, 5000);
    }
  }

  // Enhanced navigation with smooth animations
  function initializeNavigation() {
    // Mobile menu functionality
    const navbar = document.querySelector('.navbar');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
      navbarToggler.addEventListener('click', function () {
        // Add smooth animation class
        navbarCollapse.classList.add('collapsing');
        setTimeout(() => {
          navbarCollapse.classList.remove('collapsing');
        }, 350);
      });
    }

    // Navbar scroll effect
    if (navbar) {
      let lastScrollTop = 0;
      window.addEventListener('scroll', function () {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > 100) {
          navbar.classList.add('navbar-scrolled');
        } else {
          navbar.classList.remove('navbar-scrolled');
        }

        // Hide/show navbar on scroll
        if (scrollTop > lastScrollTop && scrollTop > 200) {
          navbar.style.transform = 'translateY(-100%)';
        } else {
          navbar.style.transform = 'translateY(0)';
        }
        lastScrollTop = scrollTop;
      });
    }

    // User dropdown animation
    const userDropdown = document.querySelector('.dropdown-toggle');
    if (userDropdown) {
      userDropdown.addEventListener('shown.bs.dropdown', function () {
        const menu = this.nextElementSibling;
        if (menu) {
          menu.style.animation = 'fadeInUp 0.3s ease-out';
        }
      });
    }
  }

  // Enhanced search functionality
  function initializeSearch() {
    const searchInputs = document.querySelectorAll('input[name="search"]');
    const searchForms = document.querySelectorAll('.search-form, form[action*="index.php"]');

    searchInputs.forEach(input => {
      let searchTimeout;

      input.addEventListener('input', function () {
        const value = this.value.trim();

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          if (value.length >= 2) {
            // Auto-submit for live search effect
            const form = this.closest('form');
            if (form && form.method.toLowerCase() === 'get') {
              // Only submit if it's a GET form (search forms)
              // form.submit();
            }
          }
        }, 500);
      });

      // Add clear button functionality
      if (input.value.trim()) {
        addClearButton(input);
      }

      input.addEventListener('input', function () {
        if (this.value.trim()) {
          addClearButton(this);
        } else {
          removeClearButton(this);
        }
      });
    });

    function addClearButton(input) {
      if (input.parentElement.querySelector('.search-clear-btn')) return;

      const clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.className = 'btn btn-link search-clear-btn p-0 position-absolute end-0 top-50 translate-middle-y me-2';
      clearBtn.innerHTML = '<i class="bi bi-x-circle text-muted"></i>';
      clearBtn.style.zIndex = '10';

      clearBtn.addEventListener('click', function () {
        input.value = '';
        input.focus();
        removeClearButton(input);
      });

      input.parentElement.style.position = 'relative';
      input.parentElement.appendChild(clearBtn);
    }

    function removeClearButton(input) {
      const clearBtn = input.parentElement.querySelector('.search-clear-btn');
      if (clearBtn) {
        clearBtn.remove();
      }
    }
  }

  // Modern animations and effects with fixed counter animation
  function initializeAnimations() {
    // Animated counters for statistics - Fixed implementation
    const counters = document.querySelectorAll('.counter');

    if (counters.length > 0) {
      const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
      };

      const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !entry.target.dataset.animated) {
            entry.target.dataset.animated = 'true';
            animateCounter(entry.target);
            counterObserver.unobserve(entry.target);
          }
        });
      }, observerOptions);

      counters.forEach(counter => {
        counterObserver.observe(counter);
      });
    }    // Card hover animations - removed transform animations for book cards

    // Floating shapes animation
    const shapes = document.querySelectorAll('.shape, .floating-shape');
    shapes.forEach((shape, index) => {
      shape.style.animationDelay = `${index * 0.5}s`;
      shape.style.animationDuration = `${6 + index * 2}s`;
    });

    // Parallax effect for hero section (only on desktop)
    const hero = document.querySelector('.hero-section');
    if (hero && window.innerWidth > 768) {
      window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.3;
        hero.style.transform = `translateY(${rate}px)`;
      });
    }
  }

  // Fixed counter animation function
  function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target') || element.textContent);
    if (isNaN(target) || target === 0) return;

    const duration = 2000; // 2 seconds
    const startTime = performance.now();
    const startValue = 0;

    function updateCounter(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Use easing function for smooth animation
      const easeOutQuart = 1 - Math.pow(1 - progress, 4);
      const currentValue = Math.floor(startValue + (target * easeOutQuart));

      element.textContent = currentValue.toLocaleString();

      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      } else {
        element.textContent = target.toLocaleString();
      }
    }

    requestAnimationFrame(updateCounter);
  }

  // Enhanced scroll effects
  function initializeScrollEffects() {
    // Fade in animation for elements
    const fadeElements = document.querySelectorAll('.fade-in');
    if (fadeElements.length > 0) {
      const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            fadeObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      fadeElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        fadeObserver.observe(element);
      });
    }

    // Smooth scroll for anchor links
    const anchors = document.querySelectorAll('a[href^="#"]');
    anchors.forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Navbar background change on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
          navbar.classList.add('scrolled');
        } else {
          navbar.classList.remove('scrolled');
        }
      });
    }
  }

  // Enhanced form handlers and validation
  function initializeFormHandlers() {
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
      button.addEventListener('click', function () {
        const passwordField = document.querySelector(this.getAttribute('data-target'));
        if (passwordField) {
          const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordField.setAttribute('type', type);

          const icon = this.querySelector('i');
          if (icon) {
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
          }
        }
      });
    });

    // File upload preview
    const coverImageInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview');

    if (coverImageInput && coverPreview) {
      coverImageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function (e) {
            coverPreview.src = e.target.result;
            coverPreview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // PDF file validation
    const pdfFileInput = document.getElementById('pdf_file');
    if (pdfFileInput) {
      pdfFileInput.addEventListener('change', function () {
        validatePdfFile(this);
      });
    }

    // Enhanced form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
      form.addEventListener('submit', function (e) {
        if (!validateForm(this)) {
          e.preventDefault();
        }
      });
    });

    // Real-time validation
    const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
      input.addEventListener('blur', function () {
        validateField(this);
      });
    });
  }

  // Initialize Bootstrap tooltips and popovers
  function initializeTooltips() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl);
    });
  }

  // Enhanced pagination with smooth transitions
  function initializePagination() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        if (!this.parentElement.classList.contains('disabled')) {
          // Add loading state
          this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }
      });
    });
  }

  // Favorite books functionality
  function initializeFavorites() {
    const favoriteButtons = document.querySelectorAll('[data-favorite-toggle]');
    favoriteButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const bookId = this.getAttribute('data-book-id');
        const isFavorite = this.getAttribute('data-is-favorite') === 'true';
        toggleFavorite(bookId, isFavorite, this);
      });
    });
  }

  function toggleFavorite(bookId, isFavorite, button) {
    // Disable button during request
    button.disabled = true;

    // Make AJAX request
    fetch(`ajax/toggle-favorite.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ book_id: bookId })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update button state
          const newIsFavorite = !isFavorite;
          button.setAttribute('data-is-favorite', newIsFavorite.toString());

          const icon = button.querySelector('i');
          if (icon) {
            icon.classList.toggle('bi-heart');
            icon.classList.toggle('bi-heart-fill');
          }

          // Update button text if present
          const text = button.querySelector('.btn-text');
          if (text) {
            text.textContent = newIsFavorite ? 'Remove from Favorites' : 'Add to Favorites';
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
      })
      .finally(() => {
        button.disabled = false;
      });
  }

  // Enhanced image loading with lazy loading and error handling
  function initializeImageLoading() {
    const bookImages = document.querySelectorAll('.book-image, img[data-src]');

    bookImages.forEach(img => {
      // Add loading state
      img.setAttribute('data-loading', 'true');

      // Handle successful image load
      img.addEventListener('load', function () {
        this.removeAttribute('data-loading');
        this.style.opacity = '0';
        this.style.transition = 'opacity 0.3s ease';

        // Fade in the image
        setTimeout(() => {
          this.style.opacity = '1';
        }, 50);
      });

      // Handle image load error
      img.addEventListener('error', function () {
        this.removeAttribute('data-loading');
        const placeholder = document.createElement('div');
        placeholder.className = 'book-placeholder d-flex align-items-center justify-content-center';
        placeholder.innerHTML = '<i class="bi bi-journal-bookmark display-4 text-primary opacity-50"></i>';
        placeholder.style.cssText = this.style.cssText;
        this.parentElement.replaceChild(placeholder, this);
      });
    });

    // Implement intersection observer for lazy loading
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            if (img.dataset.src) {
              img.src = img.dataset.src;
              img.removeAttribute('data-src');
              observer.unobserve(img);
            }
          }
        });
      });

      document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
      });
    }
  }

  // Mobile-specific enhancements
  function initializeMobileEnhancements() {
    // Detect touch device
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (isTouchDevice) {
      document.body.classList.add('touch-device');

      // Improve touch interactions for book cards
      const bookCards = document.querySelectorAll('.book-card');
      bookCards.forEach(card => {
        let touchStartTime;

        card.addEventListener('touchstart', function (e) {
          touchStartTime = Date.now();
          this.classList.add('touch-active');

          // Add haptic feedback if supported
          if (navigator.vibrate) {
            navigator.vibrate(10);
          }
        }, { passive: true });

        card.addEventListener('touchend', function (e) {
          const touchDuration = Date.now() - touchStartTime;

          setTimeout(() => {
            this.classList.remove('touch-active');
          }, 150);

          // Handle quick taps (< 300ms) as clicks
          if (touchDuration < 300) {
            const link = this.querySelector('.book-cover-link, .stretched-link');
            if (link && !e.target.closest('.badge, .btn')) {
              // Prevent default touch handling
              e.preventDefault();
              e.stopPropagation();

              // Navigate to book page
              setTimeout(() => {
                window.location.href = link.href;
              }, 100);
            }
          }
        });

        card.addEventListener('touchcancel', function () {
          this.classList.remove('touch-active');
        });
      });
    }

    // Handle orientation changes
    window.addEventListener('orientationchange', function () {
      setTimeout(() => {
        // Recalculate layouts after orientation change
        window.dispatchEvent(new Event('resize'));
      }, 100);
    });

    // Improve viewport handling on mobile
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport && window.innerWidth <= 768) {
      viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
  }

  // Upload progress functionality
  function initializeUploadProgress() {
    const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (!uploadForm) return;

    const progressContainer = createProgressUI(uploadForm);

    uploadForm.addEventListener('submit', function (e) {
      const pdfFile = document.getElementById('pdf_file')?.files[0];
      const coverFile = document.getElementById('cover_image')?.files[0];

      if (pdfFile || coverFile) {
        showProgress(progressContainer);
        simulateUploadProgress(progressContainer);
      }
    });
  }

  function createProgressUI(form) {
    const progressHTML = `
      <div id="upload-progress-container" class="card mt-4 d-none">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0">
            <i class="bi bi-cloud-upload me-2"></i>
            Uploading Files
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
        </div>
      </div>
    `;

    form.insertAdjacentHTML('afterend', progressHTML);
    return document.getElementById('upload-progress-container');
  }

  function showProgress(container) {
    container.classList.remove('d-none');
    container.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Disable form submission button
    const submitBtn = container.previousElementSibling.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Uploading...';
    }
  }

  function simulateUploadProgress(container) {
    const progressBar = container.querySelector('#upload-progress-bar');
    const statusText = container.querySelector('#upload-status-text');
    const percentageText = container.querySelector('#upload-percentage');

    let progress = 0;
    const interval = setInterval(() => {
      progress += Math.random() * 10;

      if (progress < 30) {
        statusText.textContent = 'Uploading files...';
      } else if (progress < 60) {
        statusText.textContent = 'Processing files...';
      } else if (progress < 90) {
        statusText.textContent = 'Finalizing upload...';
      } else {
        progress = 100;
        statusText.textContent = 'Upload complete!';
        clearInterval(interval);
      }

      progressBar.style.width = Math.min(progress, 100) + '%';
      percentageText.textContent = Math.min(Math.floor(progress), 100) + '%';

      if (progress >= 100) {
        setTimeout(() => {
          progressBar.classList.remove('progress-bar-animated');
          progressBar.classList.add('bg-success');
        }, 500);
      }
    }, 200 + Math.random() * 300);
  }

  // Initialize category page functionality
  function initializeCategoryPage() {
    // Only run on categories page
    if (!document.querySelector('.category-card')) return;

    initializeCategoryCards();
    initializeCategoryDeleteButtons();
  }

  // Enhanced category card interactions
  function initializeCategoryCards() {
    const categoryCards = document.querySelectorAll('.category-card');

    categoryCards.forEach(card => {
      // Handle card click for navigation with proper event handling
      card.addEventListener('click', function (e) {
        // Don't navigate if clicking on admin controls or buttons
        if (e.target.closest('.category-actions') || e.target.closest('button') || e.target.closest('a.btn-action')) {
          return;
        }

        const categoryLink = card.querySelector('.category-link');
        if (categoryLink) {
          window.location.href = categoryLink.href;
        }
      });

      // Add keyboard navigation
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const categoryLink = card.querySelector('.category-link');
          if (categoryLink) {
            window.location.href = categoryLink.href;
          }
        }
      });

      // Make card focusable and accessible
      card.setAttribute('tabindex', '0');
      card.setAttribute('role', 'button');

      const categoryLink = card.querySelector('.category-link');
      if (categoryLink) {
        card.setAttribute('aria-label', `View ${categoryLink.textContent.trim()} category`);
      }
    });
  }

  // Enhanced delete functionality
  function initializeCategoryDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.btn-delete[data-category-id]');

    deleteButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        handleCategoryDelete(this);
      });
    });
  }

  // Book Detail Page Functionality
  initializeBookDetailPage();

  // Book detail page specific functionality
  function initializeBookDetailPage() {
    // Check if we're on the book detail page
    if (!document.querySelector('.book-detail-page')) {
      return;
    }

    // Initialize PDF viewer
    initializePDFViewer();

    // Initialize favorite functionality
    initializeBookFavorites();

    // Initialize fullscreen functionality
    initializeFullscreenViewer();

    // Initialize download tracking
    initializeDownloadTracking();
  }

  // PDF Viewer functionality
  function initializePDFViewer() {
    const iframe = document.querySelector('.pdf-iframe');
    const loadingIndicator = document.querySelector('.viewer-loading');

    if (iframe && loadingIndicator) {
      // Show loading indicator initially
      loadingIndicator.style.display = 'flex';

      // Hide loading indicator when iframe loads
      iframe.addEventListener('load', function () {
        loadingIndicator.style.display = 'none';
        iframe.classList.add('loaded');
      });

      // Handle iframe load errors
      iframe.addEventListener('error', function () {
        loadingIndicator.innerHTML = `
          <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <span>Failed to load PDF. Please try again.</span>
        `;
      });
    }
  }

  // Book favorites functionality
  function initializeBookFavorites() {
    const favoriteForm = document.querySelector('.favorite-form');
    const favoriteBtn = document.querySelector('.action-btn-favorite');

    if (favoriteForm && favoriteBtn) {
      favoriteForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Add loading state
        favoriteBtn.disabled = true;
        const originalContent = favoriteBtn.innerHTML;
        favoriteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Processing...</span>';

        // Submit form via fetch
        const formData = new FormData(favoriteForm);

        fetch(favoriteForm.action, {
          method: 'POST',
          body: formData
        })
          .then(response => response.text())
          .then(() => {
            // Reload page to update favorite status
            window.location.reload();
          })
          .catch(error => {
            console.error('Error:', error);
            favoriteBtn.disabled = false;
            favoriteBtn.innerHTML = originalContent;
            showToast('Error updating favorite status', 'danger');
          });
      });
    }
  }

  // Fullscreen viewer functionality
  function initializeFullscreenViewer() {
    const fullscreenBtn = document.querySelector('.viewer-btn-fullscreen');
    const pdfContainer = document.querySelector('.pdf-viewer-container');

    if (fullscreenBtn && pdfContainer) {
      fullscreenBtn.addEventListener('click', function () {
        toggleFullscreen();
      });

      // Listen for fullscreen changes
      document.addEventListener('fullscreenchange', function () {
        updateFullscreenButton();
      });
    }
  }

  // Download tracking
  function initializeDownloadTracking() {
    const downloadBtns = document.querySelectorAll('[data-action="download"]');

    downloadBtns.forEach(btn => {
      btn.addEventListener('click', function (e) {
        // Add download animation
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.add('animate-bounce');
          setTimeout(() => {
            icon.classList.remove('animate-bounce');
          }, 1000);
        }

        // Track download (could be enhanced with analytics)
        console.log('Download initiated for book:', btn.href);
      });
    });
  }

  // Enhanced mobile experience
  initializeMobileBookPage();

  // Mobile-specific book page enhancements
  function initializeMobileBookPage() {
    if (!document.querySelector('.book-detail-page')) {
      return;
    }

    // Add swipe gestures for mobile PDF viewer (if supported)
    const pdfContainer = document.querySelector('.pdf-viewer-container');
    if (pdfContainer && 'ontouchstart' in window) {
      let startX, startY, startTime;

      pdfContainer.addEventListener('touchstart', function (e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTime = Date.now();
      }, { passive: true });

      pdfContainer.addEventListener('touchend', function (e) {
        if (!startX || !startY) return;

        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        const diffX = startX - endX;
        const diffY = startY - endY;
        const timeDiff = Date.now() - startTime;

        // Only process quick swipes
        if (timeDiff > 300) return;

        // Vertical swipe to toggle fullscreen
        if (Math.abs(diffY) > Math.abs(diffX) && Math.abs(diffY) > 50) {
          if (diffY > 0) {
            // Swipe up - enter fullscreen
            if (!document.fullscreenElement) {
              toggleFullscreen();
            }
          }
        }

        startX = startY = null;
      }, { passive: true });
    }

    // Improve mobile scrolling behavior
    const bookInfoWrapper = document.querySelector('.book-info-wrapper');
    if (bookInfoWrapper && window.innerWidth <= 991) {
      // Make sure sticky positioning works well on mobile
      bookInfoWrapper.style.position = 'static';
    }

    // Add haptic feedback for supported devices
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        if ('vibrate' in navigator) {
          navigator.vibrate(50); // Light haptic feedback
        }
      });
    });
  }

  // Lazy loading for book cover images
  function initializeLazyLoading() {
    const bookCoverImages = document.querySelectorAll('.book-cover-image');

    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src || img.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
          }
        });
      });

      bookCoverImages.forEach(img => {
        if (img.dataset.src) {
          imageObserver.observe(img);
        }
      });
    }
  }

  // Performance optimization for PDF loading
  function optimizePDFLoading() {
    const iframe = document.querySelector('.pdf-iframe');
    if (!iframe) return;

    // Add loading="lazy" attribute for better performance
    iframe.setAttribute('loading', 'lazy');

    // Preload PDF on hover (desktop only)
    if (window.innerWidth > 768) {
      const downloadBtns = document.querySelectorAll('[data-action="download"]');
      downloadBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function () {
          // Preload PDF in background
          const link = document.createElement('link');
          link.rel = 'prefetch';
          link.href = iframe.src;
          document.head.appendChild(link);
        }, { once: true });
      });
    }
  }

  // Call additional initialization functions
  initializeLazyLoading();
  optimizePDFLoading();
});

// Global functions for book detail page
window.toggleFullscreen = function () {
  const pdfContainer = document.querySelector('.pdf-viewer-container');
  const fullscreenBtn = document.querySelector('.viewer-btn-fullscreen');

  if (!pdfContainer) return;

  if (!document.fullscreenElement) {
    pdfContainer.requestFullscreen().catch(err => {
      console.error('Error attempting to enable fullscreen:', err);
      showToast('Fullscreen not supported', 'warning');
    });
  } else {
    document.exitFullscreen();
  }
};

function updateFullscreenButton() {
  const fullscreenBtn = document.querySelector('.viewer-btn-fullscreen');
  const icon = fullscreenBtn?.querySelector('i');

  if (icon) {
    if (document.fullscreenElement) {
      icon.className = 'bi bi-fullscreen-exit';
      fullscreenBtn.title = 'Exit Fullscreen';
    } else {
      icon.className = 'bi bi-fullscreen';
      fullscreenBtn.title = 'Toggle Fullscreen';
    }
  }
}

window.confirmDelete = function (bookTitle) {
  const message = `Are you sure you want to delete "${bookTitle}"?\n\nThis action cannot be undone and will permanently remove the book from the library.`;
  return confirm(message);
};

// Enhanced toast notification function
function showToast(message, type = 'info') {
  // Remove existing toasts
  const existingToasts = document.querySelectorAll('.custom-toast');
  existingToasts.forEach(toast => toast.remove());

  const toast = document.createElement('div');
  toast.className = `custom-toast alert alert-${type} alert-dismissible fade show`;
  toast.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border: none;
    border-radius: 12px;
    animation: slideIn 0.3s ease-out;
  `;

  const iconMap = {
    'success': 'check-circle',
    'danger': 'exclamation-triangle',
    'warning': 'exclamation-triangle',
    'info': 'info-circle'
  };

  const icon = iconMap[type] || 'info-circle';

  toast.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi bi-${icon} me-2"></i>
      <span>${message}</span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  document.body.appendChild(toast);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.animation = 'slideOut 0.3s ease-in forwards';
      setTimeout(() => {
        toast.remove();
      }, 300);
    }
  }, 5000);
}

// Add CSS animations for toast
if (!document.querySelector('#toast-animations')) {
  const style = document.createElement('style');
  style.id = 'toast-animations';
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
    
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
      }
      40% {
        transform: translateY(-10px);
      }
      60% {
        transform: translateY(-5px);
      }
    }
    
    .animate-bounce {
      animation: bounce 1s ease-in-out;
    }    `;
  document.head.appendChild(style);
}

// Call additional initialization functions
initializeLazyLoading();
optimizePDFLoading();

console.log('DUET PDF Library initialized successfully');

// Lazy loading for book cover images
function initializeLazyLoading() {
  const bookCoverImages = document.querySelectorAll('.book-cover-image');

  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src || img.src;
          img.classList.remove('lazy');
          observer.unobserve(img);
        }
      });
    });

    bookCoverImages.forEach(img => {
      if (img.dataset.src) {
        imageObserver.observe(img);
      }
    });
  }
}

// Performance optimization for PDF loading
function optimizePDFLoading() {
  const iframe = document.querySelector('.pdf-iframe');
  if (!iframe) return;

  // Add loading="lazy" attribute for better performance
  iframe.setAttribute('loading', 'lazy');

  // Preload PDF on hover (desktop only)
  if (window.innerWidth > 768) {
    const downloadBtns = document.querySelectorAll('[data-action="download"]');
    downloadBtns.forEach(btn => {
      btn.addEventListener('mouseenter', function () {
        // Preload PDF in background
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = iframe.src;
        document.head.appendChild(link);
      }, { once: true });
    });
  }
}

// Global category delete function (accessible from inline onclick handlers)
function handleCategoryDelete(button) {
  const categoryName = button.getAttribute('data-category-name');
  const categoryId = button.getAttribute('data-category-id');

  if (!categoryName || !categoryId) {
    alert('Error: Category information not found');
    return;
  }

  // Enhanced confirmation dialog
  const confirmMessage = `Are you sure you want to delete the category "${categoryName}"?\n\nThis action cannot be undone. Books in this category will remain but become uncategorized.`;

  if (confirm(confirmMessage)) {
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin/delete-category.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'category_id';
    input.value = categoryId;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }
}

// Global utility functions
window.duetLibrary = {
  showToast: function (message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 5000);
  },

  confirmDelete: function (message = 'Are you sure you want to delete this item?') {
    return confirm(message);
  }
};
