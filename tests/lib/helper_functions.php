<?php

function getSetCookieHeaders(): array
{
    $headers = [];
    foreach (xdebug_get_headers() as $header) {
        if (str_starts_with($header, "Set-Cookie:")) {
            $headers[] = $header;
        }
    }
    return $headers;
}

function getSetCookieHeader(string $name): ?string
{
    $headers = getSetCookieHeaders();
    foreach ($headers as $header) {
        $cookieContent = str_replace("Set-Cookie: ", "", $header);
        if (str_starts_with($cookieContent, "$name=")) {
            return $header;
        }
    }
    return null;
}
