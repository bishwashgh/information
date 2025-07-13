class StarSystem {
    constructor() {
        this.stars = [];
        this.numStars = 200;
        this.createStars();
    }

    createStars() {
        const starContainer = document.createElement('div');
        starContainer.id = 'cosmic-bg';
        document.body.appendChild(starContainer);

        // Create multiple layers of stars with different colors and animations
        const starLayers = [
            { color: 'blue', count: 50, size: 1, animation: 'blueTwinkle' },
            { color: 'red', count: 40, size: 1.5, animation: 'redTwinkle' },
            { color: 'yellow', count: 60, size: 2, animation: 'twinkle' },
            { color: 'white', count: 30, size: 1.2, animation: 'whiteTwinkle' },
            { color: 'purple', count: 20, size: 1.8, animation: 'purpleTwinkle' },
            { color: 'orange', count: 40, size: 1.4, animation: 'orangeTwinkle' }
        ];

        starLayers.forEach(layer => {
            for (let i = 0; i < layer.count; i++) {
                const star = document.createElement('div');
                star.className = `star ${layer.color}`;
                
                // Random position
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                
                // Random size within layer's range
                star.style.width = `${Math.random() * layer.size + 1}px`;
                star.style.height = `${Math.random() * layer.size + 1}px`;
                
                // Layer-specific animation
                star.style.animationName = layer.animation;
                
                // Random animation duration
                star.style.animationDuration = `${Math.random() * 2 + 1}s`;
                
                // Random animation delay
                star.style.animationDelay = `${Math.random() * 2}s`;
                
                starContainer.appendChild(star);
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const stars = document.querySelectorAll('.star');
            stars.forEach(star => {
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
            });
        });
    }

    // Update star positions when window resizes
    handleResize() {
        this.stars.forEach(star => this.positionStar(star));
    }

    positionStar(star) {
        const randomX = Math.random() * 100 + '%';
        const randomY = Math.random() * 100 + '%';
        star.style.left = randomX;
        star.style.top = randomY;
        
        // Add random size variation
        const size = Math.random() * 2 + 1;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;

        // Add random delay for twinkling
        star.style.animationDelay = `${Math.random() * 3}s`;
    }
}

// Initialize star system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const starSystem = new StarSystem();
    
    // Handle window resize
    window.addEventListener('resize', () => {
        starSystem.handleResize();
    });
});
