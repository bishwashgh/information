class MatrixRain {
    constructor() {
        this.columns = [];
        this.matrixRain = document.createElement('div');
        this.matrixRain.className = 'matrix-rain';
        document.body.appendChild(this.matrixRain);
        
        this.initializeMatrix();
        this.startAnimation();
    }

    initializeMatrix() {
        const columns = Math.floor(window.innerWidth / 10) + 1;
        for (let i = 0; i < columns; i++) {
            const column = document.createElement('div');
            column.className = 'matrix-column';
            column.style.left = `${i * 10}px`;
            this.matrixRain.appendChild(column);
            this.columns.push(column);
        }
    }

    startAnimation() {
        this.columns.forEach(column => {
            const animationDuration = Math.random() * 2 + 1;
            column.style.animationDuration = `${animationDuration}s`;
            column.style.animationDelay = `${Math.random() * 2}s`;

            setInterval(() => this.addMatrixCharacter(column), 100);
        });
    }

    addMatrixCharacter(column) {
        const character = document.createElement('div');
        character.className = 'matrix-character';
        character.textContent = String.fromCharCode(0x30A0 + Math.floor(Math.random() * 96));
        
        const position = Math.random() * window.innerHeight;
        character.style.top = `${position}px`;
        column.appendChild(character);

        setTimeout(() => {
            character.remove();
        }, 1000);
    }
}

// Mirror Room Effect
class MirrorRoom {
    constructor() {
        this.mirrorRoom = document.createElement('div');
        this.mirrorRoom.className = 'mirror-room';
        document.body.appendChild(this.mirrorRoom);

        this.mirrors = [];
        this.initializeMirrors();
        this.startTracking();
    }

    initializeMirrors() {
        // Create multiple mirrors
        for (let i = 0; i < 5; i++) {
            const mirror = document.createElement('div');
            mirror.className = 'mirror';
            this.mirrorRoom.appendChild(mirror);
            
            const reflection = document.createElement('div');
            reflection.className = 'mirror-reflection';
            mirror.appendChild(reflection);

            this.mirrors.push({
                mirror,
                reflection,
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight
            });
        }
    }

    startTracking() {
        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const scrollDelta = scrollY - lastScrollY;
            lastScrollY = scrollY;

            this.mirrors.forEach(mirror => {
                // Update mirror position based on scroll
                mirror.x += scrollDelta * 0.1;
                mirror.y += scrollDelta * 0.1;

                // Keep mirrors within bounds
                mirror.x = Math.max(0, Math.min(window.innerWidth - 200, mirror.x));
                mirror.y = Math.max(0, Math.min(window.innerHeight - 400, mirror.y));

                // Update mirror position
                mirror.mirror.style.transform = `translate(${mirror.x}px, ${mirror.y}px)`;

                // Update reflection with user's position
                const userPosition = this.getUserPosition();
                mirror.reflection.style.transform = `translate(${userPosition.x}px, ${userPosition.y}px) scaleY(-1)`;
            });
        });
    }

    getUserPosition() {
        // Get user's position based on scroll
        const scrollY = window.scrollY;
        const viewportHeight = window.innerHeight;
        const position = {
            x: window.innerWidth / 2,
            y: scrollY + viewportHeight / 2
        };
        return position;
    }
}

// Add toggle buttons to control effects
const effectsButton = document.createElement('button');
const effectsContainer = document.createElement('div');

effectsContainer.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    background: rgba(0, 0, 0, 0.8);
    padding: 10px;
    border-radius: 5px;
    display: flex;
    gap: 10px;
`;

effectsButton.style.cssText = `
    background: none;
    border: 1px solid #0f0;
    color: #0f0;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
    font-family: monospace;
`;

effectsButton.textContent = 'Matrix/Mirror';

effectsContainer.appendChild(effectsButton);
document.body.appendChild(effectsContainer);

// Initialize effects
let matrixEffect = null;
let mirrorEffect = null;
let effectsActive = false;

effectsButton.addEventListener('click', () => {
    effectsActive = !effectsActive;
    
    if (effectsActive) {
        matrixEffect = new MatrixRain();
        mirrorEffect = new MirrorRoom();
        effectsButton.textContent = 'Disable Effects';
    } else {
        if (matrixEffect) {
            matrixEffect.matrixRain.remove();
            matrixEffect = null;
        }
        if (mirrorEffect) {
            mirrorEffect.mirrorRoom.remove();
            mirrorEffect = null;
        }
        effectsButton.textContent = 'Matrix/Mirror';
    }
});
