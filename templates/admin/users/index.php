<section class="card">
    <div class="card-header">
        <h1>Benutzer</h1>
        <a class="btn" href="?route=admin_user_new">Neuer Benutzer</a>
    </div>
    <?php if (empty($users)): ?>
        <p>Keine Benutzer vorhanden.</p>
    <?php else: ?>
        <div class="table">
            <div class="table-row table-head">
                <div>ID</div>
                <div>Name</div>
                <div>E-Mail</div>
                <div>Rolle</div>
                <div>Aktionen</div>
            </div>
            <?php foreach ($users as $u): ?>
                <div class="table-row">
                    <div><?php echo (int) $u['id']; ?></div>
                    <div><?php echo sanitize($u['name']); ?></div>
                    <div><?php echo sanitize($u['email']); ?></div>
                    <div><?php echo sanitize($u['role']); ?></div>
                    <div class="actions">
                        <a href="?route=admin_user_edit&id=<?php echo (int) $u['id']; ?>">Bearbeiten</a>
                        <form method="post" action="?route=admin_user_reset&id=<?php echo (int) $u['id']; ?>" onsubmit="return confirm('Passwort zurücksetzen?');">
                            <button type="submit" class="link-button">Passwort zurücksetzen</button>
                        </form>
                        <form method="post" action="?route=admin_user_delete&id=<?php echo (int) $u['id']; ?>" onsubmit="return confirm('Benutzer löschen?');">
                            <button type="submit" class="link-button">Löschen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
