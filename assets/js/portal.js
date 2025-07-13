const portals = document.querySelectorAll('.portal');
const modal = document.getElementById('modal');
const modalContent = document.getElementById('modal-content');
const closeBtn = document.getElementById('close-modal');

const contentData = {
  projects: `
    <h2>Suspicious</h2>
    <p>Access restricted. Enter password to view something crispy.</p>
    <form id="key-form">
      <input type="password" id="key" placeholder="Enter Password" required style="width:100%;padding:10px;margin:10px 0;border-radius:5px;border:none;"/>
      <button type="submit" style="padding:10px 20px; background:#d4af37; border:none; cursor:pointer; border-radius:5px;">Decrypt</button>
    </form>
    <div id="project-list" style="margin-top:1rem;"></div>
  `,
  about: `
    <h2>About Bishwas</h2>
    <p>Hello! I'm Bishwas Ghimire, a hardcore developer and lifelong learner from Chitwan, Nepal.</p>
    <p>I’m passionate about coding and technology.</p>
  `,
  achievements: `
    <h2>Milestones Achieved</h2>
    <ul>

      <li>Developed encrypted project access system.</li>
      <li>Integrated AI assistant hologram concept.</li>
      <li>Active contributor in Nepali tech communities.</li>
    </ul>
  `,
  ai: `
    <h2>AI Holographic Assistant</h2>
    <p>Ask me anything, or send a message below:</p>
    <form id="ai-form">
      <input type="text" id="user-query" placeholder="Type your question..." required style="width:100%;padding:10px;margin:10px 0;border-radius:5px;border:none;"/>
      <button type="submit" style="padding:10px 20px; background:#d4af37; border:none; cursor:pointer; border-radius:5px;">Send</button>
    </form>
    <div id="ai-response" style="margin-top:1rem; color:#ffd700;"></div>
  `
};

