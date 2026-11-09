#!/usr/bin/env python3
"""COPA MCQ – SEO for every page.

Writes, between <!-- SEO:start --> and <!-- SEO:end --> in each page's <head>:
  title, description, keywords, robots, canonical, Open Graph, Twitter card and JSON-LD.
Also writes a crawlable question list (with answers) into every module page,
and regenerates sitemap.xml and robots.txt.

Run from the project root:   python3 tools/seo/apply-seo.py
(The domain comes from SITE below – change it if the site moves.)
"""
import html, json, re, subprocess, datetime, pathlib

ROOT = pathlib.Path(__file__).resolve().parents[2]
SITE = "https://copamcq.com"
BRAND = "COPA MCQ"
OG_IMG = SITE + "/assets/img/og-cover.jpg"
LOGO = SITE + "/assets/img/icon-512.png"
TODAY = datetime.date.today().isoformat()
AUTHOR = "Sanjay Rathod (CI - COPA)"

# ---------- questions (read from data/questions.js through node) ----------
Q = json.loads(subprocess.check_output(
    ["node", "-e", "global.window={};require('./data/questions.js');process.stdout.write(JSON.stringify(window.COPA_QUESTIONS))"],
    cwd=ROOT))
TOTAL = len(Q)

MODULES = [
    (1, "Introduction to Computer System", "कंप्यूटर सिस्टम का परिचय", "संगणक प्रणालीची ओळख", "computer basics, hardware, software, input output devices, memory, CPU"),
    (2, "Windows OS", "विंडोज़ ऑपरेटिंग सिस्टम", "विंडोज ऑपरेटिंग सिस्टम", "Windows operating system, desktop, control panel, file management, shortcut keys"),
    (3, "Booting Process", "बूटिंग प्रक्रिया", "बूटिंग प्रक्रिया", "booting, BIOS, POST, cold boot, warm boot, startup"),
    (4, "DOS Interface & Linux OS", "DOS इंटरफ़ेस और लिनक्स OS", "DOS इंटरफेस आणि लिनक्स OS", "DOS commands, Linux commands, terminal, file system"),
    (5, "Word Processing Software", "वर्ड प्रोसेसिंग सॉफ्टवेयर", "वर्ड प्रोसेसिंग सॉफ्टवेअर", "MS Word, word processing, formatting, mail merge, shortcut keys"),
    (6, "Spreadsheet Application", "स्प्रेडशीट एप्लिकेशन", "स्प्रेडशीट ॲप्लिकेशन", "MS Excel, spreadsheet, formulas, functions, charts"),
    (7, "PowerPoint Presentation", "पावरपॉइंट प्रेजेंटेशन", "पॉवरपॉइंट प्रेझेंटेशन", "MS PowerPoint, slides, animation, transition, presentation"),
    (8, "Database Concepts (SQL & DBMS)", "डेटाबेस अवधारणाएँ (SQL और DBMS)", "डेटाबेस संकल्पना (SQL आणि DBMS)", "DBMS, SQL queries, MS Access, tables, keys"),
    (9, "Network Communication & Concepts", "नेटवर्क संचार और अवधारणाएँ", "नेटवर्क कम्युनिकेशन आणि संकल्पना", "computer network, LAN, WAN, topology, IP address, internet"),
    (10, "Web Design Concepts (HTML & CSS)", "वेब डिज़ाइन अवधारणाएँ (HTML और CSS)", "वेब डिझाइन संकल्पना (HTML आणि CSS)", "HTML tags, CSS, web page design, web designing"),
    (11, "JavaScript Introduction & Web Hosting", "जावास्क्रिप्ट परिचय और वेब होस्टिंग", "जावास्क्रिप्ट ओळख आणि वेब होस्टिंग", "JavaScript, web hosting, domain, functions, DOM"),
    (12, "Advance Excel & Power BI", "एडवांस एक्सेल और पावर BI", "ॲडव्हान्स एक्सेल आणि पॉवर BI", "advanced Excel, VLOOKUP, pivot table, Power BI"),
    (13, "E-Commerce", "ई-कॉमर्स", "ई-कॉमर्स", "e-commerce, online shopping, digital payment, cyber security"),
    (14, "Cloud Computing & Software Development", "क्लाउड कंप्यूटिंग और सॉफ्टवेयर विकास", "क्लाउड कॉम्प्युटिंग आणि सॉफ्टवेअर डेव्हलपमेंट", "cloud computing, SaaS, PaaS, IaaS, SDLC"),
    (15, "Introduction to Python", "पायथन का परिचय", "पायथनची ओळख", "Python programming, Python basics, data types, loops, functions"),
]
COUNT = {m[0]: sum(1 for q in Q if q["m"] == m[0]) for m in MODULES}

