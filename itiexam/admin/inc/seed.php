<?php
/* Starter data written once by setup.php. Everything here can be edited or deleted from the admin panel. */
function seed_site(): void
{
    $pdo = db();
    if ((int)$pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() === 0) seed_menus();
    if ((int)$pdo->query('SELECT COUNT(*) FROM trades')->fetchColumn() === 0) seed_trades();
    $defaults = [
        'site_description' => 'Free ITI mock tests, NCVT ITI previous year question papers, Employability Skills MCQ, study material PDF, ITI jobs, apprenticeship (NAPS/NATS), admit card & result updates for COPA, Electrician, Fitter, Turner, Welder & all ITI trades.',
        'home_title' => 'ITI Exam 2026: Mock Test, Previous Papers, Study Material | ITIExam.com',
        'home_keywords' => 'ITI exam, ITI mock test, ITI online test, ITI CBT exam, NCVT ITI exam 2026, ITI previous year question paper, Employability Skills MCQ, COPA mock test, ITI study material, ITI jobs, ITI apprenticeship, NAPS, NATS, ITI result, Maharashtra ITI, DVET',
        'footer_about' => 'Online Mock Tests, Previous Year Questions, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.',
        'social_youtube' => '', 'social_facebook' => '', 'social_telegram' => '', 'social_whatsapp' => '',
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
        ['Mock Tests', '/mock-test/', [['Trade Theory', '/mock-test/trade-theory/'], ['Employability Skills', '/mock-test/employability-skills/'], ['Workshop Calc. & Science', '/mock-test/workshop-calculation-science/'], ['Engineering Drawing', '/mock-test/engineering-drawing/']]],
        ['Previous Papers', '/previous-papers/', [['1st Year Papers', '/previous-papers/1st-year/'], ['2nd Year Papers', '/previous-papers/2nd-year/']]],
        ['Employability Skills', '/employability-skills/', [['1st Year', '/employability-skills/1st-year/'], ['2nd Year', '/employability-skills/2nd-year/']]],
        ['Study Material', '/study-material/', [['Theory Notes', '/study-material/notes/'], ['MCQ Bank', '/study-material/mcq/'], ['Syllabus', '/study-material/syllabus/']]],
        ['Jobs', '/jobs/', [['Government Jobs', '/jobs/government/'], ['Private Jobs', '/jobs/private/'], ['Campus Placement', '/jobs/campus/']]],
        ['Apprenticeship', '/apprenticeship/', [['NAPS', '/apprenticeship/naps/'], ['NATS', '/apprenticeship/nats/']]],
        ['Exam Info', '/exam/', [['Exam Date', '/exam/date/'], ['Admit Card', '/exam/admit-card/'], ['Result', '/exam/result/']]],
        ['About', '/about/', [['Contact', '/contact/']]],
    ];
    foreach ($header as $i => [$l, $u, $kids]) {
        $pid = $add('header', $l, $u, $i);
        foreach ($kids as $j => [$cl, $cu]) $add('header', $cl, $cu, $j, $pid);
    }
    foreach ([['Home', '/'], ['All Trades', '/trades/'], ['Mock Tests', '/mock-test/'], ['Previous Papers', '/previous-papers/'], ['Employability Skills', '/employability-skills/'],
              ['Study Material', '/study-material/'], ['Jobs', '/jobs/'], ['Apprenticeship', '/apprenticeship/'], ['Exam Info', '/exam/'], ['About Us', '/about/']] as $i => [$l, $u]) $add('footer_quick', $l, $u, $i);
    foreach ([['NCVT MIS', 'https://www.ncvtmis.gov.in/'], ['DVET Maharashtra', 'https://dvet.gov.in/'], ['Apprenticeship Portal', 'https://www.apprenticeshipindia.gov.in/'],
              ['ITI Admission', 'https://admission.dvet.gov.in/'], ['Bharat Skills', 'https://bharatskills.gov.in/'], ['NIMI', 'https://nimi.gov.in/'], ['FAQ', '/faq/'], ['Contact Us', '/contact/']] as $i => [$l, $u]) $add('footer_resources', $l, $u, $i);
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
