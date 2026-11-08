/* =========================================================
   COPA MCQ – Study Material hub and its sections
   <body data-section="hub|theory|practical|pdf|video|qbank|reference|papers">
   Admin study material (js/admin.js → Study Material) has a "cat" (category);
   old items without a category count as Theory Notes.
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, L = C.lang, t = C.t;
  const SEC = document.body.getAttribute("data-section") || "hub";
  C.renderShell("study");

  const S = [
    { k: "theory", page: "theory-notes.html", icon: "fa-book-open-reader", g: ["#1554e0", "#4f8bff"],
      t: { en: ["Theory Notes", "Chapter-wise Notes"], hi: ["थ्योरी नोट्स", "अध्याय-वार नोट्स"], mr: ["थिअरी नोट्स", "प्रकरणनिहाय नोट्स"] } },
    { k: "practical", page: "practicals.html", icon: "fa-laptop-code", g: ["#16a34a", "#4ade80"],
      t: { en: ["Practical", "Practical & Assignments"], hi: ["प्रैक्टिकल", "प्रैक्टिकल और असाइनमेंट"], mr: ["प्रॅक्टिकल", "प्रॅक्टिकल आणि असाइनमेंट"] } },
    { k: "pdf", page: "pdf-materials.html", icon: "fa-file-pdf", g: ["#e5383b", "#ff7a59"],
      t: { en: ["PDF Materials", "Download Notes"], hi: ["PDF सामग्री", "नोट्स डाउनलोड करें"], mr: ["PDF साहित्य", "नोट्स डाउनलोड करा"] } },
    { k: "video", page: "video-tutorials.html", icon: "fa-circle-play", g: ["#c026d3", "#f472b6"],
      t: { en: ["Video Tutorials", "Video Lessons"], hi: ["वीडियो ट्यूटोरियल", "वीडियो पाठ"], mr: ["व्हिडिओ ट्यूटोरियल", "व्हिडिओ धडे"] } },
    { k: "qbank", page: "question-bank.html", icon: "fa-list-check", g: ["#7b3fe4", "#a78bfa"],
      t: { en: ["Question Bank", "All questions with correct answers – module-wise. Read, revise and print."], hi: ["प्रश्न बैंक", "मॉड्यूल-वार सभी प्रश्न सही उत्तरों के साथ – पढ़ें, दोहराएँ और प्रिंट करें।"], mr: ["प्रश्नपेढी", "मॉड्यूलनिहाय सर्व प्रश्न योग्य उत्तरांसह – वाचा, उजळणी करा आणि प्रिंट करा."] } },
    { k: "reference", page: "quick-reference.html", icon: "fa-keyboard", g: ["#f59e0b", "#fcd34d"],
      t: { en: ["Quick Reference", "Shortcuts & Commands"], hi: ["क्विक रेफरेंस", "शॉर्टकट और कमांड"], mr: ["क्विक रेफरन्स", "शॉर्टकट आणि कमांड्स"] } },
    { k: "papers", page: "previous-papers.html", icon: "fa-file-lines", g: ["#0fa3b1", "#5eead4"],
      t: { en: ["Previous Papers", "Old Question Papers"], hi: ["पिछले प्रश्नपत्र", "पुराने प्रश्नपत्र"], mr: ["मागील प्रश्नपत्रिका", "जुन्या प्रश्नपत्रिका"] } }
  ];
  const TX = {
    en: { hubT: "Study Material", hubS: "Everything you need for the ITI COPA trade – notes, practicals, PDFs, videos, question bank, shortcuts and old papers.", items: "items", item: "item", open: "Open",
      recent: "Recently added", all: "All modules", general: "General", search: "Search…", none: "Nothing here yet", noneS: "New material is added regularly. Meanwhile practise with the question bank.",
      goQ: "Open Question Bank", read: "Read", download: "Open / Download", watch: "Watch", openLive: "Open in Live Practice", assign: "Assignments from your instructor", ready: "Ready-made practicals (Live Practice Lab)",
      web: "HTML · CSS · JavaScript", python: "Python", sections: "Study sections", back: "All study material", lesson: "Lesson", lessons: "lessons", part: "Part", readNotes: "Read notes", prev: "Previous", next: "Next", topics: "Topics", moduleW: "Module", fromAdmin: "More notes from your instructor", bookNote: "Study notes prepared lesson-wise as per the COPA Trade Theory syllabus (NSQF 2022).", qs: "questions", modules: "modules", print: "Print", copy: "Copied!" },
    hi: { hubT: "अध्ययन सामग्री", hubS: "ITI COPA ट्रेड के लिए सब कुछ – नोट्स, प्रैक्टिकल, PDF, वीडियो, प्रश्न बैंक, शॉर्टकट और पुराने प्रश्नपत्र।", items: "आइटम", item: "आइटम", open: "खोलें",
      recent: "हाल ही में जोड़ा गया", all: "सभी मॉड्यूल", general: "सामान्य", search: "खोजें…", none: "अभी यहाँ कुछ नहीं है", noneS: "नई सामग्री नियमित रूप से जोड़ी जाती है। तब तक प्रश्न बैंक से अभ्यास करें।",
      goQ: "प्रश्न बैंक खोलें", read: "पढ़ें", download: "खोलें / डाउनलोड", watch: "देखें", openLive: "लाइव प्रैक्टिस में खोलें", assign: "आपके प्रशिक्षक के असाइनमेंट", ready: "तैयार प्रैक्टिकल (लाइव प्रैक्टिस लैब)",
      web: "HTML · CSS · JavaScript", python: "पायथन", sections: "अध्ययन विभाग", back: "सभी अध्ययन सामग्री", lesson: "पाठ", lessons: "पाठ", part: "भाग", readNotes: "नोट्स पढ़ें", prev: "पिछला", next: "अगला", topics: "विषय", moduleW: "मॉड्यूल", fromAdmin: "आपके प्रशिक्षक के और नोट्स", bookNote: "COPA ट्रेड थ्योरी पाठ्यक्रम (NSQF 2022) के अनुसार पाठ-वार तैयार किए गए अध्ययन नोट्स।", qs: "प्रश्न", modules: "मॉड्यूल", print: "प्रिंट", copy: "कॉपी हुआ!" },
    mr: { hubT: "अभ्यास साहित्य", hubS: "ITI COPA ट्रेडसाठी सर्व काही – नोट्स, प्रॅक्टिकल, PDF, व्हिडिओ, प्रश्नपेढी, शॉर्टकट आणि जुन्या प्रश्नपत्रिका.", items: "आयटम", item: "आयटम", open: "उघडा",
      recent: "नुकतेच जोडलेले", all: "सर्व मॉड्यूल", general: "सामान्य", search: "शोधा…", none: "इथे अजून काही नाही", noneS: "नवीन साहित्य नियमितपणे जोडले जाते. तोपर्यंत प्रश्नपेढीतून सराव करा.",
      goQ: "प्रश्नपेढी उघडा", read: "वाचा", download: "उघडा / डाउनलोड", watch: "पहा", openLive: "लाइव्ह प्रॅक्टिसमध्ये उघडा", assign: "तुमच्या निदेशकांचे असाइनमेंट", ready: "तयार प्रॅक्टिकल (लाइव्ह प्रॅक्टिस लॅब)",
      web: "HTML · CSS · JavaScript", python: "पायथन", sections: "अभ्यास विभाग", back: "सर्व अभ्यास साहित्य", lesson: "पाठ", lessons: "पाठ", part: "भाग", readNotes: "नोट्स वाचा", prev: "मागील", next: "पुढील", topics: "विषय", moduleW: "मॉड्यूल", fromAdmin: "तुमच्या निदेशकांच्या आणखी नोट्स", bookNote: "COPA ट्रेड थिअरी अभ्यासक्रमानुसार (NSQF 2022) पाठनिहाय तयार केलेल्या अभ्यास नोट्स.", qs: "प्रश्न", modules: "मॉड्यूल्स", print: "प्रिंट", copy: "कॉपी झाले!" }
  };
  const X = TX[L] || TX.en;
  const tt = (s) => s.t[L] || s.t.en;
  const sec = (k) => S.find((s) => s.k === k);
  const catOf = (n) => n.cat || "theory";
  const notesIn = (k) => C.NOTES.filter((n) => catOf(n) === k);
  const PR = window.COPA_PRACTICALS || [];
  const REF = window.COPA_REFERENCE || [];
  const lessonKey = (no) => String(no).split(/[^0-9]+/).filter(Boolean).slice(0, 3).map(Number);
  const byLesson = (a, b) => { const x = lessonKey(a.no), y = lessonKey(b.no); for (let i = 0; i < 3; i++) if ((x[i] || 0) !== (y[i] || 0)) return (x[i] || 0) - (y[i] || 0); return 0; };
  const TH = (window.COPA_THEORY || []).slice().sort((a, b) => (a.part - b.part) || ((a.g || 0) - (b.g || 0)) || byLesson(a, b));
  const count = (k) => (k === "theory" ? TH.length + notesIn(k).length : k === "qbank" ? C.QUESTIONS.length : k === "practical" ? PR.length + notesIn(k).length : k === "reference" ? REF.reduce((a, g) => a + g.k.length, 0) : notesIn(k).length);
  const ytId = (u) => { const m = String(u || "").match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/); return m ? m[1] : ""; };
  const driveId = (u) => { const m = String(u || "").match(/drive\.google\.com\/(?:file\/d\/|open\?id=)([A-Za-z0-9_-]{10,})/); return m ? m[1] : ""; };
  const modLabel = (m) => (+m ? "M" + m : X.general);
  const root = document.getElementById("study");

  /* ---------- shared header for section pages ---------- */
  function head(s, extra) {
    const T = tt(s);
    return '<section class="sh-hero" style="--g1:' + s.g[0] + ";--g2:" + s.g[1] + '"><div class="sh-hero-in">' +
      '<nav class="sh-crumb"><a href="index.html">' + esc(t("home")) + '</a><i class="fa-solid fa-chevron-right"></i><a href="study.html">' + esc(X.hubT) + '</a><i class="fa-solid fa-chevron-right"></i><span>' + esc(T[0]) + "</span></nav>" +
      '<div class="sh-title"><span class="sh-ic"><i class="fa-solid ' + s.icon + '"></i></span><div><h1>' + esc(T[0]) + "</h1><p>" + esc(T[1]) + "</p></div></div>" + (extra || "") + "</div>" +
      '<i class="fa-solid ' + s.icon + ' sh-bgic" aria-hidden="true"></i></section>' +
      '<nav class="sh-tabs" aria-label="' + esc(X.sections) + '">' + S.map((x) => '<a href="' + x.page + '" class="' + (x.k === s.k ? "on" : "") + '" style="--c:' + x.g[0] + '"><i class="fa-solid ' + x.icon + '"></i><span>' + esc(tt(x)[0]) + "</span></a>").join("") + "</nav>";
  }
  const toolbar = (id) => '<div class="sh-tools"><select class="input" id="' + id + 'M" aria-label="Module"><option value="0">' + esc(X.all) + "</option>" + C.MODULES.map((m) => '<option value="' + m.id + '">M' + m.id + " – " + esc(C.modName(m)) + "</option>").join("") + "</select>" +
    '<div class="sh-search"><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="' + id + 'S" placeholder="' + esc(X.search) + '" aria-label="' + esc(X.search) + '"></div><span class="sh-count" id="' + id + 'C"></span></div>';
  const empty = () => '<div class="sh-empty"><div class="ic"><i class="fa-regular fa-folder-open"></i></div><h3>' + esc(X.none) + "</h3><p>" + esc(X.noneS) + '</p><a class="btn btn-primary" href="question-bank.html"><i class="fa-solid fa-list-check"></i> ' + esc(X.goQ) + "</a></div>";
  const match = (n, m, q) => (!m || +n.m === m || !+n.m) && (!q || (n.title + " " + (n.body || "").replace(/<[^>]+>/g, " ") + " " + (n.linkLabel || "")).toLowerCase().indexOf(q) > -1);
  function bindTools(id, draw) {
    const M = document.getElementById(id + "M"), Q = document.getElementById(id + "S");
    const hm = parseInt((location.hash || "").replace("#m", ""), 10); if (hm >= 1 && hm <= 15) M.value = hm;
    const go = () => { history.replaceState(null, "", +M.value ? "#m" + M.value : location.pathname); draw(+M.value, Q.value.trim().toLowerCase()); };
    M.addEventListener("change", go); Q.addEventListener("input", () => draw(+M.value, Q.value.trim().toLowerCase())); draw(+M.value, "");
  }
  const nItems = (n) => n + " " + (n === 1 ? X.item : X.items);
  const setCount = (id, n) => { const c = document.getElementById(id + "C"); if (c) c.textContent = nItems(n); };

  /* ---------- HUB ---------- */
  function hub() {
    const recent = C.NOTES.slice().sort((a, b) => (b.updated || b.created || 0) - (a.updated || a.created || 0)).slice(0, 6);
    root.innerHTML =
      '<section class="sh-hub-hero"><div class="in"><span class="kick"><i class="fa-solid fa-graduation-cap"></i> ITI COPA</span><h1>' + esc(X.hubT) + "</h1><p>" + esc(X.hubS) + "</p>" +
        '<div class="stats"><span><b>' + C.QUESTIONS.length + "</b> " + esc(X.qs) + "</span><span><b>15</b> " + esc(X.modules) + "</span><span><b>" + PR.length + "</b> " + esc(tt(sec("practical"))[0]) + "</span></div></div>" +
        '<div class="art" aria-hidden="true"><i class="fa-solid fa-book-open b1"></i><i class="fa-solid fa-file-pdf b2"></i><i class="fa-solid fa-circle-play b3"></i><i class="fa-solid fa-keyboard b4"></i></div></section>' +
      '<div class="sh-grid">' + S.map((s, i) => {
        const T = tt(s), n = count(s.k);
        return '<a class="sh-card' + (s.k === "qbank" ? " wide" : "") + '" href="' + s.page + '" style="--g1:' + s.g[0] + ";--g2:" + s.g[1] + ";--d:" + i * 60 + 'ms">' +
          '<span class="ic"><i class="fa-solid ' + s.icon + '"></i></span><span class="tx"><b>' + esc(T[0]) + "</b><small>" + esc(T[1]) + "</small></span>" +
          '<span class="ft"><span class="n">' + esc(s.k === "qbank" ? n + " " + X.qs : nItems(n)) + '</span><span class="go">' + esc(X.open) + ' <i class="fa-solid fa-arrow-right"></i></span></span>' +
          '<i class="fa-solid ' + s.icon + ' bg" aria-hidden="true"></i></a>';
      }).join("") + "</div>" +
      (recent.length ? '<section class="sh-recent"><h2><i class="fa-solid fa-clock-rotate-left"></i> ' + esc(X.recent) + '</h2><div class="list">' + recent.map((n) => {
        const s = sec(catOf(n)) || S[0];
        return '<a class="sh-ritem" href="' + s.page + (+n.m ? "#m" + n.m : "") + '" style="--g1:' + s.g[0] + ";--g2:" + s.g[1] + '"><span class="ic"><i class="fa-solid ' + s.icon + '"></i></span><span class="tx"><b>' + esc(n.title) + "</b><small>" + esc(tt(s)[0]) + " · " + esc(modLabel(n.m)) + '</small></span><i class="fa-solid fa-chevron-right"></i></a>';
      }).join("") + "</div></section>" : "");
  }

  /* ---------- THEORY NOTES: built-in lesson-wise notes (js/theory-data.js) + admin notes ---------- */
  function theory() {
    root.innerHTML = head(sec("theory")) + toolbar("th") + '<p class="sh-booknote"><i class="fa-solid fa-circle-info"></i> ' + esc(X.bookNote) + '</p><div id="thL"></div><div id="thA"></div>';
    const txt = (l) => (l.no + " " + l.title + " " + l.mt + " " + l.keys.join(" ") + " " + l.html.replace(/<[^>]+>/g, " ")).toLowerCase();
    bindTools("th", (m, q) => {
      const list = TH.filter((l) => (!m || l.m === m) && (!q || txt(l).indexOf(q) > -1));
      const extra = notesIn("theory").filter((n) => match(n, m, q));
      setCount("th", list.length + extra.length);
      let html = "", lastPart = 0, lastMod = "";
      list.forEach((l) => {
        if (l.part !== lastPart) { lastPart = l.part; lastMod = ""; html += (html ? "</div></section>" : "") + '<h2 class="sh-part"><span>' + esc(X.part) + " " + l.part + "</span></h2>"; }
        const mk = l.part + "-" + (l.g || 0) + "-" + (l.el ? "el" : l.bm);   // an elective = one section
        if (mk !== lastMod) {
          if (lastMod) html += "</div></section>";
          lastMod = mk;
          const cnt = list.filter((x) => x.part === l.part && (x.g || 0) === (l.g || 0) && (l.el || x.bm === l.bm)).length;
          const mods = l.el ? list.filter((x) => x.part === l.part && x.g === l.g).map((x) => x.bm) : [];
          html += '<section class="sh-mod' + (l.el ? " el" : "") + '"><header>' + (l.el ? '<span class="elb"><i class="fa-brands fa-' + (/python/i.test(l.el) ? "python" : "java") + '"></i> ' + esc(l.el) + "</span>" : "") + '<span class="mno">' + esc(X.moduleW) + " " + (l.el ? Math.min.apply(null, mods) + "–" + Math.max.apply(null, mods) : l.bm) + "</span><h3>" + esc(l.mt) + '</h3><span class="mc">' + cnt + " " + esc(cnt === 1 ? X.lesson : X.lessons) + '</span></header><div class="sh-lessons">';
        }
        const i = TH.indexOf(l);
        html += '<article class="sh-lesson" data-tl="' + i + '"><div class="top"><span class="lno"><i class="fa-solid fa-bookmark"></i> ' + esc(X.lesson) + " " + esc(l.no) + "</span>" + (l.el ? '<span class="chip">' + esc(X.moduleW) + " " + l.bm + "</span>" : "") + "</div>" +
          "<h4>" + esc(l.title) + '</h4><div class="keys">' + l.keys.slice(0, 5).map((k) => "<span>" + esc(k) + "</span>").join("") + (l.keys.length > 5 ? "<span>+" + (l.keys.length - 5) + "</span>" : "") + "</div>" +
          '<button type="button" class="btn btn-primary btn-sm" data-tl-open="' + i + '"><i class="fa-solid fa-book-open-reader"></i> ' + esc(X.readNotes) + "</button></article>";
      });
      if (lastMod) html += "</div></section>";
      document.getElementById("thL").innerHTML = html || (extra.length ? "" : empty());
      document.getElementById("thA").innerHTML = extra.length ? '<div class="sh-sub"><h2><i class="fa-solid fa-chalkboard-user"></i> ' + esc(X.fromAdmin) + "</h2></div>" + C.renderNotes(extra) : "";
    });
    document.getElementById("thL").addEventListener("click", (e) => {
      const b = e.target.closest("[data-tl-open], .sh-lesson"); if (!b) return;
      openLesson(+(b.dataset.tlOpen != null ? b.dataset.tlOpen : b.dataset.tl));
    });
    const hl = new URLSearchParams(location.search).get("lesson");
    if (hl) { const i = TH.findIndex((l) => l.no === hl); if (i > -1) openLesson(i); }
  }
  function openLesson(i) {
    const l = TH[i]; if (!l) return;
    const nav = (j, lbl, ic) => (TH[j] ? '<button type="button" class="btn btn-ghost btn-sm" data-go="' + j + '">' + (ic === "l" ? '<i class="fa-solid fa-arrow-left"></i> ' : "") + esc(lbl) + (ic === "r" ? ' <i class="fa-solid fa-arrow-right"></i>' : "") + "</button>" : "<span></span>");
    const m = C.modal('<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
      '<div class="note-view"><div class="nv-head"><span class="chip">' + esc(X.lesson) + " " + esc(l.no) + "</span><h2>" + esc(l.title) + '</h2><button type="button" class="btn btn-ghost btn-sm" data-pr><i class="fa-solid fa-print"></i> ' + esc(X.print) + "</button></div>" +
      '<p class="tl-meta">' + esc(X.part) + " " + l.part + (l.el ? " · " + esc(l.el) : "") + " · " + esc(X.moduleW) + " " + l.bm + ": " + esc(l.mt) + "</p>" +
      '<div class="rte-page rte-content tl-body">' + l.html + "</div>" +
      '<div class="tl-nav">' + nav(i - 1, X.prev, "l") + nav(i + 1, X.next, "r") + "</div></div>", { wide: true });
    m.el.querySelector(".modal").classList.add("note-modal");
    m.el.querySelectorAll("[data-go]").forEach((b) => b.addEventListener("click", () => { m.close(); openLesson(+b.dataset.go); }));
    m.el.querySelector("[data-pr]").addEventListener("click", () => {
      const w = window.open("", "_blank"); if (!w) return;
      const css = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((x) => '<link rel="stylesheet" href="' + x.href + '">').join("");
      w.document.write("<!doctype html><html><head><meta charset='utf-8'><title>" + esc(l.no + " " + l.title) + "</title>" + css + "<style>body{background:#fff;margin:0}.rte-page{box-shadow:none;border:0}</style></head><body><h1 style='font-family:Poppins,sans-serif;margin:20px 28px 0;font-size:24px'>" + esc(X.lesson + " " + l.no + " – " + l.title) +
        "</h1><p style='margin:6px 28px 0;color:#555'>" + esc(X.moduleW + " " + l.bm + ": " + l.mt) + "</p><div class='rte-page rte-content'>" + l.html + "</div><script>setTimeout(function(){print()},400)<\/script></body></html>");
      w.document.close();
    });
  }

  /* ---------- PRACTICAL: built-in Live Practice programs + assignments ---------- */
  function practical() {
    const groups = [["web", X.web, "fa-html5", "fa-brands"], ["python", X.python, "fa-python", "fa-brands"]];
    const catIc = { html: "fa-brands fa-html5", css: "fa-brands fa-css3-alt", js: "fa-brands fa-js", py: "fa-brands fa-python" };
    root.innerHTML = head(sec("practical")) + toolbar("pr") + '<div id="prA"></div>' +
      '<div class="sh-sub"><h2><i class="fa-solid fa-flask"></i> ' + esc(X.ready) + "</h2></div>" +
      groups.map((g) => '<h3 class="sh-h3"><i class="' + g[3] + " " + g[2] + '"></i> ' + esc(g[1]) + '</h3><div class="sh-prac">' + PR.filter((p) => p.lang === g[0]).map((p, i) =>
        '<a class="sh-pcard ' + p.cat + '" href="live-practice.html?t=' + esc(p.id) + '" data-q="' + esc(p.title.toLowerCase()) + '"><span class="no">' + (i + 1) + '</span><i class="' + (catIc[p.cat] || "fa-solid fa-code") + ' lg"></i><b>' + esc(p.title) + '</b><span class="go"><i class="fa-solid fa-play"></i> ' + esc(X.openLive) + "</span></a>").join("") + "</div>").join("");
    bindTools("pr", (m, q) => {
      const list = notesIn("practical").filter((n) => match(n, m, q));
      document.getElementById("prA").innerHTML = list.length ? '<div class="sh-sub"><h2><i class="fa-solid fa-clipboard-list"></i> ' + esc(X.assign) + "</h2></div>" + C.renderNotes(list) : "";
      let shown = 0;
      document.querySelectorAll(".sh-pcard").forEach((c) => { const ok = !q || c.dataset.q.indexOf(q) > -1; c.hidden = !ok; if (ok) shown++; });
      setCount("pr", list.length + shown);
    });
  }

  /* ---------- PDF MATERIALS ---------- */
  function pdf() {
    root.innerHTML = head(sec("pdf")) + toolbar("pd") + '<div class="sh-files" id="pdL"></div>';
    bindTools("pd", (m, q) => {
      const list = notesIn("pdf").filter((n) => match(n, m, q));
      setCount("pd", list.length);
      document.getElementById("pdL").innerHTML = list.length ? list.map((n) => fileCard(n, "fa-file-pdf")).join("") : empty();
    });
  }
  function fileCard(n, icon) {
    const link = n.link || "", isPdf = /\.pdf($|\?)/i.test(link), drive = driveId(link);
    const desc = (n.body || "").replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
    const kind = isPdf ? "PDF" : drive ? "Google Drive" : link ? (link.replace(/^https?:\/\/(www\.)?/, "").split("/")[0]) : "";
    return '<article class="sh-file"><span class="fi"><i class="fa-solid ' + icon + '"></i><small>' + esc(kind || "PDF") + '</small></span><div class="tx"><span class="chip">' + esc(modLabel(n.m)) + "</span><h3>" + esc(n.title) + "</h3>" +
      (desc ? "<p>" + esc(desc.length > 160 ? desc.slice(0, 157) + "…" : desc) + "</p>" : "") + '<div class="acts">' +
      (link ? '<a class="btn btn-primary btn-sm" href="' + esc(link) + '" target="_blank" rel="noopener"><i class="fa-solid fa-download"></i> ' + esc(n.linkLabel || X.download) + "</a>" : "") +
      (n.fmt === "html" && desc ? '<button type="button" class="btn btn-ghost btn-sm" data-note-open="' + esc(n.id) + '"><i class="fa-solid fa-book-open-reader"></i> ' + esc(X.read) + "</button>" : "") + "</div></div></article>";
  }

  /* ---------- VIDEO TUTORIALS (YouTube links → thumbnails + player) ---------- */
  function video() {
    root.innerHTML = head(sec("video")) + toolbar("vd") + '<div class="sh-videos" id="vdL"></div>';
    bindTools("vd", (m, q) => {
      const list = notesIn("video").filter((n) => match(n, m, q));
      setCount("vd", list.length);
      document.getElementById("vdL").innerHTML = list.length ? list.map((n) => {
        const id = ytId(n.link);
        return '<article class="sh-vid"><button type="button" class="th" ' + (id ? 'data-yt="' + id + '"' : 'data-href="' + esc(n.link || "") + '"') + ' aria-label="' + esc(X.watch + ": " + n.title) + '">' +
          (id ? '<img src="https://i.ytimg.com/vi/' + id + '/hqdefault.jpg" alt="" loading="lazy">' : '<span class="noth"><i class="fa-solid fa-film"></i></span>') + '<span class="pl"><i class="fa-solid fa-play"></i></span></button>' +
          '<div class="tx"><span class="chip">' + esc(modLabel(n.m)) + "</span><h3>" + esc(n.title) + "</h3>" + ((n.body || "").replace(/<[^>]+>/g, "").trim() ? "<p>" + esc((n.body || "").replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 140)) + "</p>" : "") + "</div></article>";
      }).join("") : empty();
    });
    document.getElementById("vdL").addEventListener("click", (e) => {
      const b = e.target.closest(".th"); if (!b) return;
      if (b.dataset.yt) {
        const m = C.modal('<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button><div class="sh-player"><iframe src="https://www.youtube-nocookie.com/embed/' + b.dataset.yt + '?autoplay=1&rel=0" title="YouTube video" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe></div>', { wide: true });
        m.el.querySelector(".modal").classList.add("sh-vmodal");
      } else if (b.dataset.href) window.open(b.dataset.href, "_blank", "noopener");
    });
  }

  /* ---------- QUICK REFERENCE (built-in) ---------- */
  function reference() {
    root.innerHTML = head(sec("reference")) +
      '<div class="sh-tools"><div class="sh-search grow"><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="rfS" placeholder="' + esc(X.search) + ' Ctrl + C, VLOOKUP, ls …"></div>' +
      '<button class="btn btn-ghost btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> ' + esc(X.print) + '</button><span class="sh-count" id="rfC"></span></div>' +
      '<div class="sh-jump">' + REF.map((g) => '<a href="#' + g.id + '" style="--c:' + g.color + '"><i class="' + g.icon + '"></i> ' + esc(g.t[L] || g.t.en) + "</a>").join("") + "</div>" +
      '<div class="sh-ref" id="rfL"></div>' + refExtra();
    const draw = (q) => {
      let n = 0;
      document.getElementById("rfL").innerHTML = REF.map((g) => {
        const rows = g.k.filter((r) => !q || (r[0] + " " + r[1]).toLowerCase().indexOf(q) > -1); n += rows.length;
        if (!rows.length) return "";
        return '<section class="sh-rcard" id="' + g.id + '" style="--c:' + g.color + '"><h2><span class="ic"><i class="' + g.icon + '"></i></span>' + esc(g.t[L] || g.t.en) + "<small>" + rows.length + "</small></h2>" +
          '<div class="rows">' + rows.map((r) => '<div class="r"><span class="' + (g.mono ? "cmd" : "keys") + '">' + (g.mono ? '<code>' + esc(r[0]) + '</code><button type="button" class="cp" data-cp="' + esc(r[0]) + '" title="Copy"><i class="fa-regular fa-copy"></i></button>' : keys(r[0])) + "</span><span class=\"d\">" + esc(r[1]) + "</span></div>").join("") + "</div></section>";
      }).join("") || empty();
      document.getElementById("rfC").textContent = nItems(n);
    };
    document.getElementById("rfS").addEventListener("input", (e) => draw(e.target.value.trim().toLowerCase()));
    document.getElementById("rfL").addEventListener("click", (e) => {
      const b = e.target.closest("[data-cp]"); if (!b) return;
      (navigator.clipboard ? navigator.clipboard.writeText(b.dataset.cp) : Promise.reject()).then(() => C.toast(X.copy)).catch(() => {});
    });
    draw("");
  }
  const keys = (s) => s.split(/\s*\/\s*/).map((alt) => alt.split(/\s*\+\s*/).map((k) => "<kbd>" + esc(k) + "</kbd>").join('<span class="pl">+</span>')).join('<span class="or">/</span>');
  const refExtra = () => { const list = notesIn("reference"); return list.length ? '<div class="sh-sub"><h2><i class="fa-solid fa-note-sticky"></i> ' + esc(X.recent) + "</h2></div>" + C.renderNotes(list) : ""; };

  /* ---------- PREVIOUS PAPERS ---------- */
  function papers() {
    root.innerHTML = head(sec("papers")) + toolbar("pp") + '<div class="sh-files" id="ppL"></div>';
    bindTools("pp", (m, q) => {
      const list = notesIn("papers").filter((n) => match(n, m, q)).sort((a, b) => String(b.title).localeCompare(String(a.title), undefined, { numeric: true }));
      setCount("pp", list.length);
      document.getElementById("ppL").innerHTML = list.length ? list.map((n) => fileCard(n, "fa-file-lines")).join("") : empty();
    });
  }

  /* ---------- QUESTION BANK: header only (list is drawn by js/question-bank.js) ---------- */
  function qbank() {
    const h = document.getElementById("qbHead"); if (h) h.innerHTML = head(sec("qbank"));
  }

  ({ hub, theory, practical, pdf, video, reference, papers, qbank }[SEC] || hub)();
  window.COPA_STUDY = { sections: S, sec, tt };
})();
