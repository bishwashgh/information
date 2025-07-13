class MoodSystem {
    constructor() {
        this.moods = {
            happy: {
                name: 'Happy',
                emoji: '🌞',
                quotes: [
                    "Life is better when you're laughing.",
                    "Joy is the secret sauce of life.",
                    "Let's make today amazing!"
                ],
                music: 'happy.mp3'
            },
            calm: {
                name: 'Calm',
                emoji: '🌙',
                quotes: [
                    "Peace is a journey, not a destination.",
                    "Find tranquility in the moment.",
                    "Silence is golden."
                ],
                music: 'calm.mp3'
            },
            focused: {
                name: 'Focused',
                emoji: '⚡',
                quotes: [
                    "Focus breeds excellence.",
                    "Stay sharp, stay focused.",
                    "Intensity creates results."
                ],
                music: 'focused.mp3'
            },
            melancholic: {
                name: 'Melancholic',
                emoji: '🌧️',
                quotes: [
                    "In sadness, there's beauty.",
                    "Reflection brings growth.",
                    "Every cloud has a silver lining."
                ],
                music: 'melancholic.mp3'
            },
            mysterious: {
                name: 'Mysterious',
                emoji: '🧙',
                quotes: [
                    "The unknown is where magic happens.",
                    "Mysteries are life's spice.",
                    "Discover the hidden paths."
                ],
                music: 'mysterious.mp3'
            }
        };

        this.currentMood = null;
        this.musicPlayer = null;
        this.weatherApi = 'https://api.openweathermap.org/data/2.5/weather';
        this.weatherApiKey = 'YOUR_API_KEY'; // Replace with actual API key
        this.chitwanLat = 27.6671;
        this.chitwanLon = 84.7272;

        this.initMoodSystem();
    }

    initMoodSystem() {
        // Create mood widget
        this.createMoodWidget();
        
        // Set initial mood based on time/weather
        this.determineInitialMood();

        // Add mood change listeners
        this.addMoodListeners();

        // Add weather update
        this.updateWeatherMood();
    }

    createMoodWidget() {
        // Remove existing mood widget if it exists
        const existingWidget = document.querySelector('.mood-widget');
        if (existingWidget) {
            existingWidget.remove();
        }

        const widget = document.createElement('div');
        widget.className = 'mood-widget';
        widget.textContent = 'Mood: 🌞 Happy';
        document.body.appendChild(widget);

        widget.addEventListener('click', () => this.cycleMood());
    }

    determineInitialMood() {
        const hour = new Date().getHours();
        
        // Morning (6-12): Happy
        if (hour >= 6 && hour < 12) {
            this.setMood('happy');
        }
        // Afternoon (12-18): Focused
        else if (hour >= 12 && hour < 18) {
            this.setMood('focused');
        }
        // Evening (18-24): Calm
        else if (hour >= 18 || hour < 6) {
            this.setMood('calm');
        }
    }

    async updateWeatherMood() {
        try {
            const response = await fetch(`${this.weatherApi}?lat=${this.chitwanLat}&lon=${this.chitwanLon}&appid=${this.weatherApiKey}`);
            const data = await response.json();
            
            const weatherId = data.weather[0].id;
            
            // Update mood based on weather conditions
            if (weatherId >= 200 && weatherId < 300) { // Thunderstorm
                this.setMood('mysterious');
            } else if (weatherId >= 300 && weatherId < 600) { // Drizzle/Rain
                this.setMood('melancholic');
            } else if (weatherId >= 600 && weatherId < 700) { // Snow
                this.setMood('calm');
            } else if (weatherId >= 800) { // Clear/Sunny
                this.setMood('happy');
            }
        } catch (error) {
            console.error('Weather API error:', error);
        }
    }

    cycleMood() {
        const moods = Object.keys(this.moods);
        const currentIndex = moods.indexOf(this.currentMood);
        const nextIndex = (currentIndex + 1) % moods.length;
        this.setMood(moods[nextIndex]);
    }

    setMood(mood) {
        if (this.currentMood === mood) return;

        document.documentElement.setAttribute('data-mood', mood);
        const moodInfo = this.moods[mood];
        
        // Update mood widget
        const widget = document.querySelector('.mood-widget');
        widget.textContent = `Mood: ${moodInfo.emoji} ${moodInfo.name}`;

        // Update quote
        this.updateMoodQuote(moodInfo.quotes);

        // Update music
        this.updateMoodMusic(moodInfo.music);

        // Add mood-specific background animations
        this.addMoodBackgroundAnimation(mood);

        // Update current mood
        this.currentMood = mood;
    }

    addMoodBackgroundAnimation(mood) {
        const body = document.body;
        
        // Clear any existing animations
        body.style.animation = '';
        
        // Add mood-specific animations
        switch(mood) {
            case 'happy':
                body.style.animation = 'happyBackground 10s infinite linear';
                break;
            case 'calm':
                body.style.animation = 'calmBackground 30s infinite ease-in-out';
                break;
            case 'focused':
                body.style.animation = 'focusedBackground 5s infinite';
                break;
            case 'melancholic':
                body.style.animation = 'melancholicBackground 20s infinite ease-in-out';
                break;
            case 'mysterious':
        }
    }

    updateMoodQuote(quotes) {
        const quote = document.createElement('div');
        quote.className = 'mood-quote';
        quote.textContent = quotes[Math.floor(Math.random() * quotes.length)];
        document.body.appendChild(quote);

        // Remove old quote after 5 seconds
        setTimeout(() => {
            const oldQuote = document.querySelector('.mood-quote');
            if (oldQuote) oldQuote.remove();
        }, 5000);
    }

    addMoodListeners() {
        // Add keyboard shortcut for mood change
        document.addEventListener('keydown', (e) => {
            if (e.key === 'm') {
                this.cycleMood();
            }
        });

        // Add admin override panel
        if (window.location.hostname === 'localhost') {
            this.addAdminPanel();
        }
    }

    addAdminPanel() {
        const panel = document.createElement('div');
        panel.className = 'admin-panel';
        panel.innerHTML = `
            <h3>Admin Mood Control</h3>
            <select id="mood-selector">
                ${Object.entries(this.moods).map(([key, mood]) => 
                    `<option value="${key}">${mood.emoji} ${mood.name}</option>`
                ).join('')}
            </select>
            <button id="mute-music">Toggle Music</button>
        `;
        document.body.appendChild(panel);

        const moodSelector = document.getElementById('mood-selector');
        moodSelector.value = this.currentMood;
        moodSelector.addEventListener('change', () => {
            this.setMood(moodSelector.value);
        });

        const muteButton = document.getElementById('mute-music');
        muteButton.addEventListener('click', () => {
            if (this.musicPlayer) {
                this.musicPlayer.muted = !this.musicPlayer.muted;
                muteButton.textContent = this.musicPlayer.muted ? 'Unmute Music' : 'Mute Music';
            }
        });
    }
}

// Initialize mood system
document.addEventListener('DOMContentLoaded', () => {
    new MoodSystem();
});
