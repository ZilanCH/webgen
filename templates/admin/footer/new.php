<section class="card">
    <h1>Footer-Link hinzufügen</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?php echo sanitize($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <label>Label
            <input type="text" name="label" value="<?php echo sanitize($values['label'] ?? ''); ?>" required />
        </label>
        <label>URL
            <input type="url" name="url" value="<?php echo sanitize($values['url'] ?? ''); ?>" required />
        </label>
        <label>Position
            <input type="number" name="position" value="<?php echo sanitize($values['position'] ?? ''); ?>" min="1" />
        </label>
        <button type="submit" class="btn">Speichern</button>
    </form>
</section>
