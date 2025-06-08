<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit;
}

$student_id = $_GET['id'];

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM tbl_students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['student_name'];
    $number = $_POST['student_number'];
    $dept_id = $_POST['dept_id'];
    $year_id = $_POST['year_id'];

    $update = $conn->prepare("UPDATE tbl_students SET student_name=?, student_number=?, dept_id=?, year_id=? WHERE student_id=?");
    $update->bind_param("ssiii", $name, $number, $dept_id, $year_id, $student_id);
    $update->execute();

    header("Location: students.php");
    exit;
}

// Fetch departments (excluding Clinic and Library) and years
$departments = $conn->query("SELECT * FROM tbl_departments WHERE dept_name NOT IN ('Clinic', 'Library')");
$years = $conn->query("SELECT * FROM tbl_academic_years");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student - DigiClear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white p-4">

<div class="container">
    <h2>Edit Student</h2>
    <form method="post">
        <div class="mb-3">
            <label>Student Name</label>
            <input type="text" name="student_name" class="form-control" value="<?= htmlspecialchars($student['student_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Student Number</label>
            <input type="text" name="student_number" class="form-control" value="<?= htmlspecialchars($student['student_number']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="dept_id" class="form-select" required>
                <?php while ($dept = $departments->fetch_assoc()): ?>
                    <option value="<?= $dept['dept_id'] ?>" <?= $dept['dept_id'] == $student['dept_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['dept_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Academic Year</label>
            <select name="year_id" class="form-select" required>
                <?php while ($year = $years->fetch_assoc()): ?>
                    <option value="<?= $year['year_id'] ?>" <?= $year['year_id'] == $student['year_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($year['academic_year']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="students.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
