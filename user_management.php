<?php
require_once 'db_connect.php';

if (!isset($_SESSION['userLogin'])) {
    include 'login.php';
    exit();
}

// Search functionality
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';
if (!empty($search_term)) {
    $search_term = strtoupper(trim($search_term));
    $where_clause = "WHERE UPPER(USERNAME) LIKE '%" . $search_term . "%' OR UPPER(FULLNAME) LIKE '%" . $search_term . "%' OR UPPER(E_MAIL) LIKE '%" . $search_term . "%' OR UPPER(TELENUMBER) LIKE '%" . $search_term . "%'";
}

// Pagination settings
$results_per_page = 10;
$current_page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($current_page - 1) * $results_per_page;

// Count total users
$count_query = "SELECT COUNT(*) AS total FROM sp_users " . $where_clause;
$count_stid = oci_parse($DB, $count_query);
oci_execute($count_stid);
$count_row = oci_fetch_assoc($count_stid);
$total_users = $count_row['TOTAL'];
oci_free_statement($count_stid);

$total_pages = ceil($total_users / $results_per_page);

// Fetch users
$query = "SELECT * FROM (
        SELECT a.*, ROWNUM rnum FROM (
            SELECT ID, USERNAME, FULLNAME, WHCODE, FR_NAME, STATUS, JOB_TITLE, ROLE, E_MAIL, REGION, TELENUMBER, LAST_LOGIN, CREATION_DATE, LAST_LOGOUT FROM sp_users " . $where_clause . "
            ORDER BY CREATION_DATE desc
        ) a WHERE ROWNUM <= :max_rows
    ) WHERE rnum > :min_rows";

$stid = oci_parse($DB, $query);
$max_rows = $offset + $results_per_page;
$min_rows = $offset;
oci_bind_by_name($stid, ':max_rows', $max_rows);
oci_bind_by_name($stid, ':min_rows', $min_rows);
oci_execute($stid);

$users = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $users[] = $row;
}
oci_free_statement($stid);
?>

    <title>User Management</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
       
        
        .pagination {
            margin-top: 20px; /* Add some spacing above pagination */
        }
    </style>
    <h1>User Management</h1>

    <form method="get">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Search..." name="search" value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
        <input type="hidden" name="page" value="user_management">
    </form>

    <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>USERNAME</th>
                    <th>FULLNAME</th>
                    <th>WHCODE</th>
                    <th>FR_NAME</th>
                    <th>STATUS</th>
                    <th>JOB_TITLE</th>
                    <th>ROLE</th>
                    <th>E_MAIL</th>
                    <th>REGION</th>
                    <th>TELENUMBER</th>
                    <th>LAST_LOGIN</th>
                    <th>CREATION_DATE</th>
                    <th>LAST_LOGOUT</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['USERNAME']); ?></td>
                        <td><?php echo htmlspecialchars($user['FULLNAME']); ?></td>
                        <td><?php echo htmlspecialchars($user['WHCODE']); ?></td>
                        <td><?php echo htmlspecialchars($user['FR_NAME']); ?></td>
                        <td><?php echo ($user['STATUS'] == 0) ? "Terminated" : (($user['STATUS'] == 1) ? "Active" : htmlspecialchars($user['STATUS'])); ?></td>
                        <td><?php echo htmlspecialchars($user['JOB_TITLE']); ?></td>
                        <td><?php echo htmlspecialchars($user['ROLE']); ?></td>
                        <td><?php echo htmlspecialchars($user['E_MAIL']); ?></td>
                        <td><?php echo htmlspecialchars($user['REGION']); ?></td>
                        <td><?php echo htmlspecialchars($user['TELENUMBER']); ?></td>
                        <td><?php echo htmlspecialchars($user['LAST_LOGIN']); ?></td>
                        <td><?php echo htmlspecialchars($user['CREATION_DATE']); ?></td>
                        <td><?php echo htmlspecialchars($user['LAST_LOGOUT']); ?></td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm edit-user-btn"
                                    data-user-id="<?php echo htmlspecialchars($user['ID']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal">Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="editUserModalBody">
                        Loading...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveUserChanges">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                    <a class="page-link"
                       href="?page=user_management&page_num=<?php echo $current_page - 1;
                       if (!empty($search_term)) echo '&search=' . htmlspecialchars($search_term); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                        <a class="page-link"
                           href="?page=user_management&page_num=<?php echo $i;
                           if (!empty($search_term)) echo '&search=' . htmlspecialchars($search_term); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link"
                       href="?page=user_management&page_num=<?php echo $current_page + 1;
                       if (!empty($search_term)) echo '&search=' . htmlspecialchars($search_term); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</div>

<script>
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const modalBody = editUserModal.querySelector('#editUserModalBody');

        $.get('edit_user.php?ajax_edit=1&id=' + userId, function(data) {
            modalBody.innerHTML = data;
        }).fail(function() {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading user data.</div>';
        });
    });

    $(document).on('click', '#saveUserChanges', function() {
        const userId = $('#editUserModalBody input[name="user_id"]').val();
        const formData = $('#editUserModalBody form').serialize();

        $.post('edit_user.php?ajax_save=1&id=' + userId, formData, function(response) {
            if (response === "success") {
                $('#editUserModal').modal('hide');
                location.reload();
            } else {
                alert(response);
            }
        }).fail(function() {
            alert('Error saving changes.');
        });
    });
</script>
