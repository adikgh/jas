<? include "../config/core.php";
	include "lib.php";

	// Командалар өздері тіркелетін ашық бет (кірусіз). Тек сілтеме коды арқылы.
	$code = preg_replace('/[^a-f0-9]/', '', (string) @$_GET['c']);
	$t = strlen($code) == 10 ? db::row("select t.*, o.name org_name, o.whatsapp from tournaments t join organizations o on o.id = t.organization_id where t.join_code = ?", [$code]) : null;
	$count = $t ? (int) db::val("select count(*) from tournament_teams where tournament_id = ?", [(int) $t['id']]) : 0;
	$open = $t && $t['status'] == 'registration' && strtotime($t['starts_at']) > time() && (!$t['max_teams'] || $count < $t['max_teams']);


	// тіркелу
	if (isset($_GET['join'])) {
		if (!$open) fun::json(['ok' => false, 'mess' => 'Тіркелу жабық']);
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		if (db::val("select count(*) from tournament_teams where ip = ? and source = 'public' and created_at > now() - interval 1 hour", [$ip]) >= 3) fun::json(['ok' => false, 'mess' => 'Тым көп өтінім. Кейінірек көріңіз']);
		$name = trim(fun::post('name')); $cap = trim(fun::post('captain'));
		$phone = fun::phone_clean(fun::post('phone'));
		if (mb_strlen($name) < 2 || mb_strlen($name) > 80) fun::json(['ok' => false, 'mess' => 'Команда атауын жазыңыз']);
		if (mb_strlen($cap) < 2 || mb_strlen($cap) > 120) fun::json(['ok' => false, 'mess' => 'Капитанның атын жазыңыз']);
		if (!$phone) fun::json(['ok' => false, 'mess' => 'Телефон нөмірі қате']);
		if (db::val("select id from tournament_teams where tournament_id = ? and name = ?", [(int) $t['id'], $name])) fun::json(['ok' => false, 'mess' => 'Бұл атаумен команда тіркелген']);
		if (db::val("select id from tournament_teams where tournament_id = ? and phone = ?", [(int) $t['id'], $phone])) fun::json(['ok' => false, 'mess' => 'Бұл нөмірден команда тіркелген']);
		db::tx(function () use ($t, $name, $cap, $phone, $ip) {
			db::ins("insert into tournament_teams (tournament_id, name, captain, phone, source, ip) values (?, ?, ?, ?, 'public', ?)", [(int) $t['id'], $name, $cap, $phone, $ip]);
			// CRM: капитан — әлеуетті клиент (белсенді өтінімі болмаса)
			if (!db::val("select id from leads where organization_id = ? and phone = ? and status not in ('converted', 'lost')", [(int) $t['organization_id'], $phone]))
				db::ins("insert into leads (organization_id, name, phone, direction, source, comment) values (?, ?, ?, ?, 'other', ?)",
					[(int) $t['organization_id'], $cap, $phone, $t['direction'] == 'kids' ? 'kids' : 'adult', mb_substr('Турнир: '.$t['name'].' · '.$name, 0, 255)]);
		});
		core::$org_id = (int) $t['organization_id'];
		fun::audit('team_join', 'tournaments', $t['id'], null, ['team' => $name]);
		fun::json(['ok' => true]);
	}


	// site setting
	$menu_name = 'tournament_join';
	$menu_title = $t ? $t['name'] : 'Турнир';
	$site_set['header'] = false;
	$site_set['menu'] = false;
	$site_set['pmenu'] = false;
	$css = ['sign', 'tournaments'];
	$js = ['tournaments'];
?>
<? include "../block/header.php"; ?>

	<div class="u_sign">
		<div class="usign_c tr_join" data-c="<?=h($code)?>">
			<div class="usign_logo">
				<img class="usign_logo_img" src="/assets/img/first_team/logo.png" alt="ZHASS">
				<div class="usign_logo_t"><?=h($t ? $t['org_name'] : 'ZHASS ACADEMY')?></div>
			</div>

			<? if (!$t): ?>
				<div class="usign_head"><h3 class="usign_h">Сілтеме қате</h3><p>Турнир табылмады. Ұйымдастырушыдан жаңа сілтеме сұраңыз.</p></div>
			<? else: ?>
				<div class="usign_head">
					<h3 class="usign_h"><?=h($t['name'])?></h3>
					<p><?=fun::date_kz(substr($t['starts_at'], 0, 10))?> · <?=substr($t['starts_at'], 11, 5)?><?=($t['venue'] ? ' · '.h($t['venue']) : '')?></p>
				</div>
				<div class="tr_join_i">
					<span><i class="fal fa-users"></i> <?=$count?><?=($t['max_teams']?' / '.$t['max_teams']:'')?> команда</span>
					<span><i class="fal fa-user"></i> <?=$tour_dir[$t['direction']]?></span>
					<? if ($t['entry_fee'] > 0): ?><span><i class="fal fa-money-bill"></i> <?=fun::money($t['entry_fee'])?> / команда</span><? endif ?>
					<? if ($t['prize']): ?><span><i class="fal fa-trophy"></i> <?=h($t['prize'])?></span><? endif ?>
				</div>

				<? if ($open): ?>
					<form class="usign_cn tr_jform" onsubmit="return false">
						<div class="form_im"><label class="form_l">Команда атауы</label><input type="text" class="form_im_txt" name="name" maxlength="80" required></div>
						<div class="form_im"><label class="form_l">Капитан</label><input type="text" class="form_im_txt" name="captain" maxlength="120" required></div>
						<div class="form_im"><label class="form_l">Телефон</label><input type="tel" class="form_im_txt" name="phone" placeholder="8 7__ ___ __ __" required></div>
						<div class="btn tr_jsave" style="width:100%">Команданы тіркеу</div>
					</form>
					<div class="tr_join_ok" style="display:none"><i class="fal fa-check-circle"></i><b>Команда тіркелді!</b><p>Ұйымдастырушы сізге хабарласады<?=($t['entry_fee'] > 0 ? ', жарнаны сол кезде төлейсіз' : '')?>.</p></div>
				<? else: ?>
					<div class="tr_join_ok"><i class="fal fa-lock"></i><b>Тіркелу жабық</b><p><?=($t['status'] == 'registration' && $t['max_teams'] && $count >= $t['max_teams'] ? 'Барлық орын толды.' : 'Бұл турнирге тіркелу аяқталды.')?></p></div>
				<? endif ?>
				<? if ($t['whatsapp']): ?><a class="tr_wa" href="https://wa.me/<?=h($t['whatsapp'])?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i><span>WhatsApp арқылы сұрау</span></a><? endif ?>
			<? endif ?>
		</div>
	</div>

<? include "../block/footer.php"; ?>
