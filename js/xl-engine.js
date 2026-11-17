/* =========================================================
   COPA MCQ – mini spreadsheet engine for the Excel Formula Lab
   Parses and evaluates Excel formulas on a small sheet:
   numbers, text, TRUE/FALSE, cell refs (A1, $A$1), ranges (A1:C10, A:C),
   table refs ([@Actual]), operators + - * / ^ & % = <> < > <= >=
   and ~60 functions (maths, text, logic, lookup, date & time).
   Dates are Excel serial numbers (1 = 01/01/1900, 46343 = 17/11/2026).
   ========================================================= */
(function () {
  "use strict";
  const E = (e) => ({ err: e });
  const isErr = (v) => !!(v && typeof v === "object" && v.err);
  const colN = (s) => { let n = 0; for (const ch of s.toUpperCase()) n = n * 26 + ch.charCodeAt(0) - 64; return n; };
  const colS = (n) => { let s = ""; while (n > 0) { const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
  const pAddr = (a) => { const m = /^\$?([A-Z]{1,3})\$?(\d+)$/i.exec(a); return m ? { c: colN(m[1]), r: +m[2] } : null; };

  /* ---------- dates ---------- */
  const EPOCH = Date.UTC(1899, 11, 30), DAY = 864e5;
  const serial = (y, m, d) => Math.round((Date.UTC(y, m - 1, d) - EPOCH) / DAY);
  const parts = (s) => { const d = new Date(EPOCH + Math.floor(s) * DAY); return { y: d.getUTCFullYear(), m: d.getUTCMonth() + 1, d: d.getUTCDate(), wd: d.getUTCDay() }; };
  const dim = (y, m) => new Date(Date.UTC(y, m, 0)).getUTCDate();
  const isLeap = (y) => (y % 4 === 0 && y % 100 !== 0) || y % 400 === 0;
  const MON = { jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6, jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12 };
  function parseTime(s) {
    const m = /^\s*(\d{1,2}):(\d{1,2})(?::(\d{1,2}(?:\.\d+)?))?\s*(AM|PM)?\s*$/i.exec(s); if (!m) return null;
    let h = +m[1]; const mi = +m[2], se = +(m[3] || 0);
    if (m[4]) { if (h > 12) return null; h = h % 12 + (/pm/i.test(m[4]) ? 12 : 0); }
    if (h > 23 || mi > 59 || se >= 60) return null;
    return (h * 3600 + mi * 60 + se) / 86400;
  }
  function parseDate(s) {   // Indian order: dd/mm/yyyy
    s = String(s).trim(); let m, y, mo, d;
    if ((m = /^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/.exec(s))) { d = +m[1]; mo = +m[2]; y = +m[3]; }
    else if ((m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(s))) { y = +m[1]; mo = +m[2]; d = +m[3]; }
    else if ((m = /^(\d{1,2})[\s\-]([A-Za-z]{3})[A-Za-z]*[\s\-,]+(\d{2,4})$/.exec(s)) && MON[m[2].toLowerCase()]) { d = +m[1]; mo = MON[m[2].toLowerCase()]; y = +m[3]; }
    else return null;
    if (y < 100) y += y < 30 ? 2000 : 1900;
    if (mo < 1 || mo > 12 || d < 1 || d > dim(y, mo)) return null;
    return serial(y, mo, d);
  }
  function parseDateTime(s) {
    const m = /^(.+?)\s+(\d{1,2}:\d{1,2}(?::\d{1,2})?(?:\s*[AP]M)?)$/i.exec(String(s).trim());
    if (m) { const d = parseDate(m[1]), t = parseTime(m[2]); if (d != null && t != null) return d + t; }
    return null;
  }

  /* ---------- number formatting ---------- */
  function general(n) {
    if (!isFinite(n)) return "#NUM!";
    if (Object.is(n, -0)) n = 0;
    if (Math.abs(n) >= 1e11 || (Math.abs(n) < 1e-9 && n !== 0)) return n.toExponential(5).replace(/\.?0+e/, "E").replace("E+", "E+");
    const r = parseFloat(n.toPrecision(10));
    return String(r);
  }
  const p2 = (n) => String(n).padStart(2, "0");
  function fmtDate(s) { const p = parts(s); return p2(p.d) + "/" + p2(p.m) + "/" + p.y; }
  function fmtTime(s) { let t = Math.round((s - Math.floor(s)) * 86400); if (t >= 86400) t = 0; return p2(Math.floor(t / 3600)) + ":" + p2(Math.floor(t / 60) % 60) + ":" + p2(t % 60); }
  function show(v, fmt) {
    if (v === null || v === undefined) return "";
    if (isErr(v)) return v.err;
    if (typeof v === "boolean") return v ? "TRUE" : "FALSE";
    if (typeof v === "number") {
      if (fmt === "date" && v >= 1 && v < 2958466) return fmtDate(v);
      if (fmt === "datetime" && v >= 1) return fmtDate(v) + " " + fmtTime(v).slice(0, 5);
      if (fmt === "time" && v >= 0) return fmtTime(v);
      if (fmt === "pct") return general(v * 100) + "%";
      return general(v);
    }
    if (v && v.rng) return show(v.rows[0][0], fmt);
    return String(v);
  }

  /* ---------- literal values typed in a cell ---------- */
  function literal(raw) {
    const s = String(raw).trim();
    if (s === "") return { v: null };
    if (/^[-+]?(\d+\.?\d*|\.\d+)([eE][-+]?\d+)?$/.test(s)) return { v: parseFloat(s) };
    if (/^[-+]?(\d+\.?\d*|\.\d+)%$/.test(s)) return { v: parseFloat(s) / 100, f: "pct" };
    if (/^(true|false)$/i.test(s)) return { v: /^true$/i.test(s) };
    let x = parseDate(s); if (x != null) return { v: x, f: "date" };
    x = parseDateTime(s); if (x != null) return { v: x, f: "datetime" };
    x = parseTime(s); if (x != null) return { v: x, f: "time" };
    return { v: String(raw) };
  }

  /* ---------- tokenizer ---------- */
  function tokenize(src) {
    const T = []; let i = 0;
    while (i < src.length) {
      const ch = src[i], rest = src.slice(i); let m;
      if (/\s/.test(ch)) { i++; continue; }
      if (ch === '"') {
        let j = i + 1, s = "";
        for (;;) { if (j >= src.length) throw E("#NAME?"); if (src[j] === '"') { if (src[j + 1] === '"') { s += '"'; j += 2; continue; } break; } s += src[j++]; }
        T.push({ t: "str", v: s, p: i, e: j + 1 }); i = j + 1; continue;
      }
      if ((m = /^\[@\s*([^\]]+?)\s*\]/.exec(rest))) { T.push({ t: "sref", v: m[1], p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^(\d+\.?\d*|\.\d+)([eE][-+]?\d+)?/.exec(rest))) { T.push({ t: "num", v: parseFloat(m[0]), p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^(true|false)(?![A-Za-z0-9_.(])/i.exec(rest))) { T.push({ t: "bool", v: /^true$/i.test(m[0]), p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^\$?[A-Za-z]{1,3}\$?\d+(:\$?[A-Za-z]{1,3}\$?\d+)?(?![A-Za-z0-9_.(])/.exec(rest))) { T.push({ t: "ref", v: m[0].toUpperCase().replace(/\$/g, ""), p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^\$?([A-Za-z]{1,3}):\$?([A-Za-z]{1,3})(?![A-Za-z0-9_.(])/.exec(rest))) { T.push({ t: "cols", v: m[1].toUpperCase() + ":" + m[2].toUpperCase(), p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^[A-Za-z_][A-Za-z0-9_.]*(?=\s*\()/.exec(rest))) { T.push({ t: "fn", v: m[0].toUpperCase(), p: i, e: i + m[0].length }); i += m[0].length; continue; }
      if ((m = /^(<=|>=|<>|[-+*\/^&=<>%(),;])/.exec(rest))) { T.push({ t: "op", v: m[0] === ";" ? "," : m[0], p: i, e: i + m[0].length }); i += m[0].length; continue; }
      throw E("#NAME?");
    }
    return T;
  }

  /* ---------- parser → AST (each node keeps its source span p..e) ---------- */
  function parse(src) {
    const T = tokenize(src); let k = 0;
    const peek = () => T[k], isOp = (v) => T[k] && T[k].t === "op" && T[k].v === v;
    const node = (o, a, b) => Object.assign(o, { p: a, e: b });
    function cmp() { let a = cat(); while (T[k] && T[k].t === "op" && /^(=|<>|<|>|<=|>=)$/.test(T[k].v)) { const op = T[k++].v, b = cat(); a = node({ t: "bin", op, a, b }, a.p, b.e); } return a; }
    function cat() { let a = add(); while (isOp("&")) { k++; const b = add(); a = node({ t: "bin", op: "&", a, b }, a.p, b.e); } return a; }
    function add() { let a = mul(); while (isOp("+") || isOp("-")) { const op = T[k++].v, b = mul(); a = node({ t: "bin", op, a, b }, a.p, b.e); } return a; }
    function mul() { let a = pow(); while (isOp("*") || isOp("/")) { const op = T[k++].v, b = pow(); a = node({ t: "bin", op, a, b }, a.p, b.e); } return a; }
    function pow() { let a = una(); while (isOp("^")) { k++; const b = una(); a = node({ t: "bin", op: "^", a, b }, a.p, b.e); } return a; }
    function una() { if (isOp("-") || isOp("+")) { const t = T[k++], a = una(); return node(t.v === "-" ? { t: "neg", a } : a, t.p, a.e); } let a = prim(); while (isOp("%")) { const t = T[k++]; a = node({ t: "pct", a }, a.p, t.e); } return a; }
    function prim() {
      const t = peek(); if (!t) throw E("#NAME?");
      k++;
      if (t.t === "num") return node({ t: "num", v: t.v }, t.p, t.e);
      if (t.t === "str") return node({ t: "str", v: t.v }, t.p, t.e);
      if (t.t === "bool") return node({ t: "bool", v: t.v }, t.p, t.e);
      if (t.t === "sref") return node({ t: "sref", v: t.v }, t.p, t.e);
      if (t.t === "ref") {
        const [a, b] = t.v.split(":"), A = pAddr(a), B = b ? pAddr(b) : A;
        return node({ t: b ? "range" : "ref", c1: Math.min(A.c, B.c), r1: Math.min(A.r, B.r), c2: Math.max(A.c, B.c), r2: Math.max(A.r, B.r), v: t.v }, t.p, t.e);
      }
      if (t.t === "cols") { const [a, b] = t.v.split(":"); return node({ t: "range", c1: Math.min(colN(a), colN(b)), c2: Math.max(colN(a), colN(b)), r1: 1, r2: 0, v: t.v }, t.p, t.e); }
      if (t.t === "fn") {
        k++;                                                 // (
        const args = [];
        if (!isOp(")")) {
          for (;;) {
            if (isOp(",") || isOp(")")) args.push(node({ t: "missing" }, T[k].p, T[k].p)); else args.push(cmp());
            if (isOp(",")) { k++; if (isOp(")")) { args.push(node({ t: "missing" }, T[k].p, T[k].p)); } continue; }
            break;
          }
        }
        if (!isOp(")")) throw E("#NAME?");
        const end = T[k++].e;
        return node({ t: "fn", name: t.v, args }, t.p, end);
      }
      if (t.t === "op" && t.v === "(") { const a = cmp(); if (!isOp(")")) throw E("#NAME?"); const e = T[k++].e; return node({ t: "paren", a }, t.p, e); }
      throw E("#NAME?");
    }
    const ast = cmp();
    if (k < T.length) throw E("#NAME?");
    return ast;
  }

  /* ---------- coercion ---------- */
  function num(v) {
    if (isErr(v)) return v;
    if (v === null || v === undefined || v === "") return 0;
    if (typeof v === "number") return v;
    if (typeof v === "boolean") return v ? 1 : 0;
    if (v && v.rng) return v.rows.length === 1 && v.rows[0].length === 1 ? num(v.rows[0][0]) : E("#VALUE!");
    const L = literal(v); return typeof L.v === "number" ? L.v : E("#VALUE!");
  }
  function str(v) {
    if (isErr(v)) return v;
    if (v === null || v === undefined) return "";
    if (typeof v === "boolean") return v ? "TRUE" : "FALSE";
    if (typeof v === "number") return general(v);
    if (v && v.rng) return v.rows.length === 1 && v.rows[0].length === 1 ? str(v.rows[0][0]) : E("#VALUE!");
    return String(v);
  }
  function bool(v) {
    if (isErr(v)) return v;
    if (v === null || v === undefined) return false;
    if (typeof v === "boolean") return v;
    if (typeof v === "number") return v !== 0;
    if (v && v.rng) return v.rows.length === 1 && v.rows[0].length === 1 ? bool(v.rows[0][0]) : E("#VALUE!");
    if (/^true$/i.test(v)) return true; if (/^false$/i.test(v)) return false;
    return E("#VALUE!");
  }
  const scal = (v) => (v && v.rng ? (v.rows.length === 1 && v.rows[0].length === 1 ? v.rows[0][0] : E("#VALUE!")) : v);
  const flat = (v) => (v && v.rng ? [].concat.apply([], v.rows) : [v]);
  const tRank = (v) => (typeof v === "number" ? 1 : typeof v === "string" ? 2 : typeof v === "boolean" ? 3 : 0);
  function compare(a, b) {
    if (a === null) a = typeof b === "string" ? "" : typeof b === "boolean" ? false : 0;
    if (b === null) b = typeof a === "string" ? "" : typeof a === "boolean" ? false : 0;
    const ra = tRank(a), rb = tRank(b);
    if (ra !== rb) return ra - rb;
    if (ra === 2) { const x = a.toLowerCase(), y = b.toLowerCase(); return x < y ? -1 : x > y ? 1 : 0; }
    return a < b ? -1 : a > b ? 1 : 0;
  }
  const same = (a, b) => (a === null && b === null) || (a !== null && b !== null && tRank(a) === tRank(b) && compare(a, b) === 0);

  /* criteria such as ">20", "Apple", "<>Pass", "A*", 50 */
  function criteria(c) {
    if (isErr(c)) return () => false;
    let op = "=", val = c;
    if (typeof c === "string") { const m = /^(<=|>=|<>|=|<|>)([\s\S]*)$/.exec(c); if (m) { op = m[1]; val = m[2]; } }
    let n = null;
    if (typeof val === "number") n = val;
    else if (typeof val === "boolean") n = null;
    else if (val !== "" && !isNaN(+val)) n = +val;
    else if (typeof val === "string") { const d = parseDate(val); if (d != null) n = d; }
    if (typeof val === "boolean") return (x) => (op === "<>" ? x !== val : x === val);
    if (n !== null) {
      return (x) => {
        let xv = typeof x === "number" ? x : typeof x === "string" && x.trim() !== "" && !isNaN(+x) ? +x : null;
        if (xv === null) return op === "<>";
        return op === "=" ? xv === n : op === "<>" ? xv !== n : op === "<" ? xv < n : op === ">" ? xv > n : op === "<=" ? xv <= n : xv >= n;
      };
    }
    const s = String(val), wild = /[*?]/.test(s);
    const re = new RegExp("^" + s.replace(/[.+^${}()|[\]\\]/g, "\\$&").replace(/~\*/g, "\u0001").replace(/~\?/g, "\u0002").replace(/\*/g, "[\\s\\S]*").replace(/\?/g, ".").replace(/\u0001/g, "\\*").replace(/\u0002/g, "\\?") + "$", "i");
    return (x) => {
      const xs = x === null ? "" : typeof x === "string" ? x : null;
      if (op === "=") return xs !== null && (wild ? re.test(xs) : xs.toLowerCase() === s.toLowerCase()) || (s === "" && x === null);
      if (op === "<>") return !(xs !== null && (wild ? re.test(xs) : xs.toLowerCase() === s.toLowerCase())) && !(s === "" && x === null);
      if (xs === null || x === null) return false;
      const r = compare(xs, s); return op === "<" ? r < 0 : op === ">" ? r > 0 : op === "<=" ? r <= 0 : r >= 0;
    };
  }

  /* ---------- date helpers ---------- */
  function dateArg(v) {
    v = scal(v); if (isErr(v)) return v;
    if (typeof v === "number") return v < 0 ? E("#NUM!") : Math.floor(v);
    if (v === null) return 0;
    if (typeof v === "string") { const d = parseDate(v) ?? parseDateTime(v); return d == null ? E("#VALUE!") : Math.floor(d); }
    return E("#VALUE!");
  }
  function timeArg(v) {
    v = scal(v); if (isErr(v)) return v;
    if (typeof v === "number") return v;
    if (v === null) return 0;
    if (typeof v === "string") { const t = parseTime(v) ?? parseDateTime(v) ?? parseDate(v); return t == null ? E("#VALUE!") : t; }
    return E("#VALUE!");
  }
  function weekendSet(w) {
    if (w === undefined || w === null) w = 1;
    if (typeof w === "string" && /^[01]{7}$/.test(w)) { const s = [0, 0, 0, 0, 0, 0, 0]; for (let i = 0; i < 7; i++) if (w[i] === "1") s[(i + 1) % 7] = 1; return s; }   // Mon..Sun
    const n = num(w); if (isErr(n)) return n;
    const s = [0, 0, 0, 0, 0, 0, 0];
    const pairs = { 1: [6, 0], 2: [0, 1], 3: [1, 2], 4: [2, 3], 5: [3, 4], 6: [4, 5], 7: [5, 6] };
    if (pairs[n]) pairs[n].forEach((d) => (s[d] = 1)); else if (n >= 11 && n <= 17) s[(n - 11) % 7] = 1; else return E("#NUM!");
    return s;
  }
  const holSet = (v) => { const h = {}; if (v !== undefined) flat(v).forEach((x) => { const d = dateArg(x); if (!isErr(d) && x !== null) h[d] = 1; }); return h; };
  function netdays(s, e, wk, hol) {
    let sign = 1; if (s > e) { const t = s; s = e; e = t; sign = -1; }
    let n = 0; for (let d = s; d <= e; d++) if (!wk[parts(d).wd] && !hol[d]) n++;
    return n * sign;
  }
  function days360(s, e, eu) {
    const a = parts(s), b = parts(e); let d1 = a.d, d2 = b.d;
    if (eu) { if (d1 === 31) d1 = 30; if (d2 === 31) d2 = 30; }
    else {
      const lastFeb = (p) => p.m === 2 && p.d === dim(p.y, 2);
      if (lastFeb(a) && lastFeb(b)) d2 = 30;
      if (lastFeb(a)) d1 = 30;
      if (d2 === 31 && d1 >= 30) d2 = 30;
      if (d1 === 31) d1 = 30;
    }
    return (b.y - a.y) * 360 + (b.m - a.m) * 30 + (d2 - d1);
  }
  function yearfrac(s, e, basis) {
    if (s > e) { const t = s; s = e; e = t; }
    const days = e - s;
    if (basis === 2) return days / 360;
    if (basis === 3) return days / 365;
    if (basis === 4) return days360(s, e, true) / 360;
    if (basis === 0) return days360(s, e, false) / 360;
    const a = parts(s), b = parts(e);
    const oneYear = a.y === b.y || (b.y === a.y + 1 && (b.m < a.m || (b.m === a.m && b.d <= a.d)));
    if (oneYear) {
      let den = 365;
      if (a.y === b.y) den = isLeap(a.y) ? 366 : 365;
      else if ((isLeap(a.y) && s <= serial(a.y, 2, 29)) || (isLeap(b.y) && e >= serial(b.y, 2, 29))) den = 366;
      return days / den;
    }
    const total = serial(b.y + 1, 1, 1) - serial(a.y, 1, 1);
    return days / (total / (b.y - a.y + 1));
  }
  function datedif(s, e, u) {
    if (s > e) return E("#NUM!");
    const a = parts(s), b = parts(e);
    u = String(u).toUpperCase();
    const fullM = (b.y - a.y) * 12 + b.m - a.m - (b.d < a.d ? 1 : 0);
    if (u === "D") return e - s;
    if (u === "M") return fullM;
    if (u === "Y") return Math.floor(fullM / 12);
    if (u === "YM") return fullM % 12;
    if (u === "MD") { if (b.d >= a.d) return b.d - a.d; const pm = b.m === 1 ? 12 : b.m - 1, py = b.m === 1 ? b.y - 1 : b.y; return dim(py, pm) - a.d + b.d; }
    if (u === "YD") { let st = serial(b.y, a.m, Math.min(a.d, dim(b.y, a.m))); if (st > e) st = serial(b.y - 1, a.m, Math.min(a.d, dim(b.y - 1, a.m))); return e - st; }
    return E("#NUM!");
  }

  /* ---------- the sheet ---------- */
  function Sheet(cells) { this.cells = {}; this.cache = {}; this.busy = {}; if (cells) for (const k in cells) this.cells[k] = String(cells[k]); }
  Sheet.prototype.set = function (a, raw) { if (raw === "" || raw === null || raw === undefined) delete this.cells[a]; else this.cells[a] = String(raw); this.cache = {}; };
  Sheet.prototype.raw = function (a) { return this.cells[a] || ""; };
  Sheet.prototype.maxRow = function () { let m = 1; for (const k in this.cells) { const p = pAddr(k); if (p && p.r > m) m = p.r; } return m; };
  Sheet.prototype.info = function (a) {               // {v, f(ormat), ast, error}
    if (this.cache[a]) return this.cache[a];
    if (this.busy[a]) return { v: E("#CIRC!") };
    const raw = this.cells[a];
    let res;
    if (raw === undefined || raw === "") res = { v: null };
    else if (raw[0] === "=" && raw.length > 1) {
      this.busy[a] = 1;
      try { const ast = parse(raw.slice(1)); const ctx = { sh: this, at: pAddr(a) }; let v = ev(ast, ctx); if (v && v.rng) v = v.rows.length === 1 && v.rows[0].length === 1 ? v.rows[0][0] : v.rows[0][0]; if (v === null) v = 0; res = { v, f: fmtOf(ast, this), ast }; }
      catch (er) { res = { v: isErr(er) ? er : E("#NAME?"), bad: true }; }
      delete this.busy[a];
    } else res = literal(raw);
    this.cache[a] = res; return res;
  };
  Sheet.prototype.value = function (a) { return this.info(a).v; };
  Sheet.prototype.text = function (a) { const i = this.info(a); return show(i.v, i.f); };
  Sheet.prototype.range = function (c1, r1, c2, r2) {
    if (r2 === 0) r2 = Math.max(r1, this.maxRow());
    const rows = [];
    for (let r = r1; r <= r2; r++) { const row = []; for (let c = c1; c <= c2; c++) row.push(this.value(colS(c) + r)); rows.push(row); }
    return { rng: true, rows, c1, r1, c2, r2 };
  };

  /* ---------- evaluator ---------- */
  function ev(n, ctx) {
    switch (n.t) {
      case "num": case "str": case "bool": return n.v;
      case "missing": return null;
      case "paren": return ev(n.a, ctx);
      case "ref": return ctx.sh.value(colS(n.c1) + n.r1);
      case "range": return ctx.sh.range(n.c1, n.r1, n.c2, n.r2);
      case "sref": {                                        // [@Column] → same row, column whose header (row 1) matches
        if (!ctx.at) return E("#REF!");
        for (let c = 1; c <= 30; c++) { const h = ctx.sh.value(colS(c) + 1); if (typeof h === "string" && h.trim().toLowerCase() === n.v.toLowerCase()) return ctx.sh.value(colS(c) + ctx.at.r); }
        return E("#REF!");
      }
      case "neg": { const v = num(scal(ev(n.a, ctx))); return isErr(v) ? v : -v; }
      case "pct": { const v = num(scal(ev(n.a, ctx))); return isErr(v) ? v : v / 100; }
      case "bin": {
        const a = scal(ev(n.a, ctx)), b = scal(ev(n.b, ctx));
        if (isErr(a)) return a; if (isErr(b)) return b;
        if (n.op === "&") { const x = str(a), y = str(b); return isErr(x) ? x : isErr(y) ? y : x + y; }
        if (/^(=|<>|<|>|<=|>=)$/.test(n.op)) { const r = compare(a, b); return n.op === "=" ? r === 0 : n.op === "<>" ? r !== 0 : n.op === "<" ? r < 0 : n.op === ">" ? r > 0 : n.op === "<=" ? r <= 0 : r >= 0; }
        const x = num(a), y = num(b); if (isErr(x)) return x; if (isErr(y)) return y;
        if (n.op === "+") return x + y; if (n.op === "-") return x - y; if (n.op === "*") return x * y;
        if (n.op === "/") return y === 0 ? E("#DIV/0!") : x / y;
        if (n.op === "^") { const r = Math.pow(x, y); return isFinite(r) ? r : E("#NUM!"); }
        return E("#VALUE!");
      }
      case "fn": { const f = FN[n.name]; if (!f) return E("#NAME?"); try { return f(n.args, ctx); } catch (er) { if (isErr(er)) return er; throw er; } }
    }
    return E("#VALUE!");
  }
  const A = (args, ctx) => args.map((a) => ev(a, ctx));                 // eager arguments
  const req = (v) => { if (isErr(v)) throw v; return v; };
  const N = (v) => req(num(scal(v))), S = (v) => req(str(scal(v))), B = (v) => req(bool(scal(v)));
  const opt = (args, i) => args[i] && args[i].t !== "missing";
  const numsOf = (vals) => { const out = []; vals.forEach((v) => { if (v && v.rng) flat(v).forEach((x) => { if (isErr(x)) throw x; if (typeof x === "number") out.push(x); }); else { if (isErr(v)) throw v; if (v === null) return; const n = num(v); if (isErr(n)) throw n; out.push(n); } }); return out; };
  const sameShape = (a, b) => a && b && a.rng && b.rng && a.rows.length === b.rows.length && a.rows[0].length === b.rows[0].length;
  function ifsPairs(vals, from) {
    const ranges = [], preds = [];
    for (let i = from; i < vals.length; i += 2) { if (!vals[i] || !vals[i].rng) throw E("#VALUE!"); ranges.push(flat(vals[i])); preds.push(criteria(scal(vals[i + 1]))); }
    return { ranges, preds, ok: (k) => ranges.every((r, j) => preds[j](r[k])) };
  }
  function lookupRow(val, list, exact) {
    if (exact) { for (let i = 0; i < list.length; i++) { const x = list[i]; if (typeof val === "string" && typeof x === "string" && /[*?]/.test(val)) { if (criteria(val)(x)) return i; } else if (same(x, val)) return i; } return -1; }
    let best = -1; for (let i = 0; i < list.length; i++) { const x = list[i]; if (x === null || tRank(x) !== tRank(val)) continue; if (compare(x, val) <= 0) best = i; else break; } return best;
  }
  const toDay = () => { const d = new Date(); return serial(d.getFullYear(), d.getMonth() + 1, d.getDate()); };
  const FN = {
    SUM: (a, c) => numsOf(A(a, c)).reduce((x, y) => x + y, 0),
    AVERAGE: (a, c) => { const n = numsOf(A(a, c)); return n.length ? n.reduce((x, y) => x + y, 0) / n.length : E("#DIV/0!"); },
    COUNT: (a, c) => { let k = 0; A(a, c).forEach((v) => { if (v && v.rng) flat(v).forEach((x) => { if (typeof x === "number") k++; }); else if (typeof v === "number" || (typeof v === "string" && !isErr(num(v))) || typeof v === "boolean") k++; }); return k; },
    COUNTA: (a, c) => { let k = 0; A(a, c).forEach((v) => { if (v && v.rng) flat(v).forEach((x) => { if (x !== null) k++; }); else if (a.length && v !== null) k++; }); return k; },
    COUNTBLANK: (a, c) => flat(ev(a[0], c)).filter((x) => x === null || x === "").length,
    MAX: (a, c) => { const n = numsOf(A(a, c)); return n.length ? Math.max.apply(null, n) : 0; },
    MIN: (a, c) => { const n = numsOf(A(a, c)); return n.length ? Math.min.apply(null, n) : 0; },
    ABS: (a, c) => Math.abs(N(ev(a[0], c))),
    INT: (a, c) => Math.floor(N(ev(a[0], c))),
    MOD: (a, c) => { const x = N(ev(a[0], c)), y = N(ev(a[1], c)); return y === 0 ? E("#DIV/0!") : x - y * Math.floor(x / y); },
    SQRT: (a, c) => { const x = N(ev(a[0], c)); return x < 0 ? E("#NUM!") : Math.sqrt(x); },
    POWER: (a, c) => Math.pow(N(ev(a[0], c)), N(ev(a[1], c))),
    ROUND: (a, c) => { const x = N(ev(a[0], c)), d = opt(a, 1) ? Math.trunc(N(ev(a[1], c))) : 0, f = Math.pow(10, d); return Math.sign(x) * Math.round(parseFloat((Math.abs(x) * f).toPrecision(15))) / f; },
    ROUNDUP: (a, c) => { const x = N(ev(a[0], c)), f = Math.pow(10, opt(a, 1) ? N(ev(a[1], c)) : 0); return Math.sign(x) * Math.ceil(parseFloat((Math.abs(x) * f).toPrecision(15))) / f; },
    ROUNDDOWN: (a, c) => { const x = N(ev(a[0], c)), f = Math.pow(10, opt(a, 1) ? N(ev(a[1], c)) : 0); return Math.sign(x) * Math.floor(parseFloat((Math.abs(x) * f).toPrecision(15))) / f; },
    IF: (a, c) => { const t = B(ev(a[0], c)); return t ? (opt(a, 1) ? scal(ev(a[1], c)) : 0) : (a.length > 2 ? (opt(a, 2) ? scal(ev(a[2], c)) : 0) : false); },
    IFERROR: (a, c) => { const v = scal(ev(a[0], c)); return isErr(v) ? scal(ev(a[1], c)) : v; },
    AND: (a, c) => { let r = true; A(a, c).forEach((v) => flat(v).forEach((x) => { if (x === null && v && v.rng) return; if (!B(x)) r = false; })); return r; },
    OR: (a, c) => { let r = false; A(a, c).forEach((v) => flat(v).forEach((x) => { if (x === null && v && v.rng) return; if (B(x)) r = true; })); return r; },
    NOT: (a, c) => !B(ev(a[0], c)),
    SUMIF: (a, c) => { const v = A(a, c), r = v[0], p = criteria(scal(v[1])); if (!r || !r.rng) return E("#VALUE!"); const src = flat(r); let sr = src; if (v[2] && v[2].rng) { sr = flat(c.sh.range(v[2].c1, v[2].r1, v[2].c1 + (r.c2 - r.c1), v[2].r1 + (r.r2 - r.r1))); } let s = 0; src.forEach((x, i) => { if (p(x) && typeof sr[i] === "number") s += sr[i]; }); return s; },
    COUNTIF: (a, c) => { const v = A(a, c); if (!v[0] || !v[0].rng) return E("#VALUE!"); const p = criteria(scal(v[1])); return flat(v[0]).filter((x) => p(x)).length; },
    AVERAGEIF: (a, c) => { const v = A(a, c), r = v[0], p = criteria(scal(v[1])); const src = flat(r), sr = v[2] && v[2].rng ? flat(v[2]) : src; const n = []; src.forEach((x, i) => { if (p(x) && typeof sr[i] === "number") n.push(sr[i]); }); return n.length ? n.reduce((x, y) => x + y, 0) / n.length : E("#DIV/0!"); },
    SUMIFS: (a, c) => { const v = A(a, c); if (!v[0] || !v[0].rng) return E("#VALUE!"); for (let i = 1; i < v.length; i += 2) if (!sameShape(v[0], v[i])) return E("#VALUE!"); const s = flat(v[0]), P = ifsPairs(v, 1); let t = 0; s.forEach((x, k) => { if (P.ok(k) && typeof x === "number") t += x; }); return t; },
    COUNTIFS: (a, c) => { const v = A(a, c); for (let i = 2; i < v.length; i += 2) if (!sameShape(v[0], v[i])) return E("#VALUE!"); const P = ifsPairs(v, 0); let t = 0; for (let k = 0; k < P.ranges[0].length; k++) if (P.ok(k)) t++; return t; },
    LEFT: (a, c) => { const s = S(ev(a[0], c)), n = opt(a, 1) ? N(ev(a[1], c)) : 1; return n < 0 ? E("#VALUE!") : Array.from(s).slice(0, Math.floor(n)).join(""); },
    RIGHT: (a, c) => { const s = Array.from(S(ev(a[0], c))), n = opt(a, 1) ? N(ev(a[1], c)) : 1; return n < 0 ? E("#VALUE!") : n === 0 ? "" : s.slice(-Math.floor(n)).join(""); },
    MID: (a, c) => { const s = Array.from(S(ev(a[0], c))), st = N(ev(a[1], c)), n = N(ev(a[2], c)); return st < 1 || n < 0 ? E("#VALUE!") : s.slice(Math.floor(st) - 1, Math.floor(st) - 1 + Math.floor(n)).join(""); },
    LEN: (a, c) => Array.from(S(ev(a[0], c))).length,
    UPPER: (a, c) => S(ev(a[0], c)).toUpperCase(),
    LOWER: (a, c) => S(ev(a[0], c)).toLowerCase(),
    PROPER: (a, c) => S(ev(a[0], c)).toLowerCase().replace(/(^|[^A-Za-z])([a-z])/g, (m, p, ch) => p + ch.toUpperCase()),
    TRIM: (a, c) => S(ev(a[0], c)).replace(/ +/g, " ").trim(),
    CONCAT: (a, c) => A(a, c).map((v) => flat(v).map((x) => S(x)).join("")).join(""),
    CONCATENATE: (a, c) => A(a, c).map((v) => S(v)).join(""),
    TEXTJOIN: (a, c) => { const v = A(a, c), d = S(v[0]), skip = B(v[1]); const out = []; v.slice(2).forEach((x) => flat(x).forEach((y) => { if (skip && (y === null || y === "")) return; out.push(S(y)); })); return out.join(d); },
    INDEX: (a, c) => {
      const r = ev(a[0], c); if (!r || !r.rng) return E("#VALUE!");
      let row = opt(a, 1) ? N(ev(a[1], c)) : 0, col = opt(a, 2) ? N(ev(a[2], c)) : 0;
      const h = r.rows.length, w = r.rows[0].length;
      if (w === 1 && col <= 1) col = 1; else if (h === 1 && a.length === 2) { col = row; row = 1; }
      if (row < 0 || col < 0 || row > h || col > w) return E("#REF!");
      if (row === 0 || col === 0) return E("#VALUE!");
      return r.rows[row - 1][col - 1];
    },
    MATCH: (a, c) => { const v = A(a, c), val = scal(v[0]), list = flat(v[1]), t = opt(a, 2) ? N(v[2]) : 1; const i = lookupRow(val, list, t === 0); return i < 0 ? E("#N/A") : i + 1; },
    VLOOKUP: (a, c) => {
      const val = req(scal(ev(a[0], c))), t = ev(a[1], c), col = Math.floor(N(ev(a[2], c))), exact = opt(a, 3) ? !B(ev(a[3], c)) : false;
      if (!t || !t.rng) return E("#VALUE!"); if (col < 1) return E("#VALUE!"); if (col > t.rows[0].length) return E("#REF!");
      const i = lookupRow(val, t.rows.map((r) => r[0]), exact); return i < 0 ? E("#N/A") : t.rows[i][col - 1];
    },
    HLOOKUP: (a, c) => {
      const val = req(scal(ev(a[0], c))), t = ev(a[1], c), row = Math.floor(N(ev(a[2], c))), exact = opt(a, 3) ? !B(ev(a[3], c)) : false;
      if (!t || !t.rng) return E("#VALUE!"); if (row < 1) return E("#VALUE!"); if (row > t.rows.length) return E("#REF!");
      const i = lookupRow(val, t.rows[0], exact); return i < 0 ? E("#N/A") : t.rows[row - 1][i];
    },
    LOOKUP: (a, c) => {
      const val = req(scal(ev(a[0], c))), v = ev(a[1], c), res = opt(a, 2) ? ev(a[2], c) : v;
      const list = flat(v), out = flat(res); const i = lookupRow(val, list, false);
      return i < 0 ? E("#N/A") : i < out.length ? out[i] : E("#N/A");
    },
    TODAY: () => toDay(),
    NOW: () => { const d = new Date(); return toDay() + (d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds()) / 86400; },
    DATE: (a, c) => { let y = Math.floor(N(ev(a[0], c))); const m = Math.floor(N(ev(a[1], c))), d = Math.floor(N(ev(a[2], c))); if (y < 1900) y += 1900; const s = Math.round((Date.UTC(y, m - 1, d) - EPOCH) / DAY); return s < 1 ? E("#NUM!") : s; },
    DATEVALUE: (a, c) => { const s = S(ev(a[0], c)); const d = parseDate(s) ?? parseDateTime(s); return d == null ? E("#VALUE!") : Math.floor(d); },
    DATEDIF: (a, c) => { const s = req(dateArg(ev(a[0], c))), e = req(dateArg(ev(a[1], c))); return datedif(s, e, S(ev(a[2], c))); },
    DAYS: (a, c) => req(dateArg(ev(a[0], c))) - req(dateArg(ev(a[1], c))),
    DAYS360: (a, c) => days360(req(dateArg(ev(a[0], c))), req(dateArg(ev(a[1], c))), opt(a, 2) ? B(ev(a[2], c)) : false),
    EDATE: (a, c) => { const p = parts(req(dateArg(ev(a[0], c)))), k = Math.trunc(N(ev(a[1], c))); const t = new Date(Date.UTC(p.y, p.m - 1 + k, 1)); const y = t.getUTCFullYear(), m = t.getUTCMonth() + 1; return serial(y, m, Math.min(p.d, dim(y, m))); },
    EOMONTH: (a, c) => { const p = parts(req(dateArg(ev(a[0], c)))), k = Math.trunc(N(ev(a[1], c))); return Math.round((Date.UTC(p.y, p.m + k, 0) - EPOCH) / DAY); },
    NETWORKDAYS: (a, c) => netdays(req(dateArg(ev(a[0], c))), req(dateArg(ev(a[1], c))), weekendSet(1), holSet(opt(a, 2) ? ev(a[2], c) : undefined)),
    "NETWORKDAYS.INTL": (a, c) => { const w = req(weekendSet(opt(a, 2) ? scal(ev(a[2], c)) : 1)); return netdays(req(dateArg(ev(a[0], c))), req(dateArg(ev(a[1], c))), w, holSet(opt(a, 3) ? ev(a[3], c) : undefined)); },
    WORKDAY: (a, c) => {
      let d = req(dateArg(ev(a[0], c))); let k = Math.trunc(N(ev(a[1], c))); const wk = weekendSet(1), hol = holSet(opt(a, 2) ? ev(a[2], c) : undefined);
      const step = k < 0 ? -1 : 1; while (k !== 0) { d += step; if (!wk[parts(d).wd] && !hol[d]) k -= step; } return d;
    },
    YEAR: (a, c) => parts(req(dateArg(ev(a[0], c)))).y,
    MONTH: (a, c) => parts(req(dateArg(ev(a[0], c)))).m,
    DAY: (a, c) => parts(req(dateArg(ev(a[0], c)))).d,
    WEEKDAY: (a, c) => { const wd = parts(req(dateArg(ev(a[0], c)))).wd, t = opt(a, 1) ? N(ev(a[1], c)) : 1; return t === 1 ? wd + 1 : t === 2 ? ((wd + 6) % 7) + 1 : t === 3 ? (wd + 6) % 7 : E("#NUM!"); },
    WEEKNUM: (a, c) => { const s = req(dateArg(ev(a[0], c))), t = opt(a, 1) ? N(ev(a[1], c)) : 1; const y = parts(s).y, j1 = serial(y, 1, 1), wd = parts(j1).wd; const off = t === 2 || t === 11 ? (wd + 6) % 7 : wd; return Math.floor((s - j1 + off) / 7) + 1; },
    YEARFRAC: (a, c) => { const b = opt(a, 2) ? Math.trunc(N(ev(a[2], c))) : 0; if (b < 0 || b > 4) return E("#NUM!"); return yearfrac(req(dateArg(ev(a[0], c))), req(dateArg(ev(a[1], c))), b); },
    HOUR: (a, c) => { const t = req(timeArg(ev(a[0], c))); return Math.floor(Math.round((t - Math.floor(t)) * 86400) / 3600) % 24; },
    MINUTE: (a, c) => { const t = req(timeArg(ev(a[0], c))); return Math.floor(Math.round((t - Math.floor(t)) * 86400) / 60) % 60; },
    SECOND: (a, c) => { const t = req(timeArg(ev(a[0], c))); return Math.round((t - Math.floor(t)) * 86400) % 60; },
    TIME: (a, c) => { const s = N(ev(a[0], c)) * 3600 + N(ev(a[1], c)) * 60 + N(ev(a[2], c)); if (s < 0) return E("#NUM!"); return (s % 86400) / 86400; },
    TIMEVALUE: (a, c) => { const s = S(ev(a[0], c)); const t = parseTime(s) ?? parseDateTime(s); return t == null ? E("#VALUE!") : t - Math.floor(t); }
  };

  /* display format of a formula result (date / time) */
  const DATE_FN = { DATE: 1, DATEVALUE: 0, EDATE: 1, EOMONTH: 1, WORKDAY: 1, TODAY: 1 };
  function fmtOf(n, sh) {
    if (!n) return "";
    if (n.t === "paren") return fmtOf(n.a, sh);
    if (n.t === "fn") {
      if (DATE_FN[n.name]) return "date"; if (n.name === "NOW") return "datetime"; if (n.name === "TIME") return "time";
      if (n.name === "IF" || n.name === "IFERROR") return fmtOf(n.args[1], sh);
      if (/^(INDEX|VLOOKUP|HLOOKUP|LOOKUP|MAX|MIN)$/.test(n.name)) { const r = n.args[n.name === "INDEX" ? 0 : n.name === "VLOOKUP" || n.name === "HLOOKUP" ? 1 : n.name === "LOOKUP" ? (n.args[2] || n.args[1]) && (n.args[2] ? 2 : 1) : 0]; if (r && r.t === "range") { const i = sh.info(colS(r.c2) + Math.max(r.r1, 1)); if (i.f === "date") return "date"; } }
      return "";
    }
    if (n.t === "ref") { const i = sh.info(colS(n.c1) + n.r1); return i.f || ""; }
    if (n.t === "bin" && (n.op === "+" || n.op === "-")) { const x = fmtOf(n.a, sh), y = fmtOf(n.b, sh); if (n.op === "-" && /date/.test(x) && /date/.test(y)) return ""; return /date/.test(x) ? x : n.op === "+" && /date/.test(y) ? y : ""; }
    return "";
  }

  /* refs used by a formula, grouped by the top-level argument they belong to */
  function refsOf(n, out, k) {
    if (!n) return out;
    if (n.t === "ref" || n.t === "range") out.push({ c1: n.c1, r1: n.r1, c2: n.c2, r2: n.r2, k, p: n.p, e: n.e });
    ["a", "b"].forEach((x) => n[x] && refsOf(n[x], out, k));
    (n.args || []).forEach((x) => refsOf(x, out, k));
    return out;
  }
  /* the parts a beginner should see: the top-level function's arguments, or the pieces joined with & */
  function partsOf(ast) {
    let n = ast; while (n && n.t === "paren") n = n.a;
    if (!n) return [];
    if (n.t === "fn") return n.args.filter((x) => x.t !== "missing");
    if (n.t === "bin" && n.op === "&") { const L = []; (function walk(x) { if (x.t === "bin" && x.op === "&") { walk(x.a); walk(x.b); } else L.push(x); })(n); return L; }
    if (n.t === "bin") return [n.a, n.b];
    return [n];
  }

  window.XL = { Sheet, parse, show, general, literal, colS, colN, pAddr, refsOf, partsOf, isErr, ev, fmtOf, fmtDate, serial, parts };
})();
