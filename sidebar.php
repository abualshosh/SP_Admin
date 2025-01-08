<div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark" style="width: 280px; height: 100vh; overflow-y: auto;">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4">SP Admin</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?page=user_management" class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'user_management') echo 'active'; ?>" aria-current="page">
                User Management
            </a>
        </li>
        <li>
            <a href="index.php?page=list_users" class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'list_users') echo 'active'; ?>">
                List Users
            </a>
        </li>
        <li>
            <a href="index.php?page=create_user" class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'create_user') echo 'active'; ?>">
                Create User
            </a>
        </li>
        <li>
            <a href="index.php?page=edit_user" class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'edit_user') echo 'active'; ?>">
                Edit User
            </a>
        </li>
        <li>
            <a href="index.php?page=list_wh" class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'list_wh') echo 'active'; ?>">
                List WH
            </a>
        </li>
    </ul>
    <hr>
    <a href="logout.php" class="btn btn-danger w-100 mt-3">Logout</a>
</div>