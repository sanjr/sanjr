// ITI CareerTrack — real-browser walkthrough of every role (Playwright + Chromium).
// Logs in through the login page, clicks EVERY sidebar menu item and checks: HTTP 200,
// exactly one highlighted menu item, no PHP warnings/errors, no JavaScript errors, a page heading.
// Then performs the key task of several roles by filling the real forms in the browser.
// Usage (website running with DEMO data):  node python/tests/browser_walkthrough.js http://127.0.0.1:8080
const { chromium } = require('playwright');
const BASE = (process.argv[2] || 'http://127.0.0.1:8080').replace(/\/$/, '');
const ORIGIN = new URL(BASE).origin;   // sidebar links already contain the sub-folder path
const ROLES = [
  ['STATE_ADMIN', 'DVET-ADMIN'], ['RO_ADMIN', 'RO-PUN'], ['DVTO', 'DVTO-PUNE'], ['APPRENTICE_ADVISOR', 'AA-PUNE'],
  ['PRINCIPAL', 'DEMO0001'], ['TEACHER', 'teacher.pune@demo.local'], ['PLACEMENT_OFFICER', 'placement.pune@demo.local'],
  ['ITI_STAFF', 'staff.pune@demo.local'], ['EMPLOYER', 'hr.sahyadri@demo.local'], ['STUDENT', 'rohit.deshmukh@demo.local'],
];
let pass = 0, fail = 0;
const ok = (c, m) => { if (c) { pass++; console.log('  PASS ', m); } else { fail++; console.log('  FAIL ', m); } };

async function login(b, role, user) {
  const ctx = await b.newContext({ viewport: { width: 1366, height: 860 } });
  const p = await ctx.newPage();
  p.jsErrors = [];
  p.on('pageerror', e => p.jsErrors.push(e.message));
  p.on('console', m => { if (m.type() === 'error') p.jsErrors.push(m.text()); });
  p.on('dialog', d => d.accept());
  await p.goto(BASE + '/login?as=' + role);
  await p.fill('#email', user); await p.fill('#password', 'Demo@1234');
  await Promise.all([p.waitForNavigation(), p.click('#login-form button[type=submit]')]);
  return p;
}
async function check(p, label) {
  const body = await p.content();
  const bad = /Fatal error|Warning:|Notice:|Deprecated:|Uncaught|Stack trace/.test(body);
  const h1 = await p.locator('h1').count();
  ok(!bad && h1 > 0 && p.jsErrors.length === 0, `${label}${bad ? ' (PHP error!)' : ''}${p.jsErrors.length ? ' JS: ' + p.jsErrors.join(' | ') : ''}`);
  p.jsErrors.length = 0;
}
const successText = async p => (await p.locator('.alert-success').allTextContents()).join(' ');
const errorText = async p => (await p.locator('.alert-error').allTextContents()).join(' ');

