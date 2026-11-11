<? include "../config/core.php";

	// барлық әрекетке кіру міндетті
	core::need();


	// форманы тексеру (қосу және өңдеу үшін ортақ)
	function part_form($id = 0) {
		global $org_id;
		$e = [];
		$d = [
			'first_name' => fun::post('first_name'),
			'last_name' => fun::post('last_name'),
			'middle_name' => fun::post('middle_name') ?: null,
			'birth_date' => fun::post('birth_date'),
			'gender' => fun::post('gender', 'm'),
			'phone' => fun::post('phone'),
			'direction' => fun::post('direction'),
			'position' => fun::post('position') ?: null,
			'foot' => fun::post('foot') ?: null,
			'number' => fun::post('number'),
			'joined_at' => fun::post('joined_at') ?: date('Y-m-d'),
			'status' => fun::post('status', 'active'),
			'notes' => fun::post('notes') ?: null,
		];
		if (mb_strlen($d['first_name']) < 2 || mb_strlen($d['first_name']) > 80) $e['first_name'] = 'Атын жазыңыз';
		if (mb_strlen($d['last_name']) < 2 || mb_strlen($d['last_name']) > 80) $e['last_name'] = 'Тегін жазыңыз';
		if (!fun::valid_date($d['birth_date']) || $d['birth_date'] > date('Y-m-d') || fun::age($d['birth_date']) > 80) $e['birth_date'] = 'Туған күні қате';
		if (!in_array($d['direction'], ['kids', 'adult'])) $e['direction'] = 'Бағытын таңдаңыз';
		if (!in_array($d['gender'], ['m', 'f'])) $d['gender'] = 'm';
		if ($d['position'] && !in_array($d['position'], ['gk', 'df', 'mf', 'fw'])) $e['position'] = 'Позиция қате';
		if ($d['foot'] && !in_array($d['foot'], ['right', 'left', 'both'])) $e['foot'] = 'Қате';
		if (!in_array($d['status'], ['active', 'paused', 'left'])) $d['status'] = 'active';
		if ($d['number'] !== '') {
			if (!ctype_digit($d['number']) || (int) $d['number'] < 1 || (int) $d['number'] > 99) $e['number'] = '1–99';
			else $d['number'] = (int) $d['number'];
		} else $d['number'] = null;
		if (!fun::valid_date($d['joined_at'])) $e['joined_at'] = 'Күні қате';
		if ($d['phone'] !== '') { $d['phone'] = fun::phone_clean($d['phone']); if (!$d['phone']) $e['phone'] = 'Нөмір қате'; }
		else $d['phone'] = null;
		if ($d['direction'] == 'adult' && !$d['phone'] && empty($e['phone'])) $e['phone'] = 'Ересек қатысушының телефоны міндетті';

		// бір адамға бір профиль
		if (empty($e['first_name']) && empty($e['last_name']) && empty($e['birth_date'])) {
			$dup = db::val("select id from participants where organization_id = ? and first_name = ? and last_name = ? and birth_date = ? and id <> ?", [$org_id, $d['first_name'], $d['last_name'], $d['birth_date'], (int) $id]);
			if ($dup) $e['last_name'] = 'Бұл қатысушы бұрын тіркелген (№'.$dup.')';
		}
		return [$d, $e];
	}

	function group_ok($group_id, $direction) {
		global $org_id;
		if (!$group_id) return true;
		return (bool) db::val("select id from `groups` where id = ? and organization_id = ? and direction = ? and active = 1", [(int) $group_id, $org_id, $direction]);
	}



	// participant add
	if (isset($_GET['add'])) {
		core::need(['owner', 'admin']);
		list($d, $e) = part_form();
		$group_id = (int) fun::post('group_id');
		$g_name = fun::post('guardian_name');
		$g_phone = fun::post('guardian_phone');
		if ($group_id && $d['direction'] && !group_ok($group_id, $d['direction'])) $e['group_id'] = 'Топ бағытқа сай емес';
		if ($d['direction'] == 'kids') {
			if (mb_strlen($g_name) < 2) $e['guardian_name'] = 'Ата-анасының атын жазыңыз';
			if (!($g_phone = fun::phone_clean($g_phone))) $e['guardian_phone'] = 'Ата-анасының нөмірі қате';
		}
		if ($e) fun::json(['ok' => false, 'errors' => $e]);

		$id = db::tx(function () use ($d, $group_id, $g_name, $g_phone, $org_id, $user_id) {
			$id = db::ins("insert into participants (organization_id, first_name, last_name, middle_name, birth_date, gender, phone, direction, position, foot, number, joined_at, status, notes, created_by)
				values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$org_id, $d['first_name'], $d['last_name'], $d['middle_name'], $d['birth_date'], $d['gender'], $d['phone'],
				$d['direction'], $d['position'], $d['foot'], $d['number'], $d['joined_at'], $d['status'], $d['notes'], $user_id]);
			if ($group_id) fun::participant_set_group($id, $group_id, 'Тіркелу');
			if ($d['direction'] == 'kids') {
				$gid = db::val("select id from guardians where organization_id = ? and phone = ?", [$org_id, $g_phone]);
				if (!$gid) $gid = db::ins("insert into guardians (organization_id, name, phone) values (?, ?, ?)", [$org_id, $g_name, $g_phone]);
				db::q("insert into participant_guardians (organization_id, participant_id, guardian_id, relation) values (?, ?, ?, ?)", [$org_id, $id, (int) $gid, in_array(fun::post('guardian_relation'), ['mother', 'father']) ? fun::post('guardian_relation') : 'other']);
			}
			fun::audit('create', 'participants', $id, null, $d);
			return $id;
		});
		fun::json(['ok' => true, 'id' => $id]);
	}


	// participant edit
	if (isset($_GET['edit'])) {
		core::need(['owner', 'admin']);
		$id = (int) fun::post('id');
		$old = fun::participant($id);
		if (!$old) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		list($d, $e) = part_form($id);
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		db::q("update participants set first_name = ?, last_name = ?, middle_name = ?, birth_date = ?, gender = ?, phone = ?, direction = ?, position = ?, foot = ?, number = ?, joined_at = ?, status = ?, notes = ? where id = ? and organization_id = ?",
			[$d['first_name'], $d['last_name'], $d['middle_name'], $d['birth_date'], $d['gender'], $d['phone'], $d['direction'], $d['position'], $d['foot'], $d['number'], $d['joined_at'], $d['status'], $d['notes'], $id, $org_id]);
		fun::audit('update', 'participants', $id, array_intersect_key($old, $d), $d);
		fun::json(['ok' => true]);
	}


	// топ ауыстыру (тарих сақталады)
	if (isset($_GET['set_group'])) {
		core::need(['owner', 'admin']);
		$id = (int) fun::post('id');
		$group_id = (int) fun::post('group_id');
		$p = fun::participant($id);
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		if (!group_ok($group_id, $p['direction'])) fun::json(['ok' => false, 'mess' => 'Топ бағытқа сай емес']);
		$old = fun::participant_group($id);
		$ch = db::tx(function () use ($id, $group_id) { return fun::participant_set_group($id, $group_id, fun::post('reason') ?: 'Топ ауыстыру'); });
		if ($ch) fun::audit('set_group', 'participants', $id, ['group_id' => $old ? $old['id'] : null], ['group_id' => $group_id ?: null]);
		fun::json(['ok' => true]);
	}


	// ата-ана қосу
	if (isset($_GET['guardian_add'])) {
		core::need(['owner', 'admin']);
		$id = (int) fun::post('id');
		$name = fun::post('name');
		$phone = fun::phone_clean(fun::post('phone'));
		$rel = in_array(fun::post('relation'), ['mother', 'father']) ? fun::post('relation') : 'other';
		if (!fun::participant($id)) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		$e = [];
		if (mb_strlen($name) < 2) $e['name'] = 'Атын жазыңыз';
		if (!$phone) $e['phone'] = 'Нөмір қате';
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		$gid = db::val("select id from guardians where organization_id = ? and phone = ?", [$org_id, $phone]);
		if (!$gid) $gid = db::ins("insert into guardians (organization_id, name, phone) values (?, ?, ?)", [$org_id, $name, $phone]);
		if (db::val("select id from participant_guardians where participant_id = ? and guardian_id = ?", [$id, (int) $gid])) fun::json(['ok' => false, 'mess' => 'Бұл ата-ана бұрын қосылған']);
		db::q("insert into participant_guardians (organization_id, participant_id, guardian_id, relation, is_primary) values (?, ?, ?, ?, 0)", [$org_id, $id, (int) $gid, $rel]);
		fun::audit('guardian_add', 'participants', $id, null, ['guardian_id' => (int) $gid]);
		fun::json(['ok' => true]);
	}


	// архив / қалпына келтіру (өшірілмейді)
	if (isset($_GET['archive']) || isset($_GET['restore'])) {
		core::need(['owner', 'admin']);
		$id = (int) fun::post('id');
		if (!fun::participant($id)) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		if (isset($_GET['archive'])) db::q("update participants set archived_at = now(), status = 'left' where id = ? and organization_id = ?", [$id, $org_id]);
		else db::q("update participants set archived_at = null, status = 'active' where id = ? and organization_id = ?", [$id, $org_id]);
		fun::audit(isset($_GET['archive']) ? 'archive' : 'restore', 'participants', $id);
		fun::json(['ok' => true]);
	}


	// фото
	if (isset($_GET['img'])) {
		core::need(['owner', 'admin']);
		$id = (int) fun::post('id');
		$p = fun::participant($id);
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		$name = fun::img_upload(isset($_FILES['file']) ? $_FILES['file'] : null, __DIR__.'/../assets/uploads/participants');
		if (!$name) fun::json(['ok' => false, 'mess' => 'Тек JPG, PNG, WEBP, 5 МБ дейін']);
		db::q("update participants set img = ? where id = ?", [$name, $id]);
		if ($p['img'] && is_file(__DIR__.'/../assets/uploads/participants/'.basename($p['img']))) @unlink(__DIR__.'/../assets/uploads/participants/'.basename($p['img']));
		fun::audit('img', 'participants', $id, ['img' => $p['img']], ['img' => $name]);
		fun::json(['ok' => true, 'img' => '/assets/uploads/participants/'.$name]);
	}


	http_response_code(400);
	echo 'none';
