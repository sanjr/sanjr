<?php
/**
 * Diwali rockets behind the homepage hero (inside .hero-fx, so they sit under the text and take no clicks).
 * Shown only until the end of 11 Nov 2026 IST. After that this file prints nothing, so the hero is back
 * to normal by itself; the script carries the same cut-off for pages a browser or cache kept from before.
 */
const DIWALI_FX_UNTIL = '2026-11-12 00:00:00';   // IST: shown through the whole of 11/11/2026

function diwali_fx_on(): bool
{
    return time() < (new DateTimeImmutable(DIWALI_FX_UNTIL, new DateTimeZone('Asia/Kolkata')))->getTimestamp();
}

function diwali_fx(): void
{
    if (!diwali_fx_on()) return;
    $until = (new DateTimeImmutable(DIWALI_FX_UNTIL, new DateTimeZone('Asia/Kolkata')))->getTimestamp() * 1000;
    ?>
<canvas class="diwali-fx" data-until="<?= $until ?>"></canvas>
<style>
.hero-fx .diwali-fx { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
@media (prefers-reduced-motion: reduce) { .hero-fx .diwali-fx { display: none; } }
</style>
<script>
(function () {
  var cv = document.currentScript.previousElementSibling.previousElementSibling;
  if (!cv || !cv.getContext || Date.now() >= +cv.dataset.until) { if (cv) cv.remove(); return; }
  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var ctx = cv.getContext('2d'), W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
  var COLORS = ['#ffd54a', '#ff9f1c', '#ff5e7e', '#ffe9a8', '#7cf5a8', '#7cc8ff', '#ff7a1a', '#f9a8ff'];
  var rockets = [], sparks = [], running = false, visible = true, last = 0, nextLaunch = 0;

  function size() {
    var r = cv.getBoundingClientRect(); W = r.width; H = r.height;
    cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  function rnd(a, b) { return a + Math.random() * (b - a); }
  function launch() {
    var x = rnd(W * .08, W * .92);
    rockets.push({ x: x, y: H + 6, vx: rnd(-.35, .35), vy: -rnd(H * .0135, H * .017) - 3.2,
      top: rnd(H * .12, H * .45), c: COLORS[(Math.random() * COLORS.length) | 0], trail: [] });
  }
  function burst(r) {
    var n = W < 600 ? 44 : 70, c2 = COLORS[(Math.random() * COLORS.length) | 0], sp = rnd(2.6, 4.2);
    for (var i = 0; i < n; i++) {
      var a = (i / n) * Math.PI * 2 + rnd(-.08, .08), v = sp * rnd(.55, 1);
      sparks.push({ x: r.x, y: r.y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, life: 1,
        decay: rnd(.009, .015), c: Math.random() < .7 ? r.c : c2, s: rnd(1.6, 2.6) });
    }
  }
  function frame(t) {
    if (!running) return;
    var dt = Math.min((t - last) / 16.7 || 1, 3); last = t;
    if (t > nextLaunch && rockets.length < (W < 600 ? 3 : 5)) { launch(); nextLaunch = t + rnd(450, 1100); }
    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillStyle = 'rgba(0,0,0,.18)'; ctx.fillRect(0, 0, W, H);      // fades the old frame: soft trails
    ctx.globalCompositeOperation = 'lighter';
    for (var i = rockets.length - 1; i >= 0; i--) {
      var r = rockets[i];
      r.x += r.vx * dt; r.y += r.vy * dt; r.vy += .045 * dt;
      ctx.fillStyle = r.c; ctx.beginPath(); ctx.arc(r.x, r.y, 2.4, 0, 7); ctx.fill();
      ctx.globalAlpha = .25; ctx.beginPath(); ctx.arc(r.x, r.y, 6, 0, 7); ctx.fill(); ctx.globalAlpha = 1;
      ctx.fillStyle = 'rgba(255,200,120,.55)'; ctx.beginPath(); ctx.arc(r.x + rnd(-1, 1), r.y + 5, 1.3, 0, 7); ctx.fill();
      if (r.y <= r.top || r.vy >= -.5) { burst(r); rockets.splice(i, 1); }
    }
    for (var j = sparks.length - 1; j >= 0; j--) {
      var s = sparks[j];
      s.x += s.vx * dt; s.y += s.vy * dt; s.vx *= .985; s.vy = s.vy * .985 + .035 * dt; s.life -= s.decay * dt;
      if (s.life <= 0) { sparks.splice(j, 1); continue; }
      ctx.globalAlpha = Math.max(s.life, 0); ctx.fillStyle = s.c;
      ctx.beginPath(); ctx.arc(s.x, s.y, s.s, 0, 7); ctx.fill();
      ctx.globalAlpha = Math.max(s.life, 0) * .22; ctx.beginPath(); ctx.arc(s.x, s.y, s.s * 3, 0, 7); ctx.fill();   // soft glow
    }
    ctx.globalAlpha = 1;
    requestAnimationFrame(frame);
  }
  function start() { if (!running && visible && !document.hidden && Date.now() < +cv.dataset.until) { running = true; last = performance.now(); requestAnimationFrame(frame); } }
  function stop() { running = false; }
  size();
  window.addEventListener('resize', size);
  window.addEventListener('load', size);   // the hero below is not laid out yet when this runs
  if ('ResizeObserver' in window) new ResizeObserver(size).observe(cv);
  document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
  if ('IntersectionObserver' in window) new IntersectionObserver(function (e) { visible = e[0].isIntersecting; visible ? start() : stop(); }).observe(cv);
  setInterval(function () { if (Date.now() >= +cv.dataset.until) { stop(); cv.remove(); } }, 60000);
  start();
})();
</script>
<?php
}
