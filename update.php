<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "event_management";

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Validate GET parameters
if (!isset($_GET['role'], $_GET['id'])) die("Invalid request!");

$role = htmlspecialchars($_GET['role']);
$id   = intval($_GET['id']);

// Determine table & primary key
switch ($role) {
    case 'host':
        $table = "host_details";
        $id_field = "host_id";
        break;
    case 'guest':
        $table = "guest_details";
        $id_field = "guest_id";
        break;
    case 'event':
        $table = "event_details";
        $id_field = "event_id";
        break;
    case 'payment':
        $table = "payment_details";
        $id_field = "payment_id";
        break;
    default:
        die("Invalid role!");
}

// Fetch existing record
$stmt = $conn->prepare("SELECT * FROM $table WHERE $id_field = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) die("No record found!");
$row = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $params  = [];
    $types   = '';

    foreach ($row as $col => $val) {
        if ($col == $id_field) continue;
        if (isset($_POST[$col])) {
            $updates[] = "$col = ?";
            $params[] = $_POST[$col];
            $types .= 's';
        }
    }

    if (!empty($updates)) {
        $set = implode(", ", $updates);
        $params[] = $id;
        $types .= 'i';

        $update_sql = "UPDATE $table SET $set WHERE $id_field = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Redirect to showDetails.php with the same role
            header("Location: showDetails.php?role=$role");
            exit;
        } else {
            echo "<p style='color:red;'>❌ Error updating record: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update <?php echo ucfirst($role); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            margin: 0; 
            padding: 50px 0; 
            display: flex; 
            justify-content: center; 
        }
        .update-form {
            background: rgba(255,255,255,0.95);
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
        }
        .update-form h2 {
            text-align: center;
            color: #764ba2;
            margin-bottom: 25px;
        }
        .update-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        .btn-group {
            text-align: center;
        }
        .btn-group button {
            padding: 12px 25px;
            margin: 5px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-group button a{
            color: white;
            text-decoration: none;
        }

        .update-btn {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }
        .update-btn:hover, .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .update-btn:hover { background: linear-gradient(135deg, #218838, #1e7e34); }
        .back-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Event Management System</h1>
            <div class="nav-menu">
                <a href="guest.html" class="nav-btn">Guest Registration</a>
                <a href="host.html" class="nav-btn">Host Registration</a>
                <a href="payment.html" class="nav-btn">Payment</a>
                <div class="dropdown">
                    <button class="nav-btn dropdown-btn">View Data ▼</button>
                    <div class="dropdown-content">
                        <form action="showDetails.php" method="POST">
                            <button name="showHostDetails">Host Detail</button>
                            <button name="showGuestDetails">Guest Detail</button>
                            <button name="showPaymentDetails">Payment Detail</button>
                            <button name="showEventDetails">Event Detail</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="update-form">
            <h2>Update <?php echo ucfirst($role); ?> Info</h2>
            <form method="POST">
                <?php foreach ($row as $col => $val): ?>
                    <?php if ($col == $id_field) continue; ?>
                    <input type="text" name="<?php echo $col; ?>" 
                           value="<?php echo htmlspecialchars($val); ?>" 
                           placeholder="<?php echo ucfirst(str_replace("_", " ", $col)); ?>">
                <?php endforeach; ?>
                <div class="btn-group">
                    <button type="submit" class="update-btn">Update</button>
                    <button type="button" class="back-btn" onclick="window.location.href='showDetails.php?role=<?php echo $role; ?>'">Back</button>
                </div>
            </form>
        </div>
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; 2025 Event Management System. All rights reserved.</p>
                <p>Name : Md. Alimul Huq</p>
                <p>ID : 23303249</p>
                <p>Course Name : Database Management System</p>
                <p>Course Code : CSC 434</p>
            </div>
        </footer>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const dropdownBtn = document.querySelector(".dropdown-btn");
            const dropdownContent = document.querySelector(".dropdown-content");

            dropdownBtn.addEventListener("click", () => {
                dropdownContent.classList.toggle("show");
            });

            // Optional: close dropdown if clicked outside
            window.addEventListener("click", (e) => {
                if (!e.target.matches(".dropdown-btn")) {
                    if (dropdownContent.classList.contains("show")) {
                        dropdownContent.classList.remove("show");
                    }
                }
            });
        });
    </script>
</body>
</html>