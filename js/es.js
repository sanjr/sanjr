/* =========================================================
   COPA MCQ – ES (Employability Skills) page
   #            → topics overview
   #t3 / #t3-videos / #t3-mcq   → one topic: notes, YouTube videos, MCQ practice
   #cbt / #cbt-3               → ES CBT test (all topics / one topic)
   Content: built-in topics & key points (js/es-data.js), built-in MCQs
   (data/es-questions.js) + notes, videos and MCQs added by the admin.
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, L = C.lang, ES = window.COPA_ES;
  C.renderShell("es");
  const root = document.getElementById("es");
  const TX = {
    en: { title: "Employability Skills", kick: "ES · Common subject for all ITI trades", sub: "Notes, YouTube video lessons, MCQ practice and a real CBT-style test for every Employability Skills topic.",
      topics: "topics", mcqs: "MCQs", videos: "videos", notes: "notes", startCbt: "Start CBT Test", practice: "Practice MCQ", allTopics: "All topics", topic: "Topic",
      tNotes: "Notes", tVideos: "Videos", tMcq: "MCQ Practice", tTest: "Topic Test", keyPts: "Key points", moreNotes: "Notes from your instructor", noNotes: "More notes for this topic will be added soon.",
      noVideos: "Video lessons for this topic will be added soon.", noMcq: "MCQs for this topic will be added soon.", watch: "Watch",
      q: "Question", of: "of", correct: "Correct!", wrong: "Wrong – correct answer:", next: "Next", prev: "Previous", finish: "Finish", restart: "Practice again", score: "Score", done: "Practice complete",
      cbtT: "ES CBT Test", cbtS: "Computer Based Test – exactly like the NCVT online exam. Choose topics and number of questions.", selTopics: "Topics in the test", nQ: "Number of questions", all: "All", time: "Time", min: "min",
      lang: "Exam language", name: "Candidate name", start: "Start test", rules: ["One mark for every correct answer, no negative marking.", "Use Save & Next to save an answer; Mark for Review to come back later.", "The test is submitted automatically when the time is over.", "Do not refresh the page during the test."],
      saveNext: "Save & Next", mark: "Mark for Review & Next", clear: "Clear Response", submit: "Submit Test", palette: "Question Palette", answered: "Answered", notAns: "Not Answered", marked: "Marked for Review", notVisited: "Not Visited",
      sureT: "Submit the test?", sureS: "You cannot change your answers after submitting.", yes: "Yes, submit", no: "Continue test", timeUp: "Time is over – your test has been submitted.",
      result: "Your result", correctN: "Correct", wrongN: "Wrong", skipped: "Skipped", pct: "Percentage", grade: ["Needs more practice", "Pass", "Good", "Excellent!"], review: "Answer review", yourAns: "Your answer", ans: "Answer", notAnswered: "Not answered",
      again: "Take another test", home: "ES home", topicWise: "Topic-wise performance", history: "Your recent ES tests", leave: "Your test is running. Leave this page?", back: "All ES topics", pool: "questions available", empty: "Select at least one topic with questions.", noName: "Candidate", explain: "Explanation" },
    hi: { title: "रोज़गार योग्यता कौशल", kick: "ES · सभी ITI ट्रेड का सामान्य विषय", sub: "रोज़गार योग्यता कौशल के हर विषय के लिए नोट्स, YouTube वीडियो, MCQ अभ्यास और असली CBT जैसा टेस्ट।",
      topics: "विषय", mcqs: "MCQ", videos: "वीडियो", notes: "नोट्स", startCbt: "CBT टेस्ट शुरू करें", practice: "MCQ अभ्यास", allTopics: "सभी विषय", topic: "विषय",
      tNotes: "नोट्स", tVideos: "वीडियो", tMcq: "MCQ अभ्यास", tTest: "विषय टेस्ट", keyPts: "मुख्य बिंदु", moreNotes: "आपके प्रशिक्षक के नोट्स", noNotes: "इस विषय के और नोट्स जल्द जोड़े जाएँगे।",
      noVideos: "इस विषय के वीडियो जल्द जोड़े जाएँगे।", noMcq: "इस विषय के MCQ जल्द जोड़े जाएँगे।", watch: "देखें",
      q: "प्रश्न", of: "में से", correct: "सही!", wrong: "गलत – सही उत्तर:", next: "अगला", prev: "पिछला", finish: "समाप्त", restart: "फिर से अभ्यास", score: "स्कोर", done: "अभ्यास पूरा",
      cbtT: "ES CBT टेस्ट", cbtS: "कंप्यूटर आधारित टेस्ट – बिल्कुल NCVT ऑनलाइन परीक्षा जैसा। विषय और प्रश्नों की संख्या चुनें।", selTopics: "टेस्ट के विषय", nQ: "प्रश्नों की संख्या", all: "सभी", time: "समय", min: "मिनट",
      lang: "परीक्षा भाषा", name: "उम्मीदवार का नाम", start: "टेस्ट शुरू करें", rules: ["हर सही उत्तर पर एक अंक, कोई नकारात्मक अंकन नहीं।", "उत्तर सेव करने के लिए Save & Next; बाद में देखने के लिए Mark for Review।", "समय पूरा होते ही टेस्ट अपने-आप सबमिट हो जाएगा।", "टेस्ट के दौरान पेज रिफ्रेश न करें।"],
      saveNext: "सेव करें और आगे", mark: "रिव्यू के लिए मार्क करें", clear: "उत्तर हटाएँ", submit: "टेस्ट सबमिट करें", palette: "प्रश्न पैलेट", answered: "उत्तर दिया", notAns: "उत्तर नहीं दिया", marked: "रिव्यू के लिए मार्क", notVisited: "नहीं देखा",
      sureT: "टेस्ट सबमिट करें?", sureS: "सबमिट करने के बाद उत्तर नहीं बदल सकते।", yes: "हाँ, सबमिट करें", no: "टेस्ट जारी रखें", timeUp: "समय समाप्त – आपका टेस्ट सबमिट हो गया।",
      result: "आपका परिणाम", correctN: "सही", wrongN: "गलत", skipped: "छोड़े", pct: "प्रतिशत", grade: ["और अभ्यास करें", "पास", "अच्छा", "उत्कृष्ट!"], review: "उत्तरों की समीक्षा", yourAns: "आपका उत्तर", ans: "उत्तर", notAnswered: "उत्तर नहीं दिया",
      again: "दूसरा टेस्ट दें", home: "ES होम", topicWise: "विषय-वार प्रदर्शन", history: "आपके हाल के ES टेस्ट", leave: "आपका टेस्ट चल रहा है। पेज छोड़ें?", back: "सभी ES विषय", pool: "प्रश्न उपलब्ध", empty: "प्रश्नों वाला कम से कम एक विषय चुनें।", noName: "उम्मीदवार", explain: "स्पष्टीकरण" },
    mr: { title: "रोजगारक्षमता कौशल्ये", kick: "ES · सर्व ITI ट्रेडसाठी सामायिक विषय", sub: "रोजगारक्षमता कौशल्यांच्या प्रत्येक विषयासाठी नोट्स, YouTube व्हिडिओ, MCQ सराव आणि खऱ्या CBT सारखी टेस्ट.",
      topics: "विषय", mcqs: "MCQ", videos: "व्हिडिओ", notes: "नोट्स", startCbt: "CBT टेस्ट सुरू करा", practice: "MCQ सराव", allTopics: "सर्व विषय", topic: "विषय",
      tNotes: "नोट्स", tVideos: "व्हिडिओ", tMcq: "MCQ सराव", tTest: "विषय टेस्ट", keyPts: "महत्त्वाचे मुद्दे", moreNotes: "तुमच्या निदेशकांच्या नोट्स", noNotes: "या विषयाच्या आणखी नोट्स लवकरच जोडल्या जातील.",
      noVideos: "या विषयाचे व्हिडिओ लवकरच जोडले जातील.", noMcq: "या विषयाचे MCQ लवकरच जोडले जातील.", watch: "पहा",
      q: "प्रश्न", of: "पैकी", correct: "बरोबर!", wrong: "चूक – बरोबर उत्तर:", next: "पुढील", prev: "मागील", finish: "पूर्ण", restart: "पुन्हा सराव", score: "गुण", done: "सराव पूर्ण",
      cbtT: "ES CBT टेस्ट", cbtS: "कॉम्प्युटर आधारित टेस्ट – अगदी NCVT ऑनलाइन परीक्षेसारखी. विषय आणि प्रश्नसंख्या निवडा.", selTopics: "टेस्टमधील विषय", nQ: "प्रश्नांची संख्या", all: "सर्व", time: "वेळ", min: "मिनिटे",
      lang: "परीक्षेची भाषा", name: "उमेदवाराचे नाव", start: "टेस्ट सुरू करा", rules: ["प्रत्येक बरोबर उत्तराला एक गुण, निगेटिव्ह मार्किंग नाही.", "उत्तर सेव्ह करण्यासाठी Save & Next; नंतर पाहण्यासाठी Mark for Review.", "वेळ संपताच टेस्ट आपोआप सबमिट होईल.", "टेस्ट चालू असताना पेज रिफ्रेश करू नका."],
      saveNext: "सेव्ह करा आणि पुढे", mark: "रिव्ह्यूसाठी मार्क करा", clear: "उत्तर काढा", submit: "टेस्ट सबमिट करा", palette: "प्रश्न पॅलेट", answered: "उत्तर दिले", notAns: "उत्तर दिले नाही", marked: "रिव्ह्यूसाठी मार्क", notVisited: "पाहिले नाही",
      sureT: "टेस्ट सबमिट करायची?", sureS: "सबमिट केल्यानंतर उत्तरे बदलता येणार नाहीत.", yes: "हो, सबमिट करा", no: "टेस्ट सुरू ठेवा", timeUp: "वेळ संपली – तुमची टेस्ट सबमिट झाली.",
      result: "तुमचा निकाल", correctN: "बरोबर", wrongN: "चूक", skipped: "सोडलेले", pct: "टक्केवारी", grade: ["आणखी सराव करा", "पास", "चांगले", "उत्कृष्ट!"], review: "उत्तरांचा आढावा", yourAns: "तुमचे उत्तर", ans: "उत्तर", notAnswered: "उत्तर दिले नाही",
      again: "दुसरी टेस्ट द्या", home: "ES मुख्य पान", topicWise: "विषयनिहाय कामगिरी", history: "तुमच्या अलीकडील ES टेस्ट", leave: "तुमची टेस्ट चालू आहे. पान सोडायचे?", back: "सर्व ES विषय", pool: "प्रश्न उपलब्ध", empty: "प्रश्न असलेला किमान एक विषय निवडा.", noName: "उमेदवार", explain: "स्पष्टीकरण" }
  };
  const X = TX[L] || TX.en;
  const TP = ES.topics, T = (tp) => tp.t[L] || tp.t.en, K = (tp) => tp.k[L] || tp.k.en, byId = (id) => TP.find((t) => t.id === +id);
  const qOf = (id) => C.ES_QUESTIONS.filter((q) => q.m === 100 + id);
  const notesOf = (id, cat) => C.NOTES.filter((n) => n.cat === cat && +n.m === id);
  const ytId = (u) => { const m = String(u || "").match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/); return m ? m[1] : ""; };
  const ls = { get(k, d) { try { const v = localStorage.getItem("copa_es_" + k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } }, set(k, v) { try { localStorage.setItem("copa_es_" + k, JSON.stringify(v)); } catch (e) { /* */ } } };
  const qText = (q, l) => q[l] || q.en;
  let stop = null;            // cleanup of the open view (timers / listeners)

  /* ---------- small parts ---------- */
  const tabsOf = (tp, on) => '<nav class="es-tabs">' + [["", "fa-book-open", X.tNotes, notesOf(tp.id, "es").length + 1], ["-videos", "fa-circle-play", X.tVideos, notesOf(tp.id, "es-video").length], ["-mcq", "fa-list-check", X.tMcq, qOf(tp.id).length]].map((x) =>
    '<a href="#t' + tp.id + x[0] + '" class="' + (on === x[0] ? "on" : "") + '"><i class="fa-solid ' + x[1] + '"></i> ' + esc(x[2]) + " <small>" + x[3] + "</small></a>").join("") +
    '<a href="#cbt-' + tp.id + '" class="test"><i class="fa-solid fa-laptop-code"></i> ' + esc(X.tTest) + "</a></nav>";
  function videoGrid(list) {
    return '<div class="es-videos">' + list.map((n) => { const id = ytId(n.link);
      return '<article class="es-vid"><button type="button" class="th" ' + (id ? 'data-yt="' + id + '"' : 'data-href="' + esc(n.link || "") + '"') + ' aria-label="' + esc(X.watch + ": " + n.title) + '">' +
        (id ? '<img src="https://i.ytimg.com/vi/' + id + '/hqdefault.jpg" alt="" loading="lazy">' : '<span class="noth"><i class="fa-solid fa-film"></i></span>') + '<span class="pl"><i class="fa-solid fa-play"></i></span></button>' +
        '<div class="tx"><h3>' + esc(n.title) + "</h3>" + ((n.body || "").replace(/<[^>]+>/g, "").trim() ? "<p>" + esc((n.body || "").replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 140)) + "</p>" : "") + "</div></article>"; }).join("") + "</div>";
  }
  root.addEventListener("click", (e) => {
    const b = e.target.closest(".es-vid .th"); if (!b) return;
    if (b.dataset.yt) { const m = C.modal('<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button><div class="sh-player"><iframe src="https://www.youtube-nocookie.com/embed/' + b.dataset.yt + '?autoplay=1&rel=0" title="YouTube video" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe></div>', { wide: true }); m.el.querySelector(".modal").classList.add("es-vmodal"); }
    else if (b.dataset.href) window.open(b.dataset.href, "_blank", "noopener");
  });

  /* ---------- HOME ---------- */
  function home() {
    const prac = ls.get("prac", {}), allQ = C.ES_QUESTIONS.length, vids = C.NOTES.filter((n) => n.cat === "es-video").length, nts = C.NOTES.filter((n) => n.cat === "es").length + TP.length;
    const hist = ls.get("res", []);
    root.innerHTML =
      '<section class="es-hero"><div class="in"><span class="kick"><i class="fa-solid fa-user-tie"></i> ' + esc(X.kick) + "</span><h1>" + esc(X.title) + ' <span class="ab">ES</span></h1><p>' + esc(X.sub) + "</p>" +
        '<div class="es-stats"><span><b>' + TP.length + "</b> " + esc(X.topics) + "</span><span><b>" + nts + "</b> " + esc(X.notes) + "</span><span><b>" + vids + "</b> " + esc(X.videos) + "</span><span><b>" + allQ + "</b> " + esc(X.mcqs) + "</span></div>" +
        '<div class="es-cta"><a class="btn es-b1" href="#cbt"><i class="fa-solid fa-laptop-code"></i> ' + esc(X.startCbt) + '</a><a class="btn es-b2" href="#t1-mcq"><i class="fa-solid fa-list-check"></i> ' + esc(X.practice) + "</a></div></div>" +
        '<div class="es-art" aria-hidden="true"><span class="a1"><i class="fa-solid fa-comments"></i></span><span class="a2"><i class="fa-solid fa-user-tie"></i></span><span class="a3"><i class="fa-solid fa-indian-rupee-sign"></i></span><span class="a4"><i class="fa-solid fa-laptop"></i></span><span class="a5"><i class="fa-solid fa-lightbulb"></i></span></div></section>' +
      '<div class="es-grid">' + TP.map((tp, i) => {
        const qs = qOf(tp.id), ok = qs.filter((q) => prac[q.id] === 1).length, tried = qs.filter((q) => prac[q.id] != null).length;
        return '<a class="es-card" href="#t' + tp.id + '" style="--c:' + tp.c + ";--d:" + i * 40 + 'ms"><span class="no">' + String(tp.id).padStart(2, "0") + '</span><span class="ic"><i class="fa-solid ' + tp.icon + '"></i></span>' +
          "<b>" + esc(T(tp)[0]) + "</b><small>" + esc(T(tp)[1]) + "</small>" +
          '<span class="ch"><span><i class="fa-solid fa-book-open"></i> ' + (notesOf(tp.id, "es").length + 1) + '</span><span><i class="fa-solid fa-circle-play"></i> ' + notesOf(tp.id, "es-video").length + '</span><span><i class="fa-solid fa-list-check"></i> ' + qs.length + "</span></span>" +
          '<span class="pg" title="' + ok + "/" + qs.length + '"><span style="width:' + (qs.length ? (tried ? ok / qs.length * 100 : 0) : 0) + '%"></span></span></a>';
      }).join("") + "</div>" +
      '<section class="es-cbtban"><div><span class="ic"><i class="fa-solid fa-laptop-code"></i></span><div><h2>' + esc(X.cbtT) + "</h2><p>" + esc(X.cbtS) + '</p></div></div><a class="btn" href="#cbt">' + esc(X.startCbt) + ' <i class="fa-solid fa-arrow-right"></i></a></section>' +
      (hist.length ? '<section class="es-hist"><h3><i class="fa-solid fa-clock-rotate-left"></i> ' + esc(X.history) + "</h3>" + histRows(hist.slice(0, 5)) + "</section>" : "");
  }
  const histRows = (h) => '<div class="es-hrows">' + h.map((r) => '<div class="r"><span class="d">' + esc(new Date(r.d).toLocaleString(undefined, { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })) + '</span><span class="t">' + (r.tp.length === TP.length ? esc(X.allTopics) : r.tp.map((x) => "T" + x).join(", ")) + '</span><span class="s"><b>' + r.c + "</b>/" + r.n + '</span><span class="p ' + (r.pct >= 60 ? "ok" : r.pct >= 40 ? "mid" : "lo") + '">' + r.pct + "%</span></div>").join("") + "</div>";

  /* ---------- TOPIC ---------- */
  function topic(id, sub) {
    const tp = byId(id); if (!tp) return home();
    const i = TP.indexOf(tp), prev = TP[i - 1], next = TP[i + 1];
    root.innerHTML = '<section class="es-thead" style="--c:' + tp.c + '"><a class="back" href="#"><i class="fa-solid fa-arrow-left"></i> ' + esc(X.back) + '</a><div class="tt"><span class="ic"><i class="fa-solid ' + tp.icon + '"></i></span><div><span class="no">' + esc(X.topic) + " " + tp.id + " / " + TP.length + "</span><h1>" + esc(T(tp)[0]) + "</h1><p>" + esc(T(tp)[1]) + "</p></div></div>" + tabsOf(tp, sub) + "</section>" +
      '<div id="esBody"></div>' +
      '<nav class="es-pn">' + (prev ? '<a class="btn btn-ghost" href="#t' + prev.id + sub + '"><i class="fa-solid fa-arrow-left"></i> <span class="nm">' + esc(T(prev)[0]) + "</span></a>" : "<span></span>") + (next ? '<a class="btn btn-ghost" href="#t' + next.id + sub + '"><span class="nm">' + esc(T(next)[0]) + '</span> <i class="fa-solid fa-arrow-right"></i></a>' : "") + "</nav>";
    const body = document.getElementById("esBody");
    if (sub === "-videos") { const v = notesOf(tp.id, "es-video"); body.innerHTML = v.length ? videoGrid(v) : empty("fa-circle-play", X.noVideos); return; }
    if (sub === "-mcq") return practice(tp, body);
    const extra = notesOf(tp.id, "es");
    body.innerHTML = '<section class="es-keys" style="--c:' + tp.c + '"><h2><i class="fa-solid fa-lightbulb"></i> ' + esc(X.keyPts) + '</h2><ol>' + K(tp).map((k) => "<li>" + esc(k) + "</li>").join("") + "</ol></section>" +
      (extra.length ? '<h2 class="es-h2"><i class="fa-solid fa-chalkboard-user"></i> ' + esc(X.moreNotes) + "</h2>" + C.renderNotes(extra) : '<p class="es-soon"><i class="fa-regular fa-clock"></i> ' + esc(X.noNotes) + "</p>");
  }
  const empty = (ic, s) => '<div class="es-empty"><span><i class="fa-solid ' + ic + '"></i></span><p>' + esc(s) + "</p></div>";

  /* ---------- MCQ PRACTICE (one by one, instant answer) ---------- */
  function practice(tp, body) {
    const qs = qOf(tp.id); if (!qs.length) { body.innerHTML = empty("fa-list-check", X.noMcq); return; }
    let i = 0, lang = L, got = {}; const prac = ls.get("prac", {});
    function draw() {
      if (i >= qs.length) {
        const ok = qs.filter((q) => got[q.id] === 1).length;
        body.innerHTML = '<section class="es-done"><span class="ring" style="--p:' + Math.round(ok / qs.length * 100) + '"><b>' + ok + "/" + qs.length + "</b></span><h2>" + esc(X.done) + '</h2><div class="acts"><button type="button" class="btn btn-ghost" id="esRe"><i class="fa-solid fa-rotate-right"></i> ' + esc(X.restart) + '</button><a class="btn btn-primary" href="#cbt-' + tp.id + '"><i class="fa-solid fa-laptop-code"></i> ' + esc(X.tTest) + "</a></div></section>";
        document.getElementById("esRe").onclick = () => { i = 0; got = {}; draw(); };
        return;
      }
      const q = qs[i], Q = qText(q, lang), a = got[q.id];
      body.innerHTML = '<section class="es-pq"><div class="top"><span class="qn">' + esc(X.q) + " " + (i + 1) + " " + esc(X.of) + " " + qs.length + '</span><div class="lsw">' + ["en", "hi", "mr"].map((l) => '<button type="button" data-l="' + l + '" class="' + (l === lang ? "on" : "") + '">' + { en: "EN", hi: "हिं", mr: "मरा" }[l] + "</button>").join("") + "</div></div>" +
        '<div class="bar"><span style="width:' + (i / qs.length * 100) + '%"></span></div><h2>' + esc(Q.q) + "</h2>" + (q.code ? "<pre>" + esc(q.code) + "</pre>" : "") +
        '<div class="opts">' + Q.o.map((o, k) => '<button type="button" class="opt' + (a != null ? (k === q.a ? " ok" : a === 0 && k === got["s" + q.id] ? " no" : "") : "") + '" data-k="' + k + '"' + (a != null ? " disabled" : "") + '><span class="l">' + "ABCD"[k] + "</span><span>" + esc(o) + "</span></button>").join("") + "</div>" +
        (a != null ? '<div class="fb ' + (a ? "ok" : "no") + '"><b>' + (a ? '<i class="fa-solid fa-circle-check"></i> ' + esc(X.correct) : '<i class="fa-solid fa-circle-xmark"></i> ' + esc(X.wrong) + " " + "ABCD"[q.a] + ". " + esc(Q.o[q.a])) + "</b>" + (q.x && q.x[lang] ? "<p>" + esc(q.x[lang]) + "</p>" : "") + "</div>" : "") +
        '<div class="nav"><button type="button" class="btn btn-ghost" id="esP"' + (i ? "" : " disabled") + '><i class="fa-solid fa-arrow-left"></i> ' + esc(X.prev) + '</button><span class="sc">' + esc(X.score) + ": <b>" + qs.filter((x) => got[x.id] === 1).length + "</b></span>" +
        '<button type="button" class="btn btn-primary" id="esN">' + esc(i === qs.length - 1 ? X.finish : X.next) + ' <i class="fa-solid fa-arrow-right"></i></button></div></section>';
      body.querySelectorAll(".lsw [data-l]").forEach((b) => (b.onclick = () => { lang = b.dataset.l; draw(); }));
      body.querySelectorAll(".opt:not([disabled])").forEach((b) => (b.onclick = () => { const k = +b.dataset.k; got[q.id] = k === q.a ? 1 : 0; got["s" + q.id] = k; prac[q.id] = got[q.id]; ls.set("prac", prac); draw(); }));
      document.getElementById("esP").onclick = () => { i--; draw(); };
      document.getElementById("esN").onclick = () => { i++; draw(); window.scrollTo({ top: body.offsetTop - 90, behavior: "smooth" }); };
    }
    draw();
  }

  /* ---------- CBT ---------- */
  function cbtSetup(only) {
    const prof = C.getProfile(), sel = new Set(only ? [only] : TP.map((t) => t.id));
    root.innerHTML = '<section class="es-cbthead"><a class="back" href="#"><i class="fa-solid fa-arrow-left"></i> ' + esc(X.back) + '</a><h1><i class="fa-solid fa-laptop-code"></i> ' + esc(X.cbtT) + "</h1><p>" + esc(X.cbtS) + "</p></section>" +
      '<div class="es-setup"><section class="card es-sc"><h3>' + esc(X.selTopics) + ' <button type="button" class="lnk" id="esAll">' + esc(X.all) + '</button></h3><div class="es-tchk">' +
        TP.map((tp) => '<label style="--c:' + tp.c + '"><input type="checkbox" value="' + tp.id + '"' + (sel.has(tp.id) ? " checked" : "") + '><span><b>' + tp.id + ".</b> " + esc(T(tp)[0]) + " <small>(" + qOf(tp.id).length + ")</small></span></label>").join("") + "</div></section>" +
        '<section class="card es-sc"><div class="field"><label>' + esc(X.nQ) + '</label><div class="es-seg" id="esN">' + [10, 25, 50, 0].map((n, i) => '<button type="button" data-n="' + n + '" class="' + (i === 1 ? "on" : "") + '">' + (n || esc(X.all)) + "</button>").join("") + '</div><small class="pool" id="esPool"></small></div>' +
        '<div class="field"><label for="esLang">' + esc(X.lang) + '</label><select class="input" id="esLang"><option value="en">English</option><option value="hi">हिंदी (Hindi)</option><option value="mr">मराठी (Marathi)</option></select></div>' +
        '<div class="field"><label for="esName">' + esc(X.name) + '</label><input class="input" id="esName" maxlength="60" value="' + esc(prof ? prof.name : "") + '"></div>' +
        '<ul class="es-rules">' + X.rules.map((r) => '<li><i class="fa-solid fa-circle-check"></i> ' + esc(r) + "</li>").join("") + "</ul>" +
        '<p class="es-err" id="esErr" hidden></p><button type="button" class="btn btn-primary btn-block es-go" id="esGo"><i class="fa-solid fa-play"></i> ' + esc(X.start) + "</button></section></div>";
    document.getElementById("esLang").value = L;
    let n = 25;
    const chosen = () => Array.from(root.querySelectorAll(".es-tchk input:checked")).map((c) => +c.value);
    const pool = () => chosen().reduce((a, id) => a.concat(qOf(id)), []);
    const upd = () => { const p = pool().length, k = n ? Math.min(n, p) : p; document.getElementById("esPool").textContent = p + " " + X.pool + " · " + X.time + ": " + k + " " + X.min; };
    root.querySelector(".es-tchk").addEventListener("change", upd);
    document.getElementById("esAll").onclick = () => { const all = root.querySelectorAll(".es-tchk input"), on = Array.from(all).every((c) => c.checked); all.forEach((c) => (c.checked = !on)); upd(); };
    document.getElementById("esN").addEventListener("click", (e) => { const b = e.target.closest("[data-n]"); if (!b) return; n = +b.dataset.n; root.querySelectorAll("#esN button").forEach((x) => x.classList.toggle("on", x === b)); upd(); });
    upd();
    document.getElementById("esGo").onclick = () => {
      const p = pool(); if (!p.length) { const er = document.getElementById("esErr"); er.hidden = false; er.textContent = X.empty; return; }
      const qs = C.shuffle(p).slice(0, n ? Math.min(n, p.length) : p.length);
      exam({ qs, lang: document.getElementById("esLang").value, name: document.getElementById("esName").value.trim() || X.noName, tp: chosen(), sec: qs.length * 60 });
    };
  }
  function exam(S) {
    const N = S.qs.length, st = S.qs.map(() => ({ v: false, a: null, m: false })); let i = 0, left = S.sec, done = false;
    document.body.classList.add("cbt", "es-cbt-on");
    const unload = (e) => { if (!done) { e.preventDefault(); e.returnValue = X.leave; return X.leave; } };
    window.addEventListener("beforeunload", unload);
    root.innerHTML = '<div class="es-exam"><header class="eh"><div><b>' + esc(X.cbtT) + '</b><small>' + esc(S.name) + '</small></div><div class="tm" id="esT"></div></header>' +
      '<div class="eb"><section class="qa" id="esQ"></section><aside class="pal"><h4>' + esc(X.palette) + '</h4><div class="grid" id="esG"></div><div class="lg">' +
      [["a", X.answered], ["n", X.notAns], ["m", X.marked], ["v", X.notVisited]].map((x) => '<span><i class="' + x[0] + '"></i> ' + esc(x[1]) + "</span>").join("") + '</div><button type="button" class="btn btn-primary btn-block" id="esSub"><i class="fa-solid fa-paper-plane"></i> ' + esc(X.submit) + "</button></aside></div></div>";
    const cls = (s) => (s.m ? "m" + (s.a != null ? " a" : "") : s.a != null ? "a" : s.v ? "n" : "v");
    function draw() {
      st[i].v = true;
      const q = S.qs[i], Q = qText(q, S.lang), s = st[i];
      document.getElementById("esQ").innerHTML = '<div class="qh"><span>' + esc(X.q) + " " + (i + 1) + " / " + N + '</span></div><h2>' + esc(Q.q) + "</h2>" + (q.code ? "<pre>" + esc(q.code) + "</pre>" : "") +
        '<div class="opts">' + Q.o.map((o, k) => '<label class="opt' + (s.tmp === k || (s.tmp == null && s.a === k) ? " on" : "") + '"><input type="radio" name="esO" value="' + k + '"' + (s.tmp === k || (s.tmp == null && s.a === k) ? " checked" : "") + '><span class="l">' + "ABCD"[k] + "</span><span>" + esc(o) + "</span></label>").join("") + "</div>" +
        '<div class="btns"><button type="button" class="btn btn-ghost" data-act="mark"><i class="fa-solid fa-bookmark"></i> ' + esc(X.mark) + '</button><button type="button" class="btn btn-ghost" data-act="clear"><i class="fa-solid fa-eraser"></i> ' + esc(X.clear) + '</button>' +
        '<button type="button" class="btn btn-ghost" data-act="prev"' + (i ? "" : " disabled") + '><i class="fa-solid fa-arrow-left"></i> ' + esc(X.prev) + '</button><button type="button" class="btn btn-primary" data-act="save">' + esc(X.saveNext) + ' <i class="fa-solid fa-arrow-right"></i></button></div>';
      document.getElementById("esG").innerHTML = st.map((x, k) => '<button type="button" class="' + cls(x) + (k === i ? " cur" : "") + '" data-g="' + k + '">' + (k + 1) + "</button>").join("");
    }
    root.querySelector(".es-exam").addEventListener("change", (e) => { if (e.target.name === "esO") { st[i].tmp = +e.target.value; root.querySelectorAll(".opt").forEach((l) => l.classList.toggle("on", l.querySelector("input").checked)); } });
    root.querySelector(".es-exam").addEventListener("click", (e) => {
      const g = e.target.closest("[data-g]"); if (g) { delete st[i].tmp; i = +g.dataset.g; return draw(); }
      const b = e.target.closest("[data-act]"); if (!b) return;
      const s = st[i], act = b.dataset.act;
      if (act === "save") { if (s.tmp != null) s.a = s.tmp; delete s.tmp; s.m = false; if (i < N - 1) i++; draw(); }
      else if (act === "mark") { if (s.tmp != null) s.a = s.tmp; delete s.tmp; s.m = true; if (i < N - 1) i++; draw(); }
      else if (act === "clear") { s.a = null; delete s.tmp; draw(); }
      else if (act === "prev") { delete s.tmp; i--; draw(); }
    });
    document.getElementById("esSub").onclick = () => {
      if (st[i].tmp != null) { st[i].a = st[i].tmp; delete st[i].tmp; }
      const a = st.filter((x) => x.a != null).length, m = st.filter((x) => x.m).length;
      const md = C.modal('<h3>' + esc(X.sureT) + '</h3><div class="es-sum"><span class="a"><b>' + a + "</b>" + esc(X.answered) + '</span><span class="n"><b>' + (N - a) + "</b>" + esc(X.notAns) + '</span><span class="m"><b>' + m + "</b>" + esc(X.marked) + "</span></div><p>" + esc(X.sureS) + '</p><div class="modal-acts"><button type="button" class="btn btn-ghost" data-close>' + esc(X.no) + '</button><button type="button" class="btn btn-primary" id="esYes">' + esc(X.yes) + "</button></div>");
      md.el.querySelector("#esYes").onclick = () => { md.close(); finish(false); };
    };
    const tick = () => {
      left--; const el = document.getElementById("esT"); if (!el) return;
      el.textContent = C.fmtTime(left); el.classList.toggle("low", left <= 60);
      if (left <= 0) finish(true);
    };
    document.getElementById("esT").textContent = C.fmtTime(left);
    const timer = setInterval(tick, 1000);
    stop = () => { clearInterval(timer); window.removeEventListener("beforeunload", unload); document.body.classList.remove("cbt", "es-cbt-on"); };
    function finish(auto) {
      if (done) return; done = true; stop();
      const c = S.qs.filter((q, k) => st[k].a === q.a).length, sk = st.filter((x) => x.a == null).length, pct = Math.round(c / N * 100);
      const hist = ls.get("res", []); hist.unshift({ d: Date.now(), n: N, c, pct, tp: S.tp, t: S.sec - left }); ls.set("res", hist.slice(0, 30));
      if (auto) C.toast(X.timeUp);
      result(S, st, { c, w: N - c - sk, sk, pct, time: S.sec - left });
      history.replaceState(null, "", "#cbt-result");
    }
    draw();
  }
  function result(S, st, R) {
    const g = R.pct >= 80 ? 3 : R.pct >= 60 ? 2 : R.pct >= 40 ? 1 : 0;
    const per = {}; S.qs.forEach((q, k) => { const t = q.m - 100; per[t] = per[t] || { n: 0, c: 0 }; per[t].n++; if (st[k].a === q.a) per[t].c++; });
    root.innerHTML = '<section class="es-res g' + g + '"><div class="ring" style="--p:' + R.pct + '"><b>' + R.pct + "%</b><small>" + esc(X.pct) + '</small></div><div class="tx"><span class="kick">' + esc(X.result) + "</span><h1>" + esc(X.grade[g]) + "</h1><p>" + esc(S.name) + " · " + C.fmtTime(R.time) + "</p>" +
      '<div class="nums"><span class="c"><b>' + R.c + "</b>" + esc(X.correctN) + '</span><span class="w"><b>' + R.w + "</b>" + esc(X.wrongN) + '</span><span class="s"><b>' + R.sk + "</b>" + esc(X.skipped) + '</span></div><div class="acts"><a class="btn es-b1" href="#cbt"><i class="fa-solid fa-rotate-right"></i> ' + esc(X.again) + '</a><a class="btn es-b2" href="#"><i class="fa-solid fa-house"></i> ' + esc(X.home) + "</a></div></div></section>" +
      '<section class="card es-tw"><h3><i class="fa-solid fa-chart-column"></i> ' + esc(X.topicWise) + "</h3>" + Object.keys(per).map((t) => { const tp = byId(t), p = per[t]; return '<div class="r" style="--c:' + (tp ? tp.c : "#64748b") + '"><span class="n">' + esc(tp ? T(tp)[0] : "ES " + t) + '</span><span class="b"><span style="width:' + Math.round(p.c / p.n * 100) + '%"></span></span><span class="v">' + p.c + "/" + p.n + "</span></div>"; }).join("") + "</section>" +
      '<section class="es-review"><h3><i class="fa-solid fa-magnifying-glass"></i> ' + esc(X.review) + "</h3>" + S.qs.map((q, k) => {
        const Q = qText(q, S.lang), a = st[k].a, ok = a === q.a;
        return '<article class="' + (a == null ? "sk" : ok ? "ok" : "no") + '"><div class="h"><span>' + (k + 1) + '</span><i class="fa-solid ' + (a == null ? "fa-circle-minus" : ok ? "fa-circle-check" : "fa-circle-xmark") + '"></i></div><div class="b"><p class="q">' + esc(Q.q) + "</p>" +
          '<p class="y">' + esc(X.yourAns) + ": <b>" + (a == null ? esc(X.notAnswered) : "ABCD"[a] + ". " + esc(Q.o[a])) + "</b></p>" + (ok ? "" : '<p class="c">' + esc(X.ans) + ": <b>" + "ABCD"[q.a] + ". " + esc(Q.o[q.a]) + "</b></p>") + (q.x && q.x[S.lang] ? '<p class="x">' + esc(q.x[S.lang]) + "</p>" : "") + "</div></article>";
      }).join("") + "</section>";
    window.scrollTo(0, 0);
  }

  /* ---------- router ---------- */
  function route() {
    if (stop) { const s = stop; stop = null; s(); }
    const h = (location.hash || "").slice(1); let m;
    if ((m = /^t(\d+)(-videos|-mcq)?$/.exec(h))) topic(+m[1], m[2] || "");
    else if ((m = /^cbt(?:-(\d+))?$/.exec(h))) cbtSetup(m[1] ? +m[1] : 0);
    else if (h === "cbt-result") cbtSetup(0);
    else home();
  }
  window.addEventListener("hashchange", () => {
    if (document.body.classList.contains("es-cbt-on") && !confirm(X.leave)) { history.pushState(null, "", "#cbt"); return; }
    route(); window.scrollTo(0, 0);
  });
  route();
})();
