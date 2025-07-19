// Simple nav active link highlight on scroll
const sections = document.querySelectorAll('section');
const navLinks = document.querySelectorAll('nav a');

window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(section => {
    const sectionTop = section.offsetTop - 80;
    if (pageYOffset >= sectionTop) {
      current = section.getAttribute('id');
    }
  });
  navLinks.forEach(link => {
    link.classList.remove('active');
    if (link.getAttribute('href') === '#' + current) {
      link.classList.add('active');
    }
  });
});

// Genshin Impact floating particles
window.addEventListener('DOMContentLoaded', function() {
  const PARTICLE_TYPES = ['star', 'sparkle', 'leaf'];
  const PARTICLE_COUNT = 18;
  const particlesContainer = document.querySelector('.particles');
  if (!particlesContainer) return;
  for (let i = 0; i < PARTICLE_COUNT; i++) {
    const type = PARTICLE_TYPES[Math.floor(Math.random() * PARTICLE_TYPES.length)];
    const particle = document.createElement('div');
    particle.className = 'particle ' + type;
    particle.style.left = Math.random() * 100 + 'vw';
    particle.style.bottom = '-' + (Math.random() * 20 + 5) + 'vh';
    particle.style.width = particle.style.height = (Math.random() * 16 + 16) + 'px';
    particle.style.animationDuration = (10 + Math.random() * 8) + 's';
    particle.style.animationDelay = (Math.random() * 8) + 's';
    particlesContainer.appendChild(particle);
  }
});

// Genshin Impact Vision selector theme switcher
window.addEventListener('DOMContentLoaded', function() {
  const visionButtons = document.querySelectorAll('.vision-select');
  const body = document.body;
  function setTheme(vision) {
    body.classList.remove(
      'theme-pyro', 'theme-hydro', 'theme-anemo', 'theme-electro', 'theme-dendro', 'theme-cryo', 'theme-geo'
    );
    body.classList.add('theme-' + vision);
    visionButtons.forEach(btn => btn.classList.remove('selected'));
    const selected = document.querySelector('.vision-select.' + vision);
    if (selected) selected.classList.add('selected');
    localStorage.setItem('genshin-theme', vision);
  }
  visionButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      setTheme(this.dataset.vision);
    });
  });
  // Restore last theme
  const saved = localStorage.getItem('genshin-theme');
  if (saved) setTheme(saved);
  else setTheme('geo'); // Default
});

// Genshin-style tooltips for skills and project cards
window.addEventListener('DOMContentLoaded', function() {
  let tooltip;
  function showTooltip(e) {
    const text = this.getAttribute('data-tooltip');
    if (!text) return;
    tooltip = document.createElement('div');
    tooltip.className = 'genshin-tooltip';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    positionTooltip(e);
    setTimeout(() => tooltip.classList.add('visible'), 10);
  }
  function hideTooltip() {
    if (tooltip) {
      tooltip.classList.remove('visible');
      setTimeout(() => tooltip && tooltip.remove(), 180);
      tooltip = null;
    }
  }
  function positionTooltip(e) {
    if (!tooltip) return;
    const padding = 12;
    let x = e.clientX + padding;
    let y = e.clientY - 10;
    const rect = tooltip.getBoundingClientRect();
    if (x + rect.width > window.innerWidth) x = window.innerWidth - rect.width - padding;
    if (y + rect.height > window.innerHeight) y = window.innerHeight - rect.height - padding;
    if (y < 0) y = e.clientY + padding;
    tooltip.style.left = x + 'px';
    tooltip.style.top = y + 'px';
  }
  document.querySelectorAll('[data-tooltip]').forEach(el => {
    el.addEventListener('mouseenter', showTooltip);
    el.addEventListener('mousemove', positionTooltip);
    el.addEventListener('mouseleave', hideTooltip);
    el.addEventListener('focus', showTooltip);
    el.addEventListener('blur', hideTooltip);
  });
});

// Genshin Impact Wish animation on contact form submit
window.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('#contact form');
  const wishOverlay = document.querySelector('.wish-animation');
  if (!form || !wishOverlay) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    wishOverlay.innerHTML = '';
    wishOverlay.classList.add('active');
    // Create star and burst
    const star = document.createElement('div');
    star.className = 'wish-star';
    const burst = document.createElement('div');
    burst.className = 'wish-burst';
    wishOverlay.appendChild(star);
    wishOverlay.appendChild(burst);
    setTimeout(() => {
      wishOverlay.classList.remove('active');
      wishOverlay.innerHTML = '';
      // Optionally, reset the form or show a message here
    }, 1800);
  });
});

// Genshin UI sound effects for nav and button clicks
window.addEventListener('DOMContentLoaded', function() {
  const navSound = document.getElementById('ui-nav-sound');
  const btnSound = document.getElementById('ui-btn-sound');
  const soundToggle = document.querySelector('.sound-toggle');
  let muted = localStorage.getItem('genshin-muted') === 'true';
  function updateMute() {
    navSound.muted = btnSound.muted = muted;
    if (muted) soundToggle.classList.add('muted');
    else soundToggle.classList.remove('muted');
    localStorage.setItem('genshin-muted', muted);
  }
  updateMute();
  soundToggle.addEventListener('click', function() {
    muted = !muted;
    updateMute();
  });
  document.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', function() {
      if (!muted) {
        navSound.currentTime = 0;
        navSound.play();
      }
    });
  });
  document.querySelectorAll('button, .project-card').forEach(btn => {
    btn.addEventListener('click', function() {
      if (!muted) {
        btnSound.currentTime = 0;
        btnSound.play();
      }
    });
  });
});

// Smooth fade-in for project cards on scroll/load
window.addEventListener('DOMContentLoaded', function() {
  const cards = document.querySelectorAll('.project-card');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    cards.forEach(card => observer.observe(card));
  } else {
    // Fallback: show all
    cards.forEach(card => card.classList.add('visible'));
  }
});
