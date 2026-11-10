# ITI CareerTrack — Installation / स्थापना

## मराठीत (XAMPP, Windows)
1. **XAMPP** install करा (PHP 8.1 किंवा नवीन). Database साठी **MySQL 8.0.16+** वापरा.
2. ही ZIP extract करा आणि `ITI-CareerTrack` फोल्डर `C:\xampp\htdocs\` मध्ये ठेवा.
3. XAMPP Control Panel मधून **Apache** आणि **MySQL** Start करा.
4. Browser मध्ये उघडा: **http://localhost/ITI-CareerTrack/**
5. Installer page उघडेल. MySQL ची माहिती भरा (XAMPP मध्ये साधारणपणे: host `127.0.0.1`, port `3306`, user `root`, password रिकामा).
6. निवडा:
   - **Demo system** — सराव / सादरीकरणासाठी नमुना डेटा. Login: `DVET-ADMIN` / `Demo@1234` (login page वर कोणताही role click केला की demo account भरले जाते).
   - **Empty system** — खऱ्या वापरासाठी. फक्त State Admin चा login (तुम्ही निवडलेला User ID व password), 36 जिल्हे व 6 विभागीय कार्यालये तयार असतात.
7. **Install** दाबा. एक मिनिटात website तयार. Installer त्यानंतर आपोआप बंद होतो.

### Empty system नंतर State Admin ने करायचे
1. **Master Data** → Trades (modules सह) व Academic Year.
2. **ITIs Management** → प्रत्येक ITI त्याच्या **NCVT MIS Code** सह.
3. **User & Role Mgmt** → *Create ITI logins* (User ID = NCVT MIS Code, password `Test@12345`), तसेच RO Admin, DVTO, Apprentice Advisor, Instructor, Placement Officer, Employer चे users.
4. प्रत्येक user पहिल्या login ला आपला password बदलतो.

## मराठीत — Online hosting (cPanel / Hostinger / इतर)
Hosting वर `root` user आणि रिकामा password **चालत नाही** (म्हणून `Access denied for user 'root'` error येतो). आधी cPanel मध्ये database बनवा:
1. cPanel → **MySQL® Databases** (किंवा *MySQL Database Wizard*).
2. **Create New Database**: उदा. `careertrack` → cPanel त्याचे पूर्ण नाव दाखवते, उदा. `cpuser_careertrack`.
3. **Add New User**: उदा. `ctuser` + मजबूत password → पूर्ण नाव उदा. `cpuser_ctuser`.
4. **Add User To Database**: तो user त्या database ला जोडा → **ALL PRIVILEGES** ✔ → Make Changes.
5. आता installer मध्ये भरा: Host **`localhost`**, Port `3306`, Database name = `cpuser_careertrack`, MySQL user = `cpuser_ctuser`, password = तुम्ही ठेवलेला.
6. Demo किंवा Empty निवडा → **Install**.
   - Hosting MySQL triggers ला परवानगी देत नसेल (SUPER privilege नाही), तर installer तसे सांगतो आणि तेच नियम PHP मधून चालवतो — website पूर्ण चालते.
7. शक्य असल्यास subdomain चा **Document Root** `ITI-CareerTrack/app/public` ठेवा (cPanel → Domains). URL मग `https://app.example.com/` असा येतो.
8. Install अर्धवट झाले तर: installer मध्ये **"Delete existing tables in this database first"** वर tick करा आणि पुन्हा Install दाबा.
9. Hostinger व अनेक hosting वर **MariaDB** असते — ते चालते (MariaDB 10.5+ तपासले: 10.11).

