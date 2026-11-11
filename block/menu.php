<?
	// Мәзір. 'r' — кімге көрінеді (сервер жағында да әр бет өзі тексереді).
	$menu_list = [
		['dashboard', '/dashboard/', 'fa-chart-pie', 'Басқару тақтасы', ['owner', 'admin', 'coach']],
		['participants', '/participants/', 'fa-users', 'Қатысушылар', ['owner', 'admin', 'coach', 'parent', 'participant']],
		['groups', '/groups/', 'fa-users-class', 'Топтар', ['owner', 'admin', 'coach']],
		['attendance', '/attendance/', 'fa-clipboard-check', 'Қатысу', ['owner', 'admin', 'coach']],
		['schedule', '/schedule/', 'fa-calendar-alt', 'Кесте', ['owner', 'admin', 'coach', 'parent', 'participant']],
		['subscriptions', '/subscriptions/', 'fa-id-card', 'Абонемент', ['owner', 'admin']],
		['payments', '/payments/', 'fa-wallet', 'Төлемдер', ['owner', 'admin']],
		['rating', '/rating/', 'fa-trophy', 'JAS Rating', ['owner', 'admin', 'coach', 'parent', 'participant']],
		['first_team', '/first_team/', 'fa-futbol', 'First Team & ЛЛФ', ['owner', 'admin', 'coach', 'participant']],
	];
?>

<? if ($site_set['pmenu']): ?>
	<div class="pmenu">
		<div class="pmenu_c">
			<? foreach ($menu_list as $m): if (!core::is($m[4]) || !$m[1]) continue; ?>
				<a class="pmenu_i <?=($menu_name==$m[0]?'pmenu_i_act':'')?>" href="<?=$m[1]?>"><i class="fal <?=$m[2]?>"></i><span><?=$m[3]?></span></a>
			<? endforeach ?>
			<a class="pmenu_i" href="/exit.php"><i class="fal fa-sign-out"></i><span>Шығу</span></a>
		</div>
	</div>
<? endif ?>

<div class="ub1_l">
	<div class="ub1_lc">
		<a class="ub1_logo" href="/">
			<div class="ub1_logo_i">JAS</div>
			<div class="ub1_logo_t">ACADEMY<span>Шымкент</span></div>
		</a>
		<div class="umenu_c">
			<? foreach ($menu_list as $m): if (!core::is($m[4])) continue; ?>
				<? if ($m[1]): ?>
					<a class="umenu_ci <?=($menu_name==$m[0]?'umenu_ci_act':'')?>" href="<?=$m[1]?>">
						<div class="umenu_cin"><i class="fal <?=$m[2]?>"></i></div>
						<div class="umenu_cih"><?=$m[3]?></div>
					</a>
				<? else: ?>
					<div class="umenu_ci umenu_ci_ds">
						<div class="umenu_cin"><i class="fal <?=$m[2]?>"></i></div>
						<div class="umenu_cih"><?=$m[3]?> <span>жақында</span></div>
					</div>
				<? endif ?>
			<? endforeach ?>
		</div>
		<div class="ub1_lx">
			<div class="ub1_lt">
				<div class="ub1_lti"><i class="fal fa-user"></i></div>
				<div class="ub1_ltf">
					<div><?=h($user['name'])?> <?=h($user['surname']?mb_substr($user['surname'],0,1).'.':'')?></div>
					<span><?=t::w($user_right)?></span>
				</div>
			</div>
			<a class="umenu_ci" href="/exit.php">
				<div class="umenu_cin"><i class="fal fa-sign-out"></i></div>
				<div class="umenu_cih">Шығу</div>
			</a>
		</div>
	</div>
</div>
