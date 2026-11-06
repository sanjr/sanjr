# COPA MCQ Website

An online MCQ practice and CBT mock-test website for the ITI **COPA** (Computer Operator & Programming Assistant) trade, built with plain **HTML, CSS and JavaScript**. It has no build step and no server code.

## Features
- **355 MCQs from 15 modules**, taken from the COPA question bank PDF (DGT – CTS, NSQF Level 3.5). Each question has a one-line explanation of the correct answer in all three languages (`tools/explain/`, merged into `data/questions.js` by `tools/explain/merge.py`).
- **3 languages**: English, हिंदी, मराठी. You can switch language anywhere, even during an exam.
- **A separate page for each module** (`modules/module-01.html` … `module-15.html`), with:
  - Practice mode: instant right/wrong feedback, a question palette, shuffle and saved progress
  - Study (Q&A) mode: answers shown, with search and print
  - A module test in CBT style
- **CBT mock test** (`exam.html`) that works like the real ITI online exam: instructions page, countdown timer with auto-submit, question palette (Not Visited / Not Answered / Answered / Marked for Review / Answered & Marked), Save & Next, Mark for Review & Next, Clear Response, and a submit summary. An unfinished test can be resumed.
- **Student login and dashboard** (`dashboard.html`): each student registers with a Roll No. and a 4-digit PIN, so several students can use one lab computer. Each student's tests and practice are saved under their own account. The dashboard shows profile details, stats (tests, passed, failed, pass rate, average, best), a score-trend chart, module-wise performance and the full test history with Pass/Fail. A student must log in before starting a test.
- **Results**: score, percentage, Pass/Fail (**pass mark 40%**), module-wise analysis and an answer review.
- **Study Material** page listing every question with its answer, with search and print/PDF.
- All data is stored in the browser (`localStorage`) on that computer. There is no server, so a teacher cannot see every student's results in one place.
- Fully responsive (mobile, tablet and desktop). Icons and fonts (Poppins for headings, Roboto for text, Noto Sans Devanagari for Hindi/Marathi) are bundled, so the site also works offline.
- Each module has its own image (`assets/img/modules/m01.jpg` … `m15.jpg`, 960×540). They are generated from `tools/module-images.html`. You can replace any of them with your own photo using the same file name.

## Live Practice (`live-practice.html`)
- An online code editor (CodeMirror) with a **live output** panel beside it.
  - HTML / CSS / JavaScript: the preview updates while you type, and a console shows `console.log()` output and errors.
  - Python: runs in the browser using Pyodide (bundled in `assets/vendor/pyodide`, about 14 MB, loaded only when Python is first used). `input()` reads from the Input box, or shows a pop-up if the box is empty.
  - When the site is opened from a web server or hosting, the bundled copy is used and works offline.
  - When `index.html` is opened directly from a folder (`file://`), browsers block the bundled engine, so Python is loaded from the internet (jsDelivr CDN) instead. Internet is needed in that case.
- 24 ready COPA practicals: 5 HTML, 2 CSS, 5 JavaScript and 12 Python (`js/practicals.js`).
- Logged-in students can save a program with a title. Saved programs, with their output, appear in **Dashboard → My Live Practicals**.
- Shortcuts: Ctrl + Enter = Run, Ctrl + S = Save. You can also download a program as `.html` / `.py`.

## Admin Panel (`admin.html`)
Open `admin.html` in the browser. The link is not shown in the site menu, so only you know it.
- **Live Dashboard:** students taking an exam right now (name, roll no., test, progress, time left), registered students, exams taken, exams today, pass/fail, pass rate, average score, exams per module, latest results.
- **Exam Results:** every submitted test with filters (module, module/mock test, pass/fail, date, search), CSV export and delete.
- **Students:** all registered students with number of tests, passes, average and best score, and CSV export.
- **Questions:** add new MCQs (EN required, HI/MR optional, answer, one-line explanation, optional code). You can also edit, hide or restore built-in questions, and import/export JSON.
- **Study Material:** add notes and links (PDF / Google Drive / YouTube) per module. They appear on the Study Material page and in each module's Study tab.
- **Ads & Settings:** Google AdSense on/off, publisher ID, Auto ads, manual ad-unit slot IDs, an AdSense approval checklist, and downloads for `site-config.js`, `ads.txt` and `sitemap.xml`.

### Local mode vs online mode
- **Local mode (default):** no setup needed. The first visit asks you to create an admin password. The admin panel only sees data from **that computer**.
- **Online mode (recommended):** create a free Firebase project and put its config in `js/site-config.js` (the steps are written at the top of that file). Paste `firestore.rules` into Firestore → Rules and put your admin e-mail in it. After that, the admin panel shows every student on every device live, and questions, study material and ad settings are saved online for all visitors.

## Google AdSense
1. Host the site on your own domain (e.g. `copamcq.in`) and apply at https://adsense.google.com.
2. In Admin → Ads & Settings, enter your `ca-pub-…` ID and switch on "Show ads".
3. Download `ads.txt` and upload it to the root folder of the site. Do the same for `sitemap.xml`.
4. In local mode, also download `site-config.js` and replace `js/site-config.js` on the hosting.
5. Ads show on Home, Modules, module pages, Study Material, About and Dashboard. They are never shown inside the CBT exam, the admin panel or the Privacy/Terms pages.

## Run
Open `index.html` in a browser, or serve the folder:
```bash
python3 -m http.server 8000
```
It can be hosted on GitHub Pages, Netlify or any static host.

## Structure
```
index.html  modules.html  exam.html  study.html  dashboard.html  about.html  privacy.html  terms.html  admin.html
(results.html redirects to dashboard)  robots.txt  firestore.rules
modules/module-01..15.html      css/style.css
js/app.js (i18n, layout, accounts, storage)  js/db.js (local/Firebase data layer)  js/ads.js (AdSense)
js/site-config.js (Firebase + AdSense config)  js/admin.js  js/dashboard.js  js/home.js  js/module.js  js/exam.js  js/result.js
data/questions.js (all questions, EN/HI/MR)   assets/img, assets/vendor/fontawesome
```

## Notes on the question data
- Module 14 (Cloud Computing) appears twice in the PDF. The duplicate was removed, so Module 14 has 20 unique questions.
- The answer key was corrected for 3 questions where the PDF answer is technically wrong: 4-7 (`/etc/passwd`), 11-10 (`===` with `new Number` gives "No"), and 15-45 (the condition is True, so it prints "matched").
- Hindi and Marathi translations were prepared from the English text. Technical terms (commands, tags, shortcuts) are kept in English.

---
© 2019 - 2026 COPA MCQ. All rights reserved. Developed & Designed by : Sanjay Rathod (CI - COPA)
