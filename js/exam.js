/* CBT Exam engine: setup → instructions → exam → result */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc, R = window.COPA_RESULT;
  const params = new URLSearchParams(location.search);
  const stageEl = document.getElementById("stage");
  const cbtEl = document.getElementById("cbt");
  const SESSION = "exam_session";
  let sess = null, timerId = null, tempSel = null;

  C.renderShell("mock");

  const PRESETS = [
    { k: "full", label: "fullMock", n: 50, min: 60, icon: "fa-trophy" },
    { k: "quick", label: "quickTest", n: 25, min: 30, icon: "fa-bolt" },
    { k: "mini", label: "miniTest", n: 10, min: 12, icon: "fa-feather" },
    { k: "custom", label: "custom", n: 75, min: 90, icon: "fa-sliders" }
  ];

  /* ================= SETUP ================= */
  function setup() {
    showShell(true);
    const modParam = parseInt(params.get("module"), 10);
    const isModule = modParam >= 1 && modParam <= 15;
    const cfg = {
      preset: isModule ? "module" : "full",
      mods: isModule ? [modParam] : C.MODULES.map((m) => m.id),
      n: isModule ? C.byModule(modParam).length : 50,
      min: isModule ? C.byModule(modParam).length : 60,
      shuffleOpts: false
    };
    const prof = C.getProfile();

    stageEl.innerHTML =
      '<div class="page-head"><div class="breadcrumb"><a href="index.html">' + t("home") + "</a> / " + t("mock") + "</div>" +
      '<h1><i class="fa-solid fa-laptop-code" style="color:var(--blue-600)"></i> ' + t("setupTitle") + "</h1><p>" + t("setupDesc") + "</p></div>" +
      '<div class="setup-grid"><section class="card card-pad">' +
      (isModule
        ? '<div class="module-hero" style="background:linear-gradient(115deg,' + C.MODULES[modParam - 1].color + ',#0b1f4d);margin-bottom:18px;padding:18px"><span class="m-ic" style="width:56px;height:56px;font-size:26px"><i class="' + C.iconCls(C.MODULES[modParam - 1]) + '"></i></span><div><p>' + t("testMode") + " • " + t("module") + " " + modParam + "</p><h1 style=\"font-size:20px\">" + esc(C.modName(modParam)) + "</h1></div></div>"
        : '<h3 style="font-size:17px">' + t("pattern") + '</h3><div class="preset-grid" id="presets">' +
          PRESETS.map((p) => '<button type="button" class="preset ' + (p.k === "full" ? "active" : "") + '" data-p="' + p.k + '"><i class="fa-solid ' + p.icon + '" style="color:var(--blue-600)"></i> <b>' + t(p.label) + "</b><span>" + (p.k === "custom" ? "—" : p.n + " " + t("questionsShort") + " • " + p.min + " " + t("minutesShort")) + "</span></button>").join("") + "</div>") +
      '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">' +
      '<div class="field"><label for="cfgN">' + t("numQ") + '</label><input class="input" type="number" id="cfgN" min="5" max="355" value="' + cfg.n + '"></div>' +
      '<div class="field"><label for="cfgMin">' + t("duration") + '</label><input class="input" type="number" id="cfgMin" min="1" max="400" value="' + cfg.min + '"></div></div>' +
      '<div class="field"><label for="cfgLang">' + t("examLang") + '</label><select class="input" id="cfgLang"><option value="en">English</option><option value="hi">हिंदी (Hindi)</option><option value="mr">मराठी (Marathi)</option></select></div>' +
      '<label style="display:flex;gap:10px;align-items:center;font-size:14.5px;margin-bottom:16px;cursor:pointer"><input type="checkbox" id="cfgShuf" style="accent-color:var(--blue-600);width:18px;height:18px"> ' + t("shuffleOpt") + "</label>" +
      '<div class="field"><label for="cName">' + t("candidate") + " – " + t("name") + '</label><input class="input" id="cName" maxlength="60" value="' + esc(prof ? prof.name : "") + '" placeholder="' + t("name") + '"></div>' +
      '<div class="field"><label for="cRoll">' + t("rollNo") + '</label><input class="input" id="cRoll" maxlength="30" value="' + esc(prof ? prof.roll : "") + '"></div>' +
      '<button class="btn btn-primary btn-block" id="goInstr"><i class="fa-solid fa-list-check"></i> ' + t("proceed") + ' <i class="fa-solid fa-arrow-right"></i></button>' +
      "</section>" +
      '<section class="card card-pad"' + (isModule ? ' style="display:none"' : "") + '><div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px"><h3 style="font-size:17px;margin:0">' + t("selectModules") + "</h3>" +
      '<span><button class="btn btn-ghost btn-sm" id="mAll">' + t("selectAll") + '</button> <button class="btn btn-ghost btn-sm" id="mNone">' + t("clearAll") + "</button></span></div>" +
      '<div class="mod-checks">' + C.MODULES.map((m) => '<label><input type="checkbox" value="' + m.id + '" ' + (cfg.mods.indexOf(m.id) > -1 ? "checked" : "") + "><span><b>M" + m.id + "</b> " + esc(C.modName(m)) + ' <small style="color:var(--ink-3)">(' + C.byModule(m.id).length + ")</small></span></label>").join("") + "</div>" +
      '<p style="font-size:13px;color:var(--ink-3);margin:12px 0 0" id="poolInfo"></p></section></div>';

    document.getElementById("cfgLang").value = C.lang;
    const nEl = document.getElementById("cfgN"), minEl = document.getElementById("cfgMin");
    const poolInfo = document.getElementById("poolInfo");
    const selMods = () => Array.from(stageEl.querySelectorAll(".mod-checks input:checked")).map((x) => +x.value);
    const updatePool = () => {
      const pool = selMods().reduce((s, m) => s + C.byModule(m).length, 0);
      nEl.max = pool || 1;
      if (poolInfo) poolInfo.textContent = t("total") + ": " + pool + " " + t("questions");
    };
    updatePool();
    stageEl.querySelectorAll(".mod-checks input").forEach((c) => c.addEventListener("change", updatePool));
    const mAll = document.getElementById("mAll"), mNone = document.getElementById("mNone");
    if (mAll) mAll.addEventListener("click", () => { stageEl.querySelectorAll(".mod-checks input").forEach((c) => (c.checked = true)); updatePool(); });
    if (mNone) mNone.addEventListener("click", () => { stageEl.querySelectorAll(".mod-checks input").forEach((c) => (c.checked = false)); updatePool(); });
    stageEl.querySelectorAll("[data-p]").forEach((b) => b.addEventListener("click", () => {
      stageEl.querySelectorAll("[data-p]").forEach((x) => x.classList.remove("active"));
      b.classList.add("active");
      const p = PRESETS.find((x) => x.k === b.getAttribute("data-p"));
      cfg.preset = p.k;
      if (p.k !== "custom") { nEl.value = p.n; minEl.value = p.min; } else nEl.focus();
    }));
    [nEl, minEl].forEach((el) => el.addEventListener("input", () => {
      if (cfg.preset !== "module") { cfg.preset = "custom"; stageEl.querySelectorAll("[data-p]").forEach((x) => x.classList.toggle("active", x.getAttribute("data-p") === "custom")); }
    }));

    if (prof) { document.getElementById("cName").readOnly = true; document.getElementById("cRoll").readOnly = true; }
    document.getElementById("goInstr").addEventListener("click", () => {
      if (!C.getProfile()) { C.toast(t("loginFirst")); C.openLogin(() => location.reload()); return; }
      const mods = selMods();
      if (!mods.length) { C.toast(t("selectOneModule")); return; }
      const pool = mods.reduce((s, m) => s + C.byModule(m).length, 0);
      cfg.mods = mods;
      cfg.n = Math.max(1, Math.min(pool, parseInt(nEl.value, 10) || 10));
      cfg.min = Math.max(1, Math.min(400, parseInt(minEl.value, 10) || 10));
      cfg.shuffleOpts = document.getElementById("cfgShuf").checked;
      cfg.lang = document.getElementById("cfgLang").value;
      cfg.name = C.getProfile().name;
      cfg.roll = C.getProfile().roll;
      if (cfg.lang !== C.lang) C.store.set("lang", cfg.lang);
      C.store.set("exam_pending", cfg);
      location.href = "exam.html?stage=instr";
    });
  }

  /* ================= INSTRUCTIONS ================= */
  function instructions(cfg) {
    showShell(true);
    const title = cfgTitle(cfg);
    stageEl.innerHTML =
      '<div class="instr"><div class="page-head"><h1><i class="fa-solid fa-clipboard-list" style="color:var(--blue-600)"></i> ' + t("instructions") + "</h1>" +
      "<p><b>" + esc(title) + "</b> • " + cfg.n + " " + t("questions") + " • " + cfg.min + " " + t("minutes") + " • " + t("passMark") + "</p></div>" +
      '<section class="card card-pad"><ol>' +
      "<li>" + t("instr1") + "</li><li>" + t("instr2") +
      '<div class="instr-legend" style="margin-top:10px">' +
      legendRow("", "1", t("notVisited")) + legendRow("na", "2", t("notAnswered")) + legendRow("ans", "3", t("answered")) + legendRow("mr", "4", t("marked")) + legendRow("amr", "5", t("ansMarked")) +
      "</div></li><li>" + t("instr3") + "</li><li>" + t("instr4") + "</li><li>" + t("instr5") + "</li><li>" + t("instr6") + "</li><li>" + t("instr7") + "</li><li><b>" + t("instr8") + "</b></li></ol>" +
      '<hr style="border:0;border-top:1px solid var(--line);margin:18px 0">' +
      '<label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-weight:500"><input type="checkbox" id="agree" style="width:20px;height:20px;accent-color:var(--blue-600);margin-top:2px"> ' + t("instrAgree") + "</label>" +
      '<div style="display:flex;gap:10px;justify-content:space-between;margin-top:18px;flex-wrap:wrap"><a class="btn btn-ghost" href="exam.html"><i class="fa-solid fa-arrow-left"></i> ' + t("prev") + "</a>" +
      '<button class="btn btn-green" id="begin" disabled><i class="fa-solid fa-play"></i> ' + t("iStart") + "</button></div></section></div>";
    const ag = document.getElementById("agree"), bg = document.getElementById("begin");
    ag.addEventListener("change", () => (bg.disabled = !ag.checked));
    bg.addEventListener("click", () => startExam(cfg));
  }
  function legendRow(cls, n, label) { return '<div class="instr legend-row"><span class="st ' + cls + '">' + n + "</span> " + label + "</div>"; }
  function cfgTitle(cfg) {
    if (cfg.preset === "module") return t("testMode") + " – M" + cfg.mods[0] + ": " + C.modName(cfg.mods[0]);
    const p = PRESETS.find((x) => x.k === cfg.preset);
    return "COPA " + (p ? t(p.label) : t("mock")) + (cfg.mods.length < 15 ? " (" + cfg.mods.map((m) => "M" + m).join(", ") + ")" : "");
  }

  /* ================= EXAM ================= */
  function startExam(cfg) {
    let pool = [];
    cfg.mods.forEach((m) => (pool = pool.concat(C.byModule(m).map((q) => q.id))));
    let qids;
    if (cfg.preset === "module" && cfg.n >= pool.length) qids = C.shuffle(pool);
    else qids = C.shuffle(pool).slice(0, cfg.n);
    // keep questions grouped by module order like the real paper
    qids.sort((a, b) => C.qById[a].m - C.qById[b].m);
    const now = Date.now();
    sess = {
      cfg, title: cfgTitle(cfg), qids,
      perms: qids.map(() => (cfg.shuffleOpts ? C.shuffle([0, 1, 2, 3]) : [0, 1, 2, 3])),
      ans: qids.map(() => null), status: qids.map(() => "nv"),
      cur: 0, startAt: now, endAt: now + cfg.min * 60000
    };
    sess.status[0] = "na";
    C.store.del("exam_pending");
    persist();
    history.replaceState(null, "", "exam.html?stage=exam");
    runExam();
  }
  const persist = () => { C.store.set(SESSION, sess); liveUpdate(); };

  function runExam() {
    showShell(false);
    tempSel = sess.ans[sess.cur];
    drawExam();
    window.scrollTo(0, 0);
    clearInterval(timerId);
    timerId = setInterval(tick, 1000);
    tick();
    window.addEventListener("beforeunload", beforeUnload);
    liveStart();
  }
  /* live tracking for the Admin Panel */
  function liveStart() {
    if (!window.COPA_DB || !sess) return;
    const p = C.getProfile() || {};
    if (!sess.sid) { sess.sid = (p.uid || "guest") + "-" + sess.startAt; persist(); }
    window.COPA_DB.live.start({ sid: sess.sid, uid: p.uid || "", name: sess.cfg.name, roll: sess.cfg.roll, batch: p.batch || "",
      title: sess.title, mods: sess.cfg.mods, preset: sess.cfg.preset, total: sess.qids.length, startAt: sess.startAt, endAt: sess.endAt,
      answered: sess.ans.filter((a, i) => a != null && (sess.status[i] === "ans" || sess.status[i] === "amr")).length, cur: sess.cur + 1 });
  }
  function liveUpdate() {
    if (!window.COPA_DB || !sess) return;
    window.COPA_DB.live.update({ cur: sess.cur + 1, answered: sess.ans.filter((a, i) => a != null && (sess.status[i] === "ans" || sess.status[i] === "amr")).length });
  }
  function beforeUnload(e) { if (sess) { e.preventDefault(); e.returnValue = ""; } }

  function tick() {
    if (!sess) return;
    const left = Math.round((sess.endAt - Date.now()) / 1000);
    const el = document.getElementById("timer");
    if (el) { el.querySelector("span").textContent = C.fmtTime(left); el.classList.toggle("warn", left <= 300); }
    if (left <= 0) { finish(true); }
  }

  function drawExam() {
    const prof = sess.cfg;
    const l = C.lang;
    cbtEl.innerHTML =
      '<div class="cbt-top"><img class="cbt-logo" src="' + C.ROOT + 'assets/img/logo.webp" alt="COPA MCQ"><div class="title">' + esc(sess.title) + "<small>COPA • CBT</small></div><span class=\"spacer\"></span>" +
      '<label style="font-size:13px;display:flex;gap:6px;align-items:center">' + t("viewIn") + ' <select class="lang-select" id="cbtLang"><option value="en">English</option><option value="hi">हिंदी</option><option value="mr">मराठी</option></select></label>' +
      '<div class="timer" id="timer" title="' + t("timeLeft") + '"><i class="fa-regular fa-clock"></i><span>--:--</span></div>' +
      '<button class="btn btn-yellow btn-sm palette-toggle" id="palT"><i class="fa-solid fa-table-cells"></i></button></div>' +
      '<div class="cbt-body"><section class="cbt-main"><div class="cbt-qbar" id="qbar"></div><div class="cbt-qbox" id="qbox"></div>' +
      '<div class="cbt-actions">' +
      '<button class="btn btn-review" id="bMark"><i class="fa-solid fa-bookmark"></i> ' + t("markNext") + "</button>" +
      '<button class="btn btn-clear" id="bClear"><i class="fa-solid fa-eraser"></i> ' + t("clearResp") + "</button>" +
      '<span class="grow"></span>' +
      '<button class="btn btn-ghost" id="bPrev"><i class="fa-solid fa-arrow-left"></i> ' + t("prev") + "</button>" +
      '<button class="btn btn-green" id="bSave">' + t("saveNext") + ' <i class="fa-solid fa-arrow-right"></i></button></div></section>' +
      '<aside class="cbt-side" id="cbtSide"><div class="candidate"><div class="ph"><i class="fa-solid fa-user"></i></div><div><b>' + esc(prof.name || "Student") + "</b><span>" + t("rollNo") + ": " + esc(prof.roll || "—") + "</span><br><span>" + t("candidate") + "</span></div></div>" +
      '<div class="legend" id="legend"></div><div class="cbt-palette-head">' + t("palette") + '</div><div class="cbt-palette" id="pal"></div>' +
      '<button class="btn btn-primary btn-block btn-submit" id="bSubmit"><i class="fa-solid fa-paper-plane"></i> ' + t("submit") + "</button></aside></div>" +
      '<div class="sidebar-backdrop" id="palBack"></div>';

    const ls = document.getElementById("cbtLang");
    ls.value = l;
    ls.addEventListener("change", () => { C.store.set("lang", ls.value); C.setLang(ls.value); });
    document.getElementById("bSave").addEventListener("click", () => act("save"));
    document.getElementById("bMark").addEventListener("click", () => act("mark"));
    document.getElementById("bClear").addEventListener("click", () => act("clear"));
    document.getElementById("bPrev").addEventListener("click", () => go(sess.cur - 1));
    document.getElementById("bSubmit").addEventListener("click", confirmSubmit);
    document.getElementById("palT").addEventListener("click", () => document.body.classList.toggle("palette-open"));
    document.getElementById("palBack").addEventListener("click", () => document.body.classList.remove("palette-open"));
    drawQuestion(); drawPalette(); tick();
  }

  function drawQuestion() {
    const l = C.lang, i = sess.cur, q = C.qById[sess.qids[i]], perm = sess.perms[i];
    document.getElementById("qbar").innerHTML = "<b>" + t("question") + " " + (i + 1) + " " + t("of") + " " + sess.qids.length + "</b>" +
      '<span class="chip">' + t("module") + " " + q.m + " • +1</span>";
    document.getElementById("qbox").innerHTML = C.qBlock(q, l) + '<div class="options">' +
      perm.map((orig, pos) => '<button class="opt ' + (tempSel === orig ? "selected" : "") + '" data-o="' + orig + '"><span class="k">' + "ABCD"[pos] + '</span><span class="ot">' + esc(C.optText(q, l, orig)) + "</span></button>").join("") + "</div>";
    document.querySelectorAll("#qbox [data-o]").forEach((b) => b.addEventListener("click", () => {
      tempSel = +b.getAttribute("data-o");
      document.querySelectorAll("#qbox .opt").forEach((x) => x.classList.toggle("selected", x === b));
    }));
    document.getElementById("bPrev").disabled = i === 0;
    document.getElementById("qbox").scrollTop = 0;
  }

  function drawPalette() {
    const cnt = { nv: 0, na: 0, ans: 0, mr: 0, amr: 0 };
    sess.status.forEach((s) => cnt[s]++);
    document.getElementById("legend").innerHTML =
      lg("ans", cnt.ans, t("answered")) + lg("na", cnt.na, t("notAnswered")) + lg("", cnt.nv, t("notVisited")) + lg("mr", cnt.mr, t("marked")) +
      '<div style="grid-column:1/-1">' + '<span class="st amr">' + cnt.amr + "</span> " + t("ansMarked") + "</div>";
    document.getElementById("pal").innerHTML = sess.status.map((s, i) =>
      '<button class="st ' + (s === "nv" ? "" : s) + (i === sess.cur ? " cur" : "") + '" data-g="' + i + '" aria-label="' + t("question") + " " + (i + 1) + '">' + (i + 1) + "</button>").join("");
    document.querySelectorAll("#pal [data-g]").forEach((b) => b.addEventListener("click", () => { go(+b.getAttribute("data-g")); document.body.classList.remove("palette-open"); }));
  }
  function lg(cls, n, label) { return '<div><span class="st ' + cls + '">' + n + "</span> " + label + "</div>"; }

  function go(i) {
    if (i < 0 || i >= sess.qids.length) return;
    sess.cur = i;
    if (sess.status[i] === "nv") sess.status[i] = "na";
    tempSel = sess.ans[i];
    persist(); drawQuestion(); drawPalette();
    window.scrollTo(0, 0);
  }
  function act(kind) {
    const i = sess.cur;
    if (kind === "clear") { tempSel = null; sess.ans[i] = null; sess.status[i] = "na"; persist(); drawQuestion(); drawPalette(); return; }
    sess.ans[i] = tempSel;
    if (kind === "save") sess.status[i] = tempSel != null ? "ans" : "na";
    if (kind === "mark") sess.status[i] = tempSel != null ? "amr" : "mr";
    persist();
    if (i < sess.qids.length - 1) go(i + 1); else { drawQuestion(); drawPalette(); if (kind === "save") confirmSubmit(); }
  }

  function confirmSubmit() {
    const cnt = { nv: 0, na: 0, ans: 0, mr: 0, amr: 0 };
    sess.status.forEach((s) => cnt[s]++);
    const m = C.modal(
      '<h3><i class="fa-solid fa-paper-plane" style="color:var(--blue-600)"></i> ' + t("confirmSubmit") + "</h3><p style=\"color:var(--ink-2)\">" + t("confirmSubmitDesc") + "</p>" +
      '<div class="table-wrap"><table class="tbl"><tbody>' +
      "<tr><td>" + t("total") + "</td><td><b>" + sess.qids.length + "</b></td></tr>" +
      '<tr><td><span class="st ans">&nbsp;</span> ' + t("answered") + "</td><td><b>" + cnt.ans + "</b></td></tr>" +
      '<tr><td><span class="st amr">&nbsp;</span> ' + t("ansMarked") + "</td><td><b>" + cnt.amr + "</b></td></tr>" +
      '<tr><td><span class="st na">&nbsp;</span> ' + t("notAnswered") + "</td><td><b>" + cnt.na + "</b></td></tr>" +
      '<tr><td><span class="st mr">&nbsp;</span> ' + t("marked") + "</td><td><b>" + cnt.mr + "</b></td></tr>" +
      '<tr><td><span class="st">&nbsp;</span> ' + t("notVisited") + "</td><td><b>" + cnt.nv + "</b></td></tr>" +
      "<tr><td>" + t("timeLeft") + "</td><td><b>" + C.fmtTime((sess.endAt - Date.now()) / 1000) + "</b></td></tr>" +
      "</tbody></table></div>" +
      '<div class="modal-acts"><button class="btn btn-ghost" data-close>' + t("cancel") + '</button><button class="btn btn-primary" id="doSubmit">' + t("yesSubmit") + "</button></div>"
    );
    m.el.querySelector("#doSubmit").addEventListener("click", () => { m.close(); finish(false); });
  }

  function finish(auto) {
    if (!sess) return;
    clearInterval(timerId);
    window.removeEventListener("beforeunload", beforeUnload);
    const ev = R.evaluate(sess);
    const rec = {
      id: "r" + Date.now(), date: Date.now(), title: sess.title, cfg: sess.cfg,
      name: sess.cfg.name, roll: sess.cfg.roll,
      timeTaken: Math.min(sess.cfg.min * 60, Math.round((Date.now() - sess.startAt) / 1000)),
      correct: ev.correct, wrong: ev.wrong, un: ev.un, total: ev.total, pct: ev.pct, per: ev.per, rows: ev.rows
    };
    const hist = C.store.get("results", []);
    hist.unshift(rec);
    C.store.set("results", hist.slice(0, 60));
    C.store.del(SESSION);
    sess = null;
    document.body.classList.remove("palette-open");
    let send = Promise.resolve();
    if (window.COPA_DB) {
      const p = C.getProfile() || {};
      window.COPA_DB.live.end();
      // summary for the Admin Panel (answer rows stay on the student's device)
      send = window.COPA_DB.saveResult({ id: rec.id, date: rec.date, title: rec.title, uid: p.uid || "", name: rec.name, roll: rec.roll, batch: p.batch || "",
        preset: rec.cfg.preset, mods: rec.cfg.mods, total: rec.total, correct: rec.correct, wrong: rec.wrong, un: rec.un, pct: rec.pct,
        pass: rec.pct >= C.PASS, timeTaken: rec.timeTaken, per: rec.per, auto: !!auto });
    }
    const go = () => { if (auto) alert(t("timeUp")); location.replace("exam.html?result=" + rec.id); };
    Promise.race([send, new Promise((r) => setTimeout(r, 4000))]).then(go, go);
  }

  function showShell(on) {
    document.getElementById("shellLayout").hidden = !on;
    document.querySelectorAll(".site-header, .site-footer").forEach((el) => (el.hidden = !on));
    cbtEl.hidden = on;
    document.body.classList.toggle("cbt", !on);
  }

  /* ================= ROUTER ================= */
  C.onLang(() => {
    if (sess && !cbtEl.hidden) { drawExam(); }
    else location.reload();
  });

  const existing = C.store.get(SESSION, null);
  const resId = params.get("result");
  if (resId) {
    showShell(true);
    const rec = C.store.get("results", []).find((r) => r.id === resId);
    if (rec) R.render(stageEl, rec);
    else stageEl.innerHTML = '<div class="card empty"><i class="fa-solid fa-circle-exclamation"></i><p>' + t("noResults") + '</p><a class="btn btn-primary" href="exam.html">' + t("newTest") + "</a></div>";
  } else if (existing && existing.endAt) {
    if (params.get("stage") === "exam") { sess = existing; runExam(); }
    else {
      setup();
      const m = C.modal('<h3><i class="fa-solid fa-hourglass-half" style="color:var(--orange)"></i> ' + t("resumeTitle") + "</h3><p>" + t("resumeDesc") + "</p><p><b>" + esc(existing.title) + "</b> • " + t("timeLeft") + ": " + C.fmtTime((existing.endAt - Date.now()) / 1000) + "</p>" +
        '<div class="modal-acts"><button class="btn btn-ghost" id="disc">' + t("discard") + '</button><button class="btn btn-primary" id="res">' + t("resume") + "</button></div>", { sticky: true });
      m.el.querySelector("#res").addEventListener("click", () => { m.close(); sess = existing; history.replaceState(null, "", "exam.html?stage=exam"); runExam(); });
      m.el.querySelector("#disc").addEventListener("click", () => { C.store.del(SESSION); m.close(); });
    }
  } else if (!C.getProfile() && params.get("stage")) {
    history.replaceState(null, "", "exam.html"); setup();
  } else if (params.get("stage") === "instr" && C.store.get("exam_pending", null)) {
    instructions(C.store.get("exam_pending"));
  } else {
    if (params.get("stage")) history.replaceState(null, "", "exam.html");
    setup();
  }
})();
