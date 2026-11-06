# MHNJE ITI – COPA MCQ Website

An online MCQ practice and CBT mock-test website for the ITI **COPA** (Computer Operator & Programming Assistant) trade, built with plain **HTML, CSS and JavaScript**. It has no build step and no server code.

## Features
- **355 MCQs from 15 modules**, taken from the *COPA Question Bank (P. R. Gaikwad)* PDF.
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

## Run
Open `index.html` in a browser, or serve the folder:
```bash
python3 -m http.server 8000
```
It can be hosted on GitHub Pages, Netlify or any static host.

## Structure
```
index.html  modules.html  exam.html  study.html  dashboard.html  about.html  (results.html redirects to dashboard)
modules/module-01..15.html      css/style.css
js/app.js (i18n, layout, accounts, storage)  js/dashboard.js  js/home.js  js/module.js  js/exam.js  js/result.js
data/questions.js (all questions, EN/HI/MR)   assets/img, assets/vendor/fontawesome
```

## Notes on the question data
- Module 14 (Cloud Computing) appears twice in the PDF. The duplicate was removed, so Module 14 has 20 unique questions.
- The answer key was corrected for 3 questions where the PDF answer is technically wrong: 4-7 (`/etc/passwd`), 11-10 (`===` with `new Number` gives "No"), and 15-45 (the condition is True, so it prints "matched").
- Hindi and Marathi translations were prepared from the English text. Technical terms (commands, tags, shortcuts) are kept in English.
