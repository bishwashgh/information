class FlowerGarden {
    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'flower-garden';
        document.body.appendChild(this.container);

        this.flowers = [];
        this.flowerTypes = ['sunflower', 'rose', 'daisy', 'tulip'];
        this.isInitialized = false;
    }

    initialize() {
        if (this.isInitialized) return;

        // Create flowers
        for (let i = 0; i < 50; i++) {
            const flower = document.createElement('div');
            flower.className = 'flower ' + this.getRandomFlowerType();
            this.container.appendChild(flower);
            this.flowers.push(flower);
        }

        // Update flower positions
        this.updateFlowerPositions();

        // Add wind effect
        this.addWindEffect();

        this.isInitialized = true;
    }

    getRandomFlowerType() {
        return this.flowerTypes[Math.floor(Math.random() * this.flowerTypes.length)];
    }

    updateFlowerPositions() {
        this.flowers.forEach(flower => {
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            flower.style.left = `${x}px`;
            flower.style.top = `${y}px`;

            // Add random size variation
            const size = 20 + Math.random() * 20;
            flower.style.width = `${size}px`;
            flower.style.height = `${size}px`;
        });
    }

    addWindEffect() {
        const wind = document.createElement('div');
        wind.className = 'wind-effect';
        this.container.appendChild(wind);

        // Create wind particles
        for (let i = 0; i < 100; i++) {
            const particle = document.createElement('div');
            particle.className = 'wind-particle';
            wind.appendChild(particle);
        }

        // Update wind particles
        setInterval(() => {
            const particles = wind.getElementsByClassName('wind-particle');
            Array.from(particles).forEach(particle => {
                const x = Math.random() * window.innerWidth;
                const y = Math.random() * window.innerHeight;
                particle.style.left = `${x}px`;
                particle.style.top = `${y}px`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
            });
        }, 1000);
    }

    start() {
        this.initialize();
        this.updateFlowerPositions();
        this.container.style.display = 'block';
    }

    stop() {
        this.container.style.display = 'none';
        this.flowers = [];
        this.container.innerHTML = '';
        this.isInitialized = false;
    }

    toggle() {
        if (this.container.style.display === 'none') {
            this.start();
        } else {
            this.stop();
        }
    }
}

// Initialize flower garden
const flowerGarden = new FlowerGarden();

// Update mood system to include flower garden
document.addEventListener('DOMContentLoaded', () => {
    const moodSystem = new MoodSystem();
    
    // Override setMood to include flower garden
    const originalSetMood = moodSystem.setMood;
    moodSystem.setMood = function(mood) {
        originalSetMood.call(this, mood);
        
        if (mood === 'happy') {
            flowerGarden.start();
        } else {
            flowerGarden.stop();
        }
    };
});