## संपूर्ण महाराष्ट्र — 6 region, 36 जिल्हे, 430 शासकीय ITI (आधीच भरलेले)
Installer हे ITI आपोआप भरतो (`database/data/*_itis.csv`):
- **Pune region — 61:** Pune 16, Satara 11, Sangli 10, Solapur 12, Kolhapur 12
- **Mumbai region — 78:** Mumbai City 8, Mumbai Suburban 9, Thane 16, Palghar 11, Raigad 16, Ratnagiri 10, Sindhudurg 8
- **Nagpur region — 78:** Nagpur 17, Wardha 8, Bhandara 8, Gondia 10, Chandrapur 18, Gadchiroli 17
- **Nashik region — 68:** Nashik 21, Ahilyanagar 16, Jalgaon 17, Nandurbar 8, Dhule 6
- **Amravati region — 63:** Amravati 18, Yavatmal 18, Buldhana 13, Akola 8, Washim 6
- **Chh. Sambhajinagar region — 82:** Chhatrapati Sambhajinagar 11, Jalna 8, Beed 12, Dharashiv 8, Latur 11, Nanded 17, Parbhani 9, Hingoli 6

सर्वांचा password **`Demo@1234`**:
| Login | User ID | काय दिसते |
|---|---|---|
| Principal | त्या ITI चा **NCVT MIS Code** (उदा. `GU27000009` Aundh Pune, `GU27000102` Dadar) | फक्त स्वतःची ITI |
| DVTO | `DVTO-PUNE`, `DVTO-SATARA`, `DVTO-SANGLI`, `DVTO-SOLAPUR`, `DVTO-KOLHAPUR`, `DVTO-MUMBAI-CITY`, `DVTO-MUMBAI-SUBURBAN`, `DVTO-THANE`, `DVTO-PALGHAR`, `DVTO-RAIGAD`, `DVTO-RATNAGIRI`, `DVTO-SINDHUDURG`, `DVTO-NAGPUR`, `DVTO-WARDHA`, `DVTO-BHANDARA`, `DVTO-GONDIA`, `DVTO-CHANDRAPUR`, `DVTO-GADCHIROLI`, `DVTO-NASHIK`, `DVTO-AHILYANAGAR`, `DVTO-JALGAON`, `DVTO-NANDURBAR`, `DVTO-DHULE`, `DVTO-AMRAVATI`, `DVTO-YAVATMAL`, `DVTO-BULDHANA`, `DVTO-AKOLA`, `DVTO-WASHIM`, `DVTO-CHHATRAPATI-SAMBHAJINAGAR`, `DVTO-JALNA`, `DVTO-BEED`, `DVTO-DHARASHIV`, `DVTO-LATUR`, `DVTO-NANDED`, `DVTO-PARBHANI`, `DVTO-HINGOLI` | फक्त त्या जिल्ह्याच्या ITI |
| RO Admin | `RO-PUN`, `RO-MUM`, `RO-NGP`, `RO-NSK`, `RO-AMR`, `RO-CSN` | त्या region च्या सर्व ITI |

**इतर region ची यादी:** State Admin → **ITIs Management** → *Import ITI list (Excel / CSV)* — त्याच format ची Excel upload करा
(किमान NCVT MIS ITI Code, Institute Name, District; ITI Type, Taluka, Region असल्यास तेही — नसल्यास नावातून ओळखले जातात). ITI, Principal, DVTO व RO logins आपोआप बनतात.
आधीच install केलेल्या website वर Pune ची यादी हवी असेल तर हीच Excel तिथे upload करा.

## नवीन version update करणे (database नाव / password पुन्हा लागत नाही)
1. नवीन ZIP server वर upload करा आणि **जुन्या folder वरच extract करा** (Hostinger File Manager → Extract → files overwrite / replace).
2. **`app/config/config.php` delete करू नका** — त्यात database नाव व password आहे, आणि ते ZIP मध्ये नसल्याने तसेच राहते.
3. Website उघडा. पहिल्याच page ला database आपोआप update होतो (नवीन trades, indexes इ.). सर्व data (ITI, users, विद्यार्थी) तसाच राहतो.
4. Installer पुन्हा लागत नाही. **"Delete existing tables" फक्त पूर्ण नवीन (कोरी) सुरुवात करायची असेल तेव्हाच** वापरा.

