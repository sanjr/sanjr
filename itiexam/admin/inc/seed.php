<?php
/* Starter data written once by setup.php. Everything here can be edited or deleted from the admin panel. */
function seed_site(): void
{
    $pdo = db();
    if ((int)$pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() === 0) seed_menus();
    seed_home_lists();
    if ((int)$pdo->query('SELECT COUNT(*) FROM trades')->fetchColumn() === 0) seed_trades();
    if ((int)$pdo->query('SELECT COUNT(*) FROM employers')->fetchColumn() === 0) seed_jobs();
    seed_pages();
    $defaults = [
        'site_description' => 'Free ITI mock tests, NCVT ITI previous year question papers, Employability Skills MCQ, study material PDF, ITI jobs, apprenticeship (NAPS/NATS), admit card & result updates for COPA, Electrician, Fitter, Turner, Welder & all ITI trades.',
        'home_title' => 'ITI Exam ' . date('Y') . ': Free ITI Mock Test, Question Paper PDF & Jobs | ITI Exam',
        'home_keywords' => 'ITI exam, ITI mock test, ITI online test, ITI CBT exam, NCVT ITI exam 2026, ITI previous year question paper, Employability Skills MCQ, COPA mock test, ITI study material, ITI jobs, ITI apprenticeship, NAPS, NATS, ITI result, Maharashtra ITI, DVET',
        'footer_about' => 'Online Mock Tests, Previous Year Questions, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.',
        'social_youtube' => '', 'social_facebook' => '', 'social_telegram' => '', 'social_whatsapp' => '',
        'contact_email' => 'contact@itiexam.com', 'whatsapp_float' => '1', 'whatsapp_group' => '',
    ];
    foreach ($defaults as $k => $v) if (setting($k, "\0") === "\0") setting_set($k, $v);
}

function seed_menus(): void
{
    $ins = db()->prepare('INSERT INTO menu_items (location, parent_id, label, url, sort_order) VALUES (?, ?, ?, ?, ?)');
    $add = function (string $loc, string $label, string $url, int $order, int $parent = 0) use ($ins): int {
        $ins->execute([$loc, $parent, $label, $url, $order]);
        return (int)db()->lastInsertId();
    };
    $header = [
        ['Home', '/', []],
        ['Trades', '/trades/', [['COPA', '/trades/copa/'], ['Electrician', '/trades/electrician/'], ['Fitter', '/trades/fitter/'], ['Turner', '/trades/turner/'], ['Welder', '/trades/welder/']]],
        ['Mock Tests', '/mock-test/', []],
        ['Previous Papers', '/previous-papers/', []],
        ['Employability Skills', '/employability-skills/', []],
        ['Study Material', '/study-material/', [['Theory Notes', '/study-material/'], ['Syllabus', '/syllabus/'], ['Practical', '/practical/']]],
        ['Jobs', '/jobs/', [['Government Jobs', '/jobs/government/'], ['Private Company Jobs', '/jobs/private/'], ['Campus Placement', '/jobs/campus/'], ['Walk-in Interview', '/jobs/walk-in/'], ['Career Guidance', '/career-guidance/']]],
        ['Apprenticeship', '/apprenticeship/', [['NAPS', '/apprenticeship/naps/'], ['NATS', '/apprenticeship/nats/'], ['Apprentice Jobs', '/jobs/apprentice/']]],
        ['Exam Info', '/exam/', [['Exam Updates', '/exam/'], ['Articles & Tips', '/blog/']]],
        ['About', '/about/', [['Contact', '/contact/'], ['FAQ', '/faq/']]],
    ];
    foreach ($header as $i => [$l, $u, $kids]) {
        $pid = $add('header', $l, $u, $i);
        foreach ($kids as $j => [$cl, $cu]) $add('header', $cl, $cu, $j, $pid);
    }
    foreach ([['Home', '/'], ['All Trades', '/trades/'], ['Mock Tests', '/mock-test/'], ['Previous Papers', '/previous-papers/'], ['Employability Skills', '/employability-skills/'],
              ['Study Material', '/study-material/'], ['Jobs & Career', '/jobs/'], ['Apprenticeship', '/apprenticeship/'], ['Exam Info', '/exam/'], ['About Us', '/about/']] as $i => [$l, $u]) $add('footer_quick', $l, $u, $i);
    foreach ([['NCVT MIS', 'https://www.ncvtmis.gov.in/'], ['DVET Maharashtra', 'https://dvet.gov.in/'], ['Apprenticeship Portal', 'https://www.apprenticeshipindia.gov.in/'],
              ['ITI Admission', 'https://admission.dvet.gov.in/'], ['Skill India Digital (Results)', 'https://www.skillindiadigital.gov.in/'], ['Bharat Skills', 'https://bharatskills.gov.in/'], ['NIMI', 'https://nimi.gov.in/'], ['Career Guidance', '/career-guidance/'], ['FAQ', '/faq/'], ['Contact Us', '/contact/']] as $i => [$l, $u]) $add('footer_resources', $l, $u, $i);
    foreach ([['Privacy Policy', '/privacy-policy/'], ['Terms & Conditions', '/terms/'], ['Disclaimer', '/disclaimer/'], ['Contact Us', '/contact/']] as $i => [$l, $u]) $add('footer_legal', $l, $u, $i);
}

