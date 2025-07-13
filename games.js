// BSG Games - Dedicated Games File
console.log('🎮 Loading BSG Games...');

// Tic-Tac-Toe Game
class TicTacToe {
  constructor() {
    this.board = document.getElementById('tictactoe-board');
    this.status = document.getElementById('tictactoe-status');
    this.restartBtn = document.getElementById('tictactoe-restart');
    this.cells = [];
    this.currentPlayer = 'X';
    this.gameActive = true;
    this.gameState = ['', '', '', '', '', '', '', '', ''];
    
    this.winningConditions = [
      [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
      [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
      [0, 4, 8], [2, 4, 6] // Diagonals
    ];
    
    this.init();
  }
  
  init() {
    console.log('🎯 Initializing Tic-Tac-Toe...');
    
    if (!this.board) {
      console.error('❌ Tic-Tac-Toe board not found!');
      return;
    }
    
    // Get all cells
    this.cells = this.board.querySelectorAll('[data-cell]');
    console.log('✅ Found', this.cells.length, 'cells');
    
    if (this.cells.length === 0) {
      console.error('❌ No cells found!');
      return;
    }
    
    // Add click listeners
    this.cells.forEach((cell, index) => {
      cell.addEventListener('click', (e) => this.handleCellClick(e, index));
      cell.style.cursor = 'pointer';
      console.log(`✅ Cell ${index} listener added`);
    });
    
    // Add restart button listener
    if (this.restartBtn) {
      this.restartBtn.addEventListener('click', () => this.restartGame());
      console.log('✅ Restart button listener added');
    }
    
    console.log('🎯 Tic-Tac-Toe initialized successfully!');
  }
  
  handleCellClick(e, index) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log(`🎯 Cell ${index} clicked by player ${this.currentPlayer}`);
    
    // Visual feedback
    this.cells[index].style.backgroundColor = 'rgba(0, 255, 0, 0.3)';
    setTimeout(() => {
      this.cells[index].style.backgroundColor = '';
    }, 300);
    
    // Check if cell is already filled
    if (this.gameState[index] !== '' || !this.gameActive) {
      console.log('❌ Cell already filled or game not active');
      return;
    }
    
    // Update game state
    this.gameState[index] = this.currentPlayer;
    this.cells[index].textContent = this.currentPlayer;
    this.cells[index].classList.add(this.currentPlayer.toLowerCase());
    
    console.log(`✅ Cell ${index} updated with ${this.currentPlayer}`);
    
    // Check for win
    if (this.checkWin()) {
      this.gameActive = false;
      if (this.status) {
        this.status.textContent = `Player ${this.currentPlayer} wins! 🎉`;
        this.status.style.color = '#00ff00';
      }
      this.highlightWinningCells();
      return;
    }
    
    // Check for draw
    if (this.checkDraw()) {
      this.gameActive = false;
      if (this.status) {
        this.status.textContent = "Game ended in a draw! 🤝";
        this.status.style.color = '#ffff00';
      }
      return;
    }
    
    // Switch player
    this.currentPlayer = this.currentPlayer === 'X' ? 'O' : 'X';
    if (this.status) {
      this.status.textContent = `Player ${this.currentPlayer}'s turn`;
      this.status.style.color = this.currentPlayer === 'X' ? '#00ffff' : '#ff00ff';
    }
  }
  
  checkWin() {
    return this.winningConditions.some(condition => {
      return condition.every(index => {
        return this.gameState[index] === this.currentPlayer;
      });
    });
  }
  
  checkDraw() {
    return this.gameState.every(cell => cell !== '');
  }
  
  highlightWinningCells() {
    this.winningConditions.forEach(condition => {
      if (condition.every(index => this.gameState[index] === this.currentPlayer)) {
        condition.forEach(index => {
          this.cells[index].classList.add('winning');
        });
      }
    });
  }
  
  restartGame() {
    console.log('🔄 Restarting Tic-Tac-Toe...');
    this.currentPlayer = 'X';
    this.gameActive = true;
    this.gameState = ['', '', '', '', '', '', '', '', ''];
    
    this.cells.forEach(cell => {
      cell.textContent = '';
      cell.classList.remove('x', 'o', 'winning');
    });
    
    if (this.status) {
      this.status.textContent = `Player ${this.currentPlayer}'s turn`;
      this.status.style.color = '#00ffff';
    }
  }
}

// Jump Game
class JumpGame {
  constructor() {
    this.canvas = document.getElementById('jump-game');
    this.startBtn = document.getElementById('jump-start');
    this.restartBtn = document.getElementById('jump-restart');
    this.scoreDisplay = document.getElementById('jump-score');
    this.highScoreDisplay = document.getElementById('jump-high-score');
    
    this.ctx = null;
    this.gameRunning = false;
    this.playerY = 0;
    this.playerVelocity = 0;
    this.obstacles = [];
    this.score = 0;
    this.highScore = 0;
    
    this.init();
  }
  
