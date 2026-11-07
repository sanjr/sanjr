/* Builds js/hint-data.js – the complete suggestion data for the Live Practice editor.
   • HTML  : tools/hints/html-data.js (all HTML5 + old elements, attributes, values, entities)
   • CSS   : every property the browser supports + its valid keyword values (checked with CSS.supports)
   • JS    : every method/property read from the browser (String, Array, Math, document, elements …)
   Run:  node tools/hints/build-hint-data.js   (needs Playwright + Chromium; serves the repo on a local port) */
const path = require("path"), fs = require("fs"), http = require("http");
const { chromium } = require("playwright");
const ROOT = path.resolve(__dirname, "..", "..");
const H = require("./html-data.js");

const server = http.createServer((req, res) => {
  const f = path.join(ROOT, decodeURIComponent(req.url.split("?")[0]));
  fs.readFile(f, (e, d) => { if (e) { res.writeHead(404); return res.end(); } res.writeHead(200); res.end(d); });
}).listen(0, async () => {
  const port = server.address().port;
  const b = await chromium.launch();
  const p = await (await b.newContext()).newPage();
  await p.goto("http://localhost:" + port + "/live-practice.html");
  await p.waitForFunction(() => window.CodeMirror && CodeMirror.resolveMode);
  const data = await p.evaluate(() => {
    /* ---------------- CSS ---------------- */
    const cm = CodeMirror.resolveMode("text/css");
    const GLOBAL = ["inherit", "initial", "unset", "revert", "revert-layer"];
    const cand = new Set(Object.keys(cm.propertyKeywords).concat(Array.from(getComputedStyle(document.body))));
    const props = Array.from(cand).filter((pr) => !/^-(webkit|moz|ms|o)-/.test(pr) && CSS.supports(pr, "inherit")).sort();
    const colors = Object.keys(cm.colorKeywords).filter((c) => CSS.supports("color", c)).sort();
    const words = Object.keys(cm.valueKeywords).concat(["auto", "none", "normal", "flex", "grid", "inline-flex", "inline-grid", "contents", "flow-root", "space-evenly", "start", "end", "stretch", "fit-content", "max-content", "min-content", "sticky", "smooth", "balance", "pretty", "clip", "anywhere"])
      .filter((v, i, a) => a.indexOf(v) === i && GLOBAL.indexOf(v) < 0 && colors.indexOf(v) < 0 && /^[a-z][\w-]*$/.test(v));
    const V = [], vi = {};
    const cssProps = {};
    props.forEach((pr) => {
      let flags = "";
      if (CSS.supports(pr, "red")) flags += "c";
      if (CSS.supports(pr, "10px")) flags += "l";
      if (CSS.supports(pr, "50%")) flags += "p";
      if (CSS.supports(pr, "2") && !/c/.test(flags)) flags += "n";
      if (CSS.supports(pr, "1s")) flags += "t";
      if (CSS.supports(pr, "45deg")) flags += "a";
      if (CSS.supports(pr, 'url("a.png")')) flags += "u";
      const vals = [];
      // properties that accept ANY name (list-style-type, animation-name, font-family, grid-area …) would match every word → keep no keyword list for them
      const anyIdent = CSS.supports(pr, "zqxfoobar");
      if (anyIdent) flags += "i";
      else words.forEach((v) => { if (CSS.supports(pr, v)) { if (vi[v] == null) { vi[v] = V.length; V.push(v); } vals.push(vi[v]); } });
      cssProps[pr] = [flags, vals];
    });
    const pseudo = [":active", ":any-link", ":checked", ":default", ":defined", ":disabled", ":empty", ":enabled", ":first-child", ":first-of-type", ":focus", ":focus-visible", ":focus-within", ":fullscreen", ":has()", ":hover", ":in-range", ":indeterminate", ":invalid", ":is()", ":lang()", ":last-child", ":last-of-type", ":link", ":not()", ":nth-child()", ":nth-child(even)", ":nth-child(odd)", ":nth-last-child()", ":nth-of-type()", ":nth-last-of-type()", ":only-child", ":only-of-type", ":optional", ":out-of-range", ":placeholder-shown", ":read-only", ":read-write", ":required", ":root", ":target", ":valid", ":visited", ":where()",
      "::after", "::before", "::first-letter", "::first-line", "::marker", "::placeholder", "::selection", "::backdrop", "::file-selector-button"].filter((s) => { try { document.querySelector("a" + s.replace("()", "(a)").replace("(even)", "(2)").replace("(odd)", "(1)").replace(":lang(a)", ":lang(en)").replace(":nth-child(a)", ":nth-child(2)").replace(":nth-last-child(a)", ":nth-last-child(2)").replace(":nth-of-type(a)", ":nth-of-type(2)").replace(":nth-last-of-type(a)", ":nth-last-of-type(2)")); return true; } catch (e) { return /^::/.test(s); } });
    const atRules = [["@media", "@media (max-width: 600px) {\n  $0\n}"], ["@keyframes", "@keyframes name {\n  from { $0 }\n  to { }\n}"], ["@font-face", "@font-face {\n  font-family: \"MyFont\";\n  src: url(\"$0\");\n}"], ["@import", "@import url(\"$0\");"],
      ["@supports", "@supports (display: grid) {\n  $0\n}"], ["@page", "@page {\n  margin: $0;\n}"], ["@charset", "@charset \"UTF-8\";$0"], ["@layer", "@layer $0;"], ["@container", "@container (min-width: 400px) {\n  $0\n}"], ["@property", "@property --$0 {\n  syntax: \"<color>\";\n  inherits: false;\n  initial-value: red;\n}"]];
    const functions = ["rgb()", "rgba()", "hsl()", "hsla()", "hwb()", "url()", "calc()", "min()", "max()", "clamp()", "var()", "attr()", "linear-gradient()", "radial-gradient()", "conic-gradient()", "repeating-linear-gradient()",
      "translate()", "translateX()", "translateY()", "rotate()", "scale()", "scaleX()", "scaleY()", "skew()", "matrix()", "perspective()", "blur()", "brightness()", "contrast()", "grayscale()", "invert()", "opacity()", "saturate()", "sepia()", "drop-shadow()", "hue-rotate()",
      "repeat()", "minmax()", "fit-content()", "cubic-bezier()", "steps()", "counter()", "env()", "image-set()", "color-mix()"].filter((f) => { const n = f.replace("()", ""); const tests = { rgb: "rgb(0,0,0)", rgba: "rgba(0,0,0,1)", hsl: "hsl(0,0%,0%)", hsla: "hsla(0,0%,0%,1)", hwb: "hwb(0 0% 0%)", "color-mix": "color-mix(in srgb, red, blue)" }; return true || tests[n]; });
    const units = ["px", "%", "em", "rem", "vw", "vh", "vmin", "vmax", "pt", "cm", "mm", "in", "ch", "ex", "fr", "s", "ms", "deg", "rad", "turn", "dvh", "svh", "lvh"];

    /* ---------------- JavaScript ---------------- */
    const collect = (obj, stop) => {
      const out = Object.create(null);   // no inherited keys (toString, valueOf …)
      let o = obj, guard = 0;
      while (o && o !== stop && guard++ < 12) {
        Object.getOwnPropertyNames(o).forEach((n) => {
          if (n === "constructor" || /^__/.test(n) || /^\d/.test(n) || n in out) return;
          const d = Object.getOwnPropertyDescriptor(o, n);
          out[n] = d && typeof d.value === "function" && !d.get ? "m" : "p";
        });
        o = Object.getPrototypeOf(o);
      }
      return out;
    };
    const OP = Object.prototype;
    const els = ["input", "a", "img", "select", "form", "textarea", "button", "video", "table", "canvas", "option", "label", "iframe", "ol", "li"].map((t) => collect(document.createElement(t), OP));
    const element = Object.assign(Object.create(null), ...els.reverse());
    const camel = (s) => s.replace(/-([a-z])/g, (m, c) => c.toUpperCase());
    const style = Object.create(null); props.forEach((pr) => (style[camel(pr)] = "p")); ["setProperty", "getPropertyValue", "removeProperty"].forEach((m) => (style[m] = "m")); style.cssText = "p";
    const ev = Object.assign(Object.create(null), collect(new KeyboardEvent("keydown"), OP), collect(new MouseEvent("click"), OP), collect(new InputEvent("input"), OP));
    const types = {
      string: collect(String.prototype, OP), array: collect([], OP), number: collect(Number.prototype, OP), boolean: collect(Boolean.prototype, OP), date: collect(new Date(), OP), regexp: collect(/a/, OP),
      map: collect(new Map(), OP), set: collect(new Set(), OP), promise: collect(Promise.resolve(), OP), function: collect(function () {}, OP), object: collect({}, null),
      element, style, classList: collect(document.body.classList, OP), nodelist: Object.assign(Object.create(null), collect(document.querySelectorAll("a"), OP), collect(document.forms, OP)),
      event: ev, storage: collect(localStorage, OP), canvas2d: collect(document.createElement("canvas").getContext("2d"), OP)
    };
    delete types.string.length; types.string.length = "p"; types.array.length = "p";
    const statics = {
      Math: collect(Math, OP), JSON: collect(JSON, OP), console: collect(console, OP), Object: collect(Object, Function.prototype), Array: collect(Array, Function.prototype), Number: collect(Number, Function.prototype), String: collect(String, Function.prototype),
      Promise: collect(Promise, Function.prototype), Date: collect(Date, Function.prototype), Reflect: collect(Reflect, OP), Intl: collect(Intl, OP), Symbol: collect(Symbol, Function.prototype),
      document: collect(document, OP), navigator: collect(navigator, OP), location: collect(location, OP), history: collect(history, OP), screen: collect(screen, OP), localStorage: types.storage, sessionStorage: types.storage,
      performance: collect(performance, OP), crypto: collect(crypto, OP)
    };
    ["prototype", "length", "name", "caller", "arguments"].forEach((n) => Object.keys(statics).forEach((k) => { if (/^[A-Z]/.test(k) && k !== "JSON" && k !== "Math" && k !== "Reflect" && k !== "Intl") delete statics[k][n]; }));
    const winAll = collect(window, OP);
    const ES = ["Array", "ArrayBuffer", "BigInt", "Boolean", "DataView", "Date", "Error", "EvalError", "Float32Array", "Float64Array", "Function", "Infinity", "Int8Array", "Int16Array", "Int32Array", "Intl", "JSON", "Map", "Math", "NaN", "Number", "Object", "Promise", "Proxy", "RangeError", "ReferenceError", "Reflect", "RegExp", "Set", "String", "Symbol", "SyntaxError", "TypeError", "URIError", "Uint8Array", "Uint16Array", "Uint32Array", "WeakMap", "WeakSet", "WeakRef", "globalThis", "undefined",
      "decodeURI", "decodeURIComponent", "encodeURI", "encodeURIComponent", "escape", "unescape", "eval", "isFinite", "isNaN", "parseFloat", "parseInt"];
    const WEB = ["Image", "Audio", "Option", "XMLHttpRequest", "FormData", "URL", "URLSearchParams", "Blob", "File", "FileReader", "Event", "CustomEvent", "KeyboardEvent", "MouseEvent", "Notification", "WebSocket", "Worker", "AbortController", "Headers", "Request", "Response", "TextEncoder", "TextDecoder", "MutationObserver", "IntersectionObserver", "ResizeObserver", "DOMParser", "Node", "Element", "HTMLElement", "Document", "Window", "EventTarget", "NodeList", "HTMLCollection", "DocumentFragment", "Range", "Selection", "BroadcastChannel", "MessageChannel", "Geolocation"];
    const globals = Object.create(null);
    ES.concat(WEB).forEach((n) => { if (n in window) globals[n] = typeof window[n] === "function" ? (/^[A-Z]/.test(n) ? "c" : "f") : "o"; });
    Object.keys(winAll).forEach((n) => { if (/^on/.test(n) || /^[A-Z]/.test(n) || /^webkit/i.test(n) || globals[n]) return; globals[n] = winAll[n] === "m" ? "f" : "o"; });
    ["COPA", "COPA_DB", "COPA_CONFIG", "COPA_ADS", "COPA_HINTS", "COPA_PRACTICALS", "COPA_QUESTIONS", "CodeMirror", "__copa_input", "loadPyodide"].forEach((n) => delete globals[n]);
    const windowMembers = Object.create(null); Object.keys(winAll).forEach((n) => { if (!/^[A-Z]/.test(n) && !/^webkit/i.test(n) && !/^(COPA|__copa|CodeMirror|loadPyodide)/.test(n)) windowMembers[n] = winAll[n]; });
    statics.window = windowMembers;
    const keywords = ["await", "break", "case", "catch", "class", "const", "continue", "debugger", "default", "delete", "do", "else", "export", "extends", "false", "finally", "for", "function", "if", "import", "in", "instanceof", "let", "new", "null", "of", "return", "static", "super", "switch", "this", "throw", "true", "try", "typeof", "var", "void", "while", "with", "yield", "async", "get", "set", "arguments"];
    return { css: { props: cssProps, V, colors, pseudo, atRules, functions, units }, js: { types, statics, globals, keywords } };
  });
  await b.close(); server.close();

  // pack JS member maps as "name|name()" strings to keep the file small
  const pack = (o) => Object.keys(o).sort().map((n) => (o[n] === "m" ? n + "()" : n));
  const js = { keywords: data.js.keywords, globals: data.js.globals, types: {}, statics: {} };
  Object.keys(data.js.types).forEach((k) => (js.types[k] = pack(data.js.types[k])));
  Object.keys(data.js.statics).forEach((k) => (js.statics[k] = pack(data.js.statics[k])));
  const html = { elements: H.ELEMENTS, void: H.VOID, global: H.GLOBAL_ATTRS, events: H.EVENT_ATTRS, bool: H.BOOLEAN_ATTRS, values: H.ATTR_VALUES, entities: H.ENTITIES };
  const out = "/* COPA Live Practice – complete code-suggestion data (generated by tools/hints/build-hint-data.js – do not edit by hand) */\nwindow.COPA_HINT_DATA = " +
    JSON.stringify({ html, css: data.css, js }) + ";\n";
  fs.writeFileSync(path.join(ROOT, "js", "hint-data.js"), out);
  const n = (o) => Object.keys(o).length;
  console.log("HTML elements:", n(H.ELEMENTS), "| CSS properties:", n(data.css.props), "values:", data.css.V.length, "colours:", data.css.colors.length, "pseudo:", data.css.pseudo.length,
    "| JS globals:", n(js.globals), "string:", js.types.string.length, "array:", js.types.array.length, "element:", js.types.element.length, "document:", js.statics.document.length, "window:", js.statics.window.length,
    "| size:", Math.round(out.length / 1024) + " KB");
});
