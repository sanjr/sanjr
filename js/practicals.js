/* COPA Live Practice – ready-made practical programs (HTML/CSS/JS and Python) */
window.COPA_PRACTICALS = [
  /* ===================== WEB : HTML ===================== */
  { id: "w1", lang: "web", cat: "html", title: "My First Web Page",
    html: `<!-- Headings, paragraph, bold, italic -->
<h1>Welcome to COPA</h1>
<h2>Computer Operator &amp; Programming Assistant</h2>
<p>This is my <b>first</b> web page made in <i>HTML</i>.</p>
<p>(A+B)<sup>2</sup> = A<sup>2</sup> + 2AB + B<sup>2</sup></p>
<p>Water formula: H<sub>2</sub>O</p>
<hr>
<p>Made by: <u>Your Name</u></p>`,
    css: `body { font-family: Arial, sans-serif; background: #fff8f0; padding: 20px; }
h1 { color: #e44d26; }
h2 { color: #264de4; }`, js: "" },
  { id: "w2", lang: "web", cat: "html", title: "Ordered & Unordered List",
    html: `<h2>COPA Modules</h2>
<ol type="1">
  <li>Computer Fundamentals</li>
  <li>Windows OS</li>
  <li>MS Word</li>
  <li>MS Excel</li>
</ol>

<h2>Input Devices</h2>
<ul type="square">
  <li>Keyboard</li>
  <li>Mouse</li>
  <li>Scanner</li>
</ul>`,
    css: `body { font-family: Arial; padding: 20px; }
li { padding: 4px; }
ol li { color: #1554e0; }
ul li { color: #18a957; }`, js: "" },
  { id: "w3", lang: "web", cat: "html", title: "Table – Student Marks",
    html: `<h2>Student Marks</h2>
<table border="1" cellpadding="8" cellspacing="0">
  <tr>
    <th>Roll No</th><th>Name</th><th>Theory</th><th>Practical</th><th>Total</th>
  </tr>
  <tr><td>1</td><td>Ram</td><td>87</td><td>90</td><td>177</td></tr>
  <tr><td>2</td><td>Sham</td><td>68</td><td>88</td><td>156</td></tr>
  <tr><td>3</td><td>Vijay</td><td>90</td><td>98</td><td>188</td></tr>
  <tr>
    <td colspan="4" align="right"><b>Class Average</b></td><td>173.6</td>
  </tr>
</table>`,
    css: `body { font-family: Arial; padding: 20px; }
table { border-collapse: collapse; }
th { background: #107c41; color: white; }
tr:nth-child(even) { background: #e8f5ee; }`, js: "" },
  { id: "w4", lang: "web", cat: "html", title: "Student Registration Form",
    html: `<h2>Admission Form – ITI COPA</h2>
<form>
  <label>Full Name:</label><br>
  <input type="text" placeholder="Enter your name"><br><br>

  <label>Gender:</label>
  <input type="radio" name="g"> Male
  <input type="radio" name="g"> Female<br><br>

  <label>Trade:</label>
  <select>
    <option>COPA</option>
    <option>Fitter</option>
    <option>Electrician</option>
  </select><br><br>

  <input type="checkbox"> I agree to the rules<br><br>
  <input type="submit" value="Submit">
  <input type="reset" value="Clear">
</form>`,
    css: `body { font-family: Arial; padding: 20px; background: #f3f6fc; }
form { background: white; padding: 20px; border-radius: 10px; width: 300px; }
input[type=text], select { width: 100%; padding: 8px; }
input[type=submit] { background: #1554e0; color: white; border: 0; padding: 8px 16px; }`, js: "" },
  { id: "w5", lang: "web", cat: "html", title: "Image, Hyperlink & Email Link",
    html: `<h2>Image and Links</h2>
<img src="assets/img/modules/m10.jpg" alt="big boat" width="300" height="169">
<p><a href="https://nimi.gov.in" target="_blank">Visit NIMI</a></p>
<p><a href="mailto:student@example.com">Send me an Email</a></p>
<p><a href="#bottom">Go to bottom</a></p>
<p id="bottom">This is the bottom of the page.</p>`,
    css: `body { font-family: Arial; padding: 20px; }
img { border: 3px solid #e44d26; border-radius: 8px; }
a { color: #1554e0; font-weight: bold; }`, js: "" },
  /* ===================== WEB : CSS ===================== */
  { id: "w6", lang: "web", cat: "css", title: "CSS Box Model & Colours",
    html: `<div class="box">Content</div>
<p class="note">Margin → Border → Padding → Content</p>`,
    css: `body { font-family: Arial; padding: 20px; }
.box {
  width: 200px;
  padding: 20px;          /* inside space  */
  border: 6px solid #7b3fe4;
  margin: 30px;           /* outside space */
  background-color: #f0e8ff;
  color: #7b3fe4;
  text-align: center;
  font-size: 22px;
}
.note { color: gray; }`, js: "" },
  { id: "w7", lang: "web", cat: "css", title: "Profile Card with Flexbox",
    html: `<div class="card">
  <div class="avatar">R</div>
  <div>
    <h3>Rahul Patil</h3>
    <p>ITI COPA Trainee • 2025-26</p>
    <button>Follow</button>
  </div>
</div>`,
    css: `body { font-family: Arial; background: #eef2f9; display: flex; justify-content: center; padding: 40px; }
.card { display: flex; gap: 16px; align-items: center; background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.avatar { width: 70px; height: 70px; border-radius: 50%; background: #ffc21a; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold; }
h3 { margin: 0; }
button { background: #1554e0; color: white; border: 0; padding: 8px 18px; border-radius: 20px; cursor: pointer; }
button:hover { background: #0f45c2; }`, js: "" },
  /* ===================== WEB : JavaScript ===================== */
  { id: "w8", lang: "web", cat: "js", title: "JS – Alert, Prompt & Confirm",
    html: `<h2>Pop-up Boxes</h2>
<button onclick="showAlert()">Alert</button>
<button onclick="askName()">Prompt</button>
<button onclick="askConfirm()">Confirm</button>
<p id="out"></p>`,
    css: `body { font-family: Arial; padding: 20px; }
button { padding: 8px 14px; margin: 4px; }`,
    js: `function showAlert() {
  alert("Welcome to COPA!");
}
function askName() {
  var name = prompt("Enter your name");
  document.getElementById("out").innerHTML = "Hello, " + name;
}
function askConfirm() {
  var ok = confirm("Do you want to continue?");
  console.log("You clicked:", ok);
}` },
  { id: "w9", lang: "web", cat: "js", title: "JS – Simple Calculator",
    html: `<h2>Calculator</h2>
<input id="a" type="number" value="10">
<input id="b" type="number" value="3"><br><br>
<button onclick="calc('+')">+</button>
<button onclick="calc('-')">−</button>
<button onclick="calc('*')">×</button>
<button onclick="calc('/')">÷</button>
<button onclick="calc('%')">%</button>
<h3>Result: <span id="r">-</span></h3>`,
    css: `body { font-family: Arial; padding: 20px; }
input { width: 90px; padding: 6px; }
button { width: 44px; height: 40px; font-size: 18px; margin: 2px; }`,
    js: `function calc(op) {
  var a = Number(document.getElementById("a").value);
  var b = Number(document.getElementById("b").value);
  var r;
  switch (op) {
    case "+": r = a + b; break;
    case "-": r = a - b; break;
    case "*": r = a * b; break;
    case "/": r = a / b; break;
    case "%": r = a % b; break;   // remainder
  }
  document.getElementById("r").innerHTML = r;
  console.log(a + " " + op + " " + b + " = " + r);
}` },
  { id: "w10", lang: "web", cat: "js", title: "JS – Change Colour (DOM Events)",
    html: `<h2 id="title">Click a colour</h2>
<button onclick="paint('#ffe0e0')">Red</button>
<button onclick="paint('#e0ffe8')">Green</button>
<button onclick="paint('#e0ecff')">Blue</button>
<p>Screen width: <b id="w"></b> px</p>`,
    css: `body { font-family: Arial; padding: 20px; transition: background .4s; }`,
    js: `function paint(c) {
  document.body.style.backgroundColor = c;
  document.getElementById("title").innerHTML = "Colour: " + c;
}
document.getElementById("w").innerHTML = screen.width;` },
  { id: "w11", lang: "web", cat: "js", title: "JS – Array Sort, Join & Loop",
    html: `<h2>Array Programs</h2>
<p id="o1"></p><p id="o2"></p><ul id="list"></ul>`,
    css: `body { font-family: Arial; padding: 20px; }`,
    js: `var trade = ["Fitter", "COPA", "Electrician", "Welder"];
trade.sort();
document.getElementById("o1").innerHTML = "Sorted: " + trade.join(", ");

var marks = [87, 45, 90, 32, 68];
var total = 0;
for (var i = 0; i < marks.length; i++) {
  total = total + marks[i];
}
document.getElementById("o2").innerHTML = "Total = " + total + ", Average = " + (total / marks.length);

trade.forEach(function (t) {
  document.getElementById("list").innerHTML += "<li>" + t + "</li>";
});
console.log(trade);` },
  { id: "w12", lang: "web", cat: "js", title: "JS – Multiplication Table",
    html: `<h2>Table of <input id="n" type="number" value="5" style="width:60px"> <button onclick="table()">Show</button></h2>
<pre id="t"></pre>`,
    css: `body { font-family: Arial; padding: 20px; }
pre { font-size: 18px; color: #1554e0; }`,
    js: `function table() {
  var n = Number(document.getElementById("n").value);
  var s = "";
  for (var i = 1; i <= 10; i++) {
    s += n + " x " + i + " = " + (n * i) + "\\n";
  }
  document.getElementById("t").innerHTML = s;
}
table();` },

  /* ===================== PYTHON ===================== */
  { id: "p1", lang: "python", cat: "py", title: "Python – Hello World & print()",
    py: `# My first Python program
print("Hello COPA!")
print("Welcome to", "Python", sep=" - ")
print(10 + 20)
print("Power:", 2 ** 3)
print('%x, %X' % (15, 15))` },
  { id: "p2", lang: "python", cat: "py", title: "Python – Add Two Numbers (input)",
    py: `# Type the numbers in the INPUT box (one per line) or in the pop-up
a = int(input("Enter first number: "))
b = int(input("Enter second number: "))
print("Sum =", a + b)
print("Difference =", a - b)
print("Product =", a * b)`, stdin: "25\n17" },
  { id: "p3", lang: "python", cat: "py", title: "Python – Result with if / elif / else",
    py: `marks = int(input("Enter marks out of 100: "))

if marks >= 75:
    print("Distinction")
elif marks >= 60:
    print("First Class")
elif marks >= 40:
    print("PASS")
else:
    print("FAIL")`, stdin: "68" },
  { id: "p4", lang: "python", cat: "py", title: "Python – Multiplication Table (for loop)",
    py: `n = int(input("Table of: "))
for i in range(1, 11):
    print(n, "x", i, "=", n * i)`, stdin: "7" },
  { id: "p5", lang: "python", cat: "py", title: "Python – Sum of Digits (while loop)",
    py: `num = int(input("Enter a number: "))
total = 0
while num > 0:
    digit = num % 10
    total = total + digit
    num = num // 10
print("Sum of digits =", total)`, stdin: "12345" },
  { id: "p6", lang: "python", cat: "py", title: "Python – List Operations",
    py: `lst = ['Python', 'Java', 'C++']
lst.append('SQL')          # add at end
lst.insert(1, 'HTML')      # add at index 1
print(lst)
print("Length:", len(lst))
lst.pop()                  # remove last
lst.sort()
print("Sorted:", lst)
print("First:", lst[0], " Last:", lst[-1])` },
  { id: "p7", lang: "python", cat: "py", title: "Python – Dictionary (Student Marks)",
    py: `students = {"Ram": 87, "Sham": 35, "Vijay": 90}
students["Sneha"] = 72        # add new key
for name, marks in students.items():
    result = "PASS" if marks >= 40 else "FAIL"
    print(name, ":", marks, "-", result)

print("john" in students)
del students["Sham"]
print(students)` },
  { id: "p8", lang: "python", cat: "py", title: "Python – Factorial using Function",
    py: `def factorial(n):
    if n == 0 or n == 1:
        return 1
    return n * factorial(n - 1)

num = int(input("Enter a number: "))
print("Factorial of", num, "is", factorial(num))`, stdin: "5" },
  { id: "p9", lang: "python", cat: "py", title: "Python – String Operations",
    py: `s = "hello copa"
print(s.upper())
print(s.title())
print(len(s))
print(s[0:5])
print(s.replace("copa", "python"))
c = 0
for ch in s:
    if ch != "l":
        c = c + 1
print("Letters except l:", c)` },
  { id: "p10", lang: "python", cat: "py", title: "Python – Area of Circle (math)",
    py: `import math
r = float(input("Enter radius: "))
area = math.pi * r * r
print("Area of circle = %.2f" % area)
print("Circumference =", round(2 * math.pi * r, 2))`, stdin: "7" },
  { id: "p11", lang: "python", cat: "py", title: "Python – Prime Number Check",
    py: `n = int(input("Enter a number: "))
if n < 2:
    print(n, "is not prime")
else:
    for i in range(2, int(n ** 0.5) + 1):
        if n % i == 0:
            print(n, "is not prime")
            break
    else:
        print(n, "is a prime number")`, stdin: "29" },
  { id: "p12", lang: "python", cat: "py", title: "Python – Class & Object",
    py: `class Student:
    def __init__(self, name, marks):
        self.name = name
        self.marks = marks

    def result(self):
        return "PASS" if self.marks >= 40 else "FAIL"

s1 = Student("Rahul", 78)
s2 = Student("Amit", 32)
for s in [s1, s2]:
    print(s.name, s.marks, s.result())` }
];
