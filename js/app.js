/* =========================================================
   COPA MCQ  |  Core: i18n, layout, storage
   ========================================================= */
(function () {
  "use strict";

  const ROOT = document.body.getAttribute("data-root") || "";

  /* ---------- Modules ---------- */
  const MODULES = [
    { id: 1, icon: "fa-desktop", color: "#1554e0", bg: "#e6eeff", en: "Introduction to Computer System", hi: "कंप्यूटर सिस्टम का परिचय", mr: "संगणक प्रणालीची ओळख" },
    { id: 2, icon: "fa-brands fa-windows", color: "#0078d4", bg: "#e3f1fc", en: "Windows OS", hi: "विंडोज़ ऑपरेटिंग सिस्टम", mr: "विंडोज ऑपरेटिंग सिस्टम" },
    { id: 3, icon: "fa-power-off", color: "#e5383b", bg: "#fdeaea", en: "Booting Process", hi: "बूटिंग प्रक्रिया", mr: "बूटिंग प्रक्रिया" },
    { id: 4, icon: "fa-brands fa-linux", color: "#222c45", bg: "#e9ecf3", en: "DOS Interface & Linux OS", hi: "DOS इंटरफ़ेस और लिनक्स OS", mr: "DOS इंटरफेस आणि लिनक्स OS" },
    { id: 5, icon: "fa-file-word", color: "#2b579a", bg: "#e4ecf8", en: "Word Processing Software", hi: "वर्ड प्रोसेसिंग सॉफ्टवेयर", mr: "वर्ड प्रोसेसिंग सॉफ्टवेअर" },
    { id: 6, icon: "fa-file-excel", color: "#18a957", bg: "#e3f7ec", en: "Spreadsheet Application", hi: "स्प्रेडशीट एप्लिकेशन", mr: "स्प्रेडशीट ॲप्लिकेशन" },
    { id: 7, icon: "fa-file-powerpoint", color: "#d24726", bg: "#fdebe5", en: "PowerPoint Presentation", hi: "पावरपॉइंट प्रेजेंटेशन", mr: "पॉवरपॉइंट प्रेझेंटेशन" },
    { id: 8, icon: "fa-database", color: "#0f6fc6", bg: "#e2effb", en: "Database Concepts (SQL & DBMS)", hi: "डेटाबेस अवधारणाएँ (SQL और DBMS)", mr: "डेटाबेस संकल्पना (SQL आणि DBMS)" },
    { id: 9, icon: "fa-network-wired", color: "#7b3fe4", bg: "#f0e8ff", en: "Network Communication & Concepts", hi: "नेटवर्क संचार और अवधारणाएँ", mr: "नेटवर्क कम्युनिकेशन आणि संकल्पना" },
    { id: 10, icon: "fa-code", color: "#e44d26", bg: "#fdeae4", en: "Web Design Concepts (HTML & CSS)", hi: "वेब डिज़ाइन अवधारणाएँ (HTML और CSS)", mr: "वेब डिझाइन संकल्पना (HTML आणि CSS)" },
    { id: 11, icon: "fa-brands fa-js", color: "#c9a100", bg: "#fff6d6", en: "JavaScript Introduction & Web Hosting", hi: "जावास्क्रिप्ट परिचय और वेब होस्टिंग", mr: "जावास्क्रिप्ट ओळख आणि वेब होस्टिंग" },
    { id: 12, icon: "fa-chart-column", color: "#107c41", bg: "#e1f3e9", en: "Advance Excel & Power BI", hi: "एडवांस एक्सेल और पावर BI", mr: "ॲडव्हान्स एक्सेल आणि पॉवर BI" },
    { id: 13, icon: "fa-cart-shopping", color: "#f58a07", bg: "#fff1df", en: "E-Commerce", hi: "ई-कॉमर्स", mr: "ई-कॉमर्स" },
    { id: 14, icon: "fa-cloud", color: "#0fa3b1", bg: "#e0f6f8", en: "Cloud Computing & Software Development", hi: "क्लाउड कंप्यूटिंग और सॉफ्टवेयर विकास", mr: "क्लाउड कॉम्प्युटिंग आणि सॉफ्टवेअर विकास" },
    { id: 15, icon: "fa-brands fa-python", color: "#3572a5", bg: "#e5eef7", en: "Introduction to Python", hi: "पायथन का परिचय", mr: "पायथनची ओळख" }
  ];

  /* ---------- UI strings ---------- */
  const I18N = {
    en: {
      readNotes: "Read full notes", printNotes: "Print", netErr: "Could not connect to the server. Check your internet and try again.", gOr: "or", gStep: "One more step", gStepNote: "Enter your Roll No once. Next time just tap “Sign in with Google”.", gLinkPin: "This Roll No already has an account. Enter its 4-digit PIN once to connect your Google account.", gContinue: "Continue", sessionEnded: "Please log in again.",
      noPrograms: "No practicals saved yet. Open Live Practice, write a program and click Save.", 
      livePractice: "Live Practice", reviews: "Reviews", myPracticals: "My Live Practicals", 
      whyAns: "Explanation", 
      pin: "4-digit PIN", pinNote: "Remember your Roll No. and PIN – you need them to log in again on this computer.", createAcc: "Create Account", pinErr: "PIN must be exactly 4 digits.", rollExists: "This Roll No. is already registered. Please login.", noAccount: "No account found for this Roll No. Please register first.", wrongPin: "Wrong PIN. Please try again.",
      myDash: "My Dashboard", hello: "Hello", memberSince: "Member since", lastLogin: "Last login", passed: "Passed", failed: "Failed", passRate: "Pass Rate", lastScore: "Last Score",
      scoreTrend: "Score Trend", scoreTrendDesc: "Percentage in each test (dashed line = 40% pass mark)", testHistory: "Test History", allTests: "All tests saved for this student",
      loginToSee: "Login to see your dashboard", loginToSeeDesc: "Create a free student account with your Roll No. and a 4-digit PIN. All your test results will be saved in your dashboard.",
      loginFirst: "Please login first – your test result will be saved in your dashboard.", strong: "Strong", weak: "Needs Practice", modulePerf: "Module-wise Performance (all tests)", noTestModules: "Attempt a test to see module-wise performance.",
      questionsAns: "Questions", practiceProgress: "Practice Progress",
      home: "Home", dashboard: "Dashboard", practice: "MCQ Practice", mock: "Mock Test", onlineTest: "Online Test (CBT)",
      subjects: "Subjects", study: "Study Material", results: "Results", about: "About", contact: "Contact Us", login: "Login", logout: "Logout",
      tagline: "Learn • Practice • Excel", iti: "Industrial Training Institute", maharashtra: "Maharashtra",
      dream: "Dream Big… Prepare Well…", getSuccess: "Get Success…",
      welcome: "Welcome to COPA MCQ", heroLead: "Your Complete Online Learning Platform for ITI COPA Trade",
      f1: "Topic-wise MCQs", f2: "CBT style Mock Tests", f3: "Study Material in 3 languages",
      startPracticing: "Start Practicing", beExpert: "Be a COPA Expert!",
      studentLogin: "Student Login", accessDash: "Access your dashboard", or: "or", register: "Register Now",
      welcomeBack: "Welcome back", viewReport: "View Detailed Report",
      statSubjects: "COPA Modules", statQ: "MCQ Questions", statMock: "Mock Test Sets", statLang: "Languages",
      explore: "Explore COPA Modules", viewAll: "View All Modules", yourProgress: "Your Progress",
      testsAttempted: "Tests Attempted", avgScore: "Average Score", questionsPracticed: "Questions Practiced",
      prepare1: "Prepare Smart…", prepare2: "Score High in", prepare3: "Exam!", takeMock: "Take a Mock Test",
      quickLinks: "Quick Links", qPractice: "Practice MCQ", qTest: "Take Test", qNotes: "Study Notes", qResults: "Check Results",
      footerRights: "All Rights Reserved.", footerTag: "Learn Today… Lead Tomorrow…",
      questions: "Questions", module: "Module", modules: "Modules", practiceMode: "Practice", studyMode: "Study (Q&A)", testMode: "Module Test",
      question: "Question", of: "of", prev: "Previous", next: "Next", correct: "Correct", wrong: "Wrong", correctAns: "Correct answer",
      wellDone: "Well done! Correct answer.", oops: "Oops! Wrong answer.", score: "Score", attempted: "Attempted", reset: "Reset",
      shuffle: "Shuffle", search: "Search questions…", allModules: "All Modules", print: "Print / PDF", showAns: "Show answers",
      startTest: "Start Test", startPractice: "Start Practice", openStudy: "Study Q&A",
      allModulesDesc: "15 modules • 355 questions from the COPA question bank. Choose a module to practise, study or take a test.",
      setupTitle: "Online CBT Mock Test", setupDesc: "Exam experience exactly like the ITI CBT. Choose a test pattern and modules.",
      pattern: "Test Pattern", selectModules: "Select Modules", selectAll: "Select all", clearAll: "Clear",
      numQ: "Number of Questions", duration: "Duration (minutes)", examLang: "Default question language", shuffleOpt: "Shuffle options",
      fullMock: "Full Mock Test", quickTest: "Quick Test", miniTest: "Mini Test", custom: "Custom",
      minutes: "min", proceed: "Proceed to Instructions",
      instructions: "General Instructions", instrAgree: "I have read and understood the instructions. I agree to start the test.",
      iStart: "I am ready to begin",
      instr1: "The clock will be set at the server. The countdown timer at the top right corner of the screen will display the remaining time. When the timer reaches zero, the examination will end by itself.",
      instr2: "The Question Palette displayed on the right side of the screen will show the status of each question using one of the following symbols:",
      instr3: "Click on a question number in the palette to go to that question directly.",
      instr4: "Click “Save & Next” to save your answer and move to the next question.",
      instr5: "Click “Mark for Review & Next” to mark the question for review. A question that is answered and marked for review will be considered for evaluation.",
      instr6: "To change your answer, simply click another option. To deselect, click “Clear Response”.",
      instr7: "You can change the question language anytime using the language selector at the top.",
      instr8: "Each question carries 1 mark. There is NO negative marking.",
      notVisited: "Not Visited", notAnswered: "Not Answered", answered: "Answered", marked: "Marked for Review", ansMarked: "Answered & Marked for Review",
      saveNext: "Save & Next", markNext: "Mark for Review & Next", clearResp: "Clear Response", submit: "Submit Test",
      timeLeft: "Time Left", viewIn: "View in", candidate: "Candidate", rollNo: "Roll No.", palette: "Question Palette",
      confirmSubmit: "Submit the test?", confirmSubmitDesc: "You will not be able to change answers after submitting.",
      cancel: "Cancel", yesSubmit: "Yes, Submit", timeUp: "Time is up! Your test has been submitted automatically.",
      resultTitle: "Test Result", pass: "PASS", fail: "FAIL", percentage: "Percentage", unattempted: "Unattempted", timeTaken: "Time Taken",
      moduleWise: "Module-wise Analysis", reviewAns: "Review Answers", all: "All", yourAns: "Your answer", notAnsweredShort: "Not answered",
      retake: "Retake Test", newTest: "New Test", passMark: "Pass mark: 40%",
      historyTitle: "My Results", historyDesc: "All your test attempts are saved on this device.", noResults: "No tests attempted yet. Take your first mock test now!",
      date: "Date", test: "Test", marks: "Marks", status: "Status", view: "View", clearHistory: "Clear History",
      best: "Best Score", total: "Total",
      name: "Full Name", roll: "Roll Number", batch: "Batch / Year", saveProfile: "Save & Continue",
      loginNote: "Login with your Roll No. and 4-digit PIN. Results are saved on this computer.", loggedIn: "Logged in successfully!",
      resumeTitle: "Resume unfinished test?", resumeDesc: "You have an unfinished test. Do you want to continue it?", resume: "Resume", discard: "Discard",
      studyTitle: "Study Material", studyDesc: "All questions with correct answers – module-wise. Read, revise and print.",
      aboutTitle: "About COPA MCQ", contactTitle: "Contact Us",
      noMatch: "No matching questions found.", showing: "Showing", backToModules: "All Modules",
      practiceDone: "Practice completed!", restart: "Restart", questionsShort: "Qs", minutesShort: "min",
      selectOneModule: "Please select at least one module.", progress: "Progress", accuracy: "Accuracy"
    },
    hi: {
      readNotes: "पूरे नोट्स पढ़ें", printNotes: "प्रिंट", netErr: "सर्वर से कनेक्ट नहीं हो सका। इंटरनेट जाँचें और फिर कोशिश करें।", gOr: "या", gStep: "बस एक कदम और", gStepNote: "एक बार अपना रोल नंबर लिखें। अगली बार सिर्फ “Google से साइन इन” दबाएँ।", gLinkPin: "इस रोल नंबर का खाता पहले से है। Google जोड़ने के लिए एक बार उसका 4 अंकों का PIN डालें।", gContinue: "आगे बढ़ें", sessionEnded: "कृपया फिर से लॉगिन करें।",
      noPrograms: "अभी कोई प्रैक्टिकल सेव नहीं। लाइव प्रैक्टिस खोलें, प्रोग्राम लिखें और Save दबाएँ।", 
      livePractice: "लाइव प्रैक्टिस", reviews: "समीक्षाएँ", myPracticals: "मेरे लाइव प्रैक्टिकल", 
      whyAns: "व्याख्या", 
      pin: "4 अंकों का PIN", pinNote: "अपना रोल नं. और PIN याद रखें – इस कंप्यूटर पर दोबारा लॉगिन के लिए इनकी ज़रूरत होगी।", createAcc: "अकाउंट बनाएँ", pinErr: "PIN ठीक 4 अंकों का होना चाहिए।", rollExists: "यह रोल नं. पहले से रजिस्टर है। कृपया लॉगिन करें।", noAccount: "इस रोल नं. का कोई अकाउंट नहीं मिला। पहले रजिस्टर करें।", wrongPin: "गलत PIN। फिर से कोशिश करें।",
      myDash: "मेरा डैशबोर्ड", hello: "नमस्ते", memberSince: "सदस्य बने", lastLogin: "पिछला लॉगिन", passed: "उत्तीर्ण", failed: "अनुत्तीर्ण", passRate: "उत्तीर्ण दर", lastScore: "पिछला स्कोर",
      scoreTrend: "स्कोर ट्रेंड", scoreTrendDesc: "हर टेस्ट का प्रतिशत (डैश रेखा = 40% उत्तीर्ण अंक)", testHistory: "टेस्ट इतिहास", allTests: "इस छात्र के सभी सेव किए गए टेस्ट",
      loginToSee: "अपना डैशबोर्ड देखने के लिए लॉगिन करें", loginToSeeDesc: "अपने रोल नं. और 4 अंकों के PIN से मुफ्त छात्र अकाउंट बनाएँ। आपके सभी टेस्ट परिणाम डैशबोर्ड में सेव होंगे।",
      loginFirst: "कृपया पहले लॉगिन करें – आपका परिणाम आपके डैशबोर्ड में सेव होगा।", strong: "मज़बूत", weak: "अभ्यास ज़रूरी", modulePerf: "मॉड्यूलवार प्रदर्शन (सभी टेस्ट)", noTestModules: "मॉड्यूलवार प्रदर्शन देखने के लिए टेस्ट दें।",
      questionsAns: "प्रश्न", practiceProgress: "अभ्यास प्रगति",
      home: "होम", dashboard: "डैशबोर्ड", practice: "MCQ अभ्यास", mock: "मॉक टेस्ट", onlineTest: "ऑनलाइन टेस्ट (CBT)",
      subjects: "विषय", study: "अध्ययन सामग्री", results: "परिणाम", about: "परिचय", contact: "संपर्क करें", login: "लॉगिन", logout: "लॉगआउट",
      tagline: "सीखें • अभ्यास करें • उत्कृष्ट बनें", iti: "औद्योगिक प्रशिक्षण संस्थान", maharashtra: "महाराष्ट्र",
      dream: "बड़े सपने देखें… अच्छी तैयारी करें…", getSuccess: "सफलता पाएँ…",
      welcome: "COPA MCQ में आपका स्वागत है", heroLead: "ITI COPA ट्रेड के लिए आपका संपूर्ण ऑनलाइन लर्निंग प्लेटफ़ॉर्म",
      f1: "विषयवार MCQ", f2: "CBT जैसे मॉक टेस्ट", f3: "3 भाषाओं में अध्ययन सामग्री",
      startPracticing: "अभ्यास शुरू करें", beExpert: "COPA एक्सपर्ट बनें!",
      studentLogin: "छात्र लॉगिन", accessDash: "अपना डैशबोर्ड देखें", or: "या", register: "अभी रजिस्टर करें",
      welcomeBack: "पुनः स्वागत है", viewReport: "विस्तृत रिपोर्ट देखें",
      statSubjects: "COPA मॉड्यूल", statQ: "MCQ प्रश्न", statMock: "मॉक टेस्ट सेट", statLang: "भाषाएँ",
      explore: "COPA मॉड्यूल देखें", viewAll: "सभी मॉड्यूल", yourProgress: "आपकी प्रगति",
      testsAttempted: "दिए गए टेस्ट", avgScore: "औसत स्कोर", questionsPracticed: "अभ्यास किए प्रश्न",
      prepare1: "स्मार्ट तैयारी करें…", prepare2: "ज़्यादा अंक पाएँ", prepare3: "परीक्षा में!", takeMock: "मॉक टेस्ट दें",
      quickLinks: "त्वरित लिंक", qPractice: "MCQ अभ्यास", qTest: "टेस्ट दें", qNotes: "अध्ययन नोट्स", qResults: "परिणाम देखें",
      footerRights: "सर्वाधिकार सुरक्षित।", footerTag: "आज सीखें… कल नेतृत्व करें…",
      questions: "प्रश्न", module: "मॉड्यूल", modules: "मॉड्यूल", practiceMode: "अभ्यास", studyMode: "अध्ययन (प्रश्नोत्तर)", testMode: "मॉड्यूल टेस्ट",
      question: "प्रश्न", of: "में से", prev: "पिछला", next: "अगला", correct: "सही", wrong: "गलत", correctAns: "सही उत्तर",
      wellDone: "बहुत बढ़िया! सही उत्तर।", oops: "ओह! गलत उत्तर।", score: "स्कोर", attempted: "हल किए", reset: "रीसेट",
      shuffle: "शफ़ल", search: "प्रश्न खोजें…", allModules: "सभी मॉड्यूल", print: "प्रिंट / PDF", showAns: "उत्तर दिखाएँ",
      startTest: "टेस्ट शुरू करें", startPractice: "अभ्यास शुरू करें", openStudy: "प्रश्नोत्तर पढ़ें",
      allModulesDesc: "15 मॉड्यूल • COPA प्रश्न बैंक के 355 प्रश्न। अभ्यास, अध्ययन या टेस्ट के लिए मॉड्यूल चुनें।",
      setupTitle: "ऑनलाइन CBT मॉक टेस्ट", setupDesc: "बिलकुल ITI CBT जैसा परीक्षा अनुभव। टेस्ट पैटर्न और मॉड्यूल चुनें।",
      pattern: "टेस्ट पैटर्न", selectModules: "मॉड्यूल चुनें", selectAll: "सभी चुनें", clearAll: "हटाएँ",
      numQ: "प्रश्नों की संख्या", duration: "समय (मिनट)", examLang: "प्रश्नों की डिफ़ॉल्ट भाषा", shuffleOpt: "विकल्प शफ़ल करें",
      fullMock: "पूर्ण मॉक टेस्ट", quickTest: "क्विक टेस्ट", miniTest: "मिनी टेस्ट", custom: "कस्टम",
      minutes: "मिनट", proceed: "निर्देशों पर जाएँ",
      instructions: "सामान्य निर्देश", instrAgree: "मैंने निर्देश पढ़ और समझ लिए हैं। मैं टेस्ट शुरू करने के लिए सहमत हूँ।",
      iStart: "मैं शुरू करने के लिए तैयार हूँ",
      instr1: "स्क्रीन के ऊपरी दाएँ कोने पर काउंटडाउन टाइमर शेष समय दिखाएगा। टाइमर शून्य होने पर परीक्षा अपने आप समाप्त हो जाएगी।",
      instr2: "स्क्रीन के दाईं ओर प्रश्न पैलेट निम्नलिखित चिह्नों द्वारा प्रत्येक प्रश्न की स्थिति दिखाएगा:",
      instr3: "सीधे किसी प्रश्न पर जाने के लिए पैलेट में प्रश्न संख्या पर क्लिक करें।",
      instr4: "उत्तर सेव करके अगले प्रश्न पर जाने के लिए “सेव और अगला” पर क्लिक करें।",
      instr5: "प्रश्न को समीक्षा के लिए चिह्नित करने हेतु “रिव्यू के लिए मार्क करें और अगला” पर क्लिक करें। उत्तर दिया गया और चिह्नित प्रश्न मूल्यांकन में गिना जाएगा।",
      instr6: "उत्तर बदलने के लिए दूसरा विकल्प चुनें। उत्तर हटाने के लिए “उत्तर हटाएँ” पर क्लिक करें।",
      instr7: "ऊपर दिए भाषा चयनकर्ता से आप कभी भी प्रश्न की भाषा बदल सकते हैं।",
      instr8: "प्रत्येक प्रश्न 1 अंक का है। कोई नकारात्मक अंकन नहीं है।",
      notVisited: "नहीं देखा", notAnswered: "उत्तर नहीं दिया", answered: "उत्तर दिया", marked: "रिव्यू हेतु चिह्नित", ansMarked: "उत्तर दिया व रिव्यू हेतु चिह्नित",
      saveNext: "सेव और अगला", markNext: "रिव्यू के लिए मार्क करें और अगला", clearResp: "उत्तर हटाएँ", submit: "टेस्ट सबमिट करें",
      timeLeft: "शेष समय", viewIn: "भाषा", candidate: "परीक्षार्थी", rollNo: "रोल नं.", palette: "प्रश्न पैलेट",
      confirmSubmit: "टेस्ट सबमिट करें?", confirmSubmitDesc: "सबमिट करने के बाद आप उत्तर नहीं बदल पाएँगे।",
      cancel: "रद्द करें", yesSubmit: "हाँ, सबमिट करें", timeUp: "समय समाप्त! आपका टेस्ट अपने आप सबमिट हो गया है।",
      resultTitle: "टेस्ट परिणाम", pass: "उत्तीर्ण", fail: "अनुत्तीर्ण", percentage: "प्रतिशत", unattempted: "अनुत्तरित", timeTaken: "लिया गया समय",
      moduleWise: "मॉड्यूलवार विश्लेषण", reviewAns: "उत्तरों की समीक्षा", all: "सभी", yourAns: "आपका उत्तर", notAnsweredShort: "उत्तर नहीं दिया",
      retake: "फिर से टेस्ट दें", newTest: "नया टेस्ट", passMark: "उत्तीर्ण अंक: 40%",
      historyTitle: "मेरे परिणाम", historyDesc: "आपके सभी टेस्ट प्रयास इस डिवाइस पर सेव हैं।", noResults: "अभी तक कोई टेस्ट नहीं दिया। अभी अपना पहला मॉक टेस्ट दें!",
      date: "दिनांक", test: "टेस्ट", marks: "अंक", status: "स्थिति", view: "देखें", clearHistory: "इतिहास हटाएँ",
      best: "सर्वश्रेष्ठ स्कोर", total: "कुल",
      name: "पूरा नाम", roll: "रोल नंबर", batch: "बैच / वर्ष", saveProfile: "सेव करें और आगे बढ़ें",
      loginNote: "अपने रोल नं. और 4 अंकों के PIN से लॉगिन करें। परिणाम इसी कंप्यूटर पर सेव होते हैं।", loggedIn: "सफलतापूर्वक लॉगिन हुआ!",
      resumeTitle: "अधूरा टेस्ट जारी रखें?", resumeDesc: "आपका एक टेस्ट अधूरा है। क्या आप उसे जारी रखना चाहते हैं?", resume: "जारी रखें", discard: "हटाएँ",
      studyTitle: "अध्ययन सामग्री", studyDesc: "सभी प्रश्न सही उत्तरों के साथ – मॉड्यूलवार। पढ़ें, दोहराएँ और प्रिंट करें।",
      aboutTitle: "COPA MCQ के बारे में", contactTitle: "संपर्क करें",
      noMatch: "कोई मेल खाता प्रश्न नहीं मिला।", showing: "दिखाए जा रहे", backToModules: "सभी मॉड्यूल",
      practiceDone: "अभ्यास पूरा हुआ!", restart: "फिर से शुरू करें", questionsShort: "प्रश्न", minutesShort: "मिनट",
      selectOneModule: "कृपया कम से कम एक मॉड्यूल चुनें।", progress: "प्रगति", accuracy: "सटीकता"
    },
    mr: {
      readNotes: "संपूर्ण नोट्स वाचा", printNotes: "प्रिंट", netErr: "सर्व्हरशी कनेक्ट होऊ शकले नाही. इंटरनेट तपासा आणि पुन्हा प्रयत्न करा.", gOr: "किंवा", gStep: "फक्त एक पायरी", gStepNote: "एकदा तुमचा रोल नंबर लिहा. पुढच्या वेळी फक्त “Google ने साइन इन” दाबा.", gLinkPin: "या रोल नंबरचे खाते आधीच आहे. Google जोडण्यासाठी एकदा त्याचा 4 अंकी PIN टाका.", gContinue: "पुढे जा", sessionEnded: "कृपया पुन्हा लॉगिन करा.",
      noPrograms: "अजून एकही प्रॅक्टिकल सेव्ह नाही. लाइव्ह प्रॅक्टिस उघडा, प्रोग्राम लिहा आणि Save दाबा.", 
      livePractice: "लाइव्ह प्रॅक्टिस", reviews: "रिव्ह्यू", myPracticals: "माझे लाइव्ह प्रॅक्टिकल", 
      whyAns: "स्पष्टीकरण", 
      pin: "4 अंकी PIN", pinNote: "तुमचा रोल नं. आणि PIN लक्षात ठेवा – या संगणकावर पुन्हा लॉगिनसाठी ते लागतील.", createAcc: "अकाउंट तयार करा", pinErr: "PIN बरोबर 4 अंकांचा असावा.", rollExists: "हा रोल नं. आधीच नोंदणीकृत आहे. कृपया लॉगिन करा.", noAccount: "या रोल नं. चे अकाउंट सापडले नाही. आधी नोंदणी करा.", wrongPin: "चुकीचा PIN. पुन्हा प्रयत्न करा.",
      myDash: "माझा डॅशबोर्ड", hello: "नमस्कार", memberSince: "सदस्य झाल्यापासून", lastLogin: "शेवटचे लॉगिन", passed: "उत्तीर्ण", failed: "अनुत्तीर्ण", passRate: "उत्तीर्ण दर", lastScore: "शेवटचे गुण",
      scoreTrend: "गुणांचा ट्रेंड", scoreTrendDesc: "प्रत्येक टेस्टची टक्केवारी (डॅश रेषा = 40% उत्तीर्ण गुण)", testHistory: "टेस्ट इतिहास", allTests: "या विद्यार्थ्याच्या सर्व सेव्ह केलेल्या टेस्ट",
      loginToSee: "तुमचा डॅशबोर्ड पाहण्यासाठी लॉगिन करा", loginToSeeDesc: "तुमचा रोल नं. आणि 4 अंकी PIN वापरून मोफत विद्यार्थी अकाउंट तयार करा. तुमचे सर्व टेस्ट निकाल डॅशबोर्डमध्ये सेव्ह होतील.",
      loginFirst: "कृपया आधी लॉगिन करा – तुमचा निकाल तुमच्या डॅशबोर्डमध्ये सेव्ह होईल.", strong: "मजबूत", weak: "सराव आवश्यक", modulePerf: "मॉड्युलनिहाय कामगिरी (सर्व टेस्ट)", noTestModules: "मॉड्युलनिहाय कामगिरी पाहण्यासाठी टेस्ट द्या.",
      questionsAns: "प्रश्न", practiceProgress: "सराव प्रगती",
      home: "होम", dashboard: "डॅशबोर्ड", practice: "MCQ सराव", mock: "मॉक टेस्ट", onlineTest: "ऑनलाइन टेस्ट (CBT)",
      subjects: "विषय", study: "अभ्यास साहित्य", results: "निकाल", about: "माहिती", contact: "संपर्क", login: "लॉगिन", logout: "लॉगआउट",
      tagline: "शिका • सराव करा • यशस्वी व्हा", iti: "औद्योगिक प्रशिक्षण संस्था", maharashtra: "महाराष्ट्र",
      dream: "मोठी स्वप्ने पहा… चांगली तयारी करा…", getSuccess: "यश मिळवा…",
      welcome: "COPA MCQ मध्ये आपले स्वागत", heroLead: "ITI COPA ट्रेडसाठी तुमचे संपूर्ण ऑनलाइन लर्निंग प्लॅटफॉर्म",
      f1: "विषयनिहाय MCQ", f2: "CBT सारख्या मॉक टेस्ट", f3: "3 भाषांमध्ये अभ्यास साहित्य",
      startPracticing: "सराव सुरू करा", beExpert: "COPA एक्सपर्ट व्हा!",
      studentLogin: "विद्यार्थी लॉगिन", accessDash: "तुमचा डॅशबोर्ड पहा", or: "किंवा", register: "आता नोंदणी करा",
      welcomeBack: "पुन्हा स्वागत", viewReport: "सविस्तर अहवाल पहा",
      statSubjects: "COPA मॉड्युल्स", statQ: "MCQ प्रश्न", statMock: "मॉक टेस्ट संच", statLang: "भाषा",
      explore: "COPA मॉड्युल्स पहा", viewAll: "सर्व मॉड्युल्स", yourProgress: "तुमची प्रगती",
      testsAttempted: "दिलेल्या टेस्ट", avgScore: "सरासरी गुण", questionsPracticed: "सराव केलेले प्रश्न",
      prepare1: "स्मार्ट तयारी करा…", prepare2: "जास्त गुण मिळवा", prepare3: "परीक्षेत!", takeMock: "मॉक टेस्ट द्या",
      quickLinks: "झटपट लिंक्स", qPractice: "MCQ सराव", qTest: "टेस्ट द्या", qNotes: "अभ्यास नोट्स", qResults: "निकाल पहा",
      footerRights: "सर्व हक्क राखीव.", footerTag: "आज शिका… उद्या नेतृत्व करा…",
      questions: "प्रश्न", module: "मॉड्युल", modules: "मॉड्युल्स", practiceMode: "सराव", studyMode: "अभ्यास (प्रश्नोत्तरे)", testMode: "मॉड्युल टेस्ट",
      question: "प्रश्न", of: "पैकी", prev: "मागील", next: "पुढील", correct: "बरोबर", wrong: "चूक", correctAns: "बरोबर उत्तर",
      wellDone: "छान! बरोबर उत्तर.", oops: "अरेरे! चुकीचे उत्तर.", score: "गुण", attempted: "सोडवलेले", reset: "रीसेट",
      shuffle: "शफल", search: "प्रश्न शोधा…", allModules: "सर्व मॉड्युल्स", print: "प्रिंट / PDF", showAns: "उत्तरे दाखवा",
      startTest: "टेस्ट सुरू करा", startPractice: "सराव सुरू करा", openStudy: "प्रश्नोत्तरे वाचा",
      allModulesDesc: "15 मॉड्युल्स • COPA प्रश्नपेढीतील 355 प्रश्न. सराव, अभ्यास किंवा टेस्टसाठी मॉड्युल निवडा.",
      setupTitle: "ऑनलाइन CBT मॉक टेस्ट", setupDesc: "अगदी ITI CBT सारखा परीक्षेचा अनुभव. टेस्ट पॅटर्न आणि मॉड्युल्स निवडा.",
      pattern: "टेस्ट पॅटर्न", selectModules: "मॉड्युल्स निवडा", selectAll: "सर्व निवडा", clearAll: "काढा",
      numQ: "प्रश्नांची संख्या", duration: "वेळ (मिनिटे)", examLang: "प्रश्नांची डीफॉल्ट भाषा", shuffleOpt: "पर्याय शफल करा",
      fullMock: "पूर्ण मॉक टेस्ट", quickTest: "क्विक टेस्ट", miniTest: "मिनी टेस्ट", custom: "कस्टम",
      minutes: "मिनिटे", proceed: "सूचनांकडे जा",
      instructions: "सर्वसाधारण सूचना", instrAgree: "मी सूचना वाचल्या आणि समजून घेतल्या आहेत. मी टेस्ट सुरू करण्यास सहमत आहे.",
      iStart: "मी सुरू करण्यास तयार आहे",
      instr1: "स्क्रीनच्या वरच्या उजव्या कोपऱ्यातील काउंटडाउन टायमर उरलेला वेळ दाखवेल. टायमर शून्यावर आल्यावर परीक्षा आपोआप संपेल.",
      instr2: "स्क्रीनच्या उजव्या बाजूचे प्रश्न पॅलेट खालील चिन्हांद्वारे प्रत्येक प्रश्नाची स्थिती दाखवेल:",
      instr3: "थेट एखाद्या प्रश्नावर जाण्यासाठी पॅलेटमधील प्रश्न क्रमांकावर क्लिक करा.",
      instr4: "उत्तर सेव्ह करून पुढील प्रश्नावर जाण्यासाठी “सेव्ह आणि पुढील” वर क्लिक करा.",
      instr5: "प्रश्न पुनरावलोकनासाठी चिन्हांकित करण्यासाठी “रिव्ह्यूसाठी मार्क करा आणि पुढील” वर क्लिक करा. उत्तर दिलेला व चिन्हांकित प्रश्न मूल्यांकनात धरला जाईल.",
      instr6: "उत्तर बदलण्यासाठी दुसरा पर्याय निवडा. उत्तर काढण्यासाठी “उत्तर काढा” वर क्लिक करा.",
      instr7: "वरच्या भाषा निवडीतून तुम्ही कधीही प्रश्नाची भाषा बदलू शकता.",
      instr8: "प्रत्येक प्रश्नाला 1 गुण आहे. निगेटिव्ह मार्किंग नाही.",
      notVisited: "न पाहिलेले", notAnswered: "उत्तर न दिलेले", answered: "उत्तर दिलेले", marked: "रिव्ह्यूसाठी चिन्हांकित", ansMarked: "उत्तर दिलेले व रिव्ह्यूसाठी चिन्हांकित",
      saveNext: "सेव्ह आणि पुढील", markNext: "रिव्ह्यूसाठी मार्क करा आणि पुढील", clearResp: "उत्तर काढा", submit: "टेस्ट सबमिट करा",
      timeLeft: "उरलेला वेळ", viewIn: "भाषा", candidate: "परीक्षार्थी", rollNo: "रोल नं.", palette: "प्रश्न पॅलेट",
      confirmSubmit: "टेस्ट सबमिट करायची?", confirmSubmitDesc: "सबमिट केल्यानंतर तुम्हाला उत्तरे बदलता येणार नाहीत.",
      cancel: "रद्द करा", yesSubmit: "होय, सबमिट करा", timeUp: "वेळ संपली! तुमची टेस्ट आपोआप सबमिट झाली आहे.",
      resultTitle: "टेस्ट निकाल", pass: "उत्तीर्ण", fail: "अनुत्तीर्ण", percentage: "टक्केवारी", unattempted: "न सोडवलेले", timeTaken: "लागलेला वेळ",
      moduleWise: "मॉड्युलनिहाय विश्लेषण", reviewAns: "उत्तरांचे पुनरावलोकन", all: "सर्व", yourAns: "तुमचे उत्तर", notAnsweredShort: "उत्तर दिले नाही",
      retake: "पुन्हा टेस्ट द्या", newTest: "नवीन टेस्ट", passMark: "उत्तीर्ण गुण: 40%",
      historyTitle: "माझे निकाल", historyDesc: "तुमचे सर्व टेस्ट प्रयत्न या डिव्हाइसवर सेव्ह आहेत.", noResults: "अजून एकही टेस्ट दिली नाही. आताच तुमची पहिली मॉक टेस्ट द्या!",
      date: "तारीख", test: "टेस्ट", marks: "गुण", status: "स्थिती", view: "पहा", clearHistory: "इतिहास काढा",
      best: "सर्वोत्तम गुण", total: "एकूण",
      name: "पूर्ण नाव", roll: "रोल नंबर", batch: "बॅच / वर्ष", saveProfile: "सेव्ह करा आणि पुढे जा",
      loginNote: "तुमचा रोल नं. आणि 4 अंकी PIN वापरून लॉगिन करा. निकाल याच संगणकावर सेव्ह होतात.", loggedIn: "यशस्वीरीत्या लॉगिन झाले!",
      resumeTitle: "अपूर्ण टेस्ट सुरू ठेवायची?", resumeDesc: "तुमची एक टेस्ट अपूर्ण आहे. ती सुरू ठेवायची का?", resume: "सुरू ठेवा", discard: "काढून टाका",
      studyTitle: "अभ्यास साहित्य", studyDesc: "सर्व प्रश्न बरोबर उत्तरांसह – मॉड्युलनिहाय. वाचा, उजळणी करा आणि प्रिंट करा.",
      aboutTitle: "COPA MCQ विषयी", contactTitle: "संपर्क",
      noMatch: "जुळणारे प्रश्न सापडले नाहीत.", showing: "दाखवत आहे", backToModules: "सर्व मॉड्युल्स",
      practiceDone: "सराव पूर्ण झाला!", restart: "पुन्हा सुरू करा", questionsShort: "प्रश्न", minutesShort: "मिनिटे",
      selectOneModule: "कृपया किमान एक मॉड्युल निवडा.", progress: "प्रगती", accuracy: "अचूकता"
    }
  };

  /* ---------- Storage helpers ----------
     results / practice / exam data are stored per logged-in student,
     so several students can share one lab computer. */
  const PASS = 40;
  const SCOPED = { results: 1, practice: 1, exam_session: 1, exam_pending: 1 };
  const raw = {
    get(k, def) { try { const v = localStorage.getItem("copa_" + k); return v == null ? def : JSON.parse(v); } catch (e) { return def; } },
    set(k, v) { try { localStorage.setItem("copa_" + k, JSON.stringify(v)); } catch (e) { /* ignore */ } },
    del(k) { try { localStorage.removeItem("copa_" + k); } catch (e) { /* ignore */ } }
  };
  const scopedKey = (k) => { if (!SCOPED[k]) return k; const u = raw.get("current", null); return u ? k + "__" + u : k; };
  const store = {
    get: (k, def) => raw.get(scopedKey(k), def),
    set: (k, v) => raw.set(scopedKey(k), v),
    del: (k) => raw.del(scopedKey(k))
  };

  let lang = raw.get("lang", "en");
  if (!I18N[lang]) lang = "en";
  const t = (k) => (I18N[lang] && I18N[lang][k]) || I18N.en[k] || k;
  const modName = (m, l) => { const mod = typeof m === "number" ? MODULES[m - 1] : m; return mod ? mod[l || lang] : ""; };
  const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const pad = (n) => String(n).padStart(2, "0");
  const fmtTime = (s) => { s = Math.max(0, Math.round(s)); const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60; return (h ? pad(h) + ":" : "") + pad(m) + ":" + pad(x); };
  const shuffle = (a) => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  /* built-in questions + questions added/edited/hidden by the admin (cached by db.js) */
  const readCache = (k, d) => { try { const v = localStorage.getItem("copa_db_" + k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } };
  function normQ(c) {
    const en = c.en || { q: "", o: ["", "", "", ""] };
    const lang = (L) => ({ q: (L && L.q) || en.q, o: [0, 1, 2, 3].map((i) => (L && L.o && L.o[i]) || en.o[i] || "") });
    const q = { id: String(c.id), m: +c.m || 1, a: +c.a || 0, en: lang(en), hi: lang(c.hi), mr: lang(c.mr), custom: true };
    if (c.code) q.code = c.code;
    if (c.img) q.img = c.img;
    const xe = c.x && c.x.en;
    if (xe || (c.x && (c.x.hi || c.x.mr))) q.x = { en: xe || "", hi: (c.x.hi || xe || ""), mr: (c.x.mr || xe || "") };
    return q;
  }
  const QUESTIONS = (function () {
    const base = (window.COPA_QUESTIONS || []).slice();
    const idx = {}; base.forEach((q, i) => (idx[q.id] = i));
    readCache("questions", []).forEach((c) => {
      if (!c || !c.id) return;
      if (c.hidden) { if (idx[c.id] != null) base[idx[c.id]] = null; return; }
      const q = normQ(c);
      if (idx[c.id] != null) base[idx[c.id]] = q; else base.push(q);
    });
    return base.filter(Boolean);
  })();
  const NOTES = readCache("notes", []).filter((n) => n && !n.hidden).sort((a, b) => (a.m - b.m) || ((a.order || 0) - (b.order || 0)) || ((a.created || 0) - (b.created || 0)));
  const byModule = (m) => QUESTIONS.filter((q) => q.m === m);
  const qById = {}; QUESTIONS.forEach((q) => (qById[q.id] = q));
  const modulePage = (id) => ROOT + "modules/module-" + pad(id) + ".html";
  const iconCls = (m) => (m.icon.indexOf("fa-brands") === 0 ? m.icon : "fa-solid " + m.icon);

  /* question rendering helpers (shared) */
  function qBlock(q, l) {
    const L = q[l] || q.en;
    let h = '<p class="q-text">' + esc(L.q) + "</p>";
    if (l !== "en") h += '<p class="q-alt">' + esc(q.en.q) + "</p>";
    if (q.code) h += '<pre class="code">' + esc(q.code) + "</pre>";
    if (q.img) h += '<img class="q-img" src="' + ROOT + q.img + '" alt="Figure for question ' + esc(q.id) + '" loading="lazy">';
    return h;
  }
  const optText = (q, l, i) => ((q[l] || q.en).o[i]);
  /* admin study notes → HTML cards (plain text, **bold**, links, line breaks) */
  /* ---------- safe HTML for formatted study material (only allowed tags & styles) ---------- */
  const SAFE_TAGS = { P: 1, BR: 1, B: 1, STRONG: 1, I: 1, EM: 1, U: 1, S: 1, STRIKE: 1, DEL: 1, SUB: 1, SUP: 1, SPAN: 1, MARK: 1, SMALL: 1,
    H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1, BLOCKQUOTE: 1, PRE: 1, CODE: 1, UL: 1, OL: 1, LI: 1, A: 1, IMG: 1, HR: 1, DIV: 1,
    TABLE: 1, THEAD: 1, TBODY: 1, TFOOT: 1, TR: 1, TH: 1, TD: 1, CAPTION: 1, COLGROUP: 1, COL: 1 };
  const DROP_TAGS = /^(SCRIPT|STYLE|IFRAME|OBJECT|EMBED|LINK|META|TITLE|HEAD|NOSCRIPT|TEMPLATE|SVG|MATH|FORM|INPUT|BUTTON|SELECT|TEXTAREA|XML|O:P)$/;
  const SAFE_CSS = ["color", "background-color", "font-family", "font-size", "font-weight", "font-style", "text-decoration", "text-decoration-line", "text-align",
    "line-height", "margin-left", "margin-right", "padding-left", "text-indent", "vertical-align", "width", "height", "border", "border-collapse", "display", "float", "list-style-type"];
  const FONT_SIZES = ["", "10px", "13px", "16px", "18px", "24px", "32px", "48px"];
  function sanitizeHTML(html) {
    const tpl = document.createElement("template");
    tpl.innerHTML = String(html || "").replace(/<!--[\s\S]*?-->/g, "").replace(/<\/?o:p>/gi, "");
    const safeUrl = (u, img) => { u = String(u || "").trim(); return /^(https?:|mailto:)/i.test(u) || (img && /^data:image\/(png|jpe?g|gif|webp);base64,/i.test(u)) ? u : ""; };
    const walk = (parent) => {
      Array.from(parent.childNodes).forEach((n) => {
        if (n.nodeType === 3) return;
        if (n.nodeType !== 1) { n.remove(); return; }
        let tag = n.tagName.toUpperCase();
        if (DROP_TAGS.test(tag)) { n.remove(); return; }
        if (tag === "FONT") { // old <font> → <span style>
          const sp = document.createElement("span");
          if (n.getAttribute("color")) sp.style.color = n.getAttribute("color");
          if (n.getAttribute("face")) sp.style.fontFamily = n.getAttribute("face");
          if (n.getAttribute("size")) sp.style.fontSize = FONT_SIZES[+n.getAttribute("size")] || "";
          while (n.firstChild) sp.appendChild(n.firstChild);
          n.replaceWith(sp); n = sp; tag = "SPAN";
        }
        if (!SAFE_TAGS[tag]) { walk(n); while (n.firstChild) n.parentNode.insertBefore(n.firstChild, n); n.remove(); return; }
        const keep = {};
        if (tag === "A") { const h = safeUrl(n.getAttribute("href")); if (h) { keep.href = h; keep.target = "_blank"; keep.rel = "noopener"; } }
        if (tag === "IMG") { const src = safeUrl(n.getAttribute("src"), true); if (!src) { n.remove(); return; } keep.src = src; keep.alt = n.getAttribute("alt") || ""; keep.loading = "lazy"; }
        if (tag === "TD" || tag === "TH") ["colspan", "rowspan"].forEach((a) => { const v = parseInt(n.getAttribute(a), 10); if (v > 1 && v < 50) keep[a] = String(v); });
        if (tag === "OL") { const st = parseInt(n.getAttribute("start"), 10); if (st > 1) keep.start = String(st); }
        if (tag === "TABLE") keep.class = "rte-table";
        const css = [];
        const st = n.style;
        SAFE_CSS.forEach((p) => {
          const v = st.getPropertyValue(p);
          if (v && !/url\(|expression|javascript:|behavior/i.test(v)) css.push(p + ":" + v);
        });
        const al = n.getAttribute("align"); if (al && /^(left|right|center|justify)$/i.test(al) && !st.textAlign) css.push("text-align:" + al.toLowerCase());
        Array.from(n.attributes).forEach((a) => n.removeAttribute(a.name));
        Object.keys(keep).forEach((k) => n.setAttribute(k, keep[k]));
        if (css.length) n.setAttribute("style", css.join(";"));
        walk(n);
      });
    };
    walk(tpl.content);
    return tpl.innerHTML.trim();
  }
  function noteBody(txt) {
    let h = esc(txt || "");
    h = h.replace(/\*\*(.+?)\*\*/g, "<b>$1</b>");
    h = h.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    return h.replace(/\n/g, "<br>");
  }
  const noteHTML = (n) => (n.fmt === "html" ? sanitizeHTML(n.body) : noteBody(n.body));
  const noteLink = (n) => (n.link ? '<a class="btn btn-ghost btn-sm" href="' + esc(n.link) + '" target="_blank" rel="noopener"><i class="fa-solid fa-' + (/\.pdf($|\?)/i.test(n.link) ? "file-pdf" : "link") + '"></i> ' + esc(n.linkLabel || "Open") + "</a>" : "");
  function renderNotes(list) {
    if (!list.length) return "";
    return '<div class="notes-grid">' + list.map((n) => '<article class="card note-card"><div class="nc-head"><span class="chip ' + (n.m ? "" : "purple") + '">' + (n.m ? "M" + n.m : "COPA") + "</span><h3>" + esc(n.title || "") + "</h3></div>" +
      (n.fmt === "html"
        ? '<div class="nc-body rich rte-content">' + noteHTML(n) + '</div><button type="button" class="btn btn-primary btn-sm nc-read" data-note-open="' + esc(n.id) + '"><i class="fa-solid fa-book-open-reader"></i> ' + t("readNotes") + "</button>"
        : '<div class="nc-body">' + noteHTML(n) + "</div>") +
      noteLink(n) + "</article>").join("") + "</div>";
  }
  function openNote(n) {
    const m = modal('<button class="close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
      '<div class="note-view"><div class="nv-head"><span class="chip ' + (n.m ? "" : "purple") + '">' + (n.m ? "M" + n.m : "COPA") + "</span><h2>" + esc(n.title || "") + "</h2>" +
      '<button type="button" class="btn btn-ghost btn-sm" data-nprint><i class="fa-solid fa-print"></i> ' + t("printNotes") + "</button></div>" +
      '<div class="rte-page rte-content" data-margin="' + esc(n.margin || "normal") + '">' + noteHTML(n) + "</div>" + (n.link ? '<div style="margin-top:14px">' + noteLink(n) + "</div>" : "") + "</div>", { wide: true });
    m.el.querySelector(".modal").classList.add("note-modal");
    m.el.querySelector("[data-nprint]").addEventListener("click", () => {
      const w = window.open("", "_blank"); if (!w) return;
      const css = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((l) => '<link rel="stylesheet" href="' + l.href + '">').join("");
      w.document.write("<!doctype html><html><head><meta charset='utf-8'><title>" + esc(n.title || "Notes") + "</title>" + css + "<style>body{background:#fff;padding:0;margin:0}.rte-page{box-shadow:none;border:0}</style></head><body><h1 style='font-family:Poppins,sans-serif;margin:20px 28px 0'>" + esc(n.title || "") +
        "</h1><div class='rte-page rte-content' data-margin='" + esc(n.margin || "normal") + "'>" + noteHTML(n) + "</div><script>setTimeout(function(){print()},400)<\/script></body></html>");
      w.document.close();
    });
  }
  document.addEventListener("click", (e) => {
    const b = e.target.closest && e.target.closest("[data-note-open]"); if (!b) return;
    const n = NOTES.find((x) => x.id === b.getAttribute("data-note-open")); if (n) openNote(n);
  });
  const notesFor = (m) => NOTES.filter((n) => !m || +n.m === +m || !+n.m);

  /* one-line explanation of the correct answer */
  const explain = (q, l) => (q.x ? '<div class="explain"><i class="fa-solid fa-lightbulb"></i><div><b>' + t("whyAns") + ":</b> " + esc(q.x[l] || q.x.en) + "</div></div>" : "");

  /* ---------- Toast & modal ---------- */
  function toast(msg) {
    let el = document.getElementById("toast");
    if (!el) { el = document.createElement("div"); el.id = "toast"; el.className = "toast"; document.body.appendChild(el); }
    el.textContent = msg; el.classList.add("show");
    clearTimeout(el._t); el._t = setTimeout(() => el.classList.remove("show"), 2600);
  }
  function modal(html, opts) {
    opts = opts || {};
    const back = document.createElement("div");
    back.className = "modal-back show";
    back.innerHTML = '<div class="modal ' + (opts.wide ? "wide" : "") + '" role="dialog" aria-modal="true">' + html + "</div>";
    document.body.appendChild(back);
    const close = () => back.remove();
    back.addEventListener("click", (e) => { if (e.target === back && !opts.sticky) close(); });
    back.querySelectorAll("[data-close]").forEach((b) => b.addEventListener("click", close));
    return { el: back, close };
  }

  /* ---------- Student accounts ---------- */
  const hashPin = (pin) => { let h = 5381; const x = "copa|" + pin; for (let i = 0; i < x.length; i++) h = ((h << 5) + h + x.charCodeAt(i)) >>> 0; return h.toString(36); };
  const uidOf = (roll) => String(roll || "").trim().toLowerCase().replace(/\s+/g, "");
  const getUsers = () => raw.get("users", {});
  const getProfile = () => { const u = raw.get("current", null); const users = getUsers(); return u && users[u] ? Object.assign({ uid: u }, users[u]) : null; };
  /* MySQL mode: accounts and result history live on the server, so a student can log in on any device */
  const SRV = window.COPA_DB && window.COPA_DB.mode === "mysql" ? window.COPA_DB.driver : null;
  const studentToken = () => { const p = getProfile(); return (p && p.token) || ""; };
  /* "Sign in with Google" (needs the MySQL backend, a Client ID and https) */
  const GCID = (window.COPA_CONFIG && window.COPA_CONFIG.googleClientId) || "";
  const googleOn = () => !!(SRV && GCID && (location.protocol === "https:" || /^(localhost|127\.0\.0\.1)$/.test(location.hostname)));
  let gsiP = null;
  const loadGsi = () => gsiP || (gsiP = new Promise((res, rej) => {
    if (window.google && window.google.accounts && window.google.accounts.id) return res(window.google.accounts.id);
    const sc = document.createElement("script"); sc.src = "https://accounts.google.com/gsi/client"; sc.async = true;
    sc.onload = () => res(window.google.accounts.id); sc.onerror = () => { gsiP = null; rej(new Error("gsi")); };
    document.head.appendChild(sc);
  }));
  function mergeResults(uid, list) {   // server history → this device (returns how many were new)
    const key = "results__" + uid, have = raw.get(key, []), ids = {};
    have.forEach((r) => (ids[r.id] = 1));
    let added = 0;
    (list || []).forEach((r) => { if (r && r.id && !ids[r.id]) { const c = Object.assign({}, r); delete c.token; have.push(c); ids[r.id] = 1; added++; } });
    if (added) { have.sort((a, b) => (b.date || 0) - (a.date || 0)); raw.set(key, have.slice(0, 500)); }
    return added;
  }
  function uploadResults(uid, token, list, serverIds) {   // results only on this device → server
    const known = {}; (serverIds || []).forEach((id) => (known[id] = 1));
    (list || []).filter((r) => r && r.id && !known[r.id]).slice(0, 200).forEach((r) => {
      const d = Object.assign({}, r, { uid, token, pass: r.pct >= PASS, preset: r.cfg && r.cfg.preset, mods: r.cfg && r.cfg.mods });
      window.COPA_DB.saveResult(d);
    });
  }
  function signIn(uid) {
    const users = getUsers();
    users[uid].lastLogin = Date.now();
    users[uid].logins = (users[uid].logins || 0) + 1;
    raw.set("users", users); raw.set("current", uid);
    const u = users[uid];
    if (window.COPA_DB && !SRV) window.COPA_DB.upsertStudent({ uid, name: u.name, roll: u.roll, batch: u.batch || "", created: u.created, lastLogin: u.lastLogin, logins: u.logins });
  }
  function logout() {
    const p = getProfile();
    if (SRV && p && p.token) { SRV.studentLogout(p.token).catch(() => {}); const users = getUsers(); if (users[p.uid]) { delete users[p.uid].token; raw.set("users", users); } }
    raw.del("current"); location.href = ROOT + "index.html";
  }

  function openLogin(onDone, startTab) {
    // with the MySQL login an account made on another phone works here too → start on "Login"
    let tab = startTab || (SRV || Object.keys(getUsers()).length ? "login" : "register");
    const m = modal('<div id="authBox"></div>');
    const box = m.el.querySelector("#authBox");
    const done = () => { m.close(); toast(t("loggedIn")); if (onDone) onDone(); else location.href = ROOT + "dashboard.html"; };
    function draw(err) {
      const reg = tab === "register";
      box.innerHTML =
        '<button class="close" data-x aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
        '<h3><i class="fa-solid fa-user-graduate" style="color:var(--blue-600)"></i> ' + t("studentLogin") + "</h3>" +
        '<div class="auth-tabs"><button type="button" data-t="login" class="' + (!reg ? "on" : "") + '">' + t("login") + '</button><button type="button" data-t="register" class="' + (reg ? "on" : "") + '">' + t("register") + "</button></div>" +
        (err ? '<div class="auth-err"><i class="fa-solid fa-circle-exclamation"></i> ' + err + "</div>" : "") +
        (googleOn() ? '<div class="g-wrap"><div id="gBtn" class="g-btn"></div></div><div class="auth-or"><span>' + t("gOr") + "</span></div>" : "") +
        '<form id="authForm" autocomplete="off">' +
        (reg ? '<div class="field"><label for="af-name">' + t("name") + ' *</label><input class="input" id="af-name" required maxlength="60"></div>' : "") +
        '<div class="field"><label for="af-roll">' + t("roll") + ' *</label><input class="input" id="af-roll" required maxlength="30"></div>' +
        (reg ? '<div class="field"><label for="af-batch">' + t("batch") + '</label><input class="input" id="af-batch" maxlength="30" placeholder="2025-26"></div>' : "") +
        '<div class="field"><label for="af-pin">' + t("pin") + ' *</label><input class="input" id="af-pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required placeholder="••••"></div>' +
        '<p style="color:var(--ink-3);font-size:12.5px;margin:-4px 0 14px">' + t(reg ? "pinNote" : "loginNote") + "</p>" +
        '<button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-' + (reg ? "user-plus" : "arrow-right-to-bracket") + '"></i> ' + t(reg ? "createAcc" : "login") + "</button></form>";
      box.querySelector("[data-x]").addEventListener("click", m.close);
      box.querySelectorAll("[data-t]").forEach((b) => b.addEventListener("click", () => { tab = b.getAttribute("data-t"); draw(); }));
      if (googleOn()) loadGsi().then((gid) => {
        const el = box.querySelector("#gBtn"); if (!el) return;
        gid.initialize({ client_id: GCID, callback: (r) => googleCred(r.credential), ux_mode: "popup", auto_select: false, cancel_on_tap_outside: true });
        gid.renderButton(el, { type: "standard", theme: "filled_blue", size: "large", shape: "pill", text: tab === "register" ? "signup_with" : "signin_with", logo_alignment: "left", locale: lang, width: Math.min(360, (box.clientWidth || 340) - 8) });
      }).catch(() => { const w = box.querySelector(".g-wrap"); if (w) w.remove(); const o = box.querySelector(".auth-or"); if (o) o.remove(); });
      const first = box.querySelector("input"); if (first) first.focus();
      box.querySelector("#authForm").addEventListener("submit", (e) => {
        e.preventDefault();
        const roll = box.querySelector("#af-roll").value.trim(), uid = uidOf(roll), pin = box.querySelector("#af-pin").value.trim();
        if (!/^\d{4}$/.test(pin)) return draw(t("pinErr"));
        const users = getUsers();
        if (SRV) return serverAuth(reg, uid, roll, pin, users);
        if (reg) {
          if (users[uid]) return draw(t("rollExists"));
          const guestRes = raw.get("results", []), guestPrac = raw.get("practice", {});
          users[uid] = { name: box.querySelector("#af-name").value.trim(), roll, batch: box.querySelector("#af-batch").value.trim(), pin: hashPin(pin), created: Date.now() };
          raw.set("users", users);
          // move results/practice done before registering (guest) into the new account
          if (guestRes.length) { raw.set("results__" + uid, guestRes); raw.del("results"); }
          if (Object.keys(guestPrac).length) { raw.set("practice__" + uid, guestPrac); raw.del("practice"); }
          signIn(uid); done();
        } else {
          if (!users[uid]) return draw(t("noAccount"));
          if (users[uid].pin !== hashPin(pin)) return draw(t("wrongPin"));
          signIn(uid); done();
        }
      });
    }
    // save a server login on this device (also used by Google sign-in)
    function finish(uid, d, pin) {
      const users = getUsers(), p = d.profile || {};
      users[uid] = Object.assign({}, users[uid] || {}, { name: p.name || (users[uid] && users[uid].name) || "", roll: p.roll || (users[uid] && users[uid].roll) || uid, batch: p.batch || "", created: p.created || Date.now(), token: d.token },
        pin ? { pin: hashPin(pin) } : {}, p.email ? { email: p.email, picture: p.picture || "" } : {});
      raw.set("users", users);
      const server = d.results || [];
      mergeResults(uid, server);
      uploadResults(uid, d.token, raw.get("results__" + uid, []), server.map((r) => r.id));
      signIn(uid); done();
    }
    function googleCred(credential) {
      box.querySelectorAll("button, input").forEach((x) => (x.disabled = true));
      SRV.studentGoogle(credential).then((d) => (d.need === "roll" ? rollStep(d) : finish(d.uid, d))).catch((er) => draw(esc((er && er.message) || t("netErr"))));
    }
    function rollStep(g, err, needPin) {
      box.innerHTML =
        '<button class="close" data-x aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
        '<h3><i class="fa-brands fa-google" style="color:#4285f4"></i> ' + t("gStep") + "</h3>" +
        '<div class="g-who">' + (g.picture ? '<img src="' + esc(g.picture) + '" alt="" referrerpolicy="no-referrer" onerror="this.outerHTML=\'<i class=&quot;fa-solid fa-circle-user&quot;></i>\'">' : '<i class="fa-solid fa-circle-user"></i>') + "<div><b>" + esc(g.name || "") + "</b><span>" + esc(g.email || "") + "</span></div></div>" +
        (err ? '<div class="auth-err"><i class="fa-solid fa-circle-exclamation"></i> ' + err + "</div>" : "") +
        '<form id="gForm" autocomplete="off"><p style="color:var(--ink-3);font-size:13px;margin:0 0 12px">' + t("gStepNote") + "</p>" +
        '<div class="field"><label for="g-roll">' + t("roll") + ' *</label><input class="input" id="g-roll" required maxlength="30" value="' + esc(g.roll || "") + '"></div>' +
        '<div class="field"><label for="g-batch">' + t("batch") + '</label><input class="input" id="g-batch" maxlength="30" placeholder="2025-26" value="' + esc(g.batch || "") + '"></div>' +
        (needPin ? '<div class="field"><label for="g-pin">' + t("pin") + ' *</label><input class="input" id="g-pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required placeholder="••••"></div>' : "") +
        '<button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-arrow-right"></i> ' + t("gContinue") + "</button></form>";
      box.querySelector("[data-x]").addEventListener("click", m.close);
      const f = box.querySelector("#gForm");
      (box.querySelector(needPin ? "#g-pin" : "#g-roll") || {}).focus && box.querySelector(needPin ? "#g-pin" : "#g-roll").focus();
      f.addEventListener("submit", (e) => {
        e.preventDefault();
        const roll = f.querySelector("#g-roll").value.trim(), batch = f.querySelector("#g-batch").value.trim(), pinEl = f.querySelector("#g-pin"), pin = pinEl ? pinEl.value.trim() : "";
        if (pinEl && !/^\d{4}$/.test(pin)) return rollStep(Object.assign(g, { roll, batch }), t("pinErr"), true);
        f.querySelector("button[type=submit]").disabled = true;
        SRV.studentGoogleLink({ ticket: g.ticket, uid: uidOf(roll), roll, batch, name: g.name, pin }).then((d) => finish(d.uid, d, pin)).catch((er) => {
          const msg = (er && er.message) || "";
          g.roll = roll; g.batch = batch;
          if (msg === "pin_required") return rollStep(g, t("gLinkPin"), true);
          if (msg === "wrongpin") return rollStep(g, t("wrongPin"), true);
          rollStep(g, /sign in with Google/i.test(msg) ? esc(msg) : t("netErr"), needPin);
        });
      });
    }
    function serverAuth(reg, uid, roll, pin, users) {
      const btn = box.querySelector("#authForm button[type=submit]");
      btn.disabled = true; btn.insertAdjacentHTML("afterbegin", '<i class="fa-solid fa-spinner fa-spin"></i> ');
      const keep = (d) => {   // save the account on this device too (works offline next time)
        const p = d.profile || {};
        users[uid] = Object.assign({}, users[uid] || {}, { name: p.name || (users[uid] && users[uid].name) || "", roll: p.roll || roll, batch: p.batch || "", created: p.created || Date.now(), pin: hashPin(pin), token: d.token });
        raw.set("users", users);
        const server = d.results || [];
        mergeResults(uid, server);
        uploadResults(uid, d.token, raw.get("results__" + uid, []), server.map((r) => r.id));
        signIn(uid); done();
      };
      const register = (name, batch, created) => SRV.studentRegister({ uid, roll, name, batch, pin, created: created || Date.now() });
      const fail = (er) => {
        const msg = (er && er.message) || "";
        if (msg === "exists") return draw(t("rollExists"));
        if (msg === "wrongpin") return draw(t("wrongPin"));
        if (msg === "noaccount") return draw(t("noAccount"));
        // server not reachable: allow an account that already exists on this device
        if (!reg && users[uid] && users[uid].pin === hashPin(pin)) { signIn(uid); return done(); }
        draw(/^(PIN|Please|Invalid|Too many)/.test(msg) ? esc(msg) : t("netErr"));
      };
      if (reg) {
        const name = box.querySelector("#af-name").value.trim(), batch = box.querySelector("#af-batch").value.trim();
        const guestRes = raw.get("results", []), guestPrac = raw.get("practice", {});
        register(name, batch).then((d) => {
          if (guestRes.length) { raw.set("results__" + uid, guestRes); raw.del("results"); }
          if (Object.keys(guestPrac).length) { raw.set("practice__" + uid, guestPrac); raw.del("practice"); }
          keep(d);
        }).catch(fail);
      } else {
        SRV.studentLogin(uid, pin).then(keep).catch((er) => {
          // an older account that exists only on this device → create it on the server with the same PIN
          if (er && er.message === "noaccount" && users[uid] && users[uid].pin === hashPin(pin)) return register(users[uid].name, users[uid].batch || "", users[uid].created).then(keep).catch(fail);
          fail(er);
        });
      }
    }
    draw();
  }

  /* ---------- Layout ---------- */
  const LOGO = '<img class="brand-logo" src="' + ROOT + 'assets/img/logo.webp" width="256" height="205" alt="COPA MCQ logo">';

  const NAV = [
    { k: "home", href: "index.html", icon: "fa-house" },
    { k: "practice", href: "modules.html", icon: "fa-pen-to-square" },
    { k: "mock", href: "exam.html", icon: "fa-trophy" },
    { k: "livePractice", href: "live-practice.html", icon: "fa-laptop-code" },
    { k: "study", href: "study.html", icon: "fa-book-open" },
    { k: "dashboard", href: "dashboard.html", icon: "fa-gauge-high" },
    { k: "reviews", href: "reviews.html", icon: "fa-star" },
    { k: "about", href: "about.html", icon: "fa-circle-info" }
  ];
  function renderShell(active) {
    if (active === "results") active = "dashboard";
    if (active === "contact") active = "about";
    const prof = getProfile();
    const links = (cls) => NAV.map((n) => '<a href="' + ROOT + n.href + '" class="' + (active === n.k ? "active" : "") + '"><i class="fa-solid ' + n.icon + '"></i><span class="lbl">' + t(n.k) + "</span></a>").join("");
    const header = document.getElementById("app-header");
    if (header) {
      header.outerHTML =
        '<header class="site-header">' +
        '<a class="brand" href="' + ROOT + 'index.html">' + LOGO +
        '<span class="brand-text"><span class="b2">COPA MCQ</span><span class="b3">' + t("tagline") + "</span></span></a>" +
        '<nav class="main-nav" aria-label="Main">' + links() + "</nav>" +
        '<div class="header-actions">' +
        '<label class="sr-only" for="langSel">Language</label><select class="lang-select" id="langSel">' +
        '<option value="en">English</option><option value="hi">हिंदी</option><option value="mr">मराठी</option></select>' +
        '<button class="btn-login" id="loginBtn"><i class="fa-solid fa-user"></i><span class="lbl">' + (prof ? esc(prof.name.split(" ")[0]) : t("login")) + "</span></button>" +
        '<button class="menu-toggle" id="menuToggle" aria-label="Menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>' +
        "</div>" +
        '<nav class="top-drop" id="topDrop" aria-label="Mobile">' + links() + "</nav></header>" +
        '<div class="sidebar-backdrop" id="sideBack"></div>';
    }
    const side = document.getElementById("app-sidebar");
    if (side) side.remove();
    const foot = document.getElementById("app-footer");
    if (foot) {
      foot.outerHTML =
        '<footer class="site-footer"><div class="f-copy">© 2019 - ' + Math.max(2026, new Date().getFullYear()) + " <b>COPA MCQ</b>. " + t("footerRights") +
        '<br><span class="f-dev">Developed &amp; Designed by : <b>Sanjay Rathod</b> (CI - COPA)</span></div>' +
        '<nav class="f-links" aria-label="Footer"><a href="' + ROOT + 'about.html">' + t("about") + '</a><a href="' + ROOT + 'privacy.html">Privacy Policy</a><a href="' + ROOT + 'terms.html">Terms</a><a href="' + ROOT + 'admin.html"><i class="fa-solid fa-user-shield"></i> Admin</a><a href="' + ROOT + 'about.html#contact">' + t("contact") + "</a></nav>" +
        '<div class="socials"><a class="yt" href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>' +
        '<a class="tg" href="#" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>' +
        '<a class="wa" href="#" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></div></footer>';
    }
    fitHeader();
    const sel = document.getElementById("langSel");
    if (sel) { sel.value = lang; sel.addEventListener("change", () => setLang(sel.value)); }
    const lb = document.getElementById("loginBtn");
    if (lb) lb.addEventListener("click", () => (getProfile() ? (location.href = ROOT + "dashboard.html") : openLogin()));
    const mt = document.getElementById("menuToggle");
    if (mt) mt.addEventListener("click", () => {
      const o = document.body.classList.toggle("nav-open");
      mt.setAttribute("aria-expanded", o);
      mt.innerHTML = '<i class="fa-solid ' + (o ? "fa-xmark" : "fa-bars") + '"></i>';
    });
    const sb = document.getElementById("sideBack");
    if (sb) sb.addEventListener("click", () => document.body.classList.remove("nav-open", "palette-open"));
  }

  /* show the full menu whenever it fits; otherwise collapse to the ☰ drop-down */
  function fitHeader() {
    const h = document.querySelector(".site-header"); if (!h) return;
    const check = () => {
      h.classList.remove("collapsed", "compact");
      const nav = h.querySelector(".main-nav"), brand = h.querySelector(".brand"), act = h.querySelector(".header-actions");
      if (!nav || !brand || !act) return;
      const narrow = () => {
        const b = brand.getBoundingClientRect(), n = nav.getBoundingClientRect(), a = act.getBoundingClientRect();
        return window.innerWidth < 760 || n.left < b.right + 8 || n.right > a.left - 8 || h.scrollWidth > h.clientWidth + 1 || n.height > 56;
      };
      // 1) full menu  2) menu without icons  3) ☰ menu button
      let tooNarrow = narrow();
      if (tooNarrow && window.innerWidth >= 760) { h.classList.add("compact"); tooNarrow = narrow(); if (tooNarrow) h.classList.remove("compact"); }
      h.classList.toggle("collapsed", tooNarrow);
      if (!tooNarrow) document.body.classList.remove("nav-open");
    };
    check();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(check);
    let t; window.addEventListener("resize", () => { clearTimeout(t); t = setTimeout(check, 80); });
  }

  const langListeners = [];
  function setLang(l) {
    if (!I18N[l]) return;
    lang = l; raw.set("lang", l);
    document.documentElement.lang = l;
    document.body.setAttribute("data-lang", l);
    if (langListeners.length) { langListeners.forEach((f) => f(l)); } else { location.reload(); }
  }
  function onLang(fn) { langListeners.push(fn); }

  function applyStatic() {
    document.querySelectorAll("[data-i18n]").forEach((el) => { el.textContent = t(el.getAttribute("data-i18n")); });
    document.querySelectorAll("[data-i18n-ph]").forEach((el) => { el.placeholder = t(el.getAttribute("data-i18n-ph")); });
  }

  /* ---------- Stats / progress ---------- */
  function getStats() {
    const hist = store.get("results", []);
    const prac = store.get("practice", {});
    let practiced = 0, pc = 0;
    Object.keys(prac).forEach((m) => { const a = prac[m].ans || {}; Object.keys(a).forEach((k) => { practiced++; if (a[k].ok) pc++; }); });
    const avg = hist.length ? Math.round(hist.reduce((s, r) => s + r.pct, 0) / hist.length) : 0;
    const best = hist.length ? Math.max.apply(null, hist.map((r) => r.pct)) : 0;
    return { tests: hist.length, avg, best, practiced, practicedCorrect: pc, hist, prac };
  }
  function moduleProgress(m) {
    const prac = store.get("practice", {});
    const a = (prac[m] && prac[m].ans) || {};
    const total = byModule(m).length;
    const done = Object.keys(a).length;
    const ok = Object.keys(a).filter((k) => a[k].ok).length;
    return { total, done, ok, pct: total ? Math.round((done / total) * 100) : 0 };
  }

  document.documentElement.lang = lang;
  document.body.setAttribute("data-lang", lang);

  window.COPA = {
    ROOT, MODULES, I18N, QUESTIONS, NOTES, normQ, qById, byModule, modulePage, iconCls,
    get lang() { return lang; },
    t, modName, esc, fmtTime, shuffle, pad, store, toast, modal,
    google: { on: () => googleOn(), load: () => loadGsi(), clientId: GCID }, server: SRV,
    PASS, explain, renderNotes, notesFor, sanitizeHTML, noteHTML, renderShell, setLang, onLang, applyStatic, getProfile, openLogin, logout, getStats, moduleProgress, qBlock, optText
  };

  /* ---------- online mode (MySQL / Firebase): get the admin's questions, notes and settings ----------
     Pages read this content from a local cache at load. Refresh it in the background (max every 10 min,
     at once when the cache was never filled) and reload once if something changed – but never in the
     middle of an exam, in Live Practice or in the admin panel. */
  /* ---------- MySQL mode: users online, visitors and the student's history from other devices ---------- */
  window.addEventListener("load", () => {
    if (!SRV || /admin\.html/.test(location.pathname)) return;
    let vid = ""; try { vid = localStorage.getItem("copa_vid") || ""; if (!vid) { vid = "v" + Date.now().toString(36) + Math.random().toString(36).slice(2, 10); localStorage.setItem("copa_vid", vid); } } catch (e) { vid = "v" + Math.random().toString(36).slice(2, 12); }
    const info = () => ({ vid, page: location.pathname, title: document.title.split(" – ")[0].slice(0, 150), token: studentToken() });
    SRV.hit(Object.assign(info(), { ref: document.referrer })).catch(() => {});
    setInterval(() => { if (!document.hidden) SRV.ping(info()).catch(() => {}); }, 30000);
    document.addEventListener("visibilitychange", () => { if (!document.hidden) SRV.ping(info()).catch(() => {}); });
    window.addEventListener("pagehide", () => SRV.bye(vid));
    // results taken on another phone / computer
    const p = getProfile();
    if (p && p.token) SRV.studentResults(p.token).then((d) => {
      const added = mergeResults(p.uid, d.results);
      if (added && /dashboard\.html|results\.html/.test(location.pathname)) location.reload();
    }).catch((er) => {
      if (er && /log in again/i.test(er.message)) { const users = getUsers(); if (users[p.uid]) { delete users[p.uid].token; raw.set("users", users); } raw.del("current"); toast(t("sessionEnded")); }
    });
  });

  window.addEventListener("load", () => {
    const D = window.COPA_DB; if (!D || D.mode === "local") return;
    const path = location.pathname;
    const busy = /admin\.html|live-practice\.html/.test(path) || document.body.classList.contains("cbt") || /[?&](stage|result)=/.test(location.search);
    let first = false; try { first = !localStorage.getItem("copa_db_refreshed"); } catch (e) { /* ignore */ }
    D.refreshCache(first).then((changed) => {
      if (!changed || busy) return;
      let last = 0; try { last = +sessionStorage.getItem("copa_reloaded") || 0; } catch (e) { /* ignore */ }
      if (Date.now() - last < 60000) return;
      try { sessionStorage.setItem("copa_reloaded", String(Date.now())); } catch (e) { /* ignore */ }
      if (document.body.classList.contains("cbt") || document.querySelector(".cbt-top")) return; // exam started meanwhile
      location.reload();
    });
  });
})();
