<? include "../config/core.php";

	core::need(['parent', 'participant']);


	// ата-ана / қатысушы заморозкаға өтініш береді, әкімші бекітеді
	if (isset($_GET['freeze_request'])) {
		$s = db::row("select * from subscriptions where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		if (!$s || !fun::participant($s['participant_id'])) fun::json(['ok' => false, 'mess' => 'Абонемент табылмады']);
		$start = fun::post('start'); $days = (int) fun::post('days'); $reason = fun::post('reason');
		if (!in_array($s['status'], ['active', 'frozen'])) fun::json(['ok' => false, 'mess' => 'Абонемент белсенді емес']);
		if (!fun::valid_date($start) || $start < $date || $start > $s['end_date']) fun::json(['ok' => false, 'mess' => 'Басталу күні қате']);
		if ($days < 1) fun::json(['ok' => false, 'mess' => 'Күн санын жазыңыз']);
		if (mb_strlen($reason) < 3) fun::json(['ok' => false, 'mess' => 'Себебін жазыңыз']);
		if (db::val("select id from freezes where subscription_id = ? and status = 'requested'", [(int) $s['id']])) fun::json(['ok' => false, 'mess' => 'Өтініш қаралуда']);
		if (academy::sub_frozen_days($s['id']) + $days > (int) $s['freeze_days_limit']) fun::json(['ok' => false, 'mess' => 'Заморозка лимиті: '.$s['freeze_days_limit'].' күн']);
		$end = date('Y-m-d', strtotime("$start +".($days - 1)." day"));
		if (db::val("select id from freezes where subscription_id = ? and status = 'approved' and start_date <= ? and end_date >= ?", [(int) $s['id'], $end, $start])) fun::json(['ok' => false, 'mess' => 'Бұл күндерге заморозка бар']);
		$id = db::ins("insert into freezes (organization_id, subscription_id, start_date, end_date, days, reason, requested_by) values (?, ?, ?, ?, ?, ?, ?)", [$org_id, (int) $s['id'], $start, $end, $days, $reason, $user_id]);
		foreach (db::all("select user_id from memberships where organization_id = ? and role in ('owner', 'admin') and active = 1", [$org_id]) as $u)
			academy::notify_user($org_id, $u['user_id'], 'other', 'Заморозка өтініші', $days.' күн, '.date('d.m', strtotime($start)).'. '.$reason, '/subscriptions/', 'frz_'.$id);
		fun::audit('freeze_request', 'subscriptions', $s['id'], null, ['freeze_id' => $id, 'days' => $days]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
