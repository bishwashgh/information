// NeonVerse - Enhanced JavaScript

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  initializeMobileOptimizations();
  initializeNeonEffects();
  initializeScrollAnimations();
  initializeProjectInteractions();
  initializeTimelineAnimations();
  initializeGames();
  initializeSoundSystem();
  initializeLightningSphere();
});

// Neon Effects Initialization
function initializeNeonEffects() {
  // Add floating particles dynamically
  createFloatingParticles();
  
  // Initialize neon text effects
  const neonTexts = document.querySelectorAll('.neon-text, .neon-title');
  neonTexts.forEach(text => {
    text.addEventListener('mouseenter', function() {
      this.style.animationPlayState = 'paused';
    });
    text.addEventListener('mouseleave', function() {
      this.style.animationPlayState = 'running';
    });
  });
}

// Create floating particles
function createFloatingParticles() {
  const particlesContainer = document.querySelector('.floating-particles');
  const isMobile = isMobileDevice();
  const isLowEnd = isLowEndDevice();
  
  // Reduce particles significantly for mobile
  const particleCount = isLowEnd ? 0 : (isMobile ? 2 : (window.innerWidth < 768 ? 5 : 10));
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.cssText = `
      position: absolute;
      width: 1px;
      height: 1px;
      background: ${getRandomNeonColor()};
      border-radius: 50%;
      animation: float ${isMobile ? '6s' : '8s'} ease-in-out infinite;
      animation-delay: ${Math.random() * 4}s;
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      will-change: transform;
    `;
    particlesContainer.appendChild(particle);
  }
}

function getRandomNeonColor() {
  const colors = ['#00ffff', '#ff00ff', '#8000ff', '#00ff00'];
  return colors[Math.floor(Math.random() * colors.length)];
}

// Scroll Animations
function initializeScrollAnimations() {
  // Check if device supports smooth animations
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isMobile = window.innerWidth < 768;
  
  if (prefersReducedMotion || isMobile) {
    // Add visible class immediately on mobile or for users who prefer reduced motion
    const animatedElements = document.querySelectorAll('.project-card, .timeline-item, .info-item');
    animatedElements.forEach(el => el.classList.add('visible'));
    return;
  }
  
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, observerOptions);
  
  // Observe elements for animation
  const animatedElements = document.querySelectorAll('.project-card, .timeline-item, .info-item');
  animatedElements.forEach(el => observer.observe(el));
}

// Project Interactions
function initializeProjectInteractions() {
  const projectCards = document.querySelectorAll('.project-card');
  
  projectCards.forEach(card => {
    const unlockBtn = card.querySelector('.unlock-btn');
    const projectType = card.dataset.project;
    
    unlockBtn.addEventListener('click', function(e) {
      e.preventDefault();
      unlockProject(projectType);
    });
    
    // Add hover effects
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-15px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });
}

function unlockProject(projectType) {
  const detailsContainer = document.getElementById('project-details');
  const projectData = getProjectData(projectType);
  
  detailsContainer.innerHTML = `
    <div class="project-detail-content">
      <h3>${projectData.title}</h3>
      <p>${projectData.description}</p>
      <div class="project-features">
        <h4>Key Features:</h4>
        <ul>
          ${projectData.features.map(feature => `<li>${feature}</li>`).join('')}
        </ul>
      </div>
      <div class="project-links">
        <a href="#" class="neon-link">View Demo</a>
        <a href="#" class="neon-link">Source Code</a>
      </div>
    </div>
  `;
  
  detailsContainer.style.display = 'block';
  detailsContainer.scrollIntoView({ behavior: 'smooth' });
  
  // Add glow effect
  detailsContainer.style.animation = 'neonPulse 1s ease-in-out';
  setTimeout(() => {
    detailsContainer.style.animation = '';
  }, 1000);
}

