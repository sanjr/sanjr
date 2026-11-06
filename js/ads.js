/* =========================================================
   COPA MCQ – Google AdSense loader
   Settings come from (highest priority first):
     1. Admin Panel → Ads & Settings (saved online with Firebase,
        or in this browser in local mode)
     2. js/site-config.js → adsense
   Ads are never shown on the admin panel or inside a running
   CBT exam (pages with <body data-noads>, or body.cbt).
   ========================================================= */
(function () {
  "use strict";
  function settings() {
    const base = (window.COPA_CONFIG && window.COPA_CONFIG.adsense) || {};
    let saved = null;
    try { saved = JSON.parse(localStorage.getItem("copa_db_settings") || "null"); } catch (e) { saved = null; }
    const a = Object.assign({}, base, (saved && saved.adsense) || {});
    a.slots = Object.assign({}, base.slots || {}, ((saved && saved.adsense) || {}).slots || {});
    return a;
  }
  const A = settings();
  const client = String(A.client || "").trim();
  const on = !!A.enabled && /^ca-pub-\d{10,20}$/.test(client);
  // no ads on the admin panel, legal pages, or the exam instructions / running CBT exam
  const examStage = /exam\.html$/.test(location.pathname) && /[?&]stage=/.test(location.search);
  const blocked = () => examStage || (document.body && (document.body.hasAttribute("data-noads") || document.body.classList.contains("cbt")));

  if (on) {
    // account verification meta + AdSense script (also enables Auto ads if turned on in AdSense)
    const meta = document.createElement("meta");
    meta.name = "google-adsense-account"; meta.content = client;
    document.head.appendChild(meta);
  }

  let loaded = false;
  function loadScript() {
    if (loaded || !on || blocked()) return;
    loaded = true;
    const s = document.createElement("script");
    s.async = true;
    s.crossOrigin = "anonymous";
    s.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=" + encodeURIComponent(client);
    document.head.appendChild(s);
  }

  function fill() {
    if (!on || blocked()) return;
    loadScript();
    document.querySelectorAll(".ad-slot:not([data-filled])").forEach((el) => {
      const id = String((A.slots || {})[el.getAttribute("data-slot")] || "").trim();
      el.setAttribute("data-filled", "1");
      if (!/^\d{6,12}$/.test(id)) return; // no manual unit for this position – Auto ads may still place ads
      el.innerHTML = '<span class="ad-label">Advertisement</span><ins class="adsbygoogle" style="display:block" data-ad-client="' + client +
        '" data-ad-slot="' + id + '" data-ad-format="auto" data-full-width-responsive="true"></ins>';
      el.classList.add("on");
      try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (e) { /* ignore */ }
    });
  }

  function start() {
    fill();
    if ("MutationObserver" in window) {
      let t;
      new MutationObserver(() => { clearTimeout(t); t = setTimeout(fill, 200); }).observe(document.body, { childList: true, subtree: true });
    }
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start); else start();

  window.COPA_ADS = { settings: A, enabled: on, fill };
})();
