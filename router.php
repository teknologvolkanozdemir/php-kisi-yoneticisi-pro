<?php
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('#^/(?:includes(?:/|$)|config(?:\.php|\.example\.php)$|database\.sql$)#i', $path)) {
    http_response_code(404);
    exit('Bulunamadı.');
}

return false;
