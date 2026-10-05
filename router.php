<?php
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$segments = [];
foreach (explode('/', $path) as $segment) {
    if ($segment === '' || $segment === '.') {
        continue;
    }
    if ($segment === '..') {
        array_pop($segments);
        continue;
    }
    $segments[] = $segment;
}
$path = '/' . implode('/', $segments);
if (preg_match('#^/(?:includes(?:/|$)|config(?:\.php|\.example\.php)$|database\.sql$)#i', $path)) {
    http_response_code(404);
    exit('Bulunamadı.');
}

return false;
