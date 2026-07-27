# Random Quote Generator (XAMPP + PHP + jQuery + Tailwind + Groq)

A dynamic quote generator built to the original roadmap, now powered by
**Groq's LLM API** to generate fresh quotes on demand instead of pulling
from a static quotes database. No API key ever touches client-side code —
the browser only ever talks to the PHP proxy.

## 📁 Structure

```
quote-generator/
├── index.php               # Frontend UI & layout (Tailwind + jQuery CDN)
├── fetch-quote.php         # PHP backend proxy / API handler
├── config.php              # Groq API key + endpoint config
├── assets/
│   ├── js/app.js           # jQuery AJAX logic
│   ├── css/style.css       # Small extras beyond Tailwind utilities
│   └── quotes/offline-quotes.json  # Local fallback quotes by category
└── README.md
```

## 🚀 Setup (XAMPP)

1. Copy the whole `quote-generator/` folder into your XAMPP web root:
   - Windows: `C:\xampp\htdocs\`
   - macOS: `/Applications/XAMPP/htdocs/`
   - Linux: `/opt/lampp/htdocs/`
2. Start **Apache** from the XAMPP Control Panel (MySQL isn't needed for this app).
3. Add your Groq API key (see below).
4. Visit **http://localhost/quote-generator/** in your browser.

## 🔑 Adding your Groq API key

1. Grab a free key from [console.groq.com/keys](https://console.groq.com/keys).
2. Open `config.php` and paste it in:
   ```php
   define('GROQ_API_KEY', 'gsk_your_key_here');
   ```
3. Reload the page. `fetch-quote.php` will now call Groq's chat completions
   endpoint, asking the model to generate a short, original quote (with a
   plausible author) matching the selected category.

Model used by default: `llama-3.1-8b-instant` (fast + cheap). You can swap
this in `config.php` (`GROQ_MODEL`) for any other model available on your
Groq account, e.g. `llama-3.3-70b-versatile` for higher quality.

## 🔄 How the fallback chain works (`fetch-quote.php`)

```
1. Groq LLM     (only if GROQ_API_KEY is set)     -> generates a fresh
                                                       category-matched quote
2. ZenQuotes    (no key needed)                   -> random, real quote
3. Offline JSON (assets/quotes/offline-quotes.json) -> always works
```

Every response is uniform JSON:
```json
{ "quote": "...", "author": "...", "source": "groq", "category": "inspirational" }
```
The small badge next to the title shows which source served the current quote
(`via Groq AI`, `via ZenQuotes`, or `offline fallback`).

### Why the fallback matters here specifically
Groq calls can occasionally fail to return clean JSON, hit a rate limit, or
time out — `try_groq()` treats any of those as a miss and falls through to
ZenQuotes, then to the offline file, so the UI never breaks even if the LLM
call has a hiccup.

## ✨ Features included

- **Category filter** — dropdown auto-triggers a new fetch on change; the
  category is passed straight into the prompt sent to Groq.
- **Copy to clipboard** — one-click copy of quote + author, with toast confirmation.
- **Fallback handling** — offline quotes kick in automatically if the API key
  is missing, invalid, rate-limited, or the service is down.
- **Loading spinner** — shown over the quote card while the AJAX call is in flight.
- **Fade transition** — smooth fade-out/fade-in when a new quote arrives.

## 🧩 Extending it further

- Adjust the `temperature` in `try_groq()` (in `fetch-quote.php`) for more or
  less varied quotes.
- Add more categories by extending `$allowedCategories` in `fetch-quote.php`
  and adding matching arrays to `offline-quotes.json`.
- Ask Groq for multiple quotes at once and cache them client-side to cut
  down on repeated API calls.
