<div class="page-head">
    <div>
        <h1>Change password</h1>
        <p class="muted">Use 12–128 characters with uppercase, lowercase, and a number.</p>
    </div>
</div>
<form method="post" action="<?= e(url('/password')) ?>" class="panel stack">
    <?= $csrfField ?>
    <label>
        Current password
        <input type="password" name="current" required autocomplete="current-password">
    </label>
    <label>
        New password
        <input type="password" name="next" required minlength="12" maxlength="128" autocomplete="new-password">
    </label>
    <label>
        Confirm new password
        <input type="password" name="confirm" required minlength="12" maxlength="128" autocomplete="new-password">
    </label>
    <button type="submit" class="btn primary">Update password</button>
</form>
