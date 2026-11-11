<?php

	require_once __DIR__.'/db.class.php';
	require_once __DIR__.'/fun.php';
	require_once __DIR__.'/t.php';
	require_once __DIR__.'/academy.php';

	class core {
		public static $user_data = [];
		public static $org_id = 0;
		public static $roles = [];
		public static $role = false;   // ең жоғары рөл

		// рөлдердің маңыздылық реті
		public static $role_order = ['owner', 'admin', 'coach', 'parent', 'participant'];

		public function __construct() {
			new db; new fun; new t;
			date_default_timezone_set('Asia/Almaty');
			if (php_sapi_name() != 'cli') {
				session_name('jas');
				session_set_cookie_params([
					'lifetime' => 3600 * 24 * 30,
					'path' => '/',
					'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off',
					'httponly' => true,
					'samesite' => 'Lax',
				]);
				session_start();
				$this->authorize();
				$this->csrf_check();
			}
		}

		// сессиядан қолданушыны тексеру. Құпия сөз cookie/session-ға жазылмайды.
		private function authorize() {
			if (empty($_SESSION['uid'])) return;
			$u = db::row("select * from users where id = ? and locked_at is null", [(int) $_SESSION['uid']]);
			if (!$u || (int) $u['session_ver'] !== (int) @$_SESSION['uver']) { $this->user_unset(); return; }

			$org_id = (int) @$_SESSION['org'];
			$roles = array_column(db::all("select role from memberships where user_id = ? and organization_id = ? and active = 1", [(int) $u['id'], $org_id]), 'role');
			if (!$roles) { $this->user_unset(); return; }

			self::$user_data = $u;
			self::$org_id = $org_id;
			self::$roles = $roles;
			foreach (self::$role_order as $r) if (in_array($r, $roles)) { self::$role = $r; break; }
		}

		// кіргеннен кейін
		public static function sign_in($user_d, $org_id) {
			session_regenerate_id(true);
			$_SESSION['uid'] = (int) $user_d['id'];
			$_SESSION['uver'] = (int) $user_d['session_ver'];
			$_SESSION['org'] = (int) $org_id;
			$_SESSION['csrf'] = bin2hex(random_bytes(32));
		}

		public function user_unset() {
			self::$user_data = [];
			self::$org_id = 0;
			self::$roles = [];
			self::$role = false;
			unset($_SESSION['uid'], $_SESSION['uver'], $_SESSION['org']);
		}



		// рөл тексеру: core::is(['owner','admin'])
		public static function is($roles) {
			return (bool) array_intersect((array) $roles, self::$roles);
		}
		// get.php ішінде: рұқсат жоқ болса 403
		public static function need($roles = null) {
			if (!self::$user_data) { http_response_code(401); echo 'sign'; exit(); }
			if ($roles && !self::is($roles)) { http_response_code(403); echo 'right'; exit(); }
		}
		// бет ішінде: рұқсат жоқ болса бағыттау
		public static function need_page($roles = null) {
			if (!self::$user_data) { header('location: /sign/'); exit(); }
			if ($roles && !self::is($roles)) { http_response_code(403); include __DIR__.'/../block/403.php'; exit(); }
		}



		// CSRF
		public static function csrf() {
			if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
			return $_SESSION['csrf'];
		}
		private function csrf_check() {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') return;
			$t = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : @$_POST['csrf'];
			if (empty($_SESSION['csrf']) || !is_string($t) || !hash_equals($_SESSION['csrf'], $t)) {
				http_response_code(419); echo 'csrf'; exit();
			}
		}
	}


	// база бос болса: жергілікті компьютерде (localhost) демо базаны автоматты орнату
	new db;
	if (php_sapi_name() != 'cli' && !db::query("SHOW TABLES LIKE 'organizations'")->num_rows) {
		$local = in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
		$dump = __DIR__.'/../sql/import/jas_demo.sql';
		if (!$local || !is_file($dump)) exit('База бос. sql/import/jas_demo.sql файлын phpMyAdmin арқылы импорттаңыз немесе php tools/migrate.php жүргізіңіз.');
		mysqli_multi_query(db::$connect, file_get_contents($dump));
		do { if ($r = mysqli_store_result(db::$connect)) mysqli_free_result($r); } while (mysqli_more_results(db::$connect) && mysqli_next_result(db::$connect));
	}

	// data
	$core = new core;
	$user = core::$user_data;
	$user_id = $user ? (int) $user['id'] : 0;
	$user_right = core::$role;
	$org_id = core::$org_id;


	// lang (кейін орыс тілі: t.php ішіне 'ru' қосылады)
	$lang = 'kz';
	if (isset($_GET['lang']) && in_array($_GET['lang'], t::$langs)) $_SESSION['lang'] = $_GET['lang'];
	if (isset($_SESSION['lang'])) $lang = $_SESSION['lang'];
	t::$lang = $lang;


	// setting
	$site = $org_id ? db::row("select * from organizations where id = ?", [$org_id]) : db::row("select * from organizations order by id limit 1");
	$ver = 1.001;
	$site_set = [
		'header' => true,
		'menu' => true,
		'pmenu' => true,
		'footer' => false,
	];
	$scss = ['norm', 'main'];
	$sjs = ['norm', 'main'];
	$css = []; $js = [];


	// date - time
	$date = date('Y-m-d');
	$time = date('H:i:s');
	$datetime = date('Y-m-d H:i:s');


	// url
	$url = $url_full = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
	$url = (explode('?', $url))[0];
