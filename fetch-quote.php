<?php
/**
 * fetch-quote.php
 * ---------------------------------------------------------------
 * Backend proxy: the browser never talks to any external API
 * directly, so no API key is ever exposed in client-side JS.
 *
 * Flow:
 *   1. Read requested category from GET.
 *   2. If a Groq API key is configured -> ask Groq's LLM to generate
 *      a short quote + author matching that category (JSON response).
 *   3. Else / on failure -> try ZenQuotes (no key needed, random only).
 *   4. Else / on failure -> serve a quote from the local offline file.
 *
 * Always responds with JSON: { quote, author, source, category }
 * ---------------------------------------------------------------
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// ---- 1. Read + sanitize input -----------------------------------------
$allowedCategories = ['inspirational', 'business', 'life', 'success', 'happiness', 'wisdom', 'love', 'humor'];
$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : 'inspirational';
if (!in_array($category, $allowedCategories, true)) {
    $category = 'inspirational';
}

/**
 * Small cURL helper for GET requests.
 */
function curl_get(string $url, array $headers = [], int $timeout = API_TIMEOUT): array
{
    return curl_request('GET', $url, null, $headers, $timeout);
}

/**
 * Generic cURL helper (GET or POST with a JSON body).
 * Returns [body, httpCode].
 */
function curl_request(string $method, string $url, ?string $jsonBody, array $headers, int $timeout): array
{
    if (!function_exists('curl_init')) {
        $opts = [
            'http' => [
                'method'  => $method,
                'timeout' => $timeout,
                'header'  => implode("\r\n", $headers),
                'content' => $jsonBody ?? '',
            ],
        ];
        $ctx  = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);
        return [$body ?: null, $body ? 200 : 0];
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = $jsonBody;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return [null, 0];
    }
    return [$body, $code];
}

/**
 * Try Groq: ask the LLM to generate a short quote + author for the category.
 * We instruct it to reply with STRICT JSON only, then parse that JSON.
 */
function try_groq(string $category): ?array
{
    if (GROQ_API_KEY === '') {
        return null; // not configured, skip silently
    }

    $systemPrompt = 'You generate short, original, high-quality quotes. '
        . 'Reply with ONLY valid JSON, no markdown, no code fences, no extra text, '
        . 'in exactly this shape: {"quote": "...", "author": "..."}. '
        . 'The "author" field should be a plausible attributed author name '
        . '(a real historical figure if you are quoting/paraphrasing a known idea, '
        . 'otherwise "Unknown"). Keep the quote under 30 words.';

    $userPrompt = "Give me one {$category} quote.";

    $payload = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.9,
        'max_tokens'  => 150,
    ]);

    [$body, $code] = curl_request('POST', GROQ_ENDPOINT, $payload, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ], API_TIMEOUT);

    if ($code !== 200 || !$body) {
        return null;
    }

    $data = json_decode($body, true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    if (!$content) {
        return null;
    }

    // Strip accidental code fences just in case the model adds them.
    $content = trim($content);
    $content = preg_replace('/^```(json)?/i', '', $content);
    $content = preg_replace('/```$/', '', $content);
    $content = trim($content);

    $parsed = json_decode($content, true);
    if (!is_array($parsed) || empty($parsed['quote'])) {
        return null;
    }

    return [
        'quote'  => $parsed['quote'],
        'author' => $parsed['author'] ?? 'Unknown',
        'source' => 'groq',
    ];
}

/**
 * Try ZenQuotes (no key required, category filtering not supported).
 */
function try_zenquotes(): ?array
{
    [$body, $code] = curl_get(ZENQUOTES_ENDPOINT);

    if ($code !== 200 || !$body) {
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data[0]['q'])) {
        return null;
    }

    return [
        'quote'  => $data[0]['q'],
        'author' => $data[0]['a'] ?? 'Unknown',
        'source' => 'zenquotes',
    ];
}

/**
 * Local offline fallback — always succeeds if the JSON file is present.
 */
function offline_quote(string $category): array
{
    $path = __DIR__ . '/assets/quotes/offline-quotes.json';
    $pool = [];

    if (is_file($path)) {
        $all = json_decode((string) file_get_contents($path), true);
        if (is_array($all) && !empty($all[$category])) {
            $pool = $all[$category];
        }
    }

    if (empty($pool)) {
        $pool = [['quote' => 'Every day is a fresh start.', 'author' => 'Unknown']];
    }

    $pick = $pool[array_rand($pool)];

    return [
        'quote'  => $pick['quote'],
        'author' => $pick['author'],
        'source' => 'offline-fallback',
    ];
}

// ---- 2. Try providers in order, first success wins --------------------
$result = try_groq($category)
    ?? try_zenquotes()
    ?? offline_quote($category);

$result['category'] = $category;

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
