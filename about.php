<?php
session_start();
require_once 'includes/config.php';

// Page variables
$pageTitle = 'About Us - Horaa Esports';
$pageDescription = 'Learn about Horaa Esports, a premier Nepali esports organization that has rapidly ascended to prominence in the competitive PUBG Mobile scene.';

include 'includes/header.php';
?>

<div class="page-wrapper">
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title">🏆 About Horaa Esports</h1>
                <p class="page-subtitle">Premier Nepali Esports Organization</p>
            </div>
        </div>
    </section>

    <!-- About Content -->
    <section class="about-section">
        <div class="container">
            <!-- Introduction -->
            <div class="about-intro">
                <div class="intro-content">
                    <p class="intro-text">
                        Horaa Esports is a premier Nepali esports organization that has rapidly ascended to prominence in the competitive PUBG Mobile scene. Established on October 5, 2023, Horaa Esports is renowned for its exceptional gameplay, strategic prowess, and unwavering dedication to elevating Nepali esports on the global stage.
                    </p>
                </div>
            </div>

            <!-- Journey Section -->
            <div class="section-block">
                <div class="section-header">
                    <h2 class="section-title">🎮 Our Journey</h2>
                </div>
                <div class="section-content">
                    <p>
                        Founded by Sanjan Gautam, widely recognized by his online alias CR7 Horaa, the organization emerged from a shared passion for gaming and a vision to create a platform for Nepali gamers to shine internationally. Prior to founding Horaa Esports, CR7 Horaa was a prominent member of DRS Gaming, one of Nepal's most respected PUBG Mobile teams. His transition to creating Horaa Esports marked a new chapter in his journey, focusing on nurturing talent and representing Nepal globally.
                    </p>
                </div>
            </div>

            <!-- Achievements Section -->
            <div class="section-block">
                <div class="section-header">
                    <h2 class="section-title">🌟 Our Achievements</h2>
                </div>
                <div class="achievements-grid">
                    <div class="achievement-card">
                        <div class="achievement-icon">🏆</div>
                        <h3>PMSL CSA Spring 2025</h3>
                        <p>Secured 2nd place with 139 points, earning a spot in the PUBG Mobile World Cup 2025</p>
                    </div>
                    <div class="achievement-card">
                        <div class="achievement-icon">🌍</div>
                        <h3>PUBG Mobile World Cup 2025</h3>
                        <p>Made history as the first Nepali team to qualify, finishing in 9th place and earning NPR 1.78 crore</p>
                    </div>
                    <div class="achievement-card">
                        <div class="achievement-icon">🎖️</div>
                        <h3>National Recognition</h3>
                        <p>Founder CR7 Horaa was honored in the "40 Under Forty 2082" list by the Prime Minister of Nepal for his contributions to the esports industry</p>
                    </div>
                </div>
            </div>

            <!-- Team Section -->
            <div class="section-block">
                <div class="section-header">
                    <h2 class="section-title">👥 Our Team</h2>
                </div>
                <div class="team-structure">
                    <div class="team-leadership">
                        <h3>Leadership</h3>
                        <div class="team-grid">
                            <div class="team-member">
                                <h4>Founder</h4>
                                <p>Sanjan Gautam (CR7 Horaa)</p>
                            </div>
                            <div class="team-member">
                                <h4>Co-Owners</h4>
                                <p>Yubin Limbu<br>Nabaraj Shrestha</p>
                            </div>
                            <div class="team-member">
                                <h4>Head of Operations</h4>
                                <p>Umesh Budthapa (Charlie)</p>
                            </div>
                        </div>
                    </div>

                    <div class="team-gaming">
                        <h3>Gaming Team</h3>
                        <div class="team-grid">
                            <div class="team-member">
                                <h4>Team Captain / IGL</h4>
                                <p>Suprim "JiGGL3" Adhikari</p>
                            </div>
                            <div class="team-member">
                                <h4>Coach</h4>
                                <p>Ugyen "MafiaNinja" Lama</p>
                            </div>
                        </div>

                        <h4>Current Roster</h4>
                        <div class="roster-grid">
                            <div class="roster-member">Aayush "NoFear" Lama</div>
                            <div class="roster-member">Prabesh "HaitDami" Gurung</div>
                            <div class="roster-member">Shital "SleepY" Rai</div>
                            <div class="roster-member">Aakash "SkY" Sotang Rai</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vision Section -->
            <div class="section-block">
                <div class="section-header">
                    <h2 class="section-title">🎯 Our Vision</h2>
                </div>
                <div class="section-content">
                    <p>
                        At Horaa Esports, our mission is to foster a thriving esports ecosystem in Nepal by providing talented individuals with opportunities to showcase their skills on international platforms. We aim to inspire the next generation of gamers and contribute to the global recognition of Nepali esports.
                    </p>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="section-block contact-section">
                <div class="section-header">
                    <h2 class="section-title">📞 Contact Us</h2>
                </div>
                <div class="contact-content">
                    <p>For business inquiries, collaborations, or media relations, please reach out to us at:</p>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:horaaesports@gmail.com">horaaesports@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%), 
                linear-gradient(45deg, var(--primary-color), var(--primary-dark));
    background-blend-mode: overlay;
    color: var(--white);
    padding: var(--spacing-20) 0 var(--spacing-16) 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="white" stop-opacity="0.1"/><stop offset="100%" stop-color="white" stop-opacity="0"/></radialGradient></defs><circle cx="10" cy="10" r="10" fill="url(%23a)"/><circle cx="90" cy="10" r="10" fill="url(%23a)"/></svg>');
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.page-header-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.page-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    margin-bottom: var(--spacing-4);
    color: var(--white);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    animation: slideInUp 0.8s ease-out;
}

