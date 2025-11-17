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
