<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);


	// қатысуды белгілеу: marks[participant_id] = present|absent|excused|frozen
	if (isset($_GET['mark'])) {
		$id = (int) fun::post('id');
		$s = db::row("select s.*, g.coach_id group_coach from sessions s left join `groups` g on g.id = s.group_id where s.id = ? and s.organization_id = ? and s.type <> 'match'", [$id, $org_id]);
		if (!$s || (!$admin && (int) $s['coach_id'] !== $user_id && (int) $s['group_coach'] !== $user_id)) fun::json(['ok' => false, 'mess' => 'Жаттығу табылмады']);
		if ($s['status'] == 'cancelled') fun::json(['ok' => false, 'mess' => 'Жаттығу болдырылмаған']);
		$day = substr($s['starts_at'], 0, 10);
		if ($day > $date) fun::json(['ok' => false, 'mess' => 'Болашақ жаттығуды белгілеуге болмайды']);

		$marks = isset($_POST['marks']) && is_array($_POST['marks']) ? $_POST['marks'] : [];
		$allowed = ['present', 'absent', 'excused', 'frozen'];
		// тек сол күні топта болғандар немесе бұрын белгіленгендер
		$valid = array_column(db::all("select p.id from participants p where p.organization_id = ? and (
				exists (select 1 from attendance a where a.session_id = ? and a.participant_id = p.id)
				or exists (select 1 from enrollments e where e.participant_id = p.id and e.group_id = ? and e.start_date <= ? and (e.end_date is null or e.end_date > ?)))",
			[$org_id, $id, (int) $s['group_id'], $day, $day]), 'id');
		$valid = array_flip(array_map('intval', $valid));
		$set = [];
		foreach ($marks as $pid => $st) {
			$pid = (int) $pid;
			if (!isset($valid[$pid])) fun::json(['ok' => false, 'mess' => 'Қатысушы бұл топта емес']);
			if (!in_array($st, $allowed, true)) fun::json(['ok' => false, 'mess' => 'Мәртебе қате']);
			$set[$pid] = $st;
		}
		if (!$set) fun::json(['ok' => false, 'mess' => 'Ешкім белгіленбеді']);

		$changed = db::tx(function () use ($set, $id, $org_id, $user_id) {
			$old = [];
			foreach (db::all("select participant_id, status from attendance where session_id = ? for update", [$id]) as $r) $old[(int) $r['participant_id']] = $r['status'];
			$ch = [];
			foreach ($set as $pid => $st) {
				if (isset($old[$pid]) && $old[$pid] == $st) continue;
				db::q("insert into attendance (organization_id, session_id, participant_id, status, marked_by, marked_at) values (?, ?, ?, ?, ?, now())
					on duplicate key update status = values(status), marked_by = values(marked_by), marked_at = now()", [$org_id, $id, $pid, $st, $user_id]);
				$ch[$pid] = [isset($old[$pid]) ? $old[$pid] : null, $st];
			}
			db::q("update sessions set status = 'done' where id = ? and status = 'planned'", [$id]);
			return $ch;
		});

		// рейтинг: қатысу өзгерсе ұпай да түзетіледі
		foreach ($changed as $pid => $x) academy::rating_sync($pid);
		if ($changed) fun::audit('mark', 'attendance', $id, null, $changed);
		fun::json(['ok' => true, 'changed' => count($changed)]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
