<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$s = db::row("select s.*, g.name group_name, g.direction, g.coach_id group_coach, v.name venue_name, u.name coach_name
		from sessions s left join `groups` g on g.id = s.group_id left join venues v on v.id = s.venue_id left join users u on u.id = s.coach_id
		where s.id = ? and s.organization_id = ? and s.type <> 'match'", [$id, $org_id]);
	if (!$s || (!$admin && (int) $s['coach_id'] !== $user_id && (int) $s['group_coach'] !== $user_id)) { http_response_code(404); include "../block/403.php"; exit(); }

	$day = substr($s['starts_at'], 0, 10);
	$can = $s['status'] != 'cancelled' && $day <= $date;

	// сол күні топта болған қатысушылар + бұрын белгіленгендер
	$parts = db::all("select p.id, p.first_name, p.last_name, p.img, p.number, a.status att from participants p
			left join attendance a on a.participant_id = p.id and a.session_id = ?
		where p.organization_id = ? and (a.id is not null or (p.archived_at is null and exists (select 1 from enrollments e where e.participant_id = p.id and e.group_id = ? and e.start_date <= ? and (e.end_date is null or e.end_date > ?))))
		order by p.last_name, p.first_name", [$id, $org_id, (int) $s['group_id'], $day, $day]);

	foreach ($parts as &$p) {
		$sub = db::row("select s.* from subscriptions s where s.participant_id = ? and s.status <> 'cancelled' and (s.start_date is null or s.start_date <= ?) order by s.start_date is null, s.start_date desc limit 1", [$p['id'], $day]);
		$p['sub'] = 'none'; $p['left'] = null;
		if ($sub) {
			if (!$sub['start_date']) $p['sub'] = 'pending';
			elseif ($sub['end_date'] < $day) $p['sub'] = 'expired';
			elseif (db::val("select id from freezes where subscription_id = ? and status = 'approved' and ? between start_date and end_date", [$sub['id'], $day])) $p['sub'] = 'frozen';
			else {
				$p['sub'] = 'active';
				if ($sub['sessions_limit']) $p['left'] = $sub['sessions_limit'] - academy::sub_used($sub);
			}
		}
		$p['debt'] = fun::participant_debt($p['id']);
		// әдепкі: заморозкада болса — frozen
		$p['def'] = $p['att'] ?: ($p['sub'] == 'frozen' ? 'frozen' : '');
	}
	unset($p);
	$marked = (bool) array_filter($parts, function ($p) { return $p['att']; });
	$sub_bdg = ['pending' => ['bdg_warn', 'төлем күтілуде'], 'expired' => ['bdg_err', 'абонемент бітті'], 'frozen' => ['', 'заморозка'], 'none' => ['bdg_err', 'абонемент жоқ']];


	// site setting
	$menu_name = 'attendance';
	$menu_title = 'Қатысу · '.$s['group_name'];
	$site_set['utop_bk'] = '/attendance/?d='.$day;
	$css = ['attendance'];
	$js = ['attendance'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div>
				<h4><?=h($s['group_name'])?></h4>
				<p><?=fun::date_kz($day)?> · <?=date('H:i', strtotime($s['starts_at']))?>–<?=date('H:i', strtotime($s['ends_at']))?> · <?=h($s['venue_name'])?> · <?=h($s['coach_name'])?></p>
			</div>
			<? if ($can && $parts): ?><div class="btn btn_sm btn_ghost at_all"><i class="fal fa-check-double"></i><span>Барлығы келді</span></div><? endif ?>
		</div>

		<? if ($s['status'] == 'cancelled'): ?>
			<div class="card"><div class="ds_nr ds_nr_sm"><i class="fal fa-ban"></i><p>Жаттығу болдырылмады<?=($s['cancel_reason']?': '.h($s['cancel_reason']):'')?></p></div></div>
		<? elseif (!$can): ?>
			<div class="card" style="margin-bottom:16px"><div class="ds_nr ds_nr_sm"><i class="fal fa-clock"></i><p>Қатысуды жаттығу күні белгілеуге болады</p></div></div>
		<? endif ?>

		<div class="card at_list" data-id="<?=$id?>">
			<div class="card_h"><h6>Қатысушылар</h6><span class="bdg at_cnt"><?=count($parts)?></span></div>
			<? foreach ($parts as $p): ?>
				<div class="at_i" data-pid="<?=$p['id']?>" data-st="<?=h($p['def'])?>">
					<a class="at_n" href="/participants/item.php?id=<?=$p['id']?>">
						<div class="li_i"><?=($p['img'] ? '<img src="/assets/uploads/participants/'.h($p['img']).'" alt="">' : h(mb_substr($p['first_name'], 0, 1).mb_substr($p['last_name'], 0, 1)))?></div>
						<div class="li_c">
							<b><?=h($p['last_name'].' '.$p['first_name'])?></b>
							<span>
								<? if ($p['sub'] != 'active'): ?><i class="bdg <?=$sub_bdg[$p['sub']][0]?>"><?=$sub_bdg[$p['sub']][1]?></i><? endif ?>
								<? if ($p['left'] !== null): ?><i class="bdg <?=($p['left']<=2?'bdg_warn':'')?>"><?=max(0, $p['left'])?> жаттығу қалды</i><? endif ?>
								<? if ($p['debt'] > 0): ?><i class="bdg bdg_err">қарыз <?=fun::money($p['debt'])?></i><? endif ?>
							</span>
						</div>
					</a>
					<div class="at_b">
						<? foreach (['present' => 'fa-check', 'absent' => 'fa-times', 'excused' => 'fa-file-medical', 'frozen' => 'fa-snowflake'] as $k => $ic): ?>
							<div class="at_bi at_bi_<?=$k?> <?=($p['def']==$k?'at_bi_act':'')?> <?=($can?'':'at_bi_ds')?>" data-st="<?=$k?>" title="<?=t::w($k)?>"><i class="fal <?=$ic?>"></i><span><?=t::w($k)?></span></div>
						<? endforeach ?>
					</div>
				</div>
			<? endforeach ?>
			<? if (!$parts): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-users"></i><p>Топта қатысушы жоқ</p></div><? endif ?>
		</div>

		<? if ($can && $parts): ?>
			<div class="at_save_w">
				<div class="at_sum"><span class="at_s_present">0</span> келді · <span class="at_s_absent">0</span> келмеді · <span class="at_s_none">0</span> белгісіз</div>
				<div class="btn at_save"><i class="fal fa-save"></i><span><?=($marked?'Өзгерісті сақтау':'Сақтау')?></span></div>
			</div>
		<? endif ?>

		<div style="height:90px"></div>
	</div>

<? include "../block/footer.php"; ?>
