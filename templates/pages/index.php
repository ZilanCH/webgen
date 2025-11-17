<section class="card">
    <div class="card-header">
        <div>
            <h1>Meine Seiten</h1>
            <p>Verwalte deine eigenen Seiten. Admins sehen alle Seiten.</p>
        </div>
        <a class="btn" href="?route=page_new">Neue Seite</a>
    </div>
    <?php if (empty($pages)): ?>
        <p>Keine Seiten vorhanden.</p>
    <?php else: ?>
        <div class="table">
            <div class="table-row table-head">
                <div>ID</div>
                <div>Titel</div>
                <div>Besitzer</div>
                <div>Aktionen</div>
            </div>
            <?php foreach ($pages as $p): ?>
                <div class="table-row">
                    <div><?php echo (int) $p['id']; ?></div>
                    <div><?php echo sanitize($p['title']); ?></div>
                    <div><?php echo sanitize($data['users'][$p['owner_id']]['email'] ?? 'Unbekannt'); ?></div>
                    <div class="actions">
                        <a href="?route=page_view&id=<?php echo (int) $p['id']; ?>">Ansehen</a>
                        <a href="?route=page_edit&id=<?php echo (int) $p['id']; ?>">Bearbeiten</a>
                        <form method="post" action="?route=page_delete&id=<?php echo (int) $p['id']; ?>" onsubmit="return confirm('Seite löschen?');">
                            <button type="submit" class="link-button">Löschen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
