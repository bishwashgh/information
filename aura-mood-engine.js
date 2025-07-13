// AURA.MoodEngine - Dynamic Theme Shifter
class AURAMoodEngine {
    constructor() {
        this.currentTheme = 'default';
        this.themes = {
            // Time-based themes
            dawn: {
                name: 'Dawn',
                colors: {
                    primary: '#FF6B35',
                    secondary: '#F7931E',
                    accent: '#FFD23F',
                    background: 'linear-gradient(135deg, #FF6B35 0%, #F7931E 50%, #FFD23F 100%)',
                    text: '#2C1810',
                    glow: '#FF6B35'
                },
                particles: '#FFD23F',
                solarSystem: {
                    sun: '#FF6B35',
                    planets: ['#F7931E', '#FFD23F', '#FFA500', '#FF8C00']
                }
            },
            day: {
                name: 'Day',
                colors: {
                    primary: '#00D4FF',
                    secondary: '#0099CC',
                    accent: '#FFD700',
                    background: 'linear-gradient(135deg, #00D4FF 0%, #0099CC 50%, #FFD700 100%)',
                    text: '#FFFFFF',
                    glow: '#00D4FF'
                },
                particles: '#FFD700',
                solarSystem: {
                    sun: '#FFD700',
                    planets: ['#00D4FF', '#0099CC', '#87CEEB', '#4682B4']
                }
            },
            dusk: {
                name: 'Dusk',
                colors: {
                    primary: '#FF6B9D',
                    secondary: '#C44569',
                    accent: '#FFB6C1',
                    background: 'linear-gradient(135deg, #FF6B9D 0%, #C44569 50%, #FFB6C1 100%)',
                    text: '#FFFFFF',
                    glow: '#FF6B9D'
                },
                particles: '#FFB6C1',
                solarSystem: {
                    sun: '#FF6B9D',
                    planets: ['#C44569', '#FFB6C1', '#FF69B4', '#DC143C']
                }
            },
            night: {
                name: 'Night',
                colors: {
                    primary: '#4A90E2',
                    secondary: '#2C3E50',
                    accent: '#9B59B6',
                    background: 'linear-gradient(135deg, #2C3E50 0%, #4A90E2 50%, #9B59B6 100%)',
                    text: '#FFFFFF',
                    glow: '#4A90E2'
                },
                particles: '#9B59B6',
                solarSystem: {
                    sun: '#4A90E2',
                    planets: ['#2C3E50', '#9B59B6', '#8E44AD', '#6C5CE7']
                }
            },
            // Weather-based themes
            sunny: {
                name: 'Sunny',
                colors: {
                    primary: '#FFD700',
                    secondary: '#FFA500',
                    accent: '#FF6347',
                    background: 'linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FF6347 100%)',
                    text: '#2C1810',
                    glow: '#FFD700'
                },
                particles: '#FF6347',
                solarSystem: {
                    sun: '#FFD700',
                    planets: ['#FFA500', '#FF6347', '#FF4500', '#FF8C00']
                }
            },
            rainy: {
                name: 'Rainy',
                colors: {
                    primary: '#4682B4',
                    secondary: '#5F9EA0',
                    accent: '#87CEEB',
                    background: 'linear-gradient(135deg, #4682B4 0%, #5F9EA0 50%, #87CEEB 100%)',
                    text: '#FFFFFF',
                    glow: '#4682B4'
                },
                particles: '#87CEEB',
                solarSystem: {
                    sun: '#4682B4',
                    planets: ['#5F9EA0', '#87CEEB', '#B0C4DE', '#6495ED']
                }
            },
            stormy: {
                name: 'Stormy',
                colors: {
                    primary: '#2C3E50',
                    secondary: '#34495E',
                    accent: '#7F8C8D',
                    background: 'linear-gradient(135deg, #2C3E50 0%, #34495E 50%, #7F8C8D 100%)',
                    text: '#FFFFFF',
                    glow: '#2C3E50'
                },
                particles: '#7F8C8D',
                solarSystem: {
                    sun: '#2C3E50',
                    planets: ['#34495E', '#7F8C8D', '#95A5A6', '#BDC3C7']
                }
            },
            // Mood-based themes
            calm: {
                name: 'Calm',
                colors: {
                    primary: '#98D8C8',
                    secondary: '#7FB069',
                    accent: '#B8E6B8',
                    background: 'linear-gradient(135deg, #98D8C8 0%, #7FB069 50%, #B8E6B8 100%)',
                    text: '#2C3E50',
                    glow: '#98D8C8'
                },
                particles: '#B8E6B8',
                solarSystem: {
                    sun: '#98D8C8',
                    planets: ['#7FB069', '#B8E6B8', '#90EE90', '#98FB98']
                }
            },
            energetic: {
                name: 'Energetic',
                colors: {
                    primary: '#FF4757',
                    secondary: '#FF3838',
                    accent: '#FF6348',
                    background: 'linear-gradient(135deg, #FF4757 0%, #FF3838 50%, #FF6348 100%)',
                    text: '#FFFFFF',
                    glow: '#FF4757'
                },
                particles: '#FF6348',
                solarSystem: {
                    sun: '#FF4757',
                    planets: ['#FF3838', '#FF6348', '#FF4500', '#DC143C']
                }
            },
            hacker: {
                name: 'Hacker',
                colors: {
                    primary: '#00FF41',
                    secondary: '#00CC33',
                    accent: '#00FF00',
                    background: 'linear-gradient(135deg, #000000 0%, #001100 50%, #003300 100%)',
                    text: '#00FF41',
                    glow: '#00FF41'
                },
                particles: '#00FF00',
                solarSystem: {
                    sun: '#00FF41',
                    planets: ['#00CC33', '#00FF00', '#32CD32', '#228B22']
                }
            },
            dreamy: {
                name: 'Dreamy',
                colors: {
                    primary: '#E6B3FF',
                    secondary: '#CC99FF',
                    accent: '#FFB3E6',
                    background: 'linear-gradient(135deg, #E6B3FF 0%, #CC99FF 50%, #FFB3E6 100%)',
                    text: '#4A4A4A',
                    glow: '#E6B3FF'
                },
                particles: '#FFB3E6',
                solarSystem: {
                    sun: '#E6B3FF',
                    planets: ['#CC99FF', '#FFB3E6', '#DDA0DD', '#D8BFD8']
                }
            }
        };
        
        this.init();
    }

