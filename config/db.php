<?php

	// Деректер базасы.
	// Құпия сөздер осы файлда емес — config/db.local.php ішінде (ол .gitignore-да).
	class db {

		public static $host = 'localhost';
		public static $user = 'root';
		public static $pass = '';
		public static $name = 'jas';
		public static $connect = null;

		public function __construct() {
			if (self::$connect) return;
			if (is_file(__DIR__.'/db.local.php')) require __DIR__.'/db.local.php';
			if (getenv('JAS_DB')) self::$name = getenv('JAS_DB');   // тесттер үшін
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			try {
				self::$connect = mysqli_connect(self::$host, self::$user, self::$pass, self::$name);
			} catch (Exception $e) {
				exit("Настройте конфигурацию базы данных!");
			}
			mysqli_set_charset(self::$connect, 'utf8mb4');
			mysqli_query(self::$connect, "SET time_zone = '+05:00'");
		}

		// тек тұрақты (айнымалысыз) сұраныстар үшін
		public static function query($sql) {
			return mysqli_query(self::$connect, $sql);
		}

		// prepared statement: db::q("select * from participants where id = ?", [$id])
		public static function q($sql, $p = []) {
			$st = mysqli_prepare(self::$connect, $sql);
			if ($p) {
				$types = '';
				foreach ($p as $k => $v) {
					if (is_int($v) || is_bool($v)) $types .= 'i';
					elseif (is_float($v)) $types .= 'd';
					else $types .= 's';
					if (is_bool($v)) $p[$k] = (int) $v;
				}
				mysqli_stmt_bind_param($st, $types, ...$p);
			}
			mysqli_stmt_execute($st);
			$res = mysqli_stmt_get_result($st);
			if ($res === false) {
				$aff = mysqli_stmt_affected_rows($st);
				mysqli_stmt_close($st);
				return $aff;
			}
			return $res;
		}

		public static function all($sql, $p = []) {
			return mysqli_fetch_all(self::q($sql, $p), MYSQLI_ASSOC);
		}
		public static function row($sql, $p = []) {
			$r = mysqli_fetch_assoc(self::q($sql, $p));
			return $r ? $r : null;
		}
		public static function val($sql, $p = []) {
			$r = mysqli_fetch_row(self::q($sql, $p));
			return $r ? $r[0] : null;
		}
		public static function ins($sql, $p = []) {
			self::q($sql, $p);
			return (int) mysqli_insert_id(self::$connect);
		}

		// транзакция: қате болса бәрі кері қайтарылады
		public static function tx($fn) {
			mysqli_begin_transaction(self::$connect);
			try {
				$r = $fn();
				mysqli_commit(self::$connect);
				return $r;
			} catch (Throwable $e) {
				mysqli_rollback(self::$connect);
				throw $e;
			}
		}

	}
