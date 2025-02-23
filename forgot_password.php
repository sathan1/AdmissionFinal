<?php
include 'include/db.php';
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Initialize session variables if not already set
if (!isset($_SESSION['otp_sent'])) $_SESSION['otp_sent'] = false;
if (!isset($_SESSION['otp_verified'])) $_SESSION['otp_verified'] = false;
if (!isset($_SESSION['reset_email'])) $_SESSION['reset_email'] = '';

// Initialize message
$message = "";

// Clear all sessions if requested
if (isset($_GET['reset'])) {
    session_unset();
    session_destroy();
    header("Location: forgot_password.php");
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['get_otp']) || isset($_POST['resend_otp'])) {
        $email = $_POST['email'] ?? '';

        // Validate email existence in the database
        $query = "SELECT * FROM users WHERE userEmail = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "Email not registered.";
        } else {
            // Store email in session
            $_SESSION['reset_email'] = $email;

            // Generate OTP and store in session
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_sent'] = true;

            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sathancreator@gmail.com';
                $mail->Password = 'tatqezizskzqjgqg';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('sathancreator@gmail.com', 'admission@nptc.ac.in');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password OTP';
                $mail->Body = "Your OTP is: <strong>$otp</strong>";

                $mail->send();
                $message = "OTP sent to your registered email.";
                header("Location: forgot_password.php");
                exit();
            } catch (Exception $e) {
                $message = "Failed to send OTP. Mailer Error: " . $mail->ErrorInfo;
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $entered_otp = $_POST['otp'] ?? '';

        if (isset($_SESSION['otp']) && $entered_otp == $_SESSION['otp']) {
            $_SESSION['otp_verified'] = true;
            $_SESSION['otp_sent'] = false; // Disable OTP form
            $message = "OTP verified successfully. Please set your new password.";
            header("Location: forgot_password.php");
            exit();
        } else {
            $message = "Invalid OTP. Please try again.";
        }
    } elseif (isset($_POST['set_password'])) {
        if ($_SESSION['otp_verified']) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

            // Update password in database
            $query = "UPDATE users SET userPassword = ? WHERE userEmail = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $new_password, $_SESSION['reset_email']);
            if ($stmt->execute()) {
                $message = "Password updated successfully.";
                session_unset();
                session_destroy();
                header("Location: index.php");
                exit();
            } else {
                $message = "Failed to update the password.";
            }
        } else {
            $message = "OTP not verified.";
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
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/stye.css">
    <title>Forgot Password</title>
</head>
<body>
    <div class="login-outer-container">
        <div class="login-container">
            <div class="login-area">
                <h3>FORGOT PASSWORD</h3>
                <p class="text-danger"><?= $message ?></p>
                <form method="post" class="login-items">
                    <!-- Email Section -->
                    <?php if (!$_SESSION['otp_sent'] && !$_SESSION['otp_verified']): ?>
                        <div id="email-section">
                            <label for="email">Registered Email</label>
                            <input type="email" class="login" name="email" value="<?= htmlspecialchars($_SESSION['reset_email']) ?>" placeholder="Your registered email" required />
                            <input type="submit" class="login-btn" name="get_otp" value="Get OTP" />
                        </div>
                    <?php endif; ?>

                    <!-- OTP Section -->
                    <?php if ($_SESSION['otp_sent'] && !$_SESSION['otp_verified']): ?>
                        <div id="otp-section">
                            <label for="otp">Enter OTP</label>
                            <input type="text" class="login" name="otp" placeholder="Enter the OTP" required />
                            <input type="submit" class="login-btn" name="verify_otp" value="Verify OTP" />
                            <button type="submit" class="login-btn" name="resend_otp">Resend OTP</button>
                        </div>
                    <?php endif; ?>

                    <!-- New Password Section -->
                    <?php if ($_SESSION['otp_verified']): ?>
                        <div id="password-section">
                            <label for="new_password">New Password</label>
                            <input type="password" class="login" name="new_password" placeholder="Enter your new password" required />
                            <input type="submit" class="login-btn" name="set_password" value="Set Password" />
                        </div>
                    <?php endif; ?>
                </form>
                <p class="p">Back to <a class="a" href="index.php">Login</a></p>
                <?php if ($_SESSION['otp_sent'] || $_SESSION['otp_verified']): ?>
                    <p><a class="a" href="?reset=true">Restart Process</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