function getProjectData(projectType) {
  const projects = {
    calculator: {
      title: 'Neon Calculator',
      description: 'A fully functional calculator with cyberpunk neon styling and smooth animations. Features basic arithmetic operations with a futuristic interface.',
      features: [
        'Basic arithmetic operations (+, -, *, /)',
        'Neon cyberpunk design with glowing effects',
        'Responsive layout for all devices',
        'Smooth button animations and hover effects'
      ]
    },
    todo: {
      title: 'Task Manager',
      description: 'A dynamic todo list application with local storage functionality and neon cyberpunk aesthetics. Manage your tasks with style.',
      features: [
        'Add, edit, and delete tasks',
        'Local storage for data persistence',
        'Neon styling with hover animations',
        'Responsive design for mobile and desktop'
      ]
    },
    contact: {
      title: 'Contact Form',
      description: 'A PHP-powered contact form with email functionality and cyberpunk design. Includes form validation and security features.',
      features: [
        'PHP backend for email processing',
        'Form validation and error handling',
        'Neon cyberpunk styling',
        'Responsive design with accessibility features'
      ]
    }
  };
  
  return projects[projectType] || projects.calculator;
}

// Timeline Animations
function initializeTimelineAnimations() {
  const timelineItems = document.querySelectorAll('.timeline-item');
  
  timelineItems.forEach((item, index) => {
    item.addEventListener('mouseenter', function() {
      const marker = this.querySelector('.timeline-marker');
      marker.style.transform = 'translateX(-50%) scale(1.5)';
    });
    
    item.addEventListener('mouseleave', function() {
      const marker = this.querySelector('.timeline-marker');
      marker.style.transform = 'translateX(-50%) scale(1)';
    });
  });
}

// AI Assistant Functions
function toggleAI() {
  const aiChat = document.getElementById('ai-chat');
  const isHidden = aiChat.classList.contains('hidden');
  
  if (isHidden) {
    aiChat.classList.remove('hidden');
    aiChat.style.display = 'block';
    document.getElementById('ai-input').focus();
  } else {
    aiChat.classList.add('hidden');
    aiChat.style.display = 'none';
  }
}

function handleAIKey(event) {
  if (event.key === 'Enter') {
    sendAIMessage();
  }
}

function sendAIMessage() {
  const input = document.getElementById('ai-input');
  const messages = document.getElementById('chat-messages');
  const message = input.value.trim();
  
  if (!message) return;
  
  // Add user message
  addMessage('You', message, 'user');
  input.value = '';
  
  // Generate AI response
  setTimeout(() => {
    const response = generateAIResponse(message);
    addMessage('BSG AI', response, 'ai');
  }, 500);
}

