<? include "../config/core.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);

	$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
	$p = fun::participant($id);
	if (!$p) { http_response_code(404); include "../block/403.php"; exit(); }

	$crit = db::all("select * from assessment_criteria where organization_id = ? and active = 1 and (position is null or position = ?) order by sort, id", [$org_id, $p['position']]);
	$ass = db::all("select a.*, u.name coach_name, (select round(avg(s.score), 1) from assessment_scores s where s.assessment_id = a.id) avg_score
		from player_assessments a left join users u on u.id = a.coach_id where a.participant_id = ? order by a.assessed_at, a.id", [$id]);
	$scores = [];
	foreach (db::all("select s.* from assessment_scores s join player_assessments a on a.id = s.assessment_id where a.participant_id = ?", [$id]) as $s) $scores[$s['assessment_id']][$s['criterion_id']] = (int) $s['score'];
	$last = $ass ? end($ass) : null;
	$prev = count($ass) > 1 ? $ass[count($ass) - 2] : null;

	// SVG сызықтық график (орташа балл)
	$W = 640; $H = 220; $pad = 34;
	$pts = [];
	$n = count($ass);
	foreach ($ass as $i => $a) {
		$x = $n > 1 ? $pad + ($W - 2 * $pad) * $i / ($n - 1) : $W / 2;
		$y = $H - $pad - ($H - 2 * $pad) * ((float) $a['avg_score'] - 1) / 9;
		$pts[] = [round($x, 1), round($y, 1), $a];
	}


	// site setting
	$menu_name = 'progress';
	$menu_title = 'Прогресс · '.$p['first_name'];
	$site_set['utop_bk'] = '/progress/';
	$js = ['progress'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4><?=h($p['last_name'].' '.$p['first_name'])?></h4><p><?=t::w($p['direction'])?><?=($p['position']?' · '.t::w($p['position']):'')?> · <?=fun::age($p['birth_date'])?> жас</p></div>
			<? if ($staff): ?><div class="btn btn_sm" data-pop="as_add"><i class="fal fa-plus"></i><span>Бағалау</span></div><? endif ?>
		</div>

		<div class="dsh_k" style="grid-template-columns:repeat(3,1fr)">
			<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t">Соңғы орташа</div><div class="dsh_ki_n"><?=($last ? $last['avg_score'] : '—')?></div><div class="dsh_ki_s"><?=($last ? fun::date_short($last['assessed_at']) : 'бағаланбаған')?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Өзгеріс</div><div class="dsh_ki_n <?=($last && $prev ? ($last['avg_score'] >= $prev['avg_score'] ? 'cl_ok' : 'cl_err') : '')?>"><?=($last && $prev ? (($d = round($last['avg_score'] - $prev['avg_score'], 1)) > 0 ? '+' : '').$d : '—')?></div><div class="dsh_ki_s">алдыңғы бағалаудан</div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t">Бағалау саны</div><div class="dsh_ki_n"><?=$n?></div></div>
		</div>

		<div class="dsh_g" style="margin-top:16px">
			<div class="card">
				<div class="card_h"><h6>Орташа балл динамикасы</h6></div>
				<? if ($n): ?>
					<svg class="ln_chart" viewBox="0 0 <?=$W?> <?=$H?>">
						<? foreach ([1, 4, 7, 10] as $g): $gy = $H - $pad - ($H - 2 * $pad) * ($g - 1) / 9; ?>
							<line x1="<?=$pad?>" x2="<?=$W - $pad?>" y1="<?=$gy?>" y2="<?=$gy?>" stroke="#e6ebe7"/><text x="8" y="<?=$gy + 4?>" font-size="11" fill="#8a948e"><?=$g?></text>
						<? endforeach ?>
						<polyline fill="none" stroke="#0b3c7a" stroke-width="3" stroke-linejoin="round" points="<?=implode(' ', array_map(function ($q) { return $q[0].','.$q[1]; }, $pts))?>"/>
						<? foreach ($pts as $q): ?>
							<circle cx="<?=$q[0]?>" cy="<?=$q[1]?>" r="5" fill="#ffc20e"/><text x="<?=$q[0]?>" y="<?=$q[1] - 10?>" font-size="12" font-weight="700" text-anchor="middle" fill="#111113"><?=$q[2]['avg_score']?></text>
							<text x="<?=$q[0]?>" y="<?=$H - 10?>" font-size="11" text-anchor="middle" fill="#8a948e"><?=date('d.m', strtotime($q[2]['assessed_at']))?></text>
						<? endforeach ?>
					</svg>
				<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-chart-line"></i><p>Әзірге бағалау жоқ</p></div><? endif ?>
			</div>
			<div class="card">
				<div class="card_h"><h6>Көрсеткіштер</h6><? if ($prev): ?><small>алдыңғы → соңғы</small><? endif ?></div>
				<? foreach ($crit as $c): $v = $last && isset($scores[$last['id']][$c['id']]) ? $scores[$last['id']][$c['id']] : null; $pv = $prev && isset($scores[$prev['id']][$c['id']]) ? $scores[$prev['id']][$c['id']] : null; ?>
					<div style="margin:10px 0">
						<div style="display:flex;justify-content:space-between;font-size:13px"><b><?=h($c['name'])?></b><span><?=($pv!==null?$pv.' → ':'')?><b><?=($v ?? '—')?></b><?=($pv!==null && $v!==null && $v!=$pv?' <span class="'.($v>$pv?'cl_ok':'cl_err').'">'.($v>$pv?'▲':'▼').'</span>':'')?></span></div>
						<div style="height:8px;background:#eef2ef;border-radius:4px;margin-top:6px;position:relative;overflow:hidden">
							<? if ($pv !== null): ?><i style="position:absolute;left:0;top:0;bottom:0;width:<?=$pv * 10?>%;background:#d8e6dc"></i><? endif ?>
							<i style="position:absolute;left:0;top:2px;bottom:2px;width:<?=($v ?: 0) * 10?>%;background:var(--green);border-radius:3px"></i>
						</div>
					</div>
				<? endforeach ?>
			</div>
		</div>

		<div class="card" style="margin-top:16px">
			<div class="card_h"><h6>Бағалау тарихы</h6></div>
			<? foreach (array_reverse($ass) as $a): ?>
				<div class="li">
					<div class="li_i"><?=$a['avg_score']?></div>
					<div class="li_c"><b><?=fun::date_kz($a['assessed_at'])?> · <?=h($a['coach_name'])?></b><span><?=nl2br(h($a['comment'] ?: '—'))?></span></div>
				</div>
			<? endforeach ?>
			<? if (!$ass): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-clipboard"></i><p>Бағалау жоқ</p></div><? endif ?>
		</div>
		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
		<div class="pop_bl pop_bl2 as_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c" style="padding:28px;overflow-y:auto">
				<div class="pop_h"><h5>Жаңа бағалау</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="as_form" onsubmit="return false" data-id="<?=$id?>">
					<div class="form_im"><label class="form_l">Күні</label><input type="date" class="form_im_txt" name="date" value="<?=$date?>" max="<?=$date?>"></div>
					<? foreach ($crit as $c): $v = $last && isset($scores[$last['id']][$c['id']]) ? $scores[$last['id']][$c['id']] : 5; ?>
						<div style="margin:14px 0">
							<div style="display:flex;justify-content:space-between;font-size:14px"><b><?=h($c['name'])?></b><b class="as_v"><?=$v?></b></div>
							<input type="range" min="1" max="10" step="1" value="<?=$v?>" name="scores[<?=$c['id']?>]" class="as_r" style="width:100%;accent-color:var(--green)">
						</div>
					<? endforeach ?>
					<div class="form_im"><label class="form_l">Пікір, кеңес</label><textarea class="form_im_txt" name="comment" rows="3" maxlength="2000" style="height:auto"></textarea></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn as_save">Сақтау</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
