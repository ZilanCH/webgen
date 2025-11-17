<section class="card">
    <div class="card-header">
        <div>
            <h1><?php echo sanitize($page['title']); ?></h1>
            <?php if ($owner): ?>
                <p>Erstellt von <?php echo sanitize($owner['email']); ?></p>
            <?php endif; ?>
        </div>
        <form method="post" action="?route=admin_page_delete&id=<?php echo (int) $page['id']; ?>" onsubmit="return confirm('Seite löschen?');">
            <button type="submit" class="btn btn-danger">Löschen</button>
        </form>
    </div>
    <div class="page-body">
        <?php echo nl2br(sanitize($page['content'])); ?>
    </div>
</section>
