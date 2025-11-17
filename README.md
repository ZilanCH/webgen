# webgen

Flask-based admin interface for managing generated pages and user accounts.

## Getting started

1. Install dependencies (network access required):
   ```bash
   pip install -r requirements.txt
   ```
2. Start the server:
   ```bash
   python app.py
   ```
3. Sign in with the seeded admin account:
   - **Email:** `admin@example.com`
   - **Password:** `admin123`

## Features

- User authentication with session support.
- Admin dashboard to manage pages and users.
- Page ownership controls so creators can edit/delete their own pages while admins can moderate everything.
- User management for creating, editing, deleting, password resets, and role assignment (User/Admin).
- Editable footer with support for custom text and ordered links (e.g., Legal or Privacy URLs).

Data is stored in a local JSON file (`webgen.json`) so you can run the project without any SQL database setup.
