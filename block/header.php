<!DOCTYPE html>
<html lang="<?=$lang?>" id="html" class="html">
<head>

	<? include "head.php"; ?>

</head>
<body id="body" class="body">

	<? if ($site_set['menu'] == true) include "menu.php"; ?>

	<!-- body start -->
	<div class="app <?=($site_set['menu']?'app_m':'')?>">

	<? if ($site_set['header'] == true): ?>
		<div class="uhead">
			<? if (!empty($site_set['utop_bk'])): ?> <a class="uhead_lb" href="<?=h($site_set['utop_bk'])?>"><i class="fal fa-long-arrow-left"></i></a> <? endif ?>
			<h4 class="uhead_c"><?=h($menu_title)?></h4>
			<div class="uhead_r">
				<? $n_unread = $user_id ? (int) db::val("select count(*) from notifications where user_id = ? and read_at is null", [$user_id]) : 0; ?>
				<a class="uhead_bell" href="/notifications/" title="Хабарламалар"><i class="fal fa-bell"></i><?=($n_unread?'<em>'.($n_unread>99?'99+':$n_unread).'</em>':'')?></a>
				<div class="uhead_role"><?=t::w($user_right)?></div>
			</div>
		</div>
	<? endif ?>
