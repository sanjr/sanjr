# ITI CareerTrack — HTML + CSS + JavaScript preview

**Open `index.html` by double-clicking it.** No PHP, no MySQL and no internet is needed.

| File / folder | What it is |
|---|---|
| `index.html` | List of all 90 pages, grouped by role |
| `login.html` | Login page |
| `state/`, `district/`, `principal/`, `teacher/`, `placement/`, `staff/`, `employer/`, `student/` | The pages each role sees (`dashboard.html`, `students.html`, `transfers.html`, …) |
| `css/app.css` | All styling |
| `js/app.js` | Charts, admission wizard, menus |
| `js/static-demo.js` | Stops forms from submitting in this preview |

Links, sidebar menus, tabs, charts and the 5-step admission form all work.
**Saving data** (admission, transfer, attendance…) needs the real website in `../app/` (PHP + MySQL) — see `../README.md`.

All data is synthetic DEMO data. To regenerate this folder after changing the website:

```bash
python3 python/tools/export_static_html.py --out html-demo
```
