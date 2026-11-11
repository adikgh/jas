<? include "../config/core.php";

	core::need_page(['owner', 'admin']);

	$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['camp', 'content']) ? $_GET['tab'] : 'leads';
	$lst = ['new' => ['Жаңа', 'bdg_warn'], 'contacted' => ['Хабарласты', ''], 'trial_booked' => ['Сынаққа жазылды', 'bdg_dk'], 'trial_done' => ['Сынақтан өтті', ''], 'converted' => ['Қатысушы болды', 'bdg_ok'], 'lost' => ['Бас тартты', 'bdg_err']];
	$src = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'threads' => 'Threads', 'whatsapp' => 'WhatsApp', 'referral' => 'Таныс арқылы', 'walk_in' => 'Өзі келді', 'other' => 'Басқа'];
	$cst = ['idea' => 'Идея', 'in_work' => 'Дайындалуда', 'ready' => 'Дайын', 'published' => 'Жарияланды'];

	$f_st = isset($_GET['st']) && isset($lst[$_GET['st']]) ? $_GET['st'] : '';
	$leads = db::all("select l.*, c.name camp_name from leads l left join campaigns c on c.id = l.campaign_id where l.organization_id = ?".($f_st ? " and l.status = ?" : " and l.status not in ('converted', 'lost')")." order by l.id desc limit 300", $f_st ? [$org_id, $f_st] : [$org_id]);
	$cnt = []; foreach (db::all("select status, count(*) c from leads where organization_id = ? group by status", [$org_id]) as $r) $cnt[$r['status']] = (int) $r['c'];
	$camps = db::all("select c.*, count(l.id) leads, sum(l.status = 'converted') conv from campaigns c left join leads l on l.campaign_id = c.id where c.organization_id = ? group by c.id order by c.id desc", [$org_id]);
	$content = db::all("select ci.*, u.name assignee from content_items ci left join users u on u.id = ci.assignee_id where ci.organization_id = ? order by ci.publish_at is null, ci.publish_at", [$org_id]);
	$staff_users = db::all("select u.id, u.name from users u join memberships m on m.user_id = u.id where m.organization_id = ? and m.role in ('owner', 'admin', 'coach') and m.active = 1", [$org_id]);
	$all = array_sum($cnt);


	// site setting
	$menu_name = 'crm';
	$menu_title = 'Маркетинг CRM';
	$js = ['crm'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Маркетинг CRM</h4><p>Өтінім → сынақ → қатысушы</p></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<? if ($tab == 'leads'): ?><div class="btn btn_sm" data-pop="cr_lead"><i class="fal fa-plus"></i><span>Өтінім</span></div><? endif ?>
				<? if ($tab == 'camp'): ?><div class="btn btn_sm" data-pop="cr_camp"><i class="fal fa-plus"></i><span>Кампания</span></div><? endif ?>
				<? if ($tab == 'content'): ?><div class="btn btn_sm" data-pop="cr_cont"><i class="fal fa-plus"></i><span>Контент</span></div><? endif ?>
			</div>
		</div>

		<div class="dsh_k">
			<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t">Барлық өтінім</div><div class="dsh_ki_n"><?=$all?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Жаңа</div><div class="dsh_ki_n"><?=(int) @$cnt['new']?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Сынаққа жазылды</div><div class="dsh_ki_n"><?=(int) @$cnt['trial_booked']?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Конверсия</div><div class="dsh_ki_n"><?=($all ? round(@$cnt['converted'] * 100 / $all) : 0)?>%</div><div class="dsh_ki_s"><?=(int) @$cnt['converted']?> қатысушы болды</div></div>
		</div>

		<div class="tab" style="margin-top:16px">
			<a class="tab_i <?=($tab=='leads'?'tab_i_act':'')?>" href="/crm/">Өтінімдер</a>
			<a class="tab_i <?=($tab=='camp'?'tab_i_act':'')?>" href="?tab=camp">Кампаниялар</a>
			<a class="tab_i <?=($tab=='content'?'tab_i_act':'')?>" href="?tab=content">Контент жоспары</a>
		</div>

		<? if ($tab == 'leads'): ?>
			<form class="flt" method="get"><div class="form_im"><select class="form_im_txt flt_auto" name="st"><option value="">Белсенді өтінімдер</option><? foreach ($lst as $k => $v): ?><option value="<?=$k?>" <?=($f_st==$k?'selected':'')?>><?=$v[0]?> (<?=(int) @$cnt[$k]?>)</option><? endforeach ?></select></div></form>
			<div class="card" style="margin-top:12px">
				<? foreach ($leads as $l): ?>
					<div class="li">
						<div class="li_i"><i class="fal fa-user"></i></div>
						<div class="li_c"><b><?=h($l['name'])?> · <a href="tel:+7<?=h(substr($l['phone'], 1))?>"><?=fun::phone_view($l['phone'])?></a> · <a href="https://wa.me/7<?=h(substr($l['phone'], 1))?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a></b>
							<span><?=($l['direction']?t::w($l['direction']):'')?><?=($l['child_age']?' · '.$l['child_age'].' жас':'')?> · <?=$src[$l['source']]?><?=($l['camp_name']?' · '.h($l['camp_name']):'')?> · <?=date('d.m', strtotime($l['created_at']))?><?=($l['comment']?' · '.h($l['comment']):'')?></span></div>
						<div class="li_r"><select class="form_im_txt cr_st" data-id="<?=$l['id']?>" style="height:34px;padding:4px 8px;font-size:12px"><? foreach ($lst as $k => $v): ?><option value="<?=$k?>" <?=($l['status']==$k?'selected':'')?>><?=$v[0]?></option><? endforeach ?></select></div>
					</div>
				<? endforeach ?>
				<? if (!$leads): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-inbox"></i><p>Өтінім жоқ</p></div><? endif ?>
			</div>
		<? elseif ($tab == 'camp'): ?>
			<div class="card">
				<div class="tbl_w"><table class="tbl">
					<tr><th>Кампания</th><th>Арна</th><th>Бюджет</th><th>Өтінім</th><th>Қатысушы</th><th>1 өтінім</th><th>1 қатысушы</th></tr>
					<? foreach ($camps as $c): ?>
						<tr><td><b><?=h($c['name'])?></b><br><small><?=($c['start_date']?fun::date_short($c['start_date']):'')?></small></td><td><?=h($c['channel'])?></td><td><?=fun::money($c['budget'])?></td><td><?=$c['leads']?></td><td><?=(int) $c['conv']?></td>
							<td><?=($c['leads']?fun::money($c['budget'] / $c['leads']):'—')?></td><td><?=($c['conv']?fun::money($c['budget'] / $c['conv']):'—')?></td></tr>
					<? endforeach ?>
				</table></div>
				<? if (!$camps): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-bullhorn"></i><p>Кампания жоқ</p></div><? endif ?>
			</div>
		<? else: ?>
			<div class="card">
				<? foreach ($content as $c): ?>
					<div class="li">
						<div class="li_i"><i class="fab fa-<?=($c['channel']=='threads'?'at':h($c['channel']))?>"></i></div>
						<div class="li_c"><b><?=h($c['title'])?></b><span><?=($c['publish_at']?date('d.m H:i', strtotime($c['publish_at'])):'күні жоқ')?><?=($c['assignee']?' · '.h($c['assignee']):'')?></span></div>
						<div class="li_r"><select class="form_im_txt cr_cst" data-id="<?=$c['id']?>" style="height:34px;padding:4px 8px;font-size:12px"><? foreach ($cst as $k => $v): ?><option value="<?=$k?>" <?=($c['status']==$k?'selected':'')?>><?=$v?></option><? endforeach ?></select></div>
					</div>
				<? endforeach ?>
				<? if (!$content): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-calendar"></i><p>Жоспар бос</p></div><? endif ?>
			</div>
		<? endif ?>
		<div style="height:40px"></div>
	</div>

	<div class="pop_bl cr_lead">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Жаңа өтінім</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g ld_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Аты</label><input type="text" class="form_im_txt" name="name" maxlength="120"></div>
				<div class="form_im form_im_f"><label class="form_l">Телефон</label><input type="tel" class="form_im_txt fr_phone" name="phone"></div>
				<div class="form_im"><label class="form_l">Бағыты</label><select class="form_im_txt" name="direction"><option value="">—</option><option value="kids">JAS KIDS</option><option value="adult">JAS ADULT</option></select></div>
				<div class="form_im"><label class="form_l">Баланың жасы</label><input type="number" class="form_im_txt" name="child_age" min="3" max="60"></div>
				<div class="form_im"><label class="form_l">Қайдан</label><select class="form_im_txt" name="source"><? foreach ($src as $k => $v): ?><option value="<?=$k?>"><?=$v?></option><? endforeach ?></select></div>
				<div class="form_im"><label class="form_l">Кампания</label><select class="form_im_txt" name="campaign_id"><option value="">—</option><? foreach ($camps as $c): ?><option value="<?=$c['id']?>"><?=h($c['name'])?></option><? endforeach ?></select></div>
				<div class="form_im form_im_f"><label class="form_l">Пікір</label><input type="text" class="form_im_txt" name="comment" maxlength="255"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ld_save">Қосу</div></div>
		</div>
	</div>
	<div class="pop_bl cr_camp">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Жаңа кампания</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g cm_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="name" maxlength="120"></div>
				<div class="form_im"><label class="form_l">Арна</label><select class="form_im_txt" name="channel"><option value="instagram">Instagram</option><option value="tiktok">TikTok</option><option value="threads">Threads</option><option value="other">Басқа</option></select></div>
				<div class="form_im"><label class="form_l">Бюджет, ₸</label><input type="number" class="form_im_txt" name="budget" min="0"></div>
				<div class="form_im"><label class="form_l">Басталуы</label><input type="date" class="form_im_txt" name="start_date" value="<?=$date?>"></div>
				<div class="form_im"><label class="form_l">Аяқталуы</label><input type="date" class="form_im_txt" name="end_date"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn cm_save">Қосу</div></div>
		</div>
	</div>
	<div class="pop_bl cr_cont">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Контент</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g ct_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Тақырыбы</label><input type="text" class="form_im_txt" name="title" maxlength="160"></div>
				<div class="form_im"><label class="form_l">Арна</label><select class="form_im_txt" name="channel"><option value="instagram">Instagram</option><option value="tiktok">TikTok</option><option value="threads">Threads</option></select></div>
				<div class="form_im"><label class="form_l">Жариялау уақыты</label><input type="datetime-local" class="form_im_txt" name="publish_at"></div>
				<div class="form_im form_im_f"><label class="form_l">Жауапты</label><select class="form_im_txt" name="assignee_id"><option value="">—</option><? foreach ($staff_users as $u): ?><option value="<?=$u['id']?>"><?=h($u['name'])?></option><? endforeach ?></select></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ct_save">Қосу</div></div>
		</div>
	</div>

<? include "../block/footer.php"; ?>
