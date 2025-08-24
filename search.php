<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "event_management";

    $conn = mysqli_connect($servername, $username, $password, $dbname);

    $search = "";

    if(isset($_POST['search'])){
        $search = mysqli_real_escape_string($conn, $_POST['search']);
    }

    if($search != ""){
        echo "You searched for: <b>$search</b><br><br>";
    }

    if(!empty($search)){
        $view_sql = "SELECT * FROM receiver 
                    WHERE receiver_id LIKE '%$search%'
                    OR receiver_name LIKE '%$search%'
                    OR receiver_address LIKE '%$search%'
                    OR receiver_contact LIKE '%$search%'
                    OR receiver_email LIKE '%$search%'";
    }
    else{
        $view_sql = "SELECT * FROM receiver";
    }

    $result = mysqli_query($conn, $view_sql);
    ?>

    <form method="post" action="">
        <input type="text" name="search" placeholder="Search by any field" value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
        <input type="submit" name="reset" value="Reset">
    </form>
    <br>

    <?php
    if($result && mysqli_num_rows($result) > 0){
        echo "<table border='1' cellpadding='10'>
                <tr>
                    <th> receiver_id </th>
                    <th> receiver_name </th>
                    <th> receiver_address </th>
                    <th> receiver_contact </th>
                    <th> receiver_email </th>
                </tr>";
        while($row = mysqli_fetch_assoc($result)){
            echo "<tr>
                    <td>{$row['receiver_id']}</td>
                    <td>{$row['receiver_name']}</td>
                    <td>{$row['receiver_address']}</td>
                    <td>{$row['receiver_contact']}</td>
                    <td>{$row['receiver_email']}</td>
                </tr>";
        }
        echo "</table>";
    }
    else{
        echo "No Users Found.<br>";
    }

    mysqli_close($conn);
?>