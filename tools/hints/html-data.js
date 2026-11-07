/* Complete HTML element / attribute data used by tools/hints/build-hint-data.js
   [description, element-specific attributes] */
module.exports.ELEMENTS = {
  // document & metadata
  html: ["Root of the page", ["lang", "dir", "manifest"]], head: ["Page information (not shown)", []], title: ["Title in the browser tab", []],
  base: ["Base URL for links", ["href", "target"]], link: ["Link a CSS file / icon", ["rel", "href", "type", "media", "sizes", "crossorigin", "integrity", "as", "hreflang"]],
  meta: ["Page metadata", ["name", "content", "charset", "http-equiv"]], style: ["CSS inside HTML", ["media", "type"]],
  body: ["Visible content of the page", ["bgcolor", "background", "text", "link", "vlink", "alink", "onload", "onunload", "onresize"]],
  // sections
  article: ["Independent article", []], section: ["Section of a page", []], nav: ["Navigation links", []], aside: ["Side content", []],
  h1: ["Heading 1 (largest)", ["align"]], h2: ["Heading 2", ["align"]], h3: ["Heading 3", ["align"]], h4: ["Heading 4", ["align"]], h5: ["Heading 5", ["align"]], h6: ["Heading 6 (smallest)", ["align"]],
  hgroup: ["Group of headings", []], header: ["Header of page/section", []], footer: ["Footer of page/section", []], address: ["Contact information", []], main: ["Main content", []], search: ["Search section", []],
  // grouping
  p: ["Paragraph", ["align"]], hr: ["Horizontal line", ["align", "size", "width", "noshade", "color"]], pre: ["Preformatted text", ["width"]], blockquote: ["Long quotation", ["cite"]],
  ol: ["Ordered (numbered) list", ["type", "start", "reversed"]], ul: ["Unordered (bullet) list", ["type"]], menu: ["Toolbar / menu list", []], li: ["List item", ["value", "type"]],
  dl: ["Description list", []], dt: ["Description term", []], dd: ["Description details", []], figure: ["Figure with caption", []], figcaption: ["Figure caption", []], div: ["Block container (division)", ["align"]],
  // text-level
  a: ["Hyperlink (anchor)", ["href", "target", "download", "rel", "hreflang", "type", "name", "ping", "referrerpolicy"]], em: ["Emphasis (italic)", []], strong: ["Important text (bold)", []],
  small: ["Small text", []], s: ["Strikethrough (no longer correct)", []], cite: ["Title of a work", []], q: ["Short quotation", ["cite"]], dfn: ["Definition term", []], abbr: ["Abbreviation", ["title"]],
  ruby: ["Ruby annotation", []], rt: ["Ruby text", []], rp: ["Ruby fallback parenthesis", []], data: ["Machine-readable value", ["value"]], time: ["Date / time", ["datetime"]],
  code: ["Computer code", []], var: ["Variable", []], samp: ["Sample output", []], kbd: ["Keyboard input", []], sub: ["Subscript (H₂O)", []], sup: ["Superscript / power (x²)", []],
  i: ["Italic text", []], b: ["Bold text", []], u: ["Underline", []], mark: ["Highlighted text", []], bdi: ["Bi-directional isolate", []], bdo: ["Text direction override", ["dir"]],
  span: ["Inline container", []], br: ["Line break", ["clear"]], wbr: ["Possible line break", []],
  // edits
  ins: ["Inserted text", ["cite", "datetime"]], del: ["Deleted text", ["cite", "datetime"]],
  // embedded
  picture: ["Responsive image container", []], source: ["Media source", ["src", "srcset", "sizes", "type", "media", "width", "height"]],
  img: ["Image", ["src", "alt", "width", "height", "srcset", "sizes", "loading", "decoding", "usemap", "ismap", "border", "align", "hspace", "vspace", "crossorigin", "referrerpolicy"]],
  iframe: ["Embed another web page", ["src", "srcdoc", "name", "width", "height", "allow", "allowfullscreen", "loading", "sandbox", "frameborder", "scrolling", "referrerpolicy"]],
  embed: ["Embed external content", ["src", "type", "width", "height"]], object: ["External object", ["data", "type", "name", "width", "height", "form", "usemap"]], param: ["Object parameter", ["name", "value"]],
  video: ["Video player", ["src", "controls", "autoplay", "loop", "muted", "poster", "width", "height", "preload", "playsinline", "crossorigin"]],
  audio: ["Sound player", ["src", "controls", "autoplay", "loop", "muted", "preload", "crossorigin"]], track: ["Subtitles for video", ["src", "kind", "srclang", "label", "default"]],
  map: ["Image map", ["name"]], area: ["Clickable area in image map", ["shape", "coords", "href", "alt", "target", "download", "rel"]],
  svg: ["Vector graphics (SVG)", ["width", "height", "viewBox", "xmlns", "fill"]], math: ["Maths formula (MathML)", []], canvas: ["Drawing area (JavaScript)", ["width", "height"]], noscript: ["Shown if JS is off", []], script: ["JavaScript code", ["src", "type", "async", "defer", "crossorigin", "integrity", "nomodule", "referrerpolicy"]],
  template: ["Hidden HTML template", []], slot: ["Web component slot", ["name"]],
  // tables
  table: ["Table", ["border", "cellpadding", "cellspacing", "width", "align", "bgcolor", "summary", "frame", "rules"]], caption: ["Table caption", ["align"]], colgroup: ["Group of columns", ["span"]], col: ["Table column", ["span", "width"]],
  tbody: ["Table body", []], thead: ["Table header rows", []], tfoot: ["Table footer rows", []], tr: ["Table row", ["align", "valign", "bgcolor"]],
  td: ["Table data cell", ["colspan", "rowspan", "headers", "align", "valign", "width", "height", "bgcolor", "nowrap"]], th: ["Table heading cell", ["colspan", "rowspan", "headers", "scope", "abbr", "align", "valign", "width", "bgcolor"]],
  // forms
  form: ["Form", ["action", "method", "enctype", "target", "name", "autocomplete", "novalidate", "accept-charset", "rel"]], label: ["Label for an input", ["for", "form"]],
  input: ["Input box / control", ["type", "name", "value", "placeholder", "required", "readonly", "disabled", "checked", "maxlength", "minlength", "min", "max", "step", "size", "pattern", "autofocus", "autocomplete", "multiple", "accept", "list", "form", "src", "alt", "width", "height", "capture", "dirname", "formaction", "formmethod", "formtarget", "inputmode"]],
  button: ["Button", ["type", "name", "value", "disabled", "form", "formaction", "formmethod", "formtarget", "formnovalidate", "popovertarget", "autofocus"]],
  select: ["Drop-down (combo box)", ["name", "multiple", "size", "required", "disabled", "form", "autofocus", "autocomplete"]], datalist: ["Suggestions for an input", []],
  optgroup: ["Group of options", ["label", "disabled"]], option: ["Drop-down item", ["value", "selected", "disabled", "label"]],
  textarea: ["Multi-line text box", ["name", "rows", "cols", "placeholder", "maxlength", "minlength", "required", "readonly", "disabled", "wrap", "autofocus", "form", "spellcheck"]],
  output: ["Result of a calculation", ["for", "name", "form"]], progress: ["Progress bar", ["value", "max"]], meter: ["Gauge / measurement", ["value", "min", "max", "low", "high", "optimum"]],
  fieldset: ["Group of form controls", ["disabled", "name", "form"]], legend: ["Fieldset title", []],
  // interactive
  details: ["Show / hide details", ["open", "name"]], summary: ["Details heading", []], dialog: ["Dialog / pop-up box", ["open"]],
  // old / deprecated (still asked in ITI exams)
  marquee: ["Scrolling text (old)", ["behavior", "direction", "scrollamount", "scrolldelay", "loop", "bgcolor", "width", "height"]], center: ["Centre text (old – use CSS)", []],
  font: ["Font face/size/colour (old – use CSS)", ["face", "size", "color"]], big: ["Bigger text (old)", []], tt: ["Teletype text (old)", []], strike: ["Strikethrough (old)", []],
  frameset: ["Frameset (old)", ["rows", "cols"]], frame: ["Frame (old)", ["src", "name", "noresize", "scrolling"]], noframes: ["No-frames content (old)", []], acronym: ["Acronym (old – use abbr)", ["title"]],
  basefont: ["Default font (old)", ["face", "size", "color"]], applet: ["Java applet (old)", ["code", "width", "height"]], dir: ["Directory list (old)", []], bgsound: ["Background sound (old IE)", ["src", "loop"]],
  nobr: ["No line break (old)", []], blink: ["Blinking text (old)", []]
};
module.exports.VOID = ["area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr", "basefont", "frame", "bgsound"];
module.exports.GLOBAL_ATTRS = ["id", "class", "style", "title", "lang", "dir", "hidden", "tabindex", "accesskey", "contenteditable", "draggable", "spellcheck", "translate", "inputmode", "enterkeyhint", "autocapitalize", "autofocus", "nonce", "popover", "role", "is", "itemscope", "itemprop", "part", "slot", "inert",
  "aria-label", "aria-hidden", "aria-describedby", "aria-labelledby", "aria-expanded", "aria-live", "data-"];