## Trades (ट्रेड)
- अधिकृत NCVT यादीतील **167 trades** आधीच भरलेले आहेत. कोणालाही नवीन trade नाव type करता येत नाही.
- **Principal → Trades & Batches → "Add a trade to your ITI"**: dropdown मधून trade निवडा, units व seats भरा → मग त्याचा batch (unit & shift) बनवा.
- State Admin → Master Data → Trades: प्रत्येक trade चा कालावधी (duration) तपासा / बदला, trade बंद-चालू करा, modules जोडा.

## प्रवेश (Admission)
विद्यार्थ्याचा **फोटो अनिवार्य** आहे (JPG/PNG, 5 MB पर्यंत) — फोटोशिवाय प्रवेश जतन होत नाही.

## महाराष्ट्र शासन लोगो (Government of Maharashtra)
Home page व login page वर "महाराष्ट्र शासन / Government of Maharashtra" लोगोसाठी जागा तयार आहे. राज्य मुद्रा (State Emblem) वापरण्यासाठी
अधिकृत परवानगी लागते, म्हणून लोगो ZIP मध्ये दिलेला नाही. परवानगी मिळाल्यावर अधिकृत लोगो **`app/public/assets/img/gov-logo.png`** या नावाने
upload करा — तो header, login page वर आपोआप दिसेल.

## Login ID (login page वर role निवडल्यावर नाव बदलते)
| Role | Login ID |
|---|---|
| Principal | **NCVT MIS Code** |
| Instructor, ITI Staff | **Sevaarth ID** (State Admin user बनवताना Sevaarth ID भरणे अनिवार्य) |
| Student | **PCID** (Principal "Create login" दाबल्यावर) |
| State Admin, RO, DVTO, Apprentice Advisor, Placement, Employer | **User ID** |

## सर्व login ची यादी
**`LOGINS.xlsx`** (ZIP सोबत वेगळी पाठवलेली फाइल — server वर upload करू नका) — 577 login (State Admin, 6 RO, 36 DVTO, 476 Principal, इतर roles), प्रत्येक login प्रत्यक्ष तपासलेला. Password `Demo@1234`.

## वेग व क्षमता
1.3 लाख प्रवेश/वर्ष साठी एकच database पुरेसा आहे — 2.7 लाख विद्यार्थी व 100 एकाच वेळचे users यावर तपासले.
राज्यभर खऱ्या वापरासाठी shared hosting नव्हे तर VPS / cloud server (8 vCPU, 16–32 GB RAM) वापरा. तपशील: `docs/04-PERFORMANCE-AND-CAPACITY.md` (GitHub वर).

## English (Linux / any server)
1. Copy the `ITI-CareerTrack` folder to the web server. Point Apache/Nginx to `ITI-CareerTrack/app/public` (recommended) — or keep the whole folder in the web root (the included `.htaccess` files block access to private folders).
2. Open the site in a browser → the installer opens → enter MySQL details → choose Demo or Empty → Install.
3. Shared hosting (cPanel): create the database and a MySQL user in *MySQL Databases*, add the user to the database with ALL PRIVILEGES,
   and enter host `localhost` with the full prefixed names. If the host does not allow triggers (binary logging without SUPER), the installer
   says so and the website applies the same rules in PHP.
4. For production: use HTTPS, a MySQL user without DELETE rights (see `database/mysql/04_app_user.sql`), and regular backups of the database and `app/storage/documents`.

