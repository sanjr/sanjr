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
| Login | Work |
|---|---|
| State Admin (DVET) | Whole Maharashtra; creates all user IDs, ITIs, trades, years; transfers, verification, reports, audit |
| RO Admin (6 regions) | All ITIs of the region: students, results, apprenticeship, placement, verification, reports |
| DVTO Admin | All ITIs of the district: same as RO, for one district |
| Apprentice Advisor | Assigned ITIs: apprenticeship, companies (approve), verification, reports |
| Principal (User ID = NCVT MIS code) | Own ITI: admission + PCID, batches, staff passwords, attendance, exams, transfers, placement, follow-ups, reports |
| Instructor | All students of own trade(s): attendance, module progress, assessments, results, career view |
| Placement Officer | Companies, vacancies, applications, jobs/apprenticeships, follow-ups, verification |
| ITI Staff | Follow-up calls assigned to them |
| Employer / HR | Own company, vacancies, applicants; full history (view only) of own employees/apprentices; adds new records only for own company |
| Student / Alumni | Own Career Passport + PCID card, job search, adds new job/company/course |

## Requirements
MySQL 8.0.16+ **or MariaDB 10.5+** (tested on MariaDB 10.11). PHP 8.1+ with `pdo_mysql`, `zlib`, `mbstring`, `fileinfo`, `openssl`; MySQL 8.0.16+; Apache with `mod_rewrite` (XAMPP has it).
