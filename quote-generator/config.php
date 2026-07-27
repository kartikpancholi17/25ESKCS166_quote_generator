<?php

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envVars = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envVars as $line) {
        if (str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $_ENV[$name] = $value;
        putenv(sprintf('%s=%s', $name, $value));
    }
}

define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');

// Groq uses an OpenAI-compatible chat completions endpoint.
define('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');

// Any current Groq-hosted model works; llama-3.1-8b-instant is fast + cheap.
define('GROQ_MODEL', 'llama-3.1-8b-instant');

define('ZENQUOTES_ENDPOINT', 'https://zenquotes.io/api/random');

// Timeout (seconds) for outbound requests to the external API.
define('API_TIMEOUT', 8);
