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
- **Results**: score, percentage, pass/fail (33%), module-wise analysis and an answer review. History is kept on the Results page.
- **Study Material** page listing every question with its answer, with search and print/PDF.
- A local student login (name and roll number). All data stays in the browser (`localStorage`).
- Fully responsive (mobile, tablet and desktop). Icons are bundled, so the site also works offline.

## Run
Open `index.html` in a browser, or serve the folder:
```bash
python3 -m http.server 8000
```
It can be hosted on GitHub Pages, Netlify or any static host.

## Structure
```
index.html  modules.html  exam.html  study.html  results.html  about.html
modules/module-01..15.html      css/style.css
js/app.js (i18n, layout, storage)  js/home.js  js/module.js  js/exam.js  js/result.js
data/questions.js (all questions, EN/HI/MR)   assets/img, assets/vendor/fontawesome
```

## Notes on the question data
- Module 14 (Cloud Computing) appears twice in the PDF. The duplicate was removed, so Module 14 has 20 unique questions.
- The answer key was corrected for 3 questions where the PDF answer is technically wrong: 4-7 (`/etc/passwd`), 11-10 (`===` with `new Number` gives "No"), and 15-45 (the condition is True, so it prints "matched").
- Hindi and Marathi translations were prepared from the English text. Technical terms (commands, tags, shortcuts) are kept in English.
