<?php

// Authentication check
if (!isset($_SESSION['userLogin'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php'; // Include database connection

// Get the logged-in username (for CREATED_BY)
$created_by = $_SESSION['userLogin'];

// Search warehouses functionality
$warehouse_search_term = isset($_GET['warehouse_search']) ? strtoupper(trim($_GET['warehouse_search'])) : '';
$warehouse_where_clause = '';
if (!empty($warehouse_search_term)) {
    $warehouse_where_clause = "WHERE UPPER(WH_NAME) LIKE '%" . $warehouse_search_term . "%' OR UPPER(WH_CODE) LIKE '%" . $warehouse_search_term . "%'";
}

// Fetch warehouses for the dropdown with search
$warehouses_query = "SELECT WH_CODE, WH_NAME, ZONE FROM bss.warehouses " . $warehouse_where_clause . " ORDER BY WH_NAME";
$warehouses_stid = oci_parse($DB, $warehouses_query);
oci_execute($warehouses_stid);
$warehouses = [];
while ($row = oci_fetch_array($warehouses_stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $warehouses[] = $row;
}
oci_free_statement($warehouses_stid);

$groups_query = "SELECT GROUPID, JOB_DESC FROM SP_GROUPS WHERE GROUPID != 1 ORDER BY GROUPID";
$groups_stid = oci_parse($DB, $groups_query);
oci_execute($groups_stid);
$groups = [];
while ($row = oci_fetch_array($groups_stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $groups[] = $row;
}
oci_free_statement($groups_stid);



$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data (sanitize input!)
    $username = strtolower(trim($_POST['username']));
    $fullname = trim($_POST['fullname']);
    $whcode = trim($_POST['whcode']);
    $fr_name = "";
    $region = "";
    $status = "1";
    $job_title = trim($_POST['job_title']);
    $role = trim($_POST['role']);
    $email = '';
    $telnumber = trim($_POST['telnumber']);
    // $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $groupid = trim($_POST['groupid']); // Get groupid from the dropdown
    // Validation
    if (empty($username) || !preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error_message = "Username is required and can only contain uppercase letters and numbers.";
    } else {
        $email =$username. "@mtn.sd"; // Add "@mtn.sd" to the username

        if (empty($fullname) || empty($whcode) || empty($groupid)) {
            $error_message = "Fullname ,Warehouse and groupid are required.";
        } 
        else {
            // Fetch WH_NAME and ZONE based on selected WH_CODE
            $wh_details_query = "SELECT WH_NAME, ZONE FROM bss.warehouses WHERE WH_CODE = :whcode";
            $wh_details_stid = oci_parse($DB, $wh_details_query);
            oci_bind_by_name($wh_details_stid, ':whcode', $whcode);
            oci_execute($wh_details_stid);
            $wh_details_row = oci_fetch_assoc($wh_details_stid);
            if ($wh_details_row) {
                $fr_name = $wh_details_row['WH_NAME'];
                $region = $wh_details_row['ZONE'];
            } else {
                $error_message = "Invalid warehouse selected.";
                oci_free_statement($wh_details_stid);
                goto end_post; // Skip the rest of the post processing
            }
            oci_free_statement($wh_details_stid);

            // Check if username already existse
            $check_query = "SELECT COUNT(*) FROM sp_users WHERE lower (USERNAME) = lower(:username)";
            $check_stid = oci_parse($DB, $check_query);
            oci_bind_by_name($check_stid, ':username', $username);
            oci_execute($check_stid);
            $check_row = oci_fetch_row($check_stid);
            $user_exists = $check_row[0];
            oci_free_statement($check_stid);

            if ($user_exists > 0) {
                $error_message = "Username already exists.";
            } else {
                // Insert user into the database
                $insert_query = "INSERT INTO sp_users (USERNAME, FULLNAME, WHCODE, FR_NAME, STATUS, JOB_TITLE, ROLE, E_MAIL, REGION, TELENUMBER, CREATION_DATE, CREATED_BY, GROUPID) 
                                VALUES (:username, :fullname, :whcode, :fr_name, :status, :job_title, :role, :email, :region, :telnumber, SYSDATE, :created_by, :groupid)";
                $insert_stid = oci_parse($DB, $insert_query);
                oci_bind_by_name($insert_stid, ':username', $username);
                oci_bind_by_name($insert_stid, ':fullname', $fullname);
                oci_bind_by_name($insert_stid, ':whcode', $whcode);
                oci_bind_by_name($insert_stid, ':fr_name', $fr_name);
                oci_bind_by_name($insert_stid, ':status', $status);
                oci_bind_by_name($insert_stid, ':job_title', $job_title);
                oci_bind_by_name($insert_stid, ':role', $role);
                oci_bind_by_name($insert_stid, ':email', $email);
                oci_bind_by_name($insert_stid, ':region', $region);
                oci_bind_by_name($insert_stid, ':telnumber', $telnumber);
                oci_bind_by_name($insert_stid, ':created_by', $created_by);
                // oci_bind_by_name($insert_stid, ':is_admin', $is_admin);
                oci_bind_by_name($insert_stid, ':groupid', $groupid); // Bind groupid


                $r = oci_execute($insert_stid);

                if ($r) {
                    $success_message = "User created successfully!";
                } else {
                    $e = oci_error($insert_stid);
                    $error_message = "Error creating user: " . htmlentities($e['message'], ENT_QUOTES);
                }
                oci_free_statement($insert_stid);
            }
        }
    }
    end_post:
}


oci_close($DB);
?>

<h1>Create User</h1>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label for="username" class="form-label">Username* (e.g., USER123)</label>
        <input type="text" class="form-control" id="username" name="username" required>
        <div class="form-text"></div>
    </div>
    <div class="mb-3">
        <label for="fullname" class="form-label">Fullname*</label>
        <input type="text" class="form-control" id="fullname" name="fullname" required>
    </div>

    <div class="mb-3">
        <label for="warehouse_search" class="form-label">Search Warehouse:</label>
        <div class="input-group">
            <input type="text" class="form-control" id="warehouse_search" name="warehouse_search" value="<?php echo htmlspecialchars($warehouse_search_term); ?>">
            <button class="btn btn-outline-secondary" type="button" onclick="filterWarehouses()">Search</button>
        </div>

        <label for="whcode" class="form-label">Warehouse*</label>
        <select class="form-select" id="whcode" name="whcode" required>
            <option value="">Select a Warehouse</option>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?php echo htmlspecialchars($warehouse['WH_CODE']); ?>">
                    <?php echo htmlspecialchars($warehouse['WH_NAME']) . " (" . htmlspecialchars($warehouse['WH_CODE']) . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <script>
        function filterWarehouses() {
            var searchTerm = document.getElementById('warehouse_search').value;
            window.location.href = '?page=create_user&warehouse_search=' + encodeURIComponent(searchTerm);
        }
    </script>
<div class="mb-3">
        <label for="groupid" class="form-label">Group*</label>
        <select class="form-select" id="groupid" name="groupid" required>
            <option value="">Select a Group</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?php echo htmlspecialchars($group['GROUPID']); ?>">
                    <?php echo htmlspecialchars($group['JOB_DESC']) . " (" . htmlspecialchars($group['GROUPID']) . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="status" name="status">
        <label class="form-check-label" for="status">Active</label>
    </div>
        <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
        <label class="form-check-label" for="is_admin">Is Admin</label>
    </div> -->
    <div class="mb-3">
        <label for="job_title" class="form-label">Job Title</label>
        <input type="text" class="form-control" id="job_title" name="job_title">
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <input type="text" class="form-control" id="role" name="role">
    </div>
    <!-- <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control" id="email" name="email">
    </div> -->
    <div class="mb-3">
        <label for="telnumber" class="form-label">Tel Number</label>
        <input type="text" class="form-control" id="telnumber" name="telnumber">
    </div>
    <button type="submit" class="btn btn-primary">Create User</button>
</form>