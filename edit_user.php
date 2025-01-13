<?php
// edit_user.php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['userLogin'])) {
    include 'login.php';
    exit();
}

if (isset($_GET['ajax_edit']) && $_GET['ajax_edit'] == 1) {
    if (isset($_GET['id'])) {
        $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($user_id === false) {
            echo '<div class="alert alert-danger">Invalid user ID.</div>';
            exit;
        }

        $user_query = "SELECT ID, JOB_TITLE, ROLE, TELENUMBER, GROUPID, STATUS FROM sp_users WHERE ID = :user_id";
        $user_stid = oci_parse($DB, $user_query);
        oci_bind_by_name($user_stid, ':user_id', $user_id);
        oci_execute($user_stid);
        $user = oci_fetch_assoc($user_stid);
        oci_free_statement($user_stid);

        $groups_query = "SELECT GROUPID, JOB_DESC FROM SP_GROUPS WHERE GROUPID != 1 ORDER BY GROUPID";
        $groups_stid = oci_parse($DB, $groups_query);
        oci_execute($groups_stid);
        $groups = [];
        while ($group = oci_fetch_assoc($groups_stid)) {
            $groups[] = $group;
        }
        oci_free_statement($groups_stid);

        if ($user && $groups) {
            include 'edit_user_form.php';
        } else {
            echo '<div class="alert alert-danger">User or Groups not found.</div>';
        }
    }
    exit;
}

if (isset($_GET['ajax_save']) && $_GET['ajax_save'] == 1) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $job_title = filter_input(INPUT_POST, 'job_title', FILTER_SANITIZE_STRING);
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        $telnumber = filter_input(INPUT_POST, 'telnumber', FILTER_SANITIZE_STRING);
        $groupid = filter_input(INPUT_POST, 'groupid', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

        if ($user_id === false || $job_title === null || $role === null || $telnumber === null || $groupid === false || $status === false) {
            error_log("Invalid input data: " . print_r($_POST, true));
            echo json_encode(["status" => "error", "message" => "Invalid input data."]);
            exit;
        }

        $current_user = $_SESSION['userLogin'] ?? null; // Use $_SESSION['userLogin']?? null; // Get the currently logged-in user
        if ($current_user === null) {
            error_log("Current user not found in session.");
            echo json_encode(["status" => "error", "message" => "Current user information is missing."]);
            exit;
        }

        $update_query = "UPDATE sp_users SET JOB_TITLE = :job_title, ROLE = :role, TELENUMBER = :telnumber, GROUPID = :groupid, STATUS = :status, TERMINATED_BY = :terminated_by, TERMINATED_DATE = SYSDATE WHERE ID = :user_id";
        $update_stid = oci_parse($DB, $update_query);
        oci_bind_by_name($update_stid, ':job_title', $job_title);
        oci_bind_by_name($update_stid, ':role', $role);
        oci_bind_by_name($update_stid, ':telnumber', $telnumber);
        oci_bind_by_name($update_stid, ':groupid', $groupid);
        oci_bind_by_name($update_stid, ':status', $status);
        oci_bind_by_name($update_stid, ':user_id', $user_id);
        oci_bind_by_name($update_stid, ':terminated_by', $current_user);

        if ($status == 0) {
            // oci_bind_by_name($update_stid, ':terminated_date', date('Y-m-d H:i:s'));
        } else {
            // oci_bind_by_name($update_stid, ':terminated_date', null);
        }

        $r = oci_execute($update_stid);
        if (!$r) {
            $e = oci_error($update_stid);
            error_log("Database error: " . $e['message']);
            echo json_encode(["status" => "error", "message" => "A database error occurred."]);
            oci_free_statement($update_stid);
            exit;
        }

        oci_free_statement($update_stid);
        echo json_encode(["status" => "success"]);
        exit;
    }
    exit;
}
?>