<? include "../config/core.php";

	core::need_page();
	$admin = core::is(['owner', 'admin']);
	$staff = core::is(['owner', 'admin', 'coach']);

	// көрініс
	$view = isset($_GET['v']) && $_GET['v'] == 'month' ? 'month' : 'week';
	$d = isset($_GET['d']) && fun::valid_date($_GET['d']) ? $_GET['d'] : $date;
	if ($view == 'week') {
		$from = date('Y-m-d', strtotime($d.' -'.(date('N', strtotime($d)) - 1).' day'));
		$to = date('Y-m-d', strtotime("$from +6 day"));
		$prev = date('Y-m-d', strtotime("$from -7 day")); $next = date('Y-m-d', strtotime("$from +7 day"));
	} else {
		$from = date('Y-m-01', strtotime($d));
		$to = date('Y-m-t', strtotime($d));
		$prev = date('Y-m-d', strtotime("$from -1 month")); $next = date('Y-m-d', strtotime("$from +1 month"));
	}

	// шаблоннан жаттығуларды жасау (қайталанбайды)
	academy::sessions_generate($org_id, $from, $to);

	// сүзгі
	$f_group = isset($_GET['group']) ? (int) $_GET['group'] : 0;
	$f_coach = isset($_GET['coach']) && $admin ? (int) $_GET['coach'] : 0;

	$w = ["s.organization_id = ?", "s.starts_at between ? and ?"]; $wp = [$org_id, "$from 00:00:00", "$to 23:59:59"];
	if (!$admin && core::is('coach')) { $w[] = "(s.coach_id = ? or g.coach_id = ?)"; $wp[] = $user_id; $wp[] = $user_id; }
	elseif (!$staff) {
		list($sw, $sp) = fun::scope();
		$w[] = "s.group_id in (select e.group_id from enrollments e join participants p on p.id = e.participant_id where e.end_date is null and $sw)";
		$wp = array_merge($wp, $sp);
	}
	if ($f_group) { $w[] = "s.group_id = ?"; $wp[] = $f_group; }
	if ($f_coach) { $w[] = "s.coach_id = ?"; $wp[] = $f_coach; }
	$list = db::all("select s.*, g.name group_name, g.direction, v.name venue_name, v.address venue_address, u.name coach_name,
			(select count(*) from attendance a where a.session_id = s.id) marked
		from sessions s left join `groups` g on g.id = s.group_id left join venues v on v.id = s.venue_id left join users u on u.id = s.coach_id
		where ".implode(' and ', $w)." order by s.starts_at", $wp);
	$by_day = [];
	foreach ($list as $s) $by_day[substr($s['starts_at'], 0, 10)][] = $s;

	// ЛЛФ матчтары
	$matches = db::all("select m.*, o.name opp from matches m join opponents o on o.id = m.opponent_id where m.organization_id = ? and m.match_at between ? and ? and m.status <> 'cancelled' order by m.match_at", [$org_id, "$from 00:00:00", "$to 23:59:59"]);
	foreach ($matches as $m) $by_day[substr($m['match_at'], 0, 10)][] = ['match' => 1] + $m;

	$groups_all = db::all("select * from `groups` where organization_id = ? and active = 1".($admin ? "" : ($staff ? " and coach_id = ".(int) $user_id : " and 0"))." order by direction, name", [$org_id]);
	$coaches = $admin ? db::all("select u.id, u.name from users u join memberships m on m.user_id = u.id where m.organization_id = ? and m.role = 'coach' and m.active = 1", [$org_id]) : [];
	$venues = $staff ? db::all("select * from venues where organization_id = ? and active = 1", [$org_id]) : [];
	$tpls = $admin ? db::all("select t.*, g.name group_name, v.name venue_name, u.name coach_name from schedule_templates t join `groups` g on g.id = t.group_id left join venues v on v.id = t.venue_id left join users u on u.id = t.coach_id
		where t.organization_id = ? and (t.valid_to is null or t.valid_to >= ?) order by g.name, t.weekday, t.start_time", [$org_id, $date]) : [];

	$qs = $_GET; unset($qs['d'], $qs['v']);
	$ql = function ($dd, $vv) use ($qs) { return '?'.http_build_query(array_merge($qs, ['d' => $dd, 'v' => $vv])); };


	// site setting
	$menu_name = 'schedule';
	$menu_title = 'Кесте';
	$css = ['schedule'];
	$js = ['schedule'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div>
				<h4>Кесте</h4>
				<p><?=($view=='week' ? date('d.m', strtotime($from)).' – '.date('d.m.Y', strtotime($to)) : fun::$months[(int) date('n', strtotime($from))].' '.date('Y', strtotime($from)))?> · Asia/Almaty</p>
			</div>
			<? if ($staff): ?>
				<div class="btn btn_sm" data-pop="sess_add"><i class="fal fa-plus"></i><span>Қосымша жаттығу</span></div>
			<? endif ?>
		</div>

		<div class="sch_top">
			<div class="sch_nav">
				<a class="btn btn_sm btn_ghost" href="<?=h($ql($prev, $view))?>"><i class="fal fa-angle-left"></i></a>
				<a class="btn btn_sm btn_ghost" href="<?=h($ql($date, $view))?>">Бүгін</a>
				<a class="btn btn_sm btn_ghost" href="<?=h($ql($next, $view))?>"><i class="fal fa-angle-right"></i></a>
			</div>
			<div class="tab" style="margin:0">
				<a class="tab_i <?=($view=='week'?'tab_i_act':'')?>" href="<?=h($ql($d, 'week'))?>">Апта</a>
				<a class="tab_i <?=($view=='month'?'tab_i_act':'')?>" href="<?=h($ql($d, 'month'))?>">Ай</a>
				<? if ($admin): ?><div class="tab_i" data-pop="tpl_list">Тұрақты кесте</div><? endif ?>
			</div>
			<? if ($staff): ?>
				<form class="flt" method="get" style="margin:0">
					<input type="hidden" name="v" value="<?=$view?>"><input type="hidden" name="d" value="<?=h($d)?>">
					<div class="form_im"><select class="form_im_txt flt_auto" name="group"><option value="">Барлық топ</option><? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>" <?=($f_group==$g['id']?'selected':'')?>><?=h($g['name'])?></option><? endforeach ?></select></div>
					<? if ($admin): ?><div class="form_im"><select class="form_im_txt flt_auto" name="coach"><option value="">Барлық бапкер</option><? foreach ($coaches as $c): ?><option value="<?=$c['id']?>" <?=($f_coach==$c['id']?'selected':'')?>><?=h($c['name'])?></option><? endforeach ?></select></div><? endif ?>
				</form>
			<? endif ?>
		</div>

		<? if ($view == 'week'): ?>
			<div class="sch_w">
				<? for ($i = 0; $i < 7; $i++): $day = date('Y-m-d', strtotime("$from +$i day")); ?>
					<div class="sch_d <?=($day==$date?'sch_d_today':'')?>">
						<div class="sch_dh"><b><?=fun::$weekdays[$i + 1]?></b><span><?=date('d.m', strtotime($day))?></span></div>
						<? foreach ((isset($by_day[$day]) ? $by_day[$day] : []) as $s): ?>
							<? if (!empty($s['match'])): ?>
								<a class="sch_s sch_s_match" href="/first_team/match.php?id=<?=$s['id']?>">
									<b><?=date('H:i', strtotime($s['match_at']))?> · ЛЛФ</b>
									<span>ZHASS — <?=h($s['opp'])?></span>
									<span><?=h($s['venue'])?></span>
								</a>
							<? else: ?>
								<div class="sch_s sch_s_<?=$s['direction']?> <?=($s['status']=='cancelled'?'sch_s_x':'')?>" <?=($staff && $s['status']!='cancelled'?'data-sess=\''.h(json_encode(['id' => $s['id'], 'date' => substr($s['starts_at'], 0, 10), 'start' => substr($s['starts_at'], 11, 5), 'end' => substr($s['ends_at'], 11, 5), 'venue' => $s['venue_id'], 'title' => $s['group_name'].' · '.date('d.m H:i', strtotime($s['starts_at'])), 'marked' => (int) $s['marked']], JSON_UNESCAPED_UNICODE)).'\'':'')?>>
									<b><?=date('H:i', strtotime($s['starts_at']))?>–<?=date('H:i', strtotime($s['ends_at']))?></b>
									<span><?=h($s['group_name'])?> <?=($s['type']=='extra'?'<i class="sch_tag">қосымша</i>':'')?><?=($s['moved_from_id']?'<i class="sch_tag">ауыстырылды</i>':'')?></span>
									<span><?=h($s['venue_name'])?> · <?=h($s['coach_name'])?></span>
									<? if ($s['status']=='cancelled'): ?><span class="cl_err">Болдырылмады<?=($s['cancel_reason']?': '.h($s['cancel_reason']):'')?></span><? endif ?>
								</div>
							<? endif ?>
						<? endforeach ?>
						<? if (empty($by_day[$day])): ?><div class="sch_e">—</div><? endif ?>
					</div>
				<? endfor ?>
			</div>
		<? else: ?>
			<div class="sch_m">
				<? foreach (['Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб', 'Жс'] as $x): ?><div class="sch_mh"><?=$x?></div><? endforeach ?>
				<? for ($i = 1; $i < (int) date('N', strtotime($from)); $i++): ?><div class="sch_mc sch_mc_e"></div><? endfor ?>
				<? for ($day = $from; $day <= $to; $day = date('Y-m-d', strtotime("$day +1 day"))): ?>
					<a class="sch_mc <?=($day==$date?'sch_d_today':'')?>" href="<?=h($ql($day, 'week'))?>">
						<b><?=(int) date('d', strtotime($day))?></b>
						<? foreach ((isset($by_day[$day]) ? array_slice($by_day[$day], 0, 4) : []) as $s): ?>
							<? if (!empty($s['match'])): ?><i class="sch_mi sch_s_match"><?=date('H:i', strtotime($s['match_at']))?> ЛЛФ</i>
							<? else: ?><i class="sch_mi sch_s_<?=$s['direction']?> <?=($s['status']=='cancelled'?'sch_s_x':'')?>"><?=date('H:i', strtotime($s['starts_at']))?> <?=h($s['group_name'])?></i><? endif ?>
						<? endforeach ?>
						<? if (isset($by_day[$day]) && count($by_day[$day]) > 4): ?><i class="sch_mi">+<?=count($by_day[$day]) - 4?></i><? endif ?>
					</a>
				<? endfor ?>
			</div>
		<? endif ?>

		<div class="bar_lg" style="margin:16px 0 40px">
			<span style="--c:var(--green)">ZHASS KIDS</span><span style="--c:var(--gold)">ZHASS ADULT</span><span style="--c:var(--dk)">ЛЛФ матч</span><span style="--c:var(--err)">Болдырылмады</span>
		</div>
	</div>

	<? if ($staff): ?>
		<!-- қосымша жаттығу -->
		<div class="pop_bl sess_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Қосымша жаттығу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g sa_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Топ <em>*</em></label><select class="form_im_txt" name="group_id"><option value="">Таңдаңыз</option><? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>"><?=h($g['name'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Күні <em>*</em></label><input type="date" class="form_im_txt" name="date" value="<?=$date?>"></div>
					<div class="form_im"><label class="form_l">Басталуы</label><input type="time" class="form_im_txt" name="start" value="18:00"></div>
					<div class="form_im"><label class="form_l">Аяқталуы</label><input type="time" class="form_im_txt" name="end" value="19:30"></div>
					<div class="form_im form_im_f"><label class="form_l">Алаң</label><select class="form_im_txt" name="venue_id"><option value="">Топтың алаңы</option><? foreach ($venues as $v): ?><option value="<?=$v['id']?>"><?=h($v['name'])?></option><? endforeach ?></select></div>
					<? if ($admin): ?><div class="form_im form_im_f"><label class="form_l">Бапкер</label><select class="form_im_txt" name="coach_id"><option value="">Топтың бапкері</option><? foreach ($coaches as $c): ?><option value="<?=$c['id']?>"><?=h($c['name'])?></option><? endforeach ?></select></div><? endif ?>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn sa_save">Қосу</div></div>
			</div>
		</div>

		<!-- жаттығу әрекеттері -->
		<div class="pop_bl sess_act">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5 class="se_title">Жаттығу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<a class="btn btn_dk se_att" style="width:100%" href="#"><i class="fal fa-clipboard-check"></i><span>Қатысуды белгілеу</span></a>
				<div class="se_edit">
					<div class="form_sep" style="margin-top:20px">Ауыстыру</div>
					<div class="form_g">
						<div class="form_im form_im_f"><label class="form_l">Жаңа күні</label><input type="date" class="form_im_txt se_date"></div>
						<div class="form_im"><label class="form_l">Басталуы</label><input type="time" class="form_im_txt se_start"></div>
						<div class="form_im"><label class="form_l">Аяқталуы</label><input type="time" class="form_im_txt se_end"></div>
						<div class="form_im form_im_f"><label class="form_l">Алаң</label><select class="form_im_txt se_venue"><? foreach ($venues as $v): ?><option value="<?=$v['id']?>"><?=h($v['name'])?></option><? endforeach ?></select></div>
					</div>
					<div class="form_btns"><div class="btn se_move">Ауыстыру</div></div>
					<div class="form_sep" style="margin-top:20px">Болдырмау</div>
					<div class="form_g"><div class="form_im form_im_f"><input type="text" class="form_im_txt se_reason" placeholder="Себебі (ата-аналарға хабарланады)" maxlength="255"></div></div>
					<div class="form_btns"><div class="btn btn_ghost cl_err se_cancel">Жаттығуды болдырмау</div></div>
				</div>
			</div>
		</div>
	<? endif ?>

	<? if ($admin): ?>
		<!-- тұрақты кесте -->
		<div class="pop_bl pop_bl2 tpl_list">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c" style="padding:28px;overflow-y:auto">
				<div class="pop_h"><h5>Тұрақты кесте</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<? foreach ($tpls as $t): ?>
					<div class="li">
						<div class="li_i"><?=fun::$weekdays[$t['weekday']]?></div>
						<div class="li_c"><b><?=h($t['group_name'])?> · <?=substr($t['start_time'], 0, 5)?>–<?=substr($t['end_time'], 0, 5)?></b><span><?=h($t['venue_name'])?> · <?=h($t['coach_name'])?></span></div>
						<div class="li_r"><span class="bdg bdg_err tpl_stop" data-id="<?=$t['id']?>" style="cursor:pointer">Тоқтату</span></div>
					</div>
				<? endforeach ?>
				<div class="form_sep" style="margin-top:16px">Жаңа уақыт қосу</div>
				<form class="form_g tp_form" onsubmit="return false" style="margin-top:12px">
					<div class="form_im form_im_f"><select class="form_im_txt" name="group_id"><option value="">Топ</option><? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>"><?=h($g['name'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><select class="form_im_txt" name="weekday"><? for ($i = 1; $i <= 7; $i++): ?><option value="<?=$i?>"><?=fun::$weekdays[$i]?></option><? endfor ?></select></div>
					<div class="form_im"><input type="time" class="form_im_txt" name="start" value="17:00"></div>
					<div class="form_im"><input type="time" class="form_im_txt" name="end" value="18:30"></div>
					<div class="form_im form_im_f"><select class="form_im_txt" name="venue_id"><option value="">Топтың алаңы</option><? foreach ($venues as $v): ?><option value="<?=$v['id']?>"><?=h($v['name'])?></option><? endforeach ?></select></div>
				</form>
				<div class="form_btns"><div class="btn tp_save">Қосу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