module.exports.EVENT_ATTRS = ["onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover", "onmouseout", "onmousemove", "onmouseenter", "onmouseleave", "oncontextmenu", "onwheel",
  "onkeydown", "onkeyup", "onkeypress", "onfocus", "onblur", "onchange", "oninput", "onsubmit", "onreset", "onselect", "oninvalid", "onsearch",
  "onload", "onunload", "onbeforeunload", "onerror", "onresize", "onscroll", "onhashchange", "onpageshow", "onpagehide", "onafterprint", "onbeforeprint", "onoffline", "ononline",
  "ondrag", "ondragstart", "ondragend", "ondragover", "ondragenter", "ondragleave", "ondrop", "oncopy", "oncut", "onpaste",
  "onplay", "onpause", "onended", "onvolumechange", "ontimeupdate", "oncanplay", "onloadeddata", "onseeked", "ontoggle", "ontouchstart", "ontouchend", "ontouchmove", "onanimationend", "ontransitionend"];
module.exports.BOOLEAN_ATTRS = ["required", "readonly", "disabled", "checked", "selected", "multiple", "autofocus", "autoplay", "controls", "loop", "muted", "defer", "async", "hidden", "novalidate", "formnovalidate", "open", "reversed", "allowfullscreen", "ismap", "default", "playsinline", "nomodule", "noshade", "nowrap", "noresize", "inert", "itemscope"];
module.exports.ATTR_VALUES = {
  type: { input: ["text", "password", "email", "number", "tel", "url", "search", "date", "time", "datetime-local", "month", "week", "color", "range", "file", "checkbox", "radio", "submit", "reset", "button", "image", "hidden"],
    button: ["button", "submit", "reset"], ol: ["1", "a", "A", "i", "I"], ul: ["disc", "circle", "square"], li: ["1", "a", "A", "i", "I", "disc", "circle", "square"], script: ["text/javascript", "module"], style: ["text/css"], link: ["text/css", "image/png", "image/x-icon"], source: ["video/mp4", "video/webm", "audio/mpeg", "audio/ogg", "image/webp"] },
  target: ["_blank", "_self", "_parent", "_top"], method: ["get", "post", "dialog"], enctype: ["application/x-www-form-urlencoded", "multipart/form-data", "text/plain"],
  rel: ["stylesheet", "icon", "noopener", "noreferrer", "nofollow", "preload", "prefetch", "alternate", "author", "canonical", "manifest", "next", "prev"],
  dir: ["ltr", "rtl", "auto"], align: ["left", "center", "right", "justify"], valign: ["top", "middle", "bottom", "baseline"], scope: ["col", "row", "colgroup", "rowgroup"],
  loading: ["lazy", "eager"], decoding: ["async", "sync", "auto"], preload: ["none", "metadata", "auto"], crossorigin: ["anonymous", "use-credentials"], autocomplete: ["on", "off", "name", "email", "username", "new-password", "current-password", "tel", "address-line1"],
  wrap: ["soft", "hard"], shape: ["rect", "circle", "poly", "default"], kind: ["subtitles", "captions", "descriptions", "chapters", "metadata"], contenteditable: ["true", "false"], draggable: ["true", "false"], spellcheck: ["true", "false"], translate: ["yes", "no"],
  behavior: ["scroll", "slide", "alternate"], direction: ["left", "right", "up", "down"], frameborder: ["0", "1"], scrolling: ["yes", "no", "auto"], inputmode: ["none", "text", "numeric", "decimal", "tel", "email", "url", "search"],
  "http-equiv": ["content-type", "refresh", "X-UA-Compatible", "default-style"], name: { meta: ["viewport", "description", "keywords", "author", "robots", "theme-color", "generator"] },
  content: { meta: ["width=device-width, initial-scale=1.0", "index, follow", "text/html; charset=UTF-8"] }, charset: ["UTF-8"], lang: ["en", "hi", "mr"], popover: ["auto", "manual"], sandbox: ["allow-scripts", "allow-forms", "allow-popups", "allow-same-origin", "allow-modals"],
  referrerpolicy: ["no-referrer", "origin", "strict-origin-when-cross-origin", "unsafe-url"], enterkeyhint: ["enter", "done", "go", "next", "search", "send"], autocapitalize: ["off", "on", "sentences", "words", "characters"]
};
module.exports.ENTITIES = [["&nbsp;", "non-breaking space"], ["&lt;", "< less than"], ["&gt;", "> greater than"], ["&amp;", "& ampersand"], ["&quot;", "\" quote"], ["&apos;", "' apostrophe"], ["&copy;", "© copyright"], ["&reg;", "® registered"], ["&trade;", "™ trademark"],
  ["&rupee;", "₹ (use &#8377;)"], ["&#8377;", "₹ rupee"], ["&euro;", "€ euro"], ["&pound;", "£ pound"], ["&yen;", "¥ yen"], ["&cent;", "¢ cent"], ["&deg;", "° degree"], ["&plusmn;", "± plus-minus"], ["&times;", "× multiply"], ["&divide;", "÷ divide"],
  ["&frac12;", "½ half"], ["&frac14;", "¼ quarter"], ["&frac34;", "¾ three quarters"], ["&sup2;", "² squared"], ["&sup3;", "³ cubed"], ["&para;", "¶ paragraph"], ["&sect;", "§ section"], ["&hellip;", "… ellipsis"], ["&mdash;", "— dash"], ["&ndash;", "– dash"],
  ["&larr;", "← left arrow"], ["&rarr;", "→ right arrow"], ["&uarr;", "↑ up arrow"], ["&darr;", "↓ down arrow"], ["&harr;", "↔ left-right"], ["&hearts;", "♥ heart"], ["&spades;", "♠ spade"], ["&clubs;", "♣ club"], ["&diams;", "♦ diamond"],
  ["&check;", "✓ check"], ["&star;", "☆ star"], ["&infin;", "∞ infinity"], ["&ne;", "≠ not equal"], ["&le;", "≤ less or equal"], ["&ge;", "≥ greater or equal"], ["&pi;", "π pi"], ["&alpha;", "α alpha"], ["&beta;", "β beta"], ["&Omega;", "Ω omega"], ["&bull;", "• bullet"], ["&middot;", "· middle dot"], ["&laquo;", "« quotes"], ["&raquo;", "» quotes"], ["&emsp;", "wide space"], ["&ensp;", "medium space"]];
