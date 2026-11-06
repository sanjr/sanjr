/* Result rendering (shared by exam.html) */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc;

  function evaluate(sess) {
    let correct = 0, wrong = 0, un = 0;
    const per = {};
    const rows = sess.qids.map((id, i) => {
      const q = C.qById[id];
      const sel = sess.ans[i]; // original option index or null
      const st = sess.status[i];
      const counted = sel != null && (st === "ans" || st === "amr");
      per[q.m] = per[q.m] || { m: q.m, total: 0, c: 0, w: 0, u: 0 };
      per[q.m].total++;
      let r;
      if (!counted) { un++; per[q.m].u++; r = "u"; }
      else if (sel === q.a) { correct++; per[q.m].c++; r = "c"; }
      else { wrong++; per[q.m].w++; r = "w"; }
      return { id, sel: counted ? sel : null, r };
    });
    const total = sess.qids.length;
    const pct = total ? Math.round((correct / total) * 1000) / 10 : 0;
    return { correct, wrong, un, total, pct, per: Object.values(per).sort((a, b) => a.m - b.m), rows };
  }

  function render(container, rec, opts) {
    opts = opts || {};
    const l = C.lang;
    const pass = rec.pct >= C.PASS;
    const pctInt = Math.round(rec.pct);
    container.innerHTML =
      '<div class="breadcrumb"><a href="index.html">' + t("home") + '</a> / <a href="dashboard.html">' + t("dashboard") + "</a> / " + t("resultTitle") + "</div>" +
      '<section class="result-hero"><div class="ring" style="--p:' + pctInt + '" data-label="' + rec.pct + '%"></div>' +
      "<div><h2>" + t("resultTitle") + "</h2><div>" + esc(rec.title) + "</div>" +
      '<div style="opacity:.85;font-size:14px;margin-top:4px">' + esc(rec.name || "") + (rec.roll ? " • " + t("rollNo") + " " + esc(rec.roll) : "") + " • " + new Date(rec.date).toLocaleString() + "</div>" +
      '<span class="verdict ' + (pass ? "pass" : "fail") + '">' + (pass ? t("pass") : t("fail")) + '</span> <small style="opacity:.8;margin-left:8px">' + t("passMark") + "</small></div>" +
      '<div style="display:flex;flex-direction:column;gap:10px">' +
      '<a class="btn btn-yellow" href="exam.html' + (rec.cfg && rec.cfg.preset === "module" ? "?module=" + rec.cfg.mods[0] : "") + '"><i class="fa-solid fa-rotate-right"></i> ' + t("retake") + "</a>" +
      '<a class="btn btn-outline" style="background:transparent;color:#fff;border-color:#fff" href="dashboard.html"><i class="fa-solid fa-gauge-high"></i> ' + t("myDash") + "</a></div></section>" +

      '<div class="r-stats">' +
      stat(rec.correct + "/" + rec.total, t("marks"), "var(--blue-600)") +
      stat(rec.correct, t("correct"), "var(--green)") +
      stat(rec.wrong, t("wrong"), "var(--red)") +
      stat(rec.un, t("unattempted"), "var(--ink-3)") +
      "</div>" +
      '<div class="r-stats" style="margin-top:-6px">' +
      stat(rec.pct + "%", t("percentage"), "var(--purple)") +
      stat(C.fmtTime(rec.timeTaken), t("timeTaken"), "var(--orange)") +
      stat((rec.correct + rec.wrong) ? Math.round((rec.correct / (rec.correct + rec.wrong)) * 100) + "%" : "0%", t("accuracy"), "var(--teal)") +
      stat(rec.total, t("questions"), "var(--navy-800)") +
      "</div>" +

      '<section class="card card-pad"><h3><i class="fa-solid fa-chart-pie" style="color:var(--blue-600)"></i> ' + t("moduleWise") + '</h3><div class="table-wrap"><table class="tbl"><thead><tr><th>' + t("module") + "</th><th>" + t("questions") + "</th><th>" + t("correct") + "</th><th>" + t("wrong") + "</th><th>" + t("unattempted") + "</th><th>" + t("score") + "</th></tr></thead><tbody>" +
      rec.per.map((p) => {
        const s = Math.round((p.c / p.total) * 100);
        return "<tr><td><b>M" + p.m + "</b> " + esc(C.modName(p.m)) + "</td><td>" + p.total + '</td><td style="color:var(--green);font-weight:600">' + p.c + '</td><td style="color:var(--red);font-weight:600">' + p.w + "</td><td>" + p.u +
          '</td><td><div style="display:flex;align-items:center;gap:8px"><div class="mini-bar" style="flex:1"><i style="width:' + s + "%;background:" + (s >= C.PASS ? "var(--green)" : "var(--red)") + '"></i></div><b>' + s + "%</b></div></td></tr>";
      }).join("") + "</tbody></table></div></section>" +

      '<div class="section-title"><h2>' + t("reviewAns") + '</h2><div class="mode-tabs" style="margin:0" id="revTabs">' +
      '<button class="active" data-f="all">' + t("all") + " (" + rec.total + ")</button>" +
      '<button data-f="c">' + t("correct") + " (" + rec.correct + ")</button>" +
      '<button data-f="w">' + t("wrong") + " (" + rec.wrong + ")</button>" +
      '<button data-f="u">' + t("unattempted") + " (" + rec.un + ")</button></div></div>" +
      '<div id="revList"></div>';

    const list = container.querySelector("#revList");
    function draw(f) {
      const rows = rec.rows.filter((r) => f === "all" || r.r === f);
      list.innerHTML = rows.length ? rows.map((r) => {
        const q = C.qById[r.id];
        const n = rec.rows.indexOf(r) + 1;
        return '<article class="card review-item ' + r.r + '"><div class="q-head"><span class="q-no">' + t("question") + " " + n + '</span><span class="chip ' + (r.r === "c" ? "green" : r.r === "w" ? "red" : "") + '">' +
          (r.r === "c" ? t("correct") : r.r === "w" ? t("wrong") : t("notAnsweredShort")) + " • M" + q.m + "</span></div>" + C.qBlock(q, l) +
          '<div class="options">' + [0, 1, 2, 3].map((i) => {
            let cls = "opt";
            if (i === q.a) cls += " correct"; else if (i === r.sel) cls += " wrong";
            return '<div class="' + cls + '"><span class="k">' + "ABCD"[i] + '</span><span class="ot">' + esc(C.optText(q, l, i)) + (i === r.sel ? ' <small>(' + t("yourAns") + ")</small>" : "") + "</span></div>";
          }).join("") + "</div></article>";
      }).join("") : '<div class="card empty"><i class="fa-regular fa-face-smile"></i><p>—</p></div>';
    }
    draw("all");
    container.querySelectorAll("#revTabs button").forEach((b) => b.addEventListener("click", () => {
      container.querySelectorAll("#revTabs button").forEach((x) => x.classList.remove("active"));
      b.classList.add("active"); draw(b.getAttribute("data-f"));
    }));
    if (opts.scroll !== false) window.scrollTo(0, 0);
  }
  function stat(v, l, c) { return '<div class="card r-stat"><b style="color:' + c + '">' + v + "</b><span>" + l + "</span></div>"; }

  window.COPA_RESULT = { evaluate, render };
})();