  init() {
    console.log('🎮 Initializing Jump Game...');
    
    if (!this.canvas) {
      console.error('❌ Jump game canvas not found!');
      return;
    }
    
    this.ctx = this.canvas.getContext('2d');
    this.ctx.imageSmoothingEnabled = false;
    
    // Load high score
    this.highScore = localStorage.getItem('jumpHighScore') || 0;
    if (this.highScoreDisplay) {
      this.highScoreDisplay.textContent = this.highScore;
    }
    
    // Set initial player position
    this.playerY = this.canvas.height - 50;
    
    // Add event listeners
    this.canvas.addEventListener('click', () => this.jump());
    document.addEventListener('keydown', (e) => {
      if (e.code === 'Space') {
        e.preventDefault();
        this.jump();
      }
    });
    
    if (this.startBtn) {
      this.startBtn.addEventListener('click', () => this.startGame());
    }
    
    if (this.restartBtn) {
      this.restartBtn.addEventListener('click', () => this.restartGame());
    }
    
    console.log('✅ Jump Game initialized successfully!');
  }
  
  gameLoop() {
    if (!this.gameRunning) return;
    
    // Clear canvas
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    
    // Update player
    this.playerVelocity += 0.8;
    this.playerY += this.playerVelocity;
    
    if (this.playerY > this.canvas.height - 50) {
      this.playerY = this.canvas.height - 50;
      this.playerVelocity = 0;
    }
    
    // Draw player (BSG cube)
    this.ctx.fillStyle = '#00ffff';
    this.ctx.shadowColor = '#00ffff';
    this.ctx.shadowBlur = 10;
    this.ctx.fillRect(50, this.playerY, 30, 30);
    this.ctx.shadowBlur = 0;
    
    // Update and draw obstacles
    this.obstacles.forEach((obstacle, index) => {
      obstacle.x -= 3;
      this.ctx.fillStyle = '#ff00ff';
      this.ctx.shadowColor = '#ff00ff';
      this.ctx.shadowBlur = 5;
      this.ctx.fillRect(obstacle.x, obstacle.y, obstacle.width, obstacle.height);
      this.ctx.shadowBlur = 0;
      
      if (obstacle.x + obstacle.width < 0) {
        this.obstacles.splice(index, 1);
        this.score++;
        if (this.scoreDisplay) {
          this.scoreDisplay.textContent = this.score;
        }
      }
    });
    
    // Generate obstacles
    if (Math.random() < 0.02) {
      this.obstacles.push({
        x: this.canvas.width,
        y: this.canvas.height - 40,
        width: 20,
        height: 40
      });
    }
    
    // Check collision
    this.obstacles.forEach(obstacle => {
      if (50 < obstacle.x + obstacle.width &&
          50 + 30 > obstacle.x &&
          this.playerY < obstacle.y + obstacle.height &&
          this.playerY + 30 > obstacle.y) {
        this.gameOver();
      }
    });
    
    requestAnimationFrame(() => this.gameLoop());
  }
  
  jump() {
    if (this.playerY >= this.canvas.height - 50) {
      this.playerVelocity = -12;
      console.log('🦘 Jump!');
    }
  }
  
  startGame() {
    console.log('🎮 Starting Jump Game...');
    this.gameRunning = true;
    this.score = 0;
    this.obstacles = [];
    this.playerY = this.canvas.height - 50;
    this.playerVelocity = 0;
    
    if (this.scoreDisplay) {
      this.scoreDisplay.textContent = '0';
    }
    
    this.gameLoop();
  }
  
  gameOver() {
    this.gameRunning = false;
    console.log('💀 Game Over! Score:', this.score);
    
    if (this.score > this.highScore) {
      this.highScore = this.score;
      localStorage.setItem('jumpHighScore', this.highScore);
      if (this.highScoreDisplay) {
        this.highScoreDisplay.textContent = this.highScore;
      }
    }
  }
  
  restartGame() {
    this.startGame();
  }
}

// Initialize games when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  console.log('🎮 DOM loaded, initializing games...');
  
  // Initialize Tic-Tac-Toe
  setTimeout(() => {
    window.ticTacToe = new TicTacToe();
  }, 100);
  
  // Initialize Jump Game
  setTimeout(() => {
    window.jumpGame = new JumpGame();
  }, 200);
  
  console.log('✅ Games initialization complete!');
});

// Test functions for debugging
window.testTicTacToe = function() {
  console.log('🧪 Testing Tic-Tac-Toe...');
  if (window.ticTacToe) {
    window.ticTacToe.restartGame();
    console.log('✅ Tic-Tac-Toe test completed');
  } else {
    console.error('❌ Tic-Tac-Toe not initialized');
  }
};

window.testJumpGame = function() {
  console.log('🧪 Testing Jump Game...');
  if (window.jumpGame) {
    window.jumpGame.startGame();
    console.log('✅ Jump Game test completed');
  } else {
    console.error('❌ Jump Game not initialized');
  }
}; 
