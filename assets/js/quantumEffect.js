const cosmicBg = document.getElementById('cosmic-bg');

function createStar() {
  const star = document.createElement('div');
  star.classList.add('star');
  star.style.top = Math.random() * 100 + 'vh';
  star.style.left = Math.random() * 100 + 'vw';
  star.style.width = star.style.height = (Math.random() * 2 + 1) + 'px';
  star.style.animationDuration = (Math.random() * 5 + 3) + 's';
  cosmicBg.appendChild(star);

  setTimeout(() => {
    star.remove();
  }, 8000);
}

// Continuously add stars
setInterval(createStar, 200);

