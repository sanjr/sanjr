/* Module page: Practice (instant feedback) + Study (Q&A) */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc;
  const mid = parseInt(document.body.getAttribute("data-module"), 10);
  const mod = C.MODULES[mid - 1];
  C.renderShell("practice");
  document.title = "Module " + mid + ": " + mod.en + " – COPA MCQ";

  const all = C.byModule(mid);
  const KEY = "practice";
  const loadState = () => { const s = C.store.get(KEY, {}); s[mid] = s[mid] || { ans: {}, cur: 0, order: null }; return s; };
  let S = loadState();
  let order = (S[mid].order && S[mid].order.length === all.length) ? S[mid].order : all.map((q) => q.id);
  let cur = Math.min(S[mid].cur || 0, all.length - 1);
  const save = () => { S[mid].cur = cur; S[mid].order = order; C.store.set(KEY, S); };
  let tab = location.hash === "#study" ? "study" : "practice";

  const root = document.getElementById("module");
  const L = () => C.lang;

  function header() {
    const p = C.moduleProgress(mid);
    const accuracy = p.done ? Math.round((p.ok / p.done) * 100) : 0;
    return '<div class="breadcrumb"><a href="' + C.ROOT + 'index.html">' + t("home") + '</a> / <a href="' + C.ROOT + 'modules.html">' + t("modules") + "</a> / " + t("module") + " " + mid + "</div>" +
      '<section class="module-hero" style="background:linear-gradient(115deg,' + mod.color + ' 0%, #0b1f4d 85%)">' +
      '<span class="m-ic"><i class="' + C.iconCls(mod) + '"></i></span>' +
      "<div><p>" + t("module") + " " + mid + "</p><h1>" + esc(C.modName(mod)) + "</h1><p>" + esc(L() === "en" ? mod.mr : mod.en) + "</p></div>" +
      '<div class="m-stats"><div><b>' + all.length + "</b><span>" + t("questions") + "</span></div>" +
      "<div><b>" + p.done + "</b><span>" + t("attempted") + "</span></div>" +
      "<div><b>" + accuracy + "%</b><span>" + t("accuracy") + "</span></div></div></section>" +
      '<div class="mode-tabs no-print">' +
      '<button data-tab="practice" class="' + (tab === "practice" ? "active" : "") + '"><i class="fa-solid fa-pen-to-square"></i> ' + t("practiceMode") + "</button>" +
      '<button data-tab="study" class="' + (tab === "study" ? "active" : "") + '"><i class="fa-solid fa-book-open"></i> ' + t("studyMode") + "</button>" +
      '<a class="btn btn-yellow" href="' + C.ROOT + "exam.html?module=" + mid + '"><i class="fa-solid fa-stopwatch"></i> ' + t("testMode") + " (CBT)</a>" +
      nav() + "</div>";
  }
  function nav() {
    const prev = mid > 1 ? '<a class="btn btn-ghost btn-sm" href="' + C.modulePage(mid - 1) + '"><i class="fa-solid fa-chevron-left"></i> M' + (mid - 1) + "</a>" : "";
    const next = mid < 15 ? '<a class="btn btn-ghost btn-sm" href="' + C.modulePage(mid + 1) + '">M' + (mid + 1) + ' <i class="fa-solid fa-chevron-right"></i></a>' : "";
    return '<span style="margin-left:auto;display:flex;gap:8px">' + prev + next + "</span>";
  }

  function practiceView() {
    const q = C.qById[order[cur]];
    const a = S[mid].ans[q.id];
    const opts = [0, 1, 2, 3].map((i) => {
      let cls = "opt";
      if (a) { if (i === q.a) cls += " correct"; else if (i === a.sel) cls += " wrong"; }
      return '<button class="' + cls + '" data-opt="' + i + '" ' + (a ? "disabled" : "") + '><span class="k">' + "ABCD"[i] + '</span><span class="ot">' + esc(C.optText(q, L(), i)) +
        (L() !== "en" && C.optText(q, L(), i) !== q.en.o[i] ? '<br><small style="color:var(--ink-3)">' + esc(q.en.o[i]) + "</small>" : "") + "</span></button>";
    }).join("");
    let fb = "";
    if (a) fb = a.ok ? '<div class="feedback ok"><i class="fa-solid fa-circle-check"></i> ' + t("wellDone") + "</div>"
      : '<div class="feedback no"><i class="fa-solid fa-circle-xmark"></i> ' + t("oops") + " " + t("correctAns") + ": <b>" + "ABCD"[q.a] + ". " + esc(C.optText(q, L(), q.a)) + "</b></div>";

    const p = C.moduleProgress(mid);
    const pal = order.map((id, i) => {
      const x = S[mid].ans[id];
      return '<button data-go="' + i + '" class="' + (x ? (x.ok ? "ok" : "no") : "") + (i === cur ? " cur" : "") + '">' + (i + 1) + "</button>";
    }).join("");

    return '<div class="practice-layout"><section class="card q-card">' +
      '<div class="q-head"><span class="q-no">' + t("question") + " " + (cur + 1) + " " + t("of") + " " + order.length + '</span><span class="chip">ID ' + q.id + "</span></div>" +
      C.qBlock(q, L()) + '<div class="options">' + opts + "</div>" + fb +
      '<div class="q-nav"><button class="btn btn-ghost" id="prevQ" ' + (cur === 0 ? "disabled" : "") + '><i class="fa-solid fa-arrow-left"></i> ' + t("prev") + "</button>" +
      (cur < order.length - 1 ? '<button class="btn btn-primary" id="nextQ">' + t("next") + ' <i class="fa-solid fa-arrow-right"></i></button>'
        : '<a class="btn btn-yellow" href="' + C.ROOT + "exam.html?module=" + mid + '"><i class="fa-solid fa-flag-checkered"></i> ' + t("testMode") + "</a>") +
      "</div></section>" +
      '<aside class="card palette-card"><h4><i class="fa-solid fa-table-cells"></i> ' + t("palette") + "</h4>" +
      '<div class="score-line"><span class="chip green">' + t("correct") + ": " + p.ok + '</span><span class="chip red">' + t("wrong") + ": " + (p.done - p.ok) + "</span></div>" +
      '<div class="mini-bar" style="margin:10px 0 14px"><i style="width:' + p.pct + '%"></i></div>' +
      '<div class="palette">' + pal + "</div>" +
      '<div style="display:flex;gap:8px;margin-top:16px"><button class="btn btn-ghost btn-sm" id="shuf"><i class="fa-solid fa-shuffle"></i> ' + t("shuffle") + '</button>' +
      '<button class="btn btn-ghost btn-sm" id="rst"><i class="fa-solid fa-rotate-left"></i> ' + t("reset") + "</button></div></aside></div>";
  }

  function studyView(filter) {
    filter = (filter || "").toLowerCase();
    const items = all.filter((q) => !filter || [q.en.q, q[L()].q].concat(q.en.o, q[L()].o).join(" ").toLowerCase().indexOf(filter) > -1);
    return '<div class="toolbar no-print"><input class="input" id="sSearch" placeholder="' + t("search") + '" value="' + esc(filter) + '">' +
      '<button class="btn btn-ghost btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> ' + t("print") + "</button>" +
      '<span style="color:var(--ink-3);font-size:13.5px">' + t("showing") + " " + items.length + "/" + all.length + "</span></div>" +
      (items.length ? items.map((q) => studyItem(q)).join("") : '<div class="card empty"><i class="fa-solid fa-magnifying-glass"></i><p>' + t("noMatch") + "</p></div>");
  }
  function studyItem(q) {
    const l = L();
    return '<article class="card study-item"><div class="sq"><small>Q' + q.id.split("-")[1] + ".</small>" + esc(q[l].q) + "</div>" +
      (l !== "en" ? '<div class="alt">' + esc(q.en.q) + "</div>" : "") +
      (q.code ? '<pre class="code">' + esc(q.code) + "</pre>" : "") +
      (q.img ? '<img class="q-img" src="' + C.ROOT + q.img + '" alt="" loading="lazy">' : "") +
      "<ol>" + [0, 1, 2, 3].map((i) => '<li class="' + (i === q.a ? "ans" : "") + '">(' + "ABCD"[i] + ") " + esc(q[l].o[i]) + (i === q.a ? ' <i class="fa-solid fa-check"></i>' : "") + "</li>").join("") + "</ol>" +
      '<span class="chip green"><i class="fa-solid fa-key"></i> ' + t("correctAns") + ": " + "ABCD"[q.a] + "</span></article>";
  }

  function render() {
    root.innerHTML = header() + '<div id="view">' + (tab === "practice" ? practiceView() : studyView("")) + "</div>";
    bind();
  }
  function rerenderView(keepFocus) {
    const v = document.getElementById("view");
    if (tab === "practice") v.innerHTML = practiceView();
    else {
      const val = document.getElementById("sSearch") ? document.getElementById("sSearch").value : "";
      v.innerHTML = studyView(val);
      if (keepFocus) { const s = document.getElementById("sSearch"); s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
    }
    bindView();
  }
  function bind() {
    root.querySelectorAll("[data-tab]").forEach((b) => b.addEventListener("click", () => {
      tab = b.getAttribute("data-tab");
      history.replaceState(null, "", tab === "study" ? "#study" : "#");
      render();
    }));
    bindView();
  }
  function bindView() {
    if (tab === "practice") {
      root.querySelectorAll("[data-opt]").forEach((b) => b.addEventListener("click", () => {
        const q = C.qById[order[cur]];
        const sel = +b.getAttribute("data-opt");
        S[mid].ans[q.id] = { sel, ok: sel === q.a };
        save(); refreshHeaderStats(); rerenderView();
      }));
      root.querySelectorAll("[data-go]").forEach((b) => b.addEventListener("click", () => { cur = +b.getAttribute("data-go"); save(); rerenderView(); scrollTopCard(); }));
      const pv = document.getElementById("prevQ"), nx = document.getElementById("nextQ");
      if (pv) pv.addEventListener("click", () => { if (cur > 0) { cur--; save(); rerenderView(); scrollTopCard(); } });
      if (nx) nx.addEventListener("click", () => { if (cur < order.length - 1) { cur++; save(); rerenderView(); scrollTopCard(); } });
      document.getElementById("shuf").addEventListener("click", () => { order = C.shuffle(order); cur = 0; save(); rerenderView(); });
      document.getElementById("rst").addEventListener("click", () => { S[mid] = { ans: {}, cur: 0, order: null }; order = all.map((q) => q.id); cur = 0; C.store.set(KEY, S); render(); });
    } else {
      const s = document.getElementById("sSearch");
      if (s) s.addEventListener("input", () => rerenderView(true));
    }
  }
  function refreshHeaderStats() {
    const p = C.moduleProgress(mid);
    const el = root.querySelectorAll(".m-stats b");
    if (el.length === 3) { el[1].textContent = p.done; el[2].textContent = (p.done ? Math.round((p.ok / p.done) * 100) : 0) + "%"; }
  }
  function scrollTopCard() {
    const c = root.querySelector(".q-card");
    if (c && c.getBoundingClientRect().top < 0) c.scrollIntoView({ behavior: "smooth", block: "start" });
  }
  document.addEventListener("keydown", (e) => {
    if (tab !== "practice" || /input|textarea|select/i.test(e.target.tagName)) return;
    if (e.key === "ArrowRight" && cur < order.length - 1) { cur++; save(); rerenderView(); }
    else if (e.key === "ArrowLeft" && cur > 0) { cur--; save(); rerenderView(); }
    else if (/^[1-4a-dA-D]$/.test(e.key)) {
      const i = /\d/.test(e.key) ? +e.key - 1 : "abcd".indexOf(e.key.toLowerCase());
      const b = root.querySelector('[data-opt="' + i + '"]'); if (b && !b.disabled) b.click();
    }
  });

  render();
})();
