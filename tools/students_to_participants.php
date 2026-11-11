<?php
	// Ескі `students` кестесін `participants`-ке деректерді жоғалтпай көшіру.
	// php tools/students_to_participants.php          — тек тексереді (dry run)
	// php tools/students_to_participants.php --run    — көшіреді
	// Ескі кесте ӨШІРІЛМЕЙДІ: көшірілгеннен кейін `students_legacy` деп қайта аталады.
	// Бағандар әртүрлі болса, төмендегі $map-ты өзгертіңіз (participants баған => students баған).
	if (php_sapi_name() != 'cli') exit('cli only');
	require __DIR__.'/../config/db.class.php';
	new db;

	$run = in_array('--run', $argv);
	$org_id = 1;
	$map = [
		'first_name' => 'name',
		'last_name' => 'surname',
		'birth_date' => 'birthday',
		'phone' => 'phone',
		'joined_at' => 'created_at',
	];

	if (!db::val("select count(*) from information_schema.tables where table_schema = database() and table_name = 'students'")) exit("students кестесі жоқ — көшіретін ештеңе жоқ.\n");
	$cols = array_column(db::all("select column_name c from information_schema.columns where table_schema = database() and table_name = 'students'"), 'c');
	foreach ($map as $to => $from) if (!in_array($from, $cols)) exit("students.$from бағаны жоқ. \$map-ты түзетіңіз. Бар бағандар: ".implode(', ', $cols)."\n");

	$n = (int) db::val("select count(*) from students");
	echo "students: $n жазба\n";
	if (!$run) exit("Dry run. Көшіру үшін: --run\n");

	db::tx(function () use ($map, $org_id) {
		db::query("CREATE TABLE IF NOT EXISTS legacy_student_map (student_id int unsigned PRIMARY KEY, participant_id int unsigned NOT NULL)");
		foreach (db::all("select * from students") as $s) {
			if (db::val("select participant_id from legacy_student_map where student_id = ?", [(int) $s['id']])) continue;
			$birth = $s[$map['birth_date']] ?: '2000-01-01';
			$age = (int) date_diff(date_create($birth), date_create('today'))->y;
			$pid = db::ins("insert into participants (organization_id, first_name, last_name, birth_date, phone, direction, joined_at, notes) values (?, ?, ?, ?, ?, ?, ?, ?)", [
				$org_id, $s[$map['first_name']] ?: '—', $s[$map['last_name']] ?: '—', $birth, $s[$map['phone']] ?: null,
				$age >= 16 ? 'adult' : 'kids', substr($s[$map['joined_at']] ?: date('Y-m-d'), 0, 10), 'students кестесінен көшірілді (id '.$s['id'].')',
			]);
			db::q("insert into legacy_student_map (student_id, participant_id) values (?, ?)", [(int) $s['id'], $pid]);
		}
	});
	db::query("RENAME TABLE students TO students_legacy");
	echo "Дайын. Ескі кесте: students_legacy. Сәйкестік: legacy_student_map.\n";
