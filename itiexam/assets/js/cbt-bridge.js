/* Runs inside an uploaded CBT test: reports the finished result to the website page around it. */
(function () {
  "use strict";
  if (window.__itxBridge || window.parent === window) return;
  window.__itxBridge = 1;
  var t0 = Date.now(), started = 0, sent = false;
  document.addEventListener("click", function (e) {
    if (!started && e.target.closest && e.target.closest(".login-btn, .start-btn, #startBtn, #startExam")) started = Date.now();
  }, true);
  function send(d) {
    if (sent) return;
    sent = true;
    try { window.parent.postMessage({ itx: "result", data: d, time: Math.round((Date.now() - (started || t0)) / 1000) }, "*"); } catch (e) {}
  }
  // 1) The template's own save step (gets score, total, percent and PASS/FAIL)
  function hook() {
    var f = window.saveResultToFirebase;
    if (typeof f === "function" && !f.__itx) {
      window.saveResultToFirebase = function (d) {
        if (d) send({ score: d.score, total: d.total, pct: d.pct, status: d.status, lang: d.lang, location: d.location });
        return f.apply(this, arguments);
      };
      window.saveResultToFirebase.__itx = 1;
    }
  }
  hook();
  document.addEventListener("DOMContentLoaded", hook);
  window.addEventListener("load", hook);
  // 2) Fallback: read the result screen when it opens
  function txt(id) { var el = document.getElementById(id); return el ? el.textContent.trim() : ""; }
  var waiting = false;
  new MutationObserver(function () {
    var ov = document.getElementById("resultOverlay");
    if (sent || waiting || !ov || getComputedStyle(ov).display === "none") return;
    waiting = true;
    setTimeout(function () {
      if (sent) return;
      var score = parseFloat(txt("res-score")), total = parseFloat(txt("res-total-display")), pct = parseFloat(txt("res-percent")), b = txt("res-status-badge");
      var loc = ""; try { loc = typeof studentLocation === "string" ? studentLocation : ""; } catch (e) {}
      if (!isNaN(score)) send({ score: score, total: isNaN(total) ? 0 : total, pct: isNaN(pct) ? 0 : pct, status: /PASS/i.test(b) ? "PASS" : (/FAIL/i.test(b) ? "FAIL" : ""), location: loc });
    }, 6500);
  }).observe(document.documentElement, { attributes: true, subtree: true, attributeFilter: ["style", "class"] });
  // 3) A signed-in student's name fills the test's name box
  window.addEventListener("message", function (e) {
    if (e.source !== window.parent || !e.data || e.data.itx !== "student") return;
    var i = document.getElementById("candidateName");
    if (i && !i.value) { i.value = e.data.name; i.dispatchEvent(new Event("input", { bubbles: true })); }
  });
  // 4) Template fix: Hindi's language code is "hi", but older tests list "hin" as having written translations,
  //    so their own Hindi was never shown (machine translation instead). Add "hi" to that list.
  function hindi() { try { if (typeof MANUAL_LANGS !== "undefined" && MANUAL_LANGS.has && !MANUAL_LANGS.has("hi")) MANUAL_LANGS.add("hi"); } catch (e) {} }
  hindi();
  document.addEventListener("DOMContentLoaded", hindi);
  window.addEventListener("load", hindi);
  try { window.parent.postMessage({ itx: "ready" }, "*"); } catch (e) {}
})();
