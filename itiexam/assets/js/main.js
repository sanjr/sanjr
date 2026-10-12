/* ITIExam.com – UI interactions (no dependencies) */
(function () {
  "use strict";
  var nav = document.getElementById("main-nav");
  var toggle = document.querySelector(".menu-toggle");
  var desktop = window.matchMedia("(min-width: 1280px)");

  function setMenu(open) {
    nav.classList.toggle("open", open);
    document.body.classList.toggle("nav-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    toggle.querySelector("use").setAttribute("href", open ? "#i-close" : "#i-menu");
  }
  toggle.addEventListener("click", function () { setMenu(!nav.classList.contains("open")); });
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

  // Header search icon focuses hero search
  var sLink = document.querySelector('.nav-actions a[href="#hero-search"]');
  if (sLink) sLink.addEventListener("click", function () { setTimeout(function () { document.getElementById("q").focus(); }, 350); });

  // Back to top
  var top = document.querySelector(".to-top");
  window.addEventListener("scroll", function () { top.classList.toggle("show", window.scrollY > 600); }, { passive: true });

  document.getElementById("yr").textContent = new Date().getFullYear();
})();
