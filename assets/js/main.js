/*!
 * Modern Portfolio JavaScript
 * Bishwas Ghimire - Frontend Developer
 * Performance-optimized, accessible interactions
 */

(function() {
    'use strict';

    // =================================================================
    // UTILITY FUNCTIONS
    // =================================================================

    /**
     * Debounce function to limit function calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function to limit function calls
     */
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    /**
     * Query selector helper
     */
    function $(selector, parent = document) {
        return parent.querySelector(selector);
    }

    function $$(selector, parent = document) {
        return Array.from(parent.querySelectorAll(selector));
    }

    /**
     * Check if element is in viewport
     */
    function isInViewport(element, threshold = 0.1) {
        const rect = element.getBoundingClientRect();
        const height = window.innerHeight || document.documentElement.clientHeight;
        const width = window.innerWidth || document.documentElement.clientWidth;

        return (
            rect.top >= -threshold * height &&
            rect.left >= -threshold * width &&
            rect.bottom <= height + threshold * height &&
            rect.right <= width + threshold * width
        );
    }

    // =================================================================
    // THEME MANAGEMENT
    // =================================================================

    class ThemeManager {
        constructor() {
            this.currentTheme = localStorage.getItem('theme') || 'dark';
            this.themeToggle = $('#theme-toggle');
            this.init();
        }

        init() {
            this.setTheme(this.currentTheme);
            this.bindEvents();
        }

        bindEvents() {
            if (this.themeToggle) {
                this.themeToggle.addEventListener('click', () => {
                    this.toggleTheme();
                });

                // Handle keyboard activation
                this.themeToggle.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggleTheme();
                    }
                });
            }

            // Listen for system theme changes
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                mediaQuery.addEventListener('change', (e) => {
                    if (!localStorage.getItem('theme')) {
                        this.setTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        }

        setTheme(theme) {
            this.currentTheme = theme;
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            // Update meta theme-color for mobile browsers
            let metaThemeColor = $('meta[name="theme-color"]');
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.name = 'theme-color';
                document.head.appendChild(metaThemeColor);
            }
            metaThemeColor.content = theme === 'dark' ? '#0f1724' : '#ffffff';

            // Dispatch theme change event
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
        }

        toggleTheme() {
            const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
            this.setTheme(newTheme);

            // Add visual feedback
            if (this.themeToggle) {
                this.themeToggle.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.themeToggle.style.transform = '';
                }, 150);
            }
        }

        getTheme() {
            return this.currentTheme;
        }
    }

    // =================================================================
    // NAVIGATION MANAGEMENT
    // =================================================================

    class NavigationManager {
        constructor() {
            this.header = $('#header');
            this.nav = $('.nav');
            this.navMenu = $('#nav-menu');
            this.navToggle = $('#nav-toggle');
            this.navLinks = $$('.nav__link');
            this.lastScrollY = window.scrollY;
            this.isMenuOpen = false;
            this.init();
        }

        init() {
            this.bindEvents();
            this.handleScroll();
            this.setActiveLink();
        }

        bindEvents() {
            // Mobile menu toggle
            if (this.navToggle) {
                this.navToggle.addEventListener('click', () => {
                    this.toggleMobileMenu();
                });

                this.navToggle.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggleMobileMenu();
                    }
                });
            }

            // Navigation links
            this.navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        this.smoothScrollTo(href);
                        this.closeMobileMenu();
                    }
                });
            });

            // Scroll events
            window.addEventListener('scroll', throttle(() => {
                this.handleScroll();
                this.setActiveLink();
            }, 16));

            // Resize events
            window.addEventListener('resize', debounce(() => {
                if (window.innerWidth > 768 && this.isMenuOpen) {
                    this.closeMobileMenu();
                }
            }, 250));

            // Close menu on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isMenuOpen) {
                    this.closeMobileMenu();
                }
            });

            // Close menu on outside click
            document.addEventListener('click', (e) => {
                if (this.isMenuOpen && !this.nav.contains(e.target)) {
                    this.closeMobileMenu();
                }
            });
        }

        toggleMobileMenu() {
            if (this.isMenuOpen) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        }

        openMobileMenu() {
            this.isMenuOpen = true;
            this.navMenu.classList.add('show');
            this.navToggle.classList.add('active');
            this.navToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('no-scroll');

            // Focus management
            const firstNavLink = this.navLinks[0];
            if (firstNavLink) {
                setTimeout(() => firstNavLink.focus(), 100);
            }

            // Animate menu items
            this.animateMenuItems();
        }

        closeMobileMenu() {
            this.isMenuOpen = false;
            this.navMenu.classList.remove('show');
            this.navToggle.classList.remove('active');
            this.navToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('no-scroll');

            // Return focus to toggle button
            this.navToggle.focus();
        }

        animateMenuItems() {
            this.navLinks.forEach((link, index) => {
                link.style.opacity = '0';
                link.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    link.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    link.style.opacity = '1';
                    link.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        handleScroll() {
            const currentScrollY = window.scrollY;
            
            // Header background opacity
            if (this.header) {
                const opacity = Math.min(currentScrollY / 100, 0.95);
                this.header.style.setProperty('--header-opacity', opacity);
            }

            // Hide/show header on scroll (mobile)
            if (window.innerWidth <= 768) {
                if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                    // Scrolling down
                    this.header.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up
                    this.header.style.transform = 'translateY(0)';
                }
            }

            this.lastScrollY = currentScrollY;
        }

        setActiveLink() {
            const sections = $$('section[id]');
            const scrollPos = window.scrollY + 100;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');

                if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                    this.navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${sectionId}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }

        smoothScrollTo(target) {
            const element = $(target);
            if (element) {
                const headerHeight = this.header ? this.header.offsetHeight : 0;
                const targetPosition = element.offsetTop - headerHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        }
    }

    // =================================================================
    // MODAL MANAGEMENT
    // =================================================================

    class ModalManager {
        constructor() {
            this.modals = $$('.modal');
            this.openButtons = $$('[data-modal]');
            this.closeButtons = $$('[data-modal-close]');
            this.activeModal = null;
            this.focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Open modal buttons
            this.openButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modalId = button.getAttribute('data-modal');
                    this.openModal(modalId);
                });
            });

            // Close modal buttons
            this.closeButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modalId = button.getAttribute('data-modal-close');
                    this.closeModal(modalId);
                });
            });

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.activeModal) {
                    this.closeModal(this.activeModal.id);
                }
            });

            // Close on overlay click
            this.modals.forEach(modal => {
                const overlay = modal.querySelector('.modal__overlay');
                if (overlay) {
                    overlay.addEventListener('click', () => {
                        this.closeModal(modal.id);
                    });
                }
            });
        }

        openModal(modalId) {
            const modal = $(`#${modalId}`);
            if (!modal) return;

            this.activeModal = modal;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');

            // Focus management
            this.trapFocus(modal);
            
            // Focus first focusable element
            const firstFocusable = modal.querySelector(this.focusableElements);
            if (firstFocusable) {
                setTimeout(() => firstFocusable.focus(), 100);
            }

            // Animate content
            const content = modal.querySelector('.modal__content');
            if (content) {
                content.style.animation = 'modalSlideIn 0.3s ease-out forwards';
            }
        }

        closeModal(modalId) {
            const modal = $(`#${modalId}`);
            if (!modal) return;

            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
            this.activeModal = null;

            // Return focus to trigger button
            const triggerButton = $(`[data-modal="${modalId}"]`);
            if (triggerButton) {
                triggerButton.focus();
            }
        }

        trapFocus(modal) {
            const focusableElements = modal.querySelectorAll(this.focusableElements);
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];

            const handleTabKey = (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstFocusable) {
                            e.preventDefault();
                            lastFocusable.focus();
                        }
                    } else {
                        if (document.activeElement === lastFocusable) {
                            e.preventDefault();
                            firstFocusable.focus();
                        }
                    }
                }
            };

            modal.addEventListener('keydown', handleTabKey);
        }
    }

    // =================================================================
    // SCROLL ANIMATIONS
    // =================================================================

    class ScrollAnimations {
        constructor() {
            this.animateElements = $$('.animate-on-scroll, .project-card, .skill-card, .service-card');
            this.observer = null;
            this.init();
        }

        init() {
            this.setupIntersectionObserver();
            this.addAnimationClasses();
        }

        setupIntersectionObserver() {
            const options = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        
                        // Add stagger effect for grouped elements
                        this.addStaggerEffect(entry.target);
                        
                        // Stop observing once animated
                        this.observer.unobserve(entry.target);
                    }
                });
            }, options);

            this.animateElements.forEach(element => {
                this.observer.observe(element);
            });
        }

        addAnimationClasses() {
            this.animateElements.forEach(element => {
                if (!element.classList.contains('animate-on-scroll')) {
                    element.classList.add('animate-on-scroll');
                }
            });
        }

        addStaggerEffect(element) {
            const parent = element.parentElement;
            const siblings = Array.from(parent.children).filter(child => 
                child.classList.contains('animate-on-scroll')
            );
            
            const index = siblings.indexOf(element);
            const delay = index * 100;
            
            element.style.transitionDelay = `${delay}ms`;
        }

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    }

    // =================================================================
    // TYPEWRITER EFFECT
    // =================================================================

    class TypewriterEffect {
        constructor() {
            this.typewriters = document.querySelectorAll('.typewriter');
            this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            this.init();
        }

        init() {
            // Add intersection observer for better mobile performance
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && !entry.target.classList.contains('typed')) {
                        const index = Array.from(this.typewriters).indexOf(entry.target);
                        setTimeout(() => {
                            this.typeText(entry.target);
                        }, index * 1000); // Reduced delay for mobile
                        entry.target.classList.add('typed');
                    }
                });
            }, { threshold: 0.5 });

            this.typewriters.forEach((element) => {
                observer.observe(element);
            });
        }

        typeText(element) {
            const text = element.dataset.text;
            if (!text) return;

            element.classList.add('typing');
            element.textContent = '';
            
            let i = 0;
            // Faster typing speed on mobile for better UX
            const speed = this.isMobile ? 30 : 50;

            const typeInterval = setInterval(() => {
                if (i < text.length) {
                    element.textContent += text.charAt(i);
                    i++;
                } else {
                    clearInterval(typeInterval);
                    element.classList.remove('typing');
                    element.classList.add('finished');
                    // Ensure proper layout after typing
                    element.style.minHeight = 'auto';
                }
            }, speed);
        }
    }

    // =================================================================
    // FORM MANAGEMENT
    // =================================================================

    class FormManager {
        constructor() {
            this.contactForm = $('#contact-form');
            this.init();
        }

        init() {
            if (this.contactForm) {
                this.bindEvents();
                this.setupValidation();
            }
        }

        bindEvents() {
            this.contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

            // Real-time validation
            const inputs = this.contactForm.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });

                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        }

        setupValidation() {
            const inputs = this.contactForm.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.setAttribute('aria-describedby', `${input.id}-error`);
            });
        }

        validateField(field) {
            const value = field.value.trim();
            const fieldName = field.name;
            let isValid = true;
            let errorMessage = '';

            // Remove existing error
            this.clearFieldError(field);

            switch (fieldName) {
                case 'name':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Name is required';
                    } else if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'Name must be at least 2 characters';
                    }
                    break;

                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Email is required';
                    } else if (!emailRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address';
                    }
                    break;

                case 'subject':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Subject is required';
                    } else if (value.length < 3) {
                        isValid = false;
                        errorMessage = 'Subject must be at least 3 characters';
                    }
                    break;

                case 'message':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Message is required';
                    } else if (value.length < 10) {
                        isValid = false;
                        errorMessage = 'Message must be at least 10 characters';
                    }
                    break;
            }

            if (!isValid) {
                this.showFieldError(field, errorMessage);
            }

            return isValid;
        }

        showFieldError(field, message) {
            field.classList.add('error');
            field.setAttribute('aria-invalid', 'true');

            let errorElement = $(`#${field.id}-error`);
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = `${field.id}-error`;
                errorElement.className = 'field-error';
                field.parentNode.appendChild(errorElement);
            }

            errorElement.textContent = message;
            errorElement.setAttribute('role', 'alert');
        }

        clearFieldError(field) {
            field.classList.remove('error');
            field.setAttribute('aria-invalid', 'false');

            const errorElement = $(`#${field.id}-error`);
            if (errorElement) {
                errorElement.remove();
            }
        }

        async handleSubmit() {
            const formData = new FormData(this.contactForm);
            const submitButton = this.contactForm.querySelector('button[type="submit"]');
            
            // Validate all fields
            const inputs = this.contactForm.querySelectorAll('input, textarea');
            let isFormValid = true;

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isFormValid = false;
                }
            });

            if (!isFormValid) {
                return;
            }

            // Show loading state
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Sending...';
            submitButton.disabled = true;

            try {
                const response = await fetch(this.contactForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.showSuccessMessage();
                    this.contactForm.reset();
                } else {
                    throw new Error('Form submission failed');
                }
            } catch (error) {
                this.showErrorMessage();
            } finally {
                // Reset button state
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        }

        showSuccessMessage() {
            const message = document.createElement('div');
            message.className = 'form-message form-message--success';
            message.textContent = 'Thank you! Your message has been sent successfully.';
            message.setAttribute('role', 'alert');

            this.contactForm.parentNode.insertBefore(message, this.contactForm);

            setTimeout(() => {
                message.remove();
            }, 5000);
        }

        showErrorMessage() {
            const message = document.createElement('div');
            message.className = 'form-message form-message--error';
            message.textContent = 'Sorry, there was an error sending your message. Please try again.';
            message.setAttribute('role', 'alert');

            this.contactForm.parentNode.insertBefore(message, this.contactForm);

            setTimeout(() => {
                message.remove();
            }, 5000);
        }
    }

    // =================================================================
    // PERFORMANCE MONITORING
    // =================================================================

    class PerformanceMonitor {
        constructor() {
            this.metrics = {
                LCP: null,
                FID: null,
                CLS: null
            };
            this.init();
        }

        init() {
            this.measureLCP();
            this.measureFID();
            this.measureCLS();
        }

        measureLCP() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.LCP = lastEntry.startTime;
                });

                observer.observe({ entryTypes: ['largest-contentful-paint'] });
            }
        }

        measureFID() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        this.metrics.FID = entry.processingStart - entry.startTime;
                    });
                });

                observer.observe({ entryTypes: ['first-input'] });
            }
        }

        measureCLS() {
            if ('PerformanceObserver' in window) {
                let clsValue = 0;
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    });
                    this.metrics.CLS = clsValue;
                });

                observer.observe({ entryTypes: ['layout-shift'] });
            }
        }

        getMetrics() {
            return this.metrics;
        }
    }

    // =================================================================
    // MAIN APPLICATION
    // =================================================================

    class PortfolioApp {
        constructor() {
            this.themeManager = null;
            this.navigationManager = null;
            this.modalManager = null;
            this.scrollAnimations = null;
            this.formManager = null;
            this.performanceMonitor = null;
            this.init();
        }

        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.initializeComponents();
                });
            } else {
                this.initializeComponents();
            }
        }

        initializeComponents() {
            try {
                // Initialize all components
                this.themeManager = new ThemeManager();
                this.navigationManager = new NavigationManager();
                this.modalManager = new ModalManager();
                this.scrollAnimations = new ScrollAnimations();
                this.typewriterEffect = new TypewriterEffect();
                this.formManager = new FormManager();
                this.performanceMonitor = new PerformanceMonitor();

                // Add custom event listeners
                this.bindCustomEvents();

                // Initialize additional features
                this.initializeLazyLoading();
                this.initializeAccessibilityFeatures();

                console.log('Portfolio app initialized successfully');
            } catch (error) {
                console.error('Error initializing portfolio app:', error);
            }
        }

        bindCustomEvents() {
            // Theme change event
            window.addEventListener('themeChanged', (e) => {
                console.log(`Theme changed to: ${e.detail.theme}`);
            });

            // Performance monitoring
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const metrics = this.performanceMonitor.getMetrics();
                    console.log('Performance metrics:', metrics);
                }, 1000);
            });
        }

        initializeLazyLoading() {
            if ('IntersectionObserver' in window) {
                const lazyImages = $$('img[data-src]');
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

                lazyImages.forEach(img => imageObserver.observe(img));
            }
        }

        initializeAccessibilityFeatures() {
            // Skip links
            const skipLinks = $$('.skip-link');
            skipLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    const target = $(link.getAttribute('href'));
                    if (target) {
                        target.focus();
                        target.scrollIntoView();
                    }
                });
            });

            // Reduced motion preferences
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.style.setProperty('--transition-fast', '0ms');
                document.documentElement.style.setProperty('--transition-normal', '0ms');
                document.documentElement.style.setProperty('--transition-slow', '0ms');
            }

            // High contrast mode support
            if (window.matchMedia('(prefers-contrast: high)').matches) {
                document.documentElement.classList.add('high-contrast');
            }
        }

        destroy() {
            // Cleanup method for SPA environments
            if (this.scrollAnimations) {
                this.scrollAnimations.destroy();
            }
        }
    }

    // =================================================================
    // INITIALIZE APPLICATION
    // =================================================================

    // Start the application
    const app = new PortfolioApp();

    // Export for global access if needed
    window.PortfolioApp = PortfolioApp;

    // Service Worker registration (optional)
    if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered: ', registration);
                })
                .catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
        });
    }

})();

// =================================================================
// ADDITIONAL STYLES FOR FORM VALIDATION AND MESSAGES
// =================================================================

// Add dynamic styles for form validation
const formStyles = `
    .field-error {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .field-error::before {
        content: "⚠";
        flex-shrink: 0;
    }

    input.error,
    textarea.error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .form-message {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInDown 0.3s ease-out;
    }

    .form-message--success {
        background: rgba(34, 197, 94, 0.1);
        color: #059669;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .form-message--success::before {
        content: "✓";
        flex-shrink: 0;
    }

    .form-message--error {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .form-message--error::before {
        content: "✗";
        flex-shrink: 0;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .high-contrast {
        filter: contrast(150%);
    }

    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }

    .lazy.loaded {
        opacity: 1;
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = formStyles;
document.head.appendChild(styleSheet);