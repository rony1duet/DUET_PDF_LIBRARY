/**
 * DUET PDF Library - Enhanced JavaScript for Modern Design
 * Updated to support new Bootstrap 5.3.3 components and animations
 */

document.addEventListener("DOMContentLoaded", function () {
  // Initialize all components
  initializeFlashMessages();
  initializeNavigation();
  initializeSearch();
  initializeAnimations();
  initializeScrollEffects();
  initializeFormHandlers();
  initializeUploadProgress();
  validateFileUploads();
  initializeTooltips();
  initializeCategoryManagement();
  initializePagination();
  initializeFavorites();

  // Initialize new enhancements
  if (typeof initializeImageLoading === 'function') initializeImageLoading();
  if (typeof initializeMobileEnhancements === 'function') initializeMobileEnhancements();
  if (typeof initializeSearchEnhancements === 'function') initializeSearchEnhancements();

  // Auto-dismiss flash messages with enhanced animations
  function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll(".flash-message");
    if (flashMessages.length > 0) {
      setTimeout(function () {
        flashMessages.forEach(function (message) {
          message.style.opacity = "0";
          message.style.transform = "translateY(-20px)";
          setTimeout(function () {
            message.remove();
          }, 300);
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

  // Enhanced search functionality with modern UX
  function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const headerSearch = document.getElementById('headerSearch');
    const searchForm = document.querySelector('.search-form');
    const categorySelect = document.getElementById('categorySelect');
    const sortSelect = document.getElementById('sortSelect');

    // Live search with debouncing
    let searchTimeout;
    function handleSearch(input) {
      clearTimeout(searchTimeout);
      if (input && input.value.length >= 2) {
        searchTimeout = setTimeout(() => {
          performLiveSearch(input.value);
        }, 300);
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', () => handleSearch(searchInput));
      searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          const form = this.closest('form');
          if (form) form.submit();
        }
      });
    }

    if (headerSearch) {
      headerSearch.addEventListener('input', () => handleSearch(headerSearch));
      headerSearch.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          performHeaderSearch(this.value);
        }
      });

      // Search suggestions
      const suggestionsContainer = createSearchSuggestions();
      headerSearch.parentNode.appendChild(suggestionsContainer);

      headerSearch.addEventListener('focus', function () {
        this.parentElement.classList.add('search-focused');
      });

      headerSearch.addEventListener('blur', function () {
        setTimeout(() => {
          this.parentElement.classList.remove('search-focused');
          hideSuggestions();
        }, 200);
      });
    }

    // Auto-submit filters
    if (categorySelect) {
      categorySelect.addEventListener('change', function () {
        const form = this.closest('form');
        if (form) form.submit();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        const form = this.closest('form');
        if (form) form.submit();
      });
    }

    function createSearchSuggestions() {
      const suggestions = document.createElement('div');
      suggestions.className = 'search-suggestions position-absolute bg-white border border-top-0 rounded-bottom shadow-lg d-none';
      suggestions.style.cssText = `
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 300px;
        overflow-y: auto;
      `;
      return suggestions;
    }

    function performLiveSearch(query) {
      // Add loading indicator
      const searchContainer = document.querySelector('.search-container');
      if (searchContainer) {
        searchContainer.classList.add('search-loading');
      }
    }

    function performHeaderSearch(query) {
      if (query.trim()) {
        const mainSearch = document.getElementById('searchInput');
        if (mainSearch) {
          mainSearch.value = query;
          const form = mainSearch.closest('form');
          if (form) form.submit();
        } else {
          window.location.href = `index.php?search=${encodeURIComponent(query)}`;
        }
      }
    }

    function hideSuggestions() {
      const suggestions = document.querySelector('.search-suggestions');
      if (suggestions) {
        suggestions.classList.add('d-none');
      }
    }
  }

  // Modern animations and effects
  function initializeAnimations() {
    // Animated counters for statistics
    const counters = document.querySelectorAll('.counter');
    const observerOptions = {
      threshold: 0.5,
      rootMargin: '0px 0px -100px 0px'
    };

    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    counters.forEach(counter => {
      counterObserver.observe(counter);
    });

    // Card hover animations
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach(card => {
      card.addEventListener('mouseenter', function () {
        this.style.transform = 'translateY(-10px) scale(1.02)';
        this.style.boxShadow = '0 20px 40px rgba(0,159,81,0.15)';
      });

      card.addEventListener('mouseleave', function () {
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
      });
    });

    // Floating shapes animation
    const shapes = document.querySelectorAll('.floating-shape');
    shapes.forEach((shape, index) => {
      shape.style.animationDelay = `${index * 0.5}s`;
      shape.style.animationDuration = `${6 + index * 2}s`;
    });

    // Parallax effect for hero section
    const hero = document.querySelector('.hero-section');
    if (hero) {
      window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        hero.style.transform = `translateY(${rate}px)`;
      });
    }
  }

  function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target') || element.textContent);
    const duration = 2000;
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      element.textContent = Math.floor(current).toLocaleString();
    }, 16);
  }

  // Enhanced scroll effects
  function initializeScrollEffects() {
    // Fade in animation for elements
    const fadeElements = document.querySelectorAll('.fade-in');
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
      window.addEventListener('scroll', () => {
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
            if (type === 'password') {
              icon.classList.remove('bi-eye-slash');
              icon.classList.add('bi-eye');
            } else {
              icon.classList.remove('bi-eye');
              icon.classList.add('bi-eye-slash');
            }
          }
        }
      });
    });

    // File upload preview
    const coverImageInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview');

    if (coverImageInput && coverPreview) {
      coverImageInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function (e) {
            coverPreview.src = e.target.result;
            coverPreview.style.display = 'block';

            const previewContainer = document.getElementById('cover-preview-container');
            if (previewContainer) {
              previewContainer.classList.remove('d-none');
              previewContainer.style.opacity = '0';
              previewContainer.style.transform = 'scale(0.8)';
              setTimeout(() => {
                previewContainer.style.transition = 'all 0.3s ease-out';
                previewContainer.style.opacity = '1';
                previewContainer.style.transform = 'scale(1)';
              }, 50);
            }
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    }

    // PDF file validation with enhanced UX
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

      input.addEventListener('input', function () {
        clearValidationError(this);
      });
    });
  }

  // Enhanced upload progress and feedback
  function initializeUploadProgress() {
    const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (!uploadForm) return;

    // Create progress elements
    const progressHTML = `
      <div id="upload-progress" class="mt-3 d-none">
        <div class="alert alert-info">
          <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Uploading...</span>
            </div>
            <span id="upload-status">Uploading files to ImageKit...</span>
          </div>
          <div class="progress mt-2" style="height: 6px;">
            <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%"></div>
          </div>
          <small id="upload-details" class="text-muted"></small>
        </div>
      </div>
    `;

    // Insert progress HTML after the form
    uploadForm.insertAdjacentHTML('afterend', progressHTML);

    const progressContainer = document.getElementById('upload-progress');
    const progressBar = document.getElementById('upload-progress-bar');
    const statusText = document.getElementById('upload-status');
    const detailsText = document.getElementById('upload-details');

    uploadForm.addEventListener('submit', function (e) {
      const pdfFile = document.getElementById('pdf_file').files[0];
      const coverFile = document.getElementById('cover_image').files[0];

      if (pdfFile || coverFile) {
        // Show progress
        progressContainer.classList.remove('d-none');
        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
        }

        // Update progress details
        let details = [];
        if (pdfFile) details.push(`PDF: ${(pdfFile.size / 1024 / 1024).toFixed(1)}MB`);
        if (coverFile) details.push(`Cover: ${(coverFile.size / 1024).toFixed(0)}KB`);
        detailsText.textContent = details.join(' | ');

        // Simulate progress (since we can't track real upload progress with regular forms)
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += Math.random() * 15;
          if (progress > 90) progress = 90;
          progressBar.style.width = progress + '%';

          if (progress > 30 && progress < 60) {
            statusText.textContent = 'Processing files...';
          } else if (progress >= 60) {
            statusText.textContent = 'Finalizing upload...';
          }
        }, 500);

        // Clean up interval when page unloads
        window.addEventListener('beforeunload', () => clearInterval(progressInterval));
      }
    });
  }

  // File size and type validation with enhanced feedback
  function validateFileUploads() {
    const pdfInput = document.getElementById('pdf_file');
    const coverInput = document.getElementById('cover_image');

    if (pdfInput) {
      pdfInput.addEventListener('change', function () {
        validatePdfFile(this);
        updateUploadSummary();
      });
    }

    if (coverInput) {
      coverInput.addEventListener('change', function () {
        validateCoverImage(this);
        updateUploadSummary();
      });
    }
  }

  function validateCoverImage(input) {
    const errorElement = document.getElementById('cover-image-error');

    if (input.files && input.files[0]) {
      const file = input.files[0];
      const fileSize = file.size / 1024; // Convert to KB
      const fileType = file.type;

      // Clear previous error
      if (errorElement) {
        errorElement.style.display = 'none';
      }

      // Check file type
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(fileType)) {
        showError(errorElement, 'Only JPEG, PNG, and GIF images are allowed.');
        input.value = '';
        return false;
      }

      // Check file size (max 5MB)
      if (fileSize > 5 * 1024) {
        showError(errorElement, 'Cover image size must be less than 5MB.');
        input.value = '';
        return false;
      }

      // Show success feedback
      if (errorElement) {
        errorElement.className = 'alert alert-success mt-2';
        errorElement.style.display = 'block';
        errorElement.innerHTML = `<i class="bi bi-check-circle me-2"></i>Cover image ready: ${file.name} (${(fileSize / 1024).toFixed(1)}MB)`;
      }

      return true;
    }
    return false;
  }

  function updateUploadSummary() {
    const pdfFile = document.getElementById('pdf_file').files[0];
    const coverFile = document.getElementById('cover_image').files[0];

    let summaryHTML = '';
    if (pdfFile || coverFile) {
      summaryHTML = '<div class="alert alert-info mt-3"><h6>Upload Summary:</h6><ul class="mb-0">';

      if (pdfFile) {
        summaryHTML += `<li><strong>PDF:</strong> ${pdfFile.name} (${(pdfFile.size / 1024 / 1024).toFixed(1)}MB)</li>`;
      }

      if (coverFile) {
        summaryHTML += `<li><strong>Cover Image:</strong> ${coverFile.name} (${(coverFile.size / 1024).toFixed(0)}KB)</li>`;
      }

      summaryHTML += '</ul></div>';
    }

    // Update or create summary
    let summaryElement = document.getElementById('upload-summary');
    if (!summaryElement) {
      summaryElement = document.createElement('div');
      summaryElement.id = 'upload-summary';
      const form = document.querySelector('form[enctype="multipart/form-data"]');
      if (form) {
        form.appendChild(summaryElement);
      }
    }
    summaryElement.innerHTML = summaryHTML;
  }

  // Initialize Bootstrap tooltips and popovers
  function initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
      return new bootstrap.Popover(popoverTriggerEl);
    });
  }

  // Category management for forms
  function initializeCategoryManagement() {
    const categorySelectForm = document.getElementById('category_select');
    const selectedCategories = document.getElementById('selected_categories');
    const categoryInput = document.getElementById('categories');

    if (categorySelectForm && selectedCategories && categoryInput) {
      updateCategoryDisplay();

      categorySelectForm.addEventListener('change', function () {
        const categoryId = this.value;
        if (!categoryId) return;

        const categoryName = this.options[this.selectedIndex].text;
        addCategory(categoryId, categoryName);
        this.selectedIndex = 0;
      });

      function addCategory(id, name) {
        const currentCategories = categoryInput.value ? JSON.parse(categoryInput.value) : [];
        if (currentCategories.includes(id)) return;

        currentCategories.push(id);
        categoryInput.value = JSON.stringify(currentCategories);
        updateCategoryDisplay();
      }

      function removeCategory(id) {
        let currentCategories = categoryInput.value ? JSON.parse(categoryInput.value) : [];
        currentCategories = currentCategories.filter(catId => catId !== id);
        categoryInput.value = JSON.stringify(currentCategories);
        updateCategoryDisplay();
      }

      function updateCategoryDisplay() {
        const currentCategories = categoryInput.value ? JSON.parse(categoryInput.value) : [];
        selectedCategories.innerHTML = '';

        currentCategories.forEach(categoryId => {
          const option = categorySelectForm.querySelector(`option[value="${categoryId}"]`);
          if (option) {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary me-2 mb-2 d-inline-flex align-items-center';
            badge.innerHTML = `
              ${option.text}
              <button type="button" class="btn-close btn-close-white ms-2" 
                      onclick="removeCategory('${categoryId}')" aria-label="Remove"></button>
            `;
            selectedCategories.appendChild(badge);
          }
        });
      }

      // Make functions globally available
      window.removeCategory = removeCategory;
    }
  }

  // Enhanced pagination with smooth transitions
  function initializePagination() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        if (!this.closest('.page-item').classList.contains('disabled') &&
          !this.closest('.page-item').classList.contains('active')) {

          // Add loading state
          const booksGrid = document.querySelector('.books-grid');
          if (booksGrid) {
            booksGrid.style.opacity = '0.6';
            booksGrid.style.pointerEvents = 'none';
          }

          // Show loading spinner
          const loadingSpinner = document.createElement('div');
          loadingSpinner.className = 'text-center my-4';
          loadingSpinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

          if (booksGrid && booksGrid.parentNode) {
            booksGrid.parentNode.insertBefore(loadingSpinner, booksGrid.nextSibling);
          }
        }
      });
    });
  }

  // Favorite books functionality
  function initializeFavorites() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const bookId = this.getAttribute('data-book-id');
        const isFavorited = this.classList.contains('favorited');

        toggleFavorite(bookId, !isFavorited, this);
      });
    });
  }

  function toggleFavorite(bookId, isFavorite, button) {
    const action = isFavorite ? 'add' : 'remove';

    fetch(`ajax/toggle-favorite.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `book_id=${bookId}&action=${action}`
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (isFavorite) {
            button.classList.add('favorited');
            button.innerHTML = '<i class="bi bi-heart-fill"></i>';
            button.setAttribute('title', 'Remove from favorites');
          } else {
            button.classList.remove('favorited');
            button.innerHTML = '<i class="bi bi-heart"></i>';
            button.setAttribute('title', 'Add to favorites');
          }

          // Update tooltip
          const tooltip = bootstrap.Tooltip.getInstance(button);
          if (tooltip) {
            tooltip.dispose();
            new bootstrap.Tooltip(button);
          }
        }
      })
      .catch(error => {
        console.error('Error toggling favorite:', error);
      });
  }

  // Loading states for forms
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function () {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...';
        submitButton.disabled = true;

        // Re-enable after 5 seconds as fallback
        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 5000);
      }
    });
  });

  // Enhanced keyboard navigation
  document.addEventListener('keydown', function (e) {
    // Escape key handlers
    if (e.key === 'Escape') {
      // Close modals
      const modals = document.querySelectorAll('.modal.show');
      modals.forEach(modal => {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
          modalInstance.hide();
        }
      });

      // Close search suggestions
      const suggestions = document.querySelector('.search-suggestions');
      if (suggestions && !suggestions.classList.contains('d-none')) {
        suggestions.classList.add('d-none');
      }
    }

    // Ctrl+K for search focus
    if (e.ctrlKey && e.key === 'k') {
      e.preventDefault();
      const searchInput = document.getElementById('searchInput') || document.getElementById('headerSearch');
      if (searchInput) {
        searchInput.focus();
      }
    }
  });

  // Performance optimization: Lazy loading for images
  const images = document.querySelectorAll('img[data-src]');
  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.classList.remove('lazy');
        imageObserver.unobserve(img);
      }
    });
  });

  images.forEach(img => {
    imageObserver.observe(img);
  });

  // Service worker registration for offline capabilities (if available)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js')
        .then(function (registration) {
          console.log('SW registered: ', registration);
        })
        .catch(function (registrationError) {
          console.log('SW registration failed: ', registrationError);
        });
    });
  }

  console.log('DUET PDF Library initialized successfully');

  // Enhanced image loading with lazy loading and error handling
  function initializeImageLoading() {
    const bookImages = document.querySelectorAll('.book-image');

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
        card.addEventListener('touchstart', function () {
          this.classList.add('touch-active');
        });

        card.addEventListener('touchend', function () {
          setTimeout(() => {
            this.classList.remove('touch-active');
          }, 200);
        });
      });
    }

    // Handle orientation changes
    window.addEventListener('orientationchange', function () {
      setTimeout(() => {
        // Recalculate layout after orientation change
        window.dispatchEvent(new Event('resize'));
      }, 100);
    });
  }

  // Enhanced search functionality
  function initializeSearchEnhancements() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('.search-form');

    if (searchInput && searchForm) {
      let searchTimeout;

      // Add search suggestions (if implemented)
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length >= 2) {
          searchTimeout = setTimeout(() => {
            // Implement search suggestions here if needed
            console.log('Search query:', query);
          }, 300);
        }
      });

      // Add loading state to search button
      searchForm.addEventListener('submit', function () {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
          const originalText = submitBtn.innerHTML;
          submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i><span class="d-none d-sm-inline">Searching...</span>';
          submitBtn.disabled = true;

          // Reset after 3 seconds if form doesn't submit
          setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
          }, 3000);
        }
      });

      // Clear search functionality
      if (searchInput.value) {
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-link position-absolute end-0 top-50 translate-middle-y me-5';
        clearBtn.style.zIndex = '10';
        clearBtn.innerHTML = '<i class="bi bi-x-circle text-muted"></i>';

        clearBtn.addEventListener('click', function () {
          searchInput.value = '';
          searchInput.focus();
          this.remove();
        });

        searchInput.parentElement.style.position = 'relative';
        searchInput.parentElement.appendChild(clearBtn);
      }
    }
  }

  // Enhanced image loading with better error handling for book covers
  function initializeBookImageLoading() {
    const bookImages = document.querySelectorAll('.book-image');

    bookImages.forEach(img => {
      // Add loading state
      img.addEventListener('loadstart', function () {
        this.style.opacity = '0.5';
        this.classList.add('loading');
      });

      // Handle successful image load
      img.addEventListener('load', function () {
        this.style.opacity = '1';
        this.classList.remove('loading');

        // Add fade-in animation
        this.style.animation = 'fadeIn 0.3s ease-in-out';
      });

      // Handle image load error with better fallback
      img.addEventListener('error', function () {
        console.log('Failed to load cover image:', this.src);

        // Create enhanced placeholder
        const placeholder = document.createElement('div');
        placeholder.className = 'book-placeholder d-flex align-items-center justify-content-center h-100';
        placeholder.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-journal-bookmark display-4 text-primary opacity-50 mb-2"></i>
                    <div class="small text-muted">No Cover Available</div>
                </div>
            `;

        // Add hover effect to placeholder
        placeholder.addEventListener('mouseenter', function () {
          this.style.background = 'linear-gradient(135deg, #e2e8f0 0%, #d1d5db 100%)';
        });

        placeholder.addEventListener('mouseleave', function () {
          this.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
        });

        this.parentElement.replaceChild(placeholder, this);
      });

      // If image has no src or empty src, trigger error handler
      if (!this.src || this.src === '' || this.src === '#') {
        this.dispatchEvent(new Event('error'));
      }
    });
  }

  // Enhanced search functionality with loading states
  function initializeSearchLoadingStates() {
    const searchForms = document.querySelectorAll('.search-form, .category-filter');

    searchForms.forEach(form => {
      form.addEventListener('submit', function () {
        // Add loading state to search button
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
          const originalContent = submitBtn.innerHTML;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Searching...';
          submitBtn.disabled = true;

          // Re-enable after timeout as fallback
          setTimeout(() => {
            submitBtn.innerHTML = originalContent;
            submitBtn.disabled = false;
          }, 5000);
        }

        // Add loading overlay to book grid
        const booksGrid = document.querySelector('.books-grid');
        if (booksGrid) {
          booksGrid.style.opacity = '0.6';
          booksGrid.style.pointerEvents = 'none';
        }
      });
    });
  }

  // Initialize on DOM load
  document.addEventListener('DOMContentLoaded', function () {
    initializeBookImageLoading();
    initializeSearchLoadingStates();
  });

  // Debug helper for book covers
  function debugBookCovers() {
    const bookImages = document.querySelectorAll('.book-image');
    console.log('Found', bookImages.length, 'book images');

    bookImages.forEach((img, index) => {
      console.log(`Book ${index + 1}:`, {
        src: img.src,
        alt: img.alt,
        naturalWidth: img.naturalWidth,
        naturalHeight: img.naturalHeight,
        complete: img.complete
      });

      if (!img.complete || img.naturalWidth === 0) {
        console.warn(`Book ${index + 1} image failed to load:`, img.src);
      }
    });
  }

  // Call debug function after page load (only in development)
  if (window.location.hostname === 'localhost') {
    window.addEventListener('load', () => {
      setTimeout(debugBookCovers, 1000);
    });
  }
});
