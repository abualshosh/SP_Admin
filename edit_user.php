<?php
// edit_user.php
session_start();
require_once 'db_connect.php';
if (isset($_GET['ajax_edit']) && $_GET['ajax_edit'] == 1) {
    if (isset($_GET['id'])) {
        $user_id = $_GET['id'];

        $user_query = "SELECT ID, JOB_TITLE, ROLE, TELENUMBER, GROUPID FROM sp_users WHERE ID = :user_id";
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


        if ($user && $groups) { //Check if $groups is not empty
            include 'edit_user_form.php';
        } else {
            echo '<div class="alert alert-danger">User or Groups not found.</div>';
        }
    }
    exit;
}

if (isset($_GET['ajax_save']) && $_GET['ajax_save'] == 1) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = $_GET['id'];
        $job_title = trim($_POST['job_title']);
        $role = trim($_POST['role']);
        $telnumber = trim($_POST['telnumber']);
        $groupid = trim($_POST['groupid']); // Now get groupid from POST

        $update_query = "UPDATE sp_users SET JOB_TITLE = :job_title, ROLE = :role, TELENUMBER = :telnumber, GROUPID = :groupid WHERE ID = :user_id";
        $update_stid = oci_parse($DB, $update_query);
        oci_bind_by_name($update_stid, ':job_title', $job_title);
        oci_bind_by_name($update_stid, ':role', $role);
        oci_bind_by_name($update_stid, ':telnumber', $telnumber);
        oci_bind_by_name($update_stid, ':groupid', $groupid); //Bind groupid
        oci_bind_by_name($update_stid, ':user_id', $user_id);

        $r = oci_execute($update_stid);
        if (!$r) {
            $e = oci_error($update_stid);
            echo "Error updating user: " . htmlentities($e['message'], ENT_QUOTES);
            oci_free_statement($update_stid);
            exit;
        }

        oci_free_statement($update_stid);
        echo "success";
        exit;
    }
    exit;
}
?>