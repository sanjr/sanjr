/* ITI CareerTrack — small vanilla JS (no framework, no CDN). Charts are drawn as SVG. */
(function () {
  'use strict';
  var SVGNS = 'http://www.w3.org/2000/svg';
  var PALETTE = ['#1d64d8', '#17944a', '#7c3aed', '#ea7a0a', '#0ea5a4', '#d33a2c', '#c27803', '#64748b'];

  function el(name, attrs, text) {
    var n = document.createElementNS(SVGNS, name);
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (text !== undefined) n.textContent = text;
    return n;
  }
  function fmt(v) { return Number(v).toLocaleString('en-IN'); }

  // ---------------- horizontal bars (trade-wise students)
  function hbar(box, d) {
    var w = 520, rowH = 30, h = d.labels.length * rowH + 10;
    var labelW = Math.min(210, Math.max(80, Math.max.apply(null, d.labels.map(function (l) { return String(l).length; })) * 7 + 14));
    var max = Math.max.apply(null, d.values.concat([1]));
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': d.title || 'bar chart' });
    d.labels.forEach(function (lab, i) {
      var y = 5 + i * rowH, bw = (w - labelW - 60) * d.values[i] / max;
      svg.appendChild(el('text', { x: labelW - 8, y: y + 18, 'text-anchor': 'end', 'font-size': 12 }, lab));
      svg.appendChild(el('rect', { x: labelW, y: y + 5, width: Math.max(bw, 2), height: 18, rx: 3, fill: d.color || PALETTE[0] }));
      svg.appendChild(el('text', { x: labelW + bw + 6, y: y + 18, 'font-size': 12, 'font-weight': 700 }, fmt(d.values[i]) + (d.suffix || '')));
    });
    box.appendChild(svg);
  }

  // ---------------- vertical bars (reports)
  function vbar(box, d) {
    var w = 560, h = 260, padL = 40, padB = 40, padT = 24;
    var max = d.max || Math.max.apply(null, d.values.concat([1]));
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': d.title || 'bar chart' });
    for (var g = 0; g <= 5; g++) {
      var gy = padT + (h - padT - padB) * (1 - g / 5);
      svg.appendChild(el('line', { x1: padL, x2: w - 10, y1: gy, y2: gy, stroke: '#e7ebf2' }));
      svg.appendChild(el('text', { x: padL - 6, y: gy + 4, 'text-anchor': 'end', 'font-size': 10 }, Math.round(max * g / 5) + (d.suffix || '')));
    }
    var n = d.labels.length, slot = (w - padL - 10) / Math.max(n, 1), bw = Math.min(46, slot * 0.6);
    d.labels.forEach(function (lab, i) {
      var v = d.values[i], bh = (h - padT - padB) * v / max, x = padL + slot * i + (slot - bw) / 2, y = h - padB - bh;
      svg.appendChild(el('rect', { x: x, y: y, width: bw, height: Math.max(bh, 1), rx: 4, fill: d.color || PALETTE[0] }));
      svg.appendChild(el('text', { x: x + bw / 2, y: y - 6, 'text-anchor': 'middle', 'font-size': 11, 'font-weight': 700 }, fmt(v) + (d.suffix || '')));
      var maxC = Math.max(4, Math.floor(slot / 6.6));
      svg.appendChild(el('text', { x: x + bw / 2, y: h - padB + 16, 'text-anchor': 'middle', 'font-size': 11 }, lab.length > maxC ? lab.slice(0, maxC - 1) + '…' : lab));
    });
    box.appendChild(svg);
  }

  // ---------------- line (placement trend)
  function line(box, d) {
    var w = 520, h = 230, padL = 58, padB = 30, padT = 14;
    var max = Math.max.apply(null, d.values.concat([1])) * 1.15;
    var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, role: 'img', 'aria-label': d.title || 'line chart' });
    for (var g = 0; g <= 4; g++) {
      var gy = padT + (h - padT - padB) * (1 - g / 4);
      svg.appendChild(el('line', { x1: padL, x2: w - 10, y1: gy, y2: gy, stroke: '#e7ebf2' }));
      svg.appendChild(el('text', { x: padL - 6, y: gy + 4, 'text-anchor': 'end', 'font-size': 10 }, fmt(Math.round(max * g / 4))));
    }
    var n = d.values.length, pts = d.values.map(function (v, i) {
      return [padL + 14 + (w - padL - 44) * (n === 1 ? 0.5 : i / (n - 1)), padT + (h - padT - padB) * (1 - v / max)];
    });
    var area = 'M' + pts[0][0] + ',' + (h - padB) + ' L' + pts.map(function (p) { return p.join(','); }).join(' L') + ' L' + pts[n - 1][0] + ',' + (h - padB) + ' Z';
    svg.appendChild(el('path', { d: area, fill: 'rgba(29,100,216,.08)' }));
    svg.appendChild(el('polyline', { points: pts.map(function (p) { return p.join(','); }).join(' '), fill: 'none', stroke: PALETTE[0], 'stroke-width': 2.5 }));
    pts.forEach(function (p, i) {
      svg.appendChild(el('circle', { cx: p[0], cy: p[1], r: 4.5, fill: PALETTE[0] }));
      svg.appendChild(el('text', { x: p[0], y: p[1] - 9, 'text-anchor': 'middle', 'font-size': 10.5, 'font-weight': 700 }, fmt(d.values[i])));
      svg.appendChild(el('text', { x: p[0], y: h - 10, 'text-anchor': 'middle', 'font-size': 11 }, d.labels[i]));
    });
    box.appendChild(svg);
  }

  // ---------------- donut (career outcomes)
  function donut(box, d) {
    var size = 190, r = 70, sw = 28, c = 2 * Math.PI * r, total = d.values.reduce(function (a, b) { return a + b; }, 0) || 1;
    var svg = el('svg', { viewBox: '0 0 ' + size + ' ' + size, role: 'img', 'aria-label': d.title || 'donut chart', style: 'max-width:' + size + 'px' });
    svg.appendChild(el('circle', { cx: size / 2, cy: size / 2, r: r, fill: 'none', stroke: '#e7ebf2', 'stroke-width': sw }));
    var offset = 0;
    d.values.forEach(function (v, i) {
      var len = c * v / total;
      svg.appendChild(el('circle', { cx: size / 2, cy: size / 2, r: r, fill: 'none', stroke: (d.colors || PALETTE)[i % 8], 'stroke-width': sw,
        'stroke-dasharray': len + ' ' + (c - len), 'stroke-dashoffset': -offset, transform: 'rotate(-90 ' + size / 2 + ' ' + size / 2 + ')' }));
      offset += len;
    });
    if (d.center) {
      svg.appendChild(el('text', { x: size / 2, y: size / 2 + 2, 'text-anchor': 'middle', 'font-size': 24, 'font-weight': 800, fill: '#1b2333' }, d.center));
      svg.appendChild(el('text', { x: size / 2, y: size / 2 + 20, 'text-anchor': 'middle', 'font-size': 11 }, d.centerLabel || ''));
    }
    box.appendChild(svg);
  }

  document.querySelectorAll('[data-chart]').forEach(function (box) {
    try {
      var d = JSON.parse(box.getAttribute('data-chart'));
      ({ hbar: hbar, vbar: vbar, line: line, donut: donut })[d.type](box, d);
    } catch (e) { box.textContent = 'Chart unavailable'; }
  });

  // ---------------- sidebar toggle (mobile)
  var t = document.querySelector('[data-toggle-sidebar]');
  if (t) t.addEventListener('click', function () { document.getElementById('sidebar').classList.toggle('open'); });

  // ---------------- login: show/hide password
  document.querySelectorAll('[data-pw-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = document.getElementById(b.getAttribute('data-pw-toggle'));
      i.type = i.type === 'password' ? 'text' : 'password';
      b.setAttribute('aria-label', i.type === 'password' ? 'Show password' : 'Hide password');
    });
  });
  // ---------------- login: choose role (and fill demo account in DEMO mode)
  document.querySelectorAll('.role-pick [data-role]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('.role-pick [data-role]').forEach(function (x) { x.classList.remove('sel'); x.setAttribute('aria-checked', 'false'); });
      var hidden = document.getElementById('login-role'), lbl = document.getElementById('role-label');
      b.classList.add('sel'); b.setAttribute('aria-checked', 'true'); hidden.value = b.getAttribute('data-role');
      lbl.innerHTML = 'Signing in as <b></b>'; lbl.querySelector('b').textContent = b.getAttribute('data-label');
      var demo = b.getAttribute('data-demo-email');
      if (demo) { document.getElementById('email').value = demo; document.getElementById('password').value = 'Demo@1234'; }
    });
  });

  // ---------------- user form: show only the scope fields the chosen role needs
  var rsel = document.querySelector('[data-role-select]');
  if (rsel) {
    var form = rsel.closest('form');
    var sync = function () {
      var opt = rsel.options[rsel.selectedIndex] || {}, scope = opt.getAttribute ? opt.getAttribute('data-scope') : '', code = opt.getAttribute ? opt.getAttribute('data-code') : '';
      form.querySelectorAll('[data-for]').forEach(function (f) {
        var want = f.getAttribute('data-for'), show = want === scope || (want === 'TEACHER' && code === 'TEACHER');
        f.style.display = show ? '' : 'none';
      });
    };
    rsel.addEventListener('change', sync); sync();
  }

  // ---------------- print buttons (no inline JS: CSP blocks it)
  document.querySelectorAll('[data-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });

  // ---------------- confirm buttons
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { if (!window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault(); });
  });

  // ---------------- auto-submit selects
  document.querySelectorAll('[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });

  // ---------------- admission wizard
  var wiz = document.querySelector('[data-wizard]');
  if (wiz) {
    var panes = wiz.querySelectorAll('.wizard-pane'), steps = wiz.querySelectorAll('.steps .step'), cur = 0;
    var startAt = parseInt(wiz.getAttribute('data-start') || '0', 10);
    function show(i) {
      cur = i;
      panes.forEach(function (p, k) { p.classList.toggle('active', k === i); });
      steps.forEach(function (s, k) { s.classList.toggle('active', k === i); s.classList.toggle('done', k < i); });
      var last = i === panes.length - 1;
      wiz.querySelectorAll('[data-prev]').forEach(function (b) { b.style.visibility = i === 0 ? 'hidden' : 'visible'; });
      wiz.querySelectorAll('[data-next]').forEach(function (b) { b.style.display = last ? 'none' : ''; });
      wiz.querySelectorAll('[data-submit]').forEach(function (b) { b.style.display = last ? '' : 'none'; });
      if (i === panes.length - 1) fillReview();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function valid(i) {
      var ok = true;
      panes[i].querySelectorAll('input,select,textarea').forEach(function (inp) {
        if (!inp.checkValidity()) { ok = false; inp.closest('.field') && inp.closest('.field').classList.add('has-error'); }
        else if (inp.closest('.field')) inp.closest('.field').classList.remove('has-error');
      });
      if (!ok) { var first = panes[i].querySelector(':invalid'); if (first) { first.reportValidity(); } }
      return ok;
    }
    function fillReview() {
      var box = wiz.querySelector('[data-review]');
      if (!box) return;
      box.innerHTML = '';
      wiz.querySelectorAll('[data-review-label]').forEach(function (inp) {
        var v = inp.tagName === 'SELECT' ? (inp.selectedIndex > 0 ? inp.options[inp.selectedIndex].text : '') : (inp.type === 'file' ? (inp.files[0] ? inp.files[0].name : '') : inp.value);
        if (inp.type === 'checkbox') v = inp.checked ? 'Yes' : 'No';
        if (inp.name === 'aadhaar' && v) v = 'XXXX XXXX ' + v.replace(/\D/g, '').slice(-4);
        var row = document.createElement('div');
        var s = document.createElement('span'); s.textContent = inp.getAttribute('data-review-label');
        var b = document.createElement('b'); b.textContent = v || '—';
        row.appendChild(s); row.appendChild(b); box.appendChild(row);
      });
    }
    wiz.querySelectorAll('[data-next]').forEach(function (b) { b.addEventListener('click', function () { if (valid(cur)) show(Math.min(cur + 1, panes.length - 1)); }); });
    wiz.querySelectorAll('[data-prev]').forEach(function (b) { b.addEventListener('click', function () { show(Math.max(cur - 1, 0)); }); });
    steps.forEach(function (s, k) { s.style.cursor = 'pointer'; s.addEventListener('click', function () { if (k < cur || valid(cur)) show(k); }); });
    wiz.addEventListener('submit', function (ev) {
      for (var k = 0; k < panes.length; k++) { if (!valid(k)) { show(k); ev.preventDefault(); return; } }
    });
    show(Math.min(startAt, panes.length - 1));
  }

  // ---------------- dependent select: trade list per ITI (transfer page)
  var itiSel = document.querySelector('[data-iti-trades]');
  if (itiSel) {
    var map = JSON.parse(itiSel.getAttribute('data-iti-trades')), tradeSel = document.getElementById(itiSel.getAttribute('data-target'));
    var keep = tradeSel.getAttribute('data-selected');
    function refill() {
      var list = map[itiSel.value] || [];
      tradeSel.innerHTML = '<option value="">Select trade</option>';
      list.forEach(function (t) { var o = document.createElement('option'); o.value = t[0]; o.textContent = t[1]; if (String(t[0]) === keep) o.selected = true; tradeSel.appendChild(o); });
    }
    itiSel.addEventListener('change', refill); refill();
  }
})();
