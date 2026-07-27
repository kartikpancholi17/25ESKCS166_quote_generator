$(function () {
  const $btn      = $('#generate-btn');
  const $copyBtn  = $('#copy-btn');
  const $category = $('#category');
  const $quote    = $('#quote-text');
  const $author   = $('#quote-author');
  const $loader   = $('#loader');
  const $badge    = $('#source-badge');
  const $toast    = $('#toast');

  function fetchQuote() {
    const category = $category.val();

    $loader.removeClass('hidden');
    $btn.prop('disabled', true).addClass('opacity-60 cursor-not-allowed');

    $.ajax({
      url: 'fetch-quote.php',
      method: 'GET',
      dataType: 'json',
      data: { category: category },
      timeout: 8000
    })
      .done(function (res) {
        // Fade out, swap text, fade back in
        $quote.add($author).addClass('fading');

        setTimeout(function () {
          $quote.text('“' + res.quote + '”');
          $author.text('— ' + res.author);

          if (res.source === 'offline-fallback') {
            $badge.text('offline fallback').removeClass('hidden');
          } else if (res.source === 'zenquotes') {
            $badge.text('via ZenQuotes').removeClass('hidden');
          } else if (res.source === 'groq') {
            $badge.text('via Groq AI').removeClass('hidden');
          } else {
            $badge.addClass('hidden');
          }

          $quote.add($author).removeClass('fading');
        }, 200);
      })
      .fail(function () {
        $quote.add($author).removeClass('fading');
        $quote.text('“Something went wrong fetching a quote. Please try again.”');
        $author.text('— System');
        $badge.addClass('hidden');
      })
      .always(function () {
        $loader.addClass('hidden');
        $btn.prop('disabled', false).removeClass('opacity-60 cursor-not-allowed');
      });
  }

  function copyQuote() {
    const text = $quote.text() + ' ' + $author.text();

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(showToast);
    } else {
      // Fallback for older browsers
      const $tmp = $('<textarea>').val(text).appendTo('body').select();
      document.execCommand('copy');
      $tmp.remove();
      showToast();
    }
  }

  function showToast() {
    $toast.removeClass('hidden show');
    // restart animation
    void $toast[0].offsetWidth;
    $toast.addClass('show');
  }

  $btn.on('click', fetchQuote);
  $category.on('change', fetchQuote);
  $copyBtn.on('click', copyQuote);

  // Load an initial quote on page load
  fetchQuote();
});
