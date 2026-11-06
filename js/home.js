/* Home page */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc;
  C.renderShell("home");

  const prof = C.getProfile();
  const st = C.getStats();

  const heroArt =
    '<svg viewBox="0 0 420 330" role="img" aria-label="ITI COPA computer illustration">' +
    '<defs><linearGradient id="scr" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2a6df4"/><stop offset="1" stop-color="#0b1f4d"/></linearGradient>' +
    '<linearGradient id="bk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1a2b52"/><stop offset="1" stop-color="#0b1530"/></linearGradient></defs>' +
    '<ellipse cx="215" cy="305" rx="180" ry="16" fill="#0b1f4d" opacity=".18"/>' +
    '<rect x="70" y="20" width="300" height="200" rx="14" fill="#111b33"/>' +
    '<rect x="82" y="32" width="276" height="176" rx="8" fill="url(#scr)"/>' +
    '<path d="M82 160c60-40 140-70 276-60v108H82z" fill="#fff" opacity=".08"/>' +
    '<text x="220" y="112" text-anchor="middle" font-family="Poppins,sans-serif" font-weight="900" font-size="44" fill="#fff" font-style="italic">ITI</text>' +
    '<text x="220" y="162" text-anchor="middle" font-family="Poppins,sans-serif" font-weight="900" font-size="50" fill="#fff" font-style="italic">COPA</text>' +
    '<rect x="196" y="220" width="48" height="40" fill="#1c2948"/><rect x="150" y="258" width="140" height="12" rx="6" fill="#1c2948"/>' +
    '<rect x="105" y="276" width="220" height="22" rx="6" fill="#26355c"/>' +
    '<g fill="#3a4a75">' + Array.from({ length: 10 }, (_, i) => '<rect x="' + (112 + i * 21) + '" y="281" width="17" height="5" rx="1.5"/>').join("") +
    Array.from({ length: 10 }, (_, i) => '<rect x="' + (112 + i * 21) + '" y="289" width="17" height="5" rx="1.5"/>').join("") + "</g>" +
    '<ellipse cx="352" cy="290" rx="16" ry="10" fill="#26355c"/>' +
    '<g transform="translate(14 150) rotate(-8)"><rect width="96" height="130" rx="8" fill="url(#bk)"/><rect x="6" y="6" width="84" height="118" rx="5" fill="none" stroke="#ffc21a" stroke-width="1.5" opacity=".6"/>' +
    '<text x="48" y="74" text-anchor="middle" font-family="Poppins,sans-serif" font-weight="800" font-size="22" fill="#fff">COPA</text>' +
    '<text x="48" y="96" text-anchor="middle" font-family="Poppins,sans-serif" font-size="9" fill="#ffc21a">355 MCQ</text></g>' +
    '<g transform="translate(330 40)"><circle r="26" fill="#ffc21a"/><path d="M-10 0l7 7 14-14" stroke="#0b1f4d" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></g>' +
    "</svg>";

  const loginCard = prof
    ? '<div class="avatar"><i class="fa-solid fa-user-graduate"></i></div><div><h3>' + t("welcomeBack") + ", " + esc(prof.name.split(" ")[0]) + "!</h3><p>" +
      (prof.roll ? t("rollNo") + " " + esc(prof.roll) : t("accessDash")) + "</p></div>" +
      '<a class="btn btn-primary btn-block" href="results.html"><i class="fa-solid fa-gauge"></i> ' + t("dashboard") + "</a>" +
      '<div class="or">' + t("or") + '</div><a class="btn btn-outline btn-block" href="exam.html"><i class="fa-solid fa-play"></i> ' + t("takeMock") + "</a>"
    : '<div class="avatar"><i class="fa-solid fa-users"></i></div><div><h3>' + t("studentLogin") + "</h3><p>" + t("accessDash") + "</p></div>" +
      '<button class="btn btn-primary btn-block" id="heroLogin">' + t("login") + ' <i class="fa-solid fa-arrow-right"></i></button>' +
      '<div class="or">' + t("or") + '</div><button class="btn btn-outline btn-block" id="heroReg"><i class="fa-solid fa-user-plus"></i> ' + t("register") + "</button>";

  const stats = [
    { n: "15", l: t("statSubjects"), ic: "fa-graduation-cap", c: "#1554e0" },
    { n: C.QUESTIONS.length + "+", l: t("statQ"), ic: "fa-circle-question", c: "#7b3fe4" },
    { n: "∞", l: t("statMock"), ic: "fa-clipboard-list", c: "#18a957" },
    { n: "3", l: t("statLang"), ic: "fa-language", c: "#f58a07" }
  ];

  const subjects = C.MODULES.map((m) => {
    const p = C.moduleProgress(m.id);
    return '<a class="subject" href="' + C.modulePage(m.id) + '"><span class="s-no">M' + m.id + "</span>" +
      '<span class="s-ic" style="background:' + m.bg + ";color:" + m.color + '"><i class="' + (m.icon.indexOf("fa-brands") === 0 ? m.icon : "fa-solid " + m.icon) + '"></i></span>' +
      '<span class="s-name">' + esc(C.modName(m)) + "</span>" +
      '<span class="s-meta">' + p.total + " " + t("questions") + "</span>" +
      '<span class="bar"><i style="width:' + p.pct + '%"></i></span></a>';
  }).join("");

  const ringPct = st.tests ? st.avg : (st.practiced ? Math.round((st.practicedCorrect / st.practiced) * 100) : 0);

  document.getElementById("home").innerHTML =
    '<section class="hero">' +
    "<div><span class=\"hero-badge\">" + t("welcome") + "</span>" +
    '<h1>COPA <span>MCQ</span></h1><p class="lead">' + t("heroLead") + "</p>" +
    '<ul><li><i class="fa-solid fa-check"></i>' + t("f1") + '</li><li><i class="fa-solid fa-check"></i>' + t("f2") + '</li><li><i class="fa-solid fa-check"></i>' + t("f3") + "</li></ul>" +
    '<a class="btn btn-yellow" href="modules.html"><i class="fa-solid fa-circle-play"></i> ' + t("startPracticing") + ' <i class="fa-solid fa-arrow-right"></i></a></div>' +
    '<div class="hero-art"><span class="hero-tag">' + t("beExpert") + "</span>" + heroArt + "</div>" +
    '<div class="card login-card">' + loginCard + "</div></section>" +

    '<section class="stats">' + stats.map((s) => '<div class="card stat"><span class="ic" style="background:' + s.c + '"><i class="fa-solid ' + s.ic + '"></i></span><div><b>' + s.n + "</b><span>" + s.l + "</span></div></div>").join("") + "</section>" +

    '<div class="home-grid"><section><div class="section-title"><h2>' + t("explore") + '</h2><a href="modules.html">' + t("viewAll") + ' <i class="fa-solid fa-arrow-right"></i></a></div>' +
    '<div class="subject-grid">' + subjects + "</div></section>" +
    '<aside class="card card-pad progress-card" style="margin-top:30px"><h3><i class="fa-solid fa-chart-simple" style="color:var(--blue-600)"></i> ' + t("yourProgress") + "</h3>" +
    '<div class="progress-wrap"><div class="ring" style="--p:' + ringPct + '" data-label="' + ringPct + '%"></div>' +
    '<div class="p-list">' + t("testsAttempted") + "<b>" + st.tests + "</b>" + t("avgScore") + "<b>" + st.avg + "%</b>" + t("questionsPracticed") + "<b>" + st.practiced + "</b></div></div>" +
    '<a class="btn btn-primary btn-block" href="results.html">' + t("viewReport") + ' <i class="fa-solid fa-arrow-right"></i></a></aside></div>' +

    '<div class="bottom-grid"><section class="banner"><span class="target"><i class="fa-solid fa-bullseye"></i></span>' +
    "<h3>" + t("prepare1") + "<br>" + t("prepare2") + " <em>ITI COPA</em> " + t("prepare3") + "</h3>" +
    '<a class="btn btn-yellow" href="exam.html">' + t("takeMock") + ' <i class="fa-solid fa-arrow-right"></i></a></section>' +
    '<section class="card quick"><h3><i class="fa-solid fa-bolt" style="color:var(--yellow-600)"></i> ' + t("quickLinks") + '</h3><div class="quick-grid">' +
    '<a href="modules.html" style="background:var(--pink-100)"><i class="fa-solid fa-pen-to-square" style="color:var(--red)"></i>' + t("qPractice") + "</a>" +
    '<a href="exam.html" style="background:var(--green-100)"><i class="fa-solid fa-circle-play" style="color:var(--green)"></i>' + t("qTest") + "</a>" +
    '<a href="study.html" style="background:var(--purple-100)"><i class="fa-solid fa-book-open-reader" style="color:var(--purple)"></i>' + t("qNotes") + "</a>" +
    '<a href="results.html" style="background:var(--orange-100)"><i class="fa-solid fa-chart-column" style="color:var(--orange)"></i>' + t("qResults") + "</a>" +
    "</div></section></div>";

  const hl = document.getElementById("heroLogin"), hr = document.getElementById("heroReg");
  if (hl) hl.addEventListener("click", () => C.openLogin());
  if (hr) hr.addEventListener("click", () => C.openLogin());
})();
