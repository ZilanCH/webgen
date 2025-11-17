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

Data is stored in a local SQLite database (`webgen.db`).
# WebGen Auth Demo

Simple PHP-based authentication without an external backend. Users are persisted to `data/user.json` with hashed passwords, roles, and owned pages.

## Running locally

1. Ensure PHP is installed.
2. From the repository root run:

   ```bash
   php -S localhost:8000
   ```

3. Open [http://localhost:8000/index.php](http://localhost:8000/index.php) to register or log in.

## Features

- Registration writes a new user record with a securely hashed password, default role of `User`, and empty owned pages list.
- Login validates credentials with `password_verify` and stores the sanitized user in the session.
- Dashboard shows the current user and their owned pages.
- Editor and admin pages require authentication; admin area additionally requires the `Admin` role.
- Logout clears the session and returns you to the homepage.

## Data format

`data/user.json` contains an array of user objects:

```json
[
  {
    "username": "alice",
    "password": "$2y$10$...", // hashed via password_hash
    "role": "User",
    "owned_pages": []
  }
]
```

Edit roles or owned pages directly in this file if you need to promote a user to Admin or assign pages.
# WebGen Builder

A single-page PHP tool to configure and generate simple websites. Fill in your content, preview instantly, and write a ready-to-serve `index.php` into a folder named after your chosen slug.

## Getting started

1. Start a PHP dev server from the repository root:
   ```bash
   php -S localhost:8000
   ```
2. Open [http://localhost:8000](http://localhost:8000) and fill out the form.
3. Use **Preview** to see the rendered page and **Generate Site** to write `./<slug>/index.php` (logo uploads are saved in `./<slug>/assets`).

Templates include portfolio, contact, imprint/privacy, product, pricing, and about layouts. Primary/secondary color pickers, logo upload or URL, favicon URL, custom buttons, and social links (email plus Discord user URL) are supported.
