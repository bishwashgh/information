// Proof-of-Contact Puzzle System

class PuzzleContactSystem {
  constructor() {
    this.currentChallenge = null;
    this.attempts = 0;
    this.maxAttempts = 5;
    this.init();
  }

  init() {
    this.generateNewChallenge();
    this.initializeEventListeners();
    this.createFloatingParticles();
  }

  // Generate new crypto challenge
  generateNewChallenge() {
    const challenges = [
      {
        type: 'Hash Decoder',
        difficulty: 'Medium',
        targetHash: '0x7A3F9B2E',
        clues: [
          'Think about the year 2024',
          'Consider the month of your birth',
          'Add the day you were born',
          'Multiply by 2 and add 10'
        ],
        hint: 'The answer is a hexadecimal number. Try calculating: (2024 + birth_month + birth_day) * 2 + 10',
        solution: this.calculateSolution()
      },
      {
        type: 'Pattern Breaker',
        difficulty: 'Hard',
        targetHash: '0x4F2A8C1D',
        clues: [
          'Look at the sequence: 1, 3, 6, 10, 15',
          'Find the next number in the pattern',
          'Convert to hexadecimal',
          'Add 0x prefix'
        ],
        hint: 'This is a triangular number sequence. The next number is 21, which is 0x15 in hex.',
        solution: '0x15'
      },
      {
        type: 'Crypto Cipher',
        difficulty: 'Easy',
        targetHash: '0xDEADBEEF',
        clues: [
          'Decode: 68 65 6C 6C 6F',
          'Convert from ASCII to hex',
          'Reverse the bytes',
          'Add 0x prefix'
        ],
        hint: '68 65 6C 6C 6F spells "hello" in ASCII. Reversed and converted to hex.',
        solution: '0x6F6C6C6568'
      }
    ];

    this.currentChallenge = challenges[Math.floor(Math.random() * challenges.length)];
    this.updateChallengeDisplay();
  }

  // Calculate solution based on current date
  calculateSolution() {
    const now = new Date();
    const year = 2024;
    const month = now.getMonth() + 1; // January is 0
    const day = now.getDate();
    
    const calculation = (year + month + day) * 2 + 10;
    return '0x' + calculation.toString(16).toUpperCase();
  }

  // Update challenge display
  updateChallengeDisplay() {
    const targetHash = document.getElementById('target-hash');
    const challengeType = document.querySelector('.challenge-type');
    const challengeDifficulty = document.querySelector('.challenge-difficulty');
    const clueList = document.querySelector('.clue-list');
    const hintText = document.getElementById('hint-text');

    targetHash.textContent = this.currentChallenge.targetHash;
    challengeType.textContent = this.currentChallenge.type;
    challengeDifficulty.textContent = this.currentChallenge.difficulty;
    hintText.textContent = this.currentChallenge.hint;

    // Update clues
    clueList.innerHTML = '';
    this.currentChallenge.clues.forEach(clue => {
      const li = document.createElement('li');
      li.textContent = clue;
      clueList.appendChild(li);
    });
  }

  // Initialize event listeners
  initializeEventListeners() {
    // Puzzle answer input
    const puzzleInput = document.getElementById('puzzle-answer');
    puzzleInput.addEventListener('keypress', (e) => this.handlePuzzleKey(e));
    puzzleInput.addEventListener('input', (e) => this.formatHexInput(e));

    // Contact form
    const contactForm = document.getElementById('contact-form');
    contactForm.addEventListener('submit', (e) => this.submitContact(e));
  }

  // Handle puzzle key press
  handlePuzzleKey(event) {
    if (event.key === 'Enter') {
      this.checkPuzzleAnswer();
    }
  }

  // Format hex input
  formatHexInput(event) {
    let value = event.target.value.toUpperCase();
    
    // Remove non-hex characters
    value = value.replace(/[^0-9A-F]/g, '');
    
    // Add 0x prefix if not present
    if (value && !value.startsWith('0X')) {
      value = '0x' + value;
    }
    
    event.target.value = value;
  }

  // Check puzzle answer
  checkPuzzleAnswer() {
    const answer = document.getElementById('puzzle-answer').value.toUpperCase();
    const feedback = document.getElementById('puzzle-feedback');
    
    if (answer === this.currentChallenge.solution) {
      // Correct answer
      this.showSuccessFeedback();
      this.playSound('success');
      setTimeout(() => {
        this.unlockContactForm();
      }, 2000);
    } else {
      // Wrong answer
      this.attempts++;
      this.showErrorFeedback();
      this.playSound('error');
      
      if (this.attempts >= this.maxAttempts) {
        this.showMaxAttemptsMessage();
      }
    }
  }

  // Show success feedback
  showSuccessFeedback() {
    const feedback = document.getElementById('puzzle-feedback');
    feedback.className = 'puzzle-feedback success';
    feedback.innerHTML = `
      <span>✅ Correct! Access granted...</span>
    `;
    feedback.classList.remove('hidden');
  }

  // Show error feedback
  showErrorFeedback() {
    const feedback = document.getElementById('puzzle-feedback');
    feedback.className = 'puzzle-feedback error';
    feedback.innerHTML = `
      <span>❌ Incorrect. Attempts remaining: ${this.maxAttempts - this.attempts}</span>
    `;
    feedback.classList.remove('hidden');
    
    // Clear input
    document.getElementById('puzzle-answer').value = '';
    
    // Hide feedback after 3 seconds
    setTimeout(() => {
      feedback.classList.add('hidden');
    }, 3000);
  }

