<section class="card">
    <div class="card-header">
        <div>
            <h1><?php echo sanitize($page['title']); ?></h1>
            <?php if ($owner): ?>
                <p>Erstellt von <?php echo sanitize($owner['email']); ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($user) && ($user['role'] === 'admin' || $user['id'] === $page['owner_id'])): ?>
            <div class="actions">
                <a href="?route=page_edit&id=<?php echo (int) $page['id']; ?>">Bearbeiten</a>
                <form method="post" action="?route=page_delete&id=<?php echo (int) $page['id']; ?>" onsubmit="return confirm('Seite löschen?');">
                    <button type="submit" class="link-button">Löschen</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <div class="page-body">
        <?php echo nl2br(sanitize($page['content'])); ?>
    </div>
</section>
