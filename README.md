# Portfolio Website - Bishwas Ghimire

An ultra-aesthetic, fully responsive personal portfolio website built with HTML, CSS, and vanilla JavaScript. Features a minimal yet unexpected design language with glassmorphism, subtle animations, and comprehensive accessibility support.

## 🎨 Design Philosophy

This portfolio embraces an **aesthetic + rare** design approach:
- Minimal layout with generous negative space
- Unconventional visual details (asymmetrical grid, glassmorphism)
- Refined accent animations and micro-interactions
- Fully responsive from 1920px+ down to 150px width

## 🚀 Features

### Core Functionality
- ✨ **Scroll Animations** - Smooth entrance animations using IntersectionObserver
- 🎯 **Project Filtering** - Dynamic filtering with smooth transitions
- 🖼️ **Modal System** - Accessible full-screen project case studies with focus trap
- 📱 **Mobile Navigation** - Slide-over menu with focus management
- 📝 **Contact Form** - Client-side validation with success states
- 🎪 **Parallax Effects** - Subtle background motion on pointer movement
- ♿ **Full Accessibility** - WCAG compliant with keyboard navigation

### Technical Highlights
- Pure HTML5, CSS3, and ES6+ JavaScript (no frameworks)
- CSS Grid and Flexbox layouts
- CSS Custom Properties for theming
- Fluid typography using `clamp()`
- Lazy-loading images
- Prefers-reduced-motion support
- Focus management and ARIA attributes
- Semantic HTML structure

## 📐 Design System

### Color Palette
```css
Background:           #0F1226 (deep blue/near-black)
Secondary Background: #151933
Glass Panel:          rgba(255, 255, 255, 0.04)
Accent (Lilac):       #E6C1FF
Secondary (Mint):     #7BE7C7
Text Primary:         #E7E9F3
Text Muted:           #9AA0B4
Text Subtle:          #6B7280
```

### Typography
**Font Family:** Inter (via Google Fonts)
- Fallback: System UI stack

**Font Weights:**
- Light: 300
- Normal: 400
- Semibold: 600
- Bold: 700
- Black: 900

**Fluid Typography Scale:**
```css
--font-size-xs:   clamp(0.7rem, 1.5vw, 0.875rem)
--font-size-sm:   clamp(0.8rem, 1.8vw, 0.9375rem)
--font-size-base: clamp(0.875rem, 2vw, 1rem)
--font-size-md:   clamp(1rem, 2.4vw, 1.125rem)
--font-size-lg:   clamp(1.125rem, 2.8vw, 1.25rem)
--font-size-xl:   clamp(1.25rem, 3.2vw, 1.5rem)
--font-size-2xl:  clamp(1.5rem, 4vw, 2rem)
--font-size-3xl:  clamp(2rem, 5vw, 2.5rem)
--font-size-4xl:  clamp(2.5rem, 6vw, 3.5rem)
--font-size-5xl:  clamp(3rem, 8vw, 4.5rem)
```

### Spacing System
Fluid spacing using `clamp()` for responsive scaling:
```css
--spacing-1:  clamp(0.25rem, 0.5vw, 0.5rem)    /* 4-8px */
--spacing-2:  clamp(0.5rem, 1vw, 0.75rem)      /* 8-12px */
--spacing-3:  clamp(0.75rem, 1.5vw, 1rem)      /* 12-16px */
--spacing-4:  clamp(1rem, 2vw, 1.5rem)         /* 16-24px */
--spacing-5:  clamp(1.5rem, 2.5vw, 2rem)       /* 24-32px */
--spacing-6:  clamp(2rem, 3vw, 3rem)           /* 32-48px */
--spacing-8:  clamp(3rem, 4vw, 4rem)           /* 48-64px */
--spacing-10: clamp(4rem, 5vw, 5rem)           /* 64-80px */
--spacing-12: clamp(5rem, 6vw, 6rem)           /* 80-96px */
--spacing-16: clamp(6rem, 8vw, 8rem)           /* 96-128px */
```

### Layout
- **Container Max Width:** 1100px
- **Container Padding:** `clamp(1rem, 4vw, 2rem)`
- **Section Padding:** `clamp(3rem, 8vw, 6rem)`

