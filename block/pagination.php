<? // $page, $page_all, $page_link (мысалы "?q=..&page=") ?>
<? if ($page_all > 1): ?>
	<div class="uc_p">
		<? if ($page > 1): ?> <a class="uc_pi" href="<?=$page_link.($page-1)?>"><i class="fal fa-long-arrow-left"></i></a> <? endif ?>
		<? for ($pg = 1; $pg <= $page_all; $pg++): ?>
			<? if ($pg == 1 || $pg == $page_all || abs($pg - $page) <= 1): ?>
				<a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$page_link.$pg?>"><?=$pg?></a>
			<? elseif (abs($pg - $page) == 2): ?>
				<div class="uc_pi uc_pi_disp">...</div>
			<? endif ?>
		<? endfor ?>
		<? if ($page < $page_all): ?> <a class="uc_pi" href="<?=$page_link.($page+1)?>"><i class="fal fa-long-arrow-right"></i></a> <? endif ?>
	</div>
<? endif ?>
