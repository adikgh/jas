<? include "../config/core.php";

	core::need(['owner', 'admin']);
	function dt($v) { $v = str_replace('T', ' ', $v); if (strlen($v) == 16) $v .= ':00'; return strtotime($v) && date('Y-m-d H:i:s', strtotime($v)) === $v ? $v : false; }


	if (isset($_GET['lead_add'])) {
		$name = fun::post('name'); $phone = fun::phone_clean(fun::post('phone'));
		$dir = fun::post('direction'); $src = fun::post('source'); $cid = (int) fun::post('campaign_id'); $age = (int) fun::post('child_age');
		if (mb_strlen($name) < 2) fun::json(['ok' => false, 'mess' => 'Атын жазыңыз']);
		if (!$phone) fun::json(['ok' => false, 'mess' => 'Телефон қате']);
		if ($dir !== '' && !in_array($dir, ['kids', 'adult'], true)) fun::json(['ok' => false, 'mess' => 'Бағыт қате']);
		if (!in_array($src, ['instagram', 'tiktok', 'threads', 'whatsapp', 'referral', 'walk_in', 'other'], true)) $src = 'other';
		if ($cid && !db::val("select id from campaigns where id = ? and organization_id = ?", [$cid, $org_id])) fun::json(['ok' => false, 'mess' => 'Кампания табылмады']);
		if (db::val("select id from leads where organization_id = ? and phone = ? and status not in ('converted', 'lost')", [$org_id, $phone])) fun::json(['ok' => false, 'mess' => 'Бұл нөмірден белсенді өтінім бар']);
		$id = db::ins("insert into leads (organization_id, name, phone, direction, child_age, source, campaign_id, comment) values (?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, $name, $phone, $dir ?: null, $age > 0 && $age < 90 ? $age : null, $src, $cid ?: null, fun::post('comment') ?: null]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['lead_status'])) {
		$l = db::row("select * from leads where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		$s = fun::post('status');
		if (!$l || !in_array($s, ['new', 'contacted', 'trial_booked', 'trial_done', 'converted', 'lost'], true)) fun::json(['ok' => false, 'mess' => 'Қате']);
		db::q("update leads set status = ? where id = ?", [$s, (int) $l['id']]);
		fun::audit('lead_'.$s, 'leads', $l['id'], ['status' => $l['status']], ['status' => $s]);
		fun::json(['ok' => true]);
	}

	if (isset($_GET['campaign_add'])) {
		$name = fun::post('name'); $ch = fun::post('channel'); $b = (float) fun::post('budget');
		$sd = fun::post('start_date'); $ed = fun::post('end_date');
		if (mb_strlen($name) < 2) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if (!in_array($ch, ['instagram', 'tiktok', 'threads', 'other'], true)) fun::json(['ok' => false, 'mess' => 'Арна қате']);
		if ($b < 0 || ($sd && !fun::valid_date($sd)) || ($ed && !fun::valid_date($ed))) fun::json(['ok' => false, 'mess' => 'Мәлімет қате']);
		$id = db::ins("insert into campaigns (organization_id, name, channel, budget, start_date, end_date) values (?, ?, ?, ?, ?, ?)", [$org_id, $name, $ch, $b, $sd ?: null, $ed ?: null]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['content_add'])) {
		$t = fun::post('title'); $ch = fun::post('channel'); $at = fun::post('publish_at'); $a = (int) fun::post('assignee_id');
		if (mb_strlen($t) < 2) fun::json(['ok' => false, 'mess' => 'Тақырыбын жазыңыз']);
		if (!in_array($ch, ['instagram', 'tiktok', 'threads'], true)) fun::json(['ok' => false, 'mess' => 'Арна қате']);
		if ($at !== '' && !dt($at)) fun::json(['ok' => false, 'mess' => 'Уақыт қате']);
		if ($a && !db::val("select user_id from memberships where user_id = ? and organization_id = ?", [$a, $org_id])) fun::json(['ok' => false, 'mess' => 'Жауапты табылмады']);
		$id = db::ins("insert into content_items (organization_id, channel, title, publish_at, assignee_id) values (?, ?, ?, ?, ?)", [$org_id, $ch, $t, $at !== '' ? dt($at) : null, $a ?: null]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['content_status'])) {
		$s = fun::post('status');
		if (!in_array($s, ['idea', 'in_work', 'ready', 'published'], true)) fun::json(['ok' => false, 'mess' => 'Қате']);
		db::q("update content_items set status = ? where id = ? and organization_id = ?", [$s, (int) fun::post('id'), $org_id]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
