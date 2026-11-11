<? include "../config/core.php";

	core::need();

	// group add
	if (isset($_GET['add'])) {
		core::need(['owner', 'admin']);
		$name = fun::post('name');
		$dir = fun::post('direction');
		$coach = (int) fun::post('coach_id');
		$venue = (int) fun::post('venue_id');
		$cap = (int) fun::post('capacity');
		$af = (int) fun::post('age_from'); $at = (int) fun::post('age_to');
		$e = [];
		if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $e['name'] = 'Атауын жазыңыз';
		elseif (db::val("select id from `groups` where organization_id = ? and name = ?", [$org_id, $name])) $e['name'] = 'Бұл атау бар';
		if (!in_array($dir, ['kids', 'adult'])) $e['direction'] = 'Бағыт қате';
		if ($coach && !db::val("select id from memberships where organization_id = ? and user_id = ? and role = 'coach' and active = 1", [$org_id, $coach])) $e['coach_id'] = 'Бапкер табылмады';
		if ($venue && !db::val("select id from venues where organization_id = ? and id = ?", [$org_id, $venue])) $e['venue_id'] = 'Алаң табылмады';
		if ($af && $at && $af > $at) $e['age_to'] = 'Жас аралығы қате';
		if ($e) fun::json(['ok' => false, 'errors' => $e]);
		$id = db::ins("insert into `groups` (organization_id, name, direction, age_from, age_to, coach_id, venue_id, capacity) values (?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, $name, $dir, $af ?: null, $at ?: null, $coach ?: null, $venue ?: null, $cap ?: null]);
		fun::audit('create', 'groups', $id, null, ['name' => $name, 'direction' => $dir]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	http_response_code(400);
	echo 'none';
