/**
 * Portfolio Website - Bishwas Ghimire
 * Main JavaScript functionality
 * 
 * Features:
 * - Intersection Observer for scroll animations
 * - Modal system with focus trap
 * - Form validation
 * - Mobile navigation
 * - Project filtering
 * - Parallax effects
 * - Accessibility enhancements
 */

// =========================================
// CONFIGURATION & STATE
// =========================================
const config = {
  animationThreshold: 0.15,
  modalFocusableSelectors: 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
  toastDuration: 5000,
};

const state = {
  lastScrollY: 0,
  isModalOpen: false,
  modalFocusableElements: [],
  modalFirstFocusable: null,
  modalLastFocusable: null,
  prefersReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
};

// =========================================
// UTILITY FUNCTIONS
// =========================================

/**
 * Debounce function to limit rate of function calls
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
 * Trap focus within modal for accessibility
 */
function trapFocus(event) {
  if (event.key === 'Tab') {
    if (event.shiftKey) {
      if (document.activeElement === state.modalFirstFocusable) {
        event.preventDefault();
        state.modalLastFocusable.focus();
      }
    } else {
      if (document.activeElement === state.modalLastFocusable) {
        event.preventDefault();
        state.modalFirstFocusable.focus();
      }
    }
  }
}

/**
 * Show toast notification
 */
function showToast(message, duration = config.toastDuration) {
  const toast = document.querySelector('.toast');
  if (!toast) return;
  
  toast.textContent = message;
  toast.classList.add('show');
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

// =========================================
// NAVIGATION
// =========================================

/**
 * Initialize mobile navigation
 */
function initNavigation() {
  const navToggle = document.querySelector('.nav-toggle');
  const navMenu = document.querySelector('.nav-menu');
  const navLinks = document.querySelectorAll('.nav-link');
  const body = document.body;
  
  if (!navToggle || !navMenu) return;
  
  // Toggle mobile menu
  navToggle.addEventListener('click', () => {
    const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', !isExpanded);
    navMenu.classList.toggle('active');
    body.classList.toggle('no-scroll');
  });
  
  // Close menu when clicking nav links
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      navToggle.setAttribute('aria-expanded', 'false');
      navMenu.classList.remove('active');
      body.classList.remove('no-scroll');
    });
  });
  
  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (navMenu.classList.contains('active') &&
        !navMenu.contains(e.target) &&
        !navToggle.contains(e.target)) {
      navToggle.setAttribute('aria-expanded', 'false');
      navMenu.classList.remove('active');
      body.classList.remove('no-scroll');
    }
  });
  
  // Handle ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navMenu.classList.contains('active')) {
      navToggle.setAttribute('aria-expanded', 'false');
      navMenu.classList.remove('active');
      body.classList.remove('no-scroll');
      navToggle.focus();
    }
  });
}

/**
 * Hide/show navigation on scroll
 */
function initScrollNav() {
  const nav = document.querySelector('.nav');
  if (!nav) return;
  
  const handleScroll = debounce(() => {
    const currentScrollY = window.scrollY;
    
    if (currentScrollY > state.lastScrollY && currentScrollY > 100) {
      nav.style.transform = 'translateY(-100%)';
    } else {
      nav.style.transform = 'translateY(0)';
    }
    
    state.lastScrollY = currentScrollY;
  }, 10);
  
  window.addEventListener('scroll', handleScroll, { passive: true });
}

// =========================================
// SCROLL ANIMATIONS (Intersection Observer)
// =========================================

/**
 * Initialize scroll-based animations
 */
