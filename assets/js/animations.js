// Optimized Scroll Animations with throttling and performance improvements
const animateOnScroll = () => {
    // Options for better performance
    const observerOptions = {
        root: null,
        rootMargin: '50px', // Start loading slightly before elements come into view
        threshold: 0.15
    };

    // Performance optimization: Use requestAnimationFrame
    const rafCallback = (element, shouldAnimate) => {
        requestAnimationFrame(() => {
            if (shouldAnimate) {
                element.classList.add('will-animate');
                // Trigger reflow
                void element.offsetWidth;
                element.classList.add('animate');
            }
        });
    };

    // Create observer with optimized callback
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Only trigger animation when element is entering viewport
            if (entry.isIntersecting) {
                const element = entry.target;
                rafCallback(element, true);
                // Stop observing after animation
                observer.unobserve(element);
            }
        });
    }, observerOptions);

    // Batch DOM operations
    const elements = document.querySelectorAll('[data-animation]');
    const fragment = document.createDocumentFragment();
    
    elements.forEach(element => {
        // Add to observer
        observer.observe(element);
        
        // Prevent Flash Of Unstyled Content
        element.style.visibility = 'visible';
    });

    // Function to handle animation on load
    const handleInitialAnimation = () => {
        elements.forEach(element => {
            if (element.getBoundingClientRect().top < window.innerHeight) {
                rafCallback(element, true);
                observer.unobserve(element);
            }
        });
    };

    // Optimize scroll performance
    let ticking = false;
    document.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleInitialAnimation();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Initial check for elements in viewport
    handleInitialAnimation();
};

// Scroll Progress Bar
const initScrollProgress = () => {
    const progressBar = document.querySelector('.progress-bar');
    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    });
};

// Back to Top Button
const initBackToTop = () => {
    const backToTop = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
};

// Mobile Menu
const initMobileMenu = () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    });
};

// Project Filters
const initProjectFilters = () => {
    const filterButtons = document.querySelectorAll('.project-filters button');
    const projects = document.querySelectorAll('.project-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;
            
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            projects.forEach(project => {
                const categories = project.dataset.categories.split(',');
                if (filter === 'all' || categories.includes(filter)) {
                    project.style.display = 'block';
                    setTimeout(() => project.style.opacity = '1', 10);
                } else {
                    project.style.opacity = '0';
                    setTimeout(() => project.style.display = 'none', 300);
                }
            });
        });
    });
};

// Copy Email Function
const initCopyEmail = () => {
    const copyButtons = document.querySelectorAll('.copy-email');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const email = button.dataset.email;
            try {
                await navigator.clipboard.writeText(email);
                button.querySelector('.tooltip').textContent = 'Copied!';
                setTimeout(() => {
                    button.querySelector('.tooltip').textContent = 'Click to copy';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        });
    });
};

// Lazy Loading Images
const initLazyLoading = () => {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
};

// Timeline Animation
const initTimeline = () => {
    const timelineItems = document.querySelectorAll('.timeline-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, {
        threshold: 0.1
    });

    timelineItems.forEach(item => observer.observe(item));
};

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    animateOnScroll();
    initScrollProgress();
    initBackToTop();
    initMobileMenu();
    initProjectFilters();
    initCopyEmail();
    initLazyLoading();
    initTimeline();
});
