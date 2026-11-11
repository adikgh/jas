<? include "../config/core.php";

	// төлемді тек иесі мен әкімші енгізеді / түзетеді
	core::need(['owner', 'admin']);

	// абонементтің қалған сомасы (расталған + расталмаған бөлінгендер шегеріледі — артық төлем болмайды)
	function sub_rest($sub_id) {
		return (float) db::val("select s.price - s.discount - coalesce((select sum(pa.amount) from payment_allocations pa join payments py on py.id = pa.payment_id and py.status in ('confirmed', 'pending') where pa.subscription_id = s.id), 0) from subscriptions s where s.id = ?", [(int) $sub_id]);
	}
	function pay_get($id) {
		global $org_id;
		return db::row("select * from payments where id = ? and organization_id = ?", [(int) $id, $org_id]);
	}
	function pay_subs($pay_id) {
		return array_column(db::all("select subscription_id from payment_allocations where payment_id = ?", [(int) $pay_id]), 'subscription_id');
	}


	// қатысушының ашық абонементтері
	if (isset($_GET['subs'])) {
		$pid = (int) fun::post('participant_id');
		$list = db::all("select s.id, s.status, s.price - s.discount total, pl.name, pl.id plan_id from subscriptions s join plans pl on pl.id = s.plan_id where s.participant_id = ? and s.organization_id = ? and s.status <> 'cancelled' order by s.id desc", [$pid, $org_id]);
		$res = [];
		foreach ($list as $s) {
			$rest = sub_rest($s['id']);
			if ($rest <= 0) continue;
			$res[] = ['id' => (int) $s['id'], 'name' => $s['name'].' · '.t::w('sub_'.$s['status']), 'rest' => $rest];
		}
		fun::json(['ok' => true, 'list' => $res]);
	}


	// төлем енгізу
	if (isset($_GET['add'])) {
		$idem = preg_replace('/[^a-zA-Z0-9_-]/', '', fun::post('idem'));
		$pid = (int) fun::post('participant_id');
		$sid = (int) fun::post('subscription_id');
		$amount = round((float) fun::post('amount'), 2);
		$method = fun::post('method');
		$ref = fun::post('external_ref');
		$paid_at = str_replace('T', ' ', fun::post('paid_at'));
		$status = fun::post('status') == 'pending' ? 'pending' : 'confirmed';
		$comment = fun::post('comment');

		if (strlen($idem) < 16) fun::json(['ok' => false, 'mess' => 'Бетті жаңартып, қайта көріңіз']);
		// сол форма екі рет жіберілсе — екінші төлем жасалмайды
		$dup = db::val("select id from payments where organization_id = ? and idempotency_key = ?", [$org_id, $idem]);
		if ($dup) fun::json(['ok' => true, 'id' => (int) $dup, 'dup' => true]);

		$e = [];
		$sub = db::row("select * from subscriptions where id = ? and participant_id = ? and organization_id = ? and status <> 'cancelled'", [$sid, $pid, $org_id]);
		if (!$sub) $e['subscription_id'] = 'Абонементті таңдаңыз';
		if ($amount <= 0) $e['amount'] = 'Соманы жазыңыз';
		if (!in_array($method, ['kaspi', 'kaspi_red', 'bank', 'cash'], true)) $e['method'] = 'Әдіс қате';
		if ($method != 'cash' && mb_strlen($ref) < 3) $e['external_ref'] = 'Операция нөмірін жазыңыз';
		if (strlen($paid_at) == 16) $paid_at .= ':00';
		$ts = strtotime($paid_at);
		if (!$ts || date('Y-m-d H:i:s', $ts) !== $paid_at || $ts > time() + 300) $e['paid_at'] = 'Төленген уақыты қате';
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		if ($ref !== '' && db::val("select id from payments where organization_id = ? and method = ? and external_ref = ?", [$org_id, $method, $ref])) fun::json(['ok' => false, 'mess' => 'Бұл операция нөмірі бұрын енгізілген']);

		$res = db::tx(function () use ($sub, $pid, $amount, $method, $ref, $paid_at, $status, $comment, $idem, $org_id, $user_id) {
			db::row("select id from subscriptions where id = ? for update", [(int) $sub['id']]);   // бір уақыттағы екі төлемнен қорғау
			$rest = sub_rest($sub['id']);
			if ($amount > $rest + 0.001) return ['ok' => false, 'mess' => 'Сома қалған қарыздан көп: '.fun::money($rest)];
			$fee = academy::fee($org_id, $method, $sub['plan_id'], $amount);
			$id = db::ins("insert into payments (organization_id, participant_id, amount, fee_amount, method, source, kind, status, external_ref, idempotency_key, paid_at, entered_by, confirmed_by, confirmed_at, comment)
				values (?, ?, ?, ?, ?, 'manual', 'payment', ?, ?, ?, ?, ?, ?, ?, ?)",
				[$org_id, $pid, $amount, $fee, $method, $status, $ref ?: null, $idem, $paid_at, $user_id, $status == 'confirmed' ? $user_id : null, $status == 'confirmed' ? date('Y-m-d H:i:s') : null, $comment ?: null]);
			db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (?, ?, ?, ?)", [$org_id, $id, (int) $sub['id'], $amount]);
			return ['ok' => true, 'id' => $id, 'fee' => $fee];
		});
		if (!$res['ok']) fun::json($res);
		if ($status == 'confirmed') academy::sub_refresh($sub['id']);
		fun::audit('create', 'payments', $res['id'], null, ['amount' => $amount, 'method' => $method, 'ref' => $ref, 'status' => $status, 'subscription_id' => $sub['id']]);
		fun::json($res);
	}


	// растау / қабылдамау
	if (isset($_GET['confirm']) || isset($_GET['reject'])) {
		$py = pay_get(fun::post('id'));
		if (!$py || $py['status'] != 'pending') fun::json(['ok' => false, 'mess' => 'Расталмаған төлем табылмады']);
		$ok = isset($_GET['confirm']);
		$reason = fun::post('reason');
		if (!$ok && mb_strlen($reason) < 3) fun::json(['ok' => false, 'mess' => 'Себебін жазыңыз']);
		db::q("update payments set status = ?, confirmed_by = ?, confirmed_at = now(), comment = coalesce(?, comment) where id = ? and status = 'pending'",
			[$ok ? 'confirmed' : 'rejected', $user_id, $ok ? null : $reason, (int) $py['id']]);
		foreach (pay_subs($py['id']) as $sid) academy::sub_refresh($sid);
		fun::audit($ok ? 'confirm' : 'reject', 'payments', $py['id'], ['status' => 'pending'], ['status' => $ok ? 'confirmed' : 'rejected', 'reason' => $reason]);
		fun::json(['ok' => true]);
	}


	// сторно: төлем өшірілмейді, теріс сомамен кері жазба жасалады
	if (isset($_GET['reverse'])) {
		$py = pay_get(fun::post('id'));
		$reason = fun::post('reason');
		if (!$py || $py['status'] != 'confirmed' || $py['kind'] != 'payment') fun::json(['ok' => false, 'mess' => 'Тек расталған төлемді сторно жасауға болады']);
		if (mb_strlen($reason) < 3) fun::json(['ok' => false, 'mess' => 'Себебін жазыңыз']);
		$res = db::tx(function () use ($py, $reason, $org_id, $user_id) {
			db::row("select id from payments where id = ? for update", [(int) $py['id']]);
			if (db::val("select id from payments where reversal_of_id = ?", [(int) $py['id']])) return ['ok' => false, 'mess' => 'Бұл төлем бұрын сторноланған'];
			$id = db::ins("insert into payments (organization_id, participant_id, amount, fee_amount, method, source, kind, status, idempotency_key, reversal_of_id, paid_at, entered_by, confirmed_by, confirmed_at, comment)
				values (?, ?, ?, ?, ?, 'manual', 'reversal', 'confirmed', ?, ?, now(), ?, ?, now(), ?)",
				[$org_id, (int) $py['participant_id'], -$py['amount'], -$py['fee_amount'], $py['method'], 'rev-'.$py['id'], (int) $py['id'], $user_id, $user_id, $reason]);
			foreach (db::all("select * from payment_allocations where payment_id = ?", [(int) $py['id']]) as $a)
				db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (?, ?, ?, ?)", [$org_id, $id, (int) $a['subscription_id'], -$a['amount']]);
			return ['ok' => true, 'id' => $id];
		});
		if (!$res['ok']) fun::json($res);
		foreach (pay_subs($py['id']) as $sid) academy::sub_refresh($sid);
		fun::audit('reverse', 'payments', $py['id'], ['amount' => $py['amount']], ['reversal_id' => $res['id'], 'reason' => $reason]);
		fun::json($res);
	}


	// шығын
	if (isset($_GET['expense_add'])) {
		$cat = fun::post('category'); $amount = round((float) fun::post('amount'), 2); $day = fun::post('spent_at'); $comment = fun::post('comment');
		if (!in_array($cat, ['rent', 'salary', 'equipment', 'marketing', 'tournament', 'other'], true)) fun::json(['ok' => false, 'mess' => 'Санат қате']);
		if ($amount <= 0) fun::json(['ok' => false, 'mess' => 'Соманы жазыңыз']);
		if (!fun::valid_date($day) || $day > $date) fun::json(['ok' => false, 'mess' => 'Күні қате']);
		$id = db::ins("insert into expenses (organization_id, category, amount, spent_at, comment, created_by) values (?, ?, ?, ?, ?, ?)", [$org_id, $cat, $amount, $day, $comment ?: null, $user_id]);
		fun::audit('create', 'expenses', $id, null, ['category' => $cat, 'amount' => $amount]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
