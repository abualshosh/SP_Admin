<?php
session_start();

// db_connect.php (Database Connection)
?>

<?php
// Include the database connection
require_once 'db_connect.php';

class LDAP
{
    const LDAP_SERVER_V = "172.26.105.12"; // Replace with your LDAP server IP
    const LDAP_DOMAIN_V = "@mtn.sd"; // Replace with your user suffix

    public function ldap_auth($user, $pass)
    {
        $ldap_conn = ldap_connect(self::LDAP_SERVER_V);
        if (!$ldap_conn) {
            throw new Exception("Failed to connect to LDAP server: " . ldap_error($ldap_conn));
        }

        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        $ldap_user = $user . self::LDAP_DOMAIN_V;

        $ldap_bind = @ldap_bind($ldap_conn, $ldap_user, $pass);

        if (!$ldap_bind) {
            $error = ldap_error($ldap_conn);
            ldap_close($ldap_conn);
            throw new Exception("LDAP bind failed: " . $error);
        }

        ldap_close($ldap_conn);
        return true;
    }
}

function get_user_data_from_db($username)
{
    global $DB;
    $query = "SELECT ID, USERNAME, GROUPID, FULLNAME, WHCODE, JOB_TITLE FROM SP_USERS WHERE USERNAME = :username AND STATUS = 1 and IS_ADMIN = 1";
    $parse = oci_parse($DB, $query);
    oci_bind_by_name($parse, ':username', $username);
    oci_execute($parse);
    $user_data = oci_fetch_assoc($parse);
    oci_free_statement($parse);
    return $user_data;
}

function start_user_session($user_data)
{
    $_SESSION['userLogin'] = $user_data['USERNAME'];
    $_SESSION['GROUPID'] = $user_data['GROUPID'];
    $_SESSION['USERID'] = $user_data['ID'];
    $_SESSION['FULLNAME'] = $user_data['FULLNAME'];
    $_SESSION['WHCODE'] = $user_data['WHCODE'];
    $_SESSION['JOB_TITLE'] = $user_data['JOB_TITLE'];
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    update_last_login($user_data['USERNAME']);
    insert_login_log($user_data);
}

function update_last_login($username)
{
    global $DB;
    $query = "UPDATE SP_USERS SET LAST_LOGIN = SYSDATE, IPADDRESS = :ip, LOGGEDIN = :session_id WHERE USERNAME = :username";
    $parse = oci_parse($DB, $query);
    oci_bind_by_name($parse, ':ip', $_SERVER['REMOTE_ADDR']);
    oci_bind_by_name($parse, ':session_id', session_id());
    oci_bind_by_name($parse, ':username', $username);
    oci_execute($parse);
    oci_commit($DB);
    oci_free_statement($parse);
}

function insert_login_log($user_data)
{
    global $DB;
    $query = "INSERT INTO SP_LOG (USERNAME, WHCODE, IPADDRESS, JOB_TITLE) VALUES (:username, :whcode, :ip, :job_title)";
    $parse = oci_parse($DB, $query);
    oci_bind_by_name($parse, ':username', $user_data['USERNAME']);
    oci_bind_by_name($parse, ':whcode', $user_data['WHCODE']);
    oci_bind_by_name($parse, ':ip', $_SERVER['REMOTE_ADDR']);
    oci_bind_by_name($parse, ':job_title', $user_data['JOB_TITLE']);
    oci_execute($parse);
    oci_commit($DB);
    oci_free_statement($parse);
}

function popup($message, $location)
{
    echo "<script type='text/javascript'>alert('" . $message . "'); window.location.href = '" . $location . "';</script>";
}

$ldap = new LDAP();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        if ($ldap->ldap_auth($username, $password)) {
            $user_data = get_user_data_from_db($username);
            if ($user_data) {
                start_user_session($user_data);
                header('location:index.php');
                exit();
            } else {
                popup('User IS NOT ADMIN.', 'login.php');
            }
        } else {
            popup('Invalid username or password', 'login.php');
        }
    } catch (Exception $e) {
        popup('LDAP Error: ' . $e->getMessage(), 'login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Admin Page - Login</title>
    <link rel="stylesheet" href="css/bootstrap.min.css"></head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-header">
                        <h2 class="text-center">SP Admin Page</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $_GET['error']; ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>