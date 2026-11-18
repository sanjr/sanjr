"""Builds data/es-questions.js – built-in Employability Skills MCQs (EN / HI / MR).
Each item: topic, [question en, hi, mr], options (correct answer FIRST) as [en, hi, mr] triples, explanation [en, hi, mr].
The correct option is moved to a different position for every question."""
import json, pathlib

Q = [
 (1, ["Employability skills help a person to:", "रोज़गार योग्यता कौशल व्यक्ति की किसमें मदद करते हैं?", "रोजगारक्षमता कौशल्ये व्यक्तीला कशासाठी मदत करतात?"],
  [["Get, keep and grow in a job", "नौकरी पाने, बनाए रखने और आगे बढ़ने में", "नोकरी मिळवणे, टिकवणे आणि पुढे जाणे"], ["Only pass theory exams", "सिर्फ़ थ्योरी परीक्षा पास करने में", "फक्त थिअरी परीक्षा पास होणे"], ["Avoid teamwork", "टीमवर्क से बचने में", "टीमवर्क टाळणे"], ["Work without rules", "बिना नियम के काम करने में", "नियमांशिवाय काम करणे"]],
  ["They are needed to get a job, keep it and grow.", "ये नौकरी पाने, बनाए रखने और आगे बढ़ने के लिए ज़रूरी हैं।", "नोकरी मिळवणे, टिकवणे आणि पुढे जाणे यासाठी ती आवश्यक आहेत."]),
 (1, ["Which of these is a soft skill?", "इनमें से कौन-सी सॉफ्ट स्किल है?", "यापैकी कोणते सॉफ्ट स्किल आहे?"],
  [["Teamwork", "टीमवर्क", "टीमवर्क"], ["Welding", "वेल्डिंग", "वेल्डिंग"], ["Machine repair", "मशीन मरम्मत", "मशीन दुरुस्ती"], ["Wiring", "वायरिंग", "वायरिंग"]],
  ["Teamwork is a behaviour skill (soft skill); the others are technical skills.", "टीमवर्क व्यवहार कौशल (सॉफ्ट स्किल) है; बाकी तकनीकी कौशल हैं।", "टीमवर्क हे वर्तन कौशल्य (सॉफ्ट स्किल) आहे; बाकी तांत्रिक कौशल्ये आहेत."]),
 (1, ["The National Career Service portal of the Government of India is:", "भारत सरकार का राष्ट्रीय करियर सेवा पोर्टल है:", "भारत सरकारचे राष्ट्रीय करिअर सेवा पोर्टल आहे:"],
  [["www.ncs.gov.in", "www.ncs.gov.in", "www.ncs.gov.in"], ["www.irctc.co.in", "www.irctc.co.in", "www.irctc.co.in"], ["www.uidai.gov.in", "www.uidai.gov.in", "www.uidai.gov.in"], ["www.incometax.gov.in", "www.incometax.gov.in", "www.incometax.gov.in"]],
  ["ncs.gov.in lists jobs, career counselling and job fairs.", "ncs.gov.in पर नौकरियाँ, करियर परामर्श और रोज़गार मेले मिलते हैं।", "ncs.gov.in वर नोकऱ्या, करिअर समुपदेशन आणि रोजगार मेळावे मिळतात."]),
 (1, ["Skills that can be used in many different jobs are called:", "जो कौशल कई अलग-अलग नौकरियों में काम आते हैं, उन्हें कहते हैं:", "अनेक वेगवेगळ्या नोकऱ्यांमध्ये वापरता येणाऱ्या कौशल्यांना म्हणतात:"],
  [["Transferable skills", "ट्रांसफरेबल (हस्तांतरणीय) कौशल", "ट्रान्सफरेबल (हस्तांतरणीय) कौशल्ये"], ["Single skills", "एकल कौशल", "एकल कौशल्ये"], ["Hidden skills", "छिपे कौशल", "लपलेली कौशल्ये"], ["Machine skills", "मशीन कौशल", "मशीन कौशल्ये"]],
  ["Communication, teamwork and problem solving are transferable skills.", "संचार, टीमवर्क और समस्या समाधान ट्रांसफरेबल कौशल हैं।", "संवाद, टीमवर्क आणि समस्या सोडवणे ही ट्रान्सफरेबल कौशल्ये आहेत."]),

 (2, ["The Constitution of India came into force on:", "भारत का संविधान कब लागू हुआ?", "भारताचे संविधान कधी लागू झाले?"],
  [["26 January 1950", "26 जनवरी 1950", "26 जानेवारी 1950"], ["15 August 1947", "15 अगस्त 1947", "15 ऑगस्ट 1947"], ["26 November 1949", "26 नवंबर 1949", "26 नोव्हेंबर 1949"], ["2 October 1950", "2 अक्टूबर 1950", "2 ऑक्टोबर 1950"]],
  ["It was adopted on 26 Nov 1949 and came into force on 26 Jan 1950 (Republic Day).", "यह 26 नवंबर 1949 को अपनाया गया और 26 जनवरी 1950 (गणतंत्र दिवस) को लागू हुआ।", "ते 26 नोव्हेंबर 1949 रोजी स्वीकारले आणि 26 जानेवारी 1950 (प्रजासत्ताक दिन) रोजी लागू झाले."]),
 (2, ["Fundamental Duties of citizens are given in Article:", "नागरिकों के मौलिक कर्तव्य किस अनुच्छेद में हैं?", "नागरिकांची मूलभूत कर्तव्ये कोणत्या कलमात आहेत?"],
  [["51A", "51A", "51A"], ["14", "14", "14"], ["21", "21", "21"], ["370", "370", "370"]],
  ["Article 51A lists the Fundamental Duties.", "अनुच्छेद 51A में मौलिक कर्तव्य दिए गए हैं।", "कलम 51A मध्ये मूलभूत कर्तव्ये दिली आहेत."]),
 (2, ["Which word is NOT in the Preamble of the Indian Constitution?", "भारतीय संविधान की प्रस्तावना में कौन-सा शब्द नहीं है?", "भारतीय संविधानाच्या प्रस्तावनेत कोणता शब्द नाही?"],
  [["Monarchy", "राजतंत्र (Monarchy)", "राजेशाही (Monarchy)"], ["Sovereign", "संप्रभु", "सार्वभौम"], ["Secular", "पंथनिरपेक्ष", "धर्मनिरपेक्ष"], ["Republic", "गणराज्य", "प्रजासत्ताक"]],
  ["India is a Republic, not a monarchy.", "भारत गणराज्य है, राजतंत्र नहीं।", "भारत प्रजासत्ताक आहे, राजेशाही नाही."]),
 (2, ["Respecting the National Flag and National Anthem is a:", "राष्ट्रीय ध्वज और राष्ट्रगान का सम्मान करना है:", "राष्ट्रध्वज आणि राष्ट्रगीताचा आदर करणे हे आहे:"],
  [["Fundamental Duty", "मौलिक कर्तव्य", "मूलभूत कर्तव्य"], ["Company rule", "कंपनी का नियम", "कंपनीचा नियम"], ["Hobby", "शौक", "छंद"], ["Business plan", "व्यवसाय योजना", "व्यवसाय आराखडा"]],
  ["It is the first Fundamental Duty under Article 51A.", "यह अनुच्छेद 51A का पहला मौलिक कर्तव्य है।", "हे कलम 51A मधील पहिले मूलभूत कर्तव्य आहे."]),

 (3, ["Which is a good professional habit at the workplace?", "कार्यस्थल पर कौन-सी अच्छी पेशेवर आदत है?", "कामाच्या ठिकाणी कोणती चांगली व्यावसायिक सवय आहे?"],
  [["Being punctual", "समय का पाबंद रहना", "वक्तशीर असणे"], ["Coming late daily", "रोज़ देर से आना", "रोज उशिरा येणे"], ["Using the phone during work", "काम के दौरान फ़ोन चलाना", "कामाच्या वेळी फोन वापरणे"], ["Ignoring safety rules", "सुरक्षा नियमों की अनदेखी", "सुरक्षा नियमांकडे दुर्लक्ष"]],
  ["Punctuality shows discipline and respect for work.", "समय की पाबंदी अनुशासन और काम के प्रति सम्मान दिखाती है।", "वक्तशीरपणा शिस्त आणि कामाबद्दल आदर दाखवतो."]),
 (3, ["Doing the right thing even when no one is watching is called:", "कोई न देखे तब भी सही काम करना कहलाता है:", "कोणी पाहत नसतानाही योग्य काम करणे याला म्हणतात:"],
  [["Integrity", "ईमानदारी (इंटेग्रिटी)", "प्रामाणिकपणा (इंटेग्रिटी)"], ["Laziness", "आलस्य", "आळस"], ["Gossip", "गपशप", "गप्पा-टप्पा"], ["Pride", "घमंड", "गर्व"]],
  ["Integrity = honesty and strong moral values.", "इंटेग्रिटी = ईमानदारी और मज़बूत नैतिक मूल्य।", "इंटेग्रिटी = प्रामाणिकपणा आणि मजबूत नैतिक मूल्ये."]),
 (3, ["Learning new skills throughout life is called:", "जीवन भर नए कौशल सीखते रहना कहलाता है:", "आयुष्यभर नवीन कौशल्ये शिकत राहणे याला म्हणतात:"],
  [["Lifelong learning", "आजीवन सीखना", "आयुष्यभर शिक्षण"], ["Rote learning", "रटना", "घोकंपट्टी"], ["Short learning", "थोड़ा सीखना", "थोडे शिकणे"], ["Late learning", "देर से सीखना", "उशिरा शिकणे"]],
  ["Technology changes fast, so we must keep learning.", "तकनीक तेज़ी से बदलती है, इसलिए सीखते रहना ज़रूरी है।", "तंत्रज्ञान वेगाने बदलते, म्हणून शिकत राहणे आवश्यक आहे."]),
 (3, ["Which skill helps you finish work on time?", "कौन-सा कौशल काम समय पर पूरा करने में मदद करता है?", "कोणते कौशल्य काम वेळेवर पूर्ण करण्यास मदत करते?"],
  [["Time management", "समय प्रबंधन", "वेळेचे नियोजन"], ["Day-dreaming", "दिवास्वप्न", "दिवास्वप्न"], ["Delay", "देरी", "दिरंगाई"], ["Over-sleeping", "ज़्यादा सोना", "जास्त झोपणे"]],
  ["Planning and prioritising tasks is time management.", "कामों की योजना और प्राथमिकता तय करना समय प्रबंधन है।", "कामांचे नियोजन आणि प्राधान्यक्रम ठरवणे म्हणजे वेळेचे नियोजन."]),

 (4, ["Choose the correct sentence:", "सही वाक्य चुनें:", "बरोबर वाक्य निवडा:"],
  [["She goes to ITI daily.", "She goes to ITI daily.", "She goes to ITI daily."], ["She go to ITI daily.", "She go to ITI daily.", "She go to ITI daily."], ["She going to ITI daily.", "She going to ITI daily.", "She going to ITI daily."], ["She gone to ITI daily.", "She gone to ITI daily.", "She gone to ITI daily."]],
  ["With she / he / it the verb takes -s / -es: goes.", "she / he / it के साथ क्रिया में -s / -es लगता है: goes।", "she / he / it सोबत क्रियापदाला -s / -es लागते: goes."]),
 (4, ["Which is the polite way to ask?", "पूछने का विनम्र तरीका कौन-सा है?", "विचारण्याची नम्र पद्धत कोणती?"],
  [["Could you please give me the file?", "Could you please give me the file?", "Could you please give me the file?"], ["Give me the file.", "Give me the file.", "Give me the file."], ["File, now!", "File, now!", "File, now!"], ["I want file.", "I want file.", "I want file."]],
  ["'Could you please…' is polite and correct.", "'Could you please…' विनम्र और सही है।", "'Could you please…' नम्र आणि बरोबर आहे."]),
 (4, ["The plural of 'machine' is:", "'machine' का बहुवचन है:", "'machine' चे अनेकवचन आहे:"],
  [["Machines", "Machines", "Machines"], ["Machine", "Machine", "Machine"], ["Machinees", "Machinees", "Machinees"], ["Machinies", "Machinies", "Machinies"]],
  ["Most nouns take -s in the plural: machines.", "अधिकतर संज्ञाओं में बहुवचन के लिए -s लगता है: machines।", "बहुतेक नामांना अनेकवचनात -s लागते: machines."]),
 (4, ["In 'I am learning English', the tense is:", "'I am learning English' में कौन-सा काल है?", "'I am learning English' मध्ये कोणता काळ आहे?"],
  [["Present continuous", "Present continuous", "Present continuous"], ["Past simple", "Past simple", "Past simple"], ["Future simple", "Future simple", "Future simple"], ["Past perfect", "Past perfect", "Past perfect"]],
  ["am / is / are + verb-ing = present continuous.", "am / is / are + verb-ing = present continuous।", "am / is / are + verb-ing = present continuous."]),

 (5, ["In a SMART goal, 'M' stands for:", "SMART लक्ष्य में 'M' का अर्थ है:", "SMART ध्येयात 'M' म्हणजे:"],
  [["Measurable", "Measurable (मापने योग्य)", "Measurable (मोजता येण्याजोगे)"], ["Money", "Money", "Money"], ["Modern", "Modern", "Modern"], ["Manual", "Manual", "Manual"]],
  ["SMART = Specific, Measurable, Achievable, Relevant, Time-bound.", "SMART = Specific, Measurable, Achievable, Relevant, Time-bound।", "SMART = Specific, Measurable, Achievable, Relevant, Time-bound."]),
 (5, ["A goal to be achieved within about one year is a:", "लगभग एक वर्ष के अंदर पूरा होने वाला लक्ष्य है:", "साधारण एका वर्षात पूर्ण होणारे ध्येय म्हणजे:"],
  [["Short-term goal", "अल्पकालिक लक्ष्य", "अल्पकालीन ध्येय"], ["Life goal", "जीवन लक्ष्य", "आयुष्याचे ध्येय"], ["Dream", "सपना", "स्वप्न"], ["Hobby", "शौक", "छंद"]],
  ["Short-term goals are steps towards long-term goals.", "अल्पकालिक लक्ष्य दीर्घकालिक लक्ष्यों की सीढ़ियाँ हैं।", "अल्पकालीन ध्येये ही दीर्घकालीन ध्येयांच्या पायऱ्या आहेत."]),
 (5, ["SWOT analysis checks Strengths, Weaknesses, Opportunities and:", "SWOT विश्लेषण में Strengths, Weaknesses, Opportunities और कौन-सा शब्द है?", "SWOT विश्लेषणात Strengths, Weaknesses, Opportunities आणि कोणता शब्द आहे?"],
  [["Threats", "Threats (खतरे)", "Threats (धोके)"], ["Targets", "Targets", "Targets"], ["Teams", "Teams", "Teams"], ["Tools", "Tools", "Tools"]],
  ["T = Threats – outside problems that can stop you.", "T = Threats – बाहरी समस्याएँ जो आपको रोक सकती हैं।", "T = Threats – तुम्हाला अडवू शकणाऱ्या बाहेरच्या अडचणी."]),
 (5, ["The first step of career planning is:", "करियर योजना का पहला कदम है:", "करिअर नियोजनाची पहिली पायरी आहे:"],
  [["Knowing yourself (self-assessment)", "खुद को जानना (आत्म-मूल्यांकन)", "स्वतःला ओळखणे (स्व-मूल्यमापन)"], ["Buying a vehicle", "वाहन खरीदना", "वाहन खरेदी करणे"], ["Leaving studies", "पढ़ाई छोड़ना", "शिक्षण सोडणे"], ["Copying a friend", "दोस्त की नकल करना", "मित्राची नक्कल करणे"]],
  ["Know your interests, skills and values first.", "पहले अपनी रुचि, कौशल और मूल्य जानें।", "आधी तुमची आवड, कौशल्ये आणि मूल्ये ओळखा."]),

 (6, ["Communication through body movements and facial expressions is:", "शरीर की हरकतों और चेहरे के भावों से होने वाला संचार है:", "शरीराच्या हालचाली आणि चेहऱ्यावरील हावभावांनी होणारा संवाद म्हणजे:"],
  [["Non-verbal communication", "अमौखिक संचार", "अशाब्दिक संवाद"], ["Written communication", "लिखित संचार", "लिखित संवाद"], ["E-mail", "ई-मेल", "ई-मेल"], ["Radio", "रेडियो", "रेडिओ"]],
  ["Body language is non-verbal communication.", "शारीरिक भाषा अमौखिक संचार है।", "देहबोली म्हणजे अशाब्दिक संवाद."]),
 (6, ["The person who receives a message is the:", "संदेश प्राप्त करने वाला व्यक्ति होता है:", "संदेश मिळवणारी व्यक्ती म्हणजे:"],
  [["Receiver", "प्राप्तकर्ता (Receiver)", "प्राप्तकर्ता (Receiver)"], ["Sender", "प्रेषक (Sender)", "प्रेषक (Sender)"], ["Channel", "माध्यम (Channel)", "माध्यम (Channel)"], ["Noise", "शोर (Noise)", "आवाज (Noise)"]],
  ["Sender → message → channel → receiver → feedback.", "प्रेषक → संदेश → माध्यम → प्राप्तकर्ता → प्रतिक्रिया।", "प्रेषक → संदेश → माध्यम → प्राप्तकर्ता → प्रतिसाद."]),
 (6, ["Which is a sign of active listening?", "सक्रिय रूप से सुनने का संकेत कौन-सा है?", "सक्रिय ऐकण्याचे लक्षण कोणते?"],
  [["Nodding and asking questions", "सिर हिलाना और प्रश्न पूछना", "मान हलवणे आणि प्रश्न विचारणे"], ["Interrupting often", "बार-बार टोकना", "सतत मध्येच बोलणे"], ["Looking at the phone", "फ़ोन देखते रहना", "फोनकडे पाहत राहणे"], ["Leaving the room", "कमरे से चले जाना", "खोलीतून निघून जाणे"]],
  ["Active listeners show interest and check understanding.", "सक्रिय श्रोता रुचि दिखाते हैं और समझ की पुष्टि करते हैं।", "सक्रिय श्रोते रस दाखवतात आणि समजले का ते तपासतात."]),
 (6, ["The response from the receiver to the sender is called:", "प्राप्तकर्ता द्वारा प्रेषक को दिया गया उत्तर कहलाता है:", "प्राप्तकर्त्याने प्रेषकाला दिलेल्या प्रतिसादाला म्हणतात:"],
  [["Feedback", "प्रतिक्रिया (Feedback)", "प्रतिसाद (Feedback)"], ["Noise", "शोर", "आवाज"], ["Encoding", "एन्कोडिंग", "एन्कोडिंग"], ["Channel", "माध्यम", "माध्यम"]],
  ["Feedback completes the communication cycle.", "प्रतिक्रिया संचार चक्र को पूरा करती है।", "प्रतिसादामुळे संवादाचे चक्र पूर्ण होते."]),

 (7, ["Treating everyone fairly regardless of gender, caste or religion is:", "लिंग, जाति या धर्म देखे बिना सबके साथ उचित व्यवहार करना है:", "लिंग, जात किंवा धर्म न पाहता सर्वांशी योग्य वागणे म्हणजे:"],
  [["Equality and inclusion", "समानता और समावेशन", "समानता आणि समावेशन"], ["Discrimination", "भेदभाव", "भेदभाव"], ["Bullying", "धमकाना", "दादागिरी"], ["Favouritism", "पक्षपात", "पक्षपात"]],
  ["Inclusion means equal respect for all.", "समावेशन का अर्थ है सभी का समान सम्मान।", "समावेशन म्हणजे सर्वांचा समान आदर."]),
 (7, ["The Rights of Persons with Disabilities Act was passed in:", "दिव्यांगजन अधिकार अधिनियम किस वर्ष पारित हुआ?", "दिव्यांग व्यक्ती हक्क अधिनियम कोणत्या वर्षी मंजूर झाला?"],
  [["2016", "2016", "2016"], ["1950", "1950", "1950"], ["1990", "1990", "1990"], ["2022", "2022", "2022"]],
  ["The RPwD Act, 2016 recognises 21 types of disabilities.", "RPwD अधिनियम, 2016 में 21 प्रकार की दिव्यांगताएँ मान्य हैं।", "RPwD अधिनियम, 2016 मध्ये 21 प्रकारची दिव्यांगत्वे मान्य आहेत."]),
 (7, ["The POSH Act, 2013 protects against:", "POSH अधिनियम, 2013 किससे सुरक्षा देता है?", "POSH अधिनियम, 2013 कशापासून संरक्षण देतो?"],
  [["Sexual harassment of women at the workplace", "कार्यस्थल पर महिलाओं का यौन उत्पीड़न", "कामाच्या ठिकाणी महिलांचा लैंगिक छळ"], ["Tax evasion", "कर चोरी", "करचुकवेगिरी"], ["Road accidents", "सड़क दुर्घटनाएँ", "रस्ते अपघात"], ["Price rise", "महँगाई", "महागाई"]],
  ["POSH = Prevention of Sexual Harassment of women at the workplace.", "POSH = कार्यस्थल पर महिलाओं के यौन उत्पीड़न की रोकथाम।", "POSH = कामाच्या ठिकाणी महिलांच्या लैंगिक छळाला प्रतिबंध."]),
 (7, ["A workplace with people of different backgrounds is called:", "अलग-अलग पृष्ठभूमि के लोगों वाला कार्यस्थल कहलाता है:", "वेगवेगळ्या पार्श्वभूमीचे लोक असलेले कामाचे ठिकाण म्हणजे:"],
  [["Diverse", "विविधतापूर्ण", "वैविध्यपूर्ण"], ["Closed", "बंद", "बंद"], ["Private", "निजी", "खाजगी"], ["Single", "एकल", "एकल"]],
  ["Diversity brings different ideas and talents together.", "विविधता अलग-अलग विचार और प्रतिभाएँ साथ लाती है।", "विविधतेमुळे वेगवेगळ्या कल्पना आणि कौशल्ये एकत्र येतात."]),

 (8, ["A PAN number has how many characters?", "PAN नंबर में कितने अक्षर होते हैं?", "PAN नंबरमध्ये किती अक्षरे असतात?"],
  [["10", "10", "10"], ["8", "8", "8"], ["12", "12", "12"], ["16", "16", "16"]],
  ["PAN has 10 characters (letters and digits); Aadhaar has 12 digits.", "PAN में 10 अक्षर (अक्षर और अंक) होते हैं; आधार में 12 अंक।", "PAN मध्ये 10 अक्षरे (अक्षरे आणि अंक) असतात; आधारमध्ये 12 अंक."]),
 (8, ["Never share your bank ____ with anyone.", "अपना बैंक ____ कभी किसी को न बताएँ।", "तुमचा बँक ____ कधीही कोणालाही सांगू नका."],
  [["OTP / PIN", "OTP / PIN", "OTP / PIN"], ["Branch name", "शाखा का नाम", "शाखेचे नाव"], ["IFSC code", "IFSC कोड", "IFSC कोड"], ["Holiday list", "छुट्टियों की सूची", "सुट्ट्यांची यादी"]],
  ["Banks never ask for your OTP, PIN or password.", "बैंक कभी आपका OTP, PIN या पासवर्ड नहीं माँगता।", "बँक कधीही तुमचा OTP, PIN किंवा पासवर्ड मागत नाही."]),
 (8, ["UPI stands for:", "UPI का पूरा नाम है:", "UPI चे पूर्ण रूप आहे:"],
  [["Unified Payments Interface", "Unified Payments Interface", "Unified Payments Interface"], ["Universal Payment India", "Universal Payment India", "Universal Payment India"], ["Union Payment Interface", "Union Payment Interface", "Union Payment Interface"], ["United Pay Internet", "United Pay Internet", "United Pay Internet"]],
  ["UPI was developed by NPCI for instant mobile payments.", "UPI को NPCI ने तुरंत मोबाइल भुगतान के लिए बनाया।", "UPI हे NPCI ने तात्काळ मोबाईल पेमेंटसाठी तयार केले."]),
 (8, ["EPF is a savings scheme mainly for:", "EPF मुख्य रूप से किसके लिए बचत योजना है?", "EPF ही मुख्यतः कशासाठी बचत योजना आहे?"],
  [["Employees' retirement", "कर्मचारियों की सेवानिवृत्ति", "कर्मचाऱ्यांची निवृत्ती"], ["Buying a mobile", "मोबाइल खरीदना", "मोबाईल खरेदी"], ["Travel", "यात्रा", "प्रवास"], ["Gaming", "गेमिंग", "गेमिंग"]],
  ["Employee and employer both contribute to EPF every month.", "EPF में कर्मचारी और नियोक्ता दोनों हर महीने योगदान देते हैं।", "EPF मध्ये कर्मचारी आणि मालक दोघे दरमहा योगदान देतात."]),

 (9, ["Which is the strongest password?", "सबसे मज़बूत पासवर्ड कौन-सा है?", "सर्वात मजबूत पासवर्ड कोणता?"],
  [["Copa@2026#Ti", "Copa@2026#Ti", "Copa@2026#Ti"], ["123456", "123456", "123456"], ["password", "password", "password"], ["myname", "myname", "myname"]],
  ["It mixes capital and small letters, digits and symbols.", "इसमें बड़े-छोटे अक्षर, अंक और चिह्न मिले हैं।", "यात कॅपिटल-लहान अक्षरे, अंक आणि चिन्हे आहेत."]),
 (9, ["Fake e-mails that try to steal passwords are called:", "पासवर्ड चुराने वाले नकली ई-मेल कहलाते हैं:", "पासवर्ड चोरण्याचा प्रयत्न करणाऱ्या बनावट ई-मेलना म्हणतात:"],
  [["Phishing", "फ़िशिंग", "फिशिंग"], ["Booting", "बूटिंग", "बूटिंग"], ["Formatting", "फ़ॉर्मेटिंग", "फॉरमॅटिंग"], ["Spooling", "स्पूलिंग", "स्पूलिंग"]],
  ["Do not click unknown links or share details from such mails.", "ऐसे मेल के अनजान लिंक पर क्लिक न करें, जानकारी न दें।", "अशा मेलमधील अनोळखी लिंकवर क्लिक करू नका, माहिती देऊ नका."]),
 (9, ["DigiLocker is used to:", "डिजिलॉकर का उपयोग किसलिए होता है?", "डिजिलॉकर कशासाठी वापरतात?"],
  [["Store digital documents safely", "डिजिटल दस्तावेज़ सुरक्षित रखने के लिए", "डिजिटल कागदपत्रे सुरक्षित ठेवण्यासाठी"], ["Play games", "गेम खेलने के लिए", "गेम खेळण्यासाठी"], ["Edit photos", "फोटो एडिट करने के लिए", "फोटो एडिट करण्यासाठी"], ["Book movie tickets", "फ़िल्म टिकट बुक करने के लिए", "चित्रपट तिकीट बुक करण्यासाठी"]],
  ["Certificates in DigiLocker are valid like original documents.", "डिजिलॉकर के प्रमाणपत्र मूल दस्तावेज़ जैसे मान्य हैं।", "डिजिलॉकरमधील प्रमाणपत्रे मूळ कागदपत्रांसारखी वैध असतात."]),
 (9, ["Which is a safe habit on public Wi-Fi?", "सार्वजनिक Wi-Fi पर कौन-सी आदत सुरक्षित है?", "सार्वजनिक Wi-Fi वर कोणती सवय सुरक्षित आहे?"],
  [["Avoid online banking", "ऑनलाइन बैंकिंग न करें", "ऑनलाइन बँकिंग टाळा"], ["Share your OTP", "OTP साझा करें", "OTP शेअर करा"], ["Turn off antivirus", "एंटीवायरस बंद करें", "अँटीव्हायरस बंद करा"], ["Save passwords on shared PCs", "साझा PC पर पासवर्ड सेव करें", "शेअर केलेल्या PC वर पासवर्ड सेव्ह करा"]],
  ["Public networks are not secure for money transactions.", "सार्वजनिक नेटवर्क पैसों के लेन-देन के लिए सुरक्षित नहीं हैं।", "सार्वजनिक नेटवर्क पैशांच्या व्यवहारासाठी सुरक्षित नसतात."]),

 (10, ["A person who starts and runs his or her own business is called:", "जो व्यक्ति अपना व्यवसाय शुरू करके चलाता है, उसे कहते हैं:", "स्वतःचा व्यवसाय सुरू करून चालवणाऱ्या व्यक्तीला म्हणतात:"],
  [["Entrepreneur", "उद्यमी", "उद्योजक"], ["Employee", "कर्मचारी", "कर्मचारी"], ["Customer", "ग्राहक", "ग्राहक"], ["Supplier", "आपूर्तिकर्ता", "पुरवठादार"]],
  ["An entrepreneur takes the risk of running a business.", "उद्यमी व्यवसाय चलाने का जोखिम उठाता है।", "उद्योजक व्यवसाय चालवण्याची जोखीम घेतो."]),
 (10, ["PM MUDRA Yojana gives:", "PM मुद्रा योजना क्या देती है?", "PM मुद्रा योजना काय देते?"],
  [["Loans to small businesses", "छोटे व्यवसायों को ऋण", "छोट्या व्यवसायांना कर्ज"], ["Free mobiles", "मुफ़्त मोबाइल", "मोफत मोबाईल"], ["Railway passes", "रेलवे पास", "रेल्वे पास"], ["Foreign scholarships", "विदेशी छात्रवृत्ति", "परदेशी शिष्यवृत्ती"]],
  ["MUDRA loans: Shishu, Kishore and Tarun categories.", "मुद्रा ऋण: शिशु, किशोर और तरुण श्रेणियाँ।", "मुद्रा कर्ज: शिशु, किशोर आणि तरुण श्रेणी."]),
 (10, ["A written document with the goals, costs and profit plan of a business is a:", "व्यवसाय के लक्ष्य, लागत और लाभ योजना वाला लिखित दस्तावेज़ है:", "व्यवसायाची ध्येये, खर्च आणि नफ्याचे नियोजन असलेला लिखित दस्तऐवज म्हणजे:"],
  [["Business plan", "व्यवसाय योजना", "व्यवसाय आराखडा"], ["Resume", "बायोडाटा", "रिझ्युमे"], ["Bill", "बिल", "बिल"], ["Receipt", "रसीद", "पावती"]],
  ["Banks ask for a business plan before giving a loan.", "बैंक ऋण देने से पहले व्यवसाय योजना माँगते हैं।", "बँका कर्ज देण्यापूर्वी व्यवसाय आराखडा मागतात."]),
 (10, ["Money invested to start a business is called:", "व्यवसाय शुरू करने के लिए लगाया गया धन कहलाता है:", "व्यवसाय सुरू करण्यासाठी गुंतवलेल्या पैशाला म्हणतात:"],
  [["Capital", "पूँजी", "भांडवल"], ["Salary", "वेतन", "पगार"], ["Tax", "कर", "कर"], ["Fine", "जुर्माना", "दंड"]],
  ["Capital buys machines, raw material and space.", "पूँजी से मशीन, कच्चा माल और जगह ली जाती है।", "भांडवलातून मशीन, कच्चा माल आणि जागा घेतली जाते."]),

 (11, ["Who is the most important person for any business?", "किसी भी व्यवसाय के लिए सबसे महत्वपूर्ण व्यक्ति कौन है?", "कोणत्याही व्यवसायासाठी सर्वात महत्त्वाची व्यक्ती कोण?"],
  [["Customer", "ग्राहक", "ग्राहक"], ["Competitor", "प्रतियोगी", "स्पर्धक"], ["Neighbour", "पड़ोसी", "शेजारी"], ["Machine", "मशीन", "मशीन"]],
  ["Customers bring income to the business.", "ग्राहक व्यवसाय को आय देते हैं।", "ग्राहकांमुळे व्यवसायाला उत्पन्न मिळते."]),
 (11, ["When a customer complains, you should first:", "ग्राहक शिकायत करे तो सबसे पहले आपको:", "ग्राहकाने तक्रार केली तर तुम्ही सर्वात आधी:"],
  [["Listen patiently", "धैर्य से सुनना चाहिए", "शांतपणे ऐकावे"], ["Argue", "बहस करनी चाहिए", "वाद घालावा"], ["Ignore", "अनदेखा करना चाहिए", "दुर्लक्ष करावे"], ["Shout", "चिल्लाना चाहिए", "ओरडावे"]],
  ["Listen, apologise, solve and follow up.", "सुनें, खेद जताएँ, हल करें और फ़ॉलो-अप करें।", "ऐका, दिलगिरी व्यक्त करा, सोडवा आणि पाठपुरावा करा."]),
 (11, ["Customers come back again mainly because of:", "ग्राहक बार-बार मुख्य रूप से क्यों आते हैं?", "ग्राहक पुन्हा पुन्हा मुख्यतः का येतात?"],
  [["Good service", "अच्छी सेवा", "चांगली सेवा"], ["Rude behaviour", "रूखा व्यवहार", "उद्धट वागणूक"], ["Late delivery", "देर से डिलीवरी", "उशिरा डिलिव्हरी"], ["Hidden charges", "छिपे शुल्क", "लपवलेले शुल्क"]],
  ["Good service builds trust and loyalty.", "अच्छी सेवा भरोसा और वफ़ादारी बनाती है।", "चांगल्या सेवेमुळे विश्वास आणि निष्ठा निर्माण होते."]),
 (11, ["Saying 'Thank you for your patience' to a customer shows:", "ग्राहक से 'आपके धैर्य के लिए धन्यवाद' कहना दिखाता है:", "ग्राहकाला 'तुमच्या संयमाबद्दल धन्यवाद' म्हणणे दाखवते:"],
  [["Courtesy", "शिष्टाचार", "सौजन्य"], ["Anger", "गुस्सा", "राग"], ["Carelessness", "लापरवाही", "निष्काळजीपणा"], ["Pride", "घमंड", "गर्व"]],
  ["Polite words make customers feel respected.", "विनम्र शब्द ग्राहकों को सम्मान का अनुभव कराते हैं।", "नम्र शब्दांमुळे ग्राहकांना आदर वाटतो."]),

 (12, ["A short document of your education, skills and experience is a:", "आपकी शिक्षा, कौशल और अनुभव का छोटा दस्तावेज़ है:", "तुमचे शिक्षण, कौशल्ये आणि अनुभव यांचा छोटा दस्तऐवज म्हणजे:"],
  [["Resume / CV", "बायोडाटा / CV", "रिझ्युमे / CV"], ["Bill", "बिल", "बिल"], ["Ticket", "टिकट", "तिकीट"], ["Notice", "सूचना", "सूचना"]],
  ["Keep your resume short, true and up to date.", "बायोडाटा छोटा, सच्चा और अद्यतन रखें।", "रिझ्युमे छोटा, खरा आणि अद्ययावत ठेवा."]),
 (12, ["The official portal to apply for apprenticeship in India is:", "भारत में अप्रेंटिसशिप के लिए आवेदन का आधिकारिक पोर्टल है:", "भारतात अप्रेंटिसशिपसाठी अर्ज करण्याचे अधिकृत पोर्टल आहे:"],
  [["apprenticeshipindia.gov.in", "apprenticeshipindia.gov.in", "apprenticeshipindia.gov.in"], ["irctc.co.in", "irctc.co.in", "irctc.co.in"], ["amazon.in", "amazon.in", "amazon.in"], ["youtube.com", "youtube.com", "youtube.com"]],
  ["Register, search openings and apply on the apprenticeship portal.", "अप्रेंटिसशिप पोर्टल पर पंजीकरण करें, अवसर खोजें और आवेदन करें।", "अप्रेंटिसशिप पोर्टलवर नोंदणी करा, संधी शोधा आणि अर्ज करा."]),
 (12, ["Which is good behaviour in an interview?", "साक्षात्कार में कौन-सा व्यवहार अच्छा है?", "मुलाखतीत कोणते वर्तन चांगले आहे?"],
  [["Reach on time with documents", "दस्तावेज़ों के साथ समय पर पहुँचना", "कागदपत्रांसह वेळेवर पोहोचणे"], ["Chew gum", "च्युइंग गम चबाना", "च्युइंगम चघळणे"], ["Argue with the panel", "पैनल से बहस करना", "पॅनेलशी वाद घालणे"], ["Keep the phone ringing", "फ़ोन बजने देना", "फोन वाजत ठेवणे"]],
  ["Punctuality and preparation create a good first impression.", "समय पर पहुँचना और तैयारी अच्छा पहला प्रभाव बनाते हैं।", "वक्तशीरपणा आणि तयारीमुळे चांगली पहिली छाप पडते."]),
 (12, ["NAPS stands for National ____ Promotion Scheme.", "NAPS का पूरा नाम National ____ Promotion Scheme है।", "NAPS म्हणजे National ____ Promotion Scheme."],
  [["Apprenticeship", "Apprenticeship", "Apprenticeship"], ["Agriculture", "Agriculture", "Agriculture"], ["Aviation", "Aviation", "Aviation"], ["Army", "Army", "Army"]],
  ["NAPS gives stipend support to apprentices through employers.", "NAPS नियोक्ताओं के माध्यम से अप्रेंटिस को स्टाइपेंड सहायता देता है।", "NAPS मालकांमार्फत अप्रेंटिसना विद्यावेतन मदत देते."]),
]

out, count = [], {}
for i, (topic, q, opts, x) in enumerate(Q):
    count[topic] = count.get(topic, 0) + 1
    a = (i * 3 + topic) % 4                    # spread the correct answer over A–D
    order = opts[1:]
    order.insert(a, opts[0])
    item = {"id": "es-%d-%d" % (topic, count[topic]), "m": 100 + topic, "a": a}
    for li, L in enumerate(["en", "hi", "mr"]):
        item[L] = {"q": q[li], "o": [o[li] for o in order]}
    item["x"] = {"en": x[0], "hi": x[1], "mr": x[2]}
    out.append(item)

root = pathlib.Path(__file__).resolve().parents[2]
(root / "data" / "es-questions.js").write_text(
    "/* COPA MCQ – Employability Skills: built-in MCQs (EN / HI / MR). m = 100 + topic id. Built by tools/es/build_es_questions.py */\n"
    "window.COPA_ES_QUESTIONS = " + json.dumps(out, ensure_ascii=False) + ";\n", encoding="utf-8")
print(len(out), "questions", {k: v for k, v in count.items()}, "answers", [sum(1 for o in out if o["a"] == k) for k in range(4)])
