<?php

	// HTML-ге шығарғанда әрқашан h() арқылы
	function h($s) {
		return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
	}

	class fun {

		function __construct() {}

		public static $months = ['', 'қаңтар', 'ақпан', 'наурыз', 'сәуір', 'мамыр', 'маусым', 'шілде', 'тамыз', 'қыркүйек', 'қазан', 'қараша', 'желтоқсан'];
		public static $weekdays = ['', 'Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб', 'Жс'];



		// format
		public static function money($n) {
			return number_format((float) $n, 0, '.', ' ').' ₸';
		}
		public static function phone_clean($phone) {
			$p = preg_replace('/\D/', '', (string) $phone);
			if (strlen($p) == 11 && $p[0] == '7') $p[0] = '8';
			if (strlen($p) == 10) $p = '8'.$p;
			return (strlen($p) == 11 && $p[0] == '8') ? $p : false;
		}
		public static function phone_view($p) {
			if (!$p || strlen($p) != 11) return (string) $p;
			return $p[0].' ('.substr($p, 1, 3).') '.substr($p, 4, 3).'-'.substr($p, 7, 2).'-'.substr($p, 9, 2);
		}
		public static function date_kz($d) {
			if (!$d) return '—';
			$t = strtotime($d);
			return date('j', $t).' '.self::$months[(int) date('n', $t)].' '.date('Y', $t);
		}
		public static function date_short($d) {
			return $d ? date('d.m.Y', strtotime($d)) : '—';
		}
		public static function full_name($p) {
			return trim($p['last_name'].' '.$p['first_name']);
		}



		// жас: туған күннен бастап толық жыл
		public static function age($birth, $today = null) {
			if (!$birth) return null;
			$b = new DateTime($birth);
			$t = new DateTime($today ? $today : 'today');
			return $b > $t ? 0 : (int) $b->diff($t)->y;
		}

		// келесі туған күн (29 ақпан → кібісе емес жылы 28 ақпан)
		public static function next_birthday($birth, $today = null) {
			$t = new DateTime($today ? $today : 'today');
			$y = (int) $t->format('Y');
			for ($i = 0; $i < 2; $i++) {
				$m = (int) substr($birth, 5, 2); $d = (int) substr($birth, 8, 2);
				if ($m == 2 && $d == 29 && !checkdate(2, 29, $y + $i)) $d = 28;
				$n = new DateTime(sprintf('%04d-%02d-%02d', $y + $i, $m, $d));
				if ($n >= $t) return $n->format('Y-m-d');
			}
			return null;
		}

		// алдағы N күндегі туған күндер
		public static function birthdays($org_id, $days = 7, $today = null) {
			$today = $today ? $today : date('Y-m-d');
			$list = db::all("select id, first_name, last_name, birth_date, img, direction from participants
				where organization_id = ? and status = 'active' and archived_at is null", [(int) $org_id]);
			$end = date('Y-m-d', strtotime("$today +$days day"));
			$res = [];
			foreach ($list as $p) {
				$n = self::next_birthday($p['birth_date'], $today);
				if ($n && $n <= $end) {
					$p['bday'] = $n;
					$p['age_new'] = self::age($p['birth_date'], $n);
					$res[] = $p;
				}
			}
			usort($res, function ($a, $b) { return strcmp($a['bday'], $b['bday']); });
			return $res;
		}



		// Қатысушыларға қолжетімділік (серверлік тексеру).
		// Қайтарады: [sql шарт, параметрлер] — participants кестесі `p` деп аталуы керек.
		public static function scope() {
			$uid = (int) @core::$user_data['id'];
			if (core::is(['owner', 'admin'])) return ['1=1', []];
			$w = []; $p = [];
			if (core::is('coach')) {
				$w[] = "exists (select 1 from enrollments e join `groups` g on g.id = e.group_id where e.participant_id = p.id and e.end_date is null and g.coach_id = ?)";
				$p[] = $uid;
			}
			if (core::is('parent')) {
				$w[] = "exists (select 1 from participant_guardians pg join guardians gd on gd.id = pg.guardian_id where pg.participant_id = p.id and gd.user_id = ?)";
				$p[] = $uid;
			}
			if (core::is('participant')) {
				$w[] = "p.user_id = ?";
				$p[] = $uid;
			}
			if (!$w) return ['1=0', []];
			return ['('.implode(' or ', $w).')', $p];
		}

		// бір қатысушыны көруге бола ма
		public static function participant($id) {
			list($sw, $sp) = self::scope();
			$p = array_merge([(int) $id, (int) core::$org_id], $sp);
			return db::row("select p.* from participants p where p.id = ? and p.organization_id = ? and $sw", $p);
		}

		// қазіргі тобы
		public static function participant_group($id) {
			return db::row("select g.*, e.start_date, u.name coach_name, u.surname coach_surname
				from enrollments e join `groups` g on g.id = e.group_id left join users u on u.id = g.coach_id
				where e.participant_id = ? and e.end_date is null order by e.id desc limit 1", [(int) $id]);
		}

		// топқа ауыстыру: ескі тіркеу жабылады, тарих жоғалмайды
		public static function participant_set_group($pid, $group_id, $reason = null) {
			$cur = db::row("select * from enrollments where participant_id = ? and end_date is null", [(int) $pid]);
			if ($cur && (int) $cur['group_id'] == (int) $group_id) return false;
			$today = date('Y-m-d');
			if ($cur) db::q("update enrollments set end_date = ? where id = ?", [$today, (int) $cur['id']]);
			if ($group_id) db::ins("insert into enrollments (organization_id, participant_id, group_id, start_date, reason, created_by) values (?, ?, ?, ?, ?, ?)",
				[(int) core::$org_id, (int) $pid, (int) $group_id, $today, $reason, (int) @core::$user_data['id']]);
			return true;
		}



		// Қарыз = абонемент міндеттемесі − расталған төлемдердің бөлінген сомасы
		// (reversal / refund / adjustment теріс немесе оң allocation ретінде есепке кіреді)
		public static function debt_sql() {
			return "select s.organization_id, s.participant_id, s.id subscription_id,
					greatest(s.price - s.discount - coalesce(a.paid, 0), 0) debt
				from subscriptions s
				left join (select pa.subscription_id, sum(pa.amount) paid from payment_allocations pa
					join payments py on py.id = pa.payment_id and py.status = 'confirmed' group by pa.subscription_id) a on a.subscription_id = s.id
				where s.status <> 'cancelled'";
		}
		public static function participant_debt($pid) {
			return (float) db::val("select coalesce(sum(d.debt), 0) from (".self::debt_sql().") d where d.participant_id = ?", [(int) $pid]);
		}



		// audit log
		public static function audit($action, $entity, $entity_id = null, $before = null, $after = null) {
			db::q("insert into audit_logs (organization_id, user_id, action, entity, entity_id, before_data, after_data, ip) values (?, ?, ?, ?, ?, ?, ?, ?)", [
				core::$org_id ? (int) core::$org_id : null,
				!empty(core::$user_data['id']) ? (int) core::$user_data['id'] : null,
				$action, $entity, $entity_id ? (int) $entity_id : null,
				$before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
				$after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
				isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli',
			]);
		}



		// сурет жүктеу: тек jpg / png / webp, 5 МБ дейін, MIME тексеріледі, атауы кездейсоқ
		public static function img_upload($file, $dir) {
			if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return false;
			if ($file['size'] > 5 * 1024 * 1024) return false;
			$allow = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
			$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
			if (!isset($allow[$mime]) || !@getimagesize($file['tmp_name'])) return false;
			$name = bin2hex(random_bytes(16)).'.'.$allow[$mime];
			if (!is_dir($dir)) mkdir($dir, 0755, true);
			if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/').'/'.$name)) return false;
			return $name;
		}



		// get.php жауабы
		public static function json($data) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit();
		}
		public static function post($key, $def = '') {
			return isset($_POST[$key]) && is_string($_POST[$key]) ? trim(strip_tags($_POST[$key])) : $def;
		}
		public static function valid_date($d) {
			$x = DateTime::createFromFormat('Y-m-d', (string) $d);
			return $x && $x->format('Y-m-d') === $d;
		}
	}
