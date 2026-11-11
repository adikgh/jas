<? include "../config/core.php";

	core::need(['owner', 'admin']);

	function sub_get($id) {
		global $org_id;
		return db::row("select * from subscriptions where id = ? and organization_id = ?", [(int) $id, $org_id]);
	}


	// абонемент сату (төлем расталғанда басталады)
	if (isset($_GET['sell'])) {
		$p = db::row("select * from participants where id = ? and organization_id = ? and archived_at is null", [(int) fun::post('participant_id'), $org_id]);
		$pl = db::row("select * from plans where id = ? and organization_id = ? and active = 1", [(int) fun::post('plan_id'), $org_id]);
		$disc = (float) fun::post('discount', 0);
		$e = [];
		if (!$p) $e['participant_id'] = 'Қатысушыны таңдаңыз';
		if (!$pl) $e['plan_id'] = 'Тарифті таңдаңыз';
		if ($p && $pl && $p['direction'] != $pl['direction']) $e['plan_id'] = 'Тариф қатысушының бағытына сәйкес емес';
		if ($pl && ($disc < 0 || $disc > $pl['price'])) $e['discount'] = 'Жеңілдік қате';
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		if (db::val("select id from subscriptions where participant_id = ? and status = 'pending'", [(int) $p['id']])) fun::json(['ok' => false, 'mess' => 'Бұл қатысушыда төленбеген абонемент бар']);
		$id = db::ins("insert into subscriptions (organization_id, participant_id, plan_id, price, discount, sessions_limit, freeze_days_limit, created_by) values (?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, (int) $p['id'], (int) $pl['id'], $pl['price'], $disc, $pl['sessions_limit'], min(7, (int) $pl['freeze_days']), $user_id]);
		fun::audit('create', 'subscriptions', $id, null, ['participant_id' => $p['id'], 'plan' => $pl['name'], 'price' => $pl['price'], 'discount' => $disc]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// тоқтату: тек расталған төлемі жоқ абонемент
	if (isset($_GET['cancel'])) {
		$s = sub_get(fun::post('id'));
		if (!$s || $s['status'] == 'cancelled') fun::json(['ok' => false, 'mess' => 'Абонемент табылмады']);
		if (academy::sub_paid($s['id']) > 0) fun::json(['ok' => false, 'mess' => 'Төлемі бар абонементті тоқтату үшін алдымен төлемді сторно жасаңыз']);
		db::q("update subscriptions set status = 'cancelled' where id = ?", [(int) $s['id']]);
		db::q("update freezes set status = 'rejected', decided_by = ?, decided_at = now() where subscription_id = ? and status = 'requested'", [$user_id, (int) $s['id']]);
		fun::audit('cancel', 'subscriptions', $s['id'], ['status' => $s['status']], ['status' => 'cancelled']);
		fun::json(['ok' => true]);
	}


	// заморозка: жалпы лимит (7 күн) асып кетпейді, күндер қабаттаспайды
	if (isset($_GET['freeze_add'])) {
		$s = sub_get(fun::post('id'));
		$start = fun::post('start'); $days = (int) fun::post('days'); $reason = fun::post('reason');
		$approve = fun::post('approve') == '1';
		if (!$s || !in_array($s['status'], ['active', 'frozen'])) fun::json(['ok' => false, 'mess' => 'Тек белсенді абонементті тоқтатуға болады']);
		if (!fun::valid_date($start) || $start < $s['start_date'] || $start > $s['end_date']) fun::json(['ok' => false, 'mess' => 'Басталу күні абонемент мерзімінде болуы керек']);
		if ($days < 1) fun::json(['ok' => false, 'mess' => 'Күн санын жазыңыз']);
		if (academy::sub_frozen_days($s['id']) + $days > (int) $s['freeze_days_limit']) fun::json(['ok' => false, 'mess' => 'Заморозка лимиті: '.$s['freeze_days_limit'].' күн. Қалғаны: '.max(0, $s['freeze_days_limit'] - academy::sub_frozen_days($s['id']))]);
		$end = date('Y-m-d', strtotime("$start +".($days - 1)." day"));
		if (db::val("select id from freezes where subscription_id = ? and status in ('approved', 'requested') and start_date <= ? and end_date >= ?", [(int) $s['id'], $end, $start])) fun::json(['ok' => false, 'mess' => 'Бұл күндерге заморозка бар']);
		$id = db::ins("insert into freezes (organization_id, subscription_id, start_date, end_date, days, reason, status, requested_by, decided_by, decided_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, (int) $s['id'], $start, $end, $days, $reason ?: null, $approve ? 'approved' : 'requested', $user_id, $approve ? $user_id : null, $approve ? date('Y-m-d H:i:s') : null]);
		if ($approve) academy::sub_refresh($s['id']);
		fun::audit('freeze', 'subscriptions', $s['id'], null, ['freeze_id' => $id, 'start' => $start, 'days' => $days, 'approved' => $approve]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// өтінішті бекіту / бас тарту
	if (isset($_GET['freeze_decide'])) {
		$f = db::row("select f.*, s.freeze_days_limit from freezes f join subscriptions s on s.id = f.subscription_id where f.id = ? and f.organization_id = ?", [(int) fun::post('id'), $org_id]);
		$d = fun::post('d');
		if (!$f || $f['status'] != 'requested') fun::json(['ok' => false, 'mess' => 'Өтініш табылмады']);
		if (!in_array($d, ['approve', 'reject'])) fun::json(['ok' => false, 'mess' => 'Шешім қате']);
		if ($d == 'approve') {
			$used = (int) db::val("select coalesce(sum(days), 0) from freezes where subscription_id = ? and status = 'approved'", [(int) $f['subscription_id']]);
			if ($used + $f['days'] > $f['freeze_days_limit']) fun::json(['ok' => false, 'mess' => 'Лимит асып кетеді']);
		}
		db::q("update freezes set status = ?, decided_by = ?, decided_at = now() where id = ?", [$d == 'approve' ? 'approved' : 'rejected', $user_id, (int) $f['id']]);
		academy::sub_refresh($f['subscription_id']);
		fun::audit('freeze_'.$d, 'subscriptions', $f['subscription_id'], null, ['freeze_id' => $f['id']]);
		fun::json(['ok' => true]);
	}


	// тариф қосу
	if (isset($_GET['plan_add'])) {
		$name = fun::post('name'); $dir = fun::post('direction'); $price = (float) fun::post('price');
		$lim = fun::post('sessions_limit') === '' ? null : (int) fun::post('sessions_limit');
		$fd = (int) fun::post('freeze_days', 7);
		if (mb_strlen($name) < 2) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if (!in_array($dir, ['kids', 'adult'])) fun::json(['ok' => false, 'mess' => 'Бағыт қате']);
		if ($price <= 0) fun::json(['ok' => false, 'mess' => 'Бағасын жазыңыз']);
		if ($lim !== null && ($lim < 1 || $lim > 100)) fun::json(['ok' => false, 'mess' => 'Жаттығу саны қате']);
		if ($fd < 0 || $fd > 7) fun::json(['ok' => false, 'mess' => 'Заморозка 0–7 күн']);
		if (db::val("select id from plans where organization_id = ? and name = ?", [$org_id, $name])) fun::json(['ok' => false, 'mess' => 'Мұндай тариф бар']);
		$id = db::ins("insert into plans (organization_id, name, direction, price, sessions_limit, freeze_days) values (?, ?, ?, ?, ?, ?)", [$org_id, $name, $dir, $price, $lim, $fd]);
		fun::audit('create', 'plans', $id);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// Kaspi RED комиссиясы (тарифке немесе жалпы). Бос мән = тарифтікі өшіп, жалпысы қолданылады
	if (isset($_GET['plan_fee'])) {
		$pid = (int) fun::post('plan_id');
		$v = fun::post('fee');
		if ($pid && !db::val("select id from plans where id = ? and organization_id = ?", [$pid, $org_id])) fun::json(['ok' => false, 'mess' => 'Тариф табылмады']);
		if ($v !== '' && (!is_numeric($v) || $v < 0 || $v > 50)) fun::json(['ok' => false, 'mess' => 'Пайыз 0–50 аралығында']);
		$cur = db::val("select id from payment_method_fees where organization_id = ? and method = 'kaspi_red' and ".($pid ? "plan_id = ".$pid : "plan_id is null"), [$org_id]);
		if ($v === '') { if ($cur && $pid) db::q("delete from payment_method_fees where id = ?", [(int) $cur]); }
		elseif ($cur) db::q("update payment_method_fees set fee_percent = ? where id = ?", [(float) $v, (int) $cur]);
		else db::q("insert into payment_method_fees (organization_id, method, plan_id, fee_percent) values (?, 'kaspi_red', ?, ?)", [$org_id, $pid ?: null, (float) $v]);
		fun::audit('fee', 'plans', $pid ?: null, null, ['kaspi_red' => $v]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
