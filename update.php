<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "event_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// i have to choose based on button

if(isset($_GET['id'])){
    $id = $_GET['id'];

    $sql = "SELECT * FROM  WHERE ID = $id"; // table name have to add here
    $result = $conn->query($sql);

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        $name = $row['Name'];
        $program = $row['Program'];
    } 
    else{
        echo "Student not found.";
        exit;
    }
} 
else{
    echo "Invalid request.";
    exit;
}

if(isset($_POST['update'])){
    $new_name = $_POST['name'];
    $new_program = $_POST['program'];

    $update_sql = "UPDATE student_info SET Name='$new_name', Program='$new_program' WHERE ID=$id";

    if ($conn->query($update_sql) === TRUE){
        header("Location: fetch.php");
        exit;
    }
    else{
        echo "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            padding: 30px;
        }
        .update-form {
            width: 400px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 2px 10px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .btn-group {
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }
        .update-btn {
            background-color: #28a745;
        }
        .back-btn {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="update-form">
        <h2>Update Student Info</h2>
        <form method="POST">
            <label>Name:</label>
            <input type="text" name="name" value="<?php echo $name; ?>" required>

            <label>Program:</label>
            <input type="text" name="program" value="<?php echo $program; ?>" required>

            <div class="btn-group">
                <button type="submit" name="update" class="btn update-btn">Update</button>
                <a href="fetch.php" class="btn back-btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>