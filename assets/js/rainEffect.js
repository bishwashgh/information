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

        // Create canvas for rain effect
        this.canvas = document.createElement('canvas');
        this.canvas.className = 'rain-canvas';
        this.container.appendChild(this.canvas);
        this.ctx = this.canvas.getContext('2d');

        // Set canvas dimensions
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    initialize() {
        if (this.isInitialized) return;

        // Adjust effects based on device type and mood
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isFocused = document.body.classList.contains('focused');
        
        // Focused mood settings
        const numDrops = isMobile ? (isFocused ? 10 : 30) : (isFocused ? 50 : 100);
        const numRipples = isMobile ? (isFocused ? 3 : 5) : (isFocused ? 10 : 20);
        const dropSpeed = isMobile ? (isFocused ? 1 : 2) : (isFocused ? 1.5 : 4);
        const dropWidth = isMobile ? (isFocused ? 0.5 : 1) : (isFocused ? 1 : 2);
        const rippleSize = isMobile ? (isFocused ? 10 : 20) : (isFocused ? 20 : 30);

        // Create rain drops
        for (let i = 0; i < numDrops; i++) {
            this.drops.push({
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight,
                speed: dropSpeed + Math.random() * (isMobile ? 1 : 2),
                width: dropWidth + Math.random() * (isMobile ? 0.5 : 1),
                opacity: 0.2 + Math.random() * 0.3
            });
        }

        // Create ripples
        for (let i = 0; i < numRipples; i++) {
            this.ripples.push({
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight,
                radius: 0,
                maxRadius: rippleSize + Math.random() * (isMobile ? 5 : 10),
                opacity: 0.4,
                active: false
            });
        }

        // Start animation loop
        this.animate();

        this.isInitialized = true;
    }

    animate() {
        if (!this.isInitialized) return;

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw rain drops
        this.drops.forEach(drop => {
            drop.y += drop.speed;
            if (drop.y > window.innerHeight) {
                drop.y = -10;
                drop.x = Math.random() * window.innerWidth;
            }

            // Only draw if visible
            if (drop.y > -20 && drop.y < window.innerHeight + 20) {
                this.ctx.beginPath();
                this.ctx.moveTo(drop.x, drop.y);
                this.ctx.lineTo(drop.x, drop.y + 20);
                this.ctx.strokeStyle = `rgba(255, 215, 0, ${drop.opacity})`;
                this.ctx.lineWidth = drop.width;
                this.ctx.stroke();

                // Create ripple when drop hits ground
                if (drop.y > window.innerHeight - 10) {
                    const ripple = this.ripples[Math.floor(Math.random() * this.ripples.length)];
                    if (!ripple.active) {
                        ripple.x = drop.x;
                        ripple.y = window.innerHeight;
                        ripple.radius = 0;
                        ripple.active = true;
                    }
                }
            }
        });

        // Draw ripples
        this.ripples.forEach(ripple => {
            if (ripple.active) {
                ripple.radius += 2;
                if (ripple.radius > ripple.maxRadius) {
                    ripple.active = false;
                }

                // Only draw if visible
                if (ripple.radius < ripple.maxRadius) {
                    this.ctx.beginPath();
                    this.ctx.arc(ripple.x, ripple.y, ripple.radius, 0, Math.PI * 2);
                    this.ctx.fillStyle = `rgba(255, 215, 0, ${ripple.opacity * (1 - ripple.radius / ripple.maxRadius)})`;
                    this.ctx.fill();
                }
            }
        });

        // Request next frame with throttling
        const now = Date.now();
        if (!this.lastFrameTime || (now - this.lastFrameTime) > (isMobile ? 16 : 8)) { // 60fps on desktop, 30fps on mobile
            this.lastFrameTime = now;
            requestAnimationFrame(() => this.animate());
        }
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
