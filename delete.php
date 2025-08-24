<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "event_management";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if(!$conn){
        die("Connection failed: " . mysqli_connect_error());
    }

    $id = $_POST['id'];

    // Delete from database
    if(){
        $sql = "DELETE FROM  WHERE ID = '$id'";
    }
    elseif () {
        $sql = "DELETE FROM  WHERE ID = '$id'";
    }

    if (mysqli_query($conn, $sql)) {
        echo"<script>
                    alert('Student deleted successfully');
                    window.location.href='showDetails.php';
            </script>";
    }
    else {
        echo "Error deleting record: " . mysqli_error($conn);
    }

    $conn->close();
?>
