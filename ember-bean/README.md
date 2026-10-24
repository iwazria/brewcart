# Ember & Bean — Specialty Coffee Roasters

Single-file specialty coffee website, built from the supplied HTML.

## What it is

- Dark espresso theme (#2b1b12), cream text, caramel accents, Playfair Display + Inter
- Hero: "Roasted this morning. In your cup by noon."
- Interactive Three.js (r128) 3D cup with latte art and steam — drag to rotate, moves through scroll stops
- Signature menu (6 drinks): Espresso $3.50, Latte $4.75, Cappuccino $4.50, Cold brew $5.00, Mocha $5.25, Flat white $4.75
- Craft section: 14 origins on rotation, 480 roast batches each year, 32 partner farms
- Reviews, visit details (14 Mill Lane, Mon–Fri 7:00 am–6:00 pm, Sat–Sun 8:00 am–5:00 pm, (555) 014-2290)
- Contact form and roast-calendar newsletter form, both with client-side validation
- Mobile menu, reduced-motion support, preloader

## How to run

Open `index.html` in a browser with internet access. Three.js and Google Fonts load from CDNs.

## Important limit

The contact and newsletter forms in this file are front-end only: they validate and show an on-page thank-you, but they do not send or store messages. A backend is needed before messages can be saved or emailed.

## Files

- `index.html` — the whole site
- `README.md` — this file
