// Game variables
let player = document.querySelector('.player');
let obstacle = document.querySelector('.obstacle');
let scoreElement = document.getElementById('score');
let gameArea = document.querySelector('.game-area');
let gameContainer = document.querySelector('.game-container');

let gameIsRunning = false;
let score = 0;
let obstacleSpeed = 5;

// Game state
let playerPosition = {
    y: 0,
    jumping: false
};

// Initialize game
function initGame() {
    gameIsRunning = false;
    score = 0;
    obstacleSpeed = 5;
    player.style.bottom = '0';
    obstacle.style.right = '-100px';
    scoreElement.textContent = score;
    
    // Remove game over screen if exists
    const gameOverScreen = gameContainer.querySelector('.game-over');
    if (gameOverScreen) {
        gameOverScreen.remove();
    }
    
    // Reset player position
    player.style.animation = '';
    playerPosition.jumping = false;
}

// Game functions
function startGame() {
    initGame();
    gameIsRunning = true;
    
    // Start obstacle movement
    moveObstacle();
    
    // Add event listeners
    document.addEventListener('click', jump);
    document.addEventListener('keydown', handleKeyPress);
}

function jump() {
    if (!gameIsRunning) return;
    
    // Allow jumping immediately if we're not currently jumping
    if (!playerPosition.jumping) {
        playerPosition.jumping = true;
        
        // Use requestAnimationFrame for smoother animation
        let startTime = performance.now();
        function animate(currentTime) {
            const timeElapsed = currentTime - startTime;
            
            // Jump animation curve (quadratic)
            const jumpHeight = 300; // 30cm jump height
            const duration = 800; // 800ms total jump time
            const t = timeElapsed / duration;
            
            if (t < 0.5) {
                // Going up
                player.style.transform = `translateY(${-jumpHeight * Math.pow(t * 2, 2)}px)`;
            } else {
                // Going down
                player.style.transform = `translateY(${-jumpHeight * Math.pow((1 - t) * 2, 2)}px)`;
            }
            
            if (timeElapsed < duration) {
                requestAnimationFrame(animate);
            } else {
                playerPosition.jumping = false;
                player.style.transform = 'translateY(0)';
            }
        }
        requestAnimationFrame(animate);
    }
}

function moveObstacle() {
    if (!gameIsRunning) return;
    
    let obstaclePosition = parseInt(obstacle.style.right) || 0;
    obstacle.style.right = obstaclePosition + obstacleSpeed + 'px';
    
    // Check collision
    let playerRect = player.getBoundingClientRect();
    let obstacleRect = obstacle.getBoundingClientRect();
    
    if (checkCollision(playerRect, obstacleRect)) {
        gameOver();
    }
    
    // Increase score
    score++;
    scoreElement.textContent = score;
    
    // Increase speed
    if (score % 100 === 0) {
        obstacleSpeed += 0.5;
    }
    
    // Keep moving
    setTimeout(moveObstacle, 20);
}

function checkCollision(playerRect, obstacleRect) {
    return !(
        playerRect.right < obstacleRect.left ||
        playerRect.left > obstacleRect.right ||
        playerRect.bottom < obstacleRect.top ||
        playerRect.top > obstacleRect.bottom
    );
}

function gameOver() {
    gameIsRunning = false;
    
    // Add game over screen
    const gameOverScreen = document.createElement('div');
    gameOverScreen.className = 'game-over';
    gameOverScreen.textContent = 'Game Over! Press R to Restart';
    gameContainer.appendChild(gameOverScreen);
    
    // Remove event listeners
    document.removeEventListener('click', jump);
    
    // Add restart listener
    document.addEventListener('keydown', handleKeyPress);
}

function handleKeyPress(e) {
    if (e.key === 'r' || e.key === 'R') {
        restartGame();
    }
}

function restartGame() {
    // Remove event listeners
    document.removeEventListener('keydown', handleKeyPress);
    
    // Restart the game
    startGame();
}

// Game Selection
const gameSelection = document.querySelector('.game-selection');
const gameOptions = document.querySelectorAll('.game-btn');
const tictactoeGame = document.getElementById('tictactoe-game');
const ludoGame = document.getElementById('ludo-game');
const backBtn = document.getElementById('back-btn');

// Show selected game
function showGame(game) {
    gameSelection.classList.add('hidden');
    if (game === 'tictactoe') {
        tictactoeGame.classList.remove('hidden');
        ludoGame.classList.add('hidden');
    } else if (game === 'ludo') {
        ludoGame.classList.remove('hidden');
        tictactoeGame.classList.add('hidden');
    }
}

