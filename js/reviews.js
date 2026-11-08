/* =========================================================
   COPA MCQ – Reviews & Ratings page
   Anyone can read; writing needs "Sign in with Google" (checked on the server).
   One review per Google account (can be edited or deleted).
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, L = C.lang, SRV = C.server;
  C.renderShell("reviews");

  const TX = {
    en: {
      title: "Reviews & Ratings", sub: "What ITI COPA trainees say about COPA MCQ", basedOn: "Based on {n} reviews", basedOne: "Based on 1 review", noReviews: "No reviews yet",
      write: "Write a review", writeTitle: "Share your experience", signNote: "Sign in with Google to write a review. Your Google name and photo are shown with your review – your e-mail is never shown.",
      signed: "Signed in with Google", yourRating: "Your rating", rate: ["Poor", "Fair", "Good", "Very good", "Excellent"], tapStar: "Tap a star",
      tTitle: "Title (optional)", tTitlePh: "e.g. Very useful for the CBT exam", tBody: "Your review", tBodyPh: "What did you like? How did COPA MCQ help you in your studies?",
      submit: "Post review", update: "Update review", del: "Delete my review", delConfirm: "Delete your review?", thanks: "Thank you! Your review is posted.", updated: "Your review is updated.", deleted: "Your review is deleted.",
      pickRating: "Please choose a star rating.", tooShort: "Please write at least 10 characters.", top: "Top reviews", newest: "Newest", lowest: "Lowest", showMore: "Show more reviews", star: "★", all: "All",
      reply: "Reply from COPA MCQ", student: "COPA student", verified: "Google verified", readMore: "Read more", less: "Show less", edited: "edited", beFirst: "Be the first to share your experience!",
      offline: "Reviews are shown when the website is online.", signAgain: "Your Google sign-in has expired. Please sign in again.", hidden: "Your review is hidden by the admin.", yours: "Your review",
      showing: "Showing {a} of {b}", googleMissing: "Google sign-in is not available on this page right now.", filtered: "Only {s}-star reviews", ratingWord: "Website rating"
    },
    hi: {
      title: "समीक्षाएँ और रेटिंग", sub: "ITI COPA प्रशिक्षु COPA MCQ के बारे में क्या कहते हैं", basedOn: "{n} समीक्षाओं के आधार पर", basedOne: "1 समीक्षा के आधार पर", noReviews: "अभी कोई समीक्षा नहीं",
      write: "समीक्षा लिखें", writeTitle: "अपना अनुभव साझा करें", signNote: "समीक्षा लिखने के लिए Google से साइन इन करें। समीक्षा के साथ आपका Google नाम और फोटो दिखेगा – ई-मेल कभी नहीं दिखेगा।",
      signed: "Google से साइन इन", yourRating: "आपकी रेटिंग", rate: ["खराब", "ठीक", "अच्छा", "बहुत अच्छा", "उत्कृष्ट"], tapStar: "स्टार चुनें",
      tTitle: "शीर्षक (वैकल्पिक)", tTitlePh: "जैसे CBT परीक्षा के लिए बहुत उपयोगी", tBody: "आपकी समीक्षा", tBodyPh: "आपको क्या पसंद आया? COPA MCQ ने पढ़ाई में कैसे मदद की?",
      submit: "समीक्षा भेजें", update: "समीक्षा अपडेट करें", del: "मेरी समीक्षा हटाएँ", delConfirm: "अपनी समीक्षा हटाएँ?", thanks: "धन्यवाद! आपकी समीक्षा प्रकाशित हो गई।", updated: "आपकी समीक्षा अपडेट हो गई।", deleted: "आपकी समीक्षा हटा दी गई।",
      pickRating: "कृपया स्टार रेटिंग चुनें।", tooShort: "कृपया कम से कम 10 अक्षर लिखें।", top: "शीर्ष समीक्षाएँ", newest: "नवीनतम", lowest: "सबसे कम", showMore: "और समीक्षाएँ देखें", star: "★", all: "सभी",
      reply: "COPA MCQ का उत्तर", student: "COPA विद्यार्थी", verified: "Google सत्यापित", readMore: "और पढ़ें", less: "कम दिखाएँ", edited: "संपादित", beFirst: "अपना अनुभव साझा करने वाले पहले व्यक्ति बनें!",
      offline: "वेबसाइट ऑनलाइन होने पर समीक्षाएँ दिखेंगी।", signAgain: "Google साइन इन की अवधि समाप्त हो गई। कृपया फिर से साइन इन करें।", hidden: "आपकी समीक्षा एडमिन ने छिपाई है।", yours: "आपकी समीक्षा",
      showing: "{b} में से {a} दिख रही हैं", googleMissing: "अभी इस पेज पर Google साइन इन उपलब्ध नहीं है।", filtered: "केवल {s}-स्टार समीक्षाएँ", ratingWord: "वेबसाइट रेटिंग"
    },
    mr: {
      title: "रिव्ह्यू आणि रेटिंग", sub: "ITI COPA प्रशिक्षणार्थी COPA MCQ बद्दल काय म्हणतात", basedOn: "{n} रिव्ह्यूंवर आधारित", basedOne: "1 रिव्ह्यूवर आधारित", noReviews: "अजून एकही रिव्ह्यू नाही",
      write: "रिव्ह्यू लिहा", writeTitle: "तुमचा अनुभव सांगा", signNote: "रिव्ह्यू लिहिण्यासाठी Google ने साइन इन करा. रिव्ह्यूसोबत तुमचे Google नाव आणि फोटो दिसेल – ई-मेल कधीच दिसणार नाही.",
      signed: "Google ने साइन इन", yourRating: "तुमचे रेटिंग", rate: ["वाईट", "ठीक", "चांगले", "खूप चांगले", "उत्कृष्ट"], tapStar: "स्टार निवडा",
      tTitle: "शीर्षक (ऐच्छिक)", tTitlePh: "उदा. CBT परीक्षेसाठी खूप उपयोगी", tBody: "तुमचा रिव्ह्यू", tBodyPh: "तुम्हाला काय आवडले? COPA MCQ ने अभ्यासात कशी मदत केली?",
      submit: "रिव्ह्यू पाठवा", update: "रिव्ह्यू अपडेट करा", del: "माझा रिव्ह्यू हटवा", delConfirm: "तुमचा रिव्ह्यू हटवायचा?", thanks: "धन्यवाद! तुमचा रिव्ह्यू प्रसिद्ध झाला.", updated: "तुमचा रिव्ह्यू अपडेट झाला.", deleted: "तुमचा रिव्ह्यू हटवला.",
      pickRating: "कृपया स्टार रेटिंग निवडा.", tooShort: "कृपया किमान 10 अक्षरे लिहा.", top: "टॉप रिव्ह्यू", newest: "नवीन", lowest: "सर्वात कमी", showMore: "आणखी रिव्ह्यू पहा", star: "★", all: "सर्व",
      reply: "COPA MCQ चे उत्तर", student: "COPA विद्यार्थी", verified: "Google सत्यापित", readMore: "आणखी वाचा", less: "कमी दाखवा", edited: "संपादित", beFirst: "तुमचा अनुभव सांगणारे पहिले व्हा!",
      offline: "वेबसाइट ऑनलाइन असताना रिव्ह्यू दिसतील.", signAgain: "Google साइन इनची वेळ संपली. कृपया पुन्हा साइन इन करा.", hidden: "तुमचा रिव्ह्यू ॲडमिनने लपवला आहे.", yours: "तुमचा रिव्ह्यू",
      showing: "{b} पैकी {a} दिसत आहेत", googleMissing: "सध्या या पेजवर Google साइन इन उपलब्ध नाही.", filtered: "फक्त {s}-स्टार रिव्ह्यू", ratingWord: "वेबसाइट रेटिंग"
    }
  };
  const X = TX[L] || TX.en;
  const fmt = (s, o) => s.replace(/\{(\w)\}/g, (a, k) => (o[k] != null ? o[k] : a));
  const root = document.getElementById("reviews");

  /* ---------- helpers ---------- */
  const starRow = (v, cls) => '<span class="rv-stars ' + (cls || "") + '" aria-label="' + v + ' / 5"><span class="bg">★★★★★</span><span class="fg" style="width:' + Math.max(0, Math.min(100, (v / 5) * 100)) + '%">★★★★★</span></span>';
  const COLORS = ["#1554e0", "#7b3fe4", "#ef6c3a", "#16a34a", "#0fa3b1", "#e5383b", "#c026d3", "#f59e0b"];
  const avatar = (r, big) => {
    const ini = String(r.name || "?").trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join("").toUpperCase();
    let h = 0; for (const ch of String(r.name || "")) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    const fb = '<span class="rv-ava ini' + (big ? " big" : "") + '" style="background:' + COLORS[h % COLORS.length] + '">' + esc(ini) + "</span>";
    return r.picture ? '<img class="rv-ava' + (big ? " big" : "") + '" src="' + esc(r.picture) + '" alt="" referrerpolicy="no-referrer" loading="lazy" onerror="this.outerHTML=this.dataset.fb" data-fb="' + esc(fb) + '">' : fb;
  };
  const when = (ms) => {
    const d = Math.floor((Date.now() - ms) / 86400000);
    try {
      const rtf = new Intl.RelativeTimeFormat(L, { numeric: "auto" });
      if (d < 1) { const hrs = Math.floor((Date.now() - ms) / 3600000); return hrs < 1 ? rtf.format(-Math.max(1, Math.floor((Date.now() - ms) / 60000)), "minute") : rtf.format(-hrs, "hour"); }
      if (d < 30) return rtf.format(-d, "day");
    } catch (e) { /* old browser */ }
    return new Date(ms).toLocaleDateString(L === "en" ? "en-IN" : L + "-IN", { day: "numeric", month: "short", year: "numeric" });
  };
  const textHTML = (s) => esc(s).replace(/\n/g, "<br>");

  /* ---------- state ---------- */
  const S = { sort: "top", star: 0, items: [], more: false, summary: { count: 0, avg: 0, dist: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 } }, cred: "", me: null, mine: null, rating: 0, loading: false };

  /* ---------- layout ---------- */
  root.innerHTML =
    '<section class="rv-hero"><div class="rv-hero-txt"><span class="rv-kicker"><i class="fa-solid fa-star"></i> ' + esc(X.ratingWord) + "</span><h1>" + esc(X.title) + "</h1><p>" + esc(X.sub) + "</p>" +
      '<a href="#rvWrite" class="btn rv-cta"><i class="fa-solid fa-pen-to-square"></i> ' + esc(X.write) + "</a></div>" +
      '<div class="rv-score" id="rvScore"></div></section>' +
    '<section class="rv-write card" id="rvWrite"></section>' +
    '<div class="rv-bar"><div class="rv-sort" role="tablist">' + [["top", X.top, "fa-fire"], ["new", X.newest, "fa-clock"], ["low", X.lowest, "fa-arrow-down-wide-short"]].map((s) => '<button type="button" data-sort="' + s[0] + '"><i class="fa-solid ' + s[2] + '"></i> ' + esc(s[1]) + "</button>").join("") + "</div>" +
      '<div class="rv-filter" id="rvFilter"></div></div>' +
    '<div class="rv-list" id="rvList"></div>' +
    '<div class="rv-more"><button type="button" class="btn btn-ghost" id="rvMore" hidden><i class="fa-solid fa-chevron-down"></i> ' + esc(X.showMore) + '</button><p class="rv-showing" id="rvShowing"></p></div>' +
    '<div class="ad-slot" data-slot="bottom"></div>';

  const $ = (id) => document.getElementById(id);

  function drawScore() {
    const sm = S.summary, max = Math.max(1, ...Object.values(sm.dist));
    $("rvScore").innerHTML =
      '<div class="big"><b>' + (sm.count ? sm.avg.toFixed(1) : "–") + "</b><span>/ 5</span></div>" + starRow(sm.avg, "lg") +
      "<p>" + esc(sm.count ? (sm.count === 1 ? X.basedOne : fmt(X.basedOn, { n: sm.count })) : X.noReviews) + "</p>" +
      '<div class="dist">' + [5, 4, 3, 2, 1].map((k) => '<button type="button" class="row' + (S.star === k ? " on" : "") + '" data-star="' + k + '"><span>' + k + ' <i class="fa-solid fa-star"></i></span><span class="tr"><i style="width:' + ((sm.dist[k] || 0) / max) * 100 + '%"></i></span><span class="n">' + (sm.dist[k] || 0) + "</span></button>").join("") + "</div>";
    $("rvScore").querySelectorAll("[data-star]").forEach((b) => b.addEventListener("click", () => { const k = +b.dataset.star; S.star = S.star === k ? 0 : k; load(true); }));
    $("rvFilter").innerHTML = S.star ? '<button type="button" class="chip-x" id="rvClr">' + esc(fmt(X.filtered, { s: S.star })) + ' <i class="fa-solid fa-xmark"></i></button>' : "";
    if (S.star) $("rvClr").addEventListener("click", () => { S.star = 0; load(true); });
    document.querySelectorAll("[data-sort]").forEach((b) => b.classList.toggle("on", b.dataset.sort === S.sort));
  }

  function card(r) {
    const long = r.body.length > 420;
    return '<article class="rv-card' + (S.mine && S.mine.id === r.id ? " mine" : "") + '">' +
      '<header>' + avatar(r) + '<div class="who"><b>' + esc(r.name) + "</b>" +
        '<span class="tags">' + (r.student ? '<span class="tag st"><i class="fa-solid fa-user-graduate"></i> ' + esc(X.student) + "</span>" : "") + '<span class="tag gv"><i class="fa-brands fa-google"></i> ' + esc(X.verified) + "</span></span></div>" +
        '<time title="' + esc(new Date(r.updated).toLocaleString()) + '">' + esc(when(r.updated)) + (r.updated - r.created > 60000 ? " · " + esc(X.edited) : "") + "</time></header>" +
      starRow(r.rating) + (r.title ? "<h3>" + esc(r.title) + "</h3>" : "") +
      '<p class="body' + (long ? " clamp" : "") + '">' + textHTML(r.body) + "</p>" +
      (long ? '<button type="button" class="rv-read" data-more>' + esc(X.readMore) + "</button>" : "") +
      (r.reply ? '<div class="rv-reply"><b><img src="assets/img/logo.webp" alt=""> ' + esc(X.reply) + "</b><p>" + textHTML(r.reply) + "</p></div>" : "") +
      "</article>";
  }
  function drawList() {
    const list = $("rvList");
    if (S.loading && !S.items.length) { list.innerHTML = Array.from({ length: 4 }, () => '<div class="rv-card sk"><i></i><i></i><i></i></div>').join(""); return; }
    list.innerHTML = S.items.length ? S.items.map(card).join("") : '<div class="rv-empty"><i class="fa-regular fa-comments"></i><p>' + esc(S.star ? X.noReviews : X.beFirst) + "</p></div>";
    $("rvMore").hidden = !S.more;
    const total = S.star ? S.summary.dist[S.star] || 0 : S.summary.count;
    $("rvShowing").textContent = S.items.length && total ? fmt(X.showing, { a: S.items.length, b: total }) : "";
  }
  $("rvList").addEventListener("click", (e) => {
    const b = e.target.closest("[data-more]"); if (!b) return;
    const p = b.previousElementSibling; const open = p.classList.toggle("clamp") === false;
    b.textContent = open ? X.less : X.readMore;
  });

  function load(reset) {
    if (!SRV) return;
    if (reset) { S.items = []; S.more = false; }
    S.loading = true; drawList();
    const btn = $("rvMore"); btn.disabled = true;
    SRV.reviews({ sort: S.sort, star: S.star, offset: S.items.length, limit: 10 }).then((d) => {
      S.summary = d.summary; S.items = S.items.concat(d.items || []); S.more = !!d.more;
    }).catch(() => {}).then(() => { S.loading = false; btn.disabled = false; drawScore(); drawList(); });
  }
  document.querySelectorAll("[data-sort]").forEach((b) => b.addEventListener("click", () => { if (S.sort === b.dataset.sort) return; S.sort = b.dataset.sort; load(true); }));
  $("rvMore").addEventListener("click", () => load(false));

  /* ---------- write a review ---------- */
  function drawWrite(msg, isErr) {
    const w = $("rvWrite");
    const note = msg ? '<div class="rv-msg' + (isErr ? " err" : "") + '"><i class="fa-solid fa-' + (isErr ? "circle-exclamation" : "circle-check") + '"></i> ' + esc(msg) + "</div>" : "";
    if (!S.me) {
      w.innerHTML = '<div class="rv-sign"><div class="ic"><i class="fa-solid fa-pen-nib"></i></div><div class="tx"><h2>' + esc(X.writeTitle) + "</h2><p>" + esc(X.signNote) + "</p>" + note +
        '<div id="rvG" class="rv-g"></div></div></div>';
      if (C.google.on()) C.google.load().then((gid) => {
        gid.initialize({ client_id: C.google.clientId, callback: (r) => signedIn(r.credential), ux_mode: "popup", auto_select: false });
        gid.renderButton($("rvG"), { type: "standard", theme: "filled_blue", size: "large", shape: "pill", text: "continue_with", locale: L, width: 300 });
      }).catch(() => { $("rvG").innerHTML = '<p class="sub">' + esc(X.googleMissing) + "</p>"; });
      else $("rvG").innerHTML = '<p class="sub">' + esc(X.googleMissing) + "</p>";
      return;
    }
    const m = S.mine;
    if (!S.rating && m) S.rating = m.rating;
    w.innerHTML =
      '<div class="rv-form-head">' + avatar(S.me, true) + '<div><b>' + esc(S.me.name) + '</b><span><i class="fa-brands fa-google"></i> ' + esc(X.signed) + "</span></div>" +
        (m ? '<span class="rv-yours">' + esc(X.yours) + "</span>" : "") + "</div>" + note +
      (m && m.status === "hidden" ? '<div class="rv-msg err"><i class="fa-solid fa-eye-slash"></i> ' + esc(X.hidden) + "</div>" : "") +
      '<form id="rvForm"><label class="lbl">' + esc(X.yourRating) + '</label><div class="rv-pick" id="rvPick" role="radiogroup">' +
        [1, 2, 3, 4, 5].map((k) => '<button type="button" role="radio" aria-checked="' + (S.rating === k) + '" data-r="' + k + '" aria-label="' + k + " – " + esc(X.rate[k - 1]) + '"><i class="fa-solid fa-star"></i></button>').join("") +
        '<span class="lbl2" id="rvPickL">' + esc(S.rating ? X.rate[S.rating - 1] : X.tapStar) + "</span></div>" +
      '<div class="field"><label for="rvT">' + esc(X.tTitle) + '</label><input class="input" id="rvT" maxlength="100" placeholder="' + esc(X.tTitlePh) + '" value="' + esc(m ? m.title : "") + '"></div>' +
      '<div class="field"><label for="rvB">' + esc(X.tBody) + ' *</label><textarea class="input" id="rvB" maxlength="1500" rows="5" placeholder="' + esc(X.tBodyPh) + '">' + esc(m ? m.body : "") + '</textarea><span class="cnt" id="rvCnt"></span></div>' +
      '<div class="rv-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> ' + esc(m ? X.update : X.submit) + "</button>" +
        (m ? '<button type="button" class="btn btn-ghost rv-del" id="rvDel"><i class="fa-solid fa-trash"></i> ' + esc(X.del) + "</button>" : "") + "</div></form>";
    const pick = $("rvPick"), lbl = $("rvPickL");
    const paint = (v) => pick.querySelectorAll("[data-r]").forEach((b) => b.classList.toggle("on", +b.dataset.r <= v));
    paint(S.rating);
    pick.querySelectorAll("[data-r]").forEach((b) => {
      b.addEventListener("mouseenter", () => { paint(+b.dataset.r); lbl.textContent = X.rate[b.dataset.r - 1]; });
      b.addEventListener("click", () => { S.rating = +b.dataset.r; pick.querySelectorAll("[data-r]").forEach((x) => x.setAttribute("aria-checked", String(+x.dataset.r === S.rating))); paint(S.rating); lbl.textContent = X.rate[S.rating - 1]; b.classList.add("pop"); setTimeout(() => b.classList.remove("pop"), 300); });
    });
    pick.addEventListener("mouseleave", () => { paint(S.rating); lbl.textContent = S.rating ? X.rate[S.rating - 1] : X.tapStar; });
    const ta = $("rvB"), cnt = $("rvCnt"); const upd = () => (cnt.textContent = ta.value.length + " / 1500"); ta.addEventListener("input", upd); upd();
    $("rvForm").addEventListener("submit", (e) => {
      e.preventDefault();
      if (!S.rating) return drawWrite(X.pickRating, true);
      const body = ta.value.trim(); if (body.length < 10) { const t0 = $("rvT").value; drawWrite(X.tooShort, true); $("rvT").value = t0; $("rvB").value = body; $("rvB").focus(); return; }
      const btn = e.target.querySelector("button[type=submit]"); btn.disabled = true; btn.insertAdjacentHTML("afterbegin", '<i class="fa-solid fa-spinner fa-spin"></i> ');
      const wasNew = !S.mine;
      SRV.reviewSave({ credential: S.cred, rating: S.rating, title: $("rvT").value.trim(), body, lang: L }).then((d) => {
        S.summary = d.summary;
        return SRV.reviewMine(S.cred).then((x) => { S.mine = x.review; drawWrite(wasNew ? X.thanks : X.updated); load(true); });
      }).catch(fail);
    });
    if (m) $("rvDel").addEventListener("click", () => {
      if (!confirm(X.delConfirm)) return;
      SRV.reviewDelete(S.cred).then((d) => { S.summary = d.summary; S.mine = null; S.rating = 0; drawWrite(X.deleted); load(true); }).catch(fail);
    });
  }
  function fail(er) {
    const msg = (er && er.message) || "";
    if (/expired|sign in|verified|Invalid Google/i.test(msg)) { S.me = null; S.cred = ""; return drawWrite(X.signAgain, true); }
    if (msg === "short") return drawWrite(X.tooShort, true);
    if (msg === "rating") return drawWrite(X.pickRating, true);
    drawWrite(msg || C.t("netErr"), true);
  }
  function signedIn(credential) {
    S.cred = credential;
    $("rvWrite").innerHTML = '<p class="rv-wait"><i class="fa-solid fa-spinner fa-spin"></i></p>';
    SRV.reviewMine(credential).then((d) => { S.me = d.me; S.mine = d.review; S.rating = d.review ? d.review.rating : 0; drawWrite(); $("rvWrite").scrollIntoView({ behavior: "smooth", block: "start" }); }).catch(fail);
  }

  /* ---------- start ---------- */
  if (!SRV) {
    $("rvScore").innerHTML = '<div class="big"><b>–</b><span>/ 5</span></div>' + starRow(0, "lg");
    $("rvWrite").innerHTML = '<div class="rv-sign"><div class="ic"><i class="fa-solid fa-cloud"></i></div><div class="tx"><h2>' + esc(X.writeTitle) + "</h2><p>" + esc(X.offline) + "</p></div></div>";
    $("rvList").innerHTML = '<div class="rv-empty"><i class="fa-regular fa-comments"></i><p>' + esc(X.offline) + "</p></div>";
    document.querySelector(".rv-bar").hidden = true;
    return;
  }
  drawScore(); drawWrite(); load(true);
})();
