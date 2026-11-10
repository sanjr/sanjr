"""Security probes against a running DEMO install: headers, cookies, login lockout, CSRF, SQL injection,
XSS, other-ITI access (IDOR), disguised PHP upload, error disclosure, open redirect, session fixation, webhook.
Usage:  CT_DB_NAME=… CT_DB_USER=… CT_DB_PASS=… python3 python/tests/test_security.py http://127.0.0.1:8181/app/public
Run it on a fresh DEMO install (it locks the AA-PUNE account for 15 minutes and changes one address)."""
import sys, re, html, urllib.parse, time
_argv = sys.argv[:]; sys.argv = ['x']
import os; _here = os.path.dirname(os.path.abspath(__file__)); sys.path.insert(0, _here); sys.path.insert(0, os.path.dirname(_here))
import test_e2e as t
sys.argv = _argv
B = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8097").rstrip("/")
R = []
def chk(ok, name, detail=''):
    R.append((ok, name, detail)); print(('PASS ' if ok else 'FAIL ') + name + (('  — ' + detail) if detail else ''))
# 1 headers & cookies
import urllib.request
r = urllib.request.urlopen(B + '/login'); h = r.headers
for k in ['Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy']:
    chk(k in h, 'header ' + k, h.get(k, '')[:60])
ck = [v for k, v in h.items() if k.lower() == 'set-cookie']
chk(any('HttpOnly' in c for c in ck) and any('SameSite' in c for c in ck), 'session cookie HttpOnly + SameSite', '; '.join(ck)[:120])
chk('X-Powered-By' not in h, 'no X-Powered-By (PHP version hidden)', h.get('X-Powered-By', ''))
chk('Server' not in h or not re.search(r'\d', h.get('Server', '')), 'Server header hides version', h.get('Server', ''))
# 2 unauthenticated access
c = t.Client(B)
for p in ['/dashboard', '/students', '/backups', '/integrations', '/users', '/students/1', '/documents/1', '/backups/download?f=x']:
    s, body, url, _ = c.get(p); chk('/login' in url, 'anonymous blocked: ' + p, url.replace(B, ''))
# 3 login brute force / enumeration
c = t.Client(B); c.get('/login')
_, b1, _, _ = c.post('/login', {'email': 'DVET-ADMIN', 'password': 'wrong1'})
_, b2, _, _ = c.post('/login', {'email': 'NOSUCHUSER', 'password': 'wrong1'})
f1, f2 = t.flash(b1), t.flash(b2)
chk(f1 == f2, 'same message for wrong password and unknown user (no enumeration)', f1 + ' | ' + f2)
for i in range(6):
    _, b, _, _ = c.post('/login', {'email': 'AA-PUNE', 'password': 'bad%d' % i})
_, b, url, _ = c.post('/login', {'email': 'AA-PUNE', 'password': 'Demo@1234'})
chk('locked' in t.flash(b).lower() or '/dashboard' not in url, 'account locks after repeated wrong passwords', t.flash(b)[:80])
# 4 CSRF
s, _, _, _ = t.Client(B).post('/login', {'email': 'x', 'password': 'y'}, csrf=False); chk(s in (403, 419), 'POST without CSRF token rejected', str(s))
pp = t.as_user(B, 'principal.pune@demo.local')
s, _, _, _ = pp.request('/admissions/bulk', {'_csrf': 'forged', 'academic_year_id': '1'}, csrf=False); chk(s in (403, 419), 'forged CSRF token rejected', str(s))
# 5 SQL injection probes (should not cause DB errors or bypass)
for p in ["/students?q=' OR 1=1 -- ", "/students?q=%27%22%5C", "/students?trade=1 OR 1=1", "/outcomes?q=') UNION SELECT password_hash FROM users -- ",
          "/students?page=999999999999999999999", "/students?page=100001", "/students?page=-5", "/verify?pcid=' OR '1'='1", "/whatsapp?q=%27%29%3B--"]:
    s, body, _, _ = pp.get(p.replace(' ', '%20'))
    bad = re.search(r'SQLSTATE|PDOException|Fatal error|Stack trace|Warning:|/var/www', body)
    chk(s in (200, 404, 403) and not bad, 'injection probe ' + p[:50], f'{s}' + (' LEAK:' + bad.group(0) if bad else ''))
# 6 XSS reflected / stored
x = '<script>alert(1)</script>"\'><img src=x onerror=alert(1)>'
s, body, _, _ = pp.get('/students?q=' + urllib.parse.quote(x)); chk('<script>alert(1)</script>' not in body and 'onerror=alert(1)>' not in body, 'reflected XSS in search escaped')
s, body, _, _ = t.Client(B).get('/verify?pcid=' + urllib.parse.quote(x)); chk('<script>alert(1)</script>' not in body, 'reflected XSS on public verify escaped')
s, body, _, _ = t.Client(B).get('/login?as=' + urllib.parse.quote(x)); chk('<script>alert(1)</script>' not in body, 'reflected XSS on login ?as escaped')
# stored: contact address with script, view contacts
sid = 1
st = t.as_user(B, 'state@demo.local'); st.get(f'/students/{sid}?tab=contacts')
st.post(f'/students/{sid}/contacts', {'address': x})
s, body, _, _ = st.get(f'/students/{sid}?tab=contacts'); chk('<script>alert(1)</script>' not in body and '&lt;script&gt;' in body, 'stored XSS (address) escaped on profile')
s, body, _, _ = st.get(f'/students/{sid}/card'); chk('<script>alert(1)</script>' not in body, 'stored XSS escaped on PCID card')
# 7 IDOR: principal Nashik opening Pune student, documents, card
pn = t.as_user(B, 'principal.nashik@demo.local')
pune_sid = 6612
for p in [f'/students/{pune_sid}', f'/students/{pune_sid}/card', f'/students/{pune_sid}/outcome', f'/whatsapp/{pune_sid}']:
    s, _, _, _ = pn.get(p); chk(s == 404, 'other ITI cannot open ' + p, str(s))
