<?
	// Мәзір: [кілт, сілтеме, белгіше, атауы, кімге көрінеді, бөлім]. Әр бет рұқсатты серверде өзі тексереді.
	$menu_list = [
		['dashboard', '/dashboard/', 'fa-chart-pie', 'Басқару тақтасы', ['owner', 'admin', 'coach'], 'main'],
		['cabinet', '/cabinet/', 'fa-home', 'Менің кабинетім', ['parent', 'participant'], 'main'],
		['notifications', '/notifications/', 'fa-bell', 'Хабарламалар', ['owner', 'admin', 'coach', 'parent', 'participant'], 'main'],
		['participants', '/participants/', 'fa-users', 'Қатысушылар', ['owner', 'admin', 'coach', 'parent', 'participant'], 'academy'],
		['groups', '/groups/', 'fa-users-class', 'Топтар', ['owner', 'admin', 'coach'], 'academy'],
		['schedule', '/schedule/', 'fa-calendar-alt', 'Кесте', ['owner', 'admin', 'coach', 'parent', 'participant'], 'academy'],
		['attendance', '/attendance/', 'fa-clipboard-check', 'Қатысу', ['owner', 'admin', 'coach'], 'academy'],
		['progress', '/progress/', 'fa-chart-line', 'Прогресс', ['owner', 'admin', 'coach', 'parent', 'participant'], 'academy'],
		['training', '/training/', 'fa-dumbbell', 'Training Hub', ['owner', 'admin', 'coach', 'parent', 'participant'], 'academy'],
		['rating', '/rating/', 'fa-trophy', 'JAS Rating', ['owner', 'admin', 'coach', 'parent', 'participant'], 'team'],
		['first_team', '/first_team/', 'fa-futbol', 'First Team & ЛЛФ', ['owner', 'admin', 'coach', 'participant'], 'team'],
		['scouting', '/scouting/', 'fa-binoculars', 'Селекция', ['owner', 'admin', 'coach'], 'team'],
		['media', '/media/', 'fa-images', 'Медиа', ['owner', 'admin', 'coach', 'parent', 'participant'], 'team'],
		['live', '/live/', 'fa-video', 'JAS Live', ['owner', 'admin', 'coach', 'parent', 'participant'], 'team'],
		['subscriptions', '/subscriptions/', 'fa-id-card', 'Абонемент', ['owner', 'admin'], 'business'],
		['payments', '/payments/', 'fa-wallet', 'Төлемдер', ['owner', 'admin'], 'business'],
		['crm', '/crm/', 'fa-bullhorn', 'Маркетинг CRM', ['owner', 'admin'], 'business'],
		['reports', '/reports/', 'fa-file-chart-line', 'Есептер', ['owner', 'admin'], 'business'],
	];
	$menu_groups = ['main' => '', 'academy' => 'Академия', 'team' => 'Команда', 'business' => 'Бизнес'];

	// мобильді төменгі панельдің 4 негізгі батырмасы (рөлге қарай)
	$menu_tabs = core::is(['owner', 'admin']) ? ['dashboard', 'participants', 'schedule', 'payments']
		: (core::is('coach') ? ['dashboard', 'attendance', 'schedule', 'rating'] : ['cabinet', 'schedule', 'rating', 'training']);
	$menu_short = ['dashboard' => 'Басты', 'cabinet' => 'Кабинет', 'participants' => 'Қатысушы', 'payments' => 'Төлем', 'rating' => 'Рейтинг', 'training' => 'Тапсырма'];
	$menu_by = [];
	foreach ($menu_list as $m) if (core::is($m[4])) $menu_by[$m[0]] = $m;
	$menu_more_act = !in_array($menu_name, $menu_tabs);
	$menu_initials = mb_strtoupper(mb_substr($user['name'], 0, 1).mb_substr((string) $user['surname'], 0, 1));
?>

<? if ($site_set['pmenu']): ?>
	<!-- мобильді төменгі панель -->
	<nav class="tbar">
		<? foreach ($menu_tabs as $k): if (empty($menu_by[$k])) continue; $m = $menu_by[$k]; ?>
			<a class="tbar_i <?=($menu_name==$k?'tbar_i_act':'')?>" href="<?=$m[1]?>"><i class="fal <?=$m[2]?>"></i><span><?=(isset($menu_short[$k]) ? $menu_short[$k] : $m[3])?></span></a>
		<? endforeach ?>
		<button type="button" class="tbar_i tbar_more <?=($menu_more_act?'tbar_i_act':'')?>" aria-label="Мәзір"><i class="fal fa-grip-horizontal"></i><span>Мәзір</span></button>
	</nav>

	<!-- «Мәзір» парағы -->
	<div class="msheet" aria-hidden="true">
		<div class="msheet_bg"></div>
		<div class="msheet_c">
			<div class="msheet_hd"><i></i></div>
			<div class="msheet_u">
				<div class="msheet_ua"><?=h($menu_initials ?: 'J')?></div>
				<div class="msheet_un"><b><?=h($user['name'].' '.$user['surname'])?></b><span><?=t::w($user_right)?></span></div>
				<a class="msheet_x" href="/exit.php" title="Шығу"><i class="fal fa-sign-out"></i></a>
			</div>
			<? foreach ($menu_groups as $g => $gn): $items = array_filter($menu_by, function ($m) use ($g) { return $m[5] == $g; }); if (!$items) continue; ?>
				<? if ($gn): ?><div class="msheet_g"><?=$gn?></div><? endif ?>
				<div class="msheet_grid">
					<? foreach ($items as $m): ?>
						<a class="msheet_i <?=($menu_name==$m[0]?'msheet_i_act':'')?>" href="<?=$m[1]?>"><i class="fal <?=$m[2]?>"></i><span><?=$m[3]?></span></a>
					<? endforeach ?>
				</div>
			<? endforeach ?>
		</div>
	</div>
<? endif ?>

<!-- компьютердегі бүйір мәзір -->
<aside class="ub1_l">
	<div class="ub1_lc">
		<a class="ub1_logo" href="/">
			<div class="ub1_logo_i">JAS</div>
			<div class="ub1_logo_t">ACADEMY<span>Шымкент<?=(core::$portal?' · '.h(core::$portals[core::$portal]['name']):'')?></span></div>
		</a>
		<nav class="umenu_c">
			<? foreach ($menu_groups as $g => $gn): $items = array_filter($menu_by, function ($m) use ($g) { return $m[5] == $g; }); if (!$items) continue; ?>
				<? if ($gn): ?><div class="umenu_g"><?=$gn?></div><? endif ?>
				<? foreach ($items as $m): ?>
					<a class="umenu_ci <?=($menu_name==$m[0]?'umenu_ci_act':'')?>" href="<?=$m[1]?>">
						<div class="umenu_cin"><i class="fal <?=$m[2]?>"></i></div>
						<div class="umenu_cih"><?=$m[3]?></div>
					</a>
				<? endforeach ?>
			<? endforeach ?>
		</nav>
		<div class="ub1_lx">
			<div class="ub1_lt">
				<div class="ub1_lti"><?=h($menu_initials ?: 'J')?></div>
				<div class="ub1_ltf">
					<div><?=h($user['name'])?> <?=h($user['surname']?mb_substr($user['surname'],0,1).'.':'')?></div>
					<span><?=t::w($user_right)?></span>
				</div>
				<a class="ub1_lexit" href="/exit.php" title="Шығу"><i class="fal fa-sign-out"></i></a>
			</div>
		</div>
	</div>
</aside>
