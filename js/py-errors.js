/* =========================================================
   COPA Live Practice – Python errors explained in English, हिंदी and मराठी
   COPA_PY_ERRORS.explain(errorType, message, lang) → { title, what, fix }
   ========================================================= */
(function () {
  "use strict";
  // Each rule: [ErrorType or "*", regex on the message (or null), { en: [title, what, fix], hi: […], mr: […] }]
  // {1}, {2} … are replaced with the regex groups.
  const R = [
    /* ---------- SyntaxError / IndentationError ---------- */
    ["SyntaxError", /expected ':'/, {
      en: ["Colon ( : ) is missing", "Lines with if, elif, else, for, while, def, class and try must end with a colon.", "Add : at the end of the line, e.g.  if marks >= 40:"],
      hi: ["कोलन ( : ) छूट गया है", "if, elif, else, for, while, def, class और try वाली पंक्ति के अंत में कोलन होना चाहिए।", "पंक्ति के अंत में : लगाएँ, जैसे  if marks >= 40:"],
      mr: ["कोलन ( : ) राहिला आहे", "if, elif, else, for, while, def, class आणि try असलेल्या ओळीच्या शेवटी कोलन असावा लागतो.", "ओळीच्या शेवटी : लावा, उदा.  if marks >= 40:"] }],
    ["SyntaxError", /unterminated string literal|EOL while scanning string/, {
      en: ["Quote is not closed", "A text (string) was started with \" or ' but not closed.", "Close the text with the same quote, e.g.  print(\"Hello\")"],
      hi: ["कोट बंद नहीं किया", "टेक्स्ट (string) \" या ' से शुरू किया पर बंद नहीं किया।", "उसी कोट से टेक्स्ट बंद करें, जैसे  print(\"Hello\")"],
      mr: ["कोट बंद केला नाही", "मजकूर (string) \" किंवा ' ने सुरू केला पण बंद केला नाही.", "त्याच कोटने मजकूर बंद करा, उदा.  print(\"Hello\")"] }],
    ["SyntaxError", /unterminated triple-quoted string/, {
      en: ["Triple quote is not closed", "A ''' or \"\"\" text was started but never closed.", "Add the closing ''' or \"\"\" at the end of the text."],
      hi: ["ट्रिपल कोट बंद नहीं", "''' या \"\"\" वाला टेक्स्ट शुरू हुआ पर बंद नहीं हुआ।", "टेक्स्ट के अंत में ''' या \"\"\" लगाएँ।"],
      mr: ["ट्रिपल कोट बंद नाही", "''' किंवा \"\"\" असलेला मजकूर सुरू झाला पण बंद झाला नाही.", "मजकुराच्या शेवटी ''' किंवा \"\"\" लावा."] }],
    ["SyntaxError", /'(\(|\[|\{)' was never closed/, {
      en: ["Bracket {1} is not closed", "You opened a bracket {1} but did not close it.", "Add the matching closing bracket ) ] or } ."],
      hi: ["ब्रैकेट {1} बंद नहीं किया", "आपने ब्रैकेट {1} खोला पर बंद नहीं किया।", "मिलता हुआ बंद ब्रैकेट ) ] या } लगाएँ।"],
      mr: ["कंस {1} बंद केला नाही", "तुम्ही कंस {1} उघडला पण बंद केला नाही.", "जुळणारा बंद कंस ) ] किंवा } लावा."] }],
    ["SyntaxError", /unmatched '(\)|\]|\})'|closing parenthesis '(.)' does not match/, {
      en: ["Extra or wrong closing bracket", "There is a closing bracket without a matching opening bracket.", "Remove the extra bracket or use the correct one ( ) [ ] { }."],
      hi: ["अतिरिक्त या गलत बंद ब्रैकेट", "बिना खुले ब्रैकेट के बंद ब्रैकेट लगा है।", "अतिरिक्त ब्रैकेट हटाएँ या सही ब्रैकेट ( ) [ ] { } लगाएँ।"],
      mr: ["जास्त किंवा चुकीचा बंद कंस", "उघडलेल्या कंसाशिवाय बंद कंस लावला आहे.", "जास्तीचा कंस काढा किंवा योग्य कंस ( ) [ ] { } वापरा."] }],
    ["SyntaxError", /invalid syntax\. Maybe you meant '==' or ':=' instead of '='|cannot assign to/, {
      en: ["Use == to compare", "A single = stores a value. To check if two values are equal, use ==.", "Write  if a == b:  instead of  if a = b:"],
      hi: ["तुलना के लिए == लगाएँ", "एक = मान रखता है। दो मान बराबर हैं या नहीं जाँचने के लिए == चाहिए।", "if a = b: की जगह  if a == b:  लिखें"],
      mr: ["तुलनेसाठी == वापरा", "एक = मूल्य साठवतो. दोन मूल्ये समान आहेत का हे तपासण्यासाठी == लागतो.", "if a = b: ऐवजी  if a == b:  लिहा"] }],
    ["SyntaxError", /Missing parentheses in call to 'print'/, {
      en: ["print needs brackets", "In Python 3, print is a function, so it needs ( ).", "Write  print(\"Hello\")  instead of  print \"Hello\""],
      hi: ["print के साथ कोष्ठक चाहिए", "Python 3 में print एक फंक्शन है, इसलिए ( ) ज़रूरी है।", "print \"Hello\" की जगह  print(\"Hello\")  लिखें"],
      mr: ["print सोबत कंस लागतो", "Python 3 मध्ये print हे फंक्शन आहे, म्हणून ( ) आवश्यक आहे.", "print \"Hello\" ऐवजी  print(\"Hello\")  लिहा"] }],
    ["SyntaxError", /invalid syntax\. Perhaps you forgot a comma/, {
      en: ["Comma is missing", "Values inside print( ), lists or function calls must be separated with commas.", "Add a comma between the values, e.g.  print(\"Sum =\", total)"],
      hi: ["कॉमा छूट गया", "print( ), लिस्ट या फंक्शन में मानों के बीच कॉमा होना चाहिए।", "मानों के बीच कॉमा लगाएँ, जैसे  print(\"Sum =\", total)"],
      mr: ["स्वल्पविराम राहिला", "print( ), लिस्ट किंवा फंक्शनमधील मूल्यांमध्ये स्वल्पविराम असावा लागतो.", "मूल्यांमध्ये स्वल्पविराम लावा, उदा.  print(\"Sum =\", total)"] }],
    ["SyntaxError", /'return' outside function/, {
      en: ["return outside a function", "return can be used only inside a def function.", "Move the return line inside a function, or use print( ) instead."],
      hi: ["फंक्शन के बाहर return", "return केवल def फंक्शन के अंदर लिखा जा सकता है।", "return वाली पंक्ति फंक्शन के अंदर रखें या print( ) उपयोग करें।"],
      mr: ["फंक्शनच्या बाहेर return", "return फक्त def फंक्शनच्या आतच वापरता येतो.", "return ची ओळ फंक्शनच्या आत ठेवा किंवा print( ) वापरा."] }],
    ["SyntaxError", /'break' outside loop|'continue' not properly in loop/, {
      en: ["break / continue outside a loop", "break and continue work only inside a for or while loop.", "Put break/continue inside the loop (check the indentation)."],
      hi: ["लूप के बाहर break / continue", "break और continue केवल for या while लूप के अंदर काम करते हैं।", "break/continue को लूप के अंदर रखें (इंडेंटेशन जाँचें)।"],
      mr: ["लूपच्या बाहेर break / continue", "break आणि continue फक्त for किंवा while लूपच्या आतच चालतात.", "break/continue लूपच्या आत ठेवा (इंडेंटेशन तपासा)."] }],
    ["SyntaxError", /invalid character|invalid non-printable character/, {
      en: ["Wrong character in the code", "The code has a symbol Python does not understand (often curly quotes “ ” or a Marathi/Hindi character outside quotes).", "Re-type the quotes with the keyboard (\" or ') and keep other language text inside quotes."],
      hi: ["कोड में गलत अक्षर", "कोड में ऐसा चिह्न है जो Python नहीं समझता (अक्सर “ ” कोट या कोट के बाहर हिंदी/मराठी अक्षर)।", "कोट कीबोर्ड से दोबारा टाइप करें (\" या ') और दूसरी भाषा का टेक्स्ट कोट के अंदर रखें।"],
      mr: ["कोडमध्ये चुकीचे अक्षर", "कोडमध्ये असे चिन्ह आहे जे Python ला समजत नाही (बहुतेक “ ” कोट किंवा कोटच्या बाहेर मराठी/हिंदी अक्षर).", "कोट कीबोर्डने पुन्हा टाइप करा (\" किंवा ') आणि इतर भाषेतील मजकूर कोटच्या आत ठेवा."] }],
    ["SyntaxError", /invalid decimal literal|leading zeros in decimal integer/, {
      en: ["Wrong number or name", "A number is written wrongly, or a variable name starts with a digit (like 1st_name).", "Variable names must start with a letter or _, e.g. first_name. Do not write 08, write 8."],
      hi: ["गलत संख्या या नाम", "संख्या गलत लिखी है या वेरिएबल नाम अंक से शुरू हुआ है (जैसे 1st_name)।", "वेरिएबल नाम अक्षर या _ से शुरू करें, जैसे first_name। 08 नहीं, 8 लिखें।"],
      mr: ["चुकीची संख्या किंवा नाव", "संख्या चुकीची लिहिली आहे किंवा व्हेरिएबलचे नाव अंकाने सुरू होते (उदा. 1st_name).", "व्हेरिएबलचे नाव अक्षराने किंवा _ ने सुरू करा, उदा. first_name. 08 नाही, 8 लिहा."] }],
    ["IndentationError", /expected an indented block/, {
      en: ["Indentation (spaces) missing", "After a line ending with : the next lines must start with 4 spaces.", "Press Tab (or 4 spaces) before the lines inside if / for / while / def."],
      hi: ["इंडेंटेशन (स्पेस) नहीं है", ": वाली पंक्ति के बाद की पंक्तियाँ 4 स्पेस से शुरू होनी चाहिए।", "if / for / while / def के अंदर की पंक्तियों से पहले Tab (या 4 स्पेस) दबाएँ।"],
      mr: ["इंडेंटेशन (स्पेस) नाही", ": ने संपणाऱ्या ओळीनंतरच्या ओळी 4 स्पेसने सुरू व्हायला हव्यात.", "if / for / while / def च्या आतल्या ओळींच्या आधी Tab (किंवा 4 स्पेस) दाबा."] }],
    ["IndentationError", /unexpected indent/, {
      en: ["Extra spaces at the start of the line", "This line starts with spaces, but it is not inside an if / for / def block.", "Remove the spaces at the beginning of the line."],
      hi: ["पंक्ति की शुरुआत में अतिरिक्त स्पेस", "यह पंक्ति स्पेस से शुरू है, पर किसी if / for / def ब्लॉक के अंदर नहीं है।", "पंक्ति की शुरुआत के स्पेस हटाएँ।"],
      mr: ["ओळीच्या सुरुवातीला जास्त स्पेस", "ही ओळ स्पेसने सुरू होते, पण ती कोणत्याही if / for / def ब्लॉकच्या आत नाही.", "ओळीच्या सुरुवातीचे स्पेस काढून टाका."] }],
    ["IndentationError", /unindent does not match|inconsistent use of tabs/, {
      en: ["Spaces do not line up", "Lines of the same block must start with exactly the same number of spaces.", "Line up this line with the other lines of the block (use 4 spaces every time)."],
      hi: ["स्पेस बराबर नहीं हैं", "एक ही ब्लॉक की पंक्तियाँ बिलकुल समान स्पेस से शुरू होनी चाहिए।", "इस पंक्ति को ब्लॉक की बाकी पंक्तियों के बराबर लाएँ (हर बार 4 स्पेस)।"],
      mr: ["स्पेस जुळत नाहीत", "एकाच ब्लॉकच्या ओळी अगदी तितक्याच स्पेसने सुरू व्हाव्यात.", "ही ओळ ब्लॉकच्या इतर ओळींच्या बरोबर आणा (प्रत्येक वेळी 4 स्पेस)."] }],
    ["TabError", null, {
      en: ["Tabs and spaces are mixed", "The block uses both Tab and spaces for indentation.", "Use only spaces (4 spaces) for indentation."],
      hi: ["टैब और स्पेस मिल गए", "ब्लॉक में इंडेंटेशन के लिए टैब और स्पेस दोनों लगे हैं।", "इंडेंटेशन के लिए केवल स्पेस (4 स्पेस) लगाएँ।"],
      mr: ["टॅब आणि स्पेस मिसळले", "ब्लॉकमध्ये इंडेंटेशनसाठी टॅब आणि स्पेस दोन्ही वापरले आहेत.", "इंडेंटेशनसाठी फक्त स्पेस (4 स्पेस) वापरा."] }],
    ["SyntaxError", null, {
      en: ["Mistake in writing the code (syntax)", "Python could not understand this line – a symbol, bracket, quote or : may be missing or extra.", "Check the line shown above carefully: brackets ( ), quotes \" \", colon : and spelling of keywords."],
      hi: ["कोड लिखने में गलती (सिंटैक्स)", "Python यह पंक्ति नहीं समझ पाया – कोई चिह्न, ब्रैकेट, कोट या : छूट गया या ज़्यादा है।", "ऊपर दिखाई पंक्ति ध्यान से जाँचें: ब्रैकेट ( ), कोट \" \", कोलन : और कीवर्ड की स्पेलिंग।"],
      mr: ["कोड लिहिण्यात चूक (सिंटॅक्स)", "Python ला ही ओळ समजली नाही – एखादे चिन्ह, कंस, कोट किंवा : राहिले आहे किंवा जास्त आहे.", "वर दाखवलेली ओळ नीट तपासा: कंस ( ), कोट \" \", कोलन : आणि कीवर्डची स्पेलिंग."] }],

    /* ---------- NameError ---------- */
    ["NameError", /name '(\w+)' is not defined\. Did you mean: '(\w+)'/, {
      en: ["Spelling mistake: '{1}'", "Python does not know '{1}'. You probably meant '{2}'.", "Change '{1}' to '{2}'."],
      hi: ["स्पेलिंग की गलती: '{1}'", "Python '{1}' को नहीं जानता। शायद आप '{2}' लिखना चाहते थे।", "'{1}' को '{2}' में बदलें।"],
      mr: ["स्पेलिंगची चूक: '{1}'", "Python ला '{1}' माहीत नाही. बहुतेक तुम्हाला '{2}' लिहायचे होते.", "'{1}' बदलून '{2}' करा."] }],
    ["NameError", /name '(\w+)' is not defined/, {
      en: ["'{1}' is not defined", "You used '{1}' before creating it, or the spelling is different (Python is case-sensitive: Name ≠ name).", "Create it first ( {1} = … ), check the spelling, or put text inside quotes: \"{1}\"."],
      hi: ["'{1}' परिभाषित नहीं है", "आपने '{1}' बनाने से पहले उपयोग किया, या स्पेलिंग अलग है (Python में Name ≠ name)।", "पहले इसे बनाएँ ( {1} = … ), स्पेलिंग जाँचें, या टेक्स्ट हो तो कोट में लिखें: \"{1}\"।"],
      mr: ["'{1}' तयार केलेले नाही", "तुम्ही '{1}' तयार करण्याआधी वापरले, किंवा स्पेलिंग वेगळी आहे (Python मध्ये Name ≠ name).", "आधी ते तयार करा ( {1} = … ), स्पेलिंग तपासा, किंवा मजकूर असल्यास कोटमध्ये लिहा: \"{1}\"."] }],
    ["UnboundLocalError", /'(\w+)'/, {
      en: ["Variable '{1}' used before giving it a value", "Inside the function, '{1}' is changed, so Python treats it as a new local variable that has no value yet.", "Give '{1}' a value inside the function first, or pass it as a parameter / use  global {1}."],
      hi: ["वेरिएबल '{1}' को मान देने से पहले उपयोग", "फंक्शन के अंदर '{1}' बदला गया है, इसलिए Python इसे नया लोकल वेरिएबल मानता है जिसका अभी मान नहीं है।", "फंक्शन में पहले '{1}' को मान दें, या पैरामीटर के रूप में भेजें / global {1} लिखें।"],
      mr: ["व्हेरिएबल '{1}' ला मूल्य देण्याआधी वापरले", "फंक्शनमध्ये '{1}' बदलले आहे, म्हणून Python त्याला नवीन लोकल व्हेरिएबल मानते ज्याला अजून मूल्य नाही.", "फंक्शनमध्ये आधी '{1}' ला मूल्य द्या, किंवा पॅरामीटर म्हणून पाठवा / global {1} लिहा."] }],

    /* ---------- TypeError ---------- */
    ["TypeError", /can only concatenate str \(not "(\w+)"\) to str/, {
      en: ["Text and number cannot be joined with +", "You tried to join text (str) with a {1} using +.", "Convert the number to text: str(number), or use a comma in print: print(\"Total =\", total)"],
      hi: ["टेक्स्ट और संख्या को + से नहीं जोड़ सकते", "आपने टेक्स्ट (str) को {1} के साथ + से जोड़ने की कोशिश की।", "संख्या को टेक्स्ट में बदलें: str(number), या print में कॉमा लगाएँ: print(\"Total =\", total)"],
      mr: ["मजकूर आणि संख्या + ने जोडता येत नाहीत", "तुम्ही मजकूर (str) आणि {1} + ने जोडण्याचा प्रयत्न केला.", "संख्येचे मजकुरात रूपांतर करा: str(number), किंवा print मध्ये स्वल्पविराम वापरा: print(\"Total =\", total)"] }],
    ["TypeError", /unsupported operand type\(s\) for ([^:]+): '(\w+)' and '(\w+)'/, {
      en: ["Cannot use {1} with {2} and {3}", "The values are of different types ({2} and {3}). input() always gives text (str).", "Convert first, e.g.  a = int(input())  or  float(…) , then calculate."],
      hi: ["{2} और {3} पर {1} नहीं चला सकते", "मान अलग प्रकार के हैं ({2} और {3})। input() हमेशा टेक्स्ट (str) देता है।", "पहले बदलें, जैसे  a = int(input())  या  float(…) , फिर गणना करें।"],
      mr: ["{2} आणि {3} वर {1} वापरता येत नाही", "मूल्ये वेगवेगळ्या प्रकारची आहेत ({2} आणि {3}). input() नेहमी मजकूर (str) देते.", "आधी रूपांतर करा, उदा.  a = int(input())  किंवा  float(…) , मग आकडेमोड करा."] }],
    ["TypeError", /'(\w+)' object is not callable/, {
      en: ["'{1}' cannot be called with ( )", "You wrote ( ) after a value that is not a function – maybe you used a function name (like sum, list, str) as a variable name.", "Do not use names like sum, list, str, input for variables; or remove the ( )."],
      hi: ["'{1}' को ( ) से नहीं चला सकते", "आपने ऐसे मान के बाद ( ) लिखा जो फंक्शन नहीं है – शायद sum, list, str जैसे फंक्शन नाम को वेरिएबल बना दिया।", "sum, list, str, input जैसे नाम वेरिएबल के लिए न लें; या ( ) हटाएँ।"],
      mr: ["'{1}' ला ( ) ने चालवता येत नाही", "जे फंक्शन नाही अशा मूल्यानंतर तुम्ही ( ) लिहिले – कदाचित sum, list, str सारखे फंक्शनचे नाव व्हेरिएबल म्हणून वापरले.", "sum, list, str, input सारखी नावे व्हेरिएबलसाठी वापरू नका; किंवा ( ) काढा."] }],
    ["TypeError", /'(\w+)' object is not subscriptable/, {
      en: ["Cannot use [ ] on a {1}", "Square brackets [ ] work only with lists, strings, tuples and dictionaries, not with a {1}.", "Check the variable – maybe it should be a list, or use ( ) to call a function."],
      hi: ["{1} पर [ ] नहीं लगा सकते", "[ ] केवल लिस्ट, स्ट्रिंग, ट्यूपल और डिक्शनरी पर चलता है, {1} पर नहीं।", "वेरिएबल जाँचें – शायद यह लिस्ट होनी चाहिए, या फंक्शन के लिए ( ) लगाएँ।"],
      mr: ["{1} वर [ ] वापरता येत नाही", "[ ] फक्त लिस्ट, स्ट्रिंग, ट्युपल आणि डिक्शनरीवर चालतो, {1} वर नाही.", "व्हेरिएबल तपासा – कदाचित ती लिस्ट असायला हवी, किंवा फंक्शनसाठी ( ) वापरा."] }],
    ["TypeError", /'(\w+)' object is not iterable/, {
      en: ["Cannot loop over '{1}'", "for … in needs a list, string, range( ) etc. A single {1} value cannot be looped.", "Use range( ), e.g.  for i in range(n):"],
      hi: ["{1} पर लूप नहीं चला सकते", "for … in को लिस्ट, स्ट्रिंग, range( ) आदि चाहिए। अकेले {1} पर लूप नहीं चलता।", "range( ) लगाएँ, जैसे  for i in range(n):"],
      mr: ["{1} वर लूप चालवता येत नाही", "for … in साठी लिस्ट, स्ट्रिंग, range( ) इ. लागते. एकट्या {1} वर लूप चालत नाही.", "range( ) वापरा, उदा.  for i in range(n):"] }],
    ["TypeError", /missing (\d+) required positional argument/, {
      en: ["Value missing in function call", "The function needs {1} more value(s) inside the brackets.", "Pass all the values the function needs, e.g.  area(5)  instead of  area()"],
      hi: ["फंक्शन कॉल में मान छूटा", "फंक्शन को कोष्ठक में {1} और मान चाहिए।", "फंक्शन को सभी ज़रूरी मान दें, जैसे  area()  की जगह  area(5)"],
      mr: ["फंक्शन कॉलमध्ये मूल्य राहिले", "फंक्शनला कंसात आणखी {1} मूल्य(े) हवी आहेत.", "फंक्शनला सर्व आवश्यक मूल्ये द्या, उदा.  area()  ऐवजी  area(5)"] }],
    ["TypeError", /takes (\d+) positional arguments? but (\d+) (were|was) given/, {
      en: ["Too many values in function call", "The function accepts {1} value(s) but {2} were given. (Methods inside a class need self as the first parameter.)", "Pass the correct number of values, or add self in  def method(self, …):"],
      hi: ["फंक्शन कॉल में ज़्यादा मान", "फंक्शन {1} मान लेता है पर {2} दिए गए। (क्लास के मेथड में पहला पैरामीटर self चाहिए।)", "सही संख्या में मान दें, या  def method(self, …):  में self जोड़ें"],
      mr: ["फंक्शन कॉलमध्ये जास्त मूल्ये", "फंक्शन {1} मूल्य(े) घेते पण {2} दिली. (क्लासमधील मेथडमध्ये पहिला पॅरामीटर self लागतो.)", "योग्य संख्येने मूल्ये द्या, किंवा  def method(self, …):  मध्ये self जोडा"] }],
    ["TypeError", /'(<|>|<=|>=)' not supported between instances of '(\w+)' and '(\w+)'/, {
      en: ["Cannot compare {2} with {3}", "You compared a {2} and a {3} with {1}. Often one of them came from input() and is still text.", "Convert with int( ) or float( ) before comparing, e.g.  marks = int(input())"],
      hi: ["{2} और {3} की तुलना नहीं हो सकती", "आपने {2} और {3} की {1} से तुलना की। अक्सर इनमें से एक input() से आया टेक्स्ट होता है।", "तुलना से पहले int( ) या float( ) से बदलें, जैसे  marks = int(input())"],
      mr: ["{2} आणि {3} ची तुलना होऊ शकत नाही", "तुम्ही {2} आणि {3} यांची {1} ने तुलना केली. बहुतेक त्यातील एक input() मधून आलेला मजकूर असतो.", "तुलनेआधी int( ) किंवा float( ) ने रूपांतर करा, उदा.  marks = int(input())"] }],
    ["TypeError", /object of type '(\w+)' has no len\(\)/, {
      en: ["len( ) does not work on a {1}", "len( ) gives the length of a string, list, tuple or dictionary – not of a {1}.", "Use len( ) on text or a list. For a number, convert it: len(str(n))"],
      hi: ["{1} पर len( ) नहीं चलता", "len( ) स्ट्रिंग, लिस्ट, ट्यूपल या डिक्शनरी की लंबाई देता है – {1} की नहीं।", "len( ) टेक्स्ट या लिस्ट पर लगाएँ। संख्या के लिए: len(str(n))"],
      mr: ["{1} वर len( ) चालत नाही", "len( ) स्ट्रिंग, लिस्ट, ट्युपल किंवा डिक्शनरीची लांबी देते – {1} ची नाही.", "len( ) मजकूर किंवा लिस्टवर वापरा. संख्येसाठी: len(str(n))"] }],
    ["TypeError", /'(\w+)' object does not support item assignment/, {
      en: ["A {1} cannot be changed", "A {1} is fixed (immutable) – you cannot change one item with [ ] = .", "Make a new value instead, or use a list (lists can be changed)."],
      hi: ["{1} को बदला नहीं जा सकता", "{1} स्थिर (immutable) है – [ ] = से एक आइटम नहीं बदल सकते।", "नया मान बनाएँ, या लिस्ट उपयोग करें (लिस्ट बदली जा सकती है)।"],
      mr: ["{1} बदलता येत नाही", "{1} स्थिर (immutable) आहे – [ ] = ने एक आयटम बदलता येत नाही.", "नवीन मूल्य तयार करा, किंवा लिस्ट वापरा (लिस्ट बदलता येते)."] }],
    ["TypeError", null, {
      en: ["Wrong type of value", "An operation got a value of the wrong type (for example text where a number was needed).", "Check the types: use int( ), float( ) or str( ) to convert values."],
      hi: ["गलत प्रकार का मान", "किसी क्रिया को गलत प्रकार का मान मिला (जैसे संख्या की जगह टेक्स्ट)।", "प्रकार जाँचें: मान बदलने के लिए int( ), float( ) या str( ) लगाएँ।"],
      mr: ["चुकीच्या प्रकारचे मूल्य", "एखाद्या क्रियेला चुकीच्या प्रकारचे मूल्य मिळाले (उदा. संख्येऐवजी मजकूर).", "प्रकार तपासा: रूपांतरासाठी int( ), float( ) किंवा str( ) वापरा."] }],

    /* ---------- ValueError ---------- */
    ["ValueError", /invalid literal for int\(\) with base 10: '(.*)'/, {
      en: ["'{1}' is not a whole number", "int( ) can convert only digits like 25. It got '{1}' (empty input, letters, spaces or a decimal point).", "Type a whole number in the Input box. For decimals use float( ) instead of int( )."],
      hi: ["'{1}' पूर्ण संख्या नहीं है", "int( ) केवल 25 जैसे अंक बदल सकता है। इसे '{1}' मिला (खाली इनपुट, अक्षर, स्पेस या दशमलव)।", "Input बॉक्स में पूर्ण संख्या टाइप करें। दशमलव के लिए int( ) की जगह float( ) लगाएँ।"],
      mr: ["'{1}' पूर्ण संख्या नाही", "int( ) फक्त 25 सारख्या अंकांचे रूपांतर करते. त्याला '{1}' मिळाले (रिकामे इनपुट, अक्षरे, स्पेस किंवा दशांश).", "Input बॉक्समध्ये पूर्ण संख्या टाइप करा. दशांशासाठी int( ) ऐवजी float( ) वापरा."] }],
    ["ValueError", /could not convert string to float: '(.*)'/, {
      en: ["'{1}' is not a number", "float( ) got '{1}', which is not a number.", "Type a number like 7 or 7.5 in the Input box."],
      hi: ["'{1}' संख्या नहीं है", "float( ) को '{1}' मिला, जो संख्या नहीं है।", "Input बॉक्स में 7 या 7.5 जैसी संख्या टाइप करें।"],
      mr: ["'{1}' संख्या नाही", "float( ) ला '{1}' मिळाले, जे संख्या नाही.", "Input बॉक्समध्ये 7 किंवा 7.5 सारखी संख्या टाइप करा."] }],
    ["ValueError", /not enough values to unpack|too many values to unpack/, {
      en: ["Number of variables and values do not match", "On the left of = there are not the same number of variables as values on the right.", "Use the same count, e.g.  a, b = 10, 20"],
      hi: ["वेरिएबल और मानों की संख्या मेल नहीं खाती", "= के बाईं ओर के वेरिएबल और दाईं ओर के मान बराबर संख्या में नहीं हैं।", "समान संख्या रखें, जैसे  a, b = 10, 20"],
      mr: ["व्हेरिएबल आणि मूल्यांची संख्या जुळत नाही", "= च्या डावीकडील व्हेरिएबल्स आणि उजवीकडील मूल्ये समान संख्येने नाहीत.", "समान संख्या ठेवा, उदा.  a, b = 10, 20"] }],
    ["ValueError", /math domain error/, {
      en: ["Maths not possible for this value", "For example, square root or log of a negative number is not possible.", "Check the value before calling math.sqrt( ) / math.log( )."],
      hi: ["इस मान पर गणित संभव नहीं", "जैसे ऋणात्मक संख्या का वर्गमूल या log संभव नहीं।", "math.sqrt( ) / math.log( ) से पहले मान जाँचें।"],
      mr: ["या मूल्यावर गणित शक्य नाही", "उदा. ऋण संख्येचे वर्गमूळ किंवा log शक्य नाही.", "math.sqrt( ) / math.log( ) आधी मूल्य तपासा."] }],
    ["ValueError", /(.+) is not in list|list\.remove\(x\): x not in list/, {
      en: ["Item not found in the list", "remove( ) or index( ) looked for a value that is not in the list.", "Check first with  if x in my_list:"],
      hi: ["लिस्ट में आइटम नहीं मिला", "remove( ) या index( ) ने ऐसा मान ढूँढा जो लिस्ट में नहीं है।", "पहले जाँचें:  if x in my_list:"],
      mr: ["लिस्टमध्ये आयटम सापडला नाही", "remove( ) किंवा index( ) ने असे मूल्य शोधले जे लिस्टमध्ये नाही.", "आधी तपासा:  if x in my_list:"] }],
    ["ValueError", null, {
      en: ["Right type but wrong value", "The function got a value it cannot use.", "Check the value you pass (for example the text typed in the Input box)."],
      hi: ["सही प्रकार पर गलत मान", "फंक्शन को ऐसा मान मिला जिसका वह उपयोग नहीं कर सकता।", "दिया गया मान जाँचें (जैसे Input बॉक्स में टाइप किया टेक्स्ट)।"],
      mr: ["योग्य प्रकार पण चुकीचे मूल्य", "फंक्शनला असे मूल्य मिळाले जे ते वापरू शकत नाही.", "दिलेले मूल्य तपासा (उदा. Input बॉक्समध्ये टाइप केलेला मजकूर)."] }],

    /* ---------- other runtime errors ---------- */
    ["ZeroDivisionError", null, {
      en: ["Division by zero", "A number cannot be divided by 0 (this also applies to % and //).", "Check that the divisor is not 0, e.g.  if b != 0:  print(a / b)"],
      hi: ["शून्य से भाग", "किसी संख्या को 0 से भाग नहीं दे सकते (% और // पर भी लागू)।", "जाँचें कि भाजक 0 न हो, जैसे  if b != 0:  print(a / b)"],
      mr: ["शून्याने भागाकार", "कोणत्याही संख्येला 0 ने भागता येत नाही (% आणि // ला सुद्धा लागू).", "भाजक 0 नाही हे तपासा, उदा.  if b != 0:  print(a / b)"] }],
    ["IndexError", /(list|string|tuple|range object) index out of range/, {
      en: ["Position does not exist in the {1}", "Indexes start at 0. For a list of 3 items the valid positions are 0, 1, 2 (or -1 for the last).", "Use a smaller index, or check with len( ) first."],
      hi: ["{1} में यह स्थान नहीं है", "इंडेक्स 0 से शुरू होता है। 3 आइटम की लिस्ट में मान्य स्थान 0, 1, 2 हैं (या अंतिम के लिए -1)।", "छोटा इंडेक्स लें, या पहले len( ) से जाँचें।"],
      mr: ["{1} मध्ये ही जागा नाही", "इंडेक्स 0 पासून सुरू होतो. 3 आयटमच्या लिस्टमध्ये वैध जागा 0, 1, 2 आहेत (किंवा शेवटच्यासाठी -1).", "लहान इंडेक्स वापरा, किंवा आधी len( ) ने तपासा."] }],
    ["IndexError", /pop from empty list/, {
      en: ["The list is empty", "pop( ) cannot remove an item from an empty list.", "Check first:  if my_list:  my_list.pop()"],
      hi: ["लिस्ट खाली है", "pop( ) खाली लिस्ट से आइटम नहीं हटा सकता।", "पहले जाँचें:  if my_list:  my_list.pop()"],
      mr: ["लिस्ट रिकामी आहे", "pop( ) रिकाम्या लिस्टमधून आयटम काढू शकत नाही.", "आधी तपासा:  if my_list:  my_list.pop()"] }],
    ["IndexError", null, {
      en: ["Index out of range", "You asked for a position that does not exist.", "Indexes start at 0; the last item is at len( ) - 1."],
      hi: ["इंडेक्स सीमा से बाहर", "आपने ऐसा स्थान माँगा जो मौजूद नहीं है।", "इंडेक्स 0 से शुरू होता है; अंतिम आइटम len( ) - 1 पर है।"],
      mr: ["इंडेक्स मर्यादेबाहेर", "तुम्ही अस्तित्वात नसलेली जागा मागितली.", "इंडेक्स 0 पासून सुरू होतो; शेवटचा आयटम len( ) - 1 वर असतो."] }],
    ["KeyError", /(.+)/, {
      en: ["Key {1} is not in the dictionary", "The dictionary has no entry with the key {1} (spelling and capital letters must match).", "Check with  if key in d:  or use  d.get(key)  which gives None instead of an error."],
      hi: ["डिक्शनरी में की {1} नहीं है", "डिक्शनरी में {1} नाम की की नहीं है (स्पेलिंग और कैपिटल अक्षर मेल खाने चाहिए)।", "if key in d:  से जाँचें या  d.get(key)  लगाएँ जो त्रुटि की जगह None देता है।"],
      mr: ["डिक्शनरीमध्ये की {1} नाही", "डिक्शनरीमध्ये {1} नावाची की नाही (स्पेलिंग आणि कॅपिटल अक्षरे जुळायला हवीत).", "if key in d:  ने तपासा किंवा  d.get(key)  वापरा जे त्रुटीऐवजी None देते."] }],
    ["AttributeError", /'(\w+)' object has no attribute '(\w+)'\. Did you mean: '(\w+)'/, {
      en: ["Spelling mistake: .{2}", "A {1} has no '{2}'. You probably meant '{3}'.", "Change .{2} to .{3}"],
      hi: ["स्पेलिंग की गलती: .{2}", "{1} में '{2}' नहीं है। शायद आप '{3}' लिखना चाहते थे।", ".{2} को .{3} में बदलें"],
      mr: ["स्पेलिंगची चूक: .{2}", "{1} मध्ये '{2}' नाही. बहुतेक तुम्हाला '{3}' लिहायचे होते.", ".{2} बदलून .{3} करा"] }],
    ["AttributeError", /'(\w+)' object has no attribute '(\w+)'/, {
      en: ["'{2}' does not exist for {1}", "The method or property '{2}' does not exist for the type {1} (for example, append works on lists, not on strings).", "Check the spelling and the type of the variable. Type a dot after the variable to see the suggestions."],
      hi: ["{1} में '{2}' नहीं है", "{1} के लिए '{2}' मेथड या प्रॉपर्टी मौजूद नहीं है (जैसे append लिस्ट पर चलता है, स्ट्रिंग पर नहीं)।", "स्पेलिंग और वेरिएबल का प्रकार जाँचें। वेरिएबल के बाद डॉट लगाकर सुझाव देखें।"],
      mr: ["{1} मध्ये '{2}' नाही", "{1} साठी '{2}' मेथड किंवा प्रॉपर्टी अस्तित्वात नाही (उदा. append लिस्टवर चालते, स्ट्रिंगवर नाही).", "स्पेलिंग आणि व्हेरिएबलचा प्रकार तपासा. व्हेरिएबलनंतर डॉट लावून सूचना पहा."] }],
    ["AttributeError", /module '(\w+)' has no attribute '(\w+)'/, {
      en: ["Module {1} has no '{2}'", "There is no function or value named '{2}' in the {1} module.", "Check the spelling. Type  {1}.  to see everything available."],
      hi: ["मॉड्यूल {1} में '{2}' नहीं है", "{1} मॉड्यूल में '{2}' नाम का फंक्शन या मान नहीं है।", "स्पेलिंग जाँचें।  {1}.  टाइप करके सब उपलब्ध देखें।"],
      mr: ["मॉड्युल {1} मध्ये '{2}' नाही", "{1} मॉड्युलमध्ये '{2}' नावाचे फंक्शन किंवा मूल्य नाही.", "स्पेलिंग तपासा.  {1}.  टाइप करून सर्व उपलब्ध पहा."] }],
    ["ModuleNotFoundError", /No module named '([\w.]+)'/, {
      en: ["Module '{1}' not found", "Either the name is misspelt, or this module is not available in the browser (for example turtle, sqlite3, numpy).", "Check the spelling. Type  import  to see the modules that work here."],
      hi: ["मॉड्यूल '{1}' नहीं मिला", "या तो नाम की स्पेलिंग गलत है, या यह मॉड्यूल ब्राउज़र में उपलब्ध नहीं है (जैसे turtle, sqlite3, numpy)।", "स्पेलिंग जाँचें।  import  टाइप करके यहाँ चलने वाले मॉड्यूल देखें।"],
      mr: ["मॉड्युल '{1}' सापडले नाही", "एकतर नावाची स्पेलिंग चुकीची आहे, किंवा हे मॉड्युल ब्राउझरमध्ये उपलब्ध नाही (उदा. turtle, sqlite3, numpy).", "स्पेलिंग तपासा.  import  टाइप करून इथे चालणारी मॉड्युल्स पहा."] }],
    ["ImportError", /cannot import name '(\w+)' from '([\w.]+)'/, {
      en: ["'{1}' is not in {2}", "The module {2} does not have anything called '{1}'.", "Check the spelling, e.g.  from math import sqrt"],
      hi: ["{2} में '{1}' नहीं है", "मॉड्यूल {2} में '{1}' नाम की कोई चीज़ नहीं है।", "स्पेलिंग जाँचें, जैसे  from math import sqrt"],
      mr: ["{2} मध्ये '{1}' नाही", "मॉड्युल {2} मध्ये '{1}' नावाचे काही नाही.", "स्पेलिंग तपासा, उदा.  from math import sqrt"] }],
    ["FileNotFoundError", /'(.+)'/, {
      en: ["File {1} not found", "The program tried to open a file that does not exist here.", "Create the file first with  open(\"name.txt\", \"w\") , or check the file name."],
      hi: ["फ़ाइल {1} नहीं मिली", "प्रोग्राम ने ऐसी फ़ाइल खोलने की कोशिश की जो यहाँ मौजूद नहीं है।", "पहले  open(\"name.txt\", \"w\")  से फ़ाइल बनाएँ, या फ़ाइल नाम जाँचें।"],
      mr: ["फाइल {1} सापडली नाही", "प्रोग्रामने इथे नसलेली फाइल उघडण्याचा प्रयत्न केला.", "आधी  open(\"name.txt\", \"w\")  ने फाइल तयार करा, किंवा फाइलचे नाव तपासा."] }],
    ["RecursionError", null, {
      en: ["The function calls itself forever", "A recursive function has no stopping condition (base case), so it never ends.", "Add a stopping condition, e.g.  if n == 0: return 1"],
      hi: ["फंक्शन खुद को बार-बार बुला रहा है", "रिकर्सिव फंक्शन में रुकने की शर्त (base case) नहीं है, इसलिए वह कभी खत्म नहीं होता।", "रुकने की शर्त जोड़ें, जैसे  if n == 0: return 1"],
      mr: ["फंक्शन स्वतःला सतत बोलावत आहे", "रिकर्सिव्ह फंक्शनमध्ये थांबण्याची अट (base case) नाही, म्हणून ते कधीच संपत नाही.", "थांबण्याची अट जोडा, उदा.  if n == 0: return 1"] }],
    ["OverflowError", null, {
      en: ["Number is too big", "The result is too large to store (often with float or math.exp).", "Use smaller values, or use int for very big whole numbers."],
      hi: ["संख्या बहुत बड़ी है", "परिणाम संग्रहीत करने के लिए बहुत बड़ा है (अक्सर float या math.exp में)।", "छोटे मान लें, या बहुत बड़ी पूर्ण संख्या के लिए int लें।"],
      mr: ["संख्या खूप मोठी आहे", "निकाल साठवण्यासाठी खूप मोठा आहे (बहुतेक float किंवा math.exp मध्ये).", "लहान मूल्ये वापरा, किंवा खूप मोठ्या पूर्ण संख्यांसाठी int वापरा."] }],
    ["AssertionError", null, {
      en: ["assert condition is False", "The condition written after assert is not true.", "Check the values used in the assert line."],
      hi: ["assert की शर्त गलत है", "assert के बाद लिखी शर्त सत्य नहीं है।", "assert वाली पंक्ति के मान जाँचें।"],
      mr: ["assert ची अट खोटी आहे", "assert नंतर लिहिलेली अट खरी नाही.", "assert च्या ओळीतील मूल्ये तपासा."] }],
    ["EOFError", null, {
      en: ["No input given", "input( ) did not get any value.", "Type the values in the Input box (one per line) and run again."],
      hi: ["इनपुट नहीं दिया", "input( ) को कोई मान नहीं मिला।", "Input बॉक्स में मान टाइप करें (हर पंक्ति में एक) और फिर चलाएँ।"],
      mr: ["इनपुट दिले नाही", "input( ) ला कोणतेही मूल्य मिळाले नाही.", "Input बॉक्समध्ये मूल्ये टाइप करा (प्रत्येक ओळीत एक) आणि पुन्हा चालवा."] }],
    ["*", null, {
      en: ["{0}", "Python stopped because of this error.", "Read the line shown above and the message. Fix it and click Run again."],
      hi: ["{0}", "इस त्रुटि के कारण Python रुक गया।", "ऊपर दिखाई पंक्ति और संदेश पढ़ें। सुधारें और फिर से Run दबाएँ।"],
      mr: ["{0}", "या त्रुटीमुळे Python थांबले.", "वर दाखवलेली ओळ आणि संदेश वाचा. दुरुस्त करा आणि पुन्हा Run दाबा."] }]
  ];
  const LABEL = { en: ["Line", "What it means", "How to fix", "Your code"], hi: ["पंक्ति", "इसका मतलब", "कैसे सुधारें", "आपका कोड"], mr: ["ओळ", "याचा अर्थ", "कसे दुरुस्त करावे", "तुमचा कोड"] };

  function explain(type, message, lang) {
    lang = LABEL[lang] ? lang : "en";
    for (const r of R) {
      if (r[0] !== "*" && r[0] !== type) continue;
      let g = [];
      if (r[1]) { const m = r[1].exec(message || ""); if (!m) continue; g = m; }
      const t = r[2][lang] || r[2].en;
      const fill = (x) => x.replace(/\{(\d)\}/g, (all, i) => (i === "0" ? type : g[+i] != null ? g[+i] : g.slice(1).filter(Boolean)[+i - 1] || ""));
      return { title: fill(t[0]), what: fill(t[1]), fix: fill(t[2]), labels: LABEL[lang] };
    }
    return null;
  }
  window.COPA_PY_ERRORS = { explain, labels: LABEL };
})();