## Who does what (logins)
| Login | Sees | Can do |
|---|---|---|
| **State Admin (DVET)** | Whole Maharashtra — every ITI and student | Add, edit, delete everything; creates all user IDs; masters, ITIs, trades, transfers, reports, audit |
| **RO Admin** | All ITIs of own region only | Add, edit, delete student records of the region; creates **Apprentice Advisors** and gives them ITIs of the region |
| **DVTO** | All ITIs of own district | View data and reports (no changes) |
| **Apprentice Advisor** | Only the ITIs given by the State Admin or RO Admin | View data, apprenticeships, verification |
| **Principal** (User ID = NCVT MIS code) | Own ITI | Add, edit, delete; admissions (single or **bulk from Excel** + bulk photos), trades, **adds Instructors (Sevaarth ID), assigns trade & instructor to students**, companies & jobs, follow-ups |
| **Instructor** (User ID = Sevaarth ID) | Only the students assigned to him / her by the Principal | Final ITI result (Pass / Fail); next step: apprenticeship (company, contract start / end, stipend), job (company, start / end, salary), business (type, location), higher education (college, course) or not employed |
| **Employer / HR** | Own company's students | Adds apprenticeship / job at own company: contract start / end, stipend / salary |
| **Student** (User ID = PCID) | Own career passport | Adds own apprenticeship / job (company name, contract start / end, stipend) |

Placement Officer and ITI Staff logins are no longer used: their work is done by the Principal and the Instructor.

## Requirements
MySQL 8.0.16+ **or MariaDB 10.5+** (tested on MariaDB 10.11). PHP 8.1+ with `pdo_mysql`, `zlib`, `mbstring`, `fileinfo`, `openssl`; MySQL 8.0.16+; Apache with `mod_rewrite` (XAMPP has it).

## Bulk admission (Principal) — Excel मधून सर्व विद्यार्थी एकदम

1. Menu → **Bulk Admission (Excel)** → **Sample Excel file** डाउनलोड करा (Students, Trades, Instructors, Instructions sheets).
2. प्रत्येक विद्यार्थ्यासाठी एक ओळ भरा. Trade नाव "Trades" sheet प्रमाणेच. Instructor Sevaarth ID (optional) भरल्यास विद्यार्थी त्या Instructor ला assign होतो.
3. File upload करा → प्रत्येक विद्यार्थ्याला PCID मिळतो. चुकीच्या ओळी कारणासह दिसतात; दुरुस्त करून तीच file पुन्हा upload करा (आधी add झालेले विद्यार्थी परत add होत नाहीत).
4. **Photos**: file चे नाव = PCID / SSC seat number / mobile number (उदा. `9876543210.jpg`); अनेक photos एकदम किंवा एक ZIP file.
5. **Assign Trade & Instructor**: विद्यार्थी tick करा → trade आणि / किंवा instructor निवडा. Instructor ला फक्त त्याला assign केलेलेच विद्यार्थी दिसतात.

## आपोआप माहिती जोडणी (Automatic Data Linking) — विद्यार्थ्याने काही न भरता नोंद

विद्यार्थी ITI नंतर update देत नाहीत, म्हणून शासकीय नोंदींमधून माहिती आपोआप PCID ला जोडली जाते.

| स्रोत | काय भरले जाते |
| --- | --- |
| NCVT MIS | निकाल, गुण, प्रमाणपत्र क्रमांक |
| NAPS (Apprenticeship portal) | Apprenticeship: आस्थापना, contract तारखा, stipend, पूर्ण/रद्द |
| EPFO (UAN) | नोकरी: employer, रुजू तारीख, सोडल्याची तारीख, पगार |
| Udyam | स्वयंरोजगार: उद्योगाचे नाव, प्रकार, ठिकाण |
| DTE / विद्यापीठ / APAAR | पुढील शिक्षण: संस्था, अभ्यासक्रम, वर्ष |
| ID list | NCVT reg. no., UAN, APAAR … फक्त जोडणी |

**विद्यार्थी कसा ओळखला जातो (क्रमाने):** त्या प्रणालीचा ID (UAN, NCVT reg. no. …) → PCID → Aadhaar (फक्त encrypted तुलना, Aadhaar साठवला जात नाही) → mobile + जन्मतारीख → नाव + जन्मतारीख (एकच विद्यार्थी जुळला तरच).
न जुळलेल्या ओळी ठेवल्या जातात आणि दररोज रात्री पुन्हा तपासल्या जातात — ITI ने विद्यार्थ्याचा UAN / NCVT reg. no. जोडला की त्या लगेच जुळतात.

