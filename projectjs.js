async function loadGoogleNews() {
      const rssUrl = 'https://news.google.com/rss/search?q=Nepal&hl=ne&gl=NP&ceid=NP:ne';
      const apiUrl = `https://api.rss2json.com/v1/api.json?rss_url=${encodeURIComponent(rssUrl)}`;
      try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        const container = document.getElementById('google-news-container');
        container.innerHTML = '';
        data.items.slice(0, 10).forEach(item => {
          const div = document.createElement('div');
          div.className = 'news-item';
          div.tabIndex = 0;
          div.innerHTML = `
            <h3><a href="${item.link}" target="_blank" rel="noopener noreferrer">${item.title}</a></h3>
            <p>${new Date(item.pubDate).toLocaleString()}</p>
          `;
          container.appendChild(div);
        });
      } catch (error) {
        document.getElementById('google-news-container').innerText = 'Failed to load news.';
      }
    }
    loadGoogleNews();

    // Animate section titles and projects on scroll into view
    const observerOptions = {
      threshold: 0.2
    };
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, observerOptions);

    document.querySelectorAll('section').forEach(section => {
      sectionObserver.observe(section);
    });

    const projectObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          projectObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    document.querySelectorAll('.project').forEach(proj => {
      projectObserver.observe(proj);
    });