    init() {
        this.createMoodSelector();
        this.setupAutoTheme();
        this.setupWeatherAPI();
        this.applyTheme(this.getTimeBasedTheme());
        this.setupNavbarHover();
    }

    createMoodSelector() {
        const moodSelector = document.createElement('div');
        moodSelector.className = 'aura-mood-selector';
        moodSelector.innerHTML = `
            <div class="mood-panel">
                <h3>🎨 AURA.MoodEngine</h3>
                <div class="mood-options">
                    <button class="mood-btn" data-mood="auto" data-tooltip="Auto-adjusts theme based on time of day">🔄 Auto</button>
                    <button class="mood-btn" data-mood="calm" data-tooltip="Soft green tones for relaxation">😌 Calm</button>
                    <button class="mood-btn" data-mood="energetic" data-tooltip="Vibrant red and orange for high energy">⚡ Energetic</button>
                    <button class="mood-btn" data-mood="hacker" data-tooltip="Matrix-style green on black">💻 Hacker</button>
                    <button class="mood-btn" data-mood="dreamy" data-tooltip="Soft lavender and pink dreamy vibes">💫 Dreamy</button>
                </div>
                <div class="current-theme">
                    <span id="current-theme-name">Auto Mode</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(moodSelector);
        
        // Add event listeners
        moodSelector.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const mood = e.target.dataset.mood;
                this.setMood(mood);
            });
        });
        
        // Add styles
        this.addMoodSelectorStyles();
        
        // Initially hide the mood selector
        moodSelector.style.display = 'none';
    }

    addMoodSelectorStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .aura-mood-selector {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                font-family: 'Orbitron', monospace;
            }
            
            .mood-panel {
                background: rgba(0, 0, 0, 0.8);
                border: 2px solid var(--glow-color, #00D4FF);
                border-radius: 15px;
                padding: 15px;
                backdrop-filter: blur(10px);
                box-shadow: 0 0 20px var(--glow-color, #00D4FF);
                transition: all 0.3s ease;
            }
            
            .mood-panel h3 {
                color: var(--glow-color, #00D4FF);
                margin: 0 0 10px 0;
                font-size: 14px;
                text-align: center;
            }
            
            .mood-options {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .mood-btn {
                background: transparent;
                border: 1px solid var(--glow-color, #00D4FF);
                color: var(--glow-color, #00D4FF);
                padding: 8px 12px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 12px;
                transition: all 0.3s ease;
                font-family: 'Orbitron', monospace;
            }
            
            .mood-btn:hover {
                background: var(--glow-color, #00D4FF);
                color: #000;
                box-shadow: 0 0 10px var(--glow-color, #00D4FF);
            }
            
            .mood-btn.active {
                background: var(--glow-color, #00D4FF);
                color: #000;
                box-shadow: 0 0 15px var(--glow-color, #00D4FF);
            }
            
            .current-theme {
                margin-top: 10px;
                text-align: center;
                font-size: 11px;
                color: var(--glow-color, #00D4FF);
                opacity: 0.8;
            }
        `;
        document.head.appendChild(style);
    }

    getTimeBasedTheme() {
        const hour = new Date().getHours();
        
        if (hour >= 5 && hour < 8) return 'dawn';
        if (hour >= 8 && hour < 18) return 'day';
        if (hour >= 18 && hour < 20) return 'dusk';
        return 'night';
    }

    setupAutoTheme() {
        // Check time every minute
        setInterval(() => {
            if (this.currentTheme === 'auto') {
                const timeTheme = this.getTimeBasedTheme();
                this.applyTheme(timeTheme);
            }
        }, 60000);
    }

