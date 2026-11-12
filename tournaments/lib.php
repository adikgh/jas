<?
	// Турнир кестесі (ұпай: жеңіс 3, тең 1)
	function tour_table($tid) {
		$t = [];
		foreach (db::all("select id, name, place from tournament_teams where tournament_id = ? order by id", [(int) $tid]) as $r)
			$t[$r['id']] = ['id' => $r['id'], 'name' => $r['name'], 'place' => $r['place'], 'p' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0];
		foreach (db::all("select * from tournament_games where tournament_id = ? and status = 'finished'", [(int) $tid]) as $g) {
			foreach ([[$g['home_id'], $g['home_goals'], $g['away_goals']], [$g['away_id'], $g['away_goals'], $g['home_goals']]] as $x) {
				if (!isset($t[$x[0]])) continue;
				$r = &$t[$x[0]]; $r['p']++; $r['gf'] += $x[1]; $r['ga'] += $x[2];
				if ($x[1] > $x[2]) { $r['w']++; $r['pts'] += 3; } elseif ($x[1] == $x[2]) { $r['d']++; $r['pts']++; } else $r['l']++;
				unset($r);
			}
		}
		usort($t, function ($a, $b) { return [$b['pts'], $b['gf'] - $b['ga'], $b['gf']] <=> [$a['pts'], $a['gf'] - $a['ga'], $a['gf']]; });
		return $t;
	}
	$tour_kind = ['internal' => ['Ішкі турнир', 'bdg_dk'], 'sunday' => ['Жексенбілік турнир', 'bdg_warn'], 'external' => ['Сыртқы турнир', '']];
	$tour_dir = ['kids' => 'Балалар', 'adult' => 'Ересектер', 'mixed' => 'Аралас'];
	$tour_st = ['registration' => ['Тіркелу ашық', 'bdg_ok'], 'running' => ['Жүріп жатыр', 'bdg_warn'], 'finished' => ['Аяқталды', ''], 'cancelled' => ['Болмады', 'bdg_err']];
