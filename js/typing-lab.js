/* =========================================================
   COPA MCQ – Typing Lab
   Modules: Key Trainer · Typing Test · Typing Race · Balloon Pop · My Progress
   Typing language: English / मराठी (built-in Inscript keyboard on computers)
   Difficulty: Easy / Medium / Hard.  Works with mouse, keyboard and touch.
   Progress is kept in this browser (localStorage).
   ========================================================= */
(function () {
  "use strict";
  const C = window.COPA, esc = C.esc, L = C.lang;
  C.renderShell("typing");
  const DATA = window.COPA_TYPING;
  const root = document.getElementById("typing");
  const TOUCH = window.matchMedia("(pointer: coarse)").matches;

  /* ---------- storage ---------- */
  const ls = {
    get(k, d) { try { const v = localStorage.getItem("copa_ty_" + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem("copa_ty_" + k, JSON.stringify(v)); } catch (e) { /* private mode */ } }
  };
  const ST = { lang: ls.get("lang", L === "mr" ? "mr" : "en"), lvl: ls.get("lvl", "easy"), ins: ls.get("ins", !TOUCH), snd: ls.get("snd", true), mins: ls.get("mins", 1) };
  if (["en", "mr"].indexOf(ST.lang) < 0) ST.lang = "en";
  if (["easy", "medium", "hard"].indexOf(ST.lvl) < 0) ST.lvl = "easy";
  const hist = () => ls.get("hist", []);
  const addHist = (r) => { const h = hist(); h.unshift(Object.assign({ d: Date.now(), l: ST.lang, v: ST.lvl }, r)); ls.set("hist", h.slice(0, 200)); };

  /* ---------- UI text ---------- */
  const TX = {
    en: {
      title: "Typing Lab", sub: "Learn touch typing in English & Marathi – lessons, timed tests, races and games. Free, on mobile and computer.",
      tlang: "Typing language", lvl: "Difficulty level", easy: "Easy", medium: "Medium", hard: "Hard",
      easyS: "Home row · simple words", mediumS: "All letters · sentences", hardS: "Numbers · symbols · technical text",
      modules: "Choose a module", start: "Start", back: "All modules",
      m: { learn: ["Key Trainer", "Learn every key with the finger guide – rows, numbers and symbols, lesson by lesson.", "Lessons"],
        test: ["Typing Test", "1, 2 or 5 minute timed test with Net WPM, Gross WPM, accuracy and errors – exam style.", "Timed test"],
        race: ["Typing Race", "Race your car against 3 robot cars. Type fast and correct to win the cup!", "Game"],
        pop: ["Balloon Pop", "Balloons fly up – type the letter or word on each balloon to pop it before it escapes.", "Game"],
        progress: ["My Progress", "Your best speed, accuracy, history chart and badges.", "Stats"] },
      best: "Best speed", tests: "Tests", acc: "Accuracy", minutes: "Practice min", goal: "Your goal", goalS: "Net WPM with 90%+ accuracy at this level",
      tip: "Sit straight, keep your wrists up, and rest your fingers on the home row (A S D F – J K L ;). Look at the screen, not at the keys.",
      tipMr: "Marathi uses the Inscript keyboard. On a computer just type – the Lab converts your keys to Marathi automatically. On a phone, use a Marathi keyboard (e.g. Gboard → मराठी).",
      wpm: "WPM", net: "Net WPM", gross: "Gross WPM", cpm: "CPM", errors: "Errors", time: "Time", keys: "Keys/min", fixed: "Corrected",
      tapStart: "Tap here and start typing", clickStart: "Click here and start typing", timer: "min", restart: "Restart", newText: "New text", passage: "Passage",
      result: "Your result", great: "Excellent! Goal reached 🎉", keep: "Good try! Keep practising.", again: "Try again", next: "Next",
      ins: "Built-in Marathi (Inscript) keyboard", insS: "Turn off if you use your own Marathi keyboard", sound: "Sound",
      lesson: "Lesson", lessons: "Lessons", finger: "Use", fingers: { lp: "left little finger", lr: "left ring finger", lm: "left middle finger", li: "left index finger", ri: "right index finger", rm: "right middle finger", rr: "right ring finger", rp: "right little finger", th: "thumb" },
      press: "Press", done: "Lesson complete!", space: "Space", stars: "stars",
      lsn: { easy: ["Home row – left hand", "Home row – right hand", "Full home row + G H", "Reach keys E · I · R · U"], medium: ["Top row – left", "Top row – right", "Bottom row", "All letters"], hard: ["Number row", "Shift keys", "Punctuation & symbols", "Everything"] },
      race: "Race", you: "You", go: "GO!", place: ["1st", "2nd", "3rd", "4th"], win: "You won the race! 🏆", lose: "Finished – practise and race again!", bots: "Robot speeds",
      popTitle: "Balloon Pop", score: "Score", lives: "Lives", level: "Level", play: "Play", gameOver: "Game over", bestScore: "Best score", typeHere: "Type here…", popped: "Popped", missed: "Missed",
      useOsk: "On-screen keyboard", usePhone: "Phone keyboard", paused: "Paused", resume: "Resume",
      noHist: "No tests yet – take your first typing test!", history: "Recent activity", chart: "Last tests (Net WPM)", badges: "Badges", clear: "Clear my progress", clearQ: "Delete all your typing progress?",
      date: "Date", mode: "Module", lang: "Lang", bestPop: "Balloon best", lessonsDone: "Lessons done",
      bd: [["First test", "Complete a typing test"], ["20 WPM club", "Net 20 WPM or more"], ["30 WPM club", "Net 30 WPM or more"], ["40 WPM club", "Net 40 WPM or more"], ["Sharp shooter", "98% accuracy in a test"], ["Race winner", "Win a typing race"], ["Balloon master", "Score 300 in Balloon Pop"], ["Bilingual", "Practise in English and Marathi"], ["Hard worker", "10 tests or races"]]
    },
    hi: {
      title: "टाइपिंग लैब", sub: "अंग्रेज़ी और मराठी में टच टाइपिंग सीखें – पाठ, टाइम्ड टेस्ट, रेस और गेम। मुफ़्त, मोबाइल और कंप्यूटर पर।",
      tlang: "टाइपिंग भाषा", lvl: "कठिनाई स्तर", easy: "आसान", medium: "मध्यम", hard: "कठिन",
      easyS: "होम रो · सरल शब्द", mediumS: "सभी अक्षर · वाक्य", hardS: "अंक · चिह्न · तकनीकी पाठ",
      modules: "मॉड्यूल चुनें", start: "शुरू करें", back: "सभी मॉड्यूल",
      m: { learn: ["की ट्रेनर", "उंगली गाइड के साथ हर कुंजी सीखें – रो, अंक और चिह्न, पाठ-दर-पाठ।", "पाठ"],
        test: ["टाइपिंग टेस्ट", "1, 2 या 5 मिनट का टेस्ट – नेट WPM, ग्रॉस WPM, सटीकता और गलतियाँ – परीक्षा जैसा।", "टाइम्ड टेस्ट"],
        race: ["टाइपिंग रेस", "3 रोबोट कारों से रेस करें। तेज़ और सही टाइप करें और कप जीतें!", "गेम"],
        pop: ["बलून पॉप", "गुब्बारे ऊपर उड़ते हैं – उन पर लिखा अक्षर या शब्द टाइप करके फोड़ें।", "गेम"],
        progress: ["मेरी प्रगति", "आपकी सर्वश्रेष्ठ गति, सटीकता, चार्ट और बैज।", "आँकड़े"] },
      best: "सर्वश्रेष्ठ गति", tests: "टेस्ट", acc: "सटीकता", minutes: "अभ्यास मिनट", goal: "आपका लक्ष्य", goalS: "इस स्तर पर 90%+ सटीकता के साथ नेट WPM",
      tip: "सीधे बैठें, कलाई ऊपर रखें और उंगलियाँ होम रो (A S D F – J K L ;) पर रखें। कुंजियों को नहीं, स्क्रीन को देखें।",
      tipMr: "मराठी के लिए Inscript कीबोर्ड है। कंप्यूटर पर बस टाइप करें – लैब अपने-आप मराठी में बदल देगा। फ़ोन पर मराठी कीबोर्ड (जैसे Gboard → मराठी) चुनें।",
      wpm: "WPM", net: "नेट WPM", gross: "ग्रॉस WPM", cpm: "CPM", errors: "गलतियाँ", time: "समय", keys: "कुंजी/मिनट", fixed: "सुधारी",
      tapStart: "यहाँ टैप करें और टाइप करना शुरू करें", clickStart: "यहाँ क्लिक करें और टाइप करना शुरू करें", timer: "मिनट", restart: "फिर से", newText: "नया पाठ", passage: "पाठ",
      result: "आपका परिणाम", great: "शानदार! लक्ष्य पूरा 🎉", keep: "अच्छा प्रयास! अभ्यास जारी रखें।", again: "फिर कोशिश करें", next: "अगला",
      ins: "अंतर्निहित मराठी (Inscript) कीबोर्ड", insS: "अपना मराठी कीबोर्ड इस्तेमाल करते हों तो बंद करें", sound: "आवाज़",
      lesson: "पाठ", lessons: "पाठ", finger: "उपयोग करें", fingers: { lp: "बाएँ हाथ की छोटी उंगली", lr: "बाएँ हाथ की अनामिका", lm: "बाएँ हाथ की मध्यमा", li: "बाएँ हाथ की तर्जनी", ri: "दाएँ हाथ की तर्जनी", rm: "दाएँ हाथ की मध्यमा", rr: "दाएँ हाथ की अनामिका", rp: "दाएँ हाथ की छोटी उंगली", th: "अंगूठा" },
      press: "दबाएँ", done: "पाठ पूरा!", space: "स्पेस", stars: "स्टार",
      lsn: { easy: ["होम रो – बायाँ हाथ", "होम रो – दायाँ हाथ", "पूरी होम रो + G H", "E · I · R · U कुंजियाँ"], medium: ["टॉप रो – बायाँ", "टॉप रो – दायाँ", "बॉटम रो", "सभी अक्षर"], hard: ["नंबर रो", "शिफ्ट कुंजियाँ", "विराम और चिह्न", "सब कुछ"] },
      race: "रेस", you: "आप", go: "चलो!", place: ["पहला", "दूसरा", "तीसरा", "चौथा"], win: "आप रेस जीत गए! 🏆", lose: "रेस पूरी – अभ्यास करें और फिर से रेस करें!", bots: "रोबोट गति",
      popTitle: "बलून पॉप", score: "स्कोर", lives: "जीवन", level: "लेवल", play: "खेलें", gameOver: "गेम ओवर", bestScore: "सर्वश्रेष्ठ स्कोर", typeHere: "यहाँ टाइप करें…", popped: "फोड़े", missed: "छूटे",
      useOsk: "स्क्रीन कीबोर्ड", usePhone: "फ़ोन कीबोर्ड", paused: "रुका हुआ", resume: "जारी रखें",
      noHist: "अभी कोई टेस्ट नहीं – पहला टाइपिंग टेस्ट दें!", history: "हाल की गतिविधि", chart: "पिछले टेस्ट (नेट WPM)", badges: "बैज", clear: "मेरी प्रगति मिटाएँ", clearQ: "आपकी सारी टाइपिंग प्रगति मिटाएँ?",
      date: "तारीख", mode: "मॉड्यूल", lang: "भाषा", bestPop: "बलून सर्वश्रेष्ठ", lessonsDone: "पूरे पाठ",
      bd: [["पहला टेस्ट", "एक टाइपिंग टेस्ट पूरा करें"], ["20 WPM क्लब", "नेट 20 WPM या अधिक"], ["30 WPM क्लब", "नेट 30 WPM या अधिक"], ["40 WPM क्लब", "नेट 40 WPM या अधिक"], ["सटीक निशाना", "टेस्ट में 98% सटीकता"], ["रेस विजेता", "एक रेस जीतें"], ["बलून मास्टर", "बलून पॉप में 300 स्कोर"], ["द्विभाषी", "अंग्रेज़ी और मराठी दोनों में अभ्यास"], ["मेहनती", "10 टेस्ट या रेस"]]
    },
    mr: {
      title: "टायपिंग लॅब", sub: "इंग्रजी आणि मराठीत टच टायपिंग शिका – धडे, टाइम टेस्ट, रेस आणि गेम्स. मोफत, मोबाईल आणि कॉम्प्युटरवर.",
      tlang: "टायपिंग भाषा", lvl: "काठिण्य पातळी", easy: "सोपे", medium: "मध्यम", hard: "कठीण",
      easyS: "होम रो · सोपे शब्द", mediumS: "सर्व अक्षरे · वाक्ये", hardS: "अंक · चिन्हे · तांत्रिक मजकूर",
      modules: "मॉड्यूल निवडा", start: "सुरू करा", back: "सर्व मॉड्यूल",
      m: { learn: ["की ट्रेनर", "बोटांच्या मार्गदर्शनासह प्रत्येक की शिका – रो, अंक आणि चिन्हे, धड्यानुसार.", "धडे"],
        test: ["टायपिंग टेस्ट", "1, 2 किंवा 5 मिनिटांची टेस्ट – नेट WPM, ग्रॉस WPM, अचूकता आणि चुका – परीक्षेसारखी.", "टाइम टेस्ट"],
        race: ["टायपिंग रेस", "3 रोबोट गाड्यांशी शर्यत लावा. वेगाने आणि बरोबर टाइप करा आणि कप जिंका!", "गेम"],
        pop: ["बलून पॉप", "फुगे वर उडतात – फुग्यावरील अक्षर किंवा शब्द टाइप करून तो फोडा.", "गेम"],
        progress: ["माझी प्रगती", "तुमचा सर्वोत्तम वेग, अचूकता, चार्ट आणि बॅज.", "आकडेवारी"] },
      best: "सर्वोत्तम वेग", tests: "टेस्ट", acc: "अचूकता", minutes: "सराव मिनिटे", goal: "तुमचे लक्ष्य", goalS: "या पातळीवर 90%+ अचूकतेसह नेट WPM",
      tip: "ताठ बसा, मनगटे वर ठेवा आणि बोटे होम रो (A S D F – J K L ;) वर ठेवा. कीजकडे नाही, स्क्रीनकडे पहा.",
      tipMr: "मराठीसाठी Inscript कीबोर्ड वापरला जातो. कॉम्प्युटरवर फक्त टाइप करा – लॅब आपोआप मराठी अक्षरे देईल. फोनवर मराठी कीबोर्ड (उदा. Gboard → मराठी) निवडा.",
      wpm: "WPM", net: "नेट WPM", gross: "ग्रॉस WPM", cpm: "CPM", errors: "चुका", time: "वेळ", keys: "की/मिनिट", fixed: "दुरुस्त",
      tapStart: "इथे टॅप करा आणि टायपिंग सुरू करा", clickStart: "इथे क्लिक करा आणि टायपिंग सुरू करा", timer: "मिनिट", restart: "पुन्हा", newText: "नवीन मजकूर", passage: "उतारा",
      result: "तुमचा निकाल", great: "उत्कृष्ट! लक्ष्य पूर्ण 🎉", keep: "छान प्रयत्न! सराव सुरू ठेवा.", again: "पुन्हा प्रयत्न करा", next: "पुढील",
      ins: "अंगभूत मराठी (Inscript) कीबोर्ड", insS: "तुमचा स्वतःचा मराठी कीबोर्ड वापरत असाल तर बंद करा", sound: "आवाज",
      lesson: "धडा", lessons: "धडे", finger: "वापरा", fingers: { lp: "डाव्या हाताची करंगळी", lr: "डाव्या हाताची अनामिका", lm: "डाव्या हाताचे मधले बोट", li: "डाव्या हाताची तर्जनी", ri: "उजव्या हाताची तर्जनी", rm: "उजव्या हाताचे मधले बोट", rr: "उजव्या हाताची अनामिका", rp: "उजव्या हाताची करंगळी", th: "अंगठा" },
      press: "दाबा", done: "धडा पूर्ण!", space: "स्पेस", stars: "स्टार",
      lsn: { easy: ["होम रो – डावा हात", "होम रो – उजवा हात", "पूर्ण होम रो + G H", "E · I · R · U की"], medium: ["टॉप रो – डावा", "टॉप रो – उजवा", "बॉटम रो", "सर्व अक्षरे"], hard: ["नंबर रो", "शिफ्ट की", "विरामचिन्हे आणि चिन्हे", "सर्व काही"] },
      race: "रेस", you: "तुम्ही", go: "चला!", place: ["पहिला", "दुसरा", "तिसरा", "चौथा"], win: "तुम्ही रेस जिंकलात! 🏆", lose: "रेस पूर्ण – सराव करा आणि पुन्हा रेस लावा!", bots: "रोबोट वेग",
      popTitle: "बलून पॉप", score: "स्कोअर", lives: "जीव", level: "लेव्हल", play: "खेळा", gameOver: "गेम संपला", bestScore: "सर्वोत्तम स्कोअर", typeHere: "इथे टाइप करा…", popped: "फोडले", missed: "सुटले",
      useOsk: "स्क्रीन कीबोर्ड", usePhone: "फोन कीबोर्ड", paused: "थांबवले", resume: "पुढे चालू",
      noHist: "अजून टेस्ट नाही – तुमची पहिली टायपिंग टेस्ट द्या!", history: "अलीकडील सराव", chart: "मागील टेस्ट (नेट WPM)", badges: "बॅज", clear: "माझी प्रगती पुसा", clearQ: "तुमची सर्व टायपिंग प्रगती पुसायची?",
      date: "तारीख", mode: "मॉड्यूल", lang: "भाषा", bestPop: "बलून सर्वोत्तम", lessonsDone: "पूर्ण धडे",
      bd: [["पहिली टेस्ट", "एक टायपिंग टेस्ट पूर्ण करा"], ["20 WPM क्लब", "नेट 20 WPM किंवा जास्त"], ["30 WPM क्लब", "नेट 30 WPM किंवा जास्त"], ["40 WPM क्लब", "नेट 40 WPM किंवा जास्त"], ["अचूक नेम", "टेस्टमध्ये 98% अचूकता"], ["रेस विजेता", "एक रेस जिंका"], ["बलून मास्टर", "बलून पॉपमध्ये 300 स्कोअर"], ["द्विभाषिक", "इंग्रजी आणि मराठी दोन्हीत सराव"], ["मेहनती", "10 टेस्ट किंवा रेस"]]
    }
  };
  const X = TX[L] || TX.en;

  /* ---------- keyboard layout: [code, en, EN(shift), mr, MR(shift), finger] ---------- */
  const ROWS = [
    ["Backquote Digit1 Digit2 Digit3 Digit4 Digit5 Digit6 Digit7 Digit8 Digit9 Digit0 Minus Equal", "`1234567890-=", "~!@#$%^&*()_+",
      ["ॊ", "१", "२", "३", "४", "५", "६", "७", "८", "९", "०", "-", "ृ"], ["ऒ", "ऍ", "ॅ", "्र", "र्", "ज्ञ", "त्र", "क्ष", "श्र", "(", ")", "ः", "ऋ"], "lp lp lr lm li li ri ri rm rr rp rp rp"],
    ["KeyQ KeyW KeyE KeyR KeyT KeyY KeyU KeyI KeyO KeyP BracketLeft BracketRight Backslash", "qwertyuiop[]\\", "QWERTYUIOP{}|",
      ["ौ", "ै", "ा", "ी", "ू", "ब", "ह", "ग", "द", "ज", "ड", "़", "ॉ"], ["औ", "ऐ", "आ", "ई", "ऊ", "भ", "ङ", "घ", "ध", "झ", "ढ", "ञ", "ऑ"], "lp lr lm li li ri ri rm rr rp rp rp rp"],
    ["KeyA KeyS KeyD KeyF KeyG KeyH KeyJ KeyK KeyL Semicolon Quote", "asdfghjkl;'", "ASDFGHJKL:\"",
      ["ो", "े", "्", "ि", "ु", "प", "र", "क", "त", "च", "ट"], ["ओ", "ए", "अ", "इ", "उ", "फ", "ऱ", "ख", "थ", "छ", "ठ"], "lp lr lm li li ri ri rm rr rp rp"],
    ["KeyZ KeyX KeyC KeyV KeyB KeyN KeyM Comma Period Slash", "zxcvbnm,./", "ZXCVBNM<>?",
      ["ॆ", "ं", "म", "न", "व", "ल", "स", ",", ".", "य"], ["ऎ", "ँ", "ण", "ऩ", "ऴ", "ळ", "श", "ष", "।", "य़"], "lp lr lm li li ri ri rm rr rp"]
  ];
  const KEY = {};          // code -> {en:[n,s], mr:[n,s], f}
  ROWS.forEach((r) => {
    const codes = r[0].split(" "), fs = r[5].split(" ");
    codes.forEach((c, i) => { KEY[c] = { en: [r[1][i], r[2][i]], mr: [r[3][i], r[4][i]], f: fs[i] }; });
  });
  KEY.Space = { en: [" ", " "], mr: [" ", " "], f: "th" };
  const chOf = (code, sh, lang) => (KEY[code] ? KEY[code][lang || ST.lang][sh ? 1 : 0] : "");
  const isMark = (s) => /^[ऀ-ःऺ-ॏ॑-ॗॢॣ]/.test(s);
  const show = (s) => (s === " " ? "␣" : isMark(s) ? "◌" + s : s);

  /* ---------- sound ---------- */
  let AC = null;
  function beep(type) {
    if (!ST.snd) return;
    try {
      AC = AC || new (window.AudioContext || window.webkitAudioContext)();
      const o = AC.createOscillator(), g = AC.createGain(), t0 = AC.currentTime;
      const P = { ok: [660, 0.05, 0.04], bad: [170, 0.12, 0.08], pop: [520, 0.12, 0.08], win: [880, 0.35, 0.08], tick: [440, 0.08, 0.05] }[type] || [500, 0.05, 0.04];
      o.type = type === "bad" ? "sawtooth" : "sine"; o.frequency.setValueAtTime(P[0], t0);
      if (type === "pop" || type === "win") o.frequency.exponentialRampToValueAtTime(P[0] * 2.2, t0 + P[1]);
      g.gain.setValueAtTime(P[2], t0); g.gain.exponentialRampToValueAtTime(0.0001, t0 + P[1]);
      o.connect(g); g.connect(AC.destination); o.start(t0); o.stop(t0 + P[1] + 0.02);
    } catch (e) { /* no audio */ }
  }
  function confetti() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const box = document.createElement("div"); box.className = "ty-confetti"; box.setAttribute("aria-hidden", "true");
    const cols = ["#1554e0", "#22c55e", "#f59e0b", "#e5383b", "#c026d3", "#0fa3b1"];
    for (let i = 0; i < 60; i++) {
      const s = document.createElement("i");
      s.style.cssText = "left:" + Math.random() * 100 + "%;background:" + cols[i % cols.length] + ";animation-delay:" + Math.random() * 0.6 + "s;animation-duration:" + (1.8 + Math.random() * 1.4) + "s;transform:rotate(" + Math.random() * 360 + "deg)";
      box.appendChild(s);
    }
    document.body.appendChild(box); setTimeout(() => box.remove(), 3600);
  }

  /* ---------- helpers ---------- */
  const GOAL = { en: { easy: 15, medium: 25, hard: 35 }, mr: { easy: 10, medium: 18, hard: 25 } };
  const goal = () => GOAL[ST.lang][ST.lvl];
  const pick = (a) => a[Math.floor(Math.random() * a.length)];
  const shuffle = (a) => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  const segs = (s) => (window.Intl && Intl.Segmenter ? Array.from(new Intl.Segmenter(undefined, { granularity: "grapheme" }).segment(s), (x) => x.segment) : Array.from(s));
  const fmt = (sec) => Math.floor(sec / 60) + ":" + String(Math.floor(sec % 60)).padStart(2, "0");
  const LVLS = [["easy", "fa-seedling", "#16a34a"], ["medium", "fa-fire", "#f59e0b"], ["hard", "fa-bolt", "#e5383b"]];
  const MODS = [["learn", "fa-keyboard", "#0f766e", "#2dd4bf"], ["test", "fa-stopwatch", "#be123c", "#fb7185"], ["race", "fa-flag-checkered", "#a16207", "#facc15"], ["pop", "fa-burst", "#0369a1", "#38bdf8"], ["progress", "fa-chart-line", "#334155", "#94a3b8"]];
  /* small live previews drawn on each module card */
  const PV = {
    learn: () => '<div class="pk">' + ["QWERT", "ASDFG", "ZXCVB"].map((r) => "<span>" + r.split("").map((c) => "<i" + (c === "F" ? ' class="on"' : "") + ">" + c + "</i>").join("") + "</span>").join("") + "</div>",
    test: () => '<div class="pt"><span class="wr"><b>32</b><small>WPM</small></span><span class="ln"><i style="width:92%"></i><i style="width:70%"></i><i style="width:84%"></i></span></div>',
    race: () => '<div class="pr">' + ["#a16207", "#64748b", "#94a3b8"].map((c, i) => '<span><i class="fa-solid fa-car-side" style="color:' + c + ";left:" + [62, 40, 22][i] + '%"></i><em class="fa-solid fa-flag-checkered"></em></span>').join("") + "</div>",
    pop: () => '<div class="pp">' + [["k", 12, 0], ["जल", 44, 1], ["web", 74, 2]].map((b) => '<span style="left:' + b[1] + "%;animation-delay:-" + b[2] + 's">' + b[0] + "</span>").join("") + "</div>",
    progress: () => '<div class="pg2">' + [30, 45, 40, 62, 78].map((h, i) => '<i style="height:' + h + '%' + (i === 4 ? ';background:var(--b)' : "") + '"></i>').join("") + '<span class="fa-solid fa-star"></span></div>'
  };
  let cleanup = null;          // stops timers / listeners of the open module

  /* ---------- settings bar (language + difficulty) ---------- */
  function settings() {
    return '<div class="ty-set"><div class="grp"><span class="lb"><i class="fa-solid fa-language"></i> ' + esc(X.tlang) + '</span><div class="seg" data-set="lang">' +
      '<button type="button" data-v="en" class="' + (ST.lang === "en" ? "on" : "") + '"><b>Aa</b> English</button><button type="button" data-v="mr" class="' + (ST.lang === "mr" ? "on" : "") + '"><b>अ</b> मराठी</button></div></div>' +
      '<div class="grp"><span class="lb"><i class="fa-solid fa-signal"></i> ' + esc(X.lvl) + '</span><div class="seg lv" data-set="lvl">' +
      LVLS.map((l) => '<button type="button" data-v="' + l[0] + '" class="' + (ST.lvl === l[0] ? "on" : "") + '" style="--c:' + l[2] + '"><i class="fa-solid ' + l[1] + '"></i> ' + esc(X[l[0]]) + "</button>").join("") + "</div></div>" +
      '<button type="button" class="ty-snd" data-snd aria-label="' + esc(X.sound) + '" title="' + esc(X.sound) + '"><i class="fa-solid ' + (ST.snd ? "fa-volume-high" : "fa-volume-xmark") + '"></i></button></div>';
  }
  root.addEventListener("click", (e) => {
    const b = e.target.closest(".ty-set [data-v]");
    if (b) {
      const k = b.parentNode.dataset.set; ST[k] = b.dataset.v; ls.set(k, ST[k]); route(); return;
    }
    if (e.target.closest("[data-snd]")) { ST.snd = !ST.snd; ls.set("snd", ST.snd); e.target.closest("[data-snd]").innerHTML = '<i class="fa-solid ' + (ST.snd ? "fa-volume-high" : "fa-volume-xmark") + '"></i>'; }
  });

  function modHead(k) {
    const m = MODS.find((x) => x[0] === k), T = X.m[k];
    return '<div class="ty-mhead" style="--g1:' + m[2] + ";--g2:" + m[3] + '"><a class="ty-back" href="#"><i class="fa-solid fa-arrow-left"></i> <span>' + esc(X.back) + "</span></a>" +
      '<div class="ty-mt"><span class="ic"><i class="fa-solid ' + m[1] + '"></i></span><div><h1>' + esc(T[0]) + "</h1><p>" + esc(T[1]) + "</p></div></div>" +
      '<nav class="ty-mtabs">' + MODS.map((x) => '<a href="#' + x[0] + '" class="' + (x[0] === k ? "on" : "") + '" style="--c:' + x[2] + '" title="' + esc(X.m[x[0]][0]) + '"><i class="fa-solid ' + x[1] + '"></i><span>' + esc(X.m[x[0]][0]) + "</span></a>").join("") + "</nav></div>" + settings();
  }

  /* ---------- HOME ---------- */
  function home() {
    const h = hist(), tests = h.filter((r) => r.m === "test" || r.m === "race");
    const best = tests.filter((r) => r.l === ST.lang).reduce((a, r) => Math.max(a, r.w || 0), 0);
    const acc = tests.length ? Math.round(tests.slice(0, 10).reduce((a, r) => a + (r.a || 0), 0) / Math.min(10, tests.length)) : 0;
    const mins = Math.round(h.reduce((a, r) => a + (r.t || 0), 0) / 60);
    const kb = ROWS.slice(1).map((r, i) => '<div class="r r' + i + '">' + r[0].split(" ").slice(0, 10 - i).map((c) => '<span class="' + KEY[c].f + '">' + esc((ST.lang === "mr" ? KEY[c].mr[0] : KEY[c].en[0]).toUpperCase()) + "</span>").join("") + "</div>").join("");
    root.innerHTML =
      '<section class="ty-hero"><div class="in"><span class="kick"><i class="fa-solid fa-keyboard"></i> COPA · Typing Lab</span><h1>' + esc(X.title) + "</h1><p>" + esc(X.sub) + "</p>" +
        '<div class="ty-stats"><div><b>' + best + '</b><small>' + esc(X.best) + " (" + esc(X.wpm) + ')</small></div><div><b>' + tests.length + "</b><small>" + esc(X.tests) + "</small></div><div><b>" + (acc ? acc + "%" : "–") + "</b><small>" + esc(X.acc) + "</small></div><div><b>" + mins + "</b><small>" + esc(X.minutes) + "</small></div></div></div>" +
        '<div class="ty-art" aria-hidden="true"><div class="kb">' + kb + '</div><span class="f1">WPM</span><span class="f2">' + (ST.lang === "mr" ? "क" : "A") + '</span><span class="f3"><i class="fa-solid fa-trophy"></i></span></div></section>' +
      settings() +
      '<div class="ty-goal"><span class="ic"><i class="fa-solid fa-bullseye"></i></span><div><b>' + esc(X.goal) + ": " + goal() + " " + esc(X.net) + "</b><small>" + esc(X.goalS) + " · " + esc(X[ST.lvl]) + " – " + esc(X[ST.lvl + "S"]) + "</small></div></div>" +
      '<h2 class="ty-h2">' + esc(X.modules) + "</h2>" +
      '<div class="ty-mods">' + MODS.map((m, i) => '<a class="ty-mod m-' + m[0] + '" href="#' + m[0] + '" style="--a:' + m[2] + ";--b:" + m[3] + ";--d:" + i * 70 + 'ms">' +
        '<div class="pv" aria-hidden="true">' + PV[m[0]]() + '<span class="no">0' + (i + 1) + "</span></div>" +
        '<div class="bd"><span class="tag"><i class="fa-solid ' + m[1] + '"></i> ' + esc(X.m[m[0]][2]) + "</span><b>" + esc(X.m[m[0]][0]) + "</b><small>" + esc(X.m[m[0]][1]) + "</small>" +
        '<span class="ft"><span class="cta">' + esc(X.start) + '</span><span class="arr"><i class="fa-solid fa-arrow-right"></i></span></span></div></a>').join("") + "</div>" +
      '<div class="ty-tips"><div><i class="fa-solid fa-hand"></i><p>' + esc(X.tip) + "</p></div><div><i class=\"fa-solid fa-language\"></i><p>" + esc(X.tipMr) + "</p></div></div>";
  }

  /* ---------- typing engine (Typing Test + Race) ---------- */
  function typer(box, text, o) {
    box.innerHTML = '<div class="ty-text" lang="' + (ST.lang === "mr" ? "mr" : "en") + '"></div><textarea class="ty-ta" aria-label="Type here" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="' + (ST.lang === "mr" ? "mr" : "en") + '"></textarea>' +
      '<div class="ty-focus"><i class="fa-solid fa-hand-pointer"></i> ' + esc(TOUCH ? X.tapStart : X.clickStart) + "</div>";
    const tx = box.querySelector(".ty-text"), ta = box.querySelector(".ty-ta");
    const G = []; let pos = 0, word = null;
    segs(text).forEach((g) => {
      if (!word) { word = document.createElement("span"); word.className = "w"; tx.appendChild(word); }
      const s = document.createElement("span"); s.textContent = g; if (g === " ") s.className = "sp";
      word.appendChild(s); G.push({ s: pos, e: pos + g.length, el: s, st: "" }); pos += g.length;
      if (g === " ") word = null;
    });
    const S = { start: 0, keys: 0, bad: 0, fixed: 0, prev: "", done: false, correct: 0, len: 0 };
    function paint() {
      const v = ta.value; let cur = null;
      for (const g of G) {
        let st;
        if (v.length >= g.e) st = v.slice(g.s, g.e) === text.slice(g.s, g.e) ? "ok" : "bad";
        else if (v.length > g.s) st = "cur part";
        else if (v.length === g.s) st = "cur";
        else st = "";
        if (st !== g.st) { g.el.className = (g.el.textContent === " " ? "sp " : "") + st; g.st = st; }
        if (!cur && st.indexOf("cur") === 0) cur = g.el;
      }
      if (cur) {
        const lh = cur.offsetHeight || 30, top = cur.offsetTop - tx.offsetTop;
        if (top - tx.scrollTop > tx.clientHeight - lh * 1.6 || top < tx.scrollTop) tx.scrollTop = Math.max(0, top - lh * 0.6);
      }
    }
    function stats() {
      const sec = S.start ? Math.max(1, (Date.now() - S.start) / 1000) : 0, min = sec / 60 || 1 / 60;
      const v = ta.value; let ok = 0; for (let i = 0; i < v.length; i++) if (v[i] === text[i]) ok++;
      const gross = Math.round(v.length / 5 / min), err = v.length - ok;
      return { sec: Math.round(sec), gross: sec ? gross : 0, net: sec ? Math.max(0, Math.round((v.length / 5 - err) / min)) : 0, acc: S.keys ? Math.max(0, Math.round((S.keys - S.bad) / S.keys * 100)) : 100,
        err, fixed: S.fixed, cpm: sec ? Math.round(ok / min) : 0, len: v.length, ok, total: text.length, frac: ok / text.length, progress: v.length / text.length };
    }
    function onInput() {
      if (S.done) return;
      let v = ta.value.replace(/\r?\n/g, "");
      if (v.length > text.length) v = v.slice(0, text.length);
      if (v !== ta.value) ta.value = v;
      if (!S.start && v.length) { S.start = Date.now(); o.onStart && o.onStart(); }
      if (v.length > S.prev.length) {
        for (let i = S.prev.length; i < v.length; i++) { S.keys++; if (v[i] !== text[i]) { S.bad++; beep("bad"); } }
      } else if (v.length < S.prev.length) {
        for (let i = v.length; i < S.prev.length; i++) if (S.prev[i] !== text[i]) S.fixed++;
      }
      S.prev = v; paint();
      o.onProgress && o.onProgress(stats());
      if (v.length >= text.length) finish();
    }
    function finish() { if (S.done) return; S.done = true; ta.blur(); ta.disabled = true; o.onDone && o.onDone(stats()); }
    ta.addEventListener("input", onInput);
    ta.addEventListener("keydown", (e) => {
      if (/^(Arrow|Home|End|PageUp|PageDown)/.test(e.key) || e.key === "Enter" || e.key === "Tab") { e.preventDefault(); return; }
      if (ST.lang === "mr" && ST.ins && !e.ctrlKey && !e.altKey && !e.metaKey && !e.isComposing && KEY[e.code] && e.code !== "Space") {
        e.preventDefault(); ta.value += chOf(e.code, e.shiftKey, "mr"); onInput();
      }
    });
    ["paste", "drop", "cut"].forEach((ev) => ta.addEventListener(ev, (e) => e.preventDefault()));
    const end = () => { const n = ta.value.length; try { ta.setSelectionRange(n, n); } catch (e) { /* */ } };
    ["select", "click", "keyup", "focus"].forEach((ev) => ta.addEventListener(ev, end));
    ta.addEventListener("focus", () => box.classList.add("focused"));
    ta.addEventListener("blur", () => box.classList.remove("focused"));
    box.onclick = () => { if (!S.done) ta.focus({ preventScroll: true }); };
    paint();
    return { stats, finish, focus: () => ta.focus({ preventScroll: true }), lock: (on) => { ta.disabled = on; }, state: S };
  }
  function longText(minChars) {
    const P = shuffle(DATA[ST.lang][ST.lvl].p); let t = "", i = 0;
    while (t.length < minChars) { t += (t ? " " : "") + P[i % P.length]; i++; }
    return t;
  }
  const insRow = () => (ST.lang === "mr" ? '<label class="ty-ins"><input type="checkbox" id="tyIns"' + (ST.ins ? " checked" : "") + '><span class="sw"></span><span><b>' + esc(X.ins) + "</b><small>" + esc(X.insS) + "</small></span></label>" : "");
  const bindIns = () => { const c = document.getElementById("tyIns"); if (c) c.addEventListener("change", () => { ST.ins = c.checked; ls.set("ins", ST.ins); }); };

  /* ---------- TYPING TEST ---------- */
  function test() {
    const mins = ST.mins;
    root.innerHTML = modHead("test") +
      '<div class="ty-bar"><div class="seg sm" id="tyMin">' + [1, 2, 5].map((m) => '<button type="button" data-m="' + m + '" class="' + (m === mins ? "on" : "") + '">' + m + " " + esc(X.timer) + "</button>").join("") + "</div>" + insRow() +
      '<div class="acts"><button type="button" class="btn btn-ghost btn-sm" id="tyNew"><i class="fa-solid fa-rotate-right"></i> ' + esc(X.newText) + "</button></div></div>" +
      '<div class="ty-live"><div class="tm"><i class="fa-solid fa-stopwatch"></i><b id="tyT">' + fmt(mins * 60) + '</b></div><div><b id="tyW">0</b><small>' + esc(X.net) + '</small></div><div><b id="tyA">100%</b><small>' + esc(X.acc) + '</small></div><div><b id="tyE">0</b><small>' + esc(X.errors) + '</small></div><div class="pg"><span id="tyP"></span></div></div>' +
      '<div class="ty-box" id="tyBox"></div><div id="tyRes"></div>';
    bindIns();
    let tick = null, T = null;
    const box = document.getElementById("tyBox");
    function start() {
      clearInterval(tick); document.getElementById("tyRes").innerHTML = "";
      const limit = ST.mins * 60;
      document.getElementById("tyT").textContent = fmt(limit); ["tyW", "tyE"].forEach((id) => (document.getElementById(id).textContent = "0")); document.getElementById("tyA").textContent = "100%"; document.getElementById("tyP").style.width = "0";
      T = typer(box, longText(Math.max(400, ST.mins * 60 * 70 / 12)), {
        onStart() { tick = setInterval(() => { const s = T.stats(), left = Math.max(0, limit - s.sec); document.getElementById("tyT").textContent = fmt(left); document.getElementById("tyW").textContent = s.net; if (left <= 0) T.finish(); }, 250); },
        onProgress(s) { document.getElementById("tyW").textContent = s.net; document.getElementById("tyA").textContent = s.acc + "%"; document.getElementById("tyE").textContent = s.err; document.getElementById("tyP").style.width = Math.min(100, (s.sec / limit) * 100 || s.progress * 100) + "%"; },
        onDone(s) { clearInterval(tick); result(s); }
      });
    }
    function result(s) {
      const ok = s.net >= goal() && s.acc >= 90;
      addHist({ m: "test", w: s.net, g: s.gross, a: s.acc, t: s.sec });
      if (ok) { beep("win"); confetti(); }
      const r = document.getElementById("tyRes");
      r.innerHTML = '<section class="ty-result ' + (ok ? "pass" : "") + '"><div class="gauge" style="--p:' + Math.min(100, s.net / (goal() * 1.6) * 100) + '"><div><b>' + s.net + "</b><small>" + esc(X.net) + "</small></div></div>" +
        '<div class="rt"><h3>' + esc(ok ? X.great : X.keep) + "</h3><p>" + esc(X.goal) + ": " + goal() + " " + esc(X.net) + " · 90% " + esc(X.acc) + "</p>" +
        '<div class="grid"><div><b>' + s.gross + "</b><small>" + esc(X.gross) + "</small></div><div><b>" + s.acc + "%</b><small>" + esc(X.acc) + "</small></div><div><b>" + s.cpm + "</b><small>" + esc(X.cpm) + "</small></div><div><b>" + s.err + "</b><small>" + esc(X.errors) + "</small></div><div><b>" + s.fixed + "</b><small>" + esc(X.fixed) + "</small></div><div><b>" + fmt(s.sec) + "</b><small>" + esc(X.time) + "</small></div></div>" +
        '<div class="acts"><button type="button" class="btn btn-primary" data-again><i class="fa-solid fa-rotate-right"></i> ' + esc(X.again) + '</button><a class="btn btn-ghost" href="#progress"><i class="fa-solid fa-chart-line"></i> ' + esc(X.m.progress[0]) + "</a></div></div></section>";
      r.querySelector("[data-again]").addEventListener("click", () => { start(); box.scrollIntoView({ behavior: "smooth", block: "center" }); });
      r.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    document.getElementById("tyMin").addEventListener("click", (e) => { const b = e.target.closest("[data-m]"); if (!b) return; ST.mins = +b.dataset.m; ls.set("mins", ST.mins); document.querySelectorAll("#tyMin button").forEach((x) => x.classList.toggle("on", x === b)); start(); });
    document.getElementById("tyNew").addEventListener("click", start);
    start();
    cleanup = () => clearInterval(tick);
  }

  /* ---------- on-screen keyboard ---------- */
  function osk(el, opt) {
    // opt: { shift, hl:{code,shift}, tap(code, shift), small }
    const lang = ST.lang;
    const key = (c) => { const k = KEY[c], ch = k[lang][opt.shift ? 1 : 0], hl = opt.hl && opt.hl.code === c;
      return '<button type="button" class="k ' + k.f + (hl ? " hl" : "") + '" data-code="' + c + '"><span class="m">' + esc(show(ch)) + "</span>" + (lang === "mr" ? '<span class="e">' + esc(k.en[opt.shift ? 1 : 0]) + "</span>" : "") + "</button>"; };
    const hs = opt.hl && opt.hl.shift;
    el.innerHTML = '<div class="ty-osk' + (opt.small ? " sm" : "") + '">' + ROWS.map((r, i) => '<div class="row">' + (i === 1 ? '<span class="k x w15">Tab</span>' : i === 2 ? '<span class="k x w18">Caps</span>' : i === 3 ? '<button type="button" class="k x sh w23' + (opt.shift || (hs && opt.hl.code && KEY[opt.hl.code].f[0] === "r") ? " on" : "") + '" data-shift>⇧ Shift</button>' : "") +
      r[0].split(" ").map(key).join("") + (i === 0 ? '<button type="button" class="k x w2" data-bs>⌫</button>' : i === 2 ? '<span class="k x w2">Enter</span>' : i === 3 ? '<button type="button" class="k x sh w23' + (opt.shift || (hs && opt.hl.code && KEY[opt.hl.code].f[0] === "l") ? " on" : "") + '" data-shift>⇧ Shift</button>' : "") + "</div>").join("") +
      '<div class="row"><button type="button" class="k th sp' + (opt.hl && opt.hl.code === "Space" ? " hl" : "") + '" data-code="Space">' + esc(X.space) + "</button></div></div>";
    if (opt.tap) {
      el.querySelector(".ty-osk").addEventListener("pointerdown", (e) => {
        const b = e.target.closest("button"); if (!b) return; e.preventDefault();
        b.classList.add("down"); setTimeout(() => b.classList.remove("down"), 140);
        if (b.dataset.code) opt.tap(b.dataset.code, !!opt.shift);
        else if (b.hasAttribute("data-shift")) opt.tap("Shift");
        else if (b.hasAttribute("data-bs")) opt.tap("Backspace");
      });
    }
  }
  const hands = (f) => '<div class="ty-hands" aria-hidden="true">' + ["l", "r"].map((h) => '<div class="hand ' + h + '">' + (h === "l" ? ["p", "r", "m", "i"] : ["i", "m", "r", "p"]).map((x) => '<span class="fg ' + h + x + (f === h + x ? " on" : "") + '"></span>').join("") +
    '<span class="tb' + (f === "th" ? " on" : "") + '"></span><span class="palm"></span></div>').join("") + "</div>";

  /* ---------- KEY TRAINER ---------- */
  const LESSONS = {
    easy: [["KeyA KeyS KeyD KeyF"], ["KeyJ KeyK KeyL Semicolon"], ["KeyA KeyS KeyD KeyF KeyG KeyH KeyJ KeyK KeyL Semicolon"], ["KeyE KeyI KeyR KeyU KeyF KeyJ KeyD KeyK"]],
    medium: [["KeyQ KeyW KeyE KeyR KeyT"], ["KeyY KeyU KeyI KeyO KeyP"], ["KeyZ KeyX KeyC KeyV KeyB KeyN KeyM Comma Period"], ["KeyQ KeyW KeyE KeyR KeyT KeyY KeyU KeyI KeyO KeyP KeyA KeyS KeyD KeyF KeyG KeyH KeyJ KeyK KeyL KeyZ KeyX KeyC KeyV KeyB KeyN KeyM"]],
    hard: [["Digit1 Digit2 Digit3 Digit4 Digit5 Digit6 Digit7 Digit8 Digit9 Digit0"], ["KeyA KeyS KeyD KeyF KeyJ KeyK KeyL KeyE KeyR KeyU KeyI KeyO", 1], ["Minus Equal BracketLeft BracketRight Semicolon Quote Comma Period Slash"], ["Digit1 Digit5 Digit9 KeyQ KeyW KeyE KeyT KeyY KeyO KeyP KeyA KeyG KeyH KeyL KeyC KeyV KeyB KeyN KeyM Comma Period Slash", 2]]
  };
  function learn() {
    const LS = LESSONS[ST.lvl], done = ls.get("learn", {});
    let li = Math.min(ls.get("li_" + ST.lvl, 0), LS.length - 1);
    root.innerHTML = modHead("learn") + '<div class="ty-lsn" id="tyL"></div><div class="ty-trainer"><div class="ty-seq" id="tySeq"></div>' +
      '<div class="ty-hint" id="tyH"></div><div class="ty-tstats"><span><i class="fa-solid fa-gauge-high"></i> <b id="tyK">0</b> ' + esc(X.keys) + '</span><span><i class="fa-solid fa-bullseye"></i> <b id="tyA">100%</b></span><span class="pg"><span id="tyP"></span></span></div>' +
      '<div id="tyKb"></div><div id="tyHands"></div></div><div id="tyRes"></div>';
    const lsn = () => { document.getElementById("tyL").innerHTML = LS.map((l, i) => { const k = ST.lang + "-" + ST.lvl + "-" + i, s = done[k] || 0;
      return '<button type="button" data-l="' + i + '" class="' + (i === li ? "on" : "") + '"><span class="n">' + (i + 1) + "</span><span class=\"t\"><b>" + esc(X.lsn[ST.lvl][i]) + '</b><span class="stars">' + [1, 2, 3].map((x) => '<i class="fa-' + (x <= s ? "solid" : "regular") + ' fa-star"></i>').join("") + "</span></span></button>"; }).join(""); };
    let seq = [], at = 0, keys = 0, bad = 0, t0 = 0, shift = false, wrongNow = false;
    function build() {
      const L0 = LS[li], codes = L0[0].split(" "), sh = L0[1] || 0;
      seq = [];
      for (let g = 0; g < 10; g++) {
        const n = 3 + Math.floor(Math.random() * 3);
        for (let i = 0; i < n; i++) seq.push({ code: pick(codes), shift: sh === 1 ? true : sh === 2 ? Math.random() < 0.3 : false });
        if (g < 9) seq.push({ code: "Space", shift: false });
      }
      at = 0; keys = 0; bad = 0; t0 = 0; shift = false;
      document.getElementById("tyRes").innerHTML = ""; lsn(); draw();
    }
    function draw() {
      const s = document.getElementById("tySeq");
      s.innerHTML = seq.map((k, i) => '<span class="' + (i < at ? "done" : i === at ? "cur" + (wrongNow ? " wrong" : "") : "") + (k.code === "Space" ? " spc" : "") + '">' + esc(show(chOf(k.code, k.shift))) + "</span>").join("");
      const c = s.querySelector(".cur"); if (c) s.scrollLeft = c.offsetLeft - s.clientWidth / 2 + c.offsetWidth / 2;
      const k = seq[at];
      if (k) {
        const f = KEY[k.code].f;
        document.getElementById("tyH").innerHTML = '<span class="pk ' + f + '">' + esc(show(chOf(k.code, k.shift))) + "</span><span>" + esc(X.press) + " <b>" + esc(k.code === "Space" ? X.space : (k.shift ? "Shift + " : "") + (ST.lang === "mr" ? chOf(k.code, k.shift) + "  (" + KEY[k.code].en[k.shift ? 1 : 0] + ")" : chOf(k.code, k.shift))) + "</b> – " + esc(X.finger) + " <b class=\"fc " + f + '">' + esc(X.fingers[f]) + "</b></span>";
        osk(document.getElementById("tyKb"), { shift: k.shift || shift, hl: k, tap: tapKey });
        document.getElementById("tyHands").innerHTML = hands(f);
      }
      const min = t0 ? (Date.now() - t0) / 60000 : 0;
      document.getElementById("tyK").textContent = min ? Math.round(at / min) : 0;
      document.getElementById("tyA").textContent = (keys ? Math.round((keys - bad) / keys * 100) : 100) + "%";
      document.getElementById("tyP").style.width = (at / seq.length * 100) + "%";
    }
    function press(code, sh, key) {
      const k = seq[at]; if (!k) return;
      if (!t0) t0 = Date.now();
      keys++;
      const want = chOf(k.code, k.shift);
      const ok = (code === k.code && (code === "Space" || sh === k.shift)) || (key && key === want);
      if (ok) { at++; wrongNow = false; shift = false; beep("ok"); if (at >= seq.length) return fin(); }
      else { bad++; wrongNow = true; beep("bad"); const s = document.getElementById("tySeq"); s.classList.remove("shake"); void s.offsetWidth; s.classList.add("shake"); }
      draw();
    }
    function tapKey(code, sh) {
      if (code === "Shift") { shift = !shift; return draw(); }
      if (code === "Backspace") return;
      press(code, code === "Space" ? false : (sh || shift));
    }
    function fin() {
      const min = (Date.now() - t0) / 60000, acc = Math.round((keys - bad) / keys * 100), kpm = Math.round(seq.length / min);
      const stars = acc >= 95 ? 3 : acc >= 85 ? 2 : 1, key = ST.lang + "-" + ST.lvl + "-" + li;
      done[key] = Math.max(done[key] || 0, stars); ls.set("learn", done);
      addHist({ m: "learn", w: Math.round(kpm / 5), a: acc, t: Math.round(min * 60), x: li + 1 });
      if (stars === 3) { beep("win"); confetti(); }
      draw(); lsn();
      document.getElementById("tyRes").innerHTML = '<section class="ty-result small pass"><div class="big-stars">' + [1, 2, 3].map((x) => '<i class="fa-' + (x <= stars ? "solid" : "regular") + ' fa-star"></i>').join("") + '</div><div class="rt"><h3>' + esc(X.done) + "</h3>" +
        '<div class="grid"><div><b>' + kpm + "</b><small>" + esc(X.keys) + "</small></div><div><b>" + acc + "%</b><small>" + esc(X.acc) + "</small></div><div><b>" + bad + "</b><small>" + esc(X.errors) + "</small></div></div>" +
        '<div class="acts"><button type="button" class="btn btn-ghost" data-r><i class="fa-solid fa-rotate-right"></i> ' + esc(X.again) + "</button>" + (li < LS.length - 1 ? '<button type="button" class="btn btn-primary" data-n>' + esc(X.next) + ' <i class="fa-solid fa-arrow-right"></i></button>' : "") + "</div></div></section>";
      const r = document.getElementById("tyRes");
      r.querySelector("[data-r]").addEventListener("click", build);
      const n = r.querySelector("[data-n]"); if (n) n.addEventListener("click", () => { li++; ls.set("li_" + ST.lvl, li); build(); });
      r.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    document.getElementById("tyL").addEventListener("click", (e) => { const b = e.target.closest("[data-l]"); if (!b) return; li = +b.dataset.l; ls.set("li_" + ST.lvl, li); build(); });
    const kd = (e) => {
      if (e.ctrlKey || e.altKey || e.metaKey || /^(Shift|Control|Alt|Meta|CapsLock|Tab)/.test(e.key)) return;
      if (e.target.closest && e.target.closest("input, textarea, select")) return;
      if (!(KEY[e.code] || e.code === "Space")) return;
      e.preventDefault(); if (e.repeat) return;
      press(e.code, e.shiftKey, e.key);
    };
    document.addEventListener("keydown", kd);
    cleanup = () => document.removeEventListener("keydown", kd);
    build();
  }

  /* ---------- TYPING RACE ---------- */
  const BOTS = { en: { easy: [12, 17, 22], medium: [22, 28, 34], hard: [30, 38, 46] }, mr: { easy: [8, 11, 14], medium: [14, 18, 22], hard: [20, 25, 30] } };
  function race() {
    const sp = BOTS[ST.lang][ST.lvl], names = ["Robo", "Turbo", "Rocket"], cols = ["#7b3fe4", "#f59e0b", "#e5383b"];
    root.innerHTML = modHead("race") + '<div class="ty-bar">' + insRow() + '<div class="acts"><span class="ty-botsp"><i class="fa-solid fa-robot"></i> ' + esc(X.bots) + ": " + sp.join(" · ") + " " + esc(X.wpm) + '</span><button type="button" class="btn btn-ghost btn-sm" id="tyNew"><i class="fa-solid fa-rotate-right"></i> ' + esc(X.restart) + "</button></div></div>" +
      '<div class="ty-track" id="tyTrack">' + [[X.you, "#1554e0", "you"]].concat(names.map((n, i) => [n + " · " + sp[i], cols[i], "b" + i])).map((c) =>
        '<div class="lane"><span class="nm">' + esc(c[0]) + '</span><div class="road"><span class="car ' + c[2] + '" style="--c:' + c[1] + '"><i class="fa-solid fa-car-side"></i></span></div><span class="fin"><i class="fa-solid fa-flag-checkered"></i></span></div>').join("") +
      '<div class="ty-count" id="tyCnt" hidden></div></div>' +
      '<div class="ty-live"><div class="tm"><i class="fa-solid fa-stopwatch"></i><b id="tyT">0:00</b></div><div><b id="tyW">0</b><small>' + esc(X.net) + '</small></div><div><b id="tyA">100%</b><small>' + esc(X.acc) + '</small></div><div><b id="tyPl">–</b><small>' + esc(X.race) + "</small></div></div>" +
      '<div class="ty-box short" id="tyBox"></div><div id="tyRes"></div>';
    bindIns();
    let raf = 0, T = null, t0 = 0, text = "", order = [], cd = 0;
    const cars = Array.from(document.querySelectorAll("#tyTrack .car"));
    const setCar = (i, f) => { cars[i].style.left = "calc(" + Math.min(1, f) * 100 + "% - " + Math.min(1, f) * 44 + "px)"; };
    function start() {
      cancelAnimationFrame(raf); clearInterval(cd); document.getElementById("tyRes").innerHTML = ""; order = [];
      const P = DATA[ST.lang][ST.lvl].p; text = pick(P); if (text.length < 120) text += " " + pick(P);
      cars.forEach((c, i) => { setCar(i, 0); c.classList.remove("won"); });
      document.getElementById("tyT").textContent = "0:00"; document.getElementById("tyW").textContent = "0"; document.getElementById("tyA").textContent = "100%"; document.getElementById("tyPl").textContent = "–";
      T = typer(document.getElementById("tyBox"), text, { onProgress: (s) => { setCar(0, s.frac); document.getElementById("tyW").textContent = s.net; document.getElementById("tyA").textContent = s.acc + "%"; }, onDone: (s) => fin(s) });
      T.lock(true);
      const cnt = document.getElementById("tyCnt"); let n = 3; cnt.hidden = false; cnt.textContent = n; beep("tick");
      cd = setInterval(() => {
        n--; if (n > 0) { cnt.textContent = n; beep("tick"); return; }
        clearInterval(cd); cnt.textContent = X.go; beep("win"); setTimeout(() => (cnt.hidden = true), 600);
        T.lock(false); T.focus(); t0 = Date.now(); loop();
      }, 800);
    }
    function loop() {
      const sec = (Date.now() - t0) / 1000;
      document.getElementById("tyT").textContent = fmt(sec);
      sp.forEach((w, i) => { const f = (sec * w * 5 / 60) / text.length; setCar(i + 1, f); if (f >= 1 && order.indexOf("b" + i) < 0) order.push("b" + i); });
      const me = T.stats(); const pl = 1 + sp.filter((w, i) => order.indexOf("b" + i) > -1 || (sec * w * 5 / 60) > me.ok).length;
      document.getElementById("tyPl").textContent = X.place[Math.min(3, pl - 1)];
      if (!T.state.done) raf = requestAnimationFrame(loop);
    }
    function fin(s) {
      cancelAnimationFrame(raf);
      const sec = (Date.now() - t0) / 1000;
      const place = 1 + sp.filter((w) => (text.length / (w * 5 / 60)) < sec).length;
      document.getElementById("tyPl").textContent = X.place[place - 1];
      addHist({ m: "race", w: s.net, g: s.gross, a: s.acc, t: s.sec, x: place });
      if (place === 1) { cars[0].classList.add("won"); beep("win"); confetti(); }
      document.getElementById("tyRes").innerHTML = '<section class="ty-result ' + (place === 1 ? "pass" : "") + '"><div class="podium p' + place + '"><i class="fa-solid ' + (place === 1 ? "fa-trophy" : "fa-medal") + '"></i><b>' + esc(X.place[place - 1]) + "</b></div>" +
        '<div class="rt"><h3>' + esc(place === 1 ? X.win : X.lose) + '</h3><div class="grid"><div><b>' + s.net + "</b><small>" + esc(X.net) + "</small></div><div><b>" + s.acc + "%</b><small>" + esc(X.acc) + "</small></div><div><b>" + fmt(s.sec) + "</b><small>" + esc(X.time) + "</small></div></div>" +
        '<div class="acts"><button type="button" class="btn btn-primary" data-again><i class="fa-solid fa-flag-checkered"></i> ' + esc(X.again) + "</button></div></div></section>";
      document.getElementById("tyRes").querySelector("[data-again]").addEventListener("click", () => { start(); document.getElementById("tyTrack").scrollIntoView({ behavior: "smooth", block: "start" }); });
    }
    document.getElementById("tyNew").addEventListener("click", start);
    start();
    cleanup = () => { cancelAnimationFrame(raf); clearInterval(cd); };
  }

  /* ---------- BALLOON POP ---------- */
  function pop() {
    const useOsk0 = ls.get("osk", TOUCH && ST.lang === "en");
    let useOsk = TOUCH ? useOsk0 : false;
    root.innerHTML = modHead("pop") + '<div class="ty-bar">' + insRow() + (TOUCH ? '<div class="seg sm" id="tyKbd"><button type="button" data-k="1" class="' + (useOsk ? "on" : "") + '"><i class="fa-solid fa-keyboard"></i> ' + esc(X.useOsk) + '</button><button type="button" data-k="0" class="' + (useOsk ? "" : "on") + '"><i class="fa-solid fa-mobile-screen"></i> ' + esc(X.usePhone) + "</button></div>" : "") + "</div>" +
      '<div class="ty-game" id="tyG"><div class="hud"><span><i class="fa-solid fa-star"></i> <b id="gS">0</b></span><span><i class="fa-solid fa-layer-group"></i> ' + esc(X.level) + ' <b id="gL">1</b></span><span class="lv" id="gH"></span></div>' +
      '<div class="sky" id="gSky"><div class="cloud c1"></div><div class="cloud c2"></div><div class="over" id="gO"><div class="card"><span class="bl">🎈</span><h3>' + esc(X.popTitle) + '</h3><p id="gMsg">' + esc(X.m.pop[1]) + '</p><button type="button" class="btn btn-primary" id="gPlay"><i class="fa-solid fa-play"></i> ' + esc(X.play) + "</button></div></div></div>" +
      '<div class="inp"><input id="gIn" type="text" placeholder="' + esc(X.typeHere) + '" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="' + (ST.lang === "mr" ? "mr" : "en") + '"' + (useOsk ? ' inputmode="none"' : "") + "></div></div><div id=\"gKb\"></div>";
    bindIns();
    const sky = document.getElementById("gSky"), inp = document.getElementById("gIn");
    const W = DATA[ST.lang][ST.lvl].w;
    const letters = ST.lang === "en" ? "asdfghjklqwertyuiopzxcvbnm".split("") : "कखगघचजटडतदनपबमयरलवसहळ".split("");
    const CFG = { easy: { sp: 34, ev: 1900, src: () => (Math.random() < 0.7 ? pick(letters) : pick(W)) }, medium: { sp: 42, ev: 2100, src: () => pick(W) }, hard: { sp: 50, ev: 2300, src: () => pick(W) } }[ST.lvl];
    const COL = ["#e5383b", "#1554e0", "#22c55e", "#f59e0b", "#c026d3", "#0fa3b1", "#f97316"];
    let paused = false, B = [], score = 0, lives = 3, level = 1, popped = 0, missed = 0, run = false, raf = 0, last = 0, spawnT = 0, oskShift = false, t0 = 0, chars = 0;
    const hearts = () => (document.getElementById("gH").innerHTML = [0, 1, 2].map((i) => '<i class="fa-' + (i < lives ? "solid" : "regular") + ' fa-heart"></i>').join(""));
    hearts();
    function spawn() {
      const w = CFG.src(), el = document.createElement("div"), c = pick(COL);
      el.className = "bal"; el.style.setProperty("--c", c);
      el.innerHTML = '<span class="b"><span class="t">' + esc(w) + '</span></span><span class="str"></span>';
      sky.appendChild(el);
      const maxX = Math.max(10, sky.clientWidth - Math.max(70, w.length * 13 + 30));
      const b = { w, el, x: 10 + Math.random() * maxX, y: sky.clientHeight + 10, v: CFG.sp * (1 + (level - 1) * 0.12) * (0.85 + Math.random() * 0.3), sway: Math.random() * 6.28 };
      el.style.left = b.x + "px"; B.push(b);
    }
    function frame(ts) {
      if (!run) return;
      const dt = Math.min(0.05, (ts - (last || ts)) / 1000); last = ts;
      spawnT -= dt * 1000;
      if (spawnT <= 0) { spawn(); spawnT = Math.max(650, CFG.ev - (level - 1) * 160); }
      for (let i = B.length - 1; i >= 0; i--) {
        const b = B[i]; b.y -= b.v * dt; b.sway += dt * 2;
        b.el.style.transform = "translate(" + Math.sin(b.sway) * 6 + "px," + b.y + "px)";
        if (b.y < -90) { b.el.remove(); B.splice(i, 1); missed++; lives--; hearts(); beep("bad"); sky.classList.add("hit"); setTimeout(() => sky.classList.remove("hit"), 250); if (lives <= 0) return over(); }
      }
      mark();
      raf = requestAnimationFrame(frame);
    }
    function mark() {
      const v = inp.value;
      B.forEach((b) => { const on = v && b.w.indexOf(v) === 0; b.el.classList.toggle("tgt", !!on); const t = b.el.querySelector(".t"); t.innerHTML = on ? "<u>" + esc(v) + "</u>" + esc(b.w.slice(v.length)) : esc(b.w); });
    }
    function check() {
      const v = inp.value.replace(/\s+/g, "");
      if (inp.value !== v) inp.value = v;
      if (!run || !v) return mark();
      const hit = B.filter((b) => b.w === v).sort((a, b) => a.y - b.y)[0];
      if (hit) {
        chars += v.length; popped++; score += 10 * v.length * level; B.splice(B.indexOf(hit), 1);
        hit.el.classList.add("popped"); setTimeout(() => hit.el.remove(), 380); beep("pop"); inp.value = "";
        document.getElementById("gS").textContent = score;
        if (popped % 10 === 0) { level++; document.getElementById("gL").textContent = level; }
      } else if (!B.some((b) => b.w.indexOf(v) === 0)) { beep("bad"); inp.classList.add("bad"); setTimeout(() => inp.classList.remove("bad"), 250); inp.value = ""; }
      mark();
    }
    function begin() {
      B.forEach((b) => b.el.remove()); B = []; score = 0; lives = 3; level = 1; popped = 0; missed = 0; chars = 0; spawnT = 0; last = 0; t0 = Date.now();
      document.getElementById("gS").textContent = 0; document.getElementById("gL").textContent = 1; hearts();
      document.getElementById("gO").hidden = true; inp.value = ""; run = true;
      if (TOUCH) document.getElementById("tyG").scrollIntoView({ behavior: "smooth", block: "start" });
      if (!useOsk) inp.focus({ preventScroll: true });
      raf = requestAnimationFrame(frame);
    }
    function over() {
      run = false; cancelAnimationFrame(raf);
      const best = Math.max(ls.get("popbest", 0), score); ls.set("popbest", best);
      const sec = Math.round((Date.now() - t0) / 1000);
      addHist({ m: "pop", w: sec ? Math.round(chars / 5 / (sec / 60)) : 0, a: popped + missed ? Math.round(popped / (popped + missed) * 100) : 0, t: sec, x: score });
      if (score >= best && score > 0) confetti();
      const o = document.getElementById("gO"); o.hidden = false;
      o.querySelector("h3").textContent = X.gameOver;
      document.getElementById("gMsg").innerHTML = "<b class=\"sc\">" + score + "</b><br>" + esc(X.popped) + ": " + popped + " · " + esc(X.missed) + ": " + missed + "<br>" + esc(X.bestScore) + ": " + best;
      document.getElementById("gPlay").innerHTML = '<i class="fa-solid fa-rotate-right"></i> ' + esc(X.again); paused = false;
    }
    document.getElementById("gPlay").addEventListener("click", () => { if (!paused) return begin(); paused = false; document.getElementById("gO").hidden = true; run = true; last = 0; raf = requestAnimationFrame(frame); if (!useOsk) inp.focus({ preventScroll: true }); });
    inp.addEventListener("input", check);
    inp.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") { e.preventDefault(); inp.value = ""; mark(); return; }
      if (ST.lang === "mr" && ST.ins && !e.ctrlKey && !e.altKey && !e.metaKey && !e.isComposing && KEY[e.code] && e.code !== "Space") { e.preventDefault(); inp.value += chOf(e.code, e.shiftKey, "mr"); check(); }
    });
    const drawKb = () => { const k = document.getElementById("gKb"); if (!useOsk) { k.innerHTML = ""; return; } osk(k, { shift: oskShift, small: true, tap: (code, sh) => {
      if (code === "Shift") { oskShift = !oskShift; return drawKb(); }
      if (code === "Backspace") inp.value = inp.value.slice(0, -1);
      else if (code === "Space") inp.value = "";
      else { inp.value += chOf(code, sh); if (oskShift) { oskShift = false; drawKb(); } }
      check();
    } }); };
    drawKb();
    const kbd = document.getElementById("tyKbd");
    if (kbd) kbd.addEventListener("click", (e) => { const b = e.target.closest("[data-k]"); if (!b) return; useOsk = b.dataset.k === "1"; ls.set("osk", useOsk); kbd.querySelectorAll("button").forEach((x) => x.classList.toggle("on", x === b));
      if (useOsk) { inp.setAttribute("inputmode", "none"); inp.blur(); } else { inp.removeAttribute("inputmode"); inp.focus(); } drawKb(); });
    const vis = () => { if (document.hidden && run) { run = false; paused = true; cancelAnimationFrame(raf); const o = document.getElementById("gO"); o.hidden = false; o.querySelector("h3").textContent = X.paused; document.getElementById("gMsg").textContent = ""; document.getElementById("gPlay").innerHTML = '<i class="fa-solid fa-play"></i> ' + esc(X.resume); } };
    document.addEventListener("visibilitychange", vis);
    cleanup = () => { run = false; cancelAnimationFrame(raf); document.removeEventListener("visibilitychange", vis); };
  }

  /* ---------- MY PROGRESS ---------- */
  function progress() {
    const h = hist(), T = h.filter((r) => r.m === "test" || r.m === "race");
    const bestOf = (l) => T.filter((r) => r.l === l).reduce((a, r) => Math.max(a, r.w || 0), 0);
    const acc = T.length ? Math.round(T.slice(0, 10).reduce((a, r) => a + (r.a || 0), 0) / Math.min(10, T.length)) : 0;
    const done = ls.get("learn", {}), nDone = Object.keys(done).length;
    const B = [T.some((r) => r.m === "test"), T.some((r) => r.w >= 20), T.some((r) => r.w >= 30), T.some((r) => r.w >= 40), T.some((r) => r.a >= 98 && r.t >= 30), h.some((r) => r.m === "race" && r.x === 1), ls.get("popbest", 0) >= 300, h.some((r) => r.l === "en") && h.some((r) => r.l === "mr"), T.length >= 10];
    const BI = ["fa-flag", "fa-gauge", "fa-gauge-high", "fa-rocket", "fa-bullseye", "fa-trophy", "fa-burst", "fa-language", "fa-dumbbell"];
    const last = T.slice(0, 15).reverse(), mx = Math.max(10, ...last.map((r) => r.w || 0));
    const ML = { test: X.m.test[0], race: X.m.race[0], pop: X.m.pop[0], learn: X.m.learn[0] };
    root.innerHTML = modHead("progress") +
      '<div class="ty-pstats"><div class="c1"><i class="fa-solid fa-gauge-high"></i><b>' + bestOf("en") + "</b><small>" + esc(X.best) + ' · English</small></div><div class="c2"><i class="fa-solid fa-gauge-high"></i><b>' + bestOf("mr") + "</b><small>" + esc(X.best) + ' · मराठी</small></div>' +
      '<div class="c3"><i class="fa-solid fa-bullseye"></i><b>' + (acc ? acc + "%" : "–") + "</b><small>" + esc(X.acc) + '</small></div><div class="c4"><i class="fa-solid fa-burst"></i><b>' + ls.get("popbest", 0) + "</b><small>" + esc(X.bestPop) + '</small></div><div class="c5"><i class="fa-solid fa-star"></i><b>' + nDone + "/24</b><small>" + esc(X.lessonsDone) + "</small></div></div>" +
      '<div class="ty-two"><section class="ty-card"><h3><i class="fa-solid fa-chart-column"></i> ' + esc(X.chart) + "</h3>" +
        (last.length ? '<div class="ty-chart">' + last.map((r) => '<div class="c" title="' + esc(new Date(r.d).toLocaleString()) + '"><span class="v">' + (r.w || 0) + '</span><span class="b ' + (r.w >= GOAL[r.l][r.v] ? "ok" : "") + '" style="height:' + Math.max(4, (r.w || 0) / mx * 100) + '%"></span><span class="l">' + (r.l === "mr" ? "म" : "E") + "</span></div>").join("") + "</div>" : '<p class="ty-empty">' + esc(X.noHist) + ' <a href="#test">' + esc(X.m.test[0]) + " →</a></p>") + "</section>" +
      '<section class="ty-card"><h3><i class="fa-solid fa-medal"></i> ' + esc(X.badges) + '</h3><div class="ty-badges">' + X.bd.map((b, i) => '<div class="' + (B[i] ? "on" : "") + '"><span><i class="fa-solid ' + BI[i] + '"></i></span><b>' + esc(b[0]) + "</b><small>" + esc(b[1]) + "</small></div>").join("") + "</div></section></div>" +
      '<section class="ty-card"><h3><i class="fa-solid fa-clock-rotate-left"></i> ' + esc(X.history) + "</h3>" + (h.length ? '<div class="ty-hist">' + h.slice(0, 25).map((r) =>
        '<div class="hr m-' + r.m + '"><span class="d">' + esc(new Date(r.d).toLocaleDateString(undefined, { day: "numeric", month: "short" })) + '</span><span class="mo">' + esc(ML[r.m] || r.m) + '</span><span class="tg">' + (r.l === "mr" ? "मराठी" : "English") + " · " + esc(X[r.v] || "") + "</span><span class=\"w\"><b>" + (r.m === "pop" ? r.x : r.w) + "</b> " + esc(r.m === "pop" ? X.score : X.wpm) + '</span><span class="a">' + (r.a != null ? r.a + "%" : "") + "</span></div>").join("") + "</div>" +
        '<button type="button" class="btn btn-ghost btn-sm ty-clear" id="tyClr"><i class="fa-solid fa-trash"></i> ' + esc(X.clear) + "</button>" : '<p class="ty-empty">' + esc(X.noHist) + "</p>") + "</section>";
    const c = document.getElementById("tyClr");
    if (c) c.addEventListener("click", () => { if (!confirm(X.clearQ)) return; ["hist", "learn", "popbest"].forEach((k) => ls.set(k, k === "hist" ? [] : k === "learn" ? {} : 0)); progress(); });
  }

  /* ---------- router ---------- */
  const VIEWS = { learn, test, race, pop, progress }, TITLE0 = document.title;
  function route() {
    if (cleanup) { cleanup(); cleanup = null; }
    const k = (location.hash || "").slice(1);
    (VIEWS[k] || home)();
    document.title = VIEWS[k] ? X.m[k][0] + " – " + X.title + " | COPA MCQ" : TITLE0;
  }
  window.addEventListener("hashchange", () => { route(); window.scrollTo(0, 0); });
  root.addEventListener("click", (e) => { if (e.target.closest(".ty-back")) { e.preventDefault(); history.pushState(null, "", location.pathname); route(); window.scrollTo(0, 0); } });
  route();
})();
