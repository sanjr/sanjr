/* =========================================================
   COPA Live Practice – VS Code-style code suggestions
   • auto-suggest while typing (HTML, CSS, JavaScript, Python)
   • snippets (for, if, def, function, !, table, form …)
   • Emmet-style Tab: "h1" + Tab → <h1></h1>, "!" + Tab → HTML page
   Uses CodeMirror 5 show-hint + html/css/javascript hint add-ons.
   ========================================================= */
(function () {
  "use strict";
  const CM = window.CodeMirror;
  if (!CM || !CM.showHint) { window.COPA_HINTS = { attach() {}, tab: () => false }; return; }

  /* ---------- data ---------- */
  const HTML_TAGS = {
    html: "Root of the page", head: "Page information", title: "Title in browser tab", body: "Visible content", meta: "Page metadata", link: "Link a CSS file", style: "CSS inside HTML", script: "JavaScript code",
    h1: "Heading 1 (largest)", h2: "Heading 2", h3: "Heading 3", h4: "Heading 4", h5: "Heading 5", h6: "Heading 6 (smallest)", p: "Paragraph", br: "Line break", hr: "Horizontal line",
    b: "Bold text", strong: "Important (bold)", i: "Italic text", em: "Emphasis (italic)", u: "Underline", sup: "Superscript (power)", sub: "Subscript", mark: "Highlight", small: "Small text", pre: "Preformatted text", code: "Code text", blockquote: "Quotation",
    a: "Hyperlink", img: "Image", ul: "Unordered (bullet) list", ol: "Ordered (number) list", li: "List item", table: "Table", tr: "Table row", th: "Table heading cell", td: "Table data cell", caption: "Table caption",
    form: "Form", input: "Input box", label: "Label for input", button: "Button", select: "Drop-down (combo box)", option: "Drop-down item", textarea: "Multi-line text box", fieldset: "Group form fields", legend: "Fieldset title",
    div: "Block container", span: "Inline container", nav: "Navigation links", header: "Page header", footer: "Page footer", section: "Section", article: "Article", aside: "Side content", main: "Main content",
    audio: "Sound player", video: "Video player", iframe: "Embed another page", marquee: "Scrolling text (old)", center: "Center text (old)", font: "Font (old)"
  };
  const VOID = { br: 1, hr: 1, img: 1, input: 1, meta: 1, link: 1 };
  const HTML_SNIP = {
    "!": ["HTML5 page", '<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="UTF-8">\n  <title>$0</title>\n</head>\n<body>\n\n</body>\n</html>'],
    "a": ["Hyperlink", '<a href="$0"></a>'], "img": ["Image", '<img src="$0" alt="">'], "input": ["Input box", '<input type="text" $0>'],
    "link": ["Link CSS file", '<link rel="stylesheet" href="$0">'], "script": ["JavaScript", "<script>\n  $0\n</script>"], "style": ["CSS", "<style>\n  $0\n</style>"],
    "table": ["Table 2×2", '<table border="1">\n  <tr>\n    <th>$0</th>\n    <th></th>\n  </tr>\n  <tr>\n    <td></td>\n    <td></td>\n  </tr>\n</table>'],
    "ul": ["Bullet list", "<ul>\n  <li>$0</li>\n  <li></li>\n</ul>"], "ol": ["Number list", "<ol>\n  <li>$0</li>\n  <li></li>\n</ol>"],
    "form": ["Form", '<form>\n  <label>Name:</label>\n  <input type="text">\n  <input type="submit" value="Submit">$0\n</form>'],
    "select": ["Drop-down", "<select>\n  <option>$0</option>\n  <option></option>\n</select>"],
    "input:text": ["Text box", '<input type="text" placeholder="$0">'], "input:checkbox": ["Checkbox", '<input type="checkbox"> $0'], "input:radio": ["Radio button", '<input type="radio" name="$0">'],
    "input:submit": ["Submit button", '<input type="submit" value="Submit$0">'], "input:password": ["Password box", '<input type="password" $0>'], "input:number": ["Number box", '<input type="number" $0>'],
    "audio": ["Audio player", '<audio src="$0" controls></audio>'], "video": ["Video player", '<video src="$0" width="320" controls></video>'], "iframe": ["Embed page", '<iframe src="$0" width="400" height="300"></iframe>'],
    "mailto": ["Email link", '<a href="mailto:$0">Send email</a>'], "button": ["Button", '<button onclick="$0">Click</button>']
  };
  const JS_SNIP = {
    log: ["console.log()", "console.log($0);"], alert: ["alert box", 'alert("$0");'], prompt: ["prompt box", 'var name = prompt("$0");'], confirm: ["confirm box", 'var ok = confirm("$0");'],
    function: ["function", "function name($1) {\n  $0\n}"], fn: ["function", "function name() {\n  $0\n}"], arrow: ["arrow function", "const name = () => {\n  $0\n};"],
    for: ["for loop", "for (let i = 0; i < 10; i++) {\n  $0\n}"], forof: ["for…of loop", "for (const item of array) {\n  $0\n}"], foreach: ["forEach", "array.forEach(function (item) {\n  $0\n});"],
    while: ["while loop", "while ($0) {\n  \n}"], dowhile: ["do…while loop", "do {\n  $0\n} while (condition);"],
    if: ["if", "if ($0) {\n  \n}"], ifelse: ["if…else", "if ($0) {\n  \n} else {\n  \n}"], switch: ["switch", 'switch ($0) {\n  case 1:\n    break;\n  default:\n    break;\n}'],
    try: ["try…catch", "try {\n  $0\n} catch (e) {\n  console.log(e.message);\n}"], throw: ["throw error", 'throw "$0";'],
    getid: ["getElementById", 'document.getElementById("$0")'], qs: ["querySelector", 'document.querySelector("$0")'], inner: ["innerHTML", 'document.getElementById("$0").innerHTML = "";'],
    onclick: ["click event", 'document.getElementById("$0").onclick = function () {\n  \n};'], ael: ["addEventListener", 'element.addEventListener("click", function () {\n  $0\n});'],
    settimeout: ["setTimeout", "setTimeout(function () {\n  $0\n}, 1000);"], arr: ["array", "var arr = [$0];"], obj: ["object", "var obj = {\n  $0\n};"], var: ["variable", "var $0 = ;"], let: ["variable", "let $0 = ;"], const: ["constant", "const $0 = ;"]
  };
  const JS_WORDS = ["var", "let", "const", "function", "return", "if", "else", "for", "while", "do", "switch", "case", "break", "continue", "default", "try", "catch", "finally", "throw", "new", "this", "typeof", "instanceof", "true", "false", "null", "undefined", "NaN", "Infinity",
    "document", "window", "console", "Math", "Date", "Number", "String", "Array", "Object", "JSON", "parseInt", "parseFloat", "isNaN", "eval", "alert", "prompt", "confirm", "setTimeout", "setInterval", "clearInterval", "localStorage", "screen", "navigator", "location"];
  const JS_MEMBERS = {
    document: ["getElementById()", "getElementsByClassName()", "getElementsByTagName()", "querySelector()", "querySelectorAll()", "createElement()", "write()", "body", "title", "head", "forms", "images", "links", "cookie", "addEventListener()"],
    console: ["log()", "error()", "warn()", "info()", "table()", "clear()"],
    Math: ["random()", "round()", "floor()", "ceil()", "sqrt()", "pow()", "abs()", "max()", "min()", "PI"],
    window: ["alert()", "prompt()", "confirm()", "open()", "close()", "print()", "location", "innerWidth", "innerHeight", "setTimeout()"],
    screen: ["width", "height", "availWidth", "availHeight"], JSON: ["stringify()", "parse()"], localStorage: ["getItem()", "setItem()", "removeItem()", "clear()"],
    _any: ["innerHTML", "innerText", "value", "style", "length", "push()", "pop()", "shift()", "unshift()", "sort()", "reverse()", "join()", "slice()", "splice()", "indexOf()", "includes()", "forEach()", "map()", "filter()", "toString()", "toUpperCase()", "toLowerCase()", "split()", "trim()", "replace()", "charAt()", "substring()", "toFixed()", "addEventListener()", "onclick", "classList", "setAttribute()", "getAttribute()", "appendChild()", "focus()"]
  };
  const PY_KW = ["False", "None", "True", "and", "as", "assert", "break", "class", "continue", "def", "del", "elif", "else", "except", "finally", "for", "from", "global", "if", "import", "in", "is", "lambda", "nonlocal", "not", "or", "pass", "raise", "return", "try", "while", "with", "yield"];
  const PY_BUILTIN = {
    print: "print(*values, sep=' ', end='\\n')", input: "input(prompt) → str", len: "len(obj) → int", range: "range(start, stop, step)", int: "int(x) → integer", float: "float(x) → decimal", str: "str(x) → text", bool: "bool(x)",
    list: "list(iterable)", dict: "dict()", set: "set(iterable)", tuple: "tuple(iterable)", type: "type(obj)", sum: "sum(iterable)", min: "min(values)", max: "max(values)", abs: "abs(x)", round: "round(x, digits)",
    sorted: "sorted(iterable)", reversed: "reversed(seq)", enumerate: "enumerate(iterable)", zip: "zip(a, b)", id: "id(obj)", isinstance: "isinstance(obj, type)", pow: "pow(x, y)", divmod: "divmod(a, b)",
    chr: "chr(code) → char", ord: "ord(char) → code", hex: "hex(x)", bin: "bin(x)", oct: "oct(x)", format: "format(value, spec)", open: "open(file, mode)", eval: "eval(text)", map: "map(func, iterable)", filter: "filter(func, iterable)", help: "help(obj)", dir: "dir(obj)"
  };
  const PY_SNIP = {
    def: ["function", "def name($1):\n    $0"], if: ["if", "if $0:\n    "], ifelse: ["if…else", "if $0:\n    \nelse:\n    "], elif: ["elif", "elif $0:\n    "],
    for: ["for loop (range)", "for i in range($0):\n    "], forin: ["for … in list", "for item in $0:\n    "], while: ["while loop", "while $0:\n    "],
    class: ["class", "class Name:\n    def __init__(self$1):\n        $0"], try: ["try…except", "try:\n    $0\nexcept Exception as e:\n    print(e)"],
    main: ["if __name__ == '__main__'", 'if __name__ == "__main__":\n    $0'], with: ["with open()", 'with open("$0", "r") as f:\n    data = f.read()'],
    inputint: ["int(input())", 'n = int(input("$0"))'], printf: ["print f-string", 'print(f"$0")'], lambda: ["lambda", "lambda x: $0"], listcomp: ["list comprehension", "[x for x in $0]"]
  };
  const PY_METHODS = ["append()", "extend()", "insert()", "remove()", "pop()", "clear()", "index()", "count()", "sort()", "reverse()", "copy()",
    "upper()", "lower()", "title()", "capitalize()", "strip()", "split()", "join()", "replace()", "find()", "startswith()", "endswith()", "isdigit()", "isalpha()", "format()",
    "keys()", "values()", "items()", "get()", "update()", "add()", "union()", "intersection()"];
  const PY_MODULES = {
    math: ["pi", "e", "sqrt()", "pow()", "floor()", "ceil()", "factorial()", "sin()", "cos()", "tan()", "log()", "log10()", "fabs()", "gcd()"],
    random: ["random()", "randint()", "choice()", "shuffle()", "randrange()", "uniform()", "sample()"],
    datetime: ["date", "datetime", "time", "timedelta", "date.today()", "datetime.now()"], time: ["time()", "sleep()", "ctime()"], os: ["getcwd()", "listdir()", "path"], sys: ["version", "argv", "exit()"],
    collections: ["Counter()", "defaultdict()", "OrderedDict()", "deque()", "namedtuple()"]
  };

  /* ---------- helpers ---------- */
  const KIND = { kw: ["K", "#c586c0", "keyword"], fn: ["ƒ", "#dcdcaa", "function"], sn: ["▤", "#4fc1ff", "snippet"], tag: ["<>", "#e44d26", "tag"], prop: ["P", "#9cdcfe", "property"], val: ["V", "#ce9178", "value"], word: ["ab", "#a0a0a0", "text"], mod: ["M", "#4ec9b0", "module"], m: ["m", "#b180d7", "method"] };
  function render(el, self, d) {
    const k = KIND[d.kind] || KIND.word;
    el.innerHTML = '<span class="hk" style="color:' + k[1] + '">' + k[0] + '</span><span class="ht"></span><span class="hd"></span>';
    el.querySelector(".ht").textContent = d.displayText || d.text;
    el.querySelector(".hd").textContent = d.detail || k[2];
  }
  function insertSnippet(cm, data, completion) {
    const from = completion.from || data.from, to = completion.to || data.to;
    const indent = cm.getLine(from.line).match(/^\s*/)[0];
    let txt = completion.body.replace(/\n/g, "\n" + indent);
    txt = txt.replace(/\$1/g, "");
    let at = txt.indexOf("$0"); txt = txt.replace("$0", "");
    if (at < 0) at = txt.length;
    cm.replaceRange(txt, from, to, "complete");
    cm.setCursor(cm.posFromIndex(cm.indexFromPos(from) + at));
  }
  const item = (text, kind, detail, extra) => Object.assign({ text, displayText: text, kind, detail, render }, extra || {});
  const snipItem = (key, s) => item(key, "sn", s[0], { body: s[1], hint: insertSnippet });
  function wordAt(cm, re) {
    const cur = cm.getCursor(), line = cm.getLine(cur.line);
    let s = cur.ch; while (s > 0 && re.test(line.charAt(s - 1))) s--;
    return { word: line.slice(s, cur.ch), from: CM.Pos(cur.line, s), to: cur, before: line.slice(0, s) };
  }
  function docWords(cm, cur, re) {
    const seen = {}, out = []; const txt = cm.getValue(); let m;
    const r = new RegExp(re, "g");
    while ((m = r.exec(txt))) { const w = m[0]; if (w.length > 2 && w !== cur && !seen[w]) { seen[w] = 1; out.push(w); } if (out.length > 300) break; }
    return out;
  }
  const starts = (a, p) => a.toLowerCase().indexOf(p.toLowerCase()) === 0;
  function rank(list, p) {
    const seen = {};
    return list.filter((x) => { const k = x.kind + "|" + x.text; if (seen[k]) return false; seen[k] = 1; return true; })
      .sort((a, b) => {
        const pa = starts(a.text, p) || starts(a.displayText.replace(/^</, ""), p), pb = starts(b.text, p) || starts(b.displayText.replace(/^</, ""), p);
        if (pa !== pb) return pb - pa;                               // prefix matches first
        const la = a.displayText.replace(/^</, "").length, lb = b.displayText.replace(/^</, "").length;
        if (la !== lb) return la - lb;                               // closest (shortest) match first
        return (b.kind === "sn") - (a.kind === "sn");                // same name → snippet first
      })
      .slice(0, 60);
  }

  /* ---------- per-language hint functions ---------- */
  function pythonHint(cm) {
    const w = wordAt(cm, /[\w]/);
    const dot = /([A-Za-z_][\w]*)\.$/.exec(w.before);
    let list = [];
    if (dot) {
      const mod = PY_MODULES[dot[1]];
      (mod || PY_METHODS).forEach((x) => {
        if (w.word && !starts(x, w.word)) return;
        const isFn = x.endsWith("()");
        list.push(item(x, isFn ? (mod ? "fn" : "m") : "prop", (mod ? dot[1] + "." : ".") + x, { displayText: x.replace(/\(\)$/, ""),
          hint: (c, d) => { c.replaceRange(x, d.from, d.to, "complete"); if (isFn) { const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); } } }));
      });
    } else {
      if (!w.word) return null;
      Object.keys(PY_SNIP).forEach((k) => { if (starts(k, w.word)) list.push(snipItem(k, PY_SNIP[k])); });
      PY_KW.forEach((k) => { if (starts(k, w.word)) list.push(item(k, "kw", "keyword")); });
      Object.keys(PY_BUILTIN).forEach((k) => { if (starts(k, w.word)) list.push(item(k + "()", "fn", PY_BUILTIN[k], { displayText: k, hint: (c, d) => { c.replaceRange(k + "()", d.from, d.to, "complete"); const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); } })); });
      Object.keys(PY_MODULES).forEach((k) => { if (starts(k, w.word)) list.push(item(k, "mod", "module")); });
      docWords(cm, w.word, "[A-Za-z_][\\w]*").forEach((x) => { if (starts(x, w.word) && x !== w.word) list.push(item(x, "word")); });
    }
    list = rank(list, w.word);
    return list.length ? { list, from: w.from, to: w.to } : null;
  }
  function jsHint(cm) {
    const w = wordAt(cm, /[\w$]/);
    const dot = /([A-Za-z_$][\w$]*)\.$/.exec(w.before);
    let list = [];
    if (dot) {
      (JS_MEMBERS[dot[1]] || JS_MEMBERS._any).forEach((x) => {
        if (w.word && !starts(x, w.word)) return;
        const isFn = x.endsWith("()");
        list.push(item(x, isFn ? "m" : "prop", isFn ? "method" : "property", { displayText: x.replace(/\(\)$/, ""), hint: (c, d) => { c.replaceRange(x, d.from, d.to, "complete"); if (isFn) { const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); } } }));
      });
    } else {
      if (!w.word) return null;
      Object.keys(JS_SNIP).forEach((k) => { if (starts(k, w.word)) list.push(snipItem(k, JS_SNIP[k])); });
      JS_WORDS.forEach((k) => { if (starts(k, w.word)) list.push(item(k, /^[a-z]+$/.test(k) && k.length < 11 && JS_WORDS.indexOf(k) < 29 ? "kw" : "fn", "")); });
      docWords(cm, w.word, "[A-Za-z_$][\\w$]*").forEach((x) => { if (starts(x, w.word) && x !== w.word) list.push(item(x, "word")); });
    }
    list = rank(list, w.word);
    return list.length ? { list, from: w.from, to: w.to } : null;
  }
  function cssHint(cm) {
    const r = CM.hint.css && CM.hint.css(cm);
    const w = wordAt(cm, /[\w-]/);
    let list = [];
    if (r && r.list) {
      const line = cm.getLine(cm.getCursor().line).slice(0, cm.getCursor().ch);
      const isVal = /:\s*[\w-]*$/.test(line);
      r.list.forEach((x) => { const t = typeof x === "string" ? x : x.text; list.push(item(t, isVal ? "val" : "prop", isVal ? "value" : "property", isVal ? {} : { hint: (c, d) => { c.replaceRange(t + ": ;", d.from, d.to, "complete"); const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); } })); });
      list = rank(list, w.word);
      return list.length ? { list, from: r.from, to: r.to } : null;
    }
    return null;
  }
  function htmlHint(cm) {
    const cur = cm.getCursor(), lineBefore = cm.getLine(cur.line).slice(0, cur.ch);
    if (/<[^>]*$/.test(lineBefore)) {
      const r = CM.hint.html && CM.hint.html(cm);
      if (!r || !r.list || !r.list.length) return null;
      const list = r.list.map((x) => { const t = typeof x === "string" ? x : x.text; const tag = /^</.test(t); const name = t.replace(/^<\/?/, "").replace(/[\s>="].*$/, ""); return item(t, tag ? "tag" : "prop", (tag && HTML_TAGS[name]) || (tag ? "tag" : "attribute")); });
      return { list: list.slice(0, 60), from: r.from, to: r.to };
    }
    // typing a tag name without "<" → Emmet-style tag/snippet suggestions (Enter or Tab inserts <tag></tag>)
    const w = wordAt(cm, /[\w:!]/);
    if (!w.word) return null;
    const list = [];
    Object.keys(HTML_SNIP).forEach((k) => { if (starts(k, w.word)) list.push(snipItem(k, HTML_SNIP[k])); });
    Object.keys(HTML_TAGS).forEach((k) => {
      if (!starts(k, w.word) || HTML_SNIP[k]) return;
      list.push(item(k, "tag", HTML_TAGS[k], { displayText: "<" + k + ">", body: VOID[k] ? "<" + k + ">$0" : "<" + k + ">$0</" + k + ">", hint: insertSnippet }));
    });
    return list.length ? { list: rank(list, w.word), from: w.from, to: w.to } : null;
  }
  function copaHint(cm) {
    const mode = cm.getModeAt(cm.getCursor()).name;
    if (mode === "python") return pythonHint(cm);
    if (mode === "javascript") return jsHint(cm);
    if (mode === "css") return cssHint(cm);
    return htmlHint(cm);
  }
  copaHint.supportsSelection = false;

  /* ---------- Emmet-style Tab expansion (HTML) ---------- */
  function tab(cm) {
    if (cm.state.completionActive) return false;
    const mode = cm.getModeAt(cm.getCursor()).name;
    if (mode !== "xml" && mode !== "htmlmixed") return false;
    const w = wordAt(cm, /[\w:!]/);
    if (!w.word || /<[^>]*$/.test(w.before)) return false;
    const s = HTML_SNIP[w.word];
    if (s) { insertSnippet(cm, null, { from: w.from, to: w.to, body: s[1] }); return true; }
    if (HTML_TAGS[w.word]) { insertSnippet(cm, null, { from: w.from, to: w.to, body: VOID[w.word] ? "<" + w.word + ">$0" : "<" + w.word + ">$0</" + w.word + ">" }); return true; }
    return false;
  }

  /* ---------- attach to an editor ---------- */
  function attach(cm) {
    let t;
    cm.on("inputRead", (c, ch) => {
      if (ch.origin !== "+input" || c.state.completionActive) return;
      const typed = ch.text[ch.text.length - 1];
      if (!/[\w.<!:$-]$/.test(typed)) return;
      const tok = c.getTokenAt(c.getCursor());
      if (tok.type && /comment/.test(tok.type)) return;
      if (tok.type === "string" && !/css/.test(c.getModeAt(c.getCursor()).name)) return;
      clearTimeout(t);
      t = setTimeout(() => c.showHint({ hint: copaHint, completeSingle: false, closeCharacters: /[\s()\[\]{};>,]/, alignWithWord: true }), 60);
    });
    cm.addKeyMap({ "Ctrl-Space": (c) => c.showHint({ hint: copaHint, completeSingle: false }) });
  }

  window.COPA_HINTS = { attach, tab, hint: copaHint };
})();
