/* COPA MCQ – Theory Notes, PART 2 (Modules 7–32). Study notes written for COPA MCQ following the
   COPA Trade Theory (NSQF 2022) lesson plan – not a copy of the book. Format: see js/theory-data.js */
window.COPA_THEORY = (window.COPA_THEORY || []).concat([
  /* ===================== MS WORD ===================== */
  { part: 2, bm: 7, mt: "Using Word Processing Software", no: "1.7.30-33", title: "MS Word 2010 theory", m: 5,
    keys: ["MS Office apps", "Word window", "Ribbon tabs & groups", "Create, save, open", "New in 2010"],
    html: `
<h3>Microsoft Office</h3>
<p><b>MS Office</b> is an application software package (suite) from Microsoft.</p>
<table class="rte-table"><tbody><tr><th>Program</th><th>Used for</th><th>File</th></tr>
<tr><td>Word</td><td>Letters, reports, documents</td><td>.docx</td></tr><tr><td>Excel</td><td>Spreadsheets, calculations, charts</td><td>.xlsx</td></tr><tr><td>PowerPoint</td><td>Presentations / slides</td><td>.pptx</td></tr><tr><td>Access</td><td>Databases</td><td>.accdb</td></tr><tr><td>Outlook</td><td>E-mail, calendar, contacts</td><td>.pst</td></tr><tr><td>Publisher / OneNote</td><td>DTP / digital notebook</td><td>.pub / .one</td></tr></tbody></table>
<h3>Starting Word and the Word window</h3>
<p>Start → All Programs → Microsoft Office → Microsoft Word 2010 (or Win + R → <code>winword</code>). Parts of the window: <b>Title bar</b>, <b>Quick Access Toolbar</b> (Save, Undo, Redo – customisable), <b>File tab</b> (Backstage view), <b>Ribbon</b> with tabs and groups, <b>Ruler</b>, document area with the insertion point, <b>Status bar</b> (page, words, language), <b>view buttons</b> and <b>Zoom</b> slider.</p>
<h3>Ribbon tabs and their groups</h3>
<table class="rte-table"><tbody><tr><th>Tab</th><th>Groups</th></tr>
<tr><td><b>Home</b></td><td>Clipboard, Font, Paragraph, Styles, Editing</td></tr>
<tr><td><b>Insert</b></td><td>Pages, Tables, Illustrations, Links, Header &amp; Footer, Text, Symbols</td></tr>
<tr><td><b>Page Layout</b></td><td>Themes, Page Setup, Page Background (watermark, page colour, borders), Paragraph, Arrange</td></tr>
<tr><td><b>References</b></td><td>Table of Contents, Footnotes, Citations &amp; Bibliography, Captions, Index, Table of Authorities</td></tr>
<tr><td><b>Mailings</b></td><td>Create (envelopes, labels), Start Mail Merge, Write &amp; Insert Fields, Preview Results, Finish</td></tr>
<tr><td><b>Review</b></td><td>Proofing (spelling, thesaurus, word count), Language, Comments, Tracking, Changes, Compare, Protect</td></tr>
<tr><td><b>View</b></td><td>Document Views, Show (ruler, gridlines, navigation pane), Zoom, Window, Macros</td></tr></tbody></table>
<h3>Create, save and open</h3>
<ul><li><b>New</b> – Ctrl + N (blank document) or File → New → templates.</li><li><b>Save</b> – Ctrl + S; first time it opens <b>Save As</b> (F12) – choose folder, name and type: <b>.docx</b> (Word 2007+), .doc (Word 97-2003), .dotx (template), PDF, .rtf, .txt.</li><li><b>Open</b> – Ctrl + O; <b>Close</b> – Ctrl + W; <b>Print</b> – Ctrl + P.</li></ul>
<h3>New in Word 2010</h3>
<p><b>Backstage view</b> (File tab), <b>Navigation Pane</b> (Ctrl + F – search and headings), <b>Paste preview</b> (paste options: keep source formatting, merge formatting, keep text only), <b>Screenshot</b> tool (Insert → Screenshot), text effects, picture background removal and a <b>customisable Ribbon</b>.</p>` },

  { part: 2, bm: 8, mt: "Format documents", no: "1.8.34-36", title: "Insert, format text and paragraphs, Create and configure document sections", m: 5,
    keys: ["Typing & selecting", "Font formatting", "Format Painter", "Paragraph formatting", "Section breaks", "Columns, headers"],
    html: `
<h3>Inserting and selecting text</h3>
<ul><li>Type at the insertion point; <b>Insert</b> key switches between insert and overtype mode.</li><li>Insert → <b>Symbol</b>, <b>Date &amp; Time</b>, <b>Object → Text from file</b>.</li><li>Select: drag, <b>double-click</b> = word, <b>triple-click</b> = paragraph, Ctrl + click = sentence, Shift + arrows, <b>Ctrl + A</b> = whole document, click in the left margin = line.</li></ul>
<h3>Formatting text (Home → Font)</h3>
<p>Font face and size, <b>Bold</b> (Ctrl + B), <i>Italic</i> (Ctrl + I), <u>Underline</u> (Ctrl + U), strikethrough, subscript (Ctrl + =), superscript (Ctrl + Shift + =), font colour, text highlight colour, text effects, <b>Change Case</b> (Shift + F3: Sentence case, lowercase, UPPERCASE, Capitalize Each Word, tOGGLE cASE) and <b>Clear Formatting</b>. Font dialog: Ctrl + D.</p>
<h3>Copy formatting – Format Painter</h3>
<p>Select formatted text → click <b>Format Painter</b> (once to paint one place, <b>double-click</b> to paint many places, Esc to stop) → drag over the target text. Keyboard: Ctrl + Shift + C / Ctrl + Shift + V.</p>
<h3>Formatting paragraphs (Home → Paragraph)</h3>
<ul><li><b>Alignment</b>: Left (Ctrl + L), Center (Ctrl + E), Right (Ctrl + R), Justify (Ctrl + J).</li><li><b>Indents</b>: left, right, first line, hanging (ruler markers or Paragraph dialog).</li><li><b>Line spacing</b> 1.0 / 1.15 / 1.5 / 2.0 and spacing <b>before / after</b> a paragraph.</li><li>Bullets, numbering, multilevel lists, borders and shading, tab stops, sort, show/hide ¶ (Ctrl + Shift + 8).</li><li><b>Styles</b> (Normal, Heading 1, Title…) apply a set of formats at once and are used for the table of contents.</li></ul>
<h3>Document sections</h3>
<p>A <b>section</b> is a part of the document that can have its own page setup. Insert with <b>Page Layout → Breaks</b>:</p>
<table class="rte-table"><tbody><tr><th>Section break</th><th>New section starts…</th></tr>
<tr><td>Next Page</td><td>on the next page</td></tr><tr><td>Continuous</td><td>on the same page (e.g. for columns)</td></tr><tr><td>Even Page / Odd Page</td><td>on the next even / odd page</td></tr></tbody></table>
<p>Use sections for a different <b>orientation</b> (portrait / landscape), margins, <b>columns</b>, headers / footers or page numbering in one part of the document. A simple <b>page break</b> is Ctrl + Enter.</p>
<h3>Page setup items</h3>
<p>Margins, orientation, paper size (A4), columns, line numbers, hyphenation; Insert → <b>Header, Footer, Page Number</b>; Page Layout → Watermark, Page Colour, Page Borders.</p>` },

  { part: 2, bm: 9, mt: "Manage Tables and Lists", no: "1.9.37-39", title: "Create, modify tables", m: 5,
    keys: ["Insert table", "Convert text to table", "AutoFit", "Table Tools Design/Layout", "Merge & split", "Lists"],
    html: `
<h3>Creating a table (Insert → Table)</h3>
<ul><li>Drag over the <b>grid</b> to choose rows × columns.</li><li><b>Insert Table…</b> – type the number of columns and rows and choose AutoFit behaviour.</li><li><b>Draw Table</b> – draw cells with the pencil; Eraser removes lines.</li><li><b>Quick Tables</b> – ready-made calendars and tables; <b>Excel Spreadsheet</b> – embed a worksheet.</li><li>Move with <b>Tab</b> (next cell) and Shift + Tab; Tab in the last cell adds a new row.</li></ul>
<h3>Convert text to a table</h3>
<p>Type the data separated by tabs or commas → select it → Insert → Table → <b>Convert Text to Table</b> → choose the separator. The reverse is <b>Layout → Convert to Text</b>.</p>
<table class="rte-table"><tbody><tr><th>AutoFit option</th><th>Effect</th></tr>
<tr><td>AutoFit to contents</td><td>Columns fit the text in them</td></tr><tr><td>AutoFit to window</td><td>Table fills the page width</td></tr><tr><td>Fixed column width</td><td>Width you specify stays the same</td></tr></tbody></table>
<h3>Table Tools</h3>
<ul><li><b>Design tab</b>: table styles, header row, total row, banded rows/columns, <b>shading</b>, <b>borders</b>, draw borders.</li><li><b>Layout tab</b>: select, view gridlines, properties, delete, <b>insert rows above/below and columns left/right</b>, <b>Merge cells</b>, <b>Split cells</b>, <b>Split table</b> (Ctrl + Shift + Enter), cell size, distribute rows/columns, alignment, text direction, cell margins, sort, <b>Repeat header rows</b>, convert to text, <b>Formula</b> (e.g. <code>=SUM(ABOVE)</code>).</li></ul>
<h3>Lists</h3>
<ul><li><b>Bullets</b> – unordered list; <b>Numbering</b> – 1, 2, 3 / a, b, c / i, ii, iii; <b>Multilevel list</b> – outline 1, 1.1, 1.1.1.</li><li>Tab = move an item one level down, Shift + Tab = one level up.</li><li>Define New Bullet (symbol / picture) and Define New Number Format; Set Numbering Value to restart or continue.</li></ul>` },

  { part: 2, bm: 10, mt: "Create and Manage References", no: "1.10.40&41", title: "Create and manage reference elements and tables", m: 5,
    keys: ["Table of contents", "Footnotes & endnotes", "Citations & bibliography", "Captions", "Index", "Cross-reference"],
    html: `
<h3>Table of Contents (TOC)</h3>
<ol><li>Apply <b>Heading 1, Heading 2, Heading 3</b> styles to chapter titles.</li><li>Place the cursor where the TOC should be → <b>References → Table of Contents</b> → choose Automatic Table 1/2.</li><li>After editing the document click <b>Update Table</b> (page numbers only or entire table).</li><li><b>Custom Table of Contents</b> – choose levels, tab leader (dots), formats, page numbers.</li></ol>
<h3>Footnotes and endnotes</h3>
<ul><li><b>Footnote</b> – note at the bottom of the same page (Alt + Ctrl + F).</li><li><b>Endnote</b> – note at the end of the document or section (Alt + Ctrl + D).</li><li>Numbers update automatically when notes are added or deleted; Footnote dialog sets the number format.</li></ul>
<h3>Citations and bibliography</h3>
<p>References → <b>Insert Citation</b> → Add New Source (author, title, year, publisher). Choose the <b>style</b> (APA, MLA, Chicago…). <b>Manage Sources</b> edits the list; <b>Bibliography</b> inserts the list of works cited.</p>
<h3>Captions and table of figures</h3>
<p>Select a picture or table → References → <b>Insert Caption</b> (Figure 1, Table 1 – numbered automatically). <b>Insert Table of Figures</b> lists all captions with page numbers.</p>
<h3>Index and cross-reference</h3>
<ul><li><b>Mark Entry</b> (Alt + Shift + X) for important words → <b>Insert Index</b> builds an alphabetical index with page numbers.</li><li><b>Cross-reference</b> – "see Figure 2 on page 5" that updates automatically.</li><li><b>Table of Authorities</b> – list of legal cases and statutes (Mark Citation).</li></ul>` },

  { part: 2, bm: 11, mt: "Manage Graphic Elements", no: "1.11.42-45", title: "Insert, format illustrations and text boxes", m: 5,
    keys: ["Pictures & clip art", "Shapes & SmartArt", "Picture Tools", "Text wrapping", "Text box & WordArt", "Captions"],
    html: `
<h3>Insert → Illustrations</h3>
<p><b>Picture</b> (from file), <b>Clip Art</b>, <b>Shapes</b>, <b>SmartArt</b> (diagrams), <b>Chart</b> and <b>Screenshot</b>.</p>
<h3>Picture Tools → Format tab</h3>
<table class="rte-table"><tbody><tr><th>Group</th><th>Options</th></tr>
<tr><td>Adjust</td><td><b>Remove Background</b>, Corrections (sharpen/soften, brightness/contrast), Color (saturation, tone, recolor), <b>Artistic Effects</b>, Compress, Change, Reset Picture</td></tr>
<tr><td>Picture Styles</td><td>Ready styles, picture border, picture effects (shadow, reflection, glow, 3-D rotation), picture layout</td></tr>
<tr><td>Arrange</td><td>Position, <b>Wrap Text</b>, bring forward / send backward, align, group, rotate</td></tr>
<tr><td>Size</td><td><b>Crop</b>, height and width (lock aspect ratio)</td></tr></tbody></table>
<h3>Text wrapping options</h3>
<p><b>In Line with Text</b> (default – moves like a character), <b>Square</b>, <b>Tight</b>, <b>Through</b>, <b>Top and Bottom</b>, <b>Behind Text</b>, <b>In Front of Text</b>.</p>
<h3>Shapes and SmartArt</h3>
<ul><li>Shapes: lines, rectangles, arrows, flowchart symbols, callouts, stars. <b>Drawing Tools → Format</b>: shape fill, outline, effects, add text.</li><li>SmartArt: List, Process, Cycle, Hierarchy, Relationship, Matrix, Pyramid, Picture – type text in the text pane.</li></ul>
<h3>Text box, WordArt and Drop Cap</h3>
<ul><li>Insert → <b>Text Box</b> → built-in or Draw Text Box; format with Drawing Tools (fill, outline, text direction, wrap).</li><li><b>WordArt</b> – decorative text effects.</li><li><b>Drop Cap</b> – big first letter of a paragraph (Dropped / In margin).</li></ul>
<h3>Caption for a picture</h3>
<p>Right-click the picture → <b>Insert Caption</b> → label (Figure) and position (below the selected item).</p>` },

  { part: 2, bm: 12, mt: "Manage Document Collaboration", no: "1.12.46&47", title: "Manage comments change tracking and mailings", m: 5,
    keys: ["Comments", "Track changes", "Markup views", "Accept / reject", "Compare & protect", "Share & co-edit"],
    html: `
<h3>Comments (Review → Comments)</h3>
<ul><li><b>New Comment</b> (Ctrl + Alt + M) – a note in the margin without changing the text.</li><li>Previous / Next to move between comments, <b>Delete</b> (one or all), reply to a comment, Show Markup → Comments to show or hide.</li></ul>
<h3>Track Changes</h3>
<ul><li><b>Review → Track Changes</b> (Ctrl + Shift + E) – turns on and off; the button looks highlighted when on.</li><li>Insertions are shown <u>underlined in colour</u>, deletions <s>struck through</s> (or in balloons), and a line in the margin marks changed lines.</li><li>Each reviewer gets a different colour; <b>Show Markup → Reviewers</b> shows changes of selected people only.</li><li>Change user name: File → Options → General → User name.</li></ul>
<h3>Ways to display changes</h3>
<table class="rte-table"><tbody><tr><th>Word 2010 view</th><th>Newer Word</th><th>Shows</th></tr>
<tr><td>Final: Show Markup</td><td>All Markup</td><td>Final text with all changes marked</td></tr><tr><td>Final</td><td>No Markup</td><td>How the document looks if all changes are accepted</td></tr>
<tr><td>Original: Show Markup</td><td>–</td><td>Original text with changes marked</td></tr><tr><td>Original</td><td>Original</td><td>The text before any changes</td></tr></tbody></table>
<p>Balloons: show revisions in balloons, show all revisions inline, or show only comments and formatting in balloons.</p>
<h3>Accept or reject</h3>
<p><b>Accept</b> → Accept and Move to Next, Accept This Change, Accept All Changes Shown, Accept All Changes in Document. <b>Reject</b> works the same way. Right-click a change also gives Accept / Reject.</p>
<h3>Compare, combine and protect</h3>
<ul><li><b>Compare</b> – shows differences between two versions; <b>Combine</b> – merges changes from several reviewers.</li><li><b>Restrict Editing</b> – allow only comments, tracked changes or form filling; File → Info → <b>Protect Document</b> → Encrypt with password, Mark as Final.</li><li><b>Share / co-edit</b> – save to OneDrive or SharePoint and invite people; several people can edit together.</li></ul>
<p>Mailings for many letters, envelopes or labels are explained in the next lesson (mail merge).</p>` },

  { part: 2, bm: 13, mt: "Manage Mailings", no: "1.13.48", title: "Perform mail merge", m: 5,
    keys: ["Main document", "Data source", "Merge fields", "6 steps", "Link vs embed object", "Templates"],
    html: `
<h3>Mail merge – meaning</h3>
<p><b>Mail merge</b> creates many personalised documents (letters, envelopes, labels, e-mails) from one <b>main document</b> and a <b>list of recipients</b>.</p>
<table class="rte-table"><tbody><tr><th>Term</th><th>Meaning</th></tr>
<tr><td>Main (starting) document</td><td>The letter text that is the same for everyone</td></tr><tr><td>Data source</td><td>The list – Excel sheet, Access table, Outlook contacts or a new list typed in Word</td></tr>
<tr><td>Record / field</td><td>One row (one person) / one column (Name, Address)</td></tr><tr><td>Merge field</td><td>Placeholder «Name» that is replaced by data</td></tr></tbody></table>
<h3>Steps (Mailings → Start Mail Merge → Step-by-Step Wizard)</h3>
<ol><li><b>Select document type</b> – Letters, E-mail messages, Envelopes, Labels or Directory.</li><li><b>Select starting document</b> – current document, a template or an existing document.</li><li><b>Select recipients</b> – use an existing list, Outlook contacts or type a new list; tick / untick, sort and filter.</li><li><b>Write your letter</b> – insert <b>Address Block</b>, <b>Greeting Line</b> or individual <b>Insert Merge Field</b>s.</li><li><b>Preview</b> the letters (« » arrows) and correct mistakes.</li><li><b>Complete the merge</b> – <b>Print</b> directly or <b>Edit Individual Letters</b> (creates a new document with all letters).</li></ol>
<h3>Objects – link or embed</h3>
<p>Insert → <b>Object</b> → Create from File. <b>Embed</b> = a copy is stored inside the Word file. <b>Link to file</b> = only a link is stored; changes in the original file appear in Word.</p>
<h3>Templates</h3>
<ul><li>A <b>template (.dotx)</b> is a ready pattern with layout, styles and text (resume, letterhead, invoice).</li><li><b>Create:</b> design the document → File → Save As → type <b>Word Template (*.dotx)</b>.</li><li><b>Use:</b> File → New → My templates (or sample templates) → a new document opens based on it, so the template stays unchanged.</li><li>Macro-enabled template: .dotm.</li></ul>` },

  /* ===================== MS EXCEL ===================== */
  { part: 2, bm: 14, mt: "Spread Sheet Application, Manage Worksheets and Workbooks", no: "1.14.49-54", title: "Open files in MS Excel", m: 6,
    keys: ["Uses of Excel", "Excel window", "Workbook & worksheet", "Managing sheets", "Views & freeze panes", "Page setup & print area"],
    html: `
<h3>What Excel is used for</h3>
<p>Data entry and data management, accounting and budgeting, calculations with formulas, data analysis, charts and graphs, financial modelling, reports and simple programming (macros/VBA). It is popular because it is easy, powerful, has huge community support and is updated by Microsoft.</p>
<h3>The Excel window</h3>
<ul><li><b>Name Box</b> – shows the address of the active cell (e.g. B4); <b>Formula bar</b> – shows / edits the cell content.</li><li><b>Columns</b> A, B … XFD (<b>16,384</b>) and <b>rows</b> 1 … <b>1,048,576</b> (Excel 2007 and later).</li><li><b>Cell</b> = meeting of a row and column; its address is column letter + row number.</li><li><b>Sheet tabs</b>, status bar (Sum, Average, Count of selected cells), zoom and view buttons.</li></ul>
<h3>Workbook and worksheets</h3>
<ul><li>A <b>workbook</b> is the Excel file (<b>.xlsx</b>; .xls old; .xlsm with macros; .csv plain text). It contains <b>worksheets</b> (Excel 2010 starts with 3 sheets).</li><li>Right-click a sheet tab to <b>Insert, Delete, Rename, Move or Copy</b>, change <b>Tab Color</b>, Hide / Unhide, Protect Sheet. Shift + F11 inserts a sheet.</li><li>Ctrl + Page Up / Page Down moves between sheets.</li></ul>
<h3>Views and freezing</h3>
<ul><li>View → <b>Normal</b>, <b>Page Layout</b>, <b>Page Break Preview</b>, Full Screen.</li><li><b>Freeze Panes</b> keeps headings visible while scrolling (Freeze Top Row, Freeze First Column).</li><li>Split, New Window, Arrange All, Zoom.</li></ul>
<h3>Page setup and printing</h3>
<ul><li>Page Layout → Margins, Orientation, Size, <b>Print Titles</b> (repeat heading rows on every page), Scale to fit, Gridlines / Headings print.</li><li><b>Print Area</b>: select cells → Page Layout → Print Area → <b>Set Print Area</b> (you can add more areas; each prints on its own page) → Clear Print Area.</li><li>Insert → Header &amp; Footer (page number, date, file name); File → Print shows the preview.</li></ul>` },

  { part: 2, bm: 15, mt: "Manage Data Cells and Ranges", no: "1.15.55-57", title: "Manipulate data", m: 6,
    keys: ["Entering data", "Number formats", "AutoFill", "Paste Special", "Insert/delete rows", "Named ranges", "Cell range"],
    html: `
<h3>Entering and editing data</h3>
<ul><li>Text is aligned <b>left</b>, numbers and dates <b>right</b> by default. Enter = next row, Tab = next column, <b>F2</b> = edit the cell, Esc = cancel.</li><li>A number stored as text: type an apostrophe first – <code>'0123</code>.</li><li>Alt + Enter starts a new line inside a cell; <b>Wrap Text</b> shows long text on several lines.</li></ul>
<h3>Changing the data type (Number format – Ctrl + 1)</h3>
<p>General, <b>Number</b> (decimal places, 1000 separator), <b>Currency</b> (₹), <b>Accounting</b>, <b>Date</b>, Time, <b>Percentage</b>, Fraction, Scientific, <b>Text</b>, Special, Custom. Formatting changes only how a value looks, not the value itself.</p>
<h3>Filling and copying</h3>
<ul><li><b>Fill handle</b> (small square at the cell corner) – drag to copy or continue a series: 1, 2, 3 · Jan, Feb · Mon, Tue. Ctrl + D = fill down, Ctrl + R = fill right.</li><li>Home → Fill → <b>Series</b> for step values; <b>Flash Fill</b> (Ctrl + E, Excel 2013+) fills a pattern.</li><li><b>Paste Special</b> (Ctrl + Alt + V): Values, Formulas, Formats, <b>Transpose</b> (rows ↔ columns), Add/Multiply.</li><li>Find (Ctrl + F) and Replace (Ctrl + H); Go To (F5 / Ctrl + G).</li></ul>
<h3>Rows and columns</h3>
<ul><li>Right-click a row number / column letter → <b>Insert</b>, <b>Delete</b>, Hide / Unhide, Row Height / Column Width; double-click the border = AutoFit.</li><li>Merge &amp; Center, borders, fill colour, cell styles, <b>Conditional Formatting</b> (colour cells by rules), <b>Data Validation</b> (allow only certain values / drop-down list).</li></ul>
<h3>Cell range and named ranges</h3>
<ul><li>A <b>range</b> is a group of cells written with a colon: <code>A1:D10</code>. Non-adjacent cells: hold <b>Ctrl</b> – <code>A1:A5,C1:C5</code>.</li><li><b>Name a range</b>: select it and type a name in the Name Box (e.g. <code>Marks</code>), or Formulas → Define Name; manage with <b>Name Manager</b> (Ctrl + F3). Use it in formulas: <code>=SUM(Marks)</code>.</li></ul>` },

  { part: 2, bm: 16, mt: "Manage Tables and Table Data", no: "1.16.58-60", title: "Create and format tables", m: 6,
    keys: ["Create table (Ctrl + T)", "Table styles", "Total row", "Sorting", "Filtering", "Convert to range"],
    html: `
<h3>Creating an Excel table</h3>
<p>Select the data with headings → <b>Insert → Table</b> or <b>Ctrl + T</b> → tick "My table has headers". A table gets filter buttons, banded rows, automatic expansion when you add rows and its own name (Table1).</p>
<h3>Table Tools → Design</h3>
<ul><li><b>Table Styles</b> – colours and banding (Format as Table on the Home tab does the same).</li><li>Table Style Options: <b>Header Row</b>, <b>Total Row</b> (Sum, Average, Count… from a drop-down), Banded Rows / Columns, First / Last Column.</li><li>Table Name, <b>Resize Table</b>, <b>Remove Duplicates</b>, Summarize with PivotTable.</li><li><b>Convert to Range</b> – turns the table back into normal cells (formatting stays).</li></ul>
<h3>Sorting</h3>
<ul><li>Data → <b>Sort A to Z</b> / Z to A (numbers smallest to largest).</li><li><b>Custom Sort</b> – several levels (e.g. Batch, then Marks largest to smallest), sort by cell colour, or a custom list (Jan, Feb…).</li><li>Select all the data (or click inside the table) so rows stay together.</li></ul>
<h3>Filtering</h3>
<ul><li>Data → <b>Filter</b> (Ctrl + Shift + L) shows arrow buttons in the header row.</li><li>Tick values to show; or use <b>Text Filters</b> (contains, begins with), <b>Number Filters</b> (greater than, top 10, above average) and <b>Date Filters</b>.</li><li>Several columns can be filtered at once; <b>Clear</b> removes filters. Advanced Filter copies results to another place.</li></ul>
<blockquote>Structured references: in a table a formula can use column names, e.g. <code>=SUM(Table1[Marks])</code>.</blockquote>` },

  { part: 2, bm: 17, mt: "Perform Operations using Formulas and Functions", no: "1.17.61-63", title: "Functions and formulas in MS-Excel 2010", m: 6,
    keys: ["Formula elements", "Operators & precedence", "Relative / absolute", "Function arguments", "Function categories", "Error values"],
    html: `
<h3>Formulas</h3>
<ul><li>Every formula starts with <b>=</b>. Elements: <b>values or text</b>, <b>cell references</b> (including named cells and ranges), <b>operators</b> and <b>worksheet functions</b> (SUM, AVERAGE).</li><li>Example: <code>=B2+C2+D2</code>, <code>=(B2+C2)/2</code>, <code>=SUM(B2:D2)</code>.</li></ul>
<table class="rte-table"><tbody><tr><th>Order</th><th>Operators</th></tr>
<tr><td>1</td><td><code>( )</code> brackets</td></tr><tr><td>2</td><td><code>%</code> percent, <code>^</code> power</td></tr><tr><td>3</td><td><code>*</code> <code>/</code> multiply, divide</td></tr><tr><td>4</td><td><code>+</code> <code>-</code> add, subtract</td></tr><tr><td>5</td><td><code>&amp;</code> join text</td></tr><tr><td>6</td><td><code>= &lt; &gt; &lt;= &gt;= &lt;&gt;</code> comparison (TRUE / FALSE)</td></tr></tbody></table>
<h3>Cell references</h3>
<ul><li><b>Relative</b> <code>A1</code> – changes when copied.</li><li><b>Absolute</b> <code>$A$1</code> – stays fixed (press <b>F4</b> to add $).</li><li><b>Mixed</b> <code>$A1</code> or <code>A$1</code> – column or row fixed.</li><li>Other sheet: <code>=Sheet2!B5</code>.</li></ul>
<h3>Functions</h3>
<p>A function is a built-in formula: <code>=FUNCTION(argument1, argument2, …)</code>. Arguments can be required, <b>optional</b>, or many (SUM accepts up to 255). Use <b>AutoSum</b> (Alt + =) or <b>Insert Function</b> (Shift + F3 / fx button).</p>
<table class="rte-table"><tbody><tr><th>Category</th><th>Examples</th></tr>
<tr><td>Math &amp; Trig</td><td>SUM, SUMIF, ROUND, INT, ABS, SQRT, PRODUCT, MOD</td></tr>
<tr><td>Statistical</td><td>AVERAGE, COUNT, COUNTA, COUNTBLANK, COUNTIF, MAX, MIN, RANK</td></tr>
<tr><td>Logical</td><td>IF, AND, OR, NOT, IFERROR, TRUE, FALSE – <code>=IF(E2&gt;=40,"Pass","Fail")</code></td></tr>
<tr><td>Text</td><td>LEFT, RIGHT, MID, LEN, UPPER, LOWER, PROPER, TRIM, CONCATENATE, FIND</td></tr>
<tr><td>Date &amp; Time</td><td>TODAY, NOW, DATE, DAY, MONTH, YEAR, WEEKDAY, DATEDIF</td></tr>
<tr><td>Lookup &amp; Reference</td><td>VLOOKUP, HLOOKUP, MATCH, INDEX</td></tr></tbody></table>
<h3>Error values</h3>
<p><code>#DIV/0!</code> divide by zero · <code>#N/A</code> value not available (lookup) · <code>#NAME?</code> wrong function name · <code>#REF!</code> invalid reference · <code>#VALUE!</code> wrong type of value · <code>#NUM!</code> invalid number · <code>####</code> column too narrow.</p>
<h3>Ranges, sorting and filters</h3>
<p>Click a column letter / row number to select it; Ctrl + Space = column, Shift + Space = row; Shift + click selects a range. Sorting and filtering are done from the <b>Data</b> tab (see the previous lesson); several filters can work together.</p>` },

  { part: 2, bm: 18, mt: "Manage Charts", no: "1.18.64-66", title: "Manage Charts", m: 6,
    keys: ["Chart types", "Create a chart", "Chart elements", "Chart Tools", "Sparklines"],
    html: `
<h3>Chart</h3>
<p>A <b>chart</b> shows numbers as a picture so that comparisons and trends are easy to see. It is linked to the data – when the data changes, the chart changes.</p>
<table class="rte-table"><tbody><tr><th>Chart type</th><th>Best for</th></tr>
<tr><td>Column / Bar</td><td>Comparing values (marks of students, sales by month)</td></tr><tr><td>Line</td><td>Trend over time</td></tr><tr><td>Pie / Doughnut</td><td>Parts of a whole (percentage share) – one data series</td></tr><tr><td>Area</td><td>Change in amount over time</td></tr><tr><td>Scatter (XY)</td><td>Relation between two sets of numbers</td></tr><tr><td>Stock, Surface, Radar, Bubble</td><td>Special data</td></tr></tbody></table>
<h3>Create a chart</h3>
<ol><li>Select the data with headings.</li><li><b>Insert → Charts</b> → choose type and sub-type. Shortcuts: <b>Alt + F1</b> (chart on the same sheet), <b>F11</b> (chart on a new chart sheet).</li><li>Move and resize the chart; Design → <b>Move Chart</b> to another sheet.</li></ol>
<h3>Chart elements</h3>
<p><b>Chart title</b>, <b>axis titles</b>, horizontal (category) and vertical (value) <b>axes</b>, <b>legend</b>, <b>data labels</b>, data table, <b>gridlines</b>, plot area, chart area, data series and data points.</p>
<h3>Chart Tools (Excel 2010)</h3>
<ul><li><b>Design</b>: Change Chart Type, Save As Template, <b>Switch Row/Column</b>, <b>Select Data</b>, Chart Layouts, Chart Styles, Move Chart.</li><li><b>Layout</b>: insert titles, legend, data labels, axes, gridlines, trendline.</li><li><b>Format</b>: shape styles, fill, outline, effects, WordArt styles, size.</li></ul>
<h3>Sparklines (new in Excel 2010)</h3>
<p>Tiny charts inside one cell – Insert → Sparklines → <b>Line, Column, Win/Loss</b> – to show the trend of a row of numbers.</p>` },

  { part: 2, bm: 19, mt: "Manage Pivot Tables", no: "1.19.67", title: "Create Pivot Tables", m: 12,
    keys: ["What is a PivotTable", "Create", "Rows, Columns, Values, Filters", "Summarise by", "Group data", "Refresh, PivotChart"],
    html: `
<h3>PivotTable</h3>
<p>A <b>PivotTable</b> quickly <b>summarises</b> a large list – totals, counts or averages – and lets you rearrange ("pivot") the view by dragging fields. The original data is not changed.</p>
<h3>Create a PivotTable</h3>
<ol><li>Click inside the data (with headings, no blank rows/columns).</li><li><b>Insert → PivotTable</b> → check the range → New Worksheet → OK.</li><li>In the <b>PivotTable Field List</b> tick fields or drag them into the areas.</li></ol>
<table class="rte-table"><tbody><tr><th>Area</th><th>What it does</th><th>Example</th></tr>
<tr><td>Row Labels</td><td>Items shown down the left</td><td>Trade / Batch</td></tr><tr><td>Column Labels</td><td>Items across the top</td><td>Month / Gender</td></tr><tr><td>Values (Σ)</td><td>Numbers to summarise</td><td>Sum of Fees, Count of Students</td></tr><tr><td>Report Filter</td><td>Filter the whole report</td><td>Year = 2025</td></tr></tbody></table>
<h3>Working with a PivotTable</h3>
<ul><li><b>Value Field Settings</b> – summarise by Sum, Count, Average, Max, Min; show values as % of total.</li><li><b>Group</b> dates into months / quarters / years or numbers into ranges (right-click → Group).</li><li>Sort and filter row / column labels; <b>Slicers</b> (Excel 2010) give easy filter buttons.</li><li><b>Refresh</b> (Alt + F5) after the source data changes; Change Data Source.</li><li><b>PivotChart</b> – a chart based on the PivotTable.</li></ul>` },

  /* ===================== POWERPOINT ===================== */
  { part: 2, bm: 20, mt: "Power Point Presentations", no: "1.20.68-74", title: "Open files in MS PowerPoint Presentations", m: 7,
    keys: ["Presentation & slide", "Views", "Layouts & themes", "Insert & edit pictures", "Picture effects", "Slide show"],
    html: `
<h3>PowerPoint basics</h3>
<ul><li>A <b>presentation</b> (.pptx) is a set of <b>slides</b> shown one by one. New: Ctrl + N; new slide: <b>Ctrl + M</b>.</li><li><b>Slide layouts</b> (Home → Layout): Title Slide, Title and Content, Two Content, Comparison, Section Header, Title Only, Blank, Picture with Caption.</li><li><b>Themes</b> (Design tab) – colours, fonts and backgrounds for all slides; Background Styles; Slide Size.</li><li><b>Slide Master</b> (View tab) – change the design of all slides at once.</li></ul>
<h3>Views</h3>
<table class="rte-table"><tbody><tr><th>View</th><th>Use</th></tr>
<tr><td>Normal</td><td>Edit slides; thumbnails on the left, notes below</td></tr><tr><td>Slide Sorter</td><td>See all slides – rearrange, delete, duplicate</td></tr><tr><td>Notes Page</td><td>Write speaker notes</td></tr><tr><td>Reading View</td><td>Slide show inside the window</td></tr><tr><td>Slide Show</td><td>Full screen – <b>F5</b> from start, <b>Shift + F5</b> from current slide, Esc to end</td></tr></tbody></table>
<h3>Pictures on slides</h3>
<ul><li>Insert → Picture / Clip Art / Screenshot / Photo Album, or click the picture icon in a content placeholder.</li><li><b>Edit pictures</b> (Picture Tools → Format): <b>Crop</b>, resize, rotate, <b>Remove Background</b>, <b>Corrections</b> – adjust <b>brightness, contrast and sharpness</b>, <b>Color</b> (saturation, tone, recolor), Compress, Reset.</li><li><b>Picture effects</b>: shadow, reflection, glow, soft edges, bevel, 3-D rotation; picture styles and borders.</li><li><b>Artistic effects</b>: pencil sketch, watercolor, blur, glow edges, mosaic and more.</li></ul>
<h3>Saving and showing</h3>
<p>Save as .pptx, .ppsx (opens directly as a slide show), PDF or video; File → Print → Handouts (2, 3 or 6 slides per page).</p>` },

  { part: 2, bm: 21, mt: "Format Presentations", no: "1.21.75-77", title: "Insert, Format text and paragraphs", m: 7,
    keys: ["Placeholders", "Font formatting", "Text alignment", "Line spacing", "Text direction & columns", "Format Painter"],
    html: `
<h3>Adding text</h3>
<ul><li>Click a <b>placeholder</b> ("Click to add title / text") and type; or Insert → <b>Text Box</b>.</li><li>Outline view (View → Outline) lets you type the text of all slides like a list.</li></ul>
<h3>Formatting text</h3>
<p>Select the text → Home → <b>Font</b>: font, size, increase/decrease size (Ctrl + Shift + &gt; / &lt;), bold, italic, underline, shadow, strikethrough, character spacing, change case, font colour, clear formatting. <b>WordArt Styles</b> (Drawing Tools → Format) add fill, outline and text effects.</p>
<h3>Text alignment – four options</h3>
<table class="rte-table"><tbody><tr><th>Alignment</th><th>Shortcut</th><th>Effect</th></tr>
<tr><td>Align Left</td><td>Ctrl + L</td><td>Text starts at the left edge</td></tr><tr><td>Center</td><td>Ctrl + E</td><td>Brings the text to the centre – used for titles</td></tr><tr><td>Align Right</td><td>Ctrl + R</td><td>Text ends at the right edge</td></tr><tr><td>Justify</td><td>Ctrl + J</td><td>Both edges straight</td></tr></tbody></table>
<p>Vertical alignment in the box: Home → <b>Align Text</b> → Top, Middle, Bottom.</p>
<h3>Paragraph formatting</h3>
<p><b>Line spacing</b> (1.0, 1.5, 2.0…) and spacing before/after, indents, bullets and numbering, <b>Text Direction</b> (horizontal, rotate 90°/270°, stacked), <b>Columns</b> in a text box, <b>Convert to SmartArt</b>. <b>Format Painter</b> copies formatting between text.</p>` },

  { part: 2, bm: 22, mt: "Manage Tables and Bulleted Text", no: "1.22.78-80", title: "Create tables, modify tables, modify bulleted text", m: 7,
    keys: ["Bulleted & numbered lists", "List levels", "Insert table", "Table Tools", "Format table"],
    html: `
<h3>Bulleted and numbered lists</h3>
<ul><li>Text placeholders use bullets automatically. Home → <b>Bullets</b> or <b>Numbering</b> to switch them on/off or change the style.</li><li><b>Bullets and Numbering…</b> → change size, colour, use a <b>Picture</b> or <b>Customize</b> symbol; numbering can start at any number.</li><li>Levels: <b>Tab</b> (or Increase List Level) makes a sub-point, <b>Shift + Tab</b> moves it up.</li></ul>
<h3>Creating a table</h3>
<ul><li>Insert → <b>Table</b> → drag the grid, or <b>Insert Table</b> (columns, rows), <b>Draw Table</b>, or <b>Excel Spreadsheet</b>.</li><li>Or click the table icon in a content placeholder. Tab moves to the next cell.</li></ul>
<h3>Modifying and formatting a table</h3>
<ul><li><b>Table Tools → Design</b>: table styles, header row, total row, banded rows, first/last column, <b>shading</b>, <b>borders</b>, <b>effects</b> (cell bevel, shadow, reflection), WordArt styles for text.</li><li><b>Table Tools → Layout</b>: insert / delete rows and columns, <b>merge</b> and <b>split</b> cells, cell size, distribute rows/columns, text alignment and direction, cell margins, table size, arrange.</li><li>Drag the borders to resize columns; drag the frame to move the table.</li></ul>` },

  { part: 2, bm: 23, mt: "Manage Graphic Elements", no: "1.23.81-83", title: "Insert illustrations, Format illustrations and text boxes", m: 7,
    keys: ["Shapes", "SmartArt", "Change layout, colour, style", "Text in SmartArt", "Text boxes", "Charts on slides"],
    html: `
<h3>Illustrations (Insert tab)</h3>
<p><b>Picture</b>, <b>Clip Art</b>, <b>Screenshot</b>, <b>Photo Album</b>, <b>Shapes</b>, <b>SmartArt</b> and <b>Chart</b>.</p>
<h3>Shapes</h3>
<p>Lines, rectangles, basic shapes, block arrows, flowchart, stars and banners, callouts, action buttons. Drawing Tools → Format: <b>Shape Fill</b>, <b>Shape Outline</b>, <b>Shape Effects</b>, Edit Shape, arrange, group, align, size. Type in a selected shape to add text.</p>
<h3>SmartArt</h3>
<p>Diagrams that show information visually. Categories: <b>List, Process, Cycle, Hierarchy, Relationship, Matrix, Pyramid, Picture</b> (and more from Office.com).</p>
<ul><li>Add text in the <b>Text Pane</b> or click [Text] in the shapes; Enter adds a shape, Tab demotes it.</li><li>SmartArt Tools → <b>Design</b>: Add Shape, Promote / Demote, Right to Left, <b>Layouts</b> (change the layout), <b>Change Colors</b>, <b>SmartArt Styles</b>, Reset Graphic, Convert to Shapes / Text.</li><li>SmartArt Tools → <b>Format</b>: format individual shapes – fill, outline, effects, larger/smaller.</li><li>Any bulleted text can be changed with Home → <b>Convert to SmartArt</b>.</li></ul>
<h3>Text boxes</h3>
<p>Insert → <b>Text Box</b> → click or drag on the slide and type. Format with shape styles, fill, outline, WordArt styles, text direction and alignment; rotate with the green handle.</p>
<h3>Charts</h3>
<p>Insert → Chart → choose a type; an Excel sheet opens for the data. Chart Tools Design / Layout / Format work as in Excel.</p>` },

  { part: 2, bm: 24, mt: "Manage Audio & Video Elements", no: "1.24.84&85", title: "Audio & Video Elements", m: 7,
    keys: ["Insert audio", "Record audio", "Insert video", "Playback options", "Trim & fade", "Delete media"],
    html: `
<h3>Adding audio</h3>
<ul><li>Insert → <b>Audio</b> → <b>Audio from File</b> (from your PC – mp3, wav, wma), <b>Clip Art Audio</b>, or <b>Record Audio</b> (microphone – name the sound, press record, stop, OK).</li><li>A speaker icon appears on the slide; click it to play in Normal view.</li><li><b>Delete audio</b>: select the icon and press Delete.</li></ul>
<h3>Adding video</h3>
<p>Insert → <b>Video</b> → Video from File (mp4, wmv, avi), Video from Web Site (embed code), Clip Art Video. Video Tools → Format: poster frame, corrections, video styles, shape, border, effects.</p>
<h3>Playback options (Audio / Video Tools → Playback)</h3>
<table class="rte-table"><tbody><tr><th>Option</th><th>Meaning</th></tr>
<tr><td>Start: <b>On Click</b></td><td>Plays when you click it</td></tr><tr><td>Start: <b>Automatically</b></td><td>Plays automatically once you advance to the slide</td></tr>
<tr><td>Play Across Slides</td><td>Audio continues on the next slides (background music)</td></tr><tr><td>Loop until Stopped</td><td>Repeats continuously</td></tr>
<tr><td>Hide During Show</td><td>Hides the speaker icon in the show</td></tr><tr><td>Rewind after Playing</td><td>Returns to the start</td></tr>
<tr><td>Trim Audio / Video</td><td>Cut the start and end</td></tr><tr><td>Fade In / Fade Out, Volume</td><td>Smooth start / end, loudness</td></tr>
<tr><td>Bookmarks</td><td>Mark points to jump to or trigger animations</td></tr><tr><td>Play Full Screen (video)</td><td>Video fills the screen</td></tr></tbody></table>` },

  { part: 2, bm: 25, mt: "Manage Transitions and Animations", no: "1.25.86&87", title: "Add slide transitions & animations", m: 7,
    keys: ["Transitions", "Transition sound & timing", "Animation types", "Effect options", "Animation Pane", "Timing"],
    html: `
<h3>Slide transitions (Transitions tab)</h3>
<p>A <b>transition</b> is the effect when one slide changes to the next.</p>
<ol><li>Select the slide(s) → <b>Transitions</b> tab → choose an effect (Fade, Push, Wipe, Split, Cube, Flip…).</li><li><b>Effect Options</b> – direction (from left, from top…).</li><li><b>Sound</b> – applause, camera, chime… and <b>Duration</b> (speed in seconds).</li><li><b>Advance Slide</b>: On Mouse Click and/or <b>After</b> 00:05 (automatic).</li><li><b>Apply To All</b> – same transition on all slides. Preview to check.</li></ol>
<h3>Animations (Animations tab)</h3>
<p>An <b>animation</b> is an effect on text or an object <b>inside</b> a slide. Select the text / object → Animations → choose an effect → Effect Options.</p>
<table class="rte-table"><tbody><tr><th>Type</th><th>Colour of icon</th><th>Examples</th></tr>
<tr><td><b>Entrance</b></td><td>Green</td><td>Appear, Fade, Fly In, Zoom</td></tr><tr><td><b>Emphasis</b></td><td>Yellow</td><td>Pulse, Spin, Grow/Shrink, Teeter</td></tr><tr><td><b>Exit</b></td><td>Red</td><td>Disappear, Fly Out, Fade</td></tr><tr><td><b>Motion Paths</b></td><td>–</td><td>Lines, Arcs, Turns, Custom path</td></tr></tbody></table>
<h3>Controlling animations</h3>
<ul><li><b>Add Animation</b> – more than one effect on the same object.</li><li><b>Animation Pane</b> – order of effects, reorder, remove, effect options.</li><li><b>Start</b>: On Click, <b>With Previous</b>, <b>After Previous</b>; <b>Duration</b> and <b>Delay</b>.</li><li><b>Trigger</b> – start an effect when a certain object is clicked; <b>Animation Painter</b> copies an animation.</li></ul>` },

  { part: 2, bm: 26, mt: "Manage Collaboration", no: "1.26.88", title: "Add and manage comments", m: 7,
    keys: ["Add a comment", "Show / hide", "Edit & reply", "Delete", "Compare & share"],
    html: `
<h3>Comments in PowerPoint (Review tab)</h3>
<ul><li><b>Add a comment</b>: select text or an object (or the slide) → Review → <b>New Comment</b> → type → click outside. A small comment marker appears.</li><li><b>Show or hide comments</b>: Review → <b>Show Markup</b> (2010) / Show Comments.</li><li><b>Edit</b>: double-click the marker or Review → Edit Comment.</li><li><b>Reply</b> (newer versions): open the comment → Reply.</li><li><b>Delete</b>: select the marker → Review → Delete → this comment, all markup on the current slide, or all markup in the presentation.</li><li>Previous / Next move between comments.</li></ul>
<h3>Working together</h3>
<ul><li><b>Compare</b> – merge a changed copy into your presentation and accept / reject the changes.</li><li><b>Share</b> – save to OneDrive / SharePoint and invite others; send as attachment or PDF.</li><li>File → Info → <b>Protect Presentation</b>: Mark as Final, Encrypt with Password.</li></ul>` },

  /* ===================== MySQL ===================== */
  { part: 2, bm: 27, mt: "Demonstrate on MySQL", no: "1.27.89-91", title: "Install, Troubleshoot, Create and Use of database in MySQL", m: 8,
    keys: ["DBMS vs RDBMS", "Data models", "DBA", "Schema", "MySQL system tables", "Install & first commands"],
    html: `
<h3>Basic terms</h3>
<ul><li><b>Data</b> – raw facts; <b>Database</b> – an organised collection of related data.</li><li><b>DBMS</b> – software to create, store, update and retrieve data (e.g. MS Access, FoxPro).</li><li><b>RDBMS</b> – DBMS that stores data in related <b>tables</b> (rows = records/tuples, columns = fields/attributes) using keys – based on <b>E. F. Codd's</b> relational model (1970). Examples: <b>MySQL</b>, Oracle, SQL Server, PostgreSQL.</li></ul>
<table class="rte-table"><tbody><tr><th>DBMS</th><th>RDBMS</th></tr>
<tr><td>Data may be stored as files</td><td>Data stored in tables with relations</td></tr><tr><td>Normalization not required</td><td>Supports normalization</td></tr><tr><td>Usually single user, small data</td><td>Many users, large data</td></tr><tr><td>Less security, no strict integrity</td><td>Keys, constraints, high security</td></tr></tbody></table>
<h3>Data models</h3>
<p><b>Hierarchical</b> (tree – parent/child), <b>Network</b> (graph – many-to-many), <b>Relational</b> (tables – most used), <b>Entity-Relationship (ER)</b> model (entities, attributes, relationships – for design), Object-oriented.</p>
<h3>DBA and schema</h3>
<ul><li><b>DBA</b> (Database Administrator) – installs and configures the DBMS, creates users and gives permissions, takes backups and restores, monitors performance and security.</li><li><b>Schema</b> – the structure (design) of the database: tables, columns, types and relations. In MySQL, "schema" and "database" mean the same.</li><li><b>Data dictionary</b> – data about data (metadata). MySQL keeps it in the <code>mysql</code> and <code>information_schema</code> databases – grant tables (users and privileges), time zone, help, log and replication tables.</li></ul>
<h3>Installing MySQL (Windows)</h3>
<ol><li>Download the <b>MySQL Installer</b> (Community edition) from mysql.com → Developer Default / Server only.</li><li>Set the <b>root password</b>, keep port <b>3306</b>, run as a Windows service.</li><li>Use the <b>MySQL Command Line Client</b> or <b>MySQL Workbench</b> (GUI).</li></ol>
<h3>Troubleshooting</h3>
<p>Service not running → Services → start MySQL; "Access denied" → wrong user/password; port 3306 used by another program; firewall blocking; check the error log.</p>
<h3>First commands</h3>
<pre>mysql -u root -p          -- log in
SHOW DATABASES;
CREATE DATABASE copa;
USE copa;
SHOW TABLES;
DROP DATABASE test;</pre>
<p>Every SQL statement ends with a <b>semicolon ;</b>. SQL keywords are not case-sensitive.</p>` },

  { part: 2, bm: 27, mt: "Demonstrate on MySQL", no: "1.27.92-96", title: "Designing database using normalization rules, various datatypes, data integrity, DDL, DML & DCL Statements, Enforcing Primary key and Foreign key", m: 8,
    keys: ["Normalization 1NF–3NF", "Data types", "Data integrity", "DDL / DML / DCL / TCL", "Primary key", "Foreign key"],
    html: `
<h3>Normalization</h3>
<p><b>Normalization</b> organises tables to remove <b>redundancy</b> (repeated data) and problems in insert, update and delete. Proposed by E. F. Codd.</p>
<table class="rte-table"><tbody><tr><th>Normal form</th><th>Rule</th></tr>
<tr><td><b>1NF</b></td><td>Each cell holds a single (atomic) value; no repeating groups; each row unique</td></tr>
<tr><td><b>2NF</b></td><td>In 1NF and every non-key column depends on the <b>whole</b> primary key (no partial dependency)</td></tr>
<tr><td><b>3NF</b></td><td>In 2NF and no non-key column depends on another non-key column (no transitive dependency)</td></tr>
<tr><td>BCNF</td><td>Stronger 3NF – every determinant is a candidate key</td></tr></tbody></table>
<h3>MySQL data types</h3>
<table class="rte-table"><tbody><tr><th>Group</th><th>Types</th></tr>
<tr><td>Numeric</td><td>TINYINT, SMALLINT, <b>INT</b>, BIGINT, <b>DECIMAL(p,s)</b>, FLOAT, DOUBLE</td></tr>
<tr><td>String</td><td><b>CHAR(n)</b> fixed length, <b>VARCHAR(n)</b> variable length, TEXT, ENUM('M','F'), BLOB (binary)</td></tr>
<tr><td>Date / time</td><td><b>DATE</b> (YYYY-MM-DD), TIME, <b>DATETIME</b>, TIMESTAMP, YEAR</td></tr>
<tr><td>Boolean</td><td>BOOLEAN (stored as TINYINT 0/1)</td></tr></tbody></table>
<h3>Data integrity and constraints</h3>
<p><b>Entity integrity</b> (primary key unique and not null), <b>referential integrity</b> (foreign key must match a primary key), <b>domain integrity</b> (valid type and range). Constraints: <b>NOT NULL, UNIQUE, PRIMARY KEY, FOREIGN KEY, CHECK, DEFAULT, AUTO_INCREMENT</b>.</p>
<h3>Types of SQL commands</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Commands</th></tr>
<tr><td><b>DDL</b> – Data Definition</td><td>CREATE, ALTER, DROP, TRUNCATE, RENAME</td></tr>
<tr><td><b>DML</b> – Data Manipulation</td><td>SELECT, INSERT, UPDATE, DELETE</td></tr>
<tr><td><b>DCL</b> – Data Control</td><td><b>GRANT</b> (gives privileges), <b>REVOKE</b> (takes them back)</td></tr>
<tr><td><b>TCL</b> – Transaction Control</td><td>COMMIT, ROLLBACK, SAVEPOINT</td></tr></tbody></table>
<h3>Primary key and foreign key</h3>
<ul><li><b>Primary key</b> – uniquely identifies each row; cannot be NULL or repeated; one per table (can be on several columns = composite key).</li><li><b>Foreign key</b> – a column that refers to the primary key of another table, linking the tables.</li></ul>
<pre>CREATE TABLE trade (
  trade_id INT PRIMARY KEY,
  name     VARCHAR(40) NOT NULL
);
CREATE TABLE student (
  roll     INT PRIMARY KEY AUTO_INCREMENT,
  name     VARCHAR(50) NOT NULL,
  dob      DATE,
  trade_id INT,
  FOREIGN KEY (trade_id) REFERENCES trade(trade_id)
);
ALTER TABLE student ADD mobile CHAR(10);
GRANT SELECT, INSERT ON copa.* TO 'teacher'@'localhost';
REVOKE INSERT ON copa.* FROM 'teacher'@'localhost';</pre>` },

  { part: 2, bm: 28, mt: "Demonstrate on Queries", no: "1.28.97&98", title: "Insert and delete queries, Update queries", m: 8,
    keys: ["INSERT", "UPDATE", "DELETE", "SELECT with WHERE", "Transactions & ACID", "Indexes"],
    html: `
<h3>INSERT – add rows</h3>
<pre>INSERT INTO student (name, dob, trade_id) VALUES ('Priya Patil', '2007-05-14', 1);
INSERT INTO student (name, trade_id) VALUES ('Rahul', 1), ('Sneha', 1);   -- many rows</pre>
<h3>UPDATE – change rows</h3>
<pre>UPDATE student SET mobile = '9876543210' WHERE roll = 5;
UPDATE result  SET marks = marks + 5  WHERE subject = 'Python';</pre>
<p><b>Always use WHERE</b> – without it every row is changed.</p>
<h3>DELETE – remove rows</h3>
<pre>DELETE FROM student WHERE roll = 7;
DELETE FROM student;          -- removes all rows (table stays)
TRUNCATE TABLE student;       -- DDL: removes all rows quickly, resets AUTO_INCREMENT</pre>
<h3>SELECT – read data</h3>
<pre>SELECT * FROM student;
SELECT name, dob FROM student WHERE trade_id = 1 ORDER BY name;
SELECT * FROM result WHERE marks BETWEEN 40 AND 60 AND name LIKE 'P%';</pre>
<p>Operators: <code>= &lt;&gt; &gt; &lt; &gt;= &lt;=</code>, <code>AND OR NOT</code>, <code>BETWEEN</code>, <code>IN (…)</code>, <code>LIKE</code> (<code>%</code> any characters, <code>_</code> one character), <code>IS NULL</code>, <code>LIMIT</code>.</p>
<h3>Transactions</h3>
<p>A <b>transaction</b> is a group of statements that must all succeed or all fail (e.g. money transfer: debit + credit).</p>
<table class="rte-table"><tbody><tr><th>ACID property</th><th>Meaning</th></tr>
<tr><td><b>A</b>tomicity</td><td>All or nothing</td></tr><tr><td><b>C</b>onsistency</td><td>Database stays valid before and after</td></tr><tr><td><b>I</b>solation</td><td>Transactions work independently of each other</td></tr><tr><td><b>D</b>urability</td><td>Committed changes are permanent even after a crash</td></tr></tbody></table>
<pre>START TRANSACTION;
UPDATE account SET bal = bal - 500 WHERE id = 1;
UPDATE account SET bal = bal + 500 WHERE id = 2;
COMMIT;        -- or ROLLBACK; to cancel</pre>
<h3>Indexes</h3>
<p>An <b>index</b> makes searching faster (like a book index): <code>CREATE INDEX idx_name ON student(name);</code> · <code>SHOW INDEX FROM student;</code> · <code>DROP INDEX idx_name ON student;</code> Primary and unique keys are indexed automatically.</p>` },

  { part: 2, bm: 29, mt: "Demonstrate on Functions", no: "1.29.99&100", title: "Using the Number, Date and Character functions, group by having, sub query", m: 8,
    keys: ["Number functions", "Date functions", "String functions", "Aggregate functions", "GROUP BY & HAVING", "Joins & sub query"],
    html: `
<h3>Number functions</h3>
<p><code>ROUND(15.678, 1)</code> → 15.7 · <code>CEIL(4.2)</code> → 5 · <code>FLOOR(4.8)</code> → 4 · <code>TRUNCATE(4.876, 2)</code> → 4.87 · <code>ABS(-7)</code> → 7 · <code>MOD(10, 3)</code> → 1 · <code>POW(2, 3)</code> → 8 · <code>SQRT(25)</code> → 5.</p>
<h3>Date functions</h3>
<p><code>NOW()</code> date + time · <code>CURDATE()</code> · <code>CURTIME()</code> · <code>YEAR(dob)</code>, <code>MONTH()</code>, <code>DAY()</code>, <code>DAYNAME()</code> · <code>DATEDIFF('2026-12-31', CURDATE())</code> days between · <code>DATE_ADD(CURDATE(), INTERVAL 30 DAY)</code> · <code>DATE_FORMAT(dob, '%d-%m-%Y')</code>.</p>
<h3>Character (string) functions</h3>
<p><code>CONCAT(first, ' ', last)</code> · <code>LENGTH('COPA')</code> → 4 · <code>UPPER()</code> / <code>LOWER()</code> · <code>SUBSTRING('COMPUTER', 1, 4)</code> → COMP · <code>LEFT()</code> / <code>RIGHT()</code> · <code>TRIM()</code> · <code>REPLACE(s, 'a', 'b')</code> · <code>INSTR('COPA', 'P')</code> → 3.</p>
<h3>Aggregate functions</h3>
<p><code>COUNT(*)</code> number of rows · <code>SUM(marks)</code> · <code>AVG(marks)</code> average value · <code>MAX(marks)</code> · <code>MIN(marks)</code>.</p>
<h3>GROUP BY and HAVING</h3>
<pre>SELECT trade_id, COUNT(*) AS students, AVG(marks) AS average
FROM result
WHERE year = 2026            -- filters rows before grouping
GROUP BY trade_id
HAVING AVG(marks) &gt;= 60      -- filters groups after grouping
ORDER BY average DESC;</pre>
<p><b>WHERE</b> works on rows and cannot use aggregate functions; <b>HAVING</b> works on groups.</p>
<h3>Joining tables</h3>
<table class="rte-table"><tbody><tr><th>Join</th><th>Returns</th></tr>
<tr><td><b>INNER JOIN</b></td><td>Rows that match in both tables</td></tr><tr><td><b>LEFT JOIN</b></td><td>All rows of the left table + matching rows of the right</td></tr><tr><td><b>RIGHT JOIN</b></td><td>All rows of the right table + matching rows of the left</td></tr><tr><td><b>CROSS JOIN</b></td><td>Every combination of rows</td></tr></tbody></table>
<pre>SELECT Orders.OrderID, Customers.CustomerName, Orders.OrderDate
FROM Orders INNER JOIN Customers ON Orders.CustomerID = Customers.CustomerID;</pre>
<p>MySQL has no FULL OUTER JOIN – combine a LEFT and a RIGHT join with <code>UNION</code>.</p>
<h3>Sub query</h3>
<p>A <b>sub query</b> is a query inside another query, written in brackets:</p>
<pre>SELECT name, marks FROM result
WHERE marks &gt; (SELECT AVG(marks) FROM result);   -- students above average</pre>` },

  /* ===================== NETWORK ===================== */
  { part: 2, bm: 30, mt: "Set-up & Configure a Computer Network", no: "1.30.101-112", title: "Connect a computer to a network and share Devices i.e. Printers, files, folders and drives", m: 9,
    keys: ["Communication basics", "End & intermediary devices", "PAN, LAN, MAN, WAN", "Ethernet & Wi-Fi", "OSI & TCP/IP", "IP address", "Sharing files & printers"],
    html: `
<h3>Principles of communication</h3>
<p>Every communication needs a <b>sender</b> (source), a <b>receiver</b> (destination), a <b>message</b>, a <b>medium</b> (channel) and agreed rules called <b>protocols</b>. A <b>computer network</b> is two or more devices connected to share data, internet, printers and other resources.</p>
<h3>Devices on a network</h3>
<table class="rte-table"><tbody><tr><th>End devices (hosts)</th><th>Intermediary devices</th></tr>
<tr><td>Computers, laptops, printers, scanners, servers (file, web), mobile phones, IP phones, smart TVs – they create or receive messages</td><td><b>Switch</b> (connects devices in a LAN, sends data to the right port), <b>router</b> (connects different networks / internet, chooses the path), hub (old – sends to all), <b>access point</b> (Wi-Fi), modem, firewall – they forward data, keep track of paths and report errors</td></tr></tbody></table>
<h3>Types of networks by size</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Area</th><th>Example</th></tr>
<tr><td><b>PAN</b> – Personal Area Network</td><td>About 10 m around a person</td><td>Phone + Bluetooth headset</td></tr><tr><td><b>LAN</b> – Local Area Network</td><td>Room, building, campus</td><td>ITI computer lab</td></tr>
<tr><td><b>MAN</b> – Metropolitan Area Network</td><td>A city</td><td>Cable TV / city network</td></tr><tr><td><b>WAN</b> – Wide Area Network</td><td>Country / world</td><td>The <b>Internet</b></td></tr></tbody></table>
<p>Topologies (layout): bus, <b>star</b> (most LANs – all to a switch), ring, mesh, tree, hybrid.</p>
<h3>Ethernet and Wi-Fi</h3>
<ul><li><b>Ethernet</b> (IEEE 802.3) – wired LAN using twisted-pair cable with RJ-45 connectors to a switch; 100 Mbps / 1 Gbps or more; handles bandwidth-heavy work well.</li><li><b>Wi-Fi</b> (IEEE 802.11) – wireless LAN through an access point / wireless router.</li><li><b>Home network</b> – modem/router from the ISP + Wi-Fi connecting phones, PCs, TVs and printers.</li></ul>
<h3>OSI and TCP/IP models</h3>
<table class="rte-table"><tbody><tr><th>OSI (7 layers)</th><th>TCP/IP (4 layers)</th></tr>
<tr><td>7 Application · 6 Presentation · 5 Session</td><td>Application (HTTP, FTP, SMTP, DNS)</td></tr><tr><td>4 Transport</td><td>Transport (TCP, UDP)</td></tr><tr><td>3 Network</td><td>Internet (IP)</td></tr><tr><td>2 Data Link · 1 Physical</td><td>Network Access (Ethernet, Wi-Fi)</td></tr></tbody></table>
<p><b>TCP/IP</b> is named after its two main protocols. Data is broken into <b>packets</b> (an IP packet is also called a <b>datagram</b>). <b>Client</b> = asks for a service, <b>server</b> = provides it.</p>
<h3>Addresses</h3>
<ul><li><b>IP address</b> (IPv4) – 32-bit number written as four parts, e.g. <code>192.168.1.10</code>; with a <b>subnet mask</b> (255.255.255.0), <b>default gateway</b> (router) and <b>DNS</b> server. <b>IPv6</b> uses 128 bits.</li><li>Given automatically by <b>DHCP</b> or set manually (static). Private ranges: 10.x.x.x, 172.16–31.x.x, 192.168.x.x.</li><li><b>MAC address</b> – 48-bit physical address of the network card.</li><li>Check: <code>ipconfig</code> / <code>ipconfig /all</code>, test: <code>ping 192.168.1.1</code>.</li></ul>
<h3>Sharing files, folders, drives and printers (Windows)</h3>
<ol><li>Control Panel → Network and Sharing Center → <b>Change advanced sharing settings</b> → turn on <b>Network discovery</b> and <b>File and printer sharing</b> (private network).</li><li><b>Share a folder/drive</b>: right-click → Properties → <b>Sharing</b> → Share… (choose users) or <b>Advanced Sharing</b> → Share this folder → <b>Permissions</b> (Read / Change / Full Control).</li><li><b>Open a share</b>: File Explorer → Network, or type <code>\\\\PCNAME\\ShareName</code> in the address bar / Run box.</li><li><b>Map network drive</b>: This PC → Map network drive → letter Z: → <code>\\\\PCNAME\\ShareName</code>.</li><li><b>Share a printer</b>: Devices and Printers → right-click printer → Printer properties → <b>Sharing</b> → Share this printer; on other PCs: Add a printer → network printer.</li></ol>` },

  /* ===================== WEB DESIGN ===================== */
  { part: 2, bm: 31, mt: "Create Simple Static Web Pages using HTML Tags", no: "1.31.113", title: "Web designing", m: 10,
    keys: ["Website types", "Static vs dynamic", "Browser & server", "Markup languages & DHTML", "HTML structure", "Links, images, tables, lists, forms", "CSS"],
    html: `
<h3>Websites and web pages</h3>
<p>A <b>web page</b> is a document on the World Wide Web viewed in a browser; a <b>website</b> is a group of related web pages under one domain (e.g. copamcq.com). The first page is the <b>home page</b>.</p>
<p><b>Types of websites:</b> personal, commercial / e-commerce, <b>e-government</b>, educational, news, blog, community (social), archive site (keeps old electronic content), portal, non-profit.</p>
<h3>Static and dynamic websites</h3>
<table class="rte-table"><tbody><tr><th>Static</th><th>Dynamic</th></tr>
<tr><td>Same content for every visitor; plain HTML/CSS files</td><td>Content created when requested – server-side scripts (PHP, ASP.NET, Node.js) + database</td></tr>
<tr><td>Easy and quick to develop, cheap hosting, fast</td><td>Interactive (login, search, shopping), easy to update through a CMS</td></tr>
<tr><td>Changes need editing the code; not interactive</td><td>More complex, needs a server and database</td></tr></tbody></table>
<h3>Browser, server and URL</h3>
<ul><li><b>Web browser</b> – Chrome, Firefox, Edge, Safari, Opera (old: Internet Explorer). <b>Web server</b> – Apache, IIS, Nginx.</li><li><b>URL</b> – <code>https://www.copamcq.com/study.html</code> = protocol + domain + path. <b>HTTP</b> transfers web pages (HTTPS = secure).</li><li>Software can be downloaded from the internet (e.g. MySQL Community, editors) – only from official sites.</li></ul>
<h3>Markup languages</h3>
<p><b>HTML</b> (structure of pages), <b>XML</b> (stores and transports data with your own tags), <b>XHTML</b> (stricter XML-style HTML) and <b>DHTML</b> (Dynamic HTML) = <b>HTML + CSS + JavaScript + DOM</b> (Document Object Model). DHTML features: real-time positioning, dynamic fonts and styles, changing tags and properties, data binding.</p>
<h3>HTML page structure</h3>
<pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;&lt;title&gt;My Page&lt;/title&gt;&lt;/head&gt;
&lt;body&gt;
  &lt;h1&gt;Welcome to COPA&lt;/h1&gt;
  &lt;p&gt;This is a &lt;b&gt;paragraph&lt;/b&gt;.&lt;/p&gt;
&lt;/body&gt;
&lt;/html&gt;</pre>
<p>Write it in <b>Notepad</b> (Start → Notepad), save as <code>index.html</code> (Save as type: All Files) and open it in a browser. Tags are in <code>&lt; &gt;</code>; most have a closing tag; <b>empty tags</b> (<code>&lt;br&gt;</code>, <code>&lt;hr&gt;</code>, <code>&lt;img&gt;</code>) do not.</p>
<h3>Common tags</h3>
<table class="rte-table"><tbody><tr><th>Purpose</th><th>Tags / attributes</th></tr>
<tr><td>Text</td><td><code>&lt;h1&gt;…&lt;h6&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;br&gt;</code>, <code>&lt;hr&gt;</code>, <code>&lt;b&gt; &lt;i&gt; &lt;u&gt;</code>, <code>&lt;sup&gt; &lt;sub&gt;</code>, <code>&lt;pre&gt;</code></td></tr>
<tr><td>Links</td><td><code>&lt;a href="page.html" target="_blank"&gt;</code> (_blank opens a new tab); mailto: link</td></tr>
<tr><td>Images</td><td><code>&lt;img src="photo.jpg" alt="text" width="200" height="150"&gt;</code></td></tr>
<tr><td>Lists</td><td><code>&lt;ul&gt;</code> (bullets), <code>&lt;ol type="1/A/a/I"&gt;</code> (numbers), <code>&lt;li&gt;</code>; <code>&lt;dl&gt; &lt;dt&gt; &lt;dd&gt;</code></td></tr>
<tr><td>Tables</td><td><code>&lt;table border="1"&gt;</code>, <code>&lt;tr&gt;</code> row, <code>&lt;th&gt;</code> header cell, <code>&lt;td&gt;</code> data cell, <code>colspan</code>, <code>rowspan</code>, <code>&lt;caption&gt;</code></td></tr>
<tr><td>Forms</td><td><code>&lt;form&gt;</code>, <code>&lt;input type="text/password/radio/checkbox/submit"&gt;</code>, <code>&lt;textarea&gt;</code>, <code>&lt;select&gt;&lt;option&gt;</code>, <code>&lt;button&gt;</code></td></tr>
<tr><td>Media / other</td><td><code>&lt;audio&gt;</code>, <code>&lt;video&gt;</code>, <code>&lt;iframe&gt;</code>, <code>&lt;div&gt;</code>, <code>&lt;span&gt;</code>, comments <code>&lt;!-- --&gt;</code></td></tr></tbody></table>
<h3>CSS – Cascading Style Sheets</h3>
<p>CSS controls the look: colours, fonts, spacing, layout. Syntax: <code>selector { property: value; }</code> e.g. <code>h1 { color: navy; text-align: center; }</code>. Ways to add: <b>inline</b> (<code>style="…"</code>), <b>internal</b> (<code>&lt;style&gt;</code> in head), <b>external</b> (<code>&lt;link rel="stylesheet" href="style.css"&gt;</code> – best for many pages).</p>
<p>Practise these on the <a href="live-practice.html">Live Practice</a> page.</p>` },

  { part: 2, bm: 31, mt: "Create Simple Static Web Pages using HTML Tags", no: "1.31.114", title: "Introduction to CMS and web authoring tools", m: 10,
    keys: ["WYSIWYG", "Web authoring tools", "CMS", "Popular CMS", "KompoZer toolbars"],
    html: `
<h3>WYSIWYG web authoring tools</h3>
<p><b>WYSIWYG</b> = "What You See Is What You Get". These programs let you design a web page visually (like MS Word) while they write the HTML code for you.</p>
<p>Examples: <b>Adobe Dreamweaver</b>, <b>KompoZer</b> / BlueGriffon (free), MS FrontPage / Expression Web (old), online builders like Wix, Google Sites.</p>
<h3>CMS – Content Management System</h3>
<ul><li>A <b>CMS</b> is web software that lets people create, edit and publish website content through a browser <b>without writing code</b>; content is stored in a database and shown with themes.</li><li>Features: user roles and login, editor for pages and posts, media library, themes / templates, plugins / extensions, menus, SEO tools.</li><li>Popular CMS: <b>WordPress</b> (most used), <b>Joomla</b>, <b>Drupal</b>, <b>Blogger</b> (Google's blogging tool), Wix, Shopify / Magento (online shops).</li></ul>
<h3>KompoZer – a free WYSIWYG editor</h3>
<table class="rte-table"><tbody><tr><th>Toolbar</th><th>Use</th></tr>
<tr><td><b>Composition toolbar</b></td><td>New, Open, Save, Publish, Browse, Anchor, Link, Image, Table, Form, Spell</td></tr>
<tr><td><b>Format toolbar</b></td><td>Paragraph style, font, colour, bold/italic, lists, indent, alignment</td></tr>
<tr><td><b>Tab browser toolbar</b></td><td>Tabs for several open pages</td></tr>
<tr><td><b>Edit mode toolbar</b></td><td>Design (normal), Split, Source (HTML code) and Preview views</td></tr>
<tr><td><b>Status toolbar</b></td><td>Shows the tag path of the selected element (e.g. &lt;body&gt; &lt;p&gt; &lt;b&gt;)</td></tr></tbody></table>` },

  /* ===================== JAVASCRIPT ===================== */
  { part: 2, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.115", title: "Understanding JavaScript", m: 11,
    keys: ["Programming vs scripting", "Client & server side", "JavaScript history", "Features", "Advantages & disadvantages", "ECMAScript versions"],
    html: `
<h3>Programming and scripting languages</h3>
<ul><li><b>Programming</b> – writing instructions for a computer. Programs in C, C++, Java are <b>compiled</b> before running.</li><li><b>Scripting languages</b> (JavaScript, Python, PHP) are usually <b>interpreted</b> line by line and used to automate tasks or control other programs / web pages.</li><li><b>Client-side</b> scripts run in the user's browser (JavaScript); <b>server-side</b> scripts run on the web server (PHP, ASP.NET, Node.js).</li><li>Advantages of scripts: easy to learn, quick to change, no compiling, make pages interactive. Disadvantages: slower than compiled code, code is visible to users, browser differences.</li></ul>
<h3>JavaScript history</h3>
<p>Created by <b>Brendan Eich</b> at <b>Netscape</b> in <b>1995</b> (first called Mocha, then LiveScript). It is not related to Java. Standardised by <b>ECMA</b> as <b>ECMAScript</b>.</p>
<table class="rte-table"><tbody><tr><th>Edition</th><th>Year</th><th>Main additions</th></tr>
<tr><td>ES1, ES2, ES3</td><td>1997, 1998, 1999</td><td>First standard; regular expressions, try/catch (ES3)</td></tr><tr><td>ES4</td><td>–</td><td>Abandoned</td></tr>
<tr><td>ES5 / ES5.1</td><td>2009 / 2011</td><td>"use strict", JSON, array methods (forEach, map, filter)</td></tr>
<tr><td><b>ES6 = ES2015</b></td><td>2015</td><td><b>let, const</b>, arrow functions, classes, template strings, modules, promises</td></tr>
<tr><td>ES2016, ES2017</td><td>2016, 2017</td><td><code>**</code> power, <code>includes()</code>; <b>async / await</b>, Object.values</td></tr></tbody></table>
<h3>Features of JavaScript</h3>
<p>Scripting language · client-side technology · interpreter based · <b>case sensitive</b> (<code>Name</code> ≠ <code>name</code>) · event handling (click, load, submit) · validating user input before sending a form · control statements and loops · detects the browser and OS · dynamic typing · lightweight.</p>
<h3>Advantages and disadvantages</h3>
<table class="rte-table"><tbody><tr><th>Advantages</th><th>Disadvantages</th></tr>
<tr><td>Fast (runs in the browser), less server load, rich interfaces, easy to learn, works in all browsers, also used on servers (Node.js) and mobile apps</td><td>Security – code can be seen and misused; can be switched off in the browser; results may differ between browsers; no true multithreading in normal scripts</td></tr></tbody></table>
<h3>Tools needed and a sample program</h3>
<p>Only a <b>text editor</b> (Notepad, VS Code) and a <b>web browser</b>. JavaScript is written inside <code>&lt;script&gt;</code> tags or in an external <code>.js</code> file.</p>
<pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;&lt;body&gt;
&lt;h2&gt;My first JavaScript&lt;/h2&gt;
&lt;p id="demo"&gt;&lt;/p&gt;
&lt;script&gt;
  document.getElementById("demo").innerHTML = "Hello COPA!";
  document.write("Today is " + new Date().toDateString());
  console.log("Shown in the browser console (F12)");
&lt;/script&gt;
&lt;/body&gt;&lt;/html&gt;</pre>
<p>External file: <code>&lt;script src="script.js"&gt;&lt;/script&gt;</code>.</p>` },

  { part: 2, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.116&117", title: "Using JavaScript Variable and data types", m: 11,
    keys: ["Variables (var, let, const)", "Identifier rules", "Data types", "typeof", "undefined vs null", "Operators"],
    html: `
<h3>Variables</h3>
<p>A <b>variable</b> is a named container for a value.</p>
<pre>var city = "Pune";      // old way – function scope, can be re-declared
let marks = 75;         // block scope, can change
const PI = 3.14;        // block scope, cannot be re-assigned
let x;                  // declared without value → undefined</pre>
<h3>Rules for identifiers (names)</h3>
<ul><li>Can contain letters, digits, underscore <code>_</code> and dollar <code>$</code>.</li><li>Must begin with a letter, <code>_</code> or <code>$</code> – not a digit.</li><li>Case sensitive (<code>total</code> and <code>Total</code> are different).</li><li>Reserved words (<code>if, for, let, class, function</code>…) cannot be used. Style: camelCase – <code>studentName</code>.</li></ul>
<h3>Data types</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Example</th></tr>
<tr><td>String</td><td><code>"COPA"</code>, <code>'ITI'</code>, <code>\`Hi \${name}\`</code></td></tr><tr><td>Number</td><td><code>25</code>, <code>3.14</code> (one type for integers and decimals)</td></tr><tr><td>Boolean</td><td><code>true</code>, <code>false</code></td></tr><tr><td>Undefined</td><td>declared but no value</td></tr><tr><td>Null</td><td>intentionally empty value</td></tr><tr><td>BigInt, Symbol</td><td><code>123n</code>, unique keys</td></tr><tr><td>Object (complex)</td><td><code>{name: "Asha", age: 18}</code>, arrays <code>["a", "b"]</code>, functions</td></tr></tbody></table>
<ul><li>JavaScript is <b>dynamically typed</b> – the same variable can hold a number and later a string.</li><li><code>typeof</code> tells the type: <code>typeof 5</code> → "number", <code>typeof "a"</code> → "string", <code>typeof [1,2]</code> → "object", <code>typeof null</code> → "object".</li><li><b>Number + string</b> joins them: <code>"10" + " Apple"</code> → "10 Apple"; <code>5 + 20 + "Apple"</code> → "25Apple" (left to right); <code>"Apple" + 5 + 20</code> → "Apple520".</li><li><b>undefined vs null</b>: <code>undefined</code> = no value assigned yet; <code>null</code> = empty on purpose. <code>null == undefined</code> is true but <code>null === undefined</code> is false.</li><li>A <b>const</b> object or array cannot be re-assigned, but its contents <b>can change</b>: <code>const a = [1]; a.push(2);</code> is allowed.</li></ul>
<h3>Operators</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Operators</th></tr>
<tr><td>Arithmetic</td><td><code>+ - * / % ** ++ --</code></td></tr><tr><td>Assignment</td><td><code>= += -= *= /= %=</code></td></tr>
<tr><td>Comparison</td><td><code>== != === !== &gt; &lt; &gt;= &lt;=</code> (<code>===</code> checks value <b>and type</b>: <code>5 == "5"</code> true, <code>5 === "5"</code> false)</td></tr>
<tr><td>Logical</td><td><code>&amp;&amp;</code> AND, <code>||</code> OR, <code>!</code> NOT</td></tr><tr><td>Bitwise (32-bit)</td><td><code>&amp; | ^ ~ &lt;&lt; &gt;&gt; &gt;&gt;&gt;</code></td></tr>
<tr><td>String / ternary / type</td><td><code>+</code> concatenation, <code>cond ? a : b</code>, <code>typeof</code>, <code>instanceof</code></td></tr></tbody></table>` },

  { part: 2, bm: 32, mt: "JavaScript Embed JavaScript in HTML Pages", no: "1.32.118", title: "Control statements, Loops and Popup boxes in JavaScript", m: 11,
    keys: ["if / else if / else", "switch, break, default", "for loop", "while & do…while", "break & continue", "alert, confirm, prompt"],
    html: `
<h3>Conditional statements</h3>
<pre>let hour = new Date().getHours(), greeting;
if (hour &lt; 12) {
  greeting = "Good morning";
} else if (hour &lt; 18) {
  greeting = "Good day";
} else {
  greeting = "Good evening";
}</pre>
<h3>switch statement</h3>
<pre>let day;
switch (new Date().getDay()) {
  case 0: day = "Sunday"; break;
  case 2: day = "Tuesday"; break;
  case 6: day = "Saturday"; break;
  default: day = "Looking forward to the Weekend";
}</pre>
<ul><li><b>break</b> stops the switch; without it the next case also runs (<b>fall-through</b>) – useful when several cases share the same code.</li><li><b>default</b> runs when no case matches. switch uses strict comparison (===).</li></ul>
<h3>Loops</h3>
<p>Loops repeat code. A <code>for</code> loop has three parts: <b>initialisation</b>, <b>condition</b> and <b>increment/decrement</b> (each is optional).</p>
<pre>for (let i = 1; i &lt;= 5; i++) {
  text += "The number is " + i + "&lt;br&gt;";
}
let n = 1;
while (n &lt;= 5) { console.log(n); n++; }       // checks first
do { console.log(n); n--; } while (n &gt; 0);    // runs at least once
for (const s of ["HTML", "CSS", "JS"]) console.log(s);   // for…of – values
for (const k in {a: 1, b: 2}) console.log(k);           // for…in – keys</pre>
<p><b>break</b> leaves the loop; <b>continue</b> skips to the next round.</p>
<h3>Popup boxes</h3>
<table class="rte-table"><tbody><tr><th>Box</th><th>Use</th><th>Returns</th></tr>
<tr><td><code>alert("Saved!")</code></td><td>Shows a message with OK</td><td>nothing</td></tr>
<tr><td><code>confirm("Delete?")</code></td><td>OK / Cancel question</td><td><code>true</code> or <code>false</code></td></tr>
<tr><td><code>prompt("Your name?", "")</code></td><td>Asks the user to type a value</td><td>the text, or <code>null</code> if Cancel</td></tr></tbody></table>
<pre>let name = prompt("Enter your name:");
if (name &amp;&amp; confirm("Show greeting?")) alert("Hello " + name + "!");</pre>
<p>Try it in <a href="live-practice.html?t=w8">Live Practice → JS – Alert, Prompt &amp; Confirm</a>.</p>` }
]);
