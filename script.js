// =====================================================
// PRELOADER
// =====================================================
window.addEventListener('load', () => {
    const preloader = document.querySelector('.preloader');
    setTimeout(() => {
        preloader.classList.add('hidden');
    }, 4000);
});

// =====================================================
// SMOOTH SCROLL FOR NAVIGATION LINKS
// =====================================================
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('href');
        const targetSection = document.querySelector(targetId);
        
        if (targetSection) {
            targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// =====================================================
// FADE IN ON SCROLL - INTERSECTION OBSERVER
// =====================================================
const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all sections
document.querySelectorAll('.section').forEach(section => {
    observer.observe(section);
});

// =====================================================
// VIDEO GALLERY - PLAY ON HOVER
// =====================================================
document.querySelectorAll('.gallery-item-video').forEach(item => {
    const video = item.querySelector('.gallery-video');
    
    if (video) {
        item.addEventListener('mouseenter', () => {
            video.play().catch(err => console.log('Video play failed:', err));
        });
        
        item.addEventListener('mouseleave', () => {
            video.pause();
            video.currentTime = 0;
        });
    }
});

// =====================================================
// CONTACT FORM HANDLING
// =====================================================
const contactForm = document.querySelector('.contact-form');

if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            message: document.getElementById('message').value
        };
        
        // Simulate form submission
        console.log('Form submitted:', formData);
        
        // Show success message (you can customize this)
        const submitBtn = contactForm.querySelector('.form-submit');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Message Sent!';
        submitBtn.style.backgroundColor = 'var(--accent)';
        submitBtn.style.color = 'var(--bg-primary)';
        
        // Reset form
        contactForm.reset();
        
        // Reset button after 3 seconds
        setTimeout(() => {
            submitBtn.textContent = originalText;
            submitBtn.style.backgroundColor = 'transparent';
            submitBtn.style.color = 'var(--accent)';
        }, 3000);
    });
}

// =====================================================
// ACTIVE NAV LINK ON SCROLL
// =====================================================
function setActiveNavLink() {
    const sections = document.querySelectorAll('.section');
    const navLinks = document.querySelectorAll('.nav-link');
    
    let currentSection = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (window.pageYOffset >= (sectionTop - sectionHeight / 3)) {
            currentSection = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.style.color = 'var(--text-secondary)';
        link.style.borderBottomColor = 'transparent';
        
        if (link.getAttribute('href') === `#${currentSection}`) {
            link.style.color = 'var(--accent-hover)';
            link.style.borderBottomColor = 'var(--accent)';
        }
    });
}

window.addEventListener('scroll', setActiveNavLink);

// =====================================================
// KEYBOARD NAVIGATION ACCESSIBILITY
// =====================================================
document.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
        document.body.classList.add('keyboard-navigation');
    }
});

document.addEventListener('mousedown', () => {
    document.body.classList.remove('keyboard-navigation');
});

// =====================================================
// LAZY LOADING OPTIMIZATION
// =====================================================
if ('loading' in HTMLImageElement.prototype) {
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        img.src = img.dataset.src || img.src;
    });
} else {
    // Fallback for browsers that don't support lazy loading
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
    document.body.appendChild(script);
}

// =====================================================
// RESPONSIVE IMAGE LOADING
// =====================================================
function optimizeImages() {
    const images = document.querySelectorAll('img');
    const width = window.innerWidth;
    
    images.forEach(img => {
        if (width < 480 && !img.src.includes('w=400')) {
            // Load smaller images on mobile
            const newSrc = img.src.replace(/w=\d+/, 'w=400');
            if (img.src !== newSrc) img.src = newSrc;
        }
    });
}

window.addEventListener('resize', optimizeImages);
optimizeImages();

// =====================================================
// PERFORMANCE: REDUCE MOTION FOR USERS WHO PREFER IT
// =====================================================
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (prefersReducedMotion.matches) {
    document.querySelectorAll('*').forEach(el => {
        el.style.animation = 'none';
        el.style.transition = 'none';
    });
}
