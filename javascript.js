  // Enhanced smooth scrolling with animation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                const targetPosition = target.getBoundingClientRect().top;
                const startPosition = window.pageYOffset;
                const distance = targetPosition - startPosition;
                const duration = 1000; // 1 second
                let start = null;

                function animation(currentTime) {
                    if (start === null) start = currentTime;
                    const timeElapsed = currentTime - start;
                    const run = ease(timeElapsed, startPosition, distance, duration);
                    window.scrollTo(0, run);
                    if (timeElapsed < duration) requestAnimationFrame(animation);
                }

                function ease(t, b, c, d) {
                    t /= d;
                    return c * t * t + b;
                }

                requestAnimationFrame(animation);
            });
        });

        // Collaborate form submission handling
        document.getElementById('collaborateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your collaboration request! I will review your project and get back to you soon.');
            this.reset();
        });