portals.forEach(portal => {
  portal.addEventListener('click', () => {
    const target = portal.getAttribute('data-target');
    modalContent.innerHTML = contentData[target];
    modal.classList.remove('hidden');
    if(target === 'projects'){
      document.getElementById('key-form').addEventListener('submit', e => {
        e.preventDefault();
        const key = document.getElementById('key').value;
        // Simulated key check
        if(key === '231511') {
          document.getElementById('project-list').innerHTML = `
            <ul>
              <li><a href="/projects/project1.html" target="_blank" style="color:#ffd700;">Project Alpha (Encrypted)</a></li>
              <li><a href="/projects/project2.html" target="_blank" style="color:#ffd700;">Project Beta (Encrypted)</a></li>
            </ul>`;
        } else {
          document.getElementById('project-list').innerHTML = '<p style="color:#ff5555;">Invalid Password. Try again.</p>';
        }
      });
    } else if(target === 'ai'){
      const aiForm = document.getElementById('ai-form');
      const aiResponse = document.getElementById('ai-response');
      const factDisplay = document.getElementById('fact-display');
      const newFactButton = document.getElementById('new-fact');

      // Add styles for hologram effect
      document.head.innerHTML += `
        <style>
          .hologram-container {
            background: linear-gradient(145deg, #000000, #1a1a1a);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            animation: hologramGlow 2s infinite;
          }

          .ai-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
          }

          .ai-form input {
            flex: 1;
            padding: 10px;
            border: 2px solid #00ff00;
            border-radius: 5px;
            background: #1a1a1a;
            color: #00ff00;
          }

          .ai-form button {
            padding: 10px 20px;
            background: linear-gradient(145deg, #00ff00, #00cc00);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #000;
            animation: hologramPulse 2s infinite;
          }

          .ai-response {
            margin-bottom: 20px;
            color: #ffd700;
            animation: hologramText 2s infinite;
          }

          .ai-facts {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 10px;
          }

          #fact-display {
            margin: 10px 0;
            padding: 10px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 5px;
          }

          #new-fact {
            padding: 8px 15px;
            background: linear-gradient(145deg, #ffd700, #ffaa00);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #000;
          }

          @keyframes hologramGlow {
            0% { border-color: #00ff00; }
            50% { border-color: #00cc00; }
            100% { border-color: #00ff00; }
          }

          @keyframes hologramPulse {
            0% { box-shadow: 0 0 20px rgba(0, 255, 0, 0.8); }
            50% { box-shadow: 0 0 40px rgba(0, 255, 0, 1); }
            100% { box-shadow: 0 0 20px rgba(0, 255, 0, 0.8); }
          }

          @keyframes hologramText {
            0% { text-shadow: 0 0 5px rgba(0, 255, 0, 0.8); }
            50% { text-shadow: 0 0 10px rgba(0, 255, 0, 1); }
            100% { text-shadow: 0 0 5px rgba(0, 255, 0, 0.8); }
          }
        </style>
      `;

      // AI responses
      const responses = {
        greetings: [
          "Hello! I am your Holographic AI Assistant.",
          "Greetings! How can I assist you today?",
          "Welcome! I'm here to help with your digital needs."
        ],
        thanks: [
          "You're welcome! I'm here to help.",
          "No problem! Need anything else?",
          "Glad to help! What else can I assist you with?"
        ],
        about: [
          "I'm your Holographic AI Assistant, designed to help with various tasks.",
          "I can assist with programming, learning, and general questions.",
          "I'm here to help you with anything you need!"
        ],
        programming: [
          "I can help you with coding! Just let me know what you need.",
          "Let me know which programming language you're using.",
          "I can assist with debugging and code explanations."
        ],
        learning: [
          "I can help you learn new things! What topic interests you?",
          "What would you like to explore today?",
          "I can provide explanations and examples for any topic."
        ],
        time: [
          "The current time is ${new Date().toLocaleTimeString()}",
          "It's currently ${new Date().toLocaleTimeString()}"
        ],
        jokes: [
          "Why was the math book sad? Because it had too many problems.",
          "Why don't scientists trust atoms? Because they make up everything.",
          "Why did the computer go to the doctor? Because it had a virus."
        ],
        facts: [
          "Did you know that there are more possible iterations of a game of chess than there are atoms in the known universe?",
          "The world's first computer programmer was Ada Lovelace in 1842.",
          "The first computer bug was an actual moth trapped in a relay of the Harvard Mark II computer in 1947."
        ]
      };

      // Get random response from array
      function randomResponse(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
      }

      // Get AI response based on input
      function getAIResponse(input) {
        const lowerInput = input.toLowerCase();
        
        if (lowerInput.includes('hello') || lowerInput.includes('hi') || lowerInput.includes('hey')) {
          return randomResponse(responses.greetings);
        }
        
        if (lowerInput.includes('thank') || lowerInput.includes('thanks')) {
          return randomResponse(responses.thanks);
        }
        
        if (lowerInput.includes('who are you') || lowerInput.includes('what can you do')) {
          return randomResponse(responses.about);
        }
        
        if (lowerInput.includes('programming') || lowerInput.includes('code') || lowerInput.includes('coding')) {
          return randomResponse(responses.programming);
        }
        
        if (lowerInput.includes('learn') || lowerInput.includes('teach me')) {
          return randomResponse(responses.learning);
        }
        
        if (lowerInput.includes('time') || lowerInput.includes('what time is it')) {
          return randomResponse(responses.time);
        }
        
        if (lowerInput.includes('joke') || lowerInput.includes('funny')) {
          return randomResponse(responses.jokes);
        }
        
        return randomResponse(responses.default);
      }

      // Handle form submission
      aiForm.addEventListener('submit', e => {
        e.preventDefault();
        const question = aiForm.querySelector('input').value;
        
        aiResponse.textContent = "Loading response...";
        setTimeout(() => {
          const response = getAIResponse(question);
          aiResponse.textContent = response;
        }, 500);
        aiForm.reset();
      });

      // Handle new fact button
      newFactButton.addEventListener('click', () => {
        factDisplay.textContent = randomResponse(responses.facts);
      });

      // Show initial welcome message
      aiResponse.textContent = randomResponse(responses.greetings);
    }
  });
});

closeBtn.addEventListener('click', () => {
  modal.classList.add('hidden');
  modalContent.innerHTML = '';
});
