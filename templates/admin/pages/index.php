<section class="card">
    <h1>Alle Seiten</h1>
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
                        <a href="?route=admin_page_view&id=<?php echo (int) $p['id']; ?>">Details</a>
                        <form method="post" action="?route=admin_page_delete&id=<?php echo (int) $p['id']; ?>" onsubmit="return confirm('Seite löschen?');">
                            <button type="submit" class="link-button">Löschen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
