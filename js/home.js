/* Home page – landing design */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc, L = C.lang;

  /* ---------- page-specific text ---------- */
  const TX = {
    en: {
      nav: ["Home", "Modules", "Mock Test", "Study", "Results", "About"],
      signin: "Sign in", start: "Start Mock Test",
      h1a: "Learning in the computer age means we must practise", h1b: "tomorrow’s", h1c: "skills today",
      sub: "The complete ITI COPA question bank – 355 MCQs in English, Hindi & Marathi with real CBT-style mock tests.",
      joinI: "Start Practice", joinS: "Take Mock Test", tagI: "COPA Instructor", tagS: "ITI Trainee",
      trust: ["Free for all trainees", "No negative marking", "Works offline & on mobile"],
      popT: "Our Most", popH: "Popular Modules", popP: "Start with the modules every COPA trainee must master",
      explore: "Explore Modules", lessons: "Questions",
      catT: "Explore", catH: "All Modules", catP: "Choose a module and practise topic-wise",
      whyT: "Why", whyH: "COPA MCQ?", whyP: "Everything you need to clear the ITI COPA exam",
      f: [["Real CBT Exam", "Timer, question palette, mark for review and auto-submit – exactly like the ITI online exam."],
          ["3 Languages", "Read every question in English, हिंदी or मराठी and switch anytime."],
          ["Instant Feedback", "Practise module-wise and see the right answer immediately."],
          ["Detailed Analysis", "Score, pass/fail and module-wise strengths after every test."]],
      st: ["Modules", "MCQ Questions", "Languages", "Negative Marking"],
      bandH: "Prepare smart. Score high in the ITI COPA exam!",
      bandP: "Take a full 50-question CBT mock test with a 60-minute timer and get your result instantly.",
      bandB: "Take a Mock Test", bandB2: "Study Material",
      progT: "Your Progress", tests: "Tests attempted", avg: "Average score", prac: "Questions practised",
      footP: "Free online learning platform for ITI Computer Operator & Programming Assistant (COPA) trainees.",
      fh: ["Learn", "Practice", "Institute"], fl1: ["All Modules", "Study Material", "Python", "JavaScript"],
      fl2: ["Mock Test (CBT)", "Quick Test", "My Results", "Dashboard"], fl3: ["About", "Contact", "COPA MCQ", "Maharashtra"],
      by: "COPA MCQ", byR: "ITI COPA"
    },
    hi: {
      nav: ["होम", "मॉड्यूल", "मॉक टेस्ट", "अध्ययन", "परिणाम", "परिचय"],
      signin: "लॉगिन", start: "मॉक टेस्ट शुरू करें",
      h1a: "कंप्यूटर युग में सीखने का अर्थ है कि हम आज ही", h1b: "कल के", h1c: "कौशल का अभ्यास करें",
      sub: "पूरा ITI COPA प्रश्न बैंक – अंग्रेज़ी, हिंदी और मराठी में 355 MCQ, वास्तविक CBT जैसे मॉक टेस्ट के साथ।",
      joinI: "अभ्यास शुरू करें", joinS: "मॉक टेस्ट दें", tagI: "COPA अनुदेशक", tagS: "ITI प्रशिक्षु",
      trust: ["सभी प्रशिक्षुओं के लिए निःशुल्क", "कोई नकारात्मक अंकन नहीं", "ऑफ़लाइन और मोबाइल पर चलता है"],
      popT: "हमारे सबसे", popH: "लोकप्रिय मॉड्यूल", popP: "वे मॉड्यूल जिनमें हर COPA प्रशिक्षु को निपुण होना चाहिए",
      explore: "मॉड्यूल देखें", lessons: "प्रश्न",
      catT: "देखें", catH: "सभी मॉड्यूल", catP: "मॉड्यूल चुनें और विषयवार अभ्यास करें",
      whyT: "क्यों", whyH: "COPA MCQ?", whyP: "ITI COPA परीक्षा पास करने के लिए सब कुछ",
      f: [["वास्तविक CBT परीक्षा", "टाइमर, प्रश्न पैलेट, रिव्यू मार्क और ऑटो-सबमिट – बिलकुल ITI ऑनलाइन परीक्षा जैसा।"],
          ["3 भाषाएँ", "हर प्रश्न English, हिंदी या मराठी में पढ़ें और कभी भी बदलें।"],
          ["तुरंत फीडबैक", "मॉड्यूलवार अभ्यास करें और तुरंत सही उत्तर देखें।"],
          ["विस्तृत विश्लेषण", "हर टेस्ट के बाद स्कोर, परिणाम और मॉड्यूलवार ताकत।"]],
      st: ["मॉड्यूल", "MCQ प्रश्न", "भाषाएँ", "नकारात्मक अंकन"],
      bandH: "स्मार्ट तैयारी करें। ITI COPA परीक्षा में अधिक अंक पाएँ!",
      bandP: "60 मिनट टाइमर के साथ 50 प्रश्नों का पूरा CBT मॉक टेस्ट दें और तुरंत परिणाम पाएँ।",
      bandB: "मॉक टेस्ट दें", bandB2: "अध्ययन सामग्री",
      progT: "आपकी प्रगति", tests: "दिए गए टेस्ट", avg: "औसत स्कोर", prac: "अभ्यास किए प्रश्न",
      footP: "ITI कंप्यूटर ऑपरेटर एवं प्रोग्रामिंग असिस्टेंट (COPA) प्रशिक्षुओं के लिए निःशुल्क ऑनलाइन लर्निंग प्लेटफ़ॉर्म।",
      fh: ["सीखें", "अभ्यास", "संस्थान"], fl1: ["सभी मॉड्यूल", "अध्ययन सामग्री", "पायथन", "जावास्क्रिप्ट"],
      fl2: ["मॉक टेस्ट (CBT)", "क्विक टेस्ट", "मेरे परिणाम", "डैशबोर्ड"], fl3: ["परिचय", "संपर्क", "COPA MCQ", "महाराष्ट्र"],
      by: "COPA MCQ", byR: "ITI COPA"
    },
    mr: {
      nav: ["होम", "मॉड्युल्स", "मॉक टेस्ट", "अभ्यास", "निकाल", "माहिती"],
      signin: "लॉगिन", start: "मॉक टेस्ट सुरू करा",
      h1a: "संगणक युगात शिकणे म्हणजे आजच", h1b: "उद्याच्या", h1c: "कौशल्यांचा सराव करणे",
      sub: "संपूर्ण ITI COPA प्रश्नपेढी – इंग्रजी, हिंदी आणि मराठीमध्ये 355 MCQ, प्रत्यक्ष CBT सारख्या मॉक टेस्टसह.",
      joinI: "सराव सुरू करा", joinS: "मॉक टेस्ट द्या", tagI: "COPA निदेशक", tagS: "ITI प्रशिक्षणार्थी",
      trust: ["सर्व प्रशिक्षणार्थ्यांसाठी मोफत", "निगेटिव्ह मार्किंग नाही", "ऑफलाइन व मोबाइलवर चालते"],
      popT: "आमचे सर्वात", popH: "लोकप्रिय मॉड्युल्स", popP: "प्रत्येक COPA प्रशिक्षणार्थ्याने आत्मसात करावीत अशी मॉड्युल्स",
      explore: "मॉड्युल्स पहा", lessons: "प्रश्न",
      catT: "पहा", catH: "सर्व मॉड्युल्स", catP: "मॉड्युल निवडा आणि विषयनिहाय सराव करा",
      whyT: "का", whyH: "COPA MCQ?", whyP: "ITI COPA परीक्षा उत्तीर्ण होण्यासाठी आवश्यक सर्व काही",
      f: [["प्रत्यक्ष CBT परीक्षा", "टायमर, प्रश्न पॅलेट, रिव्ह्यू मार्क आणि ऑटो-सबमिट – अगदी ITI ऑनलाइन परीक्षेसारखे."],
          ["3 भाषा", "प्रत्येक प्रश्न English, हिंदी किंवा मराठीत वाचा आणि कधीही बदला."],
          ["लगेच फीडबॅक", "मॉड्युलनिहाय सराव करा आणि लगेच बरोबर उत्तर पहा."],
          ["सविस्तर विश्लेषण", "प्रत्येक टेस्टनंतर गुण, निकाल आणि मॉड्युलनिहाय ताकद."]],
      st: ["मॉड्युल्स", "MCQ प्रश्न", "भाषा", "निगेटिव्ह मार्किंग"],
      bandH: "स्मार्ट तयारी करा. ITI COPA परीक्षेत जास्त गुण मिळवा!",
      bandP: "60 मिनिटांच्या टायमरसह 50 प्रश्नांची पूर्ण CBT मॉक टेस्ट द्या आणि लगेच निकाल मिळवा.",
      bandB: "मॉक टेस्ट द्या", bandB2: "अभ्यास साहित्य",
      progT: "तुमची प्रगती", tests: "दिलेल्या टेस्ट", avg: "सरासरी गुण", prac: "सराव केलेले प्रश्न",
      footP: "ITI कॉम्प्युटर ऑपरेटर अँड प्रोग्रामिंग असिस्टंट (COPA) प्रशिक्षणार्थ्यांसाठी मोफत ऑनलाइन लर्निंग प्लॅटफॉर्म.",
      fh: ["शिका", "सराव", "संस्था"], fl1: ["सर्व मॉड्युल्स", "अभ्यास साहित्य", "पायथन", "जावास्क्रिप्ट"],
      fl2: ["मॉक टेस्ट (CBT)", "क्विक टेस्ट", "माझे निकाल", "डॅशबोर्ड"], fl3: ["माहिती", "संपर्क", "COPA MCQ", "महाराष्ट्र"],
      by: "COPA MCQ", byR: "ITI COPA"
    }
  };
  const X = TX[L] || TX.en;
  C.renderShell("home");
  const prof = C.getProfile();
  const st = C.getStats();

  /* ---------- illustrations ---------- */
  function avatar(kind) {
    const inst = kind === "i";
    const bg = inst ? "#e9e4ff" : "#ffd84d";
    const skin = inst ? "#e8b48f" : "#8a5536";
    const hair = inst ? "#3b2a22" : "#1d1410";
    const shirt = inst ? "#7fc4e8" : "#cfe6f7";
    return '<svg viewBox="0 0 132 160" aria-hidden="true"><rect width="132" height="160" fill="' + bg + '"/>' +
      '<circle cx="' + (inst ? 105 : 25) + '" cy="28" r="34" fill="#fff" opacity=".35"/>' +
      '<path d="M18 160c2-30 22-46 48-46s46 16 48 46z" fill="' + shirt + '"/>' +
      (inst ? '<path d="M52 116l14 18 14-18" fill="#fff"/>' : '<path d="M48 116c6 10 30 10 36 0l-4 44H52z" fill="#3f7dc2"/>') +
      '<rect x="57" y="96" width="18" height="22" rx="8" fill="' + skin + '"/>' +
      '<ellipse cx="66" cy="72" rx="26" ry="31" fill="' + skin + '"/>' +
      (inst
        ? '<path d="M40 66c0-24 12-34 27-34s26 10 26 33c-4-10-10-15-26-15-14 0-22 6-27 16z" fill="' + hair + '"/><path d="M42 80c2 20 12 28 24 28s22-8 24-28c-4 8-10 11-24 11s-20-3-24-11z" fill="' + hair + '" opacity=".85"/>' +
          '<rect x="47" y="68" width="15" height="10" rx="4" fill="none" stroke="#2a2a2a" stroke-width="2"/><rect x="70" y="68" width="15" height="10" rx="4" fill="none" stroke="#2a2a2a" stroke-width="2"/><path d="M62 72h8" stroke="#2a2a2a" stroke-width="2"/>'
        : '<path d="M39 70c-2-26 12-40 28-40s30 12 27 40c-2-12-6-18-10-20-6 4-26 4-34-2-6 4-9 12-11 22z" fill="' + hair + '"/>' +
          '<circle cx="56" cy="73" r="2.6" fill="#1a1a1a"/><circle cx="76" cy="73" r="2.6" fill="#1a1a1a"/>') +
      '<path d="M57 90c5 5 13 5 18 0" stroke="' + (inst ? "#fff" : "#4a2a1a") + '" stroke-width="2.6" fill="none" stroke-linecap="round"/></svg>';
  }

  const modImg = (m) => C.ROOT + "assets/img/modules/m" + C.pad(m) + ".jpg";

  const FEATURED = [
    { m: 1, art: "computer", th: "blue", tag: "Basics", tc: "#16b364" },
    { m: 5, art: "doc", th: "lav", tag: "Office", tc: "#0ea5e9" },
    { m: 8, art: "db", th: "vio", tag: "Database", tc: "#ef4444" },
    { m: 10, art: "code", th: "indigo", tag: "Web", tc: "#7c5cff" },
    { m: 15, art: "python", th: "indigo", tag: "Python", tc: "#ff6b1a" },
    { m: 12, art: "chart", th: "pink", tag: "Excel", tc: "#3b82f6" }
  ];
  const CAT_COLORS = ["#3b82f6", "#0078d4", "#ef4444", "#334155", "#2b579a", "#16b364", "#f97316", "#0ea5e9", "#8b5cf6", "#ef6c3a", "#eab308", "#10b981", "#ff6b1a", "#06b6d4", "#3572a5"];

  const href = ["index.html", "modules.html", "exam.html", "study.html", "dashboard.html", "about.html"];
  const icons = ["fa-house", "fa-layer-group", "fa-stopwatch", "fa-book-open", "fa-chart-simple", "fa-circle-info"];
  const ringV = st.tests ? st.avg : (st.practiced ? Math.round((st.practicedCorrect / st.practiced) * 100) : 0);
  const stars = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>';

  document.getElementById("lp").innerHTML =
    /* HERO */
    '<section class="lp-hero"><div class="lp-wrap"><div class="lp-frame">' +
    '<span class="lp-deco q"></span><span class="lp-deco sq"></span>' +
    '<div class="lp-avas"><div class="lp-ava l"><div class="ph">' + avatar("i") + '</div><span class="tag"><i class="fa-solid fa-chalkboard-user"></i>' + X.tagI + "</span></div>" +
    '<div class="lp-ava r"><div class="ph">' + avatar("s") + '</div><span class="tag"><i class="fa-solid fa-graduation-cap"></i>' + X.tagS + "</span></div></div>" +
    '<h1 class="rv">' + esc(X.h1a) + ' <span class="hl-o">' + esc(X.h1b) + '</span><br><span class="hl-p sw">' + esc(X.h1c) +
    '<svg viewBox="0 0 70 40" aria-hidden="true"><path d="M66 6C50 30 26 34 6 26" stroke="#b9a8ff" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M14 18l-8 8 10 4" stroke="#b9a8ff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span></h1>' +
    '<p class="sub rv">' + esc(X.sub) + "</p>" +
    '<div class="lp-cta rv"><span class="lp-arrow l"></span><span class="lp-arrow r"></span>' +
    '<a class="lp-btn o" href="modules.html"><i class="fa-solid fa-pen-to-square"></i> ' + X.joinI + '</a><a class="lp-btn p" href="exam.html"><i class="fa-solid fa-stopwatch"></i> ' + X.joinS + "</a></div>" +
    '<div class="lp-trust rv">' + X.trust.map((s) => '<span><i class="fa-solid fa-circle-check"></i>' + s + "</span>").join("") + "</div>" +
    "</div>" +
    '<div class="lp-strip rv" aria-label="Topics covered"><span><i class="fa-brands fa-windows"></i>Windows</span><span><i class="fa-brands fa-linux"></i>Linux</span><span><i class="fa-brands fa-html5"></i>HTML5</span>' +
    '<span><i class="fa-brands fa-js"></i>JavaScript</span><span><i class="fa-brands fa-python"></i>Python</span><span><i class="fa-solid fa-database"></i>SQL</span><span><i class="fa-solid fa-cloud"></i>Cloud</span></div>' +
    "</div></section>" +

    /* POPULAR */
    '<section class="lp-sec tint"><div class="lp-wrap" style="position:relative">' +
    '<span class="lp-deco tri" style="left:46%;top:-18px"></span>' +
    '<div class="lp-head rv"><div><h2>' + X.popT + ' <span class="hl-p">' + X.popH + "</span></h2><p>" + X.popP + '</p></div><a class="lp-more" href="modules.html">' + X.explore + ' <i class="fa-solid fa-arrow-right" style="transform:rotate(-45deg)"></i></a></div>' +
    '<div class="lp-courses">' + FEATURED.map((f, i) => {
      const md = C.MODULES[f.m - 1];
      const n = C.byModule(f.m).length;
      return '<a class="lp-card rv" href="' + C.modulePage(f.m) + '" style="transition-delay:' + (i % 3) * 80 + 'ms"><div class="art"><img src="' + modImg(f.m) + '" alt="' + esc(C.modName(md)) + '" loading="lazy" width="960" height="540"></div>' +
        '<div class="bd"><div class="meta"><span class="ls"><i style="background:' + f.tc + '" class="fa-solid fa-circle-play"></i>' + n + " " + X.lessons + '</span><span class="stars">' + stars + "</span></div>" +
        "<h3>" + esc(C.modName(md)) + "</h3>" +
        '<div class="ft"><span class="who"><span class="av">' + X.by.charAt(0) + "</span><span>" + esc(X.by) + "<small>" + X.byR + " • M" + f.m + "</small></span></span>" +
        '<span class="lp-pill" style="background:' + f.tc + '">' + f.tag + "</span></div></div></a>";
    }).join("") + "</div></div></section>" +

    /* CATEGORIES */
    '<section class="lp-sec"><div class="lp-wrap" style="position:relative"><span class="lp-deco dot" style="right:22%;top:24px"></span>' +
    '<div class="lp-head center rv"><h2>' + X.catT + ' <span class="hl-p">' + X.catH + "</span></h2><p>" + X.catP + "</p></div>" +
    '<div class="lp-cats">' + C.MODULES.map((m, i) => '<a class="lp-cat rv" href="' + C.modulePage(m.id) + '" style="transition-delay:' + (i % 5) * 50 + 'ms"><span class="ci" style="background:' + CAT_COLORS[i] + ";--sh:" + CAT_COLORS[i] + '55"><i class="' + C.iconCls(m) + '"></i></span><b>' + esc(C.modName(m)) + "</b><small>M" + m.id + " • " + C.byModule(m.id).length + " " + X.lessons + "</small></a>").join("") +
    "</div></div></section>" +

    /* WHY */
    '<section class="lp-sec tint"><div class="lp-wrap"><div class="lp-head center rv"><h2>' + X.whyT + ' <span class="hl-p">' + X.whyH + "</span></h2><p>" + X.whyP + "</p></div>" +
    '<div class="lp-feat">' + X.f.map((f, i) => {
      const c = [["#f3f0ff", "#7c5cff", "fa-laptop-code"], ["#fff1e8", "#ff6b1a", "fa-language"], ["#e8f8ef", "#16b364", "fa-bolt"], ["#e8f2ff", "#3b82f6", "fa-chart-pie"]][i];
      return '<div class="lp-f rv"><span class="fi" style="background:' + c[0] + ";color:" + c[1] + '"><i class="fa-solid ' + c[2] + '"></i></span><h3>' + f[0] + "</h3><p>" + f[1] + "</p></div>";
    }).join("") + "</div>" +
    '<div class="lp-stats rv"><div><b>15</b><span>' + X.st[0] + "</span></div><div><b>" + C.QUESTIONS.length + "+</b><span>" + X.st[1] + "</span></div><div><b>3</b><span>" + X.st[2] + "</span></div><div><b>0</b><span>" + X.st[3] + "</span></div></div>" +
    "</div></section>" +

    /* CTA BAND */
    '<section class="lp-sec" style="padding-bottom:0"><div class="lp-wrap"><div class="lp-band rv"><div><h2>' + X.bandH + "</h2><p>" + X.bandP + "</p>" +
    '<div class="cta"><a class="lp-btn o" href="exam.html"><i class="fa-solid fa-play"></i> ' + X.bandB + '</a><a class="lp-btn w" href="study.html"><i class="fa-solid fa-book-open"></i> ' + X.bandB2 + "</a></div></div>" +
    '<div class="lp-prog"><h4><i class="fa-solid fa-chart-simple"></i> ' + X.progT + '</h4><div class="row"><div class="lp-ring" style="--v:' + ringV + '" data-l="' + ringV + '%"></div>' +
    "<ul><li>" + X.tests + ": <b>" + st.tests + "</b></li><li>" + X.avg + ": <b>" + st.avg + "%</b></li><li>" + X.prac + ": <b>" + st.practiced + "</b></li></ul></div>" +
    '<a class="lp-btn w sm" style="margin-top:16px;width:100%" href="dashboard.html">' + t("viewReport") + ' <i class="fa-solid fa-arrow-right"></i></a></div></div></div></section>' +

    /* FOOTER */
    '<footer class="lp-foot"><div class="lp-wrap"><div class="cols"><div><a class="lp-logo" href="index.html"><span class="mk"></span><span>COPA<b> MCQ</b>.</span></a><p>' + X.footP + "</p>" +
    '<div class="lp-soc"><a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a><a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a><a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></div></div>' +
    "<div><h5>" + X.fh[0] + "</h5><ul>" + [["modules.html", 0], ["study.html", 1], [C.modulePage(15), 2], [C.modulePage(11), 3]].map((a) => '<li><a href="' + a[0] + '">' + X.fl1[a[1]] + "</a></li>").join("") + "</ul></div>" +
    "<div><h5>" + X.fh[1] + "</h5><ul>" + [["exam.html", 0], ["exam.html", 1], ["dashboard.html", 2], ["dashboard.html", 3]].map((a) => '<li><a href="' + a[0] + '">' + X.fl2[a[1]] + "</a></li>").join("") + "</ul></div>" +
    "<div><h5>" + X.fh[2] + "</h5><ul>" + [["about.html", 0], ["about.html#contact", 1], ["about.html", 2], ["about.html", 3]].map((a) => '<li><a href="' + a[0] + '">' + X.fl3[a[1]] + "</a></li>").join("") + "</ul></div></div>" +
    '<div class="bot"><span>© ' + new Date().getFullYear() + " COPA MCQ. " + t("footerRights") + "</span><span>" + t("footerTag") + "</span></div></div></footer>";

  /* ---------- behaviour ---------- */
  const els = document.querySelectorAll(".rv");
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((ents) => ents.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: 0.12 });
    els.forEach((el) => io.observe(el));
  } else els.forEach((el) => el.classList.add("in"));
})();
