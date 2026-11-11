<?php
	// Күн сайынғы автоматты тапсырмалар (хостингтегі Cron арқылы, күніне 1 рет, мысалы 08:00):
	//   php /home/ҚОЛДАНУШЫ/public_html/tools/cron.php
	// Не істейді: абонементі 3 күнде бітетіндерге және туған күн туралы хабарлама (қайталанбайды).
	if (php_sapi_name() != 'cli') { http_response_code(403); exit(); }
	require __DIR__.'/../config/core.php';
	$n = 0;
	foreach (db::all("select id from organizations") as $o) { academy::notify_daily((int) $o['id']); $n++; }
	echo date('Y-m-d H:i')." cron ok, ұйым: $n\n";