function addMessage(sender, text, type) {
  const messages = document.getElementById('chat-messages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${type}-message`;
  messageDiv.innerHTML = `
    <strong>${sender}:</strong> ${text}
  `;
  messages.appendChild(messageDiv);
  messages.scrollTop = messages.scrollHeight;
}

function generateAIResponse(message) {
  const responses = {
    greeting: [
      "Welcome to BSGVerse! How can I assist you today?",
      "Greetings, digital traveler! What brings you to the BSG realm?",
      "Hello! I'm your AI guide through the cyberpunk multiverse."
    ],
    projects: [
      "Explore the Digital Constellations section to unlock project details!",
      "Each project is a unique digital experience waiting to be discovered.",
      "The projects showcase different aspects of modern web development."
    ],
    skills: [
      "I specialize in JavaScript, React, Node.js, and creative coding.",
      "My expertise includes frontend development, AI integration, and UI/UX design.",
      "I love creating immersive digital experiences with cutting-edge technologies."
    ],
    contact: [
      "Feel free to reach out through the BSG Network section!",
      "I'm always open to new opportunities and collaborations.",
      "Let's connect and create something amazing together!"
    ],
    default: [
      "That's an interesting question! Let me think about that...",
      "I'm constantly learning and evolving, just like the digital world.",
      "The BSG lights never stop glowing, and neither does my curiosity!"
    ]
  };
  
  message = message.toLowerCase();
  
  if (message.includes('hello') || message.includes('hi') || message.includes('hey')) {
    return getRandomResponse(responses.greeting);
  } else if (message.includes('project') || message.includes('work')) {
    return getRandomResponse(responses.projects);
  } else if (message.includes('skill') || message.includes('technology') || message.includes('tech')) {
    return getRandomResponse(responses.skills);
  } else if (message.includes('contact') || message.includes('reach') || message.includes('connect')) {
    return getRandomResponse(responses.contact);
  } else {
    return getRandomResponse(responses.default);
  }
}

function getRandomResponse(responses) {
  return responses[Math.floor(Math.random() * responses.length)];
}

// Smooth Scrolling for Navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

// Form Handling
document.querySelector('.send-btn').addEventListener('click', function() {
  const name = document.getElementById('name').value;
  const email = document.getElementById('email').value;
  const message = document.getElementById('message').value;
  
  if (name && email && message) {
    // Simulate form submission
    this.textContent = 'Message Sent!';
    this.style.background = '#00ff00';
    this.style.color = '#000';
    
    setTimeout(() => {
      this.textContent = 'Send to NeonVerse';
      this.style.background = '';
      this.style.color = '';
      document.getElementById('name').value = '';
      document.getElementById('email').value = '';
      document.getElementById('message').value = '';
    }, 2000);
  } else {
    this.style.animation = 'shake 0.5s ease-in-out';
    setTimeout(() => {
      this.style.animation = '';
    }, 500);
  }
});

// Add shake animation to CSS
const style = document.createElement('style');
style.textContent = `
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }
  
  .message {
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 5px;
  }
  
  .user-message {
    background: rgba(0, 255, 255, 0.1);
    border-left: 3px solid #00ffff;
  }
  
  .ai-message {
    background: rgba(255, 0, 255, 0.1);
    border-left: 3px solid #ff00ff;
  }
  
  .neon-link {
    color: #00ffff;
    text-decoration: none;
    margin-right: 15px;
    transition: all 0.3s ease;
  }
  
  .neon-link:hover {
    text-shadow: 0 0 5px #00ffff;
  }
  
  .project-detail-content {
    text-align: left;
  }
  
  .project-features ul {
    list-style: none;
    padding: 0;
  }
  
  .project-features li {
    padding: 5px 0;
    border-left: 2px solid #00ffff;
    padding-left: 15px;
    margin: 5px 0;
  }
 `;
document.head.appendChild(style);

// Sound System
let isMuted = false;
let audioContext = null;

function initializeSoundSystem() {
  // Initialize audio context
  try {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
  } catch (e) {
    console.log('Web Audio API not supported');
  }
  
  // Start ambient sound
  setTimeout(() => {
    playAmbientSound();
  }, 2000);
  
  // Add sound effects to interactive elements
  addSoundEffects();
}

function toggleMute() {
  isMuted = !isMuted;
  const muteBtn = document.getElementById('mute-btn');
  
  if (isMuted) {
    muteBtn.classList.add('muted');
    stopAllSounds();
  } else {
    muteBtn.classList.remove('muted');
    playAmbientSound();
  }
}

function playSound(soundType) {
  if (isMuted) return;
  
  const audio = document.getElementById(soundType + '-sound');
  if (audio) {
    audio.currentTime = 0;
    audio.play().catch(e => console.log('Audio play failed:', e));
  }
}

function playAmbientSound() {
  if (isMuted) return;
  
  const ambientSound = document.getElementById('ambient-sound');
  if (ambientSound) {
    ambientSound.volume = 0.1;
    ambientSound.play().catch(e => console.log('Ambient sound failed:', e));
  }
}

function stopAllSounds() {
  const allAudio = document.querySelectorAll('audio');
  allAudio.forEach(audio => {
    audio.pause();
    audio.currentTime = 0;
  });
}

function addSoundEffects() {
  // Navigation links
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('mouseenter', () => playSound('hover'));
    link.addEventListener('click', () => playSound('click'));
  });
  
  // Buttons
  document.querySelectorAll('.neon-button').forEach(btn => {
    btn.addEventListener('mouseenter', () => playSound('hover'));
    btn.addEventListener('click', () => playSound('click'));
  });
  
  // Project cards
  document.querySelectorAll('.project-card').forEach(card => {
    card.addEventListener('mouseenter', () => playSound('hover'));
  });
  
  // Game elements
  document.querySelectorAll('.cell').forEach(cell => {
    cell.addEventListener('click', () => playSound('click'));
  });
  
  // AI assistant
  document.querySelector('.assistant-icon').addEventListener('click', () => playSound('click'));
  
  // Form elements
  document.querySelectorAll('.neon-input').forEach(input => {
    input.addEventListener('focus', () => playSound('hover'));
  });
  
  // Timeline items
  document.querySelectorAll('.timeline-item').forEach(item => {
    item.addEventListener('mouseenter', () => playSound('hover'));
  });
  
  // Game buttons
  document.querySelectorAll('#jump-start, #jump-restart, #tictactoe-restart').forEach(btn => {
    btn.addEventListener('click', () => playSound('game'));
  });
}

