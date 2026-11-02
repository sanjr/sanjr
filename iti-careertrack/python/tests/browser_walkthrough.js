// ITI CareerTrack — real-browser walkthrough of every role (Playwright + Chromium).
// Logs in through the login page, clicks EVERY sidebar menu item and checks: HTTP 200,
// exactly one highlighted menu item, no PHP warnings/errors, no JavaScript errors, a page heading.
// Then performs the key task of several roles by filling the real forms in the browser.
// Usage (website running with DEMO data):  node python/tests/browser_walkthrough.js http://127.0.0.1:8080
const { chromium } = require('playwright');
const BASE = (process.argv[2] || 'http://127.0.0.1:8080').replace(/\/$/, '');
const ORIGIN = new URL(BASE).origin;   // sidebar links already contain the sub-folder path
const PNG_HEX = '89504e470d0a1a0a0000000d4948445200000001000000010806000000' + '1f15c4890000000d49444154789c6360000002000154a24f5d0000000049454e44ae426082';
const ROLES = [
  ['STATE_ADMIN', 'DVET-ADMIN'], ['RO_ADMIN', 'RO-PUN'], ['DVTO', 'DVTO-PUNE'], ['APPRENTICE_ADVISOR', 'AA-PUNE'],
  ['PRINCIPAL', 'DEMO0001'], ['TEACHER', 'teacher.pune@demo.local'], ['EMPLOYER', 'hr.sahyadri@demo.local'], ['STUDENT', 'rohit.deshmukh@demo.local'],
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
  await p.click('[data-next]');
  await p.click('[data-next]');   // step 4 without photo: must stay
  ok(await p.locator('input[name=photo]').isVisible(), 'wizard: cannot continue without the student photo (compulsory)');
  await p.setInputFiles('input[name=photo]', { name: 'photo.png', mimeType: 'image/png', buffer: Buffer.from(PNG_HEX, 'hex') });
  await p.click('[data-next]');
  ok(await p.locator('[data-review] div').count() > 5, 'wizard: review step lists the entered data');
  await p.check('input[name=consent]');
  await Promise.all([p.waitForNavigation(), p.click('[data-submit]')]);
  const msg = await successText(p);
  ok(/PCID\): ITIC-MH-\d{4}-\d{6}-\d/.test(msg), 'admission saved, PCID shown: ' + ((msg.match(/ITIC\S+/) || [''])[0]));
  await Promise.all([p.waitForNavigation(), p.click('a:has-text("PCID Card")')]);
  ok(await p.locator('.ic-qr svg').count() === 1 && await p.locator('.ic-photo img').count() === 1 && await p.locator('.ic-card').count() === 2, 'PCID card (front + back) with photo and QR code opens');
  await check(p, 'PCID card page has no errors');
  await p.context().close();

  // Principal of a real ITI: add a trade from the official list (dropdown only)
  p = await login(b, 'PRINCIPAL', 'GR27000019');
  await p.goto(BASE + '/trades');
  ok(await p.locator('#add-trade select[name=trade_id] option').count() > 150 && await p.locator('#add-trade input[name=trade_name]').count() === 0,
     'Principal: trade is chosen from the official list (no typing)');
  await p.selectOption('#add-trade select[name=trade_id]', { label: 'Electrician — 24 months' });
  await Promise.all([p.waitForNavigation(), p.click('#add-trade button[type=submit]')]);
  ok(/Electrician added/.test(await successText(p)), 'Principal added Electrician to Govt. ITI Karad');
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
  p = await login(b, 'PRINCIPAL', 'DEMO0001');   // the Principal records jobs (Placement Officer login no longer exists)
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
  for (const [u, dist] of [['DVTO-SATARA', 'Satara'], ['DVTO-KOLHAPUR', 'Kolhapur'], ['DVTO-THANE', 'Thane'], ['DVTO-MUMBAI-CITY', 'Mumbai City'], ['DVTO-GADCHIROLI', 'Gadchiroli'], ['DVTO-JALGAON', 'Jalgaon'], ['DVTO-YAVATMAL', 'Yavatmal'], ['DVTO-NANDED', 'Nanded']]) {
    p = await login(b, 'DVTO', u);
    await p.goto(BASE + '/itis');
    const ds = await p.$$eval('table.table tr td:nth-child(5)', t => t.map(x => x.textContent.trim()));
    ok(ds.length > 0 && ds.every(x => x === dist), `${u} sees only ${dist} ITIs (${ds.length})`);
    await p.context().close();
  }
  p = await login(b, 'PRINCIPAL', 'GR27000019');
  ok(/\/dashboard$/.test(p.url()) && (await p.content()).includes('Karad'), 'Principal logs in with NCVT MIS code GR27000019 (Govt. ITI Karad)');
  await p.context().close();

  // Login page: the ID box is named per role; Instructor logs in with the Sevaarth ID
  { const c = await b.newContext(); const lp = await c.newPage(); await lp.goto(BASE + '/login');
    const lab = async () => [(await lp.locator('#id-label').innerText()).trim(), await lp.getAttribute('#email', 'placeholder')];
    let [l0, p0] = await lab(); ok(l0 === 'User ID' && p0 === 'Enter User ID', 'login: default box is "User ID" / "Enter User ID"');
    await lp.click('[data-role=PRINCIPAL]'); ok((await lab())[0] === 'NCVT MIS Code' && (await lab())[1] === 'Enter NCVT MIS Code', 'login: Principal -> NCVT MIS Code');
    await lp.click('[data-role=TEACHER]'); ok((await lab())[0] === 'Sevaarth ID' && (await lab())[1] === 'Enter Sevaarth ID', 'login: Instructor -> Sevaarth ID');
    ok(await lp.locator('.role-pick [data-role]').count() === 8 && await lp.locator('[data-role=ITI_STAFF], [data-role=PLACEMENT_OFFICER]').count() === 0,
       'login: 8 roles, no Placement Officer / ITI Staff');
    for (const r of ['STATE_ADMIN', 'RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'EMPLOYER', 'STUDENT']) {
      await lp.click(`[data-role=${r}]`); const [l, ph] = await lab(); ok(l === 'User ID' && ph === 'Enter User ID', `login: ${r} -> User ID`);
    }
    await lp.click('[data-role=TEACHER]'); const sev = await lp.inputValue('#email');
    await Promise.all([lp.waitForNavigation(), lp.click('#login-form button[type=submit]')]);
    ok(/^DEMOSEV\d+$/.test(sev) && /\/dashboard$/.test(lp.url()), `Instructor logged in with Sevaarth ID ${sev}`);
    await c.close(); }

  // Instructor: final result + apprenticeship, from the outcome form
  p = await login(b, 'TEACHER', 'teacher.pune@demo.local');
  await p.goto(BASE + '/outcomes?exam=not_appeared');
  const oh = await p.locator('a[href$="/outcome"]').first().getAttribute('href');
  await p.goto(ORIGIN + oh);
  await p.check('input[name=result][value=PASS]'); await p.fill('input[name=exam_month]', '2026-07');
  await p.check('input[data-next-pick][value=APPRENTICE]', { force: true });
  ok(await p.locator('input[name=appr_company]').isVisible() && await p.locator('input[name=job_company]').isHidden(), 'outcome form shows only the chosen step\'s fields');
  await p.fill('input[name=appr_company]', 'Walkthrough Apprentice Co'); await p.fill('input[name=appr_start]', '2026-08-01');
  await p.fill('input[name=appr_end]', '2027-07-31'); await p.fill('input[name=appr_stipend]', '9000');
  await Promise.all([p.waitForNavigation(), p.click('#outcome-form button[type=submit]')]);
  ok(/Saved: Result: Pass · Apprenticeship/.test(await successText(p)), 'Instructor recorded Pass + apprenticeship');
  await check(p, 'outcome page has no errors');
  await p.context().close();

  // Principal adds an instructor (Sevaarth ID + trade)
  p = await login(b, 'PRINCIPAL', 'DEMO0001');
  await p.goto(BASE + '/users');
  await p.selectOption('[data-role-select]', { label: 'Instructor' });
  await p.fill('#new-user input[name=full_name]', 'Walkthrough Instructor'); await p.fill('#new-user input[name=username]', 'SEVWT' + rnd.toUpperCase());
  await p.selectOption('select[name="trades[]"]', [{ index: 0 }]);
  await Promise.all([p.waitForNavigation(), p.click('#new-user button[type=submit]')]);
  ok(/User saved/.test(await successText(p)), 'Principal added an instructor');
  await p.context().close();

  // Principal: bulk admission from Excel/CSV, photos, then assign instructor
  p = await login(b, 'PRINCIPAL', 'DEMO0001');
  await p.goto(BASE + '/admissions/bulk');
  await check(p, 'bulk admission page');
  const [dl] = await Promise.all([p.waitForEvent('download'), p.click('a[href$="/admissions/bulk/sample"]')]);
  ok(/bulk-admission-sample\.xlsx$/.test(dl.suggestedFilename()), 'sample Excel downloads');
  const seat = 'WT' + Math.floor(Math.random() * 1e6);
  const csv = 'First Name *,Surname *,Gender *,Date of Birth *,Mobile *,Trade *,Admission Date *,SSC Board,SSC Seat Number,SSC Passing Year\n'
    + `Walkbulk,Student,Female,10-03-2008,96${Math.floor(1e7 + Math.random() * 8e7)},Fitter,01-08-2026,MSBSHSE,${seat},2025\n`;
  await p.setInputFiles('#bk-file', { name: 'students.csv', mimeType: 'text/csv', buffer: Buffer.from(csv) });
  await p.check('#bulk-form input[name=consent]');
  await Promise.all([p.waitForNavigation(), p.click('#bulk-form button[type=submit]')]);
  ok(/1 students admitted/.test(await successText(p)), 'Principal admitted students from a sheet');
  const bulkPcid = (await p.locator('.bk-report .code').first().textContent()).trim();
  await p.setInputFiles('#bk-photos', { name: bulkPcid + '.png', mimeType: 'image/png', buffer: Buffer.from(PNG_HEX, 'hex') });
  await Promise.all([p.waitForNavigation(), p.click('#photos button[type=submit]')]);
  ok(/1 photos saved/.test(await successText(p)), 'bulk photo matched by PCID');
  await p.goto(BASE + '/students/assign?q=' + encodeURIComponent(bulkPcid));
  await check(p, 'assign trade & instructor page');
  await p.check('input[name="admission_ids[]"]');
  ok((await p.locator('[data-as-count]').textContent()).trim() === '1 selected', 'assign page counts the ticked students');
  await p.selectOption('#as-teacher', { index: 1 });
  await Promise.all([p.waitForNavigation(), p.click('#assign-form button[type=submit]')]);
  ok(/Instructor assigned to 1 students/.test(await successText(p)), 'Principal assigned an instructor');
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
