/**
 * DUET PDF Library - Mobile & Responsive Enhancements
 * JavaScript for improved mobile experience and image loading
 */

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

        // Add touch-specific CSS
        const touchStyles = `
      .touch-device .book-card.touch-active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
      }
      
      @media (hover: none) {
        .book-card:hover {
          transform: none !important;
          box-shadow: var(--shadow-sm) !important;
        }
        .book-overlay {
          opacity: 0 !important;
        }
        .book-actions {
          opacity: 1 !important;
          position: static !important;
        }
      }
    `;

        const style = document.createElement('style');
        style.textContent = touchStyles;
        document.head.appendChild(style);
    }

    // Handle orientation changes
    window.addEventListener('orientationchange', function () {
        setTimeout(() => {
            // Recalculate layout after orientation change
            window.dispatchEvent(new Event('resize'));

            // Re-initialize any position-dependent elements
            const stickyElements = document.querySelectorAll('.sticky-top');
            stickyElements.forEach(el => {
                el.style.top = '0';
            });
        }, 100);
    });

    // Improve viewport handling on mobile
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport && window.innerWidth <= 768) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
}

// Enhanced search functionality
function initializeSearchEnhancements() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('.search-form');

    if (searchInput && searchForm) {
        let searchTimeout;

        // Add search input enhancements
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            // Show/hide clear button
            toggleClearButton(this, query.length > 0);

            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    // Could implement search suggestions here
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

                // Reset after form submission or timeout
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });

        // Initialize clear button if search has value
        if (searchInput.value.trim()) {
            toggleClearButton(searchInput, true);
        }
    }

    // Helper function to toggle clear button
    function toggleClearButton(input, show) {
        let clearBtn = input.parentElement.querySelector('.search-clear-btn');

        if (show && !clearBtn) {
            clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'btn btn-link search-clear-btn position-absolute end-0 top-50 translate-middle-y me-5';
            clearBtn.style.cssText = 'z-index: 10; padding: 0; border: none; background: none;';
            clearBtn.innerHTML = '<i class="bi bi-x-circle text-muted"></i>';

            clearBtn.addEventListener('click', function () {
                input.value = '';
                input.focus();
                input.dispatchEvent(new Event('input'));
            });

            input.parentElement.style.position = 'relative';
            input.parentElement.appendChild(clearBtn);
        } else if (!show && clearBtn) {
            clearBtn.remove();
        }
    }
}

// Enhanced hero section animations
function initializeHeroAnimations() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;

    // Counter animation for statistics
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        let current = 0;
        const increment = target / 50; // Adjust speed

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 30);
    });

    // Parallax effect for hero shapes (if supported)
    if (window.innerWidth > 768) {
        window.addEventListener('scroll', function () {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.shape');

            shapes.forEach((shape, index) => {
                const speed = 0.1 + (index * 0.05);
                shape.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }
}

// Initialize sticky search on scroll
function initializeStickySearch() {
    const searchSection = document.querySelector('.search-filter-section');
    if (!searchSection) return;

    let ticking = false;

    function updateSearchSection() {
        const scrolled = window.pageYOffset;
        const heroHeight = document.querySelector('.hero-section')?.offsetHeight || 0;

        if (scrolled > heroHeight / 2) {
            searchSection.classList.add('scrolled');
        } else {
            searchSection.classList.remove('scrolled');
        }

        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            requestAnimationFrame(updateSearchSection);
            ticking = true;
        }
    });
}

// Initialize all mobile enhancements when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    initializeImageLoading();
    initializeMobileEnhancements();
    initializeSearchEnhancements();
    initializeHeroAnimations();
    initializeStickySearch();
});

// Export functions for use in other scripts
window.duetMobileEnhancements = {
    initializeImageLoading,
    initializeMobileEnhancements,
    initializeSearchEnhancements,
    initializeHeroAnimations,
    initializeStickySearch
};
