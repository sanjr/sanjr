<?php
require __DIR__ . '/includes/functions.php';

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
        "logo": "https://www.itiexam.com/assets/img/favicon.svg",
        "slogan": "Your ITI Exam Preparation Partner",
        "sameAs": [
          "https://www.youtube.com/@itiexam",
          "https://www.facebook.com/itiexam",
          "https://t.me/itiexam"
        ]
      },
      {
        "@type": "WebSite",
        "@id": "https://www.itiexam.com/#website",
        "url": "https://www.itiexam.com/",
        "name": "ITIExam.com",
        "inLanguage": "en-IN",
        "publisher": { "@id": "https://www.itiexam.com/#org" },
        "potentialAction": {
          "@type": "SearchAction",
          "target": "https://www.itiexam.com/search?q={search_term_string}",
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
// Trade list in structured data follows the trades the admin has published
foreach ($ld['@graph'] as &$node) {
    if ($node['@type'] === 'ItemList') {
        $node['itemListElement'] = [];
        foreach (trades(true) as $i => $t) {
            $node['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t['name'], 'url' => SITE_URL . trade_url($t)];
        }
    }
}
unset($node);

$meta = [
    'title' => setting('home_title', 'ITI Exam 2026: Mock Test, Previous Papers, Study Material | ITIExam.com'),
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

          <form class="search" id="hero-search" role="search" action="/search" method="get">
            <label for="q" class="sr-only">Search ITI trade, subject or topic</label>
            <svg class="ic"><use href="#i-search"/></svg>
            <input id="q" name="q" type="search" placeholder="Search Trade, Subject or Topic (e.g. COPA, Electrician, MS Word, Employability Skills...)" autocomplete="off">
            <button type="submit"><svg class="ic"><use href="#i-search"/></svg><span>Search</span></button>
          </form>

          <ul class="hero-stats">
            <li><span class="si c-purple"><svg class="ic"><use href="#i-list"/></svg></span><b>30+</b><small>ITI Trades</small></li>
            <li><span class="si c-blue"><svg class="ic"><use href="#i-doc"/></svg></span><b>10,000+</b><small>Practice Questions</small></li>
            <li><span class="si c-orange"><svg class="ic"><use href="#i-clip"/></svg></span><b>100+</b><small>Mock Tests</small></li>
            <li><span class="si c-green"><svg class="ic"><use href="#i-brief"/></svg></span><b>Latest</b><small>Job Updates</small></li>
            <li><span class="si c-pink"><svg class="ic"><use href="#i-users"/></svg></span><b>Apprenticeship</b><small>NAPS / NATS</small></li>
            <li><span class="si c-yellow"><svg class="ic"><use href="#i-book"/></svg></span><b>Study Material</b><small>Notes, PDFs, MCQs</small></li>
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
          <div class="sec-head sm"><h2 id="mock-h">Latest Mock Tests</h2><a href="/mock-test/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <ul class="rows">
            <li><a href="/mock-test/copa-computer-fundamentals-set-1/"><span class="ri c-green"><svg class="ic"><use href="#i-layout"/></svg></span><span class="rt"><b>COPA – Computer Fundamentals (Set 1)</b><small><svg class="ic"><use href="#i-doc"/></svg>25 Questions <svg class="ic"><use href="#i-clock"/></svg>30 Minutes <em class="new">New</em></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/mock-test/electrician-basic-electrical-set-1/"><span class="ri c-yellow"><svg class="ic"><use href="#i-bulb"/></svg></span><span class="rt"><b>Electrician – Basic Electrical (Set 1)</b><small><svg class="ic"><use href="#i-doc"/></svg>25 Questions <svg class="ic"><use href="#i-clock"/></svg>30 Minutes <em class="new">New</em></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/mock-test/fitter-fitting-tools-set-1/"><span class="ri c-indigo"><svg class="ic"><use href="#i-tool"/></svg></span><span class="rt"><b>Fitter – Fitting Tools (Set 1)</b><small><svg class="ic"><use href="#i-doc"/></svg>25 Questions <svg class="ic"><use href="#i-clock"/></svg>30 Minutes <em class="new">New</em></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/mock-test/employability-skills-communication-set-1/"><span class="ri c-teal"><svg class="ic"><use href="#i-chat"/></svg></span><span class="rt"><b>Employability Skills – Communication (Set 1)</b><small><svg class="ic"><use href="#i-doc"/></svg>25 Questions <svg class="ic"><use href="#i-clock"/></svg>30 Minutes <em class="new">New</em></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
          </ul>
        </article>

        <article class="card" aria-labelledby="jobs-h">
          <div class="sec-head sm"><h2 id="jobs-h">Jobs &amp; Career</h2><a href="/jobs/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <ul class="rows">
            <li><a href="/jobs/government/"><span class="ri c-orange"><svg class="ic"><use href="#i-bank"/></svg></span><span class="rt"><b>Government Jobs</b><small>ITI Recruitment, Sarkari Naukri</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/jobs/private/"><span class="ri c-red"><svg class="ic"><use href="#i-brief"/></svg></span><span class="rt"><b>Private Company Jobs</b><small>Latest ITI Job Vacancies</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/jobs/campus/"><span class="ri c-green"><svg class="ic"><use href="#i-users"/></svg></span><span class="rt"><b>Campus Placement</b><small>ITI Campus Drive &amp; Walk-in</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/career-guidance/"><span class="ri c-red"><svg class="ic"><use href="#i-target"/></svg></span><span class="rt"><b>Career Guidance</b><small>Resume, Interview Tips, Preparation</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
          </ul>
        </article>

        <article class="card" aria-labelledby="app-h">
          <div class="sec-head sm"><h2 id="app-h">Apprenticeship</h2><a href="/apprenticeship/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <ul class="rows">
            <li><a href="/apprenticeship/naps/"><span class="ri c-blue"><svg class="ic"><use href="#i-cap"/></svg></span><span class="rt"><b>NAPS Apprenticeship</b><small>National Apprenticeship Promotion Scheme</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/apprenticeship/nats/"><span class="ri c-purple"><svg class="ic"><use href="#i-cap"/></svg></span><span class="rt"><b>NATS Apprenticeship</b><small>National Apprenticeship Training Scheme</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/apprenticeship/how-to-apply/"><span class="ri c-green"><svg class="ic"><use href="#i-doc"/></svg></span><span class="rt"><b>ITI Apprenticeship</b><small>Apply Online, Eligibility &amp; Process</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/apprenticeship/company-wise/"><span class="ri c-orange"><svg class="ic"><use href="#i-building"/></svg></span><span class="rt"><b>Company-wise Openings</b><small>Latest Apprenticeship Opportunities</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ===== Study Material + Exam Updates ===== -->
    <section class="section pt0">
      <div class="container cols-2-1">
        <div aria-labelledby="study-h">
          <div class="sec-head"><h2 id="study-h">Study Material</h2><a href="/study-material/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <div class="study">
            <a href="/study-material/notes/" class="st"><span class="sti c-red"><svg class="ic"><use href="#i-pdf"/></svg></span><h3>Theory Notes</h3><p>Module-wise PDF Notes</p></a>
            <a href="/study-material/mcq/" class="st"><span class="sti c-purple"><svg class="ic"><use href="#i-help"/></svg></span><h3>MCQ Question Bank</h3><p>1000+ Questions with Answers</p></a>
            <a href="/previous-papers/" class="st"><span class="sti c-blue"><svg class="ic"><use href="#i-doc"/></svg></span><h3>Previous Year Papers</h3><p>Solved Papers with Explanation</p></a>
            <a href="/study-material/practical/" class="st"><span class="sti c-green"><svg class="ic"><use href="#i-flask"/></svg></span><h3>Practical Notes</h3><p>Step-by-step Guides</p></a>
            <a href="/study-material/syllabus/" class="st"><span class="sti c-orange"><svg class="ic"><use href="#i-open"/></svg></span><h3>Syllabus &amp; Curriculum</h3><p>Latest NCVT Syllabus</p></a>
            <a href="/study-material/model-papers/" class="st"><span class="sti c-cyan"><svg class="ic"><use href="#i-layout"/></svg></span><h3>Model Question Papers</h3><p>Exam Pattern &amp; Blueprint</p></a>
          </div>
        </div>

        <article class="card" aria-labelledby="upd-h">
          <div class="sec-head sm"><h2 id="upd-h">Exam Updates</h2><a href="/exam/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <ul class="rows upd">
            <li><a href="/exam/date/"><time class="date d-blue" datetime="2026-10-15"><b>15</b>OCT</time><span class="rt"><b>NCVT ITI Exam Date 2026 (Expected)</b><small>Check latest exam schedule and details</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/exam/admit-card/"><time class="date d-red" datetime="2026-10-10"><b>10</b>OCT</time><span class="rt"><b>ITI Admit Card Latest Update</b><small>Download admit card information</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/exam/result/maharashtra/"><time class="date d-orange" datetime="2026-10-05"><b>05</b>OCT</time><span class="rt"><b>Maharashtra ITI Result 2026</b><small>Check result and merit list</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/apprenticeship/registration-process/"><time class="date d-teal" datetime="2026-09-28"><b>28</b>SEP</time><span class="rt"><b>Apprenticeship Registration Process</b><small>Step-by-step registration guide</small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ===== Jobs + Articles ===== -->
    <section class="section pt0">
      <div class="container cols-2-1">
        <div aria-labelledby="jobop-h">
          <div class="sec-head"><h2 id="jobop-h">Latest Job Opportunities</h2><a href="/jobs/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <div class="jobs">
            <a href="/jobs/bel-iti-apprentice/" class="job"><span class="jl" style="--c:#1d4ed8">BEL</span><span><h3>BEL</h3><p class="role">ITI Apprentice</p><p class="muted">Multiple Trades</p><p class="last">Last Date: 20 Oct 2026</p></span><span class="go"><svg class="ic"><use href="#i-arrow"/></svg></span></a>
            <a href="/jobs/indian-railway-iti-apprentice/" class="job"><span class="jl" style="--c:#dc2626">IR</span><span><h3>Indian Railway</h3><p class="role">ITI Apprentice</p><p class="muted">Various Trades</p><p class="last">Last Date: 25 Oct 2026</p></span><span class="go"><svg class="ic"><use href="#i-arrow"/></svg></span></a>
            <a href="/jobs/tata-motors-iti-trainee/" class="job"><span class="jl" style="--c:#0b5cab">TM</span><span><h3>Tata Motors</h3><p class="role">ITI Trainee</p><p class="muted">Fitter, Electrician, MMV</p><p class="last">Last Date: 18 Oct 2026</p></span><span class="go"><svg class="ic"><use href="#i-arrow"/></svg></span></a>
            <a href="/jobs/lt-iti-apprentice/" class="job"><span class="jl" style="--c:#0f172a">L&amp;T</span><span><h3>L&amp;T</h3><p class="role">ITI Apprentice</p><p class="muted">COPA, Fitter, Electrician</p><p class="last">Last Date: 22 Oct 2026</p></span><span class="go"><svg class="ic"><use href="#i-arrow"/></svg></span></a>
          </div>
        </div>

        <article class="card" aria-labelledby="art-h">
          <div class="sec-head sm"><h2 id="art-h">Recent Articles</h2><a href="/blog/" class="view-all">View All <svg class="ic"><use href="#i-arrow"/></svg></a></div>
          <ul class="rows arts">
            <li><a href="/blog/how-to-prepare-for-iti-cbt-exam/"><span class="thumb th1" aria-hidden="true">📝</span><span class="rt"><b>How to Prepare for ITI CBT Exam?</b><small><svg class="ic"><use href="#i-cal"/></svg><time datetime="2026-10-12">12 Oct 2026</time></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/blog/important-topics-employability-skills/"><span class="thumb th2" aria-hidden="true">📚</span><span class="rt"><b>Important Topics for Employability Skills</b><small><svg class="ic"><use href="#i-cal"/></svg><time datetime="2026-10-10">10 Oct 2026</time></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/blog/ncvt-iti-exam-pattern-syllabus/"><span class="thumb th3" aria-hidden="true">📋</span><span class="rt"><b>NCVT ITI Exam Pattern and Syllabus 2026</b><small><svg class="ic"><use href="#i-cal"/></svg><time datetime="2026-10-08">08 Oct 2026</time></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
            <li><a href="/blog/tips-to-score-high-in-iti-exams/"><span class="thumb th4" aria-hidden="true">🎯</span><span class="rt"><b>Tips to Score High in ITI Exams</b><small><svg class="ic"><use href="#i-cal"/></svg><time datetime="2026-10-05">05 Oct 2026</time></small></span><svg class="ic chev"><use href="#i-chev-r"/></svg></a></li>
          </ul>
        </article>
      </div>
    </section>

    <!-- ===== SEO content + FAQ ===== -->
    <section class="section pt0" aria-labelledby="about-h">
      <div class="container cols-2-1">
        <div class="seo-text card">
          <h2 id="about-h">Free ITI Exam Preparation for All Trades</h2>
          <p><strong>ITIExam.com</strong> is a free online platform for ITI students preparing for the <strong>NCVT / SCVT ITI CBT exam</strong>. Practise trade-wise <strong>ITI mock tests</strong>, solve <strong>ITI previous year question papers with answers</strong>, and revise with module-wise notes and <strong>MCQ question banks</strong> for Trade Theory, <strong>Employability Skills</strong>, Workshop Calculation &amp; Science and Engineering Drawing.</p>
          <p>Whether you are in COPA, Electrician, Fitter, Turner, Welder, MMV, Plumber, Wireman, Machinist or Diesel Mechanic, you get exam-pattern questions for 1st year and 2nd year, along with the latest <strong>ITI jobs</strong>, <strong>apprenticeship (NAPS / NATS)</strong> openings, <strong>admit card</strong> and <strong>ITI result</strong> updates, including Maharashtra DVET.</p>
        </div>
        <div class="card faq">
          <h2>Frequently Asked Questions</h2>
          <details><summary>Are ITI mock tests on ITIExam.com free?</summary><p>Yes. All ITI mock tests, previous year papers and study material are 100% free for students of every trade.</p></details>
          <details><summary>Is the ITI exam conducted in CBT mode?</summary><p>Yes. NCVT ITI theory, Employability Skills and Workshop Calculation &amp; Science exams are Computer Based Tests with MCQs. Our mock tests follow the same pattern.</p></details>
          <details><summary>Which ITI trades are covered?</summary><p>30+ trades including COPA, Electrician, Fitter, Turner, Welder, MMV, Plumber, Wireman, Machinist, Diesel Mechanic and more, for 1st and 2nd year.</p></details>
          <details><summary>How can I apply for ITI apprenticeship?</summary><p>Register on the official Apprenticeship India portal (NAPS). Our step-by-step guide covers registration, eligibility, documents and company-wise openings.</p></details>
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
          <li class="s-blue"><b data-count="30">30+</b><small>ITI Trades</small></li>
          <li class="s-green"><b data-count="10000">10,000+</b><small>Practice Questions</small></li>
          <li class="s-orange"><b data-count="100000">1,00,000+</b><small>Students Learning</small></li>
          <li class="s-purple"><b>100%</b><small>Free Access</small></li>
        </ul>
      </div>
    </section>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
