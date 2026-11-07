/* Builds js/hint-data-py.js – complete Python suggestion data, read from the same Python
   engine the website uses (Pyodide / CPython 3.12 in the browser).
   Run:  node tools/hints/build-py-hint-data.js   (needs Playwright + Chromium) */
const path = require("path"), fs = require("fs"), http = require("http");
const { chromium } = require("playwright");
const ROOT = path.resolve(__dirname, "..", "..");

const PY = String.raw`
import sys, builtins, keyword, inspect, importlib, json, types, io

SKIP = {"antigravity", "this", "idlelib", "tkinter", "turtledemo", "__main__", "__phello__", "pydoc_data", "lib2to3", "ensurepip", "venv",
        "test", "unittest.mock", "pyodide", "micropip", "_pyodide", "js", "pyodide_js", "msvcrt", "winreg", "winsound", "nt", "_winapi"}

def short_doc(o):
    d = inspect.getdoc(o) or ""
    d = d.strip().split("\n")[0][:90]
    return d

def sig(name, o):
    try:
        return name + str(inspect.signature(o))[:80]
    except Exception:
        t = getattr(o, "__text_signature__", None)
        if t:
            return name + t.replace("$self, ", "").replace("$module, ", "").replace("($self)", "()").replace("($module)", "()")[:80]
        return ""

def kind(o):
    if inspect.isclass(o):
        return "c"
    if callable(o):
        return "f"
    if isinstance(o, types.ModuleType):
        return "m"
    return "v"

def members(obj, limit=400):
    out = []
    for n in sorted(dir(obj)):
        if n.startswith("_"):
            continue
        try:
            o = getattr(obj, n)
        except Exception:
            continue
        k = kind(o)
        out.append([n, k, sig(n, o) if k in ("f", "c") else type(o).__name__])
        if len(out) >= limit:
            break
    return out

data = {}
# keywords
data["keywords"] = list(keyword.kwlist)
data["softKeywords"] = list(getattr(keyword, "softkwlist", []))
# builtins
bi = []
for n in sorted(dir(builtins)):
    if n.startswith("_"):
        continue
    o = getattr(builtins, n)
    k = "e" if inspect.isclass(o) and issubclass(o, BaseException) else kind(o)
    bi.append([n, k, sig(n, o), short_doc(o) if k in ("f", "c") else ""])
data["builtins"] = bi
# built-in types
TYPES = {"str": "", "list": [], "dict": {}, "set": set(), "frozenset": frozenset(), "tuple": (), "int": 0, "float": 0.0, "complex": 0j,
         "bytes": b"", "bytearray": bytearray(), "range": range(1), "bool": True, "file": io.StringIO()}
data["types"] = {k: [[n, kk, s] for (n, kk, s) in members(v) ] for k, v in TYPES.items()}
# standard library modules that import in the browser
mods = {}
names = sorted(set(sys.stdlib_module_names) | {"os.path", "collections.abc", "urllib.parse", "urllib.request", "json.decoder", "email.message", "concurrent.futures", "xml.etree.ElementTree", "html.parser", "http.client", "importlib.metadata", "logging.handlers", "unittest"})
failed = []
for name in names:
    if name in SKIP or name.startswith("_") or any(name.startswith(s + ".") for s in SKIP):
        continue
    try:
        m = importlib.import_module(name)
    except BaseException as e:
        failed.append(name)
        continue
    mods[name] = [short_doc(m), members(m)]
data["modules"] = mods
# members of commonly used classes (for  d = datetime.date.today();  d.  …)
CLASSES = {"datetime.date": "datetime", "datetime.datetime": "datetime", "datetime.timedelta": "datetime", "datetime.time": "datetime",
           "collections.Counter": "collections", "collections.deque": "collections", "collections.defaultdict": "collections", "collections.OrderedDict": "collections",
           "random.Random": "random", "pathlib.Path": "pathlib", "decimal.Decimal": "decimal", "fractions.Fraction": "fractions", "io.StringIO": "io",
           "string.Template": "string", "re.Match": "re", "re.Pattern": "re", "array.array": "array", "csv.DictReader": "csv"}
cls = {}
for full, modname in CLASSES.items():
    try:
        m = importlib.import_module(modname)
        c = getattr(m, full.split(".")[-1])
        cls[full] = members(c)
    except Exception:
        pass
data["classes"] = cls
data["failed"] = failed
data["version"] = sys.version.split()[0]
json.dumps(data)
`;

const server = http.createServer((req, res) => {
  const f = path.join(ROOT, decodeURIComponent(req.url.split("?")[0]));
  fs.readFile(f, (e, d) => {
    if (e) { res.writeHead(404); return res.end(); }
    res.writeHead(200, { "Content-Type": f.endsWith(".wasm") ? "application/wasm" : f.endsWith(".js") ? "text/javascript" : f.endsWith(".html") ? "text/html" : f.endsWith(".css") ? "text/css" : f.endsWith(".json") ? "application/json" : "application/octet-stream" });
    res.end(d);
  });
}).listen(0, async () => {
  const port = server.address().port;
  const b = await chromium.launch();
  const p = await (await b.newContext()).newPage();
  await p.goto("http://localhost:" + port + "/index.html");
  await p.addScriptTag({ url: "/assets/vendor/pyodide/pyodide.js" });
  const raw = await p.evaluate(async (code) => {
    const py = await window.loadPyodide({ indexURL: location.origin + "/assets/vendor/pyodide/" });
    py.setStdout({ batched: () => {} }); py.setStderr({ batched: () => {} });
    return py.runPython(code);
  }, PY);
  await b.close(); server.close();
  const d = JSON.parse(raw);
  // compact: module members → "name|k|signature"
  const pack = (arr) => arr.map((x) => x[0] + "|" + x[1] + (x[2] ? "|" + x[2] : ""));
  const out = { version: d.version, keywords: d.keywords, softKeywords: d.softKeywords,
    builtins: d.builtins.map((x) => [x[0], x[1], x[2], x[3]]), types: {}, modules: {} };
  Object.keys(d.types).forEach((k) => (out.types[k] = pack(d.types[k])));
  Object.keys(d.modules).forEach((k) => (out.modules[k] = [d.modules[k][0], pack(d.modules[k][1])]));
  out.classes = {}; Object.keys(d.classes).forEach((k) => (out.classes[k] = pack(d.classes[k])));
  const txt = "/* COPA Live Practice – complete Python suggestion data (Python " + d.version + " / Pyodide).\n   Generated by tools/hints/build-py-hint-data.js – do not edit by hand. Loaded only when Python is used. */\nwindow.COPA_PY_HINT_DATA = " + JSON.stringify(out) + ";\n";
  fs.writeFileSync(path.join(ROOT, "js", "hint-data-py.js"), txt);
  console.log("Python", d.version, "| builtins:", d.builtins.length, "| keywords:", d.keywords.length, "+", d.softKeywords.length, "soft | types:", Object.keys(d.types).map((k) => k + ":" + d.types[k].length).join(" "),
    "| modules:", Object.keys(d.modules).length, "(not available in browser:", d.failed.length + ")", "| size:", Math.round(txt.length / 1024) + " KB");
  console.log("not importable:", d.failed.join(", "));
});
