# webgen

PHP-basierte Admin-Oberfläche zum Verwalten generierter Seiten, Benutzer und Footer-Inhalte auf Dateibasis.

## Start

1. Stelle sicher, dass PHP 8+ installiert ist.
2. Starte den eingebauten PHP-Server im Projektverzeichnis:
   ```bash
   php -S localhost:5000 index.php
   ```
3. Melde dich mit dem vorgegebenen Admin-Account an:
   - **E-Mail:** `admin@example.com`
   - **Passwort:** `admin123`

Alle Daten (Benutzer, Seiten, Footer-Text, Footer-Links) werden in `webgen.json` gespeichert. Es ist keine Datenbank oder SQL erforderlich.

## Features

- Anmeldung mit Sessions und rollenbasiertem Zugriff (User/Admin).
- Admin-Dashboard für Seiten-Moderation, Benutzerverwaltung (Anlegen, Bearbeiten, Löschen, Passwort-Reset) und Rollenvergabe.
- Seitenbesitzer können ihre eigenen Seiten erstellen, bearbeiten und löschen; Admins sehen und verwalten alles.
- Footer-Management: editierbarer Text sowie Links (z. B. Legal/Privacy) mit sortierbarer Position.
- Standard-Footer, wenn kein eigener Text gesetzt ist: `©️{Seitenname} 2025 - All rights reserved!`.

## Standarddaten zurücksetzen

Lösche die Datei `webgen.json`, um mit den Standardwerten neu zu starten.

## Neuen Pull Request erstellen

So erstellst du eine neue Pull-Request, wenn du weitere Änderungen einreichen möchtest:

1. Erstelle einen neuen Branch auf Basis von `main` oder dem gewünschten Stand, zum Beispiel:
   ```bash
   git checkout -b feature/neues-feature origin/main
   ```
2. Nimm deine Änderungen vor und committe sie:
   ```bash
   git status
   git add <geänderte_dateien>
   git commit -m "Beschreibe die Änderung"
   ```
3. Schiebe den Branch zu deinem Remote:
   ```bash
   git push -u origin feature/neues-feature
   ```
4. Öffne anschließend auf GitHub/GitLab/Bitbucket ein neues Pull-Request vom neuen Branch gegen `main`.

Falls ein älterer PR verworfen wurde und du denselben Stand erneut einreichen willst, stelle sicher, dass dein Branch exakt diesen Commit enthält (z. B. per `git reset --hard <commit-hash>`), bevor du die obigen Schritte ausführst.