COMMON_KW = ("COPA MCQ, ITI COPA, COPA trade, COPA MCQ online test, COPA question bank, COPA mock test, COPA CBT exam, "
             "Computer Operator and Programming Assistant, ITI COPA question paper, COPA online test in Hindi, COPA MCQ Marathi, "
             "कोपा ट्रेड, आईटीआई कोपा प्रश्न, कोपा एमसीक्यू, कोपा प्रश्नपेढी")

ORG = {"@type": "EducationalOrganization", "@id": SITE + "/#org", "name": BRAND, "url": SITE + "/",
       "logo": {"@type": "ImageObject", "url": LOGO, "width": 512, "height": 512},
       "description": "Free online learning platform for ITI COPA (Computer Operator and Programming Assistant) trainees – MCQ practice, CBT mock tests and study material.",
       "founder": {"@type": "Person", "name": "Sanjay Rathod", "jobTitle": "Craft Instructor – COPA"}}
WEBSITE = {"@type": "WebSite", "@id": SITE + "/#website", "name": BRAND, "alternateName": ["COPAMCQ", "COPA MCQ Online Test", "copamcq.com"],
           "url": SITE + "/", "inLanguage": ["en", "hi", "mr"], "publisher": {"@id": SITE + "/#org"}}


def crumbs(*items):
    return {"@type": "BreadcrumbList", "itemListElement": [
        {"@type": "ListItem", "position": i + 1, "name": n, "item": SITE + "/" + u} for i, (n, u) in enumerate(items)]}


def webpage(url, name, desc, typ="WebPage"):
    return {"@type": typ, "@id": SITE + "/" + url + "#page", "url": SITE + "/" + url, "name": name, "description": desc,
            "isPartOf": {"@id": SITE + "/#website"}, "inLanguage": "en", "dateModified": TODAY,
            "primaryImageOfPage": {"@type": "ImageObject", "url": OG_IMG}}


