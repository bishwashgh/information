// Smooth scroll for internal links (if you add any later)
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', e => {
    e.preventDefault();
    document.querySelector(anchor.getAttribute('href')).scrollIntoView({
      behavior: 'smooth'
    });
  });
});

// Fetch GitHub stats (replace 'bishwasghimire' with your GitHub username)
const githubStatsContainer = document.getElementById('github-stats');
const githubUsername = 'bishwasghimire';

async function fetchGitHubStats() {
  try {
    const res = await fetch(`https://api.github.com/users/${githubUsername}`);
    if (!res.ok) throw new Error('Failed to fetch');
    const data = await res.json();
    githubStatsContainer.innerHTML = `
      <p>Public Repos: <strong>${data.public_repos}</strong></p>
      <p>Followers: <strong>${data.followers}</strong></p>
      <p>Following: <strong>${data.following}</strong></p>
      <p>GitHub Profile: <a href="${data.html_url}" target="_blank" style="color:#00ffff;">@${githubUsername}</a></p>
    `;
  } catch (e) {
    githubStatsContainer.innerHTML = '<p>Failed to load GitHub stats.</p>';
  }
}
fetchGitHubStats();

// Contact form submission
const form = document.getElementById('contact-form');
const status = document.getElementById('form-status');

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    try {
        const response = await fetch('contact.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });

        // Read the response body just once
        const text = await response.text();
        console.log('Response text:', text); // Debug log
        
        try {
            // Try to parse as JSON
            const result = JSON.parse(text);
            console.log('Parsed result:', result); // Debug log
            
            if (response.ok) {
                status.textContent = result.message || 'Message sent successfully';
                if (result.success) {
                    form.reset();
                    status.style.color = '#00ff00';
                }
            } else {
                status.textContent = result.error || `Server error: Status ${response.status}`;
                status.style.color = '#ff0000';
            }
        } catch (jsonError) {
            console.error('JSON parsing error:', jsonError);
            // If JSON parsing fails, treat as error
            status.textContent = 'Error: Invalid response format';
            status.style.color = '#ff0000';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        status.textContent = 'Error: ' + error.message;
        status.style.color = '#ff0000';
    }
});
