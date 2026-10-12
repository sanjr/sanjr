# ITIExam.com – Website

Fully responsive, SEO-optimised homepage for **ITIExam.com** (ITI exam preparation platform).
Pure HTML + CSS + vanilla JS — no framework, no build step, very fast loading.

## Files
| File | Purpose |
|---|---|
| `index.html` | Homepage (header, hero, trades, mock tests, jobs, apprenticeship, study material, exam updates, articles, FAQ, footer) |
| `assets/css/style.css` | Mobile-first responsive styles (mobile → tablet → laptop → desktop) |
| `assets/js/main.js` | Mobile menu, dropdowns, back-to-top |
| `robots.txt`, `sitemap.xml` | Search engine crawling |
| `site.webmanifest`, `assets/img/favicon.svg` | PWA / icons |
| `assets/img/og-image.png` | Share image for WhatsApp / Facebook / Twitter |
| `404.html` | Not-found page |

## SEO already included
- Keyword-rich `<title>`, meta description, keywords, canonical URL
- Open Graph + Twitter cards (good previews on WhatsApp/Facebook)
- JSON-LD structured data: Organization, WebSite + SearchAction (Google sitelinks search box), ItemList of trades, **FAQPage** (FAQ rich results)
- Semantic HTML5 (`header`, `nav`, `main`, `section`, `article`, single `h1`, proper `h2`/`h3` order), `<time>` tags
- Fast: no images to download in the page body, inline SVG icons, deferred JS, `font-display: swap`
- Accessible: skip link, ARIA labels, keyboard support, reduced-motion support
- robots.txt + sitemap.xml

## Deploy
Upload the contents of this folder to the web root of `www.itiexam.com`
(any static host works: Hostinger, Netlify, Vercel, GitHub Pages, Cloudflare Pages).
If you use a different domain (e.g. without `www`), find-and-replace `https://www.itiexam.com` in
`index.html`, `robots.txt` and `sitemap.xml`.

## To rank on Google (after deploying)
1. Add the site to **Google Search Console**, verify, and submit `https://www.itiexam.com/sitemap.xml`.
2. Create the inner pages linked from the homepage (`/trades/copa/`, `/mock-test/...`, `/blog/...`) —
   each with its own unique title, description and 800+ words of useful content. Google ranks *pages*, not the homepage alone.
3. Publish new articles regularly (exam dates, results, papers, apprenticeship notices) and update `sitemap.xml`.
4. Create a **Google Business Profile**, YouTube channel, Telegram & WhatsApp channels and link back to the site.
5. Get backlinks from education sites, ITI colleges, forums.
6. Check speed in **PageSpeed Insights** and keep Core Web Vitals green.
7. Replace the social links in the footer / JSON-LD with your real profiles.

> No one can guarantee a #1 Google rank — it depends on content quality, competition, backlinks and time.
> This site gives you a strong technical SEO foundation to compete.
