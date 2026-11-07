/* =========================================================
   COPA MCQ – Data layer for Admin Panel & live tracking
   Two drivers with the same API:
     • firebase – online Firestore (all devices, real-time)
     • mysql    – your own MySQL database through the PHP API in /api
                  (js/site-config.js → backend: "mysql")
     • local    – this browser only (localStorage), used when
                  js/site-config.js has no firebase config
   Admin-managed content (custom questions, study notes, site
   settings) is cached in localStorage so pages can use it
   synchronously on load (see app.js).
   ========================================================= */
(function () {
  "use strict";
  const CFG = window.COPA_CONFIG || {};
  const FB_VER = "10.12.2";
  const LIVE_TTL = 75 * 1000; // a session is "live" if its heartbeat is newer than this

  /* ---------- small helpers ---------- */
  const ls = {
    get(k, d) { try { const v = localStorage.getItem("copa_db_" + k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem("copa_db_" + k, JSON.stringify(v)); } catch (e) { /* quota */ } }
  };
  const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
  const loadScript = (src) => new Promise((res, rej) => {
    const s = document.createElement("script"); s.src = src; s.async = true;
    s.onload = res; s.onerror = () => rej(new Error("Could not load " + src)); document.head.appendChild(s);
  });
  const hash = (str) => { let h = 5381; const x = "copa-admin|" + str; for (let i = 0; i < x.length; i++) h = ((h << 5) + h + x.charCodeAt(i)) >>> 0; return h.toString(36) + x.length.toString(36); };

  /* ---------- cache (used synchronously by app.js / ads.js) ---------- */
  const cache = {
    questions: () => ls.get("questions", []),
    notes: () => ls.get("notes", []),
    settings: () => ls.get("settings", null),
    setQuestions: (v) => ls.set("questions", v),
    setNotes: (v) => ls.set("notes", v),
    setSettings: (v) => ls.set("settings", v)
  };

  /* =========================================================
     LOCAL DRIVER
     ========================================================= */
  const local = {
    mode: "local",
    init: () => Promise.resolve(),
    /* content */
    listQuestions: () => Promise.resolve(cache.questions()),
    saveQuestion(q) { const all = cache.questions().filter((x) => x.id !== q.id); all.push(q); cache.setQuestions(all); return Promise.resolve(q); },
    deleteQuestion(id) { cache.setQuestions(cache.questions().filter((x) => x.id !== id)); return Promise.resolve(); },
    listNotes: () => Promise.resolve(cache.notes()),
    saveNote(n) { const all = cache.notes().filter((x) => x.id !== n.id); all.push(n); cache.setNotes(all); return Promise.resolve(n); },
    deleteNote(id) { cache.setNotes(cache.notes().filter((x) => x.id !== id)); return Promise.resolve(); },
    getSettings: () => Promise.resolve(cache.settings()),
    saveSettings(s) { cache.setSettings(s); return Promise.resolve(s); },
    /* students / results / live */
    upsertStudent(p) { const all = ls.get("students", {}); all[p.uid] = Object.assign({}, all[p.uid] || {}, p, { updated: Date.now() }); ls.set("students", all); return Promise.resolve(); },
    saveResult(r) { const all = ls.get("results", []); all.unshift(r); ls.set("results", all.slice(0, 5000)); return Promise.resolve(); },
    liveSet(id, data) { const all = ls.get("live", {}); all[id] = data; ls.set("live", all); return Promise.resolve(); },
    liveDel(id) { const all = ls.get("live", {}); delete all[id]; ls.set("live", all); return Promise.resolve(); },
    listResults: () => Promise.resolve(ls.get("results", [])),
    listStudents: () => Promise.resolve(Object.values(ls.get("students", {}))),
    deleteResult(id) { ls.set("results", ls.get("results", []).filter((r) => r.id !== id)); return Promise.resolve(); },
    watchLive(cb) {
      const emit = () => cb(Object.values(ls.get("live", {})));
      emit();
      const onStore = (e) => { if (!e.key || e.key === "copa_db_live") emit(); };
      window.addEventListener("storage", onStore);
      const t = setInterval(emit, 3000);
      return () => { window.removeEventListener("storage", onStore); clearInterval(t); };
    },
    /* admin auth (local: password stored hashed in this browser) */
    adminHasPassword: () => !!ls.get("admin_pw", null),
    adminSetPassword(pw) { ls.set("admin_pw", hash(pw)); sessionStorage.setItem("copa_admin", "1"); return Promise.resolve(true); },
    adminLogin(email, pw) {
      if (ls.get("admin_pw", null) === hash(pw)) { sessionStorage.setItem("copa_admin", "1"); return Promise.resolve(true); }
      return Promise.reject(new Error("Wrong password"));
    },
    adminLogout() { sessionStorage.removeItem("copa_admin"); return Promise.resolve(); },
    adminCheck: () => Promise.resolve(sessionStorage.getItem("copa_admin") === "1")
  };

  /* =========================================================
     FIREBASE DRIVER (Firestore + Auth, compat SDK from gstatic)
     ========================================================= */
  let fdb = null, fauth = null, initP = null;
  const fb = {
    mode: "firebase",
    init() {
      if (initP) return initP;
      const base = "https://www.gstatic.com/firebasejs/" + FB_VER + "/";
      initP = loadScript(base + "firebase-app-compat.js")
        .then(() => Promise.all([loadScript(base + "firebase-firestore-compat.js"), loadScript(base + "firebase-auth-compat.js")]))
        .then(() => {
          const app = window.firebase.apps.length ? window.firebase.app() : window.firebase.initializeApp(CFG.firebase);
          fdb = app.firestore(); fauth = app.auth();
        });
      return initP;
    },
    _all(col, order) {
      return fb.init().then(() => {
        let q = fdb.collection(col);
        if (order) q = q.orderBy(order, "desc");
        return q.limit(5000).get();
      }).then((snap) => snap.docs.map((d) => Object.assign({ id: d.id }, d.data())));
    },
    _set(col, id, data, merge) { return fb.init().then(() => fdb.collection(col).doc(id).set(JSON.parse(JSON.stringify(data)), { merge: !!merge })); },
    _del(col, id) { return fb.init().then(() => fdb.collection(col).doc(id).delete()); },
    listQuestions() { return fb._all("questions").then((v) => { cache.setQuestions(v); return v; }); },
    saveQuestion(q) { return fb._set("questions", q.id, q).then(() => q); },
    deleteQuestion(id) { return fb._del("questions", id); },
    listNotes() { return fb._all("notes").then((v) => { cache.setNotes(v); return v; }); },
    saveNote(n) { return fb._set("notes", n.id, n).then(() => n); },
    deleteNote(id) { return fb._del("notes", id); },
    getSettings() { return fb.init().then(() => fdb.collection("settings").doc("site").get()).then((d) => { const v = d.exists ? d.data() : null; cache.setSettings(v); return v; }); },
    saveSettings(s) { return fb._set("settings", "site", s).then(() => { cache.setSettings(s); return s; }); },
    upsertStudent(p) { const d = Object.assign({}, p, { updated: Date.now() }); delete d.pin; return fb._set("students", p.uid, d, true); },
    saveResult(r) { return fb._set("results", r.id, r); },
    liveSet(id, data) { return fb._set("live", id, data); },
    liveDel(id) { return fb._del("live", id); },
    listResults() { return fb._all("results", "date"); },
    listStudents() { return fb._all("students"); },
    deleteResult(id) { return fb._del("results", id); },
    watchLive(cb) {
      let unsub = () => {};
      fb.init().then(() => { unsub = fdb.collection("live").onSnapshot((snap) => cb(snap.docs.map((d) => Object.assign({ id: d.id }, d.data())))); });
      return () => unsub();
    },
    adminHasPassword: () => true,
    adminSetPassword: () => Promise.reject(new Error("Create the admin user in Firebase Authentication.")),
    adminLogin(email, pw) {
      return fb.init().then(() => fauth.signInWithEmailAndPassword(email, pw)).then((c) => {
        const ok = !(CFG.adminEmails || []).length || CFG.adminEmails.map((e) => e.toLowerCase()).indexOf(String(c.user.email).toLowerCase()) > -1;
        if (!ok) { fauth.signOut(); throw new Error("This account is not an admin."); }
        return true;
      });
    },
    adminLogout() { return fb.init().then(() => fauth.signOut()); },
    adminCheck() {
      return fb.init().then(() => new Promise((res) => {
        const off = fauth.onAuthStateChanged((u) => {
          off();
          res(!!u && (!(CFG.adminEmails || []).length || CFG.adminEmails.map((e) => e.toLowerCase()).indexOf(String(u.email).toLowerCase()) > -1));
        });
      }));
    }
  };

  /* =========================================================
     MYSQL DRIVER (PHP API in /api – see api/index.php and api/config.php)
     ========================================================= */
  const apiBase = () => CFG.apiUrl || (((document.body && document.body.getAttribute("data-root")) || "") + "api/");
  const call = (a, body) => fetch(apiBase() + "index.php?a=" + a, body === undefined
      ? { credentials: "same-origin", cache: "no-store" }
      : { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "X-COPA": "1" }, body: JSON.stringify(body) })
    .then((r) => r.text().then((t) => {
      let j = null; try { j = JSON.parse(t); } catch (e) { /* not JSON (PHP missing / 404) */ }
      if (!j) throw new Error(r.status === 404 ? "API not found – upload the api folder to the website." : "Server error (" + r.status + ")");
      if (!j.ok) throw new Error(j.error || "Server error");
      return j.data;
    }));
  let adminState = { hasAdmin: true, user: null }, contentP = null;
  const content = (force) => {
    if (!contentP || force) contentP = call("content").then((c) => {
      cache.setQuestions(c.questions || []); cache.setNotes(c.notes || []); cache.setSettings(c.settings || null); return c;
    });
    return contentP;
  };
  const my = {
    mode: "mysql",
    init: () => Promise.resolve(),
    listQuestions: () => content(true).then((c) => c.questions || []),
    saveQuestion: (q) => call("question_save", { item: q }),
    deleteQuestion: (id) => call("question_del", { id }),
    listNotes: () => content(true).then((c) => c.notes || []),
    saveNote: (n) => call("note_save", { item: n }),
    deleteNote: (id) => call("note_del", { id }),
    getSettings: () => content(true).then((c) => c.settings || null),
    saveSettings: (s) => call("settings_save", { settings: s }).then((v) => { cache.setSettings(s); return v; }),
    upsertStudent(p) { const d = Object.assign({}, p, { updated: Date.now() }); delete d.pin; return call("student", d); },
    saveResult: (r) => call("result", r),
    liveSet: (id, data) => call("live_set", { id, data }),
    liveDel(id) {
      // keep working while the page is closing
      try { if (navigator.sendBeacon && document.visibilityState === "hidden") { /* beacon cannot send the custom header → normal call */ } } catch (e) { /* ignore */ }
      return call("live_del", { id });
    },
    listResults: () => call("results"),
    listStudents: () => call("students"),
    deleteResult: (id) => call("result_del", { id }),
    watchLive(cb) {
      let stop = false;
      const tick = () => { if (stop) return; call("live").then((v) => { if (!stop) cb(v || []); }).catch(() => {}); };
      tick(); const t = setInterval(tick, 4000);
      return () => { stop = true; clearInterval(t); };
    },
    /* admin: real username + password, checked on the server (PHP session cookie) */
    adminHasPassword: () => adminState.hasAdmin,
    adminSetup: (username, password, key) => call("admin_setup", { username, password, key }).then((d) => { adminState = { hasAdmin: true, user: d.user }; return true; }),
    adminSetPassword: () => Promise.reject(new Error("Use the setup form.")),
    adminLogin: (username, pw) => call("admin_login", { username, password: pw }).then((d) => { adminState.user = d.user; return true; }),
    adminLogout: () => call("admin_logout", {}),
    adminChangePassword: (old, password) => call("admin_password", { old, password }),
    adminCheck: () => call("admin_status").then((s) => { adminState = s; return !!s.user; }),
    /* students: login from any device (PIN checked on the server) */
    studentRegister: (d) => call("student_register", d),
    studentLogin: (uid, pin) => call("student_login", { uid, pin }),
    studentResults: (token) => call("student_results", { token }),
    studentLogout: (token) => call("student_logout", { token }),
    /* visitors & users online */
    hit: (d) => call("hit", d),
    ping: (d) => call("ping", d),
    bye(vid) { try { if (navigator.sendBeacon) navigator.sendBeacon(apiBase() + "index.php?a=bye&vid=" + encodeURIComponent(vid)); } catch (e) { /* ignore */ } },
    adminOnline: () => call("online"),
    adminVisitors: (days) => call("visitors&days=" + (+days || 7)),
    adminStudentPin: (uid, pin) => call("student_pin", { uid, pin })
  };

  // opened as a file from the computer (no PHP) → local mode
  const driver = CFG.backend === "mysql" && location.protocol !== "file:" ? my : CFG.firebase && CFG.firebase.projectId ? fb : local;
  const safe = (p) => Promise.resolve(p).catch((e) => { if (window.console) console.warn("[COPA_DB]", e && e.message); });

  /* refresh public content cache in the background (firebase / mysql), max every 10 min */
  function refreshCache(force) {
    if (driver.mode === "local") return Promise.resolve(false);
    const last = ls.get("refreshed", 0);
    if (!force && Date.now() - last < 10 * 60 * 1000) return Promise.resolve(false);
    const before = JSON.stringify([cache.questions(), cache.notes(), cache.settings()]);
    return Promise.all([driver.listQuestions(), driver.listNotes(), driver.getSettings()])
      .then(() => { ls.set("refreshed", Date.now()); return before !== JSON.stringify([cache.questions(), cache.notes(), cache.settings()]); })
      .catch(() => false);
  }

  /* ---------- live exam tracking (called from exam.js) ---------- */
  let liveId = null, liveData = null, beat = null, lastPush = 0;
  const live = {
    start(info) {
      liveId = info.sid || uid();
      liveData = Object.assign({ id: liveId, answered: 0, ts: Date.now() }, info);
      safe(driver.liveSet(liveId, liveData));
      clearInterval(beat);
      beat = setInterval(() => { if (liveData) { liveData.ts = Date.now(); safe(driver.liveSet(liveId, liveData)); } }, 25000);
    },
    update(patch) {
      if (!liveData) return;
      Object.assign(liveData, patch, { ts: Date.now() });
      if (Date.now() - lastPush > 4000) { lastPush = Date.now(); safe(driver.liveSet(liveId, liveData)); }
    },
    end() { clearInterval(beat); if (liveId) safe(driver.liveDel(liveId)); liveId = null; liveData = null; }
  };

  window.COPA_DB = {
    mode: driver.mode,
    LIVE_TTL,
    driver,
    cache,
    uid,
    refreshCache,
    live,
    upsertStudent: (p) => safe(driver.upsertStudent(p)),
    saveResult: (r) => safe(driver.saveResult(r)),
    isLive: (s) => s && Date.now() - (s.ts || 0) < LIVE_TTL
  };
})();
