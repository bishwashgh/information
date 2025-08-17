<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bishwas Ghimire — Digital Creator</title>
  <meta name="description" content="Portfolio of Bishwas Ghimire — Digital creator from Bharatpur, Chitwan. View projects, skills, and get in touch." />
  <meta name="keywords" content="Bishwas Ghimire, Digital Creator, Bharatpur, Chitwan, portfolio, web developer, designer" />

  <!-- Open Graph -->
  <meta property="og:title" content="Bishwas Ghimire — Digital Creator" />
  <meta property="og:description" content="Modern, responsive portfolio showcasing projects, skills, and contact." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://bishwasghimire.com.np" />
  <meta property="og:image" content="assets/og-banner.png" />

  <!-- Favicon (inline SVG data URI) -->
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%2326A69A'/%3E%3Ctext x='50%' y='54%' font-size='34' text-anchor='middle' fill='white' font-family='Montserrat,Arial,sans-serif'%3EB%3C/text%3E%3C/svg%3E">

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-tnQ+KQ6uC3JvO9bH8n3W7b8lG6Y6XhF2wQ0X8J9q1p2Y2Z+v4l8hC8Cj2gYQJXz0gXc1rJcQx5m1s5m2wS3s0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- AOS (Animate on Scroll) -->
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

  <link rel="stylesheet" href="styles.css" />
