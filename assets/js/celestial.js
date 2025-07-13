class CelestialSystem {
    constructor() {
        this.sun = null;
        this.earth = null;
        this.moon = null;
        this.earthPath = null;
        this.moonPath = null;
        this.initCelestialBodies();
    }

    initCelestialBodies() {
        // Create container
        const container = document.createElement('div');
        container.className = 'celestial-background';
        document.body.appendChild(container);

        // Create sun container
        this.sunContainer = document.createElement('div');
        this.sunContainer.className = 'sun-container';
        container.appendChild(this.sunContainer);

        // Create sun
        this.sun = document.createElement('div');
        this.sun.className = 'sun';
        this.sun.style.left = '50%';
        this.sun.style.top = '50%';
        this.sun.style.transform = 'translate(-50%, -50%)';
        this.sunContainer.appendChild(this.sun);

        // Create earth container
        this.earthContainer = document.createElement('div');
        this.earthContainer.className = 'earth-container';
        this.sunContainer.appendChild(this.earthContainer);

        // Create earth
        this.earth = document.createElement('div');
        this.earth.className = 'earth';
        this.earth.style.left = '50%';
        this.earth.style.top = '50%';
        this.earth.style.transform = 'translate(118.1px, 0)'; // 3cm from sun
        this.earthContainer.appendChild(this.earth);

        // Animate celestial bodies
        this.animateCelestialBodies();
    }

    animateCelestialBodies() {
        // Earth's position relative to sun (1 year = 365 days)
        const earthOrbitSpeed = 365; // days per orbit
        const earthOrbitAngle = (Date.now() / (1000 * 60 * 60 * 24)) / earthOrbitSpeed * 360;
        
        // Rotate earth container
        this.earthContainer.style.transform = `rotate(${earthOrbitAngle}deg)`;

        // Earth's rotation (1 day = 24 hours)
        const earthRotationSpeed = 24; // hours per rotation
        const earthRotationAngle = (Date.now() / (1000 * 60 * 60)) / earthRotationSpeed * 360;
        
        this.earth.style.transform = `translate(118.1px, 0) rotate(${earthRotationAngle}deg)`; // 3cm from sun

        // Update every frame
        requestAnimationFrame(() => this.animateCelestialBodies());
    }

    handleResize() {
        // Update positions on resize
        this.sun.style.left = '50%';
        this.sun.style.top = '50%';
        this.sun.style.transform = 'translate(-50%, -50%)';

        this.earthPath.style.left = '50%';
        this.earthPath.style.top = '50%';
        this.earthPath.style.transform = 'translate(-50%, -50%)';

        this.moonPath.style.left = '50%';
        this.moonPath.style.top = '50%';
        this.moonPath.style.transform = 'translate(-50%, -50%)';
    }
}

// Initialize celestial system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const celestialSystem = new CelestialSystem();
    
    // Handle window resize
    window.addEventListener('resize', () => {
        celestialSystem.handleResize();
    });
});
