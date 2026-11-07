/* =========================================================
   COPA MCQ – Word-style rich text editor for study material (admin)
   COPA_RTE.create(hostElement, { html, margin }) → { getHTML(), getMargin(), isEmpty(), focus() }
   Toolbar: undo/redo, styles, font, size, B I U S, sub/sup, colours, align, lists, indent,
   line spacing, page margins, link, image, table (+ row/column tools), line, clear format, full screen.
   Output is cleaned with COPA.sanitizeHTML (only safe tags and styles are kept).
   ========================================================= */
(function () {
  "use strict";
  const FONTS = [["Font", ""], ["Poppins", "Poppins, sans-serif"], ["Roboto", "Roboto, sans-serif"], ["Arial", "Arial, Helvetica, sans-serif"], ["Calibri", "Calibri, Carlito, sans-serif"],
    ["Times New Roman", "'Times New Roman', Times, serif"], ["Georgia", "Georgia, serif"], ["Verdana", "Verdana, sans-serif"], ["Tahoma", "Tahoma, sans-serif"],
    ["Courier New", "'Courier New', monospace"], ["हिंदी / मराठी", "'Noto Sans Devanagari', sans-serif"]];
  const SIZES = [8, 9, 10, 11, 12, 14, 16, 18, 20, 22, 24, 28, 32, 36, 48, 60];
  const STYLES = [["p", "Normal text"], ["h1", "Heading 1"], ["h2", "Heading 2"], ["h3", "Heading 3"], ["h4", "Heading 4"], ["blockquote", "Quote"], ["pre", "Code"]];
  const SPACING = [["1", "1.0"], ["1.15", "1.15"], ["1.5", "1.5"], ["2", "2.0"], ["2.5", "2.5"]];
  const MARGINS = [["narrow", "Narrow margins"], ["normal", "Normal margins"], ["wide", "Wide margins"]];
  const BLOCKS = "p,h1,h2,h3,h4,h5,h6,li,blockquote,pre,div,td,th";
  const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const clean = (h) => (window.COPA && window.COPA.sanitizeHTML ? window.COPA.sanitizeHTML(h) : h);

  const B = (cmd, icon, title, extra) => '<button type="button" class="rte-b" data-cmd="' + cmd + '" title="' + title + '"' + (extra || "") + '><i class="fa-solid fa-' + icon + '"></i></button>';
  const SEP = '<span class="rte-sep"></span>';

  function create(host, opts) {
    opts = opts || {};
    host.classList.add("rte");
    host.innerHTML =
      '<div class="rte-bar" role="toolbar" aria-label="Formatting">' +
        '<div class="rte-g">' + B("undo", "rotate-left", "Undo (Ctrl+Z)") + B("redo", "rotate-right", "Redo (Ctrl+Y)") + "</div>" + SEP +
        '<div class="rte-g"><select class="rte-s" data-sel="block" title="Paragraph style">' + STYLES.map((s) => '<option value="' + s[0] + '">' + s[1] + "</option>").join("") + "</select>" +
        '<select class="rte-s" data-sel="font" title="Font">' + FONTS.map((f) => '<option value="' + esc(f[1]) + '">' + f[0] + "</option>").join("") + "</select>" +
        '<select class="rte-s sm" data-sel="size" title="Font size"><option value="">Size</option>' + SIZES.map((s) => '<option value="' + s + '">' + s + "</option>").join("") + "</select></div>" + SEP +
        '<div class="rte-g">' + B("bold", "bold", "Bold (Ctrl+B)") + B("italic", "italic", "Italic (Ctrl+I)") + B("underline", "underline", "Underline (Ctrl+U)") + B("strikeThrough", "strikethrough", "Strikethrough") +
          B("subscript", "subscript", "Subscript") + B("superscript", "superscript", "Superscript") +
          '<label class="rte-b rte-color" title="Text colour"><i class="fa-solid fa-font"></i><span class="bar" style="background:#d61f1f"></span><input type="color" data-color="foreColor" value="#d61f1f"></label>' +
          '<label class="rte-b rte-color" title="Highlight colour"><i class="fa-solid fa-highlighter"></i><span class="bar" style="background:#ffe14d"></span><input type="color" data-color="hiliteColor" value="#ffe14d"></label></div>' + SEP +
        '<div class="rte-g">' + B("justifyLeft", "align-left", "Align left") + B("justifyCenter", "align-center", "Center") + B("justifyRight", "align-right", "Align right") + B("justifyFull", "align-justify", "Justify") + "</div>" + SEP +
        '<div class="rte-g">' + B("insertUnorderedList", "list-ul", "Bullets") + B("insertOrderedList", "list-ol", "Numbering") + B("outdent", "outdent", "Decrease indent") + B("indent", "indent", "Increase indent") +
          '<select class="rte-s sm" data-sel="spacing" title="Line spacing"><option value="">↕ Spacing</option>' + SPACING.map((s) => '<option value="' + s[0] + '">' + s[1] + "</option>").join("") + "</select></div>" + SEP +
        '<div class="rte-g">' + B("link", "link", "Insert link") + B("unlink", "link-slash", "Remove link") +
          '<label class="rte-b" title="Insert picture from computer"><i class="fa-solid fa-image"></i><input type="file" accept="image/*" data-file hidden></label>' + B("imgurl", "globe", "Insert picture from a web address") +
          '<span class="rte-tbl-wrap">' + B("table", "table", "Insert table") + '<div class="rte-grid" hidden><div class="cells"></div><div class="lbl">Insert table</div></div></span>' +
          B("insertHorizontalRule", "minus", "Horizontal line") + B("removeFormat", "text-slash", "Clear formatting") + "</div>" + SEP +
        '<div class="rte-g"><select class="rte-s" data-sel="margin" title="Page margins">' + MARGINS.map((m) => '<option value="' + m[0] + '">' + m[1] + "</option>").join("") + "</select>" +
          B("full", "expand", "Full screen") + "</div>" +
        '<div class="rte-ctx" data-ctx="table" hidden><b><i class="fa-solid fa-table"></i> Table</b>' +
          '<button type="button" data-t="rowAbove">+ Row above</button><button type="button" data-t="rowBelow">+ Row below</button><button type="button" data-t="colLeft">+ Column left</button><button type="button" data-t="colRight">+ Column right</button>' +
          '<button type="button" data-t="head">Header row</button><label class="cellc" title="Cell colour">Cell colour <input type="color" data-t-color value="#e8f0ff"></label>' +
          '<button type="button" class="del" data-t="delRow">Delete row</button><button type="button" class="del" data-t="delCol">Delete column</button><button type="button" class="del" data-t="delTable">Delete table</button></div>' +
        '<div class="rte-ctx" data-ctx="img" hidden><b><i class="fa-solid fa-image"></i> Picture size</b>' + ["25", "50", "75", "100"].map((w) => '<button type="button" data-w="' + w + '">' + w + "%</button>").join("") +
          '<button type="button" data-ia="left">Left</button><button type="button" data-ia="center">Center</button><button type="button" data-ia="right">Right</button><button type="button" class="del" data-w="del">Delete picture</button></div>' +
      "</div>" +
      '<div class="rte-paper"><div class="rte-ed rte-content" contenteditable="true" spellcheck="true" role="textbox" aria-multiline="true"></div></div>' +
      '<div class="rte-foot"><span data-wc>0 words</span><span>Tip: paste from MS Word keeps the formatting • Ctrl+B / I / U work</span></div>';

    const ed = host.querySelector(".rte-ed"), bar = host.querySelector(".rte-bar");
    const q = (s) => host.querySelector(s);
    let range = null, curImg = null;

    ed.innerHTML = clean(opts.html || "") || "<p><br></p>";
    let margin = MARGINS.some((m) => m[0] === opts.margin) ? opts.margin : "normal";
    const setMargin = (m) => { margin = m; host.dataset.margin = m; q('[data-sel="margin"]').value = m; };
    setMargin(margin);
    try { document.execCommand("defaultParagraphSeparator", false, "p"); } catch (e) { /* old browsers */ }

    /* ---- selection memory (toolbar selects and colour pickers take the focus away) ---- */
    const inEd = (n) => n && (n === ed || ed.contains(n));
    const save = () => {
      const s = window.getSelection(); if (!s.rangeCount) return;
      const r = s.getRangeAt(0); if (!inEd(r.commonAncestorContainer)) return;
      range = r.cloneRange();
    };
    document.addEventListener("selectionchange", () => { const before = range; save(); if (range !== before) refresh(); });
    ["keyup", "mouseup", "input", "blur", "focusout"].forEach((ev) => ed.addEventListener(ev, save));
    bar.addEventListener("pointerdown", save, true); // the moment the toolbar is touched, remember the cursor
    const restore = () => {
      // keep the live selection when it is still in the editor (keeps "typing styles" such as italic for the next letters)
      const s = window.getSelection();
      if (document.activeElement === ed && s.rangeCount && inEd(s.getRangeAt(0).commonAncestorContainer)) return;
      ed.focus();
      if (range) { s.removeAllRanges(); s.addRange(range); }
    };
    const exec = (cmd, val, css) => {
      restore();
      try { document.execCommand("styleWithCSS", false, css !== false); } catch (e) { /* ignore */ }
      document.execCommand(cmd, false, val == null ? null : val);
      changed();
    };
    const changed = () => { const s = window.getSelection(); if (s.rangeCount && inEd(s.getRangeAt(0).commonAncestorContainer)) range = s.getRangeAt(0).cloneRange(); count(); refresh(); };
    const count = () => { const n = (ed.innerText || "").trim().split(/\s+/).filter(Boolean).length; q("[data-wc]").textContent = n + (n === 1 ? " word" : " words"); };

    const node = () => { const s = window.getSelection(); let n = s.rangeCount ? s.getRangeAt(0).startContainer : null; if (n && n.nodeType === 3) n = n.parentNode; return inEd(n) ? n : null; };
    const cell = () => { const n = node(); return n && n.closest ? n.closest("td,th") : null; };

    /* show which buttons are on */
    function refresh() {
      ["bold", "italic", "underline", "strikeThrough", "subscript", "superscript", "justifyLeft", "justifyCenter", "justifyRight", "justifyFull", "insertUnorderedList", "insertOrderedList"].forEach((c) => {
        const b = bar.querySelector('[data-cmd="' + c + '"]'); if (!b) return;
        let on = false; try { on = document.queryCommandState(c); } catch (e) { /* ignore */ }
        b.classList.toggle("on", !!on);
      });
      const n = node();
      const blk = n && n.closest ? n.closest("h1,h2,h3,h4,blockquote,pre,p") : null;
      q('[data-sel="block"]').value = blk ? blk.tagName.toLowerCase() : "p";
      q('[data-ctx="table"]').hidden = !cell();
      q('[data-ctx="img"]').hidden = !curImg;
    }

    /* ---- buttons ---- */
    bar.addEventListener("mousedown", (e) => { if (e.target.closest("button")) e.preventDefault(); }); // keep the text selected
    bar.addEventListener("click", (e) => {
      const b = e.target.closest("[data-cmd]"); if (!b) return;
      const c = b.dataset.cmd;
      if (c === "link") {
        restore();
        const url = prompt("Link address (https://…)", "https://"); if (!url || url === "https://") return;
        const u = /^(https?:|mailto:)/i.test(url) ? url : "https://" + url;
        const s = window.getSelection();
        if (s.isCollapsed) document.execCommand("insertHTML", false, '<a href="' + esc(u) + '" target="_blank" rel="noopener">' + esc(url) + "</a>&nbsp;");
        else { document.execCommand("createLink", false, u); ed.querySelectorAll('a[href="' + CSS.escape(u) + '"]').forEach((a) => { a.target = "_blank"; a.rel = "noopener"; }); }
        return changed();
      }
      if (c === "imgurl") {
        restore();
        const url = prompt("Picture address (https://…)", "https://"); if (!url || !/^https?:\/\//i.test(url)) return;
        document.execCommand("insertHTML", false, '<img src="' + esc(url) + '" alt="" style="width:50%">');
        return changed();
      }
      if (c === "table") { const g = q(".rte-grid"); g.hidden = !g.hidden; return; }
      if (c === "full") { host.classList.toggle("rte-full"); b.querySelector("i").className = "fa-solid fa-" + (host.classList.contains("rte-full") ? "compress" : "expand"); ed.focus(); return; }
      if (c === "removeFormat") { exec("removeFormat"); exec("unlink"); return; }
      if (c === "insertHorizontalRule") return exec(c, null, false);
      exec(c);
    });

    /* ---- selects ---- */
    bar.querySelectorAll("[data-sel]").forEach((s) => s.addEventListener("change", () => {
      const v = s.value, k = s.dataset.sel;
      if (k === "block") exec("formatBlock", "<" + v + ">", false);
      else if (k === "font") { if (v) exec("fontName", v); s.value = ""; }
      else if (k === "size") { if (v) fontSize(v); s.value = ""; }
      else if (k === "spacing") { if (v) spacing(v); s.value = ""; }
      else if (k === "margin") setMargin(v);
    }));
    function fontSize(px) {
      restore();
      try { document.execCommand("styleWithCSS", false, false); } catch (e) { /* ignore */ }
      document.execCommand("fontSize", false, "7");
      const made = [];
      ed.querySelectorAll('font[size="7"]').forEach((f) => {
        const sp = document.createElement("span"); sp.style.fontSize = px + "px";
        while (f.firstChild) sp.appendChild(f.firstChild);
        f.replaceWith(sp); made.push(sp);
        sp.querySelectorAll("span").forEach((inner) => { if (inner.style.fontSize) inner.style.fontSize = ""; });
      });
      ed.querySelectorAll('span[style*="xxx-large"]').forEach((sp) => { sp.style.fontSize = px + "px"; made.push(sp); });
      if (made.length) { // keep the same text selected, so font / colour can be changed next
        const r = document.createRange(); r.setStartBefore(made[0]); r.setEndAfter(made[made.length - 1]);
        const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(r);
      }
      changed();
    }
    function spacing(v) {
      restore();
      const s = window.getSelection(); if (!s.rangeCount) return;
      const r = s.getRangeAt(0);
      let list = Array.from(ed.querySelectorAll(BLOCKS)).filter((el) => r.intersectsNode(el) && !el.querySelector(BLOCKS));
      if (!list.length) { const n = node(); const b = n && n.closest(BLOCKS); if (b && b !== ed) list = [b]; }
      if (!list.length) { document.execCommand("formatBlock", false, "<p>"); const n = node(); const b = n && n.closest("p"); if (b) list = [b]; }
      list.forEach((el) => (el.style.lineHeight = v));
      changed();
    }

    /* ---- colours ---- */
    bar.querySelectorAll("[data-color]").forEach((inp) => {
      inp.addEventListener("input", () => { inp.parentNode.querySelector(".bar").style.background = inp.value; });
      inp.addEventListener("change", () => { const c = inp.dataset.color; exec(c, inp.value); if (c === "hiliteColor" && !document.queryCommandSupported("hiliteColor")) exec("backColor", inp.value); });
    });

    /* ---- pictures from the computer (resized so the notes stay small) ---- */
    q("[data-file]").addEventListener("change", (e) => {
      const f = e.target.files && e.target.files[0]; e.target.value = ""; if (!f) return;
      if (!/^image\//.test(f.type)) return alert("Please choose a picture file.");
      const rd = new FileReader();
      rd.onload = () => {
        const im = new Image();
        im.onload = () => {
          const max = 1000, k = Math.min(1, max / Math.max(im.width, im.height));
          const cv = document.createElement("canvas"); cv.width = Math.round(im.width * k); cv.height = Math.round(im.height * k);
          const cx = cv.getContext("2d"); cx.fillStyle = "#fff"; cx.fillRect(0, 0, cv.width, cv.height); cx.drawImage(im, 0, 0, cv.width, cv.height);
          const data = f.type === "image/png" && cv.width * cv.height < 400000 ? cv.toDataURL("image/png") : cv.toDataURL("image/jpeg", 0.82);
          restore(); document.execCommand("insertHTML", false, '<img src="' + data + '" alt="" style="width:' + (cv.width > 500 ? 75 : 50) + '%">'); changed();
        };
        im.src = rd.result;
      };
      rd.readAsDataURL(f);
    });
    ed.addEventListener("click", (e) => {
      const im = e.target.closest && e.target.closest("img");
      ed.querySelectorAll("img.sel").forEach((x) => x.classList.remove("sel"));
      curImg = im || null; if (im) im.classList.add("sel");
      refresh();
    });
    q('[data-ctx="img"]').addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b || !curImg) return;
      if (b.dataset.w === "del") { curImg.remove(); curImg = null; }
      else if (b.dataset.w) curImg.style.width = b.dataset.w + "%";
      else if (b.dataset.ia) {
        curImg.style.display = "block";
        curImg.style.marginLeft = b.dataset.ia === "left" ? "0" : "auto";
        curImg.style.marginRight = b.dataset.ia === "right" ? "0" : "auto";
      }
      changed();
    });

    /* ---- tables ---- */
    const grid = q(".rte-grid"), cells = grid.querySelector(".cells"), lbl = grid.querySelector(".lbl");
    for (let r = 1; r <= 8; r++) for (let c = 1; c <= 10; c++) cells.insertAdjacentHTML("beforeend", '<span data-r="' + r + '" data-c="' + c + '"></span>');
    cells.addEventListener("mouseover", (e) => {
      const s = e.target.closest("span"); if (!s) return;
      const R = +s.dataset.r, Cc = +s.dataset.c;
      cells.querySelectorAll("span").forEach((x) => x.classList.toggle("on", +x.dataset.r <= R && +x.dataset.c <= Cc));
      lbl.textContent = R + " × " + Cc + " table";
    });
    cells.addEventListener("mousedown", (e) => e.preventDefault());
    cells.addEventListener("click", (e) => {
      const s = e.target.closest("span"); if (!s) return;
      const R = +s.dataset.r, Cc = +s.dataset.c;
      let h = '<table class="rte-table"><tbody>';
      for (let r = 0; r < R; r++) { h += "<tr>"; for (let c = 0; c < Cc; c++) h += r === 0 ? "<th><br></th>" : "<td><br></td>"; h += "</tr>"; }
      h += "</tbody></table>";
      grid.hidden = true;
      restore();
      // put the table after the current paragraph (never inside a <span> or <p>)
      const tmp = document.createElement("div"); tmp.innerHTML = h; const table = tmp.firstChild;
      let blk = node(); while (blk && blk.parentNode !== ed) blk = blk.parentNode;
      if (blk && blk.nodeType === 1 && !(blk.textContent || "").trim() && !blk.querySelector("img,table")) blk.replaceWith(table);
      else if (blk) blk.after(table); else ed.appendChild(table);
      if (!table.nextElementSibling) { const pp = document.createElement("p"); pp.innerHTML = "<br>"; table.after(pp); }
      const r = document.createRange(); r.selectNodeContents(table.rows[0].cells[0]); r.collapse(true);
      const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(r);
      changed();
    });
    document.addEventListener("click", (e) => { if (!e.target.closest(".rte-tbl-wrap")) grid.hidden = true; });

    const emptyCell = (tag) => { const x = document.createElement(tag); x.innerHTML = "<br>"; return x; };
    q('[data-ctx="table"]').addEventListener("click", (e) => {
      const b = e.target.closest("button"); const td = cell(); if (!b || !td) return;
      const tr = td.parentNode, table = td.closest("table"), idx = td.cellIndex, rows = Array.from(table.rows);
      const t = b.dataset.t;
      if (t === "rowAbove" || t === "rowBelow") {
        const nr = document.createElement("tr");
        Array.from(tr.cells).forEach(() => nr.appendChild(emptyCell("td")));
        tr.parentNode.insertBefore(nr, t === "rowAbove" ? tr : tr.nextSibling);
      } else if (t === "colLeft" || t === "colRight") {
        rows.forEach((row) => { const ref = row.cells[idx]; const nc = emptyCell(ref && ref.tagName === "TH" ? "th" : "td"); row.insertBefore(nc, ref ? (t === "colLeft" ? ref : ref.nextSibling) : null); });
      } else if (t === "delRow") { if (rows.length > 1) tr.remove(); else table.remove(); }
      else if (t === "delCol") { if (tr.cells.length > 1) rows.forEach((row) => row.cells[idx] && row.cells[idx].remove()); else table.remove(); }
      else if (t === "delTable") table.remove();
      else if (t === "head") {
        const first = rows[0], toTh = first.cells[0] && first.cells[0].tagName === "TD";
        Array.from(first.cells).forEach((c) => { const n = document.createElement(toTh ? "th" : "td"); n.innerHTML = c.innerHTML; n.style.cssText = c.style.cssText; c.replaceWith(n); });
      }
      changed();
    });
    q("[data-t-color]").addEventListener("change", (e) => { const td = cell(); if (td) { td.style.backgroundColor = e.target.value; changed(); } });
    // keep the cursor in the cell when the colour picker opens
    q("[data-t-color]").addEventListener("mousedown", () => {});

    /* ---- keyboard ---- */
    ed.addEventListener("keydown", (e) => {
      if (e.key === "Tab") {
        const td = cell();
        if (td) {
          e.preventDefault();
          const all = Array.from(td.closest("table").querySelectorAll("td,th")); let i = all.indexOf(td) + (e.shiftKey ? -1 : 1);
          if (i >= all.length) { const tr = td.closest("tr"); const nr = document.createElement("tr"); Array.from(tr.cells).forEach(() => nr.appendChild(emptyCell("td"))); tr.after(nr); i = all.length; }
          const target = td.closest("table").querySelectorAll("td,th")[Math.max(0, i)];
          const r = document.createRange(); r.selectNodeContents(target); r.collapse(true); const s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
          return;
        }
        e.preventDefault();
        const n = node();
        if (n && n.closest("li")) document.execCommand(e.shiftKey ? "outdent" : "indent");
        else if (!e.shiftKey) document.execCommand("insertHTML", false, "&nbsp;&nbsp;&nbsp;&nbsp;");
        changed();
      }
      if (e.key === "Escape" && host.classList.contains("rte-full")) { host.classList.remove("rte-full"); }
    });
    ed.addEventListener("input", count);
    ed.addEventListener("keyup", refresh);

    /* ---- paste (MS Word / web pages): keep formatting, drop everything unsafe ---- */
    ed.addEventListener("paste", (e) => {
      const cd = e.clipboardData; if (!cd) return;
      const html = cd.getData("text/html"), txt = cd.getData("text/plain");
      const file = Array.from(cd.files || []).find((f) => /^image\//.test(f.type));
      if (file && !html) { e.preventDefault(); const dt = new DataTransfer(); dt.items.add(file); const fi = q("[data-file]"); fi.files = dt.files; fi.dispatchEvent(new Event("change")); return; }
      e.preventDefault();
      if (html) document.execCommand("insertHTML", false, clean(html));
      else document.execCommand("insertHTML", false, esc(txt).replace(/\r?\n/g, "<br>"));
      changed();
    });

    count(); refresh();
    return {
      el: host,
      getHTML() {
        ed.querySelectorAll("img.sel").forEach((x) => x.classList.remove("sel"));
        const h = clean(ed.innerHTML);
        return /^(<p>(<br>|&nbsp;|\s)*<\/p>)*$/.test(h.trim()) ? "" : h;
      },
      getMargin: () => margin,
      isEmpty() { return !(ed.innerText || "").trim() && !ed.querySelector("img,table,hr"); },
      focus: () => ed.focus()
    };
  }
  window.COPA_RTE = { create };
})();
