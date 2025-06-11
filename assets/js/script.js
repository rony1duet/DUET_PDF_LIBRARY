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

  // Enhanced notification system
  function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.notification-toast');
    existing.forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <i class="bi bi-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
        <span>${message}</span>
      </div>
    `;

    // Style the notification
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === 'error' ? '#fee2e2' : '#d1fae5'};
      color: ${type === 'error' ? '#dc2626' : '#065f46'};
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      z-index: 10000;
      border-left: 4px solid ${type === 'error' ? '#dc2626' : '#10b981'};
      animation: slideInRight 0.3s ease;
    `;

    // Add animation
    if (!document.querySelector('#notificationAnimations')) {
      const style = document.createElement('style');
      style.id = 'notificationAnimations';
      style.textContent = `
        @keyframes slideInRight {
          from { opacity: 0; transform: translateX(100%); }
          to { opacity: 1; transform: translateX(0); }
        }
        .notification-content {
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }
      `;
      document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
      }
    }, 5000);
  }

  // Utility functions
  function validatePdfFile(input) {
    const file = input.files[0];
    if (!file) return true;

    const validTypes = ['application/pdf'];
    const maxSize = 50 * 1024 * 1024; // 50MB

    if (!validTypes.includes(file.type)) {
      showError(input, 'Please select a valid PDF file.');
      return false;
    }

    if (file.size > maxSize) {
      showError(input, 'File size must be less than 50MB.');
      return false;
    }

    clearError(input);
    return true;
  }

  function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
      if (!validateField(field)) {
        isValid = false;
      }
    });

    return isValid;
  }

  function validateField(field) {
    const value = field.value.trim();

    if (field.hasAttribute('required') && !value) {
      showError(field, 'This field is required.');
      return false;
    }

    if (field.type === 'email' && value && !isValidEmail(value)) {
      showError(field, 'Please enter a valid email address.');
      return false;
    }

    clearError(field);
    return true;
  }

  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function showError(field, message) {
    clearError(field);

    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentElement.appendChild(errorDiv);
  }

  function clearError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentElement.querySelector('.invalid-feedback');
    if (errorDiv) {
      errorDiv.remove();
    }
  }

  // Loading states for forms
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function () {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton && !submitButton.disabled) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...';
        submitButton.disabled = true;

        // Re-enable after 10 seconds to prevent permanent disable
        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 10000);
      }
    });
  });

  // Enhanced keyboard navigation
  document.addEventListener('keydown', function (e) {
    // ESC key to close modals
    if (e.key === 'Escape') {
      const activeModal = document.querySelector('.modal.show');
      if (activeModal) {
        const modal = bootstrap.Modal.getInstance(activeModal);
        if (modal) modal.hide();
      }
    }

    // Ctrl/Cmd + K for search focus
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      const searchInput = document.querySelector('input[name="search"]');
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }
  });

  // Performance optimization: Lazy loading for images
  const images = document.querySelectorAll('img[data-src]');
  if (images.length > 0) {
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          imageObserver.unobserve(img);
        }
      });
    });

    images.forEach(img => {
      imageObserver.observe(img);
    });
  }

  // Service worker registration for offline capabilities (if available)
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {
      // Service worker not available, silently fail
    });
  }
  console.log('DUET PDF Library initialized successfully');
});

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