// Enhanced unlockProject function with sound
const originalUnlockProject = unlockProject;
function unlockProject(projectType) {
  playSound('click');
  originalUnlockProject(projectType);
}

// Game Initialization
function initializeGames() {
  initializeTicTacToe();
  initializeJumpGame();
}

// Tic-Tac-Toe Game
function initializeTicTacToe() {
  const board = document.getElementById('tictactoe-board');
  const cells = document.querySelectorAll('[data-cell]');
  const status = document.getElementById('tictactoe-status');
  const restartBtn = document.getElementById('tictactoe-restart');
  
  let currentPlayer = 'X';
  let gameActive = true;
  let gameState = ['', '', '', '', '', '', '', '', ''];
  
  const winningConditions = [
    [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
    [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
    [0, 4, 8], [2, 4, 6] // Diagonals
  ];
  
  function handleCellClick(e) {
    const cell = e.target;
    const cellIndex = Array.from(cells).indexOf(cell);
    
    if (gameState[cellIndex] !== '' || !gameActive) return;
    
    gameState[cellIndex] = currentPlayer;
    cell.textContent = currentPlayer;
    cell.classList.add(currentPlayer.toLowerCase());
    
    if (checkWin()) {
      gameActive = false;
      status.textContent = `Player ${currentPlayer} wins!`;
      highlightWinningCells();
      return;
    }
    
    if (checkDraw()) {
      gameActive = false;
      status.textContent = "Game ended in a draw!";
      return;
    }
    
    currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
    status.textContent = `Player ${currentPlayer}'s turn`;
  }
  
  function checkWin() {
    return winningConditions.some(condition => {
      return condition.every(index => {
        return gameState[index] === currentPlayer;
      });
    });
  }
  
  function checkDraw() {
    return gameState.every(cell => cell !== '');
  }
  
  function highlightWinningCells() {
    winningConditions.forEach(condition => {
      if (condition.every(index => gameState[index] === currentPlayer)) {
        condition.forEach(index => {
          cells[index].classList.add('winning');
        });
      }
    });
  }
  
  function restartGame() {
    currentPlayer = 'X';
    gameActive = true;
    gameState = ['', '', '', '', '', '', '', '', ''];
    status.textContent = `Player ${currentPlayer}'s turn`;
    
    cells.forEach(cell => {
      cell.textContent = '';
      cell.classList.remove('x', 'o', 'winning');
    });
  }
  
  cells.forEach(cell => cell.addEventListener('click', handleCellClick));
  restartBtn.addEventListener('click', restartGame);
}

// Aggressive Mobile Performance Optimizations

// Detect mobile and low-end devices
function isMobileDevice() {
  return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function isLowEndDevice() {
  return window.innerWidth <= 480 || navigator.hardwareConcurrency <= 2;
}

// Disable heavy features on mobile
function initializeMobileOptimizations() {
  const isMobile = isMobileDevice();
  const isLowEnd = isLowEndDevice();
  
  if (isMobile) {
    // Disable lightning sphere completely on mobile
    const lightningSphere = document.querySelector('.lightning-sphere');
    if (lightningSphere) {
      lightningSphere.style.display = 'none';
    }
    
    // Disable particle creation
    const particlesContainer = document.querySelector('.floating-particles');
    if (particlesContainer) {
      particlesContainer.innerHTML = '';
    }
    
    // Optimize game performance
    const jumpGame = document.getElementById('jump-game');
    if (jumpGame) {
      jumpGame.style.imageRendering = 'pixelated';
    }
  }
  
  if (isLowEnd) {
    // Disable all animations for low-end devices
    const style = document.createElement('style');
    style.textContent = `
      * {
        animation: none !important;
        transition: none !important;
      }
    `;
    document.head.appendChild(style);
  }
}

// Optimize lightning sphere for mobile
function initializeLightningSphere() {
  const canvas = document.getElementById('lightning-sphere-canvas');
  if (!canvas) return;
  
  const isMobile = isMobileDevice();
  const isLowEnd = isLowEndDevice();
  
  // Completely disable on mobile
  if (isMobile) {
    canvas.style.display = 'none';
    return;
  }
  
  const ctx = canvas.getContext('2d');
  const w = canvas.width;
  const h = canvas.height;
  const centerX = w / 2;
  const centerY = h / 2;
  const radius = 75;
  
  function drawSphere() {
    ctx.clearRect(0, 0, w, h);
    
    // Core sphere only
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius - 8, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(0,30,40,0.95)';
    ctx.fill();
  }

  function drawLightning() {
    // Minimal lightning for performance
    const bolts = isLowEnd ? 1 : 2;
    
    for (let i = 0; i < bolts; i++) {
      const angle = Math.random() * Math.PI * 2;
      const boltLength = 30;
      const startX = centerX + Math.cos(angle) * (radius - 18);
      const startY = centerY + Math.sin(angle) * (radius - 18);
      
      ctx.save();
      ctx.strokeStyle = '#00ffff';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(centerX, centerY);
      ctx.lineTo(startX, startY);
      ctx.stroke();
      ctx.restore();
    }
  }

  function animate() {
    drawSphere();
    drawLightning();
    
    // Lower frame rate for performance
    setTimeout(() => {
      requestAnimationFrame(animate);
    }, 50); // 20fps for better performance
  }
  
  animate();
}

// Optimize particle creation for mobile
function createFloatingParticles() {
  const particlesContainer = document.querySelector('.floating-particles');
  const isMobile = isMobileDevice();
  const isLowEnd = isLowEndDevice();
  
  // No particles on mobile or low-end devices
  if (isMobile || isLowEnd) {
    particlesContainer.innerHTML = '';
    return;
  }
  
  const particleCount = window.innerWidth < 768 ? 3 : 5;
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.cssText = `
      position: absolute;
      width: 1px;
      height: 1px;
      background: ${getRandomNeonColor()};
      border-radius: 50%;
      animation: float 8s ease-in-out infinite;
      animation-delay: ${Math.random() * 4}s;
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      will-change: transform;
    `;
    particlesContainer.appendChild(particle);
  }
}

// Optimize jump game for mobile
function initializeJumpGame() {
  const canvas = document.getElementById('jump-game');
  const ctx = canvas.getContext('2d');
  const startBtn = document.getElementById('jump-start');
  const restartBtn = document.getElementById('jump-restart');
  const scoreDisplay = document.getElementById('jump-score');
  const highScoreDisplay = document.getElementById('jump-high-score');
  
  const isMobile = isMobileDevice();
  
  let gameRunning = false;
  let animationId;
  let score = 0;
  let highScore = localStorage.getItem('jumpHighScore') || 0;
  
  // Game objects
  const player = {
    x: 50,
    y: canvas.height - 60,
    width: 30,
    height: 30,
    velocityY: 0,
    jumping: false
  };
  
  const obstacles = [];
  const gravity = 0.8;
  const jumpForce = -15;
  const groundY = canvas.height - 60;
  
  highScoreDisplay.textContent = highScore;
  
  function drawPlayer() {
    ctx.fillStyle = '#00ffff';
    // Remove shadow for mobile performance
    if (!isMobile) {
      ctx.shadowColor = '#00ffff';
      ctx.shadowBlur = 10;
    }
    ctx.fillRect(player.x, player.y, player.width, player.height);
    if (!isMobile) {
      ctx.shadowBlur = 0;
    }
  }
  
  function drawObstacle(obstacle) {
    ctx.fillStyle = '#ffa500';
    // Remove shadow for mobile performance
    if (!isMobile) {
      ctx.shadowColor = '#ffa500';
      ctx.shadowBlur = 10;
    }
    ctx.fillRect(obstacle.x, obstacle.y, obstacle.width, obstacle.height);
    if (!isMobile) {
      ctx.shadowBlur = 0;
    }
  }
  
  function drawGround() {
    ctx.fillStyle = '#333';
    ctx.fillRect(0, groundY + player.height, canvas.width, canvas.height - groundY - player.height);
  }
  
  function updatePlayer() {
    player.velocityY += gravity;
    player.y += player.velocityY;
    
    if (player.y >= groundY) {
      player.y = groundY;
      player.velocityY = 0;
      player.jumping = false;
    }
  }
  
  function updateObstacles() {
    for (let i = obstacles.length - 1; i >= 0; i--) {
      const obstacle = obstacles[i];
      obstacle.x -= 5;
      
      if (obstacle.x + obstacle.width < 0) {
        obstacles.splice(i, 1);
        score++;
        scoreDisplay.textContent = score;
      }
    }
    
    // Spawn new obstacles
    if (Math.random() < 0.02) {
      obstacles.push({
        x: canvas.width,
        y: groundY,
        width: 20,
        height: 40
      });
    }
  }
  
  function checkCollision() {
    for (const obstacle of obstacles) {
      if (player.x < obstacle.x + obstacle.width &&
          player.x + player.width > obstacle.x &&
          player.y < obstacle.y + obstacle.height &&
          player.y + player.height > obstacle.y) {
        return true;
      }
    }
    return false;
  }
  
  function jump() {
    if (!player.jumping) {
      player.velocityY = jumpForce;
      player.jumping = true;
    }
  }
  
  function gameLoop() {
    if (!gameRunning) return;
    
    // Clear canvas
    ctx.fillStyle = '#0a0a0a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    drawGround();
    drawPlayer();
    
    for (const obstacle of obstacles) {
      drawObstacle(obstacle);
    }
    
    updatePlayer();
    updateObstacles();
    
    if (checkCollision()) {
      gameOver();
      return;
    }
    
    // Lower frame rate for mobile
    const frameDelay = isMobile ? 33 : 16;
    setTimeout(() => {
      requestAnimationFrame(gameLoop);
    }, frameDelay);
  }
  
  function gameOver() {
    gameRunning = false;
    if (score > highScore) {
      highScore = score;
      localStorage.setItem('jumpHighScore', highScore);
      highScoreDisplay.textContent = highScore;
    }
    alert(`Game Over! Score: ${score}`);
  }
  
  function startGame() {
    if (gameRunning) return;
    
    gameRunning = true;
    score = 0;
    scoreDisplay.textContent = score;
    obstacles.length = 0;
    player.y = groundY;
    player.velocityY = 0;
    player.jumping = false;
    
    gameLoop();
  }
  
  function restartGame() {
    gameOver();
    startGame();
  }
  
  // Event listeners
  startBtn.addEventListener('click', startGame);
  restartBtn.addEventListener('click', restartGame);
  
  // Touch/click to jump
  canvas.addEventListener('click', jump);
  canvas.addEventListener('touchstart', (e) => {
    e.preventDefault();
    jump();
  });
  
  // Keyboard support
  document.addEventListener('keydown', (e) => {
    if (e.code === 'Space') {
      e.preventDefault();
      jump();
    }
  });
}
  
// CyberMatrix Password Functions
function openCyberMatrixModal() {
  const modal = document.getElementById('cybermatrix-modal');
  modal.classList.remove('hidden');
  document.getElementById('password-input').focus();
  playSound('click');
}

function closeCyberMatrixModal() {
  const modal = document.getElementById('cybermatrix-modal');
  modal.classList.add('hidden');
  document.getElementById('password-input').value = '';
  document.getElementById('password-error').classList.add('hidden');
  playSound('click');
}

function handlePasswordKey(event) {
  if (event.key === 'Enter') {
    checkPassword();
  } else if (event.key === 'Escape') {
    closeCyberMatrixModal();
  }
}

function checkPassword() {
  const password = document.getElementById('password-input').value;
  const errorDiv = document.getElementById('password-error');
  const modalContent = document.querySelector('.cybermatrix-modal .modal-content');
  
  if (password === '231511') {
    // Correct password
    modalContent.classList.add('access-granted');
    playSound('victory');
    
    // Show success message
    const successMessage = document.createElement('div');
    successMessage.innerHTML = `
      <div style="text-align: center; color: var(--neon-green); font-family: 'Orbitron', sans-serif; margin: 20px 0;">
        <div style="font-size: 2rem; margin-bottom: 10px;">✅</div>
        <div style="font-size: 1.2rem; margin-bottom: 10px;">Access Granted!</div>
        <div style="font-size: 0.9rem; color: var(--neon-cyan);">Initializing CyberMatrix...</div>
      </div>
    `;
    
    // Replace modal content with success message
    const modalBody = document.querySelector('.modal-body');
    modalBody.innerHTML = '';
    modalBody.appendChild(successMessage);
    
    // Redirect to CyberMatrix after delay
    setTimeout(() => {
      window.open('cybermatrix.html', '_blank');
      closeCyberMatrixModal();
    }, 2000);
    
  } else {
    // Wrong password
    errorDiv.classList.remove('hidden');
    playSound('game');
    
    // Clear input and shake effect
    document.getElementById('password-input').value = '';
    modalContent.style.animation = 'errorShake 0.5s ease-in-out';
    setTimeout(() => {
      modalContent.style.animation = '';
    }, 500);
  }
}

// Mobile Menu Toggle
function toggleMobileMenu() {
  const mobileToggle = document.querySelector('.mobile-menu-toggle');
  const navLinks = document.querySelector('.nav-links');
  
  mobileToggle.classList.toggle('active');
  navLinks.classList.toggle('active');
  
  // Play sound effect
  if (!isMuted) {
    playSound('click-sound');
  }
}

// Close mobile menu when clicking on a link
document.addEventListener('DOMContentLoaded', function() {
  const navLinks = document.querySelectorAll('.nav-link');
  
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      const mobileToggle = document.querySelector('.mobile-menu-toggle');
      const navMenu = document.querySelector('.nav-links');
      
      // Close mobile menu if open
      if (mobileToggle.classList.contains('active')) {
        mobileToggle.classList.remove('active');
        navMenu.classList.remove('active');
      }
    });
  });
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', function(event) {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-links');
    
    if (!event.target.closest('.neon-nav') && navMenu.classList.contains('active')) {
      mobileToggle.classList.remove('active');
      navMenu.classList.remove('active');
    }
  });
});
  
