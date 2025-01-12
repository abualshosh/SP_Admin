<form id="editUserForm">
    <input type="hidden" name="user_id" value="<?php echo htmlentities($user['ID']); ?>">
    <div class="mb-3">
        <label for="job_title" class="form-label">Job Title:</label>
        <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlentities($user['JOB_TITLE']); ?>">
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Role:</label>
        <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlentities($user['ROLE']); ?>">
    </div>
    <div class="mb-3">
        <label for="telnumber" class="form-label">Telephone Number:</label>
        <input type="text" class="form-control" id="telnumber" name="telnumber" value="<?php echo htmlentities($user['TELENUMBER']); ?>">
    </div>
    <div class="mb-3">
        <label for="groupid" class="form-label">Group ID:</label>
        <select class="form-control" id="groupid" name="groupid">
            <?php foreach ($groups as $group): ?>
                <option value="<?php echo htmlentities($group['GROUPID']); ?>" <?php if ($user['GROUPID'] == $group['GROUPID']) echo 'selected'; ?>>
                    <?php echo htmlentities($group['GROUPID']) . " - " . htmlentities($group['JOB_DESC']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>