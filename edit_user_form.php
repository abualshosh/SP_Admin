<form id="editUserForm">
    <input type="hidden" name="user_id" value="<?= htmlentities($user['ID'] ?? ''); ?>">

    <div class="mb-3">
        <label for="job_title" class="form-label">Job Title:</label>
        <input type="text" class="form-control" id="job_title" name="job_title" value="<?= htmlentities($user['JOB_TITLE'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label">Role:</label>
        <input type="text" class="form-control" id="role" name="role" value="<?= htmlentities($user['ROLE'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label for="telnumber" class="form-label">Telephone Number:</label>
        <input type="tel" class="form-control" id="telnumber" name="telnumber" value="<?= htmlentities($user['TELENUMBER'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label for="groupid" class="form-label">Group ID:</label>
        <select class="form-control" id="groupid" name="groupid" required>
            <?php foreach ($groups as $group): ?>
                <option value="<?= htmlentities($group['GROUPID'] ?? ''); ?>" <?= ($user['GROUPID'] ?? '') == ($group['GROUPID'] ?? '') ? 'selected' : ''; ?>>
                    <?= htmlentities($group['GROUPID'] ?? '') . " - " . htmlentities($group['JOB_DESC'] ?? ''); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="status" class="form-label">Status:</label>
        <select class="form-control" id="status" name="status">
            <option value="1" <?= ($user['STATUS'] ?? '') == 1 ? 'selected' : ''; ?>>Active</option>
            <option value="0" <?= ($user['STATUS'] ?? '') == 0 ? 'selected' : ''; ?>>Terminated</option>
        </select>
    </div>

    <!-- <button type="submit" class="btn btn-primary">Save Changes</button>  -->
    <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
</form>

<script>
    const form = document.getElementById('editUserForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(form);
        const userId = new URLSearchParams(window.location.search).get('id');

        fetch(`edit_user.php?ajax_save=1&id=${userId}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Handle success (e.g., close modal, display message)
                alert("User updated successfully!");
                $('#editUserModal').modal('hide'); // Example using jQuery to close a modal
                location.reload();//refresh the page
            } else if (data.status === 'error') {
                // Handle error (e.g., display error message)
                alert("Error updating user: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred during")
                console.error('Error:', error);
            alert("An error occurred during the update.");
        });
    });
</script>