<?php
// PHP built-in server үшін router (тесттер мен жергілікті іске қосу). Apache/OSPanel-де керек емес.
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(config|sql|tests|tools|docs|assets/uploads/media)(/|$)#', $p)) { http_response_code(403); exit; }
$f = $_SERVER['DOCUMENT_ROOT'].$p;
if (is_dir($f)) { $f = rtrim($f, '/').'/index.php'; if (is_file($f)) { $_SERVER['SCRIPT_NAME'] = $p; chdir(dirname($f)); require $f; return true; } }
if (is_file($f) && substr($f, -4) == '.php') { chdir(dirname($f)); require $f; return true; }
return false;
