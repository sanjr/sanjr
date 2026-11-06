"""Merge one-line answer explanations (tools/explain/e*.txt: id<TAB>en<TAB>hi<TAB>mr) into data/questions.js."""
import json, glob, os, re, sys
here = os.path.dirname(os.path.abspath(__file__))
root = os.path.dirname(os.path.dirname(here))
src = os.path.join(root, 'data', 'questions.js')
txt = open(src, encoding='utf-8').read()
m = re.search(r'window\.COPA_QUESTIONS = (\[.*\]);', txt, re.S)
qs = json.loads(m.group(1))
ex = {}
for f in sorted(glob.glob(os.path.join(here, 'e*.txt'))):
    for line in open(f, encoding='utf-8'):
        line = line.rstrip('\n')
        if not line.strip():
            continue
        i, en, hi, mr = line.split('\t')
        ex[i] = {'en': en.strip(), 'hi': hi.strip(), 'mr': mr.strip()}
missing = [q['id'] for q in qs if q['id'] not in ex]
if missing:
    sys.exit('missing explanations: ' + ', '.join(missing))
for q in qs:
    q['x'] = ex[q['id']]
with open(src, 'w', encoding='utf-8') as f:
    f.write('/* COPA Question Bank – 355 MCQs, 15 modules (EN / HI / MR) with one-line answer explanations (x). */\n')
    f.write('window.COPA_QUESTIONS = ')
    json.dump(qs, f, ensure_ascii=False, separators=(',', ':'))
    f.write(';\n')
print('merged', len(ex), 'explanations into', len(qs), 'questions')