**वापर:**
1. State Admin → **Automatic Data Linking** → स्रोत निवडा → *Sample file* पाहा → मिळालेली Excel/CSV फाइल **Import** करा.
2. **पूर्ण आपोआप:** फाइल `app/storage/imports/inbox/<SOURCE>/` मध्ये ठेवा (उदा. `inbox/EPFO/epfo-nov.xlsx`). रात्रीचे job ती import करून `done/` मध्ये हलवते.
3. Hostinger → Advanced → **Cron Jobs** → दररोज 02:00:
   `php /home/USER/domains/DOMAIN/public_html/app/app/bin/auto_import.php`
4. **API (MoU झाल्यावर):** `app/config/config.php` मध्ये जोडा:
   ```php
   'integrations' => [
       'EPFO' => [
           'url'     => 'https://…/members',                 // API देणाऱ्या विभागाने दिलेला पत्ता
           'method'  => 'POST',                             // GET किंवा POST (त्यांच्या कागदपत्रानुसार)
           'headers' => ['X-APISETU-CLIENTID' => 'तुमचा client id', 'X-APISETU-APIKEY' => 'तुमची key'],
           'token'   => '',                                 // Bearer token दिला असेल तर इथे
           'body'    => ['fromDate' => '{since}'],          // {since} = मागच्या वेळची तारीख (आपोआप)
           'rows'    => 'data.records',                     // उत्तरात यादी कुठे आहे
           'map'     => ['memberUan' => 'UAN', 'estName' => 'Establishment Name', 'doj' => 'Date of Joining'],
       ],
   ],
   ```
   रात्रीचे job ते API रोज वाचते. चाचणीसाठी हाताने चालवा:
   `php app/bin/auto_import.php --source=EPFO --no-retry`
   निकाल State Admin → Automatic Data Linking → *Recent imports* मध्ये "Api" म्हणून दिसतो; चूक असेल तर "API failed (HTTP 401)" असे दिसते.

अशा नोंदी profile वर **"Official record — EPFO (automatic)"** अशा दिसतात आणि लगेच *Verified* असतात. शासकीय API / data वापरण्यासाठी DVET/DGT कडून अधिकृत करारनामा (MoU) व विद्यार्थ्याची संमती आवश्यक आहे.

## Email & Circulars — State Admin सर्व ITI ना एकाच वेळी, RO Admin आपल्या region मधील ITI ना

Menu: **Email & Circulars** (State Admin, RO Admin, DVTO, Principal). न वाचलेल्या संदेशांची संख्या menu मध्ये लाल रंगात दिसते.

| Login | काय करू शकतो |
|---|---|
| State Admin | संपूर्ण महाराष्ट्रातील **सर्व ITI** ना एकाच वेळी, किंवा region / जिल्हा / शासकीय-खाजगी निवडून, किंवा ITI एक-एक tick करून; RO offices व DVTO offices ना सुद्धा |
| RO Admin | फक्त **आपल्या region** मधील ITI व DVTO offices ना (दुसऱ्या region चे ITI निवडताच येत नाहीत) |
| Principal / DVTO | आलेले संदेश वाचणे, attachment download, **Reply** (attachment सह) |

- **Inbox / Starred / Sent / Drafts / Archive**, शोध (search), Priority (Normal / Important / Urgent), "Reply required by" तारीख.
- **Attachments:** PDF, Word, Excel, PowerPoint, JPG, PNG, CSV, ZIP — एका संदेशात 10 फाइल, प्रत्येकी 10 MB, एकूण 25 MB. फाइल्स वेबवरून थेट उघडत नाहीत; login करूनच download होतात.
- **Sent** मधील संदेश उघडला की प्रत्येक ITI ची स्थिती: ई-मेल गेला / अयशस्वी, portal मध्ये उघडला का (कोणी, केव्हा), reply आला का. Filter (उघडला नाही / reply नाही / ई-मेल अयशस्वी), **Excel मध्ये download**, आणि "**Remind those who have not opened**" (न उघडलेल्यांना पुन्हा ई-मेल).
- प्रत्येक ITI ला स्वतंत्र ई-मेल जातो (एका ITI ला दुसऱ्या ITI चे पत्ते दिसत नाहीत).
- ई-मेल कोणाला जातो: ITI चा official ई-मेल (Principal स्वतः Email & Circulars page वर भरू शकतो; किंवा ITI Excel import मध्ये "Email" column) + Principal चा login ई-मेल.