    async setupWeatherAPI() {
        try {
            // Using OpenWeatherMap API (you'll need to add your API key)
            const response = await fetch('https://api.openweathermap.org/data/2.5/weather?q=London&appid=YOUR_API_KEY&units=metric');
            const data = await response.json();
            
            const weather = data.weather[0].main.toLowerCase();
            if (weather.includes('rain') || weather.includes('drizzle')) {
                this.weatherTheme = 'rainy';
            } else if (weather.includes('thunder') || weather.includes('storm')) {
                this.weatherTheme = 'stormy';
            } else if (weather.includes('clear') || weather.includes('sun')) {
                this.weatherTheme = 'sunny';
            }
        } catch (error) {
            console.log('Weather API not configured');
        }
    }

    setMood(mood) {
        // Update active button
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mood="${mood}"]`).classList.add('active');
        
        this.currentTheme = mood;
        
        if (mood === 'auto') {
            const timeTheme = this.getTimeBasedTheme();
            this.applyTheme(timeTheme);
            document.getElementById('current-theme-name').textContent = `Auto: ${this.themes[timeTheme].name}`;
        } else {
            this.applyTheme(mood);
            document.getElementById('current-theme-name').textContent = this.themes[mood].name;
        }
    }

    applyTheme(themeName) {
        const theme = this.themes[themeName];
        if (!theme) return;
        
        // Apply CSS custom properties
        const root = document.documentElement;
        root.style.setProperty('--primary-color', theme.colors.primary);
        root.style.setProperty('--secondary-color', theme.colors.secondary);
        root.style.setProperty('--accent-color', theme.colors.accent);
        root.style.setProperty('--glow-color', theme.colors.glow);
        root.style.setProperty('--text-color', theme.colors.text);
        root.style.setProperty('--background-gradient', theme.colors.background);
        
        // Update solar system colors
        this.updateSolarSystemColors(theme.solarSystem);
        
        // Update particles
        this.updateParticles(theme.particles);
        
        // Add transition effect
        this.addThemeTransition();
        
        // Update current theme display
        if (this.currentTheme === 'auto') {
            document.getElementById('current-theme-name').textContent = `Auto: ${theme.name}`;
        }
    }

    updateSolarSystemColors(solarSystem) {
        const sun = document.querySelector('.sun');
        if (sun) {
            sun.style.background = solarSystem.sun;
            sun.style.boxShadow = `0 0 50px ${solarSystem.sun}`;
        }
        
        const planets = document.querySelectorAll('.planet-core');
        planets.forEach((planet, index) => {
            if (solarSystem.planets[index]) {
                planet.style.background = solarSystem.planets[index];
                planet.style.boxShadow = `0 0 20px ${solarSystem.planets[index]}`;
            }
        });
    }

    updateParticles(color) {
        const particles = document.querySelector('.floating-particles');
        if (particles) {
            particles.style.setProperty('--particle-color', color);
        }
    }

    addThemeTransition() {
        document.body.style.transition = 'all 0.5s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 500);
    }

    setupNavbarHover() {
        const auraNavLink = document.querySelector('a[href="aura-demo.html"]');
        const moodSelector = document.querySelector('.aura-mood-selector');
        
        if (!auraNavLink || !moodSelector) return;
        
        let isOverLink = false;
        let isOverDropdown = false;

        // Show mood selector on navbar hover
        auraNavLink.addEventListener('mouseenter', () => {
            isOverLink = true;
            this.showMoodSelector();
        });
        auraNavLink.addEventListener('mouseleave', () => {
            isOverLink = false;
            setTimeout(() => {
                if (!isOverDropdown) this.hideMoodSelector();
            }, 10);
        });
        moodSelector.addEventListener('mouseenter', () => {
            isOverDropdown = true;
            this.showMoodSelector();
        });
        moodSelector.addEventListener('mouseleave', () => {
            isOverDropdown = false;
            setTimeout(() => {
                if (!isOverLink) this.hideMoodSelector();
            }, 10);
        });
    }

    showMoodSelector() {
        const moodSelector = document.querySelector('.aura-mood-selector');
        if (!moodSelector) return;
        // Position the selector near the navbar
        const auraNavLink = document.querySelector('a[href="aura-demo.html"]');
        if (auraNavLink) {
            const rect = auraNavLink.getBoundingClientRect();
            moodSelector.style.left = (rect.left - 200) + 'px';
            moodSelector.style.top = (rect.bottom + 10) + 'px';
        }
        // Show with animation
        moodSelector.style.display = 'block';
        moodSelector.style.opacity = '0';
        moodSelector.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            moodSelector.style.opacity = '1';
            moodSelector.style.transform = 'translateY(0)';
        }, 10);
    }

    hideMoodSelector() {
        const moodSelector = document.querySelector('.aura-mood-selector');
        if (!moodSelector) return;
        // Hide with animation
        moodSelector.style.opacity = '0';
        moodSelector.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            moodSelector.style.display = 'none';
        }, 300);
    }

    clearMoodSelectorTimer() {
        // This method is no longer needed as there are no timers for auto-collapse
    }
}

// Initialize AURA.MoodEngine when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.auraMoodEngine = new AURAMoodEngine();
}); 
