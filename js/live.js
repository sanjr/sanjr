/* =========================================================
   COPA MCQ – Live Practice Lab
   HTML / CSS / JavaScript with live preview + console,
   Python (Pyodide, runs in the browser) with input() support.
   Logged-in students can save programs (title + code + output);
   they appear in the student dashboard.
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, LANG = C.lang;
  C.renderShell("livePractice");

  const TX = {
    en: { title: "Live Practice Lab", sub: "Write HTML, CSS, JavaScript or Python and see the output live – just like the COPA practical lab.",
      web: "HTML / CSS / JS", python: "Python", run: "Run", auto: "Live preview", save: "Save", newP: "New", download: "Download", reset: "Reset",
      titlePh: "Program title, e.g. Student Registration Form", templates: "Practicals", mine: "My Programs", search: "Search programs…",
      preview: "Live Output", console: "Console", clear: "Clear", output: "Output", stdin: "Input (stdin)", stdinHint: "one value per line – used by input()",
      pyLoading: "Loading Python… (first time ~14 MB)", pyReady: "Python ready", running: "Running…", saved: "Saved to your dashboard!", loginSave: "Login to save your program in your dashboard.",
      noMine: "No saved programs yet. Write a program and click Save.", loginMine: "Login to save and see your programs here.", del: "Delete this program?", untitled: "Untitled program",
      code: "Code", out: "Output", full: "Full screen", exitFull: "Exit full screen", needTitle: "Please enter a program title.", runFirst: "Program finished", copy: "Copy code", copied: "Code copied",
      tip1: ["Live preview", "HTML, CSS and JS output updates while you type."], tip2: ["Ctrl + Enter", "Runs the program (Python and web)."], tip3: ["Save to dashboard", "Login and save – your program and its output appear in your dashboard."],
      cats: { html: "HTML", css: "CSS", js: "JavaScript", py: "Python" }, pyErr: "Error", emptyOut: "Click ▶ Run to see the output." },
    hi: { title: "लाइव प्रैक्टिस लैब", sub: "HTML, CSS, JavaScript या Python लिखें और तुरंत आउटपुट देखें – बिलकुल COPA प्रैक्टिकल लैब जैसा।",
      web: "HTML / CSS / JS", python: "Python", run: "चलाएँ", auto: "लाइव प्रीव्यू", save: "सेव", newP: "नया", download: "डाउनलोड", reset: "रीसेट",
      titlePh: "प्रोग्राम का शीर्षक, जैसे Student Registration Form", templates: "प्रैक्टिकल", mine: "मेरे प्रोग्राम", search: "प्रोग्राम खोजें…",
      preview: "लाइव आउटपुट", console: "कंसोल", clear: "साफ़", output: "आउटपुट", stdin: "इनपुट (stdin)", stdinHint: "हर पंक्ति में एक मान – input() के लिए",
      pyLoading: "Python लोड हो रहा है… (पहली बार ~14 MB)", pyReady: "Python तैयार", running: "चल रहा है…", saved: "आपके डैशबोर्ड में सेव हो गया!", loginSave: "प्रोग्राम डैशबोर्ड में सेव करने के लिए लॉगिन करें।",
      noMine: "अभी कोई सेव प्रोग्राम नहीं। प्रोग्राम लिखकर Save दबाएँ।", loginMine: "अपने प्रोग्राम सेव करने और यहाँ देखने के लिए लॉगिन करें।", del: "यह प्रोग्राम हटाएँ?", untitled: "बिना शीर्षक प्रोग्राम",
      code: "कोड", out: "आउटपुट", full: "फुल स्क्रीन", exitFull: "फुल स्क्रीन बंद", needTitle: "कृपया प्रोग्राम का शीर्षक लिखें।", runFirst: "प्रोग्राम पूरा हुआ", copy: "कोड कॉपी", copied: "कोड कॉपी हुआ",
      tip1: ["लाइव प्रीव्यू", "टाइप करते समय HTML, CSS, JS का आउटपुट बदलता है।"], tip2: ["Ctrl + Enter", "प्रोग्राम चलाता है (Python और वेब)।"], tip3: ["डैशबोर्ड में सेव", "लॉगिन करके सेव करें – प्रोग्राम और आउटपुट डैशबोर्ड में दिखेंगे।"],
      cats: { html: "HTML", css: "CSS", js: "JavaScript", py: "Python" }, pyErr: "त्रुटि", emptyOut: "आउटपुट देखने के लिए ▶ चलाएँ दबाएँ।" },
    mr: { title: "लाइव्ह प्रॅक्टिस लॅब", sub: "HTML, CSS, JavaScript किंवा Python लिहा आणि लगेच आउटपुट पहा – अगदी COPA प्रॅक्टिकल लॅबसारखे.",
      web: "HTML / CSS / JS", python: "Python", run: "चालवा", auto: "लाइव्ह प्रिव्ह्यू", save: "सेव्ह", newP: "नवीन", download: "डाउनलोड", reset: "रीसेट",
      titlePh: "प्रोग्रामचे शीर्षक, उदा. Student Registration Form", templates: "प्रॅक्टिकल", mine: "माझे प्रोग्राम", search: "प्रोग्राम शोधा…",
      preview: "लाइव्ह आउटपुट", console: "कन्सोल", clear: "साफ", output: "आउटपुट", stdin: "इनपुट (stdin)", stdinHint: "प्रत्येक ओळीत एक मूल्य – input() साठी",
      pyLoading: "Python लोड होत आहे… (पहिल्यांदा ~14 MB)", pyReady: "Python तयार", running: "चालू आहे…", saved: "तुमच्या डॅशबोर्डमध्ये सेव्ह झाले!", loginSave: "प्रोग्राम डॅशबोर्डमध्ये सेव्ह करण्यासाठी लॉगिन करा.",
      noMine: "अजून एकही सेव्ह केलेला प्रोग्राम नाही. प्रोग्राम लिहून Save दाबा.", loginMine: "तुमचे प्रोग्राम सेव्ह करण्यासाठी व इथे पाहण्यासाठी लॉगिन करा.", del: "हा प्रोग्राम काढायचा?", untitled: "शीर्षक नसलेला प्रोग्राम",
      code: "कोड", out: "आउटपुट", full: "फुल स्क्रीन", exitFull: "फुल स्क्रीन बंद", needTitle: "कृपया प्रोग्रामचे शीर्षक लिहा.", runFirst: "प्रोग्राम पूर्ण झाला", copy: "कोड कॉपी", copied: "कोड कॉपी झाला",
      tip1: ["लाइव्ह प्रिव्ह्यू", "टाइप करताना HTML, CSS, JS चे आउटपुट बदलते."], tip2: ["Ctrl + Enter", "प्रोग्राम चालवते (Python आणि वेब)."], tip3: ["डॅशबोर्डमध्ये सेव्ह", "लॉगिन करून सेव्ह करा – प्रोग्राम आणि आउटपुट डॅशबोर्डमध्ये दिसेल."],
      cats: { html: "HTML", css: "CSS", js: "JavaScript", py: "Python" }, pyErr: "त्रुटी", emptyOut: "आउटपुट पाहण्यासाठी ▶ चालवा दाबा." }
  };
  const X = TX[LANG] || TX.en;
  const ICON = { html: ["fa-brands fa-html5", "#e44d26"], css: ["fa-brands fa-css3-alt", "#264de4"], js: ["fa-brands fa-js", "#c9a100"], py: ["fa-brands fa-python", "#3572a5"] };
  const FILES = { web: [["html", "index.html", "#e44d26", "htmlmixed"], ["css", "style.css", "#264de4", "css"], ["js", "script.js", "#f7df1e", "javascript"]], python: [["py", "main.py", "#ffd43b", "python"]] };
  const PRACT = window.COPA_PRACTICALS || [];
  const params = new URLSearchParams(location.search);
  const PKEY = "programs";

  const DEF = PRACT.find((p) => p.id === "w1");
  let S = {
    mode: "web", file: "html", id: null, tpl: "w1", title: DEF.title,
    buf: { html: DEF.html, css: DEF.css, js: DEF.js, py: PRACT.find((p) => p.id === "p1").py, stdin: "" },
    auto: true, libTab: "tpl", pyOut: []
  };
  const root = document.getElementById("lab");
  let cm = null, docs = {}, previewTimer = null, runToken = 0, consoleLines = [];

  /* ---------- layout ---------- */
  root.innerHTML =
    '<section class="lab-hero"><span class="lh-ic"><i class="fa-solid fa-laptop-code"></i></span><div><h1>' + X.title + "</h1><p>" + X.sub + "</p></div>" +
    '<div class="lang-badges"><span><i class="fa-brands fa-html5" style="color:#e44d26"></i>HTML</span><span><i class="fa-brands fa-css3-alt" style="color:#264de4"></i>CSS</span><span><i class="fa-brands fa-js" style="color:#c9a100"></i>JavaScript</span><span><i class="fa-brands fa-python" style="color:#3572a5"></i>Python</span></div></section>' +
    '<div class="lab-toolbar"><div class="mode-switch" role="tablist"><button data-mode="web"><i class="fa-solid fa-code"></i> ' + X.web + '</button><button data-mode="python"><i class="fa-brands fa-python"></i> ' + X.python + "</button></div>" +
    '<label class="title-in"><i class="fa-solid fa-heading"></i><input id="pTitle" maxlength="80" placeholder="' + esc(X.titlePh) + '"></label>' +
    '<label class="auto-tg" id="autoWrap"><input type="checkbox" id="autoRun" checked> ' + X.auto + "</label>" +
    '<button class="btn btn-run" id="runBtn"><i class="fa-solid fa-play"></i> <span class="lbl">' + X.run + "</span> <kbd>Ctrl+↵</kbd></button>" +
    '<button class="btn btn-primary" id="saveBtn"><i class="fa-solid fa-floppy-disk"></i> <span class="lbl">' + X.save + "</span></button>" +
    '<button class="btn btn-ghost" id="newBtn" title="' + X.newP + '"><i class="fa-solid fa-file-circle-plus"></i> <span class="lbl">' + X.newP + "</span></button>" +
    '<button class="btn btn-ghost" id="dlBtn" title="' + X.download + '"><i class="fa-solid fa-download"></i> <span class="lbl">' + X.download + "</span></button></div>" +
    '<div class="mob-switch" id="mobSw"><button data-show="code" class="on"><i class="fa-solid fa-code"></i> ' + X.code + '</button><button data-show="out"><i class="fa-solid fa-display"></i> ' + X.out + "</button></div>" +
    '<div class="lab" id="labGrid" data-show="code">' +
    '<aside class="lib"><div class="lib-tabs"><button data-lib="tpl"><i class="fa-solid fa-flask"></i> ' + X.templates + '</button><button data-lib="mine"><i class="fa-solid fa-folder-open"></i> ' + X.mine + ' <span id="mineCnt"></span></button></div>' +
    '<div class="lib-search"><input class="input" id="libQ" placeholder="' + esc(X.search) + '"></div><div class="lib-list" id="libList"></div></aside>' +
    '<section class="pane pane-ed"><div class="pane-bar" id="fileTabs"></div><div class="editor-host" id="edHost"></div></section>' +
    '<section class="pane pane-out" id="outPane"></section></div>' +
    '<div class="lab-tips">' + [X.tip1, X.tip2, X.tip3].map((tp, i) => '<div class="lab-tip"><i class="fa-solid fa-' + ["bolt", "keyboard", "cloud-arrow-up"][i] + '"></i><div><b>' + tp[0] + "</b>" + tp[1] + "</div></div>").join("") + "</div>" +
    '<div class="ad-slot" data-slot="bottom"></div>';

  const $ = (id) => document.getElementById(id);
  const titleIn = $("pTitle");

  /* ---------- editor ---------- */
  function initEditor() {
    if (window.CodeMirror) {
      cm = window.CodeMirror($("edHost"), {
        theme: "dracula", lineNumbers: true, lineWrapping: true, tabSize: 4, indentUnit: 4, indentWithTabs: false,
        autoCloseBrackets: true, autoCloseTags: true, matchBrackets: true, styleActiveLine: true,
        extraKeys: { "Ctrl-Enter": run, "Cmd-Enter": run, "Ctrl-S": () => save(), "Ctrl-/": "toggleComment", Tab: (c) => (c.somethingSelected() ? c.indentSelection("add") : c.replaceSelection("    ")) }
      });
      Object.keys(FILES).forEach((m) => FILES[m].forEach((f) => { docs[f[0]] = window.CodeMirror.Doc(S.buf[f[0]], f[3]); }));
      cm.swapDoc(docs[S.file]);
      cm.on("change", () => { S.buf[S.file] = cm.getValue(); onEdit(); });
    } else {
      const ta = document.createElement("textarea"); ta.className = "fallback"; ta.spellcheck = false; $("edHost").appendChild(ta);
      cm = { _ta: ta, getValue: () => ta.value, setValue: (v) => (ta.value = v), refresh() {}, focus: () => ta.focus() };
      ta.value = S.buf[S.file];
      ta.addEventListener("input", () => { S.buf[S.file] = ta.value; onEdit(); });
      ta.addEventListener("keydown", (e) => { if (e.ctrlKey && e.key === "Enter") { e.preventDefault(); run(); } if (e.key === "Tab") { e.preventDefault(); document.execCommand("insertText", false, "    "); } });
    }
  }
  function showFile(f) {
    S.file = f;
    if (docs[f] && cm.swapDoc) cm.swapDoc(docs[f]);
    else if (cm._ta) cm._ta.value = S.buf[f];
    drawFileTabs();
    setTimeout(() => cm.refresh(), 0);
  }
  function setBuffers(b) {
    Object.keys(b).forEach((k) => {
      S.buf[k] = b[k] == null ? "" : b[k];
      if (docs[k]) docs[k].setValue(S.buf[k]);
    });
    if (cm._ta) cm._ta.value = S.buf[S.file];
  }
  function drawFileTabs() {
    $("fileTabs").innerHTML = FILES[S.mode].map((f) => '<button class="ftab ' + (S.file === f[0] ? "on" : "") + '" data-f="' + f[0] + '"><span class="dot" style="background:' + f[2] + '"></span>' + f[1] + "</button>").join("") +
      '<span class="sp"></span><button class="mini" id="copyBtn" title="' + X.copy + '"><i class="fa-regular fa-copy"></i></button><button class="mini" id="resetBtn" title="' + X.reset + '"><i class="fa-solid fa-rotate-left"></i></button>';
    $("fileTabs").querySelectorAll("[data-f]").forEach((b) => b.addEventListener("click", () => showFile(b.getAttribute("data-f"))));
    $("copyBtn").addEventListener("click", () => { (navigator.clipboard ? navigator.clipboard.writeText(S.buf[S.file]) : Promise.reject()).then(() => C.toast(X.copied), () => {}); });
    $("resetBtn").addEventListener("click", () => { const p = PRACT.find((x) => x.id === S.tpl); if (p) loadTemplate(p.id); });
  }

  /* ---------- output panes ---------- */
  function drawOut() {
    const op = $("outPane");
    if (S.mode === "web") {
      op.innerHTML = '<div class="pane-bar"><div class="browser"><span class="dots"><i style="background:#ff5f57"></i><i style="background:#febc2e"></i><i style="background:#28c840"></i></span>' +
        '<span class="url"><i class="fa-solid fa-lock"></i>copa-live / ' + esc(S.title || "index.html") + "</span></div>" +
        '<span class="live-dot" id="liveDot">' + X.preview + '</span><button class="mini" id="fsBtn" title="' + X.full + '"><i class="fa-solid fa-expand"></i></button></div>' +
        '<div class="preview-host"><iframe id="pv" title="' + esc(X.preview) + '" sandbox="allow-scripts allow-modals allow-forms allow-popups"></iframe></div>' +
        '<div class="console web-console" id="webCon"><div class="console-head"><i class="fa-solid fa-terminal"></i> ' + X.console + ' <span id="conCnt"></span><span class="sp"></span><button id="conClr">' + X.clear + '</button> <button id="conMin"><i class="fa-solid fa-chevron-down"></i></button></div><div class="console-body" id="conBody"></div></div>';
      $("conClr").addEventListener("click", () => { consoleLines = []; drawConsole(); });
      $("conMin").addEventListener("click", () => { $("webCon").classList.toggle("min"); $("conMin").innerHTML = '<i class="fa-solid fa-chevron-' + ($("webCon").classList.contains("min") ? "up" : "down") + '"></i>'; });
      $("fsBtn").addEventListener("click", toggleFs);
      $("autoWrap").hidden = false;
      drawConsole();
      renderPreview();
    } else {
      op.innerHTML = '<div class="pane-bar"><span class="ftab on" style="cursor:default;color:var(--ink)"><i class="fa-solid fa-terminal"></i> ' + X.output + '</span><span class="sp"></span><span class="py-status" id="pyStat"><i class="fa-solid fa-circle-notch fa-spin"></i> ' + X.pyLoading + "</span>" +
        '<button class="mini" id="pyClr" title="' + X.clear + '"><i class="fa-solid fa-eraser"></i></button><button class="mini" id="fsBtn" title="' + X.full + '"><i class="fa-solid fa-expand"></i></button></div>' +
        '<div class="console py-console"><div class="console-body" id="pyBody"></div></div>' +
        '<div class="stdin-box"><label for="stdin"><i class="fa-solid fa-keyboard"></i> ' + X.stdin + " <small>– " + X.stdinHint + '</small></label><textarea id="stdin" spellcheck="false"></textarea></div>';
      $("stdin").value = S.buf.stdin || "";
      $("stdin").addEventListener("input", () => (S.buf.stdin = $("stdin").value));
      $("pyClr").addEventListener("click", () => { S.pyOut = []; drawPy(); });
      $("fsBtn").addEventListener("click", toggleFs);
      $("autoWrap").hidden = true;
      drawPy();
      loadPy().then(() => setPyStat("ready")).catch((e) => setPyStat("err", e.message));
      if (py) setPyStat("ready");
    }
  }
  function toggleFs() {
    const g = $("labGrid"); g.classList.toggle("fs");
    $("fsBtn").innerHTML = '<i class="fa-solid fa-' + (g.classList.contains("fs") ? "compress" : "expand") + '"></i>';
    $("fsBtn").title = g.classList.contains("fs") ? X.exitFull : X.full;
  }

  /* ---------- web preview ---------- */
  function buildDoc(b, token) {
    const base = location.href.replace(/[^/]*([?#].*)?$/, "");
    const hook = "<script>(function(){var T=" + JSON.stringify(String(token)) + ";function s(k,a){try{parent.postMessage({__copa:T,k:k,m:Array.prototype.map.call(a,function(x){try{return (x&&typeof x==='object')?JSON.stringify(x):String(x)}catch(e){return String(x)}}).join(' ')},'*')}catch(e){}}" +
      "['log','info','warn','error'].forEach(function(k){var o=console[k];console[k]=function(){s(k,arguments);if(o)o.apply(console,arguments)}});" +
      "window.onerror=function(m,u,l){s('error',[(l?'Line '+l+': ':'')+m]);return false;};})();<\/script>";
    const head = '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><base href="' + base + '">' + hook + "<style>" + (b.css || "") + "</style>";
    const js = b.js ? "<script>" + b.js.replace(/<\/script/gi, "<\\/script") + "\n<\/script>" : "";
    let html = b.html || "";
    if (/<html[\s>]/i.test(html)) {
      html = /<head[^>]*>/i.test(html) ? html.replace(/<head[^>]*>/i, (m) => m + head) : html.replace(/<html[^>]*>/i, (m) => m + "<head>" + head + "</head>");
      return /<\/body>/i.test(html) ? html.replace(/<\/body>/i, js + "</body>") : html + js;
    }
    return "<!doctype html><html><head>" + head + "</head><body>" + html + js + "</body></html>";
  }
  function renderPreview() {
    const pv = $("pv"); if (!pv) return;
    runToken++;
    consoleLines = [];
    drawConsole();
    pv.srcdoc = buildDoc(S.buf, runToken);
    const d = $("liveDot"); if (d) { d.style.animation = "none"; d.offsetHeight; d.style.animation = ""; }
  }
  window.addEventListener("message", (e) => {
    const d = e.data; if (!d || d.__copa !== String(runToken)) return;
    consoleLines.push({ k: d.k === "log" ? "out" : d.k === "error" ? "err" : d.k, m: d.m, t: new Date() });
    if (consoleLines.length > 300) consoleLines.shift();
    drawConsole();
  });
  function drawConsole() {
    const b = $("conBody"); if (!b) return;
    b.innerHTML = consoleLines.length ? consoleLines.map((l) => '<div class="cl ' + l.k + '"><span class="tm">' + l.t.toLocaleTimeString() + "</span>" + esc(l.m) + "</div>").join("") : '<div class="console-empty">console.log() output and errors appear here</div>';
    b.scrollTop = b.scrollHeight;
    const c = $("conCnt"); if (c) c.textContent = consoleLines.length ? "(" + consoleLines.length + ")" : "";
  }

  /* ---------- python ---------- */
  let py = null, pyP = null, pyBusy = false, stdinQueue = [];
  function setPyStat(st, msg) {
    const el = $("pyStat"); if (!el) return;
    el.className = "py-status " + (st === "ready" ? "ready" : st === "run" ? "run" : "");
    el.innerHTML = st === "ready" ? '<i class="fa-solid fa-circle-check"></i> ' + X.pyReady : st === "run" ? '<i class="fa-solid fa-circle-notch fa-spin"></i> ' + X.running : st === "err" ? '<i class="fa-solid fa-triangle-exclamation"></i> ' + esc(msg || "Python could not load") : '<i class="fa-solid fa-circle-notch fa-spin"></i> ' + X.pyLoading;
  }
  function loadPy() {
    if (pyP) return pyP;
    const dir = new URL("assets/vendor/pyodide/", location.href).href;
    pyP = new Promise((res, rej) => {
      const s = document.createElement("script"); s.src = dir + "pyodide.js";
      s.onload = () => window.loadPyodide({ indexURL: dir }).then(res, rej);
      s.onerror = () => rej(new Error("Python engine not found"));
      document.head.appendChild(s);
    }).then((p) => {
      py = p;
      window.__copa_input = (prompt) => {
        prompt = String(prompt || "");
        let v = stdinQueue.length ? stdinQueue.shift() : window.prompt(prompt || "Input", "");
        if (v == null) v = "";
        pyLine(prompt + v, "in");
        return v;
      };
      p.runPython("import builtins, js\ndef _copa_input(prompt=''):\n    return str(js.__copa_input(str(prompt)))\nbuiltins.input = _copa_input\n");
      p.setStdout({ batched: (s) => pyLine(s, "out") });
      p.setStderr({ batched: (s) => pyLine(s, "err") });
      return p;
    });
    pyP.catch(() => { pyP = null; });
    return pyP;
  }
  function pyLine(s, k) { S.pyOut.push({ k, m: s }); if (S.pyOut.length > 1000) S.pyOut.shift(); drawPy(); }
  function drawPy() {
    const b = $("pyBody"); if (!b) return;
    b.innerHTML = S.pyOut.length ? S.pyOut.map((l) => '<div class="cl ' + l.k + '">' + esc(l.m) + "</div>").join("") : '<div class="console-empty">' + X.emptyOut + "</div>";
    b.scrollTop = b.scrollHeight;
  }
  function runPython() {
    if (pyBusy) return;
    pyBusy = true;
    S.pyOut = [];
    pyLine("▶ " + (S.title || "main.py") + "  —  " + new Date().toLocaleTimeString(), "info");
    setPyStat("run");
    stdinQueue = (S.buf.stdin || "").split(/\r?\n/).filter((x, i, a) => !(i === a.length - 1 && x === ""));
    const t0 = performance.now();
    loadPy().then((p) => {
      const g = p.globals.get("dict")();
      return p.runPythonAsync(S.buf.py, { globals: g }).finally(() => g.destroy && g.destroy());
    }).then(() => {
      pyLine("✔ " + X.runFirst + " (" + Math.round(performance.now() - t0) + " ms)", "ok");
    }).catch((e) => {
      const msg = String((e && e.message) || e);
      const all = msg.split("\n");
      let i = -1; all.forEach((l, k) => { if (/File "<exec>"/.test(l)) i = k; });
      let lines = (i > -1 ? all.slice(i) : all).filter((l) => l.trim() && !/^\s+\^+\s*$/.test(l));
      lines = lines.map((l) => l.replace(/^\s*File "<exec>", line (\d+).*$/, "Line $1:"));
      pyLine(X.pyErr + " → " + lines.join("\n"), "err");
    }).finally(() => { pyBusy = false; setPyStat(py ? "ready" : "err"); });
  }

  /* ---------- actions ---------- */
  function run() {
    if (S.mode === "web") renderPreview(); else runPython();
    if (window.innerWidth <= 760) setShow("out");
  }
  function onEdit() {
    if (S.mode === "web" && S.auto) { clearTimeout(previewTimer); previewTimer = setTimeout(renderPreview, 500); }
  }
  function setMode(m) {
    S.mode = m;
    root.querySelectorAll("[data-mode]").forEach((b) => b.classList.toggle("on", b.getAttribute("data-mode") === m));
    showFile(FILES[m][0][0]);
    drawOut();
  }
  function loadTemplate(id) {
    const p = PRACT.find((x) => x.id === id); if (!p) return;
    S.tpl = id; S.id = null; S.title = p.title; titleIn.value = p.title;
    if (p.lang === "web") setBuffers({ html: p.html || "", css: p.css || "", js: p.js || "" });
    else { setBuffers({ py: p.py || "" }); S.buf.stdin = p.stdin || ""; S.pyOut = []; }
    setMode(p.lang);
    drawLib();
    history.replaceState(null, "", "live-practice.html?t=" + id);
  }
  const mine = () => (C.getProfile() ? C.store.get(PKEY, []) : []);
  function loadSaved(id) {
    const p = mine().find((x) => x.id === id); if (!p) return;
    S.id = p.id; S.tpl = p.tpl || null; S.title = p.title; titleIn.value = p.title;
    if (p.lang === "web") setBuffers({ html: p.html || "", css: p.css || "", js: p.js || "" });
    else { setBuffers({ py: p.py || "" }); S.buf.stdin = p.stdin || ""; S.pyOut = (p.output || []).slice(); }
    setMode(p.lang);
    drawLib();
    history.replaceState(null, "", "live-practice.html?p=" + id);
  }
  function save() {
    const prof = C.getProfile();
    if (!prof) { C.toast(X.loginSave); C.openLogin(() => { location.href = "live-practice.html" + (S.tpl ? "?t=" + S.tpl : ""); }); return; }
    S.title = titleIn.value.trim();
    if (!S.title) { C.toast(X.needTitle); titleIn.focus(); return; }
    const list = C.store.get(PKEY, []);
    const now = Date.now();
    let rec = S.id ? list.find((x) => x.id === S.id) : null;
    if (!rec) { rec = { id: "pg" + now.toString(36), created: now }; list.unshift(rec); }
    Object.assign(rec, { title: S.title, lang: S.mode, tpl: S.tpl, updated: now });
    if (S.mode === "web") Object.assign(rec, { html: S.buf.html, css: S.buf.css, js: S.buf.js, log: consoleLines.slice(-30).map((l) => ({ k: l.k, m: l.m })) });
    else Object.assign(rec, { py: S.buf.py, stdin: S.buf.stdin, output: S.pyOut.slice(-200) });
    // move to top
    const i = list.indexOf(rec); if (i > 0) { list.splice(i, 1); list.unshift(rec); }
    C.store.set(PKEY, list.slice(0, 100));
    S.id = rec.id;
    history.replaceState(null, "", "live-practice.html?p=" + rec.id);
    C.toast(X.saved);
    S.libTab = "mine"; drawLib();
  }
  function newProgram() {
    S.id = null; S.tpl = null; S.title = ""; titleIn.value = "";
    if (S.mode === "web") setBuffers({ html: "<h1>Hello COPA</h1>\n<p>Start coding…</p>", css: "body { font-family: Arial; padding: 20px; }", js: "" });
    else { setBuffers({ py: '# Write your Python program here\nprint("Hello COPA")\n' }); S.buf.stdin = ""; S.pyOut = []; }
    setMode(S.mode); drawLib(); titleIn.focus();
    history.replaceState(null, "", "live-practice.html");
  }
  function download() {
    const name = (titleIn.value.trim() || "program").replace(/[^\w\- ]+/g, "").replace(/\s+/g, "_") || "program";
    let content, type, file;
    if (S.mode === "web") { content = buildDoc(S.buf, 0).replace(/<script>\(function\(\)\{var T=[\s\S]*?<\/script>/, ""); type = "text/html"; file = name + ".html"; }
    else { content = S.buf.py; type = "text/x-python"; file = name + ".py"; }
    const a = document.createElement("a"); a.href = URL.createObjectURL(new Blob([content], { type })); a.download = file; document.body.appendChild(a); a.click();
    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 400);
  }
  function setShow(v) {
    $("labGrid").setAttribute("data-show", v);
    $("mobSw").querySelectorAll("button").forEach((b) => b.classList.toggle("on", b.getAttribute("data-show") === v));
    if (v === "code") setTimeout(() => cm.refresh(), 0);
  }

  /* ---------- library ---------- */
  function drawLib() {
    root.querySelectorAll("[data-lib]").forEach((b) => b.classList.toggle("on", b.getAttribute("data-lib") === S.libTab));
    const q = ($("libQ").value || "").trim().toLowerCase();
    const list = $("libList");
    const my = mine();
    $("mineCnt").textContent = my.length ? "(" + my.length + ")" : "";
    if (S.libTab === "tpl") {
      let html = "", last = "";
      PRACT.filter((p) => !q || p.title.toLowerCase().indexOf(q) > -1).forEach((p) => {
        if (p.cat !== last) { last = p.cat; html += '<div class="lib-group"><i class="' + ICON[p.cat][0] + '" style="color:' + ICON[p.cat][1] + '"></i>' + X.cats[p.cat] + "</div>"; }
        html += '<button class="lib-item ' + (S.tpl === p.id && !S.id ? "on" : "") + '" data-tpl="' + p.id + '"><span class="li-ic" style="background:' + ICON[p.cat][1] + '"><i class="' + ICON[p.cat][0] + '"></i></span><span class="li-t">' + esc(p.title.replace(/^(JS|Python) – /, "")) + "</span></button>";
      });
      list.innerHTML = html || '<p class="lib-empty">—</p>';
      list.querySelectorAll("[data-tpl]").forEach((b) => b.addEventListener("click", () => loadTemplate(b.getAttribute("data-tpl"))));
    } else {
      if (!C.getProfile()) { list.innerHTML = '<div class="lib-empty"><i class="fa-solid fa-user-lock" style="font-size:24px"></i><p>' + X.loginMine + '</p><button class="btn btn-primary btn-sm" id="libLogin">' + C.t("login") + "</button></div>"; $("libLogin").addEventListener("click", () => C.openLogin(() => location.reload())); return; }
      const items = my.filter((p) => !q || p.title.toLowerCase().indexOf(q) > -1);
      list.innerHTML = items.length ? items.map((p) => { const ic = p.lang === "python" ? ICON.py : ICON.html; return '<div class="lib-item ' + (S.id === p.id ? "on" : "") + '" data-pid="' + p.id + '" role="button" tabindex="0"><span class="li-ic" style="background:' + ic[1] + '"><i class="' + ic[0] + '"></i></span><span class="li-t">' + esc(p.title) + "<small>" + new Date(p.updated).toLocaleString() + '</small></span><span class="li-del" data-delp="' + p.id + '" title="Delete"><i class="fa-solid fa-trash"></i></span></div>'; }).join("")
        : '<p class="lib-empty">' + X.noMine + "</p>";
      list.querySelectorAll("[data-pid]").forEach((el) => el.addEventListener("click", (e) => {
        if (e.target.closest("[data-delp]")) {
          if (!confirm(X.del)) return;
          C.store.set(PKEY, C.store.get(PKEY, []).filter((x) => x.id !== el.getAttribute("data-pid")));
          if (S.id === el.getAttribute("data-pid")) S.id = null;
          drawLib(); return;
        }
        loadSaved(el.getAttribute("data-pid"));
      }));
    }
  }

  /* ---------- wire up ---------- */
  initEditor();
  root.querySelectorAll("[data-mode]").forEach((b) => b.addEventListener("click", () => {
    const m = b.getAttribute("data-mode"); if (m === S.mode) return;
    const t = PRACT.find((p) => p.lang === m); // a saved program has one language → start fresh in the other mode
    if (t) loadTemplate(t.id); else { S.id = null; setMode(m); }
  }));
  root.querySelectorAll("[data-lib]").forEach((b) => b.addEventListener("click", () => { S.libTab = b.getAttribute("data-lib"); drawLib(); }));
  $("libQ").addEventListener("input", drawLib);
  $("runBtn").addEventListener("click", run);
  $("saveBtn").addEventListener("click", save);
  $("newBtn").addEventListener("click", newProgram);
  $("dlBtn").addEventListener("click", download);
  $("autoRun").addEventListener("change", (e) => { S.auto = e.target.checked; if (S.auto) renderPreview(); });
  titleIn.addEventListener("input", () => { S.title = titleIn.value; const u = root.querySelector(".browser .url"); if (u) u.innerHTML = '<i class="fa-solid fa-lock"></i>copa-live / ' + esc(S.title || "index.html"); });
  $("mobSw").querySelectorAll("button").forEach((b) => b.addEventListener("click", () => setShow(b.getAttribute("data-show"))));
  document.addEventListener("keydown", (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === "Enter") { e.preventDefault(); run(); }
    if ((e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S")) { e.preventDefault(); save(); }
    if (e.key === "Escape" && $("labGrid").classList.contains("fs")) toggleFs();
  });

  if (params.get("p") && mine().some((x) => x.id === params.get("p"))) { S.libTab = "mine"; loadSaved(params.get("p")); }
  else if (params.get("t") && PRACT.some((x) => x.id === params.get("t"))) loadTemplate(params.get("t"));
  else if (params.get("lang") === "python") loadTemplate("p1");
  else loadTemplate("w1");
})();
