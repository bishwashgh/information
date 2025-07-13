document.addEventListener('DOMContentLoaded', function() {
    const board = document.getElementById('game-board');
    const cells = document.querySelectorAll('.cell');
    const status = document.getElementById('status');
    const resetButton = document.getElementById('reset-button');

    let currentPlayer = 'X';
    let gameActive = true;
    let gameState = ['', '', '', '', '', '', '', '', ''];

    const winningCombinations = [
        [0, 1, 2], // Row 1
        [3, 4, 5], // Row 2
        [6, 7, 8], // Row 3
        [0, 3, 6], // Column 1
        [1, 4, 7], // Column 2
        [2, 5, 8], // Column 3
        [0, 4, 8], // Diagonal 1
        [2, 4, 6]  // Diagonal 2
    ];

    // Event handler for both click and touch events
    function handleCellInteraction(event) {
        // Prevent default touch behavior
        if (event.type === 'touchstart') {
            event.preventDefault();
        }

        const clickedCell = event.target;
        const clickedCellIndex = Array.from(cells).indexOf(clickedCell);

        if (gameState[clickedCellIndex] !== '' || !gameActive) {
            return;
        }

        handleCellPlayed(clickedCell, clickedCellIndex);
        handleResultValidation();
    }

    // Add both click and touch event listeners
    cells.forEach(cell => {
        cell.addEventListener('click', handleCellInteraction);
        cell.addEventListener('touchstart', handleCellInteraction);
    });

    function handleCellPlayed(clickedCell, clickedCellIndex) {
        gameState[clickedCellIndex] = currentPlayer;
        clickedCell.textContent = currentPlayer;
        clickedCell.classList.add(currentPlayer.toLowerCase());
    }

    function handleResultValidation() {
        let roundWon = false;
        for (let i = 0; i < winningCombinations.length; i++) {
            const winCondition = winningCombinations[i];
            let a = gameState[winCondition[0]];
            let b = gameState[winCondition[1]];
            let c = gameState[winCondition[2]];

            if (a === '' || b === '' || c === '') {
                continue;
            }
            if (a === b && b === c) {
                roundWon = true;
                break;
            }
        }

        if (roundWon) {
            status.textContent = `Player ${currentPlayer} has won!`;
            gameActive = false;
            cells.forEach(cell => {
                cell.classList.add('disabled');
            });
            return;
        }

        const roundDraw = !gameState.includes('');
        if (roundDraw) {
            status.textContent = "Game ended in a draw!";
            gameActive = false;
            cells.forEach(cell => {
                cell.classList.add('disabled');
            });
            return;
        }

        currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
        status.textContent = `Player ${currentPlayer}'s turn`;
    }

    function handleResetGame() {
        currentPlayer = 'X';
        gameActive = true;
        gameState = ['', '', '', '', '', '', '', '', ''];
        status.textContent = `Player ${currentPlayer}'s turn`;
        cells.forEach(cell => {
            cell.textContent = '';
            cell.className = 'cell';
        });
    }

    resetButton.addEventListener('click', handleResetGame);
    handleResetGame();
});
