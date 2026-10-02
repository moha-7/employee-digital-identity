<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/(?:data|backups)(?:/|$)#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

$localFile = __DIR__ . $path;
if ($path !== '/' && is_file($localFile)) {
    return false;
}

if (preg_match('#^/vcard/([A-Za-z0-9_-]+)/?$#', $path, $matches)) {
    $_GET['vcard'] = $matches[1];
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('#^/([A-Za-z0-9_-]+)/?$#', $path, $matches)) {
    $_GET['employee'] = $matches[1];
    require __DIR__ . '/index.php';
    return true;
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo 'Not found';
return true;
