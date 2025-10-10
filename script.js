// ========================================
// Smooth Scrolling for Buttons and Links
// ========================================

function smoothScrollTo(targetId) {
    const targetElement = document.getElementById(targetId);
    if (targetElement) {
        targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Add click event to Contact Me button
const ctaButton = document.querySelector('.cta-button');
if (ctaButton) {
    ctaButton.addEventListener('click', function(e) {
        const targetSection = this.getAttribute('data-scroll-to');
        if (targetSection) {
            smoothScrollTo(targetSection);
        }
    });
}

// ========================================
// Ripple Effect on Buttons (Material You)
// ========================================

function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
    
    // Get button dimensions
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    // Set ripple styles
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    // Remove old ripples
    const oldRipples = button.getElementsByClassName('ripple');
    while (oldRipples.length > 0) {
        oldRipples[0].remove();
    }
    
    // Add new ripple
    button.appendChild(ripple);
    
    // Remove ripple after animation
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Add ripple effect to all buttons
const buttons = document.querySelectorAll('.cta-button');
buttons.forEach(button => {
    button.addEventListener('click', createRipple);
});

// ========================================
// Scroll Reveal Animation using IntersectionObserver
// ========================================

const revealElements = document.querySelectorAll('.reveal');

const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
            // Add staggered delay for elements
            setTimeout(() => {
                entry.target.classList.add('active');
            }, index * 100);
            
            // Stop observing once revealed
            observer.unobserve(entry.target);
        }
    });
}, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
});

// Observe all reveal elements
revealElements.forEach(element => {
    revealObserver.observe(element);
});

// ========================================
// Handle Project Image Loading Errors
// ========================================

const projectImages = document.querySelectorAll('.project-image');
projectImages.forEach(img => {
    img.addEventListener('error', function() {
        // Create a fallback gradient background
        this.style.display = 'none';
        this.parentElement.style.background = 'linear-gradient(135deg, #A6E3E9 0%, #71C9CE 100%)';
        
        // Add text overlay
        const fallbackText = document.createElement('div');
        fallbackText.style.cssText = `
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            padding: 20px;
        `;
        fallbackText.textContent = 'Project Image';
        this.parentElement.appendChild(fallbackText);
    });
});

// ========================================
// Lazy Loading for Profile Image
// ========================================

const profileImage = document.querySelector('.profile-image');
if (profileImage && 'loading' in HTMLImageElement.prototype) {
    profileImage.loading = 'lazy';
}

// ========================================
// Enhance Accessibility - Keyboard Navigation
// ========================================

document.addEventListener('keydown', function(e) {
    // Allow Enter key to trigger button clicks
    if (e.key === 'Enter' && e.target.classList.contains('cta-button')) {
        e.target.click();
    }
});

// ========================================
// Smooth Scroll for All Internal Links
// ========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            e.preventDefault();
            const targetId = href.substring(1);
            smoothScrollTo(targetId);
        }
    });
});

// ========================================
// Add Hover Effect Enhancement for Project Items
// ========================================

const projectItems = document.querySelectorAll('.project-item');
projectItems.forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(8px)';
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});

// ========================================
// Performance Optimization - Debounce Scroll Events
// ========================================

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

// ========================================
// Add Active State to Navigation (Future Enhancement)
// ========================================

const sections = document.querySelectorAll('section[id]');
const navObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Can be used to highlight nav items in future
            const currentSection = entry.target.getAttribute('id');
            // console.log('Current section:', currentSection);
        }
    });
}, {
    threshold: 0.5
});

sections.forEach(section => {
    navObserver.observe(section);
});

// ========================================
// Console Welcome Message
// ========================================

console.log('%c👋 Welcome to Bishwas Ghimire\'s Portfolio!', 
    'color: #71C9CE; font-size: 20px; font-weight: bold;');
console.log('%cBuilt with ❤️ using HTML, CSS, and JavaScript', 
    'color: #546e7a; font-size: 14px;');
