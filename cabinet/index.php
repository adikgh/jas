<? include "../config/core.php";

	core::need_page(['parent', 'participant']);

	list($sw, $sp) = fun::scope();
	$kids = db::all("select p.* from participants p where p.organization_id = ? and p.archived_at is null and $sw order by p.birth_date desc", array_merge([$org_id], $sp));
	$m_start = date('Y-m-01');

	foreach ($kids as &$p) {
		$pid = (int) $p['id'];
		$p['group'] = fun::participant_group($pid);
		$p['next'] = $p['group'] ? db::row("select s.*, v.name venue_name, v.address from sessions s left join venues v on v.id = s.venue_id where s.group_id = ? and s.status = 'planned' and s.starts_at >= now() order by s.starts_at limit 1", [(int) $p['group']['id']]) : null;
		$sub = db::row("select s.*, pl.name plan_name from subscriptions s join plans pl on pl.id = s.plan_id where s.participant_id = ? and s.status <> 'cancelled' order by s.id desc limit 1", [$pid]);
		if ($sub) {
			$sub['used'] = academy::sub_used($sub);
			$sub['frozen'] = academy::sub_frozen_days($sub['id']);
			$sub['freeze_req'] = db::row("select * from freezes where subscription_id = ? and status = 'requested' order by id desc limit 1", [(int) $sub['id']]);
		}
		$p['sub'] = $sub;
		$p['debt'] = fun::participant_debt($pid);
		$a = db::row("select sum(a.status = 'present') c, sum(a.status in ('present', 'absent')) t from attendance a join sessions s on s.id = a.session_id where a.participant_id = ? and s.starts_at >= ? and s.type <> 'match'", [$pid, date('Y-m-d', strtotime('-30 day'))]);
		$p['att'] = $a['t'] ? round($a['c'] * 100 / $a['t']) : null;
		$p['pts'] = (int) db::val("select coalesce(sum(points), 0) from rating_events where participant_id = ? and created_at >= ?", [$pid, $m_start]);
		$p['place'] = $p['pts'] ? (int) db::val("select count(*) + 1 from (select participant_id, sum(points) s from rating_events where organization_id = ? and scope = ? and created_at >= ? group by participant_id) x where x.s > ?", [$org_id, $p['direction'], $m_start, $p['pts']]) : null;
		$p['score'] = db::val("select round(avg(s.score), 1) from assessment_scores s where s.assessment_id = (select id from player_assessments where participant_id = ? order by assessed_at desc, id desc limit 1)", [$pid]);
		$p['tasks'] = (int) db::val("select count(*) from training_assignments t where t.organization_id = ? and (t.participant_id = ? or t.group_id = ?) and (t.due_date is null or t.due_date >= ?)
			and not exists (select 1 from assignment_submissions x where x.assignment_id = t.id and x.participant_id = ? and x.status in ('done', 'review', 'accepted'))", [$org_id, $pid, $p['group'] ? (int) $p['group']['id'] : 0, $date, $pid]);
		$p['match'] = db::row("select m.*, o.name opp from matches m join opponents o on o.id = m.opponent_id join team_rosters r on r.team_id = m.team_id and r.participant_id = ? and r.left_at is null where m.status = 'scheduled' and m.match_at >= now() order by m.match_at limit 1", [$pid]);
	}
	unset($p);

	$notes = db::all("select * from notifications where user_id = ? order by id desc limit 5", [$user_id]);
	$live = db::row("select * from live_streams where organization_id = ? and status <> 'ended' and starts_at >= ? order by status = 'live' desc, starts_at limit 1", [$org_id, date('Y-m-d H:i:s', strtotime('-3 hour'))]);


	// site setting
	$menu_name = 'cabinet';
	$menu_title = 'Менің кабинетім';
	$js = ['cabinet'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Сәлем, <?=h($user['name'])?>!</h4><p><?=fun::date_kz($date)?></p></div>
		</div>

		<? if ($live): ?>
			<a class="card li" href="/live/" style="margin:0 0 16px">
				<div class="li_i"><i class="fab fa-youtube"></i></div>
				<div class="li_c"><b><?=h($live['title'])?></b><span><?=date('d.m H:i', strtotime($live['starts_at']))?></span></div>
				<div class="li_r"><span class="bdg <?=($live['status']=='live'?'bdg_err':'bdg_warn')?>"><?=($live['status']=='live'?'● Эфирде':'Тікелей эфир')?></span></div>
			</a>
		<? endif ?>

		<div class="cab_g">
			<? foreach ($kids as $p): $s = $p['sub']; ?>
				<div class="card">
					<a class="li" href="/participants/item.php?id=<?=$p['id']?>" style="border:0;padding-top:0">
						<div class="li_i" style="width:56px;height:56px"><?=($p['img'] ? '<img src="/assets/uploads/participants/'.h($p['img']).'" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">' : h(mb_substr($p['first_name'], 0, 1).mb_substr($p['last_name'], 0, 1)))?></div>
						<div class="li_c"><b style="font-size:17px"><?=h($p['first_name'].' '.$p['last_name'])?></b><span><?=t::w($p['direction'])?><?=($p['group']?' · '.h($p['group']['name']):'')?></span></div>
						<div class="li_r"><i class="fal fa-angle-right"></i></div>
					</a>

					<div class="li"><div class="li_i"><i class="fal fa-calendar-alt"></i></div><div class="li_c">
						<? if ($p['next']): ?><b><?=fun::$weekdays[(int) date('N', strtotime($p['next']['starts_at']))]?>, <?=date('d.m · H:i', strtotime($p['next']['starts_at']))?></b><span><?=h($p['next']['venue_name'])?><?=($p['next']['address']?' · '.h($p['next']['address']):'')?></span>
						<? else: ?><b>Жақын жаттығу жоқ</b><? endif ?>
					</div><a class="li_r" href="/schedule/"><small>кесте</small></a></div>

					<div class="li"><div class="li_i"><i class="fal fa-id-card"></i></div><div class="li_c">
						<? if (!$s): ?><b>Абонемент жоқ</b><span>Әкімшіге хабарласыңыз</span>
						<? elseif ($s['status'] == 'pending'): ?><b>Төлем күтілуде</b><span><?=h($s['plan_name'])?> · <?=fun::money($s['price'] - $s['discount'])?></span>
						<? else: ?><b><?=t::w('sub_'.$s['status'])?> · <?=fun::date_short($s['end_date'])?> дейін</b><span><?=($s['sessions_limit'] ? max(0, $s['sessions_limit'] - $s['used']).' жаттығу қалды' : 'шексіз')?> · заморозка <?=max(0, $s['freeze_days_limit'] - $s['frozen'])?> күн қалды</span><? endif ?>
					</div>
						<? if ($s && in_array($s['status'], ['active', 'frozen']) && !$s['freeze_req'] && $s['frozen'] < $s['freeze_days_limit']): ?><div class="li_r"><span class="bdg cb_fz" style="cursor:pointer" data-id="<?=$s['id']?>" data-max="<?=$s['freeze_days_limit'] - $s['frozen']?>" data-n="<?=h($p['first_name'])?>"><i class="fal fa-snowflake"></i> Заморозка</span></div>
						<? elseif ($s && $s['freeze_req']): ?><div class="li_r"><span class="bdg bdg_warn">өтініш қаралуда</span></div><? endif ?>
					</div>
					<? if ($p['debt'] > 0): ?><div class="li"><div class="li_i"><i class="fal fa-wallet"></i></div><div class="li_c"><b class="cl_err">Қарыз: <?=fun::money($p['debt'])?></b><span>Kaspi арқылы төлеп, чекті әкімшіге жіберіңіз</span></div></div><? endif ?>

					<div class="dsh_k" style="grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px">
						<a class="card dsh_ki" href="/participants/item.php?id=<?=$p['id']?>#attendance" style="background:#f5f5f3"><div class="dsh_ki_t">Қатысу 30 к.</div><div class="dsh_ki_n" style="font-size:22px"><?=($p['att']!==null?$p['att'].'%':'—')?></div></a>
						<a class="card dsh_ki" href="/rating/" style="background:#f5f5f3"><div class="dsh_ki_t">Рейтинг</div><div class="dsh_ki_n" style="font-size:22px"><?=($p['place']?'#'.$p['place']:'—')?></div><div class="dsh_ki_s"><?=$p['pts']?> ұпай</div></a>
						<a class="card dsh_ki" href="/progress/item.php?id=<?=$p['id']?>" style="background:#f5f5f3"><div class="dsh_ki_t">Баға</div><div class="dsh_ki_n" style="font-size:22px"><?=($p['score'] ?: '—')?></div></a>
					</div>
					<? if ($p['tasks']): ?><a class="li" href="/training/"><div class="li_i"><i class="fal fa-dumbbell"></i></div><div class="li_c"><b><?=$p['tasks']?> үй тапсырмасы</b><span>Training Hub</span></div><div class="li_r"><i class="fal fa-angle-right"></i></div></a><? endif ?>
					<? if ($p['match']): ?><a class="li" href="/first_team/match.php?id=<?=$p['match']['id']?>"><div class="li_i"><i class="fal fa-futbol"></i></div><div class="li_c"><b>ZHASS — <?=h($p['match']['opp'])?></b><span><?=date('d.m H:i', strtotime($p['match']['match_at']))?> · <?=h($p['match']['venue'])?></span></div></a><? endif ?>
				</div>
			<? endforeach ?>
			<? if (!$kids): ?><div class="card"><div class="ds_nr"><i class="fal fa-user"></i><p>Профиль байланыстырылмаған. Әкімшіге хабарласыңыз.</p></div></div><? endif ?>

			<div class="card">
				<div class="card_h"><h6>Хабарламалар</h6><a href="/notifications/"><small>барлығы</small></a></div>
				<? foreach ($notes as $n): ?>
					<a class="li" href="<?=h($n['link'] ?: '/notifications/')?>"><div class="li_i"><i class="fal fa-bell"></i></div><div class="li_c"><b style="<?=($n['read_at']?'font-weight:500':'')?>"><?=h($n['title'])?></b><span><?=h($n['body'])?> · <?=date('d.m H:i', strtotime($n['created_at']))?></span></div></a>
				<? endforeach ?>
				<? if (!$notes): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-bell"></i><p>Хабарлама жоқ</p></div><? endif ?>
			</div>
		</div>
		<div style="height:40px"></div>
	</div>

	<div class="pop_bl cb_freeze">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Заморозка өтініші · <span class="cf_n"></span></h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g cf_form" onsubmit="return false">
				<input type="hidden" name="id">
				<div class="form_im"><label class="form_l">Басталуы</label><input type="date" class="form_im_txt" name="start" value="<?=date('Y-m-d', strtotime('+1 day'))?>" min="<?=$date?>"></div>
				<div class="form_im"><label class="form_l">Күн (<span class="cf_max"></span> дейін)</label><input type="number" class="form_im_txt" name="days" min="1" max="7" value="1"></div>
				<div class="form_im form_im_f"><label class="form_l">Себебі</label><input type="text" class="form_im_txt" name="reason" maxlength="255" placeholder="Ауырып қалды, сапар..."></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn cf_save">Жіберу</div></div>
		</div>
	</div>

<? include "../block/footer.php"; ?>
