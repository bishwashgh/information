// CyberMatrix Dashboard JavaScript

class CyberMatrixDashboard {
  constructor() {
    this.isDarkMode = true;
    this.commandLineVisible = false;
    this.matrixRain = null;
    this.commandHistory = [];
    this.commandIndex = -1;
    
    this.init();
  }

  init() {
    this.createMatrixRain();
    this.initializeEventListeners();
    this.initializeCommandLine();
    this.initializeSoundEffects();
    this.startLogUpdates();
  }

  // Matrix Rain Animation
  createMatrixRain() {
    const matrixContainer = document.getElementById('matrix-rain');
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()_+-=[]{}|;:,.<>?';
    const columns = Math.floor(window.innerWidth / 20);
    
    for (let i = 0; i < columns; i++) {
      this.createMatrixColumn(matrixContainer, i, characters);
    }
  }

  createMatrixColumn(container, columnIndex, characters) {
    const column = document.createElement('div');
    column.style.position = 'absolute';
    column.style.left = (columnIndex * 20) + 'px';
    column.style.top = '-100vh';
    
    const speed = Math.random() * 3 + 2;
    const delay = Math.random() * 5;
    
    column.style.animation = `matrixFall ${speed}s linear infinite`;
    column.style.animationDelay = `${delay}s`;
    
    // Create random characters
    const charCount = Math.floor(Math.random() * 20) + 10;
    for (let i = 0; i < charCount; i++) {
      const char = document.createElement('div');
      char.className = 'matrix-character';
      char.textContent = characters[Math.floor(Math.random() * characters.length)];
      char.style.opacity = Math.random() * 0.8 + 0.2;
      char.style.animationDelay = `${i * 0.1}s`;
      column.appendChild(char);
    }
    
    container.appendChild(column);
  }

  // Event Listeners
  initializeEventListeners() {
    // Mode toggle
    const modeToggle = document.getElementById('mode-toggle');
    modeToggle.addEventListener('click', () => this.toggleMode());

    // Command line toggle
    const commandToggle = document.getElementById('command-toggle');
    commandToggle.addEventListener('click', () => this.toggleCommandLine());

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => this.handleKeyPress(e));

    // Status item interactions
    const statusItems = document.querySelectorAll('.status-item');
    statusItems.forEach(item => {
      item.addEventListener('mouseenter', () => this.showSecretMessage(item.dataset.message));
      item.addEventListener('mouseleave', () => this.hideSecretMessage());
    });

    // Stream item interactions
    const streamItems = document.querySelectorAll('.stream-item');
    streamItems.forEach(item => {
      item.addEventListener('click', () => this.triggerGlitch(item));
    });

