# ITIExam.com – Website + Admin Panel

A responsive, SEO-friendly website for ITI exam preparation, with an **admin panel** where you can add trades, pages and menu links without touching code.

Built with **PHP 8 + SQLite**, so you don't need to set up a database. MySQL also works.
It runs on any normal shared hosting (Hostinger, GoDaddy, BigRock, cPanel).

## What the admin can do (`/admin/`)
| Menu | What it does |
|---|---|
| **Trades** | Add, edit or delete trades (name, full name, description, duration, NSQF level, sector, exam pattern, image or emoji, card colour, SEO title/description/keywords, “About trade” content). Every trade page uses the **same design** automatically. |
| **Trades → Content** | Each trade has its **own** content, split into sections: Quick Links, Feature Tiles, Mock Tests, Syllabus & Modules, Study Material, Practical, Previous Year Papers, Important Questions, Videos, Resources, FAQ. Each item can link to a page, another website, a `#section` or an **uploaded PDF/image**. A section with no items is hidden on the page. You can **copy a section from another trade** as a starting point. |
| **Pages** | Create any new page at any URL, e.g. `/about/` or `/jobs/government/`, with your own HTML content and SEO fields. |
| **Menus & Links** | Edit the header menu (with dropdowns) and footer links. Pick any trade or page from a list, or type any URL. |
| **Settings** | Homepage title and description for Google, footer text, social media links, change password. |

New trades and pages are added to `/sitemap.xml` automatically.

## Website URLs
- `/` – homepage (Popular Trades come from the admin)
- `/trades/` – list of all trades
- `/trades/{slug}/` – trade page (e.g. `/trades/copa/`)
- `/{any-page}/` – pages created by the admin
- `/sitemap.xml` – sitemap that updates itself

## Upload to hosting
1. Upload everything inside the `itiexam/` folder to `public_html` (or your domain's root folder).
2. Make sure the `data/` and `uploads/` folders are writable (permission 755 or 775).
3. Open `includes/config.php` and set `SITE_URL` to your domain (e.g. `https://www.itiexam.com`).
4. Open `https://your-domain/admin/`. The first time, it asks you to create your admin username and password. After that, setup is locked.
5. Log in and start adding trades.

To use MySQL instead of SQLite: create a database in your hosting panel, then in `includes/config.php`
set `DB_DRIVER` to `'mysql'` and fill in `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS`. The tables are created automatically.

If your domain doesn't have SSL yet, remove the 4 “Force HTTPS” lines from `.htaccess`.

## Try it on your computer
```bash
cd itiexam
php -S localhost:8000 router.php
```
Then open http://localhost:8000/ and http://localhost:8000/admin/

## Security
- Passwords are stored as hashes (`password_hash`), and the session ID is changed after login.
- Every form has a CSRF token. After 5 wrong logins, login is locked for 5 minutes.
- Uploads allow only JPG/PNG/WEBP/GIF/PDF (the real file type is checked, not just the extension), and scripts cannot run from `uploads/`.
- The `data/` (database) and `includes/` folders are blocked from the browser.
- `/admin/` is hidden from Google (`noindex` and `robots.txt`).
- The HTML content fields (trade “About” and Pages) are shown exactly as the admin types them, so give admin access only to people you trust.

## To rank on Google
1. Add the site to **Google Search Console** and submit `https://your-domain/sitemap.xml`.
2. For every trade, write unique content in **About this trade** (300+ words), plus a unique SEO title and description.
3. Add new pages regularly (exam dates, results, papers, jobs) and link to them from menus and trades.
4. Link your YouTube, Telegram and WhatsApp channels in **Settings**, and get backlinks from education sites.
5. Check speed with **PageSpeed Insights**.

> No one can guarantee a #1 Google rank. It depends on content quality, competition, backlinks and time. This site gives you a strong technical SEO base to compete with.
