<?php
session_start();
require_once 'db_connect.php';

// Check if the user is authenticated
if (!isset($_SESSION['userLogin'])) {
    // User is not logged in, redirect to the login page
    header("Location: login.php"); // Redirect to your login page (login.php)
    exit(); // Important: Stop further execution to prevent content from being displayed
}

// Function to determine the active tab
// function getActiveTab() {
//     return isset($_GET['tab']) ? $_GET['tab'] : 'home';
// }

// $activeTab = getActiveTab();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Admin Page</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="d-flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-grow-1 ms-3 mt-3">
        <div class="container-fluid">
            <?php
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
                $pageFile = $page . '.php'; // Construct the filename
                if (file_exists($pageFile)) {
                    include $pageFile; // Include the appropriate page
                } else {
                    echo "<h1>Page Not Found</h1>";
                }
            } else {
                echo "<h1>Welcome to the Admin Area</h1><p>Select an option from the menu.</p>";
            }
            ?>
        </div>
    </main>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>