**ई-मेल पाठवणे सुरू करणे (Hostinger):** settings न भरल्यास सर्व संदेश portal Inbox मध्ये मिळतात, पण ई-मेल जात नाही (DEMO).
1. hPanel → **Emails** → तुमच्या domain वर mailbox बनवा, उदा. `noreply@copamcq.com` (password लक्षात ठेवा).
2. `app/config/config.php` मध्ये शेवटच्या `);` च्या आधी हे जोडा:
```php
  'mail' => [
    'host' => 'smtp.hostinger.com', 'port' => 465, 'secure' => 'ssl',
    'username' => 'noreply@copamcq.com', 'password' => 'mailbox चा password',
    'from' => 'noreply@copamcq.com', 'from_name' => 'DVET Maharashtra — ITI CareerTrack',
    'per_hour' => 300,
  ],
```
3. hPanel → Advanced → **Cron Jobs** → दर 5 मिनिटांनी: `php /home/USER/public_html/app/app/bin/send_mail.php`
   (page उघडे असताना ई-मेल लगेच जातात; उरलेले cron पाठवते. `per_hour` = hosting plan ची तासाची मर्यादा.)
4. State Admin → Email & Circulars → **E-mail settings** → "Send test" ने स्वतःला test ई-मेल पाठवून तपासा (spam folder सुद्धा पाहा).

## WhatsApp Bot — विद्यार्थी फक्त क्रमांक पाठवून स्टेटस अपडेट करतात (Principal व Instructor)

Menu: **WhatsApp Updates** (Principal: ITI चे विद्यार्थी; Instructor: फक्त assign केलेले).
1. यादीतून विद्यार्थी tick करा → **Send WhatsApp question**. विद्यार्थ्याला संदेश जातो: "1 नोकरी · 2 Apprenticeship · 3 पुढील शिक्षण · 4 व्यवसाय · 5 नोकरी शोधत आहे".
2. विद्यार्थी क्रमांक पाठवतो; bot कंपनी/कॉलेज आणि तारीख विचारतो. उत्तर येताच नोंद होते (source: Student, ITI ने पडताळायची) आणि सध्याचा status लगेच बदलतो. STOP = संदेश बंद.
3. रात्रीचे cron job, status माहीत नसलेल्या / 6 महिने जुन्या pass-outs ना आपोआप आठवण पाठवते (WhatsApp जोडल्यावरच).

**DEMO mode:** खालील settings नसतील तर संदेश फोनवर जात नाहीत; Chat page वर "DEMO: reply as the student" ने उत्तर देऊन पूर्ण प्रक्रिया दाखवता येते (jury साठी).

**खरा WhatsApp जोडणे (Meta — WhatsApp Business Cloud API):**
1. business.facebook.com → Business Account (शक्यतो DVET/ITI नावाने, verification) → developers.facebook.com → App → *WhatsApp* product जोडा → फोन नंबर जोडा.
2. WhatsApp Manager → **Message template** बनवा: नाव `career_status_update`, भाषा Marathi (`mr`), category Utility, body:
   `नमस्कार {{1}}! ITI CareerTrack (DVET महाराष्ट्र): तुम्ही सध्या काय करत आहात? फक्त क्रमांक पाठवा: 1 नोकरी, 2 Apprenticeship, 3 पुढील शिक्षण, 4 स्वतःचा व्यवसाय, 5 नोकरी शोधत आहे. (बंद करण्यासाठी STOP)` — Meta ची मंजुरी लागते.
