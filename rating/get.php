<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);

	// бапкер тек өз тобындағыларды марапаттайды
	function part_get($id) {
		$p = fun::participant($id);
		return $p && !$p['archived_at'] ? $p : null;
	}


	// бапкер марапаты (+ачивка ұпайы, әдепкі 30)
	if (isset($_GET['award'])) {
		$p = part_get(fun::post('participant_id'));
		$a = db::row("select * from achievements where id = ? and organization_id = ?", [(int) fun::post('achievement_id'), $org_id]);
		$comment = fun::post('comment');
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушыны таңдаңыз']);
		if (!$a) fun::json(['ok' => false, 'mess' => 'Марапатты таңдаңыз']);
		$id = db::tx(function () use ($p, $a, $comment, $org_id, $user_id) {
			$pa = db::ins("insert into participant_achievements (organization_id, participant_id, achievement_id, awarded_by, comment) values (?, ?, ?, ?, ?)", [$org_id, (int) $p['id'], (int) $a['id'], $user_id, $comment ?: null]);
			db::q("insert into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id, comment, created_by) values (?, ?, ?, 'coach_award', ?, 'achievement', ?, ?, ?)",
				[$org_id, (int) $p['id'], $p['direction'], (int) ($a['points'] ?: academy::$points['coach_award']), $pa, $a['name'].($comment ? ': '.$comment : ''), $user_id]);
			return $pa;
		});
		fun::audit('award', 'participants', $p['id'], null, ['achievement' => $a['name'], 'comment' => $comment]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// жеке прогресс: айына бір рет (+20)
	if (isset($_GET['progress'])) {
		$p = part_get(fun::post('participant_id'));
		$comment = fun::post('comment');
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушыны таңдаңыз']);
		if (mb_strlen($comment) < 3) fun::json(['ok' => false, 'mess' => 'Прогресті сипаттаңыз']);
		$src = (int) date('Ym');
		if (db::val("select id from rating_events where participant_id = ? and rule = 'progress' and source_type = 'month' and source_id = ?", [(int) $p['id'], $src])) fun::json(['ok' => false, 'mess' => 'Бұл айда прогресс ұпайы берілген']);
		db::q("insert into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id, comment, created_by) values (?, ?, ?, 'progress', ?, 'month', ?, ?, ?)",
			[$org_id, (int) $p['id'], $p['direction'], academy::$points['progress'], $src, $comment, $user_id]);
		fun::audit('progress', 'participants', $p['id'], null, ['comment' => $comment]);
		fun::json(['ok' => true]);
	}


	// қолмен түзету (тек иесі/әкімші)
	if (isset($_GET['manual'])) {
		core::need(['owner', 'admin']);
		$p = part_get(fun::post('participant_id'));
		$pts = (int) fun::post('points');
		$comment = fun::post('comment');
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушыны таңдаңыз']);
		if (!$pts || abs($pts) > 200) fun::json(['ok' => false, 'mess' => 'Ұпай −200…200']);
		if (mb_strlen($comment) < 3) fun::json(['ok' => false, 'mess' => 'Себебін жазыңыз']);
		$src = (int) db::val("select coalesce(max(source_id), 0) + 1 from rating_events where participant_id = ? and rule = 'manual'", [(int) $p['id']]);
		db::q("insert into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id, comment, created_by) values (?, ?, ?, 'manual', ?, 'manual', ?, ?, ?)",
			[$org_id, (int) $p['id'], $p['direction'], $pts, $src, $comment, $user_id]);
		fun::audit('rating_manual', 'participants', $p['id'], null, ['points' => $pts, 'comment' => $comment]);
		fun::json(['ok' => true]);
	}


	// марапатты қайтарып алу (тек иесі/әкімші; автоматты ұпайлар қатысу арқылы түзетіледі)
	if (isset($_GET['revoke'])) {
		core::need(['owner', 'admin']);
		$r = db::row("select * from rating_events where id = ? and organization_id = ? and rule in ('coach_award', 'progress', 'manual')", [(int) fun::post('id'), $org_id]);
		if (!$r) fun::json(['ok' => false, 'mess' => 'Жазба табылмады']);
		db::tx(function () use ($r) {
			db::q("delete from rating_events where id = ?", [(int) $r['id']]);
			if ($r['source_type'] == 'achievement') db::q("delete from participant_achievements where id = ?", [(int) $r['source_id']]);
		});
		fun::audit('rating_revoke', 'participants', $r['participant_id'], $r, null);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