// Back to selection
function backToSelection() {
    gameSelection.classList.remove('hidden');
    tictactoeGame.classList.add('hidden');
}

// Game selection event listeners
gameOptions.forEach(button => {
    button.addEventListener('click', () => {
        const game = button.dataset.game;
        showGame(game);
    });
});

// Game button navigation
document.addEventListener('DOMContentLoaded', () => {
    const gamesBtn = document.getElementById('games-btn');
    gamesBtn.addEventListener('click', () => {
        window.location.href = 'index.html';
    });

    // Initialize Tic Tac Toe game
    setupTicTacToeGame();
});

// Tic Tac Toe game logic
let currentPlayer = 1;
let board = Array(9).fill(null);
let gameActive = true;

// Initialize game when page loads
document.addEventListener('DOMContentLoaded', () => {
    setupTicTacToeGame();
});

// Setup Tic Tac Toe game
function setupTicTacToeGame() {
    // Add click event listeners to cells
    const cells = document.querySelectorAll('.cell');
    cells.forEach(cell => {
        cell.addEventListener('click', () => handleCellClick(cell, parseInt(cell.dataset.cellIndex)));
    });

    // Add player selection buttons
    const player1Btn = document.getElementById('player1-btn');
    const player2Btn = document.getElementById('player2-btn');
    
    player1Btn.addEventListener('click', () => {
        player1Btn.classList.add('active');
        player2Btn.classList.remove('active');
    });

    player2Btn.addEventListener('click', () => {
        player2Btn.classList.add('active');
        player1Btn.classList.remove('active');
    });

    // Add reset button
    const resetBtn = document.getElementById('reset-btn');
    resetBtn.addEventListener('click', resetGame);

    // Add back button
    const backBtn = document.getElementById('back-btn');
    backBtn.addEventListener('click', () => {
        window.location.href = 'index.html';
    });
}

// Handle cell click
function handleCellClick(clickedCell, clickedCellIndex) {
    if (board[clickedCellIndex] || !gameActive) return;

    board[clickedCellIndex] = currentPlayer;
    clickedCell.textContent = currentPlayer === 1 ? 'X' : 'O';
    clickedCell.classList.add(currentPlayer === 1 ? 'x' : 'o');

    if (checkWin()) {
        gameActive = false;
        declareWinner(currentPlayer);
    } else if (checkDraw()) {
        gameActive = false;
        declareDraw();
    } else {
        currentPlayer = currentPlayer === 1 ? 2 : 1;
        updatePlayerDisplay();
    }
}

// Check for win
function checkWin() {
    const winPatterns = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
        [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
        [0, 4, 8], [2, 4, 6]             // Diagonals
    ];

    return winPatterns.some(pattern => {
        return pattern.every(index => board[index] === currentPlayer);
    });
}

// Check for draw
function checkDraw() {
    return board.every(cell => cell !== null);
}

// Update player display
function updatePlayerDisplay() {
    const player1Btn = document.getElementById('player1-btn');
    const player2Btn = document.getElementById('player2-btn');
    
    if (currentPlayer === 1) {
        player1Btn.classList.add('active');
        player2Btn.classList.remove('active');
    } else {
        player2Btn.classList.add('active');
        player1Btn.classList.remove('active');
    }
}

// Declare winner
function declareWinner(player) {
    const winnerAnnouncement = document.createElement('div');
    winnerAnnouncement.className = 'winner-announcement';
    winnerAnnouncement.textContent = `Player ${player} Wins!`;
    document.querySelector('.game-container').appendChild(winnerAnnouncement);
}

// Declare draw
function declareDraw() {
    const winnerAnnouncement = document.createElement('div');
    winnerAnnouncement.className = 'winner-announcement';
    winnerAnnouncement.textContent = 'It\'s a Draw!';
    document.querySelector('.game-container').appendChild(winnerAnnouncement);
}

// Reset game
function resetGame() {
    board = Array(9).fill(null);
    gameActive = true;
    currentPlayer = 1;
    
    const cells = document.querySelectorAll('.cell');
    cells.forEach(cell => {
        cell.textContent = '';
        cell.classList.remove('x', 'o');
    });
    
    const winnerAnnouncement = document.querySelector('.winner-announcement');
    if (winnerAnnouncement) {
        winnerAnnouncement.remove();
    }
    
    updatePlayerDisplay();
}

// Initialize game when Ludo game is shown
document.addEventListener('DOMContentLoaded', () => {
    const ludoGame = document.getElementById('ludo-game');
    ludoGame.addEventListener('click', (e) => {
        if (e.target.classList.contains('game-btn') && e.target.dataset.game === 'ludo') {
            initLudoGame();
        }
    });
});

// Start the game
startGame();
