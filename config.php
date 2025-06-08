<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_digiclear";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_digiclear";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

