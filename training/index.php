<? include "../config/core.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);
	$cat = ['technique' => 'Техника', 'physical' => 'Физика', 'tactics' => 'Тактика', 'ball' => 'Доппен жұмыс', 'individual' => 'Жеке жаттығу'];
	$lvl = ['easy' => 'Жеңіл', 'medium' => 'Орташа', 'hard' => 'Қиын'];
	$sub_st = ['done' => ['Орындалды', 'bdg_warn'], 'review' => ['Тексерілуде', 'bdg_warn'], 'accepted' => ['Қабылданды', 'bdg_ok'], 'rejected' => ['Қайта орындау', 'bdg_err']];

	$f_cat = isset($_GET['cat']) && isset($cat[$_GET['cat']]) ? $_GET['cat'] : '';
	$lessons = db::all("select * from training_lessons where organization_id = ?".($f_cat ? " and category = ?" : "")." order by id desc", $f_cat ? [$org_id, $f_cat] : [$org_id]);

	if ($staff) {
		$gw = core::is(['owner', 'admin']) ? "" : " and (g.coach_id = ".(int) $user_id." or t.created_by = ".(int) $user_id.")";
		$asg = db::all("select t.*, l.title, g.name group_name, concat(p.last_name, ' ', p.first_name) part_name,
				(select count(*) from assignment_submissions x where x.assignment_id = t.id) subs,
				(select count(*) from assignment_submissions x where x.assignment_id = t.id and x.status in ('done', 'review')) to_check
			from training_assignments t join training_lessons l on l.id = t.lesson_id left join `groups` g on g.id = t.group_id left join participants p on p.id = t.participant_id
			where t.organization_id = ?$gw order by t.id desc limit 50", [$org_id]);
		$check = db::all("select x.*, l.title, concat(p.last_name, ' ', p.first_name) part_name from assignment_submissions x join training_assignments t on t.id = x.assignment_id join training_lessons l on l.id = t.lesson_id
			join participants p on p.id = x.participant_id left join `groups` g on g.id = t.group_id where x.organization_id = ? and x.status in ('done', 'review')$gw order by x.id", [$org_id]);
		$groups = db::all("select id, name from `groups` g where organization_id = ? and active = 1".(core::is(['owner', 'admin']) ? "" : " and coach_id = ".(int) $user_id)." order by name", [$org_id]);
		list($sw, $sp) = fun::scope();
		$parts = db::all("select p.id, p.first_name, p.last_name from participants p where p.organization_id = ? and p.archived_at is null and $sw order by p.last_name", array_merge([$org_id], $sp));
	} else {
		// ата-ана/қатысушы: өз балаларының тапсырмалары
		list($sw, $sp) = fun::scope();
		$kids = db::all("select p.id, p.first_name, (select e.group_id from enrollments e where e.participant_id = p.id and e.end_date is null limit 1) gid from participants p where p.organization_id = ? and p.archived_at is null and $sw", array_merge([$org_id], $sp));
		$my = [];
		foreach ($kids as $k) foreach (db::all("select t.*, l.title, l.category, l.description, l.video_key, l.reps, l.coach_tip, l.level, x.id sub_id, x.status sub_status, x.coach_comment
				from training_assignments t join training_lessons l on l.id = t.lesson_id left join assignment_submissions x on x.assignment_id = t.id and x.participant_id = ?
				where t.organization_id = ? and (t.participant_id = ? or t.group_id = ?) order by t.due_date is null, t.due_date desc limit 30", [(int) $k['id'], $org_id, (int) $k['id'], (int) $k['gid']]) as $t) { $t['kid'] = $k; $my[] = $t; }
	}


	// site setting
	$menu_name = 'training';
	$menu_title = 'Training Hub';
	$js = ['training'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Training Hub</h4><p>Үйде орындайтын жаттығулар мен тапсырмалар</p></div>
			<? if ($staff): ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<div class="btn btn_sm btn_ghost" data-pop="tr_lesson"><i class="fal fa-plus"></i><span>Жаттығу</span></div>
					<div class="btn btn_sm" data-pop="tr_assign"><i class="fal fa-tasks"></i><span>Тапсырма беру</span></div>
				</div>
			<? endif ?>
		</div>

		<? if ($staff): ?>
			<? if ($check): ?>
				<div class="card" style="margin-bottom:16px">
					<div class="card_h"><h6>Тексеру керек</h6><span class="bdg bdg_warn"><?=count($check)?></span></div>
					<? foreach ($check as $x): ?>
						<div class="li">
							<div class="li_i"><i class="fal fa-clipboard-check"></i></div>
							<div class="li_c"><b><?=h($x['part_name'])?> · <?=h($x['title'])?></b><span><?=h($x['comment'] ?: '—')?><?=($x['video_key']?' · <a href="'.h('https://youtu.be/'.$x['video_key']).'" target="_blank" rel="noopener">видео</a>':'')?> · <?=date('d.m H:i', strtotime($x['created_at']))?></span></div>
							<div class="li_r" style="display:flex;gap:6px"><span class="bdg bdg_ok tr_rev" data-id="<?=$x['id']?>" data-s="accepted" style="cursor:pointer">Қабылдау</span><span class="bdg bdg_err tr_rev" data-id="<?=$x['id']?>" data-s="rejected" style="cursor:pointer">Қайтару</span></div>
						</div>
					<? endforeach ?>
				</div>
			<? endif ?>
			<div class="card" style="margin-bottom:16px">
				<div class="card_h"><h6>Берілген тапсырмалар</h6></div>
				<? foreach ($asg as $a): ?>
					<div class="li"><div class="li_i"><i class="fal fa-tasks"></i></div>
						<div class="li_c"><b><?=h($a['title'])?></b><span><?=h($a['group_name'] ?: $a['part_name'])?><?=($a['due_date']?' · '.fun::date_short($a['due_date']).' дейін':'')?></span></div>
						<div class="li_r"><span class="bdg"><?=$a['subs']?> орындады</span></div></div>
				<? endforeach ?>
				<? if (!$asg): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-tasks"></i><p>Тапсырма жоқ</p></div><? endif ?>
			</div>
		<? else: ?>
			<div class="card" style="margin-bottom:16px">
				<div class="card_h"><h6>Менің тапсырмаларым</h6></div>
				<? foreach ($my as $t): $st = $t['sub_status']; ?>
					<div class="li" style="align-items:flex-start">
						<div class="li_i"><i class="fal fa-dumbbell"></i></div>
						<div class="li_c"><b><?=h($t['title'])?> <? if (count($kids) > 1): ?><small>· <?=h($t['kid']['first_name'])?></small><? endif ?></b>
							<span><?=h($t['description'])?><?=($t['reps']?' · '.h($t['reps']):'')?><?=($t['due_date']?' · '.fun::date_short($t['due_date']).' дейін':'')?></span>
							<? if ($t['coach_tip']): ?><span>💡 <?=h($t['coach_tip'])?></span><? endif ?>
							<? if ($t['coach_comment']): ?><span>Бапкер: <?=h($t['coach_comment'])?></span><? endif ?>
							<? if ($t['video_key']): ?><span><a href="https://www.youtube.com/watch?v=<?=h($t['video_key'])?>" target="_blank" rel="noopener"><i class="fal fa-play-circle"></i> Видеоны көру</a></span><? endif ?>
						</div>
						<div class="li_r">
							<? if ($st && $st != 'rejected'): ?><span class="bdg <?=$sub_st[$st][1]?>"><?=$sub_st[$st][0]?></span>
							<? else: ?><span class="bdg bdg_ok tr_done" data-id="<?=$t['id']?>" data-pid="<?=$t['kid']['id']?>" data-t="<?=h($t['title'])?>" style="cursor:pointer"><?=($st=='rejected'?'Қайта жіберу':'Орындадым')?></span><? endif ?>
						</div>
					</div>
				<? endforeach ?>
				<? if (!$my): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-dumbbell"></i><p>Әзірге тапсырма жоқ</p></div><? endif ?>
			</div>
		<? endif ?>

		<div class="tab">
			<a class="tab_i <?=(!$f_cat?'tab_i_act':'')?>" href="/training/">Барлық жаттығу</a>
			<? foreach ($cat as $k => $v): ?><a class="tab_i <?=($f_cat==$k?'tab_i_act':'')?>" href="?cat=<?=$k?>"><?=$v?></a><? endforeach ?>
		</div>
		<div class="cab_g">
			<? foreach ($lessons as $l): ?>
				<div class="card">
					<? if ($l['video_key']): ?><a class="md_i" style="aspect-ratio:16/9;margin-bottom:12px" href="https://www.youtube.com/watch?v=<?=h($l['video_key'])?>" target="_blank" rel="noopener"><img src="https://img.youtube.com/vi/<?=h($l['video_key'])?>/hqdefault.jpg" alt="" loading="lazy"><i class="fal fa-play-circle md_play"></i></a><? endif ?>
					<div class="card_h"><h6><?=h($l['title'])?></h6><span class="bdg"><?=$lvl[$l['level']]?></span></div>
					<p style="font-size:13px;color:#4a544e"><?=h($l['description'])?></p>
					<p style="font-size:12px;color:#6e6e73;margin-top:8px"><?=$cat[$l['category']]?><?=($l['duration_min']?' · '.$l['duration_min'].' мин':'')?><?=($l['reps']?' · '.h($l['reps']):'')?></p>
					<? if ($l['coach_tip']): ?><p style="font-size:12px;margin-top:8px">💡 <?=h($l['coach_tip'])?></p><? endif ?>
				</div>
			<? endforeach ?>
		</div>
		<? if (!$lessons): ?><div class="card"><div class="ds_nr"><i class="fal fa-dumbbell"></i><p>Жаттығу жоқ</p></div></div><? endif ?>
		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
		<div class="pop_bl pop_bl2 tr_lesson">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c" style="padding:28px;overflow-y:auto">
				<div class="pop_h"><h5>Жаңа жаттығу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g ls_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="title" maxlength="160"></div>
					<div class="form_im"><label class="form_l">Санаты</label><select class="form_im_txt" name="category"><? foreach ($cat as $k => $v): ?><option value="<?=$k?>"><?=$v?></option><? endforeach ?></select></div>
					<div class="form_im"><label class="form_l">Деңгейі</label><select class="form_im_txt" name="level"><? foreach ($lvl as $k => $v): ?><option value="<?=$k?>"><?=$v?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Сипаттамасы</label><textarea class="form_im_txt" name="description" rows="3" style="height:auto"></textarea></div>
					<div class="form_im"><label class="form_l">Ұзақтығы, мин</label><input type="number" class="form_im_txt" name="duration_min" min="1" max="300"></div>
					<div class="form_im"><label class="form_l">Қайталау</label><input type="text" class="form_im_txt" name="reps" maxlength="60" placeholder="3 × 10"></div>
					<div class="form_im form_im_f"><label class="form_l">YouTube видео</label><input type="text" class="form_im_txt" name="youtube" placeholder="https://youtu.be/..."></div>
					<div class="form_im form_im_f"><label class="form_l">Бапкер кеңесі</label><input type="text" class="form_im_txt" name="coach_tip" maxlength="500"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ls_save">Сақтау</div></div>
			</div>
		</div>
		<div class="pop_bl tr_assign">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Тапсырма беру</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g as2_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Жаттығу</label><select class="form_im_txt" name="lesson_id"><? foreach ($lessons as $l): ?><option value="<?=$l['id']?>"><?=h($l['title'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Топқа</label><select class="form_im_txt" name="group_id"><option value="">—</option><? foreach ($groups as $g): ?><option value="<?=$g['id']?>"><?=h($g['name'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">немесе жеке қатысушыға</label><select class="form_im_txt" name="participant_id"><option value="">—</option><? foreach ($parts as $p): ?><option value="<?=$p['id']?>"><?=h($p['last_name'].' '.$p['first_name'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Мерзімі</label><input type="date" class="form_im_txt" name="due_date" value="<?=date('Y-m-d', strtotime('+7 day'))?>"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn as2_save">Беру</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
