/* Theory Notes: "Read in" language picker (Google Translate in the browser), year tabs, search,
   reading progress saved on this device, and the lesson reader tools. */
(function () {
  'use strict';
  var root = document.querySelector('[data-notes]');
  if (!root) return;
  var slug = root.getAttribute('data-notes');
  function ls(k, v) {                         // localStorage that never throws (private mode, blocked storage)
    try { if (v === undefined) return localStorage.getItem(k); if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v); } catch (e) {}
    return null;
  }
  function json(k, d) { try { return JSON.parse(ls(k)) || d; } catch (e) { return d; } }

  /* ================= language ================= */
  var picker = document.querySelector('[data-nlang]');
  var LKEY = 'notes:lang';
  function cookieLang() { var m = document.cookie.match(/(?:^|;\s*)googtrans=\/[^/]*\/([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }
  function setCookie(v) {
    var host = location.hostname, parts = host.split('.'), doms = ['', host];
    if (parts.length > 1) doms.push('.' + parts.slice(-2).join('.'), '.' + host);
    doms.forEach(function (d) {
      var dom = d ? '; domain=' + d : '';
      document.cookie = 'googtrans=' + (v ? '/en/' + v : '') + '; path=/' + dom + (v ? '; max-age=31536000' : '; expires=Thu, 01 Jan 1970 00:00:00 GMT');
    });
  }
  var gtLoaded = false, gtTimer = null;
  function loadGT() {
    if (gtLoaded) return; gtLoaded = true;
    var codes = [].map.call(picker.querySelectorAll('[data-lang]'), function (b) { return b.getAttribute('data-lang'); }).filter(function (c) { return c !== 'en'; });
    window.nlangInit = function () {
      try { new google.translate.TranslateElement({ pageLanguage: 'en', includedLanguages: codes.join(','), autoDisplay: false }, 'gt-el'); } catch (e) { fail(); }
    };
    var s = document.createElement('script');
    s.src = 'https://translate.google.com/translate_a/element.js?cb=nlangInit';
    s.async = true; s.onerror = fail;
    document.head.appendChild(s);
  }
  function busy(on) { picker.classList.toggle('is-busy', !!on); }
  function fail() {
    busy(false);
    if (picker.querySelector('.nlang-fail')) return;
    var p = document.createElement('p'); p.className = 'nlang-fail';
    p.textContent = 'Translation could not load. Check your internet and try again.';
    picker.appendChild(p);
  }
  function label(code) {
    var b = picker.querySelector('[data-lang="' + code + '"]') || picker.querySelector('[data-lang="en"]');
    picker.querySelector('[data-nlang-cur]').textContent = b.querySelector('b').textContent;
    [].forEach.call(picker.querySelectorAll('[data-lang]'), function (x) { x.setAttribute('aria-current', x === b ? 'true' : 'false'); });
  }
  function translated() { var c = document.documentElement.className; return /translated-(ltr|rtl)/.test(c); }
  function watch() {                          // spinner until Google has translated the page
    clearTimeout(gtTimer); busy(true);
    var t0 = Date.now();
    (function tick() {
      if (translated()) return busy(false);
      if (Date.now() - t0 > 15000) return fail();
      gtTimer = setTimeout(tick, 300);
    })();
  }
  function choose(code) {
    label(code);
    if (code === 'en') { ls(LKEY, null); setCookie(''); if (cookieLang() || translated()) location.reload(); return; }
    ls(LKEY, code); setCookie(code);
    var combo = document.querySelector('#gt-el select.goog-te-combo');
    if (combo) { combo.value = code; combo.dispatchEvent(new Event('change')); watch(); }
    else { loadGT(); watch(); waitCombo(code); }
  }
  function waitCombo(code) {                  // the cookie usually translates on load; if not, pick the language in Google's list
    var n = 0;
    (function tick() {
      var combo = document.querySelector('#gt-el select.goog-te-combo');
      if (combo && combo.options.length > 1) { if (!translated() && combo.value !== code) { combo.value = code; combo.dispatchEvent(new Event('change')); } return; }
      if (++n < 60) setTimeout(tick, 250);
    })();
  }
  if (picker) {
    var btn = picker.querySelector('.nlang-btn'), pop = picker.querySelector('.nlang-pop');
    function open(on) { pop.hidden = !on; btn.setAttribute('aria-expanded', on ? 'true' : 'false'); }
    btn.addEventListener('click', function (e) { e.stopPropagation(); open(pop.hidden); });
    document.addEventListener('click', function (e) { if (!picker.contains(e.target)) open(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') open(false); });
    pop.addEventListener('click', function (e) { var b = e.target.closest('[data-lang]'); if (b) { open(false); choose(b.getAttribute('data-lang')); } });
    var want = cookieLang() || ls(LKEY) || '';
    if (want && want !== 'en' && picker.querySelector('[data-lang="' + want + '"]')) {
      if (!cookieLang()) setCookie(want);
      label(want); loadGT(); watch(); waitCombo(want);
    } else label('en');
  }

  /* ================= progress (saved on this device) ================= */
  var PKEY = 'notes:' + slug, read = json(PKEY, []);
  function isRead(id) { return read.indexOf(+id) > -1; }
  function saveRead(id, on) {
    id = +id; var i = read.indexOf(id);
    if (on && i < 0) read.push(id); if (!on && i > -1) read.splice(i, 1);
    ls(PKEY, JSON.stringify(read));
  }
  function paintDots() {
    [].forEach.call(document.querySelectorAll('a[data-lid]'), function (a) { a.classList.toggle('is-read', isRead(a.getAttribute('data-lid'))); });
  }
  paintDots();

  /* ================= index page ================= */
  if (root.classList.contains('ni-page')) {
    var all = document.querySelectorAll('.ni-lessons a[data-lid]'), done = 0;
    [].forEach.call(all, function (a) { if (isRead(a.getAttribute('data-lid'))) done++; });
    if (done) {
      var pr = document.querySelector('.ni-prog');
      if (pr) { pr.hidden = false; pr.querySelector('[data-prog]').textContent = Math.round(done * 100 / all.length) + '%'; }
    }
    [].forEach.call(document.querySelectorAll('[data-mod]'), function (m) {
      var ls_ = m.querySelectorAll('a[data-lid]'), d = 0;
      [].forEach.call(ls_, function (a) { if (isRead(a.getAttribute('data-lid'))) d++; });
      m.querySelector('[data-mbar]').style.width = (ls_.length ? d * 100 / ls_.length : 0) + '%';
      if (d) m.querySelector('[data-mdone]').textContent = d === ls_.length ? '✓ Completed' : d + '/' + ls_.length + ' read';
    });
    // continue where the student stopped
    var last = json(PKEY + ':last', null), cont = document.querySelector('[data-continue]'), lastYear = null;
    if (last && cont) {
      var link = document.querySelector('.ni-lessons a[data-lid="' + last.id + '"]');
      if (link) {
        cont.href = link.href;
        cont.querySelector('[data-continue-t]').textContent = 'Continue reading';
        cont.querySelector('[data-continue-b]').textContent = last.t || link.querySelector('b').textContent;
        var yp = link.closest('[data-yp]'); if (yp) lastYear = yp.getAttribute('data-yp');
      }
    }
    // year tabs
    var tabs = document.querySelectorAll('.ni-tabs [data-year]'), panels = document.querySelectorAll('[data-yp]');
    function showYear(y) {
      [].forEach.call(tabs, function (t) { t.setAttribute('aria-selected', t.getAttribute('data-year') === y ? 'true' : 'false'); });
      [].forEach.call(panels, function (p) { p.hidden = p.getAttribute('data-yp') !== y; });
      try { history.replaceState(null, '', y === '1' ? location.pathname : '#year-' + y); } catch (e) {}
    }
    [].forEach.call(tabs, function (t) {
      t.addEventListener('click', function () { showYear(t.getAttribute('data-year')); });
      t.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        var arr = [].slice.call(tabs), i = arr.indexOf(t) + (e.key === 'ArrowRight' ? 1 : -1), n = arr[(i + arr.length) % arr.length];
        n.focus(); n.click();
      });
    });
    var hy = (location.hash.match(/^#year-(\d)$/) || [])[1];
    if (tabs.length && (hy || lastYear)) showYear(hy || lastYear);
    // search across all years
    var q = document.querySelector('[data-nsearch]'), none = document.querySelector('.ni-none');
    if (q) q.addEventListener('input', function () {
      var v = q.value.trim().toLowerCase(), any = false;
      [].forEach.call(document.querySelectorAll('[data-mod]'), function (m) {
        var hit = 0;
        [].forEach.call(m.querySelectorAll('li[data-q]'), function (li) { var ok = !v || li.getAttribute('data-q').indexOf(v) > -1; li.hidden = !ok; if (ok) hit++; });
        m.hidden = !hit; if (hit) any = true;
      });
      if (v) [].forEach.call(panels, function (p) { p.hidden = !p.querySelector('[data-mod]:not([hidden])'); });
      else { var sel = document.querySelector('.ni-tabs [aria-selected="true"]'); [].forEach.call(panels, function (p) { p.hidden = sel ? p.getAttribute('data-yp') !== sel.getAttribute('data-year') : false; }); }
      if (none) none.hidden = any || !v;
    });
  }

  /* ================= lesson page ================= */
  if (root.classList.contains('nl-page')) {
    var lid = root.getAttribute('data-lesson'), art = document.querySelector('.nl-article');
    var title = (document.querySelector('.nl-head h1') || {}).textContent || '';
    ls(PKEY + ':last', JSON.stringify({ id: +lid, t: title.trim().replace(/\s+/g, ' ') }));
    var mark = document.querySelector('[data-mark]');
    function paintMark() { var on = isRead(lid); mark.classList.toggle('on', on); mark.querySelector('[data-mark-t]').textContent = on ? 'Marked as read' : 'Mark as read'; paintDots(); }
    if (mark) { paintMark(); mark.addEventListener('click', function () { saveRead(lid, !isRead(lid)); paintMark(); }); }
    // reading progress bar; reaching the end marks the lesson as read
    var bar = document.querySelector('.nl-progress span'), auto = false;
    function onScroll() {
      var r = art.getBoundingClientRect(), h = r.height - innerHeight * .6, p = h > 0 ? Math.min(1, Math.max(0, -r.top / h)) : 1;
      bar.style.width = (p * 100) + '%';
      if (p > .97 && !auto) { auto = true; if (!isRead(lid)) { saveRead(lid, true); if (mark) paintMark(); } }
    }
    addEventListener('scroll', onScroll, { passive: true }); onScroll();
    // text size
    var FKEY = 'notes:fs', fs = +ls(FKEY) || 16;
    function setFs(v) { fs = Math.min(22, Math.max(14, v)); art.style.setProperty('--fs', fs + 'px'); ls(FKEY, String(fs)); }
    if (+ls(FKEY)) setFs(fs);
    [].forEach.call(document.querySelectorAll('[data-font]'), function (b) { b.addEventListener('click', function () { setFs(fs + (b.getAttribute('data-font') === '+' ? 1 : -1)); }); });
    var pb = document.querySelector('[data-print]'); if (pb) pb.addEventListener('click', function () { window.print(); });
    var sb = document.querySelector('[data-share]');
    if (sb) sb.addEventListener('click', function () {
      var d = { title: document.title, url: location.href.split('#')[0] };
      if (navigator.share) navigator.share(d).catch(function () {});
      else if (navigator.clipboard) navigator.clipboard.writeText(d.url).then(function () { sb.lastChild.textContent = ' Link copied'; });
    });
    // tap a picture to zoom
    var zoom = document.querySelector('.nl-zoom');
    if (zoom) {
      art.addEventListener('click', function (e) {
        var img = e.target.closest('.note-body img, .nl-cover img'); if (!img || img.closest('a')) return;
        zoom.querySelector('img').src = img.currentSrc || img.src; zoom.querySelector('img').alt = img.alt; zoom.hidden = false;
      });
      zoom.addEventListener('click', function () { zoom.hidden = true; });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') zoom.hidden = true; });
    }
    // keyboard: ← → move between lessons
    document.addEventListener('keydown', function (e) {
      if (e.target.closest('input, textarea, select') || e.altKey || e.ctrlKey || e.metaKey) return;
      var a = e.key === 'ArrowLeft' ? document.querySelector('a[rel="prev"]') : e.key === 'ArrowRight' ? document.querySelector('a[rel="next"]') : null;
      if (a) location.href = a.href;
    });
  }
})();
