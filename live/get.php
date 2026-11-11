<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);


	if (isset($_GET['add'])) {
		$title = fun::post('title'); $url = fun::post('url'); $mid = (int) fun::post('match_id');
		$at = str_replace('T', ' ', fun::post('starts_at')); if (strlen($at) == 16) $at .= ':00';
		$host = strtolower((string) parse_url($url, PHP_URL_HOST));
		$platform = preg_match('/(^|\.)(youtube\.com|youtu\.be)$/', $host) ? 'youtube' : (preg_match('/(^|\.)instagram\.com$/', $host) ? 'instagram' : (preg_match('/(^|\.)tiktok\.com$/', $host) ? 'tiktok' : null));
		if (mb_strlen($title) < 3) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if (!$platform || strpos($url, 'https://') !== 0) fun::json(['ok' => false, 'mess' => 'Тек YouTube, Instagram немесе TikTok сілтемесі (https://)']);
		if (!strtotime($at) || date('Y-m-d H:i:s', strtotime($at)) !== $at) fun::json(['ok' => false, 'mess' => 'Уақытын жазыңыз']);
		if ($mid && !db::val("select id from matches where id = ? and organization_id = ?", [$mid, $org_id])) fun::json(['ok' => false, 'mess' => 'Матч табылмады']);
		$id = db::ins("insert into live_streams (organization_id, match_id, platform, title, url, youtube_id, starts_at) values (?, ?, ?, ?, ?, ?, ?)",
			[$org_id, $mid ?: null, $platform, $title, $url, $platform == 'youtube' ? academy::yt_id($url) : null, $at]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['status'])) {
		$l = db::row("select * from live_streams where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		$s = fun::post('status');
		if (!$l || !in_array($s, ['live', 'ended'], true)) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		db::q("update live_streams set status = ? where id = ?", [$s, (int) $l['id']]);
		// эфир басталды — ата-аналар мен қатысушыларға хабарлама
		if ($s == 'live') foreach (db::all("select distinct user_id from memberships where organization_id = ? and active = 1 and role in ('parent', 'participant')", [$org_id]) as $u)
			academy::notify_user($org_id, $u['user_id'], 'match', '● Тікелей эфир басталды', $l['title'], '/live/', 'live_'.$l['id']);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
