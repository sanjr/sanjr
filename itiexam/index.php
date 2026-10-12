<?php
require __DIR__ . '/includes/functions.php';

$faqs = [
    ['Are ITI mock tests on ITI Exam free?', 'Yes. All ITI mock tests, previous year question papers and study material are 100% free for students of every trade. No sign-up is needed.'],
    ['Is the ITI exam conducted in CBT mode?', 'Yes. NCVT ITI Trade Theory, Employability Skills and Workshop Calculation & Science exams are Computer Based Tests (CBT) with multiple choice questions. Our mock tests follow the same pattern.'],
    ['Which ITI trades are covered?', 'COPA, Electrician, Fitter, Turner, Welder, MMV, Plumber, Wireman, Machinist, Diesel Mechanic and more, for both 1st year and 2nd year.'],
    ['Where can I check my ITI result?', 'NCVT ITI results and marksheets are published on the official Skill India Digital (SIDH) portal and NCVT MIS. Check our Exam Updates section for the latest information.'],
    ['How can I apply for ITI apprenticeship?', 'Register on the official Apprenticeship India portal (NAPS). Our step-by-step apprenticeship guide covers registration, eligibility, documents and company-wise openings.'],
    ['How do I get daily ITI job alerts?', 'Open the ITI Jobs page for the latest government, private and apprentice jobs, and join our WhatsApp group to get new jobs and exam updates on your phone.'],
];

$ld = json_decode(str_replace('https://www.itiexam.com', SITE_URL, <<<'JSON'
{
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": "https://www.itiexam.com/#org",
        "name": "ITIExam.com",
        "alternateName": "ITI Exam",
        "url": "https://www.itiexam.com/",
        "logo": { "@type": "ImageObject", "url": "https://www.itiexam.com/assets/img/logo.png", "width": 600, "height": 600 },
        "image": "https://www.itiexam.com/assets/img/og-image.jpg",
        "slogan": "Learn • Test • Get Jobs",
        "description": "Free ITI exam preparation for all NCVT trades: mock tests, previous year question papers, study material, ITI jobs and apprenticeship updates."
      },
      {
        "@type": "WebSite",
        "@id": "https://www.itiexam.com/#website",
        "url": "https://www.itiexam.com/",
        "name": "ITIExam.com",
        "alternateName": ["ITI Exam", "ITIExam", "ITI Exam Preparation"],
        "inLanguage": "en-IN",
        "publisher": { "@id": "https://www.itiexam.com/#org" },
        "potentialAction": {
          "@type": "SearchAction",
          "target": "https://www.itiexam.com/search/?q={search_term_string}",
          "query-input": "required name=search_term_string"
        }
      },
      {
        "@type": "EducationalOrganization",
        "name": "ITIExam.com",
        "url": "https://www.itiexam.com/",
        "description": "Online ITI exam preparation platform with mock tests, previous year papers, study material, jobs and apprenticeship updates."
      },
      {
        "@type": "ItemList",
        "name": "Popular ITI Trades",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "name": "COPA", "url": "https://www.itiexam.com/trades/copa/" },
          { "@type": "ListItem", "position": 2, "name": "Electrician", "url": "https://www.itiexam.com/trades/electrician/" },
          { "@type": "ListItem", "position": 3, "name": "Fitter", "url": "https://www.itiexam.com/trades/fitter/" },
          { "@type": "ListItem", "position": 4, "name": "Turner", "url": "https://www.itiexam.com/trades/turner/" },
          { "@type": "ListItem", "position": 5, "name": "Welder", "url": "https://www.itiexam.com/trades/welder/" },
          { "@type": "ListItem", "position": 6, "name": "MMV", "url": "https://www.itiexam.com/trades/mmv/" },
          { "@type": "ListItem", "position": 7, "name": "Plumber", "url": "https://www.itiexam.com/trades/plumber/" },
          { "@type": "ListItem", "position": 8, "name": "Wireman", "url": "https://www.itiexam.com/trades/wireman/" }
        ]
      },
      {
        "@type": "FAQPage",
        "mainEntity": [
          {
            "@type": "Question",
            "name": "Are ITI mock tests on ITIExam.com free?",
            "acceptedAnswer": { "@type": "Answer", "text": "Yes. All ITI mock tests, previous year papers and study material on ITIExam.com are 100% free for students of every trade." }
          },
          {
            "@type": "Question",
            "name": "Is the ITI exam conducted in CBT mode?",
            "acceptedAnswer": { "@type": "Answer", "text": "Yes. NCVT ITI theory, Employability Skills and Workshop Calculation & Science exams are conducted as Computer Based Tests (CBT) with multiple choice questions. Our mock tests follow the same pattern." }
          },
          {
            "@type": "Question",
            "name": "Which ITI trades are covered?",
            "acceptedAnswer": { "@type": "Answer", "text": "30+ trades including COPA, Electrician, Fitter, Turner, Welder, MMV, Plumber, Wireman, Machinist, Diesel Mechanic and more, for both 1st and 2nd year." }
          },
          {
            "@type": "Question",
            "name": "How can I apply for ITI apprenticeship?",
            "acceptedAnswer": { "@type": "Answer", "text": "ITI pass students can register on the official Apprenticeship India portal (NAPS). Our step-by-step guide explains registration, eligibility, documents and how to apply for company-wise openings." }
          }
        ]
      }
    ]
  }
