<? include "../config/core.php";

	// тек қызметкерлер
	core::need_page(['owner', 'admin', 'coach']);
	$fin = core::is(['owner', 'admin']);        // қаржы тек иесі мен әкімшіге
	$is_coach_only = !$fin;

	list($sw, $sp) = fun::scope();
	$m_start = date('Y-m-01');
	$m_end = date('Y-m-t');


	// қатысушылар
	$cnt = ['kids' => 0, 'adult' => 0];
	foreach (db::all("select p.direction, count(*) c from participants p where p.organization_id = ? and p.status = 'active' and p.archived_at is null and $sw group by p.direction", array_merge([$org_id], $sp)) as $r) $cnt[$r['direction']] = (int) $r['c'];
	$cnt_all = $cnt['kids'] + $cnt['adult'];
	$first_team = (int) db::val("select count(distinct r.participant_id) from team_rosters r join teams t on t.id = r.team_id join participants p on p.id = r.participant_id where t.organization_id = ? and r.left_at is null and $sw", array_merge([$org_id], $sp));

	// белсенді абонемент
	$subs_active = (int) db::val("select count(*) from subscriptions s join participants p on p.id = s.participant_id where s.organization_id = ? and s.status = 'active' and s.end_date >= ? and $sw", array_merge([$org_id, $date], $sp));
	$subs_soon = (int) db::val("select count(*) from subscriptions s join participants p on p.id = s.participant_id where s.organization_id = ? and s.status = 'active' and s.end_date between ? and ? and $sw", array_merge([$org_id, $date, date('Y-m-d', strtotime('+5 day'))], $sp));

	// қаржы
	if ($fin) {
		$inc = db::row("select coalesce(sum(amount), 0) amount, coalesce(sum(fee_amount), 0) fee from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ?", [$org_id, "$m_start 00:00:00", "$m_end 23:59:59"]);
		$exp = (float) db::val("select coalesce(sum(amount), 0) from expenses where organization_id = ? and spent_at between ? and ?", [$org_id, $m_start, $m_end]);
		$net = (float) $inc['amount'] - (float) $inc['fee'] - $exp;
		$pending_pay = db::row("select count(*) c, coalesce(sum(amount), 0) s from payments where organization_id = ? and status = 'pending'", [$org_id]);

		// 6 ай кіріс
		$chart = [];
		for ($i = 5; $i >= 0; $i--) {
			$ms = date('Y-m-01', strtotime("$m_start -$i month"));
			$me = date('Y-m-t', strtotime($ms));
			$chart[] = ['m' => mb_substr(fun::$months[(int) date('n', strtotime($ms))], 0, 3), 'v' => (float) db::val("select coalesce(sum(amount - fee_amount), 0) from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ?", [$org_id, "$ms 00:00:00", "$me 23:59:59"])];
		}
		$chart_max = max(1, max(array_column($chart, 'v')));
	}

	// қарызы барлар
	$debt_sql = fun::debt_sql();
	$debtors = db::all("select p.id, p.first_name, p.last_name, p.img, p.direction, sum(d.debt) debt from ($debt_sql) d join participants p on p.id = d.participant_id
		where d.organization_id = ? and p.archived_at is null and $sw group by p.id having debt > 0 order by debt desc", array_merge([$org_id], $sp));
	$debt_total = array_sum(array_column($debtors, 'debt'));

	// бүгінгі жаттығулар
	$tp = [$org_id, "$date 00:00:00", "$date 23:59:59"];
	if ($is_coach_only) $tp[] = $user_id;
	$today_s = db::all("select s.*, g.name group_name, g.direction, v.name venue_name, u.name coach_name,
			(select count(*) from enrollments e where e.group_id = s.group_id and e.end_date is null) enrolled,
			(select count(*) from attendance a where a.session_id = s.id and a.status = 'present') present,
			(select count(*) from attendance a where a.session_id = s.id) marked
		from sessions s left join `groups` g on g.id = s.group_id left join venues v on v.id = s.venue_id left join users u on u.id = s.coach_id
		where s.organization_id = ? and s.starts_at between ? and ? and s.status <> 'cancelled'".($is_coach_only ? " and s.coach_id = ?" : "")." order by s.starts_at", $tp);

	// қатысу статистикасы (30 күн)
	$att = ['present' => 0, 'absent' => 0, 'excused' => 0, 'frozen' => 0];
	foreach (db::all("select a.status, count(*) c from attendance a join sessions s on s.id = a.session_id join participants p on p.id = a.participant_id
		where a.organization_id = ? and s.starts_at >= ? and $sw group by a.status", array_merge([$org_id, date('Y-m-d', strtotime('-30 day'))], $sp)) as $r) $att[$r['status']] = (int) $r['c'];
	$att_all = array_sum($att);
	$att_pct = $att_all ? round($att['present'] * 100 / $att_all) : 0;

	// туған күндер (рұқсат етілген қатысушылар ғана)
	$allowed = array_flip(array_column(db::all("select p.id from participants p where p.organization_id = ? and $sw", array_merge([$org_id], $sp)), 'id'));
	$bdays = array_values(array_filter(fun::birthdays($org_id, 7), function ($p) use ($allowed) { return isset($allowed[$p['id']]); }));

	// CRM өтінімдер
	if ($fin) {
		$leads_new = (int) db::val("select count(*) from leads where organization_id = ? and status = 'new'", [$org_id]);
		$leads = db::all("select * from leads where organization_id = ? and status = 'new' order by created_at desc limit 5", [$org_id]);
	}

	// алдағы ЛЛФ матчтары
	$matches = db::all("select m.*, o.name opp, c.name comp from matches m join opponents o on o.id = m.opponent_id left join competitions c on c.id = m.competition_id
		where m.organization_id = ? and m.match_at >= now() and m.status = 'scheduled' order by m.match_at limit 3", [$org_id]);


	// site setting
	$menu_name = 'dashboard';
	$menu_title = 'Басқару тақтасы';
	$css = []; $js = [];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div>
				<h4>Сәлем, <?=h($user['name'])?>!</h4>
				<p><?=fun::date_kz($date)?> · <?=t::w($user_right)?></p>
			</div>
			<? if (core::is(['owner', 'admin'])): ?>
				<a class="btn btn_sm" href="/participants/?add"><i class="fal fa-user-plus"></i><span>Қатысушы қосу</span></a>
			<? endif ?>
		</div>

		<!-- негізгі көрсеткіштер -->
		<div class="dsh_k">
			<div class="card dsh_ki dsh_ki_dk">
				<div class="dsh_ki_t"><i class="fal fa-users"></i>Барлық қатысушылар</div>
				<div class="dsh_ki_n"><?=$cnt_all?></div>
				<div class="dsh_ki_s">First Team: <?=$first_team?></div>
			</div>
			<div class="card dsh_ki">
				<div class="dsh_ki_t"><i class="fal fa-child"></i>JAS KIDS</div>
				<div class="dsh_ki_n"><?=$cnt['kids']?></div>
				<div class="dsh_ki_s">белсенді</div>
			</div>
			<div class="card dsh_ki">
				<div class="dsh_ki_t"><i class="fal fa-running"></i>JAS ADULT</div>
				<div class="dsh_ki_n"><?=$cnt['adult']?></div>
				<div class="dsh_ki_s">белсенді</div>
			</div>
			<div class="card dsh_ki">
				<div class="dsh_ki_t"><i class="fal fa-id-card"></i>Белсенді абонемент</div>
				<div class="dsh_ki_n"><?=$subs_active?></div>
				<div class="dsh_ki_s"><?=$subs_soon?> абонемент 5 күнде бітеді</div>
			</div>
		</div>

		<? if ($fin): ?>
			<!-- қаржы -->
			<div class="dsh_g">
				<div class="card">
					<div class="card_h"><h6>Қаржы · <?=fun::$months[(int) date('n')]?></h6></div>
					<div class="dsh_fin">
						<div class="dsh_fin_i"><span>Кіріс</span><b><?=fun::money($inc['amount'])?></b></div>
						<div class="dsh_fin_i"><span>Шығын (+ RED комиссия)</span><b><?=fun::money($exp + $inc['fee'])?></b></div>
						<div class="dsh_fin_i"><span>Таза нәтиже</span><b class="<?=($net>=0?'cl_ok':'cl_err')?>"><?=fun::money($net)?></b></div>
					</div>
					<div class="chart_b">
						<? foreach ($chart as $c): ?>
							<div class="chart_bi">
								<b><?=($c['v'] ? round($c['v'] / 1000).'k' : '0')?></b>
								<i style="height:<?=round($c['v'] * 100 / $chart_max)?>%"></i>
								<span><?=h($c['m'])?></span>
							</div>
						<? endforeach ?>
					</div>
				</div>
				<div class="card">
					<div class="card_h"><h6>Қарызы барлар</h6><span class="bdg bdg_err"><?=count($debtors)?></span></div>
					<div class="dsh_fin_i"><span>Жалпы қарыз</span><b class="cl_err"><?=fun::money($debt_total)?></b></div>
					<? if ($pending_pay['c']): ?>
						<div class="dsh_fin_i" style="margin-top:10px"><span>Расталмаған төлемдер</span><b><?=$pending_pay['c']?> · <?=fun::money($pending_pay['s'])?></b></div>
					<? endif ?>
					<div style="margin-top:8px">
						<? foreach (array_slice($debtors, 0, 5) as $d): ?>
							<a class="li" href="/participants/item.php?id=<?=$d['id']?>#payments">
								<div class="li_i"><?=h(mb_substr($d['first_name'], 0, 1).mb_substr($d['last_name'], 0, 1))?></div>
								<div class="li_c"><b><?=h(fun::full_name($d))?></b><span><?=t::w($d['direction'])?></span></div>
								<div class="li_r cl_err"><?=fun::money($d['debt'])?></div>
							</a>
						<? endforeach ?>
						<? if (!$debtors): ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-check-circle"></i><p>Қарыз жоқ</p></div> <? endif ?>
					</div>
				</div>
			</div>
		<? endif ?>

		<div class="dsh_g">
			<!-- бүгінгі жаттығулар -->
			<div class="card">
				<div class="card_h"><h6>Бүгінгі жаттығулар</h6><span class="bdg"><?=count($today_s)?></span></div>
				<? foreach ($today_s as $s): ?>
					<div class="li">
						<div class="li_i"><?=date('H:i', strtotime($s['starts_at']))?></div>
						<div class="li_c">
							<b><?=h($s['group_name'] ?: 'Жаттығу')?> <?=($s['type']=='extra'?'<span class="bdg bdg_warn" style="display:inline-flex">қосымша</span>':'')?></b>
							<span><?=date('H:i', strtotime($s['starts_at']))?>–<?=date('H:i', strtotime($s['ends_at']))?> · <?=h($s['venue_name'])?> · <?=h($s['coach_name'])?></span>
						</div>
						<div class="li_r"><?=$s['present']?>/<?=$s['enrolled']?><span><?=($s['marked']?'белгіленді':'белгіленбеген')?></span></div>
					</div>
				<? endforeach ?>
				<? if (!$today_s): ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-calendar"></i><p>Бүгін жаттығу жоқ</p></div> <? endif ?>
			</div>

			<!-- қатысу -->
			<div class="card">
				<div class="card_h"><h6>Қатысу · 30 күн</h6><span class="bdg bdg_ok"><?=$att_pct?>%</span></div>
				<? if ($att_all): ?>
					<div class="bar">
						<i style="width:<?=$att['present']*100/$att_all?>%;background:var(--ok)"></i>
						<i style="width:<?=$att['excused']*100/$att_all?>%;background:var(--warn)"></i>
						<i style="width:<?=$att['frozen']*100/$att_all?>%;background:#5b8def"></i>
						<i style="width:<?=$att['absent']*100/$att_all?>%;background:var(--err)"></i>
					</div>
					<div class="bar_lg">
						<span style="--c:var(--ok)">Келді <?=$att['present']?></span>
						<span style="--c:var(--warn)">Себепті <?=$att['excused']?></span>
						<span style="--c:#5b8def">Заморозка <?=$att['frozen']?></span>
						<span style="--c:var(--err)">Келмеді <?=$att['absent']?></span>
					</div>
				<? else: ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-clipboard"></i><p>Әзірге дерек жоқ</p></div> <? endif ?>
			</div>
		</div>

		<div class="dsh_g3">
			<!-- туған күн -->
			<div class="card">
				<div class="card_h"><h6>Алдағы туған күндер</h6><span class="bdg bdg_adult"><?=count($bdays)?></span></div>
				<? foreach ($bdays as $b): ?>
					<a class="li" href="/participants/item.php?id=<?=$b['id']?>">
						<div class="li_i"><i class="fal fa-birthday-cake"></i></div>
						<div class="li_c"><b><?=h(fun::full_name($b))?></b><span><?=$b['age_new']?> жас толады · <?=t::w($b['direction'])?></span></div>
						<div class="li_r"><?=($b['bday']==$date?'Бүгін':date('d.m', strtotime($b['bday'])))?></div>
					</a>
				<? endforeach ?>
				<? if (!$bdays): ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-birthday-cake"></i><p>7 күнде туған күн жоқ</p></div> <? endif ?>
			</div>

			<!-- CRM -->
			<? if ($fin): ?>
				<div class="card">
					<div class="card_h"><h6>Жаңа CRM өтінімдер</h6><span class="bdg bdg_warn"><?=$leads_new?></span></div>
					<? foreach ($leads as $l): ?>
						<div class="li">
							<div class="li_i"><i class="<?=($l['source']=='whatsapp'?'fab fa-whatsapp':($l['source']=='instagram'?'fab fa-instagram':'fal fa-hashtag'))?>"></i></div>
							<div class="li_c"><b><?=h($l['name'])?></b><span><?=h(fun::phone_view($l['phone']))?> · <?=t::w($l['direction'] ?: '')?></span></div>
							<div class="li_r"><span><?=date('d.m H:i', strtotime($l['created_at']))?></span></div>
						</div>
					<? endforeach ?>
					<? if (!$leads): ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-inbox"></i><p>Жаңа өтінім жоқ</p></div> <? endif ?>
				</div>
			<? endif ?>

			<!-- ЛЛФ -->
			<div class="card">
				<div class="card_h"><h6>Алдағы ЛЛФ матчтары</h6><span class="bdg bdg_dk">FIRST TEAM</span></div>
				<? foreach ($matches as $m): ?>
					<div class="li">
						<div class="li_i"><i class="fal fa-futbol"></i></div>
						<div class="li_c"><b>JAS — <?=h($m['opp'])?></b><span><?=h($m['comp'])?> · <?=h($m['round'])?> · <?=($m['is_home']?'үйде':'қонақта')?></span></div>
						<div class="li_r"><?=date('d.m', strtotime($m['match_at']))?><span><?=date('H:i', strtotime($m['match_at']))?></span></div>
					</div>
				<? endforeach ?>
				<? if (!$matches): ?> <div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Алдағы матч жоқ</p></div> <? endif ?>
			</div>
		</div>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
