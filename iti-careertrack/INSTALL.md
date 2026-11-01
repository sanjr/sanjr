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
8. Install अर्धवट झाले तर: phpMyAdmin मध्ये त्या database मधील सर्व tables Drop करा (किंवा नवीन database बनवा) आणि पुन्हा installer उघडा.

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
PHP 8.1+ with `pdo_mysql`, `zlib`, `mbstring`, `fileinfo`, `openssl`; MySQL 8.0.16+; Apache with `mod_rewrite` (XAMPP has it).
