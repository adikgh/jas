<? include "../config/core.php";

	core::need_page();

	// күнделікті еске салғыштар (dedupe арқылы бір рет қана жасалады)
	academy::notify_daily($org_id);

	$only = isset($_GET['unread']);
	$list = db::all("select * from notifications where user_id = ? and organization_id = ?".($only ? " and read_at is null" : "")." order by id desc limit 100", [$user_id, $org_id]);
	$unread = (int) db::val("select count(*) from notifications where user_id = ? and organization_id = ? and read_at is null", [$user_id, $org_id]);
	$ico = ['birthday' => 'fa-birthday-cake', 'sub_expiring' => 'fa-ticket-alt', 'schedule_changed' => 'fa-calendar-exclamation', 'assessment' => 'fa-chart-line', 'lesson' => 'fa-play-circle', 'match' => 'fa-futbol', 'other' => 'fa-bell'];


	// site setting
	$menu_name = 'notifications';
	$menu_title = 'Хабарламалар';
	$js = ['notifications'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Хабарламалар</h4><p><?=($unread ? $unread.' оқылмаған' : 'Барлығы оқылды')?></p></div>
			<? if ($unread): ?><div class="btn btn_sm btn_ghost nt_all"><i class="fal fa-check-double"></i><span>Барлығын оқылды деп белгілеу</span></div><? endif ?>
		</div>

		<div class="tab">
			<a class="tab_i <?=(!$only?'tab_i_act':'')?>" href="/notifications/">Барлығы</a>
			<a class="tab_i <?=($only?'tab_i_act':'')?>" href="?unread">Оқылмаған (<?=$unread?>)</a>
		</div>

		<div class="card" style="margin-top:16px">
			<? foreach ($list as $n): ?>
				<a class="li nt_i" data-id="<?=$n['id']?>" href="<?=h($n['link'] ?: '#')?>" style="<?=($n['read_at']?'opacity:.6':'')?>">
					<div class="li_i"><i class="fal <?=$ico[$n['type']]?>"></i></div>
					<div class="li_c"><b><?=h($n['title'])?></b><span><?=h($n['body'])?><?=($n['body']?' · ':'')?><?=fun::date_short(substr($n['created_at'], 0, 10))?> <?=substr($n['created_at'], 11, 5)?></span></div>
					<div class="li_r"><? if (!$n['read_at']): ?><span class="bdg bdg_warn">жаңа</span><? endif ?></div>
				</a>
			<? endforeach ?>
			<? if (!$list): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-bell"></i><p>Хабарлама жоқ</p></div><? endif ?>
		</div>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
