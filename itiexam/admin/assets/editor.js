/* ITIExam admin – simple visual editor for <textarea data-editor>.
   No external library: works offline on any hosting. "HTML" button shows the raw code. */
(function () {
  "use strict";

  var ICON = {
    bold: "<b>B</b>", italic: "<i>I</i>", underline: "<u>U</u>",
    ul: "• List", ol: "1. List", link: "🔗 Link", unlink: "Unlink", image: "🖼 Photo",
    table: "▦ Table", quote: "❝ Quote", hr: "— Line", clear: "Clear format",
    undo: "↶", redo: "↷", source: "&lt;/&gt; HTML", file: "📎 PDF/File"
  };

  function el(tag, attrs, html) {
    var n = document.createElement(tag);
    for (var k in attrs || {}) n.setAttribute(k, attrs[k]);
    if (html != null) n.innerHTML = html;
    return n;
  }

  function csrfOf(form) {
    var i = form && form.querySelector('input[name="csrf"]');
    return i ? i.value : "";
  }

  function escapeHtml(t) {
    return t.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  }

  /* ---------- image / file picker (uses the Media Library) ---------- */
  function openPicker(form, onPick, wantFiles) {
    var shade = el("div", { "class": "ed-shade" });
    var box = el("div", { "class": "ed-modal", role: "dialog", "aria-label": "Choose a photo" });
    box.appendChild(el("div", { "class": "ed-mhead" },
      "<b>" + (wantFiles ? "Upload a PDF or file" : "Choose a photo") + "</b><button type=\"button\" class=\"ed-x\" aria-label=\"Close\">✕</button>"));
    var up = el("label", { "class": "ed-up" },
      "⬆ Upload new " + (wantFiles ? "PDF / image" : "photo") + " <input type=\"file\" accept=\"" + (wantFiles ? "application/pdf,image/*" : "image/*") + "\" hidden>");
    var msg = el("p", { "class": "ed-msg" });
    var grid = el("div", { "class": "ed-grid" }, wantFiles ? "" : "<p class=\"ed-msg\">Loading photos…</p>");
    box.appendChild(up); box.appendChild(msg); box.appendChild(grid);
    shade.appendChild(box);
    document.body.appendChild(shade);

    function close() { shade.remove(); }
    shade.addEventListener("click", function (e) { if (e.target === shade) close(); });
    box.querySelector(".ed-x").addEventListener("click", close);

    if (!wantFiles) {
      fetch("/admin/media.php?json=1", { credentials: "same-origin" }).then(function (r) { return r.json(); }).then(function (list) {
        grid.innerHTML = list.length ? "" : "<p class=\"ed-msg\">No photos yet – upload one above.</p>";
        list.forEach(function (f) {
          var b = el("button", { type: "button", "class": "ed-pick", title: f.name }, "<img src=\"" + f.url + "\" alt=\"\" loading=\"lazy\">");
          b.addEventListener("click", function () { close(); onPick(f.url, f.name); });
          grid.appendChild(b);
        });
      }).catch(function () { grid.innerHTML = "<p class=\"ed-msg\">Could not load the Media Library.</p>"; });
    }

    up.querySelector("input").addEventListener("change", function () {
      var file = this.files[0];
      if (!file) return;
      msg.textContent = "Uploading " + file.name + "…";
      var fd = new FormData();
      fd.append("csrf", csrfOf(form)); fd.append("ajax", "1"); fd.append("files[]", file);
      fetch("/admin/media.php", { method: "POST", body: fd, credentials: "same-origin" }).then(function (r) { return r.json(); }).then(function (res) {
        if (res.url) { close(); onPick(res.url, file.name); } else { msg.textContent = res.error || "Upload failed."; }
      }).catch(function () { msg.textContent = "Upload failed. Check your internet and try again."; });
    });
  }

  /* ---------- editor ---------- */
  function makeEditor(ta) {
    var form = ta.form;
    var wrap = el("div", { "class": "ed" });
    var bar = el("div", { "class": "ed-bar", role: "toolbar", "aria-label": "Text formatting" });
    var area = el("div", { "class": "ed-area", contenteditable: "true", role: "textbox", "aria-multiline": "true" });
    area.innerHTML = ta.value;
    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar); wrap.appendChild(area); wrap.appendChild(ta);
    ta.classList.add("ed-src");
    ta.hidden = true;
    var sourceMode = false;

    function sync() { if (!sourceMode) ta.value = area.innerHTML; }
    function exec(cmd, val) { area.focus(); document.execCommand(cmd, false, val || null); sync(); }
    var savedRange = null;
    function saveSel() {
      var s = window.getSelection();
      if (s.rangeCount && area.contains(s.anchorNode)) savedRange = s.getRangeAt(0).cloneRange();
    }
    function restoreSel() {
      area.focus();
      if (savedRange) { var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
    }
    function insertHtml(html) { restoreSel(); document.execCommand("insertHTML", false, html); sync(); }

    var block = el("select", { "class": "ed-block", "aria-label": "Text style" },
      "<option value=\"p\">Normal text</option><option value=\"h2\">Heading</option><option value=\"h3\">Sub-heading</option>");
    block.addEventListener("change", function () { exec("formatBlock", "<" + block.value + ">"); block.value = "p"; });
    bar.appendChild(block);

    var buttons = [
      ["bold", function () { exec("bold"); }], ["italic", function () { exec("italic"); }], ["underline", function () { exec("underline"); }],
      ["ul", function () { exec("insertUnorderedList"); }], ["ol", function () { exec("insertOrderedList"); }],
      ["link", function () {
        saveSel();
        var url = prompt("Link address (https://… or /page/):", "https://");
        if (!url || url === "https://") return;
        restoreSel();
        if (window.getSelection().isCollapsed) insertHtml("<a href=\"" + escapeHtml(url) + "\">" + escapeHtml(url) + "</a>");
        else exec("createLink", url);
      }],
      ["unlink", function () { exec("unlink"); }],
      ["image", function () {
        saveSel();
        openPicker(form, function (url, name) {
          var alt = prompt("Describe the photo in a few words (helps Google):", name.replace(/\.[a-z]+$/i, "")) || "";
          insertHtml("<p><img src=\"" + url + "\" alt=\"" + escapeHtml(alt) + "\" loading=\"lazy\"></p>");
        }, false);
      }],
      ["file", function () {
        saveSel();
        openPicker(form, function (url, name) {
          var label = prompt("Link text:", "Download " + name) || name;
          insertHtml("<a href=\"" + url + "\" target=\"_blank\">" + escapeHtml(label) + "</a>");
        }, true);
      }],
      ["table", function () {
        saveSel();
        var rows = parseInt(prompt("How many rows?", "3"), 10) || 0, cols = parseInt(prompt("How many columns?", "2"), 10) || 0;
        if (rows < 1 || cols < 1) return;
        var h = "<table><thead><tr>";
        for (var c = 0; c < cols; c++) h += "<th>Heading</th>";
        h += "</tr></thead><tbody>";
        for (var r = 0; r < rows; r++) { h += "<tr>"; for (c = 0; c < cols; c++) h += "<td>&nbsp;</td>"; h += "</tr>"; }
        insertHtml(h + "</tbody></table><p><br></p>");
      }],
      ["quote", function () { exec("formatBlock", "<blockquote>"); }],
      ["hr", function () { exec("insertHorizontalRule"); }],
      ["clear", function () { exec("removeFormat"); exec("formatBlock", "<p>"); }],
      ["undo", function () { exec("undo"); }], ["redo", function () { exec("redo"); }],
      ["source", function (btn) {
        sourceMode = !sourceMode;
        if (sourceMode) { ta.value = area.innerHTML; ta.hidden = false; area.hidden = true; }
        else { area.innerHTML = ta.value; ta.hidden = true; area.hidden = false; }
        btn.classList.toggle("on", sourceMode);
        bar.querySelectorAll("button:not(.ed-source), select").forEach(function (b) { b.disabled = sourceMode; });
      }]
    ];
    buttons.forEach(function (b) {
      var btn = el("button", { type: "button", "class": "ed-btn ed-" + b[0], title: b[0] }, ICON[b[0]]);
      btn.addEventListener("mousedown", function (e) { e.preventDefault(); });   // keep the text selection
      btn.addEventListener("click", function () { b[1](btn); });
      bar.appendChild(btn);
    });

    area.addEventListener("input", sync);
    area.addEventListener("keyup", saveSel);
    area.addEventListener("mouseup", saveSel);
    // Paste as clean text so copied content keeps the website's own style
    area.addEventListener("paste", function (e) {
      var text = (e.clipboardData || window.clipboardData).getData("text/plain");
      if (!text) return;
      e.preventDefault();
      var html = text.split(/\r?\n\s*\r?\n/).map(function (p) { return "<p>" + escapeHtml(p).replace(/\r?\n/g, "<br>") + "</p>"; }).join("");
      document.execCommand("insertHTML", false, html);
      sync();
    });
    if (form) form.addEventListener("submit", function () { if (!sourceMode) ta.value = area.innerHTML; });
    if (!area.innerHTML.trim()) area.innerHTML = "<p><br></p>";
  }

  document.querySelectorAll("textarea[data-editor]").forEach(makeEditor);
})();
