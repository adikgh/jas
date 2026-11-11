<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach']);
	$fin = core::is(['owner', 'admin']);

	$gp = [$org_id];
	if (!$fin) $gp[] = $user_id;
	$groups = db::all("select g.*, u.name coach_name, u.surname coach_surname, v.name venue_name, v.address venue_address,
			(select count(*) from enrollments e join participants p on p.id = e.participant_id where e.group_id = g.id and e.end_date is null and p.archived_at is null and p.status = 'active') cnt
		from `groups` g left join users u on u.id = g.coach_id left join venues v on v.id = g.venue_id
		where g.organization_id = ?".($fin ? "" : " and g.coach_id = ?")." order by g.active desc, g.direction, g.name", $gp);
	$tpl = [];
	foreach (db::all("select * from schedule_templates where organization_id = ? and (valid_to is null or valid_to >= ?) order by weekday, start_time", [$org_id, $date]) as $t) $tpl[$t['group_id']][] = $t;

	$coaches = $fin ? db::all("select u.id, u.name, u.surname from users u join memberships m on m.user_id = u.id where m.organization_id = ? and m.role = 'coach' and m.active = 1 order by u.name", [$org_id]) : [];
	$venues = $fin ? db::all("select * from venues where organization_id = ? and active = 1 order by name", [$org_id]) : [];


	// site setting
	$menu_name = 'groups';
	$menu_title = 'Топтар';
	$js = ['groups'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4>Топтар</h4><p>Тұрақты кесте және бапкерлер</p></div>
			<? if ($fin): ?><div class="btn btn_sm" data-pop="group_add"><i class="fal fa-plus"></i><span>Топ қосу</span></div><? endif ?>
		</div>

		<div class="dsh_g3" style="margin-top:0">
			<? foreach ($groups as $g): ?>
				<div class="card" style="<?=($g['active']?'':'opacity:.55')?>">
					<div class="card_h">
						<h6><?=h($g['name'])?></h6>
						<span class="bdg bdg_<?=$g['direction']?>"><?=t::w($g['direction'])?></span>
					</div>
					<div class="li"><div class="li_i"><i class="fal fa-whistle"></i></div><div class="li_c"><b><?=h(trim($g['coach_name'].' '.$g['coach_surname']) ?: '—')?></b><span>бапкер</span></div></div>
					<div class="li"><div class="li_i"><i class="fal fa-map-marker-alt"></i></div><div class="li_c"><b><?=h($g['venue_name'] ?: '—')?></b><span><?=h($g['venue_address'])?></span></div></div>
					<div class="li"><div class="li_i"><i class="fal fa-calendar-alt"></i></div><div class="li_c">
						<b><?=(isset($tpl[$g['id']]) ? implode(', ', array_map(function ($t) { return fun::$weekdays[$t['weekday']].' '.substr($t['start_time'], 0, 5); }, $tpl[$g['id']])) : 'Кесте жоқ')?></b><span>тұрақты кесте</span></div></div>
					<a class="li" href="/participants/?group=<?=$g['id']?>"><div class="li_i"><i class="fal fa-users"></i></div><div class="li_c"><b><?=$g['cnt']?><?=($g['capacity']?' / '.$g['capacity']:'')?> қатысушы</b><span><?=($g['age_from']?$g['age_from'].'–'.$g['age_to'].' жас':'')?></span></div><div class="li_r"><i class="fal fa-angle-right"></i></div></a>
				</div>
			<? endforeach ?>
		</div>
		<? if (!$groups): ?><div class="card"><div class="ds_nr"><i class="fal fa-users-class"></i><p>Топ жоқ</p></div></div><? endif ?>

		<div style="height:40px"></div>
	</div>

	<? if ($fin): ?>
		<div class="pop_bl group_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Жаңа топ</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g gr_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Атауы <em>*</em></label><input type="text" class="form_im_txt" name="name" maxlength="80" placeholder="KIDS U9"></div>
					<div class="form_im"><label class="form_l">Бағыты <em>*</em></label><select class="form_im_txt" name="direction"><option value="kids">JAS KIDS</option><option value="adult">JAS ADULT</option></select></div>
					<div class="form_im"><label class="form_l">Сыйымдылығы</label><input type="number" class="form_im_txt" name="capacity" min="1" max="200"></div>
					<div class="form_im"><label class="form_l">Жасы (бастап)</label><input type="number" class="form_im_txt" name="age_from" min="3" max="80"></div>
					<div class="form_im"><label class="form_l">Жасы (дейін)</label><input type="number" class="form_im_txt" name="age_to" min="3" max="80"></div>
					<div class="form_im"><label class="form_l">Бапкер</label><select class="form_im_txt" name="coach_id"><option value="">—</option><? foreach ($coaches as $c): ?><option value="<?=$c['id']?>"><?=h(trim($c['name'].' '.$c['surname']))?></option><? endforeach ?></select></div>
					<div class="form_im"><label class="form_l">Алаң</label><select class="form_im_txt" name="venue_id"><option value="">—</option><? foreach ($venues as $v): ?><option value="<?=$v['id']?>"><?=h($v['name'])?></option><? endforeach ?></select></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn gr_save">Сақтау</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
