<? include "../config/core.php";
	include "lib.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);

	$id = (int) @$_GET['id'];
	$t = db::row("select * from tournaments where id = ? and organization_id = ?", [$id, $org_id]);
	if (!$t) { http_response_code(404); include "../block/403.php"; exit(); }

	$teams = db::all("select * from tournament_teams where tournament_id = ? order by place is null, place, name", [$id]);
	$tname = array_column($teams, 'name', 'id');
	$games = db::all("select * from tournament_games where tournament_id = ? order by id", [$id]);
	$awards = db::all("select * from tournament_awards where tournament_id = ? order by id", [$id]);
	$table = tour_table($id);
	$played = count(array_filter($games, function ($g) { return $g['status'] == 'finished'; }));
	$paid = count(array_filter($teams, function ($x) { return $x['paid']; }));
	$join = core::portal_url('', '/tournaments/join.php?c='.$t['join_code']);

	$players = [];
	if ($staff) $players = db::all("select id, first_name, last_name from participants where organization_id = ? and archived_at is null".($t['direction'] != 'mixed' ? " and direction = ?" : "")." order by last_name, first_name limit 400",
		$t['direction'] != 'mixed' ? [$org_id, $t['direction']] : [$org_id]);

	// Instagram қорытынды мәтіні
	$post = '';
	if ($staff && ($played || $awards)) {
		$medal = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
		$post = "🏆 ".mb_strtoupper($t['name'])."\n".fun::date_kz(substr($t['starts_at'], 0, 10)).($t['venue'] ? ' · '.$t['venue'] : '')."\n\n";
		$top = array_filter($teams, function ($x) { return $x['place'] && $x['place'] <= 3; });
		if ($top) { foreach ($top as $x) $post .= $medal[$x['place']].' '.$x['name']."\n"; $post .= "\n"; }
		elseif ($table && $played) { foreach (array_slice($table, 0, 3) as $i => $x) $post .= $medal[$i + 1].' '.$x['name'].' — '.$x['pts']." ұпай\n"; $post .= "\n"; }
		foreach ($awards as $a) $post .= '⭐ '.$a['title'].': '.$a['person']."\n";
		$post .= "\n".count($teams)." команда · ".$played." ойын\nҚатысқан командаларға рахмет! Келесі турнирге тіркелу — директ немесе WhatsApp 💙💛\n\n#zhassacademy #шымкент #футбол #турнир";
	}


	// site setting
	$menu_name = 'tournaments';
	$menu_title = $t['name'];
	$site_set['utop_bk'] = '/tournaments/';
	$css = ['tournaments'];
	$js = ['tournaments'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c tr_item" data-id="<?=$id?>">

		<div class="card tr_head">
			<div class="tr_card_t"><span class="bdg <?=$tour_kind[$t['kind']][1]?>"><?=$tour_kind[$t['kind']][0]?></span><span class="bdg <?=$tour_st[$t['status']][1]?>"><?=$tour_st[$t['status']][0]?></span><span class="bdg"><?=$tour_dir[$t['direction']]?></span></div>
			<h4><?=h($t['name'])?></h4>
			<div class="tr_head_m">
				<span><i class="fal fa-calendar"></i> <?=fun::date_kz(substr($t['starts_at'], 0, 10))?> · <?=substr($t['starts_at'], 11, 5)?></span>
				<? if ($t['venue']): ?><span><i class="fal fa-map-marker-alt"></i> <?=h($t['venue'])?></span><? endif ?>
				<span><i class="fal fa-users"></i> <?=count($teams)?><?=($t['max_teams']?'/'.$t['max_teams']:'')?> команда</span>
				<? if ($t['entry_fee'] > 0): ?><span><i class="fal fa-money-bill"></i> <?=fun::money($t['entry_fee'])?><?=($staff?' · төледі '.$paid.'/'.count($teams):'')?></span><? endif ?>
				<? if ($t['prize']): ?><span><i class="fal fa-medal"></i> <?=h($t['prize'])?></span><? endif ?>
			</div>
			<? if ($staff): ?>
				<div class="tr_head_s">
					<select class="form_im_txt tr_st"><? foreach ($tour_st as $k => $v): ?><option value="<?=$k?>" <?=($t['status']==$k?'selected':'')?>><?=$v[0]?></option><? endforeach ?></select>
					<? if ($t['status'] == 'registration'): ?>
						<input type="text" class="form_im_txt tr_link" readonly value="<?=h($join)?>">
						<div class="btn btn_sm tr_link_copy"><i class="fal fa-link"></i><span>Тіркелу сілтемесі</span></div>
					<? endif ?>
				</div>
			<? endif ?>
		</div>

		<div class="tr_grid">
			<div>
				<? if ($played): ?>
				<div class="card">
					<div class="card_h"><h6>Турнир кестесі</h6><span class="bdg"><?=$played?> ойын</span></div>
					<div class="tbl_w"><table class="tbl tr_tbl">
						<tr><th>#</th><th>Команда</th><th>О</th><th>Ж</th><th>Т</th><th>Ұ</th><th>Голдар</th><th>Ұпай</th></tr>
						<? foreach ($table as $i => $r): ?>
							<tr class="<?=($i < 3 ? 'tr_top' : '')?>"><td><?=$i + 1?></td><td><b><?=h($r['name'])?></b></td><td><?=$r['p']?></td><td><?=$r['w']?></td><td><?=$r['d']?></td><td><?=$r['l']?></td><td><?=$r['gf']?>:<?=$r['ga']?></td><td><b><?=$r['pts']?></b></td></tr>
						<? endforeach ?>
					</table></div>
				</div>
				<? endif ?>

				<div class="card" style="margin-top:16px">
					<div class="card_h"><h6>Ойындар</h6><span class="bdg"><?=count($games)?></span></div>
					<? foreach ($games as $g): ?>
						<div class="tr_game" data-g="<?=$g['id']?>">
							<span class="tr_game_s"><?=h($g['stage'] ?: '—')?></span>
							<b class="tr_game_h"><?=h(@$tname[$g['home_id']])?></b>
							<? if ($staff): ?>
								<span class="tr_game_sc"><input type="number" class="form_im_txt tr_hg" min="0" max="50" value="<?=h($g['home_goals'])?>"><i>:</i><input type="number" class="form_im_txt tr_ag" min="0" max="50" value="<?=h($g['away_goals'])?>"></span>
							<? else: ?>
								<span class="tr_game_sc"><?=($g['status'] == 'finished' ? (int) $g['home_goals'].' : '.(int) $g['away_goals'] : 'vs')?></span>
							<? endif ?>
							<b class="tr_game_a"><?=h(@$tname[$g['away_id']])?></b>
							<? if ($staff): ?><span class="tr_game_b"><i class="fal fa-check tr_g_save" title="Сақтау"></i><i class="fal fa-trash tr_g_rm" title="Өшіру"></i></span><? endif ?>
						</div>
					<? endforeach ?>
					<? if (!$games): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-futbol"></i><p>Ойын кестесі әлі жоқ</p></div><? endif ?>
					<? if ($staff && count($teams) >= 2): ?>
						<div class="tr_form_r tr_gform">
							<select class="form_im_txt" name="home_id"><? foreach ($teams as $x): ?><option value="<?=$x['id']?>"><?=h($x['name'])?></option><? endforeach ?></select>
							<select class="form_im_txt" name="away_id"><? foreach ($teams as $i => $x): ?><option value="<?=$x['id']?>" <?=($i==1?'selected':'')?>><?=h($x['name'])?></option><? endforeach ?></select>
							<input type="text" class="form_im_txt" name="stage" maxlength="40" placeholder="Кезең: А тобы, 1/2, финал">
							<div class="btn btn_sm tr_g_add"><i class="fal fa-plus"></i><span>Ойын</span></div>
						</div>
					<? endif ?>
				</div>

				<? if ($post): ?>
				<div class="card" style="margin-top:16px">
					<div class="card_h"><h6><i class="fab fa-instagram"></i> Турнир қорытындысы (пост)</h6></div>
					<textarea class="form_im_txt tr_post" rows="12" style="height:auto;padding:12px;line-height:1.5"><?=h($post)?></textarea>
					<div class="form_btns"><div class="btn btn_sm tr_post_copy"><i class="fal fa-copy"></i><span>Мәтінді көшіру</span></div></div>
				</div>
				<? endif ?>
			</div>

			<div>
				<div class="card">
					<div class="card_h"><h6>Командалар</h6><span class="bdg"><?=count($teams)?></span></div>
					<? foreach ($teams as $x): ?>
						<div class="li tr_team" data-t="<?=$x['id']?>">
							<div class="li_i"><?=($x['place'] ? ([1 => '🥇', 2 => '🥈', 3 => '🥉'][$x['place']] ?? $x['place']) : h(mb_strtoupper(mb_substr($x['name'], 0, 2))))?></div>
							<div class="li_c"><b><?=h($x['name'])?></b><span><?=h($x['captain'] ?: '')?><?=($staff && $x['phone'] ? ' · '.fun::phone_view($x['phone']) : '')?><?=($x['source'] == 'public' ? ' · сілтеме арқылы' : '')?></span></div>
							<? if ($staff): ?>
								<div class="li_r tr_team_r">
									<? if ($t['entry_fee'] > 0): ?><label class="tr_paid"><input type="checkbox" class="tr_t_paid" <?=($x['paid']?'checked':'')?>> төледі</label><? endif ?>
									<select class="form_im_txt tr_t_place" title="Орын"><option value="">орын</option><? for ($i = 1; $i <= 3; $i++): ?><option value="<?=$i?>" <?=($x['place']==$i?'selected':'')?>><?=$i?></option><? endfor ?></select>
									<i class="fal fa-times tr_t_rm" title="Өшіру"></i>
								</div>
							<? endif ?>
						</div>
					<? endforeach ?>
					<? if (!$teams): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-users"></i><p>Команда тіркелмеген</p></div><? endif ?>
					<? if ($staff && !in_array($t['status'], ['finished', 'cancelled'])): ?>
						<div class="tr_form_r tr_tform">
							<input type="text" class="form_im_txt" name="name" maxlength="80" placeholder="Команда атауы">
							<input type="text" class="form_im_txt" name="captain" maxlength="120" placeholder="Капитан">
							<input type="tel" class="form_im_txt" name="phone" placeholder="8 7__ ___ __ __">
							<div class="btn btn_sm tr_t_add"><i class="fal fa-plus"></i><span>Қосу</span></div>
						</div>
					<? endif ?>
				</div>

				<div class="card" style="margin-top:16px">
					<div class="card_h"><h6>Марапаттар</h6></div>
					<? foreach ($awards as $a): ?>
						<div class="li" data-a="<?=$a['id']?>">
							<div class="li_i">⭐</div>
							<div class="li_c"><b><?=h($a['person'])?></b><span><?=h($a['title'])?></span></div>
							<? if ($staff): ?><div class="li_r"><i class="fal fa-times tr_a_rm" title="Өшіру"></i></div><? endif ?>
						</div>
					<? endforeach ?>
					<? if (!$awards): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-medal"></i><p>Үздік ойыншы, қақпашы, бомбардир</p></div><? endif ?>
					<? if ($staff): ?>
						<div class="tr_form_r tr_aform">
							<select class="form_im_txt" name="title"><option>Үздік ойыншы</option><option>Үздік бомбардир</option><option>Үздік қақпашы</option><option>Үздік қорғаушы</option><option>Fair play</option></select>
							<select class="form_im_txt" name="participant_id"><option value="">— академиядан емес —</option><? foreach ($players as $p): ?><option value="<?=$p['id']?>"><?=h($p['last_name'].' '.$p['first_name'])?></option><? endforeach ?></select>
							<input type="text" class="form_im_txt" name="person" maxlength="120" placeholder="Аты-жөні (команда)">
							<div class="btn btn_sm tr_a_add"><i class="fal fa-plus"></i><span>Марапат</span></div>
						</div>
					<? endif ?>
				</div>
			</div>
		</div>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
