<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);


	// бағалау: бапкер тек өз тобындағыны (fun::participant scope)
	if (isset($_GET['add'])) {
		$p = fun::participant((int) fun::post('id'));
		$day = fun::post('date');
		$comment = fun::post('comment');
		$sc = isset($_POST['scores']) && is_array($_POST['scores']) ? $_POST['scores'] : [];
		if (!$p || $p['archived_at']) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		if (!fun::valid_date($day) || $day > $date) fun::json(['ok' => false, 'mess' => 'Күні қате']);
		$crit = array_flip(array_map('intval', array_column(db::all("select id from assessment_criteria where organization_id = ? and active = 1", [$org_id]), 'id')));
		$set = [];
		foreach ($sc as $cid => $v) {
			$cid = (int) $cid; $v = (int) $v;
			if (!isset($crit[$cid])) fun::json(['ok' => false, 'mess' => 'Көрсеткіш қате']);
			if ($v < 1 || $v > 10) fun::json(['ok' => false, 'mess' => 'Балл 1–10']);
			$set[$cid] = $v;
		}
		if (count($set) < 3) fun::json(['ok' => false, 'mess' => 'Кемінде 3 көрсеткішті бағалаңыз']);
		$prev = db::val("select round(avg(s.score), 2) from assessment_scores s where s.assessment_id = (select id from player_assessments where participant_id = ? order by assessed_at desc, id desc limit 1)", [(int) $p['id']]);

		$aid = db::tx(function () use ($p, $day, $comment, $set, $org_id, $user_id) {
			$aid = db::ins("insert into player_assessments (organization_id, participant_id, coach_id, assessed_at, comment) values (?, ?, ?, ?, ?)", [$org_id, (int) $p['id'], $user_id, $day, $comment ?: null]);
			foreach ($set as $cid => $v) db::q("insert into assessment_scores (assessment_id, criterion_id, score) values (?, ?, ?)", [$aid, $cid, $v]);
			return $aid;
		});
		$avg = round(array_sum($set) / count($set), 2);
		// орташа өссе — айына бір рет +20 (жеке прогресс)
		$bonus = false;
		if ($prev !== null && $avg > (float) $prev) {
			$bonus = (bool) db::q("insert ignore into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id, comment, created_by) values (?, ?, ?, 'progress', ?, 'month', ?, ?, ?)",
				[$org_id, (int) $p['id'], $p['direction'], academy::$points['progress'], (int) date('Ym'), 'Бағалау өсті: '.$prev.' → '.$avg, $user_id]);
		}
		academy::notify_participant($org_id, $p['id'], 'assessment', 'Жаңа бағалау: '.$p['first_name'], 'Орташа балл: '.$avg, '/progress/item.php?id='.$p['id'], 'ass_'.$aid);
		fun::audit('assessment', 'participants', $p['id'], null, ['assessment_id' => $aid, 'avg' => $avg]);
		fun::json(['ok' => true, 'id' => $aid, 'avg' => $avg, 'bonus' => $bonus]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
