<?php
	// Миграциялар: php tools/migrate.php
	// sql/ ішіндегі файлдарды ретімен орындайды, орындалғанын `migrations` кестесіне жазады.
	if (php_sapi_name() != 'cli') exit('cli only');

	require __DIR__.'/../config/db.class.php';
	if (!empty($argv[1])) db::$name = $argv[1];       // php tools/migrate.php jas_test
	new db;
	if (!empty($argv[1])) { mysqli_select_db(db::$connect, $argv[1]); }

	db::query("CREATE TABLE IF NOT EXISTS `migrations` (
		`name` varchar(120) NOT NULL PRIMARY KEY,
		`applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$files = glob(__DIR__.'/../sql/*.sql');
	sort($files);
	foreach ($files as $f) {
		$name = basename($f);
		if (db::val("select count(*) from migrations where name = ?", [$name])) { echo "skip  $name\n"; continue; }
		$sql = file_get_contents($f);
		if (!mysqli_multi_query(db::$connect, $sql)) exit("ERROR $name: ".mysqli_error(db::$connect)."\n");
		do { if ($r = mysqli_store_result(db::$connect)) mysqli_free_result($r); } while (mysqli_more_results(db::$connect) && mysqli_next_result(db::$connect));
		if (mysqli_errno(db::$connect)) exit("ERROR $name: ".mysqli_error(db::$connect)."\n");
		db::q("insert into migrations (name) values (?)", [$name]);
		echo "done  $name\n";
	}
	echo "ok\n";