function initScrollAnimations() {
  const animatedElements = document.querySelectorAll('.animate-in');
  
  if (!animatedElements.length || state.prefersReducedMotion) {
    // If reduced motion is preferred, show all elements immediately
    animatedElements.forEach(el => el.classList.add('visible'));
    return;
  }
  
  const observerOptions = {
    threshold: config.animationThreshold,
    rootMargin: '0px 0px -50px 0px',
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        // Optionally unobserve after animation
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  animatedElements.forEach(el => observer.observe(el));
}

// =========================================
// PARALLAX EFFECT
// =========================================

/**
 * Initialize subtle parallax effect on hero
 */
function initParallax() {
  if (state.prefersReducedMotion) return;
  
  const hero = document.querySelector('.hero');
  const orbs = document.querySelectorAll('.orb');
  
  if (!hero || !orbs.length) return;
  
  const handleMouseMove = debounce((e) => {
    const { clientX, clientY } = e;
    const { innerWidth, innerHeight } = window;
    
    // Calculate movement as percentage from center
    const xPercent = (clientX / innerWidth - 0.5) * 2;
    const yPercent = (clientY / innerHeight - 0.5) * 2;
    
    orbs.forEach((orb, index) => {
      const speed = (index + 1) * 10;
      const x = xPercent * speed;
      const y = yPercent * speed;
      
      orb.style.transform = `translate(${x}px, ${y}px)`;
    });
  }, 10);
  
  hero.addEventListener('mousemove', handleMouseMove);
}

// =========================================
// PROJECT FILTERING
// =========================================

/**
 * Initialize project filtering
 */
function initProjectFiltering() {
  const filterButtons = document.querySelectorAll('.filter-btn');
  const projectCards = document.querySelectorAll('.project-card');
  
  if (!filterButtons.length || !projectCards.length) return;
  
  filterButtons.forEach(button => {
    button.addEventListener('click', () => {
      const filter = button.dataset.filter;
      
      // Update active state
      filterButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');
      
      // Filter projects
      projectCards.forEach(card => {
        const tags = card.dataset.tags;
        
        if (filter === 'all' || tags.includes(filter)) {
          card.classList.remove('hidden');
          // Trigger animation
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, 50);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          setTimeout(() => {
            card.classList.add('hidden');
          }, 300);
        }
      });
    });
  });
}

// =========================================
// PROJECT MODAL
// =========================================

/**
 * Project data (in a real app, this would come from an API or CMS)
 */
const projectData = {
  1: {
    title: 'NPL Work Hub',
    image: 'assets/images/nplworkhub.jpg',
    description: 'NPL Work Hub is a comprehensive work management platform that connects professionals with job opportunities across Nepal. The platform features job listings, application tracking, employer dashboards, and professional networking capabilities. Built with modern web technologies to provide a seamless experience for both job seekers and employers.',
    role: ['Full-stack Development', 'UI/UX Design', 'Platform Architecture', 'Database Design'],
    tech: ['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL', 'Bootstrap'],
    highlights: [
      'Connected thousands of professionals with employment opportunities',
      'Implemented advanced job search and filtering system',
      'Built real-time application tracking dashboard',
      'Designed responsive interface for mobile and desktop users',
      'Integrated secure authentication and authorization system',
    ],
    liveUrl: 'https://nplworkhub.tech',
    codeUrl: '#',
  },
};

/**
 * Open project modal with data
 */
function openModal(projectId) {
  const modal = document.querySelector('.modal');
  const modalTitle = modal.querySelector('#modal-title');
  const modalGallery = modal.querySelector('.modal-gallery img');
  const modalDescription = modal.querySelector('.modal-description');
  const modalRoleList = modal.querySelector('.modal-section:nth-of-type(2) .modal-list');
  const modalTechTags = modal.querySelector('.modal-tags');
  const modalHighlights = modal.querySelector('.modal-section:nth-of-type(4) .modal-list');
  const modalLinks = modal.querySelectorAll('.modal-links a');
  
  const project = projectData[projectId];
  if (!project || !modal) return;
  
  // Populate modal with project data
  modalTitle.textContent = project.title;
  modalGallery.src = project.image;
  modalGallery.alt = `${project.title} screenshot`;
  modalDescription.textContent = project.description;
  
  // Role list
  modalRoleList.innerHTML = project.role.map(role => `<li>${role}</li>`).join('');
  
  // Tech tags
  modalTechTags.innerHTML = project.tech.map(tech => `<span class="tag">${tech}</span>`).join('');
  
  // Highlights
  modalHighlights.innerHTML = project.highlights.map(highlight => `<li>${highlight}</li>`).join('');
  
  // Links
  modalLinks[0].href = project.liveUrl;
  modalLinks[1].href = project.codeUrl;
  
  // Show modal
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('no-scroll');
  state.isModalOpen = true;
  
  // Set up focus trap
  state.modalFocusableElements = Array.from(
    modal.querySelectorAll(config.modalFocusableSelectors)
  ).filter(el => !el.hasAttribute('disabled'));
  
  state.modalFirstFocusable = state.modalFocusableElements[0];
  state.modalLastFocusable = state.modalFocusableElements[state.modalFocusableElements.length - 1];
  
  // Focus first element
  setTimeout(() => {
    const closeBtn = modal.querySelector('.modal-close');
    if (closeBtn) closeBtn.focus();
  }, 100);
  
  // Add focus trap listener
  modal.addEventListener('keydown', trapFocus);
}