</head>
<body class="preload">
  <header class="site-header" id="top">
    <div class="container nav-wrap">
      <a class="brand" href="#top" aria-label="Bishwas Ghimire — Home">
        <span class="logo">BG</span>
        <span class="brand-text">Bishwas <strong>Ghimire</strong></span>
      </a>
      <nav class="nav" aria-label="Primary">
        <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
        <ul id="navLinks">
          <li><a href="#about">About</a></li>
          <li><a href="#projects">Projects</a></li>
          <li><a href="#testimonials">Testimonials</a></li>
          <li><a href="#contact">Contact</a></li>
          <li><button id="themeToggle" class="theme-toggle" aria-label="Toggle theme"><i class="fa-solid fa-moon"></i></button></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <!-- Hero -->
    <section class="hero" id="hero">
      <canvas id="matrix"></canvas>
      <div class="hero-inner container" data-aos="fade-up">
        <div class="profile-pic-container">
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRv2KlVJJZ25nBXS_wQbKILYBd2216z_zKM-Q&s" alt="Bishwas Ghimire" class="profile-pic">
        </div>
        <p class="eyebrow">Digital Creator • Bharatpur, Chitwan</p>
        <h1>Hi, I’m <span class="accent">Bishwas Ghimire</span> — crafting clean, modern, responsive experiences.</h1>
        <p class="sub">
          I blend design, code, and content to build delightful, fast, and accessible digital products.
        </p>
        <div class="cta-row">
          <a href="#projects" class="btn primary">View My Work</a>
          <a href="#contact" class="btn ghost">Contact Me</a>
        </div>
        <div class="typewriter" aria-live="polite">
          <span id="typewriter"></span>
        </div>
      </div>
    </section>

    <!-- About -->
    <section class="about section" id="about">
      <div class="container grid-2">
        <div data-aos="fade-right">
          <h2>About Me</h2>
          <p>
            I’m a digital creator from <strong>Bharatpur, Chitwan</strong>. I focus on building
            user-friendly interfaces, clear content, and smooth interactions. When I’m not shipping pixels,
            I’m probably exploring new tools, sketching ideas, or sipping great coffee.
          </p>
          <ul class="facts">
            <li><i class="fa-solid fa-location-dot"></i> Based in Bharatpur, Chitwan</li>
            <li><i class="fa-solid fa-envelope"></i> <a href="https://bishwasghimire.com.np" target="_blank" rel="noopener">bishwasghimire.com.np</a></li>
            <li><i class="fa-solid fa-phone"></i> <a href="tel:+9779866342404">+977 9866342404</a></li>
          </ul>
          <div class="socials">
            <a href="https://www.facebook.com/bishwas.ghimire.144" target="_blank" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="https://www.instagram.com/bishwas.ghimire.144/" target="_blank" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>
        <div class="skills" data-aos="fade-left">
          <h3>Skills</h3>
          <div class="skill-list">
            <span>HTML</span><span>CSS</span><span>JavaScript</span><span>PHP</span>
            <span>UI/UX</span><span>Responsive Design</span><span>SEO</span><span>Accessibility</span>
          </div>
          <div class="timeline">
            <div class="milestone">
              <span class="dot"></span>
              <div class="card">
                <h4>2023–Present</h4>
                <p>Creating modern, responsive web experiences.</p>
              </div>
            </div>
            <div class="milestone">
              <span class="dot"></span>
              <div class="card">
                <h4>Earlier</h4>
                <p>Learning, experimenting, and shipping side projects.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Projects -->
    <section class="projects section" id="projects">
      <div class="container">
        <div class="section-head">
          <h2>Selected Projects</h2>
          <div class="filters">
            <button class="chip active" data-filter="all">All</button>
            <button class="chip" data-filter="web">Web</button>
            <button class="chip" data-filter="uiux">UI/UX</button>
            <button class="chip" data-filter="content">Content</button>
          </div>
        </div>

        <div class="grid-3" id="projectGrid">
          <!-- Project Card Template -->
          <article class="card project" data-tags="web uiux">
            <img src="https://picsum.photos/seed/uiux/800/600" alt="Project mockup 1" loading="lazy">
            <div class="p-body">
              <h3>Minimal Web App</h3>
              <p>Clean UI with smooth animations and accessible interactions.</p>
              <p class="stack">HTML • CSS • JS</p>
              <div class="p-actions">
                <a class="btn small" href="#" aria-disabled="true">Live</a>
                <a class="btn small ghost" href="#" aria-disabled="true">GitHub</a>
              </div>
            </div>
          </article>

          <article class="card project" data-tags="web content">
            <img src="https://picsum.photos/seed/web/800/600" alt="Project mockup 2" loading="lazy">
            <div class="p-body">
              <h3>Landing Page</h3>
              <p>High-converting, fast, and delightful landing experience.</p>
              <p class="stack">HTML • CSS • JS</p>
              <div class="p-actions">
                <a class="btn small" href="#" aria-disabled="true">Live</a>
                <a class="btn small ghost" href="#" aria-disabled="true">GitHub</a>
              </div>
            </div>
          </article>

          <article class="card project" data-tags="uiux content">
            <img src="https://picsum.photos/seed/content/800/600" alt="Project mockup 3" loading="lazy">
            <div class="p-body">
              <h3>Style Guide</h3>
              <p>Design system with reusable components and tokens.</p>
              <p class="stack">Figma • UI/UX</p>
              <div class="p-actions">
                <a class="btn small" href="#" aria-disabled="true">Case Study</a>
                <a class="btn small ghost" href="#" aria-disabled="true">More</a>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials section" id="testimonials">
      <div class="container">
        <h2>What People Say</h2>
        <div class="slider" aria-roledescription="carousel">
          <div class="slide active">
            <blockquote>
              “Bishwas delivers clean, fast, and thoughtful work. Highly recommended.”
            </blockquote>
            <p class="author">— Happy Client</p>
          </div>
          <div class="slide">
            <blockquote>
              “Great sense of design and usability. Would collaborate again.”
            </blockquote>
            <p class="author">— Collaborator</p>
          </div>
          <div class="slide">
            <blockquote>
              “Communication was clear and the results were excellent.”
            </blockquote>
            <p class="author">— Project Partner</p>
          </div>
          <div class="slider-controls">
            <button class="prev" aria-label="Previous">&larr;</button>
            <button class="next" aria-label="Next">&rarr;</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact -->
    <section class="contact section" id="contact">
      <div class="container grid-2">
        <div data-aos="fade-right">
          <h2>Let’s Build Something</h2>
          <p>Have an idea, project, or collaboration in mind? Send a message — I’ll reply soon.</p>
          <ul class="contact-list">
            <li><i class="fa-solid fa-envelope"></i> <a href="https://bishwasghimire.com.np" target="_blank" rel="noopener">bishwasghimire.com.np</a></li>
            <li><i class="fa-solid fa-phone"></i> <a href="tel:+9779866342404">+977 9866342404</a></li>
          </ul>
          <div class="socials">
            <a href="https://www.facebook.com/bishwas.ghimire.144" target="_blank" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="https://www.instagram.com/bishwas.ghimire.144/" target="_blank" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          </div>
          <a class="btn ghost" href="https://drive.google.com/file/d/1VmPGJIcBPgREMbSiqYI2GijxlgeeRR9m/view?usp=sharing" target="_blank" rel="noopener">View Resume</a>
        </div>

        <form class="card form" action="contact.php" method="POST" data-aos="fade-left" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" placeholder="Your name" required>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="you@example.com" required>
          </div>
          <div class="field">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" placeholder="Tell me about your project..." required></textarea>
          </div>
          <!-- Honeypot -->
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
          <button class="btn primary" type="submit">Send Message</button>
          <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
            <p class="notice success">Thanks! Your message was sent.</p>
          <?php elseif (isset($_GET['sent']) && $_GET['sent'] === '0'): ?>
            <p class="notice error">Sorry, something went wrong. Please try again.</p>
          <?php endif; ?>
        </form>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <?php echo date('Y'); ?> Bishwas Ghimire — All rights reserved.</p>
      <nav aria-label="Footer">
        <a href="#top">Back to top</a>
      </nav>
    </div>
  </footer>

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "Bishwas Ghimire",
    "jobTitle": "Digital Creator",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Bharatpur",
      "addressRegion": "Chitwan",
      "addressCountry": "NP"
    },
    "url": "https://bishwasghimire.com.np",
    "sameAs": [
      "https://www.facebook.com/bishwas.ghimire.144",
      "https://www.instagram.com/bishwas.ghimire.144/"
    ],
    "telephone": "+9779866342404"
  }
  </script>

  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="script.js"></script>
</body>
</html>
