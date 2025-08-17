
// Remove preload class after initial render for smooth transitions
window.addEventListener('load', () => document.body.classList.remove('preload'));

// Theme toggle with localStorage
const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
const root = document.documentElement;
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
  root.classList.add('light');
}
const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
  themeBtn.addEventListener('click', () => {
    root.classList.toggle('light');
    localStorage.setItem('theme', root.classList.contains('light') ? 'light' : 'dark');
  });
}

// Mobile nav
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('navLinks');
if (hamburger && navLinks) {
  hamburger.addEventListener('click', () => {
    const open = navLinks.style.display === 'flex';
    navLinks.style.display = open ? 'none' : 'flex';
    hamburger.setAttribute('aria-expanded', (!open).toString());
  });
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click', e=>{
    const id = a.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) {
      e.preventDefault();
      el.scrollIntoView({behavior:'smooth', block:'start'});
    }
  });
});

// Typewriter effect
const lines = [
  "Building delightful web experiences.",
  "Clean design. Modern code. Fast sites.",
  "Available for collaborations and projects."
];
let idx = 0, char = 0;
const tw = document.getElementById('typewriter');
function type() {
  if (!tw) return;
  if (char <= lines[idx].length) {
    tw.textContent = lines[idx].slice(0, char++);
  } else {
    setTimeout(()=>{
      char = 0;
      idx = (idx + 1) % lines.length;
      tw.textContent = "";
    }, 1200);
  }
  setTimeout(type, 50);
}
type();

// Filterable projects
const chips = document.querySelectorAll('.chip');
const cards = document.querySelectorAll('.project');
chips.forEach(chip => chip.addEventListener('click', ()=>{
  chips.forEach(c=>c.classList.remove('active'));
  chip.classList.add('active');
  const tag = chip.dataset.filter;
  cards.forEach(card=>{
    const tags = card.dataset.tags.split(" ");
    card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
  });
}));

// Testimonials slider
const slides = document.querySelectorAll('.slide');
let slideIndex = 0;
function showSlide(i){
  slides.forEach(s=>s.classList.remove('active'));
  slides[i].classList.add('active');
}
document.querySelector('.slider .next').addEventListener('click', ()=>{
  slideIndex = (slideIndex + 1) % slides.length;
  showSlide(slideIndex);
});
document.querySelector('.slider .prev').addEventListener('click', ()=>{
  slideIndex = (slideIndex - 1 + slides.length) % slides.length;
  showSlide(slideIndex);
});

// Matrix background (subtle)
const canvas = document.getElementById('matrix');
if (canvas) {
  const ctx = canvas.getContext('2d');
  let w, h, cols, ypos;
  const resize = () => {
    w = canvas.width = window.innerWidth;
    h = canvas.height = document.querySelector('.hero').offsetHeight;
    cols = Math.floor(w / 20);
    ypos = Array(cols).fill(0);
  };
  window.addEventListener('resize', resize);
  resize();
  function draw(){
    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bg');
    ctx.globalAlpha = 0.08;
    ctx.fillRect(0,0,w,h);
    ctx.globalAlpha = 1;
    ctx.fillStyle = '#26A69A';
    ctx.font = '14pt monospace';
    for(let i=0; i<ypos.length; i++){
      const text = String.fromCharCode(0x30A0 + Math.random() * 96);
      ctx.fillText(text, i*20, ypos[i]*18);
      if (ypos[i]*18 > h || Math.random() > 0.975) ypos[i] = 0;
      ypos[i]++;
    }
    requestAnimationFrame(draw);
  }
  draw();
}

// Init AOS
AOS.init({ once:true, duration:700, easing:'ease-out-quad' });