/**
 * Close project modal
 */
function closeModal() {
  const modal = document.querySelector('.modal');
  if (!modal) return;
  
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('no-scroll');
  state.isModalOpen = false;
  
  // Remove focus trap listener
  modal.removeEventListener('keydown', trapFocus);
}

/**
 * Initialize modal system
 */
function initModal() {
  const modal = document.querySelector('.modal');
  const modalClose = document.querySelector('.modal-close');
  const modalOverlay = document.querySelector('.modal-overlay');
  const projectCards = document.querySelectorAll('.project-card');
  
  if (!modal) return;
  
  // Open modal when clicking project cards
  projectCards.forEach(card => {
    const viewBtn = card.querySelector('.project-view-btn');
    const projectId = card.dataset.project;
    
    if (viewBtn && projectId) {
      viewBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openModal(projectId);
      });
      
      // Also allow clicking the whole card
      card.addEventListener('click', () => {
        openModal(projectId);
      });
      
      // Keyboard accessibility
      card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openModal(projectId);
        }
      });
    }
  });
  
  // Close modal
  if (modalClose) {
    modalClose.addEventListener('click', closeModal);
  }
  
  if (modalOverlay) {
    modalOverlay.addEventListener('click', closeModal);
  }
  
  // Close on ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && state.isModalOpen) {
      closeModal();
    }
  });
}

// =========================================
// FORM VALIDATION
// =========================================

/**
 * Validate email format
 */
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Show form error
 */
function showFormError(input, message) {
  const formGroup = input.closest('.form-group');
  const errorElement = formGroup.querySelector('.form-error');
  
  formGroup.classList.add('error');
  errorElement.textContent = message;
  input.setAttribute('aria-invalid', 'true');
}

/**
 * Clear form error
 */
function clearFormError(input) {
  const formGroup = input.closest('.form-group');
  const errorElement = formGroup.querySelector('.form-error');
  
  formGroup.classList.remove('error');
  errorElement.textContent = '';
  input.removeAttribute('aria-invalid');
}

/**
 * Validate form input
 */
function validateInput(input) {
  const value = input.value.trim();
  const name = input.name;
  
  clearFormError(input);
  
  if (!value) {
    showFormError(input, `${name.charAt(0).toUpperCase() + name.slice(1)} is required`);
    return false;
  }
  
  if (name === 'email' && !isValidEmail(value)) {
    showFormError(input, 'Please enter a valid email address');
    return false;
  }
  
  if (name === 'message' && value.length < 10) {
    showFormError(input, 'Message must be at least 10 characters');
    return false;
  }
  
  return true;
}

/**
 * Initialize contact form
 */
function initContactForm() {
  const form = document.querySelector('.contact-form');
  const formSuccess = document.querySelector('.form-success');
  
  if (!form) return;
  
  const inputs = form.querySelectorAll('.form-input');
  
  // Real-time validation on blur
  inputs.forEach(input => {
    input.addEventListener('blur', () => {
      if (input.value.trim()) {
        validateInput(input);
      }
    });
    
    // Clear error on input
    input.addEventListener('input', () => {
      if (input.closest('.form-group').classList.contains('error')) {
        clearFormError(input);
      }
    });
  });
  
  // Form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate all inputs
    let isValid = true;
    inputs.forEach(input => {
      if (!validateInput(input)) {
        isValid = false;
      }
    });
    
    if (!isValid) {
      // Focus first invalid input
      const firstError = form.querySelector('.form-group.error .form-input');
      if (firstError) firstError.focus();
      return;
    }
    
    // Get form data
    const formData = {
      name: form.name.value.trim(),
      email: form.email.value.trim(),
      message: form.message.value.trim(),
    };
    
    // Simulate form submission (in a real app, send to server)
    try {
      // Show loading state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Sending...';
      submitBtn.disabled = true;
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // Show success message
      form.reset();
      formSuccess.classList.add('show');
      showToast('Message sent successfully!');
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        formSuccess.classList.remove('show');
      }, 5000);
      
      // Reset button
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
      
      console.log('Form submitted:', formData);
    } catch (error) {
      showToast('Something went wrong. Please try again.');
      console.error('Form submission error:', error);
    }
  });
}

