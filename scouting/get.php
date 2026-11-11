<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);
	$st_all = ['watching', 'candidate', 'invited', 'passed', 'failed', 'accepted'];


	if (isset($_GET['campaign_add'])) {
		$name = fun::post('name'); $target = fun::post('target');
		$sd = fun::post('start_date'); $ed = fun::post('end_date');
		if (mb_strlen($name) < 3) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if (!in_array($target, ['first_team', 'city_league'], true)) fun::json(['ok' => false, 'mess' => 'Мақсат қате']);
		if (($sd && !fun::valid_date($sd)) || ($ed && !fun::valid_date($ed)) || ($sd && $ed && $ed < $sd)) fun::json(['ok' => false, 'mess' => 'Күндер қате']);
		$id = db::ins("insert into selection_campaigns (organization_id, name, target, start_date, end_date, created_by) values (?, ?, ?, ?, ?, ?)", [$org_id, $name, $target, $sd ?: null, $ed ?: null, $user_id]);
		fun::audit('create', 'selection_campaigns', $id);
		fun::json(['ok' => true, 'id' => $id]);
	}

	if (isset($_GET['campaign_status'])) {
		core::need(['owner', 'admin']);
		$c = db::row("select * from selection_campaigns where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		$st = fun::post('status');
		if (!$c || !in_array($st, ['open', 'closed'], true)) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		db::q("update selection_campaigns set status = ? where id = ?", [$st, (int) $c['id']]);
		fun::audit($st, 'selection_campaigns', $c['id']);
		fun::json(['ok' => true]);
	}

	if (isset($_GET['candidate_add'])) {
		$c = db::row("select * from selection_campaigns where id = ? and organization_id = ? and status = 'open'", [(int) fun::post('campaign_id'), $org_id]);
		$p = db::row("select * from participants where id = ? and organization_id = ? and archived_at is null", [(int) fun::post('participant_id'), $org_id]);
		if (!$c) fun::json(['ok' => false, 'mess' => 'Іріктеу жабық']);
		if (!$p) fun::json(['ok' => false, 'mess' => 'Қатысушыны таңдаңыз']);
		if ($c['target'] == 'first_team' && $p['direction'] != 'adult') fun::json(['ok' => false, 'mess' => 'Бірінші құрамға тек JAS ADULT']);
		if (db::val("select id from selection_candidates where campaign_id = ? and participant_id = ?", [(int) $c['id'], (int) $p['id']])) fun::json(['ok' => false, 'mess' => 'Тізімде бар']);
		$id = db::ins("insert into selection_candidates (organization_id, campaign_id, participant_id, notes) values (?, ?, ?, ?)", [$org_id, (int) $c['id'], (int) $p['id'], fun::post('notes') ?: null]);
		fun::audit('candidate_add', 'participants', $p['id'], null, ['campaign' => $c['name']]);
		fun::json(['ok' => true, 'id' => $id]);
	}

	// мәртебе: шешімді бапкер/әкімші қолмен қабылдайды. «Қабылданды» + first_team → құрамға қосылады
	if (isset($_GET['candidate_status'])) {
		$x = db::row("select x.*, c.target, c.status camp_status, p.direction from selection_candidates x join selection_campaigns c on c.id = x.campaign_id join participants p on p.id = x.participant_id where x.id = ? and x.organization_id = ?", [(int) fun::post('id'), $org_id]);
		$st = fun::post('status');
		if (!$x || $x['camp_status'] != 'open') fun::json(['ok' => false, 'mess' => 'Табылмады']);
		if (!in_array($st, $st_all, true)) fun::json(['ok' => false, 'mess' => 'Мәртебе қате']);
		db::q("update selection_candidates set status = ?, decided_by = ?, decided_at = now() where id = ?", [$st, $user_id, (int) $x['id']]);
		$roster = false;
		if ($st == 'accepted' && $x['target'] == 'first_team' && $x['direction'] == 'adult') {
			$team = db::val("select id from teams where organization_id = ? and type = 'first_team' order by id limit 1", [$org_id]);
			if ($team && !db::val("select id from team_rosters where team_id = ? and participant_id = ? and left_at is null", [(int) $team, (int) $x['participant_id']])) {
				db::q("insert into team_rosters (organization_id, team_id, participant_id, joined_at) values (?, ?, ?, ?)", [$org_id, (int) $team, (int) $x['participant_id'], $date]);
				$roster = true;
			}
		}
		if (in_array($st, ['invited', 'accepted'])) academy::notify_participant($org_id, $x['participant_id'], 'other', $st == 'accepted' ? 'Құттықтаймыз! Іріктеуден өттіңіз' : 'Сіз іріктеуге шақырылдыңыз', null, '/cabinet/', 'sel_'.$x['id'].'_'.$st);
		fun::audit('candidate_'.$st, 'participants', $x['participant_id'], ['status' => $x['status']], ['status' => $st, 'roster' => $roster]);
		fun::json(['ok' => true, 'roster' => $roster]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