(async () => {
  const b = await chromium.launch();
  for (const [role, user] of ROLES) {
    console.log(`\n== ${role} (${user})`);
    const p = await login(b, role, user);
    ok(/\/(dashboard|follow-ups)$/.test(p.url()), `logged in -> ${p.url().replace(BASE, '')}`);
    const links = await p.$$eval('.sidebar nav a', as => as.map(a => [a.textContent.trim(), a.getAttribute('href')]));
    for (const [text, href] of links) {
      const r = await p.goto(href.startsWith('http') ? href : ORIGIN + href);
      if (!r || r.status() !== 200) { ok(false, `menu "${text}" -> HTTP ${r ? r.status() : 0}`); continue; }
      ok(await p.locator('.sidebar nav a.active').count() === 1, `menu "${text}" opens and is highlighted`);
      await check(p, `menu "${text}" page has no errors`);
    }
    await p.context().close();
  }

  console.log('\n== Key tasks done in the browser');
  // Principal: 5-step admission wizard by clicking
  let p = await login(b, 'PRINCIPAL', 'DEMO0001');
  await p.goto(BASE + '/admissions/new');
  const rnd = Math.random().toString(36).replace(/[^a-z]/g, '').slice(0, 5) || 'abcde';
  ok(await p.locator('[data-submit]').isHidden(), 'wizard: Confirm button hidden on step 1');
  await p.fill('input[name=first_name]', 'Browser' + rnd); await p.fill('input[name=last_name]', 'Tester');
  await p.selectOption('select[name=gender]', 'FEMALE'); await p.fill('input[name=date_of_birth]', '2008-06-01');
  await p.fill('input[name=mobile]', '98' + String(Date.now()).slice(-8));
  await p.click('[data-next]');
  await p.selectOption('select[name=ssc_board]', 'MSBSHSE'); await p.fill('input[name=ssc_seat_number]', 'B' + String(Date.now()).slice(-7));
  await p.fill('input[name=ssc_passing_year]', '2025');
  await p.click('[data-next]');
  await p.selectOption('select[name=trade_id]', { index: 1 });
  await p.click('[data-next]'); await p.click('[data-next]');
  ok(await p.locator('[data-review] div').count() > 5, 'wizard: review step lists the entered data');
  await p.check('input[name=consent]');
  await Promise.all([p.waitForNavigation(), p.click('[data-submit]')]);
  const msg = await successText(p);
  ok(/PCID\): ITIC-MH-\d{4}-\d{6}-\d/.test(msg), 'admission saved, PCID shown: ' + ((msg.match(/ITIC\S+/) || [''])[0]));
  await Promise.all([p.waitForNavigation(), p.click('a:has-text("PCID Card")')]);
  ok(await p.locator('.qr svg').count() === 1, 'PCID card with QR code opens');
  await check(p, 'PCID card page has no errors');
  await p.context().close();

  // State Admin: create an Apprentice Advisor with the role-dependent form
  p = await login(b, 'STATE_ADMIN', 'DVET-ADMIN');
  await p.goto(BASE + '/users');
  await p.selectOption('[data-role-select]', { label: 'Apprentice Advisor' });
  ok(await p.locator('[data-for=ITI_GROUP]').isVisible() && await p.locator('[data-for=REGION]').isHidden(), 'user form shows only the fields this role needs');
  await p.fill('#new-user input[name=full_name]', 'Browser Advisor'); await p.fill('#new-user input[name=username]', 'AA-B' + rnd.toUpperCase());
  await p.selectOption('select[name="itis[]"]', [{ index: 0 }, { index: 1 }]);
  await Promise.all([p.waitForNavigation(), p.click('#new-user button[type=submit]')]);
  ok(/User saved/.test(await successText(p)), 'State Admin created an Apprentice Advisor');
  await p.context().close();

  // Instructor: mark attendance
  p = await login(b, 'TEACHER', 'teacher.pune@demo.local');
  await p.goto(BASE + '/attendance');
  const n = await p.locator('input[type=radio][value=ABSENT]').count();
  if (n) { await p.locator('input[type=radio][value=ABSENT]').first().check(); }
  await Promise.all([p.waitForNavigation(), p.click('button:has-text("Save attendance")')]);
  ok(/Attendance saved/.test(await successText(p)), `instructor saved attendance for ${n} students`);
  await p.context().close();

  // Placement officer: record a job for an alumnus
  p = await login(b, 'PLACEMENT_OFFICER', 'placement.pune@demo.local');
  await p.goto(BASE + '/placements?status=SEEKING_EMPLOYMENT');
  await Promise.all([p.waitForNavigation(), p.locator('a:has-text("+ Record")').first().click()]);
  await p.selectOption('#company_id', { index: 1 });
  await p.fill('input[name=designation]', 'Junior Technician'); await p.fill('input[name=joining_date]', '2026-10-15');
  await p.fill('input[name=salary_amount]', '16000');
  await Promise.all([p.waitForNavigation(), p.click('button:has-text("Add employment")')]);
  ok(/Employment added/.test(await successText(p)), 'placement officer recorded a new job');
  await p.context().close();

  // Student: update status
  p = await login(b, 'STUDENT', 'rohit.deshmukh@demo.local');
  await p.goto(BASE + '/me/update?type=status');
  await p.selectOption('select[name=status]', 'SEEKING_EMPLOYMENT');
  await Promise.all([p.waitForNavigation(), p.click('button:has-text("Save status")')]);
  ok(/Thank you/.test(await successText(p)), 'student updated own career status');
  await p.context().close();

  // Employer: wrong date of birth is refused
  p = await login(b, 'EMPLOYER', 'hr.sahyadri@demo.local');
  await p.goto(BASE + '/employer/add');
  await p.fill('input[name=pcid]', 'ITIC-MH-2022-001077-9'); await p.fill('input[name=date_of_birth]', '2000-01-01');
  await p.fill('input[name=start_date]', '2026-10-01'); await p.fill('input[name=designation]', 'Technician');
  await Promise.all([p.waitForNavigation(), p.click('button:has-text("Add to career history")')]);
  ok(/do not match|valid PCID/.test(await errorText(p)), 'employer: wrong date of birth is refused');
  await p.context().close();

  // Pune region ITI list (database/data): each DVTO sees only the ITIs of own district; Principal logs in with NCVT MIS code
  for (const [u, dist] of [['DVTO-SATARA', 'Satara'], ['DVTO-KOLHAPUR', 'Kolhapur'], ['DVTO-THANE', 'Thane'], ['DVTO-MUMBAI-CITY', 'Mumbai City'], ['DVTO-GADCHIROLI', 'Gadchiroli'], ['DVTO-JALGAON', 'Jalgaon']]) {
    p = await login(b, 'DVTO', u);
    await p.goto(BASE + '/itis');
    const ds = await p.$$eval('table.table tr td:nth-child(5)', t => t.map(x => x.textContent.trim()));
    ok(ds.length > 0 && ds.every(x => x === dist), `${u} sees only ${dist} ITIs (${ds.length})`);
    await p.context().close();
  }
  p = await login(b, 'PRINCIPAL', 'GR27000019');
  ok(/\/dashboard$/.test(p.url()) && (await p.content()).includes('Karad'), 'Principal logs in with NCVT MIS code GR27000019 (Govt. ITI Karad)');
  await p.context().close();

  // Public website and installer lock
  const ctx = await b.newContext(); p = await ctx.newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  for (const path of ['/', '/about', '/courses', '/careers', '/public-reports', '/contact', '/verify', '/login', '/forgot-password']) {
    const r = await p.goto(BASE + path); ok(r.status() === 200 && errs.length === 0, `public page ${path}`);
  }
  const ri = await p.goto(BASE + '/install.php'); ok(ri.status() === 404, 'installer is locked after installation');
  await b.close();
  console.log(`\n==== browser walkthrough: ${pass} passed, ${fail} failed ====`);
  process.exit(fail ? 1 : 0);
})();
