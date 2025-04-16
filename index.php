<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "school_management");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle assignment creation (Teacher)
if (isset($_POST['create_assignment']) && $_SESSION['role'] == 'teacher') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $teacher_id = $_SESSION['user_id'];
    $sql = "INSERT INTO assignments (title, description, teacher_id) VALUES ('$title', '$description', '$teacher_id')";
    mysqli_query($conn, $sql);
    $success = "Assignment created!";
}

// Handle assignment deletion (Teacher)
if (isset($_GET['delete_assignment']) && $_SESSION['role'] == 'teacher') {
    $id = $_GET['delete_assignment'];
    $sql = "DELETE FROM assignments WHERE id='$id' AND teacher_id='{$_SESSION['user_id']}'";
    mysqli_query($conn, $sql);
    $success = "Assignment deleted!";
}

// Handle assignment update (Teacher)
if (isset($_POST['update_assignment']) && $_SESSION['role'] == 'teacher') {
    $id = $_POST['assignment_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $sql = "UPDATE assignments SET title='$title', description='$description' WHERE id='$id' AND teacher_id='{$_SESSION['user_id']}'";
    mysqli_query($conn, $sql);
    $success = "Assignment updated!";
}

// Handle section creation (Admin)
if (isset($_POST['create_section']) && $_SESSION['role'] == 'admin') {
    $name = $_POST['name'];
    $sql = "INSERT INTO sections (name) VALUES ('$name')";
    mysqli_query($conn, $sql);
    $success = "Section created!";
}

// Handle user management (Admin: insert, update, delete)
if (isset($_POST['admin_insert_user']) && $_SESSION['role'] == 'admin') {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    $table = $role == 'teacher' ? 'teachers' : ($role == 'student' ? 'students' : 'admins');
    $sql = "INSERT INTO $table (username, password, name) VALUES ('$username', '$password', '$name')";
    mysqli_query($conn, $sql);
    $success = "User added!";
}

if (isset($_POST['admin_update_user']) && $_SESSION['role'] == 'admin') {
    $id = $_POST['user_id'];
    $role = $_POST['role'];
    $username = $_POST['username'];
    $name = $_POST['name'];
    $table = $role == 'teacher' ? 'teachers' : ($role == 'student' ? 'students' : 'admins');
    $sql = "UPDATE $table SET username='$username', name='$name' WHERE id='$id'";
    mysqli_query($conn, $sql);
    $success = "User updated!";
}

if (isset($_GET['admin_delete_user']) && $_SESSION['role'] == 'admin') {
    $id = $_GET['admin_delete_user'];
    $role = $_GET['role'];
    $table = $role == 'teacher' ? 'teachers' : ($role == 'student' ? 'students' : 'admins');
    $sql = "DELETE FROM $table WHERE id='$id'";
    mysqli_query($conn, $sql);
    $success = "User deleted!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-gradient">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">School Management</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link">Welcome, <?php echo $_SESSION['username']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                <a class="nav-item nav-link btn btn-danger text-white" href="?logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'teacher'): ?>
            <!-- Teacher: Create Assignment -->
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create Assignment</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>
                        <button type="submit" name="create_assignment" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>

            <!-- Teacher: View and Manage Assignments -->
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Your Assignments</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $teacher_id = $_SESSION['user_id'];
                            $sql = "SELECT * FROM assignments WHERE teacher_id='$teacher_id'";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)):
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                    <td>
                                        <a href="?delete_assignment=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>">Update</button>
                                    </td>
                                </tr>
                                <!-- Update Assignment Modal -->
                                <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Assignment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="assignment_id" value="<?php echo $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Title</label>
                                                        <input type="text" name="title" class="form-control" value="<?php echo $row['title']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" class="form-control" required><?php echo $row['description']; ?></textarea>
                                                    </div>
                                                    <button type="submit" name="update_assignment" class="btn btn-primary">Update</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($_SESSION['role'] == 'student'): ?>
            <!-- Student: View Assignments -->
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Assignments</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT a.*, t.name AS teacher_name FROM assignments a JOIN teachers t ON a.teacher_id=t.id";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)):
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                    <td><?php echo $row['teacher_name']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($_SESSION['role'] == 'admin'): ?>
            <!-- Admin: Create Section -->
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create Section</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Section Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <button type="submit" name="create_section" class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>

            <!-- Admin: Manage Users -->
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Manage Users</h4>
                </div>
                <div class="card-body">
                    <!-- Add User -->
                    <h5>Add User</h5>
                    <form method="POST" class="mb-4">
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
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <button type="submit" name="admin_insert_user" class="btn btn-success">Add User</button>
                    </form>

                    <!-- View and Manage Users -->
                    <?php
                    $roles = ['teachers', 'students', 'admins'];
                    foreach ($roles as $role):
                        $role_singular = rtrim($role, 's');
                    ?>
                        <h5><?php echo ucfirst($role_singular); ?> List</h5>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM $role";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['username']; ?></td>
                                        <td><?php echo $row['name']; ?></td>
                                        <td>
                                            <a href="?admin_delete_user=<?php echo $row['id']; ?>&role=<?php echo $role_singular; ?>" class="btn btn-danger btn-sm">Delete</a>
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateUserModal<?php echo $role_singular . $row['id']; ?>">Update</button>
                                        </td>
                                    </tr>
                                    <!-- Update User Modal -->
                                    <div class="modal fade" id="updateUserModal<?php echo $role_singular . $row['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update <?php echo ucfirst($role_singular); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="role" value="<?php echo $role_singular; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Username</label>
                                                            <input type="text" name="username" class="form-control" value="<?php echo $row['username']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?php echo $row['name']; ?>" required>
                                                        </div>
                                                        <button type="submit" name="admin_update_user" class="btn btn-primary">Update</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>