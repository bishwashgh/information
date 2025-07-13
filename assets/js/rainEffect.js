class RainEffect {
    constructor() {
        // Create rain container
        this.container = document.createElement('div');
        this.container.className = 'rain-container';
        document.body.appendChild(this.container);

        // Create sound controls
        this.soundControls = document.createElement('div');
        this.soundControls.className = 'rain-sound-controls';
        document.body.appendChild(this.soundControls);

        // Create sound button
        this.soundButton = document.createElement('button');
        this.soundButton.className = 'rain-sound-button';
        this.soundButton.textContent = 'Toggle Rain Sound';
        this.soundControls.appendChild(this.soundButton);

        // Initialize sound
        this.sound = new Audio('assets/sounds/rain.mp3');
        this.sound.loop = true;
        this.sound.volume = 0.3;
        
        // Add sound toggle event
        this.soundButton.addEventListener('click', () => {
            if (this.sound.paused) {
                this.sound.play();
                this.soundButton.textContent = 'Mute Rain Sound';
            } else {
                this.sound.pause();
                this.soundButton.textContent = 'Toggle Rain Sound';
            }
        });

        // Initialize arrays for rain elements
        this.rainDrops = [];
        this.ripples = [];
        this.isInitialized = false;
    }

    initialize() {
        if (this.isInitialized) return;

        // Create rain drops
        for (let i = 0; i < 50; i++) {
            const drop = document.createElement('div');
            drop.className = 'rain-drop';
            drop.style.left = `${Math.random() * window.innerWidth}px`;
            drop.style.top = `${Math.random() * window.innerHeight}px`;
            this.container.appendChild(drop);
            this.rainDrops.push(drop);
        }

        // Create ripples
        for (let i = 0; i < 10; i++) {
            const ripple = document.createElement('div');
            ripple.className = 'ripple';
            ripple.style.left = `${Math.random() * window.innerWidth}px`;
            ripple.style.top = `${Math.random() * window.innerHeight}px`;
            ripple.style.animationDelay = `${i * 0.2}s`;
            this.container.appendChild(ripple);
            this.ripples.push(ripple);
        }

        // Update rain elements
        this.updateRainDrops();
        this.updateRipples();
        
        this.isInitialized = true;
    }

    updateRainDrops() {
        this.rainDrops.forEach(drop => {
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            drop.style.left = `${x}px`;
            drop.style.top = `${y}px`;
            drop.style.animationDuration = `${2 + Math.random() * 2}s`;
        });
    }

    updateRipples() {
        this.ripples.forEach((ripple, index) => {
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            ripple.style.animationDelay = `${index * 0.2}s`;
            ripple.style.animationDuration = `${1 + Math.random() * 1}s`;
        });
    }

    start() {
        this.initialize();
        this.sound.play();
        this.soundButton.textContent = 'Mute Rain Sound';
    }

    stop() {
        this.sound.pause();
        this.soundButton.textContent = 'Toggle Rain Sound';
        this.container.style.display = 'none';
        this.soundControls.style.display = 'none';
    }

    toggle() {
        if (this.sound.paused) {
            this.start();
        } else {
            this.stop();
        }
    }
}

// Initialize rain effect
const rainEffect = new RainEffect();

// Update mood system to include rain effect
document.addEventListener('DOMContentLoaded', () => {
    const moodSystem = new MoodSystem();
    
    // Override setMood to include rain effect
    const originalSetMood = moodSystem.setMood;
    moodSystem.setMood = function(mood) {
        originalSetMood.call(this, mood);
        
        if (mood === 'melancholic') {
            rainEffect.start();
        } else {
            rainEffect.stop();
        }
    };
});
