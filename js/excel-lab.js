/* =========================================================
   COPA MCQ – Excel Formula Lab (Study Material → excel-lab.html)
   Live mini Excel sheet for every formula: edit data or the formula in the fx bar,
   see coloured ranges, the meaning of every argument (EN / HI / MR), how the answer
   is calculated, and solve an exercise that is checked instantly.
   Called by js/study-hub.js:  window.COPA_XL(root, heroHtml)
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, LG = C.lang, XL = window.XL, DATA = window.COPA_XL_DATA;
  const LI = LG === "hi" ? 1 : LG === "mr" ? 2 : 0;
  const COL = ["#1a73e8", "#d93025", "#8e24aa", "#188038", "#e37400", "#00838f"];
  const TX = {
    en: { formulas: "formulas", live: "Live Excel sheet", exs: "exercises", all: "All", basic: "Basic", mid: "Intermediate", date: "Date & Time", pdf: "Special", test: "Live Test Sheet", dateM: "Module 33",
      search: "Search formula… e.g. VLOOKUP", parts: "What each part means", here: "In this example", liveS: "Click a cell, then change its value or the formula in the fx bar – everything updates instantly.",
      result: "Result in", tryT: "Try this", ex: "Exercise", check: "Check", answer: "Show answer", typeF: "Type your formula here, e.g. =SUM(B1:B5)", ok: "Correct! Well done 🎉",
      wrongV: "Not yet – your formula gives {v}, the answer should be {e}.", needFn: "Use the {f} function in your formula.", empty: "Type a formula that starts with =", solved: "Solved", reset: "Reset",
      copy: "Copy", copied: "Copied!", prev: "Previous", next: "Next", serial: "Excel stores this date as the number {n} (days counted from 01/01/1900).", tserial: "Time is stored as a part of one day: {n} × 24 = {h} hours.",
      testT: "Ready Live Test Sheet", testS: "All 16 formulas on one sheet. Change any data – every answer updates. Click a formula to see the cells it uses.", copyAll: "Copy all formulas", csv: "Download practice sheet (.csv)",
      formula: "Formula", meaning: "Meaning", note: "Function names are always in English in Excel – only the explanations change with the website language.", yourCell: "Your answer goes in cell", progress: "{a} / {b} exercises solved",
      part: "Part", ans: "Answer", gives: "gives", syntax: "Syntax", open: "Open", csvS: "Opens in Excel with the data and all formulas ready.", copyF: "Formula copied – paste it in Excel",
      errs: { "#N/A": "#N/A = the value was not found in the table.", "#DIV/0!": "#DIV/0! = a number is divided by 0 or by an empty cell.", "#VALUE!": "#VALUE! = wrong type of value, e.g. text where a number is needed.", "#NAME?": "#NAME? = Excel does not know a name – check the spelling and the \" \" quotes.", "#REF!": "#REF! = the cell or column does not exist in the range.", "#NUM!": "#NUM! = the number is not possible here (e.g. start date after end date).", "#CIRC!": "Circular reference – the formula uses its own cell." } },
    hi: { formulas: "फ़ॉर्मूले", live: "लाइव Excel शीट", exs: "अभ्यास", all: "सभी", basic: "बेसिक", mid: "इंटरमीडिएट", date: "तारीख और समय", pdf: "विशेष", test: "लाइव टेस्ट शीट", dateM: "मॉड्यूल 33",
      search: "फ़ॉर्मूला खोजें… जैसे VLOOKUP", parts: "हर भाग का अर्थ", here: "इस उदाहरण में", liveS: "किसी सेल पर क्लिक करें, फिर fx बार में उसका मान या फ़ॉर्मूला बदलें – सब तुरंत बदलेगा।",
      result: "परिणाम –", tryT: "यह करके देखें", ex: "अभ्यास", check: "जाँचें", answer: "उत्तर देखें", typeF: "अपना फ़ॉर्मूला यहाँ लिखें, जैसे =SUM(B1:B5)", ok: "सही! बहुत बढ़िया 🎉",
      wrongV: "अभी नहीं – आपका फ़ॉर्मूला {v} देता है, उत्तर {e} होना चाहिए।", needFn: "अपने फ़ॉर्मूले में {f} फ़ंक्शन का उपयोग करें।", empty: "= से शुरू होने वाला फ़ॉर्मूला लिखें", solved: "हल किया", reset: "रीसेट",
      copy: "कॉपी", copied: "कॉपी हुआ!", prev: "पिछला", next: "अगला", serial: "Excel इस तारीख को नंबर {n} के रूप में रखता है (01/01/1900 से गिने गए दिन)।", tserial: "समय को एक दिन के भाग के रूप में रखा जाता है: {n} × 24 = {h} घंटे।",
      testT: "तैयार लाइव टेस्ट शीट", testS: "सभी 16 फ़ॉर्मूले एक शीट पर। कोई भी डेटा बदलें – हर उत्तर बदलेगा। फ़ॉर्मूले पर क्लिक करके उसके सेल देखें।", copyAll: "सभी फ़ॉर्मूले कॉपी करें", csv: "प्रैक्टिस शीट डाउनलोड करें (.csv)",
      formula: "फ़ॉर्मूला", meaning: "अर्थ", note: "Excel में फ़ंक्शन के नाम हमेशा अंग्रेज़ी में होते हैं – सिर्फ़ समझाने वाला टेक्स्ट वेबसाइट की भाषा में बदलता है।", yourCell: "आपका उत्तर इस सेल में आएगा", progress: "{b} में से {a} अभ्यास हल",
      part: "भाग", ans: "उत्तर", gives: "देता है", syntax: "सिंटैक्स", open: "खोलें", csvS: "Excel में डेटा और सभी फ़ॉर्मूलों के साथ खुलती है।", copyF: "फ़ॉर्मूला कॉपी हुआ – Excel में पेस्ट करें",
      errs: { "#N/A": "#N/A = मान टेबल में नहीं मिला।", "#DIV/0!": "#DIV/0! = किसी नंबर को 0 या खाली सेल से भाग दिया गया।", "#VALUE!": "#VALUE! = गलत प्रकार का मान, जैसे नंबर की जगह टेक्स्ट।", "#NAME?": "#NAME? = Excel नाम नहीं पहचानता – स्पेलिंग और \" \" कोट्स जाँचें।", "#REF!": "#REF! = वह सेल या कॉलम रेंज में नहीं है।", "#NUM!": "#NUM! = यह नंबर यहाँ संभव नहीं (जैसे शुरुआती तारीख अंतिम तारीख के बाद)।", "#CIRC!": "Circular reference – फ़ॉर्मूला अपने ही सेल का उपयोग कर रहा है।" } },
    mr: { formulas: "फॉर्म्युले", live: "लाइव्ह Excel शीट", exs: "सराव", all: "सर्व", basic: "बेसिक", mid: "इंटरमिजिएट", date: "तारीख आणि वेळ", pdf: "विशेष", test: "लाइव्ह टेस्ट शीट", dateM: "मॉड्यूल 33",
      search: "फॉर्म्युला शोधा… उदा. VLOOKUP", parts: "प्रत्येक भागाचा अर्थ", here: "या उदाहरणात", liveS: "कोणत्याही सेलवर क्लिक करा, मग fx बारमध्ये त्याची व्हॅल्यू किंवा फॉर्म्युला बदला – सगळे लगेच बदलते.",
      result: "निकाल –", tryT: "हे करून पहा", ex: "सराव", check: "तपासा", answer: "उत्तर पहा", typeF: "तुमचा फॉर्म्युला इथे लिहा, उदा. =SUM(B1:B5)", ok: "बरोबर! छान 🎉",
      wrongV: "अजून नाही – तुमचा फॉर्म्युला {v} देतो, उत्तर {e} हवे.", needFn: "तुमच्या फॉर्म्युल्यात {f} फंक्शन वापरा.", empty: "= ने सुरू होणारा फॉर्म्युला लिहा", solved: "सोडवले", reset: "रीसेट",
      copy: "कॉपी", copied: "कॉपी झाले!", prev: "मागील", next: "पुढील", serial: "Excel ही तारीख {n} या नंबरच्या रूपात ठेवते (01/01/1900 पासून मोजलेले दिवस).", tserial: "वेळ एका दिवसाचा भाग म्हणून ठेवली जाते: {n} × 24 = {h} तास.",
      testT: "तयार लाइव्ह टेस्ट शीट", testS: "सर्व 16 फॉर्म्युले एकाच शीटवर. कोणताही डेटा बदला – प्रत्येक उत्तर बदलते. फॉर्म्युल्यावर क्लिक करून तो कोणते सेल वापरतो ते पहा.", copyAll: "सर्व फॉर्म्युले कॉपी करा", csv: "प्रॅक्टिस शीट डाउनलोड करा (.csv)",
      formula: "फॉर्म्युला", meaning: "अर्थ", note: "Excel मध्ये फंक्शनची नावे नेहमी इंग्रजीतच असतात – फक्त समजावणारा मजकूर वेबसाइटच्या भाषेत बदलतो.", yourCell: "तुमचे उत्तर या सेलमध्ये येईल", progress: "{b} पैकी {a} सराव सोडवले",
      part: "भाग", ans: "उत्तर", gives: "देतो", syntax: "सिंटॅक्स", open: "उघडा", csvS: "Excel मध्ये डेटा आणि सर्व फॉर्म्युल्यांसह उघडते.", copyF: "फॉर्म्युला कॉपी झाला – Excel मध्ये पेस्ट करा",
      errs: { "#N/A": "#N/A = व्हॅल्यू टेबलमध्ये सापडली नाही.", "#DIV/0!": "#DIV/0! = एखाद्या नंबरला 0 ने किंवा रिकाम्या सेलने भागले.", "#VALUE!": "#VALUE! = चुकीच्या प्रकारची व्हॅल्यू, उदा. नंबरऐवजी टेक्स्ट.", "#NAME?": "#NAME? = Excel ला नाव ओळखत नाही – स्पेलिंग आणि \" \" कोट्स तपासा.", "#REF!": "#REF! = तो सेल किंवा कॉलम रेंजमध्ये नाही.", "#NUM!": "#NUM! = हा नंबर इथे शक्य नाही (उदा. सुरुवातीची तारीख शेवटच्या तारखेनंतर).", "#CIRC!": "Circular reference – फॉर्म्युला स्वतःचाच सेल वापरतो." } }
  };
  const X = TX[LG] || TX.en;
  const CATS = [["all", "fa-layer-group"], ["basic", "fa-calculator"], ["mid", "fa-magnifying-glass-chart"], ["date", "fa-calendar-days"], ["pdf", "fa-star"], ["test", "fa-table"]];
  const T = (e) => e.t[LG] || e.t.en;
  const fmtS = (s, o) => s.replace(/\{(\w+)\}/g, (m, k) => (o[k] != null ? o[k] : m));
  const store = { get() { try { return JSON.parse(localStorage.getItem("copa_xl_done") || "{}"); } catch (e) { return {}; } }, set(v) { try { localStorage.setItem("copa_xl_done", JSON.stringify(v)); } catch (e) { /* */ } } };
  let done = store.get();
  const firstFn = (n) => (/^[A-Z]/.test(n) ? n.split(/[\s/(]/)[0] : "");

  /* ---------- value helpers ---------- */
  function showNode(node, ctx) {
    let v; try { v = XL.ev(node, ctx); } catch (er) { v = XL.isErr(er) ? er : { err: "#VALUE!" }; }
    if (v && v.rng) {
      const out = []; let fmt = "";
      for (let r = 0; r < v.rows.length; r++) for (let c = 0; c < v.rows[0].length; c++) {
        const a = XL.colS(v.c1 + c) + (v.r1 + r), x = v.rows[r][c];
        if (x === null) continue; fmt = ctx.sh.info(a).f || ""; out.push(XL.show(x, fmt));
      }
      return out.length ? (out.length > 8 ? out.slice(0, 8).join(", ") + " … (" + out.length + ")" : out.join(", ")) : "—";
    }
    const s = XL.show(v, XL.fmtOf(node, ctx.sh));
    return typeof v === "string" ? '"' + s + '"' : s;
  }

  /* ---------- the Excel-like sheet component ---------- */
  function sheetUI(box, sh, o) {
    // o: { cols, rows, main, xcell, sel, onChange, title }
    let sel = o.sel || o.main || "A1", hlFor = o.main || sel, timer = 0, orig = "";
    const cols = o.cols, rows = o.rows;
    box.innerHTML = '<div class="xw"><div class="xw-title"><span class="xw-logo">X</span><b>' + esc(o.title || "Book1") + ' – Excel</b><span class="xw-dots"><i></i><i></i><i></i></span></div>' +
      '<div class="xw-fx"><input class="xw-nb" aria-label="Name box" readonly><span class="xw-fxl"><i class="fa-solid fa-xmark"></i><i class="fa-solid fa-check"></i><b>fx</b></span><input class="xw-fb" aria-label="Formula bar" spellcheck="false" autocomplete="off" autocapitalize="off" autocorrect="off"></div>' +
      '<div class="xw-scroll" tabindex="0" aria-label="Sheet"><table class="xw-grid"><thead><tr><th class="xw-corner"></th>' + Array.from({ length: cols }, (_, i) => "<th>" + XL.colS(i + 1) + "</th>").join("") + "</tr></thead><tbody>" +
      Array.from({ length: rows }, (_, r) => "<tr><th>" + (r + 1) + "</th>" + Array.from({ length: cols }, (_, c) => '<td data-a="' + XL.colS(c + 1) + (r + 1) + '"></td>').join("") + "</tr>").join("") + "</tbody></table></div>" +
      '<div class="xw-foot"><span class="xw-sheet">Sheet1</span><span class="xw-hint"><i class="fa-regular fa-hand-pointer"></i> ' + esc(X.liveS) + "</span></div></div>";
    const nb = box.querySelector(".xw-nb"), fb = box.querySelector(".xw-fb"), grid = box.querySelector(".xw-scroll"), tds = {};
    box.querySelectorAll("td").forEach((td) => (tds[td.dataset.a] = td));
    function paint() {
      for (const a in tds) {
        const td = tds[a], i = sh.info(a), v = i.v;
        td.textContent = XL.show(v, i.f);
        td.className = (typeof v === "number" ? "n" : XL.isErr(v) ? "e" : typeof v === "boolean" ? "b" : "") + (a === sel ? " sel" : "") + (a === o.main ? " main" : "") + (a === o.xcell ? " xc" : "") + (sh.raw(a)[0] === "=" ? " fx" : "");
        td.style.removeProperty("--hc");
      }
      const info = sh.info(hlFor);
      if (info.ast) XL.partsOf(info.ast).forEach((p, k) => XL.refsOf(p, [], k).forEach((rf) => {
        const r2 = rf.r2 === 0 ? rows : rf.r2;
        for (let r = rf.r1; r <= Math.min(r2, rows); r++) for (let c = rf.c1; c <= Math.min(rf.c2, cols); c++) { const td = tds[XL.colS(c) + r]; if (td) { td.classList.add("hl"); td.style.setProperty("--hc", COL[k % COL.length]); } }
      }));
    }
    function select(a, keepFocus) {
      sel = a; nb.value = a; fb.value = sh.raw(a); orig = fb.value;
      if (sh.raw(a)[0] === "=") hlFor = a; else if (!o.main) hlFor = a;
      paint();
      const td = tds[a]; if (td && !keepFocus) td.scrollIntoView({ block: "nearest", inline: "nearest" });
      o.onSelect && o.onSelect(a);
    }
    function commit() { clearTimeout(timer); if (sh.raw(sel) !== fb.value) { sh.set(sel, fb.value); if (fb.value[0] === "=") hlFor = sel; paint(); o.onChange && o.onChange(sel); } }
    fb.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(commit, 300); });
    fb.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); commit(); const p = XL.pAddr(sel); if (p.r < rows) select(XL.colS(p.c) + (p.r + 1)); grid.focus({ preventScroll: true }); }
      else if (e.key === "Escape") { fb.value = orig; commit(); grid.focus({ preventScroll: true }); }
    });
    fb.addEventListener("blur", commit);
    box.querySelector("tbody").addEventListener("click", (e) => { const td = e.target.closest("td"); if (td) { commit(); select(td.dataset.a); } });
    box.querySelector("tbody").addEventListener("dblclick", (e) => { if (e.target.closest("td")) { fb.focus(); fb.setSelectionRange(fb.value.length, fb.value.length); } });
    grid.addEventListener("keydown", (e) => {
      const p = XL.pAddr(sel); let c = p.c, r = p.r;
      if (e.key === "ArrowUp") r--; else if (e.key === "ArrowDown") r++; else if (e.key === "ArrowLeft") c--; else if (e.key === "ArrowRight") c++;
      else if (e.key === "Enter" || e.key === "F2") { e.preventDefault(); fb.focus(); return; }
      else if (e.key === "Delete" || e.key === "Backspace") { e.preventDefault(); fb.value = ""; commit(); return; }
      else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) { e.preventDefault(); fb.value = e.key; fb.focus(); clearTimeout(timer); timer = setTimeout(commit, 300); return; }
      else return;
      e.preventDefault(); c = Math.max(1, Math.min(cols, c)); r = Math.max(1, Math.min(rows, r)); select(XL.colS(c) + r);
    });
    select(sel, true);
    return { paint, select, focusFb: () => fb.focus(), get sel() { return sel; }, highlight(a) { hlFor = a; paint(); } };
  }
  const sizeOf = (cells, extra) => {
    let c = 4, r = 6;
    Object.keys(cells).concat(extra).forEach((a) => { const p = XL.pAddr(a); if (p) { c = Math.max(c, p.c + 1); r = Math.max(r, p.r + 1); } });
    return { cols: Math.min(c, 16), rows: Math.min(r, 14) };
  };

  /* ---------- page ---------- */
  window.COPA_XL = function (root, hero) {
    const total = DATA.L.length;
    root.innerHTML = hero +
      '<div class="xl-top"><div class="xl-cats" role="tablist">' + CATS.map((c, i) => '<button type="button" role="tab" data-cat="' + c[0] + '" class="' + (i ? "" : "on") + '"><i class="fa-solid ' + c[1] + '"></i> ' + esc(X[c[0]]) + (c[0] !== "all" && c[0] !== "test" ? " <small>" + DATA.L.filter((e) => e.c === c[0]).length + "</small>" : "") + "</button>").join("") + "</div>" +
      '<div class="xl-bar"><div class="sh-search"><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="xlS" placeholder="' + esc(X.search) + '" aria-label="' + esc(X.search) + '"></div>' +
      '<div class="xl-prog"><span class="ring" id="xlPr"></span><span id="xlPt"></span></div></div></div>' +
      '<div class="xl-wrap"><aside class="xl-list" id="xlL"></aside><section class="xl-main" id="xlM" aria-live="polite"></section></div>' +
      '<p class="xl-note"><i class="fa-solid fa-language"></i> ' + esc(X.note) + "</p>";
    let cat = "all", q = "", cur = null;
    const listEl = document.getElementById("xlL"), main = document.getElementById("xlM");
    const visible = () => DATA.L.filter((e) => (cat === "all" || e.c === cat) && (!q || (e.n + " " + e.f + " " + T(e)[0]).toLowerCase().indexOf(q) > -1));
    function prog() {
      const n = DATA.L.filter((e) => done[e.id]).length;
      document.getElementById("xlPt").textContent = fmtS(X.progress, { a: n, b: total });
      document.getElementById("xlPr").style.setProperty("--p", (n / total) * 100);
    }
    function drawList() {
      const V = visible(); let html = "", last = "";
      V.forEach((e) => {
        if (e.c !== last && cat === "all") { last = e.c; html += '<h4 class="xl-lh c-' + e.c + '">' + esc(X[e.c]) + (e.c === "date" ? " <small>" + esc(X.dateM) + "</small>" : "") + "</h4>"; }
        html += '<button type="button" class="xl-li c-' + e.c + (cur === e.id ? " on" : "") + '" data-id="' + e.id + '"><b>' + esc(e.n) + '</b><code>' + esc(e.f.length > 34 ? e.f.slice(0, 32) + "…" : e.f) + "</code>" + (done[e.id] ? '<i class="fa-solid fa-circle-check ok" title="' + esc(X.solved) + '"></i>' : "") + "</button>";
      });
      if (cat === "all" && !q) html += '<h4 class="xl-lh c-test">' + esc(X.test) + '</h4><button type="button" class="xl-li c-test' + (cur === "test" ? " on" : "") + '" data-id="test"><b>' + esc(X.testT) + "</b><code>16 " + esc(X.formulas) + "</code></button>";
      listEl.innerHTML = html || '<p class="xl-empty">—</p>';
      prog();
    }
    function go(id, scroll) {
      cur = id;
      if (id === "test") testView(); else lab(DATA.L.find((e) => e.id === id) || DATA.L[0]);
      listEl.querySelectorAll(".xl-li").forEach((b) => b.classList.toggle("on", b.dataset.id === cur));
      const b = listEl.querySelector(".xl-li.on"); if (b) b.scrollIntoView({ block: "nearest", inline: "center" });
      history.replaceState(null, "", "#" + id);
      if (scroll && window.innerWidth < 900) main.scrollIntoView({ behavior: "smooth", block: "start" });
    }
    listEl.addEventListener("click", (e) => { const b = e.target.closest(".xl-li"); if (b) go(b.dataset.id, true); });
    root.querySelector(".xl-cats").addEventListener("click", (e) => {
      const b = e.target.closest("[data-cat]"); if (!b) return;
      root.querySelectorAll(".xl-cats button").forEach((x) => x.classList.toggle("on", x === b));
      if (b.dataset.cat === "test") { cat = "all"; drawList(); return go("test"); }
      cat = b.dataset.cat; drawList(); const V = visible(); if (V.length && (cur === "test" || !V.some((x) => x.id === cur))) go(V[0].id);
    });
    document.getElementById("xlS").addEventListener("input", (e) => { q = e.target.value.trim().toLowerCase(); drawList(); const V = visible(); if (q && V.length && !V.some((x) => x.id === cur)) go(V[0].id); });

    /* ----- one formula ----- */
    function lab(e) {
      const tt = T(e), sz = sizeOf(e.d, [e.at, e.x.at]);
      const sh = new XL.Sheet(e.d); sh.set(e.at, e.f);
      const V = visible(), i = V.indexOf(e);
      main.innerHTML =
        '<article class="xl-card c-' + e.c + '"><header class="xl-h"><span class="cat">' + esc(X[e.c]) + '</span><h2>' + esc(e.n) + (done[e.id] ? ' <span class="okb"><i class="fa-solid fa-circle-check"></i> ' + esc(X.solved) + "</span>" : "") + "</h2><p>" + esc(tt[0]) + "</p>" +
          '<div class="xl-acts"><button type="button" class="btn btn-ghost btn-sm" data-copy><i class="fa-regular fa-copy"></i> ' + esc(X.copy) + '</button><button type="button" class="btn btn-ghost btn-sm" data-reset><i class="fa-solid fa-rotate-left"></i> ' + esc(X.reset) + "</button></div></header>" +
        '<div class="xl-syn"><span class="lb">' + esc(X.syntax) + '</span><code id="xlF"></code></div>' +
        '<div class="xl-grid2"><div><div id="xlSheet"></div></div>' +
        '<div class="xl-side"><div class="xl-res" id="xlR"></div><div class="xl-parts"><h3><i class="fa-solid fa-puzzle-piece"></i> ' + esc(X.parts) + '</h3><ol id="xlP"></ol></div>' +
        '<div class="xl-try"><i class="fa-solid fa-wand-magic-sparkles"></i><div><b>' + esc(X.tryT) + "</b><p>" + esc(tt[1]) + "</p></div></div></div></div>" +
        '<section class="xl-ex"><h3><span class="ic"><i class="fa-solid fa-pen-to-square"></i></span> ' + esc(X.ex) + "</h3><p class=\"task\">" + esc(tt[2]) + ' <span class="cell">' + esc(X.yourCell) + " <b>" + e.x.at + "</b></span></p>" +
          '<div class="xl-in"><span class="fx">fx</span><input id="xlX" spellcheck="false" autocomplete="off" autocapitalize="off" autocorrect="off" placeholder="' + esc(X.typeF) + '"><button type="button" class="btn btn-primary" id="xlC"><i class="fa-solid fa-check"></i> ' + esc(X.check) + "</button></div>" +
          '<div class="xl-fb2"><button type="button" class="lnk" id="xlA"><i class="fa-regular fa-eye"></i> ' + esc(X.answer) + '</button><span id="xlMsg" role="status"></span></div></section>' +
        '<nav class="xl-nav">' + (V[i - 1] ? '<button type="button" class="btn btn-ghost" data-go="' + V[i - 1].id + '"><i class="fa-solid fa-arrow-left"></i> ' + esc(V[i - 1].n) + "</button>" : "<span></span>") + (V[i + 1] ? '<button type="button" class="btn btn-primary" data-go="' + V[i + 1].id + '">' + esc(V[i + 1].n) + ' <i class="fa-solid fa-arrow-right"></i></button>' : "") + "</nav></article>";
      const ui = sheetUI(document.getElementById("xlSheet"), sh, Object.assign({ main: e.at, xcell: e.x.at, title: e.n, onChange: update }, sz));
      function update() {
        const raw = sh.raw(e.at), info = sh.info(e.at), ctx = { sh, at: XL.pAddr(e.at) };
        // syntax line with coloured parts
        const F = document.getElementById("xlF");
        if (info.ast && raw[0] === "=") {
          const src = raw.slice(1), parts = XL.partsOf(info.ast); let html = "=", pos = 0;
          parts.forEach((p, k) => { html += esc(src.slice(pos, p.p)) + '<span style="--c:' + COL[k % COL.length] + '">' + esc(src.slice(p.p, p.e)) + "</span>"; pos = p.e; });
          F.innerHTML = html + esc(src.slice(pos));
        } else F.textContent = raw;
        // result
        const v = info.v, s = XL.show(v, info.f);
        let note = "";
        if (XL.isErr(v)) note = '<p class="err"><i class="fa-solid fa-triangle-exclamation"></i> ' + esc(X.errs[v.err] || v.err) + "</p>";
        else if (typeof v === "number" && /date/.test(info.f || "")) note = '<p class="sn"><i class="fa-solid fa-circle-info"></i> ' + esc(fmtS(X.serial, { n: XL.general(Math.floor(v)) })) + "</p>";
        else if (typeof v === "number" && (info.f === "time" || /TIMEVALUE|TIME\(/.test(raw))) note = '<p class="sn"><i class="fa-solid fa-circle-info"></i> ' + esc(fmtS(X.tserial, { n: XL.general(v), h: XL.general(Math.round(v * 24 * 1000) / 1000) })) + "</p>";
        document.getElementById("xlR").innerHTML = '<span class="lb">' + esc(X.result) + " <b>" + e.at + '</b></span><div class="big' + (XL.isErr(v) ? " bad" : "") + '">' + esc(s || "—") + "</div>" + note;
        // parts
        const parts = info.ast ? XL.partsOf(info.ast) : [];
        const amp = !!(info.ast && info.ast.t === "bin" && info.ast.op === "&");
        const lastA = e.a[e.a.length - 1], more = /…$/.test(lastA[0]);
        const lbl = (k) => (amp ? X.part + " " + (k + 1) : k < e.a.length ? lastA === e.a[k] && more ? e.a[k][0].replace(/ …$/, "") : e.a[k][0] : more ? lastA[0].replace(/^\[?([a-z_]+?)\d+\]? …$/i, "[$1" + (k + 1) + "]") : X.part + " " + (k + 1));
        const key = (k) => (amp ? (k === 0 ? e.a[0][1] : "") : k < e.a.length ? e.a[k][1] : more ? lastA[1] : "");
        document.getElementById("xlP").innerHTML = parts.map((p, k) => '<li style="--c:' + COL[k % COL.length] + '"><div class="ph"><span class="dot"></span><b>' + esc(lbl(k)) + "</b></div>" +
            (key(k) ? '<p class="mn">' + esc(DATA.ARG[key(k)][LI]) + "</p>" : "") +
            '<p class="vv"><span class="lb">' + esc(X.here) + ':</span> <code>' + esc(raw.slice(1).slice(p.p, p.e)) + '</code> <i class="fa-solid fa-arrow-right"></i> <b>' + esc(showNode(p, ctx)) + "</b></p></li>").join("") ||
          '<li style="--c:' + COL[0] + '"><p class="mn">' + esc(DATA.ARG[e.a[0][1]][LI]) + "</p></li>";
      }
      update();
      // exercise
      const xin = document.getElementById("xlX"), msg = document.getElementById("xlMsg");
      let xt = 0;
      const put = () => { let f = xin.value.trim(); if (f && f[0] !== "=") f = "=" + f; sh.set(e.x.at, f); ui.paint(); };
      xin.addEventListener("input", () => { clearTimeout(xt); xt = setTimeout(put, 300); msg.className = ""; msg.textContent = ""; });
      const check = () => {
        put(); let f = xin.value.trim(); if (!f) { msg.className = "no"; msg.textContent = X.empty; return; }
        if (f[0] !== "=") f = "=" + f;
        const need = e.x.need != null ? e.x.need : firstFn(e.n);
        const ref = new XL.Sheet(sh.cells); ref.set(e.x.at, e.x.f);
        const want = ref.value(e.x.at), got = sh.value(e.x.at), wf = ref.info(e.x.at).f, gf = sh.info(e.x.at).f;
        const ok = !XL.isErr(got) && (typeof want === "number" && typeof got === "number" ? Math.abs(want - got) < 1e-9 * Math.max(1, Math.abs(want)) : XL.show(got, gf) === XL.show(want, wf));
        if (ok && need && !new RegExp("(^|[^A-Z.])" + need.replace(".", "\\.") + "\\s*\\(", "i").test(f)) { msg.className = "no"; msg.textContent = fmtS(X.needFn, { f: need }); return; }
        if (ok) {
          msg.className = "yes"; msg.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + esc(X.ok);
          if (!done[e.id]) { done[e.id] = 1; store.set(done); drawList(); const h = main.querySelector(".xl-h h2"); if (h && !h.querySelector(".okb")) h.insertAdjacentHTML("beforeend", ' <span class="okb"><i class="fa-solid fa-circle-check"></i> ' + esc(X.solved) + "</span>"); }
        } else {
          msg.className = "no";
          msg.textContent = XL.isErr(got) ? (X.errs[got.err] || got.err) : fmtS(X.wrongV, { v: XL.show(got, gf) || "—", e: XL.show(want, wf) });
        }
      };
      document.getElementById("xlC").addEventListener("click", check);
      xin.addEventListener("keydown", (ev) => { if (ev.key === "Enter") { ev.preventDefault(); check(); } });
      document.getElementById("xlA").addEventListener("click", () => { xin.value = e.x.f; put(); msg.className = "hint"; msg.textContent = X.ans + ": " + e.x.f; });
      main.querySelector("[data-copy]").addEventListener("click", () => copyTxt(sh.raw(e.at)));
      main.querySelector("[data-reset]").addEventListener("click", () => lab(e));
      main.querySelectorAll("[data-go]").forEach((b) => b.addEventListener("click", () => { go(b.dataset.go); main.scrollIntoView({ behavior: "smooth", block: "start" }); }));
    }

    /* ----- the ready live test sheet ----- */
    function testView() {
      const D = DATA.TEST, sh = new XL.Sheet(D.d);
      D.f.forEach((f, i) => sh.set("Q" + (i + 1), f[0]));
      const meaning = (id) => { const e = DATA.L.find((x) => x.id === id); return e ? T(e)[0] : ""; };
      main.innerHTML = '<article class="xl-card c-test"><header class="xl-h"><span class="cat">' + esc(X.test) + "</span><h2>" + esc(X.testT) + "</h2><p>" + esc(X.testS) + "</p>" +
        '<div class="xl-acts"><button type="button" class="btn btn-ghost btn-sm" id="xlCA"><i class="fa-regular fa-copy"></i> ' + esc(X.copyAll) + '</button><button type="button" class="btn btn-primary btn-sm" id="xlCsv" title="' + esc(X.csvS) + '"><i class="fa-solid fa-file-arrow-down"></i> ' + esc(X.csv) + "</button></div></header>" +
        '<div id="xlSheet"></div><div class="xl-tbl"><table><thead><tr><th>#</th><th>' + esc(X.formula) + "</th><th>" + esc(X.ans) + "</th><th>" + esc(X.meaning) + "</th></tr></thead><tbody>" +
        D.f.map((f, i) => '<tr data-q="Q' + (i + 1) + '"><td>' + (i + 1) + '</td><td><code>' + esc(f[0]) + '</code></td><td class="r" data-r="Q' + (i + 1) + '"></td><td class="m">' + esc(meaning(f[1])) + ' <a href="#' + f[1] + '" data-open="' + f[1] + '">' + esc(X.open) + " →</a></td></tr>").join("") + "</tbody></table></div></article>";
      const res = () => main.querySelectorAll("[data-r]").forEach((td) => { const i = sh.info(td.dataset.r); td.textContent = XL.show(i.v, i.f); td.classList.toggle("bad", XL.isErr(i.v)); });
      const ui = sheetUI(document.getElementById("xlSheet"), sh, { cols: 15, rows: 10, title: "Test sheet", sel: "A1", onChange: res });
      res();
      main.querySelector(".xl-tbl tbody").addEventListener("click", (ev) => {
        const a = ev.target.closest("[data-open]"); if (a) { ev.preventDefault(); return go(a.dataset.open, true); }
        const tr = ev.target.closest("tr[data-q]"); if (!tr) return;
        main.querySelectorAll("tr.on").forEach((x) => x.classList.remove("on")); tr.classList.add("on");
        ui.highlight(tr.dataset.q);
        document.getElementById("xlSheet").scrollIntoView({ behavior: "smooth", block: "nearest" });
      });
      document.getElementById("xlCA").addEventListener("click", () => copyTxt(D.f.map((f) => f[0]).join("\n")));
      document.getElementById("xlCsv").addEventListener("click", () => {
        const q = (s) => (/[",\n]/.test(s) ? '"' + String(s).replace(/"/g, '""') + '"' : String(s));
        const lines = [];
        for (let r = 1; r <= 16; r++) {
          const row = []; for (let c = 1; c <= 15; c++) row.push(q(r <= 10 ? sh.raw(XL.colS(c) + r) : ""));
          row.push(""); row.push(q(D.f[r - 1] ? D.f[r - 1][0] : "")); lines.push(row.join(","));
        }
        const blob = new Blob(["﻿" + lines.join("\r\n")], { type: "text/csv;charset=utf-8" });
        const a = document.createElement("a"); a.href = URL.createObjectURL(blob); a.download = "COPA_Excel_Formula_Practice.csv"; document.body.appendChild(a); a.click(); setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500);
      });
    }
    function copyTxt(s) {
      const ok = () => C.toast(X.copyF);
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(s).then(ok).catch(() => fallback());
      else fallback();
      function fallback() { const t = document.createElement("textarea"); t.value = s; document.body.appendChild(t); t.select(); try { document.execCommand("copy"); ok(); } catch (er) { /* */ } t.remove(); }
    }

    window.addEventListener("hashchange", () => { const k = (location.hash || "").slice(1); if (k && k !== cur && (k === "test" || DATA.L.some((e) => e.id === k))) go(k); });
    drawList();
    const h = (location.hash || "").slice(1);
    go(h === "test" || DATA.L.some((e) => e.id === h) ? h : DATA.L[0].id);
  };
})();