  // Show max attempts message
  showMaxAttemptsMessage() {
    const feedback = document.getElementById('puzzle-feedback');
    feedback.className = 'puzzle-feedback error';
    feedback.innerHTML = `
      <span>🚫 Maximum attempts reached. Generating new challenge...</span>
    `;
    feedback.classList.remove('hidden');
    
    setTimeout(() => {
      this.resetPuzzle();
    }, 3000);
  }

  // Unlock contact form
  unlockContactForm() {
    const puzzleSection = document.getElementById('puzzle-section');
    const contactSection = document.getElementById('contact-section');
    
    // Hide puzzle section
    puzzleSection.style.animation = 'fadeOut 0.5s ease-out';
    setTimeout(() => {
      puzzleSection.classList.add('hidden');
    }, 500);
    
    // Show contact section
    setTimeout(() => {
      contactSection.classList.remove('hidden');
      contactSection.style.animation = 'fadeIn 0.5s ease-out';
    }, 600);
    
    this.playSound('unlock');
  }

  // Submit contact form
  submitContact(event) {
    event.preventDefault();
    
    const formData = {
      name: document.getElementById('contact-name').value,
      email: document.getElementById('contact-email').value,
      subject: document.getElementById('contact-subject').value,
      message: document.getElementById('contact-message').value,
      puzzleSolved: true,
      challengeType: this.currentChallenge.type,
      attempts: this.attempts,
      timestamp: new Date().toISOString()
    };
    
    // Add hidden message for puzzle solvers
    const hiddenMessage = `🧠 Genius Contacted You - Puzzle: ${this.currentChallenge.type} (${this.attempts} attempts)`;
    formData.hiddenMessage = hiddenMessage;
    
    // Simulate form submission
    this.showSuccessModal();
    
    // In a real implementation, you would send this to your server
    console.log('Contact Form Data:', formData);
    
    // Reset form
    document.getElementById('contact-form').reset();
  }

  // Show success modal
  showSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.remove('hidden');
    this.playSound('success');
  }

  // Close success modal
  closeSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.classList.add('hidden');
  }

  // Get hint
  getHint() {
    const modal = document.getElementById('hint-modal');
    modal.classList.remove('hidden');
    this.playSound('click');
  }

  // Close hint modal
  closeHintModal() {
    const modal = document.getElementById('hint-modal');
    modal.classList.add('hidden');
  }

  // Generate new challenge
  newChallenge() {
    this.attempts = 0;
    this.generateNewChallenge();
    document.getElementById('puzzle-answer').value = '';
    document.getElementById('puzzle-feedback').classList.add('hidden');
    this.playSound('click');
  }

  // Reset puzzle
  resetPuzzle() {
    this.attempts = 0;
    this.generateNewChallenge();
    document.getElementById('puzzle-answer').value = '';
    document.getElementById('puzzle-feedback').classList.add('hidden');
    
    // Show puzzle section again
    const puzzleSection = document.getElementById('puzzle-section');
    const contactSection = document.getElementById('contact-section');
    
    contactSection.classList.add('hidden');
    puzzleSection.classList.remove('hidden');
    puzzleSection.style.animation = '';
    
    this.playSound('click');
  }

  // Create floating particles
  createFloatingParticles() {
    const particlesContainer = document.querySelector('.floating-particles');
    const particleCount = 8;
    
    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div');
      particle.style.cssText = `
        position: absolute;
        width: 2px;
        height: 2px;
        background: ${this.getRandomNeonColor()};
        border-radius: 50%;
        animation: float ${4 + Math.random() * 3}s ease-in-out infinite;
        animation-delay: ${Math.random() * 4}s;
        left: ${Math.random() * 100}%;
        top: ${Math.random() * 100}%;
      `;
      particlesContainer.appendChild(particle);
    }
  }

  getRandomNeonColor() {
    const colors = ['#00ffff', '#ff00ff', '#00ff00', '#ffd700'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

  // Play sound effects
  playSound(soundType) {
    const audio = document.getElementById(`${soundType}-sound`);
    if (audio) {
      audio.currentTime = 0;
      audio.play().catch(e => console.log('Audio play failed:', e));
    }
  }
}

// Global functions for HTML onclick handlers
function handlePuzzleKey(event) {
  if (event.key === 'Enter') {
    checkPuzzleAnswer();
  }
}

function checkPuzzleAnswer() {
  puzzleSystem.checkPuzzleAnswer();
}

function getHint() {
  puzzleSystem.getHint();
}

function newChallenge() {
  puzzleSystem.newChallenge();
}

function resetPuzzle() {
  puzzleSystem.resetPuzzle();
}

function submitContact(event) {
  puzzleSystem.submitContact(event);
}

function closeSuccessModal() {
  puzzleSystem.closeSuccessModal();
}

function closeHintModal() {
  puzzleSystem.closeHintModal();
}

// Initialize the puzzle system when DOM is loaded
let puzzleSystem;
document.addEventListener('DOMContentLoaded', () => {
  puzzleSystem = new PuzzleContactSystem();
});

// Add some Easter eggs
document.addEventListener('keydown', (e) => {
  // Konami code for instant unlock
  if (e.key === 'ArrowUp' && e.ctrlKey && e.altKey) {
    console.log('🎮 Konami code detected! Instant unlock!');
    puzzleSystem.unlockContactForm();
  }
  
  // Secret key combination
  if (e.key === 'h' && e.ctrlKey && e.shiftKey) {
    console.log('🔍 Secret hint activated!');
    puzzleSystem.getHint();
  }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
  @keyframes fadeOut {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(0.9); }
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
  }
`;
document.head.appendChild(style); 
