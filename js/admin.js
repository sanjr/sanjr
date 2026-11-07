/* =========================================================
   COPA MCQ – Admin Panel
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, DB = window.COPA_DB, D = DB.driver, CFG = window.COPA_CONFIG || {};
  const esc = C.esc, root = document.getElementById("adm");
  const BUILTIN = (window.COPA_QUESTIONS || []).slice();
  const BUILTIN_IDS = {}; BUILTIN.forEach((q) => (BUILTIN_IDS[q.id] = q));
  const modName = (m) => (+m ? "M" + m + " – " + C.MODULES[m - 1].en : "General / All modules");
  const fmtDT = (d) => (d ? new Date(d).toLocaleString(undefined, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "—");
  const fmtD = (d) => (d ? new Date(d).toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" }) : "—");
  const isToday = (d) => d && new Date(d).toDateString() === new Date().toDateString();
  const toast = C.toast;

  let tab = (location.hash || "#overview").slice(1);
  let state = { results: [], students: [], live: [], custom: [], notes: [], settings: null, online: [] };
  let unwatch = null, liveTimer = null;

  /* ================= AUTH ================= */
  function loginView(err) {
    const fbMode = DB.mode === "firebase", myMode = DB.mode === "mysql";
    const first = !fbMode && !D.adminHasPassword();
    const modeTxt = fbMode ? "Online (Firebase)" : myMode ? "Online (MySQL database)" : "Local mode";
    const f1 = (id, label, type, extra) => '<div class="field"><label for="' + id + '">' + label + '</label><input class="input" id="' + id + '" type="' + type + '" required ' + (extra || "") + "></div>";
    root.innerHTML =
      '<div class="card adm-login"><img src="assets/img/logo.webp" alt="COPA MCQ logo" style="height:90px;width:auto;margin:0 auto 6px;display:block"><h2 style="margin:0">Admin Panel</h2>' +
      '<p style="color:var(--ink-3);margin:6px 0 0">COPA MCQ • ' + modeTxt + "</p>" +
      (first && myMode ? '<p style="font-size:13px;color:var(--ink-2);margin:12px 0 0;text-align:left"><i class="fa-solid fa-user-plus" style="color:var(--blue-600)"></i> First time: create the admin account. The <b>setup key</b> is the <code>setup_key</code> in <code>api/config.php</code> (or your MySQL password if it is empty).</p>' : "") +
      (err ? '<div class="auth-err" style="margin-top:14px"><i class="fa-solid fa-circle-exclamation"></i> ' + esc(err) + "</div>" : "") +
      '<form id="aLogin">' +
      (fbMode ? f1("aEmail", "Admin email", "email", 'autocomplete="username"') : "") +
      (myMode ? f1("aUser", first ? "Choose admin username" : "Username", "text", 'autocomplete="username" minlength="3" maxlength="60" autocapitalize="off" spellcheck="false"') : "") +
      f1("aPw", first ? "Create admin password (min " + (myMode ? 8 : 6) + " characters)" : "Password", "password", 'minlength="' + (first ? (myMode ? 8 : 6) : 1) + '" autocomplete="' + (first ? "new-password" : "current-password") + '"') +
      (first ? f1("aPw2", "Confirm password", "password", 'minlength="' + (myMode ? 8 : 6) + '" autocomplete="new-password"') : "") +
      (first && myMode ? f1("aKey", "Setup key", "password", 'autocomplete="off"') : "") +
      '<button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> ' + (first ? (myMode ? "Create admin & enter" : "Set password & enter") : "Login") + "</button></form>" +
      (DB.mode === "local" ? '<p style="font-size:12.5px;color:var(--ink-3);margin:16px 0 0;text-align:left"><i class="fa-solid fa-circle-info"></i> Local mode: data is only from this computer. Set <code>backend: "mysql"</code> (or add Firebase) in <code>js/site-config.js</code> to see all students live.</p>' : "") +
      '<p style="margin:14px 0 0"><a href="index.html"><i class="fa-solid fa-arrow-left"></i> Back to website</a></p></div>';
    const f = document.getElementById("aLogin");
    f.querySelector("input").focus();
    f.addEventListener("submit", (e) => {
      e.preventDefault();
      const pw = document.getElementById("aPw").value;
      const btn = f.querySelector("button[type=submit]"); btn.disabled = true;
      let p;
      if (first) {
        if (pw !== document.getElementById("aPw2").value) return loginView("Passwords do not match.");
        p = myMode ? D.adminSetup(document.getElementById("aUser").value.trim(), pw, document.getElementById("aKey").value) : D.adminSetPassword(pw);
      } else p = D.adminLogin(fbMode ? document.getElementById("aEmail").value.trim() : myMode ? document.getElementById("aUser").value.trim() : "", pw);
      Promise.resolve(p).then(() => start()).catch((er) => loginView(er.message || "Login failed"));
    });
  }

  /* ================= LOAD DATA ================= */
  function localExtras() {
    // local mode: also include accounts & full result histories saved by the student pages on this computer
    let users = {}; try { users = JSON.parse(localStorage.getItem("copa_users") || "{}"); } catch (e) { users = {}; }
    const students = state.students.slice(); const seen = {}; students.forEach((s) => (seen[s.uid] = 1));
    Object.keys(users).forEach((u) => { if (!seen[u]) students.push(Object.assign({ uid: u }, users[u], { pin: undefined })); });
    const results = state.results.slice(); const rs = {}; results.forEach((r) => (rs[r.id] = 1));
    Object.keys(users).forEach((u) => {
      let h = []; try { h = JSON.parse(localStorage.getItem("copa_results__" + u) || "[]"); } catch (e) { h = []; }
      h.forEach((r) => { if (!rs[r.id]) { rs[r.id] = 1; results.push({ id: r.id, date: r.date, title: r.title, uid: u, name: r.name, roll: r.roll, batch: users[u].batch || "", preset: r.cfg && r.cfg.preset, mods: r.cfg && r.cfg.mods, total: r.total, correct: r.correct, wrong: r.wrong, un: r.un, pct: r.pct, pass: r.pct >= C.PASS, timeTaken: r.timeTaken, per: r.per }); } });
    });
    results.sort((a, b) => b.date - a.date);
    state.students = students; state.results = results;
  }
  function loadAll() {
    return Promise.all([D.listResults(), D.listStudents(), D.listQuestions(), D.listNotes(), D.getSettings()])
      .then(([r, s, q, n, st]) => {
        state.results = (r || []).sort((a, b) => b.date - a.date);
        state.students = s || []; state.custom = q || []; state.notes = n || []; state.settings = st;
        if (DB.mode === "local") localExtras();
      });
  }

  /* ================= SHELL ================= */
  const TABS = [
    ["overview", "fa-gauge-high", "Live Dashboard"],
    ["visitors", "fa-earth-asia", "Visitors"],
    ["results", "fa-square-poll-vertical", "Exam Results"],
    ["students", "fa-user-graduate", "Students"],
    ["questions", "fa-circle-question", "Questions"],
    ["notes", "fa-book-open", "Study Material"],
    ["settings", "fa-rectangle-ad", "Ads & Settings"]
  ];
  function shell() {
    root.innerHTML =
      '<header class="adm-top"><div class="bar"><a class="brand" href="index.html" style="color:#fff"><img class="brand-logo" src="assets/img/logo.webp" width="256" height="205" alt="COPA MCQ logo">' +
      '<span class="brand-text"><span class="b2">COPA MCQ</span><span class="b3">Admin Panel</span></span></a><span class="adm-badge">ADMIN</span>' +
      '<span class="adm-mode ' + (DB.mode !== "local" ? "fb" : "") + '"><span class="dot"></span>' + (DB.mode === "firebase" ? "Online • Firebase" : DB.mode === "mysql" ? "Online • MySQL" : "Local mode • this computer") + "</span>" +
      '<span class="sp"></span><a class="view" href="index.html" target="_blank"><i class="fa-solid fa-up-right-from-square"></i><span>View site</span></a>' +
      '<button class="out" id="aOut"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></button></div>' +
      '<nav class="adm-tabs">' + TABS.map((x) => '<button data-tab="' + x[0] + '" class="' + (tab === x[0] ? "on" : "") + '"><i class="fa-solid ' + x[1] + '"></i>' + x[2] + (x[0] === "overview" ? ' <span class="cnt" id="liveCnt" hidden>0</span>' : "") + "</button>").join("") + "</nav></header>" +
      '<main class="adm-main" id="view"></main>';
    document.getElementById("aOut").addEventListener("click", () => D.adminLogout().then(() => location.reload()));
    root.querySelectorAll("[data-tab]").forEach((b) => b.addEventListener("click", () => {
      tab = b.getAttribute("data-tab"); history.replaceState(null, "", "#" + tab); shell(); draw();
      // online modes: fetch the newest results / students from the database when the tab is opened
      if (DB.mode !== "local" && (tab === "results" || tab === "students" || tab === "overview")) {
        const t0 = tab;
        Promise.all([D.listResults(), D.listStudents()]).then(([r, s]) => {
          const before = JSON.stringify([state.results.length, state.students.length]);
          state.results = (r || []).sort((a, b2) => b2.date - a.date); state.students = s || [];
          if (tab === t0 && before !== JSON.stringify([state.results.length, state.students.length])) draw();
        }).catch(() => {});
      }
    }));
  }
  function draw() {
    const v = document.getElementById("view");
    ({ overview, visitors, results, students, questions, notes, settings }[tab] || overview)(v);
  }
  function setLive(list) {
    state.live = (list || []).filter(DB.isLive).sort((a, b) => b.startAt - a.startAt);
    const c = document.getElementById("liveCnt");
    if (c) { c.textContent = state.live.length; c.hidden = !state.live.length; }
    if (tab === "overview") { const el = document.getElementById("liveBox"); if (el) el.innerHTML = liveTable(); const k = document.getElementById("liveKpi"); if (k) k.textContent = state.live.length; }
  }


  /* ================= VISITORS (MySQL mode) ================= */
  const flag = (cc) => (/^[A-Z]{2}$/.test(cc || "") ? String.fromCodePoint(...cc.split("").map((c) => 127397 + c.charCodeAt(0))) + " " : "");
  const place = (r) => { const p = [r.city, r.region, r.country].filter(Boolean); return p.length ? flag(r.cc) + esc(p.join(", ")) : '<span class="sub">Finding location…</span>'; };
  const devIc = (d) => '<i class="fa-solid fa-' + (d === "Mobile" ? "mobile-screen" : d === "Tablet" ? "tablet-screen-button" : "desktop") + '"></i>';
  const ago = (ts) => { const s = Math.max(0, Math.round((Date.now() - ts) / 1000)); return s < 60 ? s + " s" : s < 3600 ? Math.floor(s / 60) + " min" : Math.floor(s / 3600) + " h " + Math.floor((s % 3600) / 60) + " min"; };
  const pageName = (pg) => esc(String(pg || "/").replace(/^\/+/, "").replace(/\.html$/, "") || "home");
  function onlineTable(max) {
    const list = state.online.slice(0, max || 500);
    if (!list.length) return '<p class="empty" style="padding:16px"><i class="fa-solid fa-moon"></i> Nobody is on the website right now.</p>';
    return '<div class="table-wrap"><table class="tbl"><thead><tr><th>Location</th><th>Now on page</th><th>Student</th><th>Device</th><th>On site for</th></tr></thead><tbody>' +
      list.map((u) => "<tr><td>" + place(u) + "</td><td><b>" + pageName(u.page) + '</b><span class="sub">' + esc(u.title || "") + "</span></td><td>" +
        (u.name ? "<b>" + esc(u.name) + '</b><span class="sub">Roll ' + esc(u.roll || "") + "</span>" : '<span class="sub">Guest</span>') + "</td><td>" + devIc(u.device) + " " + esc(u.device || "") +
        '<span class="sub">' + esc([u.os, u.browser].filter(Boolean).join(" • ")) + "</span></td><td>" + ago(u.since) + "</td></tr>").join("") +
      (state.online.length > list.length ? '<tr><td colspan="5" class="sub">+ ' + (state.online.length - list.length) + " more…</td></tr>" : "") + "</tbody></table></div>";
  }
  let onlineTimer = null;
  function pollOnline() {
    if (DB.mode !== "mysql" || !D.adminOnline) return;
    D.adminOnline().then((list) => {
      state.online = list || [];
      const c = document.getElementById("onCnt"); if (c) c.textContent = state.online.length;
      const b = document.getElementById("onBox"); if (b) b.innerHTML = onlineTable(tab === "overview" ? 8 : 500);
      const k = document.getElementById("vzOn"); if (k) k.innerHTML = '<span class="pulse"></span>' + state.online.length;
    }).catch(() => {});
  }
  let vzDays = 7;
  function visitors(v) {
    if (DB.mode !== "mysql") {
      v.innerHTML = '<div class="adm-h"><div><h2>Visitors</h2><p>Where students visit from and who is online now</p></div></div><div class="card adm-card"><p><i class="fa-solid fa-circle-info"></i> The visitors report works with the <b>MySQL</b> backend. Set <code>backend: "mysql"</code> in <code>js/site-config.js</code> and fill in <code>api/config.php</code>.</p></div>';
      return;
    }
    const ranges = [[1, "Today"], [7, "7 days"], [30, "30 days"], [90, "90 days"]];
    v.innerHTML = '<div class="adm-h"><div><h2>Visitors</h2><p>Approximate location from the visitor\'s internet connection (city / state / country) • users online update every 10 s</p></div>' +
      '<div class="vz-range">' + ranges.map((r) => '<button class="btn btn-sm ' + (r[0] === vzDays ? "btn-primary" : "btn-ghost") + '" data-days="' + r[0] + '">' + r[1] + "</button>").join("") + "</div></div>" +
      '<div id="vzBody"><p class="empty"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</p></div>';
    v.querySelectorAll("[data-days]").forEach((b) => b.addEventListener("click", () => { vzDays = +b.dataset.days; visitors(v); }));
    D.adminVisitors(vzDays).then((d) => {
      state.online = state.online.length ? state.online : [];
      const S = d.summary, R = ranges.find((r) => r[0] === d.days), rl = R ? R[1] : d.days + " days";
      const top = (rows, label, fmt) => {
        const max = Math.max(1, ...rows.map((r) => +r.visitors));
        return '<section class="card adm-card"><h3>' + label + "</h3>" + (rows.length ? '<div class="vz-list">' + rows.map((r) => '<div class="vz-row"><span class="nm">' + fmt(r) + '</span><span class="tr"><i style="width:' + (r.visitors / max) * 100 + '%"></i></span><b>' + r.visitors + '</b><span class="sub">' + r.views + " views</span></div>").join("") + "</div>" : '<p class="sub">No data yet.</p>') + "</section>";
      };
      const dmax = Math.max(1, ...d.daily.map((x) => +x.visitors));
      document.getElementById("vzBody").innerHTML =
        '<div class="adm-grid4">' + kpi('<span class="pulse"></span>' + d.online, "Users online now", "fa-users", "", "live", "vzOn") +
        kpi(S.visitors, "Visitors (" + rl + ")", "fa-user-group", "#1554e0") + kpi(S.views, "Page views (" + rl + ")", "fa-eye", "#7b3fe4") + kpi(S.students, "Logged-in students (" + rl + ")", "fa-user-graduate", "#18a957") + "</div>" +
        '<section class="card adm-card" style="margin-bottom:18px"><h3><i class="fa-solid fa-users" style="color:#0fa3b1"></i> Users on the website now <span class="vz-count" id="onCnt">' + d.online + '</span></h3><div id="onBox">' + onlineTable(500) + "</div></section>" +
        '<section class="card adm-card" style="margin-bottom:18px"><h3><i class="fa-solid fa-chart-column" style="color:var(--blue-600)"></i> Visitors per day <span class="sub" style="margin-left:8px">today: ' + S.todayVisitors + " visitors • " + S.todayViews + ' views</span></h3>' +
        '<div class="vz-days">' + d.daily.map((x) => '<div class="d" title="' + esc(x.day) + ": " + x.visitors + " visitors, " + x.views + ' views"><b>' + x.visitors + '</b><i style="height:' + Math.max(4, (x.visitors / dmax) * 100) + '%"></i><span>' + esc(x.day.slice(8)) + "/" + esc(x.day.slice(5, 7)) + "</span></div>").join("") + "</div></section>" +
        '<div class="adm-grid2">' + top(d.cities, '<i class="fa-solid fa-city" style="color:var(--orange)"></i> Top cities', (r) => place(r)) +
        top(d.states, '<i class="fa-solid fa-map-location-dot" style="color:var(--purple)"></i> States', (r) => (r.region || r.country ? flag(r.cc) + esc([r.region, r.country].filter(Boolean).join(", ")) : '<span class="sub">Unknown</span>')) +
        top(d.countries, '<i class="fa-solid fa-earth-asia" style="color:#0fa3b1"></i> Countries', (r) => (r.country ? flag(r.cc) + esc(r.country) : '<span class="sub">Unknown</span>')) +
        top(d.pages, '<i class="fa-solid fa-file-lines" style="color:var(--blue-600)"></i> Pages', (r) => pageName(r.page)) +
        top(d.devices, '<i class="fa-solid fa-mobile-screen" style="color:var(--green)"></i> Devices', (r) => devIc(r.device) + " " + esc(r.device)) +
        top(d.refs, '<i class="fa-solid fa-link" style="color:var(--orange)"></i> Came from (other websites)', (r) => esc(r.ref)) +
        top(d.os, '<i class="fa-brands fa-android" style="color:#3ddc84"></i> Operating system', (r) => esc(r.os)) +
        top(d.browsers, '<i class="fa-brands fa-chrome" style="color:#4285f4"></i> Browser', (r) => esc(r.browser)) + "</div>" +
        '<section class="card adm-card" style="margin-top:18px"><h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--orange)"></i> Latest visits</h3><div class="table-wrap"><table class="tbl"><thead><tr><th>Time</th><th>Location</th><th>Page</th><th>Student</th><th>Device</th><th>From</th></tr></thead><tbody>' +
        (d.recent.length ? d.recent.map((r) => "<tr><td>" + fmtDT(+r.ts) + "</td><td>" + place(r) + "</td><td>" + pageName(r.page) + "</td><td>" + (r.name ? esc(r.name) : '<span class="sub">Guest</span>') + "</td><td>" + devIc(r.device) + " " + esc(r.device) + '<span class="sub">' + esc(r.os + " • " + r.browser) + "</span></td><td>" + (r.ref ? esc(r.ref) : '<span class="sub">direct</span>') + "</td></tr>").join("")
          : '<tr class="empty-row"><td colspan="6">No visits yet.</td></tr>') + "</tbody></table></div></section>";
      pollOnline();
    }).catch((er) => { document.getElementById("vzBody").innerHTML = '<div class="card adm-card"><p>Could not load visitors: ' + esc(er.message) + "</p></div>"; });
  }

  /* ================= OVERVIEW ================= */
  const kpi = (v, l, ic, c, cls, id) => '<div class="card kpi ' + (cls || "") + '"><span class="ic" style="background:' + c + '"><i class="fa-solid ' + ic + '"></i></span><div><b' + (id ? ' id="' + id + '"' : "") + ">" + v + "</b><span>" + l + "</span></div></div>";
  function liveTable() {
    if (!state.live.length) return '<p class="empty" style="padding:22px 0;margin:0"><i class="fa-solid fa-mug-hot" style="font-size:26px"></i><br>No student is taking an exam right now.</p>';
    return '<div class="table-wrap"><table class="tbl"><thead><tr><th>Student</th><th>Test</th><th>Progress</th><th>Question</th><th>Time left</th><th>Started</th></tr></thead><tbody>' +
      state.live.map((s) => {
        const pct = s.total ? Math.round((s.answered / s.total) * 100) : 0;
        return "<tr><td><b>" + esc(s.name || "Student") + '</b><span class="sub">Roll ' + esc(s.roll || "—") + (s.batch ? " • " + esc(s.batch) : "") + "</span></td><td>" + esc(s.title || "") + '</td><td><div style="display:flex;gap:8px;align-items:center"><div class="prog" style="flex:1"><i style="width:' + pct + '%"></i></div><b>' + (s.answered || 0) + "/" + (s.total || 0) + "</b></div></td><td>" + (s.cur || 1) + "</td><td>" + C.fmtTime(Math.max(0, (s.endAt - Date.now()) / 1000)) + "</td><td>" + new Date(s.startAt).toLocaleTimeString() + "</td></tr>";
      }).join("") + "</tbody></table></div>";
  }
  function moduleStats() {
    const m = {}; for (let i = 1; i <= 15; i++) m[i] = { mod: 0, mock: 0, pass: 0, tot: 0, c: 0, q: 0 };
    state.results.forEach((r) => {
      const isMod = r.preset === "module" && r.mods && r.mods.length === 1;
      if (isMod) { const x = m[r.mods[0]]; if (x) { x.mod++; if (r.pct >= C.PASS) x.pass++; } }
      (r.per || []).forEach((p) => { const x = m[p.m]; if (!x) return; if (!isMod) x.mock++; x.c += p.c; x.q += p.total; });
    });
    return m;
  }
  function overview(v) {
    const R = state.results, n = R.length, pass = R.filter((r) => r.pct >= C.PASS).length;
    const avg = n ? Math.round(R.reduce((s, r) => s + r.pct, 0) / n) : 0;
    const ms = moduleStats(); const maxC = Math.max(1, ...Object.values(ms).map((x) => x.mod + x.mock));
    const testers = {}; R.forEach((r) => (testers[r.uid || r.roll || r.name] = 1));
    v.innerHTML =
      '<div class="adm-h"><div><h2>Live Dashboard</h2><p>Updates automatically' + (DB.mode === "local" ? " (local mode – only this computer)" : "") + "</p></div>" +
      '<button class="btn btn-ghost btn-sm" id="rf"><i class="fa-solid fa-rotate"></i> Refresh</button></div>' +
      '<div class="adm-grid4">' +
      kpi('<span class="pulse"></span>' + state.live.length, "Students taking exam now", "fa-tower-broadcast", "", "live", "liveKpi") +
      kpi(state.students.length, "Registered students", "fa-user-graduate", "#1554e0") +
      kpi(n, "Exams taken (" + Object.keys(testers).length + " students)", "fa-clipboard-check", "#7b3fe4") +
      kpi(R.filter((r) => isToday(r.date)).length, "Exams today", "fa-calendar-day", "#f58a07") +
      kpi(pass, "Passed (≥ " + C.PASS + "%)", "fa-circle-check", "#18a957") +
      kpi(n - pass, "Failed", "fa-circle-xmark", "#e5383b") +
      kpi((n ? Math.round((pass / n) * 100) : 0) + "%", "Pass rate", "fa-flag-checkered", "#0fa3b1") +
      kpi(avg + "%", "Average score", "fa-percent", "#0b1f4d") + "</div>" +
      (DB.mode === "mysql" ? '<section class="card adm-card" style="margin-bottom:18px"><h3><i class="fa-solid fa-users" style="color:#0fa3b1"></i> Users on the website now <span class="vz-count" id="onCnt">' + state.online.length + '</span>' +
        '<button class="btn btn-ghost btn-sm" data-go="visitors" style="margin-left:auto">Visitors report <i class="fa-solid fa-arrow-right"></i></button></h3><div id="onBox">' + onlineTable(8) + "</div></section>" : "") +
      '<section class="card adm-card" style="margin-bottom:18px"><h3><span class="pulse" style="background:var(--green)"></span> Live exams</h3><div id="liveBox">' + liveTable() + "</div></section>" +
      '<div class="adm-grid2"><section class="card adm-card"><h3><i class="fa-solid fa-layer-group" style="color:var(--purple)"></i> Exams by module</h3>' +
      '<div class="adm-legend"><span style="--c:var(--blue-600)">Module tests</span><span style="--c:#9bb7ff">Included in mock tests</span></div><div class="bars">' +
      C.MODULES.map((md) => { const x = ms[md.id]; return '<div class="row"><span class="nm" title="' + esc(md.en) + '"><b>M' + md.id + "</b> " + esc(md.en) + '</span><span class="tr"><i style="width:' + (x.mod / maxC) * 100 + '%;background:var(--blue-600)"></i><i style="width:' + (x.mock / maxC) * 100 + '%;background:#9bb7ff"></i></span><span class="v">' + x.mod + " / " + x.mock + "</span></div>"; }).join("") +
      "</div></section>" +
      '<section class="card adm-card"><h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--orange)"></i> Latest results</h3>' + resultsTable(R.slice(0, 8), true) +
      '<button class="btn btn-ghost btn-sm" style="margin-top:12px" data-go="results">All results <i class="fa-solid fa-arrow-right"></i></button></section></div>';
    v.querySelector("#rf").addEventListener("click", () => loadAll().then(draw));
    v.querySelectorAll("[data-go]").forEach((b) => b.addEventListener("click", () => { tab = b.getAttribute("data-go"); history.replaceState(null, "", "#" + tab); shell(); draw(); }));
  }

  /* ================= RESULTS ================= */
  function resultsTable(rows, compact) {
    if (!rows.length) return '<div class="table-wrap"><table class="tbl"><tbody><tr class="empty-row"><td>No results yet.</td></tr></tbody></table></div>';
    return '<div class="table-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Student</th><th>Test</th>' + (compact ? "" : "<th>Correct</th><th>Wrong</th><th>Time</th>") + "<th>Score</th><th>Status</th>" + (compact ? "" : "<th></th>") + "</tr></thead><tbody>" +
      rows.map((r) => {
        const ok = r.pct >= C.PASS;
        return "<tr><td>" + fmtDT(r.date) + "</td><td><b>" + esc(r.name || "—") + '</b><span class="sub">Roll ' + esc(r.roll || "—") + (r.batch ? " • " + esc(r.batch) : "") + "</span></td><td>" + esc(r.title || "") + "</td>" +
          (compact ? "" : '<td style="color:var(--green);font-weight:600">' + r.correct + '</td><td style="color:var(--red);font-weight:600">' + r.wrong + "</td><td>" + C.fmtTime(r.timeTaken || 0) + "</td>") +
          "<td><b>" + r.correct + "/" + r.total + "</b> (" + r.pct + "%)</td><td><span class=\"chip " + (ok ? "green" : "red") + '">' + (ok ? "PASS" : "FAIL") + "</span></td>" +
          (compact ? "" : '<td><button class="icon-btn del" data-delres="' + esc(r.id) + '" title="Delete"><i class="fa-solid fa-trash"></i></button></td>') + "</tr>";
      }).join("") + "</tbody></table></div>";
  }
  function results(v) {
    v.innerHTML = '<div class="adm-h"><div><h2>Exam Results</h2><p>All submitted tests • pass mark ' + C.PASS + '%</p></div><button class="btn btn-ghost btn-sm" id="csvR"><i class="fa-solid fa-file-csv"></i> Export CSV</button></div>' +
      '<div class="adm-tools"><input class="input grow" id="fS" placeholder="Search name, roll no. or test"><select class="input" id="fM"><option value="">All modules</option>' + C.MODULES.map((m) => '<option value="' + m.id + '">M' + m.id + " " + esc(m.en) + "</option>").join("") + '</select>' +
      '<select class="input" id="fT"><option value="">All tests</option><option value="module">Module tests</option><option value="mock">Mock tests</option></select><select class="input" id="fP"><option value="">Pass & Fail</option><option value="p">Pass</option><option value="f">Fail</option></select>' +
      '<select class="input" id="fD"><option value="">Any date</option><option value="1">Today</option><option value="7">Last 7 days</option><option value="30">Last 30 days</option></select></div>' +
      '<div class="card" id="rT"></div>';
    const get = () => {
      const s = v.querySelector("#fS").value.trim().toLowerCase(), m = +v.querySelector("#fM").value, ty = v.querySelector("#fT").value, p = v.querySelector("#fP").value, d = +v.querySelector("#fD").value;
      return state.results.filter((r) => {
        if (s && (r.name + " " + r.roll + " " + r.title).toLowerCase().indexOf(s) < 0) return false;
        if (m && !(r.mods || []).includes(m) && !(r.per || []).some((x) => x.m === m)) return false;
        if (ty === "module" && r.preset !== "module") return false;
        if (ty === "mock" && r.preset === "module") return false;
        if (p === "p" && r.pct < C.PASS) return false;
        if (p === "f" && r.pct >= C.PASS) return false;
        if (d === 1 && !isToday(r.date)) return false;
        if (d > 1 && Date.now() - r.date > d * 86400000) return false;
        return true;
      });
    };
    const render = () => {
      const rows = get();
      v.querySelector("#rT").innerHTML = '<div style="padding:12px 16px;font-size:13px;color:var(--ink-3)">' + rows.length + " results • " + rows.filter((r) => r.pct >= C.PASS).length + " pass • " + rows.filter((r) => r.pct < C.PASS).length + " fail</div>" + resultsTable(rows);
      v.querySelectorAll("[data-delres]").forEach((b) => b.addEventListener("click", () => {
        if (!confirm("Delete this result?")) return;
        const id = b.getAttribute("data-delres");
        D.deleteResult(id).then(() => { state.results = state.results.filter((r) => r.id !== id); render(); toast("Result deleted"); });
      }));
    };
    v.querySelectorAll(".adm-tools input, .adm-tools select").forEach((el) => el.addEventListener("input", render));
    v.querySelector("#csvR").addEventListener("click", () => csv("copa-results.csv", ["Date", "Name", "Roll", "Batch", "Test", "Questions", "Correct", "Wrong", "Unattempted", "Percent", "Status", "Time (s)"],
      get().map((r) => [new Date(r.date).toLocaleString(), r.name, r.roll, r.batch, r.title, r.total, r.correct, r.wrong, r.un, r.pct, r.pct >= C.PASS ? "PASS" : "FAIL", r.timeTaken])));
    render();
  }

  /* ================= STUDENTS ================= */
  function students(v) {
    const by = {};
    state.results.forEach((r) => { const k = r.uid || String(r.roll || "").toLowerCase(); (by[k] = by[k] || []).push(r); });
    const rows = state.students.map((s) => {
      const rs = by[s.uid] || by[String(s.roll || "").toLowerCase()] || [];
      return Object.assign({}, s, { tests: rs.length, avg: rs.length ? Math.round(rs.reduce((a, r) => a + r.pct, 0) / rs.length) : 0, best: rs.length ? Math.max(...rs.map((r) => r.pct)) : 0, pass: rs.filter((r) => r.pct >= C.PASS).length, last: rs.length ? rs[0].date : 0 });
    }).sort((a, b) => (b.lastLogin || 0) - (a.lastLogin || 0));
    v.innerHTML = '<div class="adm-h"><div><h2>Students</h2><p>' + rows.length + ' registered students</p></div><button class="btn btn-ghost btn-sm" id="csvS"><i class="fa-solid fa-file-csv"></i> Export CSV</button></div>' +
      '<div class="adm-tools"><input class="input grow" id="sS" placeholder="Search name, roll no. or batch"></div><div class="card" id="sT"></div>';
    const render = () => {
      const s = v.querySelector("#sS").value.trim().toLowerCase();
      const list = rows.filter((r) => !s || (r.name + " " + r.roll + " " + (r.batch || "")).toLowerCase().indexOf(s) > -1);
      v.querySelector("#sT").innerHTML = '<div class="table-wrap"><table class="tbl"><thead><tr><th>Student</th><th>Batch</th><th>Registered</th><th>Last login</th><th>Tests</th><th>Passed</th><th>Average</th><th>Best</th>' + (DB.mode === "mysql" ? "<th>PIN</th>" : "") + "</tr></thead><tbody>" +
        (list.length ? list.map((r) => "<tr><td><b>" + esc(r.name || "—") + '</b><span class="sub">Roll ' + esc(r.roll || "—") + "</span></td><td>" + esc(r.batch || "—") + "</td><td>" + fmtD(r.created) + "</td><td>" + fmtDT(r.lastLogin) + "</td><td><b>" + r.tests + '</b></td><td style="color:var(--green);font-weight:600">' + r.pass + "</td><td>" + r.avg + "%</td><td>" + r.best + "%</td>" + (DB.mode === "mysql" ? '<td><button class="btn btn-ghost btn-sm" data-pin="' + esc(r.uid) + '" data-nm="' + esc(r.name || r.roll || "") + '" title="Student forgot the PIN"><i class="fa-solid fa-key"></i> Reset</button></td>' : "") + "</tr>").join("")
          : '<tr class="empty-row"><td colspan="9">No students yet.</td></tr>') + "</tbody></table></div>";
    };
    v.querySelector("#sS").addEventListener("input", render);
    v.querySelector("#sT").addEventListener("click", (e) => {
      const b = e.target.closest("[data-pin]"); if (!b) return;
      const pin = prompt("New 4-digit PIN for " + b.dataset.nm + " (tell it to the student):", String(Math.floor(1000 + Math.random() * 9000)));
      if (pin == null) return;
      if (!/^\d{4}$/.test(pin)) return toast("PIN must be 4 digits");
      D.adminStudentPin(b.dataset.pin, pin).then(() => toast("PIN changed – the student is logged out on all devices")).catch((er) => toast("Error: " + er.message));
    });
    v.querySelector("#csvS").addEventListener("click", () => csv("copa-students.csv", ["Name", "Roll", "Batch", "Registered", "Last login", "Tests", "Passed", "Average %", "Best %"],
      rows.map((r) => [r.name, r.roll, r.batch, fmtD(r.created), fmtDT(r.lastLogin), r.tests, r.pass, r.avg, r.best])));
    render();
  }

  /* ================= QUESTIONS ================= */
  function mergedQuestions() {
    const over = {}; state.custom.forEach((c) => (over[c.id] = c));
    const list = BUILTIN.map((q) => { const o = over[q.id]; return o ? Object.assign({ _status: o.hidden ? "hid" : "edit" }, o.hidden ? q : C.normQ(o), { hidden: !!o.hidden }) : Object.assign({ _status: "" }, q); });
    state.custom.filter((c) => !BUILTIN_IDS[c.id]).forEach((c) => list.push(Object.assign({ _status: "new" }, C.normQ(c))));
    return list;
  }
  function afterContentChange(msg) {
    toast(msg);
    return D.listQuestions().then((q) => { state.custom = q || []; return DB.refreshCache(true); });
  }
  function questions(v) {
    v.innerHTML = '<div class="adm-h"><div><h2>Questions</h2><p>Add new MCQs, edit or hide existing ones. Changes appear on the website for students.</p></div>' +
      '<div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-ghost btn-sm" id="qExp"><i class="fa-solid fa-download"></i> Export</button><label class="btn btn-ghost btn-sm" style="cursor:pointer"><i class="fa-solid fa-upload"></i> Import<input type="file" id="qImp" accept=".json" hidden></label>' +
      '<button class="btn btn-primary btn-sm" id="qAdd"><i class="fa-solid fa-plus"></i> Add question</button></div></div>' +
      '<div class="adm-tools"><input class="input grow" id="qS" placeholder="Search question text"><select class="input" id="qM"><option value="">All modules</option>' + C.MODULES.map((m) => '<option value="' + m.id + '">M' + m.id + " " + esc(m.en) + "</option>").join("") + '</select>' +
      '<select class="input" id="qF"><option value="">All</option><option value="new">Added by admin</option><option value="edit">Edited</option><option value="hid">Hidden</option></select></div><div class="card" id="qL"></div>';
    const render = () => {
      const s = v.querySelector("#qS").value.trim().toLowerCase(), m = +v.querySelector("#qM").value, f = v.querySelector("#qF").value;
      const all = mergedQuestions();
      const list = all.filter((q) => (!m || q.m === m) && (!f || q._status === f) && (!s || (q.en.q + " " + q.en.o.join(" ")).toLowerCase().indexOf(s) > -1));
      const shown = list.slice(0, 200);
      v.querySelector("#qL").innerHTML = '<div style="padding:12px 16px;font-size:13px;color:var(--ink-3);border-bottom:1px solid var(--line)">' + list.length + " of " + all.length + " questions" + (list.length > 200 ? " (showing first 200 – use search/filter)" : "") + "</div>" +
        (shown.length ? shown.map((q) => '<div class="q-row"><div class="qi"><b>M' + q.m + " • " + esc(q.id) + "</b>" +
          (q._status === "new" ? '<span class="badge new">NEW</span>' : q._status === "edit" ? '<span class="badge edit">EDITED</span>' : q._status === "hid" ? '<span class="badge hid">HIDDEN</span>' : "") +
          '<div style="margin-top:4px">' + esc(q.en.q) + "</div>" + '<div class="ans"><i class="fa-solid fa-check"></i> ' + "ABCD"[q.a] + ". " + esc(q.en.o[q.a]) + "</div></div>" +
          '<div class="acts"><button class="icon-btn" data-ed="' + esc(q.id) + '" title="Edit"><i class="fa-solid fa-pen"></i></button>' +
          (BUILTIN_IDS[q.id] ? '<button class="icon-btn" data-hide="' + esc(q.id) + '" title="' + (q._status === "hid" ? "Show" : "Hide") + '"><i class="fa-solid fa-eye' + (q._status === "hid" ? "" : "-slash") + '"></i></button>' : "") +
          (q._status ? '<button class="icon-btn del" data-del="' + esc(q.id) + '" title="' + (BUILTIN_IDS[q.id] ? "Restore original" : "Delete") + '"><i class="fa-solid fa-' + (BUILTIN_IDS[q.id] ? "rotate-left" : "trash") + '"></i></button>' : "") + "</div></div>").join("")
          : '<p class="empty">No questions found.</p>');
      v.querySelectorAll("[data-ed]").forEach((b) => b.addEventListener("click", () => qForm(all.find((q) => q.id === b.getAttribute("data-ed")), render)));
      v.querySelectorAll("[data-hide]").forEach((b) => b.addEventListener("click", () => {
        const id = b.getAttribute("data-hide"), cur = state.custom.find((c) => c.id === id);
        const p = cur && cur.hidden ? D.deleteQuestion(id) : D.saveQuestion({ id, hidden: true, m: BUILTIN_IDS[id].m, updated: Date.now() });
        p.then(() => afterContentChange(cur && cur.hidden ? "Question visible again" : "Question hidden from students")).then(render);
      }));
      v.querySelectorAll("[data-del]").forEach((b) => b.addEventListener("click", () => {
        const id = b.getAttribute("data-del");
        if (!confirm(BUILTIN_IDS[id] ? "Restore the original question?" : "Delete this question?")) return;
        D.deleteQuestion(id).then(() => afterContentChange(BUILTIN_IDS[id] ? "Original restored" : "Question deleted")).then(render);
      }));
    };
    v.querySelectorAll(".adm-tools input, .adm-tools select").forEach((el) => el.addEventListener("input", render));
    v.querySelector("#qAdd").addEventListener("click", () => qForm(null, render));
    v.querySelector("#qExp").addEventListener("click", () => download("copa-custom-questions.json", JSON.stringify(state.custom, null, 2), "application/json"));
    v.querySelector("#qImp").addEventListener("change", (e) => {
      const f = e.target.files[0]; if (!f) return;
      f.text().then((txt) => {
        let arr; try { arr = JSON.parse(txt); } catch (er) { return toast("Invalid JSON file"); }
        if (!Array.isArray(arr)) return toast("File must contain a list of questions");
        const ok = arr.filter((q) => q && q.id && (q.hidden || (q.en && q.en.q && q.en.o && q.en.o.length === 4)));
        Promise.all(ok.map((q) => D.saveQuestion(q))).then(() => afterContentChange(ok.length + " questions imported")).then(render);
      });
    });
    render();
  }
  function qForm(q, done) {
    const isNew = !q;
    q = q || { id: "c-" + DB.uid(), m: +((document.getElementById("qM") || {}).value) || 1, a: 0, en: { q: "", o: ["", "", "", ""] }, hi: { q: "", o: ["", "", "", ""] }, mr: { q: "", o: ["", "", "", ""] }, x: { en: "", hi: "", mr: "" }, code: "" };
    const same = (l) => q[l] && q.en && q[l].q === q.en.q && !isNew; // translation copied from English → leave empty in form
    const val = (l, k, i) => { if (l !== "en" && same(l)) return ""; const L = q[l] || {}; return esc(i == null ? L[k] || "" : (L.o || [])[i] || ""); };
    const block = (l, title, req) => '<div class="lang-block"><h4>' + title + (req ? "" : " <small>optional – English is used if empty</small>") + "</h4>" +
      '<div class="field"><label>Question</label><textarea class="input" data-l="' + l + '" data-k="q" ' + (req ? "required" : "") + ">" + val(l, "q") + "</textarea></div>" +
      [0, 1, 2, 3].map((i) => '<div class="opt-in"><span>' + "ABCD"[i] + '</span><input class="input" data-l="' + l + '" data-k="o" data-i="' + i + '" value="' + val(l, "o", i) + '" ' + (req ? "required" : "") + "></div>").join("") +
      '<div class="field" style="margin:6px 0 0"><label>One-line explanation of the answer</label><input class="input" data-l="' + l + '" data-k="x" value="' + esc((q.x && q.x[l]) || "") + '"></div></div>';
    const m = C.modal(
      '<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button><h3><i class="fa-solid fa-' + (isNew ? "plus" : "pen") + '" style="color:var(--blue-600)"></i> ' + (isNew ? "Add question" : "Edit question " + esc(q.id)) + "</h3>" +
      '<form id="qf"><div class="form-grid"><div class="field"><label>Module</label><select class="input" id="qfM">' + C.MODULES.map((md) => '<option value="' + md.id + '"' + (md.id === q.m ? " selected" : "") + ">M" + md.id + " " + esc(md.en) + "</option>").join("") + "</select></div>" +
      '<div class="field"><label>Correct answer</label><div class="ans-pick">' + [0, 1, 2, 3].map((i) => '<label><input type="radio" name="qa" value="' + i + '"' + (i === q.a ? " checked" : "") + "><span>" + "ABCD"[i] + "</span></label>").join("") + "</div></div>" +
      '<div class="field full"><label>Code / table (optional, shown in a code box)</label><textarea class="input" id="qfCode" style="font-family:Consolas,monospace">' + esc(q.code || "") + "</textarea></div></div>" +
      block("en", "English", true) + block("hi", "हिंदी (Hindi)", false) + block("mr", "मराठी (Marathi)", false) +
      '<div class="modal-acts"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save question</button></div></form>', { wide: true, sticky: true });
    m.el.querySelector("#qf").addEventListener("submit", (e) => {
      e.preventDefault();
      const out = { id: q.id, m: +m.el.querySelector("#qfM").value, a: +m.el.querySelector('input[name="qa"]:checked').value, en: { q: "", o: [] }, hi: { q: "", o: [] }, mr: { q: "", o: [] }, x: {}, updated: Date.now() };
      m.el.querySelectorAll("[data-l]").forEach((el) => {
        const l = el.getAttribute("data-l"), k = el.getAttribute("data-k"), v = el.value.trim();
        if (k === "q") out[l].q = v; else if (k === "o") out[l].o[+el.getAttribute("data-i")] = v; else out.x[l] = v;
      });
      const code = m.el.querySelector("#qfCode").value.replace(/\s+$/, ""); if (code) out.code = code;
      ["hi", "mr"].forEach((l) => { if (!out[l].q && !out[l].o.some(Boolean)) delete out[l]; });
      if (!out.x.en && !out.x.hi && !out.x.mr) delete out.x;
      if (q.img) out.img = q.img;
      D.saveQuestion(out).then(() => { m.close(); return afterContentChange(isNew ? "Question added" : "Question saved"); }).then(done).catch((er) => toast("Error: " + er.message));
    });
  }

  /* ================= STUDY MATERIAL ================= */
  function notes(v) {
    const list = state.notes.slice().sort((a, b) => (a.m - b.m) || ((a.order || 0) - (b.order || 0)));
    v.innerHTML = '<div class="adm-h"><div><h2>Study Material</h2><p>Notes and links (PDF, video, Google Drive) shown on the Study Material page and in each module.</p></div><button class="btn btn-primary btn-sm" id="nAdd"><i class="fa-solid fa-plus"></i> Add study material</button></div>' +
      '<div class="card">' + (list.length ? list.map((n) => '<div class="q-row"><div class="qi"><b>' + esc(n.title) + "</b>" + (n.hidden ? '<span class="badge hid">HIDDEN</span>' : "") + '<span class="sub" style="display:block;font-size:12.5px;color:var(--ink-3)">' + esc(modName(n.m)) + (n.link ? ' • <a href="' + esc(n.link) + '" target="_blank" rel="noopener">' + esc(n.linkLabel || n.link) + "</a>" : "") + "</span>" +
        '<div style="margin-top:4px;font-size:13.5px;color:var(--ink-2)">' + esc(plain(n).slice(0, 180)) + (plain(n).length > 180 ? "…" : "") + "</div></div>" +
        '<div class="acts"><button class="icon-btn" data-ned="' + esc(n.id) + '" title="Edit"><i class="fa-solid fa-pen"></i></button><button class="icon-btn del" data-ndel="' + esc(n.id) + '" title="Delete"><i class="fa-solid fa-trash"></i></button></div></div>').join("")
        : '<p class="empty"><i class="fa-solid fa-book-open" style="font-size:30px"></i><br>No study material yet. Click “Add study material”.</p>') + "</div>";
    const refresh = (msg) => { toast(msg); D.listNotes().then((n) => { state.notes = n || []; return DB.refreshCache(true); }).then(() => notes(v)); };
    v.querySelector("#nAdd").addEventListener("click", () => nForm(null, refresh));
    v.querySelectorAll("[data-ned]").forEach((b) => b.addEventListener("click", () => nForm(state.notes.find((n) => n.id === b.getAttribute("data-ned")), refresh)));
    v.querySelectorAll("[data-ndel]").forEach((b) => b.addEventListener("click", () => { if (confirm("Delete this study material?")) D.deleteNote(b.getAttribute("data-ndel")).then(() => refresh("Deleted")); }));
  }
  const plain = (n) => { if (n.fmt !== "html") return n.body || ""; const d = document.createElement("div"); d.innerHTML = C.sanitizeHTML(n.body); return (d.textContent || "").replace(/\s+/g, " ").trim(); };
  function nForm(n, done) {
    const isNew = !n;
    n = n || { id: "n-" + DB.uid(), m: 0, title: "", body: "", link: "", linkLabel: "", order: 0, created: Date.now() };
    const m = C.modal('<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button><h3><i class="fa-solid fa-book-open" style="color:var(--purple)"></i> ' + (isNew ? "Add" : "Edit") + " study material</h3>" +
      '<form id="nf"><div class="form-grid"><div class="field"><label>Module</label><select class="input" id="nfM"><option value="0">General / All modules</option>' + C.MODULES.map((md) => '<option value="' + md.id + '"' + (md.id === +n.m ? " selected" : "") + ">M" + md.id + " " + esc(md.en) + "</option>").join("") + "</select></div>" +
      '<div class="field"><label>Order (small numbers first)</label><input class="input" id="nfO" type="number" value="' + (+n.order || 0) + '"></div>' +
      '<div class="field full"><label>Title *</label><input class="input" id="nfT" required maxlength="140" value="' + esc(n.title) + '"></div>' +
      '<div class="field full"><label>Notes (any language) – format like MS Word: fonts, size, colours, tables, pictures, margins…</label><div id="nfB"></div></div>' +
      '<div class="field"><label>Link (PDF / Google Drive / YouTube) – optional</label><input class="input" id="nfL" type="url" placeholder="https://…" value="' + esc(n.link) + '"></div>' +
      '<div class="field"><label>Link button text</label><input class="input" id="nfLL" maxlength="40" placeholder="Download PDF" value="' + esc(n.linkLabel) + '"></div>' +
      '<label class="switch full"><input type="checkbox" id="nfH"' + (n.hidden ? " checked" : "") + "> Hide from students</label></div>" +
      '<div class="modal-acts"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button></div></form>', { wide: true, sticky: true });
    m.el.querySelector(".modal").classList.add("modal-xl");
    const rte = window.COPA_RTE ? window.COPA_RTE.create(m.el.querySelector("#nfB"), { html: C.noteHTML(n), margin: n.margin }) : null;
    m.el.querySelector("#nf").addEventListener("submit", (e) => {
      e.preventDefault();
      const body = rte ? rte.getHTML() : "";
      if (body.length > 900000) { toast("Notes are too large (" + Math.round(body.length / 1024) + " KB). Use fewer or smaller pictures, or add a PDF link."); return; }
      const out = { id: n.id, m: +m.el.querySelector("#nfM").value, order: +m.el.querySelector("#nfO").value || 0, title: m.el.querySelector("#nfT").value.trim(), body, fmt: "html", margin: rte ? rte.getMargin() : "normal",
        link: m.el.querySelector("#nfL").value.trim(), linkLabel: m.el.querySelector("#nfLL").value.trim(), hidden: m.el.querySelector("#nfH").checked, created: n.created || Date.now(), updated: Date.now() };
      D.saveNote(out).then(() => { m.close(); done(isNew ? "Study material added" : "Saved"); }).catch((er) => toast("Error: " + er.message));
    });
  }

  /* ================= ADS & SETTINGS ================= */
  function currentAds() {
    const base = CFG.adsense || {}, s = (state.settings && state.settings.adsense) || {};
    return { enabled: s.enabled != null ? !!s.enabled : !!base.enabled, client: s.client || base.client || "", autoAds: s.autoAds != null ? !!s.autoAds : base.autoAds !== false, slots: Object.assign({ top: "", middle: "", bottom: "" }, base.slots || {}, s.slots || {}) };
  }
  function settings(v) {
    const A = currentAds(), site = (state.settings && state.settings.siteUrl) || CFG.siteUrl || location.origin + location.pathname.replace(/admin\.html.*$/, "");
    const pub = A.client.replace(/^ca-/, "");
    const checks = [
      [true, "Original, useful content – 355 COPA MCQs, study material, mock tests"],
      [true, "About, Contact, Privacy Policy and Terms pages are published"],
      [true, "Site works on mobile and is easy to navigate"],
      [/^ca-pub-\d{10,20}$/.test(A.client), "AdSense publisher ID entered below"],
      [false, "Upload ads.txt to the root of your domain (button below) – check this yourself"],
      [false, "Use your own domain (e.g. copamcq.in). Free sub-domains are often not approved – check this yourself"]
    ];
    v.innerHTML = '<div class="adm-h"><div><h2>Ads & Settings</h2><p>Google AdSense – earn revenue from ads on the website</p></div></div>' +
      '<div class="adm-grid2"><section class="card adm-card"><h3><i class="fa-brands fa-google" style="color:#4285f4"></i> Google AdSense</h3>' +
      '<form id="sf"><label class="switch" style="margin-bottom:14px"><input type="checkbox" id="sEn"' + (A.enabled ? " checked" : "") + "> Show ads on the website</label>" +
      '<div class="field"><label for="sCl">Publisher ID</label><input class="input" id="sCl" placeholder="ca-pub-1234567890123456" pattern="ca-pub-[0-9]{10,20}" value="' + esc(A.client) + '"></div>' +
      '<label class="switch" style="margin-bottom:14px"><input type="checkbox" id="sAuto"' + (A.autoAds ? " checked" : "") + "> Auto ads (Google decides where to place ads – also turn on Auto ads in your AdSense account)</label>" +
      '<div class="form-grid"><div class="field"><label>Top ad-unit slot ID</label><input class="input" id="sT" placeholder="1234567890" value="' + esc(A.slots.top) + '"></div>' +
      '<div class="field"><label>Middle ad-unit slot ID</label><input class="input" id="sM" placeholder="1234567890" value="' + esc(A.slots.middle) + '"></div>' +
      '<div class="field"><label>Bottom ad-unit slot ID</label><input class="input" id="sB" placeholder="1234567890" value="' + esc(A.slots.bottom) + '"></div>' +
      '<div class="field"><label>Website address (for sitemap)</label><input class="input" id="sU" type="url" value="' + esc(site) + '"></div></div>' +
      '<p style="font-size:12.5px;color:var(--ink-3);margin:0 0 12px">Ads appear on Home, Modules, module pages, Study Material, About and Dashboard. They never appear inside the CBT exam or the admin panel.</p>' +
      '<button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save ad settings</button></form>' +
      (DB.mode === "local" ? '<p class="note-pill" style="margin-top:14px"><i class="fa-solid fa-triangle-exclamation"></i> Local mode: saved only in this browser. Click “Download site-config.js” and upload it to the <b>js/</b> folder of your hosting so every visitor gets the ads.</p>' : '<p class="note-pill" style="margin-top:14px;background:var(--green-100);color:#0e7a3e"><i class="fa-solid fa-cloud"></i> Saved online – applies to all visitors within ~10 minutes.</p>') +
      "</section>" +
      '<section class="card adm-card"><h3><i class="fa-solid fa-list-check" style="color:var(--green)"></i> AdSense approval checklist</h3><ul class="check-list">' +
      checks.map((c) => '<li><i class="fa-solid fa-' + (c[0] ? "circle-check ok" : "circle-exclamation no") + '"></i><span>' + c[1] + "</span></li>").join("") + "</ul>" +
      '<h3 style="margin-top:18px"><i class="fa-solid fa-file-arrow-down" style="color:var(--blue-600)"></i> Files for your hosting</h3>' +
      '<div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-ghost btn-sm" id="dCfg"><i class="fa-solid fa-file-code"></i> site-config.js</button><button class="btn btn-ghost btn-sm" id="dAds"><i class="fa-solid fa-file-lines"></i> ads.txt</button><button class="btn btn-ghost btn-sm" id="dMap"><i class="fa-solid fa-sitemap"></i> sitemap.xml</button></div>' +
      '<p style="font-size:12.5px;color:var(--ink-3)">ads.txt and sitemap.xml go in the main (root) folder of the website.</p>' +
      (pub ? '<div class="codebox">google.com, ' + esc(pub) + ", DIRECT, f08c47fec0942fa0</div>" : "") +
      '<h3 style="margin-top:18px"><i class="fa-solid fa-database" style="color:var(--purple)"></i> Data mode</h3>' +
      (DB.mode === "mysql" ? '<p style="font-size:14px">Online – <b>MySQL database</b> on your hosting (PHP API in <code>/api</code>). All students on all devices are visible live; questions, study material and ad settings are saved in the database.</p>' +
          '<button class="btn btn-ghost btn-sm" id="cPw"><i class="fa-solid fa-key"></i> Change admin password</button>'
        : DB.mode === "firebase" ? "<p>Online (Firebase project <b>" + esc(CFG.firebase.projectId) + "</b>) – all students on all devices are visible live.</p>"
        : '<p style="font-size:14px">Local mode – the admin panel shows only students who used <b>this</b> computer. To see every student live, add a free Firebase project in <code>js/site-config.js</code> (steps are written at the top of that file) and paste <code>firestore.rules</code> into Firestore → Rules.</p>' +
          '<button class="btn btn-ghost btn-sm" id="cPw"><i class="fa-solid fa-key"></i> Change admin password</button>') +
      "</section></div>";
    const f = v.querySelector("#sf");
    const read = () => ({ adsense: { enabled: f.querySelector("#sEn").checked, client: f.querySelector("#sCl").value.trim(), autoAds: f.querySelector("#sAuto").checked, slots: { top: f.querySelector("#sT").value.trim(), middle: f.querySelector("#sM").value.trim(), bottom: f.querySelector("#sB").value.trim() } }, siteUrl: f.querySelector("#sU").value.trim() });
    f.addEventListener("submit", (e) => {
      e.preventDefault();
      const s = read();
      if (s.adsense.enabled && !/^ca-pub-\d{10,20}$/.test(s.adsense.client)) return toast("Enter a valid publisher ID (ca-pub-…)");
      D.saveSettings(s).then(() => { state.settings = s; DB.cache.setSettings(s); toast("Ad settings saved"); settings(v); }).catch((er) => toast("Error: " + er.message));
    });
    v.querySelector("#dCfg").addEventListener("click", () => {
      const s = read(), out = Object.assign({}, CFG, { siteUrl: s.siteUrl, adsense: s.adsense });
      download("site-config.js", "/* COPA MCQ – Site configuration (generated by Admin Panel " + new Date().toLocaleString() + ").\n   See README / previous file for Firebase setup steps. */\nwindow.COPA_CONFIG = " + JSON.stringify(out, null, 2) + ";\n", "text/javascript");
    });
    v.querySelector("#dAds").addEventListener("click", () => {
      const c = read().adsense.client;
      if (!/^ca-pub-\d{10,20}$/.test(c)) return toast("Enter your publisher ID first");
      download("ads.txt", "google.com, " + c.replace(/^ca-/, "") + ", DIRECT, f08c47fec0942fa0\n", "text/plain");
    });
    v.querySelector("#dMap").addEventListener("click", () => {
      let u = read().siteUrl; if (!/^https?:\/\//.test(u)) return toast("Enter the full website address (https://…)");
      if (!u.endsWith("/")) u += "/";
      const pages = ["", "modules.html", "exam.html", "live-practice.html", "study.html", "about.html", "privacy.html", "terms.html"].concat(C.MODULES.map((m) => "modules/module-" + C.pad(m.id) + ".html"));
      download("sitemap.xml", '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + pages.map((p) => "  <url><loc>" + u + p + "</loc></url>").join("\n") + "\n</urlset>\n", "application/xml");
    });
    const cp = v.querySelector("#cPw");
    if (cp && DB.mode === "mysql") cp.addEventListener("click", () => {
      const old = prompt("Current admin password:"); if (!old) return;
      const pw = prompt("New admin password (min 8 characters):"); if (!pw) return;
      if (pw.length < 8) return toast("Password too short (min 8)");
      D.adminChangePassword(old, pw).then(() => toast("Password changed")).catch((er) => toast("Error: " + er.message));
    });
    else if (cp) cp.addEventListener("click", () => {
      const pw = prompt("New admin password (min 6 characters):");
      if (!pw) return; if (pw.length < 6) return toast("Password too short");
      D.adminSetPassword(pw).then(() => toast("Password changed"));
    });
  }

  /* ================= UTIL ================= */
  function download(name, content, type) {
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([content], { type: type || "text/plain" }));
    a.download = name; document.body.appendChild(a); a.click();
    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }
  function csv(name, head, rows) {
    const q = (v) => '"' + String(v == null ? "" : v).replace(/"/g, '""') + '"';
    download(name, "﻿" + [head].concat(rows).map((r) => r.map(q).join(",")).join("\r\n"), "text/csv");
  }

  /* ================= START ================= */
  function start() {
    root.innerHTML = '<p class="empty" style="margin-top:20vh"><i class="fa-solid fa-spinner fa-spin"></i><br>Loading admin data…</p>';
    loadAll().then(() => {
      shell(); draw();
      if (unwatch) unwatch();
      unwatch = D.watchLive(setLive);
      clearInterval(onlineTimer);
      if (DB.mode === "mysql") { pollOnline(); onlineTimer = setInterval(() => { if (!document.hidden && (tab === "overview" || tab === "visitors")) pollOnline(); }, 10000); }
      clearInterval(liveTimer);
      liveTimer = setInterval(() => { setLive(state.live.concat()); }, 15000); // refresh "time left"
      // new results arrive while live sessions end → reload results quietly
      setInterval(() => { if (tab === "overview") D.listResults().then((r) => { const before = state.results.length; state.results = (r || []).sort((a, b) => b.date - a.date); if (DB.mode === "local") localExtras(); if (state.results.length !== before) draw(); }); }, 30000);
    }).catch((er) => { root.innerHTML = '<div class="card adm-login"><h3>Could not load data</h3><p>' + esc(er.message) + '</p><button class="btn btn-primary" onclick="location.reload()">Retry</button></div>'; });
  }
  D.init().then(() => D.adminCheck()).then((ok) => (ok ? start() : loginView())).catch((er) => loginView(er.message));
})();