## 📱 Responsive Breakpoints

The site is fully responsive with carefully crafted breakpoints:

| Breakpoint | Width Range | Notes |
|------------|-------------|-------|
| **Large Desktop** | ≥ 1920px | Max container width: 1400px |
| **Desktop** | 1366px - 1919px | Default styles |
| **Tablet** | 768px - 1365px | Single column hero, stacked contact |
| **Mobile Nav** | ≤ 1023px | Hamburger menu activated |
| **Mobile Large** | 480px - 767px | Full-width buttons, single column projects |
| **Mobile Medium** | 320px - 479px | Condensed spacing, adjusted timeline |
| **Extreme Narrow** | 150px - 319px | Minimal padding, stacked layout |

### Extreme Narrow (150px) Support
The site remains fully functional and readable at 150px width:
- Single-column stacked layout
- Minimal but readable padding
- Condensed font sizes with readable lower bounds
- Vertical scrolling prioritized
- Essential information preserved

## 🏗️ Project Structure

```
portfoliowebsite/
├── assets/
│   ├── images/          # Project images (add your own)
│   │   ├── project1.jpg
│   │   ├── project2.jpg
│   │   ├── project3.jpg
│   │   └── project4.jpg
│   └── svg/             # SVG icons and graphics
├── index.html           # Main HTML file
├── styles.css           # All styles with design system
├── main.js              # All JavaScript functionality
└── README.md            # This file
```

## 🎯 Getting Started

### Prerequisites
- A modern web browser (Chrome, Firefox, Safari, Edge)
- A local web server (optional, but recommended for testing)

### Installation

1. **Clone or download** this repository

2. **Add your images:**
   - Place project screenshots in `assets/images/`
   - Name them: `project1.jpg`, `project2.jpg`, `project3.jpg`, `project4.jpg`
   - Recommended size: 1200x800px (3:2 aspect ratio)
   - Optimize images for web (use tools like TinyPNG or ImageOptim)

3. **Customize content:**
   - Edit `index.html` to add your personal information
   - Update project data in `main.js` (lines 421-516)
   - Modify social links in the contact section
   - Update meta tags for SEO

4. **Run locally:**

   **Option A - Simple HTTP Server (Python):**
   ```bash
   # Python 3
   python -m http.server 8000
   
   # Python 2
   python -m SimpleHTTPServer 8000
   ```
   Then visit: `http://localhost:8000`

   **Option B - Node.js http-server:**
   ```bash
   npx http-server -p 8000
   ```

   **Option C - VS Code Live Server:**
   - Install "Live Server" extension
   - Right-click `index.html` → "Open with Live Server"

   **Option D - Just open the file:**
   - Double-click `index.html` to open in browser
   - Note: Some features may require a web server

## 🎨 Customization Guide

### Changing Colors
Edit CSS variables in `styles.css` (lines 10-25):
```css
:root {
  --color-bg: #0F1226;              /* Background */
  --color-accent: #E6C1FF;          /* Primary accent */
  --color-accent-secondary: #7BE7C7; /* Secondary accent */
  /* ... more colors */
}
```

### Updating Projects
Edit the `projectData` object in `main.js` (starting at line 421):
```javascript
const projectData = {
  1: {
    title: 'Your Project Title',
    image: 'assets/images/yourimage.jpg',
    description: 'Your description...',
    role: ['Role 1', 'Role 2'],
    tech: ['Tech1', 'Tech2'],
    highlights: ['Highlight 1', 'Highlight 2'],
    liveUrl: 'https://your-live-site.com',
    codeUrl: 'https://github.com/yourusername/repo',
  },
  // Add more projects...
};
```

### Adding More Skills
Update the skills grid in `index.html` (around line 140):
```html
<div class="skills-grid">
  <div class="skill-chip">Your Skill</div>
  <!-- Add more skills -->
</div>
```