JSON), true);
// Social profiles and email come from Admin → Settings; trade list follows the published trades
foreach ($ld['@graph'] as &$node) {
    if ($node['@type'] === 'FAQPage') {
        $node['mainEntity'] = array_map(fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs);
    }
    if ($node['@type'] === 'Organization') {
        $node['email'] = contact_email();
        $node['contactPoint'] = ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => contact_email(), 'areaServed' => 'IN', 'availableLanguage' => ['English', 'Hindi', 'Marathi']];
        $same = array_values(array_filter([setting('social_youtube'), setting('social_facebook'), setting('social_telegram'), setting('social_whatsapp'), whatsapp_group()]));
        if ($same) $node['sameAs'] = $same;
    }
    if ($node['@type'] === 'ItemList') {
        $node['itemListElement'] = [];
        foreach (trades(true) as $i => $t) {
            $node['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t['name'], 'url' => SITE_URL . trade_url($t)];
        }
    }
}
unset($node);

$mockTests = db()->query("SELECT i.*, t.name AS tname, t.slug AS tslug FROM trade_items i JOIN trades t ON t.id = i.trade_id
    WHERE i.section = 'mock_tests' AND t.status = 'published' ORDER BY i.is_new DESC, i.id DESC LIMIT 4")->fetchAll();
$homeJobs = latest_jobs(4);
$examUpdates = latest_posts('exam-update', 4);
$articles = latest_posts('article', 4);
$tradeCount = count(trades());

$meta = [
    'title' => setting('home_title') ?: 'ITI Exam ' . date('Y') . ': Free ITI Mock Test, Question Paper PDF & Jobs | ITI Exam',
    'description' => setting('site_description'),
    'keywords' => setting('home_keywords'),
    'path' => '/',
    'jsonld' => [$ld],
];
require __DIR__ . '/includes/header.php';
?>

  <main id="main">
    <!-- ===== Hero ===== -->
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-copy">
          <h1><span class="h1-sm">Your Complete Platform for</span> ITI Exam <span class="grad">Preparation</span></h1>
          <p class="lead">Online <strong>ITI Mock Tests</strong>, <strong>Previous Year Questions</strong>, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.</p>

          <form class="search" id="hero-search" role="search" action="/search/" method="get">
            <label for="q" class="sr-only">Search ITI trade, subject or topic</label>
            <svg class="ic"><use href="#i-search"/></svg>
            <input id="q" name="q" type="search" placeholder="Search Trade, Subject or Topic (e.g. COPA, Electrician, MS Word, Employability Skills...)" autocomplete="off">
            <button type="submit"><svg class="ic"><use href="#i-search"/></svg><span>Search</span></button>
          </form>

          <ul class="hero-stats">
            <li><a href="/trades/"><span class="si c-purple"><?= icon('list') ?></span><b><?= $tradeCount ?>+</b><small>ITI Trades</small></a></li>
            <li><a href="/mock-test/"><span class="si c-blue"><?= icon('doc') ?></span><b><?= e(setting('stat_questions') ?: '10,000+') ?></b><small>Practice Questions</small></a></li>
            <li><a href="/mock-test/"><span class="si c-orange"><?= icon('clip') ?></span><b><?= e(setting('stat_tests') ?: '100+') ?></b><small>Mock Tests</small></a></li>
            <li><a href="/jobs/"><span class="si c-green"><?= icon('brief') ?></span><b>Latest</b><small>Job Updates</small></a></li>
            <li><a href="/apprenticeship/"><span class="si c-pink"><?= icon('users') ?></span><b>Apprenticeship</b><small>NAPS / NATS</small></a></li>
            <li><a href="/study-material/"><span class="si c-yellow"><?= icon('book') ?></span><b>Study Material</b><small>Notes, PDFs, MCQs</small></a></li>
          </ul>
        </div>

        <div class="hero-art" aria-hidden="true">
          <p class="quote">“Practice<br>Learn<br>Improve<br>Succeed”</p>
          <div class="art-circle">
            <div class="art-book"><span>ITI</span></div>
            <div class="art-badge b1">✔ 25/25</div>
            <div class="art-badge b2">🏆 Top Rank</div>
            <div class="art-thumb">👍</div>
          </div>
        </div>

        <aside class="quick-links" aria-label="Quick links">
          <a href="/mock-test/"><span class="qi c-blue"><svg class="ic"><use href="#i-doc"/></svg></span><span><b>Mock Tests</b><small>Trade &amp; Module-wise</small></span></a>
          <a href="/previous-papers/"><span class="qi c-green"><svg class="ic"><use href="#i-clip"/></svg></span><span><b>Previous Papers</b><small>With Solutions</small></span></a>
          <a href="/employability-skills/"><span class="qi c-orange"><svg class="ic"><use href="#i-target"/></svg></span><span><b>Employability Skills</b><small>1st Year &amp; 2nd Year</small></span></a>
          <a href="/study-material/"><span class="qi c-purple"><svg class="ic"><use href="#i-book"/></svg></span><span><b>Study Material</b><small>Notes, PDFs, MCQs</small></span></a>
          <a href="/jobs/"><span class="qi c-red"><svg class="ic"><use href="#i-brief"/></svg></span><span><b>Jobs &amp; Career</b><small>Latest Job Updates</small></span></a>
          <a href="/apprenticeship/"><span class="qi c-teal"><svg class="ic"><use href="#i-cap"/></svg></span><span><b>Apprenticeship</b><small>NAPS / NATS</small></span></a>
          <a href="/trades/"><span class="qi c-navy"><svg class="ic"><use href="#i-grid"/></svg></span><span><b>All ITI Trades</b><small>Explore Now</small></span></a>
        </aside>
      </div>
    </section>

    <div class="container"><?= ad('home_top', 'wide') ?></div>

    <!-- ===== Popular Trades ===== -->
    <section class="section" aria-labelledby="trades-h">
      <div class="container">
        <div class="sec-head">
          <h2 id="trades-h">Popular ITI Trades</h2>
          <a href="/trades/" class="view-all">View All Trades <svg class="ic"><use href="#i-arrow"/></svg></a>
        </div>
        <div class="trades">
<?php foreach (trades(true) as $t): ?>
          <a class="trade" href="<?= e(trade_url($t)) ?>" style="--tbg:<?= e($t['card_color']) ?>"><?php if ($t['image']): ?><img class="timg" src="<?= e(upload_url($t['image'])) ?>" alt="<?= e($t['name']) ?> ITI trade" width="64" height="64" loading="lazy"><?php else: ?><span class="emo" aria-hidden="true"><?= e($t['emoji'] ?: '🛠️') ?></span><?php endif; ?><h3><?= e($t['name']) ?></h3><p><?= e($t['short_desc']) ?></p><span class="go"><?= icon('arrow') ?></span></a>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ===== Mock Tests / Jobs / Apprenticeship ===== -->
    <section class="section pt0">
      <div class="container cols-3">
        <article class="card" aria-labelledby="mock-h">
          <div class="sec-head sm"><h2 id="mock-h">Latest Mock Tests</h2><a href="/mock-test/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <ul class="rows">
<?php foreach ($mockTests as $i => $m): $href = $m['url'] && $m['url'][0] !== '#' ? $m['url'] : '/trades/' . $m['tslug'] . '/#mock-tests'; ?>
            <li><a href="<?= e($href) ?>"<?= link_attrs($href) ?>><span class="ri <?= chip_color($i + 5) ?>"><?= icon($m['icon'] ?: 'clip') ?></span><span class="rt"><b><?= e($m['tname']) ?> – <?= e($m['title']) ?></b><small><?= $m['questions'] ? icon('doc') . (int)$m['questions'] . ' Questions ' : '' ?><?= $m['minutes'] ? icon('clock') . (int)$m['minutes'] . ' Minutes ' : '' ?><?= $m['is_new'] ? '<em class="new">New</em>' : '' ?></small></span><?= icon('chev-r', 'ic chev') ?></a></li>
<?php endforeach; if (!$mockTests): ?>
            <li class="muted-row">Mock tests will appear here once added from the admin panel.</li>
<?php endif; ?>
          </ul>
        </article>

        <article class="card" aria-labelledby="jobs-h">
          <div class="sec-head sm"><h2 id="jobs-h">Jobs &amp; Career</h2><a href="/jobs/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <ul class="rows">
            <li><a href="/jobs/government/"><span class="ri c-orange"><?= icon('bank') ?></span><span class="rt"><b>Government Jobs</b><small>ITI Recruitment, Sarkari Naukri</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/jobs/private/"><span class="ri c-red"><?= icon('brief') ?></span><span class="rt"><b>Private Company Jobs</b><small>Latest ITI Job Vacancies</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/jobs/campus/"><span class="ri c-green"><?= icon('users') ?></span><span class="rt"><b>Campus Placement</b><small>ITI Campus Drive &amp; Walk-in</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/career-guidance/"><span class="ri c-red"><?= icon('target') ?></span><span class="rt"><b>Career Guidance</b><small>Resume, Interview Tips, Preparation</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
          </ul>
        </article>

        <article class="card" aria-labelledby="app-h">
          <div class="sec-head sm"><h2 id="app-h">Apprenticeship</h2><a href="/apprenticeship/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <ul class="rows">
            <li><a href="/apprenticeship/naps/"><span class="ri c-blue"><?= icon('cap') ?></span><span class="rt"><b>NAPS Apprenticeship</b><small>National Apprenticeship Promotion Scheme</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/apprenticeship/nats/"><span class="ri c-purple"><?= icon('cap') ?></span><span class="rt"><b>NATS Apprenticeship</b><small>National Apprenticeship Training Scheme</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/apprenticeship/"><span class="ri c-green"><?= icon('doc') ?></span><span class="rt"><b>ITI Apprenticeship</b><small>Apply Online, Eligibility &amp; Process</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
            <li><a href="/jobs/apprentice/"><span class="ri c-orange"><?= icon('building') ?></span><span class="rt"><b>Company-wise Openings</b><small>Latest Apprenticeship Opportunities</small></span><?= icon('chev-r', 'ic chev') ?></a></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ===== Study Material + Exam Updates ===== -->
    <section class="section pt0">
      <div class="container cols-2-1">
        <div aria-labelledby="study-h">
          <div class="sec-head"><h2 id="study-h">Study Material</h2><a href="/study-material/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <div class="study">
            <a href="/study-material/" class="st"><span class="sti c-red"><?= icon('pdf') ?></span><h3>Theory Notes</h3><p>Module-wise PDF Notes</p></a>
            <a href="/mock-test/" class="st"><span class="sti c-purple"><?= icon('help') ?></span><h3>MCQ Question Bank</h3><p>Questions with Answers</p></a>
            <a href="/previous-papers/" class="st"><span class="sti c-blue"><?= icon('doc') ?></span><h3>Previous Year Papers</h3><p>Solved Papers with Explanation</p></a>
            <a href="/practical/" class="st"><span class="sti c-green"><?= icon('flask') ?></span><h3>Practical Notes</h3><p>Step-by-step Guides</p></a>
            <a href="/syllabus/" class="st"><span class="sti c-orange"><?= icon('open') ?></span><h3>Syllabus &amp; Curriculum</h3><p>Latest NCVT Syllabus</p></a>
            <a href="/employability-skills/" class="st"><span class="sti c-cyan"><?= icon('target') ?></span><h3>Employability Skills</h3><p>1st &amp; 2nd Year</p></a>
          </div>
        </div>

        <article class="card" aria-labelledby="upd-h">
          <div class="sec-head sm"><h2 id="upd-h">Exam Updates</h2><a href="/exam/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <ul class="rows upd">
<?php foreach ($examUpdates as $i => $u): $ts = strtotime($u['created_at']); ?>
            <li><a href="/<?= e($u['slug']) ?>/"><time class="date <?= ['d-blue', 'd-red', 'd-orange', 'd-teal'][$i % 4] ?>" datetime="<?= date('Y-m-d', $ts) ?>"><b><?= date('d', $ts) ?></b><?= strtoupper(date('M', $ts)) ?></time><span class="rt"><b><?= e($u['title']) ?></b><?php if ($u['excerpt']): ?><small><?= e(mb_strimwidth($u['excerpt'], 0, 60, '…')) ?></small><?php endif; ?></span><?= icon('chev-r', 'ic chev') ?></a></li>
<?php endforeach; if (!$examUpdates): ?>
            <li class="muted-row">No exam updates yet.</li>
<?php endif; ?>
          </ul>
        </article>
      </div>
    </section>

    <div class="container"><?= ad('home_middle', 'wide') ?></div>

    <!-- ===== Jobs + Articles ===== -->
    <section class="section pt0">
      <div class="container cols-2-1">
        <div aria-labelledby="jobop-h">
          <div class="sec-head"><h2 id="jobop-h">Latest Job Opportunities</h2><a href="/jobs/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <div class="jobs">
<?php foreach ($homeJobs as $j): ?>
            <a href="<?= e(job_url($j)) ?>" class="job"><?= employer_logo($j) ?><span><h3><?= e(company_name($j)) ?></h3><p class="role"><?= e($j['title']) ?></p><p class="muted"><?= e($j['trades']) ?></p><?php if ($j['last_date']): ?><p class="last">Last Date: <?= e(nice_date($j['last_date'])) ?></p><?php endif; ?></span><span class="go"><?= icon('arrow') ?></span></a>
<?php endforeach; ?>
          </div>
<?php if (!$homeJobs): ?>          <p class="card muted-row">New jobs will appear here soon. <a href="/jobs/">See all jobs</a></p>
<?php endif; ?>
        </div>

        <article class="card" aria-labelledby="art-h">
          <div class="sec-head sm"><h2 id="art-h">Recent Articles</h2><a href="/blog/" class="view-all">View All <?= icon('arrow') ?></a></div>
          <ul class="rows arts">
<?php foreach ($articles as $i => $a) echo '            ', post_row($a, $i), "\n"; if (!$articles): ?>
            <li class="muted-row">No articles yet.</li>
<?php endif; ?>
          </ul>
        </article>
      </div>
    </section>

    <!-- ===== SEO content + FAQ ===== -->
    <section class="section pt0" aria-labelledby="about-h">
      <div class="container cols-2-1">
        <div class="seo-text card">
          <h2 id="about-h">ITI Exam <?= date('Y') ?> – Free Mock Test, Question Paper &amp; Study Material</h2>
          <p><strong>ITI Exam</strong> is a free website for ITI students preparing for the <strong>NCVT / SCVT ITI CBT exam <?= date('Y') ?></strong>. Practise trade-wise <strong>ITI mock tests</strong> and <strong>ITI online tests</strong>, solve <strong>ITI previous year question papers with answers (PDF)</strong>, and revise with module-wise notes and <strong>MCQ question banks</strong> for Trade Theory, <strong>Employability Skills</strong>, Workshop Calculation &amp; Science and Engineering Drawing.</p>
          <h3>All ITI trades in one place</h3>
          <p>Find <a href="/trades/copa/">COPA MCQ</a>, <a href="/trades/electrician/">Electrician mock test</a>, <a href="/trades/fitter/">Fitter question paper</a>, Turner, Welder, MMV, Plumber, Wireman, Machinist and Diesel Mechanic material for <strong>1st year and 2nd year</strong>. Every trade page has a mock test, syllabus, notes, previous papers and important questions. See <a href="/trades/">all ITI trades</a>.</p>
          <h3>ITI jobs, apprenticeship and results</h3>
          <p>Get the latest <a href="/jobs/">ITI jobs</a> – <a href="/jobs/government/">government jobs</a>, <a href="/jobs/apprentice/">railway &amp; PSU apprentice</a>, private company and campus placement – plus <a href="/apprenticeship/">apprenticeship (NAPS / NATS)</a> guides and <a href="/exam/">ITI exam date, admit card and result</a> updates for every state, including Maharashtra DVET.</p>
          <p lang="mr">आयटीआय परीक्षा तयारीसाठी मोफत मॉक टेस्ट, प्रश्नपत्रिका, नोट्स आणि ITI नोकरी अपडेट्स – सर्व एका ठिकाणी.</p>
          <p lang="hi">आईटीआई परीक्षा की तैयारी के लिए मुफ्त मॉक टेस्ट, प्रश्न पत्र, नोट्स और आईटीआई जॉब अपडेट – सब एक जगह।</p>
        </div>
        <div class="card faq">
          <h2>Frequently Asked Questions</h2>
<?php foreach ($faqs as [$q, $ans]): ?>
          <details><summary><?= e($q) ?></summary><p><?= e($ans) ?></p></details>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ===== CTA band ===== -->
    <section class="cta">
      <div class="container cta-grid">
        <div class="cta-art" aria-hidden="true">🧑‍💻</div>
        <div class="cta-copy">
          <h2>Empowering ITI Students for a Brighter Future</h2>
          <p>Access free mock tests, study material, job updates and apprenticeship opportunities – all in one place.</p>
        </div>
        <span class="rocket" aria-hidden="true">🚀</span>
        <ul class="cta-stats">
          <li class="s-blue"><b><?= $tradeCount ?>+</b><small>ITI Trades</small></li>
          <li class="s-green"><b><?= e(setting('stat_questions') ?: '10,000+') ?></b><small>Practice Questions</small></li>
          <li class="s-orange"><b><?= e(setting('stat_students') ?: '1,00,000+') ?></b><small>Students Learning</small></li>
          <li class="s-purple"><b>100%</b><small>Free Access</small></li>
        </ul>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
