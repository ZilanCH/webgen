<section class="card">
    <h1>Seite bearbeiten</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?php echo sanitize($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="form-grid">
        <label>Titel
            <input type="text" name="title" value="<?php echo sanitize($values['title'] ?? ''); ?>" required />
        </label>
        <label>Inhalt
            <textarea name="content" rows="8" required><?php echo sanitize($values['content'] ?? ''); ?></textarea>
        </label>
        <button type="submit" class="btn">Aktualisieren</button>
    </form>
</section>
