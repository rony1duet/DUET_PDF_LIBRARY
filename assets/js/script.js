/**
 * DUET PDF Library - Optimized JavaScript
 * Modular, efficient, and maintainable code
 */

// Constants and configuration
const CONFIG = {
  ANIMATION_DURATION: 300,
  SEARCH_DELAY: 500,
  TOAST_DURATION: 5000,
  FLASH_MESSAGE_DELAY: 5000,
  COUNTER_ANIMATION_DURATION: 2000,
  STATS_REFRESH_INTERVAL: 300000,
  TIMESTAMP_UPDATE_INTERVAL: 60000,
  NOTIFICATION_CHECK_INTERVAL: 300000
};

// Cache DOM elements and selectors
const SELECTORS = {
  adminDashboard: '.admin-dashboard-card',
  profilePage: '.profile-page',
  bookDetailPage: '.book-detail-page',
  categoryCard: '.category-card',
  flashMessages: '.alert',
  navbar: '.navbar',
  searchInputs: 'input[name="search"]',
  counters: '.counter',
  bookImages: '.book-image, img[data-src]',
  tooltips: '[data-bs-toggle="tooltip"]',
  popovers: '[data-bs-toggle="popover"]'
};

// Utility functions
const Utils = {
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  throttle(func, limit) {
    let inThrottle;
    return function () {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  },

  isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  },
  sanitizeInput(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
};

// Enhanced button interactions
function initializeButtonEnhancements() {
  // Add ripple effect to outline buttons
  const outlineButtons = document.querySelectorAll('[class*="btn-outline"]');

  outlineButtons.forEach(button => {
    button.addEventListener('click', function (e) {
      // Create ripple effect
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        background-color: currentColor;
        opacity: 0.3;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
      `;

      // Ensure button has relative positioning
      const originalPosition = getComputedStyle(this).position;
      if (originalPosition === 'static') {
        this.style.position = 'relative';
      }

      this.appendChild(ripple);

      // Remove ripple after animation
      setTimeout(() => {
        ripple.remove();
      }, 600);
    });

    // Add loading state functionality
    if (button.closest('form')) {
      const form = button.closest('form');
      form.addEventListener('submit', function () {
        if (button.type === 'submit') {
          button.disabled = true;
          const originalText = button.innerHTML;
          button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';

          // Re-enable after 3 seconds (fallback)
          setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
          }, 3000);
        }
      });
    }
  });
}

// Main initialization
document.addEventListener("DOMContentLoaded", function () {
  // Core initialization with performance optimization
  const initPromises = [
    initializeFlashMessages(),
    initializeNavigation(),
    initializeSearch(),
    initializeScrollEffects(),
    initializeFormHandlers(),
    initializeTooltips()
  ];
  // Initialize page-specific components
  requestAnimationFrame(() => {
    initializeAnimations();
    initializePagination();
    initializeFavorites();
    initializeImageLoading();
    initializeMobileEnhancements();
    initializeUploadProgress();
    initializeCategoryPage();
    initializeButtonEnhancements(); // Add button enhancements
  });

  // Conditional initialization for specific pages
  if (document.querySelector(SELECTORS.adminDashboard)) {
    initializeAdminDashboard();
  }

  if (document.querySelector(SELECTORS.profilePage)) {
    initializeProfilePage();
  }

  if (document.querySelector(SELECTORS.bookDetailPage)) {
    initializeBookDetailPage();
  }

  // Wait for all core initializations
  Promise.all(initPromises).then(() => {
    console.log('DUET PDF Library initialized successfully');
  }).catch(err => {
    console.error('Initialization error:', err);
  });
  // Auto-dismiss flash messages with enhanced animations
  function initializeFlashMessages() {
    return new Promise((resolve) => {
      const flashMessages = document.querySelectorAll(SELECTORS.flashMessages);
      if (flashMessages.length === 0) {
        resolve();
        return;
      }

      setTimeout(() => {
        flashMessages.forEach((message) => {
          if (message.classList.contains('alert-dismissible')) {
            message.style.transition = `opacity ${CONFIG.ANIMATION_DURATION}ms ease, transform ${CONFIG.ANIMATION_DURATION}ms ease`;
            message.style.opacity = "0";
            message.style.transform = "translateY(-20px)";
            setTimeout(() => message.remove(), CONFIG.ANIMATION_DURATION);
          }
        });
        resolve();
      }, CONFIG.FLASH_MESSAGE_DELAY);
    });
  }

  // Optimized navigation with efficient event handling
  function initializeNavigation() {
    return new Promise((resolve) => {
      const navbar = document.querySelector(SELECTORS.navbar);
      const navbarToggler = navbar?.querySelector('.navbar-toggler');
      const navbarCollapse = navbar?.querySelector('.navbar-collapse');

      if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', () => {
          navbarCollapse.classList.add('collapsing');
          setTimeout(() => navbarCollapse.classList.remove('collapsing'), 350);
        });
      }

      if (navbar) {
        let lastScrollTop = 0;
        const handleScroll = Utils.throttle(() => {
          const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

          navbar.classList.toggle('navbar-scrolled', scrollTop > 100);

          if (scrollTop > lastScrollTop && scrollTop > 200) {
            navbar.style.transform = 'translateY(-100%)';
          } else {
            navbar.style.transform = 'translateY(0)';
          }
          lastScrollTop = scrollTop;
        }, 16); // ~60fps

        window.addEventListener('scroll', handleScroll, { passive: true });
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

      resolve();
    });
  }
  // Enhanced search functionality with debouncing
  function initializeSearch() {
    return new Promise((resolve) => {
      const searchInputs = document.querySelectorAll(SELECTORS.searchInputs);
      const searchForms = document.querySelectorAll('.search-form, form[action*="index.php"]');

      if (searchInputs.length === 0) {
        resolve();
        return;
      }

      const debouncedSearch = Utils.debounce((input) => {
        const value = input.value.trim();
        if (value.length >= 2) {
          const form = input.closest('form');
          if (form && form.method.toLowerCase() === 'get') {
            // Placeholder for live search implementation
            console.log('Search:', value);
          }
        }
      }, CONFIG.SEARCH_DELAY);

      searchInputs.forEach(input => {
        input.addEventListener('input', () => debouncedSearch(input));

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

      resolve();
    });

    function addClearButton(input) {
      if (input.parentElement.querySelector('.search-clear-btn')) return;

      const clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.className = 'btn btn-link search-clear-btn p-0 position-absolute end-0 top-50 translate-middle-y me-2';
      clearBtn.innerHTML = '<i class="bi bi-x-circle text-muted"></i>';
      clearBtn.style.zIndex = '10';

      clearBtn.addEventListener('click', () => {
        input.value = '';
        input.focus();
        removeClearButton(input);
      });

      input.parentElement.style.position = 'relative';
      input.parentElement.appendChild(clearBtn);
    }

    function removeClearButton(input) {
      const clearBtn = input.parentElement.querySelector('.search-clear-btn');
      clearBtn?.remove();
    }
  }
  // Optimized animations and effects
  function initializeAnimations() {
    // Use single observer for all counters
    initializeCounters();
    initializeFloatingShapes();
    initializeParallaxEffect();
  }

  function initializeCounters() {
    const counters = document.querySelectorAll(SELECTORS.counters);
    if (counters.length === 0) return;

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

    counters.forEach(counter => counterObserver.observe(counter));
  }

  function initializeFloatingShapes() {
    const shapes = document.querySelectorAll('.shape, .floating-shape');
    shapes.forEach((shape, index) => {
      shape.style.animationDelay = `${index * 0.5}s`;
      shape.style.animationDuration = `${6 + index * 2}s`;
    });
  }

  function initializeParallaxEffect() {
    const hero = document.querySelector('.hero-section');
    if (!hero || window.innerWidth <= 768) return;

    const handleParallax = Utils.throttle(() => {
      const scrolled = window.pageYOffset;
      const rate = scrolled * -0.3;
      hero.style.transform = `translateY(${rate}px)`;
    }, 16);

    window.addEventListener('scroll', handleParallax, { passive: true });
  }

  // Optimized counter animation
  function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target') || element.textContent);
    if (isNaN(target) || target === 0) return;

    const startTime = performance.now();
    const startValue = 0;

    function updateCounter(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / CONFIG.COUNTER_ANIMATION_DURATION, 1);

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
  // Optimized scroll effects with single observer
  function initializeScrollEffects() {
    return new Promise((resolve) => {
      initializeFadeEffects();
      initializeSmoothScrolling();
      initializeNavbarScrollEffect();
      resolve();
    });
  }

  function initializeFadeEffects() {
    const fadeElements = document.querySelectorAll('.fade-in');
    if (fadeElements.length === 0) return;

    const fadeObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const element = entry.target;
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
          fadeObserver.unobserve(element);
        }
      });
    }, { threshold: 0.1 });

    fadeElements.forEach(element => {
      element.style.cssText = 'opacity: 0; transform: translateY(30px); transition: opacity 0.6s ease-out, transform 0.6s ease-out;';
      fadeObserver.observe(element);
    });
  }
  function initializeSmoothScrolling() {
    const anchors = document.querySelectorAll('a[href^="#"]');
    anchors.forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');

        // Skip if href is just '#' or empty
        if (!href || href === '#' || href.length <= 1) {
          return;
        }

        e.preventDefault();

        try {
          const target = document.querySelector(href);
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        } catch (error) {
          console.warn('Invalid selector for smooth scrolling:', href);
        }
      });
    });
  }

  function initializeNavbarScrollEffect() {
    const navbar = document.querySelector(SELECTORS.navbar);
    if (!navbar) return;

    const handleScroll = Utils.throttle(() => {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    }, 16);

    window.addEventListener('scroll', handleScroll, { passive: true });
  }
  // Enhanced form handlers with validation
  function initializeFormHandlers() {
    return new Promise((resolve) => {
      initializePasswordToggles();
      initializeFileUploads();
      initializeFormValidation();
      resolve();
    });
  }

  function initializePasswordToggles() {
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
  }

  function initializeFileUploads() {
    // Cover image preview
    const coverImageInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview');

    if (coverImageInput && coverPreview) {
      coverImageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = e => {
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
  }

  function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
      form.addEventListener('submit', function (e) {
        if (!validateForm(this)) {
          e.preventDefault();
        }
      });
    });

    const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
      input.addEventListener('blur', () => validateField(input));
    });
  }

  function validatePdfFile(input) {
    const file = input.files[0];
    if (!file) return true;

    if (!file.type.includes('pdf')) {
      showToast('Please select a valid PDF file', 'danger');
      input.value = '';
      return false;
    }

    const maxSize = 50 * 1024 * 1024; // 50MB
    if (file.size > maxSize) {
      showToast('File size must be less than 50MB', 'danger');
      input.value = '';
      return false;
    }

    return true;
  }

  function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
      if (!validateField(input)) {
        isValid = false;
      }
    });

    return isValid;
  }

  function validateField(field) {
    const value = field.value.trim();
    const isValid = value.length > 0;

    field.classList.toggle('is-invalid', !isValid);
    field.classList.toggle('is-valid', isValid);

    return isValid;
  }
  // Initialize Bootstrap components efficiently
  function initializeTooltips() {
    return new Promise((resolve) => {
      // Initialize tooltips
      const tooltipElements = document.querySelectorAll(SELECTORS.tooltips);
      if (tooltipElements.length > 0 && typeof bootstrap !== 'undefined') {
        tooltipElements.forEach(element => {
          try {
            new bootstrap.Tooltip(element);
          } catch (error) {
            console.warn('Failed to initialize tooltip:', error);
          }
        });
      }

      // Initialize popovers
      const popoverElements = document.querySelectorAll(SELECTORS.popovers);
      if (popoverElements.length > 0 && typeof bootstrap !== 'undefined') {
        popoverElements.forEach(element => {
          try {
            new bootstrap.Popover(element);
          } catch (error) {
            console.warn('Failed to initialize popover:', error);
          }
        });
      }

      resolve();
    });
  }

  // Optimized pagination
  function initializePagination() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        if (!this.parentElement.classList.contains('disabled')) {
          this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }
      });
    });
  }

  // Favorites functionality with error handling
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

  async function toggleFavorite(bookId, isFavorite, button) {
    if (!bookId) {
      console.error('Book ID is required for favorite toggle');
      return;
    }

    button.disabled = true;
    const originalContent = button.innerHTML;

    try {
      const response = await fetch('ajax/toggle-favorite.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_id: bookId })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        const newIsFavorite = !isFavorite;
        button.setAttribute('data-is-favorite', newIsFavorite.toString());

        const icon = button.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-heart');
          icon.classList.toggle('bi-heart-fill');
        }

        const text = button.querySelector('.btn-text');
        if (text) {
          text.textContent = newIsFavorite ? 'Remove from Favorites' : 'Add to Favorites';
        }

        showToast(data.message || 'Favorite updated successfully', 'success');
      } else {
        throw new Error(data.message || 'Failed to update favorite');
      }
    } catch (error) {
      console.error('Error toggling favorite:', error);
      showToast('Failed to update favorite. Please try again.', 'danger');
      button.innerHTML = originalContent;
    } finally {
      button.disabled = false;
    }
  }
  // Optimized image loading with lazy loading
  function initializeImageLoading() {
    const bookImages = document.querySelectorAll(SELECTORS.bookImages);
    if (bookImages.length === 0) return;

    // Create intersection observer for lazy loading
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          loadImage(img);
          imageObserver.unobserve(img);
        }
      });
    }, {
      rootMargin: '50px'
    });

    bookImages.forEach(img => {
      img.setAttribute('data-loading', 'true');

      // Handle image load events
      img.addEventListener('load', handleImageLoad, { once: true });
      img.addEventListener('error', handleImageError, { once: true });

      // Observe for lazy loading if data-src is present
      if (img.dataset.src && 'IntersectionObserver' in window) {
        imageObserver.observe(img);
      } else if (img.src) {
        // Image already has src, just handle loading state
        loadImage(img);
      }
    });
  }

  function loadImage(img) {
    if (img.dataset.src && !img.src) {
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
    }
  }

  function handleImageLoad() {
    this.removeAttribute('data-loading');
    this.style.cssText = 'opacity: 0; transition: opacity 0.3s ease;';

    requestAnimationFrame(() => {
      this.style.opacity = '1';
    });
  }

  function handleImageError() {
    this.removeAttribute('data-loading');
    const placeholder = createImagePlaceholder();
    placeholder.style.cssText = this.style.cssText;
    this.parentElement?.replaceChild(placeholder, this);
  }

  function createImagePlaceholder() {
    const placeholder = document.createElement('div');
    placeholder.className = 'book-placeholder d-flex align-items-center justify-content-center';
    placeholder.innerHTML = '<i class="bi bi-journal-bookmark display-4 text-primary opacity-50"></i>';
    return placeholder;
  }
  // Mobile-specific optimizations
  function initializeMobileEnhancements() {
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (isTouchDevice) {
      document.body.classList.add('touch-device');
      initializeTouchInteractions();
    }

    // Handle orientation changes efficiently
    let orientationTimeout;
    window.addEventListener('orientationchange', () => {
      clearTimeout(orientationTimeout);
      orientationTimeout = setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
      }, 100);
    });

    // Optimize viewport for mobile
    optimizeViewport();
  }

  function initializeTouchInteractions() {
    const bookCards = document.querySelectorAll('.book-card');

    bookCards.forEach(card => {
      let touchStartTime;
      let touchStartY;

      card.addEventListener('touchstart', function (e) {
        touchStartTime = Date.now();
        touchStartY = e.touches[0].clientY;
        this.classList.add('touch-active');

        if (navigator.vibrate) {
          navigator.vibrate(10);
        }
      }, { passive: true });

      card.addEventListener('touchend', function (e) {
        const touchDuration = Date.now() - touchStartTime;
        const touchEndY = e.changedTouches[0].clientY;
        const touchDistance = Math.abs(touchEndY - touchStartY);

        setTimeout(() => this.classList.remove('touch-active'), 150);

        // Handle quick taps with minimal movement
        if (touchDuration < 300 && touchDistance < 10) {
          const link = this.querySelector('.book-cover-link, .stretched-link');
          if (link && !e.target.closest('.badge, .btn')) {
            e.preventDefault();
            e.stopPropagation();
            setTimeout(() => window.location.href = link.href, 100);
          }
        }
      });

      card.addEventListener('touchcancel', function () {
        this.classList.remove('touch-active');
      });
    });
  }

  function optimizeViewport() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport && window.innerWidth <= 768) {
      viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
  }
  // Optimized upload progress
  function initializeUploadProgress() {
    const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (!uploadForm) return;

    const progressContainer = createProgressUI(uploadForm);

    uploadForm.addEventListener('submit', function (e) {
      const files = [
        document.getElementById('pdf_file')?.files[0],
        document.getElementById('cover_image')?.files[0]
      ].filter(Boolean);

      if (files.length > 0) {
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
    const statusMessages = [
      'Uploading files...',
      'Processing files...',
      'Finalizing upload...',
      'Upload complete!'
    ];

    const interval = setInterval(() => {
      progress += Math.random() * 10;
      const messageIndex = Math.floor(progress / 25);

      if (messageIndex < statusMessages.length) {
        statusText.textContent = statusMessages[messageIndex];
      }

      if (progress >= 100) {
        progress = 100;
        clearInterval(interval);
        setTimeout(() => {
          progressBar.classList.remove('progress-bar-animated');
          progressBar.classList.add('bg-success');
        }, 500);
      }

      progressBar.style.width = `${Math.min(progress, 100)}%`;
      percentageText.textContent = `${Math.min(Math.floor(progress), 100)}%`;
    }, 200 + Math.random() * 300);
  }
  // Optimized category page functionality
  function initializeCategoryPage() {
    if (!document.querySelector(SELECTORS.categoryCard)) return;

    initializeCategoryCards();
    initializeCategoryDeleteButtons();
  }

  function initializeCategoryCards() {
    const categoryCards = document.querySelectorAll(SELECTORS.categoryCard);

    categoryCards.forEach(card => {
      // Optimize card interactions
      card.addEventListener('click', function (e) {
        if (e.target.closest('.category-actions, button, a.btn-action')) {
          return;
        }

        const categoryLink = card.querySelector('.category-link');
        if (categoryLink) {
          window.location.href = categoryLink.href;
        }
      });

      // Keyboard navigation
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const categoryLink = card.querySelector('.category-link');
          if (categoryLink) {
            window.location.href = categoryLink.href;
          }
        }
      });

      // Accessibility improvements
      card.setAttribute('tabindex', '0');
      card.setAttribute('role', 'button');

      const categoryLink = card.querySelector('.category-link');
      if (categoryLink) {
        const categoryName = categoryLink.textContent.trim();
        card.setAttribute('aria-label', `View ${categoryName} category`);
      }
    });
  }

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
  // Book detail page initialization
  function initializeBookDetailPage() {
    if (!document.querySelector(SELECTORS.bookDetailPage)) return;

    initializePDFViewer();
    initializeBookFavorites();
    initializeFullscreenViewer();
    initializeDownloadTracking();
  }

  function initializePDFViewer() {
    const iframe = document.querySelector('.pdf-iframe');
    const loadingIndicator = document.querySelector('.viewer-loading');

    if (iframe && loadingIndicator) {
      loadingIndicator.style.display = 'flex';

      iframe.addEventListener('load', function () {
        loadingIndicator.style.display = 'none';
        iframe.classList.add('loaded');
      }, { once: true });

      iframe.addEventListener('error', function () {
        loadingIndicator.innerHTML = `
          <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <span>Failed to load PDF. Please try again.</span>
        `;
      }, { once: true });
    }
  }

  function initializeBookFavorites() {
    const favoriteForm = document.querySelector('.favorite-form');
    const favoriteBtn = document.querySelector('.action-btn-favorite');

    if (favoriteForm && favoriteBtn) {
      favoriteForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        favoriteBtn.disabled = true;
        const originalContent = favoriteBtn.innerHTML;
        favoriteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Processing...</span>';

        try {
          const formData = new FormData(favoriteForm);
          const response = await fetch(favoriteForm.action, {
            method: 'POST',
            body: formData
          });

          if (response.ok) {
            window.location.reload();
          } else {
            throw new Error('Network response was not ok');
          }
        } catch (error) {
          console.error('Error:', error);
          favoriteBtn.disabled = false;
          favoriteBtn.innerHTML = originalContent;
          showToast('Error updating favorite status', 'danger');
        }
      });
    }
  }

  function initializeFullscreenViewer() {
    const fullscreenBtn = document.querySelector('.viewer-btn-fullscreen');
    const pdfContainer = document.querySelector('.pdf-viewer-container');

    if (fullscreenBtn && pdfContainer) {
      fullscreenBtn.addEventListener('click', toggleFullscreen);
      document.addEventListener('fullscreenchange', updateFullscreenButton);
    }
  }

  function initializeDownloadTracking() {
    const downloadBtns = document.querySelectorAll('[data-action="download"]');

    downloadBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.add('animate-bounce');
          setTimeout(() => icon.classList.remove('animate-bounce'), 1000);
        }
        console.log('Download initiated for book:', btn.href);
      });
    });
  }
  // Optimized admin dashboard functionality
  function initializeAdminDashboard() {
    initializeAdminCounters();
    initializeAdminCharts();
    initializeAdminUpdates();
    initializeAdminNotifications();
    initializeAdminInteractions();
  }

  function initializeAdminCounters() {
    const counters = document.querySelectorAll('.admin-dashboard-card h3');
    if (counters.length === 0) return;

    const observerOptions = {
      threshold: 0.3,
      rootMargin: '0px 0px -50px 0px'
    };

    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.dataset.animated) {
          entry.target.dataset.animated = 'true';
          animateAdminCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    counters.forEach(counter => {
      if (counter.textContent.match(/\d/)) {
        counterObserver.observe(counter);
      }
    });
  }

  function animateAdminCounter(element) {
    const text = element.textContent;
    const numMatch = text.match(/[\d,]+/);
    if (!numMatch) return;

    const targetValue = parseInt(numMatch[0].replace(/,/g, ''));
    if (isNaN(targetValue)) return;

    const startTime = performance.now();
    const startValue = 0;

    function updateCounter(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / CONFIG.COUNTER_ANIMATION_DURATION, 1);

      const easeOut = 1 - Math.pow(1 - progress, 3);
      const currentValue = Math.floor(startValue + (targetValue * easeOut));

      element.textContent = text.replace(/[\d,]+/, currentValue.toLocaleString());

      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      }
    }

    requestAnimationFrame(updateCounter);
  }

  function initializeAdminCharts() {
    const chartBars = document.querySelectorAll('.trend-chart .bg-success, .trend-chart .bg-info');

    chartBars.forEach((bar, index) => {
      setTimeout(() => {
        bar.style.cssText = 'opacity: 0; transform: scaleY(0); transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);';
        setTimeout(() => {
          bar.style.opacity = '1';
          bar.style.transform = 'scaleY(1)';
        }, 100);
      }, index * 100);
    });
  }

  function initializeAdminUpdates() {
    // Update timestamps periodically
    const timestampInterval = setInterval(updateRelativeTimestamps, CONFIG.TIMESTAMP_UPDATE_INTERVAL);

    // Check for notifications
    const notificationInterval = setInterval(checkPendingNotifications, CONFIG.NOTIFICATION_CHECK_INTERVAL);

    // Clean up intervals when leaving the page
    window.addEventListener('beforeunload', () => {
      clearInterval(timestampInterval);
      clearInterval(notificationInterval);
    });
  }

  function updateRelativeTimestamps() {
    const timestamps = document.querySelectorAll('[data-timestamp]');
    timestamps.forEach(element => {
      const timestamp = element.getAttribute('data-timestamp');
      const relativeTime = getRelativeTimeString(new Date(timestamp));
      element.textContent = relativeTime;
    });
  }

  function getRelativeTimeString(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  }

  function checkPendingNotifications() {
    const pendingBadge = document.querySelector('.notification-badge');
    if (pendingBadge) {
      pendingBadge.classList.add('animate-pulse');
      setTimeout(() => pendingBadge.classList.remove('animate-pulse'), 2000);
    }
  }

  function initializeAdminNotifications() {
    if (sessionStorage.getItem('admin-welcome') !== 'shown') {
      setTimeout(() => {
        showToast('Welcome to the Admin Dashboard! 👋', 'info');
        sessionStorage.setItem('admin-welcome', 'shown');
      }, 1000);
    }

    checkSystemHealth();
  }

  function checkSystemHealth() {
    const pendingCount = parseInt(document.querySelector('[data-pending-count]')?.getAttribute('data-pending-count') || '0');

    if (pendingCount > 20) {
      showToast('High workload detected. Consider reviewing pending requests.', 'warning');
    }

    const healthIndicators = document.querySelectorAll('.system-health-indicator');
    healthIndicators.forEach((indicator, index) => {
      setTimeout(() => {
        indicator.style.animation = 'pulse 2s infinite';
      }, index * 200);
    });
  }

  function initializeAdminInteractions() {
    // Quick action buttons
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    quickActionBtns.forEach(btn => {
      btn.addEventListener('mouseenter', function () {
        this.style.transform = 'translateY(-3px) scale(1.02)';
      });

      btn.addEventListener('mouseleave', function () {
        this.style.transform = 'translateY(0) scale(1)';
      });
    });

    // Dashboard cards
    const dashboardCards = document.querySelectorAll('.admin-dashboard-card');
    dashboardCards.forEach(card => {
      card.addEventListener('mouseenter', function () {
        this.style.transform = 'translateY(-4px)';
        this.style.boxShadow = '0 8px 32px rgba(0,0,0,0.12)';
      });

      card.addEventListener('mouseleave', function () {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '';
      });
    });

    // Table rows
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    tableRows.forEach(row => {
      row.addEventListener('click', function () {
        const link = this.querySelector('a');
        if (link) {
          window.location.href = link.href;
        }
      });

      row.style.cursor = 'pointer';
    });

    // Keyboard shortcuts
    initializeAdminKeyboardShortcuts();
  }

  function initializeAdminKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
      if (!document.querySelector(SELECTORS.adminDashboard)) return;

      if (e.ctrlKey || e.metaKey) {
        const shortcuts = {
          'b': 'add-book.php',
          'c': 'add-category.php',
          'r': 'requests.php',
          'u': 'users.php'
        };

        if (shortcuts[e.key]) {
          e.preventDefault();
          window.location.href = shortcuts[e.key];
        }
      }
    });
  }
  // Optimized profile page functionality
  function initializeProfilePage() {
    if (!document.querySelector(SELECTORS.profilePage)) return;

    initializeProfileTabs();
  }

  function initializeProfileTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabButtons.length === 0) return;

    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        const targetTab = button.getAttribute('data-tab');

        // Remove active classes
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Add active classes
        button.classList.add('active');
        const targetContent = document.getElementById(targetTab + '-tab');
        if (targetContent) {
          targetContent.classList.add('active');
        }
      });
    });
  }
});

// Global utility functions and event handlers
window.toggleFullscreen = function () {
  const pdfContainer = document.querySelector('.pdf-viewer-container');
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
  const message = `Are you sure you want to delete "${Utils.sanitizeInput(bookTitle)}"?\n\nThis action cannot be undone and will permanently remove the book from the library.`;
  return confirm(message);
};

// Enhanced toast notification system
function showToast(message, type = 'info') {
  // Remove existing toasts to prevent spam
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
      <span>${Utils.sanitizeInput(message)}</span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  document.body.appendChild(toast);

  // Auto-remove toast
  const timeoutId = setTimeout(() => {
    if (toast.parentNode) {
      toast.style.animation = 'slideOut 0.3s ease-in forwards';
      setTimeout(() => toast.remove(), 300);
    }
  }, CONFIG.TOAST_DURATION);

  // Clear timeout if manually dismissed
  toast.addEventListener('closed.bs.alert', () => {
    clearTimeout(timeoutId);
  });
}

// Global category delete handler
function handleCategoryDelete(button) {
  const categoryName = button.getAttribute('data-category-name');
  const categoryId = button.getAttribute('data-category-id');

  if (!categoryName || !categoryId) {
    showToast('Error: Category information not found', 'danger');
    return;
  }

  const confirmMessage = `Are you sure you want to delete the category "${Utils.sanitizeInput(categoryName)}"?\n\nThis action cannot be undone. Books in this category will remain but become uncategorized.`;

  if (confirm(confirmMessage)) {
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';

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

// Profile page favorite removal
async function removeFromFavorites(bookId) {
  if (!bookId) {
    showToast('Invalid book ID', 'danger');
    return;
  }

  const favoriteBtn = document.querySelector(`[data-book-id="${bookId}"] .favorite-btn`);
  if (!favoriteBtn) return;

  const originalContent = favoriteBtn.innerHTML;
  favoriteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
  favoriteBtn.disabled = true;

  try {
    const response = await fetch('profile.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=remove_favorite&book_id=${encodeURIComponent(bookId)}`
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      const bookCard = document.querySelector(`[data-book-id="${bookId}"]`);
      if (bookCard) {
        bookCard.style.cssText = 'opacity: 0; transform: scale(0.8); transition: all 0.3s ease;';

        setTimeout(() => {
          bookCard.remove();
          checkEmptyFavorites();
          updateFavoriteStats();
        }, 300);
      }

      showToast('Book removed from favorites', 'success');
    } else {
      throw new Error(result.message || 'Failed to remove favorite');
    }
  } catch (error) {
    console.error('Error removing favorite:', error);
    favoriteBtn.innerHTML = originalContent;
    favoriteBtn.disabled = false;
    showToast('Failed to remove favorite. Please try again.', 'danger');
  }
}

function checkEmptyFavorites() {
  const remainingCards = document.querySelectorAll('.books-grid .book-card');
  if (remainingCards.length === 0) {
    const favoritesTab = document.getElementById('favorites-tab');
    if (favoritesTab) {
      favoritesTab.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="bi bi-heart"></i>
          </div>
          <h3>No favorite books yet</h3>
          <p>Browse the library and click the heart icon to add books to your favorites</p>
          <a href="index.php" class="btn btn-primary">
            <i class="bi bi-book"></i> Browse Books
          </a>
        </div>
      `;
    }
  }
}

function updateFavoriteStats() {
  const statNumber = document.querySelector('.stat-card .stat-number');
  if (statNumber) {
    const currentCount = parseInt(statNumber.textContent) || 0;
    statNumber.textContent = Math.max(0, currentCount - 1);
  }
}

// Global utility object for external use
window.duetLibrary = {
  showToast,
  confirmDelete: (message = 'Are you sure you want to delete this item?') => confirm(message),
  utils: Utils
};

// Add required CSS animations
if (!document.querySelector('#duet-animations')) {
  const style = document.createElement('style');
  style.id = 'duet-animations';
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-10px); }
      60% { transform: translateY(-5px); }
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .animate-bounce { animation: bounce 1s ease-in-out; }
    .animate-pulse { animation: pulse 2s infinite; }
    
    .touch-device .book-card.touch-active {
      transform: scale(0.98);
      transition: transform 0.1s ease;
    }
    
    .navbar {
      transition: transform 0.3s ease;
    }
    
    .navbar-scrolled {
      backdrop-filter: blur(10px);
      background-color: rgba(255, 255, 255, 0.95);
    }
  `;
  document.head.appendChild(style);
}
