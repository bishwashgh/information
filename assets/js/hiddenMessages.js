class HiddenMessages {
    constructor() {
        this.image = document.querySelector('.avatar');
        this.hiddenMessage = document.createElement('div');
        this.hiddenMessage.className = 'hidden-message';
        document.body.appendChild(this.hiddenMessage);

        this.devModeOverlay = document.createElement('div');
        this.devModeOverlay.className = 'dev-mode-overlay';
        document.body.appendChild(this.devModeOverlay);

        this.devModeContent = document.createElement('div');
        this.devModeContent.className = 'dev-mode-content';
        this.devModeOverlay.appendChild(this.devModeContent);

        this.devModeCode = document.createElement('pre');
        this.devModeCode.className = 'dev-mode-code';
        this.devModeContent.appendChild(this.devModeCode);

        this.initGlitchEffect();
        this.initDevMode();
        this.initImageGlitch();
    }

    initGlitchEffect() {
        const glitchText = 'Welcome to the Glitch Zone';
        this.hiddenMessage.setAttribute('data-text', glitchText);
        this.hiddenMessage.textContent = glitchText;
        this.hiddenMessage.className = 'hidden-message glitch';
    }

    initDevMode() {
        const secretKey = 'BISHWAS';
        let typedKey = '';

        document.addEventListener('keydown', (e) => {
            if (e.key === secretKey[typedKey.length]) {
                typedKey += e.key;
                if (typedKey === secretKey) {
                    this.showDevMode();
                }
            } else {
                typedKey = '';
            }
        });
    }

    showDevMode() {
        this.devModeOverlay.classList.add('visible');
        this.devModeCode.textContent = `
        Welcome to Dev Mode!
        
        Here are some secrets:
        - The matrix effect uses real-time character generation
        - Mirrors track your scroll position
        - Glitch effects use CSS animations
        - Quantum particles are physics-based
        - Celestial bodies follow real astronomical distances
        
        Exit Options:
        - Press ESC
        - Click anywhere outside
        - Press BISHWAS again
        `;

        // Add multiple exit methods
        const exitDevMode = () => {
            this.devModeOverlay.classList.remove('visible');
            // Reset typed key for secret code
            this.typedKey = '';
        };

        // ESC key exit
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                exitDevMode();
            }
        }, { once: true });

        // Click outside exit
        this.devModeOverlay.addEventListener('click', (e) => {
            if (e.target === this.devModeOverlay) {
                exitDevMode();
            }
        });

        // Secret code again exit
        document.addEventListener('keydown', (e) => {
            if (this.typedKey.length === 0 && e.key === 'B') {
                this.typedKey = 'B';
            } else if (this.typedKey === 'BI' && e.key === 'S') {
                this.typedKey = 'BIS';
            } else if (this.typedKey === 'BIS' && e.key === 'H') {
                this.typedKey = 'BISH';
            } else if (this.typedKey === 'BISH' && e.key === 'W') {
                this.typedKey = 'BISHW';
            } else if (this.typedKey === 'BISHW' && e.key === 'A') {
                this.typedKey = 'BISHWA';
            } else if (this.typedKey === 'BISHWA' && e.key === 'S') {
                exitDevMode();
            } else {
                this.typedKey = '';
            }
        });
    }

    initImageGlitch() {
        if (this.image) {
            let glitchInterval;
            
            this.image.addEventListener('mouseenter', () => {
                glitchInterval = setInterval(() => {
                    this.applyGlitchEffect(this.image);
                }, 100);
                
                // Show hidden message with glitch effect
                this.hiddenMessage.textContent = 'Welcome to the Glitch Zone';
                this.hiddenMessage.classList.add('glitch');
                this.hiddenMessage.classList.add('visible');
            });

            this.image.addEventListener('mouseleave', () => {
                clearInterval(glitchInterval);
                this.image.style.filter = '';
                this.hiddenMessage.classList.remove('visible');
                this.hiddenMessage.classList.remove('glitch');
            });
        }
    }

    applyGlitchEffect(element) {
        const filters = [
            'hue-rotate(180deg)',
            'brightness(200%)',
            'contrast(200%)',
            'saturate(200%)',
            'blur(2px)',
            'invert(100%)'
        ];
        
        // Apply random filter
        const randomFilter = filters[Math.floor(Math.random() * filters.length)];
        element.style.filter = randomFilter;
        
        // Remove filter after a short delay
        setTimeout(() => {
            element.style.filter = '';
        }, 100);

        const randomFilters = filters
            .filter(() => Math.random() > 0.5)
            .join(' ');

        element.style.filter = randomFilters;
    }
}

// Initialize hidden messages
document.addEventListener('DOMContentLoaded', () => {
    new HiddenMessages();
});
