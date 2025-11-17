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