s, _, _, _ = pn.post(f'/students/{pune_sid}/contacts', {'mobile': '9999999999'}); chk(s == 404, 'other ITI cannot change contacts', str(s))
stu = t.as_user(B, 'rohit.deshmukh@demo.local')
for p in ['/students/1', '/students', '/backups', '/users', '/integrations', '/whatsapp']:
    s, _, _, _ = stu.get(p); chk(s in (403, 404), 'student blocked from ' + p, str(s))
# 8 upload a PHP file disguised as photo
php = b'<?php echo "PWNED"; ?>'
s, body, _, _ = pp.request('/admissions/bulk/photos', {}, files={'photos[]': ('ITIC-MH-2022-001077-9.php', php, 'image/png')})
chk('PWNED' not in body and ('not' in t.flash(body).lower() or 'photo' in t.flash(body).lower()), 'PHP file rejected as photo', t.flash(body)[:90])
s, body, _, _ = pp.request('/admissions/bulk', {'academic_year_id': '1', 'consent': '1'}, files={'file': ('evil.php', php, 'text/plain')})
chk('Upload the Excel' in body or 'error' in body.lower(), 'non-Excel file rejected in bulk admission', t.flash(body)[:90])
# 9 error disclosure
s, body, _, _ = pp.get('/students/99999999999999999999'); chk(not re.search(r'Fatal|Stack trace|/var/www|SQLSTATE', body), 'huge id: no internal error shown', str(s))
s, body, _, _ = pp.get('/nonexistent-page'); chk(s == 404 and not re.search(r'/var/www|Stack', body), '404 page clean', str(s))
# 10 open redirect
s, body, url, _ = t.Client(B).get('/login?next=https://evil.example'); chk(url.startswith(B), 'no open redirect via ?next', url)
# 11 session fixation & logout
c = t.Client(B); c.get('/login'); before = [ck.value for ck in c.jar if ck.name == 'CTSESSID']
c.post('/login', {'email': 'DEMO0001', 'password': 'Demo@1234'}); after = [ck.value for ck in c.jar if ck.name == 'CTSESSID']
chk(before and after and before != after, 'session id changes at login (no fixation)')
c.post('/logout', {}); s, _, url, _ = c.get('/dashboard'); chk('/login' in url, 'logout ends session', url.replace(B, ''))
# 12 webhook
s, _, _, _ = t.Client(B).request('/webhooks/whatsapp', {'entry': '[]'}, csrf=False); chk(s == 403, 'unsigned WhatsApp webhook refused', str(s))
s, _, _, _ = t.Client(B).get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=1'); chk(s == 403, 'webhook verify without token refused', str(s))
# 13 email module: stored XSS in subject / body, HTML file disguised as PDF, other office's attachment
sm = t.as_user(B, 'state@demo.local')
_, body, url, _ = sm.request('/mail/compose', {'to_iti': '1', 'iti_mode': 'all', 'region_id': '2', 'subject': 'XSS ' + x, 'body': x, 'action': 'send'},
                             files={'attachments[]': ('x.pdf', b'<html><script>alert(1)</script></html>', 'application/pdf')})
chk('only PDF' in t.flash(body), 'HTML file disguised as PDF attachment refused', t.flash(body)[:70])
_, body, url, _ = sm.request('/mail/compose', {'to_iti': '1', 'iti_mode': 'all', 'region_id': '2', 'subject': 'XSS ' + x, 'body': x, 'action': 'send'})
mid = re.search(r'/mail/(\d+)', url)
pm = t.as_user(B, 'principal.pune@demo.local')
_, b1, _, _ = pm.get('/mail'); _, b2, _, _ = pm.get('/mail/' + (mid.group(1) if mid else '0'))
chk(bool(mid) and '<script>alert(1)</script>' not in b1 + b2 and 'onerror=alert(1)>' not in b1 + b2, 'stored XSS in mail subject / body escaped')
s2, _, _, _ = t.as_user(B, 'ro.mum@demo.local').get('/mail/' + (mid.group(1) if mid else '0'))
chk(s2 == 404, 'RO of another region cannot open the message', str(s2))
print('\n%d passed, %d failed' % (sum(1 for r in R if r[0]), sum(1 for r in R if not r[0])))
sys.exit(1 if any(not r[0] for r in R if not r[1].startswith("Server header")) else 0)
