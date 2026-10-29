<?php
/* Admin → CBT upload form: "Whole folder" mode. The browser packs the chosen folder into one ZIP
   (no compression) and sends it as test_file, so hosting limits on the number of uploaded files don't matter. */
$cbtExts = array_keys(CBT_TYPES);
?>
<style>
  .up-mode { border: 1px solid #e3e8f4; border-radius: 12px; padding: 12px 14px; margin: 4px 0 12px; }
  .up-mode legend { font-weight: 600; padding: 0 6px; }
  .up-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
  .up-tabs label { display: flex; align-items: center; gap: 6px; padding: 8px 12px; border: 1px solid #d6deef; border-radius: 10px; cursor: pointer; margin: 0; font-weight: 600; }
  .up-tabs label:has(input:checked) { border-color: #1f4fd6; background: #eef3ff; color: #1f4fd6; }
  .up-tabs small { font-weight: 500; color: #16a34a; }
  .up-pane[hidden] { display: none; }
  .up-info { margin-top: 8px; padding: 8px 10px; border-radius: 8px; background: #ecfdf3; color: #166534; font-size: 13px; }
  .up-info.bad { background: #fff1f2; color: #be123c; }
</style>
<script>
(function () {
  var form = document.getElementById('form'), folder = document.getElementById('up-folder'), file = document.getElementById('up-file'),
      info = form.querySelector('.up-pane .up-info'), msg = form.querySelector('.up-msg'), isNew = <?= $cbtNew ? 'true' : 'false' ?>, EXT = <?= json_encode($cbtExts) ?>,
      MAX = <?= (int)MAX_CBT_BYTES ?>;
  function mode() { return form.querySelector('input[name=up_mode]:checked').value; }
  form.querySelectorAll('input[name=up_mode]').forEach(function (r) {
    r.addEventListener('change', function () {
      form.querySelectorAll('.up-pane').forEach(function (p) { p.hidden = p.dataset.mode !== mode(); });
    });
  });

  // Files worth sending, with paths relative to the chosen folder (its own name removed)
  function picked() {
    var list = [].slice.call(folder.files || []).filter(function (f) {
      var p = f.webkitRelativePath || f.name, base = p.split('/').pop(), ext = base.split('.').pop().toLowerCase();
      return base[0] !== '.' && EXT.indexOf(ext) > -1;
    });
    var paths = list.map(function (f) { return (f.webkitRelativePath || f.name).replace(/\\/g, '/'); });
    var top = paths.length && paths.every(function (p) { return p.indexOf('/') > 0 && p.split('/')[0] === paths[0].split('/')[0]; }) ? paths[0].split('/')[0].length + 1 : 0;
    return list.map(function (f, i) { return { file: f, path: paths[i].slice(top) }; });
  }
  folder.addEventListener('change', function () {
    var l = picked(), html = l.filter(function (x) { return /\.html?$/i.test(x.path); }), size = l.reduce(function (a, x) { return a + x.file.size; }, 0),
        idx = l.some(function (x) { return /^index\.html?$/i.test(x.path); }),
        tests = l.filter(function (x) { return /(^|\/)index\.html?$/i.test(x.path); }).length;
    info.hidden = false;
    info.classList.toggle('bad', !html.length || size > MAX);
    info.textContent = !l.length ? 'No usable files in this folder.' : !html.length ? 'This folder has no .html file.' :
      size > MAX ? 'Folder is too big (' + (size / 1048576).toFixed(1) + ' MB, max ' + (MAX / 1048576) + ' MB).' :
      tests > 1 ? '✓ ' + tests + ' tests found (' + (size / 1048576).toFixed(1) + ' MB) – they will open in Bulk import to check trades and names.' :
      '✓ ' + l.length + ' files ready (' + (size / 1048576).toFixed(2) + ' MB)' + (idx ? ' · start page: index.html' : ' · start page: ' + html[0].path);
  });

  // --- minimal ZIP writer (stored, no compression) ---
  var CRC = (function () { var t = [], c, n, k; for (n = 0; n < 256; n++) { c = n; for (k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; } return t; })();
  function crc32(b) { var c = 0xFFFFFFFF; for (var i = 0; i < b.length; i++) c = CRC[(c ^ b[i]) & 255] ^ (c >>> 8); return (c ^ 0xFFFFFFFF) >>> 0; }
  function makeZip(items) {
    var enc = new TextEncoder(), parts = [], central = [], off = 0;
    return Promise.all(items.map(function (x) { return x.file.arrayBuffer(); })).then(function (bufs) {
      bufs.forEach(function (buf, i) {
        var data = new Uint8Array(buf), name = enc.encode(items[i].path), crc = crc32(data);
        var h = new DataView(new ArrayBuffer(30));
        h.setUint32(0, 0x04034b50, true); h.setUint16(4, 20, true); h.setUint16(6, 0x0800, true); h.setUint16(8, 0, true);
        h.setUint32(14, crc, true); h.setUint32(18, data.length, true); h.setUint32(22, data.length, true); h.setUint16(26, name.length, true);
        parts.push(h.buffer, name, data);
        var c = new DataView(new ArrayBuffer(46));
        c.setUint32(0, 0x02014b50, true); c.setUint16(4, 20, true); c.setUint16(6, 20, true); c.setUint16(8, 0x0800, true);
        c.setUint32(16, crc, true); c.setUint32(20, data.length, true); c.setUint32(24, data.length, true); c.setUint16(28, name.length, true); c.setUint32(42, off, true);
        central.push(c.buffer, name);
        off += 30 + name.length + data.length;
      });
      var csize = central.reduce(function (a, p) { return a + (p.byteLength || p.length); }, 0);
      var e = new DataView(new ArrayBuffer(22));
      e.setUint32(0, 0x06054b50, true); e.setUint16(8, items.length, true); e.setUint16(10, items.length, true); e.setUint32(12, csize, true); e.setUint32(16, off, true);
      return new Blob(parts.concat(central, [e.buffer]), { type: 'application/zip' });
    });
  }

  // What went wrong, in words, when the hosting (not this website) refused the upload
  var LIMIT = <?= (int)min(array_filter([ini_bytes((string)ini_get('upload_max_filesize')), ini_bytes((string)ini_get('post_max_size'))]) ?: [8 << 20]) ?>;
  function problem(status, text) {
    if (status === 413) return text && text.length < 400 ? text : 'The file is too big for this hosting. In hPanel → Advanced → PHP Configuration set upload_max_filesize and post_max_size to 64M.';
    if (status === 403 || status === 406) return 'The hosting\'s firewall blocked this upload (error ' + status + '). Test files with scrambled (obfuscated) JavaScript can look like a virus to it. '
      + 'Ask Hostinger support to allow uploads to /admin/cbt.php (ModSecurity / Imunify360), or upload the test ZIP to data/import/ with File Manager and use Bulk import.';
    if (status === 400) return text && text.length < 400 ? text : 'The form expired. Refresh the page and try again.';
    if (status >= 500) { var m = /Error code: ([A-F0-9]{6})/.exec(text || ''); return 'The server had a problem saving the test (error ' + status + (m ? ', code ' + m[1] : '') + '). Admin → Dashboard → “Recent website errors” shows the details.'; }
    return 'Upload failed (error ' + status + ').';
  }
  function send(fd, btn, label) {
    btn.disabled = true; btn.textContent = 'Uploading…';
    return fetch(form.action || location.href, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
      return r.text().then(function (html) {
        if (!r.ok) throw { msg: problem(r.status, html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()) };
        // The answer already holds the next page with its "Test saved" message: show it (and its address) as it is
        if (r.redirected) history.replaceState(null, '', r.url);
        document.open(); document.write(html); document.close();
      });
    }).catch(function (e) {
      btn.disabled = false; btn.textContent = label;
      msg.hidden = false;
      msg.textContent = e && e.msg ? e.msg : 'Upload failed: the connection broke. Check your internet and try again.';
      msg.scrollIntoView({ block: 'center' });
    });
  }

  form.addEventListener('submit', function (ev) {
    msg.hidden = true;
    var btn = form.querySelector('.bar .btn:not(.alt)'), label = btn.textContent;
    if (mode() !== 'folder') {
      if (!file.files.length) {
        if (isNew) { ev.preventDefault(); alert('Choose a .zip or .html file.'); }
        return;                                            // editing without a new file: normal save
      }
      ev.preventDefault();
      if (file.files[0].size > LIMIT) { alert('This file is ' + (file.files[0].size / 1048576).toFixed(1) + ' MB; the hosting accepts up to ' + (LIMIT / 1048576).toFixed(1) + ' MB. See “Upload check” on this page.'); return; }
      send(new FormData(form), btn, label);
      return;
    }
    var l = picked();
    if (!l.length) {
      if (isNew) { ev.preventDefault(); alert('Choose the test folder first.'); }
      return;                                              // editing without new files: normal save
    }
    ev.preventDefault();
    if (!info.hidden && info.classList.contains('bad')) { alert(info.textContent); return; }
    btn.disabled = true; btn.textContent = 'Packing…';
    makeZip(l).then(function (zip) {
      if (zip.size > LIMIT) throw { msg: 'The folder is ' + (zip.size / 1048576).toFixed(1) + ' MB; the hosting accepts up to ' + (LIMIT / 1048576).toFixed(1) + ' MB. See “Upload check” on this page.' };
      var fd = new FormData(form);
      fd.set('test_file', zip, 'test-folder.zip');
      fd.set('src_name', ((folder.files[0] && folder.files[0].webkitRelativePath) || '').split('/')[0]);
      return send(fd, btn, label);
    }).catch(function (e) {
      btn.disabled = false; btn.textContent = label;
      msg.hidden = false; msg.textContent = e && e.msg ? e.msg : 'Could not read the folder. Try the ZIP option.';
    });
  });
})();
</script>
