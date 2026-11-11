<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach', 'parent', 'participant']);


	if (isset($_GET['lesson_add'])) {
		core::need(['owner', 'admin', 'coach']);
		$t = fun::post('title'); $c = fun::post('category'); $l = fun::post('level');
		$yt = fun::post('youtube') !== '' ? academy::yt_id(fun::post('youtube')) : null;
		if (mb_strlen($t) < 3) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if (!in_array($c, ['technique', 'physical', 'tactics', 'ball', 'individual'], true) || !in_array($l, ['easy', 'medium', 'hard'], true)) fun::json(['ok' => false, 'mess' => 'Санат қате']);
		if (fun::post('youtube') !== '' && !$yt) fun::json(['ok' => false, 'mess' => 'YouTube сілтемесі қате']);
		$dm = (int) fun::post('duration_min');
		$id = db::ins("insert into training_lessons (organization_id, category, title, description, video_key, duration_min, level, reps, coach_tip, created_by) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			[$org_id, $c, $t, fun::post('description') ?: null, $yt, $dm > 0 && $dm < 300 ? $dm : null, $l, fun::post('reps') ?: null, fun::post('coach_tip') ?: null, $user_id]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['assign'])) {
		core::need(['owner', 'admin', 'coach']);
		$l = db::row("select * from training_lessons where id = ? and organization_id = ?", [(int) fun::post('lesson_id'), $org_id]);
		$gid = (int) fun::post('group_id'); $pid = (int) fun::post('participant_id'); $due = fun::post('due_date');
		if (!$l) fun::json(['ok' => false, 'mess' => 'Жаттығуды таңдаңыз']);
		if (!$gid && !$pid) fun::json(['ok' => false, 'mess' => 'Топты немесе қатысушыны таңдаңыз']);
		if ($gid && $pid) fun::json(['ok' => false, 'mess' => 'Топ немесе қатысушы — біреуін таңдаңыз']);
		if ($due && (!fun::valid_date($due) || $due < $date)) fun::json(['ok' => false, 'mess' => 'Мерзімі қате']);
		if ($gid) {
			$g = db::row("select * from `groups` where id = ? and organization_id = ?", [$gid, $org_id]);
			if (!$g || (!core::is(['owner', 'admin']) && (int) $g['coach_id'] !== $user_id)) fun::json(['ok' => false, 'mess' => 'Топ табылмады']);
		}
		if ($pid && !fun::participant($pid)) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
		$id = db::ins("insert into training_assignments (organization_id, lesson_id, group_id, participant_id, due_date, created_by) values (?, ?, ?, ?, ?, ?)", [$org_id, (int) $l['id'], $gid ?: null, $pid ?: null, $due ?: null, $user_id]);
		if ($gid) academy::notify_group($org_id, $gid, 'lesson', 'Жаңа үй тапсырмасы: '.$l['title'], $due ? date('d.m', strtotime($due)).' дейін' : null, '/training/', 'asg_'.$id);
		else academy::notify_participant($org_id, $pid, 'lesson', 'Жаңа үй тапсырмасы: '.$l['title'], $due ? date('d.m', strtotime($due)).' дейін' : null, '/training/', 'asg_'.$id);
		fun::json(['ok' => true, 'id' => $id]);
	}

	// қатысушы / ата-ана: орындадым
	if (isset($_GET['submit'])) {
		$t = db::row("select * from training_assignments where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		$p = fun::participant((int) fun::post('participant_id'));
		if (!$t || !$p) fun::json(['ok' => false, 'mess' => 'Тапсырма табылмады']);
		$gid = (int) db::val("select group_id from enrollments where participant_id = ? and end_date is null limit 1", [(int) $p['id']]);
		if ((int) $t['participant_id'] !== (int) $p['id'] && (int) $t['group_id'] !== $gid) fun::json(['ok' => false, 'mess' => 'Бұл тапсырма сізге емес']);
		$yt = fun::post('video') !== '' ? academy::yt_id(fun::post('video')) : null;
		if (fun::post('video') !== '' && !$yt) fun::json(['ok' => false, 'mess' => 'Видео сілтемесі қате (YouTube)']);
		$cur = db::row("select * from assignment_submissions where assignment_id = ? and participant_id = ?", [(int) $t['id'], (int) $p['id']]);
		if ($cur && $cur['status'] != 'rejected') fun::json(['ok' => false, 'mess' => 'Бұрын жіберілген']);
		if ($cur) db::q("update assignment_submissions set status = 'done', comment = ?, video_key = ?, created_at = now() where id = ?", [fun::post('comment') ?: null, $yt, (int) $cur['id']]);
		else db::ins("insert into assignment_submissions (organization_id, assignment_id, participant_id, comment, video_key) values (?, ?, ?, ?, ?)", [$org_id, (int) $t['id'], (int) $p['id'], fun::post('comment') ?: null, $yt]);
		fun::json(['ok' => true]);
	}

	if (isset($_GET['review'])) {
		core::need(['owner', 'admin', 'coach']);
		$x = db::row("select x.* from assignment_submissions x where x.id = ? and x.organization_id = ?", [(int) fun::post('id'), $org_id]);
		$s = fun::post('status');
		if (!$x || !fun::participant($x['participant_id'])) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		if (!in_array($s, ['accepted', 'rejected'], true)) fun::json(['ok' => false, 'mess' => 'Қате']);
		db::q("update assignment_submissions set status = ?, coach_comment = ?, reviewed_by = ?, reviewed_at = now() where id = ?", [$s, fun::post('comment') ?: null, $user_id, (int) $x['id']]);
		academy::notify_participant($org_id, $x['participant_id'], 'lesson', $s == 'accepted' ? 'Тапсырма қабылданды 👍' : 'Тапсырманы қайта орындау керек', fun::post('comment') ?: null, '/training/', 'rev_'.$x['id'].'_'.$s.'_'.time());
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
