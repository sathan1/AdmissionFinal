<?php
include 'include/db.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];  // Change 'username' to 'email'
    $password = $_POST['password'];

    // Check if the user exists by email
    $query = "SELECT * FROM users WHERE userEmail = ?";  // Change 'userName' to 'userEmail'
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);  // Bind 'email' instead of 'username'
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if the password matches
        if ($user['userRole'] === 'admin' && $password === $user['userPassword']) {
            // Login admin with plaintext password
            $_SESSION['userId'] = $user['userId'];
            $_SESSION['userName'] = $user['userName'];
            $_SESSION['userRole'] = $user['userRole'];
            header("Location: admin/dashboard.php");
            exit();
        } elseif (password_verify($password, $user['userPassword'])) {
            // Login for other users with hashed passwords
            $_SESSION['userId'] = $user['userId'];
            $_SESSION['userName'] = $user['userName'];
            $_SESSION['userRole'] = $user['userRole'];
            header("Location: student/forms.php");
            exit();
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/stye.css">
    <script src="https://kit.fontawesome.com/c0e27fec68.js" crossorigin="anonymous"></script>
    <title>Login</title>

</head>

<body>
    <div class='login-outer-container'>
        <div class='login-container'>
            <div class='login-area'>
                <h3>LOGIN</h3>
                <p class="text-danger"><?= $message ?></p>
                <form method="post" class='login-items'>
                    <label for="email">Email</label>  <!-- Change 'username' to 'email' -->
                    <input type="email" class='login' name="email" placeholder='Your email' required />  <!-- Change 'username' to 'email' -->
                    <label for="password">Password</label>
                    <input type="password" class='login' name="password" placeholder="Your Password" required />
                    <input type="submit" class='login-btn' value="Login" />
                </form>
                <p class='p'>New to apply? <a class='a' href="register.php">Create an Account</a></p>
                <p class='p'>Forget password?<a class='a' href="forgot_password.php">Forgot password</a></p>
            </div>
        </div>
    </div>
</body>
</html>
