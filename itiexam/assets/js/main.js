/* ITIExam.com – UI interactions (no dependencies) */
(function () {
  "use strict";
  var nav = document.getElementById("main-nav");
  var toggle = document.querySelector(".menu-toggle");
  var desktop = window.matchMedia("(min-width: 1280px)");

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

  // Header search icon focuses the hero search when it is on this page
  var sLink = document.querySelector('.nav-actions a[href="/#hero-search"]');
  var q = document.getElementById("q");
  if (sLink && q) sLink.addEventListener("click", function (e) {
    e.preventDefault();
    document.getElementById("hero-search").scrollIntoView({ behavior: "smooth", block: "center" });
    setTimeout(function () { q.focus(); }, 350);
  });

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
