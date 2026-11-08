/* COPA MCQ – Question Bank: all questions with correct answers, module-wise (header: js/study-hub.js) */
(function () {
  var C = window.COPA, t = C.t, esc = C.esc, l = C.lang;
  C.applyStatic();
  var fMod = document.getElementById("fMod"), fS = document.getElementById("fSearch"), fA = document.getElementById("fAns");
  fMod.innerHTML = '<option value="0">' + t("allModules") + ' (' + C.QUESTIONS.length + ')</option>' +
    C.MODULES.map(function (m) { return '<option value="' + m.id + '">M' + m.id + ' – ' + esc(C.modName(m)) + '</option>'; }).join("");
  var hm = parseInt((location.hash || "").replace("#m", ""), 10);
  if (hm >= 1 && hm <= 15) fMod.value = hm;
  function draw() {
    var m = +fMod.value, s = fS.value.trim().toLowerCase(), show = fA.checked;
    var items = C.QUESTIONS.filter(function (q) {
      if (m && q.m !== m) return false;
      if (!s) return true;
      return [q.en.q, q[l].q].concat(q.en.o, q[l].o, q.code || "", q.x ? q.x[l] : "").join(" ").toLowerCase().indexOf(s) > -1;
    });
    document.getElementById("fCount").textContent = t("showing") + " " + items.length;
    var html = "", lastM = 0;
    items.slice(0, 400).forEach(function (q) {
      if (q.m !== lastM) {
        lastM = q.m;
        var mod = C.MODULES[q.m - 1];
        html += '<div class="section-title"><h2 style="border-color:' + mod.color + '">' + t("module") + ' ' + q.m + ': ' + esc(C.modName(mod)) + '</h2><a href="' + C.modulePage(q.m) + '">' + t("startPractice") + ' <i class="fa-solid fa-arrow-right"></i></a></div>';
      }
      html += '<article class="card study-item"><div class="sq"><small>Q' + q.id.split("-")[1] + '.</small>' + esc(q[l].q) + '</div>' +
        (l !== "en" ? '<div class="alt">' + esc(q.en.q) + '</div>' : "") +
        (q.code ? '<pre class="code">' + esc(q.code) + '</pre>' : "") +
        (q.img ? '<img class="q-img" src="' + q.img + '" alt="" loading="lazy">' : "") +
        '<ol>' + [0, 1, 2, 3].map(function (i) { return '<li class="' + (show && i === q.a ? "ans" : "") + '">(' + "ABCD"[i] + ') ' + esc(q[l].o[i]) + (show && i === q.a ? ' <i class="fa-solid fa-check"></i>' : "") + '</li>'; }).join("") + '</ol>' +
        (show ? '<span class="chip green"><i class="fa-solid fa-key"></i> ' + t("correctAns") + ': ' + "ABCD"[q.a] + '</span>' + C.explain(q, l) : "") + '</article>';
    });
    document.getElementById("list").innerHTML = html || '<div class="card empty"><i class="fa-solid fa-magnifying-glass"></i><p>' + t("noMatch") + '</p></div>';
  }
  fMod.addEventListener("change", function () { history.replaceState(null, "", +fMod.value ? "#m" + fMod.value : "#"); draw(); });
  fS.addEventListener("input", draw);
  fA.addEventListener("change", draw);
  draw();
})();
