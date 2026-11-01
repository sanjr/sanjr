/* Static preview: pages are plain HTML. Saving data needs the real PHP + MySQL website. */
(function () {
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.getAttribute('data-static-nav')) { return; }
    ev.preventDefault();
    window.alert('This is the static HTML preview.\nSaving data needs the real website (PHP + MySQL) — see README.md.');
  }, true);
})();
