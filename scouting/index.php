<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach']);

	$camps = db::all("select c.*, (select count(*) from selection_candidates x where x.campaign_id = c.id) cnt,
			(select count(*) from selection_candidates x where x.campaign_id = c.id and x.status = 'accepted') acc
		from selection_campaigns c where c.organization_id = ? order by c.status = 'open' desc, c.id desc", [$org_id]);
	$cid = isset($_GET['id']) ? (int) $_GET['id'] : ($camps ? (int) $camps[0]['id'] : 0);
	$camp = null; foreach ($camps as $c) if ((int) $c['id'] === $cid) $camp = $c;

	$st_name = ['watching' => 'Бақылауда', 'candidate' => 'Кандидат', 'invited' => 'Шақырылды', 'passed' => 'Сынақтан өтті', 'failed' => 'Өтпеді', 'accepted' => 'Қабылданды'];
	$by = array_fill_keys(array_keys($st_name), []);
	$m_start = date('Y-m-01');
	if ($camp) {
		$rows = db::all("select x.*, p.first_name, p.last_name, p.birth_date, p.position, p.direction, u.name decided_name,
				(select coalesce(sum(points), 0) from rating_events r where r.participant_id = p.id) pts,
				(select round(sum(a.status = 'present') * 100 / nullif(sum(a.status in ('present', 'absent')), 0)) from attendance a where a.participant_id = p.id) att,
				(select round(avg(s.score), 1) from assessment_scores s where s.assessment_id = (select id from player_assessments pa where pa.participant_id = p.id order by pa.assessed_at desc, pa.id desc limit 1)) score
			from selection_candidates x join participants p on p.id = x.participant_id left join users u on u.id = x.decided_by
			where x.campaign_id = ? order by pts desc", [$cid]);
		foreach ($rows as $r) $by[$r['status']][] = $r;
		$dir = $camp['target'] == 'first_team' ? 'adult' : null;
		// ұсыныс: рейтингі жоғары, әлі тізімде жоқ (тек ұсыныс, шешімді бапкер қабылдайды)
		$sugg = db::all("select p.id, p.first_name, p.last_name, coalesce(sum(r.points), 0) pts from participants p left join rating_events r on r.participant_id = p.id
			where p.organization_id = ? and p.archived_at is null and p.status = 'active'".($dir ? " and p.direction = 'adult'" : "")."
			and not exists (select 1 from selection_candidates x where x.campaign_id = ? and x.participant_id = p.id)
			and not exists (select 1 from team_rosters t where t.participant_id = p.id and t.left_at is null)
			group by p.id order by pts desc, p.last_name limit 200", [$org_id, $cid]);
	}


	// site setting
	$menu_name = 'scouting';
	$menu_title = 'Селекция';
	$js = ['scouting'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Селекция</h4><p>Рейтинг тек көмекші ақпарат — шешімді бапкер қабылдайды</p></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<div class="btn btn_sm btn_ghost" data-pop="sc_camp"><i class="fal fa-plus"></i><span>Іріктеу</span></div>
				<? if ($camp && $camp['status'] == 'open'): ?><div class="btn btn_sm" data-pop="sc_add"><i class="fal fa-user-plus"></i><span>Кандидат қосу</span></div><? endif ?>
			</div>
		</div>

		<div class="tab">
			<? foreach ($camps as $c): ?><a class="tab_i <?=((int) $c['id']===$cid?'tab_i_act':'')?>" href="?id=<?=$c['id']?>"><?=h($c['name'])?> <small>(<?=$c['cnt']?>)</small><?=($c['status']=='closed'?' · жабық':'')?></a><? endforeach ?>
		</div>

		<? if ($camp): ?>
			<p style="font-size:13px;color:#6e6e73;margin:0 0 12px"><?=($camp['target']=='first_team'?'Бірінші құрамға':'Қалалық лигаға')?> · <?=($camp['start_date']?fun::date_short($camp['start_date']):'')?> – <?=($camp['end_date']?fun::date_short($camp['end_date']):'')?> · қабылданды <?=$camp['acc']?>
				<? if (core::is(['owner', 'admin'])): ?> · <a href="#" class="sc_close" data-id="<?=$cid?>" data-st="<?=($camp['status']=='open'?'closed':'open')?>"><?=($camp['status']=='open'?'Жабу':'Қайта ашу')?></a><? endif ?></p>
			<div class="sc_k">
				<? foreach ($st_name as $k => $v): ?>
					<div class="sc_kc">
						<h6><?=$v?><span class="bdg"><?=count($by[$k])?></span></h6>
						<? foreach ($by[$k] as $r): ?>
							<div class="sc_ki">
								<a href="/participants/item.php?id=<?=$r['participant_id']?>"><b><?=h($r['last_name'].' '.$r['first_name'])?></b></a>
								<small><?=fun::age($r['birth_date'])?> жас<?=($r['position']?' · '.t::w($r['position']):'')?></small><br>
								<small>⭐ <?=(int) $r['pts']?> · қатысу <?=($r['att']!==null?$r['att'].'%':'—')?> · баға <?=($r['score'] ?: '—')?></small>
								<? if ($r['notes']): ?><br><small>«<?=h($r['notes'])?>»</small><? endif ?>
								<? if ($r['decided_name']): ?><br><small>шешім: <?=h($r['decided_name'])?></small><? endif ?>
								<? if ($camp['status'] == 'open'): ?>
									<select class="sc_st" data-id="<?=$r['id']?>"><? foreach ($st_name as $kk => $vv): ?><option value="<?=$kk?>" <?=($kk==$k?'selected':'')?>><?=$vv?></option><? endforeach ?></select>
								<? endif ?>
							</div>
						<? endforeach ?>
					</div>
				<? endforeach ?>
			</div>
		<? else: ?>
			<div class="card"><div class="ds_nr"><i class="fal fa-binoculars"></i><p>Іріктеу әлі жоқ</p></div></div>
		<? endif ?>
		<div style="height:40px"></div>
	</div>

	<div class="pop_bl sc_camp">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Жаңа іріктеу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g cp_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="name" maxlength="120" placeholder="FIRST TEAM көктемгі іріктеу"></div>
				<div class="form_im form_im_f"><label class="form_l">Мақсаты</label><select class="form_im_txt" name="target"><option value="first_team">Бірінші құрам</option><option value="city_league">Қалалық лига</option></select></div>
				<div class="form_im"><label class="form_l">Басталуы</label><input type="date" class="form_im_txt" name="start_date" value="<?=$date?>"></div>
				<div class="form_im"><label class="form_l">Аяқталуы</label><input type="date" class="form_im_txt" name="end_date"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn cp_save">Құру</div></div>
		</div>
	</div>
	<? if ($camp): ?>
		<div class="pop_bl sc_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Кандидат қосу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g cd_form" onsubmit="return false">
					<input type="hidden" name="campaign_id" value="<?=$cid?>">
					<div class="form_im form_im_f"><label class="form_l">Қатысушы (рейтинг бойынша)</label><select class="form_im_txt" name="participant_id"><option value="">Таңдаңыз</option><? foreach ($sugg as $s): ?><option value="<?=$s['id']?>"><?=h($s['last_name'].' '.$s['first_name'])?> · ⭐ <?=(int) $s['pts']?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Ескертпе</label><input type="text" class="form_im_txt" name="notes" maxlength="500"></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn cd_save">Қосу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
