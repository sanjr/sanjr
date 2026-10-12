# ITIExam.com – Website + Admin Panel

A responsive, SEO-friendly website for ITI exam preparation, with an **admin panel** where you can add trades, pages and menu links without touching code.

Built with **PHP 8 + SQLite**, so you don't need to set up a database. MySQL also works.
It runs on any normal shared hosting (Hostinger, GoDaddy, BigRock, cPanel).

## What the admin can do (`/admin/`)
| Menu | What it does |
|---|---|
| **Homepage** | Edit the hero text and photo, the handwritten quote, the bottom banner, the FAQ and the SEO text; switch each homepage section on or off. |
| **Media Library** | Upload many photos/PDFs at once, copy their link, see where each file is used, and delete. |
| **Visual editor** | Pages, articles, trade “About” text, job details and homepage text use a toolbar editor (headings, bold, lists, links, photos from the Media Library, PDF links, tables, quotes) – no HTML needed. An **HTML** button shows the code. |
| **Trades** | Add, edit or delete trades (name, full name, description, duration, NSQF level, sector, exam pattern, image or emoji, card colour, SEO title/description/keywords, “About trade” content). Every trade page uses the **same design** automatically. |
| **Trades → Content** | Each trade has its **own** content, split into sections: Quick Links, Feature Tiles, Mock Tests, Syllabus & Modules, Study Material, Practical, Previous Year Papers, Important Questions, Videos, Resources, FAQ. Each item can link to a page, another website, a `#section` or an **uploaded PDF/image**. A section with no items is hidden on the page. You can **copy a section from another trade** as a starting point. |
| **Jobs** | Add, edit, copy or delete jobs: company, job type (Government, Private, Campus, Walk-in, Apprentice, Contract, Fresher), trades, qualification, experience, location, vacancies, salary, age limit, last date, apply link, notification PDF, full details, Featured/New badges and SEO. After the last date, a job is shown as **Closed** and drops out of the listings automatically. |
| **Employers** | Companies with logo (or coloured short-name badge). Featured employers appear on the Jobs page. |
| **Pages & Articles** | Create any page at any URL (e.g. `/about/` or `/blog/my-post/`). Set its type to **Exam Update** or **Article** and it appears on the homepage automatically. Shortcodes: `[trades]`, `[trades section="mock-tests"]`, `[jobs type="apprentice" limit="6"]`, `[jobs trade="COPA"]`, `[posts category="article"]`. |
| **Menus & Links** | Edit the header menu (with dropdowns), footer links and the homepage lists (hero quick links, Jobs & Career, Apprenticeship, Study Material tiles – each with small text and icon). Pick any trade or page from a list, or type any URL. |
| **Ads (AdSense)** | Paste your AdSense Publisher ID and switch ads on. Works with **Auto ads**, and you can also add fixed ad units in 9 places (homepage, trade pages, jobs list, job details, articles, sidebar, above footer). `/ads.txt` is created automatically. Ads are hidden while you are logged in as admin. |
| **Link Checker** | Lists every menu, trade, page or job link that opens “Page not found”, and the missing addresses visitors reached (404 log), with Fix / Create page buttons. |
| **Settings** | **WhatsApp group link** (join box on every page + floating button), contact email, **logo & favicon upload**, Google Search Console / Bing verification, Google Analytics 4 ID, homepage title/description, homepage numbers, footer text, social links, change password. |

New trades and pages are added to `/sitemap.xml` automatically. Each trade page also shows the latest jobs for that trade.

**Before launch:** setup adds 6 **sample jobs** and starter pages so you can see the design. The dashboard reminds you about sample jobs until you edit or delete them. Never leave fake job listings live.

## Updating an existing website
Upload the new files (keep the `data` and `uploads` folders). On the first page load the site upgrades itself: missing starter pages are created, old menu links are corrected, and the starter About/Contact pages are refreshed if you never edited them. Then open **Admin → Link Checker** to confirm nothing is broken.

## Google AdSense setup
1. Admin → **Ads (AdSense)** → paste your Publisher ID (`ca-pub-…`) → tick **Show ads on the website** → Save.
2. In AdSense → **Sites**, add your domain. `https://your-domain/ads.txt` is already served for you.
3. Choose one or both:
   - **Auto ads:** in AdSense → Ads → By site → switch on Auto ads. Google places ads automatically.
   - **Fixed ad units:** in AdSense → Ads → By ad unit → create a *Display* (responsive) unit, copy the number from `data-ad-slot="…"` and paste it into the place you want in Admin → Ads.
4. Check ads in a **private/incognito window**. They are hidden for the logged-in admin, so you never click your own ads (that breaks AdSense rules).
5. The starter **Privacy Policy** already explains Google advertising cookies, which AdSense requires.
6. If you get visitors from Europe/UK, turn on Google's consent message in AdSense → Privacy & messaging.

## Website URLs
- `/` – homepage (Popular Trades come from the admin)
- `/trades/` – list of all trades
- `/trades/{slug}/` – trade page (e.g. `/trades/copa/`)
- `/jobs/` – all jobs with filters (type, trade, location, qualification, experience, salary), sorting and pagination
- `/jobs/government/`, `/jobs/private/`, `/jobs/campus/`, `/jobs/walk-in/`, `/jobs/apprentice/` … – job categories
- `/jobs/{slug}/` – job details page with Google for Jobs data (JobPosting)
- `/search/?q=` – search across trades, jobs and pages
- `/{any-page}/` – pages and articles created by the admin
- `/sitemap.xml` – sitemap that updates itself
- `/ads.txt` – AdSense ads.txt, built from Admin → Ads

## Upload to hosting
1. Upload everything inside the `itiexam/` folder to `public_html` (or your domain's root folder).
2. Make sure the `data/` and `uploads/` folders are writable (permission 755 or 775).
3. Optional: in `includes/config.php` set `$siteUrl` to your exact address (e.g. `https://blog.copamcq.com`). If left empty, the site uses the address it is opened on. Subdomains work; `www.` is never added.
4. Open `https://your-domain/admin/`. The first time, it asks you to create your admin username and password. After that, setup is locked.
5. Log in and start adding trades.

To use MySQL instead of SQLite: create a database in your hosting panel, then in `includes/config.php`
set `DB_DRIVER` to `'mysql'` and fill in `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS`. The tables are created automatically.

If your domain or subdomain doesn't have SSL yet, remove the 3 “Force HTTPS” lines from `.htaccess`.

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