function seed_trades(): void
{
    $pdo = db();
    $ins = $pdo->prepare('INSERT INTO trades (slug, name, full_name, short_desc, description, duration, nsqf_level, sector, exam_pattern, emoji, card_color, is_popular, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $list = [
        ['copa', 'COPA', 'Computer Operator and Programming Assistant', 'Computer Operator and Programming Assistant', 'ITI COPA trade prepares students with essential computer skills, office applications, programming, web development and IT support skills required in various industries and organizations.', '1 Year', 'Level 4', 'IT & ITES', 'NCVT (CBT + Practical)', '🖥️', '#f5f0ff', 1],
        ['electrician', 'Electrician', '', 'Installation, Maintenance and Repair', '', '2 Years', 'Level 5', 'Power', 'NCVT (CBT + Practical)', '💡', '#fff8e6', 1],
        ['fitter', 'Fitter', '', 'Mechanical Fitting and Assembly', '', '2 Years', 'Level 5', 'Capital Goods & Manufacturing', 'NCVT (CBT + Practical)', '🔧', '#eef4ff', 1],
        ['turner', 'Turner', '', 'Lathe Machine Operations', '', '2 Years', 'Level 5', 'Capital Goods & Manufacturing', 'NCVT (CBT + Practical)', '⚙️', '#f1f5f9', 1],
        ['welder', 'Welder', '', 'Welding and Fabrication', '', '1 Year', 'Level 3', 'Capital Goods & Manufacturing', 'NCVT (CBT + Practical)', '🥽', '#fff1f2', 1],
        ['mmv', 'MMV', 'Mechanic Motor Vehicle', 'Mechanic Motor Vehicle', '', '2 Years', 'Level 5', 'Automotive', 'NCVT (CBT + Practical)', '🚗', '#f0f9ff', 1],
        ['plumber', 'Plumber', '', 'Plumbing and Pipe Fitting', '', '1 Year', 'Level 3', 'Construction', 'NCVT (CBT + Practical)', '🚰', '#ecfeff', 1],
        ['wireman', 'Wireman', '', 'Electrical Wiring and Installation', '', '2 Years', 'Level 4', 'Power', 'NCVT (CBT + Practical)', '🔌', '#fff7ed', 1],
        ['machinist', 'Machinist', '', 'Machine Tool Operations', '', '2 Years', 'Level 5', 'Capital Goods & Manufacturing', 'NCVT (CBT + Practical)', '🛠️', '#f1f5f9', 0],
        ['diesel-mechanic', 'Diesel Mechanic', 'Mechanic Diesel', 'Diesel Engine Repair', '', '1 Year', 'Level 4', 'Automotive', 'NCVT (CBT + Practical)', '🚜', '#fefce8', 0],
    ];
    foreach ($list as $i => $r) $ins->execute([...$r, $i, now(), now()]);

    // Sample content for COPA only – every other trade starts empty so the admin adds its own content.
    $copa = (int)$pdo->query("SELECT id FROM trades WHERE slug = 'copa'")->fetchColumn();
    $it = $pdo->prepare('INSERT INTO trade_items (trade_id, section, title, subtitle, url, label, icon, questions, minutes, is_new, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $add = function (string $sec, array $rows) use ($it, $copa) {
        foreach ($rows as $i => $r) {
            $r += ['', '', '', '', '', 0, 0, 0];
            $it->execute([$copa, $sec, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $i]);
        }
    };
    $add('quick_links', [['Start Mock Test', '', '#mock-tests', '', 'clip'], ['Syllabus & Modules', '', '#syllabus', '', 'list'], ['Theory Notes (PDF)', '', '#study-material', '', 'pdf'],
        ['Practical Exercises', '', '#practical', '', 'flask'], ['Previous Year Papers', '', '#previous-papers', '', 'doc'], ['Important Questions', '', '#important-questions', '', 'alert'], ['Related Videos', '', '#videos', '', 'play'], ['Software & Tools', '', '#resources', '', 'gear']]);
    $add('features', [['Module-wise Tests', 'Topic wise practice', '#mock-tests', '', 'doc'], ['Full Mock Tests', 'Exam pattern based', '#mock-tests', '', 'list'], ['Previous Papers', 'With solutions', '#previous-papers', '', 'clip'],
        ['Theory Notes', 'PDF Download', '#study-material', '', 'pdf'], ['Practical Exercises', 'Step-by-step', '#practical', '', 'flask'], ['Important Questions', 'Most expected MCQs', '#important-questions', '', 'brief']]);
    $add('mock_tests', [['Computer Fundamentals – Set 1', '', '#', '', 'monitor', 25, 30, 1], ['MS Office – Set 1', '', '#', '', 'doc', 25, 30, 1], ['Internet & Networking – Set 1', '', '#', '', 'globe', 25, 30],
        ['HTML & CSS – Set 1', '', '#', '', 'code', 25, 30], ['JavaScript – Set 1', '', '#', '', 'js', 25, 30]]);
    $add('syllabus', [['Safe Working Practices', 'Safety, 5S, Ergonomics, ESD'], ['Computer Basics and Software Installation', 'Windows OS, Software, Hardware'], ['Word Processing and Office Tools', 'MS Word, Excel, PowerPoint'],
        ['Internet and Networking', 'Internet, Email, Networking Basics'], ['Web Designing', 'HTML, CSS, JavaScript'], ['Python Programming', 'Python Basics, Programming Concepts'],
        ['Database Management', 'MySQL, SQL, Queries, Normalization'], ['Cloud Computing and Cyber Security', 'Cloud, Cyber Safety, Data Security']]);
    $add('study_material', [['Theory Notes (PDF)', 'Module-wise notes', '#', '', 'pdf'], ['MCQ Question Bank', '1000+ Questions', '#', '', 'help'], ['Model Question Papers', 'Exam pattern & blueprint', '#', '', 'layout']]);
    $add('practical', [['Practical Notes', 'Step-by-step guides', '#', '', 'flask'], ['MS Excel Practical', 'Formulas, charts and pivot tables', '#', '', 'grid']]);
    $add('previous_papers', [['NCVT CBT Exam 2024', 'With Solutions', '#', '2024'], ['NCVT CBT Exam 2023', 'With Solutions', '#', '2023'], ['NCVT CBT Exam 2022', 'With Solutions', '#', '2022'], ['NCVT CBT Exam 2021', 'With Solutions', '#', '2021']]);
    $add('important_questions', [['Computer Fundamentals', 'Most expected questions', '#', '', 'monitor'], ['MS Office', 'Important MCQs', '#', '', 'doc'], ['Internet & Networking', 'Previous year important questions', '#', '', 'globe'],
        ['HTML, CSS & JavaScript', 'Expected questions', '#', '', 'code'], ['Python and MySQL', 'High weightage questions', '#', '', 'db']]);
    $add('faq', [['What is the duration of the ITI COPA course?', 'ITI COPA is a 1 year (2 semester) NCVT course at NSQF Level 4.'],
        ['What is the exam pattern for COPA?', 'Trade Theory, Employability Skills and Workshop Calculation are Computer Based Tests (MCQ); the practical exam is held at the ITI.'],
        ['What jobs can I get after ITI COPA?', 'Computer operator, data entry operator, office assistant, junior web designer, IT support and apprenticeship in government and private companies.']]);
    $pdo->prepare('UPDATE trades SET content = ? WHERE id = ?')->execute(['<p>The <strong>ITI COPA (Computer Operator and Programming Assistant)</strong> trade is a one-year NCVT course in the IT &amp; ITES sector. Students learn computer fundamentals, Windows and Linux, MS Office, internet and networking, HTML, CSS, JavaScript, Python, databases and cyber security.</p><p>Use the mock tests above to practise for the CBT exam, read module-wise theory notes, and solve previous year papers with solutions to score high.</p>', $copa]);
}

