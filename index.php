<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Random Quote Generator</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="app-body">
  <main class="app-main">
    <div class="quote-card">
      <div class="glow glow-top-right"></div>
      <div class="glow glow-bottom-left"></div>
      <div>
        <div class="header">
          <h1 class="title">Random Quote Generator</h1>
          <span id="source-badge" class="source-badge hidden"></span>
        </div>
        <div class="category-section">
          <label for="category" class="category-label">Category</label>
          <select id="category" class="category-select">
            <option value="inspirational">Inspirational</option>
            <option value="business">Business</option>
            <option value="life">Life</option>
            <option value="success">Success</option>
            <option value="happiness">Happiness</option>
            <option value="wisdom">Wisdom</option>
            <option value="love">Love</option>
            <option value="humor">Humor</option>
          </select>
        </div>
        <div class="quote-display">
          <div id="loader" class="loader hidden">
            <div class="spinner"></div>
          </div>
          <p id="quote-text" class="quote-text">“Click the button below to get your first quote.”</p>
          <p id="quote-author" class="quote-author">— Random Quote Generator</p>
        </div>
        <div class="actions">
          <button id="generate-btn" class="btn btn-primary">Generate Quote</button>
          <button id="copy-btn" class="btn btn-secondary">📋 Copy</button>
        </div>
        <div id="toast" class="toast hidden">Copied to clipboard!</div>
      </div>
    </div>
    <p class="footer-text">Built with PHP + jQuery · quotes proxied securely through <code>fetch-quote.php</code></p>
  </main>
<script src="assets/js/app.js"></script>
</body>
</html>
