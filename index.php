<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventPro - Event Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
    <?php session_start(); ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <span class="logo-icon">📅</span>
                <span class="logo-text">EventPro</span>
            </a>
            <div class="mobile-menu-toggle">
                <span class="hamburger"></span>
                <span class="hamburger"></span>
                <span class="hamburger"></span>
            </div>
            <div class="nav-menu">
                <a href="#about" class="nav-link">About Us</a>
                <a href="#contact" class="nav-link">Contact</a>
                <?php
                    if (isset($_SESSION['user_name'])) {
                        echo '<div class="profile-section">';
                        echo '<div class="profile-dropdown">';
                        echo '<button class="profile-btn">';
                        echo '<span class="profile-avatar">👤</span>';
                        echo '<span class="profile-name">' . htmlspecialchars($_SESSION['user_name']) . '</span>';
                        echo '<span class="dropdown-arrow">▼</span>';
                        echo '</button>';
                        echo '<div class="dropdown-menu">';
                        echo '<a href="view_profile.php" class="dropdown-item"><span class="dropdown-icon">👤</span>View My Profile</a>';
                        echo '<a href="update_profile.php" class="dropdown-item"><span class="dropdown-icon">⚙️</span>Update My Profile</a>';
                        echo '<a href="view_events.php" class="dropdown-item"><span class="dropdown-icon">👁️</span>View Booked Event Details</a>';
                        echo '<hr class="dropdown-divider">';
                        echo '<a href="logout.php" class="dropdown-item"><span class="dropdown-icon">🚪</span>Logout</a>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="auth-section">';
                        echo '<a href="login.php" class="login-btn">Login</a>';
                        echo '<a href="register.php" class="register-btn">Register</a>';
                        echo '</div>';
                    }
                ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-background"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Create Memorable Events</h1>
            <p class="hero-subtitle">From intimate gatherings to grand celebrations, we make every moment special</p>
            <a href="bookingEvent.php" class="hero-cta">Plan Your Event</a>
        </div>
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </section>

    <section class="events-section" id="services">
        <div class="container">
            <header class="section-header">
                <h2 class="section-title">Our Event Services</h2>
                <p class="section-subtitle">Discover our comprehensive range of event management services tailored to make your special occasions unforgettable</p>
            </header>
            <div class="events-grid">
                <div class="event-card" onclick="bookEvent('Wedding Programme')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/258092/pexels-photo-258092.jpeg" alt="Wedding">
                        <div class="card-overlay"></div>
                        <div class="card-icon">💒</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Wedding Programme</h3>
                        <p class="card-description">Create the perfect wedding celebration with our comprehensive planning services</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
                <div class="event-card" onclick="bookEvent('Reunion Programme')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/3171837/pexels-photo-3171837.jpeg" alt="Reunion">
                        <div class="card-overlay"></div>
                        <div class="card-icon">👥</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Reunion Programme</h3>
                        <p class="card-description">Reconnect with loved ones through memorable reunion events</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
                <div class="event-card" onclick="bookEvent('Birthday Parties')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/1543766/pexels-photo-1543766.jpeg" alt="Birthday">
                        <div class="card-overlay"></div>
                        <div class="card-icon">🎂</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Birthday Parties</h3>
                        <p class="card-description">Celebrate life with unforgettable birthday experiences</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
                <div class="event-card" onclick="bookEvent('Anniversaries')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/349609/pexels-photo-349609.jpeg" alt="Anniversary">
                        <div class="card-overlay"></div>
                        <div class="card-icon">💕</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Anniversaries</h3>
                        <p class="card-description">Honor special milestones with elegant anniversary celebrations</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
                <div class="event-card" onclick="bookEvent('Cultural Festivals')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/1051435/pexels-photo-1051435.jpeg" alt="Cultural Festival">
                        <div class="card-overlay"></div>
                        <div class="card-icon">🎭</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Cultural Festivals</h3>
                        <p class="card-description">Celebrate heritage with vibrant cultural festival events</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
                <div class="event-card" onclick="bookEvent('Local Sports Tournaments')">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/248547/pexels-photo-248547.jpeg" alt="Sports Tournament">
                        <div class="card-overlay"></div>
                        <div class="card-icon">🏆</div>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">Local Sports Tournaments</h3>
                        <p class="card-description">Organize competitive sports events and tournaments</p>
                        <button class="card-btn">Book Event</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer" id="contact">
        <div class="footer-background"></div>
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">
                    <span class="logo-icon">📅</span>
                    <span class="logo-text">EventPro</span>
                </a>
                <p class="footer-description">Professional event management services creating memorable experiences for every occasion.</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <a href="#about">About Us</a>
                    <a href="#services">Services</a>
                    <a href="#portfolio">Portfolio</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="footer-column">
                    <h4>Contact Info</h4>
                    <p>📧 info@eventpro.com</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>📍 123 Event Street, City</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="copyright">© 2025 EventPro. All rights reserved.</p>
            <div class="student-info">
                <p><strong>Name:</strong> MD. Alimul Huq</p>
                <p><strong>ID:</strong> 23303249</p>
                <p><strong>Programme:</strong> BCSE</p>
                <p><strong>Course Code:</strong> 434 | <strong>Course Name:</strong> Database Management System</p>
            </div>
        </div>
    </footer>

    <script>
        function bookEvent(eventType) {
            <?php if (!isset($_SESSION['user_name'])) { ?>
                alert('Please log in to book an event.');
                window.location.href = 'login.php';
            <?php } else { ?>
                window.location.href = 'bookingEvent.php?event_type=' + encodeURIComponent(eventType);
            <?php } ?>
        }

        document.querySelector('.mobile-menu-toggle').addEventListener('click', () => {
            document.querySelector('.nav-menu').classList.toggle('active');
            document.querySelector('.mobile-menu-toggle').classList.toggle('active');
        });

        document.querySelector('.profile-btn')?.addEventListener('click', () => {
            document.querySelector('.dropdown-menu').classList.toggle('active');
            document.querySelector('.profile-btn').classList.toggle('active');
        });

        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>