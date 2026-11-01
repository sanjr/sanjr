/* ITI CareerTrack — small offline QR Code generator (byte mode, error-correction level M, versions 1–10).
 * Written for this project so the PCID card works without any internet / CDN.
 * Usage: <div data-qr="https://..."></div>  or  CTQR.svg(text, sizePx)
 */
(function (global) {
  'use strict';
  // data codeword blocks per version at level M: [[count, dataCodewords], ...] and EC codewords per block
  var BLOCKS = [null,
    { ec: 10, b: [[1, 16]] }, { ec: 16, b: [[1, 28]] }, { ec: 26, b: [[1, 44]] }, { ec: 18, b: [[2, 32]] },
    { ec: 24, b: [[2, 43]] }, { ec: 16, b: [[4, 27]] }, { ec: 18, b: [[4, 31]] }, { ec: 22, b: [[2, 38], [2, 39]] },
    { ec: 22, b: [[3, 36], [2, 37]] }, { ec: 26, b: [[4, 43], [1, 44]] }];
  var ALIGN = [null, [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50]];

  function gfMul(x, y) {
    var z = 0;
    for (var i = 7; i >= 0; i--) { z = (z << 1) ^ ((z >>> 7) * 0x11D); z ^= ((y >>> i) & 1) * x; }
    return z & 0xFF;
  }
  function rsDivisor(deg) {
    var r = []; for (var i = 0; i < deg; i++) r.push(0); r[deg - 1] = 1;
    var root = 1;
    for (i = 0; i < deg; i++) {
      for (var j = 0; j < deg; j++) { r[j] = gfMul(r[j], root); if (j + 1 < deg) r[j] ^= r[j + 1]; }
      root = gfMul(root, 0x02);
    }
    return r;
  }
  function rsRemainder(data, div) {
    var res = div.map(function () { return 0; });
    data.forEach(function (b) {
      var f = b ^ res.shift(); res.push(0);
      div.forEach(function (c, i) { res[i] ^= gfMul(c, f); });
    });
    return res;
  }
  function utf8(s) {
    var out = [], e = unescape(encodeURIComponent(s));
    for (var i = 0; i < e.length; i++) out.push(e.charCodeAt(i));
    return out;
  }

  function encode(text) {
    var bytes = utf8(text), ver, total;
    for (ver = 1; ver <= 10; ver++) {
      total = BLOCKS[ver].b.reduce(function (a, b) { return a + b[0] * b[1]; }, 0);
      var cntBits = ver < 10 ? 8 : 16;
      if (4 + cntBits + bytes.length * 8 <= total * 8) break;
    }
    if (ver > 10) throw new Error('Text too long for QR');
    var bits = [];
    function put(v, n) { for (var i = n - 1; i >= 0; i--) bits.push((v >>> i) & 1); }
    put(4, 4); put(bytes.length, ver < 10 ? 8 : 16);
    bytes.forEach(function (b) { put(b, 8); });
    put(0, Math.min(4, total * 8 - bits.length));
    while (bits.length % 8) bits.push(0);
    var data = [];
    for (var i = 0; i < bits.length; i += 8) { var v = 0; for (var j = 0; j < 8; j++) v = (v << 1) | bits[i + j]; data.push(v); }
    for (var pad = 0xEC; data.length < total; pad ^= 0xEC ^ 0x11) data.push(pad);

    // split into blocks, add EC, interleave
    var spec = BLOCKS[ver], div = rsDivisor(spec.ec), blocks = [], k = 0;
    spec.b.forEach(function (g) { for (var n = 0; n < g[0]; n++) { var d = data.slice(k, k + g[1]); k += g[1]; blocks.push({ d: d, e: rsRemainder(d, div) }); } });
    var maxD = Math.max.apply(null, blocks.map(function (b) { return b.d.length; })), cw = [];
    for (i = 0; i < maxD; i++) blocks.forEach(function (b) { if (i < b.d.length) cw.push(b.d[i]); });
    for (i = 0; i < spec.ec; i++) blocks.forEach(function (b) { cw.push(b.e[i]); });

    var size = 17 + 4 * ver, M = [], F = [];
    for (i = 0; i < size; i++) { M.push(new Array(size).fill(false)); F.push(new Array(size).fill(false)); }
    function fn(x, y, dark) { M[y][x] = dark; F[y][x] = true; }

    // timing
    for (i = 0; i < size; i++) { fn(6, i, i % 2 === 0); fn(i, 6, i % 2 === 0); }
    // finders + separators
    [[3, 3], [size - 4, 3], [3, size - 4]].forEach(function (c) {
      for (var dy = -4; dy <= 4; dy++) for (var dx = -4; dx <= 4; dx++) {
        var x = c[0] + dx, y = c[1] + dy, dist = Math.max(Math.abs(dx), Math.abs(dy));
        if (x >= 0 && x < size && y >= 0 && y < size) fn(x, y, dist !== 2 && dist !== 4);
      }
    });
    // alignment
    var al = ALIGN[ver];
    al.forEach(function (ay, ii) { al.forEach(function (ax, jj) {
      if ((ii === 0 && jj === 0) || (ii === 0 && jj === al.length - 1) || (ii === al.length - 1 && jj === 0)) return;
      for (var dy = -2; dy <= 2; dy++) for (var dx = -2; dx <= 2; dx++) fn(ax + dx, ay + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
    }); });
    // reserve format areas (real bits drawn later)
    function drawFormat(mask) {
      var d = (0 << 3) | mask, rem = d;               // level M => format bits 00
      for (var t = 0; t < 10; t++) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
      var b = ((d << 10) | rem) ^ 0x5412, bit = function (n) { return ((b >>> n) & 1) !== 0; };
      for (var q = 0; q <= 5; q++) fn(8, q, bit(q));
      fn(8, 7, bit(6)); fn(8, 8, bit(7)); fn(7, 8, bit(8));
      for (q = 9; q < 15; q++) fn(14 - q, 8, bit(q));
      for (q = 0; q < 8; q++) fn(size - 1 - q, 8, bit(q));
      for (q = 8; q < 15; q++) fn(8, size - 15 + q, bit(q));
      fn(8, size - 8, true);                           // dark module
    }
    drawFormat(0);
    if (ver >= 7) {
      var rem = ver;
      for (i = 0; i < 12; i++) rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25);
      var vb = (ver << 12) | rem;
      for (i = 0; i < 18; i++) { var on = ((vb >>> i) & 1) !== 0, a = size - 11 + i % 3, bb = Math.floor(i / 3); fn(a, bb, on); fn(bb, a, on); }
    }
    // data
    var bi = 0;
    for (var right = size - 1; right >= 1; right -= 2) {
      if (right === 6) right = 5;
      for (var vert = 0; vert < size; vert++) for (j = 0; j < 2; j++) {
        var x = right - j, up = ((right + 1) & 2) === 0, y = up ? size - 1 - vert : vert;
        if (!F[y][x] && bi < cw.length * 8) { M[y][x] = ((cw[bi >>> 3] >>> (7 - (bi & 7))) & 1) !== 0; bi++; }
      }
    }
    // choose mask with the lowest (simplified) penalty
    var masks = [
      function (x, y) { return (x + y) % 2 === 0; }, function (x, y) { return y % 2 === 0; },
      function (x) { return x % 3 === 0; }, function (x, y) { return (x + y) % 3 === 0; },
      function (x, y) { return (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0; }, function (x, y) { return x * y % 2 + x * y % 3 === 0; },
      function (x, y) { return (x * y % 2 + x * y % 3) % 2 === 0; }, function (x, y) { return ((x + y) % 2 + x * y % 3) % 2 === 0; }];
    function applyMask(m) { for (var y = 0; y < size; y++) for (var x = 0; x < size; x++) if (!F[y][x] && masks[m](x, y)) M[y][x] = !M[y][x]; }
    function penalty() {
      var p = 0, dark = 0, y, x;
      for (y = 0; y < size; y++) {
        var runR = 1, runC = 1;
        for (x = 0; x < size; x++) {
          if (M[y][x]) dark++;
          if (x > 0) {
            if (M[y][x] === M[y][x - 1]) { runR++; if (runR === 5) p += 3; else if (runR > 5) p++; } else runR = 1;
            if (M[x][y] === M[x - 1][y]) { runC++; if (runC === 5) p += 3; else if (runC > 5) p++; } else runC = 1;
          }
          if (x > 0 && y > 0 && M[y][x] === M[y - 1][x] && M[y][x] === M[y][x - 1] && M[y][x] === M[y - 1][x - 1]) p += 3;
        }
      }
      return p + Math.floor(Math.abs(dark * 20 - size * size * 10) / (size * size)) * 10;
    }
    var best = 0, bestP = Infinity;
    for (var m = 0; m < 8; m++) { applyMask(m); drawFormat(m); var pp = penalty(); if (pp < bestP) { bestP = pp; best = m; } applyMask(m); }
    applyMask(best); drawFormat(best);
    return M;
  }

  function svg(text, px) {
    var M = encode(text), n = M.length, q = 4, s = n + 2 * q, path = '';
    for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) if (M[y][x]) path += 'M' + (x + q) + ',' + (y + q) + 'h1v1h-1z';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + s + ' ' + s + '" width="' + (px || 140) + '" height="' + (px || 140) +
      '" shape-rendering="crispEdges" role="img" aria-label="QR code"><rect width="100%" height="100%" fill="#fff"/><path d="' + path + '" fill="#000"/></svg>';
  }

  global.CTQR = { encode: encode, svg: svg };
  document.querySelectorAll('[data-qr]').forEach(function (el) {
    try { el.innerHTML = svg(el.getAttribute('data-qr'), parseInt(el.getAttribute('data-qr-size') || '140', 10)); }
    catch (e) { el.textContent = el.getAttribute('data-qr'); }
  });
})(window);
