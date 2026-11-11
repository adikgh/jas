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
				<div class="uhead_role"><?=t::w($user_right)?></div>
			</div>
		</div>
	<? endif ?>