.page-subtitle {
    font-size: var(--font-size-xl);
    opacity: 0.95;
    margin-bottom: 0;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    animation: slideInUp 0.8s ease-out 0.2s both;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* About Section */
.about-section {
    padding: var(--spacing-20) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
}

.about-intro {
    text-align: center;
    margin-bottom: var(--spacing-20);
    position: relative;
}

.about-intro::after {
    content: '';
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

.intro-text {
    font-size: var(--font-size-lg);
    line-height: 1.8;
    max-width: 1000px;
    margin: 0 auto;
    color: var(--gray-700);
    padding: var(--spacing-8);
    background: var(--white);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.intro-text::before {
    content: '🏆';
    position: absolute;
    top: -10px;
    left: -10px;
    font-size: 4rem;
    opacity: 0.1;
    z-index: 1;
}

.section-block {
    margin-bottom: var(--spacing-20);
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease-out forwards;
}

.section-block:nth-child(2) { animation-delay: 0.1s; }
.section-block:nth-child(3) { animation-delay: 0.2s; }
.section-block:nth-child(4) { animation-delay: 0.3s; }
.section-block:nth-child(5) { animation-delay: 0.4s; }
.section-block:nth-child(6) { animation-delay: 0.5s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
    position: relative;
}

.section-header::after {
    content: '';
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: var(--primary-color);
    border-radius: 2px;
}

.section-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--gray-900);
    margin-bottom: var(--spacing-4);
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.section-content p {
    font-size: var(--font-size-base);
    line-height: 1.7;
    color: var(--gray-700);
}

/* Achievements Grid */
.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-8);
    margin-top: var(--spacing-12);
}

.achievement-card {
    background: linear-gradient(135deg, var(--white) 0%, #f8fafc 100%);
    padding: var(--spacing-10);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.2);
}

.achievement-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.6s;
}

.achievement-card:hover::before {
    left: 100%;
}

.achievement-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 30px 60px rgba(0,0,0,0.15);
}

.achievement-icon {
    font-size: 4rem;
    margin-bottom: var(--spacing-6);
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.achievement-card h3 {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--gray-900);
    margin-bottom: var(--spacing-4);
}

