/* Home page – landing design */
(function () {
  "use strict";
  const C = window.COPA, t = C.t, esc = C.esc, L = C.lang;

  /* ---------- page-specific text ---------- */
  const TX = {
    en: {
      nav: ["Home", "Modules", "Mock Test", "Study", "Results", "About"],
      signin: "Sign in", start: "Start Mock Test",
      tg: [["Explore Technology, Master", "Computer Skills,", "Shape Your Future."], ["The Digital World Never Stops Evolving, So Keep", "Learning", "and Growing."], ["Every Click, Every Code, Every Skill Takes You Closer to the", "Digital", "Future."]],
      testiH: "What our learners say", testiS: "Real reviews from ITI COPA trainees – written with Google sign-in.", allRev: "Read all reviews", writeRev: "Write a review", rateOf: "{a}/5 · {n} reviews",
      faqH: "Frequently asked questions about ITI COPA", faqS: "Everything you need to know about the COPA trade and COPA MCQ.",
      faq: [["What is the COPA trade in ITI?", "COPA (Computer Operator and Programming Assistant) is a one-year ITI trade under the Craftsmen Training Scheme. It covers computer fundamentals, Windows and Linux, MS Office, databases, networking, web design with HTML, CSS and JavaScript, e-commerce, cloud computing and Python."],
        ["How many COPA MCQ questions are available on COPA MCQ?", "COPA MCQ has 355 multiple-choice questions with correct answers and a one-line explanation, divided into 15 modules, in English, Hindi and Marathi."],
        ["Is the COPA online mock test free?", "Yes. All COPA MCQ practice, module tests and full CBT mock tests are completely free. The mock test works like the real CBT exam with a timer, question palette, mark for review and an instant result."],
        ["What is the passing mark in the COPA MCQ mock test?", "COPA MCQ mock tests use a pass mark of 40%. Your dashboard keeps the full history of your results with pass or fail for every test."],
        ["Can I practise COPA practicals online?", "Yes. The Live Practice lab lets you write HTML, CSS, JavaScript and Python programs in the browser, see the output instantly and save your programs to your dashboard."],
        ["Can I study COPA in Hindi or Marathi?", "Yes. Every question, option and explanation is available in English, Hindi and Marathi. Change the language from the menu at any time."]],
      h1a: "Learning in the computer age means we must practise", h1b: "tomorrow’s", h1c: "skills today",
      sub: "The complete ITI COPA question bank – 355 MCQs in English, Hindi & Marathi with real CBT-style mock tests.",
      joinI: "Start Practice", joinS: "Take Mock Test", tagI: "COPA Instructor", tagS: "ITI Trainee",
      trust: ["Free for all trainees", "No negative marking", "Works offline & on mobile"],
      popT: "Our Most", popH: "Popular Modules", popP: "Start with the modules every COPA trainee must master",
      explore: "Explore Modules", lessons: "Questions",
      catT: "Explore", catH: "All Modules", catP: "Choose a module and practise topic-wise",
      lpT: "Live", lpH: "Coding Practice", lpP: "Do your COPA practicals online – write HTML, CSS, JavaScript or Python and see the output instantly.", lpB: "Start Live Practice", lpB2: "Try Python", lpF: ["Live preview while you type", "Python runs in the browser – no install", "24 ready COPA practicals", "Save programs to your dashboard"], whyT: "Why", whyH: "COPA MCQ?", whyP: "Everything you need to clear the ITI COPA exam",
      f: [["Real CBT Exam", "Timer, question palette, mark for review and auto-submit – exactly like the ITI online exam."],
          ["3 Languages", "Read every question in English, हिंदी or मराठी and switch anytime."],
          ["Instant Feedback", "Practise module-wise and see the right answer immediately."],
          ["Detailed Analysis", "Score, pass/fail and module-wise strengths after every test."]],
      st: ["Modules", "MCQ Questions", "Languages", "Negative Marking"],
      bandH: "Prepare smart. Score high in the ITI COPA exam!",
      bandP: "Take a full 50-question CBT mock test with a 60-minute timer and get your result instantly.",
      bandB: "Take a Mock Test", bandB2: "Study Material",
      progT: "Your Progress", tests: "Tests attempted", avg: "Average score", prac: "Questions practised",
      footP: "Free online learning platform for ITI Computer Operator & Programming Assistant (COPA) trainees.",
      fh: ["Learn", "Practice", "Institute"], fl1: ["All Modules", "Study Material", "Python", "JavaScript"],
      fl2: ["Mock Test (CBT)", "Quick Test", "My Results", "Dashboard"], fl3: ["About", "Contact", "COPA MCQ", "Maharashtra"],
      by: "COPA MCQ", byR: "ITI COPA"
    },
    hi: {
      nav: ["होम", "मॉड्यूल", "मॉक टेस्ट", "अध्ययन", "परिणाम", "परिचय"],
      signin: "लॉगिन", start: "मॉक टेस्ट शुरू करें",
      tg: [["तकनीक को जानें,", "कंप्यूटर कौशल में", "महारत पाएँ, अपना भविष्य बनाएँ।"], ["डिजिटल दुनिया कभी रुकती नहीं, इसलिए", "सीखते रहें", "और आगे बढ़ते रहें।"], ["हर क्लिक, हर कोड, हर कौशल आपको", "डिजिटल भविष्य के", "और करीब ले जाता है।"]],
      testiH: "हमारे विद्यार्थी क्या कहते हैं", testiS: "ITI COPA प्रशिक्षुओं की असली समीक्षाएँ – Google साइन इन से लिखी गई।", allRev: "सभी समीक्षाएँ पढ़ें", writeRev: "समीक्षा लिखें", rateOf: "{a}/5 · {n} समीक्षाएँ",
      faqH: "ITI COPA के बारे में अक्सर पूछे जाने वाले प्रश्न", faqS: "COPA ट्रेड और COPA MCQ के बारे में ज़रूरी जानकारी।",
      faq: [["ITI में COPA ट्रेड क्या है?", "COPA (कंप्यूटर ऑपरेटर एंड प्रोग्रामिंग असिस्टेंट) क्राफ्ट्समेन ट्रेनिंग स्कीम के अंतर्गत एक वर्ष का ITI ट्रेड है। इसमें कंप्यूटर की मूल बातें, Windows और Linux, MS Office, डेटाबेस, नेटवर्किंग, HTML, CSS और JavaScript से वेब डिज़ाइन, ई-कॉमर्स, क्लाउड कंप्यूटिंग और Python पढ़ाए जाते हैं।"],
        ["COPA MCQ पर कितने प्रश्न उपलब्ध हैं?", "COPA MCQ पर 15 मॉड्यूल में 355 बहुविकल्पीय प्रश्न सही उत्तर और एक पंक्ति की व्याख्या के साथ अंग्रेज़ी, हिंदी और मराठी में उपलब्ध हैं।"],
        ["क्या COPA ऑनलाइन मॉक टेस्ट मुफ़्त है?", "हाँ। सभी अभ्यास, मॉड्यूल टेस्ट और पूर्ण CBT मॉक टेस्ट पूरी तरह मुफ़्त हैं। मॉक टेस्ट असली CBT परीक्षा जैसा है – टाइमर, प्रश्न पैलेट, मार्क फॉर रिव्यू और तुरंत परिणाम।"],
        ["COPA MCQ मॉक टेस्ट में पासिंग मार्क कितने हैं?", "COPA MCQ मॉक टेस्ट में पासिंग मार्क 40% है। आपके डैशबोर्ड में हर टेस्ट का पास/फेल सहित पूरा रिज़ल्ट इतिहास रहता है।"],
        ["क्या मैं COPA प्रैक्टिकल ऑनलाइन कर सकता हूँ?", "हाँ। लाइव प्रैक्टिस लैब में आप ब्राउज़र में HTML, CSS, JavaScript और Python प्रोग्राम लिखकर तुरंत आउटपुट देख सकते हैं और प्रोग्राम डैशबोर्ड में सेव कर सकते हैं।"],
        ["क्या मैं COPA हिंदी या मराठी में पढ़ सकता हूँ?", "हाँ। हर प्रश्न, विकल्प और व्याख्या अंग्रेज़ी, हिंदी और मराठी में है। मेनू से कभी भी भाषा बदलें।"]],
      h1a: "कंप्यूटर युग में सीखने का अर्थ है कि हम आज ही", h1b: "कल के", h1c: "कौशल का अभ्यास करें",
      sub: "पूरा ITI COPA प्रश्न बैंक – अंग्रेज़ी, हिंदी और मराठी में 355 MCQ, वास्तविक CBT जैसे मॉक टेस्ट के साथ।",
      joinI: "अभ्यास शुरू करें", joinS: "मॉक टेस्ट दें", tagI: "COPA अनुदेशक", tagS: "ITI प्रशिक्षु",
      trust: ["सभी प्रशिक्षुओं के लिए निःशुल्क", "कोई नकारात्मक अंकन नहीं", "ऑफ़लाइन और मोबाइल पर चलता है"],
      popT: "हमारे सबसे", popH: "लोकप्रिय मॉड्यूल", popP: "वे मॉड्यूल जिनमें हर COPA प्रशिक्षु को निपुण होना चाहिए",
      explore: "मॉड्यूल देखें", lessons: "प्रश्न",
      catT: "देखें", catH: "सभी मॉड्यूल", catP: "मॉड्यूल चुनें और विषयवार अभ्यास करें",
      lpT: "लाइव", lpH: "कोडिंग प्रैक्टिस", lpP: "अपने COPA प्रैक्टिकल ऑनलाइन करें – HTML, CSS, JavaScript या Python लिखें और तुरंत आउटपुट देखें।", lpB: "लाइव प्रैक्टिस शुरू करें", lpB2: "Python आज़माएँ", lpF: ["टाइप करते समय लाइव प्रीव्यू", "Python ब्राउज़र में चलता है – इंस्टॉल की ज़रूरत नहीं", "24 तैयार COPA प्रैक्टिकल", "प्रोग्राम डैशबोर्ड में सेव करें"], whyT: "क्यों", whyH: "COPA MCQ?", whyP: "ITI COPA परीक्षा पास करने के लिए सब कुछ",
      f: [["वास्तविक CBT परीक्षा", "टाइमर, प्रश्न पैलेट, रिव्यू मार्क और ऑटो-सबमिट – बिलकुल ITI ऑनलाइन परीक्षा जैसा।"],
          ["3 भाषाएँ", "हर प्रश्न English, हिंदी या मराठी में पढ़ें और कभी भी बदलें।"],
          ["तुरंत फीडबैक", "मॉड्यूलवार अभ्यास करें और तुरंत सही उत्तर देखें।"],
          ["विस्तृत विश्लेषण", "हर टेस्ट के बाद स्कोर, परिणाम और मॉड्यूलवार ताकत।"]],
      st: ["मॉड्यूल", "MCQ प्रश्न", "भाषाएँ", "नकारात्मक अंकन"],
      bandH: "स्मार्ट तैयारी करें। ITI COPA परीक्षा में अधिक अंक पाएँ!",
      bandP: "60 मिनट टाइमर के साथ 50 प्रश्नों का पूरा CBT मॉक टेस्ट दें और तुरंत परिणाम पाएँ।",
      bandB: "मॉक टेस्ट दें", bandB2: "अध्ययन सामग्री",
      progT: "आपकी प्रगति", tests: "दिए गए टेस्ट", avg: "औसत स्कोर", prac: "अभ्यास किए प्रश्न",
      footP: "ITI कंप्यूटर ऑपरेटर एवं प्रोग्रामिंग असिस्टेंट (COPA) प्रशिक्षुओं के लिए निःशुल्क ऑनलाइन लर्निंग प्लेटफ़ॉर्म।",
      fh: ["सीखें", "अभ्यास", "संस्थान"], fl1: ["सभी मॉड्यूल", "अध्ययन सामग्री", "पायथन", "जावास्क्रिप्ट"],
      fl2: ["मॉक टेस्ट (CBT)", "क्विक टेस्ट", "मेरे परिणाम", "डैशबोर्ड"], fl3: ["परिचय", "संपर्क", "COPA MCQ", "महाराष्ट्र"],
      by: "COPA MCQ", byR: "ITI COPA"
    },
    mr: {
      nav: ["होम", "मॉड्युल्स", "मॉक टेस्ट", "अभ्यास", "निकाल", "माहिती"],
      signin: "लॉगिन", start: "मॉक टेस्ट सुरू करा",
      tg: [["तंत्रज्ञान जाणून घ्या,", "संगणक कौशल्यांवर", "प्रभुत्व मिळवा, तुमचे भविष्य घडवा."], ["डिजिटल जग कधीच थांबत नाही, म्हणून", "शिकत राहा", "आणि प्रगती करत राहा."], ["प्रत्येक क्लिक, प्रत्येक कोड, प्रत्येक कौशल्य तुम्हाला", "डिजिटल भविष्याच्या", "अधिक जवळ नेते."]],
      testiH: "आमचे विद्यार्थी काय म्हणतात", testiS: "ITI COPA प्रशिक्षणार्थींचे खरे रिव्ह्यू – Google साइन इनने लिहिलेले.", allRev: "सर्व रिव्ह्यू वाचा", writeRev: "रिव्ह्यू लिहा", rateOf: "{a}/5 · {n} रिव्ह्यू",
      faqH: "ITI COPA बद्दल वारंवार विचारले जाणारे प्रश्न", faqS: "COPA ट्रेड आणि COPA MCQ बद्दल आवश्यक माहिती.",
      faq: [["ITI मधील COPA ट्रेड म्हणजे काय?", "COPA (कॉम्प्युटर ऑपरेटर अँड प्रोग्रामिंग असिस्टंट) हा क्राफ्ट्समन ट्रेनिंग स्कीम अंतर्गत एक वर्षाचा ITI ट्रेड आहे. यात संगणकाच्या मूलभूत गोष्टी, Windows आणि Linux, MS Office, डेटाबेस, नेटवर्किंग, HTML, CSS आणि JavaScript ने वेब डिझाइन, ई-कॉमर्स, क्लाउड कॉम्प्युटिंग आणि Python शिकवले जाते."],
        ["COPA MCQ वर किती प्रश्न उपलब्ध आहेत?", "COPA MCQ वर 15 मॉड्यूल्समध्ये 355 बहुपर्यायी प्रश्न योग्य उत्तर आणि एका ओळीच्या स्पष्टीकरणासह इंग्रजी, हिंदी आणि मराठीमध्ये उपलब्ध आहेत."],
        ["COPA ऑनलाइन मॉक टेस्ट मोफत आहे का?", "हो. सर्व सराव, मॉड्यूल टेस्ट आणि पूर्ण CBT मॉक टेस्ट पूर्णपणे मोफत आहेत. मॉक टेस्ट खऱ्या CBT परीक्षेसारखी आहे – टायमर, प्रश्न पॅलेट, मार्क फॉर रिव्ह्यू आणि लगेच निकाल."],
        ["COPA MCQ मॉक टेस्टमध्ये पासिंग मार्क किती आहेत?", "COPA MCQ मॉक टेस्टमध्ये पासिंग मार्क 40% आहे. तुमच्या डॅशबोर्डमध्ये प्रत्येक टेस्टचा पास/नापास सह संपूर्ण निकाल इतिहास राहतो."],
        ["मी COPA प्रॅक्टिकल ऑनलाइन करू शकतो का?", "हो. लाइव्ह प्रॅक्टिस लॅबमध्ये तुम्ही ब्राउझरमध्ये HTML, CSS, JavaScript आणि Python प्रोग्राम लिहून लगेच आउटपुट पाहू शकता आणि प्रोग्राम डॅशबोर्डमध्ये सेव्ह करू शकता."],
        ["मी COPA हिंदी किंवा मराठीत शिकू शकतो का?", "हो. प्रत्येक प्रश्न, पर्याय आणि स्पष्टीकरण इंग्रजी, हिंदी आणि मराठीमध्ये आहे. मेनूमधून कधीही भाषा बदला."]],
      h1a: "संगणक युगात शिकणे म्हणजे आजच", h1b: "उद्याच्या", h1c: "कौशल्यांचा सराव करणे",
      sub: "संपूर्ण ITI COPA प्रश्नपेढी – इंग्रजी, हिंदी आणि मराठीमध्ये 355 MCQ, प्रत्यक्ष CBT सारख्या मॉक टेस्टसह.",
      joinI: "सराव सुरू करा", joinS: "मॉक टेस्ट द्या", tagI: "COPA निदेशक", tagS: "ITI प्रशिक्षणार्थी",
      trust: ["सर्व प्रशिक्षणार्थ्यांसाठी मोफत", "निगेटिव्ह मार्किंग नाही", "ऑफलाइन व मोबाइलवर चालते"],
      popT: "आमचे सर्वात", popH: "लोकप्रिय मॉड्युल्स", popP: "प्रत्येक COPA प्रशिक्षणार्थ्याने आत्मसात करावीत अशी मॉड्युल्स",
      explore: "मॉड्युल्स पहा", lessons: "प्रश्न",
      catT: "पहा", catH: "सर्व मॉड्युल्स", catP: "मॉड्युल निवडा आणि विषयनिहाय सराव करा",
      lpT: "लाइव्ह", lpH: "कोडिंग प्रॅक्टिस", lpP: "तुमचे COPA प्रॅक्टिकल ऑनलाइन करा – HTML, CSS, JavaScript किंवा Python लिहा आणि लगेच आउटपुट पहा.", lpB: "लाइव्ह प्रॅक्टिस सुरू करा", lpB2: "Python वापरून पहा", lpF: ["टाइप करताना लाइव्ह प्रिव्ह्यू", "Python ब्राउझरमध्ये चालते – इन्स्टॉलची गरज नाही", "24 तयार COPA प्रॅक्टिकल", "प्रोग्राम डॅशबोर्डमध्ये सेव्ह करा"], whyT: "का", whyH: "COPA MCQ?", whyP: "ITI COPA परीक्षा उत्तीर्ण होण्यासाठी आवश्यक सर्व काही",
      f: [["प्रत्यक्ष CBT परीक्षा", "टायमर, प्रश्न पॅलेट, रिव्ह्यू मार्क आणि ऑटो-सबमिट – अगदी ITI ऑनलाइन परीक्षेसारखे."],
          ["3 भाषा", "प्रत्येक प्रश्न English, हिंदी किंवा मराठीत वाचा आणि कधीही बदला."],
          ["लगेच फीडबॅक", "मॉड्युलनिहाय सराव करा आणि लगेच बरोबर उत्तर पहा."],
          ["सविस्तर विश्लेषण", "प्रत्येक टेस्टनंतर गुण, निकाल आणि मॉड्युलनिहाय ताकद."]],
      st: ["मॉड्युल्स", "MCQ प्रश्न", "भाषा", "निगेटिव्ह मार्किंग"],
      bandH: "स्मार्ट तयारी करा. ITI COPA परीक्षेत जास्त गुण मिळवा!",
      bandP: "60 मिनिटांच्या टायमरसह 50 प्रश्नांची पूर्ण CBT मॉक टेस्ट द्या आणि लगेच निकाल मिळवा.",
      bandB: "मॉक टेस्ट द्या", bandB2: "अभ्यास साहित्य",
      progT: "तुमची प्रगती", tests: "दिलेल्या टेस्ट", avg: "सरासरी गुण", prac: "सराव केलेले प्रश्न",
      footP: "ITI कॉम्प्युटर ऑपरेटर अँड प्रोग्रामिंग असिस्टंट (COPA) प्रशिक्षणार्थ्यांसाठी मोफत ऑनलाइन लर्निंग प्लॅटफॉर्म.",
      fh: ["शिका", "सराव", "संस्था"], fl1: ["सर्व मॉड्युल्स", "अभ्यास साहित्य", "पायथन", "जावास्क्रिप्ट"],
      fl2: ["मॉक टेस्ट (CBT)", "क्विक टेस्ट", "माझे निकाल", "डॅशबोर्ड"], fl3: ["माहिती", "संपर्क", "COPA MCQ", "महाराष्ट्र"],
      by: "COPA MCQ", byR: "ITI COPA"
    }
  };
  const X = TX[L] || TX.en;
  C.renderShell("home");
  const prof = C.getProfile();
  const st = C.getStats();

  /* ---------- illustrations ---------- */
  function avatar(kind) {
    const inst = kind === "i";
    const bg = inst ? "#e9e4ff" : "#ffd84d";
    const skin = inst ? "#e8b48f" : "#8a5536";
    const hair = inst ? "#3b2a22" : "#1d1410";
    const shirt = inst ? "#7fc4e8" : "#cfe6f7";
    return '<svg viewBox="0 0 132 160" aria-hidden="true"><rect width="132" height="160" fill="' + bg + '"/>' +
      '<circle cx="' + (inst ? 105 : 25) + '" cy="28" r="34" fill="#fff" opacity=".35"/>' +
      '<path d="M18 160c2-30 22-46 48-46s46 16 48 46z" fill="' + shirt + '"/>' +
      (inst ? '<path d="M52 116l14 18 14-18" fill="#fff"/>' : '<path d="M48 116c6 10 30 10 36 0l-4 44H52z" fill="#3f7dc2"/>') +
      '<rect x="57" y="96" width="18" height="22" rx="8" fill="' + skin + '"/>' +
      '<ellipse cx="66" cy="72" rx="26" ry="31" fill="' + skin + '"/>' +
      (inst
        ? '<path d="M40 66c0-24 12-34 27-34s26 10 26 33c-4-10-10-15-26-15-14 0-22 6-27 16z" fill="' + hair + '"/><path d="M42 80c2 20 12 28 24 28s22-8 24-28c-4 8-10 11-24 11s-20-3-24-11z" fill="' + hair + '" opacity=".85"/>' +
          '<rect x="47" y="68" width="15" height="10" rx="4" fill="none" stroke="#2a2a2a" stroke-width="2"/><rect x="70" y="68" width="15" height="10" rx="4" fill="none" stroke="#2a2a2a" stroke-width="2"/><path d="M62 72h8" stroke="#2a2a2a" stroke-width="2"/>'
        : '<path d="M39 70c-2-26 12-40 28-40s30 12 27 40c-2-12-6-18-10-20-6 4-26 4-34-2-6 4-9 12-11 22z" fill="' + hair + '"/>' +
          '<circle cx="56" cy="73" r="2.6" fill="#1a1a1a"/><circle cx="76" cy="73" r="2.6" fill="#1a1a1a"/>') +
      '<path d="M57 90c5 5 13 5 18 0" stroke="' + (inst ? "#fff" : "#4a2a1a") + '" stroke-width="2.6" fill="none" stroke-linecap="round"/></svg>';
  }

  let wi = 0;
  const words = (txt, cls) => String(txt).split(/\s+/).filter(Boolean).map((w) => '<span class="w' + (cls ? " " + cls : "") + '" style="--d:' + (0.1 + 0.07 * wi++).toFixed(2) + 's">' + esc(w) + "</span>").join(" ");
  const modImg = (m) => C.ROOT + "assets/img/modules/m" + C.pad(m) + ".jpg";

  const FEATURED = [
    { m: 1, art: "computer", th: "blue", tag: "Basics", tc: "#16b364" },
    { m: 5, art: "doc", th: "lav", tag: "Office", tc: "#0ea5e9" },
    { m: 8, art: "db", th: "vio", tag: "Database", tc: "#ef4444" },
    { m: 10, art: "code", th: "indigo", tag: "Web", tc: "#7c5cff" },
    { m: 15, art: "python", th: "indigo", tag: "Python", tc: "#ff6b1a" },
    { m: 12, art: "chart", th: "pink", tag: "Excel", tc: "#3b82f6" }
  ];
  const CAT_COLORS = ["#3b82f6", "#0078d4", "#ef4444", "#334155", "#2b579a", "#16b364", "#f97316", "#0ea5e9", "#8b5cf6", "#ef6c3a", "#eab308", "#10b981", "#ff6b1a", "#06b6d4", "#3572a5"];

  const href = ["index.html", "modules.html", "exam.html", "study.html", "dashboard.html", "about.html"];
  const icons = ["fa-house", "fa-layer-group", "fa-stopwatch", "fa-book-open", "fa-chart-simple", "fa-circle-info"];
  const ringV = st.tests ? st.avg : (st.practiced ? Math.round((st.practicedCorrect / st.practiced) * 100) : 0);
  const stars = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>';

  document.getElementById("lp").innerHTML =
    /* HERO */
    '<section class="lp-hero"><div class="lp-wrap"><div class="lp-frame">' +
    '<span class="lp-deco q"></span><span class="lp-deco sq"></span>' +
    '<div class="lp-avas"><div class="lp-ava l"><div class="ph">' + avatar("i") + '</div><span class="tag"><i class="fa-solid fa-chalkboard-user"></i>' + X.tagI + "</span></div>" +
    '<div class="lp-ava r"><div class="ph">' + avatar("s") + '</div><span class="tag"><i class="fa-solid fa-graduation-cap"></i>' + X.tagS + "</span></div></div>" +
    '<h1 class="lp-h1" aria-live="polite">' + [[X.h1a, X.h1b, X.h1c]].concat(X.tg).map((h, i) => { wi = 0;
      return '<span class="h1s' + (i ? "" : " on") + '"' + (i ? ' aria-hidden="true"' : "") + ">" + words(h[0], "") + " " + words(h[1], "hl-o shine") + (i ? " " : "<br>") + '<span class="sw">' + words(h[2], "hl-p shine") +
        (i ? "" : '<svg viewBox="0 0 70 40" aria-hidden="true"><path class="draw" d="M66 6C50 30 26 34 6 26" stroke="#b9a8ff" stroke-width="3" fill="none" stroke-linecap="round"/><path class="draw d2" d="M14 18l-8 8 10 4" stroke="#b9a8ff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>') + "</span></span>"; }).join("") + "</h1>" +
    '<p class="sub rv">' + esc(X.sub) + "</p>" +
    '<div class="lp-cta rv"><span class="lp-arrow l"></span><span class="lp-arrow r"></span>' +
    '<a class="lp-btn o" href="modules.html"><i class="fa-solid fa-pen-to-square"></i> ' + X.joinI + '</a><a class="lp-btn p" href="exam.html"><i class="fa-solid fa-stopwatch"></i> ' + X.joinS + "</a></div>" +
    '<div class="lp-trust rv">' + X.trust.map((s) => '<span><i class="fa-solid fa-circle-check"></i>' + s + "</span>").join("") + "</div>" +
    '<a class="lp-rate" id="lpRate" href="reviews.html" hidden></a>' +
    "</div>" +
    '<div class="lp-strip rv" aria-label="Topics covered"><span><i class="fa-brands fa-windows"></i>Windows</span><span><i class="fa-brands fa-linux"></i>Linux</span><span><i class="fa-brands fa-html5"></i>HTML5</span>' +
    '<span><i class="fa-brands fa-js"></i>JavaScript</span><span><i class="fa-brands fa-python"></i>Python</span><span><i class="fa-solid fa-database"></i>SQL</span><span><i class="fa-solid fa-cloud"></i>Cloud</span></div>' +
    "</div></section>" +

    /* POPULAR */
    '<section class="lp-sec tint"><div class="lp-wrap" style="position:relative">' +
    '<span class="lp-deco tri" style="left:46%;top:-18px"></span>' +
    '<div class="lp-head rv"><div><h2>' + X.popT + ' <span class="hl-p">' + X.popH + "</span></h2><p>" + X.popP + '</p></div><a class="lp-more" href="modules.html">' + X.explore + ' <i class="fa-solid fa-arrow-right" style="transform:rotate(-45deg)"></i></a></div>' +
    '<div class="lp-courses">' + FEATURED.map((f, i) => {
      const md = C.MODULES[f.m - 1];
      const n = C.byModule(f.m).length;
      return '<a class="lp-card rv" href="' + C.modulePage(f.m) + '" style="transition-delay:' + (i % 3) * 80 + 'ms"><div class="art"><img src="' + modImg(f.m) + '" alt="' + esc(C.modName(md)) + '" loading="lazy" width="960" height="540"></div>' +
        '<div class="bd"><div class="meta"><span class="ls"><i style="background:' + f.tc + '" class="fa-solid fa-circle-play"></i>' + n + " " + X.lessons + '</span><span class="stars">' + stars + "</span></div>" +
        "<h3>" + esc(C.modName(md)) + "</h3>" +
        '<div class="ft"><span class="who"><span class="av">' + X.by.charAt(0) + "</span><span>" + esc(X.by) + "<small>" + X.byR + " • M" + f.m + "</small></span></span>" +
        '<span class="lp-pill" style="background:' + f.tc + '">' + f.tag + "</span></div></div></a>";
    }).join("") + "</div></div></section>" +

    '<div class="lp-wrap"><div class="ad-slot" data-slot="middle"></div></div>' +
    /* CATEGORIES */
    '<section class="lp-sec"><div class="lp-wrap" style="position:relative"><span class="lp-deco dot" style="right:22%;top:24px"></span>' +
    '<div class="lp-head center rv"><h2>' + X.catT + ' <span class="hl-p">' + X.catH + "</span></h2><p>" + X.catP + "</p></div>" +
    '<div class="lp-cats">' + C.MODULES.map((m, i) => '<a class="lp-cat rv" href="' + C.modulePage(m.id) + '" style="transition-delay:' + (i % 5) * 50 + 'ms"><span class="ci" style="background:' + CAT_COLORS[i] + ";--sh:" + CAT_COLORS[i] + '55"><i class="' + C.iconCls(m) + '"></i></span><b>' + esc(C.modName(m)) + "</b><small>M" + m.id + " • " + C.byModule(m.id).length + " " + X.lessons + "</small></a>").join("") +
    "</div></div></section>" +

    /* LIVE PRACTICE */
    '<section class="lp-sec"><div class="lp-wrap"><div class="lp-live rv"><div class="ll-txt"><span class="ll-badge"><i class="fa-solid fa-circle"></i> LIVE</span><h2>' + X.lpT + ' <span class="hl-o">' + X.lpH + "</span></h2><p>" + X.lpP + "</p>" +
    '<ul>' + X.lpF.map((f) => '<li><i class="fa-solid fa-circle-check"></i>' + f + "</li>").join("") + "</ul>" +
    '<div class="ll-cta"><a class="lp-btn p" href="live-practice.html"><i class="fa-solid fa-laptop-code"></i> ' + X.lpB + '</a><a class="lp-btn ghost" href="live-practice.html?lang=python"><i class="fa-brands fa-python"></i> ' + X.lpB2 + "</a></div></div>" +
    '<a class="ll-demo" href="live-practice.html" aria-label="' + esc(X.lpB) + '"><div class="ll-code"><div class="ll-bar"><i style="background:#ff5f57"></i><i style="background:#febc2e"></i><i style="background:#28c840"></i><span>index.html</span><span>style.css</span><span>script.js</span></div>' +
    '<pre><span class="t">&lt;h1&gt;</span>Hello COPA<span class="t">&lt;/h1&gt;</span>\n<span class="t">&lt;button</span> <span class="a">onclick</span>=<span class="s">"hi()"</span><span class="t">&gt;</span>Click<span class="t">&lt;/button&gt;</span>\n\n<span class="k">function</span> <span class="f">hi</span>() {\n  <span class="f">alert</span>(<span class="s">"Welcome!"</span>);\n}</pre></div>' +
    '<div class="ll-out"><div class="ll-ob"><span class="ll-dot"></span>Live Output</div><div class="ll-page"><b>Hello COPA</b><span class="ll-btn">Click</span></div></div>' +
    '<div class="ll-py"><span class="ll-pyb"><i class="fa-brands fa-python"></i> main.py</span><code>print("PASS")</code><code class="o">PASS</code></div></a></div></div></section>' +
    /* WHY */
    '<section class="lp-sec tint"><div class="lp-wrap"><div class="lp-head center rv"><h2>' + X.whyT + ' <span class="hl-p">' + X.whyH + "</span></h2><p>" + X.whyP + "</p></div>" +
    '<div class="lp-feat">' + X.f.map((f, i) => {
      const c = [["#f3f0ff", "#7c5cff", "fa-laptop-code"], ["#fff1e8", "#ff6b1a", "fa-language"], ["#e8f8ef", "#16b364", "fa-bolt"], ["#e8f2ff", "#3b82f6", "fa-chart-pie"]][i];
      return '<div class="lp-f rv"><span class="fi" style="background:' + c[0] + ";color:" + c[1] + '"><i class="fa-solid ' + c[2] + '"></i></span><h3>' + f[0] + "</h3><p>" + f[1] + "</p></div>";
    }).join("") + "</div>" +
    '<div class="lp-stats rv"><div><b>15</b><span>' + X.st[0] + "</span></div><div><b>" + C.QUESTIONS.length + "+</b><span>" + X.st[1] + "</span></div><div><b>3</b><span>" + X.st[2] + "</span></div><div><b>0</b><span>" + X.st[3] + "</span></div></div>" +
    "</div></section>" +

    '<div class="lp-wrap"><div class="ad-slot" data-slot="bottom"></div></div>' +
    /* CTA BAND */
    '<section class="lp-sec" style="padding-bottom:0"><div class="lp-wrap"><div class="lp-band rv"><div><h2>' + X.bandH + "</h2><p>" + X.bandP + "</p>" +
    '<div class="cta"><a class="lp-btn o" href="exam.html"><i class="fa-solid fa-play"></i> ' + X.bandB + '</a><a class="lp-btn w" href="study.html"><i class="fa-solid fa-book-open"></i> ' + X.bandB2 + "</a></div></div>" +
    '<div class="lp-prog"><h4><i class="fa-solid fa-chart-simple"></i> ' + X.progT + '</h4><div class="row"><div class="lp-ring" style="--v:' + ringV + '" data-l="' + ringV + '%"></div>' +
    "<ul><li>" + X.tests + ": <b>" + st.tests + "</b></li><li>" + X.avg + ": <b>" + st.avg + "%</b></li><li>" + X.prac + ": <b>" + st.practiced + "</b></li></ul></div>" +
    '<a class="lp-btn w sm" style="margin-top:16px;width:100%" href="dashboard.html">' + t("viewReport") + ' <i class="fa-solid fa-arrow-right"></i></a></div></div></div></section>' +

    /* REVIEWS (filled from the server) */
    '<section class="lp-sec lp-testi" id="lpTesti" hidden><div class="lp-wrap"><div class="lp-head rv"><div><h2>' + esc(X.testiH) + "</h2><p>" + esc(X.testiS) + '</p></div><div class="lp-testi-btns"><a class="lp-btn w sm" href="reviews.html#rvWrite"><i class="fa-solid fa-pen-to-square"></i> ' + esc(X.writeRev) + '</a><a class="lp-btn p sm" href="reviews.html">' + esc(X.allRev) + ' <i class="fa-solid fa-arrow-right"></i></a></div></div><div class="lp-testi-grid" id="lpTestiG"></div></div></section>' +

    /* FAQ */
    '<section class="lp-sec lp-faq" aria-labelledby="faqH"><div class="lp-wrap"><div class="lp-head rv"><h2 id="faqH">' + esc(X.faqH) + "</h2><p>" + esc(X.faqS) + "</p></div>" +
    '<div class="faq-list">' + X.faq.map((f, i) => '<details class="faq-item rv"' + (i ? "" : " open") + '><summary><span>' + esc(f[0]) + '</span><i class="fa-solid fa-plus"></i></summary><p>' + esc(f[1]) + "</p></details>").join("") + "</div></div></section>" +

    /* FOOTER */
    '<footer class="lp-foot"><div class="lp-wrap"><div class="cols"><div><a class="lp-logo" href="index.html"><img class="mk-img" src="assets/img/logo.webp" width="256" height="205" alt="COPA MCQ logo"><span>COPA<b> MCQ</b>.</span></a><p>' + X.footP + "</p>" +
    '<div class="lp-soc"><a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a><a href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a><a href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></div></div>' +
    "<div><h5>" + X.fh[0] + "</h5><ul>" + [["modules.html", 0], ["study.html", 1], [C.modulePage(15), 2], [C.modulePage(11), 3]].map((a) => '<li><a href="' + a[0] + '">' + X.fl1[a[1]] + "</a></li>").join("") + "</ul></div>" +
    "<div><h5>" + X.fh[1] + "</h5><ul>" + [["exam.html", 0], ["exam.html", 1], ["dashboard.html", 2], ["dashboard.html", 3]].map((a) => '<li><a href="' + a[0] + '">' + X.fl2[a[1]] + "</a></li>").join("") + "</ul></div>" +
    "<div><h5>" + X.fh[2] + "</h5><ul>" + [["about.html", X.fl3[0]], ["about.html#contact", X.fl3[1]], ["privacy.html", "Privacy Policy"], ["terms.html", "Terms & Conditions"], ["admin.html", '<i class="fa-solid fa-user-shield"></i> Admin Login']].map((a) => '<li><a href="' + a[0] + '">' + a[1] + "</a></li>").join("") + "</ul></div></div>" +
    '<div class="bot"><span>© 2019 - ' + Math.max(2026, new Date().getFullYear()) + " COPA MCQ. " + t("footerRights") + '</span><span>Developed &amp; Designed by : <b style="color:#fff">Sanjay Rathod</b> (CI - COPA)</span></div></div></footer>';

  /* ---------- behaviour ---------- */
  const els = document.querySelectorAll(".rv");
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((ents) => ents.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: 0.12 });
    els.forEach((el) => io.observe(el));
  } else els.forEach((el) => el.classList.add("in"));

  /* ---------- Diwali rockets behind the hero (until 11 Nov 2026, 00:00 IST – then it removes itself) ---------- */
  (function diwali() {
    const END = Date.parse("2026-11-11T00:00:00+05:30");
    if (Date.now() >= END) return;
    const frame = document.querySelector(".lp-frame"); if (!frame) return;
    const cv = document.createElement("canvas"); cv.className = "lp-fw"; cv.setAttribute("aria-hidden", "true");
    frame.prepend(cv); frame.classList.add("fw");
    const cx = cv.getContext("2d");
    const COLORS = ["#ffb300", "#ff6d00", "#ff1744", "#f50057", "#d500f9", "#651fff", "#2979ff", "#00c853", "#ffd600", "#ff3d00"];
    let W = 0, H = 0, dpr = 1, rockets = [], sparks = [], flashes = [], last = 0, nextLaunch = 0, running = false, visible = true, raf = 0;
    const mobile = () => W < 640;
    const size = () => { dpr = Math.min(2, window.devicePixelRatio || 1); W = frame.clientWidth; H = frame.clientHeight; cv.width = W * dpr; cv.height = H * dpr; cx.setTransform(dpr, 0, 0, dpr, 0, 0); };
    const rnd = (a, b) => a + Math.random() * (b - a);
    function launch() {
      const x = rnd(W * 0.06, W * 0.94);
      const ty = rnd(H * 0.1, H * 0.5);                       // explode in the upper part
      const vy = -Math.sqrt(2 * 0.12 * (H - ty)) * rnd(0.95, 1.05);
      rockets.push({ x, y: H + 4, vx: rnd(-0.6, 0.6), vy, color: COLORS[(Math.random() * COLORS.length) | 0], trail: [] });
    }
    function burst(r) {
      const n = mobile() ? 48 : 84, c2 = COLORS[(Math.random() * COLORS.length) | 0], ring = Math.random() < 0.35;
      for (let i = 0; i < n; i++) {
        const a = (i / n) * Math.PI * 2 + rnd(-0.05, 0.05), sp = (ring ? 4.4 : rnd(1.6, 5.4)) * (mobile() ? 0.75 : 1);
        sparks.push({ x: r.x, y: r.y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, life: 1, decay: rnd(0.010, 0.018), color: Math.random() < 0.7 ? r.color : c2, trail: [], glitter: Math.random() < 0.25 });
      }
      // golden crackle in the middle
      flashes.push({ x: r.x, y: r.y, life: 1, rad: mobile() ? 70 : 110, color: r.color });
      for (let i = 0; i < 14; i++) sparks.push({ x: r.x, y: r.y, vx: rnd(-1.2, 1.2), vy: rnd(-1.2, 1.2), life: 1, decay: rnd(0.025, 0.04), color: "#ffd54f", trail: [], glitter: true });
    }
    function step(t) {
      raf = 0;
      if (!running) return;
      if (Date.now() >= END) { stop(true); return; }
      const dt = Math.min(2.5, last ? (t - last) / 16.67 : 1); last = t;
      if (t > nextLaunch) { launch(); if (Math.random() < 0.3) launch(); nextLaunch = t + rnd(mobile() ? 900 : 600, mobile() ? 1800 : 1300); }
      cx.clearRect(0, 0, W, H);
      cx.globalCompositeOperation = "source-over";
      // soft golden glow where a rocket bursts
      for (let i = flashes.length - 1; i >= 0; i--) {
        const f = flashes[i]; f.life -= 0.035 * dt; if (f.life <= 0) { flashes.splice(i, 1); continue; }
        const g = cx.createRadialGradient(f.x, f.y, 0, f.x, f.y, f.rad * (1.3 - f.life * 0.3));
        g.addColorStop(0, "rgba(255,214,102," + 0.45 * f.life + ")"); g.addColorStop(1, "rgba(255,214,102,0)");
        cx.fillStyle = g; cx.beginPath(); cx.arc(f.x, f.y, f.rad * 1.3, 0, 7); cx.fill();
      }
      // rockets going up with a sparkling tail
      for (let i = rockets.length - 1; i >= 0; i--) {
        const r = rockets[i];
        r.trail.push([r.x, r.y]); if (r.trail.length > 10) r.trail.shift();
        r.x += r.vx * dt; r.y += r.vy * dt; r.vy += 0.12 * dt;
        cx.lineCap = "round";
        for (let k = 1; k < r.trail.length; k++) {
          cx.strokeStyle = "rgba(255,170,40," + (k / r.trail.length) * 0.9 + ")"; cx.lineWidth = 1 + (k / r.trail.length) * 2.2;
          cx.beginPath(); cx.moveTo(r.trail[k - 1][0], r.trail[k - 1][1]); cx.lineTo(r.trail[k][0], r.trail[k][1]); cx.stroke();
        }
        cx.fillStyle = "#fff3c4"; cx.beginPath(); cx.arc(r.x, r.y, 2.4, 0, 7); cx.fill();
        if (Math.random() < 0.5) sparks.push({ x: r.x, y: r.y, vx: rnd(-0.5, 0.5), vy: rnd(0.5, 1.5), life: 0.7, decay: 0.05, color: "#ffb300", trail: [], glitter: false });
        if (r.vy >= -0.4) { burst(r); rockets.splice(i, 1); }
      }
      // explosion sparks
      for (let i = sparks.length - 1; i >= 0; i--) {
        const p = sparks[i];
        p.trail.push([p.x, p.y]); if (p.trail.length > 5) p.trail.shift();
        p.vx *= 0.985; p.vy = p.vy * 0.985 + 0.045 * dt; p.x += p.vx * dt; p.y += p.vy * dt; p.life -= p.decay * dt;
        if (p.life <= 0) { sparks.splice(i, 1); continue; }
        const al = p.glitter ? p.life * (Math.random() < 0.5 ? 1 : 0.25) : p.life;
        cx.globalAlpha = Math.max(0, al) * 0.95; cx.strokeStyle = p.color; cx.lineWidth = 2.6;
        if (p.trail.length > 1) { cx.beginPath(); cx.moveTo(p.trail[0][0], p.trail[0][1]); for (const q of p.trail) cx.lineTo(q[0], q[1]); cx.stroke(); }
        cx.fillStyle = p.color; cx.beginPath(); cx.arc(p.x, p.y, 2.3, 0, 7); cx.fill();
      }
      cx.globalAlpha = 1;
      raf = requestAnimationFrame(step);
    }
    function start() { if (running || !visible || document.hidden) return; running = true; last = 0; raf = requestAnimationFrame(step); }
    function stop(forever) {
      running = false; if (raf) cancelAnimationFrame(raf); raf = 0;
      if (forever) { cv.remove(); frame.classList.remove("fw"); window.removeEventListener("resize", size); }
    }
    size(); window.addEventListener("resize", size);
    if ("IntersectionObserver" in window) new IntersectionObserver((e) => { visible = e[0].isIntersecting; visible ? start() : stop(false); }).observe(frame);
    document.addEventListener("visibilitychange", () => (document.hidden ? stop(false) : start()));
    setTimeout(() => { if (Date.now() >= END) stop(true); }, Math.max(0, Math.min(END - Date.now(), 2147483000)));   // page left open past the end time
    start();
  })();

  /* website rating + top reviews (MySQL mode) */
  (function reviews() {
    const SRV = C.server; if (!SRV || !SRV.reviews) return;
    const stars = (v) => '<span class="lp-stars"><span class="bg">★★★★★</span><span class="fg" style="width:' + (v / 5) * 100 + '%">★★★★★</span></span>';
    SRV.reviews({ sort: "top", limit: 3 }).then((d) => {
      const S = d.summary; if (!S || !S.count) return;
      const r = document.getElementById("lpRate");
      r.innerHTML = stars(S.avg) + "<b>" + S.avg.toFixed(1) + "</b><span>" + esc(X.rateOf.replace("{a}", S.avg.toFixed(1)).replace("{n}", S.count)) + "</span>"; r.hidden = false;
      const items = (d.items || []).filter((x) => x.rating >= 4); if (!items.length) return;
      document.getElementById("lpTestiG").innerHTML = items.map((x) => '<figure class="lp-testi-card"><i class="fa-solid fa-quote-left q"></i>' + stars(x.rating) + (x.title ? "<h4>" + esc(x.title) + "</h4>" : "") +
        "<blockquote>" + esc(x.body.length > 220 ? x.body.slice(0, 217) + "…" : x.body) + "</blockquote><figcaption>" +
        (x.picture ? '<img src="' + esc(x.picture) + '" alt="" referrerpolicy="no-referrer" loading="lazy" onerror="this.remove()">' : "") + "<b>" + esc(x.name) + "</b></figcaption></figure>").join("");
      const sec = document.getElementById("lpTesti"); sec.hidden = false;
      sec.querySelectorAll(".rv").forEach((e) => e.classList.add("in"));
    }).catch(() => {});
  })();

  /* headline: same design – the text is typed, erased and the next one typed */
  (function rotate() {
    const h1 = document.querySelector(".lp-h1"), sl = h1 ? h1.querySelectorAll(".h1s") : [];
    if (!sl.length) return;
    let i = 0;
    const show = (k) => sl.forEach((x, j) => { x.classList.toggle("on", j === k); if (j === k) x.removeAttribute("aria-hidden"); else x.setAttribute("aria-hidden", "true"); });
    // split every word into letters (grapheme clusters keep हिंदी / मराठी letters whole)
    const seg = window.Intl && Intl.Segmenter ? new Intl.Segmenter(L, { granularity: "grapheme" }) : null;
    const split = (t) => (seg ? Array.from(seg.segment(t), (x) => x.segment) : Array.from(t));
    const chars = Array.from(sl).map((x) => {
      const list = [];
      x.querySelectorAll(".w").forEach((w, k, all) => {
        const parts = split(w.textContent); w.textContent = "";
        w.classList.add("wait");
        parts.forEach((ch) => { const c = document.createElement("span"); c.className = "c hid"; c.textContent = ch; w.appendChild(c); list.push(c); });
        if (k < all.length - 1) list.push(null); // pause for the space between words
      });
      return list;
    });
    const caret = document.createElement("span"); caret.className = "lp-caret"; caret.setAttribute("aria-hidden", "true");
    // keep the full height of the tallest sentence reserved, so nothing below moves while typing
    const measure = () => { h1.style.minHeight = ""; h1.classList.remove("typed"); const hh = h1.offsetHeight; h1.classList.add("typed"); h1.style.minHeight = hh + "px"; };
    measure();
    let rz; addEventListener("resize", () => { clearTimeout(rz); rz = setTimeout(measure, 150); });
    const put = (list, n) => { // caret after the n-th letter
      let c = null; for (let k = n - 1; k >= 0 && !c; k--) c = list[k];
      if (c) c.after(caret); else sl[i].prepend(caret);
    };
    const reveal = (c, on) => { c.classList.toggle("hid", !on); const w = c.parentNode; w.classList.toggle("wait", !w.querySelector(".c:not(.hid)")); };
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));
    const visible = async () => { while (document.hidden) await wait(400); };
    (async function loop() {
      await wait(500);
      for (;;) {
        const x = sl[i], list = chars[i];
        show(i); x.classList.remove("done"); list.forEach((c) => c && reveal(c, false));
        h1.classList.add("typing"); put(list, 0);
        for (let n = 1; n <= list.length; n++) {
          await visible();
          if (list[n - 1]) reveal(list[n - 1], true);
          put(list, n);
          await wait(list[n - 1] ? 45 + Math.random() * 45 : 90);
        }
        h1.classList.remove("typing"); x.classList.add("done");
        if (sl.length < 2) return;
        await wait(3500); // next sentence after 3.5 seconds
        h1.classList.add("typing");
        for (let n = list.length; n > 0; n--) { await visible(); if (list[n - 1]) reveal(list[n - 1], false); put(list, n - 1); await wait(16); }
        h1.classList.remove("typing");
        await wait(350);
        i = (i + 1) % sl.length;
      }
    })();
  })();
})();
