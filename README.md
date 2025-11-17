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
