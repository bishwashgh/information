document.addEventListener('DOMContentLoaded', function() {
    // Create gaming button
    const gameButton = document.createElement('button');
    gameButton.id = 'game-button';
    gameButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #c0c0c0;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 0 10px rgba(192, 192, 192, 0.8);
        transition: all 0.3s ease;
    `;
    gameButton.textContent = '🎮';
    document.querySelector('header').appendChild(gameButton);

    // Create modal
    const modal = document.createElement('div');
    modal.id = 'game-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;

    const modalContent = document.createElement('div');
    modalContent.id = 'modal-content';
    modalContent.style.cssText = `
        background: #1a1a1a;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    `;

    // Add close button
    const closeButton = document.createElement('button');
    closeButton.id = 'close-modal';
    closeButton.textContent = 'X';
    closeButton.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        padding: 5px 10px;
    `;
    modalContent.appendChild(closeButton);

    // Add game container
    const gameContainer = document.createElement('div');
    gameContainer.id = 'game-container';
    gameContainer.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        width: 100%;
    `;

    // Create game board
    const gameBoard = document.createElement('div');
    gameBoard.id = 'game-board';
    gameBoard.style.cssText = `
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        background: #2a2a2a;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    `;

    // Create cells
    for (let i = 0; i < 9; i++) {
        const cell = document.createElement('div');
        cell.className = 'cell';
        cell.style.cssText = `
            width: 100px;
            height: 100px;
            background: #3a3a3a;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        `;
        gameBoard.appendChild(cell);
    }

    // Add status display
    const status = document.createElement('div');
    status.id = 'status';
    status.textContent = "Player X's turn";
    status.style.cssText = `
        font-size: 1.5em;
        color: #ffd700;
        margin-bottom: 20px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    `;

    // Add reset button
    const resetButton = document.createElement('button');
    resetButton.id = 'reset-button';
    resetButton.textContent = 'New Game';
    resetButton.style.cssText = `
        padding: 12px 24px;
        background: #ffd700;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        font-weight: bold;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    `;
    resetButton.addEventListener('click', () => {
        resetButton.style.transform = 'scale(1)';
        resetButton.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.2)';
    });
    resetButton.addEventListener('mouseover', () => {
        resetButton.style.transform = 'scale(1.05)';
        resetButton.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.3)';
    });
    resetButton.addEventListener('mouseout', () => {
        resetButton.style.transform = 'scale(1)';
        resetButton.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.2)';
    });

    // Add all elements to container
    gameContainer.appendChild(gameBoard);
    gameContainer.appendChild(status);
    gameContainer.appendChild(resetButton);
    modalContent.appendChild(gameContainer);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Initialize game
    const cells = document.querySelectorAll('.cell');
    let currentPlayer = 'X';
    let gameActive = true;
    let gameState = ['', '', '', '', '', '', '', '', ''];

    const winningCombinations = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6]
    ];

    // Add event listeners
    cells.forEach(cell => {
        cell.addEventListener('click', () => handleClick(cell));
        cell.addEventListener('touchstart', (e) => {
            e.preventDefault();
            handleClick(cell);
        });
        cell.addEventListener('mouseover', () => {
            if (!gameActive || cell.textContent) return;
            cell.style.transform = 'scale(1.05)';
        });
        cell.addEventListener('mouseout', () => {
            if (!gameActive || cell.textContent) return;
            cell.style.transform = 'scale(1)';
        });
    });

    resetButton.addEventListener('click', () => {
        currentPlayer = 'X';
        gameActive = true;
        gameState = ['', '', '', '', '', '', '', '', ''];
        status.textContent = `Player ${currentPlayer}'s turn`;
        cells.forEach(cell => {
            cell.textContent = '';
            cell.className = 'cell';
            cell.style.cursor = 'pointer';
            cell.style.transform = 'scale(1)';
        });
    });

    // Create winning popup
    const winningPopup = document.createElement('div');
    winningPopup.id = 'winning-popup';
    winningPopup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.9);
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        color: #ffd700;
        font-size: 1.5em;
        display: none;
        z-index: 1002;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    `;

    const popupContent = document.createElement('div');
    popupContent.id = 'popup-content';
    popupContent.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    `;

    const popupText = document.createElement('div');
    popupText.id = 'popup-text';
    popupText.style.cssText = `
        font-size: 2em;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    `;

    const popupButton = document.createElement('button');
    popupButton.id = 'popup-button';
    popupButton.textContent = 'Play Again';
    popupButton.style.cssText = `
        padding: 12px 24px;
        background: #ffd700;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        font-weight: bold;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    `;
    popupButton.addEventListener('click', () => {
        winningPopup.style.display = 'none';
        resetButton.click();
    });

    popupContent.appendChild(popupText);
    popupContent.appendChild(popupButton);
    winningPopup.appendChild(popupContent);
    document.body.appendChild(winningPopup);

    function handleClick(cell) {
        const cellIndex = Array.from(cells).indexOf(cell);
        if (gameState[cellIndex] !== '' || !gameActive) return;

        gameState[cellIndex] = currentPlayer;
        cell.textContent = currentPlayer;
        cell.classList.add(currentPlayer.toLowerCase());
        cell.style.cursor = 'default';
        cell.style.transform = 'scale(1)';

        if (checkWin()) {
            status.textContent = `Player ${currentPlayer} wins!`;
            gameActive = false;
            cells.forEach(cell => {
                cell.style.cursor = 'default';
            });
            
            // Show winning popup
            popupText.textContent = `Player ${currentPlayer} wins!`;
            winningPopup.style.display = 'flex';
            return;
        }

        if (checkDraw()) {
            status.textContent = "It's a draw!";
            gameActive = false;
            cells.forEach(cell => {
                cell.style.cursor = 'default';
            });
            
            // Show draw popup
            popupText.textContent = "It's a draw!";
            winningPopup.style.display = 'flex';
            return;
        }

        currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
        status.textContent = `Player ${currentPlayer}'s turn`;
    }

    function checkWin() {
        return winningCombinations.some(combination => {
            return combination.every(index => {
                return gameState[index] === currentPlayer;
            });
        });
    }

    function checkDraw() {
        return gameState.every(cell => cell !== '');
    }

    // Modal controls
    gameButton.addEventListener('click', () => {
        modal.style.display = 'flex';
    });

    closeButton.addEventListener('click', () => {
        modal.style.display = 'none';
        resetButton.click();
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            resetButton.click();
        }
    });
});
