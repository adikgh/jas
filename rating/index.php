<? include "../config/core.php";

	core::need_page();
	$admin = core::is(['owner', 'admin']);
	$staff = core::is(['owner', 'admin', 'coach']);

	$scope = isset($_GET['s']) && $_GET['s'] == 'adult' ? 'adult' : 'kids';
	$type = isset($_GET['t']) && in_array($_GET['t'], ['discipline', 'all']) ? $_GET['t'] : 'month';
	$ym = isset($_GET['m']) && preg_match('/^\d{4}-\d{2}$/', $_GET['m']) ? $_GET['m'] : date('Y-m');
	$m_start = "$ym-01"; $m_end = date('Y-m-t', strtotime($m_start));

	// өз балалары / өзі (ата-ана мен қатысушы тек өз орнын көреді)
	list($sw, $sp) = fun::scope();
	$mine = array_map('intval', array_column(db::all("select p.id from participants p where p.organization_id = ? and $sw", array_merge([$org_id], $sp)), 'id'));
	if (!$staff && $mine) {
		$d = db::val("select direction from participants where id = ?", [$mine[0]]);
		if (!isset($_GET['s']) && $d) $scope = $d;
	}

	if ($type == 'discipline') {
		// тәртіп: айдағы қатысу пайызы (себепті және заморозка есептелмейді), кемінде 4 белгі
		$rows = db::all("select p.id, p.first_name, p.last_name, p.img, sum(a.status = 'present') came, sum(a.status = 'absent') missed,
				round(sum(a.status = 'present') * 100 / nullif(sum(a.status in ('present', 'absent')), 0)) val
			from attendance a join sessions s on s.id = a.session_id join participants p on p.id = a.participant_id
			where a.organization_id = ? and p.direction = ? and p.archived_at is null and s.type <> 'match' and s.status <> 'cancelled' and s.starts_at between ? and ?
			group by p.id having sum(a.status in ('present', 'absent')) >= 4 order by val desc, came desc, p.last_name", [$org_id, $scope, "$m_start 00:00:00", "$m_end 23:59:59"]);
	} else {
		$w = $type == 'month' ? " and r.created_at between ? and ?" : "";
		$p = [$org_id, $scope]; if ($type == 'month') { $p[] = "$m_start 00:00:00"; $p[] = "$m_end 23:59:59"; }
		$rows = db::all("select p.id, p.first_name, p.last_name, p.img, sum(r.points) val,
				sum(r.rule in ('attendance', 'streak5', 'streak10', 'month100')) att_cnt, sum(r.rule = 'coach_award') awards
			from rating_events r join participants p on p.id = r.participant_id
			where r.organization_id = ? and r.scope = ? and p.archived_at is null$w
			group by p.id having val > 0 order by val desc, p.last_name", $p);
	}
	// орын: бірдей ұпай — бірдей орын
	$place = 0; $prev = null;
	foreach ($rows as $i => &$r) { if ($r['val'] !== $prev) { $place = $i + 1; $prev = $r['val']; } $r['place'] = $place; }
	unset($r);
	$show = $staff ? $rows : array_values(array_filter($rows, function ($r) use ($mine) { return in_array((int) $r['id'], $mine, true); }));
	$total = count($rows);

	// марапат / прогресс үшін (бапкер — тек өз топтары)
	$parts = $staff ? db::all("select p.id, p.first_name, p.last_name, p.direction from participants p where p.organization_id = ? and p.archived_at is null and $sw order by p.last_name, p.first_name", array_merge([$org_id], $sp)) : [];
	$achs = $staff ? db::all("select * from achievements where organization_id = ? order by name", [$org_id]) : [];
	$recent = $staff ? db::all("select r.*, p.first_name, p.last_name, u.name by_name from rating_events r join participants p on p.id = r.participant_id left join users u on u.id = r.created_by
		where r.organization_id = ? and r.scope = ? and r.rule in ('coach_award', 'progress', 'manual') and p.archived_at is null and $sw order by r.id desc limit 10", array_merge([$org_id, $scope], $sp)) : [];

	$type_name = ['month' => 'Айлық', 'discipline' => 'Тәртіп', 'all' => 'All-Time'];
	$rule = ['coach_award' => 'Бапкер марапаты', 'progress' => 'Жеке прогресс', 'manual' => 'Түзету'];
	$ql = function ($a) { return '?'.http_build_query(array_merge($_GET, $a)); };


	// site setting
	$menu_name = 'rating';
	$menu_title = 'ZHASS Rating';
	$css = ['schedule', 'rating'];
	$js = ['rating'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4>ZHASS Rating</h4><p><?=t::w($scope)?> · <?=$type_name[$type]?><?=($type!='all'?' · '.fun::$months[(int) substr($ym, 5)].' '.substr($ym, 0, 4):'')?></p></div>
			<? if ($staff): ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<div class="btn btn_sm btn_ghost" data-pop="rt_progress"><i class="fal fa-chart-line"></i><span>Прогресс +20</span></div>
					<div class="btn btn_sm" data-pop="rt_award"><i class="fal fa-medal"></i><span>Марапаттау</span></div>
				</div>
			<? endif ?>
		</div>

		<div class="sch_top">
			<div class="tab" style="margin:0">
				<a class="tab_i <?=($scope=='kids'?'tab_i_act':'')?>" href="<?=h($ql(['s' => 'kids']))?>">ZHASS KIDS</a>
				<a class="tab_i <?=($scope=='adult'?'tab_i_act':'')?>" href="<?=h($ql(['s' => 'adult']))?>">ZHASS ADULT</a>
			</div>
			<div class="tab" style="margin:0">
				<? foreach ($type_name as $k => $v): ?><a class="tab_i <?=($type==$k?'tab_i_act':'')?>" href="<?=h($ql(['t' => $k]))?>"><?=$v?></a><? endforeach ?>
			</div>
			<? if ($type != 'all'): ?>
				<div class="sch_nav">
					<a class="btn btn_sm btn_ghost" href="<?=h($ql(['m' => date('Y-m', strtotime("$m_start -1 month"))]))?>"><i class="fal fa-angle-left"></i></a>
					<a class="btn btn_sm btn_ghost" href="<?=h($ql(['m' => date('Y-m')]))?>">Осы ай</a>
					<a class="btn btn_sm btn_ghost" href="<?=h($ql(['m' => date('Y-m', strtotime("$m_start +1 month"))]))?>"><i class="fal fa-angle-right"></i></a>
				</div>
			<? endif ?>
		</div>

		<? if ($staff && count($rows) >= 3 && $type != 'discipline'): ?>
			<div class="rt_top">
				<? foreach ([1, 0, 2] as $i): $r = $rows[$i]; ?>
					<a class="rt_tp rt_tp<?=$i + 1?>" href="/participants/item.php?id=<?=$r['id']?>#rating">
						<div class="rt_tpi"><?=($r['img'] ? '<img src="/assets/uploads/participants/'.h($r['img']).'" alt="">' : h(mb_substr($r['first_name'], 0, 1).mb_substr($r['last_name'], 0, 1)))?><em><?=$r['place']?></em></div>
						<b><?=h($r['first_name'])?> <?=h(mb_substr($r['last_name'], 0, 1))?>.</b>
						<span><?=(int) $r['val']?> ұпай</span>
					</a>
				<? endforeach ?>
			</div>
		<? endif ?>

		<div class="card">
			<div class="card_h"><h6><?=($staff ? 'Рейтинг' : 'Сіздің орныңыз')?></h6><span class="bdg"><?=$total?> қатысушы</span></div>
			<? foreach ($show as $r): ?>
				<a class="li rt_li" href="/participants/item.php?id=<?=$r['id']?>#rating">
					<div class="rt_pl rt_pl<?=min(4, $r['place'])?>"><?=$r['place']?></div>
					<div class="li_c"><b><?=h($r['last_name'].' '.$r['first_name'])?></b>
						<span><?=($type=='discipline' ? $r['came'].' келді · '.$r['missed'].' келмеді' : (int) $r['att_cnt'].' қатысу ұпайы'.($r['awards']?' · '.$r['awards'].' марапат':''))?></span></div>
					<div class="li_r"><b><?=(int) $r['val']?><?=($type=='discipline'?'%':'')?></b></div>
				</a>
			<? endforeach ?>
			<? if (!$show): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-trophy"></i><p><?=($type=='discipline'?'Кемінде 4 жаттығу белгіленгенде шығады':'Әзірге ұпай жоқ')?></p></div><? endif ?>
		</div>

		<div class="dsh_g" style="margin-top:16px">
			<div class="card">
				<div class="card_h"><h6>Ұпай ережесі</h6></div>
				<? foreach ([['fa-check', 'Жаттығуға келу', 10], ['fa-fire', 'Қатарынан 5 жаттығу', 25], ['fa-fire-alt', 'Қатарынан 10 жаттығу', 50], ['fa-calendar-check', 'Ай бойы 100% (кемінде '.academy::$month_min.' жаттығу)', 100], ['fa-medal', 'Бапкер марапаты', 30], ['fa-chart-line', 'Жеке прогресс (айына 1 рет)', 20]] as $x): ?>
					<div class="li"><div class="li_i"><i class="fal <?=$x[0]?>"></i></div><div class="li_c"><b><?=$x[1]?></b></div><div class="li_r cl_ok">+<?=$x[2]?></div></div>
				<? endforeach ?>
				<p style="font-size:12px;color:#6e6e73;margin-top:8px">Себепті және заморозка серияны үзбейді. ЛЛФ матчтары ұпай бермейді. Рейтинг селекцияға автоматты қабылдамайды — шешімді бапкер қабылдайды.</p>
			</div>
			<? if ($staff): ?>
				<div class="card">
					<div class="card_h"><h6>Соңғы марапаттар</h6><? if ($admin): ?><span class="bdg" data-pop="rt_manual" style="cursor:pointer">± Түзету</span><? endif ?></div>
					<? foreach ($recent as $r): ?>
						<div class="li">
							<div class="li_i"><i class="fal fa-medal"></i></div>
							<div class="li_c"><b><?=h($r['last_name'].' '.$r['first_name'])?></b><span><?=$rule[$r['rule']]?><?=($r['comment']?' · '.h($r['comment']):'')?> · <?=h($r['by_name'])?>, <?=date('d.m', strtotime($r['created_at']))?></span></div>
							<div class="li_r <?=($r['points']<0?'cl_err':'cl_ok')?>"><?=($r['points']>0?'+':'').$r['points']?><? if ($admin): ?> <i class="fal fa-times rt_rev" data-id="<?=$r['id']?>" style="cursor:pointer;color:#999" title="Қайтарып алу"></i><? endif ?></div>
						</div>
					<? endforeach ?>
					<? if (!$recent): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-medal"></i><p>Әзірге жоқ</p></div><? endif ?>
				</div>
			<? endif ?>
		</div>

		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
		<? $popt = function () use ($parts) { foreach ($parts as $p) echo '<option value="'.$p['id'].'">'.h($p['last_name'].' '.$p['first_name']).' · '.($p['direction']=='kids'?'KIDS':'ADULT').'</option>'; }; ?>
		<div class="pop_bl rt_award">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Марапаттау</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g aw_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Қатысушы <em>*</em></label><select class="form_im_txt" name="participant_id"><option value="">Таңдаңыз</option><? $popt() ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Марапат <em>*</em></label><select class="form_im_txt" name="achievement_id"><? foreach ($achs as $a): ?><option value="<?=$a['id']?>"><?=h($a['name'])?> · +<?=(int) $a['points']?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Не үшін</label><input type="text" class="form_im_txt" name="comment" maxlength="255"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn aw_save">Марапаттау</div></div>
			</div>
		</div>
		<div class="pop_bl rt_progress">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Жеке прогресс · +20</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g pr_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Қатысушы <em>*</em></label><select class="form_im_txt" name="participant_id"><option value="">Таңдаңыз</option><? $popt() ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Қандай прогресс <em>*</em></label><input type="text" class="form_im_txt" name="comment" maxlength="255" placeholder="Сол аяқпен соққы жақсарды"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn pr_save">Қосу</div></div>
			</div>
		</div>
	<? endif ?>
	<? if ($admin): ?>
		<div class="pop_bl rt_manual">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Ұпайды түзету</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g mn_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Қатысушы</label><select class="form_im_txt" name="participant_id"><option value="">Таңдаңыз</option><? $popt() ?></select></div>
					<div class="form_im"><label class="form_l">Ұпай (±)</label><input type="number" class="form_im_txt" name="points" min="-200" max="200"></div>
					<div class="form_im form_im_f"><label class="form_l">Себебі <em>*</em></label><input type="text" class="form_im_txt" name="comment" maxlength="255"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn mn_save">Сақтау</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
