<?php
require_once 'db_connect.php';

if (!isset($_SESSION['userLogin'])) {
    include 'login.php';
    exit();
}

$user_data = null;
$team_leader_data = null;
$current_user = $_SESSION['userLogin'];

// Function to log queries (for debugging)
function logQuery($query, $bindings = []) {
    $log_message = "Query: " . $query . "\nBindings: " . print_r($bindings, true) . "\n";
    error_log($log_message);
}

// Find user logic
if (isset($_POST['find_user']) && isset($_POST['username_to_find'])) {
    $username_to_find = trim($_POST['username_to_find']);

    $get_user_data_query = "SELECT ID, USERNAME, FULLNAME, E_MAIL, TELENUMBER, JOB_TITLE, ROLE, WHCODE, GROUPID FROM sp_users WHERE USERNAME = :username";
    logQuery($get_user_data_query, [':username' => $username_to_find]);
    $get_user_data_stid = oci_parse($DB, $get_user_data_query);
    oci_bind_by_name($get_user_data_stid, ':username', $username_to_find);
    oci_execute($get_user_data_stid);
    $user_data = oci_fetch_assoc($get_user_data_stid);
    oci_free_statement($get_user_data_stid);

    if ($user_data) {
        // Find Team Leader
        $whcode = $user_data['WHCODE'];
        $find_team_leader_query = "SELECT USERNAME, FULLNAME FROM sp_users WHERE WHCODE = :whcode AND GROUPID = 4 AND STATUS = 1 AND ROWNUM = 1";
        logQuery($find_team_leader_query, [':whcode' => $whcode]);
        $find_team_leader_stid = oci_parse($DB, $find_team_leader_query);
        oci_bind_by_name($find_team_leader_stid, ':whcode', $whcode);
        oci_execute($find_team_leader_stid);
        $team_leader_data = oci_fetch_assoc($find_team_leader_stid);
        oci_free_statement($find_team_leader_stid);

        if (!$team_leader_data) {
            echo "<script>alert('No active Team Leader found for this user\'s WHCODE.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
}

// Transform Request logic
if (isset($_POST['transform_request']) && $user_data && $team_leader_data) {
    $username_to_transform = $user_data['USERNAME'];
    $user_id_to_transform = $user_data['ID']; // Get the user ID
    $team_leader_username = $team_leader_data['USERNAME'];
    $team_leader_id = null;
    $get_team_leader_id_query = "SELECT ID FROM sp_users WHERE USERNAME = :username";
    logQuery($get_team_leader_id_query, [':username' => $team_leader_username]);
    $get_team_leader_id_stid = oci_parse($DB, $get_team_leader_id_query);
    oci_bind_by_name($get_team_leader_id_stid, ':username', $team_leader_username);
    oci_execute($get_team_leader_id_stid);
    $team_leader_id_row = oci_fetch_assoc($get_team_leader_id_stid);
    oci_free_statement($get_team_leader_id_stid);
    if($team_leader_id_row){
        $team_leader_id = $team_leader_id_row['ID'];
    }


    // Count NTC requests before transformation
    $ntc_count_query = "SELECT COUNT(*) AS ntc_count FROM ntc_registration WHERE APPLIED_USER = :user_id AND STATUS IN ('0', '1')";
    logQuery($ntc_count_query, [':user_id' => $user_id_to_transform]);
    $ntc_count_stid = oci_parse($DB, $ntc_count_query);
    oci_bind_by_name($ntc_count_stid, ':user_id', $user_id_to_transform);
    oci_execute($ntc_count_stid);
    $ntc_count_row = oci_fetch_assoc($ntc_count_stid);
    $ntc_count = $ntc_count_row['NTC_COUNT'];
    oci_free_statement($ntc_count_stid);

    $confirm_message = "Are you sure you want to transform " . htmlspecialchars($username_to_transform) . "'s NTC requests to " . htmlspecialchars($team_leader_username) . "?\\n\\n";
    if ($ntc_count > 0) {
        $confirm_message .= "There are " . $ntc_count . " open NTC requests that will be transferred.";
    } else {
        $confirm_message .= "There are no open NTC requests to transfer.";
    }

    echo "<script>
        if (confirm('" . $confirm_message . "')) {
            document.getElementById('transform_confirmed_form').submit();
        }
    </script>";
}

// Hidden form
echo "<form id='transform_confirmed_form' method='POST' style='display:none;'>";
echo "<input type='hidden' name='transform_confirmed' value='1'>";
echo "<input type='hidden' name='username_to_transform' value='" . htmlspecialchars($_POST['username_to_find'] ?? '') . "'>";
echo "</form>";

if (isset($_POST['transform_confirmed']) && $_POST['transform_confirmed'] == 1) {

    $username_to_transform = trim($_POST['username_to_transform']);
    $get_user_id_query = "SELECT ID FROM sp_users WHERE USERNAME = :username";
    logQuery($get_user_id_query, [':username' => $username_to_transform]);
    $get_user_id_stid = oci_parse($DB, $get_user_id_query);
    oci_bind_by_name($get_user_id_stid, ':username', $username_to_transform);
    oci_execute($get_user_id_stid);
    $user_id_row = oci_fetch_assoc($get_user_id_stid);
    oci_free_statement($get_user_id_stid);
    if ($user_id_row) {
        $user_id_to_transform = $user_id_row['ID'];
        $get_team_leader_username_query = "SELECT USERNAME FROM sp_users WHERE GROUPID = 4 AND STATUS = 1 AND WHCODE = (SELECT WHCODE FROM sp_users WHERE ID = :user_id) AND ROWNUM = 1";
        logQuery($get_team_leader_username_query, [':user_id' => $user_id_to_transform]);
        $get_team_leader_username_stid = oci_parse($DB, $get_team_leader_username_query);
        oci_bind_by_name($get_team_leader_username_stid, ':user_id', $user_id_to_transform);
        oci_execute($get_team_leader_username_stid);
        $team_leader_username_row = oci_fetch_assoc($get_team_leader_username_stid);
        oci_free_statement($get_team_leader_username_stid);

        if($team_leader_username_row){
            $team_leader_username = $team_leader_username_row['USERNAME'];
            $get_team_leader_id_query = "SELECT ID FROM sp_users WHERE USERNAME = :username";
            logQuery($get_team_leader_id_query, [':username' => $team_leader_username]);
            $get_team_leader_id_stid = oci_parse($DB, $get_team_leader_id_query);
            oci_bind_by_name($get_team_leader_id_stid, ':username', $team_leader_username);
            oci_execute($get_team_leader_id_stid);
            $team_leader_id_row = oci_fetch_assoc($get_team_leader_id_stid);
            oci_free_statement($get_team_leader_id_stid);
            if($team_leader_id_row){
                $team_leader_id = $team_leader_id_row['ID'];
                $transform_query = "UPDATE ntc_registration SET APPLIED_USER = :team_leader_id WHERE APPLIED_USER = :user_id AND STATUS IN ('0', '1')";
                logQuery($transform_query, [':team_leader_id' => $team_leader_id, ':user_id' => $user_id_to_transform]);
                $transform_stid = oci_parse($DB, $transform_query);
                oci_bind_by_name($transform_stid, ':team_leader_id', $team_leader_id);
                oci_bind_by_name($transform_stid, ':user_id', $user_id_to_transform);
                $r = oci_execute($transform_stid);
                oci_free_statement($transform_stid);

                if (!$r) {
                    $e = oci_error($transform_stid);
                    $error_message = "Error transforming requests: " . htmlentities($e['message'], ENT_QUOTES);
                    error_log($error_message);
                    echo "<script>alert('An error occurred during transformation. Please check the logs.');</script>";
                } else {
                    echo "<script>alert('Requests for " . htmlspecialchars($username_to_transform) . " have been transformed to " . htmlspecialchars($team_leader_username) . " successfully.'); window.location.href='user_management.php';</script>";
                }
            }else{
                echo "<script>alert('Team leader id not found.');</script>";
            }
        }else{
            echo "<script>alert('Team leader username not found.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
}
?>
<?php
// Hidden form
echo "<form id='transform_confirmed_form' method='POST' style='display:none;'>";
echo "<input type='hidden' name='transform_confirmed' value='1'>";
echo "<input type='hidden' name='username_to_transform' value='" . htmlspecialchars($_POST['username_to_find'] ?? '') . "'>";
echo "<input type='hidden' name='termination_reason' id='termination_reason' value=''>";
echo "</form>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transform Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>Transform Request</h1>

    <form method="post">
        <div class="mb-3">
            <label for="username_to_find" class="form-label">Username to Find:</label>
            <input type="text" class="form-control" id="username_to_find" name="username_to_find" required>
        </div>
        <button type="submit" name="find_user" class="btn btn-primary">Find User</button>
        <?php if ($user_data && $team_leader_data): ?>
            <div class="mt-3 border p-3">
                <h3>User Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['USERNAME']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user_data['FULLNAME']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['E_MAIL']); ?></p>
                <p><strong>Telephone:</strong> <?php echo htmlspecialchars($user_data['TELENUMBER']); ?></p>
                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($user_data['JOB_TITLE']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user_data['ROLE']); ?></p>
                <p><strong>WHCode:</strong> <?php echo htmlspecialchars($user_data['WHCODE']); ?></p>
                <h3>Team Leader Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($team_leader_data['USERNAME']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($team_leader_data['FULLNAME']); ?></p>
                <button type="submit" name="transform_request" class="btn btn-warning mt-3">Send Transform Request</button>
            </div>
        <?php elseif ($user_data && !$team_leader_data): ?>
            <div class="mt-3 border p-3">
                <h3>User Information:</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['USERNAME']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user_data['FULLNAME']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['E_MAIL']); ?></p>
                <p><strong>Telephone:</strong> <?php echo htmlspecialchars($user_data['TELENUMBER']); ?></p>
                <p><strong>Job Title:</strong> <?php echo htmlspecialchars($user_data['JOB_TITLE']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user_data['ROLE']); ?></p>
                <p><strong>WHCode:</strong> <?php echo htmlspecialchars($user_data['WHCODE']); ?></p>
                <p class="text-danger">No active Team Leader found for this user's WHCODE.</p>
            </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>