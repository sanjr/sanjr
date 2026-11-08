/* COPA MCQ – Quick Reference: keyboard shortcuts, commands, functions and full forms (built in) */
window.COPA_REFERENCE = [
  { id: "win", icon: "fa-brands fa-windows", color: "#0078d4", t: { en: "Windows Shortcuts", hi: "विंडोज़ शॉर्टकट", mr: "विंडोज शॉर्टकट" }, k: [
    ["Ctrl + C", "Copy"], ["Ctrl + X", "Cut"], ["Ctrl + V", "Paste"], ["Ctrl + Z", "Undo"], ["Ctrl + Y", "Redo"], ["Ctrl + A", "Select all"],
    ["Ctrl + S", "Save"], ["Ctrl + P", "Print"], ["Ctrl + F", "Find"], ["Ctrl + N", "New window / document"], ["Alt + Tab", "Switch between open windows"],
    ["Alt + F4", "Close the active window / shut down dialog"], ["Win", "Open the Start menu"], ["Win + D", "Show / hide the desktop"], ["Win + E", "Open File Explorer"],
    ["Win + L", "Lock the computer"], ["Win + R", "Open the Run box"], ["Win + I", "Open Settings"], ["Win + Shift + S", "Screenshot of part of the screen"],
    ["PrtScn", "Copy the whole screen as a picture"], ["Ctrl + Shift + Esc", "Open Task Manager"], ["Ctrl + Alt + Del", "Security screen (lock, sign out, Task Manager)"],
    ["F2", "Rename the selected file / folder"], ["F5", "Refresh"], ["Delete", "Move to Recycle Bin"], ["Shift + Delete", "Delete permanently"],
    ["Win + .", "Emoji panel"], ["Win + V", "Clipboard history"], ["Win + Tab", "Task view / virtual desktops"]] },
  { id: "word", icon: "fa-solid fa-file-word", color: "#2b579a", t: { en: "MS Word Shortcuts", hi: "MS Word शॉर्टकट", mr: "MS Word शॉर्टकट" }, k: [
    ["Ctrl + B", "Bold"], ["Ctrl + I", "Italic"], ["Ctrl + U", "Underline"], ["Ctrl + E", "Center align"], ["Ctrl + L", "Left align"], ["Ctrl + R", "Right align"],
    ["Ctrl + J", "Justify"], ["Ctrl + ] / Ctrl + [", "Increase / decrease font size by 1"], ["Ctrl + Shift + > / <", "Increase / decrease font size"],
    ["Shift + F3", "Change case (UPPER / lower / Title)"], ["Ctrl + K", "Insert hyperlink"], ["Ctrl + H", "Find and Replace"], ["Ctrl + G", "Go To (page, line)"],
    ["Ctrl + Enter", "Page break"], ["Ctrl + 1 / 2 / 5", "Line spacing 1 / 2 / 1.5"], ["F7", "Spelling & Grammar"], ["Shift + F7", "Thesaurus"],
    ["F12", "Save As"], ["Ctrl + Home / End", "Go to start / end of the document"], ["Ctrl + =", "Subscript"], ["Ctrl + Shift + =", "Superscript"],
    ["Ctrl + Space", "Remove character formatting"], ["Ctrl + Q", "Remove paragraph formatting"], ["Ctrl + D", "Font dialog box"], ["Ctrl + W", "Close document"]] },
  { id: "excel", icon: "fa-solid fa-file-excel", color: "#217346", t: { en: "MS Excel Shortcuts", hi: "MS Excel शॉर्टकट", mr: "MS Excel शॉर्टकट" }, k: [
    ["F2", "Edit the active cell"], ["F4", "Make reference absolute ($A$1) / repeat last action"], ["Alt + =", "AutoSum"], ["Ctrl + ;", "Insert today's date"],
    ["Ctrl + Shift + ;", "Insert current time"], ["Ctrl + 1", "Format Cells dialog"], ["Ctrl + Space", "Select the whole column"], ["Shift + Space", "Select the whole row"],
    ["Ctrl + Arrow key", "Jump to the edge of the data"], ["Ctrl + Home", "Go to cell A1"], ["Ctrl + D", "Fill down"], ["Ctrl + R", "Fill right"],
    ["Alt + Enter", "New line inside a cell"], ["Ctrl + Shift + L", "Turn filter on / off"], ["Ctrl + T", "Create a table"], ["F11", "Create a chart on a new sheet"],
    ["Alt + F1", "Create a chart on the same sheet"], ["Ctrl + Page Up / Down", "Previous / next sheet"], ["Shift + F11", "Insert a new sheet"],
    ["Ctrl + Shift + $", "Currency format"], ["Ctrl + Shift + %", "Percentage format"], ["Ctrl + `", "Show formulas"], ["Ctrl + E", "Flash Fill"], ["Ctrl + 9 / 0", "Hide row / column"]] },
  { id: "ppt", icon: "fa-solid fa-file-powerpoint", color: "#d24726", t: { en: "PowerPoint Shortcuts", hi: "पावरपॉइंट शॉर्टकट", mr: "पॉवरपॉइंट शॉर्टकट" }, k: [
    ["F5", "Start slide show from the beginning"], ["Shift + F5", "Start from the current slide"], ["Ctrl + M", "New slide"], ["Ctrl + D", "Duplicate object / slide"],
    ["Esc", "End the slide show"], ["B / .", "Black screen during the show"], ["W / ,", "White screen during the show"], ["N / → / Space", "Next slide"], ["P / ←", "Previous slide"],
    ["Number + Enter", "Go to that slide number"], ["Ctrl + G", "Group objects"], ["Ctrl + Shift + G", "Ungroup"], ["Ctrl + P", "Pen during the show"], ["Ctrl + E / L / R", "Center / left / right align text"]] },
  { id: "dos", icon: "fa-solid fa-terminal", color: "#334155", t: { en: "DOS / Command Prompt Commands", hi: "DOS / कमांड प्रॉम्प्ट कमांड", mr: "DOS / कमांड प्रॉम्प्ट कमांड्स" }, mono: true, k: [
    ["dir", "List files and folders"], ["cd folder / cd ..", "Change directory / go up one level"], ["md name (mkdir)", "Make a new directory"], ["rd name (rmdir)", "Remove an empty directory"],
    ["copy a.txt b.txt", "Copy a file"], ["xcopy src dst /s", "Copy folders with sub-folders"], ["move a.txt D:\\", "Move a file"], ["del a.txt", "Delete a file"], ["ren old.txt new.txt", "Rename a file"],
    ["type a.txt", "Show the contents of a text file"], ["cls", "Clear the screen"], ["ver", "Show the Windows version"], ["date / time", "Show or set date / time"],
    ["tree", "Show folder structure"], ["attrib +h a.txt", "Change file attributes (hide)"], ["ipconfig", "Show IP address details"], ["ping google.com", "Check network connection"],
    ["tasklist / taskkill", "List / stop running programs"], ["chkdsk", "Check the disk for errors"], ["help", "List commands"], ["exit", "Close the command prompt"]] },
  { id: "linux", icon: "fa-brands fa-linux", color: "#222c45", t: { en: "Linux Commands", hi: "लिनक्स कमांड", mr: "लिनक्स कमांड्स" }, mono: true, k: [
    ["pwd", "Show the current directory"], ["ls / ls -l / ls -a", "List files (long / with hidden)"], ["cd dir / cd ..", "Change directory / go up"], ["mkdir dir", "Make a directory"],
    ["rmdir dir", "Remove an empty directory"], ["touch file.txt", "Create an empty file"], ["cp a b", "Copy"], ["mv a b", "Move / rename"], ["rm file / rm -r dir", "Delete a file / folder"],
    ["cat file", "Show file contents"], ["nano file", "Edit a file in the terminal"], ["chmod 755 file", "Change permissions"], ["chown user file", "Change owner"],
    ["sudo command", "Run as administrator (root)"], ["apt install pkg", "Install a package (Ubuntu)"], ["grep word file", "Search text in a file"], ["man ls", "Manual / help for a command"],
    ["ps / top", "Running processes"], ["kill PID", "Stop a process"], ["df -h", "Disk space"], ["free -h", "Memory (RAM) usage"], ["whoami", "Current user"], ["clear", "Clear the screen"], ["echo text", "Print text"]] },
  { id: "xlf", icon: "fa-solid fa-square-root-variable", color: "#16a34a", t: { en: "Excel Functions", hi: "Excel फ़ंक्शन", mr: "Excel फंक्शन्स" }, mono: true, k: [
    ["=SUM(A1:A10)", "Total of the range"], ["=AVERAGE(A1:A10)", "Average"], ["=MAX(A1:A10) / =MIN()", "Largest / smallest value"], ["=COUNT(A1:A10)", "Count numbers"],
    ["=COUNTA(A1:A10)", "Count non-empty cells"], ["=IF(B2>=40,\"Pass\",\"Fail\")", "Condition"], ["=SUMIF(A:A,\"COPA\",B:B)", "Sum with a condition"], ["=COUNTIF(B:B,\">=40\")", "Count with a condition"],
    ["=VLOOKUP(101,A2:D50,3,FALSE)", "Find a value in the first column, return from column 3"], ["=HLOOKUP(x,A1:Z3,2,FALSE)", "Lookup in the first row"], ["=XLOOKUP(x,A:A,C:C)", "Modern lookup (Excel 365 / 2021)"],
    ["=CONCAT(A2,\" \",B2)", "Join text"], ["=LEN(A2)", "Number of characters"], ["=UPPER() / =LOWER() / =PROPER()", "Change case"], ["=TRIM(A2)", "Remove extra spaces"],
    ["=ROUND(A2,2)", "Round to 2 decimals"], ["=TODAY() / =NOW()", "Today's date / date and time"], ["=RANK(B2,B$2:B$40)", "Rank in a list"]] },
  { id: "py", icon: "fa-brands fa-python", color: "#3572a5", t: { en: "Python Basics", hi: "पायथन बेसिक्स", mr: "पायथन बेसिक्स" }, mono: true, k: [
    ["print(\"Hi\", x)", "Show output"], ["x = input(\"Name: \")", "Read input (always text)"], ["int(x) / float(x) / str(x)", "Convert type"], ["type(x)", "Type of a value"], ["len(s)", "Length"],
    ["range(1, 11)", "Numbers 1 to 10"], ["for i in range(5):", "Loop 5 times"], ["while n > 0:", "Loop while condition is true"], ["if / elif / else", "Decisions"],
    ["def area(r): return 3.14*r*r", "Function"], ["lst.append(x) / lst.sort()", "List methods"], ["d = {\"a\": 1}; d[\"a\"]", "Dictionary"], ["s.upper() / s.split()", "String methods"],
    ["import math; math.sqrt(16)", "Use a module"], ["try: ... except ValueError:", "Handle errors"], ["# comment", "Comment"], ["// and %", "Floor division and remainder"], ["**", "Power (2**3 = 8)"]] },
  { id: "html", icon: "fa-brands fa-html5", color: "#e44d26", t: { en: "HTML Tags", hi: "HTML टैग", mr: "HTML टॅग्स" }, mono: true, k: [
    ["<h1> … <h6>", "Headings"], ["<p>", "Paragraph"], ["<br> / <hr>", "Line break / horizontal line"], ["<b> <i> <u>", "Bold / italic / underline"], ["<a href=\"…\">", "Hyperlink"],
    ["<img src=\"…\" alt=\"…\">", "Image"], ["<ul> <ol> <li>", "Lists"], ["<table> <tr> <th> <td>", "Table"], ["<form> <input> <button>", "Form"], ["<select> <option>", "Drop-down list"],
    ["<div> / <span>", "Block / inline container"], ["<header> <nav> <main> <footer>", "Page sections (HTML5)"], ["<audio> / <video>", "Media"], ["<link rel=\"stylesheet\">", "Attach CSS"], ["<script src=\"…\">", "Attach JavaScript"]] },
  { id: "net", icon: "fa-solid fa-network-wired", color: "#0ea5e9", t: { en: "Network Ports & Protocols", hi: "नेटवर्क पोर्ट और प्रोटोकॉल", mr: "नेटवर्क पोर्ट आणि प्रोटोकॉल" }, k: [
    ["HTTP – 80", "Web pages"], ["HTTPS – 443", "Secure web pages"], ["FTP – 20 / 21", "File transfer"], ["SSH – 22", "Secure remote login"], ["Telnet – 23", "Remote login (not secure)"],
    ["SMTP – 25", "Send e-mail"], ["POP3 – 110", "Receive e-mail (download)"], ["IMAP – 143", "Receive e-mail (sync)"], ["DNS – 53", "Domain name → IP address"], ["DHCP – 67 / 68", "Automatic IP address"],
    ["RDP – 3389", "Remote Desktop"], ["IPv4", "32-bit address, e.g. 192.168.1.10"], ["IPv6", "128-bit address"], ["Class C private", "192.168.0.0 – 192.168.255.255"]] },
  { id: "ff", icon: "fa-solid fa-spell-check", color: "#8b5cf6", t: { en: "Important Full Forms", hi: "महत्वपूर्ण फुल फॉर्म", mr: "महत्त्वाचे फुल फॉर्म" }, k: [
    ["CPU", "Central Processing Unit"], ["ALU", "Arithmetic Logic Unit"], ["RAM", "Random Access Memory"], ["ROM", "Read Only Memory"], ["BIOS", "Basic Input Output System"],
    ["SSD / HDD", "Solid State Drive / Hard Disk Drive"], ["USB", "Universal Serial Bus"], ["OS", "Operating System"], ["GUI / CLI", "Graphical / Command Line User Interface"],
    ["DOS", "Disk Operating System"], ["LAN / MAN / WAN", "Local / Metropolitan / Wide Area Network"], ["IP", "Internet Protocol"], ["URL", "Uniform Resource Locator"],
    ["HTML", "HyperText Markup Language"], ["CSS", "Cascading Style Sheets"], ["HTTP", "HyperText Transfer Protocol"], ["DBMS / SQL", "Database Management System / Structured Query Language"],
    ["ISP", "Internet Service Provider"], ["Wi-Fi", "Wireless Fidelity"], ["PDF", "Portable Document Format"], ["SaaS / PaaS / IaaS", "Software / Platform / Infrastructure as a Service"],
    ["UPI", "Unified Payments Interface"], ["OTP", "One Time Password"], ["COPA", "Computer Operator and Programming Assistant"], ["ITI", "Industrial Training Institute"]] }
];