.achievement-card p {
    color: var(--gray-600);
    line-height: 1.7;
    font-size: var(--font-size-base);
}

/* Team Structure */
.team-structure {
    max-width: 1200px;
    margin: 0 auto;
}

.team-leadership,
.team-gaming {
    margin-bottom: var(--spacing-16);
    padding: var(--spacing-10);
    background: linear-gradient(135deg, var(--white) 0%, #f8fafc 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.3);
}

.team-leadership h3,
.team-gaming h3,
.team-gaming h4 {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--gray-900);
    margin-bottom: var(--spacing-8);
    text-align: center;
    position: relative;
}

.team-leadership h3::after,
.team-gaming h3::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

.team-gaming h4 {
    font-size: var(--font-size-lg);
    margin-top: var(--spacing-12);
    margin-bottom: var(--spacing-6);
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-8);
    margin-bottom: var(--spacing-10);
}

.team-member {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-8);
    border-radius: var(--border-radius-lg);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.5);
    position: relative;
    overflow: hidden;
}

.team-member::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.team-member:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

.team-member h4 {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-bold);
    color: var(--primary-color);
    margin-bottom: var(--spacing-3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.team-member p {
    color: var(--gray-700);
    margin-bottom: 0;
    font-weight: 500;
}

.roster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--spacing-6);
    max-width: 900px;
    margin: 0 auto;
}

.roster-member {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    text-align: center;
    font-weight: var(--font-weight-semibold);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.roster-member::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.roster-member:hover::before {
    left: 100%;
}

.roster-member:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
}

/* Contact Section */
.contact-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: var(--spacing-16);
    border-radius: var(--border-radius-xl);
    text-align: center;
    color: var(--white);
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.contact-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.contact-section .section-title {
    color: var(--white) !important;
    -webkit-text-fill-color: var(--white) !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 2;
}

.contact-content {
    position: relative;
    z-index: 2;
}

.contact-content p {
    margin-bottom: var(--spacing-8);
    font-size: var(--font-size-lg);
    opacity: 0.95;
}

.contact-info {
    display: flex;
    justify-content: center;
    gap: var(--spacing-8);
}

.contact-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: var(--spacing-6) var(--spacing-8);
    border-radius: var(--border-radius-lg);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.contact-item:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.contact-item i {
    color: var(--white);
    font-size: var(--font-size-xl);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.contact-item a {
    color: var(--white);
    text-decoration: none;
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-lg);
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.contact-item a:hover {
    color: rgba(255, 255, 255, 0.9);
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-title {
        font-size: var(--font-size-2xl);
    }
    
    .page-subtitle {
        font-size: var(--font-size-lg);
    }
    
    .achievements-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .roster-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    
    .contact-info {
        flex-direction: column;
        align-items: center;
    }
    
    .intro-text {
        font-size: var(--font-size-base);
        padding: var(--spacing-6);
    }
    
    .page-header {
        padding: var(--spacing-16) 0 var(--spacing-12) 0;
    }
    
    .about-section {
        padding: var(--spacing-16) 0;
    }
    
    .section-block {
        margin-bottom: var(--spacing-12);
    }
    
    .team-leadership,
    .team-gaming {
        padding: var(--spacing-6);
        margin-bottom: var(--spacing-12);
    }
    
    .contact-section {
        padding: var(--spacing-12);
    }
    
    .achievement-card {
        padding: var(--spacing-8);
    }
    
    .contact-item {
        padding: var(--spacing-5) var(--spacing-6);
    }
}

/* Additional animations and effects */
.section-content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
    padding: var(--spacing-6);
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.section-content p {
    font-size: var(--font-size-lg);
    line-height: 1.8;
    color: var(--gray-700);
}

/* Smooth scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Loading animation for elements */
.page-wrapper {
    animation: pageLoad 0.8s ease-out;
}

@keyframes pageLoad {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php include 'includes/footer.php'; ?>
