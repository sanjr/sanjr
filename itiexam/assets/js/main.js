/* ITIExam.com – UI interactions (no dependencies) */
(function () {
  "use strict";
  var nav = document.getElementById("main-nav");
  var toggle = document.querySelector(".menu-toggle");
  var desktop = window.matchMedia("(min-width: 1024px)");

  var overlay = document.querySelector(".nav-overlay");
  function setMenu(open) {
    nav.classList.toggle("open", open);
    if (overlay) overlay.hidden = !open;
    document.body.classList.toggle("nav-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    toggle.querySelector("use").setAttribute("href", open ? "#i-close" : "#i-menu");
  }
  toggle.addEventListener("click", function () { setMenu(!nav.classList.contains("open")); });
  if (overlay) overlay.addEventListener("click", function () { setMenu(false); });
  nav.addEventListener("click", function (e) { if (e.target.closest("a")) setMenu(false); });

  // Header gets a shadow once the page is scrolled
  var header = document.querySelector(".site-header");
  function onScrollHeader() { header.classList.toggle("scrolled", window.scrollY > 8); }
  window.addEventListener("scroll", onScrollHeader, { passive: true });
  onScrollHeader();
  desktop.addEventListener("change", function () { setMenu(false); });

  // Dropdowns: tap to open on mobile, keyboard on desktop
  nav.querySelectorAll(".has-dd > button").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var li = btn.parentElement;
      var open = !li.classList.contains("open");
      nav.querySelectorAll(".has-dd.open").forEach(function (o) {
        if (o !== li) { o.classList.remove("open"); o.firstElementChild.setAttribute("aria-expanded", "false"); }
      });
      li.classList.toggle("open", open);
      btn.setAttribute("aria-expanded", String(open));
    });
  });
  document.addEventListener("click", function (e) {
    if (desktop.matches && !e.target.closest(".has-dd")) {
      nav.querySelectorAll(".has-dd.open").forEach(function (o) { o.classList.remove("open"); });
    }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      setMenu(false);
      nav.querySelectorAll(".has-dd.open").forEach(function (o) { o.classList.remove("open"); });
    }
  });

  // ---- Desktop menu bar: if it does not fit next to the logo and buttons, make it more compact ----
  var navUl = nav.querySelector("ul"), logoEl = document.querySelector(".site-header .logo"), acts = document.querySelector(".nav-actions");
  function fitNav() {
    var root = document.documentElement;
    root.classList.remove("nav-tight", "nav-tight2");
    if (!desktop.matches || !navUl || !logoEl || !acts) return;
    function over() { var r = navUl.getBoundingClientRect(); return r.left < logoEl.getBoundingClientRect().right + 4 || r.right > acts.getBoundingClientRect().left - 4; }
    if (over()) { root.classList.add("nav-tight"); if (over()) root.classList.add("nav-tight2"); }
  }
  fitNav();
  window.addEventListener("resize", fitNav);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitNav);

  // ---- "Get the app" bar for Android phone visitors (closed for 14 days after ✕) ----
  var appBar = document.getElementById("app-bar");
  if (appBar && /Android/i.test(navigator.userAgent)) {
    var closedAt = 0;
    try { closedAt = +localStorage.getItem("itx_appbar") || 0; } catch (e) {}
    if (Date.now() - closedAt > 14 * 864e5) appBar.hidden = false;
    appBar.querySelector(".app-bar-x").addEventListener("click", function () {
      appBar.hidden = true;
      try { localStorage.setItem("itx_appbar", String(Date.now())); } catch (e) {}
    });
  }

  // ---- Saved lists in this browser (recent searches, recently viewed pages) ----
  function load(k) { try { return JSON.parse(localStorage.getItem(k) || "[]"); } catch (e) { return []; } }
  function save(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }

  // Pages that describe themselves (trades, tests) go into "Recently viewed"
  var rvMeta = document.querySelector('meta[name="x-rv"]');
  if (rvMeta) try {
    var cur = JSON.parse(rvMeta.content); cur.u = location.pathname;
    save("itx_rv", [cur].concat(load("itx_rv").filter(function (x) { return x.u !== cur.u; })).slice(0, 10));
  } catch (e) {}

  // A deleted page (404) leaves "Recently viewed" straight away
  if (document.querySelector('meta[name="x-gone"]')) save("itx_rv", load("itx_rv").filter(function (x) { return x.u !== location.pathname; }));

  // ---- Student account menu ----
  var stMenu = document.querySelector(".st-menu");
  if (stMenu) {
    var stBtn = stMenu.querySelector(".st-chip");
    stBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      stBtn.setAttribute("aria-expanded", String(stMenu.classList.toggle("open")));
    });
    document.addEventListener("click", function (e) {
      if (!stMenu.contains(e.target)) { stMenu.classList.remove("open"); stBtn.setAttribute("aria-expanded", "false"); }
    });
  }

  // Results finished before login are saved to the dashboard once the student logs in
  var stMeta = document.querySelector('meta[name="x-st"]'), pending = load("itx_pending");
  if (stMeta && pending.length && window.fetch) {
    save("itx_pending", []);
    Promise.all(pending.map(function (r) {
      return fetch("/api/attempt/", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json" }, body: JSON.stringify(r) })
        .then(function (res) { return res.ok || res.status === 404; }, function () { return false; })
        .then(function (ok) { if (!ok) save("itx_pending", load("itx_pending").concat([r])); return ok; });
    })).then(function (oks) {
      if (oks.some(Boolean) && document.body.classList.contains("st-dash")) location.reload();
    });
  }

  function rvRow(x) {
    return '<li><a href="' + esc(x.u) + '"><span class="sov-emo">' + esc(x.i || "📄") + '</span><span class="rt"><b>' + esc(x.t) + "</b>" +
      (x.s ? "<small>" + esc(x.s) + "</small>" : "") + '</span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>';
  }

  // Homepage strip: "Continue where you left off"
  var rvHome = document.getElementById("rv-home");
  function drawHome() {
    if (!rvHome) return;
    var list = load("itx_rv");
    rvHome.hidden = !list.length;
    rvHome.querySelector(".rv-track").innerHTML = list.slice(0, 8).map(function (x) {
      return '<a class="rv-card" href="' + esc(x.u) + '"><span class="rv-emo">' + esc(x.i || "📄") + '</span><b>' + esc(x.t) + "</b>" + (x.s ? "<small>" + esc(x.s) + "</small>" : "") + "</a>";
    }).join("");
  }
  drawHome();

  // ---- Live search panel ----
  var sov = document.getElementById("sov");
  if (sov) {
    var sq = document.getElementById("sov-q"), res = document.getElementById("sov-res"), start = sov.querySelector(".sov-start"),
        msg = sov.querySelector(".sov-msg"), all = sov.querySelector(".sov-all"), clr = sov.querySelector(".sov-clear"),
        timer, ctl, cache = {}, opener, sel = -1;

    function drawStart() {
      var rs = load("itx_rs"), rv = load("itx_rv");
      var r = document.getElementById("sov-recent"), v = document.getElementById("sov-viewed");
      r.hidden = !rs.length; v.hidden = !rv.length;
      r.querySelector(".sov-chips").innerHTML = rs.map(function (x) {
        return '<a href="/search/?q=' + encodeURIComponent(x) + '" data-q="' + esc(x) + '"><svg class="ic"><use href="#i-clock"/></svg>' + esc(x) + "</a>";
      }).join("");
      v.querySelector(".sov-list").innerHTML = rv.slice(0, 5).map(rvRow).join("");
    }
    function openSov(val) {
      opener = document.activeElement;
      drawStart();
      sov.hidden = false;
      document.body.classList.add("sov-on");
      requestAnimationFrame(function () { sov.classList.add("in"); });
      sq.value = val || "";
      update();
      setTimeout(function () { sq.focus(); }, 30);
      if (history.state !== "sov") history.pushState("sov", "");
    }
    function closeSov(fromPop) {
      if (sov.hidden) return;
      sov.classList.remove("in");
      document.body.classList.remove("sov-on");
      setTimeout(function () { sov.hidden = true; }, 180);
      if (!fromPop && history.state === "sov") history.back();
      if (opener && opener.focus) opener.focus({ preventScroll: true });
    }
    window.addEventListener("popstate", function () { closeSov(true); });
    if (history.state === "sov") history.replaceState(null, "");   // came back to a page that had the panel open

    function hl(t, words) {
      var s = esc(t);
      words.forEach(function (w) { if (w.length > 1) s = s.replace(new RegExp("(" + esc(w).replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"), "<mark>$1</mark>"); });
      return s;
    }
    function show(data) {
      var words = data.q.split(/\s+/);
      sel = -1;
      res.innerHTML = data.items.map(function (x, i) {
        return '<li role="option" id="sov-o' + i + '"><a href="' + esc(x.u) + '"><span class="sov-emo">' + esc(x.i) + '</span><span class="rt"><b>' + hl(x.t, words) + "</b>" +
          (x.s ? "<small>" + esc(x.s) + "</small>" : "") + '</span><span class="sov-k k-' + x.k.toLowerCase() + '">' + esc(x.k) + "</span></a></li>";
      }).join("");
      res.hidden = !data.items.length;
      msg.hidden = !!data.items.length;
      msg.innerHTML = data.items.length ? "" : "No results for “<b>" + esc(data.q) + "</b>”. Try another word, e.g. a trade name.";
      all.hidden = !data.items.length;
      all.href = "/search/?q=" + encodeURIComponent(data.q);
      all.innerHTML = 'See all results for “' + esc(data.q) + '” <svg class="ic"><use href="#i-arrow"/></svg>';
    }
    function update() {
      var v = sq.value.trim();
      clr.hidden = !sq.value;
      start.hidden = v.length >= 2;
      clearTimeout(timer);
      if (v.length < 2) { res.hidden = msg.hidden = all.hidden = true; sov.classList.remove("busy"); return; }
      if (cache[v.toLowerCase()]) return show(cache[v.toLowerCase()]);
      sov.classList.add("busy");
      timer = setTimeout(function () {
        if (ctl) ctl.abort();
        ctl = "AbortController" in window ? new AbortController() : null;
        fetch("/search/?json=1&q=" + encodeURIComponent(v), ctl ? { signal: ctl.signal } : {})
          .then(function (r) { return r.json(); })
          .then(function (d) { cache[v.toLowerCase()] = d; sov.classList.remove("busy"); if (sq.value.trim() === v) show(d); })
          .catch(function () { sov.classList.remove("busy"); });
      }, 160);
    }
    sq.addEventListener("input", update);
    clr.addEventListener("click", function () { sq.value = ""; update(); sq.focus(); });
    sov.querySelector(".sov-back").addEventListener("click", function () { closeSov(); });
    sov.addEventListener("click", function (e) {
      if (e.target === sov) return closeSov();
      var chip = e.target.closest("[data-q]");
      if (chip && sov.contains(chip)) { e.preventDefault(); sq.value = chip.getAttribute("data-q"); update(); sq.focus(); return; }
      var x = e.target.closest("[data-clear]");
      if (x) { save(x.getAttribute("data-clear"), []); drawStart(); drawHome(); }
    });
    sq.addEventListener("keydown", function (e) {
      var opts = res.hidden ? [] : res.querySelectorAll("li");
      if (e.key === "ArrowDown" || e.key === "ArrowUp") {
        if (!opts.length) return;
        e.preventDefault();
        sel = (sel + (e.key === "ArrowDown" ? 1 : -1) + opts.length) % opts.length;
        opts.forEach(function (o, i) { o.classList.toggle("on", i === sel); });
        sq.setAttribute("aria-activedescendant", opts[sel].id);
        opts[sel].scrollIntoView({ block: "nearest" });
      } else if (e.key === "Enter" && sel > -1 && opts[sel]) {
        e.preventDefault(); location.href = opts[sel].querySelector("a").href;
      } else if (e.key === "Escape") { closeSov(); }
    });
    sov.querySelector(".sov-bar").addEventListener("submit", function (e) {
      if (sq.value.trim().length < 2) e.preventDefault();
    });

    // Everything marked data-sov opens the panel; so does the page's own search box on phones
    document.addEventListener("click", function (e) {
      var t = e.target.closest("[data-sov]");
      if (t) { e.preventDefault(); setMenu(false); openSov(); }
    });
    var heroQ = document.querySelector("#hero-search input[type=search]");
    if (heroQ && !document.querySelector(".search-res")) heroQ.addEventListener("focus", function () {
      if (window.matchMedia("(max-width: 767px)").matches) { heroQ.blur(); openSov(heroQ.value); }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "/" && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && !e.ctrlKey && !e.metaKey) { e.preventDefault(); openSov(); }
    });
  }

  // Homepage strip "Clear" (outside the panel)
  if (rvHome) rvHome.querySelector("[data-clear]").addEventListener("click", function () { save("itx_rv", []); drawHome(); });

  // ---- Trade page: 1st Year / 2nd Year mock test tabs ----
  document.querySelectorAll(".yr-tabs").forEach(function (bar) {
    bar.addEventListener("click", function (e) {
      var b = e.target.closest("[data-yr]"); if (!b) return;
      bar.querySelectorAll("[data-yr]").forEach(function (x) { x.classList.toggle("on", x === b); x.setAttribute("aria-selected", String(x === b)); });
      bar.parentElement.querySelectorAll(".yr-pane").forEach(function (p) { p.hidden = p.getAttribute("data-yr") !== b.getAttribute("data-yr"); });
    });
  });

  // ---- Footer link groups fold up on phones ----
  var fg = document.querySelector(".foot-grid");
  if (fg && window.matchMedia("(max-width: 767px)").matches) {
    fg.classList.add("facc");
    fg.querySelectorAll("nav > h3").forEach(function (h) {
      h.setAttribute("role", "button"); h.setAttribute("tabindex", "0"); h.setAttribute("aria-expanded", "false");
      function tog() { var o = h.parentElement.classList.toggle("fopen"); h.setAttribute("aria-expanded", String(o)); }
      h.addEventListener("click", tog);
      h.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); tog(); } });
    });
  }

  // Related trades carousel arrows
  document.querySelectorAll("[data-scroll]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var track = document.getElementById(btn.getAttribute("data-target"));
      track.scrollBy({ left: Number(btn.getAttribute("data-scroll")) * track.clientWidth * 0.8, behavior: "smooth" });
    });
  });

  // Trade page tabs: highlight the section in view
  var tabs = document.querySelectorAll(".trade-tabs a, .tmenu a");
  if (tabs.length && "IntersectionObserver" in window) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        tabs.forEach(function (a) { a.classList.toggle("on", a.getAttribute("href") === "#" + en.target.id); });
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    document.querySelectorAll(".tsec[id]").forEach(function (s) { spy.observe(s); });
  }

  // Job filters start collapsed on phones and tablets
  var filters = document.getElementById("filters");
  if (filters && window.matchMedia("(max-width: 1199px)").matches) filters.removeAttribute("open");

  // Phone bottom bar: hides while scrolling down, comes back on scroll up or at the page end
  var bnav = document.querySelector(".bnav");
  if (bnav) {
    var lastY = window.scrollY, ticking = false;
    window.addEventListener("scroll", function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () {
        var y = window.scrollY, atEnd = window.innerHeight + y >= document.documentElement.scrollHeight - 40;
        if (Math.abs(y - lastY) > 6) { bnav.classList.toggle("hide", y > lastY && y > 160 && !atEnd); lastY = y; }
        ticking = false;
      });
    }, { passive: true });
    bnav.addEventListener("click", function () { if (navigator.vibrate) navigator.vibrate(8); });
  }

  // Back to top
  var top = document.querySelector(".to-top");
  window.addEventListener("scroll", function () { top.classList.toggle("show", window.scrollY > 600); }, { passive: true });

})();
