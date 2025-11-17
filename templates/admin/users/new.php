<section class="card">
    <h1>Neuen Benutzer anlegen</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?php echo sanitize($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <label>Name
            <input type="text" name="name" value="<?php echo sanitize($values['name'] ?? ''); ?>" required />
        </label>
        <label>E-Mail
            <input type="email" name="email" value="<?php echo sanitize($values['email'] ?? ''); ?>" required />
        </label>
        <label>Rolle
            <select name="role">
                <option value="user" <?php echo (($values['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo (($values['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </label>
        <label>Passwort
            <input type="text" name="password" value="<?php echo sanitize($values['password'] ?? ''); ?>" required />
        </label>
        <button type="submit" class="btn">Speichern</button>
    </form>
</section>
