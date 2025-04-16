<?php
session_start();

// Redirect to index if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "school_management");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle registration
$error = '';
$success = '';
if (isset($_POST['register'])) {
    // Validate inputs
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (!in_array($role, ['teacher', 'student', 'admin'])) {
        $error = "Invalid role selected!";
    } elseif (empty($username) || empty($password) || empty($name)) {
        $error = "All fields are required!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Map role to table
        $table = $role == 'teacher' ? 'teachers' : ($role == 'student' ? 'students' : 'admins');
        
        // Prepare and execute query
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO $table (username, password, name) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $username, $password_hashed, $name);
            if ($stmt->execute()) {
                $success = "Registration successful! Please <a href='login.php'>login</a>.";
            } else {
                if ($stmt->errno == 1062) { // Duplicate entry
                    $error = "Username '$username' already exists!";
                } else {
                    $error = "Registration failed: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare query: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gradient">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
            <h2 class="text-center mb-4">Register</h2>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
            </form>
            <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>