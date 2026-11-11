<?php
	// Тесттер: php tests/run.php
	// Бөлек `jas_test` базасын тазалап, миграция + seed жасайды, содан кейін тексереді.
	// Талап: MySQL-да jas_test базасы бар, config/db.local.php ішіндегі қолданушыға оған рұқсат бар.
	if (php_sapi_name() != 'cli') exit('cli only');
	putenv('JAS_DB=jas_test');
	$root = realpath(__DIR__.'/..');
	$php = PHP_BINARY;

	require $root.'/config/db.php';
	new db;

	// --- база дайындау
	db::query("SET FOREIGN_KEY_CHECKS = 0");
	foreach (db::all("select table_name t from information_schema.tables where table_schema = database()") as $t) db::query("DROP TABLE `{$t['t']}`");
	db::query("SET FOREIGN_KEY_CHECKS = 1");
	exec(escapeshellarg($php)." ".escapeshellarg("$root/tools/migrate.php")." jas_test", $o1, $rc1);
	exec(escapeshellarg($php)." ".escapeshellarg("$root/tools/seed.php")." jas_test", $o2, $rc2);
	require $root.'/config/core.php';

	$pass = 0; $fail = 0;
	function ok($name, $cond) {
		global $pass, $fail;
		if ($cond) { $pass++; echo "  ✓ $name\n"; } else { $fail++; echo "  ✗ $name\n"; }
	}
	function as_user($phone) {
		$u = db::row("select * from users where phone = ?", [$phone]);
		core::$user_data = $u; core::$org_id = 1;
		core::$roles = array_column(db::all("select role from memberships where user_id = ?", [(int) $u['id']]), 'role');
		return $u;
	}

	echo "\nМиграция және seed\n";
	ok('migrate', $rc1 === 0);
	ok('seed', $rc2 === 0);
	ok('40+ кесте', db::val("select count(*) from information_schema.tables where table_schema = database()") >= 40);
	ok('students кестесі жоқ, participants бар', !db::val("select count(*) from information_schema.tables where table_schema = database() and table_name = 'students'") && db::val("select count(*) from participants") == 44);


	echo "\nfun: жас, туған күн, телефон\n";
	ok('жас: туған күні өтпеген', fun::age('2015-12-01', '2026-11-11') === 10);
	ok('жас: туған күні бүгін', fun::age('2015-11-11', '2026-11-11') === 11);
	ok('29 ақпан → 28 ақпан (кібісе емес жыл)', fun::next_birthday('2016-02-29', '2027-02-01') === '2027-02-28');
	ok('туған күн жыл ауысқанда', fun::next_birthday('2010-01-03', '2026-12-30') === '2027-01-03');
	ok('телефон +7 → 8', fun::phone_clean('+7 (707) 123-45-67') === '87071234567');
	ok('телефон 10 сан', fun::phone_clean('7071234567') === '87071234567');
	ok('қате телефон', fun::phone_clean('12345') === false);
	ok('h() XSS', h('<script>"') === '&lt;script&gt;&quot;');


	echo "\nҚарыз және төлем\n";
	as_user('87000000002');
	$p = db::row("select p.id from participants p where not exists (select 1 from subscriptions s where s.participant_id = p.id) limit 1");
	$pid = (int) db::val("select id from participants order by id limit 1");
	$sub = db::ins("insert into subscriptions (organization_id, participant_id, plan_id, price, status) values (1, ?, 1, 25000, 'pending')", [$pid]);
	$before = fun::participant_debt($pid);
	$pay = db::ins("insert into payments (organization_id, participant_id, amount, method, status, idempotency_key, paid_at, entered_by) values (1, ?, 10000, 'cash', 'confirmed', 'test-1', now(), 2)", [$pid]);
	db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (1, ?, ?, 10000)", [$pay, $sub]);
	ok('ішінара төлем: қарыз 15000-ға азайды', abs(fun::participant_debt($pid) - ($before - 10000)) < 0.01);
	$pend = db::ins("insert into payments (organization_id, participant_id, amount, method, status, idempotency_key, paid_at, entered_by) values (1, ?, 15000, 'kaspi', 'pending', 'test-2', now(), 2)", [$pid]);
	db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (1, ?, ?, 15000)", [$pend, $sub]);
	ok('расталмаған төлем қарызды азайтпайды', abs(fun::participant_debt($pid) - ($before - 10000)) < 0.01);
	$rev = db::ins("insert into payments (organization_id, participant_id, amount, method, kind, status, idempotency_key, reversal_of_id, paid_at, entered_by) values (1, ?, -10000, 'cash', 'reversal', 'confirmed', 'test-3', ?, now(), 2)", [$pid, $pay]);
	db::q("insert into payment_allocations (organization_id, payment_id, subscription_id, amount) values (1, ?, ?, -10000)", [$rev, $sub]);
	ok('reversal қарызды қайтарады', abs(fun::participant_debt($pid) - $before) < 0.01);
	$dup = false; try { db::ins("insert into payments (organization_id, participant_id, amount, method, status, idempotency_key, paid_at, entered_by) values (1, ?, 1, 'cash', 'pending', 'test-1', now(), 2)", [$pid]); } catch (Exception $e) { $dup = true; }
	ok('idempotency key қайталанбайды', $dup);
	$dup = false; try { db::ins("insert into payments (organization_id, participant_id, amount, method, kind, status, idempotency_key, reversal_of_id, paid_at, entered_by) values (1, ?, -10000, 'cash', 'reversal', 'confirmed', 'test-4', ?, now(), 2)", [$pid, $pay]); } catch (Exception $e) { $dup = true; }
	ok('бір төлемге бір ғана reversal', $dup);


	echo "\nҚатысу және рейтинг\n";
	$aid = (int) db::val("select id from attendance where status = 'present' limit 1");
	$dup = false; try { db::q("insert into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id) select organization_id, participant_id, 'kids', 'attendance', 10, 'attendance', id from attendance where id = ?", [$aid]); } catch (Exception $e) { $dup = true; }
	ok('бір жаттығуға екі рет ұпай берілмейді', $dup);
	$a = db::row("select * from attendance limit 1");
	$dup = false; try { db::q("insert into attendance (organization_id, session_id, participant_id, status) values (1, ?, ?, 'present')", [(int) $a['session_id'], (int) $a['participant_id']]); } catch (Exception $e) { $dup = true; }
	ok('бір жаттығуда бір қатысу жазбасы', $dup);


	echo "\nҚолжетімділік (scope)\n";
	as_user('87000000003');
	list($sw, $sp) = fun::scope();
	$coach_n = (int) db::val("select count(*) from participants p where p.organization_id = 1 and $sw", $sp);
	$coach_real = (int) db::val("select count(*) from enrollments e join `groups` g on g.id = e.group_id where e.end_date is null and g.coach_id = 3");
	ok("бапкер тек өз тобын көреді ($coach_n)", $coach_n == $coach_real && $coach_n < 44);
	$other = (int) db::val("select e.participant_id from enrollments e join `groups` g on g.id = e.group_id where g.coach_id = 4 and e.end_date is null limit 1");
	ok('бапкер басқа топтың қатысушысын ашпайды', fun::participant($other) === null);
	as_user('87000000005');
	list($sw, $sp) = fun::scope();
	ok('ата-ана тек өз балаларын көреді (2)', (int) db::val("select count(*) from participants p where p.organization_id = 1 and $sw", $sp) === 2);
	as_user('87000000006');
	list($sw, $sp) = fun::scope();
	ok('қатысушы тек өзін көреді', (int) db::val("select count(*) from participants p where p.organization_id = 1 and $sw", $sp) === 1);
	as_user('87000000001');
	list($sw, $sp) = fun::scope();
	ok('иесі бәрін көреді', (int) db::val("select count(*) from participants p where p.organization_id = 1 and $sw", $sp) === 44);


	echo "\nТоп ауысу тарихы\n";
	as_user('87000000002');
	$g_old = (int) db::val("select group_id from enrollments where participant_id = ? and end_date is null", [$pid]);
	$g_new = (int) db::val("select id from `groups` where direction = (select direction from participants where id = ?) and id <> ? limit 1", [$pid, $g_old]);
	fun::participant_set_group($pid, $g_new, 'тест');
	ok('жаңа топ белсенді', (int) db::val("select group_id from enrollments where participant_id = ? and end_date is null", [$pid]) === $g_new);
	ok('ескі топ тарихта сақталды', (int) db::val("select count(*) from enrollments where participant_id = ? and group_id = ? and end_date is not null", [$pid, $g_old]) === 1);


	// --- HTTP тесттері (PHP built-in server)
	echo "\nHTTP: auth, рөлдер, CSRF, Participants API\n";
	$port = 8097;
	$router = $root.'/tests/router.php';
	$null = DIRECTORY_SEPARATOR == '\\' ? 'NUL' : '/dev/null';
	$cmd = [$php, '-d', 'short_open_tag=On', '-S', "127.0.0.1:$port", '-t', $root, $router];
	$srv = proc_open(PHP_VERSION_ID >= 70400 ? $cmd : implode(' ', array_map('escapeshellarg', $cmd)), [1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes, $root, array_merge(getenv(), ['JAS_DB' => 'jas_test']));
	usleep(700000);

	function http($method, $path, $data = null, &$jar = null, $csrf = true) {
		global $port;
		$h = curl_init("http://127.0.0.1:$port$path");
		$jar = $jar ?: tempnam(sys_get_temp_dir(), 'jar');
		curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HEADER => true]);
		if ($method == 'POST') {
			$tok = '';
			if ($csrf) { $page = http('GET', '/sign/', null, $jar); if ($page['code'] != 200) $page = http('GET', '/participants/', null, $jar); preg_match('/name="csrf" content="([a-f0-9]+)"/', $page['body'], $m); $tok = isset($m[1]) ? $m[1] : ''; }
			curl_setopt($h, CURLOPT_POST, true);
			curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data ?: []));
			if ($tok) curl_setopt($h, CURLOPT_HTTPHEADER, ['X-CSRF-Token: '.$tok]);
		}
		$r = curl_exec($h);
		$code = curl_getinfo($h, CURLINFO_HTTP_CODE);
		$hs = curl_getinfo($h, CURLINFO_HEADER_SIZE);
		curl_close($h);
		return ['code' => $code, 'head' => substr($r, 0, $hs), 'body' => substr($r, $hs)];
	}
	function login($phone, $pw = 'jas2026') {
		$jar = null;
		$r = http('POST', '/sign/get.php?sign_in', ['phone' => $phone, 'password' => $pw], $jar);
		return [$r['body'], $jar];
	}

	$jar = null;
	$r = http('GET', '/dashboard/', null, $jar);
	ok('кірмеген адам → /sign/', $r['code'] == 302 && strpos($r['head'], '/sign/') !== false);
	ok('config/ браузерден жабық', http('GET', '/config/db.php')['code'] == 403);
	list($b) = login('87000000002', 'wrong-pass');
	ok('қате құпия сөз', $b == 'none');
	$jar = null;
	$r = http('POST', '/sign/get.php?sign_in', ['phone' => '87000000002', 'password' => 'jas2026'], $jar, false);
	ok('CSRF токенсіз POST → 419', $r['code'] == 419);

	list($b, $admin) = login('87000000002');
	ok('әкімші кірді', $b == 'yes');
	ok('әкімші dashboard ашады', http('GET', '/dashboard/', null, $admin)['code'] == 200);
	ok('dashboard қаржыны көрсетеді', strpos(http('GET', '/dashboard/', null, $admin)['body'], 'Таза нәтиже') !== false);

	list($b, $parent) = login('87000000005');
	ok('ата-ана dashboard → 403', http('GET', '/dashboard/', null, $parent)['code'] == 403);
	$kid = (int) db::val("select pg.participant_id from participant_guardians pg join guardians g on g.id = pg.guardian_id where g.phone = '87000000005' limit 1");
	$stranger = (int) db::val("select id from participants where id not in (select pg.participant_id from participant_guardians pg join guardians g on g.id = pg.guardian_id where g.phone = '87000000005') limit 1");
	ok('ата-ана өз баласын ашады', http('GET', "/participants/item.php?id=$kid", null, $parent)['code'] == 200);
	ok('ата-ана басқа баланы ашпайды (404)', http('GET', "/participants/item.php?id=$stranger", null, $parent)['code'] == 404);

	list($b, $coach) = login('87000000003');
	$r = http('POST', '/participants/get.php?add', ['first_name' => 'Тест', 'last_name' => 'Бапкер', 'birth_date' => '2015-01-01', 'direction' => 'kids'], $coach);
	ok('бапкер қатысушы қоса алмайды (403)', $r['code'] == 403);
	ok('бапкер dashboard-та қаржыны көрмейді', strpos(http('GET', '/dashboard/', null, $coach)['body'], 'Таза нәтиже') === false);

	$r = json_decode(http('POST', '/participants/get.php?add', ['first_name' => '', 'birth_date' => '2030-01-01', 'direction' => 'kids'], $admin)['body'], true);
	ok('validation: қате өрістер қайтарылады', $r && !$r['ok'] && isset($r['errors']['first_name'], $r['errors']['birth_date'], $r['errors']['guardian_phone']));
	$new = ['first_name' => 'Нұрым', 'last_name' => 'Тестов', 'birth_date' => '2016-05-10', 'direction' => 'kids', 'group_id' => 1, 'guardian_name' => 'Ата-анасы', 'guardian_phone' => '87019998877', 'position' => 'mf', 'number' => '10', 'name_xss' => '<b>'];
	$r = json_decode(http('POST', '/participants/get.php?add', $new, $admin)['body'], true);
	ok('әкімші қатысушы қосты', $r && $r['ok'] && $r['id'] > 0);
	$nid = $r ? (int) $r['id'] : 0;
	ok('топқа тіркелді', (int) db::val("select group_id from enrollments where participant_id = ? and end_date is null", [$nid]) === 1);
	ok('ата-ана байланысты', (int) db::val("select count(*) from participant_guardians where participant_id = ?", [$nid]) === 1);
	ok('audit log жазылды', (int) db::val("select count(*) from audit_logs where entity = 'participants' and entity_id = ? and action = 'create'", [$nid]) === 1);
	$r = json_decode(http('POST', '/participants/get.php?add', $new, $admin)['body'], true);
	ok('бір адамға бір профиль (қайталау бөгеледі)', $r && !$r['ok'] && isset($r['errors']['last_name']));
	$r = json_decode(http('POST', '/participants/get.php?set_group', ['id' => $nid, 'group_id' => 3], $admin)['body'], true);
	ok('KIDS қатысушыны ADULT тобына ауыстыруға болмайды', $r && !$r['ok']);
	$r = json_decode(http('POST', '/participants/get.php?set_group', ['id' => $nid, 'group_id' => 2], $admin)['body'], true);
	ok('топ ауыстыру', $r && $r['ok'] && (int) db::val("select count(*) from enrollments where participant_id = ?", [$nid]) === 2);
	$r = json_decode(http('POST', '/participants/get.php?archive', ['id' => $nid], $admin)['body'], true);
	ok('архив: өшірілмейді', $r && $r['ok'] && db::val("select archived_at from participants where id = ?", [$nid]));
	$list = http('GET', '/participants/?q='.urlencode('Тестов').'&st=archive', null, $admin)['body'];
	ok('іздеу + архив сүзгісі', strpos($list, 'Тестов Нұрым') !== false);
	$list = http('GET', '/participants/?q='.urlencode('<script>alert(1)</script>'), null, $admin)['body'];
	ok('іздеу өрісі XSS қорғалған', strpos($list, '<script>alert(1)</script>') === false);

	echo "\nКесте, қатысу, рейтинг\n";
	$c3 = (int) db::val("select id from users where phone = '87000000003'");
	$r = json_decode(http('POST', '/schedule/get.php?session_add', ['group_id' => 1, 'date' => date('Y-m-d'), 'start' => '06:00', 'end' => '07:00'], $admin)['body'], true);
	ok('қосымша жаттығу қосылды', $r && $r['ok']);
	$sid = $r ? (int) $r['id'] : 0;
	$r = json_decode(http('POST', '/schedule/get.php?session_add', ['group_id' => 2, 'date' => date('Y-m-d'), 'start' => '06:30', 'end' => '07:30'], $admin)['body'], true);
	ok('алаң/бапкер қақтығысы бөгеледі', $r && !$r['ok'] && strpos($r['mess'], 'Қақтығыс') !== false);
	$kids = array_column(db::all("select participant_id from enrollments where group_id = 1 and end_date is null"), 'participant_id');
	$marks = []; foreach ($kids as $k) $marks[$k] = 'present';
	list($b, $coach4) = login('87000000004');
	$r = http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => $marks], $coach4);
	ok('басқа бапкер бөтен топты белгілей алмайды', ($x = json_decode($r['body'], true)) && !$x['ok']);
	ok('ата-ана қатысу белгілей алмайды (403)', http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => $marks], $parent)['code'] == 403);
	$before = (int) db::val("select count(*) from rating_events where rule = 'attendance'");
	$r = json_decode(http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => $marks], $coach)['body'], true);
	ok('бапкер өз тобын белгіледі', $r && $r['ok'] && $r['changed'] == count($kids));
	ok('жаттығу өткізілді (done)', db::val("select status from sessions where id = ?", [$sid]) == 'done');
	ok('+10 ұпай әр келгенге', (int) db::val("select count(*) from rating_events where rule = 'attendance'") == $before + count($kids));
	$r = json_decode(http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => $marks], $coach)['body'], true);
	ok('қайта сақтау ұпайды екі рет бермейді', $r && $r['ok'] && $r['changed'] == 0 && (int) db::val("select count(*) from rating_events where rule = 'attendance'") == $before + count($kids));
	$k0 = (int) $kids[0];
	http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => [$k0 => 'absent']], $coach);
	ok('келмеді деп түзетілсе ұпай алынады', !db::val("select r.id from rating_events r join attendance a on a.id = r.source_id where r.rule = 'attendance' and a.session_id = ? and a.participant_id = ?", [$sid, $k0]));
	$r = json_decode(http('POST', '/attendance/get.php?mark', ['id' => $sid, 'marks' => [$stranger => 'present']], $coach)['body'], true);
	ok('топта жоқ қатысушыны белгілеуге болмайды', $r && !$r['ok']);
	$fut = (int) db::val("select id from sessions where group_id = 1 and starts_at > ? and status = 'planned' limit 1", [date('Y-m-d 23:59:59')]);
	$r = json_decode(http('POST', '/attendance/get.php?mark', ['id' => $fut, 'marks' => [$kids[1] => 'present']], $coach)['body'], true);
	ok('болашақ жаттығу белгіленбейді', $r && !$r['ok']);
	$r = json_decode(http('POST', '/schedule/get.php?session_cancel', ['id' => $sid, 'reason' => 'тест'], $admin)['body'], true);
	ok('белгіленген жаттығу болдырылмайды', $r && !$r['ok']);

	// серия: 5 қатарынан келсе +25, келмеді серияны үзеді, себепті үзбейді
	$tp = (int) db::ins("insert into participants (organization_id, first_name, last_name, birth_date, direction, joined_at) values (1, 'Серия', 'Тест', '2015-01-01', 'kids', curdate())");
	$st5 = ['present', 'present', 'excused', 'present', 'present', 'present', 'absent', 'present'];
	foreach ($st5 as $i => $x) {
		$s = db::ins("insert into sessions (organization_id, group_id, type, starts_at, ends_at, status) values (1, 1, 'extra', ?, ?, 'done')", [date('Y-m-d 05:00:00', strtotime('-'.(40 - $i).' day')), date('Y-m-d 06:00:00', strtotime('-'.(40 - $i).' day'))]);
		db::q("insert into attendance (organization_id, session_id, participant_id, status) values (1, ?, ?, ?)", [$s, $tp, $x]);
	}
	academy::rating_sync($tp); academy::rating_sync($tp);
	ok('streak5 бір рет, себепті серияны үзбейді', (int) db::val("select count(*) from rating_events where participant_id = ? and rule = 'streak5'", [$tp]) === 1);
	ok('rating_sync қайталанбайды', (int) db::val("select sum(points) from rating_events where participant_id = ?", [$tp]) === 6 * 10 + 25);
	ok('ұпай жаттығу күнімен жазылады', db::val("select min(date(created_at)) from rating_events where participant_id = ?", [$tp]) == date('Y-m-d', strtotime('-40 day')));
	$r = json_decode(http('POST', '/rating/get.php?progress', ['participant_id' => $kids[1], 'comment' => 'Пас жақсарды'], $coach)['body'], true);
	ok('прогресс +20', $r && $r['ok']);
	$r = json_decode(http('POST', '/rating/get.php?progress', ['participant_id' => $kids[1], 'comment' => 'Тағы'], $coach)['body'], true);
	ok('прогресс айына бір рет', $r && !$r['ok']);
	$r = json_decode(http('POST', '/rating/get.php?award', ['participant_id' => $stranger, 'achievement_id' => 1], $coach)['body'], true);
	ok('бапкер бөтен қатысушыны марапаттай алмайды', $r && !$r['ok']);
	ok('ата-ана рейтингте тек өз баласын көреді', substr_count(http('GET', '/rating/?t=all', null, $parent)['body'], 'rt_pl rt_pl') <= 2);


	echo "\nАбонемент, төлем, заморозка\n";
	$pp = (int) db::val("select p.id from participants p where p.direction = 'kids' and p.archived_at is null and not exists (select 1 from subscriptions s where s.participant_id = p.id and s.status = 'pending') limit 1");
	$plan = (int) db::val("select id from plans where direction = 'kids' limit 1");
	$r = json_decode(http('POST', '/subscriptions/get.php?sell', ['participant_id' => $pp, 'plan_id' => $plan, 'discount' => 0], $admin)['body'], true);
	ok('абонемент сатылды (төлем күтілуде)', $r && $r['ok'] && db::val("select status from subscriptions where id = ?", [$r['id']]) == 'pending');
	$sub = $r ? (int) $r['id'] : 0;
	$price = (float) db::val("select price from subscriptions where id = ?", [$sub]);
	$r = json_decode(http('POST', '/subscriptions/get.php?sell', ['participant_id' => $pp, 'plan_id' => $plan], $admin)['body'], true);
	ok('екінші төленбеген абонемент бөгеледі', $r && !$r['ok']);
	$adult_plan = (int) db::val("select id from plans where direction = 'adult' limit 1");
	$r = json_decode(http('POST', '/subscriptions/get.php?sell', ['participant_id' => $pp, 'plan_id' => $adult_plan], $admin)['body'], true);
	ok('бағытқа сәйкес емес тариф бөгеледі', $r && !$r['ok']);
	$pay = ['participant_id' => $pp, 'subscription_id' => $sub, 'amount' => $price / 2, 'method' => 'kaspi_red', 'external_ref' => 'TEST-1', 'paid_at' => date('Y-m-d\TH:i'), 'status' => 'confirmed', 'idem' => str_repeat('a', 32)];
	ok('бапкер төлем енгізе алмайды (403)', http('POST', '/payments/get.php?add', $pay, $coach)['code'] == 403);
	$r = json_decode(http('POST', '/payments/get.php?add', $pay, $admin)['body'], true);
	ok('ішінара төлем расталды', $r && $r['ok']);
	$p1 = $r ? (int) $r['id'] : 0;
	ok('RED комиссиясы есептелді', abs((float) db::val("select fee_amount from payments where id = ?", [$p1]) - $price / 2 * 0.08) < 0.01);
	$s = db::row("select * from subscriptions where id = ?", [$sub]);
	ok('абонемент төлем күнінен басталды, 1 ай', $s['status'] == 'active' && $s['start_date'] == date('Y-m-d') && $s['end_date'] == date('Y-m-d', strtotime('+1 month')));
	ok('қарыз = міндеттеме − расталған', abs(fun::participant_debt($pp) - $price / 2) < 0.01);
	$r = json_decode(http('POST', '/payments/get.php?add', $pay, $admin)['body'], true);
	ok('сол форма қайта жіберілсе екінші төлем жоқ', $r && !empty($r['dup']) && (int) db::val("select count(*) from payments where participant_id = ? and kind = 'payment'", [$pp]) >= 1 && (int) db::val("select count(*) from payments where idempotency_key = ?", [str_repeat('a', 32)]) === 1);
	$r = json_decode(http('POST', '/payments/get.php?add', array_merge($pay, ['idem' => str_repeat('b', 32), 'external_ref' => 'TEST-2', 'amount' => $price]), $admin)['body'], true);
	ok('артық төлем бөгеледі', $r && !$r['ok']);
	$r = json_decode(http('POST', '/payments/get.php?add', array_merge($pay, ['idem' => str_repeat('c', 32), 'amount' => 100]), $admin)['body'], true);
	ok('бір операция нөмірі екі рет енгізілмейді', $r && !$r['ok']);
	$r = json_decode(http('POST', '/payments/get.php?add', array_merge($pay, ['idem' => str_repeat('d', 32), 'external_ref' => 'TEST-3', 'status' => 'pending', 'amount' => $price / 2]), $admin)['body'], true);
	ok('расталмаған төлем қарызды жаппайды', $r && $r['ok'] && abs(fun::participant_debt($pp) - $price / 2) < 0.01);
	http('POST', '/payments/get.php?confirm', ['id' => $r['id']], $admin);
	ok('растаудан кейін қарыз 0', fun::participant_debt($pp) == 0);

	// заморозка
	$r = json_decode(http('POST', '/subscriptions/get.php?freeze_add', ['id' => $sub, 'start' => date('Y-m-d', strtotime('+2 day')), 'days' => 5, 'approve' => 1], $admin)['body'], true);
	ok('5 күн заморозка бекітілді, мерзім ұзарды', $r && $r['ok'] && db::val("select end_date from subscriptions where id = ?", [$sub]) == date('Y-m-d', strtotime('+1 month +5 day')));
	$r = json_decode(http('POST', '/subscriptions/get.php?freeze_add', ['id' => $sub, 'start' => date('Y-m-d', strtotime('+10 day')), 'days' => 3, 'approve' => 1], $admin)['body'], true);
	ok('жалпы 7 күннен асатын заморозка бөгеледі', $r && !$r['ok']);

	// сторно
	$r = json_decode(http('POST', '/payments/get.php?reverse', ['id' => $p1, 'reason' => 'Қате сома'], $admin)['body'], true);
	ok('сторно: кері жазба жасалды, бастапқы төлем өшпеді', $r && $r['ok'] && db::val("select amount from payments where reversal_of_id = ?", [$p1]) == -$price / 2 && db::val("select id from payments where id = ?", [$p1]));
	ok('сторнодан кейін қарыз қайта есептелді', abs(fun::participant_debt($pp) - $price / 2) < 0.01);
	$r = json_decode(http('POST', '/payments/get.php?reverse', ['id' => $p1, 'reason' => 'Тағы'], $admin)['body'], true);
	ok('бір төлемді екі рет сторно жасауға болмайды', $r && !$r['ok']);
	ok('төлем әрекеттері audit log-та', (int) db::val("select count(*) from audit_logs where entity = 'payments' and action in ('create', 'confirm', 'reverse')") >= 3);


	echo "\nFirst Team & ЛЛФ\n";
	$m = db::row("select * from matches where status = 'scheduled' order by match_at limit 1");
	$pl = (int) db::val("select participant_id from team_rosters where left_at is null limit 1");
	$r = json_decode(http('POST', '/first_team/get.php?match_save', ['id' => $m['id'], 'status' => 'finished', 'goals_for' => 1, 'goals_against' => 0], $admin)['body'], true);
	ok('басталмаған матч аяқталмайды', $r && !$r['ok']);
	$fin = db::row("select * from matches where status = 'finished' order by id limit 1");
	$r = json_decode(http('POST', '/first_team/get.php?match_save', ['id' => $fin['id'], 'status' => 'finished', 'goals_for' => 1, 'goals_against' => 0, 'players' => [$pl => ['goals' => 3, 'minute_out' => 90]]], $admin)['body'], true);
	ok('футболшы голы команда голынан көп бола алмайды', $r && !$r['ok']);
	$used = academy::sub_used(db::row("select * from subscriptions where participant_id = ? and status = 'active' order by id desc limit 1", [$pl]) ?: ['start_date' => null]);
	$r = json_decode(http('POST', '/first_team/get.php?match_save', ['id' => $fin['id'], 'status' => 'finished', 'goals_for' => 2, 'goals_against' => 1, 'mvp' => $pl, 'players' => [$pl => ['is_starter' => 1, 'minute_in' => 0, 'minute_out' => 90, 'goals' => 2]]], $admin)['body'], true);
	ok('матч нәтижесі мен статистика сақталды', $r && $r['ok'] && (int) db::val("select goals from match_player_stats where match_id = ? and participant_id = ?", [$fin['id'], $pl]) === 2);
	ok('матч абонемент лимитін азайтпайды', $used === academy::sub_used(db::row("select * from subscriptions where participant_id = ? and status = 'active' order by id desc limit 1", [$pl]) ?: ['start_date' => null]));
	ok('қатысушы матч бетін тек көреді', http('POST', '/first_team/get.php?match_save', ['id' => $fin['id'], 'status' => 'finished'], login('87000000006')[1])['code'] == 403);


	echo "\nПрогресс, іріктеу, кабинет, медиа, сабақтар, эфир, CRM, хабарлама, есеп\n";
	$pa = (int) db::val("select participant_id from player_assessments order by id limit 1");
	$crit = array_column(db::all("select id from assessment_criteria where organization_id = 1 and active = 1"), 'id');
	$sc = []; foreach ($crit as $c) $sc[$c] = 10;
	$r = json_decode(http('POST', '/progress/get.php?add', ['id' => $pa, 'date' => date('Y-m-d'), 'scores' => [$crit[0] => 11, $crit[1] => 5, $crit[2] => 5]], $admin)['body'], true);
	ok('бағалау: 1–10 сыртындағы балл бөгеледі', $r && !$r['ok']);
	$r = json_decode(http('POST', '/progress/get.php?add', ['id' => $pa, 'date' => date('Y-m-d'), 'scores' => [$crit[0] => 7]], $admin)['body'], true);
	ok('бағалау: кемінде 3 көрсеткіш', $r && !$r['ok']);
	$r = json_decode(http('POST', '/progress/get.php?add', ['id' => $pa, 'date' => date('Y-m-d'), 'scores' => $sc], $admin)['body'], true);
	ok('бағалау сақталды, өссе +20 прогресс ұпайы', $r && $r['ok'] && (int) db::val("select count(*) from rating_events where participant_id = ? and rule = 'progress'", [$pa]) === 1);
	http('POST', '/progress/get.php?add', ['id' => $pa, 'date' => date('Y-m-d'), 'scores' => $sc], $admin);
	ok('прогресс ұпайы айына бір рет', (int) db::val("select count(*) from rating_events where participant_id = ? and rule = 'progress'", [$pa]) === 1);
	ok('ата-ана бағалау қоя алмайды', http('POST', '/progress/get.php?add', ['id' => $pa, 'date' => date('Y-m-d'), 'scores' => $sc], $parent)['code'] == 403);

	$x = db::row("select x.id, x.participant_id from selection_candidates x join selection_campaigns c on c.id = x.campaign_id join participants p on p.id = x.participant_id where c.target = 'first_team' and c.status = 'open' and p.direction = 'adult' limit 1");
	$r = json_decode(http('POST', '/scouting/get.php?candidate_status', ['id' => $x['id'], 'status' => 'accepted'], $admin)['body'], true);
	ok('іріктеуден өткен ересек First Team құрамына қосылды', $r && $r['ok'] && $r['roster'] && db::val("select id from team_rosters where participant_id = ? and left_at is null", [$x['participant_id']]));
	ok('ата-ана іріктеуді өзгерте алмайды', http('POST', '/scouting/get.php?candidate_status', ['id' => $x['id'], 'status' => 'failed'], $parent)['code'] == 403);

	$kids = array_column(db::all("select pg.participant_id from participant_guardians pg join guardians g on g.id = pg.guardian_id join users u on u.id = g.user_id where u.phone = '87000000005'"), 'participant_id');
	$alien_sub = (int) db::val("select id from subscriptions where status = 'active' and participant_id not in (".implode(',', array_map('intval', $kids)).") limit 1");
	$r = json_decode(http('POST', '/cabinet/get.php?freeze_request', ['id' => $alien_sub, 'start' => date('Y-m-d', strtotime('+1 day')), 'days' => 2, 'reason' => 'Ауырып қалды'], $parent)['body'], true);
	ok('ата-ана бөтен абонементке заморозка сұрай алмайды', $r && !$r['ok']);
	$my_sub = db::row("select * from subscriptions where status = 'active' and participant_id in (".implode(',', array_map('intval', $kids)).") and end_date > ? order by id desc limit 1", [date('Y-m-d', strtotime('+2 day'))]);
	if ($my_sub) {
		$r = json_decode(http('POST', '/cabinet/get.php?freeze_request', ['id' => $my_sub['id'], 'start' => date('Y-m-d', strtotime('-1 day')), 'days' => 2, 'reason' => 'Ауырып қалды'], $parent)['body'], true);
		ok('өткен күнге заморозка сұралмайды', $r && !$r['ok']);
		db::q("delete from freezes where subscription_id = ? and status = 'requested'", [$my_sub['id']]);
		$r = json_decode(http('POST', '/cabinet/get.php?freeze_request', ['id' => $my_sub['id'], 'start' => date('Y-m-d', strtotime('+12 day')), 'days' => 2, 'reason' => 'Ауырып қалды'], $parent)['body'], true);
		ok('заморозка өтініші жасалды (requested), әкімшіге хабарлама', $r && $r['ok'] && db::val("select id from freezes where subscription_id = ? and status = 'requested'", [$my_sub['id']]) && db::val("select count(*) from notifications n join users u on u.id = n.user_id where u.phone = '87000000002' and n.dedupe_key like 'frz_%'"));
	} else ok('ата-ана баласының белсенді абонементі бар', false);

	$no_consent = (int) db::val("select p.id from participants p where p.organization_id = 1 and not exists (select 1 from media_consents c where c.participant_id = p.id and c.scope = 'internal' and c.revoked_at is null) limit 1");
	$r = json_decode(http('POST', '/media/get.php?upload', ['visibility' => 'parents', 'youtube' => 'https://youtu.be/jNQXAC9IVRw', 'tags' => [$no_consent]], $admin)['body'], true);
	ok('келісімсіз қатысушы белгіленген медиа ата-аналарға жарияланбайды', $r && !$r['ok']);
	$r = json_decode(http('POST', '/media/get.php?upload', ['visibility' => 'staff', 'youtube' => 'https://youtu.be/dQw4w9WgXcQ', 'tags' => [$no_consent]], $admin)['body'], true);
	ok('«тек қызметкерлер» көрінуімен сақталады', $r && $r['ok']);
	$r = json_decode(http('POST', '/media/get.php?upload', ['visibility' => 'staff', 'youtube' => 'https://youtu.be/dQw4w9WgXcQ'], $admin)['body'], true);
	ok('бір видео екі рет қосылмайды (қате бетсіз)', $r && !$r['ok']);
	$fn = 'test_'.bin2hex(random_bytes(4)).'.jpg';
	file_put_contents($root.'/assets/uploads/media/'.$fn, base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='));
	$mid = db::ins("insert into media_assets (organization_id, type, storage_key, visibility) values (1, 'photo', ?, 'staff')", ['img:'.$fn]);
	ok('рұқсаты бар қызметкер фотоны ашады', http('GET', '/media/file.php?id='.$mid, null, $admin)['code'] == 200);
	ok('ата-ана «тек қызметкер» фотосын ала алмайды', http('GET', '/media/file.php?id='.$mid, null, $parent)['code'] == 404);
	ok('тікелей /assets/uploads/media/ URL жабық', http('GET', '/assets/uploads/media/'.$fn, null, $parent)['code'] == 403);
	@unlink($root.'/assets/uploads/media/'.$fn);

	$asg = db::row("select a.* from training_assignments a where a.group_id is not null limit 1");
	$r = json_decode(http('POST', '/training/get.php?submit', ['id' => $asg['id'], 'participant_id' => $alien = (int) db::val("select participant_id from enrollments where end_date is null and group_id <> ? limit 1", [$asg['group_id']])], $admin)['body'], true);
	ok('тапсырма басқа топтың қатысушысына жатпайды', $r && !$r['ok']);
	$own = (int) db::val("select participant_id from enrollments where end_date is null and group_id = ? limit 1", [$asg['group_id']]);
	$r = json_decode(http('POST', '/training/get.php?submit', ['id' => $asg['id'], 'participant_id' => $own], $parent)['body'], true);
	ok('ата-ана бөтен баланың тапсырмасын жібере алмайды', in_array($own, $kids) || ($r && !$r['ok']));

	$r = json_decode(http('POST', '/live/get.php?add', ['title' => 'Тест эфир', 'url' => 'http://youtube.com/watch?v=jNQXAC9IVRw', 'starts_at' => date('Y-m-d\TH:i')], $admin)['body'], true);
	ok('эфир: https емес сілтеме бөгеледі', $r && !$r['ok']);
	$r = json_decode(http('POST', '/live/get.php?add', ['title' => 'Тест эфир', 'url' => 'https://evil.example.com/youtube.com', 'starts_at' => date('Y-m-d\TH:i')], $admin)['body'], true);
	ok('эфир: бөтен домен бөгеледі', $r && !$r['ok']);
	$r = json_decode(http('POST', '/live/get.php?add', ['title' => 'Тест эфир', 'url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw', 'starts_at' => date('Y-m-d\TH:i')], $admin)['body'], true);
	ok('эфир қосылды, YouTube ID алынды', $r && $r['ok'] && db::val("select youtube_id from live_streams where id = ?", [$r['id']]) == 'jNQXAC9IVRw');

	$r = json_decode(http('POST', '/crm/get.php?lead_add', ['name' => 'Тест Ата-ана', 'phone' => '87071112233', 'source' => 'instagram', 'direction' => 'kids'], $admin)['body'], true);
	ok('өтінім қосылды', $r && $r['ok']);
	$r = json_decode(http('POST', '/crm/get.php?lead_add', ['name' => 'Тест Ата-ана', 'phone' => '+7 707 111 22 33', 'source' => 'instagram'], $admin)['body'], true);
	ok('сол нөмірден қайталама белсенді өтінім бөгеледі', $r && !$r['ok']);
	ok('бапкер CRM-ге кірмейді', http('POST', '/crm/get.php?lead_add', ['name' => 'X', 'phone' => '87071112234'], $coach)['code'] == 403);

	$uid_p = (int) db::val("select id from users where phone = '87000000005'");
	$uid_a = (int) db::val("select id from users where phone = '87000000002'");
	academy::notify_user(1, $uid_p, 'other', 'Тест', null, null, 'test_p');
	academy::notify_user(1, $uid_p, 'other', 'Тест', null, null, 'test_p');
	ok('хабарлама dedupe: екі рет жасалмайды', (int) db::val("select count(*) from notifications where user_id = ? and dedupe_key = 'test_p'", [$uid_p]) === 1);
	$a_unread = (int) db::val("select count(*) from notifications where user_id = ? and read_at is null", [$uid_a]);
	$r = json_decode(http('POST', '/notifications/get.php?read_all', [], $parent)['body'], true);
	ok('ата-ана өз хабарламаларын оқыды', $r && $r['ok'] && !(int) db::val("select count(*) from notifications where user_id = ? and read_at is null", [$uid_p]));
	ok('басқа адамның хабарламасына әсер етпейді', (int) db::val("select count(*) from notifications where user_id = ? and read_at is null", [$uid_a]) === $a_unread && $a_unread > 0);

	ok('бапкер есептерді көрмейді', http('GET', '/reports/', null, $coach)['code'] == 403);
	$csv = http('GET', '/reports/?csv=debtors', null, $admin);
	ok('есеп CSV экспорты (Excel BOM)', $csv['code'] == 200 && substr($csv['body'], 0, 3) == "\xEF\xBB\xBF");
	$pg = http('GET', '/reports/', null, $admin);
	ok('есептер беті ашылады', $pg['code'] == 200 && strpos($pg['body'], 'Ай сайынғы кіріс') !== false);


	for ($i = 0; $i < 5; $i++) login('87000000004', 'bad-pass-'.$i);
	list($b) = login('87000000004');
	ok('5 қате әрекеттен кейін бөгеледі', $b == 'limit');

	proc_terminate($srv);

	echo "\nНәтиже: $pass өтті, $fail құлады\n";
	exit($fail ? 1 : 0);
