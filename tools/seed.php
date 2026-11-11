<?php
	// Демо деректер (ойдан шығарылған, нақты адамдар емес): php tools/seed.php [db_name]
	// Нақты қатысушылар деректерін тек Қазақстандағы серверге жүктеңіз.
	if (php_sapi_name() != 'cli') exit('cli only');

	require __DIR__.'/../config/core.php';
	if (!empty($argv[1])) mysqli_select_db(db::$connect, $argv[1]);
	mt_srand(2026);

	if (db::val("select count(*) from organizations")) exit("Деректер бар, seed өткізілді.\n");

	$today = date('Y-m-d');
	$pass = password_hash('jas2026', PASSWORD_DEFAULT);

	db::tx(function () use ($today, $pass) {

		// ұйым
		$org = db::ins("insert into organizations (name, slug, city, phone, whatsapp, instagram) values ('JAS ACADEMY', 'jas', 'Шымкент', '87000000000', '77000000000', 'jas.academy')");
		core::$org_id = $org;

		// қызметкерлер
		$users = [
			['87000000001', 'Ерлан', 'Иесі', 'owner'],
			['87000000002', 'Әсем', 'Әкімші', 'admin'],
			['87000000003', 'Нұрлан', 'Бапкер', 'coach'],
			['87000000004', 'Даурен', 'Бапкер', 'coach'],
			['87000000005', 'Гүлнар', 'Ата-ана', 'parent'],
			['87000000006', 'Арман', 'Футболшы', 'participant'],
		];
		$u = [];
		foreach ($users as $x) {
			$id = db::ins("insert into users (phone, password_hash, name, surname) values (?, ?, ?, ?)", [$x[0], $pass, $x[1], $x[2]]);
			db::q("insert into memberships (organization_id, user_id, role) values (?, ?, ?)", [$org, $id, $x[3]]);
			$u[$x[0]] = $id;
		}
		$coach1 = $u['87000000003']; $coach2 = $u['87000000004'];

		// алаңдар, топтар
		$v1 = db::ins("insert into venues (organization_id, name, address) values (?, 'JAS Arena', 'Шымкент, демо мекенжай 1')", [$org]);
		$v2 = db::ins("insert into venues (organization_id, name, address) values (?, 'Мектеп алаңы', 'Шымкент, демо мекенжай 2')", [$org]);
		$groups = [
			['KIDS U8', 'kids', 6, 8, $coach1, $v1, [[1, '16:00'], [3, '16:00'], [5, '16:00']]],
			['KIDS U11', 'kids', 9, 11, $coach1, $v1, [[2, '17:00'], [4, '17:00'], [6, '11:00']]],
			['ADULT A', 'adult', 18, 45, $coach2, $v2, [[1, '20:00'], [3, '20:00'], [5, '20:00']]],
			['ADULT B', 'adult', 18, 45, $coach2, $v2, [[2, '21:00'], [4, '21:00'], [7, '19:00']]],
		];
		$g = [];
		foreach ($groups as $x) {
			$gid = db::ins("insert into `groups` (organization_id, name, direction, age_from, age_to, coach_id, venue_id, capacity) values (?, ?, ?, ?, ?, ?, ?, 20)", [$org, $x[0], $x[1], $x[2], $x[3], $x[4], $x[5]]);
			foreach ($x[6] as $s) {
				$end = date('H:i', strtotime($s[1]) + 90 * 60);
				$tid = db::ins("insert into schedule_templates (organization_id, group_id, weekday, start_time, end_time, venue_id, coach_id, valid_from) values (?, ?, ?, ?, ?, ?, ?, ?)",
					[$org, $gid, $s[0], $s[1], $end, $x[5], $x[4], date('Y-m-d', strtotime('-60 day'))]);
				$g[$gid]['tpl'][] = ['id' => $tid, 'wd' => $s[0], 'st' => $s[1], 'en' => $end, 'venue' => $x[5], 'coach' => $x[4]];
			}
			$g[$gid]['dir'] = $x[1];
		}
		$gids = array_keys($g);

		// тарифтер
		$plan_k = db::ins("insert into plans (organization_id, name, direction, price, sessions_limit) values (?, 'KIDS 12 жаттығу', 'kids', 25000, 12)", [$org]);
		$plan_a = db::ins("insert into plans (organization_id, name, direction, price, sessions_limit) values (?, 'ADULT 12 жаттығу', 'adult', 20000, 12)", [$org]);
		db::ins("insert into plans (organization_id, name, direction, price, sessions_limit) values (?, 'ADULT шексіз', 'adult', 30000, null)", [$org]);
		db::q("insert into payment_method_fees (organization_id, method, plan_id, fee_percent) values (?, 'kaspi_red', null, 8.00)", [$org]);

		// қатысушылар (ойдан шығарылған аттар)
		$fn = ['Алихан', 'Нұрсұлтан', 'Ерасыл', 'Әлинұр', 'Бекзат', 'Темірлан', 'Дінмұхамед', 'Санжар', 'Ақжол', 'Мирас', 'Асылжан', 'Ернұр', 'Жанибек', 'Әміржан', 'Арсен', 'Батыр', 'Елдос', 'Нұрислам', 'Райымбек', 'Жансерік'];
		$ln = ['Сейітов', 'Омаров', 'Жұмабаев', 'Тұрсынов', 'Әбдіразақов', 'Құдайбергенов', 'Мұратов', 'Бекболатов', 'Исаев', 'Сәрсенов'];
		$pos = ['gk', 'df', 'df', 'mf', 'mf', 'fw'];
		$feet = ['right', 'right', 'left', 'both'];
		$parts = [];
		for ($i = 0; $i < 44; $i++) {
			$gid = $gids[$i % 4];
			$dir = $g[$gid]['dir'];
			if ($dir == 'kids') $birth = sprintf('%04d-%02d-%02d', ($gid == $gids[0] ? 2018 : 2015) + mt_rand(0, 2), mt_rand(1, 12), mt_rand(1, 28));
			else $birth = sprintf('%04d-%02d-%02d', mt_rand(1992, 2006), mt_rand(1, 12), mt_rand(1, 28));
			if ($i < 3) $birth = (date('Y', strtotime($today)) - ($dir == 'kids' ? 8 : 24)).date('-m-d', strtotime("$today +".($i * 2 + 1)." day"));  // алдағы туған күндер
			$joined = date('Y-m-d', strtotime('-'.mt_rand(20, 400).' day'));
			$uid = $i == 2 ? $u['87000000006'] : null;
			$pid = db::ins("insert into participants (organization_id, user_id, first_name, last_name, birth_date, phone, direction, position, foot, number, joined_at, created_by)
				values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$org, $uid, $fn[$i % 20], $ln[mt_rand(0, 9)], $birth,
				$dir == 'adult' ? '8707'.str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT) : null,
				$dir, $pos[mt_rand(0, 5)], $feet[mt_rand(0, 3)], mt_rand(1, 99), $joined, $u['87000000002']]);
			db::q("insert into enrollments (organization_id, participant_id, group_id, start_date) values (?, ?, ?, ?)", [$org, $pid, $gid, $joined]);
			$parts[] = ['id' => $pid, 'gid' => $gid, 'dir' => $dir];

			// балаларға ата-ана
			if ($dir == 'kids') {
				$gphone = $i < 2 ? '87000000005' : '8701'.str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
				$gd = db::row("select id from guardians where organization_id = ? and phone = ?", [$org, $gphone]);
				$gdid = $gd ? (int) $gd['id'] : db::ins("insert into guardians (organization_id, user_id, name, phone) values (?, ?, ?, ?)",
					[$org, $gphone == '87000000005' ? $u['87000000005'] : null, $gphone == '87000000005' ? 'Гүлнар Ата-ана' : 'Ата-анасы', $gphone]);
				db::q("insert into participant_guardians (organization_id, participant_id, guardian_id, relation) values (?, ?, ?, 'mother')", [$org, $pid, $gdid]);
			}
		}

		// жаттығулар: 3 апта бұрыннан 1 апта алдыға
		$sess = [];
		for ($d = -21; $d <= 7; $d++) {
			$day = date('Y-m-d', strtotime("$today $d day"));
			$wd = (int) date('N', strtotime($day));
			foreach ($g as $gid => $gx) foreach ($gx['tpl'] as $t) if ($t['wd'] == $wd) {
				$past = $d < 0;
				$sid = db::ins("insert into sessions (organization_id, group_id, template_id, type, starts_at, ends_at, venue_id, coach_id, status) values (?, ?, ?, 'training', ?, ?, ?, ?, ?)",
					[$org, $gid, $t['id'], "$day {$t['st']}:00", "$day {$t['en']}:00", $t['venue'], $t['coach'], $past ? 'done' : 'planned']);
				if ($past) $sess[$gid][] = $sid;
			}
		}
		// бүгін бір қосымша жаттығу
		db::ins("insert into sessions (organization_id, group_id, type, starts_at, ends_at, venue_id, coach_id) values (?, ?, 'extra', ?, ?, ?, ?)",
			[$org, $gids[2], "$today 10:00:00", "$today 11:30:00", $v1, $coach2]);

		// қатысу + рейтинг (+10)
		$st = ['present', 'present', 'present', 'present', 'absent', 'excused'];
		foreach ($parts as $p) foreach ((isset($sess[$p['gid']]) ? $sess[$p['gid']] : []) as $sid) {
			$s = $st[mt_rand(0, 5)];
			db::ins("insert into attendance (organization_id, session_id, participant_id, status, marked_by) values (?, ?, ?, ?, ?)", [$org, $sid, $p['id'], $s, $p['dir'] == 'kids' ? $coach1 : $coach2]);
		}
		foreach ($parts as $p) academy::rating_sync($p['id']);

		// абонемент + төлем
		$k = 0;
		foreach ($parts as $p) {
			$k++;
			$plan = $p['dir'] == 'kids' ? $plan_k : $plan_a;
			$price = $p['dir'] == 'kids' ? 25000 : 20000;
			$start = date('Y-m-d', strtotime('-'.mt_rand(0, 27).' day'));
			$end = date('Y-m-d', strtotime("$start +1 month"));
			$pending = $k % 11 == 0;
			$sub = db::ins("insert into subscriptions (organization_id, participant_id, plan_id, price, sessions_limit, start_date, end_date, status, created_by) values (?, ?, ?, ?, 12, ?, ?, ?, ?)",
				[$org, $p['id'], $plan, $price, $pending ? null : $start, $pending ? null : $end, $pending ? 'pending' : 'active', $u['87000000002']]);
			if ($pending) continue;
			$amount = $k % 7 == 0 ? $price / 2 : $price;            // кейбіреуі ішінара төледі
			$method = ['kaspi', 'kaspi', 'cash', 'kaspi_red', 'bank'][mt_rand(0, 4)];
			$fee = $method == 'kaspi_red' ? round($amount * 0.08, 2) : 0;
			$pay = db::ins("insert into payments (organization_id, participant_id, amount, fee_amount, method, status, external_ref, idempotency_key, paid_at, entered_by, confirmed_by, confirmed_at)
				values (?, ?, ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?)", [$org, $p['id'], $amount, $fee, $method, $method == 'cash' ? null : 'DEMO'.(100000 + $k),
				bin2hex(random_bytes(16)), "$start 12:00:00", $u['87000000002'], $u['87000000002'], "$start 12:05:00"]);
			db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (?, ?, ?, ?)", [$org, $pay, $sub, $amount]);
		}

		// расталмаған төлем (ішінара төлегендердің қалғаны) + заморозка өтініші
		$part_sub = db::all("select s.id, s.participant_id, s.price - coalesce((select sum(pa.amount) from payment_allocations pa where pa.subscription_id = s.id), 0) rest from subscriptions s where s.organization_id = ? and s.status = 'active' having rest > 0 limit 2", [$org]);
		foreach ($part_sub as $i => $ps) {
			$pay = db::ins("insert into payments (organization_id, participant_id, amount, method, status, external_ref, idempotency_key, paid_at, entered_by) values (?, ?, ?, 'kaspi', 'pending', ?, ?, ?, ?)",
				[$org, $ps['participant_id'], $ps['rest'], 'DEMO9'.$i, bin2hex(random_bytes(16)), date('Y-m-d H:i:s'), $u['87000000002']]);
			db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (?, ?, ?, ?)", [$org, $pay, $ps['id'], $ps['rest']]);
		}
		$fs = db::row("select id from subscriptions where organization_id = ? and status = 'active' and end_date > ? order by id desc limit 1", [$org, date('Y-m-d', strtotime('+7 day'))]);
		if ($fs) db::q("insert into freezes (organization_id, subscription_id, start_date, end_date, days, reason, requested_by) values (?, ?, ?, ?, 3, 'Ауырып қалды', ?)",
			[$org, $fs['id'], date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+3 day')), $u['87000000005']]);

		// шығындар
		foreach ([['rent', 350000, 'Алаң жалдау'], ['salary', 600000, 'Бапкерлер айлығы'], ['equipment', 85000, 'Доптар, форма'], ['marketing', 60000, 'Instagram жарнама']] as $e)
			db::q("insert into expenses (organization_id, category, amount, spent_at, comment, created_by) values (?, ?, ?, ?, ?, ?)", [$org, $e[0], $e[1], date('Y-m-05'), $e[2], $u['87000000001']]);

		// CRM өтінімдер
		$camp = db::ins("insert into campaigns (organization_id, name, channel, budget, start_date) values (?, 'Күзгі қабылдау', 'instagram', 60000, ?)", [$org, date('Y-m-01')]);
		foreach ([['Айгерім', 'new', 'instagram'], ['Бауыржан', 'new', 'tiktok'], ['Сәуле', 'new', 'instagram'], ['Қанат', 'contacted', 'whatsapp'], ['Дана', 'trial_booked', 'referral'], ['Тимур', 'converted', 'instagram']] as $i => $l)
			db::q("insert into leads (organization_id, name, phone, direction, source, campaign_id, status) values (?, ?, ?, ?, ?, ?, ?)", [$org, $l[0], '8702000000'.$i, $i % 2 ? 'adult' : 'kids', $l[2], $camp, $l[1]]);

		// бірінші құрам, ЛЛФ
		$team = db::ins("insert into teams (organization_id, name) values (?, 'JAS FIRST TEAM')", [$org]);
		foreach ($parts as $p) if ($p['dir'] == 'adult' && mt_rand(0, 1)) db::q("insert into team_rosters (organization_id, team_id, participant_id, joined_at) values (?, ?, ?, ?)", [$org, $team, $p['id'], date('Y-m-d', strtotime('-90 day'))]);
		$comp = db::ins("insert into competitions (organization_id, name, season, start_date) values (?, 'ЛЛФ Шымкент', ?, ?)", [$org, date('Y'), date('Y-m-d', strtotime('-60 day'))]);
		$opps = [];
		foreach (['Ордабасы Ветераны', 'Тұран FC', 'Жастар', 'Арсенал Шымкент', 'Қазығұрт FC'] as $o) $opps[] = db::ins("insert into opponents (organization_id, name) values (?, ?)", [$org, $o]);
		db::q("insert into matches (organization_id, competition_id, team_id, opponent_id, round, match_at, venue, goals_for, goals_against, status) values (?, ?, ?, ?, '1-тур', ?, 'JAS Arena', 3, 1, 'finished')", [$org, $comp, $team, $opps[0], date('Y-m-d 19:00:00', strtotime('-14 day'))]);
		db::q("insert into matches (organization_id, competition_id, team_id, opponent_id, round, match_at, venue, goals_for, goals_against, status) values (?, ?, ?, ?, '2-тур', ?, 'Тұран алаңы', 2, 2, 'finished')", [$org, $comp, $team, $opps[1], date('Y-m-d 19:00:00', strtotime('-7 day'))]);
		foreach ([3, 10, 17] as $n => $d) db::q("insert into matches (organization_id, competition_id, team_id, opponent_id, round, match_at, venue, is_home) values (?, ?, ?, ?, ?, ?, 'JAS Arena', ?)", [$org, $comp, $team, $opps[$n + 2], ($n + 3).'-тур', date('Y-m-d 20:00:00', strtotime("+$d day")), $n % 2 ? 0 : 1]);

		// өткен матчтардың футболшы статистикасы
		$roster = array_column(db::all("select participant_id from team_rosters where team_id = ? order by id", [$team]), 'participant_id');
		foreach (db::all("select id, goals_for from matches where team_id = ? and status = 'finished' order by id", [$team]) as $mi => $m) {
			$left = (int) $m['goals_for'];
			foreach (array_slice($roster, 0, 11) as $i => $rp) {
				$gl = $left > 0 && $i >= 3 ? 1 : 0; $left -= $gl;
				db::q("insert into match_player_stats (organization_id, match_id, participant_id, is_starter, minute_in, minute_out, goals, assists, yellow) values (?, ?, ?, 1, 0, 90, ?, ?, ?)",
					[$org, $m['id'], $rp, $gl, $i == 5 || $i == 6 ? 1 : 0, $i == 3 ? 1 : 0]);
			}
			if (isset($roster[3 + $mi])) db::q("update matches set mvp_participant_id = ? where id = ?", [$roster[3 + $mi], $m['id']]);
		}

		// бағалау санаттары, марапаттар
		foreach ([['ball_control', 'Допты бақылау'], ['passing', 'Пас дәлдігі'], ['shooting', 'Соққы'], ['dribbling', 'Дриблинг'], ['speed', 'Жылдамдық'], ['stamina', 'Төзімділік'], ['game_iq', 'Ойын түсінігі'], ['positioning', 'Позиция таңдау'], ['tactics', 'Тактикалық шешім'], ['discipline', 'Тәртіп']] as $i => $c)
			db::q("insert into assessment_criteria (organization_id, code, name, sort) values (?, ?, ?, ?)", [$org, $c[0], $c[1], $i + 1]);
		foreach ([['Апта үздігі', 'fa-star'], ['Тәртіп чемпионы', 'fa-shield-check'], ['Үздік гол', 'fa-futbol']] as $a)
			db::q("insert into achievements (organization_id, name, icon, points) values (?, ?, ?, 30)", [$org, $a[0], $a[1]]);

		// бағалаулар (2 рет: 2 ай бұрын және 10 күн бұрын)
		$crit = array_column(db::all("select id from assessment_criteria where organization_id = ?", [$org]), 'id');
		foreach ($parts as $i => $p) {
			if ($i % 3 == 2) continue;
			$base = mt_rand(4, 7);
			foreach ([-60, -10] as $k => $d) {
				$aid = db::ins("insert into player_assessments (organization_id, participant_id, coach_id, assessed_at, comment) values (?, ?, ?, ?, ?)",
					[$org, $p['id'], $p['dir'] == 'kids' ? $coach1 : $coach2, date('Y-m-d', strtotime("$d day")), $k ? 'Жақсы өсу бар, сол аяқпен жұмыс істеу керек' : 'Бастапқы бағалау']);
				foreach ($crit as $c) db::q("insert into assessment_scores (assessment_id, criterion_id, score) values (?, ?, ?)", [$aid, $c, max(1, min(10, $base + $k * mt_rand(0, 2) + mt_rand(-1, 1)))]);
			}
		}

		// селекция
		$sel = db::ins("insert into selection_campaigns (organization_id, name, target, start_date, end_date, created_by) values (?, 'FIRST TEAM 2027 іріктеу', 'first_team', ?, ?, ?)", [$org, $today, date('Y-m-d', strtotime('+30 day')), $coach2]);
		$st = ['watching', 'candidate', 'invited', 'watching', 'candidate'];
		$k = 0;
		foreach ($parts as $p) if ($p['dir'] == 'adult' && !db::val("select id from team_rosters where participant_id = ? and left_at is null", [$p['id']]) && $k < 5)
			db::q("insert into selection_candidates (organization_id, campaign_id, participant_id, status, notes) values (?, ?, ?, ?, ?)", [$org, $sel, $p['id'], $st[$k++], 'Жылдамдығы жақсы']);

		// Training Hub
		$lessons = [['technique', 'Допты екі аяқпен жүргізу', 'Конустар арасымен 10 метр, екі аяқпен кезектесіп', 'easy', '3 × 10', 'jNQXAC9IVRw'], ['physical', 'Жылдамдыққа жаттығу', 'Сатымен жүгіру және 20 м спринт', 'medium', '4 × 20 м', null], ['ball', 'Жонглирование', 'Допты жерге түсірмей ұрып тұру', 'easy', '5 мин', null], ['tactics', 'Позиция таңдау', 'Видеоны қарап, қорғаныс позициясын түсіну', 'medium', '1 видео', null]];
		$lids = [];
		foreach ($lessons as $l) $lids[] = db::ins("insert into training_lessons (organization_id, category, title, description, video_key, duration_min, level, reps, coach_tip, created_by) values (?, ?, ?, ?, ?, 15, ?, ?, 'Денені алға ұстаңыз, допқа қарамай жүргізуге тырысыңыз', ?)", [$org, $l[0], $l[1], $l[2], $l[5], $l[3], $l[4], $coach1]);
		$asg = db::ins("insert into training_assignments (organization_id, lesson_id, group_id, due_date, created_by) values (?, ?, ?, ?, ?)", [$org, $lids[0], $gids[0], date('Y-m-d', strtotime('+5 day')), $coach1]);
		db::ins("insert into training_assignments (organization_id, lesson_id, group_id, due_date, created_by) values (?, ?, ?, ?, ?)", [$org, $lids[2], $gids[1], date('Y-m-d', strtotime('+3 day')), $coach1]);

		// медиа (YouTube видеолар) + келісімдер
		$alb = db::ins("insert into media_albums (organization_id, name) values (?, 'ЛЛФ 1-тур')", [$org]);
		db::q("insert into media_assets (organization_id, album_id, type, kind, storage_key, title, visibility, uploaded_by) values (?, ?, 'video', 'highlight', 'yt:jNQXAC9IVRw', 'Матч шолуы', 'parents', ?)", [$org, $alb, $u['87000000002']]);
		foreach ($parts as $i => $p) if ($i % 2 == 0) db::q("insert into media_consents (organization_id, participant_id, scope, granted_at, recorded_by) values (?, ?, 'internal', now(), ?)", [$org, $p['id'], $u['87000000002']]);

		// маркетинг: контент жоспары, тікелей эфир
		foreach ([['instagram', 'KIDS жаттығуынан reels', 'ready', 1], ['tiktok', 'Голдар топ-5', 'in_work', 3], ['instagram', 'Күзгі қабылдау жарнамасы', 'idea', 6]] as $c)
			db::q("insert into content_items (organization_id, channel, title, publish_at, status, assignee_id) values (?, ?, ?, ?, ?, ?)", [$org, $c[0], $c[1], date('Y-m-d 19:00:00', strtotime("+{$c[3]} day")), $c[2], $u['87000000002']]);
		$nm = db::row("select id, match_at from matches where team_id = ? and status = 'scheduled' order by match_at limit 1", [$team]);
		if ($nm) db::q("insert into live_streams (organization_id, match_id, platform, title, url, youtube_id, starts_at) values (?, ?, 'youtube', 'ЛЛФ: JAS FIRST TEAM тікелей эфир', 'https://www.youtube.com/watch?v=jNQXAC9IVRw', 'jNQXAC9IVRw', ?)", [$org, $nm['id'], $nm['match_at']]);
	});

	echo "seed ok\nДемо кіру: 87000000001 (иесі), 87000000002 (әкімші), 87000000003 (бапкер), 87000000005 (ата-ана), 87000000006 (қатысушы)\nҚұпия сөз: jas2026\n";
