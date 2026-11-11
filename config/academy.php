<?php

	// Академияның бизнес-логикасы: кесте, қатысу рейтингі, абонемент, төлем.
	class academy {

		function __construct() {}



		// ================= КЕСТЕ =================

		// Шаблондардан нақты жаттығуларды жасау (қайталанбайды: uq_session_tpl)
		public static function sessions_generate($org_id, $from, $to) {
			$tpls = db::all("select t.*, g.active from schedule_templates t join `groups` g on g.id = t.group_id
				where t.organization_id = ? and g.active = 1 and t.valid_from <= ? and (t.valid_to is null or t.valid_to >= ?)", [(int) $org_id, $to, $from]);
			$n = 0;
			for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime("$d +1 day"))) {
				$wd = (int) date('N', strtotime($d));
				foreach ($tpls as $t) {
					if ((int) $t['weekday'] != $wd || $d < $t['valid_from'] || ($t['valid_to'] && $d > $t['valid_to'])) continue;
					$n += (int) db::q("insert ignore into sessions (organization_id, group_id, template_id, type, starts_at, ends_at, venue_id, coach_id) values (?, ?, ?, 'training', ?, ?, ?, ?)",
						[(int) $org_id, (int) $t['group_id'], (int) $t['id'], "$d {$t['start_time']}", "$d {$t['end_time']}", $t['venue_id'] ? (int) $t['venue_id'] : null, $t['coach_id'] ? (int) $t['coach_id'] : null]);
				}
			}
			return $n;
		}

		// Уақыт пен алаң / бапкер қақтығысы
		public static function conflicts($org_id, $start, $end, $venue_id, $coach_id, $exclude_id = 0) {
			$w = []; $p = [(int) $org_id, $end, $start, (int) $exclude_id];
			if ($venue_id) { $w[] = "s.venue_id = ?"; $p[] = (int) $venue_id; }
			if ($coach_id) { $w[] = "s.coach_id = ?"; $p[] = (int) $coach_id; }
			if (!$w) return [];
			return db::all("select s.*, g.name group_name, v.name venue_name, u.name coach_name from sessions s
				left join `groups` g on g.id = s.group_id left join venues v on v.id = s.venue_id left join users u on u.id = s.coach_id
				where s.organization_id = ? and s.status <> 'cancelled' and s.starts_at < ? and s.ends_at > ? and s.id <> ? and (".implode(' or ', $w).")", $p);
		}

		// Топтағы ата-аналар мен қатысушыларға хабарлама (платформа ішінде)
		public static function notify_group($org_id, $group_id, $type, $title, $body = null, $link = null, $dedupe = null) {
			$users = db::all("select distinct x.uid from (
					select p.user_id uid from enrollments e join participants p on p.id = e.participant_id where e.group_id = ? and e.end_date is null and p.user_id is not null
					union select gd.user_id from enrollments e join participant_guardians pg on pg.participant_id = e.participant_id join guardians gd on gd.id = pg.guardian_id where e.group_id = ? and e.end_date is null and gd.user_id is not null
				) x", [(int) $group_id, (int) $group_id]);
			foreach ($users as $u) db::q("insert ignore into notifications (organization_id, user_id, type, title, body, link, dedupe_key) values (?, ?, ?, ?, ?, ?, ?)",
				[(int) $org_id, (int) $u['uid'], $type, $title, $body, $link, $dedupe]);
			return count($users);
		}



		// ================= РЕЙТИНГ =================
		// Ұпай ережелері
		public static $points = ['attendance' => 10, 'streak5' => 25, 'streak10' => 50, 'month100' => 100, 'coach_award' => 30, 'progress' => 20];
		public static $month_min = 8;   // 100% бонусы үшін айына кемінде жаттығу

		// Бір қатысушының автоматты ұпайларын қатысу бойынша қайта есептеу.
		// Қатысу өзгерсе — артық ұпай өшеді, жетпегені қосылады. Қайталанбайды.
		public static function rating_sync($pid) {
			$p = db::row("select id, organization_id, direction from participants where id = ?", [(int) $pid]);
			if (!$p) return;
			$att = db::all("select a.id, a.status, s.starts_at from attendance a join sessions s on s.id = a.session_id
				where a.participant_id = ? and s.type <> 'match' and s.status <> 'cancelled' order by s.starts_at, a.id", [(int) $pid]);

			$want = [];   // "rule|source_type|source_id" => [points, created_at]
			$streak = 0;
			$months = [];
			foreach ($att as $a) {
				$m = substr($a['starts_at'], 0, 7);
				if (!isset($months[$m])) $months[$m] = ['p' => 0, 'a' => 0];
				if ($a['status'] == 'present') {
					$want['attendance|attendance|'.$a['id']] = [self::$points['attendance'], $a['starts_at']];
					$months[$m]['p']++;
					$streak++;
					if ($streak == 5) $want['streak5|attendance|'.$a['id']] = [self::$points['streak5'], $a['starts_at']];
					if ($streak == 10) { $want['streak10|attendance|'.$a['id']] = [self::$points['streak10'], $a['starts_at']]; $streak = 0; }
				} elseif ($a['status'] == 'absent') {
					$months[$m]['a']++;
					$streak = 0;
				}
				// excused / frozen: серияны үзбейді, бірақ санамайды
			}
			$cur = date('Y-m');
			foreach ($months as $m => $c) {
				if ($m >= $cur) continue;   // тек аяқталған айлар
				if ($c['a'] == 0 && $c['p'] >= self::$month_min) $want['month100|month|'.str_replace('-', '', $m)] = [self::$points['month100'], date('Y-m-t 23:00:00', strtotime("$m-01"))];
			}

			$have = [];
			foreach (db::all("select id, rule, source_type, source_id from rating_events where participant_id = ? and rule in ('attendance', 'streak5', 'streak10', 'month100')", [(int) $pid]) as $r)
				$have[$r['rule'].'|'.$r['source_type'].'|'.$r['source_id']] = (int) $r['id'];

			foreach ($have as $k => $id) if (!isset($want[$k])) db::q("delete from rating_events where id = ?", [$id]);
			// created_at = жаттығу күні: айлық рейтинг жаттығу болған айға есептеледі
			foreach ($want as $k => $x) if (!isset($have[$k])) {
				list($rule, $st, $sid) = explode('|', $k);
				db::q("insert ignore into rating_events (organization_id, participant_id, scope, rule, points, source_type, source_id, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)",
					[(int) $p['organization_id'], (int) $pid, $p['direction'], $rule, $x[0], $st, (int) $sid, $x[1]]);
			}
			return count($want);
		}



		// ================= АБОНЕМЕНТ =================

		// Расталған төлем сомасы (allocation бойынша)
		public static function sub_paid($sub_id) {
			return (float) db::val("select coalesce(sum(pa.amount), 0) from payment_allocations pa join payments py on py.id = pa.payment_id and py.status = 'confirmed' where pa.subscription_id = ?", [(int) $sub_id]);
		}

		// Мерзім мен мәртебені жаңарту:
		// басталуы = алғашқы расталған төлем күні, бітуі = +1 күнтізбелік ай + бекітілген заморозка күндері
		public static function sub_refresh($sub_id, $today = null) {
			$today = $today ? $today : date('Y-m-d');
			$s = db::row("select * from subscriptions where id = ?", [(int) $sub_id]);
			if (!$s || $s['status'] == 'cancelled') return $s;
			$first = db::val("select min(date(py.paid_at)) from payments py join payment_allocations pa on pa.payment_id = py.id
				where pa.subscription_id = ? and py.status = 'confirmed' and py.kind = 'payment' and not exists (select 1 from payments r where r.reversal_of_id = py.id)", [(int) $sub_id]);
			$fdays = (int) db::val("select coalesce(sum(days), 0) from freezes where subscription_id = ? and status = 'approved'", [(int) $sub_id]);
			if (!$first) {
				$start = null; $end = null; $st = 'pending';
			} else {
				$start = $first;
				$end = date('Y-m-d', strtotime("$start +1 month +$fdays day"));
				$frozen = db::val("select id from freezes where subscription_id = ? and status = 'approved' and ? between start_date and end_date", [(int) $sub_id, $today]);
				$st = $frozen ? 'frozen' : ($end < $today ? 'expired' : 'active');
			}
			db::q("update subscriptions set start_date = ?, end_date = ?, status = ? where id = ?", [$start, $end, $st, (int) $sub_id]);
			$s['start_date'] = $start; $s['end_date'] = $end; $s['status'] = $st;
			return $s;
		}

		// Барлық абонементтің мәртебесін жаңарту (бет ашылғанда)
		public static function subs_refresh_all($org_id) {
			foreach (db::all("select id from subscriptions where organization_id = ? and status in ('active', 'frozen') and (end_date < curdate() or status = 'frozen' or exists (select 1 from freezes f where f.subscription_id = subscriptions.id and f.status = 'approved' and curdate() between f.start_date and f.end_date))", [(int) $org_id]) as $s)
				self::sub_refresh($s['id']);
		}

		// Қолданылған жаттығулар: тек келген және өткізілген (матч кірмейді)
		public static function sub_used($s) {
			if (!$s['start_date']) return 0;
			return (int) db::val("select count(*) from attendance a join sessions se on se.id = a.session_id
				where a.participant_id = ? and a.status = 'present' and se.type <> 'match' and se.status <> 'cancelled' and se.starts_at <= now() and date(se.starts_at) between ? and ?",
				[(int) $s['participant_id'], $s['start_date'], $s['end_date']]);
		}

		public static function sub_frozen_days($sub_id) {
			return (int) db::val("select coalesce(sum(days), 0) from freezes where subscription_id = ? and status in ('approved', 'requested')", [(int) $sub_id]);
		}



		// ================= ТӨЛЕМ =================

		// Kaspi RED комиссиясы: алдымен тарифке арналған, болмаса жалпы
		public static function fee($org_id, $method, $plan_id, $amount) {
			$pct = db::val("select fee_percent from payment_method_fees where organization_id = ? and method = ? and (plan_id = ? or plan_id is null) order by plan_id is null limit 1", [(int) $org_id, $method, (int) $plan_id]);
			return $pct ? round($amount * (float) $pct / 100, 2) : 0;
		}
	}
