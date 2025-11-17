<section class="card grid">
    <div class="stat">
        <p class="muted">Seiten</p>
        <h2><?php echo count($data['pages']); ?></h2>
        <a href="?route=admin_pages" class="link">Alle Seiten ansehen</a>
    </div>
    <div class="stat">
        <p class="muted">Benutzer</p>
        <h2><?php echo count($data['users']); ?></h2>
        <a href="?route=admin_users" class="link">Benutzer verwalten</a>
    </div>
    <div class="stat">
        <p class="muted">Footer</p>
        <h2>Links: <?php echo count($data['footer_links']); ?></h2>
        <a href="?route=admin_footer" class="link">Footer bearbeiten</a>
    </div>
</section>
