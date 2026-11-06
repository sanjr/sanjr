/* Student dashboard */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc;
  C.renderShell("dashboard");
  const root = document.getElementById("dash");
  const prof = C.getProfile();

  /* ---------- not logged in ---------- */
  if (!prof) {
    root.innerHTML =
      '<section class="card dash-gate"><div class="gate-ic"><i class="fa-solid fa-user-lock"></i></div>' +
      "<h1>" + t("loginToSee") + "</h1><p>" + t("loginToSeeDesc") + "</p>" +
      '<div class="gate-acts"><button class="btn btn-primary" id="gLogin"><i class="fa-solid fa-arrow-right-to-bracket"></i> ' + t("login") + "</button>" +
      '<button class="btn btn-yellow" id="gReg"><i class="fa-solid fa-user-plus"></i> ' + t("register") + "</button></div></section>";
    document.getElementById("gLogin").addEventListener("click", () => C.openLogin(() => location.reload(), "login"));
    document.getElementById("gReg").addEventListener("click", () => C.openLogin(() => location.reload(), "register"));
    return;
  }

  const hist = C.store.get("results", []); // newest first
  const PASS = C.PASS;
  const n = hist.length;
  const passed = hist.filter((r) => r.pct >= PASS).length;
  const avg = n ? Math.round(hist.reduce((s, r) => s + r.pct, 0) / n) : 0;
  const best = n ? Math.max.apply(null, hist.map((r) => r.pct)) : 0;
  const last = n ? hist[0].pct : 0;
  const st = C.getStats();
  const initials = prof.name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join("").toUpperCase();
  const fmtD = (d) => new Date(d).toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" });
  const fmtDT = (d) => new Date(d).toLocaleString(undefined, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });

  /* ---------- module performance across tests ---------- */
  const agg = {};
  hist.forEach((r) => (r.per || []).forEach((p) => { agg[p.m] = agg[p.m] || { m: p.m, total: 0, c: 0 }; agg[p.m].total += p.total; agg[p.m].c += p.c; }));
  const modRows = Object.values(agg).sort((a, b) => a.m - b.m);

  const stat = (v, l, ic, c, sub) => '<div class="card stat"><span class="ic" style="background:' + c + '"><i class="fa-solid ' + ic + '"></i></span><div><b>' + v + "</b><span>" + l + "</span>" + (sub ? '<small class="stat-sub">' + sub + "</small>" : "") + "</div></div>";

  root.innerHTML =
    '<div class="breadcrumb"><a href="index.html">' + t("home") + "</a> / " + t("dashboard") + "</div>" +
    '<section class="dash-hero">' +
    '<div class="d-ava">' + esc(initials || "S") + "</div>" +
    '<div class="d-info"><span class="d-hello">' + t("hello") + ",</span><h1>" + esc(prof.name) + "</h1>" +
    '<div class="d-meta"><span><i class="fa-solid fa-id-card"></i> ' + t("rollNo") + " " + esc(prof.roll) + "</span>" +
    (prof.batch ? '<span><i class="fa-solid fa-users"></i> ' + esc(prof.batch) + "</span>" : "") +
    '<span><i class="fa-regular fa-calendar"></i> ' + t("memberSince") + " " + fmtD(prof.created) + "</span>" +
    (prof.lastLogin ? '<span><i class="fa-regular fa-clock"></i> ' + t("lastLogin") + " " + fmtDT(prof.lastLogin) + "</span>" : "") + "</div></div>" +
    '<div class="d-acts"><a class="btn btn-yellow" href="exam.html"><i class="fa-solid fa-play"></i> ' + t("takeMock") + "</a>" +
    '<a class="btn btn-outline d-ghost" href="modules.html"><i class="fa-solid fa-pen-to-square"></i> ' + t("practice") + "</a>" +
    '<button class="btn btn-outline d-ghost" id="dOut"><i class="fa-solid fa-right-from-bracket"></i> ' + t("logout") + "</button></div></section>" +

    '<div class="dash-stats">' +
    stat(n, t("testsAttempted"), "fa-clipboard-check", "#1554e0") +
    stat(passed, t("passed"), "fa-circle-check", "#18a957") +
    stat(n - passed, t("failed"), "fa-circle-xmark", "#e5383b") +
    stat((n ? Math.round((passed / n) * 100) : 0) + "%", t("passRate"), "fa-flag-checkered", "#7b3fe4") +
    stat(avg + "%", t("avgScore"), "fa-percent", "#0fa3b1") +
    stat(best + "%", t("best"), "fa-trophy", "#f58a07", t("lastScore") + ": " + last + "%") +
    "</div>" +

    '<div class="dash-grid">' +
    '<section class="card card-pad"><div class="d-head"><div><h3><i class="fa-solid fa-chart-line" style="color:var(--blue-600)"></i> ' + t("scoreTrend") + '</h3><p>' + t("scoreTrendDesc") + '</p></div><span class="chip">' + t("passMark") + "</span></div>" +
    '<div class="trend" id="trend"></div></section>' +
    '<section class="card card-pad"><div class="d-head"><div><h3><i class="fa-solid fa-layer-group" style="color:var(--purple)"></i> ' + t("modulePerf") + "</h3></div></div>" +
    '<div class="mod-perf">' + (modRows.length ? modRows.map((p) => {
      const s = Math.round((p.c / p.total) * 100), ok = s >= PASS;
      return '<div class="mp-row"><div class="mp-name"><b>M' + p.m + "</b> " + esc(C.modName(p.m)) + '</div><div class="mp-bar"><i style="width:' + s + "%;background:" + (ok ? "var(--green)" : "var(--red)") + '"></i><span class="mp-pass" style="left:' + PASS + '%"></span></div>' +
        '<div class="mp-val"><b>' + s + '%</b><span class="chip ' + (ok ? "green" : "red") + '"><i class="fa-solid fa-' + (ok ? "check" : "triangle-exclamation") + '"></i> ' + t(ok ? "strong" : "weak") + "</span></div></div>";
    }).join("") : '<p class="empty" style="padding:20px 0">' + t("noTestModules") + "</p>") + "</div></section></div>" +

    '<section class="card card-pad" style="margin-top:22px"><div class="d-head"><div><h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--orange)"></i> ' + t("testHistory") + "</h3><p>" + t("allTests") + "</p></div>" +
    '<div class="mode-tabs" style="margin:0" id="hTabs"><button class="active" data-f="all">' + t("all") + " (" + n + ')</button><button data-f="p">' + t("pass") + " (" + passed + ')</button><button data-f="f">' + t("fail") + " (" + (n - passed) + ")</button></div></div>" +
    '<div id="hList"></div></section>' +

    '<section class="card card-pad" style="margin-top:22px"><div class="d-head"><div><h3><i class="fa-solid fa-pen-to-square" style="color:var(--green)"></i> ' + t("practiceProgress") + "</h3><p>" + t("questionsPracticed") + ": <b>" + st.practiced + "</b></p></div></div>" +
    '<div class="prac-grid">' + C.MODULES.map((m) => {
      const p = C.moduleProgress(m.id);
      return '<a class="prac-item" href="' + C.modulePage(m.id) + '"><span class="s-ic" style="background:' + m.bg + ";color:" + m.color + '"><i class="' + C.iconCls(m) + '"></i></span><span class="pi-t"><b>M' + m.id + " " + esc(C.modName(m)) + "</b>" +
        '<span class="mini-bar"><i style="width:' + p.pct + '%"></i></span><small>' + p.done + "/" + p.total + " • " + t("correct") + " " + p.ok + "</small></span></a>";
    }).join("") + "</div></section>";

  document.getElementById("dOut").addEventListener("click", () => C.logout());

  /* ---------- history table ---------- */
  function drawHist(f) {
    const rows = hist.map((r, i) => ({ r, no: n - i })).filter((x) => f === "all" || (f === "p" ? x.r.pct >= PASS : x.r.pct < PASS));
    const el = document.getElementById("hList");
    if (!n) {
      el.innerHTML = '<div class="empty"><i class="fa-solid fa-clipboard-question"></i><p>' + t("noResults") + '</p><a class="btn btn-yellow" href="exam.html"><i class="fa-solid fa-play"></i> ' + t("takeMock") + "</a></div>";
      return;
    }
    el.innerHTML = '<div class="table-wrap"><table class="tbl hist-tbl"><thead><tr><th>#</th><th>' + t("date") + "</th><th>" + t("test") + "</th><th>" + t("questionsAns") + "</th><th>" + t("correct") + "</th><th>" + t("wrong") + "</th><th>" + t("unattempted") + "</th><th>" + t("marks") + "</th><th>" + t("percentage") + "</th><th>" + t("timeTaken") + "</th><th>" + t("status") + "</th><th></th></tr></thead><tbody>" +
      rows.map((x) => {
        const r = x.r, ok = r.pct >= PASS;
        const dl = (k) => ' data-label="' + esc(t(k)) + '"';
        return '<tr><td class="h-no">' + x.no + '</td><td class="nowrap h-date">' + fmtDT(r.date) + '</td><td class="h-title">' + esc(r.title) + "</td><td" + dl("questionsAns") + ">" + r.total + "</td><td" + dl("correct") + ' style="color:var(--green);font-weight:600">' + r.correct + "</td><td" + dl("wrong") + ' style="color:var(--red);font-weight:600">' + r.wrong + "</td><td" + dl("unattempted") + ">" + r.un + "</td><td" + dl("marks") + "><b>" + r.correct + "/" + r.total + "</b></td>" +
          "<td" + dl("percentage") + '><div class="pct-cell"><div class="mini-bar"><i style="width:' + Math.min(100, r.pct) + "%;background:" + (ok ? "var(--green)" : "var(--red)") + '"></i></div><b>' + r.pct + "%</b></div></td>" +
          '<td class="nowrap"' + dl("timeTaken") + ">" + C.fmtTime(r.timeTaken) + '</td><td class="h-st"><span class="chip ' + (ok ? "green" : "red") + '"><i class="fa-solid fa-' + (ok ? "circle-check" : "circle-xmark") + '"></i> ' + t(ok ? "pass" : "fail") + "</span></td>" +
          '<td class="h-view"><a class="btn btn-ghost btn-sm" href="exam.html?result=' + r.id + '">' + t("view") + "</a></td></tr>";
      }).join("") + "</tbody></table></div>" +
      (rows.length ? "" : '<p class="empty">—</p>');
  }
  drawHist("all");
  root.querySelectorAll("#hTabs button").forEach((b) => b.addEventListener("click", () => {
    root.querySelectorAll("#hTabs button").forEach((x) => x.classList.remove("active"));
    b.classList.add("active"); drawHist(b.getAttribute("data-f"));
  }));

  /* ---------- score trend (single series line, 40% pass reference) ---------- */
  function drawTrend() {
    const box = document.getElementById("trend");
    const data = hist.slice(0, 20).reverse(); // oldest → newest, last 20
    if (!data.length) { box.innerHTML = '<div class="empty"><i class="fa-solid fa-chart-line"></i><p>' + t("noResults") + "</p></div>"; return; }
    const W = Math.max(320, box.clientWidth || 600), H = 240, P = { l: 40, r: 18, t: 16, b: 30 };
    const iw = W - P.l - P.r, ih = H - P.t - P.b;
    const x = (i) => P.l + (data.length === 1 ? iw / 2 : (i * iw) / (data.length - 1));
    const y = (v) => P.t + ih - (Math.max(0, Math.min(100, v)) / 100) * ih;
    let g = '<svg viewBox="0 0 ' + W + " " + H + '" width="100%" height="' + H + '" role="img" aria-label="' + esc(t("scoreTrend")) + '">';
    [0, 20, 40, 60, 80, 100].forEach((v) => {
      g += '<line x1="' + P.l + '" x2="' + (W - P.r) + '" y1="' + y(v) + '" y2="' + y(v) + '" class="tr-grid"/><text x="' + (P.l - 8) + '" y="' + (y(v) + 4) + '" text-anchor="end" class="tr-ax">' + v + "</text>";
    });
    g += '<line x1="' + P.l + '" x2="' + (W - P.r) + '" y1="' + y(PASS) + '" y2="' + y(PASS) + '" class="tr-pass"/><text x="' + (P.l + 6) + '" y="' + (y(PASS) + 15) + '" text-anchor="start" class="tr-pass-l">' + t("pass") + " " + PASS + "%</text>";
    const pts = data.map((r, i) => x(i) + "," + y(r.pct)).join(" ");
    if (data.length > 1) g += '<polyline points="' + pts + '" class="tr-line"/>';
    const step = data.length > 10 ? Math.ceil(data.length / 8) : 1;
    data.forEach((r, i) => {
      if (i % step === 0 || i === data.length - 1) g += '<text x="' + x(i) + '" y="' + (H - 8) + '" text-anchor="middle" class="tr-ax">T' + (n - Math.min(n, 20) + i + 1) + "</text>";
      g += '<circle cx="' + x(i) + '" cy="' + y(r.pct) + '" r="5" class="tr-dot ' + (r.pct >= PASS ? "" : "f") + '" data-i="' + i + '"/>';
    });
    const lr = data[data.length - 1];
    g += '<text x="' + Math.min(x(data.length - 1), W - P.r - 4) + '" y="' + (y(lr.pct) - 12) + '" text-anchor="end" class="tr-lab">' + lr.pct + "%</text>";
    const colW = data.length === 1 ? iw : iw / (data.length - 1);
    data.forEach((r, i) => { g += '<rect x="' + (x(i) - colW / 2) + '" y="' + P.t + '" width="' + colW + '" height="' + ih + '" fill="transparent" data-h="' + i + '"/>'; });
    g += '<line class="tr-cross" id="trX" y1="' + P.t + '" y2="' + (P.t + ih) + '" x1="0" x2="0" style="display:none"/></svg><div class="tr-tip" id="trTip" hidden></div>';
    box.innerHTML = g;
    const tip = document.getElementById("trTip"), cross = document.getElementById("trX");
    const show = (i) => {
      const r = data[i], ok = r.pct >= PASS, sx = box.clientWidth / W;
      cross.setAttribute("x1", x(i)); cross.setAttribute("x2", x(i)); cross.style.display = "";
      tip.innerHTML = "<b>" + r.pct + "%</b> • " + r.correct + "/" + r.total + "<br><small>" + esc(r.title) + "<br>" + fmtDT(r.date) + "</small><br>" +
        '<span class="chip ' + (ok ? "green" : "red") + '">' + t(ok ? "pass" : "fail") + "</span>";
      tip.hidden = false;
      const left = x(i) * sx, tw = tip.offsetWidth;
      tip.style.left = Math.max(0, Math.min(box.clientWidth - tw, left - tw / 2)) + "px";
      tip.style.top = Math.max(0, y(r.pct) * sx - tip.offsetHeight - 14) + "px";
    };
    const hide = () => { tip.hidden = true; cross.style.display = "none"; };
    box.querySelectorAll("[data-h]").forEach((el) => {
      el.addEventListener("mouseenter", () => show(+el.getAttribute("data-h")));
      el.addEventListener("click", () => show(+el.getAttribute("data-h")));
    });
    box.querySelector("svg").addEventListener("mouseleave", hide);
  }
  drawTrend();
  let rt; window.addEventListener("resize", () => { clearTimeout(rt); rt = setTimeout(drawTrend, 150); });
})();
