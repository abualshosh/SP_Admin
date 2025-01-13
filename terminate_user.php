<?php
require_once 'db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle findUser action
if (isset($_GET['action']) && $_GET['action'] === 'findUser') {
    $username_to_find = trim($_GET['username_to_find']);

    $get_user_data_query = "SELECT ID, USERNAME, FULLNAME, E_MAIL, TELENUMBER, JOB_TITLE, ROLE, WHCODE, GROUPID FROM sp_users WHERE UPPER(USERNAME) = UPPER(:username)";
    $get_user_data_stid = oci_parse($DB, $get_user_data_query);
    oci_bind_by_name($get_user_data_stid, ':username', $username_to_find);
    oci_execute($get_user_data_stid);

    $error = oci_error($get_user_data_stid);
    if ($error) {
        error_log("Oracle Error (findUser): " . $error['message']);
        $response = array('success' => false, 'message' => 'Database error during user lookup.');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $user_data = oci_fetch_assoc($get_user_data_stid);
    oci_free_statement($get_user_data_stid);

    if ($user_data) {
        $whcode = $user_data['WHCODE'];
        $find_team_leader_query = "SELECT USERNAME, FULLNAME FROM sp_users WHERE WHCODE = :whcode AND GROUPID = 4 AND STATUS = 1 AND ROWNUM = 1";
        $find_team_leader_stid = oci_parse($DB, $find_team_leader_query);
        oci_bind_by_name($find_team_leader_stid, ':whcode', $whcode);
        oci_execute($find_team_leader_stid);

        $error = oci_error($find_team_leader_stid);
        if ($error) {
            error_log("Oracle Error (findTeamLeader): " . $error['message']);
            $response = array('success' => false, 'message' => 'Database error during team leader lookup.');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        $team_leader_data = oci_fetch_assoc($find_team_leader_stid);
        oci_free_statement($find_team_leader_stid);

        if (!$team_leader_data) {
            $response = array('success' => false, 'message' => 'No active Team Leader found for this user\'s WHCODE.');
        } else {
            $response = array('success' => true, 'user' => $user_data, 'teamLeader' => $team_leader_data);
        }
    } else {
        $response = array('success' => false, 'message' => 'User not found.');
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle transformRequest action
elseif (isset($_POST['action']) && $_POST['action'] === 'transformRequest') {
    $username_to_transform = trim($_POST['username_to_transform']);
    $team_leader_username = trim($_POST['team_leader_username']);

    $transform_query = "UPDATE ntc_registration SET APPLIED_USER = :team_leader_username WHERE APPLIED_USER = :user_username AND STATUS IN ('0', '1')";
    $transform_stid = oci_parse($DB, $transform_query);
    oci_bind_by_name($transform_stid, ':team_leader_username', $team_leader_username);
    oci_bind_by_name($transform_stid, ':user_username', $username_to_transform);
    $r = oci_execute($transform_stid);

    $error = oci_error($transform_stid);
    if ($error) {
        error_log("Oracle Error (transformRequest): " . $error['message']);
        $response = array('success' => false, 'message' => 'Error transforming requests.');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($r) {
        $response = array('success' => true, 'message' => 'Requests for ' . htmlspecialchars($username_to_transform) . ' have been transformed to ' . htmlspecialchars($team_leader_username) . ' successfully.');
    } else {
        $response = array('success' => false, 'message' => 'No requests were transformed (possibly no matching requests found).'); // More specific message
    }

    header('Content-Type: application/json'); // MUST be before echo
echo json_encode($response);
exit; // Very important!
}

// HTML and JavaScript
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transform Request (with AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>
<body>
<div class="container">
    <h1>Transform Request</h1>
    <form>
        <div class="mb-3">
            <label for="username_to_find" class="form-label">Username to Find:</label>
            <input type="text" class="form-control" id="username_to_find" name="username_to_find" required>
        </div>
        <button type="button" id="findUserButton" class="btn btn-primary">Find User</button>

        <div id="userInformation" style="display: none;">
            <h3>User Information:</h3>
            <p><strong>Username:</strong> <span id="userUsername"></span></p>
            <p><strong>Full Name:</strong> <span id="userFullName"></span></p>
            <p><strong>Email:</strong> <span id="userEmail"></span></p>
            <p><strong>Telephone:</strong> <span id="userTelephone"></span></p>
            <p><strong>Job Title:</strong> <span id="userJobTitle"></span></p>
            <p><strong>Role:</strong> <span id="userRole"></span></p>
            <p><strong>WHCode:</strong> <span id="userWHCode"></span></p>
            <h3>Team Leader Information:</h3>
            <p><strong>Username:</strong> <span id="teamLeaderUsername"></span></p>
            <p><strong>Full Name:</strong> <span id="teamLeaderFullName"></span></p>
            <button type="button" id="transformButton" class="btn btn-warning mt-3" style="display: none;">Send Transform Request</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $("#findUserButton").click(function() {
        var username = $("#username_to_find").val();
        $.ajax({
            url: '', // This is correct now since everything is in the same file
            type: 'GET',
            data: { action: 'findUser', username_to_find: username },
            dataType: 'json',
            success: function(data) {
                console.log("Response:", data);
                if (data.success) {
                    $("#userInformation").show();
                    $("#userUsername").text(data.user.USERNAME);
                    $("#userFullName").text(data.user.FULLNAME);
                    $("#userEmail").text(data.user.E_MAIL);
                    $("#userTelephone").text(data.user.TELENUMBER);
                    $("#userJobTitle").text(data.user.JOB_TITLE);
                    $("#userRole").text(data.user.ROLE);
                    $("#userWHCode").text(data.user.WHCODE);
                    $("#teamLeaderUsername").text(data.teamLeader.USERNAME);
                    $("#teamLeaderFullName").text(data.teamLeader.FULLNAME);
                    $("#transformButton").show();
                } else {
                    alert(data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                alert("An error occurred during the AJAX request. Check the console.");
            }
        });
    });

    $("#transformButton").click(function() {
        var usernameToTransform = $("#userUsername").text
        var teamLeaderUsername = $("#teamLeaderUsername").text();

$.ajax({
    url: '', // Still correct
    type: 'POST',
    data: { action: 'transformRequest', username_to_transform: usernameToTransform, team_leader_username: teamLeaderUsername },
    dataType: 'json',
    success: function(data) {
        console.log("Transform Response:", data);
        if (data.success) {
            alert(data.message);
            window.location.href = 'user_management.php'; // Redirect if successful
        } else {
            alert(data.message);
        }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      console.error("Transform AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
      alert("An error occurred during the transform request. Check the console.");
    }
});
});
});
</script>
</body>
</html>