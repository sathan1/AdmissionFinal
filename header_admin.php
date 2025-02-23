<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php"); // Redirect to the main login page or index page
    exit();
}

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Header styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f4f4f4;
            border-bottom: 2px solid #ccc;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .logout-btn {
            background-color: red;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .logout-btn:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Admin Portal</h1>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </header>
</body>
</html>