3. App → WhatsApp → Configuration → **Webhook**: Callback URL `https://तुमचे-domain/app/public/webhooks/whatsapp` (जिथे site उघडते तो पत्ता + `/webhooks/whatsapp`), Verify token = तुम्ही ठरवलेला शब्द; field **messages** subscribe करा.
4. `app/config/config.php` मध्ये जोडा:
   ```php
   'whatsapp' => [
       'phone_number_id' => 'Meta मधील Phone number ID',
       'token'           => 'Permanent access token (System user)',
       'app_secret'      => 'App → Settings → Basic → App secret',
       'verify_token'    => 'webhook साठी ठरवलेला शब्द',
       'template'        => 'career_status_update',
       'language'        => 'mr',
       'reminder_days'   => 180,      // इतके दिवस अपडेट नसेल तर रात्री आपोआप आठवण (0 = बंद)
       'reminders_per_night' => 500,
   ],
   ```
5. WhatsApp Updates page वर "WhatsApp connected" (हिरवा) दिसेल. एका विद्यार्थ्याला (स्वतःचा नंबर) पाठवून तपासा.

टीप: Meta business-initiated (template) संदेशांसाठी शुल्क आकारते; विद्यार्थ्याच्या उत्तरानंतर 24 तासांतील bot चे उत्तर मोफत असते. विद्यार्थ्याची संमती (प्रवेश फॉर्ममधील consent) आवश्यक.

## Database सुरक्षा — रोज backup, दुसरा database (replica) आणि आपोआप failover

एकाच database ला काही झाले तरी system थांबू नये म्हणून तीन स्तर:

| स्तर | काय होते | कधी |
| --- | --- | --- |
| 1. Backup file | संपूर्ण database (tables, data, views, triggers) ची `.sql.gz` फाइल `app/storage/backups/` मध्ये; शेवटच्या 14 ठेवल्या जातात | रोज रात्री (cron) किंवा State Admin → **Database Backups → Backup now** |
| 2. Backup database (replica) | तीच फाइल **दुसऱ्या database** मध्ये भरली जाते — शक्यतो दुसऱ्या server / hosting खात्यावर | रोज रात्री backup नंतर लगेच |
| 3. Failover | मुख्य database उत्तर देत नसेल तर website आपोआप backup database वर, **फक्त पाहण्यासाठी (read-only)** चालू राहते; लाल पट्टी दिसते; save बंद. मुख्य परत आला की आपोआप मुख्य वापरला जातो | आपोआप |

**एकदाच सेटअप:**
1. दुसरा MySQL database + user बनवा (Hostinger hPanel → Databases; उत्तम: दुसऱ्या hosting / शासकीय server वर).
2. `app/config/config.php` मध्ये:
   ```php
   'db_backup' => ['host' => 'localhost', 'port' => 3306, 'name' => 'u427511805_ctbackup', 'user' => 'u427511805_ctbackup', 'pass' => '…'],
   'backup'    => ['keep' => 14, 'failover' => true],
   ```
3. Cron — रोज 01:30 (02:00 च्या import आधी):
   `php /home/USER/domains/DOMAIN/public_html/app/app/bin/backup.php`
4. आठवड्यातून एकदा State Admin → Database Backups → सर्वात नवीन फाइल **Download** करून शासकीय संगणक / Drive वर ठेवा (personal data — सुरक्षित ठिकाणी).

**Data हरवला / बिघडला तर (restore):**
`php app/bin/backup.php --restore=careertrack-YYYYmmdd-HHiiss.sql.gz --yes` — आधी सध्याच्या स्थितीची safety copy आपोआप बनते, मग निवडलेली फाइल मुख्य database मध्ये भरली जाते.

**राज्यभर वापरासाठी (VPS / शासकीय server):** MySQL ची real-time **source → replica replication** (GTID) वापरा — replica काही सेकंदांत अद्ययावत राहते. वरील `db_backup` मध्ये replica चा पत्ता दिला की failover तसेच चालते. Hostinger shared hosting वर real-time replication शक्य नाही; तिथे रोजची प्रत (वरील पद्धत) वापरा.
