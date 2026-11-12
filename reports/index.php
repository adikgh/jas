<? include "../config/core.php";

	core::need_page(['owner', 'admin']);

	$year = isset($_GET['y']) ? max(2020, min(2100, (int) $_GET['y'])) : (int) date('Y');
	$y_start = "$year-01-01"; $y_end = "$year-12-31";
	$dir_name = ['kids' => 'ZHASS KIDS', 'adult' => 'ZHASS ADULT'];
	$met_name = ['kaspi' => 'Kaspi', 'kaspi_red' => 'Kaspi RED', 'bank' => 'Банк', 'cash' => 'Қолма-қол'];
	$cat_name = ['rent' => 'Жалдау', 'salary' => 'Жалақы', 'equipment' => 'Құрал-жабдық', 'marketing' => 'Маркетинг', 'tournament' => 'Турнир', 'other' => 'Басқа'];
	$src_name = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'threads' => 'Threads', 'whatsapp' => 'WhatsApp', 'referral' => 'Таныс арқылы', 'walk_in' => 'Өзі келді', 'other' => 'Басқа'];

	// 12 ай: кіріс (комиссиясыз), шығын, жаңа/кеткен қатысушылар
	$months = [];
	for ($i = 1; $i <= 12; $i++) $months[$i] = ['m' => fun::$months[$i], 'inc' => 0, 'fee' => 0, 'exp' => 0, 'new' => 0, 'left' => 0];
	foreach (db::all("select month(paid_at) m, sum(amount) s, sum(fee_amount) f from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ? group by m", [$org_id, "$y_start 00:00:00", "$y_end 23:59:59"]) as $r) { $months[$r['m']]['inc'] = (float) $r['s']; $months[$r['m']]['fee'] = (float) $r['f']; }
	foreach (db::all("select month(spent_at) m, sum(amount) s from expenses where organization_id = ? and spent_at between ? and ? group by m", [$org_id, $y_start, $y_end]) as $r) $months[$r['m']]['exp'] = (float) $r['s'];
	foreach (db::all("select month(joined_at) m, count(*) c from participants where organization_id = ? and joined_at between ? and ? group by m", [$org_id, $y_start, $y_end]) as $r) $months[$r['m']]['new'] = (int) $r['c'];
	foreach (db::all("select month(archived_at) m, count(*) c from participants where organization_id = ? and archived_at between ? and ? group by m", [$org_id, "$y_start 00:00:00", "$y_end 23:59:59"]) as $r) $months[$r['m']]['left'] = (int) $r['c'];
	foreach ($months as &$mm) $mm['net'] = $mm['inc'] - $mm['fee'] - $mm['exp']; unset($mm);
	$tot = ['inc' => array_sum(array_column($months, 'inc')), 'fee' => array_sum(array_column($months, 'fee')), 'exp' => array_sum(array_column($months, 'exp')), 'net' => array_sum(array_column($months, 'net'))];
	$ch_max = max(1, max(array_column($months, 'inc')));

	$by_dir = db::all("select p.direction k, sum(py.amount) s from payments py join participants p on p.id = py.participant_id where py.organization_id = ? and py.status = 'confirmed' and py.paid_at between ? and ? group by p.direction", [$org_id, "$y_start 00:00:00", "$y_end 23:59:59"]);
	$by_met = db::all("select method k, sum(amount) s, sum(fee_amount) f from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ? group by method order by s desc", [$org_id, "$y_start 00:00:00", "$y_end 23:59:59"]);
	$by_cat = db::all("select category k, sum(amount) s from expenses where organization_id = ? and spent_at between ? and ? group by category order by s desc", [$org_id, $y_start, $y_end]);

	// соңғы 30 күндегі қатысу (тек өткен жаттығулар)
	$att = db::all("select g.id, g.name, g.direction, count(a.id) total, sum(a.status = 'present') present, count(distinct s.id) sess
		from `groups` g join sessions s on s.group_id = g.id and s.status = 'done' and s.starts_at >= ? left join attendance a on a.session_id = s.id
		where g.organization_id = ? and g.active = 1 group by g.id order by g.name", [date('Y-m-d', strtotime('-30 day')), $org_id]);

	$active = db::row("select count(*) c, sum(direction = 'kids') kids, sum(direction = 'adult') adult from participants where organization_id = ? and archived_at is null and status = 'active'", [$org_id]);

	$debt_sql = fun::debt_sql();
	$debtors = db::all("select p.id, p.first_name, p.last_name, p.phone, p.direction, sum(d.debt) debt from ($debt_sql) d join participants p on p.id = d.participant_id
		where d.organization_id = ? and p.archived_at is null group by p.id having debt > 0 order by debt desc", [$org_id]);

	$leads = db::all("select source k, count(*) c, sum(status = 'converted') conv, sum(status = 'lost') lost from leads where organization_id = ? and created_at between ? and ? group by source order by c desc", [$org_id, "$y_start 00:00:00", "$y_end 23:59:59"]);

	// CSV экспорт (Excel үшін BOM + ;)
	if (isset($_GET['csv'])) {
		$rows = [];
		if ($_GET['csv'] == 'finance') { $rows[] = ['Ай', 'Кіріс', 'Комиссия', 'Шығын', 'Таза', 'Жаңа қатысушы', 'Кеткен']; foreach ($months as $m) $rows[] = [$m['m'], $m['inc'], $m['fee'], $m['exp'], $m['net'], $m['new'], $m['left']]; }
		elseif ($_GET['csv'] == 'debtors') { $rows[] = ['Аты-жөні', 'Телефон', 'Бағыт', 'Қарыз']; foreach ($debtors as $d) $rows[] = [$d['last_name'].' '.$d['first_name'], $d['phone'] ? fun::phone_view($d['phone']) : '', $dir_name[$d['direction']], $d['debt']]; }
		elseif ($_GET['csv'] == 'attendance') { $rows[] = ['Топ', 'Жаттығу', 'Белгі', 'Келді', '%']; foreach ($att as $a) $rows[] = [$a['name'], $a['sess'], $a['total'], $a['present'], $a['total'] ? round($a['present'] * 100 / $a['total']) : 0]; }
		else { http_response_code(404); exit(); }
		fun::audit('report_export', 'report', null, ['csv' => $_GET['csv'], 'year' => $year]);
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="jas_'.$_GET['csv'].'_'.$year.'.csv"');
		$out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
		foreach ($rows as $r) fputcsv($out, array_map(function ($v) { $v = (string) $v; return preg_match('/^[=+\-@]/', $v) ? "'".$v : $v; }, $r), ';');
		exit();
	}


	// site setting
	$menu_name = 'reports';
	$menu_title = 'Есептер';
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Есептер · <?=$year?></h4><p>Қаржы, қатысу, қатысушылар, маркетинг</p></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<a class="btn btn_sm btn_ghost" href="?y=<?=$year-1?>"><i class="fal fa-angle-left"></i><span><?=$year-1?></span></a>
				<? if ($year < date('Y')): ?><a class="btn btn_sm btn_ghost" href="?y=<?=$year+1?>"><span><?=$year+1?></span><i class="fal fa-angle-right"></i></a><? endif ?>
			</div>
		</div>

		<div class="dsh_k">
			<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t">Кіріс</div><div class="dsh_ki_n"><?=fun::money($tot['inc'])?></div><div class="dsh_ki_s">комиссия <?=fun::money($tot['fee'])?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Шығын</div><div class="dsh_ki_n"><?=fun::money($tot['exp'])?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Таза нәтиже</div><div class="dsh_ki_n <?=($tot['net']>=0?'cl_ok':'cl_err')?>"><?=fun::money($tot['net'])?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Белсенді қатысушы</div><div class="dsh_ki_n"><?=(int) $active['c']?></div><div class="dsh_ki_s">KIDS <?=(int) $active['kids']?> · ADULT <?=(int) $active['adult']?></div></div>
		</div>

		<div class="card" style="margin-top:16px">
			<div class="card_h"><h6>Ай сайынғы кіріс</h6><a class="btn btn_sm btn_ghost" href="?y=<?=$year?>&csv=finance"><i class="fal fa-file-csv"></i><span>CSV</span></a></div>
			<div class="chart_b">
				<? foreach ($months as $m): ?>
					<div class="chart_bi"><b><?=($m['inc'] ? round($m['inc'] / 1000).'k' : '0')?></b><i style="height:<?=round($m['inc'] * 100 / $ch_max)?>%"></i><span><?=h(mb_substr($m['m'], 0, 3))?></span></div>
				<? endforeach ?>
			</div>
			<div class="tbl_w" style="margin-top:14px"><table class="tbl">
				<tr><th>Ай</th><th>Кіріс</th><th>Комиссия</th><th>Шығын</th><th>Таза</th><th>Жаңа</th><th>Кетті</th></tr>
				<? foreach ($months as $m): ?>
					<tr><td><?=h($m['m'])?></td><td><?=fun::money($m['inc'])?></td><td><?=fun::money($m['fee'])?></td><td><?=fun::money($m['exp'])?></td><td class="<?=($m['net']>=0?'cl_ok':'cl_err')?>"><b><?=fun::money($m['net'])?></b></td><td><?=$m['new']?></td><td><?=$m['left']?></td></tr>
				<? endforeach ?>
			</table></div>
		</div>

		<div class="cab_g" style="margin-top:16px">
			<div class="card">
				<div class="card_h"><h6>Бағыт бойынша кіріс</h6></div>
				<? foreach ($by_dir as $r): ?><div class="li"><div class="li_c"><b><?=$dir_name[$r['k']]?></b><span><?=($tot['inc'] ? round($r['s'] * 100 / $tot['inc']) : 0)?>%</span></div><div class="li_r"><b><?=fun::money($r['s'])?></b></div></div><? endforeach ?>
				<? if (!$by_dir): ?><p class="ds_nr_sm">Дерек жоқ</p><? endif ?>
			</div>
			<div class="card">
				<div class="card_h"><h6>Төлем тәсілі</h6></div>
				<? foreach ($by_met as $r): ?><div class="li"><div class="li_c"><b><?=$met_name[$r['k']]?></b><span>комиссия <?=fun::money($r['f'])?></span></div><div class="li_r"><b><?=fun::money($r['s'])?></b></div></div><? endforeach ?>
				<? if (!$by_met): ?><p class="ds_nr_sm">Дерек жоқ</p><? endif ?>
			</div>
			<div class="card">
				<div class="card_h"><h6>Шығын санаттары</h6></div>
				<? foreach ($by_cat as $r): ?><div class="li"><div class="li_c"><b><?=$cat_name[$r['k']]?></b><span><?=($tot['exp'] ? round($r['s'] * 100 / $tot['exp']) : 0)?>%</span></div><div class="li_r"><b><?=fun::money($r['s'])?></b></div></div><? endforeach ?>
				<? if (!$by_cat): ?><p class="ds_nr_sm">Дерек жоқ</p><? endif ?>
			</div>
		</div>

		<div class="card" style="margin-top:16px">
			<div class="card_h"><h6>Қатысу · соңғы 30 күн</h6><a class="btn btn_sm btn_ghost" href="?y=<?=$year?>&csv=attendance"><i class="fal fa-file-csv"></i><span>CSV</span></a></div>
			<? foreach ($att as $a): $pc = $a['total'] ? round($a['present'] * 100 / $a['total']) : 0; ?>
				<div class="li">
					<div class="li_c"><b><?=h($a['name'])?></b><span><?=$a['sess']?> жаттығу · <?=(int) $a['present']?>/<?=(int) $a['total']?> келді</span><div class="bar" style="margin-top:6px"><i style="width:<?=$pc?>%;background:<?=($pc>=75?'var(--cl)':($pc>=50?'#e3a008':'#e02424'))?>"></i></div></div>
					<div class="li_r"><b><?=$pc?>%</b></div>
				</div>
			<? endforeach ?>
			<? if (!$att): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-clipboard-check"></i><p>Өткен жаттығу жоқ</p></div><? endif ?>
		</div>

		<div class="cab_g" style="margin-top:16px">
			<div class="card">
				<div class="card_h"><h6>Қарызы барлар</h6><span class="bdg bdg_err"><?=fun::money(array_sum(array_column($debtors, 'debt')))?></span><a class="btn btn_sm btn_ghost" href="?y=<?=$year?>&csv=debtors"><i class="fal fa-file-csv"></i><span>CSV</span></a></div>
				<? foreach (array_slice($debtors, 0, 15) as $d): ?>
					<a class="li" href="/participants/item.php?id=<?=$d['id']?>"><div class="li_c"><b><?=h($d['last_name'].' '.$d['first_name'])?></b><span><?=$dir_name[$d['direction']]?></span></div><div class="li_r"><b class="cl_err"><?=fun::money($d['debt'])?></b></div></a>
				<? endforeach ?>
				<? if (!$debtors): ?><p class="ds_nr_sm">Қарыз жоқ</p><? endif ?>
			</div>
			<div class="card">
				<div class="card_h"><h6>Өтінім көзі → конверсия</h6></div>
				<? foreach ($leads as $l): ?>
					<div class="li"><div class="li_c"><b><?=$src_name[$l['k']]?></b><span><?=$l['c']?> өтінім · <?=(int) $l['conv']?> қатысушы · <?=(int) $l['lost']?> бас тартты</span></div><div class="li_r"><b><?=round($l['conv'] * 100 / $l['c'])?>%</b></div></div>
				<? endforeach ?>
				<? if (!$leads): ?><p class="ds_nr_sm">Өтінім жоқ</p><? endif ?>
			</div>
		</div>

		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
