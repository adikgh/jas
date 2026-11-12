<? include "../config/core.php";
	include "lib.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);

	$list = db::all("select t.*, (select count(*) from tournament_teams x where x.tournament_id = t.id) teams,
			(select name from tournament_teams x where x.tournament_id = t.id and x.place = 1 limit 1) winner
		from tournaments t where t.organization_id = ? order by t.status in ('finished', 'cancelled'), t.starts_at desc limit 100", [$org_id]);


	// site setting
	$menu_name = 'tournaments';
	$menu_title = 'Турнирлер';
	$css = ['tournaments'];
	$js = ['tournaments'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Турнирлер</h4><p>Әр жексенбі турнир · 2 апта сайын ішкі турнир · кубок, медаль, үздік ойыншылар</p></div>
			<? if ($staff): ?><div class="btn btn_sm" data-pop="tr_add"><i class="fal fa-plus"></i><span>Турнир</span></div><? endif ?>
		</div>

		<div class="cab_g">
			<? foreach ($list as $t): ?>
				<a class="card tr_card" href="/tournaments/item.php?id=<?=$t['id']?>">
					<div class="tr_card_t"><span class="bdg <?=$tour_kind[$t['kind']][1]?>"><?=$tour_kind[$t['kind']][0]?></span><span class="bdg <?=$tour_st[$t['status']][1]?>"><?=$tour_st[$t['status']][0]?></span></div>
					<b class="tr_card_n"><?=h($t['name'])?></b>
					<span class="tr_card_s"><i class="fal fa-calendar"></i> <?=fun::date_kz(substr($t['starts_at'], 0, 10))?> · <?=substr($t['starts_at'], 11, 5)?><?=($t['venue']?' · '.h($t['venue']):'')?></span>
					<div class="tr_card_f">
						<span><i class="fal fa-users"></i> <?=$t['teams']?><?=($t['max_teams']?'/'.$t['max_teams']:'')?> команда</span>
						<span><?=$tour_dir[$t['direction']]?></span>
						<? if ($t['winner']): ?><span class="tr_win">🏆 <?=h($t['winner'])?></span><? elseif ($t['entry_fee'] > 0): ?><span><?=fun::money($t['entry_fee'])?></span><? endif ?>
					</div>
				</a>
			<? endforeach ?>
		</div>
		<? if (!$list): ?><div class="card"><div class="ds_nr"><i class="fal fa-trophy"></i><p>Турнир әлі жоқ</p></div></div><? endif ?>

		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
	<div class="pop_bl tr_add">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Жаңа турнир</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g tr_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="name" maxlength="120" placeholder="ZHASS Sunday Cup"></div>
				<div class="form_im"><label class="form_l">Түрі</label><select class="form_im_txt" name="kind"><option value="sunday">Жексенбілік</option><option value="internal">Ішкі (академия ішінде)</option><option value="external">Сыртқы</option></select></div>
				<div class="form_im"><label class="form_l">Кімге</label><select class="form_im_txt" name="direction"><option value="adult">Ересектер</option><option value="kids">Балалар</option><option value="mixed">Аралас</option></select></div>
				<div class="form_im"><label class="form_l">Күні, уақыты</label><input type="datetime-local" class="form_im_txt" name="starts_at"></div>
				<div class="form_im"><label class="form_l">Алаң</label><input type="text" class="form_im_txt" name="venue" maxlength="160" placeholder="Жабық манеж"></div>
				<div class="form_im"><label class="form_l">Жарна, ₸ (командадан)</label><input type="number" class="form_im_txt" name="entry_fee" min="0" value="0"></div>
				<div class="form_im"><label class="form_l">Команда саны (макс.)</label><input type="number" class="form_im_txt" name="max_teams" min="2" max="64"></div>
				<div class="form_im form_im_f"><label class="form_l">Жүлде</label><input type="text" class="form_im_txt" name="prize" maxlength="255" placeholder="Кубок, медальдар, үздік ойыншыға сыйлық"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn tr_save">Құру</div></div>
		</div>
	</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
