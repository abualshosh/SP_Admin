
<?php
session_start(); // Start the session at the very beginning of the file
require_once 'db_connect.php'; // Include database connection

if (!isset($_SESSION['userLogin'])) {
    include 'login.php';
    exit();
}
$error_message = "";
$success_message = "";

// Check if user ID is provided
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch user details
    $query = "SELECT * FROM sp_users WHERE ID = :user_id";
    $stid = oci_parse($DB, $query);
    oci_bind_by_name($stid, ':user_id', $user_id);
    oci_execute($stid);
    $user = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$user) {
        $error_message = "User not found.";
    }
} else {
    $error_message = "No user ID provided.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $username = strtolower(trim($_POST['username']));
    $fullname = trim($_POST['fullname']);
    $whcode = trim($_POST['whcode']);
    $job_title = trim($_POST['job_title']);
    $role = trim($_POST['role']);
    $telnumber = trim($_POST['telnumber']);
    $groupid = trim($_POST['groupid']);

    // Validation
    if (empty($username) || !preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error_message = "Username is required and can only contain uppercase letters and numbers.";
    } else {
        $email = $username . "@mtn.sd";

        if (empty($fullname) || empty($whcode) || empty($groupid)) {
            $error_message = "Fullname, Warehouse, and groupid are required.";
        } else {
            // Update user in the database
            $update_query = "UPDATE sp_users SET USERNAME = :username, FULLNAME = :fullname, WHCODE = :whcode, JOB_TITLE = :job_title, ROLE = :role, TELENUMBER = :telnumber, GROUPID = :groupid WHERE USER_ID = :user_id";
            $update_stid = oci_parse($DB, $update_query);
            oci_bind_by_name($update_stid, ':username', $username);
            oci_bind_by_name($update_stid, ':fullname', $fullname);
            oci_bind_by_name($update_stid, ':whcode', $whcode);
            oci_bind_by_name($update_stid, ':job_title', $job_title);
            oci_bind_by_name($update_stid, ':role', $role);
            oci_bind_by_name($update_stid, ':telnumber', $telnumber);
            oci_bind_by_name($update_stid, ':groupid', $groupid);
            oci_bind_by_name($update_stid, ':user_id', $user_id);
            $r = oci_execute($update_stid);

            if ($r) {
                $success_message = "User updated successfully!";
            } else {
                $e = oci_error($update_stid);
                $error_message = "Error updating user: " . htmlentities($e['message'], ENT_QUOTES);
            }
            oci_free_statement($update_stid);
        }
    }
}
oci_close($DB);
?>

    
<body>
<link rel="stylesheet" href="style.css">

    <div class="container">
        <h2 class="mt-4">Edit User</h2>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($user): ?>
            <form method="post" action="edit_user.php?id=<?php echo urlencode($user['ID']); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlentities($user['ID']); ?>">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlentities($user['USERNAME']); ?>">
                </div>
                <div class="form-group">
                    <label for="fullname">Fullname:</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlentities($user['FULLNAME']); ?>">
                </div>
                <div class="form-group">
                    <label for="whcode">Warehouse:</label>
                    <input type="text" class="form-control" id="whcode" name="whcode" value="<?php echo htmlentities($user['WHCODE']); ?>">
                </div>
                <div class="form-group">
                    <label for="job_title">Job Title:</label>
                    <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlentities($user['JOB_TITLE']); ?>">
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlentities($user['ROLE']); ?>">
                </div>
                <div class="form-group">
                    <label for="telnumber">Telephone Number:</label>
                    <input type="text" class="form-control" id="telnumber" name="telnumber" value="<?php echo htmlentities($user['TELENUMBER']); ?>">
                </div>
                <div class="form-group">
                    <label for="groupid">Group ID:</label>
                    <input type="text" class="form-control" id="groupid" name="groupid" value="<?php echo htmlentities($user['GROUPID']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update User</button>
            </form>
        <?php endif; ?>
    </div>
