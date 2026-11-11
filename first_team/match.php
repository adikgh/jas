<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach', 'participant']);
	$staff = core::is(['owner', 'admin', 'coach']);

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$mt = db::row("select m.*, o.name opp, c.name comp_name, c.season, t.name team_name from matches m join opponents o on o.id = m.opponent_id join teams t on t.id = m.team_id left join competitions c on c.id = m.competition_id
		where m.id = ? and m.organization_id = ?", [$id, $org_id]);
	if (!$mt) { http_response_code(404); include "../block/403.php"; exit(); }

	// құрам (белсенді) + бұрын статистикасы жазылғандар
	$players = db::all("select p.id, p.first_name, p.last_name, coalesce(r.number, p.number) number, coalesce(r.position, p.position) position,
			s.id sid, s.is_starter, s.minute_in, s.minute_out, s.goals, s.assists, s.yellow, s.red
		from participants p
			left join team_rosters r on r.participant_id = p.id and r.team_id = ? and r.left_at is null
			left join match_player_stats s on s.participant_id = p.id and s.match_id = ?
		where p.organization_id = ? and (r.id is not null or s.id is not null)
		order by s.id is null, s.is_starter desc, field(coalesce(r.position, p.position), 'gk', 'df', 'mf', 'fw'), p.last_name", [(int) $mt['team_id'], $id, $org_id]);
	$played = array_filter($players, function ($p) { return $p['sid']; });
	$mvp = $mt['mvp_participant_id'] ? db::row("select first_name, last_name from participants where id = ?", [(int) $mt['mvp_participant_id']]) : null;
	$st_name = ['scheduled' => 'Жоспарда', 'live' => 'Жүріп жатыр', 'finished' => 'Аяқталды', 'cancelled' => 'Болмады'];
	$res = $mt['status'] == 'finished' ? ($mt['goals_for'] > $mt['goals_against'] ? ['Жеңіс', 'bdg_ok'] : ($mt['goals_for'] == $mt['goals_against'] ? ['Тең', 'bdg_warn'] : ['Ұтылыс', 'bdg_err'])) : null;


	// site setting
	$menu_name = 'first_team';
	$menu_title = 'JAS — '.$mt['opp'];
	$site_set['utop_bk'] = '/first_team/';
	$css = ['first_team'];
	$js = ['first_team'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="card ft_score">
			<div class="ft_score_t"><?=h($mt['comp_name'].' '.$mt['season'])?> · <?=h($mt['round'])?></div>
			<div class="ft_score_c">
				<div><div class="ft_logo">JAS</div><b><?=h($mt['team_name'])?></b></div>
				<div class="ft_score_n">
					<? if ($mt['status'] == 'finished' || $mt['status'] == 'live'): ?><?=(int) $mt['goals_for']?> : <?=(int) $mt['goals_against']?><? else: ?><?=date('H:i', strtotime($mt['match_at']))?><? endif ?>
					<span><?=$st_name[$mt['status']]?><?=($res?' · '.$res[0]:'')?></span>
				</div>
				<div><div class="ft_logo ft_logo_o"><?=h(mb_strtoupper(mb_substr($mt['opp'], 0, 2)))?></div><b><?=h($mt['opp'])?></b></div>
			</div>
			<div class="ft_score_t"><?=fun::date_kz(substr($mt['match_at'], 0, 10))?> · <?=h($mt['venue'])?> · <?=($mt['is_home']?'үйде':'қонақта')?><?=($mvp?' · ⭐ MVP: '.h($mvp['first_name'].' '.$mvp['last_name']):'')?></div>
		</div>

		<? if ($staff): ?>
			<div class="card ms_form" data-id="<?=$id?>" style="margin-top:16px">
				<div class="card_h"><h6>Нәтиже</h6></div>
				<div class="form_g">
					<div class="form_im"><label class="form_l">Мәртебе</label><select class="form_im_txt ms_st"><? foreach ($st_name as $k => $v): ?><option value="<?=$k?>" <?=($mt['status']==$k?'selected':'')?>><?=$v?></option><? endforeach ?></select></div>
					<div class="form_im"><label class="form_l">JAS голы</label><input type="number" class="form_im_txt ms_gf" min="0" max="50" value="<?=h($mt['goals_for'])?>"></div>
					<div class="form_im"><label class="form_l">Қарсылас голы</label><input type="number" class="form_im_txt ms_ga" min="0" max="50" value="<?=h($mt['goals_against'])?>"></div>
					<div class="form_im"><label class="form_l">MVP</label><select class="form_im_txt ms_mvp"><option value="">—</option><? foreach ($players as $p): ?><option value="<?=$p['id']?>" <?=($mt['mvp_participant_id']==$p['id']?'selected':'')?>><?=h($p['last_name'].' '.$p['first_name'])?></option><? endforeach ?></select></div>
				</div>
			</div>
		<? endif ?>

		<div class="card" style="margin-top:16px">
			<div class="card_h"><h6><?=($staff ? 'Құрам және футболшы статистикасы' : 'Ойнағандар')?></h6><span class="bdg"><?=count($played)?> ойнады</span></div>
			<? if ($staff): ?>
				<div class="tbl_w"><table class="tbl ms_tbl">
					<tr><th>Ойнады</th><th>Футболшы</th><th title="Негізгі құрам">Негізгі</th><th>Шықты, мин</th><th>Ауысты, мин</th><th>Гол</th><th>Пас</th><th>🟨</th><th>🟥</th></tr>
					<? foreach ($players as $p): ?>
						<tr class="ms_p" data-pid="<?=$p['id']?>">
							<td><input type="checkbox" class="ms_pl" <?=($p['sid']?'checked':'')?>></td>
							<td><b><?=h($p['number'] ? '#'.$p['number'].' ' : '')?><?=h($p['last_name'].' '.$p['first_name'])?></b><br><small><?=($p['position']?t::w($p['position']):'')?></small></td>
							<td><input type="checkbox" class="ms_v" data-k="is_starter" <?=($p['is_starter']?'checked':'')?>></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="minute_in" min="0" max="130" value="<?=h($p['minute_in'] ?? 0)?>"></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="minute_out" min="0" max="130" value="<?=h($p['minute_out'] ?? 90)?>"></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="goals" min="0" max="20" value="<?=(int) $p['goals']?>"></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="assists" min="0" max="20" value="<?=(int) $p['assists']?>"></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="yellow" min="0" max="2" value="<?=(int) $p['yellow']?>"></td>
							<td><input type="number" class="form_im_txt ms_v" data-k="red" min="0" max="1" value="<?=(int) $p['red']?>"></td>
						</tr>
					<? endforeach ?>
				</table></div>
				<div class="form_btns"><div class="btn ms_save"><i class="fal fa-save"></i><span>Сақтау</span></div></div>
			<? else: ?>
				<? foreach ($played as $p): ?>
					<div class="li">
						<div class="li_i"><?=h($p['number'] ?: '—')?></div>
						<div class="li_c"><b><?=h($p['last_name'].' '.$p['first_name'])?></b><span><?=($p['is_starter']?'негізгі':'ауыстыру')?> · <?=(int) $p['minute_in']?>–<?=(int) $p['minute_out']?> мин</span></div>
						<div class="li_r"><?=str_repeat('⚽', (int) $p['goals'])?><?=($p['assists']?' 🅰'.$p['assists']:'')?><?=str_repeat('🟨', (int) $p['yellow'])?><?=($p['red']?'🟥':'')?></div>
					</div>
				<? endforeach ?>
				<? if (!$played): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Құрам әлі белгіленбеген</p></div><? endif ?>
			<? endif ?>
		</div>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