### Modifying Typography
Change the Google Font in `index.html` (line 15) and update the font family in `styles.css`:
```css
--font-family-base: 'YourFont', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

## ♿ Accessibility Features

### Keyboard Navigation
- **Tab/Shift+Tab:** Navigate through interactive elements
- **Enter/Space:** Activate buttons and links
- **Escape:** Close modal and mobile menu
- **Tab Trap:** Focus contained within modal when open

### Screen Readers
- Semantic HTML structure
- ARIA labels and roles
- Alt text for images
- Live regions for dynamic content
- Skip to main content link

### Visual Accessibility
- High contrast text (WCAG AA compliant)
- Focus indicators on all interactive elements
- Respects `prefers-reduced-motion`
- No flashing or rapidly moving content
- Scalable text (no fixed pixel sizes)

## 🚀 Performance Optimization

### Implemented Optimizations
- Lazy-loading images with `loading="lazy"`
- CSS variables for efficient styling
- Debounced scroll and resize handlers
- IntersectionObserver for animations (no scroll listeners)
- Minimal DOM manipulation
- No external dependencies (except Google Fonts)
- Efficient CSS selectors
- Hardware-accelerated animations (transform, opacity)

### Lighthouse Targets
- **Performance:** ≥ 90
- **Accessibility:** ≥ 90
- **Best Practices:** ≥ 90
- **SEO:** ≥ 90

### Tips for Further Optimization
1. Optimize images (use WebP format with JPG fallback)
2. Implement critical CSS inline
3. Self-host fonts for faster loading
4. Add service worker for offline support
5. Implement resource hints (preconnect, prefetch)

## 🌐 Browser Support

### Fully Supported
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Opera 76+

### Partial Support
- IE 11: Visual degradation, but content remains accessible

### Progressive Enhancement
- Site functions without JavaScript (content is accessible)
- Fallback fonts if Google Fonts fail to load
- Graceful degradation for unsupported CSS features

## 📦 Deployment

### GitHub Pages
1. Push to GitHub repository
2. Go to Settings → Pages
3. Select branch and save
4. Access at: `https://yourusername.github.io/repository-name`

### Netlify
1. Drag and drop folder to Netlify
2. Or connect GitHub repository
3. Auto-deploy on push

### Vercel
```bash
npm i -g vercel
vercel
```

### Traditional Hosting
1. Upload all files via FTP/SFTP
2. Ensure file permissions are correct
3. Point domain to hosting directory

## 🔧 Troubleshooting

### Images Not Loading
- Check file paths in `index.html` and `main.js`
- Ensure images exist in `assets/images/`
- Check file extensions match (`.jpg`, `.png`, etc.)

### Animations Not Working
- Check browser console for errors
- Verify JavaScript is enabled
- Test in different browser
- Check if `prefers-reduced-motion` is enabled

### Mobile Menu Not Opening
- Check if JavaScript loaded successfully
- Inspect browser console for errors
- Test on different device/browser

### Form Not Submitting
- This is a demonstration form (no backend)
- To connect to real backend, modify `initContactForm()` in `main.js`
- Add your API endpoint or email service

## 🛠️ Development Notes

### Code Organization
- **HTML:** Semantic structure with ARIA attributes
- **CSS:** Mobile-first approach with progressive enhancement
- **JavaScript:** Modular functions with clear separation of concerns

### Best Practices Followed
- ✅ Semantic HTML5
- ✅ BEM-inspired CSS naming
- ✅ Mobile-first responsive design
- ✅ Progressive enhancement
- ✅ Accessibility-first approach
- ✅ Performance optimization
- ✅ Clean, commented code
- ✅ Cross-browser compatibility

### Future Enhancements (Optional)
- [ ] Add dark/light theme toggle
- [ ] Implement blog section with markdown
- [ ] Add service worker for PWA support
- [ ] Create admin panel for content management
- [ ] Add more project case studies
- [ ] Implement analytics tracking
- [ ] Add multi-language support

## 📄 License

This project is open source and available for personal and commercial use. Feel free to customize and make it your own!

## 🤝 Credits

- **Design & Development:** Bishwas Ghimire
- **Font:** Inter by Rasmus Andersson
- **Icons:** Inline SVGs (Feather Icons style)

## 📞 Contact

- **Email:** hello@bishwasghimire.com
- **GitHub:** github.com/bishwasghimire
- **LinkedIn:** linkedin.com/in/bishwasghimire
- **Twitter:** twitter.com/bishwasghimire

---

**Built with ❤️ using HTML, CSS, and JavaScript**

*Last updated: October 2025*
