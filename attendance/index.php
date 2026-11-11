<? include "../config/core.php";

	core::need_page(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);

	$d = isset($_GET['d']) && fun::valid_date($_GET['d']) ? $_GET['d'] : $date;
	academy::sessions_generate($org_id, $d, $d);

	// бапкер тек өз жаттығуларын көреді
	$cw = $admin ? "" : " and (s.coach_id = ".(int) $user_id." or g.coach_id = ".(int) $user_id.")";
	$sql = "select s.*, g.name group_name, g.direction, v.name venue_name, u.name coach_name,
			(select count(*) from attendance a where a.session_id = s.id) marked,
			(select count(*) from attendance a where a.session_id = s.id and a.status = 'present') came,
			(select count(*) from enrollments e join participants p on p.id = e.participant_id where e.group_id = s.group_id and e.start_date <= date(s.starts_at) and (e.end_date is null or e.end_date > date(s.starts_at)) and p.archived_at is null) total
		from sessions s left join `groups` g on g.id = s.group_id left join venues v on v.id = s.venue_id left join users u on u.id = s.coach_id
		where s.organization_id = ? and s.type <> 'match' %s$cw order by s.starts_at";
	$list = db::all(sprintf($sql, "and date(s.starts_at) = ?"), [$org_id, $d]);

	// белгіленбей қалған өткен жаттығулар (7 күн)
	$missed = db::all(sprintf($sql, "and s.status <> 'cancelled' and s.starts_at between ? and ? and not exists (select 1 from attendance a where a.session_id = s.id)"),
		[$org_id, date('Y-m-d 00:00:00', strtotime("$date -7 day")), date('Y-m-d H:i:s')]);
	$missed = array_filter($missed, function ($s) { return $s['total'] > 0; });


	// site setting
	$menu_name = 'attendance';
	$menu_title = 'Қатысу';
	$css = ['schedule'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4>Қатысу</h4><p><?=fun::date_kz($d)?> · <?=fun::$weekdays[(int) date('N', strtotime($d))]?></p></div>
		</div>

		<div class="sch_top">
			<div class="sch_nav">
				<a class="btn btn_sm btn_ghost" href="?d=<?=date('Y-m-d', strtotime("$d -1 day"))?>"><i class="fal fa-angle-left"></i></a>
				<a class="btn btn_sm btn_ghost" href="?d=<?=$date?>">Бүгін</a>
				<a class="btn btn_sm btn_ghost" href="?d=<?=date('Y-m-d', strtotime("$d +1 day"))?>"><i class="fal fa-angle-right"></i></a>
			</div>
			<form class="flt" method="get" style="margin:0"><div class="form_im"><input type="date" class="form_im_txt flt_auto" name="d" value="<?=h($d)?>"></div></form>
		</div>

		<div class="card">
			<div class="card_h"><h6>Жаттығулар</h6><span class="bdg"><?=count($list)?></span></div>
			<? foreach ($list as $s): $x = $s['status'] == 'cancelled'; ?>
				<a class="li" <?=($x?'':'href="/attendance/session.php?id='.$s['id'].'"')?> style="<?=($x?'opacity:.5':'')?>">
					<div class="li_i"><?=date('H:i', strtotime($s['starts_at']))?></div>
					<div class="li_c">
						<b><?=h($s['group_name'])?> <?=($s['type']=='extra'?'<i class="sch_tag">қосымша</i>':'')?></b>
						<span><?=h($s['venue_name'])?> · <?=h($s['coach_name'])?><?=($x?' · болдырылмады':'')?></span>
					</div>
					<div class="li_r">
						<? if ($x): ?><span class="bdg bdg_err">Болдырылмады</span>
						<? elseif ($s['marked']): ?><span class="bdg bdg_ok"><?=$s['came']?> / <?=$s['total']?> келді</span>
						<? else: ?><span class="bdg bdg_warn">Белгіленбеген</span><? endif ?>
					</div>
				</a>
			<? endforeach ?>
			<? if (!$list): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-calendar"></i><p>Бұл күні жаттығу жоқ</p></div><? endif ?>
		</div>

		<? if ($missed): ?>
			<div class="card" style="margin-top:16px">
				<div class="card_h"><h6>Белгіленбей қалды (7 күн)</h6><span class="bdg bdg_err"><?=count($missed)?></span></div>
				<? foreach ($missed as $s): ?>
					<a class="li" href="/attendance/session.php?id=<?=$s['id']?>">
						<div class="li_i"><?=date('d.m', strtotime($s['starts_at']))?></div>
						<div class="li_c"><b><?=h($s['group_name'])?></b><span><?=date('H:i', strtotime($s['starts_at']))?> · <?=h($s['coach_name'])?> · <?=$s['total']?> қатысушы</span></div>
						<div class="li_r"><i class="fal fa-angle-right"></i></div>
					</a>
				<? endforeach ?>
			</div>
		<? endif ?>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