# ---------- FAQ (same text is shown on the home page, js/home.js) ----------
FAQ = [
    ("What is the COPA trade in ITI?",
     "COPA (Computer Operator and Programming Assistant) is a one-year ITI trade under the Craftsmen Training Scheme. It covers computer fundamentals, Windows and Linux, MS Office, databases, networking, web design with HTML, CSS and JavaScript, e-commerce, cloud computing and Python."),
    ("How many COPA MCQ questions are available on COPA MCQ?",
     f"COPA MCQ has {TOTAL} multiple-choice questions with correct answers and a one-line explanation, divided into 15 modules, in English, Hindi and Marathi."),
    ("Is the COPA online mock test free?",
     "Yes. All COPA MCQ practice, module tests and full CBT mock tests are completely free. The mock test works like the real CBT exam with a timer, question palette, mark for review and an instant result."),
    ("What is the passing mark in the COPA MCQ mock test?",
     "COPA MCQ mock tests use a pass mark of 40%. Your dashboard keeps the full history of your results with pass or fail for every test."),
    ("Can I practise COPA practicals online?",
     "Yes. The Live Practice lab lets you write HTML, CSS, JavaScript and Python programs in the browser, see the output instantly and save your programs to your dashboard."),
    ("Can I study COPA in Hindi or Marathi?",
     "Yes. Every question, option and explanation is available in English, Hindi and Marathi. Change the language from the menu at any time."),
]
FAQ_LD = {"@type": "FAQPage", "@id": SITE + "/#faq", "mainEntity": [
    {"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in FAQ]}

# ---------- page table ----------
PAGES = {}


def page(path, title, desc, kw, ld, robots="index, follow, max-image-preview:large, max-snippet:-1", og_type="website", prio="0.8", freq="weekly"):
    PAGES[path] = dict(title=title, desc=desc, kw=kw, ld=ld, robots=robots, og_type=og_type, prio=prio, freq=freq)


url_of = lambda p: "" if p == "index.html" else p

home_t = "COPA MCQ – ITI COPA Online Test, Mock Test & Question Bank 2026"
home_d = (f"Free ITI COPA MCQ online test: {TOTAL} COPA trade questions with answers, CBT mock tests, study material "
          "& live HTML/Python practice in English, Hindi, Marathi.")
page("index.html", home_t, home_d, COMMON_KW + ", COPA MCQ 2026, ITI COPA online exam, COPA trade theory MCQ, COPA semester exam questions",
     [ORG, WEBSITE, webpage("", home_t, home_d), FAQ_LD,
      {"@type": "Course", "@id": SITE + "/#course", "name": "ITI COPA – Computer Operator and Programming Assistant (MCQ preparation)",
       "description": f"Complete ITI COPA trade preparation: {TOTAL} MCQs in 15 modules, CBT mock tests, study material and live coding practice in English, Hindi and Marathi.",
       "provider": {"@id": SITE + "/#org"}, "inLanguage": ["en", "hi", "mr"], "educationalLevel": "ITI (Craftsmen Training Scheme)",
       "isAccessibleForFree": True, "offers": {"@type": "Offer", "price": "0", "priceCurrency": "INR", "category": "Free"},
       "hasCourseInstance": {"@type": "CourseInstance", "courseMode": "online", "courseWorkload": "PT60H"},
       "teaches": [m[1] for m in MODULES]}],
     prio="1.0", freq="daily")

t = "ITI COPA Modules MCQ – All 15 Modules Online Practice | COPA MCQ"
d = f"Practise ITI COPA MCQ module-wise: all 15 COPA trade modules from computer basics to Python, {TOTAL} questions with answers in English, Hindi and Marathi."
page("modules.html", t, d, COMMON_KW + ", COPA module wise MCQ, COPA syllabus, COPA all modules",
     [webpage("modules.html", t, d, "CollectionPage"), crumbs(("Home", ""), ("COPA Modules", "modules.html")),
      {"@type": "ItemList", "name": "ITI COPA modules", "numberOfItems": 15, "itemListElement": [
          {"@type": "ListItem", "position": m[0], "name": f"Module {m[0]}: {m[1]}", "url": f"{SITE}/modules/module-{m[0]:02d}.html"} for m in MODULES]}],
     prio="0.9")

for (n, en, hi, mr, topics) in MODULES:
    p = f"modules/module-{n:02d}.html"
    t = f"COPA Module {n} MCQ: {en} – Online Test with Answers"
    if len(t) > 62:
        t = f"COPA Module {n} MCQ: {en} – Online Test"
    if len(t) > 66:
        t = f"COPA Module {n} MCQ: {en}"
    d = f"ITI COPA Module {n} {en} MCQ: {COUNT[n]} questions with answers & explanation, online practice and module test in English, Hindi, Marathi."
    if len(d) > 160:
        d = f"COPA Module {n} {en} MCQ: {COUNT[n]} questions with answers, online practice & module test in English, Hindi, Marathi."
    qs = [q for q in Q if q["m"] == n][:10]
    page(p, t, d, f"COPA Module {n}, {en} MCQ, {en} questions, {topics}, ITI COPA {en} MCQ in Hindi, {hi}, {mr}, " + COMMON_KW,
         [webpage(p, t, d), crumbs(("Home", ""), ("COPA Modules", "modules.html"), (f"Module {n}: {en}", p)),
          {"@type": "Quiz", "name": f"ITI COPA Module {n} – {en} MCQ", "about": {"@type": "Thing", "name": en},
           "educationalLevel": "ITI COPA", "learningResourceType": "Practice questions", "inLanguage": ["en", "hi", "mr"],
           "isAccessibleForFree": True, "provider": {"@id": SITE + "/#org"}, "numberOfQuestions": COUNT[n],
           "hasPart": [{"@type": "Question", "eduQuestionType": "Multiple choice", "name": q["en"]["q"], "text": q["en"]["q"],
                        "acceptedAnswer": {"@type": "Answer", "text": q["en"]["o"][q["a"]]},
                        "suggestedAnswer": [{"@type": "Answer", "text": o} for i, o in enumerate(q["en"]["o"]) if i != q["a"]]} for q in qs]}],
         og_type="article", prio="0.9")

t = "COPA CBT Mock Test 2026 – Free ITI COPA Online Exam | COPA MCQ"
d = "Free ITI COPA CBT mock test like the real online exam: timer, question palette, mark for review, instant result with pass/fail (40%) and answer review."
page("exam.html", t, d, "COPA mock test, COPA CBT mock test, ITI COPA online exam, COPA online test free, COPA practice test, ITI CBT exam practice, " + COMMON_KW,
     [webpage("exam.html", t, d), crumbs(("Home", ""), ("CBT Mock Test", "exam.html")),
      {"@type": "Quiz", "name": "ITI COPA CBT Mock Test", "educationalLevel": "ITI COPA", "learningResourceType": "Mock test",
       "isAccessibleForFree": True, "provider": {"@id": SITE + "/#org"}, "inLanguage": ["en", "hi", "mr"]}],
     prio="0.9")

t = f"COPA Question Bank with Answers – {TOTAL} ITI COPA MCQ | COPA MCQ"
d = f"Complete ITI COPA question bank: all {TOTAL} COPA trade MCQs with answers & explanation, module-wise, searchable, printable, in English, Hindi, Marathi."
page("question-bank.html", t, d, "COPA question bank, COPA question bank with answers, COPA important questions, COPA MCQ with answers, " + COMMON_KW,
     [webpage("question-bank.html", t, d), crumbs(("Home", ""), ("Study Material", "study.html"), ("Question Bank", "question-bank.html")),
      {"@type": "LearningResource", "name": "ITI COPA Question Bank with Answers", "learningResourceType": "Question bank",
       "educationalLevel": "ITI COPA", "isAccessibleForFree": True, "inLanguage": ["en", "hi", "mr"], "provider": {"@id": SITE + "/#org"},
       "teaches": [m[1] for m in MODULES]}],
     prio="0.9")

t = "ITI COPA Study Material – Notes, PDF, Videos, Practicals | COPA MCQ"
d = "ITI COPA study material in one place: theory notes, practicals & assignments, PDF notes, video lessons, question bank, shortcuts and previous papers."
page("study.html", t, d, "ITI COPA study material, COPA notes, COPA notes PDF, COPA practical, COPA video lectures, COPA previous papers, COPA shortcut keys, " + COMMON_KW,
     [webpage("study.html", t, d, "CollectionPage"), crumbs(("Home", ""), ("Study Material", "study.html")),
      {"@type": "ItemList", "name": "ITI COPA study material", "itemListElement": [{"@type": "ListItem", "position": i + 1, "name": nm, "url": SITE + "/" + u} for i, (nm, u) in enumerate([
          ("Trade Syllabus", "trade-syllabus.html"), ("Theory Notes", "theory-notes.html"), ("Practical & Assignments", "practicals.html"), ("PDF Materials", "pdf-materials.html"), ("Video Tutorials", "video-tutorials.html"),
          ("Question Bank", "question-bank.html"), ("Quick Reference", "quick-reference.html"), ("Previous Papers", "previous-papers.html")])]}],
     prio="0.9")
t = "ITI COPA Syllabus 2025 (CTS 3.0, NSQF 3.5) – Trade Practical & Theory | COPA MCQ"
d = "Latest ITI COPA trade syllabus (DGT, revised Aug 2025): duration, 14 learning outcomes, professional skills (trade practical) and professional knowledge (trade theory). Free PDF download."
page("trade-syllabus.html", t, d, "COPA syllabus, ITI COPA syllabus 2025, COPA syllabus PDF, COPA trade syllabus NSQF 3.5, COPA CTS 3.0 syllabus, COPA trade practical, COPA trade theory, COPA learning outcomes, " + COMMON_KW,
     [webpage("trade-syllabus.html", t, d), crumbs(("Home", ""), ("Study Material", "study.html"), ("Trade Syllabus", "trade-syllabus.html")),
      {"@type": "Course", "name": "Computer Operator and Programming Assistant (COPA) – CTS 3.0", "description": d, "educationalCredentialAwarded": "National Trade Certificate (NTC)",
       "provider": {"@type": "GovernmentOrganization", "name": "Directorate General of Training (DGT), MSDE", "url": "https://dgt.gov.in"}, "timeRequired": "P1Y", "inLanguage": "en",
       "hasCourseInstance": {"@type": "CourseInstance", "courseMode": "Onsite", "courseWorkload": "PT1200H"}}],
     prio="0.9")
for u, nm, t, d, kw in [
    ("theory-notes.html", "Theory Notes", "COPA Theory Notes – Chapter-wise ITI COPA Notes | COPA MCQ", "Chapter-wise ITI COPA theory notes for every module: computer basics, Windows, Linux, MS Office, DBMS, networking, HTML, JavaScript, Python.", "COPA theory notes, ITI COPA notes, COPA chapter wise notes, COPA notes in Hindi, COPA notes Marathi"),
    ("practicals.html", "Practical & Assignments", "COPA Practical List & Assignments – HTML, JS, Python | COPA MCQ", f"ITI COPA practicals and assignments: ready-made HTML, CSS, JavaScript and Python programs you can run and edit online in the Live Practice Lab.", "COPA practical list, ITI COPA practical, COPA practical programs, HTML practical, Python practical, COPA assignments"),
    ("pdf-materials.html", "PDF Materials", "COPA Notes PDF Download – ITI COPA Study Material | COPA MCQ", "Download ITI COPA notes and study material as PDF, module-wise – free for all COPA trainees.", "COPA notes PDF, ITI COPA PDF, COPA book PDF, COPA study material PDF download"),
    ("video-tutorials.html", "Video Tutorials", "COPA Video Tutorials – ITI COPA Video Lessons | COPA MCQ", "Watch ITI COPA video lessons module-wise – MS Office, internet, HTML, JavaScript, Python and more.", "COPA video lectures, ITI COPA video, COPA YouTube classes, COPA tutorial"),
    ("quick-reference.html", "Quick Reference", "Computer Shortcut Keys, DOS & Linux Commands – COPA Quick Reference", "Windows, MS Word, Excel and PowerPoint shortcut keys, DOS and Linux commands, Excel functions, Python and HTML basics, ports and full forms for ITI COPA.", "computer shortcut keys, MS Word shortcut keys, Excel shortcut keys, DOS commands, Linux commands, Excel functions, computer full forms"),
    ("previous-papers.html", "Previous Papers", "COPA Previous Year Question Papers – ITI COPA Old Papers | COPA MCQ", "ITI COPA previous year and old question papers to download and practise for the CBT exam.", "COPA previous year question paper, ITI COPA old papers, COPA question paper PDF, COPA CBT paper"),
]:
    page(u, t, d, kw + ", " + COMMON_KW, [webpage(u, t, d), crumbs(("Home", ""), ("Study Material", "study.html"), (nm, u))], prio="0.8")

t = "COPA Practical Online – HTML, CSS, JS & Python Editor | COPA MCQ"
d = "Do ITI COPA practicals online: write HTML, CSS, JavaScript & Python code with VS Code-style suggestions, see live output and save your programs."
page("live-practice.html", t, d, "COPA practical, ITI COPA practical online, HTML online editor, Python online compiler, JavaScript online editor, CSS live preview, COPA lab, " + COMMON_KW,
     [webpage("live-practice.html", t, d), crumbs(("Home", ""), ("Live Practice", "live-practice.html")),
      {"@type": "WebApplication", "name": "COPA MCQ Live Practice Lab", "url": SITE + "/live-practice.html", "applicationCategory": "EducationalApplication",
       "operatingSystem": "Any (web browser)", "isAccessibleForFree": True, "offers": {"@type": "Offer", "price": "0", "priceCurrency": "INR"},
       "featureList": ["HTML, CSS and JavaScript live preview", "Python 3 in the browser", "Code suggestions", "Python errors explained in English, Hindi and Marathi", "Save programs"]}],
     prio="0.8")

t = "About COPA MCQ – Free ITI COPA Trade Learning Platform | Contact"
d = "About COPA MCQ, the free ITI COPA learning platform by Sanjay Rathod (CI - COPA): MCQ practice, CBT mock tests and live coding for COPA trainees. Contact us."
page("about.html", t, d, "about COPA MCQ, COPA MCQ contact, " + COMMON_KW,
     [webpage("about.html", t, d, "AboutPage"), crumbs(("Home", ""), ("About", "about.html")), ORG], prio="0.5", freq="monthly")

t = "COPA MCQ Reviews & Ratings – What ITI COPA Trainees Say"
d = "Read honest reviews and star ratings of COPA MCQ by ITI COPA trainees, and share your own experience with Google sign-in."
page("reviews.html", t, d, "COPA MCQ reviews, COPA MCQ rating, ITI COPA online test review, COPA mock test review, " + COMMON_KW,
     [webpage("reviews.html", t, d), crumbs(("Home", ""), ("Reviews", "reviews.html"))], prio="0.6", freq="daily")

t = "Privacy Policy – COPA MCQ"
d = "Privacy policy of COPA MCQ: what data is stored, how cookies, local storage and Google AdSense advertising are used, and your choices."
page("privacy.html", t, d, "COPA MCQ privacy policy", [webpage("privacy.html", t, d), crumbs(("Home", ""), ("Privacy Policy", "privacy.html"))], prio="0.3", freq="yearly")

t = "Terms & Conditions – COPA MCQ"
d = "Terms and conditions for using COPA MCQ, the free ITI COPA MCQ practice, CBT mock test and live practice website."
page("terms.html", t, d, "COPA MCQ terms and conditions", [webpage("terms.html", t, d), crumbs(("Home", ""), ("Terms & Conditions", "terms.html"))], prio="0.3", freq="yearly")

NOINDEX = "noindex, follow"
page("dashboard.html", "Student Dashboard – My COPA Test Results | COPA MCQ", "Your COPA MCQ student dashboard: test result history, pass/fail, scores and saved practicals.", "", [], robots=NOINDEX)
page("results.html", "Dashboard – COPA MCQ", "Your COPA MCQ results.", "", [], robots=NOINDEX)
page("admin.html", "Admin Panel – COPA MCQ", "COPA MCQ admin panel.", "", [], robots="noindex, nofollow")


# ---------- write head blocks ----------
def head_block(path, P):
    url = SITE + "/" + url_of(path)
    e = lambda s: html.escape(s, quote=True)
    lines = [
        "<!-- SEO:start (generated by tools/seo/apply-seo.py – edit the script, not this block) -->",
        f"<title>{e(P['title'])}</title>",
        f'<meta name="description" content="{e(P["desc"])}">',
    ]
    if P["kw"]:
        lines.append(f'<meta name="keywords" content="{e(P["kw"])}">')
    lines += [f'<meta name="robots" content="{P["robots"]}">', f'<meta name="author" content="{e(AUTHOR)}">']
    if not P["robots"].startswith("noindex"):
        lines += [
            f'<link rel="canonical" href="{url}">',
            f'<meta property="og:type" content="{P["og_type"]}">',
            f'<meta property="og:site_name" content="{BRAND}">',
            f'<meta property="og:title" content="{e(P["title"])}">',
            f'<meta property="og:description" content="{e(P["desc"])}">',
            f'<meta property="og:url" content="{url}">',
            f'<meta property="og:image" content="{OG_IMG}">',
            '<meta property="og:image:width" content="1200">', '<meta property="og:image:height" content="630">',
            f'<meta property="og:image:alt" content="{BRAND} – ITI COPA online MCQ test, CBT mock test and question bank">',
            '<meta property="og:locale" content="en_IN">', '<meta property="og:locale:alternate" content="hi_IN">', '<meta property="og:locale:alternate" content="mr_IN">',
            '<meta name="twitter:card" content="summary_large_image">',
            f'<meta name="twitter:title" content="{e(P["title"])}">',
            f'<meta name="twitter:description" content="{e(P["desc"])}">',
            f'<meta name="twitter:image" content="{OG_IMG}">',
        ]
        if P["ld"]:
            ld = json.dumps({"@context": "https://schema.org", "@graph": P["ld"]}, ensure_ascii=False, separators=(",", ":"))
            lines.append('<script type="application/ld+json">' + ld.replace("</", "<\\/") + "</script>")
    lines.append("<!-- SEO:end -->")
    return "\n  ".join(lines)


def strip_old(s):
    s = re.sub(r"\s*<!-- SEO:start.*?<!-- SEO:end -->", "", s, flags=re.S)
    s = re.sub(r"\s*<title>.*?</title>", "", s, flags=re.S)
    for pat in (r'<meta name="(description|keywords|robots|author|twitter:[a-z:]+)"[^>]*>', r'<meta property="og:[a-z:_]+"[^>]*>', r'<link rel="canonical"[^>]*>'):
        s = re.sub(r"\s*" + pat, "", s)
    return s


# crawlable question list for each module page (outside the JS-rendered area)
def module_qa(n, en):
    qs = [q for q in Q if q["m"] == n]
    items = []
    for i, q in enumerate(qs, 1):
        ans = q["en"]["o"][q["a"]]
        x = (q.get("x") or {}).get("en", "")
        items.append(f'<li><p class="sq">{html.escape(q["en"]["q"])}</p><p class="sa"><b>Answer:</b> {html.escape(ans)}'
                     + (f' <span>– {html.escape(x)}</span>' if x else "") + "</p></li>")
    return ("<!-- SEO-QA:start (generated) -->\n    "
            f'<section class="container seo-qa" aria-labelledby="seoQaH"><details><summary><h2 id="seoQaH">COPA Module {n}: {html.escape(en)} – all {len(qs)} MCQ questions with answers</h2></summary>'
            f'<ol>{"".join(items)}</ol></details></section>\n    <!-- SEO-QA:end -->')


for path, P in PAGES.items():
    f = ROOT / path
    if not f.exists():
        continue
    s = strip_old(f.read_text(encoding="utf-8"))
    s = re.sub(r'(<meta name="viewport"[^>]*>)', lambda m: m.group(1) + "\n  " + head_block(path, P), s, count=1)
    m = re.match(r"modules/module-(\d+)\.html", path)
    if m:
        n = int(m.group(1)); en = MODULES[n - 1][1]
        s = re.sub(r"\s*<!-- SEO-QA:start.*?<!-- SEO-QA:end -->", "", s, flags=re.S)
        s = s.replace('<div class="container" id="module"></div>', '<div class="container" id="module"></div>\n    ' + module_qa(n, en), 1)
    f.write_text(s, encoding="utf-8")

# ---------- sitemap.xml & robots.txt ----------
urls = [(SITE + "/" + url_of(p), P) for p, P in PAGES.items() if not P["robots"].startswith("noindex")]
sm = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">']
for u, P in urls:
    sm.append(f"  <url><loc>{u}</loc><lastmod>{TODAY}</lastmod><changefreq>{P['freq']}</changefreq><priority>{P['prio']}</priority>"
              + (f"<image:image><image:loc>{OG_IMG}</image:loc></image:image>" if u == SITE + "/" else "") + "</url>")
sm.append("</urlset>")
(ROOT / "sitemap.xml").write_text("\n".join(sm) + "\n", encoding="utf-8")
(ROOT / "robots.txt").write_text(
    "User-agent: *\nAllow: /\nDisallow: /admin.html\nDisallow: /tools/\n\nSitemap: " + SITE + "/sitemap.xml\n", encoding="utf-8")
print("pages:", len(PAGES), "| sitemap urls:", len(urls))
for p, P in PAGES.items():
    print(f"{len(P['title']):3d} {len(P['desc']):3d}  {p:30s} {P['title']}")
