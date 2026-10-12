<?php
/** @var array $users */
/** @var array $user */
?>
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p class="muted">Admins create accounts. Disabling a user keeps reservation history. At least one active admin is required.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/users')) ?>" class="panel form-grid">
    <h2>Add user</h2>
    <?= $csrfField ?>
    <label>
        Username
        <input type="text" name="username" required pattern="[A-Za-z0-9._-]{3,64}" placeholder="jsmith">
    </label>
    <label>
        Email
        <input type="email" name="email" required>
    </label>
    <label>
        Initial password
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </label>
    <label>
        Role
        <select name="role">
            <option value="standard">Standard (reserve)</option>
            <option value="view">View only</option>
            <option value="admin">Admin</option>
        </select>
    </label>
    <button type="submit" class="btn primary">Create user</button>
</form>

<div class="stack">
    <?php foreach ($users as $row): ?>
        <form method="post" action="<?= e(url('/admin/users/update')) ?>" class="panel form-grid user-row">
            <?= $csrfField ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <p class="row-title"><strong><?= e($row['username']) ?></strong></p>
            <label>
                Email
                <input type="email" name="email" value="<?= e($row['email']) ?>" required>
            </label>
            <label>
                Role
                <select name="role">
                    <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="standard" <?= $row['role'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                    <option value="view" <?= $row['role'] === 'view' ? 'selected' : '' ?>>View only</option>
                </select>
            </label>
            <label class="check">
                <input type="checkbox" name="active" value="1" <?= (int) $row['active'] === 1 ? 'checked' : '' ?>>
                Active
            </label>
            <label>
                Reset password
                <input type="password" name="password" minlength="8" placeholder="Leave blank to keep" autocomplete="new-password">
            </label>
            <button type="submit" class="btn small">Save</button>
        </form>
    <?php endforeach; ?>
</div>
