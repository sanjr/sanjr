/* COPA MCQ – Theory Notes, PART 3 (Modules 32–37). Study notes written for COPA MCQ following the
   COPA Trade Theory (NSQF 2022) lesson plan – not a copy of the book. Format: see js/theory-data.js */
window.COPA_THEORY = (window.COPA_THEORY || []).concat([
  /* ===================== JAVASCRIPT (continued) ===================== */
  { part: 3, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.119", title: "Arrays in JavaScript", m: 11,
    keys: ["Creating arrays", "Index & length", "Array methods", "Sorting", "Animation", "Audio & video in HTML5"],
    html: `
<h3>Arrays</h3>
<p>An <b>array</b> stores many values in one variable. Index starts at <b>0</b>.</p>
<pre>const trades = ["COPA", "IT", "ICTSM", "CHNM"];   // array literal (recommended)
const marks  = new Array(40, 55, 72);            // with new – avoid
trades[0]          // "COPA"   – first element
trades[1] = "DTPO" // change an element
trades.length      // 4        – number of elements
trades[trades.length - 1]   // last element</pre>
<ul><li>An array can hold different types, even objects and other arrays.</li><li>Arrays are a special kind of <b>object</b> (<code>typeof</code> gives "object"); check with <code>Array.isArray(x)</code>.</li><li>JavaScript has <b>no associative arrays</b> (named indexes) – use an <b>object</b> for that: <code>{name: "Asha", roll: 12}</code>.</li><li>Adding at a high index (<code>a[10] = 5</code>) creates empty (undefined) "holes".</li></ul>
<h3>Looping through an array</h3>
<pre>for (let i = 0; i &lt; trades.length; i++) console.log(trades[i]);
trades.forEach(t =&gt; console.log(t));
for (const t of trades) console.log(t);</pre>
<h3>Array methods</h3>
<table class="rte-table"><tbody><tr><th>Method</th><th>Work</th></tr>
<tr><td><code>push(x)</code> / <code>pop()</code></td><td>Add to / remove from the <b>end</b></td></tr>
<tr><td><code>unshift(x)</code> / <code>shift()</code></td><td>Add to / remove from the <b>start</b> (other elements shift)</td></tr>
<tr><td><code>toString()</code>, <code>join(" - ")</code></td><td>Array → string (comma separated / own separator)</td></tr>
<tr><td><code>concat(b)</code></td><td>Merge arrays into a new array</td></tr>
<tr><td><code>splice(2, 1, "x")</code></td><td>Remove and/or insert elements at a position (changes the array)</td></tr>
<tr><td><code>slice(1, 3)</code></td><td>Copy a part into a new array (original unchanged)</td></tr>
<tr><td><code>sort()</code> / <code>reverse()</code></td><td>Sort alphabetically / reverse order. Numbers: <code>sort((a, b) =&gt; a - b)</code></td></tr>
<tr><td><code>indexOf(x)</code>, <code>includes(x)</code></td><td>Find position / check if present</td></tr>
<tr><td><code>map</code>, <code>filter</code>, <code>reduce</code>, <code>find</code></td><td>Transform, select, combine, search with a function</td></tr>
<tr><td><code>delete a[0]</code></td><td>Leaves an undefined hole – use <code>splice</code> or <code>shift</code> instead</td></tr></tbody></table>
<h3>Animation with JavaScript</h3>
<p>Animations are made by changing an element's style <b>step by step</b>. The element needs <code>position: relative</code> or <code>absolute</code> inside a container.</p>
<pre>let pos = 0;
const box = document.getElementById("box");
const id = setInterval(() =&gt; {
  if (pos === 300) clearInterval(id);       // stop
  else { pos++; box.style.left = pos + "px"; }
}, 5);                                       // every 5 ms</pre>
<p><code>requestAnimationFrame()</code> gives smoother animations; CSS transitions/animations are another way.</p>
<h3>Multimedia in HTML5</h3>
<table class="rte-table"><tbody><tr><th>Media</th><th>Formats (media type)</th></tr>
<tr><td>Video</td><td><b>MP4</b> (video/mp4), <b>WebM</b> (video/webm), Ogg (video/ogg)</td></tr>
<tr><td>Audio</td><td><b>MP3</b> (audio/mpeg), WAV (audio/wav), Ogg (audio/ogg)</td></tr></tbody></table>
<pre>&lt;video width="400" controls autoplay muted&gt;
  &lt;source src="lesson.mp4" type="video/mp4"&gt;
  &lt;track src="subs.vtt" kind="subtitles" srclang="en"&gt;
  Your browser does not support video.
&lt;/video&gt;
&lt;audio controls&gt;&lt;source src="song.mp3" type="audio/mpeg"&gt;&lt;/audio&gt;</pre>
<p><b>controls</b> adds play/pause/volume. Tags: <code>&lt;video&gt;</code>, <code>&lt;audio&gt;</code>, <code>&lt;source&gt;</code> (multiple resources), <code>&lt;track&gt;</code> (subtitles). DOM: methods <code>play()</code>, <code>pause()</code>, <code>load()</code>; properties <code>currentTime</code>, <code>duration</code>, <code>paused</code>, <code>muted</code>, <code>volume</code>; events <code>play</code>, <code>pause</code>, <code>ended</code>.</p>` },

  { part: 3, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.120", title: "Develop dynamic HTML pages using JavaScript", m: 11,
    keys: ["Functions", "Return & invocation", "Scope", "String methods", "Number methods", "Math object"],
    html: `
<h3>Functions</h3>
<p>A <b>function</b> is a block of code that does a task and runs when it is <b>called (invoked)</b> – by code, by an event (button click) or automatically.</p>
<pre>function toCelsius(f) {          // f = parameter
  return (5 / 9) * (f - 32);     // return ends the function and gives a value
}
let c = toCelsius(98.6);         // 98.6 = argument → 37
const add = function (a, b) { return a + b; };   // function expression
const mul = (a, b) =&gt; a * b;                     // arrow function (ES6)
(function () { console.log("runs at once"); })(); // self-invoked</pre>
<ul><li>Names follow variable rules (letters, digits, <code>_</code>, <code>$</code>).</li><li>Parameters are separated by commas and act like <b>local variables</b>.</li><li>A function can be used as a value: <code>let x = toCelsius(77) + " °C";</code></li><li>Calling without <code>()</code> returns the function itself, not its result.</li></ul>
<h3>Scope</h3>
<ul><li><b>Local</b> variables – declared inside a function; exist only while it runs.</li><li><b>Global</b> variables – declared outside functions; all scripts on the page can use them (in a browser they belong to <code>window</code>).</li><li>Assigning to an undeclared variable makes it <b>automatically global</b> – avoid it (strict mode stops it).</li><li><code>let</code> and <code>const</code> also have <b>block scope</b> (inside <code>{ }</code>).</li></ul>
<h3>Strings</h3>
<p>Write with single, double or back quotes. <b>Escape</b> characters: <code>\\'</code> single quote, <code>\\"</code> double quote, <code>\\\\</code> backslash, <code>\\n</code> new line.</p>
<table class="rte-table"><tbody><tr><th>Method / property</th><th>Example → result</th></tr>
<tr><td><code>length</code></td><td><code>"COPA".length</code> → 4</td></tr>
<tr><td><code>indexOf()</code> / <code>lastIndexOf()</code> / <code>search()</code></td><td>Position of text (−1 if not found)</td></tr>
<tr><td><code>slice(start, end)</code></td><td><code>"Kabadi,Cricket".slice(7)</code> → "Cricket" (negative values count from the end)</td></tr>
<tr><td><code>substring(start, end)</code></td><td>Like slice but no negative values</td></tr>
<tr><td><code>replace(a, b)</code></td><td>Replaces the <b>first</b> match only; use <code>/text/g</code> or <code>replaceAll</code> for all</td></tr>
<tr><td><code>toUpperCase()</code> / <code>toLowerCase()</code></td><td>"information technology" → "INFORMATION TECHNOLOGY"</td></tr>
<tr><td><code>concat()</code></td><td><code>"Mr.".concat("Selvaraj")</code> → "Mr.Selvaraj"</td></tr>
<tr><td><code>trim()</code></td><td>Removes spaces from both ends</td></tr>
<tr><td><code>charAt(0)</code> or <code>s[0]</code></td><td>Character at a position</td></tr>
<tr><td><code>split(",")</code></td><td>String → array: "a,b,c" → ["a","b","c"]</td></tr>
<tr><td><code>includes()</code>, <code>startsWith()</code>, <code>padStart()</code></td><td>Check / pad text</td></tr></tbody></table>
<h3>Numbers and Math</h3>
<p><code>(9.656).toFixed(2)</code> → "9.66" · <code>toPrecision(3)</code> · <code>toString()</code> · <code>Number("12")</code> · <code>parseInt("12px")</code> → 12 · <code>parseFloat("3.5kg")</code> → 3.5 · <code>isNaN()</code> · <code>Math.round(), Math.ceil(), Math.floor(), Math.max(), Math.min(), Math.sqrt(), Math.pow(), Math.random(), Math.PI</code>. Random number 1–10: <code>Math.floor(Math.random() * 10) + 1</code>.</p>
<p>Dynamic pages use these with the DOM – see the next lesson.</p>` },

  { part: 3, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.121", title: "Deploy web project using IIS", m: 11,
    keys: ["HTML DOM tree", "Finding elements", "Changing content & style", "Adding / removing elements", "Events", "Deploy on IIS"],
    html: `
<h3>The HTML DOM</h3>
<p>When a page loads, the browser builds the <b>DOM – Document Object Model</b>: a <b>tree of objects</b> (document → html → head/body → elements → text). The DOM is a W3C standard; there is an HTML DOM and an XML DOM. It defines HTML <b>elements as objects</b>, their <b>properties</b>, <b>methods</b> and <b>events</b>, so JavaScript can read and change everything on the page.</p>
<h3>Finding elements</h3>
<table class="rte-table"><tbody><tr><th>Method</th><th>Finds</th></tr>
<tr><td><code>document.getElementById("demo")</code></td><td>One element by id</td></tr><tr><td><code>document.getElementsByTagName("p")</code></td><td>All elements of a tag</td></tr><tr><td><code>document.getElementsByClassName("note")</code></td><td>All elements with a class</td></tr><tr><td><code>document.querySelector(".note p")</code> / <code>querySelectorAll()</code></td><td>By CSS selector</td></tr><tr><td><code>document.forms</code>, <code>images</code>, <code>links</code></td><td>Object collections</td></tr></tbody></table>
<h3>Changing elements</h3>
<pre>const el = document.getElementById("demo");
el.innerHTML = "Hello &lt;b&gt;COPA&lt;/b&gt;";   // change content
el.style.color = "blue";                  // change style
document.getElementById("pic").src = "new.jpg";   // change an attribute
el.setAttribute("title", "Info");
const p = document.createElement("p");    // create
p.textContent = "New paragraph";
document.body.appendChild(p);             // add
el.remove();  /* or parent.removeChild(el), parent.replaceChild(newEl, oldEl) */
document.write("Writes into the output stream");   // only while loading</pre>
<p>Useful document properties: <code>document.title</code>, <code>URL</code>, <code>domain</code>, <code>cookie</code>, <code>readyState</code>, <code>characterSet</code>, <code>doctype</code>, <code>baseURI</code>, <code>lastModified</code>.</p>
<h3>Events</h3>
<table class="rte-table"><tbody><tr><th>Event</th><th>Happens when</th></tr>
<tr><td><code>onclick</code></td><td>An element is clicked</td></tr><tr><td><code>onload</code> / <code>onunload</code></td><td>Page has loaded / user leaves the page</td></tr><tr><td><code>onchange</code></td><td>Value of an input/select changes</td></tr><tr><td><code>onmouseover</code> / <code>onmouseout</code></td><td>Mouse enters / leaves an element</td></tr><tr><td><code>onmousedown</code> / <code>onmouseup</code></td><td>Mouse button pressed / released</td></tr><tr><td><code>onkeydown</code>, <code>onsubmit</code>, <code>onfocus</code>, <code>oninput</code></td><td>Key pressed, form sent, field selected, typing</td></tr></tbody></table>
<pre>&lt;button onclick="showDate()"&gt;Date&lt;/button&gt;                 &lt;!-- HTML attribute --&gt;
document.getElementById("btn").onclick = showDate;          // DOM property
document.getElementById("btn").addEventListener("click", showDate);   // best – many handlers allowed</pre>
<h3>Deploying a web project on IIS (Windows)</h3>
<ol><li><b>Install IIS</b>: Control Panel → Programs → <b>Turn Windows features on or off</b> → tick <b>Internet Information Services</b> → OK.</li><li>Test: open <code>http://localhost</code> – the IIS welcome page appears. Default folder: <code>C:\\inetpub\\wwwroot</code>.</li><li>Simple way: copy your HTML, CSS, JS and images into <code>wwwroot</code> (or a sub-folder) and open <code>http://localhost/foldername/</code>.</li><li>Better: open <b>IIS Manager</b> (<code>inetmgr</code>) → Sites → <b>Add Website</b> → site name, <b>physical path</b> (your project folder), <b>binding</b> (port e.g. 8080 or a host name) → OK.</li><li>Set the <b>Default Document</b> (index.html); Start / Stop / Restart the site; allow the port in the <b>firewall</b> to open it from other PCs: <code>http://&lt;server-IP&gt;:8080</code>.</li></ol>` },

  /* ===================== ADVANCED EXCEL ===================== */
  { part: 3, bm: 33, mt: "Data Visualization or Analysis using Excel", no: "1.33.122-129", title: "Create advanced formulas and macros", m: 12,
    keys: ["References & linking", "Custom number formats", "Protection", "Advanced formulas", "Pivot table & chart", "Conditional formatting", "Power Query", "Macros"],
    html: `
<h3>Types of references</h3>
<table class="rte-table"><tbody><tr><th>Reference</th><th>Example</th><th>When copied</th></tr>
<tr><td>Relative</td><td><code>A1</code></td><td>Row and column both change</td></tr><tr><td>Absolute</td><td><code>$A$1</code></td><td>Column and row do not change</td></tr>
<tr><td>Mixed</td><td><code>A$1</code> / <code>$A1</code></td><td>Row does not change / column does not change</td></tr>
<tr><td>3-D</td><td><code>=SUM(Jan:Mar!B5)</code></td><td>Same cell on several sheets</td></tr>
<tr><td>External link</td><td><code>=[Sales.xlsx]Sheet1!B5</code></td><td>Linked to another workbook (Data → Edit Links)</td></tr></tbody></table>
<h3>Custom number formats (Ctrl + 1 → Number → Custom)</h3>
<p>Codes: <code>0</code> digit (shows zeros), <code>#</code> digit (no extra zeros), <code>,</code> thousands, <code>.</code> decimal, <code>"text"</code>, <code>@</code> text, colours <code>[Red]</code>. Sections: positive;negative;zero;text.</p>
<p>Examples: <code>"₹"#,##0.00</code> → ₹1,250.00 · <code>00000</code> → 00042 · <code>dd-mm-yyyy</code> → 08-11-2026 · <code>0.0" kg"</code> → 52.5 kg · <code>;;;</code> hides the value. Delete a custom format from the same list (cells using it return to General).</p>
<h3>Protection</h3>
<ul><li><b>Protect Sheet</b> (Review tab) – locked cells cannot be edited. First unlock input cells: Format Cells → Protection → untick Locked.</li><li><b>Protect Workbook</b> – structure (no adding, deleting or renaming sheets).</li><li><b>Encrypt with Password</b> (File → Info → Protect Workbook) – password needed to open the file. <b>Microsoft cannot recover forgotten passwords.</b> Sheet protection is not strong security – don't rely on it for secret data.</li></ul>
<h3>Advanced formulas</h3>
<p>Nested <code>IF</code> (<code>=IF(B2&gt;=75,"Distinction",IF(B2&gt;=40,"Pass","Fail"))</code>), <code>IFS</code>, <code>AND</code>/<code>OR</code>, <code>SUMIF(S)</code>, <code>COUNTIF(S)</code>, <code>AVERAGEIF(S)</code>, <code>VLOOKUP(value, table, col, FALSE)</code>, <code>HLOOKUP</code>, <code>INDEX</code> + <code>MATCH</code>, <code>XLOOKUP</code> (365), <code>IFERROR</code>, <code>TEXT(A2,"dd-mmm-yyyy")</code>, <code>RANK</code>, <code>ROUND</code>.</p>
<h3>Pivot tables and pivot charts</h3>
<p>Used for querying large data easily, subtotalling and aggregating numbers, filtering, sorting, grouping, conditional formatting and attractive reports. Expand/collapse items, show details (double-click a value), change summary functions (Sum/Count/Average), calculated fields. Layouts: <b>Compact, Outline, Tabular</b>; subtotals and grand totals on/off; <b>PivotChart</b> and <b>Slicers</b> for interactive dashboards. Data can come from tables, other workbooks, Access, SQL Server, OLAP cubes, XML or .odc connections.</p>
<h3>Conditional formatting (Home tab)</h3>
<p><b>Highlight Cells Rules</b> (greater than, between, duplicates, text contains, dates), <b>Top/Bottom Rules</b> (top 10, above average), <b>Data Bars</b>, <b>Color Scales</b>, <b>Icon Sets</b> (e.g. 5-point rating), New Rule with a formula, Manage Rules, Clear Rules.</p>
<h3>Power Query (Get &amp; Transform Data)</h3>
<p>Tool to import and clean data. Four phases: <b>Connect</b> (to workbook, CSV, web, database), <b>Transform</b> (remove columns, filter, split, change types – steps are recorded), <b>Combine</b> (<b>Append</b> = add rows of tables; <b>Merge</b> = join tables) and <b>Load</b> (to a sheet or the data model). Refresh repeats all steps on new data. It was an add-in for Excel 2010/2013 and is built in from Excel 2016 (Data tab).</p>
<h3>Macros</h3>
<ul><li>A <b>macro</b> records a series of actions so they can be repeated with one click (written in <b>VBA</b>).</li><li><b>Record</b>: View (or Developer) → Macros → <b>Record Macro</b> → name (no spaces), shortcut key, store in this workbook → do the steps → <b>Stop Recording</b>. "Use Relative References" records positions relative to the active cell.</li><li><b>Run</b>: Alt + F8 → choose → Run (or the shortcut / a button). <b>Edit</b>: Alt + F11 (VBA editor).</li><li>Save as <b>Excel Macro-Enabled Workbook (.xlsm)</b>. Macro security: File → Options → Trust Center – enable macros only from trusted files.</li></ul>` },

  /* ===================== E-COMMERCE ===================== */
  { part: 3, bm: 34, mt: "Browse E-Commerce Sites to Identify Products & Services", no: "1.34.130-132", title: "E-Commerce scope and benefits", m: 13,
    keys: ["Definition", "Traditional vs e-commerce", "Types (B2B, B2C, C2C, C2B)", "Scope in India", "Benefits", "Limitations"],
    html: `
<h3>E-commerce</h3>
<p><b>Electronic commerce</b> is buying and selling of goods and services, and transferring money and data, over the <b>internet</b>. It uses technologies such as online catalogues, shopping carts, electronic payment, <b>EDI</b> (Electronic Data Interchange between businesses), internet marketing, supply chain and inventory systems. <b>E-business</b> is wider – all business activities done electronically.</p>
<h3>Traditional commerce vs e-commerce</h3>
<table class="rte-table"><tbody><tr><th>Traditional commerce</th><th>E-commerce</th></tr>
<tr><td>Buyer visits the shop and can physically check the product</td><td>Buyer sees photos, details and reviews online</td></tr><tr><td>Limited opening hours and local area</td><td>Open 24×7, reaches the whole country / world</td></tr>
<tr><td>Face-to-face contact with the seller</td><td>Contact through website, e-mail, chat</td></tr><tr><td>Higher costs – rent, staff</td><td>Lower costs, wider choice, easy price comparison</td></tr>
<tr><td>Cash / card at the counter</td><td>Online payment, UPI, wallets or cash on delivery</td></tr></tbody></table>
<h3>Types of e-commerce</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Meaning</th><th>Example</th></tr>
<tr><td><b>B2B</b></td><td>Business to Business</td><td>IndiaMART, a company buying raw material</td></tr><tr><td><b>B2C</b></td><td>Business to Consumer</td><td>Amazon, Flipkart, Myntra</td></tr>
<tr><td><b>C2C</b></td><td>Consumer to Consumer</td><td>OLX, Quikr, eBay</td></tr><tr><td><b>C2B</b></td><td>Consumer to Business</td><td>Freelancers, photographers selling to companies</td></tr>
<tr><td>B2G / G2C</td><td>Business/Government</td><td>GeM portal, online tax, passport services</td></tr></tbody></table>
<h3>Scope</h3>
<p>Online shopping keeps growing fast in India (cheap smartphones and data, UPI, studies by ASSOCHAM and others). Areas: retail, travel and ticket booking, banking and insurance, education, food delivery, groceries, healthcare, entertainment, government services. It is ideal for <b>niche products</b> that are hard to find locally.</p>
<h3>Benefits</h3>
<ul><li><b>Business</b>: global reach, low set-up and running cost, 24×7 sales, less paperwork, faster order processing, demographic data about customers, e-mail marketing to new and old customers, better inventory and supply management.</li><li><b>Customers</b>: convenience, more choice, price comparison, reviews, home delivery, discounts, easy returns.</li><li><b>Society</b>: work from home, rural access to products and services, less travel.</li></ul>
<h3>Limitations</h3>
<p>Cannot touch or try the product, delivery time, security and fraud risks, need for internet and digital skills, return problems, less personal contact.</p>` },

  { part: 3, bm: 34, mt: "Browse E-Commerce Sites to Identify Products & Services", no: "1.34.133", title: "Undertake transactions on an e-commerce site", m: 13,
    keys: ["E-commerce sites", "Catalogue, cart, checkout", "Payment options", "Order processing", "Authorization & chargeback", "Online services"],
    html: `
<h3>Popular e-commerce sites</h3>
<p>Shopping: Amazon, Flipkart, Myntra, Meesho, Snapdeal, Nykaa · Groceries: BigBasket, Blinkit · Food: Swiggy, Zomato · Travel: IRCTC, MakeMyTrip, redBus · Others: BookMyShow, PolicyBazaar, Government e-Marketplace (GeM).</p>
<h3>Steps of an online purchase</h3>
<ol><li><b>Online catalogue</b> – search and filter products; read description, price, ratings and reviews.</li><li><b>Shopping cart</b> – Add to Cart; change quantity or remove items.</li><li><b>Checkout</b> – log in / sign up, choose delivery address, apply coupon.</li><li><b>Payment</b> – choose the payment method and confirm (OTP / UPI PIN).</li><li><b>Order processing</b> – order confirmation by SMS / e-mail, packing, shipping with a tracking number, delivery.</li><li>After sales – invoice, return / replacement / refund, rating the product.</li></ol>
<h3>Payment options</h3>
<p>Debit / credit card, <b>UPI</b> (Google Pay, PhonePe, Paytm, BHIM), net banking, mobile wallets (Paytm, Amazon Pay; earlier Airtel Money, Vodafone M-Pesa), EMI, gift cards, <b>Cash on Delivery</b>.</p>
<h3>Authorization and chargeback</h3>
<ul><li><b>Authorization</b> – the card issuer (bank) checks the card details and balance and approves or declines the payment.</li><li><b>Chargeback</b> – the bank returns money to the customer when a transaction is disputed (fraud, item not received); the merchant loses that amount.</li></ul>
<h3>Other online transactions</h3>
<p>Paying electricity / water / gas bills, mobile and DTH recharge, FASTag, train / bus / air tickets, insurance premium, school and exam fees, income tax and GST payments, online banking transfers (NEFT, RTGS, IMPS).</p>
<blockquote>Safe shopping: use trusted sites (https and padlock), check the seller and reviews, never share OTP or PIN, keep the order and payment records.</blockquote>` },

  { part: 3, bm: 34, mt: "Browse E-Commerce Sites to Identify Products & Services", no: "1.34.134-137", title: "E-Commerce Security issues and Payment Gateways", m: 13,
    keys: ["E-commerce security", "DoS & DDoS", "Brute force & phishing", "Social engineering", "How to be secure", "Payment gateway"],
    html: `
<h3>E-commerce security</h3>
<p>Protection of e-commerce assets (website, customer data, payments) from unauthorised access, use, change or destruction. Needs: <b>privacy</b>, <b>integrity</b>, <b>authentication</b>, <b>availability</b> and <b>non-repudiation</b>.</p>
<h3>Security threats</h3>
<table class="rte-table"><tbody><tr><th>Threat</th><th>What it does</th></tr>
<tr><td><b>Denial of Service (DoS)</b></td><td>Floods a server with traffic so real users cannot use it</td></tr>
<tr><td>ICMP flood (Smurf attack)</td><td>Sends huge numbers of ping (ICMP) packets with a fake source address</td></tr>
<tr><td>Teardrop attack</td><td>Sends broken (mangled) overlapping IP fragments that crash the system</td></tr>
<tr><td>Phlashing (Permanent DoS)</td><td>Damages hardware firmware so the device must be replaced</td></tr>
<tr><td><b>DDoS</b> (Distributed DoS)</td><td>Many infected computers (a botnet) attack together</td></tr>
<tr><td><b>Brute force</b></td><td>Tries all password combinations to log in</td></tr>
<tr><td><b>Phishing</b></td><td>Fake e-mails, SMS or websites that steal passwords and card details</td></tr>
<tr><td><b>Social engineering</b></td><td>Tricking people (calls, fake support) into giving information – a non-technical attack</td></tr>
<tr><td>Others</td><td>Malware, SQL injection, card fraud, fake sellers, data breaches</td></tr></tbody></table>
<p><b>Signs of a DoS attack</b> (as listed by US-CERT): unusually slow network, a particular website not available, no website can be accessed, a sudden big increase in spam e-mail.</p>
<h3>How to be secure</h3>
<ul><li>Shop only at <b>secure sites</b> – <code>https://</code> and the padlock show <b>SSL/TLS encryption</b>.</li><li><b>Research the site</b> before ordering – physical address, phone number, reviews, return policy; complain to consumer forums (National Consumer Helpline 1915) or cyber crime portal (1930) when cheated.</li><li>Never share OTP, CVV or UPI PIN; use strong, different passwords and 2-factor authentication; avoid public Wi-Fi for payments.</li><li>Businesses: firewalls, anti-DDoS services, updates, <b>PCI DSS</b> card-security standard, 3-D Secure (OTP), regular backups.</li></ul>
<h3>Payment gateway</h3>
<p>A <b>payment gateway</b> is an e-commerce service that <b>authorises</b> card / online payments and securely transfers payment information between the customer, the merchant's website and the banks (like a POS card machine for online shops). Examples: Razorpay, PayU, CCAvenue, Paytm, BillDesk, Stripe, PayPal.</p>
<ol><li>Customer places the order and enters card / UPI details on the website.</li><li>The gateway <b>encrypts</b> the data and sends it to the merchant's bank (<b>acquirer</b>).</li><li>It goes through the card network (Visa, Mastercard, RuPay) to the customer's bank (<b>issuer</b>).</li><li>The issuer checks the details and balance (and OTP) and <b>approves or declines</b>.</li><li>The answer comes back to the website; the order is confirmed; later the money is <b>settled</b> into the merchant's account.</li></ol>` },

  /* ===================== INFORMATION SECURITY ===================== */
  { part: 3, bm: 35, mt: "Protect Information, Computers and Networks from Viruses, Spyware and other Malicious Code", no: "1.35.138", title: "Overview of information security and threats", m: 13,
    keys: ["CIA triad", "Authenticity & non-repudiation", "Information vs cyber security", "Challenges & benefits", "Security framework", "Threats & malware", "Protection"],
    html: `
<h3>Information security</h3>
<p><b>Information security</b> is the protection of information and information systems from unauthorised access, use, disclosure, change or destruction. <b>Information assurance</b> means managing the risks so that information stays reliable and available.</p>
<h3>CIA triad – basic principles</h3>
<table class="rte-table"><tbody><tr><th>Principle</th><th>Meaning</th><th>Example control</th></tr>
<tr><td><b>Confidentiality</b></td><td>Only authorised people can see the information</td><td>Passwords, encryption, access rights</td></tr>
<tr><td><b>Integrity</b></td><td>Information is accurate and not changed without permission</td><td>Checksums, hashing, permissions</td></tr>
<tr><td><b>Availability</b></td><td>Information and systems are available when needed</td><td>Backups, UPS, redundancy, anti-DDoS</td></tr></tbody></table>
<p>Also important: <b>Authenticity</b> (the user / data is genuine – login, digital certificates) and <b>Non-repudiation</b> (a person cannot deny having sent or agreed to something – digital signatures, logs).</p>
<h3>Information security and cyber security</h3>
<p>Information security protects information in <b>any form</b> (paper or digital). <b>Cyber security</b> protects computers, networks, programs and data <b>in cyberspace</b> from digital attacks. Cyber security is a part of information security. An <b>intranet</b> (private network of an organisation) also needs protection – many threats come from inside or from devices connected to the internet.</p>
<h3>Key challenges</h3>
<p>Lack of management focus, no Information Security Management System (<b>ISMS</b>, e.g. ISO/IEC 27001) or governance, unclear or shared roles and responsibilities, pressure of competition, and inadequate <b>training and awareness</b> of staff.</p>
<h3>Benefits</h3>
<p>Protects networks, computers and data from attacks; business continuity; stakeholder and customer confidence; better company credentials; faster recovery after disruption; legal compliance.</p>
<h3>Security framework</h3>
<ul><li><b>Policy</b> – general management statements; <b>Standards</b> – specific mandatory controls; <b>Guidelines</b> – recommendations / best practices; <b>Procedures</b> – step-by-step instructions.</li><li>Functions (NIST Cyber Security Framework): <b>Identify → Protect → Detect → Respond → Recover</b>. Supported by risk assessment, monitoring, training and regular review.</li></ul>
<h3>Threats and malware</h3>
<table class="rte-table"><tbody><tr><th>Malware / threat</th><th>Behaviour</th></tr>
<tr><td><b>Virus</b></td><td>Attaches to files and spreads when the file runs</td></tr><tr><td><b>Worm</b></td><td>Spreads by itself through networks</td></tr><tr><td><b>Trojan horse</b></td><td>Looks useful but does harm in the background</td></tr><tr><td><b>Ransomware</b></td><td>Encrypts files and asks for money</td></tr>
<tr><td><b>Spyware / keylogger</b></td><td>Secretly records activity and keystrokes</td></tr><tr><td>Adware, rootkit, botnet</td><td>Unwanted ads; hides deep in the system; network of infected PCs</td></tr>
<tr><td>Phishing, social engineering</td><td>Tricks users into giving data</td></tr><tr><td>DoS / DDoS, hacking, data breach, insider threat</td><td>Stops service; unauthorised access or misuse of data</td></tr></tbody></table>
<h3>Protection</h3>
<p>Licensed OS and regular <b>updates</b> · <b>antivirus / anti-malware</b> · <b>firewall</b> · strong passwords and <b>2-factor authentication</b> · do not open unknown attachments or links · scan pen drives · regular <b>backups</b> (3-2-1 rule) · encryption · least-privilege user accounts · security awareness training.</p>` },

  { part: 3, bm: 35, mt: "Protect Information, Computers and Networks from Viruses, Spyware and other Malicious Code", no: "1.35.139", title: "Privacy Protection and IT Act", m: 13,
    keys: ["Privacy", "Sensitive data", "Online privacy practices", "Cyber crimes", "Classification", "IT Act 2000", "Need of cyber law"],
    html: `
<h3>Privacy</h3>
<p><b>Privacy</b> is a person's right to control how their personal information is collected and used. Security protects data from unauthorised people; privacy decides <b>who should</b> have access and for what purpose.</p>
<p>Sensitive data: health records, financial and bank details, criminal investigations, residence and location (geolocation), biometrics (Aadhaar), defence data. A <b>privacy breach</b> is when such data is exposed. Technical standards for privacy policies include <b>P3P</b> (Platform for Privacy Preferences), <b>XACML</b> and <b>EPAL</b> (access-control and privacy policy languages).</p>
<h3>Protecting privacy online</h3>
<ul><li>Use different, <b>strong passwords</b> and a password manager; turn on 2-factor authentication.</li><li>Close old online accounts; shred sensitive paper documents.</li><li>Use browser privacy settings – private window, block third-party cookies, clear history; visit only trusted websites.</li><li>Mobile: give apps only needed permissions, lock screen, update apps, turn off location when not needed.</li><li>Do not overshare on social media; beware of spoofed e-mails and SMS.</li></ul>
<h3>Common cyber crimes</h3>
<table class="rte-table"><tbody><tr><th>Crime</th><th>Meaning</th></tr>
<tr><td><b>Hacking</b></td><td>Unauthorised access to or control over a computer system</td></tr><tr><td>Cracking</td><td>Breaking software protection / passwords</td></tr><tr><td><b>E-mail / SMS spoofing</b></td><td>Message that appears to come from someone else</td></tr>
<tr><td>Cyber stalking, defamation</td><td>Following / harassing or insulting someone online</td></tr><tr><td>Carding, cheating &amp; fraud</td><td>Misuse of card details, online fraud</td></tr><tr><td><b>Cyber squatting</b></td><td>Registering a domain name of a famous brand to sell it</td></tr>
<tr><td>Software piracy</td><td>Copying / distributing software illegally</td></tr><tr><td>Cyber vandalism, transmitting viruses</td><td>Damaging data or websites</td></tr><tr><td>Internet time theft</td><td>Using someone else's paid internet</td></tr><tr><td>Cyber terrorism / warfare</td><td>Attacks on a nation's systems for political aims</td></tr></tbody></table>
<p><b>Classification</b>: crimes against <b>persons</b>, against <b>property</b>, against <b>government</b> and against <b>society</b> (child pornography, online gambling, financial crimes, forgery).</p>
<h3>Information Technology Act, 2000 (India)</h3>
<ul><li>India's cyber law; came into force on <b>17 October 2000</b> and was amended in <b>2008</b>. Gives legal recognition to <b>electronic records and digital signatures</b> and punishes cyber crimes.</li><li><b>Section 43</b> – penalty / compensation for damage to a computer system (unauthorised access, downloading data, viruses).</li><li><b>Section 65</b> – tampering with computer source code. <b>Section 66</b> – computer-related offences (hacking). <b>66C</b> – identity theft. <b>66D</b> – cheating by impersonation. <b>66E</b> – violation of privacy. <b>66F</b> – cyber terrorism.</li><li><b>Section 67, 67A, 67B</b> – publishing obscene, sexually explicit and child sexual abuse material.</li><li>Section 66A was struck down by the Supreme Court in 2015. Personal data is now also protected by the <b>Digital Personal Data Protection Act, 2023</b>.</li><li>Report cyber crime: <b>cybercrime.gov.in</b> or helpline <b>1930</b>.</li></ul>
<h3>Need of cyber law</h3>
<p>Growth of online banking, shopping and government services; to punish cyber criminals; to make electronic documents and signatures legally valid; to protect privacy and data; to build trust in e-commerce.</p>` },

  /* ===================== CLOUD ===================== */
  { part: 3, bm: 36, mt: "Cloud Computing", no: "1.36.140-143", title: "Working with Cloud Services", m: 14,
    keys: ["Definition", "History", "IaaS, PaaS, SaaS", "Public, private, hybrid", "Benefits", "Disadvantages", "Examples & backup"],
    html: `
<h3>Cloud computing</h3>
<p><b>Cloud computing</b> is the on-demand delivery of computing resources – servers, storage, databases, networking, software and analytics – <b>over the internet</b>, paying only for what you use, instead of owning and maintaining your own hardware.</p>
<h3>History in short</h3>
<p><b>1999</b> – Salesforce delivers business applications through a website (SaaS) · <b>2002</b> – Amazon Web Services (AWS) · <b>2006</b> – Amazon <b>EC2</b> (rent virtual computers) and Google Apps (now Google Workspace) · 2008–2010 – Google App Engine, Microsoft <b>Azure</b> · today – virtualization, containers and serverless computing.</p>
<h3>Service models</h3>
<table class="rte-table"><tbody><tr><th>Model</th><th>Provider gives</th><th>Examples</th></tr>
<tr><td><b>IaaS</b> – Infrastructure as a Service</td><td>Virtual machines, storage, networks – you manage the OS and apps</td><td>AWS EC2, Azure VMs, Google Compute Engine, DigitalOcean</td></tr>
<tr><td><b>PaaS</b> – Platform as a Service</td><td>Ready platform to build and run apps – you manage only your code</td><td>Google App Engine, Heroku, Azure App Service</td></tr>
<tr><td><b>SaaS</b> – Software as a Service</td><td>Ready-to-use software in the browser</td><td>Gmail, Google Workspace, Microsoft 365, Salesforce, Zoom, Dropbox</td></tr></tbody></table>
<h3>Deployment models</h3>
<ul><li><b>Public cloud</b> – shared infrastructure of a provider (AWS, Azure, Google Cloud).</li><li><b>Private cloud</b> – used by one organisation only (more control and security).</li><li><b>Hybrid cloud</b> – mix of private and public with data / apps moving between them.</li><li><b>Multicloud</b> – services from more than one provider; community cloud – shared by similar organisations.</li></ul>
<h3>Common uses</h3>
<p>File storage and sharing (Google Drive, <b>Dropbox</b>, OneDrive), cloud databases, web applications and hosting, e-mail and collaboration (Microsoft 365, Google Workspace – several people edit the same file), backup and disaster recovery, video streaming, AI and data analysis.</p>
<h3>Benefits</h3>
<p>Low cost (no big hardware purchase), <b>flexibility / scalability</b> (add or reduce resources quickly), <b>mobility</b> (access from anywhere on any device), better <b>collaboration</b>, automatic updates, <b>disaster recovery</b> and backup, security provided by experts.</p>
<h3>Disadvantages</h3>
<p>Needs a good internet connection, possible downtime, data security and privacy concerns, less control, vendor lock-in, costs can grow if not monitored.</p>
<h3>Shared responsibility</h3>
<p>The provider secures the data centres and infrastructure; the <b>customer</b> is responsible for their data, accounts and passwords. Even SaaS data (Microsoft 365, Google Workspace, Salesforce) should have a separate <b>backup</b> (third-party backup tools).</p>
<p>Learning resources: free courses on AWS, Microsoft Learn, Google Cloud Skills, NPTEL, Coursera, edX, Udemy and Internshala.</p>` },

  /* ===================== SDLC ===================== */
  { part: 3, bm: 37, mt: "Develop an application and perform the Application Development Life Cycle", no: "1.37.144&145", title: "Identify Phases of the Application Development Life Cycle", m: 14,
    keys: ["SDLC phases", "Planning & analysis", "Design & prototyping", "Development & testing", "Implementation & maintenance", "SDLC models", "System analyst"],
    html: `
<h3>Software (Application) Development Life Cycle – SDLC</h3>
<p>The <b>SDLC</b> is a step-by-step process to plan, build, test, deliver and maintain software of good quality, on time and within budget.</p>
<table class="rte-table"><tbody><tr><th>Phase</th><th>Main work</th><th>Output</th></tr>
<tr><td>1. <b>Planning</b></td><td>Define the problem, scope, cost, time, resources and risks</td><td>Project plan</td></tr>
<tr><td>2. <b>Requirement analysis / feasibility</b></td><td>Meet users, gather exact needs; check technical, economic and operational feasibility; study alternatives and prototypes</td><td><b>SRS</b> – Software Requirement Specification</td></tr>
<tr><td>3. <b>Design &amp; prototyping</b></td><td>Architecture, user interfaces, system interfaces, network, <b>database</b> design, security; prototypes for feedback</td><td>Design document</td></tr>
<tr><td>4. <b>Development (coding)</b></td><td>Programmers write code in the chosen languages (Python, Java, PHP, JavaScript…) following standards</td><td>Working software</td></tr>
<tr><td>5. <b>Testing</b></td><td>Unit, integration, system and user acceptance testing; find and fix bugs</td><td>Tested software</td></tr>
<tr><td>6. <b>Implementation &amp; integration</b></td><td>Install / deploy for users, integrate with existing systems, train users, migrate data</td><td>Live system</td></tr>
<tr><td>7. <b>Operations &amp; maintenance</b></td><td>Fix new bugs, updates, improvements, performance and security patches</td><td>Updated versions</td></tr></tbody></table>
<h3>SDLC models</h3>
<table class="rte-table"><tbody><tr><th>Model</th><th>Idea</th><th>Suitable for</th></tr>
<tr><td><b>Waterfall</b></td><td>Linear – each phase finishes before the next; simple</td><td>Small projects with clear, fixed requirements</td></tr>
<tr><td><b>V-Model</b></td><td>Each development phase has a matching testing phase</td><td>Projects needing strict testing</td></tr>
<tr><td><b>Iterative</b></td><td>Build a basic version, then improve it in repeated cycles</td><td>Large projects; early working versions</td></tr>
<tr><td><b>Spiral</b></td><td>Repeated cycles with strong <b>risk analysis</b> in each loop</td><td>Large, risky projects</td></tr>
<tr><td><b>Agile</b> (Scrum)</td><td>Short sprints (1–4 weeks), continuous customer feedback</td><td>Changing requirements, most modern apps</td></tr>
<tr><td><b>Big Bang</b></td><td>Start coding with little planning</td><td>Very small or student projects</td></tr></tbody></table>
<h3>Role of the system analyst</h3>
<ul><li>Gathers facts and information from users; studies the existing system.</li><li>Decides priorities (which bugs or features first) and suggests alternative solutions.</li><li>Writes specifications that users and programmers can easily understand.</li><li>Designs logical systems while keeping modularity; coordinates between users, managers and developers.</li><li>Should have good <b>communication</b>, analytical, problem-solving and technical knowledge, and be a good planner.</li></ul>` }
]);
