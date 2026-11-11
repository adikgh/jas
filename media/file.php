<? include "../config/core.php";
	include "lib.php";

	// фото тек рұқсаты барға беріледі (тікелей URL жабық)
	core::need_page();
	list($w, $p) = media_where();
	$m = db::row("select m.* from media_assets m where m.id = ? and m.type = 'photo' and $w", array_merge([(int) @$_GET['id']], $p));
	$name = $m ? basename(substr($m['storage_key'], 4)) : '';
	$f = __DIR__.'/../assets/uploads/media/'.$name;
	if (!$m || !$name || !is_file($f)) { http_response_code(404); exit(); }
	$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
	header('Content-Type: '.$types[pathinfo($f, PATHINFO_EXTENSION)]);
	header('Cache-Control: private, max-age=86400');
	header('X-Content-Type-Options: nosniff');
	readfile($f);
