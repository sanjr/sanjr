/* COPA MCQ – Theory Notes, PART 4 (Elective modules 38–42: Python and Java). Study notes written for
   COPA MCQ following the COPA Trade Theory (NSQF 2022) lesson plan – not a copy of the book.
   g = elective group (1 = Python, 2 = Java) so both tracks stay in their own order. */
window.COPA_THEORY = (window.COPA_THEORY || []).concat([
  /* ===================== ELECTIVE MODULE I : PROGRAMMING IN PYTHON ===================== */
  { part: 4, g: 1, el: "Elective I – Python", bm: 38, mt: "Programming in Python", no: "1.38.01&02", title: "Programming language (Python) – Use Python from command line", m: 15,
    keys: ["What is Python", "Features", "Applications", "Installation", "Interactive & script mode", "Comments", "Variables"],
    html: `
<h3>Python</h3>
<p><b>Python</b> is a general-purpose, high-level, <b>interpreted</b>, object-oriented programming language created by <b>Guido van Rossum</b> (released in <b>1991</b>). The name comes from the comedy show "Monty Python's Flying Circus". Current major version: <b>Python 3</b>.</p>
<h3>Features</h3>
<ul><li><b>Easy to learn and use</b> – simple English-like syntax, uses <b>indentation</b> instead of braces.</li><li><b>Interpreted</b> – runs line by line; errors are shown immediately.</li><li><b>Cross-platform</b> – same code runs on Windows, Linux, macOS.</li><li><b>Free and open source</b> (PSF licence); huge standard library ("batteries included").</li><li><b>Object-oriented</b> – classes and objects; also supports procedural style.</li><li>Dynamically typed, extensible, embeddable, GUI support (Tkinter), integrated with C/C++ and Java.</li></ul>
<h3>Applications</h3>
<p>Web development (Django, Flask), data science and analysis (Pandas, NumPy), machine learning and AI (TensorFlow, scikit-learn), automation and scripting, desktop GUI apps, games (Pygame), networking, cyber security, education and IoT (Raspberry Pi).</p>
<h3>Installing Python</h3>
<ol><li>Download from <b>python.org</b> → Downloads → Windows installer.</li><li>Run it and tick <b>"Add Python to PATH"</b> → Install Now.</li><li>Check in the command prompt: <code>python --version</code>. Linux usually has it: <code>python3 --version</code>.</li><li>IDLE (comes with Python), VS Code, PyCharm or Jupyter can be used to write programs.</li></ol>
<h3>Using Python from the command line</h3>
<ul><li><b>Interactive mode</b>: type <code>python</code> → the <code>&gt;&gt;&gt;</code> prompt appears → type a statement and press Enter. Exit with <code>exit()</code> or Ctrl + Z, Enter.</li><li><b>Script mode</b>: save the program as <code>hello.py</code> and run <code>python hello.py</code>.</li></ul>
<pre>print("Hello Python")
a = 10; b = 20
print("Sum is", a + b)</pre>
<h3>Comments</h3>
<pre># single-line comment
'''
Multi-line comment
(a string that is not used – also used as docstring)
'''</pre>
<h3>Variables</h3>
<ul><li>Created when a value is assigned – no declaration needed: <code>name = "Asha"</code>, <code>age = 18</code>.</li><li><b>Rules</b>: start with a letter or underscore, contain only letters, digits and <code>_</code>, are <b>case sensitive</b>, cannot be a keyword (if, for, class…).</li><li>Many values to many variables: <code>x, y, z = "Red", "Green", "Blue"</code>.</li><li>One value to many variables: <code>x = y = z = 0</code>.</li></ul>
<p>Practise on the <a href="live-practice.html?t=p1">Live Practice</a> page.</p>` },

  { part: 4, g: 1, el: "Elective I – Python", bm: 39, mt: "Programming in Python", no: "1.39.03-05", title: "Perform Operations using Data Types and Operators", m: 15,
    keys: ["Standard data types", "Numbers", "Type conversion", "Arithmetic", "Comparison & logical", "Bitwise", "Identity & membership"],
    html: `
<h3>Standard data types</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Example</th></tr>
<tr><td>Numbers – <b>int</b>, <b>float</b>, <b>complex</b></td><td><code>10</code>, <code>3.14</code>, <code>2+3j</code></td></tr><tr><td><b>str</b> (string)</td><td><code>"Welcome Python"</code></td></tr><tr><td><b>bool</b></td><td><code>True</code>, <code>False</code></td></tr>
<tr><td><b>list</b> (changeable)</td><td><code>[1, 2, "a"]</code></td></tr><tr><td><b>tuple</b> (unchangeable)</td><td><code>(1, 2, 3)</code></td></tr><tr><td><b>set</b> (unique, unordered)</td><td><code>{1, 2, 3}</code></td></tr><tr><td><b>dict</b> (key : value)</td><td><code>{"name": "Asha", "age": 18}</code></td></tr><tr><td>NoneType</td><td><code>None</code></td></tr></tbody></table>
<p><code>type(x)</code> shows the type. Conversion: <code>int("25")</code>, <code>float("2.5")</code>, <code>str(10)</code>, <code>list("abc")</code>, <code>bool(0)</code> → False. <b>input() always returns a string</b> – convert it: <code>age = int(input("Enter your age: "))</code>.</p>
<h3>Operators</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Operators (example with a = 10, b = 3)</th></tr>
<tr><td><b>Arithmetic</b></td><td><code>+</code> 13, <code>-</code> 7, <code>*</code> 30, <code>/</code> 3.333…, <code>%</code> modulus 1, <code>//</code> floor division 3, <code>**</code> exponent 1000</td></tr>
<tr><td><b>Assignment</b></td><td><code>= += -= *= /= %= //= **=</code></td></tr>
<tr><td><b>Comparison</b></td><td><code>== != &gt; &lt; &gt;= &lt;=</code> → True / False</td></tr>
<tr><td><b>Logical</b></td><td><code>and</code> (True if both are true), <code>or</code> (True if one is true), <code>not</code> (reverses the result)</td></tr>
<tr><td><b>Bitwise</b></td><td><code>&amp;</code> AND, <code>|</code> OR, <code>^</code> XOR (1 if only one bit is 1), <code>~</code> NOT (inverts bits), <code>&lt;&lt;</code> shift left, <code>&gt;&gt;</code> shift right</td></tr>
<tr><td><b>Identity</b></td><td><code>is</code>, <code>is not</code> – True if both variables are the <b>same object</b></td></tr>
<tr><td><b>Membership</b></td><td><code>in</code>, <code>not in</code> – <code>"P" in "COPA"</code> → True</td></tr></tbody></table>
<p><b>Precedence</b> (high → low): <code>**</code> → unary <code>+ - ~</code> → <code>* / // %</code> → <code>+ -</code> → shifts → <code>&amp;</code> → <code>^</code> → <code>|</code> → comparisons, identity, membership → <code>not</code> → <code>and</code> → <code>or</code>.</p>` },

  { part: 4, g: 1, el: "Elective I – Python", bm: 40, mt: "Programming in Python", no: "1.40.06&07", title: "Control Flow with Decisions and Loops", m: 15,
    keys: ["Indentation", "if, if-else, if-elif-else", "while loop", "for loop & range()", "else with loops", "Nested loops", "break, continue, pass"],
    html: `
<h3>Indentation</h3>
<p>Python uses <b>indentation</b> (usually 4 spaces) to make blocks. Lines ending with <b>:</b> start a block. Wrong indentation gives an <code>IndentationError</code>.</p>
<h3>Decisions</h3>
<pre>age = int(input("Enter your age: "))
if age &gt;= 18:
    print("Eligible to vote")
else:
    print("Not eligible")

marks = int(input("Enter the marks: "))
if marks &gt;= 75:
    print("Distinction")
elif marks &gt;= 60:
    print("First class")
elif marks &gt;= 40:
    print("Pass")
else:
    print("Fail")</pre>
<p>Maximum of three numbers: <code>if a &gt; b and a &gt; c: … elif b &gt; c: … else: …</code> (or simply <code>max(a, b, c)</code>). Short form: <code>result = "Pass" if marks &gt;= 40 else "Fail"</code>.</p>
<h3>Loops</h3>
<pre>i = 1
while i &lt;= 5:          # repeats while the condition is True
    print(i)
    i += 1
else:
    print("The while loop terminated")   # runs when the loop ends normally

for n in range(1, 11):        # 1 to 10
    print(5, "x", n, "=", 5 * n)</pre>
<ul><li><code>range(stop)</code>, <code>range(start, stop)</code>, <code>range(start, stop, step)</code> – the stop value is <b>not included</b>: <code>range(1, 6)</code> → 1, 2, 3, 4, 5; <code>range(10, 0, -2)</code> → 10, 8, 6, 4, 2.</li><li><code>for</code> can loop over a string, list, tuple, set or dictionary.</li><li><code>else</code> with a loop runs only if the loop was not stopped by <code>break</code>.</li></ul>
<h3>Nested loops – pattern</h3>
<pre>rows = int(input("Enter the no. of rows you want to print: "))
for i in range(1, rows + 1):
    for j in range(i):
        print("*", end=" ")
    print()</pre>
<h3>break, continue, pass</h3>
<p><code>break</code> – leaves the loop · <code>continue</code> – skips to the next round · <code>pass</code> – does nothing (placeholder for an empty block).</p>` },

  { part: 4, g: 1, el: "Elective I – Python", bm: 41, mt: "Programming in Python", no: "1.41.08&09", title: "Document and Structure Code", m: 15,
    keys: ["List", "Indexing & slicing", "List functions", "Tuple", "Set & set operators", "Dictionary", "Functions & docstrings"],
    html: `
<h3>List – ordered, changeable</h3>
<pre>L = [10, 20, 30, 40]
L[0]      # 10        L[-1] → 40 (last)
L[1:3]    # [20, 30]  slicing (end not included)
L + [50]  # joining   L * 2 → repeats
20 in L   # True      len(L) → 4, min(L), max(L), sum(L)</pre>
<table class="rte-table"><tbody><tr><th>Method</th><th>Work</th></tr>
<tr><td><code>append(x)</code> / <code>insert(i, x)</code> / <code>extend(list)</code></td><td>Add at the end / at position / many items</td></tr>
<tr><td><code>remove(x)</code></td><td>Removes the first x (ValueError if x not in list)</td></tr>
<tr><td><code>pop(i)</code></td><td>Removes and returns item i (last if no index; IndexError if out of range)</td></tr>
<tr><td><code>index(x)</code>, <code>count(x)</code></td><td>Position of x (optional start, end) / how many times</td></tr>
<tr><td><code>sort()</code>, <code>sort(reverse=True)</code>, <code>reverse()</code></td><td>Sort ascending / descending, reverse order</td></tr>
<tr><td><code>copy()</code>, <code>clear()</code></td><td>Copy / empty the list</td></tr></tbody></table>
<h3>Tuple – ordered, unchangeable</h3>
<p><code>T = (1, 2, 3, "python")</code> – indexing, slicing, <code>+</code>, <code>*</code>, <code>in</code>, <code>len</code>, <code>min</code>, <code>max</code> work; methods <code>count()</code> and <code>index()</code>; <code>tuple([1, 2])</code> converts a sequence. A single-item tuple needs a comma: <code>(5,)</code>. Tuples are faster and safe for fixed data.</p>
<h3>Set – unordered, unique items</h3>
<pre>Days = {"Monday", "Tuesday", "Wednesday"}
A = {1, 2, 3, 4}; B = {3, 4, 5, 6}
A | B   # union        {1, 2, 3, 4, 5, 6}
A &amp; B   # intersection {3, 4}
A - B   # difference   {1, 2}
A ^ B   # symmetric difference {1, 2, 5, 6}</pre>
<p>Methods: <code>add()</code>, <code>update()</code>, <code>remove()</code> (error if missing), <code>discard()</code> (no error), <code>pop()</code> (removes a random item), <code>clear()</code>, <code>union()</code>, <code>intersection()</code>, <code>difference()</code>. Empty set: <code>set()</code> (not <code>{}</code>).</p>
<h3>Dictionary – key : value pairs</h3>
<pre>student = {"name": "Asha", "roll": 12, "trade": "COPA"}
student["name"]            # access → "Asha"
student.get("age", 0)      # safe access with default
student["roll"] = 15       # update
student["city"] = "Pune"   # add
del student["trade"]       # delete
for k, v in student.items(): print(k, v)</pre>
<p>Methods: <code>keys()</code>, <code>values()</code>, <code>items()</code>, <code>update()</code>, <code>pop(key)</code>, <code>clear()</code>. Keys must be unique and unchangeable (str, number, tuple).</p>
<h3>Documenting and structuring code</h3>
<pre>def area(r):
    """Return the area of a circle of radius r."""   # docstring
    return 3.14 * r * r

print(area.__doc__)</pre>
<p>Use functions, meaningful names, comments, docstrings, 4-space indentation and the <b>PEP 8</b> style guide.</p>` },

  { part: 4, g: 1, el: "Elective I – Python", bm: 42, mt: "Programming in Python", no: "1.42.10&11", title: "Perform Operations Using Modules and Tools", m: 15,
    keys: ["Modules", "Creating & importing", "Renaming (as)", "Iterators", "math module", "User input", "pip & tools"],
    html: `
<h3>Modules</h3>
<p>A <b>module</b> is a Python file (<code>.py</code>) containing functions, classes and variables that can be reused in other programs. Python has many built-in modules: <code>math</code>, <code>random</code>, <code>datetime</code>, <code>os</code>, <code>sys</code>, <code>statistics</code>, <code>json</code>.</p>
<h3>Creating and loading a module</h3>
<pre># calc.py  (the module)
def add(a, b): return a + b
def sub(a, b): return a - b

# main.py
import calc                     # load the whole module
print("Sum is:", calc.add(12, 6))
import calc as c                # renaming a module
print("Sub is:", c.sub(25, 12))
from math import sqrt, pi       # import only some names
from random import *            # import all (not recommended)</pre>
<p><code>dir(math)</code> lists the names in a module; <code>help(math.sqrt)</code> shows help.</p>
<h3>Iterators</h3>
<ul><li>An <b>iterable</b> is an object you can loop over – list, tuple, string, set, dict.</li><li>An <b>iterator</b> gives one value at a time: <code>it = iter(("apple", "mango"))</code>, then <code>next(it)</code>. When no values are left it raises <b>StopIteration</b>.</li><li>A class becomes an iterator by defining <code>__iter__()</code> and <code>__next__()</code>; raise StopIteration to stop (e.g. after 20 iterations).</li></ul>
<h3>Math</h3>
<p>Built-in: <code>min()</code>, <code>max()</code>, <code>abs()</code>, <code>pow(2, 3)</code>, <code>round(3.567, 2)</code>, <code>sum()</code>. <b>math module</b>: <code>math.sqrt(64)</code> → 8.0, <code>math.ceil(1.4)</code> → 2, <code>math.floor(1.6)</code> → 1, <code>math.factorial(5)</code> → 120, <code>math.pi</code>, <code>math.pow()</code>, <code>math.log()</code>, <code>math.sin()</code>, <code>math.gcd()</code>.</p>
<h3>User input</h3>
<pre>name = input("Enter your name: ")       # Python 3
a = int(input("Enter a: ")); b = int(input("Enter b: "))
print("Sum is:", a + b, "Mul is:", a * b, "Div is:", a / b)</pre>
<p>In old <b>Python 2.7</b> the function was <code>raw_input()</code>; Python 3 uses <code>input()</code>. <b>f-strings</b> (Python 3.6+): <code>print(f"Sum is {a + b}")</code>.</p>
<h3>Tools</h3>
<p><b>pip</b> installs extra packages: <code>pip install requests</code> · virtual environments: <code>python -m venv env</code> · editors / IDEs: IDLE, VS Code, PyCharm, Jupyter Notebook.</p>` },

  /* ===================== ELECTIVE MODULE II : PROGRAMMING IN JAVA ===================== */
  { part: 4, g: 2, el: "Elective II – Java", bm: 38, mt: "Programming in JAVA", no: "1.38.01-03", title: "Object Oriented Programming and JAVA Language", m: 14,
    keys: ["What is Java", "History", "Types of applications", "Editions (SE, EE, ME, FX)", "Features", "OOP concepts", "First program"],
    html: `
<h3>Java</h3>
<p><b>Java</b> is a high-level, <b>object-oriented</b>, secure, platform-independent programming language and a <b>platform</b> (hardware or software environment in which a program runs – Java has its own runtime, the JRE/JVM).</p>
<h3>History</h3>
<p>Developed by <b>James Gosling</b> and his team ("Green Team") at <b>Sun Microsystems</b> starting in 1991. First named <b>Oak</b> (after an oak tree); since "Oak" was already a registered company, it was renamed <b>Java</b> (after Java coffee). Released in <b>1995</b> (JDK 1.0 in 1996). Oracle bought Sun in 2010. A new version comes every six months (March and September); LTS versions: Java 8, 11, 17, 21.</p>
<h3>Types of Java applications</h3>
<table class="rte-table"><tbody><tr><th>Type</th><th>Examples / technology</th></tr>
<tr><td><b>Standalone</b> (desktop)</td><td>Media player, antivirus – AWT, Swing, JavaFX</td></tr><tr><td><b>Web</b> applications</td><td>Run on a server – Servlet, JSP, Spring</td></tr><tr><td><b>Enterprise</b></td><td>Banking, business – EJB, Java EE / Jakarta EE</td></tr><tr><td><b>Mobile</b></td><td>Android apps, Java ME</td></tr></tbody></table>
<h3>Java platforms / editions</h3>
<p><b>Java SE</b> (Standard Edition – core: java.lang, java.util, I/O, networking, Swing) · <b>Java EE</b> (Enterprise Edition – Servlet, JSP, web services) · <b>Java ME</b> (Micro Edition – mobile / embedded) · <b>JavaFX</b> (rich internet / GUI applications).</p>
<h3>Features</h3>
<ul><li><b>Simple</b> – syntax like C++ but no pointers or operator overloading; <b>automatic garbage collection</b> removes unreferenced objects.</li><li><b>Object-oriented</b> – everything is inside classes.</li><li><b>Platform independent</b> – "Write Once, Run Anywhere": code compiles to <b>bytecode</b> that runs on any JVM.</li><li><b>Secured</b> – no explicit pointers, programs run in the JVM sandbox; ClassLoader, <b>Bytecode Verifier</b> and Security Manager.</li><li><b>Robust</b> – strong memory management, exception handling, type checking.</li><li><b>Architecture-neutral</b> and <b>portable</b> – sizes of data types are fixed.</li><li>High performance (JIT compiler), <b>distributed</b> (RMI, networking), <b>multi-threaded</b>, dynamic.</li></ul>
<h3>OOP concepts</h3>
<p><b>Object</b> (real-world entity with state and behaviour), <b>Class</b> (blueprint of objects), <b>Inheritance</b>, <b>Polymorphism</b>, <b>Abstraction</b>, <b>Encapsulation</b> – explained in lesson 1.41.17-23.</p>
<h3>First Java program</h3>
<pre>// File: Simple.java  (file name = class name)
class Simple {
    public static void main(String[] args) {
        System.out.println("Hello Java");
    }
}</pre>
<p>Compile: <code>javac Simple.java</code> → creates <code>Simple.class</code> (bytecode). Run: <code>java Simple</code>.</p>` },

  { part: 4, g: 2, el: "Elective II – Java", bm: 39, mt: "Programming in JAVA", no: "1.39.04-07", title: "Demonstrate writing JAVA programs", m: 14,
    keys: ["JDK, JRE, JVM", "JVM architecture", "Bytecode", "CLASSPATH", "Data types & variables", "Operators"],
    html: `
<h3>JDK, JRE and JVM</h3>
<table class="rte-table"><tbody><tr><th>Part</th><th>Meaning</th></tr>
<tr><td><b>JVM</b> – Java Virtual Machine</td><td>Abstract machine that <b>runs bytecode</b>; available for many platforms</td></tr>
<tr><td><b>JRE</b> – Java Runtime Environment</td><td>JVM + libraries needed to run programs</td></tr>
<tr><td><b>JDK</b> – Java Development Kit</td><td>JRE + development tools (<code>javac</code>, <code>java</code>, <code>javadoc</code>, <code>jar</code>) – needed to write programs</td></tr></tbody></table>
<h3>What the JVM does</h3>
<p><b>Loads</b> code, <b>verifies</b> code, <b>executes</b> code and provides the <b>runtime environment</b>. It defines the memory areas, class file format, register set, garbage-collected heap and error reporting.</p>
<h3>JVM architecture</h3>
<ul><li><b>Class Loader</b> subsystem – Bootstrap (core java.lang classes), Extension/Platform and Application (system) class loaders.</li><li><b>Memory areas</b>: <b>Class (Method) Area</b> – per-class data, constants, method code · <b>Heap</b> – objects · <b>Stack</b> – frames of method calls and local variables · <b>PC Register</b> – address of the current instruction · <b>Native Method Stack</b>.</li><li><b>Execution Engine</b> – interpreter, <b>JIT (Just-In-Time) compiler</b> that turns frequent bytecode into machine code, and the garbage collector.</li></ul>
<h3>Bytecode</h3>
<p>The compiler (<code>javac</code>) converts source code into <b>bytecode</b> (<code>.class</code>) – platform-independent instructions between high-level code and machine code. The JVM interprets / compiles it for the actual machine. Advantages: platform independence and portability, security checks. Disadvantage: a little slower than native code at start-up.</p>
<h3>CLASSPATH</h3>
<p><b>CLASSPATH</b> tells the compiler and JVM where to find classes and libraries (.jar). Windows command prompt: <code>set CLASSPATH=.;C:\\java\\lib\\mylib.jar</code> (or permanently in System Properties → Environment Variables). Linux: <code>export CLASSPATH=.:/home/user/lib/mylib.jar</code>. Check: <code>echo %CLASSPATH%</code> / <code>echo $CLASSPATH</code>. Also add the JDK <code>bin</code> folder to <b>PATH</b> so <code>javac</code> works everywhere.</p>
<h3>Data types and variables</h3>
<table class="rte-table"><tbody><tr><th>Primitive type</th><th>Size</th></tr>
<tr><td><code>byte</code> · <code>short</code> · <code>int</code> · <code>long</code></td><td>1 · 2 · 4 · 8 bytes (whole numbers)</td></tr><tr><td><code>float</code> · <code>double</code></td><td>4 · 8 bytes (decimals)</td></tr><tr><td><code>char</code></td><td>2 bytes (Unicode character)</td></tr><tr><td><code>boolean</code></td><td>true / false</td></tr></tbody></table>
<pre>int roll = 12;  double fee = 2500.50;  char grade = 'A';
boolean pass = true;  String name = "Asha";   // String is a class (non-primitive)
final double PI = 3.14;                       // constant</pre>
<p>Operators are like C/JavaScript: arithmetic <code>+ - * / %</code>, relational, logical <code>&amp;&amp; || !</code>, assignment, increment <code>++ --</code>, ternary <code>?:</code>, bitwise. Input with <code>Scanner sc = new Scanner(System.in); int n = sc.nextInt();</code></p>` },

  { part: 4, g: 2, el: "Elective II – Java", bm: 40, mt: "Programming in JAVA", no: "1.40.08-16", title: "JAVA Program Flow Control", m: 14,
    keys: ["if, else if, else", "switch", "while & do-while", "for & for-each", "break & continue", "Nested loops", "Arrays"],
    html: `
<h3>Conditions</h3>
<pre>int time = 20;
if (time &lt; 12) {
    System.out.println("Good morning");
} else if (time &lt; 18) {
    System.out.println("Good day");
} else {
    System.out.println("Good evening");
}
String result = (marks &gt;= 40) ? "Pass" : "Fail";   // ternary</pre>
<h3>switch</h3>
<pre>switch (day) {
  case 1: System.out.println("Monday"); break;
  case 2: System.out.println("Tuesday"); break;
  default: System.out.println("Weekend");
}</pre>
<p><b>break</b> stops the switch (without it execution falls through to the next case); <b>default</b> runs when no case matches. switch works with int, char, String and enum.</p>
<h3>Loops</h3>
<pre>int i = 0;
while (i &lt; 5) { System.out.println(i); i++; }     // checks first
do { System.out.println(i); i--; } while (i &gt; 0); // runs at least once
for (int j = 1; j &lt;= 10; j++) System.out.println("5 x " + j + " = " + 5 * j);
int[] marks = {72, 45, 90};
for (int m : marks) System.out.println(m);        // for-each</pre>
<h3>break and continue</h3>
<pre>for (int k = 0; k &lt; 10; k++) {
  if (k == 4) continue;   // skips the value 4
  if (k == 8) break;      // stops the loop
  System.out.println(k);
}</pre>
<p>Loops can be <b>nested</b> (a loop inside a loop) – e.g. for patterns and tables.</p>
<h3>Arrays</h3>
<pre>String[] cars = {"Volvo", "BMW", "Ford"};
System.out.println(cars[0]);     // access – index starts at 0
cars[0] = "Opel";                // change an element
System.out.println(cars.length); // array length → 3
int[] nums = new int[5];         // array of 5 ints (all 0)
int[][] matrix = {{1, 2}, {3, 4}};   // two-dimensional array</pre>
<p>An array has a <b>fixed size</b>; for a changeable size use <code>ArrayList</code>.</p>` },

  { part: 4, g: 2, el: "Elective II – Java", bm: 41, mt: "Programming in JAVA", no: "1.41.17-23", title: "JAVA Classes, Overloading and Inheritance", m: 14,
    keys: ["OOPs concepts", "Class & object", "Constructor", "Method overloading", "Coupling & cohesion", "Association, aggregation, composition", "Access modifiers"],
    html: `
<h3>Object-oriented programming (OOPs)</h3>
<p>OOP designs a program using <b>classes and objects</b>. <b>Simula</b> is considered the first OOP language and <b>Smalltalk</b> the first truly object-oriented language. Main aim: model real-world entities, reuse code and make programs easy to maintain.</p>
<table class="rte-table"><tbody><tr><th>Concept</th><th>Meaning</th></tr>
<tr><td><b>Object</b></td><td>Real entity with state (data) and behaviour (methods) – e.g. a pen, a student</td></tr><tr><td><b>Class</b></td><td>Blueprint / template from which objects are created</td></tr>
<tr><td><b>Inheritance</b></td><td>One class gets the properties of another – code reuse</td></tr><tr><td><b>Polymorphism</b></td><td>One task done in many ways – method <b>overloading</b> (compile time) and <b>overriding</b> (run time)</td></tr>
<tr><td><b>Abstraction</b></td><td>Hiding internal details and showing only functionality – abstract classes, interfaces</td></tr><tr><td><b>Encapsulation</b></td><td>Wrapping data and code together in a class; private fields with getters / setters</td></tr></tbody></table>
<h3>Class, object and constructor</h3>
<pre>class Student {
    int roll; String name;                 // fields (state)
    Student(int r, String n) {             // constructor – same name, no return type
        roll = r; name = n;
    }
    void show() { System.out.println(roll + " " + name); }   // method (behaviour)
}
Student s1 = new Student(12, "Asha");      // object created with new
s1.show();</pre>
<p>A <b>default constructor</b> is added automatically when you write none. <code>this</code> refers to the current object.</p>
<h3>Method overloading</h3>
<pre>int add(int a, int b) { return a + b; }
int add(int a, int b, int c) { return a + b + c; }
double add(double a, double b) { return a + b; }</pre>
<p>Same method name with a different <b>number or type of parameters</b> in the same class (return type alone is not enough).</p>
<h3>Other terms</h3>
<ul><li><b>Coupling</b> – how much one class depends on another (loose coupling is better). <b>Cohesion</b> – how well a class does one clear job (high cohesion is better).</li><li><b>Association</b> – relationship between objects: one-to-one, one-to-many, many-to-one, many-to-many (one country has one prime minister).</li><li><b>Aggregation</b> – "has-a" weak relationship (a department has teachers; teachers can exist without it). <b>Composition</b> – strong relationship (a house has rooms; deleting the house deletes the rooms).</li><li><b>Access modifiers</b>: <code>private</code> (class only), default (package), <code>protected</code> (package + subclasses), <code>public</code> (everywhere). <b>Package</b> – group of related classes.</li><li><b>Static vs dynamic binding</b> – method decided at compile time (overloading, static/final/private methods) or at run time (overriding).</li></ul>
<h3>OOP vs procedure-oriented</h3>
<p>Procedure-oriented programs share <b>global data</b> between functions; OOP keeps <b>object data</b> inside objects – more secure, reusable and easier to maintain. Object-based languages (e.g. JavaScript, VBScript) use objects but do not support all OOP features such as classical inheritance.</p>` },

  { part: 4, g: 2, el: "Elective II – Java", bm: 42, mt: "Programming in JAVA", no: "1.42.24-30", title: "Abstract Classes and Interfaces in JAVA", m: 14,
    keys: ["Inheritance (extends)", "Types of inheritance", "Method overriding", "Overloading vs overriding", "Abstract class", "Interface"],
    html: `
<h3>Inheritance</h3>
<p>Inheritance represents the <b>IS-A</b> relationship (a Programmer IS-A Employee). The <b>subclass (child)</b> inherits fields and methods of the <b>superclass (parent)</b> using <code>extends</code>. Uses: <b>reusability</b> and method <b>overriding</b> (run-time polymorphism).</p>
<pre>class Employee { float salary = 40000; }
class Programmer extends Employee {
    int bonus = 10000;
}
// new Programmer().salary → 40000.0 (inherited)</pre>
<table class="rte-table"><tbody><tr><th>Type</th><th>Structure</th></tr>
<tr><td>Single</td><td>B extends A</td></tr><tr><td>Multilevel</td><td>C extends B, B extends A</td></tr><tr><td>Hierarchical</td><td>B and C both extend A</td></tr><tr><td>Multiple / Hybrid</td><td><b>Not supported with classes</b> (to avoid ambiguity – compile-time error); achieved with <b>interfaces</b></td></tr></tbody></table>
<h3>Method overriding</h3>
<pre>class Vehicle { void run() { System.out.println("Vehicle is running"); } }
class Bike extends Vehicle {
    @Override
    void run() { System.out.println("Bike is running safely"); }
}</pre>
<p>Rules: same name and same parameters as in the parent, needs an IS-A relationship, cannot reduce visibility. <b>static</b>, <b>final</b> and <b>private</b> methods cannot be overridden. <code>super.run()</code> calls the parent version.</p>
<table class="rte-table"><tbody><tr><th>Overloading</th><th>Overriding</th></tr>
<tr><td>Same class, different parameters</td><td>Subclass, same parameters</td></tr><tr><td>Compile-time polymorphism</td><td>Run-time polymorphism</td></tr><tr><td>Increases readability</td><td>Gives a specific implementation of a parent method</td></tr></tbody></table>
<h3>Abstract class</h3>
<pre>abstract class Shape {
    abstract double area();             // abstract method – no body
    void info() { System.out.println("I am a shape"); }
}
class Circle extends Shape {
    double r = 2;
    double area() { return 3.14 * r * r; }
}</pre>
<p>Declared with <code>abstract</code>; <b>cannot be instantiated</b> (no <code>new Shape()</code>); can have abstract and normal methods, constructors and fields. A subclass must implement all abstract methods (or be abstract). A <b>factory method</b> returns objects of subclasses.</p>
<h3>Interface</h3>
<pre>interface Printable { void print(); }      // methods are public abstract by default
interface Showable  { void show(); }
class Report implements Printable, Showable {   // multiple inheritance through interfaces
    public void print() { System.out.println("Printing"); }
    public void show()  { System.out.println("Showing"); }
}</pre>
<p>An interface is a blueprint with abstract methods and <code>public static final</code> constants (Java 8+ also allows default and static methods). It gives <b>100% abstraction</b> and <b>multiple inheritance</b>; a class can <code>implements</code> many interfaces.</p>` }
]);