// =========================================
// BACK TO TOP
// =========================================

/**
 * Initialize back to top button
 */
function initBackToTop() {
  const backToTopBtn = document.querySelector('.back-to-top');
  
  if (!backToTopBtn) return;
  
  backToTopBtn.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    });
  });
}

// =========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// =========================================

/**
 * Initialize smooth scroll for anchor links
 */
function initSmoothScroll() {
  const anchorLinks = document.querySelectorAll('a[href^="#"]');
  
  anchorLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      
      // Skip if it's just '#' or empty
      if (!href || href === '#') return;
      
      const target = document.querySelector(href);
      
      if (target) {
        e.preventDefault();
        
        const navHeight = document.querySelector('.nav')?.offsetHeight || 0;
        const targetPosition = target.offsetTop - navHeight;
        
        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth',
        });
        
        // Update focus for accessibility
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
        target.removeAttribute('tabindex');
      }
    });
  });
}

// =========================================
// KEYBOARD NAVIGATION ENHANCEMENTS
// =========================================

/**
 * Enhance keyboard navigation for project cards
 */
function initKeyboardNav() {
  const projectCards = document.querySelectorAll('.project-card');
  
  projectCards.forEach(card => {
    // Make cards focusable
    card.setAttribute('tabindex', '0');
    
    // Add visual focus indicator behavior
    card.addEventListener('focus', () => {
      card.style.outline = '2px solid var(--color-accent)';
      card.style.outlineOffset = '4px';
    });
    
    card.addEventListener('blur', () => {
      card.style.outline = 'none';
    });
  });
}

// =========================================
// INITIALIZATION
// =========================================

/**
 * Initialize all functionality when DOM is ready
 */
function init() {
  console.log('🎨 Initializing portfolio...');
  
  // Check for reduced motion preference
  if (state.prefersReducedMotion) {
    console.log('⚡ Reduced motion mode enabled');
  }
  
  // Initialize all features
  initNavigation();
  initScrollNav();
  initScrollAnimations();
  initParallax();
  initProjectFiltering();
  initModal();
  initContactForm();
  initBackToTop();
  initSmoothScroll();
  initKeyboardNav();
  
  console.log('✨ Portfolio initialized successfully!');
}

// Wait for DOM to be ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// =========================================
// WINDOW RESIZE HANDLER
// =========================================

/**
 * Handle window resize events
 */
const handleResize = debounce(() => {
  // Update viewport height custom property for mobile
  document.documentElement.style.setProperty('--vh', `${window.innerHeight * 0.01}px`);
  
  // Close mobile menu if window becomes larger
  if (window.innerWidth > 1023) {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
      navToggle.setAttribute('aria-expanded', 'false');
      navMenu.classList.remove('active');
      document.body.classList.remove('no-scroll');
    }
  }
}, 250);

window.addEventListener('resize', handleResize);

// Set initial viewport height
handleResize();

// =========================================
// PROGRESSIVE ENHANCEMENT
// =========================================

/**
 * Add 'js-enabled' class to HTML element
 * This allows CSS to provide enhanced styles when JS is available
 */
document.documentElement.classList.add('js-enabled');

/**
 * Service Worker registration (optional - for PWA features)
 * Uncomment if you want to add offline support
 */
/*
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => console.log('SW registered:', registration))
      .catch(error => console.log('SW registration failed:', error));
  });
}
*/
