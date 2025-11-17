<section class="card auth-card">
    <h1>Login</h1>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo sanitize($error); ?></div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <label>E-Mail
            <input type="email" name="email" required />
        </label>
        <label>Passwort
            <input type="password" name="password" required />
        </label>
        <button type="submit" class="btn">Anmelden</button>
    </form>
</section>
