<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1519227355133-6ed7e4b238ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'); /* Updated event-themed background image */
            background-size: cover; /* Scales image to cover the entire background */
            background-position: center; /* Centers the image */
            background-repeat: no-repeat; /* Prevents image repetition */
            background-attachment: fixed; /* Fixed background for a parallax effect */
            background-color: rgba(0, 0, 0, 0.1); /* Fallback color with slight overlay */
        }

        /* Semi-transparent overlay for better text readability */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2); /* Dark overlay for contrast */
            z-index: -1; /* Places overlay behind content */
        }
        .form-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #1e293b;
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
        }
        .form-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
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
                <a href="index.php" class="nav-link">Home</a>
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
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Register</h2>
            <?php
                include 'db_connect.php';
                $error = '';
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $user_name = $_POST['user_name'];
                    $user_gmail = $_POST['user_gmail'];
                    $user_phone_number = $_POST['user_phone_number'];
                    $user_address = $_POST['user_address'];
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];

                    if ($password !== $confirm_password) {
                        $error = "Passwords do not match.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        try {
                            $stmt = $pdo->prepare("INSERT INTO users (user_name, user_password, user_gmail, user_phone_number, user_address) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$user_name, $hashed_password, $user_gmail, $user_phone_number, $user_address]);
                            header("Location: login.php");
                            exit();
                        } catch (PDOException $e) {
                            $error = "Error: Username or email already exists.";
                        }
                    }
                }
            ?>
            <form method="POST">
                <div class="form-group">
                    <label for="user_name">Username</label>
                    <input type="text" id="user_name" name="user_name" required>
                </div>
                <div class="form-group">
                    <label for="user_gmail">Email</label>
                    <input type="email" id="user_gmail" name="user_gmail" required>
                </div>
                <div class="form-group">
                    <label for="user_phone_number">Phone Number</label>
                    <input type="text" id="user_phone_number" name="user_phone_number" required>
                </div>
                <div class="form-group">
                    <label for="user_address">Address</label>
                    <textarea id="user_address" name="user_address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn">Register</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
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