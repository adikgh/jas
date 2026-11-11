<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach', 'participant']);
	$staff = core::is(['owner', 'admin', 'coach']);

	$team = db::row("select * from teams where organization_id = ? and type = 'first_team' order by id limit 1", [$org_id]);
	$tid = $team ? (int) $team['id'] : 0;

	$roster = db::all("select r.*, p.first_name, p.last_name, p.img, p.position p_position, p.number p_number,
			(select count(*) from match_player_stats s join matches m on m.id = s.match_id where s.participant_id = p.id and m.team_id = r.team_id and m.status = 'finished') games,
			(select coalesce(sum(s.goals), 0) from match_player_stats s join matches m on m.id = s.match_id where s.participant_id = p.id and m.team_id = r.team_id and m.status = 'finished') goals,
			(select coalesce(sum(s.assists), 0) from match_player_stats s join matches m on m.id = s.match_id where s.participant_id = p.id and m.team_id = r.team_id and m.status = 'finished') assists,
			(select coalesce(sum(s.yellow), 0) from match_player_stats s join matches m on m.id = s.match_id where s.participant_id = p.id and m.team_id = r.team_id and m.status = 'finished') yellow,
			(select coalesce(sum(s.red), 0) from match_player_stats s join matches m on m.id = s.match_id where s.participant_id = p.id and m.team_id = r.team_id and m.status = 'finished') red,
			(select count(*) from matches m where m.mvp_participant_id = p.id and m.team_id = r.team_id) mvp
		from team_rosters r join participants p on p.id = r.participant_id
		where r.team_id = ? and r.left_at is null order by field(coalesce(r.position, p.position), 'gk', 'df', 'mf', 'fw'), coalesce(r.number, p.number), p.last_name", [$tid]);

	$matches = db::all("select m.*, o.name opp, c.name comp_name from matches m join opponents o on o.id = m.opponent_id left join competitions c on c.id = m.competition_id
		where m.team_id = ? order by m.match_at desc", [$tid]);
	$st = ['w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0];
	$next = null; $form = [];
	foreach ($matches as $m) {
		if ($m['status'] == 'finished') {
			$k = $m['goals_for'] > $m['goals_against'] ? 'w' : ($m['goals_for'] == $m['goals_against'] ? 'd' : 'l');
			$st[$k]++; $st['gf'] += $m['goals_for']; $st['ga'] += $m['goals_against'];
			if (count($form) < 5) $form[] = $k;
		}
		if (in_array($m['status'], ['scheduled', 'live']) && $m['match_at'] >= date('Y-m-d 00:00:00')) $next = $m;   // desc болғандықтан соңғысы — ең жақыны
	}
	$pts = $st['w'] * 3 + $st['d'];
	$scorers = array_filter($roster, function ($r) { return $r['goals'] + $r['assists'] > 0; });
	usort($scorers, function ($a, $b) { return $b['goals'] - $a['goals'] ?: $b['assists'] - $a['assists']; });
	$scorers = array_slice($scorers, 0, 5);

	$cands = $staff ? db::all("select p.id, p.first_name, p.last_name from participants p where p.organization_id = ? and p.direction = 'adult' and p.archived_at is null and p.status = 'active'
		and not exists (select 1 from team_rosters r where r.participant_id = p.id and r.team_id = ? and r.left_at is null) order by p.last_name", [$org_id, $tid]) : [];
	$comps = db::all("select * from competitions where organization_id = ? order by season desc, name", [$org_id]);
	$opps = db::all("select * from opponents where organization_id = ? order by name", [$org_id]);
	$fl = ['w' => ['Ж', 'bdg_ok'], 'd' => ['Т', 'bdg_warn'], 'l' => ['Ұ', 'bdg_err']];


	// site setting
	$menu_name = 'first_team';
	$menu_title = 'First Team & ЛЛФ';
	$css = ['rating', 'first_team'];
	$js = ['first_team'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4><?=h($team ? $team['name'] : 'First Team')?></h4><p>JAS FIRST TEAM · ЛЛФ · матчтар абонемент лимитін азайтпайды</p></div>
			<? if ($staff && $team): ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<div class="btn btn_sm btn_ghost" data-pop="ros_add"><i class="fal fa-user-plus"></i><span>Құрамға қосу</span></div>
					<div class="btn btn_sm" data-pop="match_add"><i class="fal fa-plus"></i><span>Матч</span></div>
				</div>
			<? endif ?>
		</div>

		<? if (!$team): ?>
			<div class="card"><div class="ds_nr"><i class="fal fa-futbol"></i><p>Бірінші құрам әлі құрылмаған</p><? if (core::is(['owner', 'admin'])): ?><div class="btn btn_sm ft_team" style="margin-top:12px">JAS FIRST TEAM құру</div><? endif ?></div></div>
		<? else: ?>

		<div class="dsh_k">
			<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t"><i class="fal fa-trophy"></i>Ұпай</div><div class="dsh_ki_n"><?=$pts?></div><div class="dsh_ki_s"><?=$st['w']?> Ж · <?=$st['d']?> Т · <?=$st['l']?> Ұ</div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-futbol"></i>Голдар</div><div class="dsh_ki_n"><?=$st['gf']?>:<?=$st['ga']?></div><div class="dsh_ki_s">айырма <?=($st['gf']-$st['ga']>0?'+':'').($st['gf']-$st['ga'])?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-users"></i>Құрам</div><div class="dsh_ki_n"><?=count($roster)?></div><div class="dsh_ki_s">футболшы</div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-chart-line"></i>Соңғы формасы</div><div class="dsh_ki_n" style="display:flex;gap:4px"><? foreach ($form as $f): ?><span class="bdg <?=$fl[$f][1]?>"><?=$fl[$f][0]?></span><? endforeach ?><?=(!$form?'—':'')?></div></div>
		</div>

		<? if ($next): ?>
			<a class="card ft_next" href="/first_team/match.php?id=<?=$next['id']?>">
				<div><span class="bdg bdg_warn">Келесі матч</span> <small><?=h($next['comp_name'])?> · <?=h($next['round'])?></small></div>
				<div class="ft_vs"><b>JAS</b><em><?=date('H:i', strtotime($next['match_at']))?></em><b><?=h($next['opp'])?></b></div>
				<div><small><?=fun::date_kz(substr($next['match_at'], 0, 10))?> · <?=h($next['venue'])?> · <?=($next['is_home']?'үйде':'қонақта')?></small></div>
			</a>
		<? endif ?>

		<div class="dsh_g" style="margin-top:16px">
			<div class="card">
				<div class="card_h"><h6>Матчтар</h6><span class="bdg"><?=count($matches)?></span></div>
				<? foreach ($matches as $m): $k = $m['status'] == 'finished' ? ($m['goals_for'] > $m['goals_against'] ? 'w' : ($m['goals_for'] == $m['goals_against'] ? 'd' : 'l')) : ''; ?>
					<a class="li" href="/first_team/match.php?id=<?=$m['id']?>" style="<?=($m['status']=='cancelled'?'opacity:.5':'')?>">
						<div class="li_i"><?=date('d.m', strtotime($m['match_at']))?></div>
						<div class="li_c"><b>JAS — <?=h($m['opp'])?></b><span><?=h($m['comp_name'])?> · <?=h($m['round'])?> · <?=($m['is_home']?'үйде':'қонақта')?></span></div>
						<div class="li_r">
							<? if ($m['status'] == 'finished'): ?><span class="bdg <?=$fl[$k][1]?>"><?=(int) $m['goals_for']?>:<?=(int) $m['goals_against']?></span>
							<? elseif ($m['status'] == 'cancelled'): ?><span class="bdg">болмады</span>
							<? else: ?><span class="bdg"><?=date('H:i', strtotime($m['match_at']))?></span><? endif ?>
						</div>
					</a>
				<? endforeach ?>
				<? if (!$matches): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Матч жоқ</p></div><? endif ?>
			</div>
			<div class="card">
				<div class="card_h"><h6>Бомбардирлер</h6></div>
				<? foreach (array_values($scorers) as $i => $r): ?>
					<a class="li" href="/participants/item.php?id=<?=$r['participant_id']?>#matches">
						<div class="rt_pl rt_pl<?=min(4, $i + 1)?>"><?=$i + 1?></div>
						<div class="li_c"><b><?=h($r['last_name'].' '.$r['first_name'])?></b><span><?=$r['games']?> матч · <?=$r['assists']?> пас</span></div>
						<div class="li_r"><b><?=$r['goals']?></b> <i class="fal fa-futbol"></i></div>
					</a>
				<? endforeach ?>
				<? if (!$scorers): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Әзірге гол жоқ</p></div><? endif ?>
			</div>
		</div>

		<div class="card" style="margin-top:16px">
			<div class="card_h"><h6>Құрам</h6><span class="bdg"><?=count($roster)?></span></div>
			<? if ($roster): ?>
				<div class="tbl_w"><table class="tbl">
					<tr><th>#</th><th>Футболшы</th><th>Позиция</th><th title="Матч">М</th><th title="Гол">Г</th><th title="Пас">П</th><th title="Сары">🟨</th><th title="Қызыл">🟥</th><th>MVP</th><? if ($staff): ?><th></th><? endif ?></tr>
					<? foreach ($roster as $r): $pos = $r['position'] ?: $r['p_position']; ?>
						<tr>
							<td><b><?=h($r['number'] ?: $r['p_number'] ?: '—')?></b></td>
							<td><a href="/participants/item.php?id=<?=$r['participant_id']?>#matches"><b><?=h($r['last_name'].' '.$r['first_name'])?></b></a></td>
							<td><?=($pos?t::w($pos):'—')?></td>
							<td><?=$r['games']?></td><td><b><?=$r['goals']?></b></td><td><?=$r['assists']?></td><td><?=$r['yellow']?></td><td><?=$r['red']?></td><td><?=($r['mvp']?'⭐ '.$r['mvp']:'')?></td>
							<? if ($staff): ?><td><span class="bdg bdg_err ft_rm" data-id="<?=$r['id']?>" style="cursor:pointer" title="Құрамнан шығару"><i class="fal fa-user-minus"></i></span></td><? endif ?>
						</tr>
					<? endforeach ?>
				</table></div>
			<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-users"></i><p>Құрам бос</p></div><? endif ?>
		</div>
		<? endif ?>

		<div style="height:40px"></div>
	</div>

	<? if ($staff && $team): ?>
		<div class="pop_bl ros_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Құрамға қосу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g ra_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Футболшы (JAS ADULT) <em>*</em></label><select class="form_im_txt" name="participant_id"><option value="">Таңдаңыз</option><? foreach ($cands as $p): ?><option value="<?=$p['id']?>"><?=h($p['last_name'].' '.$p['first_name'])?></option><? endforeach ?></select></div>
					<div class="form_im"><label class="form_l">Нөмірі</label><input type="number" class="form_im_txt" name="number" min="1" max="99"></div>
					<div class="form_im"><label class="form_l">Позиция</label><select class="form_im_txt" name="position"><option value="">—</option><? foreach (['gk', 'df', 'mf', 'fw'] as $k): ?><option value="<?=$k?>"><?=t::w($k)?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><p class="form_e" style="color:#6b766f">Рейтинг құрамға автоматты қоспайды — шешімді бапкер қабылдайды.</p></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ra_save">Қосу</div></div>
			</div>
		</div>
		<div class="pop_bl match_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Жаңа матч</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g ma_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Турнир</label><select class="form_im_txt ma_comp" name="competition_id"><? foreach ($comps as $c): ?><option value="<?=$c['id']?>"><?=h($c['name'].' '.$c['season'])?></option><? endforeach ?><option value="new">+ Жаңа турнир</option></select></div>
					<div class="form_im form_im_f ma_comp_new" style="display:none"><input type="text" class="form_im_txt" name="competition_name" maxlength="120" placeholder="Турнир атауы, мысалы ЛЛФ Шымкент"></div>
					<div class="form_im form_im_f"><label class="form_l">Қарсылас <em>*</em></label><select class="form_im_txt ma_opp" name="opponent_id"><option value="">Таңдаңыз</option><? foreach ($opps as $o): ?><option value="<?=$o['id']?>"><?=h($o['name'])?></option><? endforeach ?><option value="new">+ Жаңа қарсылас</option></select></div>
					<div class="form_im form_im_f ma_opp_new" style="display:none"><input type="text" class="form_im_txt" name="opponent_name" maxlength="120" placeholder="Команда атауы"></div>
					<div class="form_im"><label class="form_l">Тур</label><input type="text" class="form_im_txt" name="round" maxlength="30" placeholder="6-тур"></div>
					<div class="form_im"><label class="form_l">Күні, уақыты <em>*</em></label><input type="datetime-local" class="form_im_txt" name="match_at"></div>
					<div class="form_im"><label class="form_l">Алаң</label><input type="text" class="form_im_txt" name="venue" maxlength="160" placeholder="JAS Arena"></div>
					<div class="form_im"><label class="form_l">Орны</label><select class="form_im_txt" name="is_home"><option value="1">Үйде</option><option value="0">Қонақта</option></select></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ma_save">Қосу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
