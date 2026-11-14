<?php
/* ITI Fitter – 1st Year theory notes, Modules 1 to 8 (NSQF trade theory topics).
   Loaded once by seed_notes_fitter(); after that the admin edits them in Admin → Theory Notes.
   [module no, title, summary, [[lesson no, title, summary, image, body html], ...]] */
$I = '/assets/img/notes/fitter/';
return [
['1', 'Safety', 'ITI familiarisation, safety precautions, PPE, safety signs, fire safety, 5S and safe lifting.', [

['1.1', 'ITI Familiarisation', 'What an ITI is, how the Fitter course runs, who is who in the institute and what you can do after it.', '', <<<'H'
<h2>What is an ITI?</h2>
<p>An <strong>Industrial Training Institute (ITI)</strong> gives job-oriented skill training under the <strong>Craftsmen Training Scheme (CTS)</strong>. The scheme is run by the <strong>Directorate General of Training (DGT)</strong>, Ministry of Skill Development &amp; Entrepreneurship, Government of India. After passing the All India Trade Test you get the <strong>National Trade Certificate (NTC)</strong> issued by NCVT.</p>
<div class="nb-key"><strong>Objective of ITI training</strong>
<ul><li>Supply skilled manpower (craftsmen) to industry.</li><li>Train young people for wage-employment and self-employment.</li><li>Reduce unemployment by giving practical, hands-on skills.</li></ul></div>
<h2>Fitter trade at a glance</h2>
<table><tr><th>Item</th><th>Details</th></tr>
<tr><td>Duration</td><td>2 years (4 semesters / 2 annual exams)</td></tr>
<tr><td>Sector</td><td>Capital Goods &amp; Manufacturing</td></tr>
<tr><td>Subjects</td><td>Trade Practical, Trade Theory, Workshop Calculation &amp; Science, Engineering Drawing, Employability Skills</td></tr>
<tr><td>Exam</td><td>CBT (online) for theory subjects + practical exam</td></tr></table>
<h2>Who is who in an ITI</h2>
<ul><li><strong>Principal</strong> – head of the institute.</li><li><strong>Vice-Principal / Group Instructor</strong> – looks after a group of trades.</li><li><strong>Training Officer / Instructor (Craft Instructor)</strong> – teaches theory and practical of the trade.</li><li><strong>Office staff, store keeper, librarian</strong> – administration, tools and books.</li></ul>
<h2>Facilities</h2>
<p>Workshop with machines and tools, theory classroom, drawing hall, library, computer lab, hostel (in many ITIs), placement cell and sports.</p>
<h2>After ITI Fitter</h2>
<ul><li><strong>Apprenticeship</strong> under the Apprentices Act, 1961 (railways, PSUs, private industry).</li><li>Jobs as fitter, maintenance fitter, assembly fitter, machine operator.</li><li>Higher study: diploma (lateral entry) or CITS to become an instructor.</li><li>Self-employment: fabrication or repair workshop.</li></ul>
<details><summary>Quick Revision</summary><ul><li>ITI training runs under CTS, controlled by DGT.</li><li>Certificate after passing: NTC by NCVT.</li><li>Fitter is a 2-year engineering trade.</li><li>Apprenticeship comes under the Apprentices Act, 1961.</li></ul></details>
H],

['1.2', 'Safety Precautions in the Workshop', 'Accidents do not happen, they are caused. General, personal and machine safety rules every fitter must follow.', $I . 'workshop-safety.webp', <<<'H'
<p><strong>Safety</strong> means working in a way that protects you, other people, the machine and the job. Remember: <em>accidents do not happen – they are caused</em>, mostly by carelessness, hurry or lack of knowledge.</p>
<h2>Causes of accidents</h2>
<ul><li><strong>Unsafe acts</strong> – working without guards, not wearing PPE, horse-play, using wrong tools, hurry.</li><li><strong>Unsafe conditions</strong> – oily floor, poor light, loose wiring, broken tools, no ventilation.</li></ul>
<h2>1. General safety</h2>
<ul><li>Keep the floor clean and dry; wipe oil and coolant at once.</li><li>Walk – never run – in the workshop. Use marked gangways.</li><li>Keep tools in their proper place; never leave tools on the edge of the bench or on a machine.</li><li>Know where the first-aid box, fire extinguisher and main switch are.</li><li>Report every accident and injury, however small, to the instructor.</li></ul>
<h2>2. Personal safety</h2>
<ul><li>Wear well-fitting overalls with sleeves buttoned or rolled up.</li><li>Wear safety shoes. Do not wear rings, watches, chains or loose clothes near machines.</li><li>Tie long hair and cover it with a cap.</li><li>Do not remove chips with bare hands – use a brush or chip hook.</li></ul>
<h2>3. Machine safety</h2>
<ul><li>Never operate a machine without the instructor's permission and full knowledge of it.</li><li>Keep all guards in place.</li><li><strong>Stop the machine before measuring, cleaning or adjusting.</strong></li><li>Never leave a running machine unattended.</li><li>Switch off the power in case of power failure.</li></ul>
<div class="nb-warn"><strong>Electrical safety</strong> Do not touch switches with wet hands. Report damaged plugs and cables. Only a qualified electrician should repair electrical faults.</div>
<details><summary>Quick Revision</summary><ul><li>Accidents are caused by unsafe acts and unsafe conditions.</li><li>Three groups of safety: general, personal, machine.</li><li>Stop the machine before measuring or cleaning.</li><li>Remove chips with a brush, never with hands.</li></ul></details>
H],

['1.3', 'Personal Protective Equipment (PPE)', 'Types of PPE, which hazard each protects from and how to choose and care for it.', $I . 'ppe.webp', <<<'H'
<p><strong>Personal Protective Equipment (PPE)</strong> is equipment worn by a worker to protect the body from hazards in the workplace. PPE is the <em>last line of defence</em> – first remove or control the hazard, then use PPE for the remaining risk.</p>
<h2>Types of PPE and their use</h2>
<table><tr><th>Body part</th><th>PPE</th><th>Protects from</th></tr>
<tr><td>Head</td><td>Safety helmet</td><td>Falling objects, bumping the head</td></tr>
<tr><td>Eyes</td><td>Safety goggles</td><td>Flying chips, grinding sparks, dust</td></tr>
<tr><td>Face</td><td>Face shield, welding helmet</td><td>Sparks, hot metal, arc rays</td></tr>
<tr><td>Ears</td><td>Ear plugs, ear muffs</td><td>High noise</td></tr>
<tr><td>Lungs</td><td>Dust mask, respirator</td><td>Dust, fumes, gases</td></tr>
<tr><td>Hands</td><td>Gloves (leather, rubber, cotton)</td><td>Cuts, heat, chemicals, electricity</td></tr>
<tr><td>Body</td><td>Overall, leather apron</td><td>Dirt, oil, sparks, hot metal</td></tr>
<tr><td>Feet</td><td>Safety shoes (steel toe)</td><td>Falling objects, sharp chips</td></tr></table>
<div class="nb-warn"><strong>Never wear gloves on a rotating machine</strong> (drill, lathe). A glove can be caught and pull the hand into the machine.</div>
<h2>Choosing PPE</h2>
<ul><li>Select according to the <strong>hazard</strong> of the job.</li><li>It must meet the standard (BIS / ISI mark) and fit the worker properly.</li><li>It should be comfortable so that the worker actually wears it.</li></ul>
<h2>Care of PPE</h2>
<ul><li>Inspect before use; replace damaged PPE.</li><li>Clean after use and store in a clean, dry place.</li><li>Use of PPE is <strong>mandatory</strong> where it is prescribed.</li></ul>
<details><summary>Quick Revision</summary><ul><li>PPE = last line of defence.</li><li>Goggles for eyes, ear muffs for noise, safety shoes for feet.</li><li>No gloves on rotating machines.</li><li>Choose PPE by hazard; it must meet standards.</li></ul></details>
H],

['1.4', 'Safety Signs and Fire Safety', 'Colours and shapes of safety signs, classes of fire and the right extinguisher for each.', '', <<<'H'
<h2>Safety signs</h2>
<p>Safety signs give a safety message quickly by <strong>shape, colour and symbol</strong>, so that even a person who cannot read the language understands it.</p>
<table><tr><th>Type</th><th>Shape &amp; colour</th><th>Meaning / example</th></tr>
<tr><td>Prohibition</td><td>Red circle with a diagonal bar</td><td>Do not do – "No smoking"</td></tr>
<tr><td>Mandatory</td><td>Blue circle, white symbol</td><td>Must do – "Wear goggles"</td></tr>
<tr><td>Warning</td><td>Yellow triangle, black border</td><td>Be careful – "High voltage"</td></tr>
<tr><td>Safe condition</td><td>Green square/rectangle</td><td>Safe way – "First aid", "Exit"</td></tr>
<tr><td>Fire equipment</td><td>Red square/rectangle</td><td>"Fire extinguisher", "Fire alarm"</td></tr></table>
<h2>Fire</h2>
<p>Fire needs three things – <strong>fuel, heat and oxygen</strong> (the <em>fire triangle</em>). Remove any one and the fire goes out: starving (remove fuel), smothering (cut oxygen), cooling (remove heat).</p>
<h3>Classes of fire and extinguishers</h3>
<table><tr><th>Class</th><th>Burning material</th><th>Use</th></tr>
<tr><td>A</td><td>Wood, paper, cloth, cotton waste</td><td>Water, foam</td></tr>
<tr><td>B</td><td>Oil, petrol, paint, grease</td><td>Foam, CO₂, dry chemical powder (DCP)</td></tr>
<tr><td>C</td><td>Gases – LPG, acetylene</td><td>DCP (first shut off the gas)</td></tr>
<tr><td>D</td><td>Metals – magnesium, sodium</td><td>Special dry powder</td></tr>
<tr><td>Electrical</td><td>Live electrical equipment</td><td>CO₂ or DCP – <strong>never water</strong></td></tr></table>
<div class="nb-tip"><strong>Using an extinguisher – PASS</strong> <b>P</b>ull the pin · <b>A</b>im at the base of the fire · <b>S</b>queeze the handle · <b>S</b>weep from side to side.</div>
<details><summary>Quick Revision</summary><ul><li>Red circle = prohibition, blue circle = mandatory, yellow triangle = warning, green = safe condition.</li><li>Fire triangle: fuel + heat + oxygen.</li><li>Never use water on oil or electrical fires.</li><li>PASS: Pull, Aim, Squeeze, Sweep.</li></ul></details>
H],

['1.5', 'Housekeeping, 5S and Waste Disposal', 'Keeping the workshop clean and organised with 5S, and disposing of shop waste safely.', '', <<<'H'
<p><strong>Good housekeeping</strong> means a clean, orderly workplace. It prevents accidents, saves time searching for tools and improves quality.</p>
<h2>5S – a Japanese system of housekeeping</h2>
<table><tr><th>S</th><th>Japanese</th><th>Meaning</th></tr>
<tr><td>1</td><td>Seiri</td><td><strong>Sort</strong> – remove what is not needed.</td></tr>
<tr><td>2</td><td>Seiton</td><td><strong>Set in order</strong> – a place for everything, everything in its place.</td></tr>
<tr><td>3</td><td>Seiso</td><td><strong>Shine</strong> – clean the work area and machines.</td></tr>
<tr><td>4</td><td>Seiketsu</td><td><strong>Standardise</strong> – make the first three a written routine.</td></tr>
<tr><td>5</td><td>Shitsuke</td><td><strong>Sustain</strong> – self-discipline to follow it every day.</td></tr></table>
<h2>Waste in a fitting shop</h2>
<ul><li>Metal chips and scrap pieces</li><li>Oily cotton waste and rags</li><li>Used coolant, oil and grease</li><li>Paper, packing material, broken tools</li></ul>
<h2>Safe disposal</h2>
<ul><li><strong>Segregate</strong> waste – metal, oily, paper, chemical – in separate marked bins.</li><li>Keep oily cotton waste in a <strong>closed metal bin</strong>; it can catch fire by itself.</li><li>Metal scrap is collected for recycling.</li><li>Used oil and coolant are collected in drums, not poured into drains.</li><li>Follow the "reduce, reuse, recycle" rule.</li></ul>
<details><summary>Quick Revision</summary><ul><li>5S: Sort, Set in order, Shine, Standardise, Sustain.</li><li>Oily waste → closed metal bin.</li><li>Segregate waste before disposal.</li></ul></details>
H],

['1.6', 'Hot Work and Safe Lifting', 'Precautions for welding, grinding and cutting, and the correct way to lift and carry loads.', '', <<<'H'
<h2>Hot work</h2>
<p><strong>Hot work</strong> is any work that makes flame, sparks or heat – welding, gas cutting, grinding, brazing, soldering.</p>
<ul><li>Take a <strong>hot work permit</strong> where required.</li><li>Remove or cover flammable material (oil, cotton waste, paper) around the area.</li><li>Keep a suitable fire extinguisher ready.</li><li>Use proper PPE: goggles/face shield, leather gloves, apron.</li><li>Provide good ventilation to remove fumes.</li><li>After the job, watch the area for some time for hidden fire.</li></ul>
<h2>Safe lifting of loads</h2>
<p>Wrong lifting is the most common cause of back injury.</p>
<ol><li>Check the weight and size of the load; get help or a lifting device if it is heavy.</li><li>Stand close to the load, feet apart for balance.</li><li><strong>Bend the knees, keep the back straight.</strong></li><li>Grip the load firmly and lift by straightening the legs.</li><li>Keep the load close to the body; do not twist – turn with your feet.</li><li>Put the load down by bending the knees again.</li></ol>
<div class="nb-warn"><strong>Lifting equipment</strong> Use chain blocks, cranes and slings only if trained. Never stand under a suspended load. Check the safe working load (SWL) marked on the equipment.</div>
<details><summary>Quick Revision</summary><ul><li>Hot work = welding, cutting, grinding – remove flammables, keep extinguisher.</li><li>Lift with legs, not with back.</li><li>Never stand under a suspended load.</li></ul></details>
H],
]],

['2', 'Basic Fitting', 'Measuring and marking tools, bench vice, hacksaw, files, chisel, vernier caliper, micrometer and properties of metals.', [

['2.1', 'Linear Measurement and the Steel Rule', 'Units of length, the engineer\'s steel rule and how to read it without parallax error.', '', <<<'H'
<p><strong>Linear measurement</strong> is measuring length, width, height, thickness and diameter. In engineering we use the <strong>metric system</strong>: the base unit is the <strong>metre (m)</strong>, and most shop measurements are in <strong>millimetres (mm)</strong>.</p>
<div class="nb-formula">1 m = 100 cm = 1000 mm<br>1 mm = 1000 microns (µm)<br>1 inch = 25.4 mm</div>
<h2>Engineer's steel rule</h2>
<ul><li>Made of spring steel or stainless steel.</li><li>Common sizes: <strong>150 mm, 300 mm, 600 mm</strong>.</li><li>Graduations in mm and 0.5 mm (and inches on the other side).</li><li>Accuracy: about <strong>±0.5 mm</strong>.</li></ul>
<h2>Correct use</h2>
<ul><li>Keep the rule's edge on the line of measurement, not at an angle.</li><li>Do not measure from the worn end; start from the 10 mm mark and subtract 10.</li><li><strong>Keep your eye exactly above the reading</strong>. Looking from a side gives a wrong reading called <em>parallax error</em>.</li></ul>
<h2>Care</h2>
<ul><li>Do not use the rule as a screwdriver or scraper.</li><li>Keep it away from cutting tools; apply a thin film of oil to prevent rust.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Shop unit of length: mm.</li><li>Steel rule accuracy ±0.5 mm.</li><li>Eye exactly above the mark – avoids parallax error.</li></ul></details>
H],

['2.2', 'Calipers and Divider', 'Outside, inside and odd-leg calipers and the divider – what each one measures or marks.', '', <<<'H'
<p><strong>Calipers</strong> are <em>indirect</em> measuring tools: they take the size from the job, then the size is read on a steel rule. They are made of carbon steel and their legs are hardened at the tips.</p>
<h2>Types by joint</h2>
<ul><li><strong>Firm joint calipers</strong> – legs held by friction of a rivet joint.</li><li><strong>Spring joint calipers</strong> – legs held by a spring and set with a nut; setting does not slip.</li></ul>
<h2>Types by use</h2>
<table><tr><th>Tool</th><th>Use</th></tr>
<tr><td>Outside caliper</td><td>Outside diameter, thickness – legs bent inward.</td></tr>
<tr><td>Inside caliper</td><td>Bore diameter, width of slots – legs bent outward.</td></tr>
<tr><td>Odd-leg caliper (Jenny)</td><td>One bent leg, one pointed leg: marks lines parallel to an edge and finds the centre of round bars.</td></tr>
<tr><td>Divider</td><td>Both legs pointed: scribes circles and arcs, transfers and divides distances.</td></tr></table>
<div class="nb-tip"><strong>Correct "feel"</strong> The caliper should just touch the job and pass over it with light pressure. Do not force it – the legs will spring and give a wrong size.</div>
<p>Size of a caliper or divider is the distance from the centre of the joint (pivot) to the tip of the leg.</p>
<details><summary>Quick Revision</summary><ul><li>Calipers transfer size; read it on a steel rule.</li><li>Outside → OD, inside → bore, odd-leg → parallel lines/centre, divider → circles.</li><li>Spring joint calipers hold their setting best.</li></ul></details>
H],

['2.3', 'Marking Tools: Punches, Marking Media, Surface Plate, Angle Plate, V-Block', 'Tools and surfaces used to mark out a job accurately before cutting.', '', <<<'H'
<p><strong>Marking</strong> means drawing the lines, centres and outlines on the job from the drawing, so that the job can be cut and filed to size.</p>
<h2>Marking media</h2>
<table><tr><th>Medium</th><th>Used on</th></tr>
<tr><td>Chalk / whitewash</td><td>Rough castings and forgings</td></tr>
<tr><td>Copper sulphate solution</td><td>Filed or machined steel surfaces</td></tr>
<tr><td>Prussian blue</td><td>Finished surfaces; also for checking flatness in scraping</td></tr>
<tr><td>Cellulose lacquer (layout dye)</td><td>Finished surfaces – dries fast, lines are very clear</td></tr></table>
<h2>Scriber and punches</h2>
<ul><li><strong>Scriber</strong> – hardened pointed steel tool for drawing lines.</li><li><strong>Prick punch (30°)</strong> – light marks for divider point location.</li><li><strong>Dot punch (60°)</strong> – makes witness marks along scribed lines.</li><li><strong>Centre punch (90°)</strong> – makes a large mark to start a drill.</li></ul>
<h2>Surface plate</h2>
<p>A very flat, rigid plate (cast iron or granite) used as a <strong>datum surface</strong> for marking and checking. Grades 1, 2 and 3 (grade 1 is the most accurate). Never hammer on it; cover it when not in use.</p>
<h2>Angle plate</h2>
<p>Cast iron block with two faces machined exactly at <strong>90°</strong>. The job is clamped to it to mark or machine surfaces square to each other.</p>
<h2>V-block</h2>
<p>A hardened steel block with a <strong>90° V-groove</strong>. It holds round bars for marking centres and drilling. Used in pairs (matched set) with a clamp.</p>
<details><summary>Quick Revision</summary><ul><li>Prussian blue / layout dye on finished work, chalk on castings.</li><li>Prick 30°, dot 60°, centre 90°.</li><li>Surface plate = datum; angle plate = 90°; V-block = holding round jobs.</li></ul></details>
H],

['2.4', 'Bench Vice and Hacksaw', 'Parts and use of the bench vice; hacksaw frame, blade pitch and correct sawing method.', $I . 'bench-vice-hacksaw.webp', <<<'H'
<h2>Bench vice</h2>
<p>A holding device fixed on the work bench. It holds the job firmly for filing, sawing and chipping. Its size is given by the <strong>width of the jaws</strong> (e.g. 100 mm, 150 mm).</p>
<p><strong>Parts:</strong> fixed jaw, movable jaw, hardened jaw plates, spindle (screw), handle, box nut, base.</p>
<ul><li>Height of the vice top should be at the worker's <strong>elbow height</strong>.</li><li>Use <strong>soft jaws (vice clamps)</strong> of aluminium/copper to protect finished surfaces.</li><li>Do not hammer on the handle to tighten; do not extend the handle with a pipe.</li><li>Keep the screw clean and oiled.</li></ul>
<h2>Hacksaw</h2>
<p>Used to cut metal by hand. It has a <strong>frame</strong> (fixed or adjustable), a <strong>blade</strong>, wing nut and handle.</p>
<h3>Hacksaw blade</h3>
<ul><li>Length: 250 mm or 300 mm (between hole centres).</li><li>Material: high-speed steel or low-alloy steel. <strong>All-hard</strong> blades for rigid cutting, <strong>flexible</strong> blades (only teeth hardened) for general work.</li><li><strong>Pitch</strong> = distance between two teeth.</li></ul>
<table><tr><th>Pitch</th><th>Use</th></tr>
<tr><td>1.8 mm (coarse)</td><td>Soft metals, thick sections – aluminium, brass, mild steel bars</td></tr>
<tr><td>1.4 mm</td><td>Mild steel, cast iron, general work</td></tr>
<tr><td>1.0 mm</td><td>Angle iron, thicker tubes, tool steel</td></tr>
<tr><td>0.8 mm (fine)</td><td>Thin tubes and sheets</td></tr></table>
<div class="nb-key"><strong>Rule</strong> At least <strong>three teeth</strong> must be in contact with the job. Fix the blade with <strong>teeth facing forward</strong> – it cuts on the forward stroke.</div>
<h3>Sawing method</h3>
<ul><li>Tension the blade properly with the wing nut.</li><li>Start the cut with a small file nick and light pressure.</li><li>Use the full length of the blade, about 40–50 strokes per minute.</li><li>Pressure on forward stroke only; release on return stroke.</li><li>Ease the pressure when the cut is about to finish.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Vice size = jaw width; top at elbow height.</li><li>Teeth forward; cut on forward stroke.</li><li>Fine pitch for thin work, coarse for soft/thick work.</li><li>Minimum 3 teeth in contact.</li></ul></details>
H],

['2.5', 'Files', 'Parts, cuts, grades and shapes of files, and how to choose the right file.', '', <<<'H'
<p>A <strong>file</strong> is a hardened high-carbon steel cutting tool with many small teeth, used to remove metal and to finish surfaces to size.</p>
<h2>Parts of a file</h2>
<p>Tip (point), face, edge, heel, shoulder, tang and handle. <strong>Length</strong> of a file is measured from the tip to the heel (tang not included) – common lengths 150, 200, 250, 300 mm.</p>
<h2>Cut of a file</h2>
<ul><li><strong>Single cut</strong> – one set of parallel teeth; for soft metals and sharpening.</li><li><strong>Double cut</strong> – two sets crossing; fast removal on steel and cast iron.</li><li><strong>Rasp cut</strong> – separate pointed teeth; for wood, leather, plastics.</li><li><strong>Curved cut</strong> – for soft metals like aluminium and lead.</li></ul>
<h2>Grade (coarseness)</h2>
<p>Rough → Bastard → Second cut → Smooth → Dead smooth. Rough and bastard files remove more metal; smooth and dead smooth give a fine finish.</p>
<h2>Shapes</h2>
<table><tr><th>Shape</th><th>Use</th></tr>
<tr><td>Flat</td><td>General flat surfaces</td></tr>
<tr><td>Hand</td><td>Like flat, but one safe edge (no teeth) – filing a step without touching the side</td></tr>
<tr><td>Square</td><td>Square holes and slots</td></tr>
<tr><td>Round</td><td>Enlarging round holes, inside curves</td></tr>
<tr><td>Half-round</td><td>Concave curves and flat surfaces</td></tr>
<tr><td>Triangular</td><td>Corners less than 90°, sharpening</td></tr>
<tr><td>Knife-edge</td><td>Narrow slots, acute corners</td></tr></table>
<div class="nb-tip"><strong>Good filing practice</strong> Push the file forward with pressure, lift slightly on the return stroke. Clean the teeth with a <strong>file card</strong>; rub chalk on a new file for soft metals to stop pinning. Never use a file without a handle.</div>
<details><summary>Quick Revision</summary><ul><li>File length: tip to heel.</li><li>Single, double, rasp, curved cut.</li><li>Grades: rough, bastard, second cut, smooth, dead smooth.</li><li>Hand file has one safe edge.</li></ul></details>
H],

['2.6', 'Try Square and Cold Chisel', 'Checking squareness with a try square; types and point angles of cold chisels and safe chipping.', '', <<<'H'
<h2>Try square</h2>
<p>A precision tool to check <strong>squareness (90°)</strong> and <strong>flatness</strong> of surfaces and to mark lines at 90° to an edge.</p>
<ul><li>Parts: <strong>stock</strong> and <strong>blade</strong>, fixed exactly at 90°. Made of hardened and ground steel.</li><li>Size = length of the blade (e.g. 100, 150 mm).</li><li><strong>Checking:</strong> hold the stock firmly against the finished face, bring the blade down on the surface and look against light. No light gap = square.</li></ul>
<h2>Cold chisel</h2>
<p>A hand cutting tool made of high-carbon steel, used with a hammer for <strong>chipping</strong> – removing metal, cutting sheets and cutting off rivet heads. "Cold" because it cuts metal in cold state.</p>
<h3>Types</h3>
<ul><li><strong>Flat chisel</strong> – removing metal from flat surfaces, cutting sheets.</li><li><strong>Cross-cut chisel</strong> – cutting keyways and narrow grooves.</li><li><strong>Half-round chisel</strong> – cutting oil grooves, correcting drill hole position.</li><li><strong>Diamond point chisel</strong> – square corners and V-grooves.</li><li><strong>Web chisel</strong> – removing metal between chain-drilled holes.</li></ul>
<h3>Point angle of chisel</h3>
<table><tr><th>Material</th><th>Point angle</th></tr>
<tr><td>Aluminium</td><td>30°</td></tr><tr><td>Copper</td><td>45°</td></tr><tr><td>Brass</td><td>50°</td></tr><tr><td>Mild steel</td><td>55°</td></tr><tr><td>Cast iron</td><td>60°</td></tr></table>
<p>Harder the metal, larger the point angle. The chisel is held at <strong>about 35°–40°</strong> to the surface while chipping.</p>
<div class="nb-warn"><strong>Mushroom head</strong> Grind off the mushroom head of a chisel – pieces can fly and hurt. Wear goggles and use a chipping screen.</div>
<details><summary>Quick Revision</summary><ul><li>Try square checks 90° and flatness (light gap test).</li><li>Five types of chisels: flat, cross-cut, half-round, diamond point, web.</li><li>MS 55°, CI 60°, brass 50°, Cu 45°, Al 30°.</li></ul></details>
H],

['2.7', 'Vernier Caliper', 'Principle, parts and least count (0.02 mm) of the vernier caliper, with a reading example.', '', <<<'H'
<p>The <strong>vernier caliper</strong> is a precision instrument for measuring outside, inside and depth sizes to an accuracy of <strong>0.02 mm</strong>.</p>
<h2>Principle</h2>
<p>It works on the <strong>difference between two scales</strong> – the main scale and the vernier scale. In a common metric vernier, <strong>49 mm</strong> of the main scale is divided into <strong>50 equal divisions</strong> on the vernier.</p>
<div class="nb-formula">1 MSD = 1 mm<br>1 VSD = 49/50 = 0.98 mm<br>Least count = 1 MSD − 1 VSD = 1 − 0.98 = <strong>0.02 mm</strong></div>
<h2>Parts</h2>
<p>Beam with main scale, fixed jaw, sliding jaw with vernier scale, outside measuring jaws, inside measuring jaws (nibs), depth bar, locking screw, fine adjustment screw.</p>
<h2>Taking a reading</h2>
<div class="nb-formula">Reading = Main scale reading + (Coinciding vernier division × LC)</div>
<p><strong>Example:</strong> Zero of the vernier is just past 32 mm on the main scale, and the 18th vernier line coincides with a main scale line.<br>Reading = 32 + (18 × 0.02) = 32 + 0.36 = <strong>32.36 mm</strong>.</p>
<h2>Precautions</h2>
<ul><li>Check the <strong>zero error</strong> before use – close the jaws and see that both zeros coincide.</li><li>Clean the jaws and the job.</li><li>Do not press hard; lock the jaw and read straight in front of the eye.</li><li>Never measure a moving or hot job.</li></ul>
<details><summary>Quick Revision</summary><ul><li>49 mm = 50 VSD → LC 0.02 mm.</li><li>Reading = MSR + (VC × LC).</li><li>Measures outside, inside and depth.</li><li>Always check zero error first.</li></ul></details>
H],

['2.8', 'Outside Micrometer', 'Principle, parts and least count (0.01 mm) of the outside micrometer, reading and precautions.', $I . 'outside-micrometer.webp', <<<'H'
<p>The <strong>outside micrometer</strong> measures outside sizes (diameter, thickness) to an accuracy of <strong>0.01 mm</strong>. Common ranges: 0–25, 25–50, 50–75 mm and so on.</p>
<h2>Principle</h2>
<p>It works on the principle of a <strong>screw and nut</strong>: when the screw is turned one full turn, it moves forward by one <strong>pitch</strong>.</p>
<div class="nb-formula">Pitch of spindle screw = 0.5 mm<br>Thimble divisions = 50<br>Least count = Pitch ÷ No. of thimble divisions = 0.5 ÷ 50 = <strong>0.01 mm</strong></div>
<h2>Parts</h2>
<p>Frame, anvil, spindle, sleeve (barrel) with main scale, thimble with circular scale, <strong>ratchet stop</strong> (gives uniform pressure), spindle lock.</p>
<h2>Reading</h2>
<div class="nb-formula">Reading = Sleeve reading (mm + 0.5 mm divisions) + (Thimble division × 0.01)</div>
<p><strong>Example:</strong> Sleeve shows 12 mm and one 0.5 mm line after it → 12.5 mm. Thimble line 23 is on the datum line → 23 × 0.01 = 0.23 mm. Reading = 12.5 + 0.23 = <strong>12.73 mm</strong>.</p>
<h2>Precautions</h2>
<ul><li>Clean the anvil and spindle faces; check zero error with the faces closed (or with a setting gauge for larger sizes).</li><li>Always turn the <strong>ratchet</strong> for the final touch – never over-tighten with the thimble.</li><li>Hold the micrometer by the frame (body heat changes size).</li><li>Store with the faces slightly apart, in its box.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Principle: screw and nut.</li><li>Pitch 0.5 mm, 50 thimble divisions → LC 0.01 mm.</li><li>Use the ratchet for uniform pressure.</li></ul></details>
H],

['2.9', 'Properties of Metals', 'Physical and mechanical properties of metals and why a fitter must know them.', '', <<<'H'
<p>The choice of metal for a job depends on its <strong>properties</strong>. Properties are divided into <strong>physical</strong> and <strong>mechanical</strong>.</p>
<h2>Physical properties</h2>
<ul><li><strong>Colour</strong> – copper reddish, brass yellow, steel grey.</li><li><strong>Weight / density</strong> – aluminium light, lead heavy.</li><li><strong>Structure</strong> – seen on a fractured surface (grains).</li><li><strong>Conductivity</strong> – ability to conduct heat and electricity (silver, copper, aluminium are good).</li><li><strong>Magnetic property</strong> – iron and steel are attracted by a magnet; copper, aluminium are not.</li><li><strong>Fusibility</strong> – ability to melt (tin and lead melt easily).</li></ul>
<h2>Mechanical properties</h2>
<table><tr><th>Property</th><th>Meaning</th><th>Example</th></tr>
<tr><td>Ductility</td><td>Can be drawn into wire</td><td>Copper, mild steel</td></tr>
<tr><td>Malleability</td><td>Can be hammered or rolled into sheets</td><td>Gold, aluminium, lead</td></tr>
<tr><td>Hardness</td><td>Resists scratching, wear and indentation</td><td>Hardened tool steel</td></tr>
<tr><td>Brittleness</td><td>Breaks without bending</td><td>Cast iron, glass</td></tr>
<tr><td>Toughness</td><td>Withstands shock / impact without breaking</td><td>Wrought iron, alloy steel</td></tr>
<tr><td>Tenacity (strength)</td><td>Resists pulling (tensile) force</td><td>Steel</td></tr>
<tr><td>Elasticity</td><td>Returns to shape after load is removed</td><td>Spring steel</td></tr>
<tr><td>Plasticity</td><td>Keeps the new shape after load is removed</td><td>Clay, lead</td></tr></table>
<div class="nb-key"><strong>Remember</strong> Brittleness is the opposite of toughness. A file is hard but brittle – it breaks if dropped.</div>
<details><summary>Quick Revision</summary><ul><li>Physical: colour, weight, structure, conductivity, magnetism, fusibility.</li><li>Mechanical: ductility, malleability, hardness, brittleness, toughness, tenacity, elasticity, plasticity.</li><li>Ductile → wire; malleable → sheet.</li></ul></details>
H],
]],

['3', 'Sheet Metal', 'Sheet metal safety and tools, seams and edges, soldering and riveting.', [

['3.1', 'Sheet Metal Safety and Tools', 'Safety in sheet metal work, snips, hand lever shears, mallets, hammers and stakes.', $I . 'sheet-metal-tools.webp', <<<'H'
<h2>Safety in sheet metal work</h2>
<ul><li>Sheet edges and burrs are as sharp as a knife – wear <strong>gloves</strong> while handling sheets.</li><li>Wear <strong>goggles</strong> while cutting and drilling.</li><li><strong>Deburr</strong> (remove sharp edges) after every cut.</li><li>Keep sheet scrap in a scrap bin, not on the floor.</li></ul>
<h2>Cutting tools</h2>
<ul><li><strong>Snips (tin snips)</strong> – hand cutting of thin sheets. Straight snips for straight lines and outside curves; bent snips for inside curves; universal and aviation snips for both.</li><li><strong>Hand lever shears (bench shears)</strong> – fixed on the bench, cut thicker sheets that snips cannot cut, using leverage.</li></ul>
<h2>Striking tools</h2>
<ul><li><strong>Mallets</strong> – wood, rubber, rawhide or plastic. Used to bend and shape sheet without marking or stretching it.</li><li><strong>Hammers</strong> – ball peen (riveting, general), straight and cross peen, <strong>setting hammer</strong> (closing seams), <strong>raising hammer</strong> (forming hollow shapes), planishing hammer (smoothing).</li></ul>
<h2>Stakes</h2>
<p>Stakes are steel anvils of different shapes on which sheet is bent and formed. They fit in a stake holder (bench plate).</p>
<ul><li><strong>Hatchet stake</strong> – sharp straight bends and edges.</li><li><strong>Half-moon stake</strong> – bending edges of curved work.</li><li><strong>Beak horn stake</strong> – forming cones and round shapes, riveting.</li><li><strong>Funnel stake</strong> – forming funnels and tapered jobs.</li><li><strong>Creasing stake</strong> – wiring and beading edges.</li><li><strong>Hand dolly</strong> – support while riveting and planishing.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Sheet edges are sharp – gloves and deburring.</li><li>Snips for thin sheet; hand lever shears for thicker sheet.</li><li>Mallet bends without marking the sheet.</li><li>Stakes are anvils for forming.</li></ul></details>
H],

['3.2', 'Seams and Edges', 'Grooved, locked, lap seams; wired edge and hem – how sheet metal parts are joined and stiffened.', $I . 'sheet-metal-seams.webp', <<<'H'
<p>A <strong>seam</strong> is a joint made by folding and interlocking the edges of sheets. An <strong>edge</strong> is folded or wired to make the sheet stiff and to remove the sharp edge.</p>
<h2>Seams</h2>
<table><tr><th>Seam</th><th>How it is made / use</th></tr>
<tr><td>Lap seam</td><td>One sheet overlaps the other; joined by soldering or riveting. Simplest seam.</td></tr>
<tr><td>Grooved seam</td><td>Both edges folded, hooked together and pressed down with a <strong>hand groover</strong>. Strong and flat – used in pipes, buckets.</td></tr>
<tr><td>Locked (double) seam</td><td>Edges folded twice and locked – watertight; used in bottoms of containers and roofing.</td></tr>
<tr><td>Knocked-up seam</td><td>Used to fix bottoms of square or round containers.</td></tr></table>
<h2>Edges</h2>
<ul><li><strong>Single hem</strong> – edge folded over once (180°).</li><li><strong>Double hem</strong> – folded twice; stronger.</li><li><strong>Wired edge</strong> – edge wrapped around a wire. Gives great strength and a smooth edge – used in buckets, trays, funnels.</li></ul>
<div class="nb-formula">Allowance for wired edge ≈ 2.5 × diameter of the wire<br>Allowance for grooved seam ≈ 3 × width of the seam</div>
<div class="nb-tip"><strong>Why fold edges?</strong> Folding makes the job stiff, safe to handle and good-looking. The fold allowance must be added while marking the development.</div>
<details><summary>Quick Revision</summary><ul><li>Seams: lap, grooved, locked (double).</li><li>Edges: single hem, double hem, wired edge.</li><li>Wired edge allowance ≈ 2.5 × wire diameter.</li></ul></details>
H],

['3.3', 'Soldering', 'Soft solder, flux and the soldering process for joining sheet metal.', '', <<<'H'
<p><strong>Soldering</strong> is joining two metal pieces with a <strong>filler metal (solder)</strong> that melts at a lower temperature than the base metals. The base metal does <em>not</em> melt.</p>
<h2>Soft solder</h2>
<ul><li>An alloy of <strong>tin and lead</strong> (lead-free solders use tin with silver/copper).</li><li>Melting range about <strong>183 °C – 250 °C</strong>.</li><li>Common grades: 60/40 (tin/lead) for electrical work, 50/50 for general sheet metal.</li></ul>
<h2>Flux</h2>
<p>Flux cleans the surface, prevents oxidation during heating and helps the solder flow.</p>
<table><tr><th>Flux</th><th>Type / use</th></tr>
<tr><td>Zinc chloride (killed spirit)</td><td>Active (corrosive) – steel, copper, brass. Wash the joint after soldering.</td></tr>
<tr><td>Hydrochloric acid (dilute)</td><td>Active – galvanised iron sheets</td></tr>
<tr><td>Resin / rosin</td><td>Non-corrosive – electrical work</td></tr>
<tr><td>Tallow</td><td>Lead and pewter</td></tr></table>
<h2>Soldering process</h2>
<ol><li>Clean the surfaces thoroughly (file, emery, wire brush) – solder will not stick to dirt or oxide.</li><li>Fit the parts closely.</li><li>Apply flux.</li><li>Heat the <strong>soldering iron (copper bit)</strong> and <strong>tin</strong> its face with solder.</li><li>Heat the joint, then apply the solder – it flows into the joint by capillary action.</li><li>Let it cool without moving the parts; clean off the flux.</li></ol>
<div class="nb-warn"><strong>Safety</strong> Flux fumes and lead are harmful – work in a ventilated area, wash hands after soldering, keep the hot iron on its stand.</div>
<details><summary>Quick Revision</summary><ul><li>Base metal does not melt in soldering.</li><li>Soft solder = tin + lead, melts ~183–250 °C.</li><li>Clean surface + flux = good joint.</li><li>Zinc chloride is active flux; resin is for electrical work.</li></ul></details>
H],

['3.4', 'Riveting', 'Types of rivet heads, riveting process, rivet length, pitch and edge distance.', $I . 'riveting.webp', <<<'H'
<p><strong>Riveting</strong> is a method of making a <strong>permanent joint</strong> between plates using rivets. A rivet has a <strong>head</strong> and a <strong>shank</strong>; the tail of the shank is formed into a second head after it is passed through the holes.</p>
<h2>Rivet materials</h2>
<p>Mild steel, copper, brass, aluminium. The rivet material should be the same as (or similar to) the plates, to avoid corrosion.</p>
<h2>Types of rivet heads</h2>
<ul><li><strong>Snap (round) head</strong> – most common, general work.</li><li><strong>Pan head</strong> – heavy structural work.</li><li><strong>Countersunk head</strong> – where a flush (flat) surface is needed.</li><li><strong>Flat head</strong> – thin sheet metal work.</li><li><strong>Conical head</strong> – heavy work, hammered by hand.</li><li><strong>Pop (blind) rivets</strong> – when only one side of the job is reachable.</li></ul>
<h2>Proportions</h2>
<div class="nb-formula">Hole diameter ≈ rivet diameter + 0.1 mm (small clearance)<br>Length of rivet for snap head = total plate thickness + 1.5 d<br>Length for countersunk head = total plate thickness + 1 d<br>Pitch (centre to centre) ≈ 3 d · Edge distance (margin) ≈ 1.5 d</div>
<p>(d = diameter of rivet)</p>
<h2>Riveting process (hand)</h2>
<ol><li>Mark and drill the holes; deburr.</li><li>Insert the rivet; support the head on a <strong>dolly</strong> (snap).</li><li>Use a <strong>rivet set</strong> to close the plates together.</li><li>Hammer the tail with a ball peen hammer to swell and shape it.</li><li>Finish the head with a <strong>rivet snap</strong>.</li></ol>
<details><summary>Quick Revision</summary><ul><li>Riveting = permanent joint.</li><li>Snap head most common; countersunk for flush surface; pop rivet for one-side access.</li><li>Rivet length = plate thickness + 1.5 d (snap head).</li><li>Pitch ≈ 3 d, margin ≈ 1.5 d.</li></ul></details>
H],
]],

['4', 'Welding', 'Welding safety, arc welding parameters, CO₂/MIG welding, oxy-acetylene cutting and weld joints.', [

['4.1', 'Welding Safety', 'Hazards of welding and gas cutting, and the protective equipment and precautions against them.', '', <<<'H'
<p>Welding produces <strong>intense light, heat, sparks, fumes and electric shock</strong>. A fitter must know these hazards before striking an arc or lighting a torch.</p>
<h2>Hazards and protection</h2>
<table><tr><th>Hazard</th><th>Protection</th></tr>
<tr><td>Ultraviolet and infrared rays (arc eye, skin burns)</td><td>Welding helmet / hand screen with correct <strong>filter glass</strong> (shade 10–12 for arc, 4–6 for gas welding/cutting goggles)</td></tr>
<tr><td>Sparks, spatter, hot metal</td><td>Leather gloves, apron, sleeves, safety shoes; no synthetic clothes</td></tr>
<tr><td>Fumes and gases</td><td>Good <strong>ventilation</strong>, fume extraction; respirator for galvanised/coated metal</td></tr>
<tr><td>Electric shock</td><td>Dry gloves and clothes, proper earthing, no damaged cables</td></tr>
<tr><td>Fire and explosion</td><td>Remove flammables, fire extinguisher ready; never weld containers that held oil/fuel</td></tr></table>
<h2>Gas cylinder safety</h2>
<ul><li>Keep cylinders <strong>upright</strong> and chained; away from heat and oil.</li><li>Never use oil or grease on oxygen fittings – it can explode.</li><li>Close cylinder valves when work is over.</li><li>Check for leaks with <strong>soap solution</strong> – never with a flame.</li></ul>
<div class="nb-warn"><strong>Protect others</strong> Use welding screens so that nearby people are not exposed to the arc. Never look at the arc with naked eyes.</div>
<details><summary>Quick Revision</summary><ul><li>Arc rays → helmet with filter glass (shade 10–12).</li><li>Ventilation for fumes; dry gloves against shock.</li><li>Cylinders upright; no oil on oxygen fittings.</li><li>Leak test with soap solution.</li></ul></details>
H],

['4.2', 'Arc Welding Parameters', 'Welding current, arc length, travel speed, electrode angle and polarity in manual metal arc welding.', '', <<<'H'
<p>In <strong>manual metal arc welding (MMAW / SMAW)</strong>, an electric arc between a coated <strong>electrode</strong> and the job melts both, and they fuse to form the weld. The quality of the weld depends on the correct <strong>welding parameters</strong>.</p>
<h2>1. Welding current</h2>
<ul><li>Chosen according to <strong>electrode diameter</strong> and job thickness.</li><li>Rough rule: <strong>about 40 A per mm</strong> of electrode core diameter (e.g. 3.15 mm electrode ≈ 100–130 A).</li><li>Too low current → poor fusion, arc sticks. Too high → excessive spatter, undercut, burn-through.</li></ul>
<h2>2. Arc length</h2>
<ul><li>Distance between the electrode tip and the job.</li><li>Correct (normal) arc length ≈ <strong>diameter of the electrode core wire</strong>.</li><li>Long arc → spatter, poor penetration, porosity. Short arc → electrode sticks.</li></ul>
<h2>3. Travel speed</h2>
<p>Too fast → narrow, weak bead. Too slow → wide, heavy bead and overheating.</p>
<h2>4. Electrode angle</h2>
<p>For a flat butt weld: about <strong>70°–80°</strong> to the line of travel, 90° to the job on either side.</p>
<h2>5. Polarity (with DC)</h2>
<ul><li><strong>DCEN (straight polarity)</strong> – electrode negative: more heat on the job, deeper penetration.</li><li><strong>DCEP (reverse polarity)</strong> – electrode positive: more heat on electrode; used for thin sheets and many coated electrodes.</li><li>AC has no polarity.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Parameters: current, arc length, travel speed, electrode angle, polarity.</li><li>Current ≈ 40 A per mm of electrode dia.</li><li>Arc length ≈ electrode core diameter.</li><li>DCEN → deeper penetration.</li></ul></details>
H],

['4.3', 'CO₂ / MIG Welding', 'Principle, equipment and advantages of gas metal arc (CO₂/MIG) welding.', $I . 'co2-mig-welding.webp', <<<'H'
<p><strong>MIG welding</strong> (Metal Inert Gas), also called <strong>GMAW</strong> (Gas Metal Arc Welding), uses a continuous <strong>wire electrode</strong> fed through a gun. A shielding gas protects the molten weld pool from air. When <strong>carbon dioxide (CO₂)</strong> is used as the shielding gas it is called <strong>CO₂ welding</strong> (strictly, MAG – metal active gas).</p>
<h2>Equipment</h2>
<ul><li><strong>Power source</strong> – constant voltage DC, used on <strong>DCEP</strong>.</li><li><strong>Wire feeder</strong> – feeds the wire from a spool at a set speed (sets the current).</li><li><strong>Welding gun (torch)</strong> – contact tip, nozzle, trigger switch.</li><li><strong>Shielding gas cylinder</strong> with regulator and flow meter (CO₂, argon or Ar + CO₂ mixture).</li><li>Earth (work) cable and clamp.</li></ul>
<h2>Advantages</h2>
<ul><li><strong>High welding speed</strong> – no stopping to change electrodes.</li><li>No slag to remove; clean welds.</li><li>Easy to learn; can be automated.</li><li>Good for thin and thick sheets of steel; aluminium with argon.</li></ul>
<h2>Limitations</h2>
<ul><li>Gas shield is blown away by wind – difficult outdoors.</li><li>Equipment is costlier than an arc welding set.</li></ul>
<div class="nb-tip"><strong>Typical settings</strong> Wire diameter 0.8–1.2 mm; gas flow about 12–18 litre/minute; stick-out (wire length out of contact tip) about 10–15 mm.</div>
<details><summary>Quick Revision</summary><ul><li>MIG = GMAW: continuous wire + shielding gas.</li><li>CO₂ shielding → CO₂ welding.</li><li>Polarity: DCEP; constant voltage power source.</li><li>Main advantage: high speed, no slag.</li></ul></details>
H],

['4.4', 'Oxy-Acetylene Gas Cutting', 'Cylinders, regulators, flashback arrestor, types of flame and the gas cutting process.', $I . 'oxy-acetylene-setup.webp', <<<'H'
<p>In <strong>oxy-acetylene cutting</strong>, steel is heated to its ignition temperature (bright red, about <strong>870–900 °C</strong>) by a preheating flame and then a jet of pure <strong>cutting oxygen</strong> burns (oxidises) the metal and blows the oxide away, making a cut (kerf).</p>
<h2>Equipment</h2>
<table><tr><th>Part</th><th>Details</th></tr>
<tr><td>Oxygen cylinder</td><td>Painted <strong>black</strong>; right-hand threads; high pressure</td></tr>
<tr><td>Acetylene cylinder</td><td>Painted <strong>maroon</strong>; <strong>left-hand</strong> threads; acetylene dissolved in acetone in a porous mass</td></tr>
<tr><td>Regulators</td><td>Reduce cylinder pressure to working pressure; two gauges each</td></tr>
<tr><td>Hoses</td><td>Oxygen – black/blue, acetylene – red</td></tr>
<tr><td>Flashback arrestor</td><td>Stops a flame travelling back into the hose and cylinder</td></tr>
<tr><td>Cutting torch</td><td>Preheat valves + cutting oxygen lever; cutting nozzle</td></tr></table>
<h2>Types of flame</h2>
<ul><li><strong>Neutral flame</strong> – oxygen : acetylene ≈ 1 : 1. Clear inner cone, about 3100–3200 °C. Used for most welding and for cutting preheat.</li><li><strong>Carburising (reducing) flame</strong> – excess acetylene; feather around the cone.</li><li><strong>Oxidising flame</strong> – excess oxygen; short, pointed cone, hissing sound; used for brass.</li></ul>
<h2>Cutting procedure</h2>
<ol><li>Open cylinder valves slowly; set working pressures.</li><li>Open acetylene and light with a <strong>spark lighter</strong>, then add oxygen to get a neutral flame.</li><li>Preheat the starting edge to bright red.</li><li>Press the cutting oxygen lever and move the torch steadily along the line.</li><li>To stop: close cutting oxygen, then acetylene, then oxygen.</li></ol>
<div class="nb-warn"><strong>Never</strong> use acetylene at a pressure above <strong>1 kg/cm² (about 15 psi)</strong> – it becomes unstable. Light the torch only with a spark lighter, never with a match or cigarette lighter.</div>
<details><summary>Quick Revision</summary><ul><li>Oxygen black (RH thread); acetylene maroon (LH thread).</li><li>Neutral flame 1:1, ~3200 °C.</li><li>Cutting = preheat + oxygen jet oxidises steel.</li><li>Flashback arrestor prevents flame going back.</li></ul></details>
H],

['4.5', 'Types of Weld Joints', 'Butt, lap, fillet (T), corner and edge joints, and the parts of a weld.', '', <<<'H'
<p>A <strong>weld joint</strong> is the way two pieces are placed and welded together. The joint is chosen according to the shape of parts, the load and the thickness.</p>
<h2>Basic joints</h2>
<table><tr><th>Joint</th><th>Arrangement</th><th>Use</th></tr>
<tr><td>Butt joint</td><td>Edges placed in line, end to end</td><td>Plates, pipes – strong joint; thick plates need edge preparation (V, U, J)</td></tr>
<tr><td>Lap joint</td><td>One plate overlaps the other; welded with fillet</td><td>Sheet metal, repair work</td></tr>
<tr><td>Tee (T) joint</td><td>One plate at 90° on another; fillet weld</td><td>Frames, brackets, stiffeners</td></tr>
<tr><td>Corner joint</td><td>Edges meet at a corner (L shape)</td><td>Boxes, frames</td></tr>
<tr><td>Edge joint</td><td>Two edges placed side by side</td><td>Thin sheets</td></tr></table>
<h2>Fillet weld</h2>
<p>A weld of roughly triangular section joining two surfaces at right angles (in lap, T and corner joints). Its size is given by the <strong>leg length</strong>.</p>
<h2>Parts of a weld</h2>
<ul><li><strong>Face</strong> – exposed surface of the weld.</li><li><strong>Root</strong> – deepest point of the weld.</li><li><strong>Toe</strong> – where the weld face meets the base metal.</li><li><strong>Reinforcement</strong> – weld metal above the plate surface.</li><li><strong>Penetration</strong> – depth to which the base metal is fused.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Five joints: butt, lap, T, corner, edge.</li><li>Fillet weld in lap, T and corner joints; size = leg length.</li><li>Thick plate butt joints need V/U edge preparation.</li></ul></details>
H],
]],

['5', 'Drilling', 'Twist drill, cutting speed and RPM, holding devices, drill troubles, reamers, taps, dies and gauges.', [

['5.1', 'Twist Drill – Parts and Angles', 'Parts of a twist drill and its important angles: point angle 118°, lip clearance and helix angle.', $I . 'twist-drill.webp', <<<'H'
<p><strong>Drilling</strong> is making a round hole in a solid material with a rotating cutting tool called a <strong>drill</strong>. The most common drill is the <strong>twist drill</strong>, made of high-speed steel (HSS).</p>
<h2>Parts of a twist drill</h2>
<ul><li><strong>Shank</strong> – part held in the machine. <em>Straight shank</em> (held in a drill chuck, usually up to 13 mm) or <em>taper shank</em> (Morse taper, fits directly in the spindle).</li><li><strong>Tang</strong> – flat end of a taper shank; helps to remove the drill with a drift.</li><li><strong>Body</strong> – from shank to point, has the flutes.</li><li><strong>Flutes</strong> – spiral grooves; carry chips out and coolant in.</li><li><strong>Land / margin</strong> – narrow strip along the flutes that gives the size and guides the drill.</li><li><strong>Web</strong> – central metal between flutes; gives strength.</li><li><strong>Point</strong> – cone-shaped cutting end with <strong>two lips (cutting edges)</strong> and the <strong>chisel edge</strong>.</li></ul>
<h2>Drill angles</h2>
<table><tr><th>Angle</th><th>Value (general work)</th><th>Purpose</th></tr>
<tr><td>Point angle</td><td><strong>118°</strong></td><td>Cutting angle of the two lips</td></tr>
<tr><td>Lip clearance angle</td><td>8° – 12°</td><td>Lets the lips cut without rubbing</td></tr>
<tr><td>Helix angle (rake)</td><td>about 27° – 30°</td><td>Rake for cutting and chip flow</td></tr>
<tr><td>Chisel edge angle</td><td>120° – 135°</td><td>Shows correct lip clearance</td></tr></table>
<div class="nb-key"><strong>Point angle by material</strong> Harder material → larger point angle (e.g. 135° for hard steel); soft material → smaller (e.g. 90° for plastics, 60° for wood). Both lips must have <strong>equal length and equal angle</strong>, otherwise the hole will be oversize.</div>
<details><summary>Quick Revision</summary><ul><li>Standard point angle 118°.</li><li>Lip clearance 8°–12°; helix 27°–30°.</li><li>Straight shank in chuck; taper shank in spindle/sleeve.</li><li>Unequal lips → oversize hole.</li></ul></details>
H],

['5.2', 'Cutting Speed, RPM and Feed', 'What cutting speed and feed are, and how to calculate drill RPM with the formula.', '', <<<'H'
<h2>Cutting speed</h2>
<p><strong>Cutting speed (V)</strong> is the speed at which the cutting edge passes over the material, given in <strong>metres per minute (m/min)</strong>. It depends on the job material, tool material, coolant and finish required.</p>
<table><tr><th>Material (HSS drill)</th><th>Cutting speed (approx.)</th></tr>
<tr><td>Mild steel</td><td>25 – 30 m/min</td></tr><tr><td>Cast iron</td><td>20 – 25 m/min</td></tr><tr><td>Brass</td><td>40 – 60 m/min</td></tr><tr><td>Aluminium</td><td>60 – 90 m/min</td></tr><tr><td>Tool steel</td><td>10 – 15 m/min</td></tr></table>
<h2>Spindle speed (RPM)</h2>
<div class="nb-formula">V = (π × D × N) ÷ 1000<br>N = (1000 × V) ÷ (π × D)<br>N = RPM, V = cutting speed in m/min, D = drill diameter in mm</div>
<p><strong>Example:</strong> Find the RPM for a 10 mm drill in mild steel at a cutting speed of 25 m/min.<br>N = (1000 × 25) ÷ (3.14 × 10) = 25000 ÷ 31.4 ≈ <strong>796 RPM</strong>. Select the nearest lower speed on the machine.</p>
<div class="nb-key"><strong>Remember</strong> Bigger drill → lower RPM. Smaller drill → higher RPM (for the same material).</div>
<h2>Feed</h2>
<p><strong>Feed</strong> is the distance the drill moves into the job in one revolution, in <strong>mm/rev</strong>. Smaller drills get less feed (e.g. 0.05–0.1 mm/rev); larger drills more (0.2–0.3 mm/rev). Too much feed breaks the drill; too little causes rubbing and work hardening.</p>
<details><summary>Quick Revision</summary><ul><li>Cutting speed in m/min; feed in mm/rev.</li><li>N = 1000 V ÷ πD.</li><li>10 mm drill, MS, 25 m/min → ~796 RPM.</li></ul></details>
H],

['5.3', 'Drill Holding Devices and Drill Troubles', 'Drill chuck, sleeve and socket, drift, and the common drilling troubles with their remedies.', '', <<<'H'
<h2>Holding the drill</h2>
<ul><li><strong>Drill chuck</strong> – three-jaw key chuck that holds <strong>straight shank</strong> drills (commonly up to 13 mm). Always remove the chuck key before starting the machine.</li><li><strong>Sleeve</strong> – used when the taper shank of a drill is smaller than the spindle taper.</li><li><strong>Socket</strong> – used when the drill taper is bigger than the spindle taper (extension).</li><li><strong>Drift</strong> – tapered flat key to remove taper-shank drills, sleeves and chucks from the spindle.</li></ul>
<p>Taper shanks use the <strong>Morse taper (MT 1 to MT 6)</strong>; it is a self-holding taper.</p>
<h2>Holding the job</h2>
<p>Machine vice, clamps and bolts, V-block, angle plate, drill jig. Never hold the job by hand on a drilling machine.</p>
<h2>Drill troubles and remedies</h2>
<table><tr><th>Trouble</th><th>Cause</th><th>Remedy</th></tr>
<tr><td>Drill breaks</td><td>Too much feed, blunt drill, chips clogged in flutes, job loose</td><td>Reduce feed, regrind, withdraw drill often, clamp job</td></tr>
<tr><td>Oversize hole</td><td>Unequal lip lengths or angles, loose spindle</td><td>Regrind drill correctly, check machine</td></tr>
<tr><td>Rough hole</td><td>Blunt drill, no coolant, too much feed</td><td>Sharpen, use coolant, reduce feed</td></tr>
<tr><td>Outer corners wear / burn (blue colour)</td><td>Speed too high</td><td>Reduce speed, use coolant</td></tr>
<tr><td>Lips chip off</td><td>Too much lip clearance or feed</td><td>Regrind with correct clearance</td></tr>
<tr><td>Drill does not cut</td><td>No lip clearance, blunt</td><td>Regrind</td></tr></table>
<details><summary>Quick Revision</summary><ul><li>Straight shank → chuck; taper shank → sleeve/socket; drift removes it.</li><li>Morse taper is self-holding.</li><li>Unequal lips → oversize hole; high speed → burnt corners.</li></ul></details>
H],

['5.4', 'Reamers and Reaming', 'Why holes are reamed, types of reamers, reaming allowance and correct method.', $I . 'reamer-tap-die-gauge.webp', <<<'H'
<p>A drilled hole is not perfectly round, straight or smooth. <strong>Reaming</strong> finishes a drilled hole to an <strong>accurate size and good surface finish</strong> using a multi-tooth tool called a <strong>reamer</strong>.</p>
<h2>Types of reamers</h2>
<ul><li><strong>Hand reamer</strong> – square end for a tap wrench; long taper lead at the start; turned by hand.</li><li><strong>Machine reamer</strong> – straight or taper shank; short bevel lead; used on drilling machine or lathe at low speed.</li><li><strong>Adjustable reamer</strong> – blades can be set to slightly different sizes.</li><li><strong>Taper reamer</strong> – for taper pin holes.</li><li>Flutes may be <strong>straight</strong> or <strong>helical</strong> (helical gives smoother cutting).</li></ul>
<h2>Reaming allowance</h2>
<p>The hole is first drilled slightly undersize. Leave only a small amount for the reamer to remove – about <strong>0.2 mm for small holes up to 0.5 mm for larger holes</strong>. Too much allowance overloads the reamer; too little makes it rub.</p>
<div class="nb-formula">Drill size for reaming = Reamer size − Reaming allowance<br>Example: 10 mm reamed hole → drill 9.8 mm</div>
<h2>Correct method</h2>
<ul><li>Keep the reamer square (in line) with the hole.</li><li>Turn <strong>clockwise only</strong> – <strong>never turn a reamer backwards</strong>; it blunts the edges.</li><li>Use cutting fluid (except on cast iron and brass).</li><li>For machine reaming use about <strong>half the drilling speed</strong>.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Reaming gives accurate size and finish.</li><li>Hand reamer – long taper lead; machine reamer – short bevel lead.</li><li>Allowance about 0.2–0.5 mm.</li><li>Never rotate a reamer anti-clockwise.</li></ul></details>
H],

['5.5', 'Taps and Dies', 'Cutting internal threads with taps and external threads with dies; tap drill size.', '', <<<'H'
<h2>Taps – for internal threads</h2>
<p>A <strong>tap</strong> is a hardened tool with threads and flutes, used to cut <strong>internal threads</strong> in a hole. Hand taps come in a set of three:</p>
<table><tr><th>Tap</th><th>Chamfer (taper) at the start</th><th>Use</th></tr>
<tr><td>Taper tap (first)</td><td>8 – 10 threads chamfered</td><td>Starts the thread easily and straight</td></tr>
<tr><td>Intermediate / second tap</td><td>3 – 5 threads</td><td>Cuts the threads deeper</td></tr>
<tr><td>Bottoming / plug tap</td><td>1 – 2 threads</td><td>Cuts full threads to the bottom of a blind hole</td></tr></table>
<p>Taps are turned with a <strong>tap wrench</strong> (double-ended adjustable or T-type).</p>
<h2>Tap drill size</h2>
<div class="nb-formula">Tap drill size = Major (nominal) diameter − Pitch<br>M10 × 1.5 → 10 − 1.5 = <strong>8.5 mm drill</strong><br>M8 × 1.25 → 8 − 1.25 = 6.75 ≈ 6.8 mm drill</div>
<h3>Tapping method</h3>
<ol><li>Drill the correct tap-drill hole and chamfer its mouth.</li><li>Start with the taper tap, <strong>square to the job</strong> (check with a try square).</li><li>Turn ½ turn forward and ¼ turn back to break the chips.</li><li>Use cutting oil; finish with second and bottoming taps.</li></ol>
<h2>Dies – for external threads</h2>
<p>A <strong>die</strong> cuts <strong>external threads</strong> on bolts and rods. It is held in a <strong>die stock (die holder)</strong>.</p>
<ul><li><strong>Circular split die</strong> – has a slit; size can be adjusted slightly with screws in the die stock.</li><li><strong>Die nut</strong> – for cleaning damaged threads.</li><li>Chamfer the rod end before starting; the tapered side of the die goes first.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Taps → internal threads; dies → external threads.</li><li>Set of taps: taper, second, bottoming.</li><li>Tap drill = major dia − pitch (M10 → 8.5 mm).</li><li>Turn ½ forward, ¼ back to break chips.</li></ul></details>
H],

['5.6', 'Limit Gauges: Plug, Ring and Snap', 'GO and NO-GO gauges for checking holes and shafts quickly in production.', '', <<<'H'
<p>In mass production every part cannot be measured with a micrometer. <strong>Limit gauges</strong> quickly check whether the size is <strong>within the limits</strong>. They do not show the actual size – only <em>accept</em> or <em>reject</em>.</p>
<h2>GO and NO-GO</h2>
<ul><li><strong>GO</strong> side must enter (or pass over) the part.</li><li><strong>NO-GO</strong> side must <strong>not</strong> enter (or pass over).</li><li>If GO goes and NO-GO does not go → the part is correct.</li></ul>
<h2>Types</h2>
<table><tr><th>Gauge</th><th>Checks</th><th>Details</th></tr>
<tr><td>Plug gauge</td><td>Holes (bores)</td><td>GO end = lower limit of hole (longer), NO-GO end = upper limit (shorter, often marked red)</td></tr>
<tr><td>Ring gauge</td><td>Shafts (outside diameter)</td><td>Separate GO and NO-GO rings; NO-GO ring has a groove on its outside</td></tr>
<tr><td>Snap gauge</td><td>Shafts and thickness</td><td>C-shaped; GO jaw at front (upper limit), NO-GO jaw behind (lower limit)</td></tr>
<tr><td>Thread gauges</td><td>Threads</td><td>Thread plug and thread ring gauges</td></tr></table>
<div class="nb-key"><strong>Remember</strong> For a hole the GO size is the <strong>smallest</strong> allowed size; for a shaft the GO size is the <strong>largest</strong> allowed size.</div>
<details><summary>Quick Revision</summary><ul><li>Limit gauges accept or reject – they do not measure.</li><li>Plug gauge → hole; ring and snap gauge → shaft.</li><li>GO must go, NO-GO must not go.</li></ul></details>
H],
]],

['6', 'Fitting Assembly', 'Limits, fits and tolerances, engineering metals, scraping, precision instruments and fitting jobs.', [

['6.1', 'Interchangeability: Limits, Fits and Tolerance', 'Basic size, limits, tolerance, allowance, types of fit and the hole basis system.', '', <<<'H'
<p><strong>Interchangeability</strong> means any part from a lot can replace a similar part and fit correctly without extra fitting. It is possible only when parts are made <strong>within limits</strong>.</p>
<h2>Terms</h2>
<ul><li><strong>Basic (nominal) size</strong> – the size given on the drawing, e.g. Ø25.</li><li><strong>Limits</strong> – the two extreme sizes allowed: <strong>upper (maximum) limit</strong> and <strong>lower (minimum) limit</strong>.</li><li><strong>Tolerance</strong> – difference between upper and lower limits.</li><li><strong>Deviation</strong> – difference between a limit and the basic size (upper / lower deviation).</li><li><strong>Allowance</strong> – intentional difference between the hole and shaft at maximum metal condition.</li></ul>
<div class="nb-formula">Example: Ø25 +0.03 / −0.02<br>Upper limit = 25.03 mm, Lower limit = 24.98 mm<br>Tolerance = 25.03 − 24.98 = <strong>0.05 mm</strong></div>
<h2>Fits</h2>
<p><strong>Fit</strong> is the relation between a hole and a shaft that are to be assembled.</p>
<table><tr><th>Fit</th><th>Condition</th><th>Example</th></tr>
<tr><td>Clearance fit</td><td>Shaft always smaller than hole</td><td>Shaft in a bearing, sliding parts</td></tr>
<tr><td>Interference fit</td><td>Shaft always bigger than hole – pressed in</td><td>Gear on shaft, bush in housing</td></tr>
<tr><td>Transition fit</td><td>May give clearance or interference</td><td>Spigot, location of parts</td></tr></table>
<h2>Hole basis system</h2>
<p>The <strong>hole is kept constant</strong> (its lower deviation is zero – symbol <strong>H</strong>) and the shaft size is changed to get different fits. It is preferred because holes are made with standard drills and reamers. In the <em>shaft basis system</em> the shaft is constant (symbol h).</p>
<p>BIS system: <strong>IS 919</strong>, with 18 grades of tolerance (IT01 to IT16 / IT18). Example: <strong>H7/g6</strong> – hole H7, shaft g6 (clearance fit).</p>
<details><summary>Quick Revision</summary><ul><li>Tolerance = upper limit − lower limit.</li><li>Fits: clearance, interference, transition.</li><li>Hole basis: hole constant, lower deviation zero (H).</li><li>Capital letters for holes, small letters for shafts.</li></ul></details>
H],

['6.2', 'Engineering Metals: Cast Iron, Steel and Non-ferrous Metals', 'Ferrous and non-ferrous metals used in fitting, with their properties and uses.', '', <<<'H'
<p>Metals are divided into <strong>ferrous</strong> (contain iron) and <strong>non-ferrous</strong> (no iron).</p>
<h2>Ferrous metals</h2>
<h3>Pig iron and cast iron</h3>
<ul><li><strong>Pig iron</strong> is produced in the blast furnace from iron ore; it is the raw material for all iron and steel.</li><li><strong>Cast iron</strong> has <strong>2 – 4 % carbon</strong>. It is <strong>brittle</strong>, strong in compression, easy to cast and machine, and absorbs vibration.</li><li><strong>Grey cast iron</strong> – machine beds, frames, pulleys. <strong>White cast iron</strong> – very hard. <strong>Malleable</strong> and <strong>SG (nodular)</strong> cast iron – tougher.</li></ul>
<h3>Steel</h3>
<table><tr><th>Type</th><th>Carbon %</th><th>Uses</th></tr>
<tr><td>Low carbon (mild) steel</td><td>up to 0.3 %</td><td>Sheets, rods, nuts, bolts, structures – ductile, weldable</td></tr>
<tr><td>Medium carbon steel</td><td>0.3 – 0.6 %</td><td>Shafts, axles, gears, rails</td></tr>
<tr><td>High carbon steel</td><td>0.6 – 1.5 %</td><td>Files, chisels, punches, springs, cutting tools – can be hardened</td></tr></table>
<p><strong>Alloy steels</strong>: stainless steel (chromium + nickel – does not rust), high-speed steel (tungsten, chromium, vanadium – drills, lathe tools).</p>
<h2>Non-ferrous metals and alloys</h2>
<table><tr><th>Metal / alloy</th><th>Properties and uses</th></tr>
<tr><td>Copper</td><td>Reddish, very good conductor, ductile – electrical wires, tubes</td></tr>
<tr><td>Aluminium</td><td>Light, corrosion resistant, good conductor – utensils, aircraft, cables</td></tr>
<tr><td>Zinc</td><td>Coating on steel (galvanising)</td></tr>
<tr><td>Tin</td><td>Coating (tin plate), solder</td></tr>
<tr><td>Lead</td><td>Heavy, soft – batteries, solder</td></tr>
<tr><td>Brass = copper + zinc</td><td>Yellow, easily machined – fittings, valves</td></tr>
<tr><td>Bronze = copper + tin</td><td>Wear resistant – bushes, bearings</td></tr></table>
<details><summary>Quick Revision</summary><ul><li>Cast iron 2–4 % C – brittle, good in compression.</li><li>Mild steel ≤0.3 % C; high carbon 0.6–1.5 % C (tools).</li><li>Brass = Cu + Zn; Bronze = Cu + Sn.</li></ul></details>
H],

['6.3', 'Scraping', 'Why surfaces are scraped, types of scrapers, blue matching and bearing marks.', $I . 'scrapers-dti.webp', <<<'H'
<p><strong>Scraping</strong> is removing very small amounts of metal (high spots) by hand with a <strong>scraper</strong> to get a truly flat or perfectly fitting surface. It is used for machine slideways, surface plates and bearings.</p>
<h2>Purpose</h2>
<ul><li>To produce a flat and true surface.</li><li>To get a perfect fit between mating parts (bearing and shaft).</li><li>Scraped surfaces hold a thin film of oil in tiny pockets, which gives good lubrication.</li></ul>
<h2>Types of scrapers</h2>
<table><tr><th>Scraper</th><th>Use</th></tr>
<tr><td>Flat scraper</td><td>Flat surfaces – slideways, surface plates</td></tr>
<tr><td>Half-round scraper</td><td>Curved (concave) surfaces – bearings, bushes</td></tr>
<tr><td>Three-square (triangular) scraper</td><td>Removing burrs, small curved surfaces, corners of holes</td></tr>
<tr><td>Bull-nose scraper</td><td>Large bearings</td></tr></table>
<p>Scrapers are made of high-carbon tool steel, hardened at the cutting end, and kept sharp on an oil stone.</p>
<h2>Blue matching (checking with Prussian blue)</h2>
<ol><li>Apply a <strong>thin film of Prussian blue</strong> on the surface plate (master surface).</li><li>Rub the job lightly on it.</li><li>High spots pick up blue; low spots stay bright.</li><li>Scrape the blue (high) spots only.</li><li>Clean, re-apply and repeat until blue spots are evenly spread all over the surface.</li></ol>
<div class="nb-key"><strong>Bearing marks</strong> The quality of scraping is judged by the number of contact (blue) spots in a 25 mm × 25 mm square – more and evenly spread spots mean a better surface.</div>
<details><summary>Quick Revision</summary><ul><li>Scraping removes high spots for flatness and fit.</li><li>Flat scraper – flat; half-round – bearings; triangular – burrs and corners.</li><li>Prussian blue shows high spots.</li></ul></details>
H],

['6.4', 'Precision Instruments: Vernier Micrometer, Screw Thread Micrometer, Dial Test Indicator', 'Instruments for 0.001 mm readings, measuring thread pitch diameter and checking with a DTI.', '', <<<'H'
<h2>Vernier micrometer</h2>
<p>An outside micrometer with an additional <strong>vernier scale on the sleeve</strong>. It gives readings to <strong>0.001 mm</strong>.</p>
<div class="nb-formula">10 vernier divisions = 9 thimble divisions<br>LC = 0.01 ÷ 10 = <strong>0.001 mm</strong><br>Reading = Sleeve + Thimble (× 0.01) + Vernier line coinciding (× 0.001)</div>
<h2>Screw thread micrometer</h2>
<ul><li>Measures the <strong>effective (pitch) diameter</strong> of external threads.</li><li>Spindle has a <strong>conical point</strong> and the anvil has a <strong>V-groove</strong>; they fit in the thread form.</li><li>Anvils are changed according to the pitch range of the thread. Least count 0.01 mm.</li></ul>
<h2>Dial Test Indicator (DTI)</h2>
<p>A <strong>comparator</strong> – it shows the <strong>difference</strong> in size or position, not the actual size. Small movements of the contact point are magnified and shown by a pointer on a dial.</p>
<ul><li><strong>Plunger type</strong> dial indicator – plunger moves in a straight line; for height, run-out, end play.</li><li><strong>Lever type</strong> DTI – small swinging stylus; for checking in bores and difficult places.</li><li>Common least count <strong>0.01 mm</strong> (also 0.001 mm types).</li><li>Mounted on a <strong>magnetic stand</strong> or surface gauge.</li></ul>
<h3>Uses of DTI</h3>
<ul><li>Checking flatness and parallelism of surfaces.</li><li>Checking <strong>run-out / eccentricity</strong> of shafts.</li><li>Setting jobs true in a 4-jaw chuck or on a machine.</li><li>Comparing heights with slip gauges.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Vernier micrometer LC 0.001 mm.</li><li>Screw thread micrometer → effective diameter (cone and V anvil).</li><li>DTI is a comparator; LC 0.01 mm; used for run-out, parallelism, setting.</li></ul></details>
H],

['6.5', 'Fitting Jobs: Sliding, Angular, Step, T and Square Fits', 'Common fitting assignments of the first year and how to make them to ±0.04 mm accuracy.', '', <<<'H'
<p>In the fitting section the trainee makes <strong>pairs of parts that fit each other</strong>. These jobs develop accuracy in marking, sawing, filing and measuring.</p>
<h2>Common fitting jobs</h2>
<table><tr><th>Fit</th><th>What it is</th></tr>
<tr><td>Square fit</td><td>A square male piece fits into a square opening of the female piece in all positions.</td></tr>
<tr><td>Sliding fit</td><td>The male part slides smoothly in the female part without shake.</td></tr>
<tr><td>Step fit</td><td>Steps of the male part fit the steps of the female part.</td></tr>
<tr><td>Angular (dovetail / V) fit</td><td>Angled faces (e.g. 60°) fit together; checked with a bevel protractor.</td></tr>
<tr><td>T-fit</td><td>A T-shaped male piece fits a T-slot.</td></tr></table>
<div class="nb-key"><strong>Accuracy</strong> Typical first-year fitting jobs are made to <strong>±0.04 mm</strong> and should fit with <strong>interchangeability</strong> – the male part fits even after turning it over (180°).</div>
<h2>Sequence of work</h2>
<ol><li>Check the raw material size; file and finish two <strong>datum faces</strong> at 90°.</li><li>File the other faces to size, parallel and square.</li><li>Apply marking medium; mark out from the datum faces with a vernier height gauge / odd-leg caliper.</li><li>Punch witness marks; drill relief holes at inside corners.</li><li>Remove the waste metal by sawing and chipping.</li><li>File close to the lines; finish with a smooth file, checking with vernier, micrometer, try square and bevel protractor.</li><li>Make the male part first, then file the female part to suit it.</li><li>Check the fit; remove high spots with care.</li><li>Deburr all edges.</li></ol>
<div class="nb-tip"><strong>Tip</strong> Never "force" a fit with a hammer. Use Prussian blue on the male part to see where the female part is tight.</div>
<details><summary>Quick Revision</summary><ul><li>Fits: square, sliding, step, angular, T.</li><li>First-year accuracy ±0.04 mm.</li><li>Work from datum faces; make male first, then female.</li><li>Drill relief holes at inside corners.</li></ul></details>
H],
]],

['7', 'Turning', 'Lathe parts, safety and work holding, tool angles, turning operations, speed, feed and coolants.', [

['7.1', 'Centre Lathe – Parts and Functions', 'Bed, headstock, tailstock, carriage and feed mechanism of the centre lathe.', $I . 'centre-lathe.webp', <<<'H'
<p>The <strong>lathe</strong> is a machine tool that removes metal from a <strong>rotating job</strong> with a <strong>single-point cutting tool</strong>. It produces cylindrical shapes. The most common type is the <strong>centre lathe</strong> (engine lathe). Its size is given by the <strong>height of centres</strong> and the <strong>distance between centres</strong>.</p>
<h2>Main parts</h2>
<table><tr><th>Part</th><th>Function</th></tr>
<tr><td>Bed</td><td>Rigid cast iron base with accurate <strong>guideways (V and flat)</strong>; carries the headstock, tailstock and carriage.</td></tr>
<tr><td>Headstock</td><td>Fixed on the left; contains the hollow <strong>spindle</strong> and speed-changing gears. The chuck or face plate is mounted on the spindle nose.</td></tr>
<tr><td>Tailstock</td><td>On the right, slides on the bed; supports long jobs with a <strong>dead centre</strong>, holds drills and reamers in its <strong>barrel (quill)</strong>.</td></tr>
<tr><td>Carriage</td><td>Moves the tool along and across the job.</td></tr>
<tr><td>Feed mechanism</td><td>Feed gear box, <strong>lead screw</strong> (for thread cutting) and <strong>feed rod</strong> (for turning feed).</td></tr></table>
<h2>Parts of the carriage</h2>
<ul><li><strong>Saddle</strong> – slides on the bed guideways (longitudinal movement).</li><li><strong>Cross-slide</strong> – moves the tool at 90° to the axis (cross feed, facing).</li><li><strong>Compound rest (top slide)</strong> – can be swivelled to any angle; used for short taper turning.</li><li><strong>Tool post</strong> – holds the cutting tool.</li><li><strong>Apron</strong> – front part with hand wheel, feed levers and <strong>half-nut lever</strong> (engages the lead screw for threading).</li></ul>
<details><summary>Quick Revision</summary><ul><li>Lathe: rotating job + single-point tool.</li><li>Main parts: bed, headstock, tailstock, carriage, feed mechanism.</li><li>Carriage: saddle, cross-slide, compound rest, tool post, apron.</li><li>Lead screw for threading, feed rod for turning.</li></ul></details>
H],

['7.2', 'Lathe Safety and Work Holding', 'Safety on the lathe, holding work between centres with driving dog and catch plate, and chucks.', '', <<<'H'
<h2>Safety on the lathe</h2>
<ul><li>Wear goggles; tuck in loose clothing; no gloves, rings or watches.</li><li><strong>Remove the chuck key immediately</strong> after use – never leave it in the chuck.</li><li>Stop the machine before measuring, cleaning or changing tools.</li><li>Remove chips with a chip hook or brush, never by hand.</li><li>Keep the tool overhang short and the job tightly held.</li><li>Do not lean on the machine; keep hands away from the rotating chuck.</li></ul>
<h2>Holding work between centres</h2>
<p>Long jobs that need accurate concentric turning are held <strong>between two centres</strong>.</p>
<ul><li>Drill <strong>centre holes</strong> at both ends with a centre drill (60° cone).</li><li><strong>Live centre</strong> in the headstock spindle rotates with the job; <strong>dead centre</strong> in the tailstock (lubricate it) – or a revolving centre.</li><li>The <strong>catch plate (driving plate)</strong> is screwed on the spindle nose.</li><li>A <strong>driving dog (carrier)</strong> is clamped on the job; its tail engages the slot or pin of the catch plate and drives the job.</li></ul>
<h2>Chucks</h2>
<table><tr><th>Chuck</th><th>Features</th></tr>
<tr><td>3-jaw self-centring</td><td>All jaws move together; quick for round and hexagonal jobs; less accurate centring and grip.</td></tr>
<tr><td><strong>4-jaw independent</strong></td><td>Each jaw moves separately; holds square, rectangular and irregular jobs; can be set true (with DTI) or off-centre for eccentric turning; strong grip.</td></tr>
<tr><td>Collet chuck</td><td>Small, accurate bar work.</td></tr></table>
<p>Other holding devices: face plate (irregular jobs), mandrel (jobs with a bore), steady and follower rests (long thin jobs).</p>
<details><summary>Quick Revision</summary><ul><li>Remove chuck key at once; no gloves on lathe.</li><li>Between centres: centre holes + catch plate + driving dog.</li><li>4-jaw chuck: independent jaws, square/irregular/eccentric jobs.</li></ul></details>
H],

['7.3', 'Single Point Cutting Tool and Tool Angles', 'Tool materials, rake and clearance angles and why each angle is needed.', '', <<<'H'
<p>A lathe tool has <strong>one cutting edge</strong> – it is a <strong>single point cutting tool</strong>. Common tool materials: <strong>high-speed steel (HSS)</strong>, <strong>carbide tipped</strong> tools and inserts.</p>
<h2>Tool angles</h2>
<table><tr><th>Angle</th><th>Purpose</th></tr>
<tr><td>Top (back) rake</td><td>Slope of the top face towards the back; helps chips flow and reduces cutting force.</td></tr>
<tr><td>Side rake</td><td>Slope of the top face sideways; chip flow and easy cutting.</td></tr>
<tr><td>Front (end) clearance</td><td>Below the cutting edge at the front; stops the tool face rubbing on the job.</td></tr>
<tr><td>Side clearance</td><td>Below the side cutting edge; stops rubbing during feed.</td></tr>
<tr><td>Side cutting edge angle (approach)</td><td>Spreads the cut over a longer edge; protects the tip.</td></tr>
<tr><td>End cutting edge angle</td><td>Clearance between the end edge and the machined surface.</td></tr>
<tr><td>Nose radius</td><td>Strengthens the tip and gives better finish.</td></tr></table>
<div class="nb-key"><strong>Rake vs clearance</strong> <strong>Rake</strong> is on the top face – it is for <em>cutting and chip flow</em>. <strong>Clearance</strong> is below the cutting edge – it is to <em>prevent rubbing</em>.</div>
<h3>Typical HSS tool angles for mild steel</h3>
<p>Top rake about 12°–14°, side rake 10°–14°, clearance angles about 6°–8°. Soft materials (aluminium) need larger rake; brass and cast iron need small or zero rake.</p>
<h2>Setting the tool</h2>
<ul><li>Cutting edge exactly at the <strong>centre height</strong> of the job (check with tailstock centre).</li><li>Overhang as short as possible.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Rake → chip flow, less force; clearance → no rubbing.</li><li>HSS for MS: rake ~12–14°, clearance ~6–8°.</li><li>Brass and cast iron: little or no rake.</li><li>Tool at centre height, short overhang.</li></ul></details>
H],

['7.4', 'Lathe Operations', 'Facing, plain and step turning, knurling, taper turning and external thread cutting.', '', <<<'H'
<h2>Facing</h2>
<p>Making the <strong>end of the job flat</strong> and square to its axis. The tool moves across the job by the cross-slide, from the centre outward or outside inward.</p>
<h2>Plain (straight) turning</h2>
<p>Reducing the diameter of the job along its length. The tool moves parallel to the axis by the carriage. Done as <strong>rough turning</strong> (deep cut, coarse feed) then <strong>finish turning</strong> (light cut, fine feed).</p>
<h2>Step turning</h2>
<p>Turning different diameters one after another to form steps (shoulders), measured with a steel rule / vernier from a face.</p>
<h2>Chamfering and grooving</h2>
<p>Chamfer – beveling the sharp edge (usually 45°). Grooving – making a narrow groove with a form/parting tool.</p>
<h2>Knurling</h2>
<p>Pressing a pattern (<strong>straight or diamond</strong>) on the surface with a <strong>knurling tool</strong> to give a good grip – e.g. handles. Done at <strong>low speed</strong> with plenty of oil; the job size increases slightly.</p>
<h2>Taper turning</h2>
<p>Methods: (1) <strong>swivelling the compound rest</strong> – short, steep tapers; (2) <strong>tailstock set-over</strong> – long, gentle tapers between centres; (3) <strong>taper turning attachment</strong>; (4) <strong>form tool</strong> – very short tapers.</p>
<div class="nb-formula">Compound rest angle: tan α = (D − d) ÷ 2L<br>D = big diameter, d = small diameter, L = length of taper</div>
<h2>External thread cutting</h2>
<ul><li>A tool ground to the thread form (60° for metric) is set square to the job with a <strong>centre gauge</strong>.</li><li>The <strong>lead screw</strong> and <strong>half-nut</strong> move the carriage one pitch per revolution of the job.</li><li>Change gears / gear box set for the required pitch.</li><li>Several cuts are taken; check with a thread gauge or nut.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Facing – end flat; turning – reduce diameter.</li><li>Knurling at low speed: straight or diamond.</li><li>Taper: compound rest, tailstock set-over, attachment, form tool.</li><li>tan α = (D − d)/2L.</li></ul></details>
H],

['7.5', 'Cutting Speed, Feed, Depth of Cut and Coolants on the Lathe', 'Choosing spindle speed, feed and depth of cut, and the purpose and types of coolants.', '', <<<'H'
<h2>Cutting speed and RPM</h2>
<div class="nb-formula">N = (1000 × V) ÷ (π × D)<br>N = RPM, V = cutting speed (m/min), D = job diameter (mm)</div>
<p><strong>Example:</strong> Mild steel Ø40 mm, HSS tool, V = 25 m/min. N = 25000 ÷ (3.14 × 40) ≈ <strong>199 RPM</strong>.</p>
<p>Cutting speed depends on the job material, tool material (carbide tools allow 3–4 times more speed than HSS), rigidity of the set-up and the finish needed.</p>
<h2>Feed</h2>
<p>Distance the tool moves along the job per revolution, in <strong>mm/rev</strong>. Roughing: about 0.2–0.5 mm/rev. Finishing: about 0.05–0.1 mm/rev.</p>
<h2>Depth of cut</h2>
<div class="nb-formula">Depth of cut = (D − d) ÷ 2</div>
<p>When the tool is moved in by 1 mm, the diameter reduces by <strong>2 mm</strong>.</p>
<h2>Coolants (cutting fluids)</h2>
<h3>Purpose</h3>
<ul><li>Cool the tool and the job – longer tool life.</li><li>Lubricate – less friction, better finish.</li><li>Wash away chips; prevent rust.</li></ul>
<h3>Types</h3>
<table><tr><th>Coolant</th><th>Used for</th></tr>
<tr><td>Soluble oil (oil + water emulsion, milky)</td><td>Most general turning and drilling of steel</td></tr>
<tr><td>Straight cutting oil</td><td>Threading, heavy cuts, gear cutting</td></tr>
<tr><td>Kerosene</td><td>Aluminium</td></tr>
<tr><td>Dry (no coolant) / compressed air</td><td>Cast iron, brass</td></tr></table>
<details><summary>Quick Revision</summary><ul><li>N = 1000V ÷ πD (D = job dia).</li><li>Depth of cut = (D − d)/2.</li><li>Soluble oil for steel; dry for cast iron and brass; kerosene for aluminium.</li></ul></details>
H],
]],

['8', 'Basic Maintenance', 'TPM, routine and preventive maintenance, inspection, lubrication, installation, overhauling and dowel pins.', [

['8.1', 'Total Productive Maintenance (TPM)', 'Aim of TPM, its pillars and the six big losses – maintenance with the involvement of every worker.', $I . 'tpm.webp', <<<'H'
<p><strong>Total Productive Maintenance (TPM)</strong> is a system of maintaining machines with the <strong>involvement of all employees</strong> – from operators to top management – so that machines are always ready for production.</p>
<h2>Aims of TPM</h2>
<ul><li><strong>Zero breakdowns</strong>, zero defects, zero accidents.</li><li>Maximum <strong>Overall Equipment Effectiveness (OEE)</strong>.</li><li>Operators take care of their own machines (cleaning, lubrication, small checks).</li></ul>
<h2>Pillars of TPM</h2>
<ol><li><strong>Autonomous maintenance (Jishu Hozen)</strong> – daily care by the operator.</li><li><strong>Planned maintenance</strong> – scheduled maintenance by the maintenance team.</li><li><strong>Quality maintenance</strong> – keeping machines in condition to make zero defects.</li><li><strong>Focused improvement (Kaizen)</strong> – small continuous improvements.</li><li><strong>Early equipment management</strong> – learning from old machines when buying new ones.</li><li><strong>Training and education</strong>.</li><li><strong>Safety, health and environment</strong>.</li><li><strong>Office TPM</strong> – efficiency in support departments.</li></ol>
<p>The foundation of TPM is <strong>5S</strong>.</p>
<h2>Six big losses</h2>
<ul><li>Breakdowns · Set-up and adjustment · Small stops · Reduced speed · Defects in start-up · Production defects and rework.</li></ul>
<details><summary>Quick Revision</summary><ul><li>TPM = maintenance with every worker's involvement.</li><li>Aim: zero breakdown, zero defect, zero accident.</li><li>Jishu Hozen = autonomous (operator) maintenance.</li><li>Foundation of TPM: 5S.</li></ul></details>
H],

['8.2', 'Routine and Preventive Maintenance', 'Daily routine care, preventive (scheduled) maintenance and breakdown maintenance compared.', '', <<<'H'
<p><strong>Maintenance</strong> means keeping machines and equipment in good working condition so that they work safely, accurately and for a long life.</p>
<h2>Types of maintenance</h2>
<table><tr><th>Type</th><th>Meaning</th></tr>
<tr><td>Breakdown (corrective)</td><td>Repair after the machine fails. Causes sudden stoppage and high cost.</td></tr>
<tr><td>Routine maintenance</td><td>Simple daily/weekly work – cleaning, lubrication, tightening, checking.</td></tr>
<tr><td>Preventive maintenance</td><td>Planned, <strong>scheduled</strong> work done before failure – inspection, adjustment, replacement of worn parts.</td></tr>
<tr><td>Predictive (condition-based)</td><td>Checking condition (vibration, temperature, noise, oil) to predict failure.</td></tr></table>
<h2>Routine maintenance – daily checklist</h2>
<ul><li>Clean the machine – remove chips, dust and coolant.</li><li>Check oil levels and lubricate as per the <strong>lubrication chart</strong>.</li><li>Check guards, belts, switches and coolant.</li><li>Report unusual noise, heat or vibration.</li><li>Oil the slideways at the end of the day.</li></ul>
<h2>Preventive maintenance</h2>
<ul><li>Done as per a <strong>schedule</strong> – weekly, monthly, quarterly, yearly.</li><li>A <strong>history card / record</strong> is kept for each machine.</li><li>Includes checking alignment, belt tension, bearing condition, changing oil, replacing worn parts.</li></ul>
<div class="nb-key"><strong>Benefits of preventive maintenance</strong> Fewer breakdowns, longer machine life, better accuracy, safer working, lower total cost.</div>
<details><summary>Quick Revision</summary><ul><li>Breakdown = after failure; preventive = scheduled before failure.</li><li>Routine: cleaning, lubrication, checking.</li><li>Keep a history card for each machine.</li></ul></details>
H],

['8.3', 'Inspection and Lubrication', 'Types of inspection, simple inspection gadgets, and lubricants and lubrication methods.', '', <<<'H'
<h2>Inspection</h2>
<p><strong>Inspection</strong> is checking the condition of machine parts to find faults before they cause failure.</p>
<ul><li><strong>Visual inspection</strong> – leaks, cracks, loose bolts, worn belts.</li><li><strong>Running inspection</strong> – noise, vibration, temperature while the machine runs.</li><li><strong>Shutdown inspection</strong> – machine stopped and opened for detailed checks.</li><li><strong>Accuracy (geometric) test</strong> – alignment, flatness, run-out of spindle.</li></ul>
<h3>Inspection gadgets</h3>
<p>Spirit level, dial test indicator, feeler gauge, stethoscope (listening to bearings), thermometer/infrared gun, vibration meter, torch and mirror.</p>
<h2>Lubrication</h2>
<p><strong>Lubrication</strong> is putting a film of lubricant between moving surfaces.</p>
<h3>Purpose</h3>
<ul><li>Reduce friction and wear.</li><li>Remove heat.</li><li>Prevent rust and corrosion.</li><li>Keep out dust and dirt.</li></ul>
<h3>Types of lubricants</h3>
<ul><li><strong>Liquid</strong> – mineral oils (machine oil, gear oil, spindle oil).</li><li><strong>Semi-solid</strong> – <strong>grease</strong> for bearings and places where oil would leak.</li><li><strong>Solid</strong> – graphite, molybdenum disulphide for high temperature.</li></ul>
<h3>Methods</h3>
<p>Oil can, grease gun (grease nipple), wick/drip feed, splash (gear box), forced (pump) lubrication, centralised lubrication systems.</p>
<div class="nb-tip"><strong>Follow the chart</strong> Always use the grade of oil shown in the machine's lubrication chart, and clean the nipple before using a grease gun.</div>
<details><summary>Quick Revision</summary><ul><li>Inspection: visual, running, shutdown, accuracy test.</li><li>Lubrication reduces friction, wear, heat and rust.</li><li>Lubricants: oil, grease, solid (graphite).</li></ul></details>
H],

['8.4', 'Installation, Overhauling, Assembly Techniques and Dowel Pins', 'Installing a machine on its foundation, overhauling, good assembly practice and the use of dowel pins.', '', <<<'H'
<h2>Installation of machines</h2>
<ol><li>Select the location – space for operation and maintenance, light, power supply.</li><li>Prepare a <strong>foundation</strong> (concrete) as per the manufacturer's drawing with pockets for <strong>foundation bolts</strong>.</li><li>Place the machine with a crane / rollers; fit foundation bolts and grout them.</li><li><strong>Level</strong> the machine with a precision spirit level and wedges / levelling pads.</li><li>Tighten the bolts after the grout has set; check the level again.</li><li>Connect power, test run without load, then with load.</li></ol>
<h2>Overhauling</h2>
<p><strong>Overhauling</strong> is the complete dismantling, cleaning, inspection, repair or replacement of worn parts and re-assembly of a machine to bring it back to original condition.</p>
<ul><li>Study the manual and drawings; mark parts and keep them in order.</li><li>Use proper pullers and tools – never hammer directly on finished parts.</li><li>Replace bearings, seals, gaskets, worn gears; check alignment; re-test.</li></ul>
<h2>Assembly techniques</h2>
<ul><li>Clean all parts; remove burrs.</li><li>Check parts with the drawing before assembly.</li><li>Lubricate mating surfaces.</li><li>Tighten bolts <strong>evenly and in a criss-cross order</strong>, to the specified torque.</li><li>Use correct fasteners and locking devices (spring washers, lock nuts, split pins).</li></ul>
<h2>Dowel pins</h2>
<p><strong>Dowel pins</strong> are hardened, accurately ground cylindrical (or taper) pins used to <strong>locate two parts in an exact position</strong> relative to each other.</p>
<ul><li><strong>Screws hold</strong> the parts together; <strong>dowels locate</strong> them.</li><li>Usually <strong>two dowels</strong> are used, placed as far apart as possible.</li><li>The holes in both parts are drilled and <strong>reamed together</strong> after the parts are clamped in position.</li><li>Fit: interference fit in one part, close (transition) fit in the other, so parts can be separated and re-assembled in the same position.</li></ul>
<details><summary>Quick Revision</summary><ul><li>Installation: foundation, bolts, grouting, levelling, test run.</li><li>Overhauling = dismantle, clean, inspect, repair, re-assemble.</li><li>Tighten bolts evenly, criss-cross.</li><li>Dowels locate, screws hold; ream holes together.</li></ul></details>
H],
]],
];
