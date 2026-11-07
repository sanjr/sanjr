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
    getid: ["getElementById", 'document.getElementById("$0")'], qs: ["querySelector", 'document.querySelector("$0")'],
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
  /* ---------- JavaScript (type-aware members, like VS Code) ---------- */
  const JS_TYPES = {
    string: ["length", "toUpperCase()", "toLowerCase()", "charAt()", "indexOf()", "includes()", "slice()", "substring()", "split()", "replace()", "trim()", "concat()", "startsWith()", "endsWith()", "repeat()", "padStart()"],
    array: ["length", "push()", "pop()", "shift()", "unshift()", "sort()", "reverse()", "join()", "slice()", "splice()", "indexOf()", "includes()", "forEach()", "map()", "filter()", "reduce()", "find()", "concat()", "toString()"],
    number: ["toFixed()", "toString()", "toPrecision()"],
    element: ["innerHTML", "innerText", "textContent", "value", "style", "className", "classList", "id", "onclick", "onchange", "onmouseover", "checked", "disabled", "src", "href", "children", "parentElement",
      "addEventListener()", "setAttribute()", "getAttribute()", "appendChild()", "removeChild()", "remove()", "focus()", "click()", "querySelector()"],
    style: ["color", "backgroundColor", "fontSize", "fontWeight", "fontFamily", "display", "width", "height", "margin", "padding", "border", "borderRadius", "textAlign", "visibility", "opacity", "backgroundImage", "transform"],
    classList: ["add()", "remove()", "toggle()", "contains()"],
    date: ["getFullYear()", "getMonth()", "getDate()", "getDay()", "getHours()", "getMinutes()", "getSeconds()", "getTime()", "toLocaleDateString()", "toLocaleTimeString()", "toDateString()"],
    object: ["hasOwnProperty()", "toString()"]
  };
  const JS_FUNCS = { alert: "alert(message)", prompt: "prompt(text) → string", confirm: "confirm(text) → true/false", parseInt: "parseInt(text) → integer", parseFloat: "parseFloat(text) → decimal", isNaN: "isNaN(value)",
    eval: "eval(code)", setTimeout: "setTimeout(fn, ms)", setInterval: "setInterval(fn, ms)", clearInterval: "clearInterval(id)", Number: "Number(value)", String: "String(value)", Boolean: "Boolean(value)" };
  const JS_OBJECTS = { document: "web page (DOM)", window: "browser window", console: "developer console", Math: "maths functions", Date: "dates – new Date()", JSON: "JSON tools", Array: "Array", Object: "Object", localStorage: "saved data", screen: "screen size", navigator: "browser info", location: "page address" };
  const JS_KEYWORDS = ["var", "let", "const", "function", "return", "if", "else", "for", "while", "do", "switch", "case", "break", "continue", "default", "try", "catch", "finally", "throw", "new", "this", "typeof", "instanceof", "in", "of", "true", "false", "null", "undefined", "NaN", "Infinity"];
  function jsTypeOf(cm, recv) {
    // recv = text just before the dot
    if (/["'`]$/.test(recv)) return "string";
    if (/\]$/.test(recv) && /\[[^\[\]]*\]$/.test(recv) && !/[\w$)]\[[^\[\]]*\]$/.test(recv)) return "array";      // [1,2].
    if (/(getElementById|querySelector|createElement)\s*\([^()]*\)$/.test(recv)) return "element";
    if (/querySelectorAll\s*\([^()]*\)$|getElementsBy\w+\s*\([^()]*\)$/.test(recv)) return "array";
    if (/new\s+Date\s*\([^()]*\)$/.test(recv)) return "date";
    if (/\.style$/.test(recv) || /^style$/.test(recv)) return "style";
    if (/\.classList$/.test(recv)) return "classList";
    if (/\.(innerHTML|innerText|textContent|value|id|className)$/.test(recv) || /\.(toUpperCase|toLowerCase|trim|slice|substring|replace|charAt|join|toString)\([^()]*\)$/.test(recv) || /\b(prompt|String)\s*\([^()]*\)$/.test(recv)) return "string";
    if (/\.(split|map|filter|concat|sort|reverse)\([^()]*\)$/.test(recv)) return "array";
    if (/\b(parseInt|parseFloat|Number)\s*\([^()]*\)$|\.length$/.test(recv)) return "number";
    const m = /([A-Za-z_$][\w$]*)$/.exec(recv);
    if (!m) return null;
    const name = m[1];
    if (JS_MEMBERS[name]) return "obj:" + name;
    // look at how the variable was assigned:  name = "…" | [ | 12 | document.getElementById(…) | new Date()
    const txt = cm.getValue();
    const re = new RegExp("(?:^|[^\\w$.])" + name.replace(/\$/g, "\\$") + "\\s*=\\s*([^;\\n]+)", "g");
    let last = null, mm; while ((mm = re.exec(txt))) last = mm[1].trim();
    if (last == null) { if (new RegExp("function\\s*\\w*\\s*\\([^)]*\\b" + name + "\\b").test(txt)) return null; return null; }
    if (/^["'`]/.test(last) || /^prompt\s*\(/.test(last) || /^String\s*\(/.test(last)) return "string";
    if (/^\[/.test(last) || /\.split\(/.test(last)) return "array";
    if (/^-?\d/.test(last) || /^(parseInt|parseFloat|Number)\s*\(/.test(last)) return "number";
    if (/^document\.(getElementById|querySelector|createElement)\s*\(/.test(last)) return "element";
    if (/^document\.(querySelectorAll|getElementsBy\w+)\s*\(/.test(last)) return "array";
    if (/^new\s+Date/.test(last)) return "date";
    if (/^\{/.test(last)) return "object";
    return null;
  }
  const callItem = (name, kind, detail) => item(name + "()", kind, detail, { displayText: name, hint: (c, d) => { c.replaceRange(name + "()", d.from, d.to, "complete"); const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); } });
  function memberItems(names, prefix, detail) {
    const out = [];
    names.forEach((x) => {
      if (prefix && !starts(x, prefix)) return;
      const isFn = x.endsWith("()");
      out.push(isFn ? callItem(x.slice(0, -2), "m", detail || "method") : item(x, "prop", detail || "property"));
    });
    return out;
  }
  function jsHint(cm) {
    const w = wordAt(cm, /[\w$]/);
    const dotM = /\.\s*$/.exec(w.before);
    let list = [];
    if (dotM) {
      const recv = w.before.slice(0, dotM.index).replace(/\s+$/, "");
      if (/\d$/.test(recv) && !/[\w$]\d*$/.test(recv.replace(/\d+$/, ""))) return null;     // "3." is a decimal number
      const ty = jsTypeOf(cm, recv);
      if (ty && ty.indexOf("obj:") === 0) list = memberItems(JS_MEMBERS[ty.slice(4)], w.word, ty.slice(4) + " method").map((x) => { if (x.kind === "prop") x.detail = ty.slice(4) + " property"; return x; });
      else if (ty) list = memberItems(JS_TYPES[ty], w.word, null).map((x) => { x.detail = ty + (x.kind === "m" ? " method" : " property"); return x; });
      else {
        // unknown type → common members of strings, arrays and HTML elements
        const seen = {};
        ["element", "string", "array"].forEach((t) => JS_TYPES[t].forEach((x) => { if (!seen[x]) { seen[x] = 1; } }));
        list = memberItems(Object.keys(seen), w.word, null);
      }
      // properties/methods already used in this file
      const used = {}; let m; const re = /\.([A-Za-z_$][\w$]*)/g; const txt = cm.getValue();
      while ((m = re.exec(txt))) if (m[1] !== w.word && (!w.word || starts(m[1], w.word))) used[m[1]] = 1;
      Object.keys(used).forEach((u) => { if (!list.some((x) => x.displayText === u)) list.push(item(u, "word", "used in this file")); });
      list = list.slice(0, 60);
      return list.length ? { list, from: w.from, to: w.to } : null;
    }
    if (!w.word) return null;
    if (/\d/.test(w.word[0])) return null;
    Object.keys(JS_SNIP).forEach((k) => { if (starts(k, w.word)) list.push(snipItem(k, JS_SNIP[k])); });
    JS_KEYWORDS.forEach((k) => { if (starts(k, w.word)) list.push(item(k, "kw", "keyword")); });
    Object.keys(JS_OBJECTS).forEach((k) => { if (starts(k, w.word)) list.push(item(k, "mod", JS_OBJECTS[k])); });
    Object.keys(JS_FUNCS).forEach((k) => { if (starts(k, w.word)) list.push(callItem(k, "fn", JS_FUNCS[k])); });
    // your own functions and variables
    const txt = cm.getValue(), own = {}; let m;
    const fre = /function\s+([A-Za-z_$][\w$]*)/g; while ((m = fre.exec(txt))) own[m[1]] = "fn";
    const vre = /(?:var|let|const)\s+([A-Za-z_$][\w$]*)/g; while ((m = vre.exec(txt))) if (!own[m[1]]) own[m[1]] = "var";
    Object.keys(own).forEach((k) => { if (k !== w.word && starts(k, w.word)) list.push(own[k] === "fn" ? callItem(k, "fn", "your function") : item(k, "prop", "your variable")); });
    docWords(cm, w.word, "[A-Za-z_$][\\w$]*").forEach((x) => { if (starts(x, w.word) && x !== w.word && !own[x]) list.push(item(x, "word")); });
    // one entry per name: snippet > function/variable > keyword > text
    const pri = { sn: 0, fn: 1, mod: 1, prop: 1, m: 1, kw: 2, word: 3 };
    const best = {};
    list.forEach((x) => { const k = x.displayText; if (x.kind === "sn") { best["sn|" + k] = x; return; } if (!best[k] || pri[x.kind] < pri[best[k].kind]) best[k] = x; });
    list = rank(Object.keys(best).map((k) => best[k]), w.word);
    return list.length ? { list, from: w.from, to: w.to } : null;
  }

  /* ---------- CSS ---------- */
  const COLORS = ["red", "blue", "green", "yellow", "orange", "purple", "pink", "black", "white", "gray", "grey", "brown", "navy", "maroon", "teal", "lime", "aqua", "olive", "gold", "silver", "violet", "indigo", "crimson", "skyblue", "lightblue", "lightgreen", "lightgray", "darkblue", "darkgreen", "darkred", "transparent", "#ffffff", "#000000", "rgb(0, 0, 0)", "rgba(0, 0, 0, 0.5)"];
  const SIZES = ["10px", "12px", "14px", "16px", "18px", "20px", "24px", "30px", "50%", "100%", "auto", "1em", "1.5em", "2em", "1rem", "0"];
  const CSS_VALUES = {
    color: COLORS, "background-color": COLORS, background: COLORS.concat(["url(\"image.jpg\")", "linear-gradient(to right, red, blue)", "none"]), "border-color": COLORS, "outline-color": COLORS,
    "text-align": ["left", "center", "right", "justify"], display: ["block", "inline", "inline-block", "flex", "grid", "none", "table"],
    position: ["static", "relative", "absolute", "fixed", "sticky"], "font-weight": ["normal", "bold", "bolder", "lighter", "100", "300", "400", "500", "600", "700", "900"],
    "font-style": ["normal", "italic", "oblique"], "font-family": ["Arial, sans-serif", "\"Times New Roman\", serif", "Verdana, sans-serif", "Georgia, serif", "\"Courier New\", monospace", "Tahoma, sans-serif", "sans-serif", "serif", "monospace"],
    "font-size": SIZES, width: SIZES, height: SIZES, "max-width": SIZES, "min-height": SIZES, margin: SIZES.concat(["0 auto", "10px 20px"]), padding: SIZES.concat(["10px 20px"]), "border-radius": ["4px", "8px", "10px", "50%"], "line-height": ["1.5", "normal", "20px"],
    "text-decoration": ["none", "underline", "overline", "line-through"], "text-transform": ["uppercase", "lowercase", "capitalize", "none"],
    border: ["1px solid black", "2px dashed red", "3px dotted blue", "none"], "border-style": ["solid", "dashed", "dotted", "double", "groove", "ridge", "none"], "border-collapse": ["collapse", "separate"],
    float: ["left", "right", "none"], clear: ["both", "left", "right", "none"], cursor: ["pointer", "default", "text", "move", "not-allowed", "help"],
    "justify-content": ["center", "space-between", "space-around", "space-evenly", "flex-start", "flex-end"], "align-items": ["center", "flex-start", "flex-end", "stretch", "baseline"],
    "flex-direction": ["row", "column", "row-reverse", "column-reverse"], "flex-wrap": ["wrap", "nowrap"], overflow: ["hidden", "auto", "scroll", "visible"],
    "list-style-type": ["none", "disc", "circle", "square", "decimal", "lower-alpha", "upper-roman"], "list-style": ["none", "disc", "square", "decimal"],
    visibility: ["visible", "hidden"], "white-space": ["nowrap", "normal", "pre"], "vertical-align": ["middle", "top", "bottom", "baseline"],
    "background-repeat": ["no-repeat", "repeat", "repeat-x", "repeat-y"], "background-size": ["cover", "contain", "auto", "100% 100%"], "background-position": ["center", "top", "bottom", "left", "right"],
    "background-image": ["url(\"image.jpg\")", "linear-gradient(to right, red, blue)", "none"], "box-shadow": ["0 4px 10px rgba(0, 0, 0, 0.2)", "none"], "text-shadow": ["2px 2px 4px gray", "none"],
    opacity: ["0", "0.5", "1"], "z-index": ["1", "10", "100", "-1"], transition: ["all 0.3s", "background 0.5s ease", "none"], "box-sizing": ["border-box", "content-box"]
  };
  const PSEUDO = [":hover", ":active", ":focus", ":first-child", ":last-child", ":nth-child(2)", ":nth-child(even)", ":visited", ":link", "::before", "::after", "::first-letter", "::first-line", ":checked"];
  function cssContext(cm) {
    const cur = cm.getCursor();
    let txt = cm.getRange(CM.Pos(0, 0), cur);
    if (cm.getMode().name !== "css") { const i = txt.toLowerCase().lastIndexOf("<style"); if (i > -1) txt = txt.slice(txt.indexOf(">", i) + 1); }
    txt = txt.replace(/\/\*[\s\S]*?(\*\/|$)/g, "");
    let depth = 0; for (const ch of txt) { if (ch === "{") depth++; else if (ch === "}") depth = Math.max(0, depth - 1); }
    if (depth === 0) return { kind: "selector" };
    const stmt = txt.slice(Math.max(txt.lastIndexOf("{"), txt.lastIndexOf(";"), txt.lastIndexOf("}")) + 1);
    const c = stmt.indexOf(":");
    return c > -1 ? { kind: "value", prop: stmt.slice(0, c).trim().toLowerCase() } : { kind: "prop" };
  }
  function cssHint(cm, opts) {
    const ctx = cssContext(cm);
    if (ctx.kind === "selector") {
      const w = wordAt(cm, /[\w.#:@-]/);
      if (!w.word) return null;
      let list = [];
      const ci = w.word.indexOf(":");
      if (ci > -1) {                     // a:hover, li:nth-child(…), p::before
        const ps = w.word.slice(ci);
        PSEUDO.forEach((x) => { if (starts(x, ps)) list.push(item(x, "kw", "pseudo-class")); });
        return list.length ? { list, from: CM.Pos(w.from.line, w.from.ch + ci), to: w.to } : null;
      }
      else {
        const html = (opts && opts.html && opts.html()) || cm.getValue();
        const seen = {}; let m;
        const re = /\b(class|id)\s*=\s*["']([^"']+)["']/gi;
        while ((m = re.exec(html))) m[2].split(/\s+/).forEach((n) => { if (!n) return; const sel = (m[1].toLowerCase() === "id" ? "#" : ".") + n; if (!seen[sel]) { seen[sel] = 1; if (starts(sel, w.word) || starts(n, w.word)) list.push(item(sel, "prop", m[1].toLowerCase() === "id" ? "id in your HTML" : "class in your HTML")); } });
        if (!/^[.#]/.test(w.word)) {
          ["*", "body", "html"].concat(Object.keys(HTML_TAGS)).forEach((k) => { if (starts(k, w.word) && !seen[k]) { seen[k] = 1; list.push(item(k, "tag", "<" + k + "> " + (HTML_TAGS[k] || "selector"))); } });
          if (starts("@media", w.word) || w.word === "@") list.push(snipItem("@media", ["mobile screens", "@media (max-width: 600px) {\n  $0\n}"]));
        }
      }
      list = rank(list, w.word.replace(/^[.#]/, ""));
      return list.length ? { list, from: w.from, to: w.to } : null;
    }
    const w = wordAt(cm, /[\w#().,%-]/);
    if (ctx.kind === "value") {
      const own = CSS_VALUES[ctx.prop] || [];
      let base = [];
      const r = CM.hint.css && CM.hint.css(cm);
      if (r && r.list) base = r.list.map((x) => (typeof x === "string" ? x : x.text));
      let list = own.filter((v) => !w.word || starts(v, w.word)).map((v, i) => item(v, "val", ctx.prop, { _o: i }));
      if (/color|background|border/.test(ctx.prop)) COLORS.forEach((v) => { if ((!w.word || starts(v, w.word)) && own.indexOf(v) < 0) list.push(item(v, "val", "colour")); });
      base.forEach((v) => { if ((!w.word || starts(v, w.word)) && own.indexOf(v) < 0) list.push(item(v, "val", "value")); });
      const seen = {}; list = list.filter((x) => (seen[x.text] ? false : (seen[x.text] = 1)));
      // property-specific values keep their natural order and come first
      list.sort((a, b) => (a._o != null ? a._o : 999) - (b._o != null ? b._o : 999) || a.text.length - b.text.length);
      list = list.slice(0, 60);
      return list.length ? { list, from: w.from, to: w.to } : null;
    }
    // property name
    const pw = wordAt(cm, /[\w-]/);
    if (!pw.word) return null;
    const r = CM.hint.css && CM.hint.css(cm);
    const names = (r && r.list ? r.list.map((x) => (typeof x === "string" ? x : x.text)) : []).concat(Object.keys(CSS_VALUES));
    let list = [];
    const seen = {};
    names.forEach((t) => { if (seen[t] || !starts(t, pw.word)) return; seen[t] = 1; list.push(item(t, "prop", "property", { _common: CSS_VALUES[t] ? 1 : 0, hint: (c, d) => { c.replaceRange(t + ": ;", d.from, d.to, "complete"); const p = c.getCursor(); c.setCursor(CM.Pos(p.line, p.ch - 1)); setTimeout(() => c.showHint({ hint: copaHint, completeSingle: false }), 30); } })); });
    // common properties first, then shortest
    list.sort((a, b) => (b._common - a._common) || a.text.length - b.text.length);
    list = list.slice(0, 60);
    return list.length ? { list, from: pw.from, to: pw.to } : null;
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
  let OPTS = {};
  function copaHint(cm) {
    const mode = cm.getModeAt(cm.getCursor()).name;
    if (mode === "python") return pythonHint(cm);
    if (mode === "javascript") return jsHint(cm);
    if (mode === "css") return cssHint(cm, OPTS);
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
  function attach(cm, opts) {
    OPTS = opts || {};
    let t;
    cm.on("inputRead", (c, ch) => {
      if (ch.origin !== "+input" || c.state.completionActive) return;
      const typed = ch.text[ch.text.length - 1];
      const cssMode = c.getModeAt(c.getCursor()).name === "css";
      const cur = c.getCursor(), before = c.getLine(cur.line).slice(0, cur.ch);
      const cssValueStart = cssMode && /:\s?$/.test(before) && cssContext(c).kind === "value";   // "color:" or "color: " → show values
      if (!cssValueStart && !/[\w.<!:#@$-]$/.test(typed)) return;
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
