<?php

declare(strict_types=1);

// Small helper to build WhatsApp redirect URLs safely.

function build_whatsapp_url(string $phoneNumberDigits, string $message): string
{
    $base = 'https://wa.me/' . ltrim($phoneNumberDigits, '+');
    // WhatsApp expects text in querystring; URL-encode message.
    return $base . '?text=' . rawurlencode($message);
}

