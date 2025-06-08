<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM tbl_admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password using MD5 hash
        if (md5($password) === $user['password']) {
            $_SESSION['admin'] = $username;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid login credentials.";
        }
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DigiClear Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #004e92, #000428);
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin: 0;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .login-card h3 {
            color: #004e92;
            font-weight: 700;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #004e92;
            text-align: left;
            display: block;
        }
        .form-control {
            border-radius: 10px;
        }
        .form-control:focus {
            border-color: #004e92;
            box-shadow: 0 0 0 0.2rem rgba(0, 78, 146, 0.25);
        }
        .btn-primary {
            background-color: #004e92;
            border: none;
            border-radius: 10px;
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #003366;
        }
        .alert {
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .logo-text {
            font-weight: bold;
            font-size: 1.2rem;
            color: #004e92;
            margin-top: 10px;
            margin-bottom: 30px;
        }
        .logo-img {
            width: 100px;
            height: auto;
            object-fit: contain;
        }
    </style>
</head>
<body>

    <div class="login-card">
    <img src="assests/images/nbsclogo.png" alt="NBSC Logo" class="logo-img img-fluid mb-2">
    <div class="logo-text">NBSC DigiClear System</div>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>
        </form>
    </div>

</body>
</html>
