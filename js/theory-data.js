/* =========================================================
   COPA MCQ – Theory Notes (built in), lesson-wise
   Study notes written for COPA MCQ, following the lesson plan of the
   COPA Trade Theory book (NSQF 2022). Not a copy of the book.
   part  = book part (1–4) · bm = book module · m = website module (1–15) for the filter
   ========================================================= */
window.COPA_THEORY = [
  /* ===================== PART 1 · MODULE 1 : Safe Working Practices ===================== */
  { part: 1, bm: 1, mt: "Safe Working Practices", no: "1.1.01&02", title: "Electrical safety", m: 1,
    keys: ["Electric shock", "Rescue from live wire", "First aid", "Artificial respiration", "Safety rules"],
    html: `
<h3>What decides how dangerous a shock is</h3>
<ul><li><b>Amount of current</b> passing through the body (not the voltage alone).</li><li><b>Time</b> of contact – the longer, the worse.</li><li><b>Path</b> of the current – hand to hand or hand to foot through the heart is most dangerous.</li><li><b>Body resistance</b> – wet skin, sweat or wet floor lowers resistance and increases current.</li></ul>
<table class="rte-table"><tbody><tr><th>Current (AC, approx.)</th><th>Effect on the body</th></tr>
<tr><td>About 1 mA</td><td>Slight tingling</td></tr><tr><td>5 – 10 mA</td><td>Painful shock</td></tr><tr><td>10 – 20 mA</td><td>Muscles contract – the person may not be able to let go</td></tr><tr><td>50 – 100 mA and above</td><td>Breathing problems, heart fibrillation – can be fatal</td></tr></tbody></table>
<h3>Rescuing a person in contact with a live wire</h3>
<ol><li><b>Act at once</b> but do not touch the person with bare hands.</li><li><b>Switch off</b> the supply – main switch, MCB or pull out the plug.</li><li>If switching off is not possible, stand on a <b>dry insulating</b> surface (dry wood, rubber mat) and push or pull the victim away using a dry wooden stick, dry rope, rubber or folded newspaper.</li><li>If the victim is working at a height, prevent a fall or make the fall safe.</li></ol>
<h3>First aid after the rescue</h3>
<ul><li>Check <b>breathing and pulse</b>; send someone to call medical help (ambulance <b>108</b>).</li><li>If breathing has stopped, start <b>artificial respiration / CPR</b> immediately and continue until help arrives.</li><li><b>Burns:</b> cover with a clean, dry, sterile dressing – do not apply oil or ointment and do not burst blisters.</li><li>Treat for <b>shock</b>: keep the person lying down, warm and calm; loosen tight clothing.</li><li>Do not give anything by mouth to an unconscious person and <b>never leave an unconscious person alone</b>; place in the recovery position.</li></ul>
<h3>Methods of artificial respiration</h3>
<ul><li><b>Mouth-to-mouth</b> (or mouth-to-nose) – most effective and used in CPR.</li><li><b>Schafer's method</b> – victim lying face down, pressure on the lower back.</li><li><b>Nelson's (Holger Nielsen) arm-lift back-pressure method</b> – victim face down, press the back and lift the arms alternately.</li></ul>
<h3>Safety rules in the computer lab</h3>
<ul><li>Use properly <b>earthed</b> sockets and good quality plugs; never overload a socket.</li><li>Protect circuits with <b>MCB and ELCB/RCCB</b>.</li><li>Switch off and unplug before opening a computer or touching internal parts.</li><li>Use insulated tools; keep hands, floor and work area dry.</li><li>Replace damaged wires and switches; report faults to the instructor.</li></ul>
<blockquote><b>Remember:</b> Switch OFF first – rescue next – first aid immediately.</blockquote>` },

  { part: 1, bm: 1, mt: "Safe Working Practices", no: "1.1.03&04", title: "Safety practice – fire extinguishers", m: 1,
    keys: ["Fire triangle", "Classes of fire", "Types of extinguishers", "PASS method", "Fire procedure"],
    html: `
<h3>Fire triangle</h3>
<p>A fire needs three things at the same time: <b>Fuel</b> (something that burns), <b>Heat</b> and <b>Oxygen</b>. Removing any one of them puts the fire out:</p>
<ul><li><b>Starving</b> – remove the fuel.</li><li><b>Smothering</b> – cut off the oxygen (blanket, sand, foam, CO₂).</li><li><b>Cooling</b> – remove the heat (water).</li></ul>
<h3>Classes of fire and the right extinguisher</h3>
<table class="rte-table"><tbody><tr><th>Class</th><th>What is burning</th><th>Use</th><th>Never use</th></tr>
<tr><td><b>A</b></td><td>Wood, paper, cloth, plastics (solid materials)</td><td>Water, foam, dry powder</td><td>–</td></tr>
<tr><td><b>B</b></td><td>Oil, petrol, diesel, grease, paint (flammable liquids)</td><td>Foam, dry powder, CO₂</td><td>Water (spreads the fire)</td></tr>
<tr><td><b>C</b></td><td>Gases – LPG, acetylene</td><td>Dry powder; first shut the gas valve</td><td>Water jet</td></tr>
<tr><td><b>D</b></td><td>Metals – sodium, magnesium, aluminium powder</td><td>Special dry powder</td><td>Water, foam</td></tr>
<tr><td><b>Electrical</b></td><td>Live electrical equipment, computers</td><td><b>CO₂</b>, dry powder, clean agent (after switching off power)</td><td>Water, foam</td></tr></tbody></table>
<h3>Types of fire extinguishers</h3>
<ul><li><b>Water type</b> (gas-cartridge or stored-pressure) – only for Class A fires.</li><li><b>Foam type</b> – Class A and B; forms a blanket over burning liquids.</li><li><b>Dry Chemical Powder (DCP)</b> – Class B, C and electrical; special powders for metal fires.</li><li><b>CO₂ (carbon dioxide)</b> – Class B and electrical fires; leaves no residue, so it is best for <b>computers</b> and labs.</li><li><b>Halon / clean agent</b> – used for electronic equipment and server rooms (halon is being replaced by eco-friendly clean agents).</li></ul>
<h3>How to use an extinguisher – PASS</h3>
<table class="rte-table"><tbody><tr><th>Step</th><th>Action</th></tr><tr><td><b>P</b> – Pull</td><td>Pull the safety pin (break the seal).</td></tr><tr><td><b>A</b> – Aim</td><td>Aim the nozzle at the <b>base</b> of the fire.</td></tr><tr><td><b>S</b> – Squeeze</td><td>Squeeze the handle to discharge.</td></tr><tr><td><b>S</b> – Sweep</td><td>Sweep from side to side until the fire is out.</td></tr></tbody></table>
<p>Keep yourself low (smoke rises), stand 2–3 m away, keep the exit behind you, and watch for re-ignition.</p>
<h3>General procedure in case of fire</h3>
<ol><li>Raise the <b>alarm</b> – shout "Fire! Fire!" and press the fire alarm.</li><li><b>Switch off</b> electricity and close gas valves if it is safe.</li><li>Call the <b>fire brigade – 101</b>.</li><li>Fight only a <b>small</b> fire with the correct extinguisher; otherwise leave.</li><li><b>Evacuate</b> calmly by the nearest exit (do not use lifts) and gather at the assembly point.</li></ol>` },

  /* ===================== PART 1 · MODULE 2 : Assemble a Desktop PC ===================== */
  { part: 1, bm: 2, mt: "Assemble a Desktop PC", no: "1.2.05&06", title: "Introduction to computers", m: 1,
    keys: ["Definition", "History", "Generations", "Classification", "Advantages & limitations", "Applications", "SMPS voltages"],
    html: `
<h3>Definition</h3>
<p>A <b>computer</b> is an electronic machine that accepts data (<b>input</b>), processes it according to instructions stored in its memory (<b>process</b>), stores the results (<b>storage</b>) and gives information (<b>output</b>). This is the <b>IPO cycle</b>.</p>
<h3>History in short</h3>
<table class="rte-table"><tbody><tr><th>Machine / person</th><th>Remember for</th></tr>
<tr><td>Abacus</td><td>Earliest counting device</td></tr><tr><td>Blaise Pascal (1642)</td><td>Mechanical adding machine</td></tr><tr><td><b>Charles Babbage</b></td><td>Difference & Analytical Engine – "Father of the computer"</td></tr><tr><td>Lady Ada Lovelace</td><td>First computer programmer</td></tr><tr><td>Mark I (1944)</td><td>Electro-mechanical computer (Howard Aiken, IBM–Harvard)</td></tr><tr><td>ENIAC (1946)</td><td>First general-purpose electronic computer</td></tr><tr><td>EDVAC / EDSAC</td><td>Stored-program idea (John von Neumann); EDSAC – first practical stored-program computer</td></tr><tr><td>UNIVAC-1 (1951)</td><td>First commercial computer</td></tr></tbody></table>
<h3>Generations of computers</h3>
<table class="rte-table"><tbody><tr><th>Generation</th><th>Main component</th><th>Features</th></tr>
<tr><td>1st (about 1946–56)</td><td>Vacuum tubes</td><td>Very large, much heat and power, machine language, punched cards</td></tr>
<tr><td>2nd (about 1956–63)</td><td>Transistors</td><td>Smaller, faster (microseconds), assembly and early high-level languages (FORTRAN, COBOL)</td></tr>
<tr><td>3rd (about 1964–71)</td><td>Integrated Circuits (IC)</td><td>Keyboard and monitor, operating systems, lower cost</td></tr>
<tr><td>4th (1971 – present)</td><td>Microprocessor (VLSI)</td><td>Personal computers, GUI, networks, very fast and small</td></tr>
<tr><td>5th (present & future)</td><td>ULSI, AI</td><td>Artificial intelligence, natural language, parallel processing, robotics</td></tr></tbody></table>
<h3>Classification</h3>
<ul><li><b>By working:</b> <b>Analog</b> (continuous values – thermometer, speedometer), <b>Digital</b> (discrete 0/1 – PC), <b>Hybrid</b> (both – ICU machines).</li><li><b>By size & power:</b> <b>Micro</b> (desktop, laptop, tablet), <b>Mini</b> (small organisations), <b>Mainframe</b> (banks, railways – many users), <b>Super</b> computer (weather, research – fastest, e.g. PARAM in India).</li><li><b>By purpose:</b> General purpose and Special purpose.</li></ul>
<h3>Advantages and limitations</h3>
<table class="rte-table"><tbody><tr><th>Advantages</th><th>Limitations</th></tr>
<tr><td>Speed, accuracy, large storage, diligence (no tiredness), versatility, automation, reliability</td><td>No intelligence of its own (needs instructions), GIGO – Garbage In Garbage Out, no feelings or decisions, needs electricity, health problems with long use</td></tr></tbody></table>
<h3>Applications</h3>
<p>Education, banking (ATM, net banking), business and offices, railway/airline reservation, hospitals, government (e-governance, Aadhaar), communication (e-mail, internet), entertainment, science and weather forecasting. <b>Artificial Intelligence</b> areas: game playing, expert systems, natural language, neural networks and robotics.</p>
<h3>Voltages inside a computer (SMPS)</h3>
<p>The <b>SMPS</b> (Switched Mode Power Supply) converts 230 V AC mains into low DC voltages for the motherboard and drives.</p>
<table class="rte-table"><tbody><tr><th>Wire colour (ATX)</th><th>Voltage / signal</th></tr>
<tr><td>Orange</td><td>+3.3 V</td></tr><tr><td>Red</td><td>+5 V</td></tr><tr><td>Yellow</td><td>+12 V (drives, fans, CPU)</td></tr><tr><td>Blue</td><td>−12 V</td></tr><tr><td>Black</td><td>Ground (0 V)</td></tr><tr><td>Purple</td><td>+5 V standby</td></tr><tr><td>Green</td><td>PS_ON (turns the SMPS on)</td></tr><tr><td>Grey</td><td>Power Good signal</td></tr></tbody></table>` },

  /* ===================== PART 1 · MODULE 3 : Using Windows Operating System ===================== */
  { part: 1, bm: 3, mt: "Using Windows Operating System", no: "1.3.07-13", title: "Introduction to CPU architecture and motherboard", m: 1,
    keys: ["Hardware", "Internal & external", "CPU & ALU", "Ports", "Motherboard", "Partitions", "Booting"],
    html: `
<h3>Computer hardware</h3>
<p><b>Hardware</b> = the physical parts of a computer that we can touch. <b>Software</b> = programs that tell the hardware what to do.</p>
<table class="rte-table"><tbody><tr><th>Internal hardware</th><th>External hardware (peripherals)</th></tr>
<tr><td>CPU, motherboard, RAM, hard disk / SSD, CD/DVD/Blu-ray drive, SMPS, sound card, video (graphics) card, network card</td><td>Monitor (CRT, LCD, LED flat panel), keyboard, mouse, printer, scanner, speakers, webcam, external drives</td></tr></tbody></table>
<h3>CPU – Central Processing Unit</h3>
<ul><li>The "brain" of the computer; fetches, decodes and executes instructions.</li><li><b>CU – Control Unit:</b> controls and coordinates all parts.</li><li><b>ALU – Arithmetic Logic Unit:</b> does calculations (+ − × ÷) and comparisons (&lt;, &gt;, =).</li><li><b>Registers / cache:</b> very fast small memory inside the CPU.</li><li>Speed is measured in <b>GHz</b>; modern CPUs have several <b>cores</b>.</li></ul>
<h3>Ports and connectors (back panel)</h3>
<table class="rte-table"><tbody><tr><th>Port</th><th>Use</th></tr>
<tr><td>PS/2 (purple / green)</td><td>Old keyboard (purple) and mouse (green)</td></tr><tr><td>USB</td><td>Most devices – pen drive, mouse, printer, phone</td></tr><tr><td>VGA, DVI, HDMI, DisplayPort</td><td>Monitor / projector</td></tr><tr><td>RJ-45 (LAN)</td><td>Network cable</td></tr><tr><td>Audio jacks</td><td>Green – speakers, Pink – microphone, Blue – line in</td></tr><tr><td>Serial / Parallel</td><td>Old modems and printers (legacy)</td></tr></tbody></table>
<p><b>Front panel:</b> power switch and power LED, reset button, HDD LED, USB ports, audio / mic jacks, optical drive with open/close button.</p>
<h3>Motherboard – main parts</h3>
<ul><li><b>Processor socket</b> (with cooler) and <b>chipset</b>.</li><li><b>Memory slots</b> (DIMM) for RAM.</li><li><b>Expansion slots:</b> PCI Express today; AGP, PCI and ISA on old boards.</li><li><b>SATA connectors</b> for hard disk, SSD and DVD drive (old: IDE/PATA ribbon cable).</li><li><b>24-pin ATX power</b> connector and 4/8-pin CPU power.</li><li><b>BIOS/UEFI chip</b> and <b>CMOS battery</b> (coin cell).</li><li>Front-panel headers and the back I/O panel.</li></ul>
<h3>Disk partitions</h3>
<ul><li>A <b>partition</b> divides one physical disk into separate logical drives (C:, D:…).</li><li><b>MBR</b> disks: up to <b>4 primary</b> partitions, or 3 primary + 1 <b>extended</b> partition holding many <b>logical</b> drives.</li><li><b>GPT</b> disks (with UEFI): up to 128 partitions and disks larger than 2 TB.</li></ul>
<h3>Booting</h3>
<p><b>Booting</b> is the process of starting the computer and loading the operating system into RAM.</p>
<ul><li><b>Cold boot</b> – starting from power off.</li><li><b>Warm boot</b> – restarting a running computer (Restart / Ctrl + Alt + Del).</li></ul>
<ol><li>Power on → SMPS sends <b>Power Good</b>.</li><li>CPU runs the <b>BIOS/UEFI</b>.</li><li><b>POST</b> (Power-On Self-Test) checks RAM, keyboard, video, drives (beep codes on errors).</li><li>BIOS finds the boot device using the <b>boot order</b>.</li><li>The <b>boot loader</b> (MBR / EFI) loads the OS kernel.</li><li>The OS starts and shows the login screen.</li></ol>` },

  /* ===================== PART 1 · MODULE 4 : Computer Basics & Software Installation ===================== */
  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.14", title: "View the BIOS settings and their modifications", m: 2,
    keys: ["Windows versions", "Windows 10 features", "Start menu", "Virtual desktops", "Opening BIOS setup"],
    html: `
<h3>Versions of Microsoft Windows</h3>
<table class="rte-table"><tbody><tr><th>Version</th><th>Year</th><th>Remember for</th></tr>
<tr><td>Windows 1.0</td><td>1985</td><td>First version – a graphical shell running on MS-DOS</td></tr>
<tr><td>Windows 2.0</td><td>1987</td><td>Overlapping windows, keyboard shortcuts</td></tr>
<tr><td>Windows 3.0 / 3.1</td><td>1990 / 1992</td><td>Program Manager, 256 colours, Minesweeper; ran DOS programs in windows</td></tr>
<tr><td>Windows 95</td><td>1995</td><td><b>Start button, taskbar</b>, plug and play</td></tr>
<tr><td>Windows 98 / ME</td><td>1998 / 2000</td><td>USB support, Internet Explorer built in</td></tr>
<tr><td>Windows 2000</td><td>2000</td><td>Business version based on Windows NT</td></tr>
<tr><td>Windows XP</td><td>2001</td><td>Very popular, stable; new colourful look</td></tr>
<tr><td>Windows Vista</td><td>2007</td><td>Aero glass look, Windows Defender, desktop search</td></tr>
<tr><td>Windows 7</td><td>2009</td><td>Faster, pinned taskbar, Aero Snap</td></tr>
<tr><td>Windows 8 / 8.1</td><td>2012 / 2013</td><td>Tiles Start screen for touch; 8.1 brought back the Start button</td></tr>
<tr><td>Windows 10</td><td>2015</td><td>Start menu returns, Cortana, virtual desktops, Edge, Action Center</td></tr>
<tr><td>Windows 11</td><td>2021</td><td>Centred Start menu and taskbar, Snap layouts</td></tr></tbody></table>
<h3>Windows 10 – main features</h3>
<ul><li><b>Start menu</b> – app list on the left and live tiles on the right.</li><li><b>Desktop background</b> – Settings → Personalization → Background → choose picture / solid colour / slideshow.</li><li><b>File Explorer</b> (Win + E) – Quick access, This PC, Downloads.</li><li><b>Cortana</b> – voice assistant for search and reminders.</li><li><b>Task View & Virtual desktops</b> (Win + Tab, Win + Ctrl + D) – separate desktops for different work.</li><li><b>Action Center</b> (Win + A) – notifications and quick settings.</li></ul>
<p>Keyboard shortcuts are listed on the <a href="quick-reference.html#win">Quick Reference</a> page.</p>
<h3>Viewing BIOS settings</h3>
<ul><li>Restart the PC and press the setup key during POST – usually <b>Del</b>, <b>F2</b>, <b>F10</b>, <b>F12</b> or <b>Esc</b> (shown on screen).</li><li>Common screens: <b>Main</b> (date, time, system info), <b>Advanced</b> (CPU, SATA mode), <b>Boot</b> (boot order), <b>Security</b> (passwords), <b>Exit</b>.</li><li>Use arrow keys to move, Enter to select, <b>F10 = Save & Exit</b>, Esc = exit without saving.</li><li>Change only what you understand – wrong settings can stop the PC from booting.</li></ul>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.15", title: "Install Windows operating system", m: 3,
    keys: ["BIOS functions", "CMOS", "Configuring BIOS", "Partitioning", "Formatting", "Installation steps"],
    html: `
<h3>BIOS and CMOS</h3>
<ul><li><b>BIOS</b> (Basic Input/Output System) is firmware stored in a ROM/flash chip on the motherboard. Modern PCs use <b>UEFI</b>, its successor.</li><li><b>Functions:</b> POST, finding and loading the boot loader (bootstrap), basic drivers for keyboard/display/disk, and the <b>setup utility</b>.</li><li><b>CMOS</b> is a small memory that keeps BIOS settings (date, time, boot order). A <b>coin battery (CR2032)</b> keeps it alive when the PC is off – if the battery is weak, time and settings are lost.</li></ul>
<h3>What can be configured in BIOS setup</h3>
<ul><li>System date and time.</li><li><b>Boot sequence</b> – USB / DVD / hard disk first.</li><li>Drive configuration – SATA mode (AHCI / RAID), enable or disable drives.</li><li>Enable / disable on-board devices (audio, LAN, USB).</li><li>Supervisor and user <b>passwords</b>.</li><li>Power management and fan settings; CPU/memory speed (performance).</li></ul>
<h3>Partitioning and formatting</h3>
<ul><li><b>Partitioning</b> – dividing the disk into parts (e.g. C: for Windows, D: for data).</li><li><b>Formatting</b> – preparing a partition with a <b>file system</b> (NTFS for Windows) so files can be stored. Formatting erases all data in that partition.</li></ul>
<h3>Steps to install Windows 10</h3>
<ol><li>Check requirements: 1 GHz CPU, 2 GB RAM (64-bit), about 20 GB free disk space, DirectX 9 graphics.</li><li>Make a <b>bootable USB</b> (Media Creation Tool) or use the DVD.</li><li>Set the <b>boot order</b> to USB/DVD in BIOS and restart.</li><li>Choose language, time and keyboard → <b>Install now</b>.</li><li>Enter the product key (or "I don't have a key") and choose the edition.</li><li>Accept the licence → choose <b>Custom: Install Windows only</b>.</li><li>Select / create / format the partition → <b>Next</b>; Windows copies files and restarts several times.</li><li>Set region, keyboard, user account, password and privacy options → desktop appears.</li><li>Install drivers and updates.</li></ol>
<blockquote><b>Dual boot:</b> to keep an old OS, install the new one into a <b>separate partition</b>; a boot menu then lets you choose the OS.</blockquote>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.16", title: "Format hard disk and create partition", m: 3,
    keys: ["File systems", "Quick vs full format", "Disk Management", "Command line", "Reset this PC"],
    html: `
<h3>Formatting</h3>
<p><b>Formatting</b> creates a new, empty file system on a partition. <b>All data on that partition is erased</b> – take a backup first.</p>
<table class="rte-table"><tbody><tr><th>File system</th><th>Used for</th></tr>
<tr><td><b>NTFS</b></td><td>Windows system and data drives – security, large files</td></tr><tr><td>FAT32</td><td>Old / small pen drives – max 4 GB per file</td></tr><tr><td>exFAT</td><td>Large pen drives and memory cards – works on Windows and Mac</td></tr></tbody></table>
<ul><li><b>Quick format</b> – only clears the file table (fast).</li><li><b>Full format</b> – also checks the disk for <b>bad sectors</b> (slow).</li></ul>
<h3>Disk Management tool (Windows)</h3>
<p>Open: right-click Start → <b>Disk Management</b> (or Win + R → <code>diskmgmt.msc</code>).</p>
<ul><li><b>New Simple Volume</b> – create a partition in unallocated space (choose size, drive letter, file system).</li><li><b>Shrink Volume</b> – make a partition smaller to free space.</li><li><b>Extend Volume</b> – add free space to a partition.</li><li><b>Format</b>, <b>Delete Volume</b>, <b>Change Drive Letter and Paths</b>.</li></ul>
<h3>Using the command line</h3>
<ul><li><code>format D: /FS:NTFS /Q</code> – quick-format drive D: with NTFS.</li><li><code>diskpart</code> → <code>list disk</code> → <code>select disk 1</code> → <code>create partition primary size=50000</code> → <code>format fs=ntfs quick</code> → <code>assign</code>.</li></ul>
<h3>Resetting Windows</h3>
<p>Settings → Update &amp; Security → <b>Recovery</b> → <b>Reset this PC</b>:</p>
<ul><li><b>Keep my files</b> – reinstalls Windows, keeps personal files, removes apps and settings.</li><li><b>Remove everything</b> – removes files, apps and settings (use before giving the PC to someone else).</li></ul>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.17", title: "Identify and rectify common hardware and software issues during OS installation", m: 3,
    keys: ["Troubleshooting steps", "Approach", "Common problems", "Safe mode", "Bad sectors", "Optimising the PC"],
    html: `
<h3>Basic troubleshooting steps</h3>
<ol><li><b>Identify the problem</b> – ask the user, note the symptoms and any recent change.</li><li><b>Find the probable cause</b> – start with the simplest and most likely.</li><li><b>Test</b> the cause.</li><li><b>Plan and apply</b> the solution.</li><li><b>Verify</b> that the system works fully.</li><li><b>Document</b> the problem and the solution.</li></ol>
<h3>Good approach</h3>
<ul><li>Check the simple things first – power, cables, switches, loose connections.</li><li>Change <b>one thing at a time</b>.</li><li>Use known-good parts to swap and test (RAM, cable, SMPS).</li><li>Always back up data before major repairs.</li></ul>
<h3>Common problems and remedies</h3>
<table class="rte-table"><tbody><tr><th>Symptom</th><th>Possible cause → remedy</th></tr>
<tr><td>No power, no fan</td><td>Mains cable, switch, faulty SMPS → check / replace</td></tr>
<tr><td>Beeps, no display</td><td>RAM or graphics card loose → reseat / clean contacts; check monitor cable</td></tr>
<tr><td>"No boot device / Operating system not found"</td><td>Wrong boot order, disk not detected, SATA cable → fix BIOS settings and cables</td></tr>
<tr><td>Disk not shown during Windows setup</td><td>SATA mode / RAID driver → set AHCI or load the driver</td></tr>
<tr><td>Setup stops or copy error</td><td>Bad USB/DVD, bad RAM or bad sectors → remake media, test RAM, check disk</td></tr>
<tr><td>Blue screen (BSOD)</td><td>Driver, RAM or disk problem → update drivers, run memory test, use Safe Mode</td></tr>
<tr><td>Very slow PC</td><td>Too many start-up programs, malware, low disk space, less RAM → clean up, antivirus, add RAM/SSD</td></tr>
<tr><td>Shuts down by itself</td><td>Overheating – dusty fan or dried thermal paste → clean, replace paste</td></tr>
<tr><td>Wrong date / time every day</td><td>Weak CMOS battery → replace</td></tr></tbody></table>
<h3>Useful tools</h3>
<ul><li><b>Safe Mode</b> – starts Windows with minimum drivers to remove faulty drivers or malware.</li><li><b>CHKDSK / surface scan</b> – finds and marks <b>bad sectors</b> on the disk.</li><li>Device Manager (drivers), Task Manager (start-up apps), Disk Cleanup, Windows Update.</li></ul>
<h3>Optimising the PC</h3>
<ul><li>Remove unwanted programs and start-up items; run Disk Cleanup; defragment HDDs (not SSDs).</li><li>Keep Windows, drivers and antivirus up to date.</li><li>In BIOS: put the system disk first in the boot order and enable fast boot.</li></ul>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.18", title: "Install necessary application software for Windows i.e. Office Package, PDF Reader, Media Player etc", m: 1,
    keys: ["System vs application software", "Types with examples", "Generic & bespoke", "Installing software", "Uninstalling"],
    html: `
<h3>Types of software</h3>
<ul><li><b>System software</b> – runs the computer: operating system, device drivers, utilities (antivirus, disk cleanup).</li><li><b>Programming software</b> – helps to write programs: compilers, interpreters, IDEs (VS Code, IDLE).</li><li><b>Application software</b> – made for a specific task of the user (all software other than system and programming software).</li></ul>
<h3>Application software – types and examples</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Examples</th></tr>
<tr><td>Word processing</td><td>MS Word, WordPad, Notepad, Google Docs, LibreOffice Writer</td></tr>
<tr><td>Spreadsheet</td><td>MS Excel, Google Sheets, Apple Numbers</td></tr>
<tr><td>Database</td><td>MS Access, Oracle, MySQL</td></tr>
<tr><td>Presentation</td><td>MS PowerPoint, Keynote, Google Slides</td></tr>
<tr><td>Multimedia</td><td>VLC, Windows Media Player, RealPlayer</td></tr>
<tr><td>Graphics</td><td>Pixel based: Paint, Photoshop, GIMP · Vector based: CorelDRAW, Illustrator, Inkscape</td></tr>
<tr><td>Desktop publishing (DTP)</td><td>MS Publisher, Adobe PageMaker / InDesign</td></tr>
<tr><td>Internet & e-mail</td><td>Chrome, Firefox, Edge · Outlook, Thunderbird</td></tr>
<tr><td>Educational / simulation</td><td>Encyclopedias, MATLAB, Google Earth, flight simulators</td></tr>
<tr><td>Enterprise</td><td>ERP (Tally, SAP), CRM (SugarCRM, Salesforce)</td></tr>
<tr><td>Application suites</td><td>Microsoft Office, LibreOffice / OpenOffice</td></tr></tbody></table>
<ul><li><b>Generic (off-the-shelf)</b> software is made for many users (MS Office).</li><li><b>Bespoke (custom)</b> software is made for one organisation's special needs.</li></ul>
<h3>Installing software</h3>
<ol><li>Download only from the <b>official website</b> or a trusted source (e.g. Adobe Acrobat Reader, VLC, LibreOffice).</li><li>Run the <b>setup.exe / .msi</b> file → allow the admin prompt (UAC).</li><li>Accept the licence, choose the folder and components (Typical / Custom) → <b>Install</b> → Finish.</li><li>For MS Office: sign in with the Microsoft account / enter the product key and activate.</li></ol>
<p><b>Uninstall:</b> Settings → Apps → select the app → Uninstall (or Control Panel → Programs and Features).</p>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.19", title: "Configure Bluetooth and Wi-Fi settings", m: 1,
    keys: ["Bluetooth", "Pairing", "Applications", "Wi-Fi", "SSID & security", "Connecting"],
    html: `
<h3>Bluetooth</h3>
<ul><li>A <b>short-range wireless</b> technology to exchange data between devices – usually up to about <b>10 metres</b>.</li><li>Works in the <b>2.4 GHz</b> band using frequency hopping, so many devices can work together.</li><li>Named after the Danish king <b>Harald Bluetooth</b>, who united Denmark and Norway.</li><li>A small network of Bluetooth devices is a <b>piconet</b> (one master with up to 7 active devices).</li><li>Devices must be <b>paired</b> once (confirm the code / PIN).</li></ul>
<h3>Applications of Bluetooth</h3>
<p>Wireless headsets and speakers, keyboard and mouse, file sharing between phones and PC, car hands-free kits, smart watches and fitness bands, printers, game controllers, medical and industrial sensors.</p>
<h3>Turning on Bluetooth in Windows 10</h3>
<ol><li>Settings → <b>Devices</b> → <b>Bluetooth &amp; other devices</b> → turn Bluetooth <b>On</b>.</li><li>Put the other device in pairing mode.</li><li>Click <b>Add Bluetooth or other device</b> → Bluetooth → choose the device → confirm the PIN → Done.</li></ul>
<h3>Wi-Fi</h3>
<ul><li>Wireless LAN based on the <b>IEEE 802.11</b> standards (b/g/n/ac/ax – Wi-Fi 6), using <b>2.4 GHz and 5 GHz</b> radio bands.</li><li>A <b>wireless router / access point</b> connects devices to the network and internet.</li><li><b>SSID</b> – the name of the Wi-Fi network.</li><li><b>Security</b> – use <b>WPA2 or WPA3</b> with a strong password; avoid open networks and old WEP.</li><li>A phone can share its mobile data as a <b>hotspot</b>.</li></ul>
<h3>Connecting to Wi-Fi in Windows</h3>
<ol><li>Click the network icon on the taskbar.</li><li>Choose the network (SSID) → <b>Connect</b> (tick "Connect automatically" if wanted).</li><li>Enter the password (network security key) → Next.</li></ol>
<p>Settings → Network &amp; Internet → Wi-Fi → <b>Manage known networks</b> to forget or change saved networks.</p>` },

  { part: 1, bm: 4, mt: "Computer Basics & Software Installation", no: "1.4.20&21", title: "DVDs, CDs and burning DVDs", m: 1,
    keys: ["CD, DVD, Blu-ray capacity", "Disc formats", "Speed (X)", "Live File System vs Mastered", "Burning steps"],
    html: `
<h3>Optical discs</h3>
<table class="rte-table"><tbody><tr><th>Disc</th><th>Capacity</th><th>Note</th></tr>
<tr><td><b>CD</b></td><td>650 / <b>700 MB</b> (about 80 min audio)</td><td>Infrared laser (780 nm)</td></tr>
<tr><td><b>DVD</b></td><td><b>4.7 GB</b> single layer · 8.5 GB dual layer</td><td>Red laser (650 nm)</td></tr>
<tr><td><b>Blu-ray</b></td><td><b>25 GB</b> single layer · 50 GB dual layer</td><td>Blue-violet laser (405 nm) – HD video</td></tr></tbody></table>
<h3>Disc types</h3>
<ul><li><b>ROM</b> (CD-ROM, DVD-ROM) – read only, data pressed at the factory.</li><li><b>R</b> (CD-R, DVD-R, DVD+R) – write <b>once</b>.</li><li><b>RW</b> (CD-RW, DVD-RW, DVD+RW) – rewritable many times; <b>DVD-RAM</b> also rewritable.</li></ul>
<p><b>Speed:</b> 1X CD = 150 KB/s; 1X DVD ≈ 1.385 MB/s. "16X" means 16 times the 1X speed (CDs up to 52X).</p>
<h3>Burning formats in Windows</h3>
<table class="rte-table"><tbody><tr><th>Live File System ("Like a USB flash drive")</th><th>Mastered ("With a CD/DVD player")</th></tr>
<tr><td>Files can be added or deleted later (on RW discs), saved one by one; works best on computers</td><td>All files are collected and burned at once; works on most computers and DVD players, cars and TVs</td></tr></tbody></table>
<h3>Burning a disc in Windows</h3>
<ol><li>Insert a blank CD/DVD → choose <b>Burn files to disc</b>.</li><li>Give a disc title and choose Live File System or Mastered.</li><li>Drag the files into the disc window.</li><li>Mastered: click <b>Drive Tools → Finish burning</b> (Burn to disc) and wait.</li><li>To burn an <b>ISO image</b> (e.g. Windows setup): right-click the .iso → <b>Burn disc image</b>.</li></ol>` },

  /* ===================== PART 1 · MODULE 5 : DOS Command Line Interface ===================== */
  { part: 1, bm: 5, mt: "DOS Command Line Interface", no: "1.5.22", title: "Use basic DOS commands for directory listing", m: 4,
    keys: ["Hierarchical directory", "Path", "DIR and switches", "MD", "CD", "RD", "Wildcards"],
    html: `
<h3>Hierarchical (tree) directory system</h3>
<p>Files are kept in <b>directories (folders)</b> arranged like a family tree. The top level is the <b>root directory</b> (<code>C:\\</code>); directories inside it are <b>sub-directories</b>.</p>
<pre>C:\\                 ← root (level 1)
├── COPA            ← level 2
│   ├── DOS         ← level 3
│   │   └── PRACT_1
│   └── LINUX
└── WINDOWS</pre>
<ul><li><b>Path</b> – the route to a file: <code>C:\\COPA\\DOS\\TEST.TXT</code>.</li><li><b>Absolute path</b> starts from the root; <b>relative path</b> starts from the current directory.</li><li>The prompt <code>C:\\COPA&gt;</code> shows the current drive and directory.</li><li>Change drive by typing its letter: <code>D:</code></li></ul>
<h3>Directory commands</h3>
<table class="rte-table"><tbody><tr><th>Command</th><th>Use</th><th>Example</th></tr>
<tr><td><b>DIR</b></td><td>List files and directories</td><td><code>DIR</code>, <code>DIR C:\\COPA</code></td></tr>
<tr><td><b>MD / MKDIR</b></td><td>Make a directory</td><td><code>MD COPA</code>, <code>MD C:\\COPA\\DOS</code></td></tr>
<tr><td><b>CD / CHDIR</b></td><td>Change directory</td><td><code>CD COPA</code>, <code>CD ..</code> (up), <code>CD \\</code> (root)</td></tr>
<tr><td><b>RD / RMDIR</b></td><td>Remove an <b>empty</b> directory</td><td><code>RD PRACT_1</code> (<code>RD /S</code> removes with contents)</td></tr>
<tr><td><b>TREE</b></td><td>Show the folder tree</td><td><code>TREE C:\\COPA /F</code></td></tr></tbody></table>
<h3>Useful DIR switches</h3>
<table class="rte-table"><tbody><tr><th>Switch</th><th>Meaning</th></tr>
<tr><td><code>/P</code></td><td>Pause after each screen</td></tr><tr><td><code>/W</code></td><td>Wide list (names only, in columns)</td></tr><tr><td><code>/S</code></td><td>Include all sub-directories</td></tr><tr><td><code>/B</code></td><td>Bare format – names only</td></tr>
<tr><td><code>/A:H</code> · <code>/A:S</code> · <code>/A:R</code> · <code>/A:D</code></td><td>Show hidden · system · read-only files · directories</td></tr>
<tr><td><code>/O:N</code> · <code>/O:S</code> · <code>/O:E</code> · <code>/O:D</code> · <code>/O:G</code></td><td>Sort by name · size · extension · date · directories first</td></tr></tbody></table>
<h3>Wildcards</h3>
<ul><li><code>*</code> – any number of characters: <code>DIR *.TXT</code> lists all text files.</li><li><code>?</code> – exactly one character: <code>DIR TEST?.TXT</code> → TEST1.TXT, TEST2.TXT.</li></ul>` },

  { part: 1, bm: 5, mt: "DOS Command Line Interface", no: "1.5.23", title: "Manage files and folders using DOS commands", m: 4,
    keys: ["TYPE", "COPY CON", "COPY / XCOPY", "REN", "DEL / ERASE", "UNDELETE", "MOVE, ATTRIB"],
    html: `
<h3>File commands</h3>
<table class="rte-table"><tbody><tr><th>Command</th><th>Use</th><th>Example</th></tr>
<tr><td><b>COPY CON</b></td><td>Create a text file from the keyboard; press <b>Ctrl + Z</b> (or F6) and Enter to save</td><td><code>COPY CON TEST1.TXT</code></td></tr>
<tr><td><b>TYPE</b></td><td>Display the contents of a text (ASCII) file</td><td><code>TYPE TEST1.TXT</code></td></tr>
<tr><td><b>COPY</b></td><td>Copy files</td><td><code>COPY TEST1.TXT TRIAL.TXT</code>, <code>COPY *.TXT D:\\BACKUP</code></td></tr>
<tr><td><b>XCOPY</b></td><td>Copy directories with sub-directories</td><td><code>XCOPY C:\\COPA D:\\COPA /S /E</code></td></tr>
<tr><td><b>REN / RENAME</b></td><td>Rename a file</td><td><code>REN TEST2.TXT CHECKED.TXT</code></td></tr>
<tr><td><b>DEL / ERASE</b></td><td>Delete files (<code>/P</code> asks before each file)</td><td><code>DEL TRIAL.TXT</code>, <code>DEL *.BAK</code></td></tr>
<tr><td><b>MOVE</b></td><td>Move a file or rename a directory</td><td><code>MOVE A.TXT D:\\DATA</code></td></tr>
<tr><td><b>ATTRIB</b></td><td>Show / change attributes: +R read-only, +H hidden, +S system, +A archive</td><td><code>ATTRIB +H SECRET.TXT</code></td></tr></tbody></table>
<h3>Important points</h3>
<ul><li><code>DEL *.*</code> deletes <b>all</b> files in the directory – DOS asks "Are you sure (Y/N)?".</li><li><code>COPY</code> asks before overwriting; <code>/Y</code> overwrites without asking, <code>/V</code> verifies the copy.</li><li>Messages: <b>"1 file(s) copied"</b> – success; <b>"File not found"</b> or <b>"Duplicate file name or file not found"</b> – wrong name, or the new name already exists (REN).</li><li>A file name in old DOS is <b>8.3</b> – up to 8 characters + 3-character extension (TEST1.TXT).</li></ul>
<h3>Recovering deleted files – UNDELETE</h3>
<p>In MS-DOS 5/6, <code>UNDELETE</code> could recover deleted files: <code>/LIST</code> shows recoverable files, <code>/ALL</code> recovers without asking, and <b>Delete Sentry / Delete Tracking</b> methods protected deleted files. Modern Windows does not have UNDELETE – use the <b>Recycle Bin</b> or recovery software.</p>` },

  /* ===================== PART 1 · MODULE 6 : Ubuntu Linux ===================== */
  { part: 1, bm: 6, mt: "Install Ubuntu Linux operating system and execute basic Linux commands", no: "1.6.24&25", title: "Introduction to Linux operating system", m: 4,
    keys: ["Operating system", "Linux history", "Kernel, shell", "Features", "Distributions", "Open source software", "Licences"],
    html: `
<h3>Operating system</h3>
<p>An <b>operating system</b> is system software that manages hardware and software and provides an interface between the user and the computer (e.g. Windows, Linux, macOS, Android).</p>
<h3>Linux</h3>
<ul><li>A free, <b>open-source, UNIX-like</b> operating system.</li><li>The <b>Linux kernel</b> was written by <b>Linus Torvalds</b> (Finland) in <b>1991</b>; with the GNU tools of Richard Stallman it is often called <b>GNU/Linux</b>.</li><li>Released under the <b>GNU GPL</b> licence – source code is free to use, change and share.</li></ul>
<h3>Parts of a Linux system</h3>
<table class="rte-table"><tbody><tr><th>Part</th><th>Work</th></tr>
<tr><td><b>Kernel</b></td><td>Core – manages CPU, memory, devices and processes</td></tr><tr><td><b>Shell</b></td><td>Command interpreter between user and kernel (bash)</td></tr><tr><td><b>Environment (GUI)</b></td><td>Desktop such as GNOME, KDE, XFCE</td></tr><tr><td><b>Applications</b></td><td>Browsers, office, media players, tools</td></tr></tbody></table>
<h3>Features</h3>
<p>Free and open source · <b>multi-user</b> · <b>multitasking</b> · secure (permissions, fewer viruses) · stable · portable (runs on many types of hardware) · large community support.</p>
<h3>Where Linux is used</h3>
<p>Web and cloud <b>servers</b>, <b>Android</b> phones (Linux kernel), almost all <b>supercomputers</b>, routers and embedded devices, smart TVs, and desktops in schools and offices.</p>
<h3>Distributions (distros)</h3>
<p><b>Ubuntu</b> (popular desktop, based on Debian), <b>Debian</b>, <b>Red Hat</b> (RHEL) and Fedora, CentOS / Rocky Linux, Linux Mint, Kali Linux (security), openSUSE.</p>
<h3>Open source software (OSS)</h3>
<ul><li>Software distributed <b>with its source code</b>, which anyone may use, study, change and share.</li><li>History: <b>GNU project (1983)</b> and Free Software Foundation by Richard Stallman; the term "open source" and the <b>Open Source Initiative (OSI)</b> came in <b>1998</b>.</li><li>Common licences: <b>GNU GPL</b> (changes must also stay open), <b>MIT</b> (very simple and permissive), <b>Apache 2.0</b> (permissive with patent rights).</li><li>Examples: Linux, Mozilla Firefox, VLC media player, LibreOffice, GIMP, Apache web server, SugarCRM, Python.</li></ul>` },

  { part: 1, bm: 6, mt: "Install Ubuntu Linux operating system and execute basic Linux commands", no: "1.6.26-29", title: "Handling commands and various editors", m: 4,
    keys: ["Terminal & shells", "Directory layout", "Special characters", "Basic commands", "File commands", "vi / nano editors"],
    html: `
<h3>Terminal and shell</h3>
<ul><li>Open the terminal in Ubuntu with <b>Ctrl + Alt + T</b>.</li><li>The <b>shell</b> reads commands and runs them. Common shells: <b>bash</b> (default), sh, zsh, ksh, csh.</li><li>See the shells available: <code>cat /etc/shells</code>; current shell: <code>echo $SHELL</code>.</li></ul>
<h3>Linux directory layout</h3>
<table class="rte-table"><tbody><tr><th>Directory</th><th>Contains</th></tr>
<tr><td><code>/</code></td><td>Root – top of the whole file system</td></tr><tr><td><code>/bin</code>, <code>/sbin</code></td><td>Essential user commands · system (admin) commands</td></tr><tr><td><code>/boot</code></td><td>Boot loader files and the kernel</td></tr><tr><td><code>/dev</code></td><td>Device files</td></tr><tr><td><code>/etc</code></td><td>Configuration files</td></tr><tr><td><code>/home</code></td><td>Home folders of users</td></tr><tr><td><code>/root</code></td><td>Home of the root (admin) user</td></tr><tr><td><code>/lib</code></td><td>Shared libraries</td></tr><tr><td><code>/media</code>, <code>/mnt</code></td><td>Mounted pen drives, CDs and other disks</td></tr><tr><td><code>/tmp</code></td><td>Temporary files</td></tr><tr><td><code>/usr</code></td><td>Installed programs and their files</td></tr><tr><td><code>/var</code></td><td>Variable data – logs, mail, spool</td></tr></tbody></table>
<h3>Special characters</h3>
<table class="rte-table"><tbody><tr><th>Character</th><th>Meaning</th></tr>
<tr><td><code>~</code></td><td>Your home directory</td></tr><tr><td><code>.</code> · <code>..</code></td><td>Current directory · parent directory</td></tr><tr><td><code>/</code></td><td>Root / separator in paths</td></tr><tr><td><code>*</code> · <code>?</code></td><td>Wildcards – many characters · one character</td></tr><tr><td><code>|</code></td><td>Pipe – output of one command to another</td></tr><tr><td><code>&gt;</code> · <code>&gt;&gt;</code></td><td>Redirect output to a file (overwrite · append)</td></tr><tr><td><code>&amp;</code> · <code>;</code></td><td>Run in background · run commands one after another</td></tr></tbody></table>
<h3>Basic commands</h3>
<p><code>pwd</code> current directory · <code>cd dir</code> / <code>cd ..</code> / <code>cd ~</code> change directory · <code>echo text</code> print text · <code>date</code> date and time · <code>cal</code> calendar · <code>clear</code> clear screen · <code>man ls</code> manual · <code>whatis ls</code> one-line description · <code>info ls</code> info pages · <code>ls --help</code> quick help.</p>
<h3>Working with files and directories</h3>
<p><code>ls -l</code> list · <code>mkdir</code> / <code>rmdir</code> make / remove directory · <code>touch f</code> new empty file · <code>cp a b</code> copy · <code>mv a b</code> move/rename · <code>rm f</code> / <code>rm -r dir</code> delete · <code>cat f</code> show · <code>more</code> / <code>less</code> page by page · <code>head</code> / <code>tail</code> first / last lines · <code>wc</code> count lines/words · <code>file f</code> type of file · <code>chmod 755 f</code> permissions.</p>
<h3>Text editors</h3>
<ul><li><b>vi / vim</b> – powerful terminal editor with modes: <b>command mode</b> (default), <b>insert mode</b> (<code>i</code>, Esc to leave) and <b>ex mode</b> (<code>:w</code> save, <code>:q</code> quit, <code>:wq</code> save and quit, <code>:q!</code> quit without saving). Movement: <code>h j k l</code> (left, down, up, right), <code>0</code> start / <code>$</code> end of line; <code>x</code> delete a character, <code>dd</code> delete a line.</li><li><b>nano</b> – simple editor: <b>Ctrl + O</b> save, <b>Ctrl + X</b> exit, Ctrl + K cut line.</li><li><b>gedit / Text Editor</b> – graphical editor like Notepad. Word processors: LibreOffice Writer.</li></ul>` }
];
