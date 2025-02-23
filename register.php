<?php
include 'include/db.php';

$message = "";
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hashing the password
    $role = "student"; // Default role is 'student'

    // Check if the userName or userEmail already exists
    $checkQuery = "SELECT userName, userEmail FROM users WHERE userName = ? OR userEmail = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        if ($existingUser['userName'] === $name) {
            $message = "User with this name already exists!";
        } elseif ($existingUser['userEmail'] === $email) {
            $message = "User with this email already exists!";
        }
    } else {
        // Register the new user
        $query = "INSERT INTO users (userName, userEmail, userPassword, userRole) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $message = "Registration successful! Redirecting to login...";
            $redirect = true; // Set redirect flag to true
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stye.css">
    <script src="https://kit.fontawesome.com/c0e27fec68.js" crossorigin="anonymous"></script>
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <title>Sign Up</title>
</head>

<body>
    <div class='login-outer-container'>
        <div class='login-container'>
            <div class='login-area'>
                <h3>REGISTER TO APPLY</h3>
                <p class="text-danger"><?= $message ?></p> <!-- Displaying the message -->
                <form method="post" class='login-items'>
                    <label for="name">Name</label>
                    <input type="text" class='login' name="name" placeholder='Your name' required />
                    <label for="email">Email</label>
                    <input type="email" class='login' name="email" placeholder="your-email@gmail.com" required />
                    <label for="password">Password</label>
                    <input type="password" class='login' name="password" placeholder='Enter password' required />
                    <input type="submit" class='login-btn' value="Register" />
                </form>
                <p class='p'>Already have an account? 
                    <a class='a' href="index.php">Please Login</a>
                </p>
                <p class="text-danger"><?= htmlspecialchars($message) ?></p> <!-- Escaping to prevent XSS -->
            </div>
        </div>
    </div>

    <?php if ($redirect): ?>
    <script>
        // Show popup and redirect after 2 seconds
        alert("Registration successful! Redirecting to login page...");
        setTimeout(() => {
            window.location.href = "index.php";
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
