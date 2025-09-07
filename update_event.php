<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1540575467063-1bd6d21265f0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'); /* Event-themed background image */
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
        .form-group input, .form-group select, .form-group textarea {
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
    <?php
        include 'db_connect.php';
        if (!isset($_SESSION['user_name'])) {
            header("Location: login.php");
            exit();
        }
        $error = '';
        $event_id = $_GET['event_id'];
        $stmt = $pdo->prepare("SELECT * FROM booking_details WHERE event_id = ? AND user_name = ?");
        $stmt->execute([$event_id, $_SESSION['user_name']]);
        $event = $stmt->fetch();
        if (!$event) {
            header("Location: view_events.php");
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $event_address = $_POST['event_address'];
            $event_date = $_POST['event_date'];
            $guest_number = $_POST['guest_number'];
            $food_type = $_POST['food_type'];
            $food_description = $_POST['food_description'];
            $sound_system = $_POST['sound_system'];
            $decoration_description = $_POST['decoration_description'];
            $current_date = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_details WHERE event_date = ? AND event_id != ?");
            $stmt->execute([$event_date, $event_id]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $error = "This date is already booked.";
            } elseif (strtotime($event_date) < strtotime($current_date)) {
                $error = "Cannot book a past date.";
            } else {
                $stmt = $pdo->prepare("UPDATE booking_details SET event_address = ?, event_date = ?, guest_number = ?, food_type = ?, food_description = ?, sound_system = ?, decoration_description = ? WHERE event_id = ? AND user_name = ?");
                $stmt->execute([$event_address, $event_date, $guest_number, $food_type, $food_description, $sound_system, $decoration_description, $event_id, $_SESSION['user_name']]);
                header("Location: view_events.php");
                exit();
            }
        }
    ?>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Update Event</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <input type="text" id="event_type" value="<?php echo htmlspecialchars($event['event_type']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="event_address">Event Address</label>
                    <textarea id="event_address" name="event_address" required><?php echo htmlspecialchars($event['event_address']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date and Time</label>
                    <input type="datetime-local" id="event_date" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                </div>
                <div class="form-group">
                    <label for="guest_number">Number of Guests</label>
                    <input type="number" id="guest_number" name="guest_number" min="1" value="<?php echo htmlspecialchars($event['guest_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="food_type">Food Type</label>
                    <select id="food_type" name="food_type" required>
                        <option value="Vegetarian" <?php if ($event['food_type'] == 'Vegetarian') echo 'selected'; ?>>Vegetarian</option>
                        <option value="Non-Vegetarian" <?php if ($event['food_type'] == 'Non-Vegetarian') echo 'selected'; ?>>Non-Vegetarian</option>
                        <option value="Vegan" <?php if ($event['food_type'] == 'Vegan') echo 'selected'; ?>>Vegan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="food_description">Food Description</label>
                    <textarea id="food_description" name="food_description"><?php echo htmlspecialchars($event['food_description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="sound_system">Sound System</label>
                    <select id="sound_system" name="sound_system">
                        <option value="None" <?php if ($event['sound_system'] == 'None') echo 'selected'; ?>>No</option>
                        <option value="Basic" <?php if ($event['sound_system'] == 'Basic') echo 'selected'; ?>>Yes (Basic)</option>
                        <option value="Premium" <?php if ($event['sound_system'] == 'Premium') echo 'selected'; ?>>Yes (Premium)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decoration_description">Decoration Description</label>
                    <textarea id="decoration_description" name="decoration_description"><?php echo htmlspecialchars($event['decoration_description']); ?></textarea>
                </div>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn">Update Event</button>
            </form>
            <a href="view_events.php" class="back-btn" style="display: block; text-align: center; margin-top: 1rem;">Back to Events</a>
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