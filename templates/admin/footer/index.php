<section class="card">
    <div class="card-header">
        <h1>Footer verwalten</h1>
        <a class="btn" href="?route=admin_footer_new">Neuer Link</a>
    </div>
    <form method="post" action="?route=admin_footer_text" class="form-grid">
        <label>Footer Text (leer lassen für Standard)
            <textarea name="text" rows="3" placeholder="©️{Seitenname} 2025 - All rights reserved!"><?php echo sanitize($footerText ?? ''); ?></textarea>
        </label>
        <button type="submit" class="btn">Text speichern</button>
    </form>
</section>

<section class="card">
    <h2>Links</h2>
    <?php if (empty($links)): ?>
        <p>Keine Links vorhanden.</p>
    <?php else: ?>
        <div class="table">
            <div class="table-row table-head">
                <div>Position</div>
                <div>Label</div>
                <div>URL</div>
                <div>Aktionen</div>
            </div>
            <?php foreach ($links as $link): ?>
                <div class="table-row">
                    <div><?php echo (int) $link['position']; ?></div>
                    <div><?php echo sanitize($link['label']); ?></div>
                    <div><?php echo sanitize($link['url']); ?></div>
                    <div class="actions">
                        <a href="?route=admin_footer_edit&id=<?php echo (int) $link['id']; ?>">Bearbeiten</a>
                        <form method="post" action="?route=admin_footer_delete&id=<?php echo (int) $link['id']; ?>" onsubmit="return confirm('Link löschen?');">
                            <button type="submit" class="link-button">Löschen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