    // Command line close
    const commandClose = document.querySelector('.command-close');
    commandClose.addEventListener('click', () => this.toggleCommandLine());
  }

  // Mode Toggle
  toggleMode() {
    this.isDarkMode = !this.isDarkMode;
    const body = document.body;
    const modeToggle = document.getElementById('mode-toggle');
    const toggleIcon = modeToggle.querySelector('.toggle-icon');
    const toggleText = modeToggle.querySelector('.toggle-text');

    if (this.isDarkMode) {
      body.removeAttribute('data-theme');
      toggleIcon.textContent = '🌙';
      toggleText.textContent = 'Dark Mode';
    } else {
      body.setAttribute('data-theme', 'light');
      toggleIcon.textContent = '☀️';
      toggleText.textContent = 'Light Mode';
    }

    this.playSound('terminal');
  }

  // Command Line
  initializeCommandLine() {
    const commandInput = document.getElementById('command-input');
    commandInput.addEventListener('keydown', (e) => this.handleCommandInput(e));
  }

  toggleCommandLine() {
    const commandLine = document.getElementById('command-line');
    this.commandLineVisible = !this.commandLineVisible;

    if (this.commandLineVisible) {
      commandLine.classList.remove('hidden');
      document.getElementById('command-input').focus();
    } else {
      commandLine.classList.add('hidden');
    }

    this.playSound('terminal');
  }

  handleCommandInput(e) {
    if (e.key === 'Enter') {
      const input = e.target.value.trim();
      if (input) {
        this.executeCommand(input);
        e.target.value = '';
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      this.navigateCommandHistory('up');
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      this.navigateCommandHistory('down');
    }
  }

  executeCommand(command) {
    this.commandHistory.push(command);
    this.commandIndex = this.commandHistory.length;

    const output = document.querySelector('.command-output');
    const outputLine = document.createElement('div');
    outputLine.className = 'output-line';
    outputLine.textContent = `cybermatrix:~$ ${command}`;
    output.appendChild(outputLine);

    // Process command
    const response = this.processCommand(command);
    if (response) {
      const responseLine = document.createElement('div');
      responseLine.className = 'output-line';
      responseLine.textContent = response;
      output.appendChild(responseLine);
    }

    // Scroll to bottom
    output.scrollTop = output.scrollHeight;
    this.playSound('terminal');
  }

  processCommand(command) {
    const cmd = command.toLowerCase();
    
    switch (cmd) {
      case 'help':
        return `Available commands:
- help: Show this help
- clear: Clear terminal
- status: Show system status
- matrix: Toggle matrix rain
- glitch: Trigger glitch effect
- reboot: Simulate system reboot
- hack: Initiate hack sequence
- exit: Close terminal`;
      
      case 'clear':
        document.querySelector('.command-output').innerHTML = '';
        return null;
      
      case 'status':
        return `System Status:
✓ Memory Core: Online
✓ Neural Network: Connected
✓ Quantum Link: Stable
✓ Interface: Ready
All systems operational.`;
      
      case 'matrix':
        this.toggleMatrixRain();
        return 'Matrix rain toggled.';
      
      case 'glitch':
        this.triggerGlobalGlitch();
        return 'Glitch effect triggered.';
      
      case 'reboot':
        this.simulateReboot();
        return 'System reboot initiated...';
      
      case 'hack':
        this.initiateHack();
        return 'Hack sequence initiated...';
      
      case 'exit':
        this.toggleCommandLine();
        return null;
      
      default:
        return `Command not found: ${command}. Type 'help' for available commands.`;
    }
  }

  navigateCommandHistory(direction) {
    const input = document.getElementById('command-input');
    
    if (direction === 'up' && this.commandIndex > 0) {
      this.commandIndex--;
      input.value = this.commandHistory[this.commandIndex];
    } else if (direction === 'down' && this.commandIndex < this.commandHistory.length - 1) {
      this.commandIndex++;
      input.value = this.commandHistory[this.commandIndex];
    } else if (direction === 'down' && this.commandIndex === this.commandHistory.length - 1) {
      this.commandIndex = this.commandHistory.length;
      input.value = '';
    }
  }

  // Keyboard Shortcuts
  handleKeyPress(e) {
    // Tilde key for command line
    if (e.key === '`' || e.key === '~') {
      e.preventDefault();
      this.toggleCommandLine();
    }
    
    // Ctrl+Alt+C for command line
    if (e.ctrlKey && e.altKey && e.key === 'c') {
      e.preventDefault();
      this.toggleCommandLine();
    }
    
    // Escape to close command line
    if (e.key === 'Escape' && this.commandLineVisible) {
      this.toggleCommandLine();
    }
  }

  // Secret Messages
  showSecretMessage(message) {
    const secretMessage = document.getElementById('secret-message');
    const messageText = secretMessage.querySelector('.message-text');
    
    messageText.textContent = message;
    secretMessage.classList.remove('hidden');
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
      this.hideSecretMessage();
    }, 3000);
  }

  hideSecretMessage() {
    const secretMessage = document.getElementById('secret-message');
    secretMessage.classList.add('hidden');
  }

  // Glitch Effects
  triggerGlitch(item) {
    const soundId = item.dataset.sound;
    this.playSound(soundId);
    
    // Add glitch effect
    item.classList.add('glitch');
    setTimeout(() => {
      item.classList.remove('glitch');
    }, 300);
    
    // Random data update
    const dataBytes = item.querySelectorAll('.data-byte');
    dataBytes.forEach(byte => {
      const randomHex = Math.floor(Math.random() * 256).toString(16).toUpperCase().padStart(2, '0');
      byte.textContent = `0x${randomHex}`;
    });
  }

  triggerGlobalGlitch() {
    const dashboard = document.querySelector('.dashboard-container');
    dashboard.classList.add('glitch');
    setTimeout(() => {
      dashboard.classList.remove('glitch');
    }, 500);
    
    this.playSound('glitch1');
  }

  // Matrix Rain Control
  toggleMatrixRain() {
    const matrixContainer = document.getElementById('matrix-rain');
    if (matrixContainer.style.display === 'none') {
      matrixContainer.style.display = 'block';
      this.createMatrixRain();
    } else {
      matrixContainer.style.display = 'none';
      matrixContainer.innerHTML = '';
    }
  }

  // System Simulations
  simulateReboot() {
    const logContainer = document.querySelector('.log-container');
    const rebootEntry = document.createElement('div');
    rebootEntry.className = 'log-entry';
    rebootEntry.innerHTML = `
      <span class="log-time">[${this.getCurrentTime()}]</span>
      <span class="log-message">System reboot initiated...</span>
    `;
    logContainer.appendChild(rebootEntry);
    
    // Simulate reboot sequence
    setTimeout(() => {
      const entries = [
        'Shutting down services...',
        'Memory cleared...',
        'Reinitializing core systems...',
        'Loading neural interface...',
        'System reboot complete'
      ];
      
      entries.forEach((entry, index) => {
        setTimeout(() => {
          const newEntry = document.createElement('div');
          newEntry.className = 'log-entry';
          newEntry.innerHTML = `
            <span class="log-time">[${this.getCurrentTime()}]</span>
            <span class="log-message">${entry}</span>
          `;
          logContainer.appendChild(newEntry);
          logContainer.scrollTop = logContainer.scrollHeight;
        }, index * 1000);
      });
    }, 1000);
  }

  initiateHack() {
    const hackMessages = [
      'Bypassing firewall...',
      'Accessing mainframe...',
      'Decrypting security protocols...',
      'Injecting payload...',
      'Hack successful!'
    ];
    
    const logContainer = document.querySelector('.log-container');
    hackMessages.forEach((message, index) => {
      setTimeout(() => {
        const newEntry = document.createElement('div');
        newEntry.className = 'log-entry';
        newEntry.innerHTML = `
          <span class="log-time">[${this.getCurrentTime()}]</span>
          <span class="log-message" style="color: var(--neon-gold);">${message}</span>
        `;
        logContainer.appendChild(newEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
        this.playSound('glitch2');
      }, index * 800);
    });
  }

  // Log Updates
  startLogUpdates() {
    setInterval(() => {
      this.addRandomLogEntry();
    }, 10000); // Add log entry every 10 seconds
  }

  addRandomLogEntry() {
    const logMessages = [
      'Monitoring system performance...',
      'Scanning for vulnerabilities...',
      'Updating security protocols...',
      'Optimizing neural pathways...',
      'Synchronizing quantum states...',
      'Analyzing data streams...',
      'Maintaining cyber interface...',
      'Calibrating sensors...'
    ];
    
    const randomMessage = logMessages[Math.floor(Math.random() * logMessages.length)];
    const logContainer = document.querySelector('.log-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'log-entry';
    newEntry.innerHTML = `
      <span class="log-time">[${this.getCurrentTime()}]</span>
      <span class="log-message">${randomMessage}</span>
    `;
    logContainer.appendChild(newEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
  }

  // Sound Effects
  initializeSoundEffects() {
    // Preload audio elements
    this.audioElements = {
      glitch1: document.getElementById('glitch1'),
      glitch2: document.getElementById('glitch2'),
      glitch3: document.getElementById('glitch3'),
      terminal: document.getElementById('terminal-sound')
    };
  }

  playSound(soundId) {
    const audio = this.audioElements[soundId];
    if (audio) {
      audio.currentTime = 0;
      audio.play().catch(e => console.log('Audio play failed:', e));
    }
  }

  // Utility Functions
  getCurrentTime() {
    const now = new Date();
    return now.toTimeString().split(' ')[0];
  }
}

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new CyberMatrixDashboard();
});

// Add some Easter eggs
document.addEventListener('keydown', (e) => {
  // Konami code
  if (e.key === 'ArrowUp' && e.ctrlKey) {
    console.log('🎮 Konami code detected!');
    document.body.style.animation = 'glitch 1s ease-in-out';
    setTimeout(() => {
      document.body.style.animation = '';
    }, 1000);
  }
});

// Easter egg: Click on logo multiple times
let logoClickCount = 0;
document.querySelector('.logo').addEventListener('click', () => {
  logoClickCount++;
  if (logoClickCount >= 5) {
    alert('🎉 You found the secret! Welcome to the Matrix!');
    logoClickCount = 0;
  }
}); 