/** Employers + sample jobs. Sample jobs are flagged so the dashboard reminds the admin to replace them. */
function seed_jobs(): void
{
    $pdo = db();
    $emps = [['BEL', 'BEL', '#1d4ed8'], ['Indian Railways', 'IR', '#dc2626'], ['Tata Motors', 'TM', '#0b5cab'], ['Larsen & Toubro', 'L&T', '#0f172a'],
             ['Maruti Suzuki', 'MSIL', '#1e3a8a'], ['Adani Group', 'ADANI', '#7c3aed'], ['JSW Steel', 'JSW', '#b91c1c'], ['Ashok Leyland', 'AL', '#0369a1'],
             ['Hyundai Motor India', 'HMI', '#1e40af'], ['Mahindra & Mahindra', 'M&M', '#be123c']];
    $ins = $pdo->prepare('INSERT INTO employers (name, short_name, color, is_featured, sort_order) VALUES (?, ?, ?, 1, ?)');
    foreach ($emps as $i => $e) $ins->execute([...$e, $i]);
    $eid = fn($n) => (int)$pdo->query('SELECT id FROM employers WHERE name = ' . $pdo->quote($n))->fetchColumn();

    $d = fn($days) => date('Y-m-d', strtotime("+$days days"));
    $note = '<p><strong>This is a sample job added during setup.</strong> Replace it with real notification details, or delete it from Admin → Jobs.</p>';
    $jobs = [
        ['ITI Apprentice Recruitment', 'BEL', 'government', 'Multiple Trades', 'ITI Pass', 'Across India', $d(8), 1],
        ['ITI Apprentice', 'Indian Railways', 'government', 'Multiple Trades', 'ITI Pass', 'All India', $d(13), 1],
        ['ITI Trainee Recruitment', 'Tata Motors', 'private', 'Fitter, Electrician, MMV', 'ITI Pass', 'Pune, Maharashtra', $d(6), 0],
        ['ITI Apprentice', 'Larsen & Toubro', 'apprentice', 'COPA, Fitter, Electrician, Welder', 'ITI Pass', 'Chennai, Tamil Nadu', $d(10), 0],
        ['ITI Trainee', 'Maruti Suzuki', 'private', 'Fitter, Electrician, MMV, Welder', 'ITI Pass', 'Gurugram, Haryana', $d(16), 0],
        ['ITI Apprentice / Trainee', 'Adani Group', 'apprentice', 'Multiple Trades', 'ITI Pass', 'Ahmedabad, Gujarat', $d(18), 0],
    ];
    $ji = $pdo->prepare('INSERT INTO jobs (slug, title, employer_id, company, job_type, trades, qualification, location, last_date, is_featured, salary_text, summary, description, posted_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $ids = [];
    foreach ($jobs as $i => [$title, $co, $type, $trades, $qual, $loc, $last, $feat]) {
        $posted = date('Y-m-d H:i:s', strtotime("-$i days"));
        $ji->execute([slugify("$co $title " . date('Y')), $title . ' ' . date('Y'), $eid($co), $co, $type, $trades, $qual, $loc, $last, $feat,
            $type === 'apprentice' || $type === 'government' ? 'Stipend as per Apprentices Act' : '', "$co is hiring ITI pass candidates. Check eligibility and last date.", $note, $posted, $posted]);
        $ids[] = (int)$pdo->lastInsertId();
    }
    setting_set('sample_job_ids', implode(',', $ids));
}

/** Starter pages so every menu and homepage link opens a real page. Skips any URL that already exists. */
function seed_pages(): void
{
    $pdo = db();
    $exists = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
    $ins = $pdo->prepare('INSERT INTO pages (slug, title, content, excerpt, category, meta_description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $site = SITE_NAME;
    $pages = [
        ['mock-test', 'ITI Mock Test – Free Online Test for All Trades', '', 'Choose your trade and start a free ITI mock test based on the latest NCVT CBT pattern.',
         "<p>Practise free <strong>ITI online mock tests</strong> for Trade Theory, Employability Skills and Workshop Calculation &amp; Science. Every test follows the NCVT CBT pattern with multiple choice questions and a timer.</p><h2>Latest online tests</h2>[tests limit=\"12\"]<h2>Select your trade</h2>[trades section=\"mock-tests\"]<h2>How to use mock tests</h2><ul><li>Take one full test every day and note the wrong answers.</li><li>Revise the topic from theory notes, then attempt the next set.</li><li>Before the exam, attempt previous year papers under the same time limit.</li></ul>"],
        ['previous-papers', 'ITI Previous Year Question Papers with Answers', '', 'NCVT ITI previous year CBT question papers with solutions for every trade.',
         "<p>Solving <strong>ITI previous year question papers</strong> is the fastest way to understand the exam pattern and the topics that are asked again and again.</p><h2>Select your trade</h2>[trades section=\"previous-papers\"]"],
        ['study-material', 'ITI Study Material – Theory Notes PDF, MCQ & Model Papers', '', 'Module-wise ITI theory notes, MCQ question bank and model papers for all trades.',
         "<p>Read module-wise <strong>ITI theory notes</strong>, practise the <strong>MCQ question bank</strong> and download model papers for your trade.</p><h2>Select your trade</h2>[trades section=\"study-material\"]"],
        ['syllabus', 'ITI Syllabus – NCVT Trade-wise Syllabus & Modules', '', 'Latest NCVT syllabus and module list for each ITI trade.',
         "<p>Check the latest <strong>NCVT syllabus</strong> and module-wise topics for your trade.</p>[trades section=\"syllabus\"]"],
        ['practical', 'ITI Practical Exercises – Step-by-step Guides', '', 'Practical exercises and step-by-step guides for ITI trades.',
         "<p>Step-by-step practical exercises for your ITI trade.</p>[trades section=\"practical\"]"],
        ['employability-skills', 'Employability Skills MCQ – ITI 1st & 2nd Year', '', 'Employability Skills notes and MCQ for ITI 1st and 2nd year CBT exam.',
         "<p><strong>Employability Skills (ES)</strong> is a common subject for all ITI trades. It covers English literacy, IT literacy, communication skills, entrepreneurship, productivity, occupational safety and labour welfare.</p><h2>Topics</h2><ul><li>Introduction to Employability Skills</li><li>Constitution values and citizenship</li><li>Communication skills and English</li><li>Basic IT literacy</li><li>Entrepreneurship and financial literacy</li><li>Occupational safety, health and environment</li></ul><p>Use the mock tests on each trade page to practise ES questions.</p>"],
        ['apprenticeship', 'ITI Apprenticeship 2026 – NAPS, NATS & How to Apply', '', 'Apprenticeship guide for ITI pass students: NAPS, NATS, eligibility, registration and latest openings.',
         "<p>After ITI, an <strong>apprenticeship</strong> gives you paid on-the-job training in a company and improves your chances of a permanent job.</p><h2>Schemes</h2><ul><li><a href=\"/apprenticeship/naps/\">NAPS</a> – National Apprenticeship Promotion Scheme (for ITI pass students)</li><li><a href=\"/apprenticeship/nats/\">NATS</a> – National Apprenticeship Training Scheme (for diploma and graduates)</li></ul><h2>How to register</h2><ol><li>Open the official portal <a href=\"https://www.apprenticeshipindia.gov.in/\" target=\"_blank\" rel=\"noopener\">apprenticeshipindia.gov.in</a>.</li><li>Register as a candidate with your mobile number and email.</li><li>Complete your profile and upload ITI marksheet and Aadhaar.</li><li>Search for openings by trade and location and apply.</li></ol><h2>Latest apprentice openings</h2>[jobs type=\"apprentice\" limit=\"6\"]"],
        ['apprenticeship/naps', 'NAPS Apprenticeship – National Apprenticeship Promotion Scheme', '', 'What NAPS is, who can apply and how ITI students can register.',
         "<p>The <strong>National Apprenticeship Promotion Scheme (NAPS)</strong> encourages companies to engage apprentices. ITI pass candidates can apply through the Apprenticeship India portal.</p><h2>Eligibility</h2><ul><li>ITI pass in the relevant trade (or appearing, as per the opening)</li><li>Minimum age 14 years (18 for hazardous trades)</li></ul><p>See the <a href=\"/apprenticeship/\">apprenticeship guide</a> for registration steps.</p>"],
        ['apprenticeship/nats', 'NATS Apprenticeship – National Apprenticeship Training Scheme', '', 'NATS apprenticeship for diploma holders and graduates.',
         "<p>The <strong>National Apprenticeship Training Scheme (NATS)</strong> is for diploma holders and graduates in engineering and other streams. Registration is done on the official NATS portal.</p><p>ITI pass students should apply under <a href=\"/apprenticeship/naps/\">NAPS</a>.</p>"],
        ['career-guidance', 'Career Guidance for ITI Students – Resume & Interview Tips', '', 'Resume format, interview tips and career options after ITI.',
         "<h2>Career options after ITI</h2><ul><li>Apprenticeship in government or private companies</li><li>Government jobs: Railways, PSUs, state electricity boards</li><li>Private company jobs and campus placement</li><li>Lateral entry to diploma (2nd year)</li><li>Self-employment in your trade</li></ul><h2>Resume tips</h2><ul><li>Keep it to one page: trade, ITI name, year, marks, skills and practical experience.</li><li>Mention machines, tools and software you can use.</li></ul><h2>Interview tips</h2><ul><li>Revise basic trade theory and safety rules.</li><li>Carry ITI marksheet, NCVT certificate, Aadhaar and photos.</li></ul><h2>Latest jobs</h2>[jobs limit=\"4\"]"],
        ['exam', 'ITI Exam Updates – Exam Date, Admit Card & Result', '', 'Latest ITI exam date, admit card and result updates.',
         "<p>Latest updates on <strong>NCVT ITI exam dates</strong>, <strong>admit cards</strong> and <strong>results</strong>. Always confirm on the official portal <a href=\"https://www.ncvtmis.gov.in/\" target=\"_blank\" rel=\"noopener\">NCVT MIS</a>.</p>[posts category=\"exam-update\" limit=\"20\"]"],
        ['blog', 'ITI Preparation Tips & Articles', '', 'Study tips, exam strategy and career articles for ITI students.',
         "<p>Tips and guides to help you prepare for the ITI exam and plan your career.</p>[posts category=\"article\" limit=\"20\"]"],
        ['about', 'About ITI Exam', '', 'ITI Exam is a free online platform for ITI students: trade-wise mock tests, previous year papers, study material, ITI jobs and apprenticeship updates.', about_html()],
        ['contact', 'Contact Us', '', "Contact the $site team by email or WhatsApp.", contact_html()],
        ['faq', 'Frequently Asked Questions', '', 'Answers to common questions about ITI exams, mock tests and jobs.',
         "<h3>Are the mock tests free?</h3><p>Yes, all mock tests and study material are free.</p><h3>Is the ITI exam conducted in CBT mode?</h3><p>Yes. Theory, Employability Skills and Workshop Calculation &amp; Science are computer based tests with MCQs.</p><h3>Where can I check my ITI result?</h3><p>Results are published on the official NCVT MIS portal. See our <a href=\"/exam/\">exam updates</a>.</p><h3>How do I apply for apprenticeship?</h3><p>Read our <a href=\"/apprenticeship/\">apprenticeship guide</a>.</p>"],
        ['privacy-policy', 'Privacy Policy', '', "How $site collects and uses information.",
         "<p>This website does not require you to create an account. We may collect anonymous usage data (pages visited, device type) through analytics to improve the site. We do not sell personal information.</p><p>If you contact us by email, we use your address only to reply.</p><h2>Advertising and cookies</h2><p>We use <strong>Google AdSense</strong> to show advertisements. Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this website or other websites. Google's use of advertising cookies enables it and its partners to serve ads to you based on your visits to this site and/or other sites on the Internet.</p><p>You can opt out of personalised advertising by visiting <a href=\"https://adssettings.google.com\" target=\"_blank\" rel=\"noopener\">Google Ads Settings</a>. To learn more, see <a href=\"https://policies.google.com/technologies/ads\" target=\"_blank\" rel=\"noopener\">how Google uses information from sites that use its services</a>.</p><p>You can also block or delete cookies in your browser settings.</p>"],
        ['terms', 'Terms & Conditions', '', "Terms of use for $site.",
         "<p>Content on this website is for educational purposes. You may use it for personal study. Copying content to other websites without permission is not allowed.</p><p>We try to keep information accurate, but always confirm exam and job details on the official websites.</p>"],
        ['disclaimer', 'Disclaimer', '', "Disclaimer for $site.",
         "<p>$site is not an official website of NCVT, DGT or any government body. Job and exam information is collected from official notifications and other public sources. Always check the official notification before applying.</p><p>We never charge money for any job or apprenticeship. Beware of fraud calls asking for payment.</p>"],
        ['exam/ncvt-iti-exam-date', 'NCVT ITI Exam Date ' . date('Y') . ' – How to Check the Schedule', 'exam-update', 'Where and how to check the official NCVT ITI CBT exam schedule.',
         "<p>The NCVT publishes the ITI CBT exam schedule on the <a href=\"https://www.ncvtmis.gov.in/\" target=\"_blank\" rel=\"noopener\">NCVT MIS portal</a>. Your ITI also displays the time table on its notice board.</p><h2>How to check</h2><ol><li>Open NCVT MIS and go to the Exam section.</li><li>Open the latest CBT exam schedule notice.</li><li>Note the dates for Trade Theory, Employability Skills and Workshop Calculation &amp; Science.</li></ol><p>Start revising with <a href=\"/mock-test/\">free mock tests</a>.</p>"],
        ['exam/iti-admit-card', 'ITI Admit Card – How to Download Hall Ticket', 'exam-update', 'Steps to download the ITI CBT admit card (hall ticket).',
         "<p>The ITI admit card (hall ticket) is released a few days before the CBT exam.</p><h2>Steps</h2><ol><li>Visit the NCVT MIS portal.</li><li>Open the Hall Ticket / Admit Card link.</li><li>Enter your roll number or registration details.</li><li>Download and print the admit card.</li></ol><p>Check your name, photo, trade and exam centre. Report any mistake to your ITI immediately.</p>"],
        ['exam/maharashtra-iti-result', 'Maharashtra ITI Result – How to Check & Download Marksheet', 'exam-update', 'How to check the Maharashtra ITI result and download the e-marksheet.',
         "<p>NCVT ITI results and e-marksheets are published on the official <a href=\"https://www.skillindiadigital.gov.in/\" target=\"_blank\" rel=\"noopener\">Skill India Digital (SIDH)</a> portal and on NCVT MIS. Log in with your PRN and date of birth.</p><ol><li>Open Skill India Digital or NCVT MIS → Results / Marksheet.</li><li>Enter your roll number and year.</li><li>Download the e-marksheet.</li></ol><p>For admission and DVET notices visit <a href=\"https://dvet.gov.in/\" target=\"_blank\" rel=\"noopener\">dvet.gov.in</a>.</p>"],
        ['exam/apprenticeship-registration-process', 'Apprenticeship Registration Process – Step by Step', 'exam-update', 'Step-by-step apprenticeship registration on the Apprenticeship India portal.',
         "<p>Follow these steps to register for apprenticeship after ITI.</p><ol><li>Open apprenticeshipindia.gov.in and click Register → Candidate.</li><li>Verify your mobile number and email.</li><li>Fill education details and upload documents.</li><li>Apply to openings in your trade.</li></ol><p>Read the full <a href=\"/apprenticeship/\">apprenticeship guide</a>.</p>"],
        ['blog/how-to-prepare-for-iti-cbt-exam', 'How to Prepare for ITI CBT Exam?', 'article', 'A simple plan to prepare for the ITI computer based test.',
         "<h2>1. Know the pattern</h2><p>The CBT has MCQs for Trade Theory, Employability Skills and Workshop Calculation &amp; Science. There is no negative marking in most papers, so attempt every question.</p><h2>2. Make a daily plan</h2><ul><li>45 minutes theory notes</li><li>30 minutes mock test</li><li>15 minutes revising wrong answers</li></ul><h2>3. Practise on a computer</h2><p>Take <a href=\"/mock-test/\">online mock tests</a> so you get used to the screen and the timer.</p><h2>4. Solve previous papers</h2><p>Many questions repeat. Solve <a href=\"/previous-papers/\">previous year papers</a> in the last two weeks.</p>"],
        ['blog/important-topics-employability-skills', 'Important Topics for Employability Skills', 'article', 'High-weightage Employability Skills topics for the ITI exam.',
         "<p>Employability Skills is common for every trade, so it is an easy subject to score in.</p><h2>Important topics</h2><ul><li>Communication skills and listening</li><li>Basic computer and internet</li><li>Entrepreneurship and government schemes</li><li>Occupational safety and first aid</li><li>Labour welfare and social security</li><li>Quality tools and productivity</li></ul><p>Read <a href=\"/employability-skills/\">Employability Skills notes</a>.</p>"],
        ['blog/ncvt-iti-exam-pattern-syllabus', 'NCVT ITI Exam Pattern and Syllabus', 'article', 'Subject-wise ITI CBT exam pattern and where to find the syllabus.',
         "<h2>Subjects</h2><ul><li>Trade Theory (CBT)</li><li>Workshop Calculation &amp; Science (CBT)</li><li>Engineering Drawing (for engineering trades)</li><li>Employability Skills (CBT)</li><li>Trade Practical (at ITI)</li></ul><p>Trade-wise modules are listed on each <a href=\"/trades/\">trade page</a> and in our <a href=\"/syllabus/\">syllabus section</a>.</p>"],
        ['blog/tips-to-score-high-in-iti-exams', 'Tips to Score High in ITI Exams', 'article', 'Practical tips that help ITI students score better marks.',
         "<ul><li>Revise safety, tools and measuring instruments – they are asked every year.</li><li>Learn formulas for Workshop Calculation on a single sheet.</li><li>Attempt one full mock test daily for the last 15 days.</li><li>Do not leave any question blank.</li><li>Sleep well before the exam and reach the centre early.</li></ul><p>Start with a <a href=\"/mock-test/\">free mock test</a> today.</p>"],
    ];
    foreach ($pages as $i => [$slug, $title, $cat, $excerpt, $html]) {
        $exists->execute([$slug]);
        if ($exists->fetchColumn()) continue;
        $t = date('Y-m-d H:i:s', strtotime('-' . $i . ' hours'));
        $ins->execute([$slug, $title, $html, $excerpt, $cat, $excerpt, 'published', $t, $t]);
    }
}


function about_html(): string
{
    return <<<'HTML'
<p><strong>ITI Exam</strong> (ITIExam.com) is a free online platform made for <strong>ITI students across India</strong>. We help you prepare for the <strong>NCVT / SCVT ITI CBT exam</strong>, find the latest <strong>ITI jobs</strong> and get an <strong>apprenticeship</strong> – all in one place, in simple language, on your mobile.</p>
<p>Our motto is <strong>Learn • Test • Get Jobs</strong>.</p>

<h2>What you get on ITI Exam</h2>
<ul>
<li><strong>ITI Mock Tests</strong> – trade-wise and module-wise online tests in the real CBT pattern, with a timer.</li>
<li><strong>Previous Year Question Papers</strong> – NCVT ITI papers with answers for 1st and 2nd year.</li>
<li><strong>Study Material</strong> – module-wise theory notes, MCQ question bank, syllabus and model papers.</li>
<li><strong>Employability Skills</strong> – notes and MCQs for this common subject of every trade.</li>
<li><strong>ITI Jobs</strong> – government jobs, private company jobs, campus placement and walk-in interviews for ITI pass candidates.</li>
<li><strong>Apprenticeship</strong> – NAPS / NATS guides, registration steps and company-wise openings.</li>
<li><strong>Exam updates</strong> – ITI exam date, admit card and result information.</li>
</ul>

<h2>Trades we cover</h2>
<p>COPA, Electrician, Fitter, Turner, Welder, Mechanic Motor Vehicle (MMV), Plumber, Wireman, Machinist, Diesel Mechanic and many more ITI trades. We add new trades and questions regularly.</p>

<h2>Who is this website for?</h2>
<ul>
<li>ITI 1st year and 2nd year students preparing for the CBT exam</li>
<li>ITI pass candidates looking for jobs or apprenticeship</li>
<li>Students in Maharashtra (DVET) and every other state following the NCVT syllabus</li>
<li>Instructors who want ready practice material for their class</li>
</ul>

<h2>Why students trust us</h2>
<ul>
<li><strong>100% free</strong> – no sign-up and no payment needed to study.</li>
<li><strong>Exam-focused</strong> – questions follow the latest NCVT syllabus and CBT pattern.</li>
<li><strong>Mobile first</strong> – the website is fast and easy to use on any phone.</li>
<li><strong>Verified job updates</strong> – every job links to its official notification and apply page.</li>
</ul>

<h2>आमच्याबद्दल (मराठी)</h2>
<p>ITI Exam ही ITI विद्यार्थ्यांसाठी पूर्णपणे मोफत वेबसाइट आहे. येथे सर्व ट्रेडसाठी मॉक टेस्ट, मागील वर्षांचे प्रश्नपत्रिका, नोट्स, ITI नोकऱ्या आणि अप्रेंटिसशिपची माहिती मिळते.</p>

<h2>हमारे बारे में (हिंदी)</h2>
<p>ITI Exam आईटीआई छात्रों के लिए पूरी तरह मुफ्त वेबसाइट है। यहाँ सभी ट्रेड के मॉक टेस्ट, पिछले वर्ष के प्रश्न पत्र, नोट्स, आईटीआई जॉब्स और अप्रेंटिसशिप की जानकारी मिलती है।</p>

<h2>Important note</h2>
<p>ITI Exam is an independent educational website. It is not connected to NCVT, DGT, DVET or any government department. Always confirm exam dates, results and job details on the official websites. We never ask for money for any job or apprenticeship.</p>

<h2>Contact us</h2>
[contact]
[whatsapp]
HTML;
}

function contact_html(): string
{
    return <<<'HTML'
<p>Have a question, found a mistake in a question, or want us to add your trade? We are happy to help.</p>
[contact]
<h2>Before you write</h2>
<ul>
<li>For exam dates, admit cards and results, also check the official <a href="https://www.ncvtmis.gov.in/" target="_blank" rel="noopener">NCVT MIS</a> portal.</li>
<li>For job applications, use the apply link given on each job page.</li>
<li>Mention your trade and year so we can reply faster.</li>
</ul>
<p>We usually reply within 2 working days.</p>
[whatsapp]
HTML;
}

/**
 * Runs once when an older installation is upgraded: adds missing starter pages,
 * points old menu links to pages that exist, and refreshes untouched About/Contact pages.
 */
function upgrade_content(): void
{
    $pdo = db();
    seed_pages();
    seed_home_lists();

    $remap = ['/study-material/notes/' => '/study-material/', '/study-material/mcq/' => '/mock-test/', '/study-material/syllabus/' => '/syllabus/',
              '/exam/date/' => '/exam/ncvt-iti-exam-date/', '/exam/admit-card/' => '/exam/iti-admit-card/', '/exam/result/' => '/exam/maharashtra-iti-result/',
              '/exam/pattern/' => '/blog/ncvt-iti-exam-pattern-syllabus/', '/apprenticeship/how-to-apply/' => '/apprenticeship/'];
    $up = $pdo->prepare('UPDATE menu_items SET url = ? WHERE url = ?');
    foreach ($remap as $old => $new) $up->execute([$new, $old]);
    $gone = ['/mock-test/trade-theory/', '/mock-test/employability-skills/', '/mock-test/workshop-calculation-science/', '/mock-test/engineering-drawing/',
             '/previous-papers/1st-year/', '/previous-papers/2nd-year/', '/previous-papers/cbt/', '/employability-skills/1st-year/', '/employability-skills/2nd-year/', '/videos/'];
    $del = $pdo->prepare('DELETE FROM menu_items WHERE url = ? AND parent_id <> 0');
    foreach ($gone as $u) $del->execute([$u]);

    // Refresh About / Contact only if they still have the old starter text
    $pg = $pdo->prepare('SELECT id, content FROM pages WHERE slug = ?');
    $set = $pdo->prepare('UPDATE pages SET title = ?, content = ?, excerpt = ?, meta_description = ?, updated_at = ? WHERE id = ?');
    $pg->execute(['about']);
    if (($r = $pg->fetch()) && str_contains($r['content'], 'Our goal is simple') && strlen($r['content']) < 700) {
        $ex = 'ITI Exam is a free online platform for ITI students: trade-wise mock tests, previous year papers, study material, ITI jobs and apprenticeship updates.';
        $set->execute(['About ITI Exam', about_html(), $ex, $ex, now(), $r['id']]);
    }
    $pg->execute(['contact']);
    if (($r = $pg->fetch()) && str_contains($r['content'], 'We usually reply within 2 working days') && strlen($r['content']) < 400) {
        $set->execute(['Contact Us', contact_html(), 'Contact the ITI Exam team by email or WhatsApp.', 'Contact the ITI Exam team by email or WhatsApp.', now(), $r['id']]);
    }
    if (setting('contact_email', "\0") === "\0") setting_set('contact_email', 'contact@itiexam.com');
    // Mock Test page: also list the uploaded online tests
    $mt = $pdo->prepare("SELECT id, content FROM pages WHERE slug = 'mock-test'");
    $mt->execute();
    if (($r = $mt->fetch()) && !str_contains($r['content'], '[tests')) {
        $pdo->prepare('UPDATE pages SET content = ?, updated_at = ? WHERE id = ?')->execute([str_replace('<h2>Select your trade</h2>', '<h2>Latest online tests</h2>[tests limit="12"]<h2>Select your trade</h2>', $r['content']), now(), $r['id']]);
    }
    if (setting('home_title') === 'ITI Exam 2026: Mock Test, Previous Papers, Study Material | ITIExam.com') {
        setting_set('home_title', 'ITI Exam ' . date('Y') . ': Free ITI Mock Test, Question Paper PDF & Jobs | ITI Exam');
    }
}


/** Editable homepage lists (Admin → Menus & Links → Homepage …). Added once. */
function seed_home_lists(): void
{
    $pdo = db();
    if ((int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE location LIKE 'home_%'")->fetchColumn() > 0) return;
    $ins = $pdo->prepare('INSERT INTO menu_items (location, parent_id, label, url, subtitle, icon, sort_order) VALUES (?, 0, ?, ?, ?, ?, ?)');
    $lists = [
        'home_quick' => [['Mock Tests', '/mock-test/', 'Trade & Module-wise', 'doc'], ['Previous Papers', '/previous-papers/', 'With Solutions', 'clip'],
            ['Employability Skills', '/employability-skills/', '1st Year & 2nd Year', 'target'], ['Study Material', '/study-material/', 'Notes, PDFs, MCQs', 'book'],
            ['Jobs & Career', '/jobs/', 'Latest Job Updates', 'brief'], ['Apprenticeship', '/apprenticeship/', 'NAPS / NATS', 'cap'], ['All ITI Trades', '/trades/', 'Explore Now', 'grid']],
        'home_career' => [['Government Jobs', '/jobs/government/', 'ITI Recruitment, Sarkari Naukri', 'bank'], ['Private Company Jobs', '/jobs/private/', 'Latest ITI Job Vacancies', 'brief'],
            ['Campus Placement', '/jobs/campus/', 'ITI Campus Drive & Walk-in', 'users'], ['Career Guidance', '/career-guidance/', 'Resume, Interview Tips, Preparation', 'target']],
        'home_apprentice' => [['NAPS Apprenticeship', '/apprenticeship/naps/', 'National Apprenticeship Promotion Scheme', 'cap'], ['NATS Apprenticeship', '/apprenticeship/nats/', 'National Apprenticeship Training Scheme', 'cap'],
            ['ITI Apprenticeship', '/apprenticeship/', 'Apply Online, Eligibility & Process', 'doc'], ['Company-wise Openings', '/jobs/apprentice/', 'Latest Apprenticeship Opportunities', 'building']],
        'home_study' => [['Theory Notes', '/study-material/', 'Module-wise PDF Notes', 'pdf'], ['MCQ Question Bank', '/mock-test/', 'Questions with Answers', 'help'],
            ['Previous Year Papers', '/previous-papers/', 'Solved Papers with Explanation', 'doc'], ['Practical Notes', '/practical/', 'Step-by-step Guides', 'flask'],
            ['Syllabus & Curriculum', '/syllabus/', 'Latest NCVT Syllabus', 'open'], ['Employability Skills', '/employability-skills/', '1st & 2nd Year', 'target']],
    ];
    foreach ($lists as $loc => $rows) foreach ($rows as $i => [$l, $u, $sub, $ic]) $ins->execute([$loc, $l, $u, $sub, $ic, $i]);
}
