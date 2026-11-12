<? include "../config/core.php";

	core::need_page();
	$fin = core::is(['owner', 'admin']);
	$staff = core::is(['owner', 'admin', 'coach']);

	// рұқсат серверде тексеріледі: басқа баланың профилі ашылмайды
	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$p = fun::participant($id);
	if (!$p) { http_response_code(404); include "../block/403.php"; exit(); }

	$age = fun::age($p['birth_date']);
	$group = fun::participant_group($id);
	$guardians = db::all("select gd.*, pg.relation, pg.is_primary from participant_guardians pg join guardians gd on gd.id = pg.guardian_id where pg.participant_id = ? order by pg.is_primary desc, pg.id", [$id]);
	$enrolls = db::all("select e.*, g.name group_name from enrollments e join `groups` g on g.id = e.group_id where e.participant_id = ? order by e.start_date desc, e.id desc", [$id]);
	$rosters = db::all("select r.*, t.name team_name from team_rosters r join teams t on t.id = r.team_id where r.participant_id = ? order by r.joined_at desc", [$id]);
	$in_first = (bool) array_filter($rosters, function ($r) { return !$r['left_at']; });

	// абонемент + төлем
	$subs = db::all("select s.*, pl.name plan_name, coalesce((select sum(pa.amount) from payment_allocations pa join payments py on py.id = pa.payment_id and py.status = 'confirmed' where pa.subscription_id = s.id), 0) paid,
			(select count(*) from attendance a join sessions se on se.id = a.session_id where a.participant_id = s.participant_id and a.status = 'present' and se.type <> 'match' and se.status = 'done' and s.start_date is not null and date(se.starts_at) between s.start_date and s.end_date) used,
			coalesce((select sum(f.days) from freezes f where f.subscription_id = s.id and f.status = 'approved'), 0) frozen_days
		from subscriptions s join plans pl on pl.id = s.plan_id where s.participant_id = ? order by s.id desc", [$id]);
	$sub_now = $subs ? $subs[0] : null;
	$payments = db::all("select py.*, u.name entered_name from payments py left join users u on u.id = py.entered_by where py.participant_id = ? order by py.paid_at desc, py.id desc", [$id]);
	$debt = fun::participant_debt($id);

	// қатысу
	$att = db::all("select a.status, s.starts_at, s.type, g.name group_name from attendance a join sessions s on s.id = a.session_id left join `groups` g on g.id = s.group_id where a.participant_id = ? order by s.starts_at desc limit 60", [$id]);
	$att_c = ['present' => 0, 'absent' => 0, 'excused' => 0, 'frozen' => 0];
	foreach (db::all("select status, count(*) c from attendance where participant_id = ? group by status", [$id]) as $r) $att_c[$r['status']] = (int) $r['c'];
	$att_all = array_sum($att_c);
	$att_pct = $att_all ? round($att_c['present'] * 100 / $att_all) : 0;

	// рейтинг
	$r_all = (int) db::val("select coalesce(sum(points), 0) from rating_events where participant_id = ?", [$id]);
	$r_month = (int) db::val("select coalesce(sum(points), 0) from rating_events where participant_id = ? and created_at >= ?", [$id, date('Y-m-01')]);
	$r_place = (int) db::val("select count(*) + 1 from (select participant_id, sum(points) s from rating_events where organization_id = ? and scope = ? and created_at >= ? group by participant_id) x where x.s > ?", [$org_id, $p['direction'], date('Y-m-01'), $r_month]);
	$r_events = db::all("select * from rating_events where participant_id = ? order by created_at desc, id desc limit 20", [$id]);

	// прогресс, марапат, матч, медиа
	$assess = db::all("select pa.*, u.name coach_name, (select round(avg(sc.score), 1) from assessment_scores sc where sc.assessment_id = pa.id) avg_score from player_assessments pa left join users u on u.id = pa.coach_id where pa.participant_id = ? order by pa.assessed_at desc", [$id]);
	$achs = db::all("select pa.*, a.name, a.icon from participant_achievements pa join achievements a on a.id = pa.achievement_id where pa.participant_id = ? order by pa.awarded_at desc", [$id]);
	$mstat = db::row("select count(*) games, coalesce(sum(goals), 0) goals, coalesce(sum(assists), 0) assists, coalesce(sum(yellow), 0) yellow, coalesce(sum(red), 0) red, coalesce(sum(is_starter), 0) starts from match_player_stats where participant_id = ?", [$id]);
	$mvp = (int) db::val("select count(*) from matches where mvp_participant_id = ?", [$id]);
	$vis = $staff ? "1=1" : "m.visibility in ('tagged', 'parents', 'public')";
	$media = db::all("select m.* from media_assets m join media_asset_participants mp on mp.media_asset_id = m.id where mp.participant_id = ? and $vis order by m.created_at desc limit 24", [$id]);
	$consent = db::all("select * from media_consents where participant_id = ? and revoked_at is null", [$id]);

	// өзгерістер тарихы
	$audit = $fin ? db::all("select l.*, u.name user_name from audit_logs l left join users u on u.id = l.user_id where l.entity = 'participants' and l.entity_id = ? order by l.id desc limit 30", [$id]) : [];

	// топтар (ауыстыру үшін)
	$groups_all = $fin ? db::all("select g.*, u.name coach_name from `groups` g left join users u on u.id = g.coach_id where g.organization_id = ? and g.active = 1 and g.direction = ? order by g.name", [$org_id, $p['direction']]) : [];

	$rel = ['mother' => 'Анасы', 'father' => 'Әкесі', 'other' => 'Ата-анасы'];
	$audit_act = ['create' => 'Тіркелді', 'update' => 'Өңделді', 'set_group' => 'Топ ауысты', 'archive' => 'Архивке жіберілді', 'restore' => 'Қалпына келтірілді', 'img' => 'Фото өзгерді', 'guardian_add' => 'Ата-ана қосылды'];


	// site setting
	$menu_name = 'participants';
	$menu_title = fun::full_name($p);
	$site_set['utop_bk'] = '/participants/';
	$js = ['participants'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<? if ($p['archived_at']): ?>
			<div class="card" style="margin-top:16px;background:var(--cm1);box-shadow:none"><b>Бұл қатысушы архивте.</b> Барлық тарихы сақталған.
				<? if ($fin): ?> <span class="bdg bdg_dk part_restore" data-id="<?=$id?>" style="cursor:pointer">Қалпына келтіру</span><? endif ?>
			</div>
		<? endif ?>

		<div class="prf" style="margin-top:16px">

			<!-- сол жақ -->
			<div class="prf_l">
				<div class="prf_card">
					<? if ($p['number']): ?><div class="prf_num"><?=(int)$p['number']?></div><? endif ?>
					<div class="prf_img" <?=($p['img']?'style="background-image:url(/assets/uploads/participants/'.h($p['img']).')"':'')?>>
						<? if (!$p['img']): ?><span class="prf_img_l"><?=h(mb_substr($p['first_name'],0,1).mb_substr($p['last_name'],0,1))?></span><? endif ?>
						<? if ($fin): ?>
							<div class="prf_img_e"><i class="fas fa-camera"></i></div>
							<input type="file" class="prf_file dsp_n" accept="image/jpeg,image/png,image/webp" data-id="<?=$id?>">
						<? endif ?>
					</div>
					<div class="prf_name"><?=h(fun::full_name($p))?></div>
					<div class="prf_sub"><?=$age?> жас · <?=fun::date_short($p['birth_date'])?></div>
					<div class="prf_bdg">
						<span class="bdg bdg_g"><?=t::w($p['direction'])?></span>
						<? if ($in_first): ?><span class="bdg bdg_g">FIRST TEAM</span><? endif ?>
						<? if ($p['position']): ?><span class="bdg"><?=t::w($p['position'])?></span><? endif ?>
						<? if ($p['foot']): ?><span class="bdg"><?=t::w($p['foot'])?></span><? endif ?>
						<span class="bdg"><?=($p['status']=='left'?t::w('left_st'):t::w($p['status']))?></span>
					</div>
					<div class="prf_st">
						<div><b><?=$r_all?></b><span>ұпай</span></div>
						<div><b><?=$att_pct?>%</b><span>қатысу</span></div>
						<div><b><?=$r_month ? '#'.$r_place : '—'?></b><span>ай рейтингі</span></div>
					</div>
				</div>

				<? if ($fin): ?>
					<div class="card">
						<div class="form_btns" style="margin:0;flex-wrap:wrap">
							<div class="btn btn_sm btn_dk" data-pop="part_edit"><i class="fal fa-pen"></i><span>Өңдеу</span></div>
							<div class="btn btn_sm btn_ghost" data-pop="part_group"><i class="fal fa-exchange"></i><span>Топ ауыстыру</span></div>
						</div>
					</div>
				<? endif ?>
			</div>

			<!-- оң жақ -->
			<div>
				<div class="tab">
					<div class="tab_i tab_i_act" data-tab="main">Негізгі</div>
					<div class="tab_i" data-tab="payments">Абонемент және төлем</div>
					<div class="tab_i" data-tab="attendance">Қатысу</div>
					<div class="tab_i" data-tab="rating">Рейтинг</div>
					<div class="tab_i" data-tab="progress">Прогресс</div>
					<div class="tab_i" data-tab="awards">Марапаттар</div>
					<div class="tab_i" data-tab="matches">Матч статистикасы</div>
					<div class="tab_i" data-tab="media">Медиа</div>
					<div class="tab_i" data-tab="history">Тарих</div>
				</div>

				<!-- негізгі -->
				<div class="tab_c tab_c_act" data-tab="main">
					<div class="card">
						<div class="card_h"><h6>Жеке мәлімет</h6></div>
						<div class="inf">
							<div class="inf_i"><span>Аты-жөні</span><b><?=h(trim($p['last_name'].' '.$p['first_name'].' '.$p['middle_name']))?></b></div>
							<div class="inf_i"><span>Туған күні / жасы</span><b><?=fun::date_kz($p['birth_date'])?> · <?=$age?> жас</b></div>
							<div class="inf_i"><span>Телефон</span><b><?=h($p['phone'] ? fun::phone_view($p['phone']) : '—')?></b></div>
							<div class="inf_i"><span>Тобы</span><b><?=h($group ? $group['name'] : '—')?></b></div>
							<div class="inf_i"><span>Бапкері</span><b><?=h($group ? trim($group['coach_name'].' '.$group['coach_surname']) : '—')?></b></div>
							<div class="inf_i"><span>Академияға тіркелген</span><b><?=fun::date_kz($p['joined_at'])?></b></div>
							<div class="inf_i"><span>Позиция</span><b><?=($p['position']?t::w($p['position']):'—')?></b></div>
							<div class="inf_i"><span>Жетекші аяғы</span><b><?=($p['foot']?t::w($p['foot']):'—')?></b></div>
							<div class="inf_i"><span>Ойын нөмірі</span><b><?=($p['number']?'#'.(int)$p['number']:'—')?></b></div>
							<div class="inf_i"><span>Абонемент</span><b><?=($sub_now?t::w('sub_'.$sub_now['status']).($sub_now['end_date']?' · '.fun::date_short($sub_now['end_date']).' дейін':''):'—')?></b></div>
						</div>
						<? if ($p['notes'] && $staff): ?><div class="inf_i" style="border:none"><span>Ескерту</span><b style="font-weight:600"><?=nl2br(h($p['notes']))?></b></div><? endif ?>
					</div>

					<div class="card" style="margin-top:16px">
						<div class="card_h"><h6>Ата-анасының байланысы</h6><? if ($fin): ?><a href="#" data-pop="part_guard" onclick="return false">+ Қосу</a><? endif ?></div>
						<? foreach ($guardians as $gd): ?>
							<div class="li">
								<div class="li_i"><i class="fal fa-user-friends"></i></div>
								<div class="li_c"><b><?=h($gd['name'])?></b><span><?=$rel[$gd['relation']]?><?=($gd['is_primary']?' · негізгі':'')?><?=($gd['user_id']?' · кабинеті бар':'')?></span></div>
								<a class="li_r" href="tel:+7<?=h(substr($gd['phone'], 1))?>"><?=h(fun::phone_view($gd['phone']))?></a>
							</div>
						<? endforeach ?>
						<? if (!$guardians): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-user-friends"></i><p>Ата-ана байланысы жоқ</p></div><? endif ?>
					</div>
				</div>

				<!-- абонемент және төлем -->
				<div class="tab_c" data-tab="payments">
					<div class="dsh_k" style="grid-template-columns:repeat(3,1fr)">
						<div class="card dsh_ki"><div class="dsh_ki_t">Қарыз</div><div class="dsh_ki_n <?=($debt>0?'cl_err':'cl_ok')?>"><?=fun::money($debt)?></div></div>
						<div class="card dsh_ki"><div class="dsh_ki_t">Жаттығу (қалды)</div><div class="dsh_ki_n"><?=($sub_now && $sub_now['sessions_limit'] ? max(0, $sub_now['sessions_limit'] - $sub_now['used']).'/'.$sub_now['sessions_limit'] : '∞')?></div></div>
						<div class="card dsh_ki"><div class="dsh_ki_t">Заморозка (қалды)</div><div class="dsh_ki_n"><?=($sub_now ? max(0, $sub_now['freeze_days_limit'] - $sub_now['frozen_days']) : 0)?> күн</div></div>
					</div>
					<div class="card" style="margin-top:16px">
						<div class="card_h"><h6>Абонементтер</h6></div>
						<? if ($subs): ?>
							<div class="tbl_w"><table class="tbl">
								<tr><th>Тариф</th><th>Мерзімі</th><th>Бағасы</th><th>Төленді</th><th>Мәртебе</th></tr>
								<? foreach ($subs as $s): ?>
									<tr>
										<td><?=h($s['plan_name'])?></td>
										<td><?=($s['start_date'] ? fun::date_short($s['start_date']).' – '.fun::date_short($s['end_date']) : 'төлемнен кейін басталады')?></td>
										<td><?=fun::money($s['price'] - $s['discount'])?></td>
										<td class="<?=($s['paid'] < $s['price'] - $s['discount'] ? 'cl_err' : 'cl_ok')?>"><?=fun::money($s['paid'])?></td>
										<td><span class="bdg <?=($s['status']=='active'?'bdg_ok':($s['status']=='pending'?'bdg_warn':''))?>"><?=t::w('sub_'.$s['status'])?></span></td>
									</tr>
								<? endforeach ?>
							</table></div>
						<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-id-card"></i><p>Абонемент жоқ</p></div><? endif ?>
					</div>
					<div class="card" style="margin-top:16px">
						<div class="card_h"><h6>Төлем тарихы</h6></div>
						<? if ($payments): ?>
							<div class="tbl_w"><table class="tbl">
								<tr><th>Күні</th><th>Сома</th><th>Әдісі</th><th>Мәртебе</th><? if ($fin): ?><th>Енгізген</th><? endif ?></tr>
								<? foreach ($payments as $py): ?>
									<tr>
										<td><?=date('d.m.Y H:i', strtotime($py['paid_at']))?></td>
										<td class="<?=($py['amount']<0?'cl_err':'')?>"><?=fun::money($py['amount'])?><?=($py['kind']!='payment'?' <span class="bdg">'.h($py['kind']).'</span>':'')?></td>
										<td><?=t::w($py['method'])?><?=($py['external_ref'] && $fin?'<br><small>'.h($py['external_ref']).'</small>':'')?></td>
										<td><span class="bdg <?=($py['status']=='confirmed'?'bdg_ok':($py['status']=='pending'?'bdg_warn':'bdg_err'))?>"><?=t::w('pay_'.$py['status'])?></span></td>
										<? if ($fin): ?><td><?=h($py['entered_name'])?><br><small><?=date('d.m H:i', strtotime($py['entered_at']))?></small></td><? endif ?>
									</tr>
								<? endforeach ?>
							</table></div>
						<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-wallet"></i><p>Төлем жоқ</p></div><? endif ?>
					</div>
				</div>

				<!-- қатысу -->
				<div class="tab_c" data-tab="attendance">
					<div class="card">
						<div class="card_h"><h6>Қатысу тарихы</h6><span class="bdg bdg_ok"><?=$att_pct?>%</span></div>
						<div class="bar_lg" style="margin:0 0 12px">
							<span style="--c:var(--ok)">Келді <?=$att_c['present']?></span>
							<span style="--c:var(--warn)">Себепті <?=$att_c['excused']?></span>
							<span style="--c:#5b8def">Заморозка <?=$att_c['frozen']?></span>
							<span style="--c:var(--err)">Келмеді <?=$att_c['absent']?></span>
						</div>
						<? foreach ($att as $a): ?>
							<div class="li">
								<div class="li_i"><?=date('d.m', strtotime($a['starts_at']))?></div>
								<div class="li_c"><b><?=h($a['group_name'] ?: 'Жаттығу')?></b><span><?=fun::$weekdays[(int) date('N', strtotime($a['starts_at']))]?> · <?=date('H:i', strtotime($a['starts_at']))?></span></div>
								<div class="li_r"><span class="bdg <?=($a['status']=='present'?'bdg_ok':($a['status']=='absent'?'bdg_err':'bdg_warn'))?>"><?=t::w($a['status'])?></span></div>
							</div>
						<? endforeach ?>
						<? if (!$att): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-clipboard"></i><p>Қатысу белгіленбеген</p></div><? endif ?>
					</div>
				</div>

				<!-- рейтинг -->
				<div class="tab_c" data-tab="rating">
					<div class="dsh_k" style="grid-template-columns:repeat(3,1fr)">
						<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t">All-Time</div><div class="dsh_ki_n"><?=$r_all?></div></div>
						<div class="card dsh_ki"><div class="dsh_ki_t">Осы ай</div><div class="dsh_ki_n"><?=$r_month?></div></div>
						<div class="card dsh_ki"><div class="dsh_ki_t"><?=t::w($p['direction'])?> орны</div><div class="dsh_ki_n"><?=($r_month?'#'.$r_place:'—')?></div></div>
					</div>
					<div class="card" style="margin-top:16px">
						<div class="card_h"><h6>Ұпай тарихы</h6></div>
						<? $rule = ['attendance' => 'Жаттығуға қатысу', 'streak5' => 'Қатарынан 5 жаттығу', 'streak10' => 'Қатарынан 10 жаттығу', 'month100' => 'Ай бойы 100%', 'coach_award' => 'Бапкер марапаты', 'progress' => 'Жеке прогресс', 'manual' => 'Түзету']; ?>
						<? foreach ($r_events as $r): ?>
							<div class="li">
								<div class="li_i"><i class="fal fa-trophy"></i></div>
								<div class="li_c"><b><?=$rule[$r['rule']]?></b><span><?=date('d.m.Y', strtotime($r['created_at']))?><?=($r['comment']?' · '.h($r['comment']):'')?></span></div>
								<div class="li_r <?=($r['points']<0?'cl_err':'cl_ok')?>"><?=($r['points']>0?'+':'').$r['points']?></div>
							</div>
						<? endforeach ?>
						<? if (!$r_events): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-trophy"></i><p>Әзірге ұпай жоқ</p></div><? endif ?>
					</div>
				</div>

				<!-- прогресс -->
				<div class="tab_c" data-tab="progress">
					<div class="card">
						<div class="card_h"><h6>Бапкер бағалаулары</h6></div>
						<? foreach ($assess as $a): ?>
							<div class="li">
								<div class="li_i"><?=h($a['avg_score'])?></div>
								<div class="li_c"><b><?=fun::date_kz($a['assessed_at'])?></b><span><?=h($a['coach_name'])?><?=($a['comment']?' · '.h(mb_substr($a['comment'],0,80)):'')?></span></div>
							</div>
						<? endforeach ?>
						<? if (!$assess): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-chart-line"></i><p>Бағалау әлі жоқ. Прогресс графиктері 2-кезеңде.</p></div><? endif ?>
					</div>
				</div>

				<!-- марапаттар -->
				<div class="tab_c" data-tab="awards">
					<div class="card">
						<div class="card_h"><h6>Марапаттар</h6></div>
						<? foreach ($achs as $a): ?>
							<div class="li">
								<div class="li_i" style="background:var(--cm1);color:#7a5600"><i class="fal <?=h($a['icon'])?>"></i></div>
								<div class="li_c"><b><?=h($a['name'])?></b><span><?=fun::date_short($a['awarded_at'])?><?=($a['comment']?' · '.h($a['comment']):'')?></span></div>
							</div>
						<? endforeach ?>
						<? if (!$achs): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-medal"></i><p>Марапат әлі жоқ</p></div><? endif ?>
					</div>
				</div>

				<!-- матч -->
				<div class="tab_c" data-tab="matches">
					<div class="card">
						<div class="card_h"><h6>Футболшы статистикасы</h6><? if ($in_first): ?><span class="bdg bdg_dk">FIRST TEAM</span><? endif ?></div>
						<? if ($mstat['games']): ?>
							<div class="dsh_fin">
								<div class="dsh_fin_i"><span>Матч</span><b><?=$mstat['games']?></b></div>
								<div class="dsh_fin_i"><span>Гол</span><b><?=$mstat['goals']?></b></div>
								<div class="dsh_fin_i"><span>Ассист</span><b><?=$mstat['assists']?></b></div>
								<div class="dsh_fin_i"><span>Старт</span><b><?=$mstat['starts']?></b></div>
								<div class="dsh_fin_i"><span>Сары / қызыл</span><b><?=$mstat['yellow']?> / <?=$mstat['red']?></b></div>
								<div class="dsh_fin_i"><span>Матч үздігі</span><b><?=$mvp?></b></div>
							</div>
						<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Матч статистикасы жоқ</p></div><? endif ?>
					</div>
				</div>

				<!-- медиа -->
				<div class="tab_c" data-tab="media">
					<div class="card">
						<div class="card_h"><h6>Фото-видео</h6>
							<? if ($p['direction'] == 'kids' && $staff): ?><span class="bdg <?=($consent?'bdg_ok':'bdg_err')?>"><?=($consent?'Келісім бар':'Медиа келісімі жоқ')?></span><? endif ?>
						</div>
						<? if ($media): ?>
							<div class="dsh_k">
								<? foreach ($media as $m): ?><div class="card dsh_ki"><i class="fal fa-<?=($m['type']=='video'?'video':'image')?>"></i> <?=h($m['title'])?></div><? endforeach ?>
							</div>
						<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-images"></i><p>Медиа жоқ. ZHASS MEDIA 3-кезеңде.</p></div><? endif ?>
					</div>
				</div>

				<!-- тарих -->
				<div class="tab_c" data-tab="history">
					<div class="card">
						<div class="card_h"><h6>Топ / құрам тарихы</h6></div>
						<? foreach ($enrolls as $e): ?>
							<div class="li">
								<div class="li_i"><i class="fal fa-users-class"></i></div>
								<div class="li_c"><b><?=h($e['group_name'])?></b><span><?=fun::date_short($e['start_date'])?> – <?=($e['end_date']?fun::date_short($e['end_date']):'қазір')?><?=($e['reason']?' · '.h($e['reason']):'')?></span></div>
								<? if (!$e['end_date']): ?><div class="li_r"><span class="bdg bdg_ok">қазіргі</span></div><? endif ?>
							</div>
						<? endforeach ?>
						<? foreach ($rosters as $r): ?>
							<div class="li">
								<div class="li_i" style="background:var(--dk);color:var(--gold)"><i class="fal fa-futbol"></i></div>
								<div class="li_c"><b><?=h($r['team_name'])?></b><span><?=fun::date_short($r['joined_at'])?> – <?=($r['left_at']?fun::date_short($r['left_at']):'қазір')?></span></div>
							</div>
						<? endforeach ?>
						<? if (!$enrolls && !$rosters): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-history"></i><p>Тарих жоқ</p></div><? endif ?>
					</div>
					<? if ($fin): ?>
						<div class="card" style="margin-top:16px">
							<div class="card_h"><h6>Өзгерістер журналы (audit)</h6></div>
							<? foreach ($audit as $l): ?>
								<div class="li">
									<div class="li_i"><i class="fal fa-pen"></i></div>
									<div class="li_c"><b><?=h(isset($audit_act[$l['action']]) ? $audit_act[$l['action']] : $l['action'])?></b><span><?=h($l['user_name'] ?: 'жүйе')?> · <?=date('d.m.Y H:i', strtotime($l['created_at']))?></span></div>
								</div>
							<? endforeach ?>
							<? if (!$audit): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-history"></i><p>Жазба жоқ</p></div><? endif ?>
						</div>
					<? endif ?>
				</div>
			</div>
		</div>

		<div style="height:40px"></div>
	</div>

	<? if ($fin): ?>
		<? $fp = $p; include "form.php"; ?>

		<!-- топ ауыстыру -->
		<div class="pop_bl part_group">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Топ ауыстыру</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<p style="font-size:14px;color:var(--bl5);margin-bottom:14px">Қазіргі тобы: <b><?=h($group ? $group['name'] : '—')?></b>. Ескі топтағы тарихы сақталады.</p>
				<div class="form_g">
					<div class="form_im form_im_f"><label class="form_l">Жаңа топ</label>
						<select class="form_im_txt sg_group">
							<option value="">Топсыз</option>
							<? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>" <?=($group && $group['id']==$g['id']?'selected':'')?>><?=h($g['name'])?> · <?=h($g['coach_name'])?></option><? endforeach ?>
						</select>
					</div>
					<div class="form_im form_im_f"><label class="form_l">Себебі</label><input type="text" class="form_im_txt sg_reason" maxlength="255" placeholder="Мысалы: деңгейі өсті"></div>
				</div>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn btn_set_group" data-id="<?=$id?>">Ауыстыру</div></div>
			</div>
		</div>

		<!-- ата-ана қосу -->
		<div class="pop_bl part_guard">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Ата-ана қосу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<div class="form_g">
					<div class="form_im form_im_f"><label class="form_l">Аты-жөні</label><input type="text" class="form_im_txt ga_name" maxlength="120"></div>
					<div class="form_im"><label class="form_l">Телефоны</label><input type="tel" class="form_im_txt fr_phone ga_phone" placeholder="8 (700) 000-00-00"></div>
					<div class="form_im"><label class="form_l">Кім болады</label><select class="form_im_txt ga_rel"><option value="mother">Анасы</option><option value="father">Әкесі</option><option value="other">Басқа</option></select></div>
				</div>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn btn_guard_add" data-id="<?=$id?>">Қосу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
