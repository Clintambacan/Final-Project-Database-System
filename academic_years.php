<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Academic Years - DigiClear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-4">
    <h3 class="mb-4">Academic Years</h3>
    <form action="add_academic_year.php" method="POST" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" name="year" class="form-control" placeholder="e.g., 2024-2025" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success">Add Year</button>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>#</th><th>Year</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM tbl_academic_years");
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['year']}</td>
                    <td><a href='delete_academic_year.php?id={$row['id']}' class='btn btn-danger btn-sm'>Delete</a></td>
                  </tr>";
            $i++;
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
