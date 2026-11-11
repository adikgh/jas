<? include "../config/core.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);

	list($sw, $sp) = fun::scope();
	$f_dir = isset($_GET['dir']) && in_array($_GET['dir'], ['kids', 'adult']) ? $_GET['dir'] : '';
	$f_q = isset($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 60)) : '';
	$w = ["p.organization_id = ?", "p.archived_at is null", $sw]; $wp = array_merge([$org_id], $sp);
	if ($f_dir) { $w[] = "p.direction = ?"; $wp[] = $f_dir; }
	if ($f_q !== '') { $w[] = "concat(p.last_name, ' ', p.first_name) like ?"; $wp[] = '%'.$f_q.'%'; }

	$list = db::all("select p.id, p.first_name, p.last_name, p.img, p.direction, p.position,
			(select count(*) from player_assessments a where a.participant_id = p.id) cnt,
			(select max(a.assessed_at) from player_assessments a where a.participant_id = p.id) last_at
		from participants p where ".implode(' and ', $w)." order by last_at is null, p.last_name, p.first_name", $wp);
	if (!$staff && count($list) == 1) { header('location: /progress/item.php?id='.$list[0]['id']); exit(); }

	// соңғы екі бағалаудың орташасы
	foreach ($list as &$p) {
		$avg = db::all("select round(avg(s.score), 1) a from player_assessments a join assessment_scores s on s.assessment_id = a.id where a.participant_id = ? group by a.id order by a.assessed_at desc, a.id desc limit 2", [$p['id']]);
		$p['avg'] = $avg ? (float) $avg[0]['a'] : null;
		$p['diff'] = count($avg) == 2 ? round($avg[0]['a'] - $avg[1]['a'], 1) : null;
	}
	unset($p);


	// site setting
	$menu_name = 'progress';
	$menu_title = 'Прогресс';
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Футболшы прогресі</h4><p>Бапкер бағалауы: 10 көрсеткіш, 1–10 балл</p></div>
		</div>

		<? if ($staff): ?>
			<form class="flt" method="get">
				<div class="form_im"><input type="text" class="form_im_txt" name="q" value="<?=h($f_q)?>" placeholder="Аты-жөні"></div>
				<div class="form_im"><select class="form_im_txt flt_auto" name="dir"><option value="">Барлық бағыт</option><option value="kids" <?=($f_dir=='kids'?'selected':'')?>>JAS KIDS</option><option value="adult" <?=($f_dir=='adult'?'selected':'')?>>JAS ADULT</option></select></div>
			</form>
		<? endif ?>

		<div class="card" style="margin-top:12px">
			<? foreach ($list as $p): ?>
				<a class="li" href="/progress/item.php?id=<?=$p['id']?>">
					<div class="li_i"><?=($p['img'] ? '<img src="/assets/uploads/participants/'.h($p['img']).'" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">' : h(mb_substr($p['first_name'], 0, 1).mb_substr($p['last_name'], 0, 1)))?></div>
					<div class="li_c"><b><?=h($p['last_name'].' '.$p['first_name'])?></b><span><?=t::w($p['direction'])?><?=($p['position']?' · '.t::w($p['position']):'')?> · <?=($p['cnt'] ? $p['cnt'].' бағалау, соңғысы '.fun::date_short($p['last_at']) : 'бағаланбаған')?></span></div>
					<div class="li_r">
						<? if ($p['avg'] !== null): ?><b><?=$p['avg']?></b><? if ($p['diff'] !== null && $p['diff'] != 0): ?> <span class="bdg <?=($p['diff']>0?'bdg_ok':'bdg_err')?>"><?=($p['diff']>0?'+':'').$p['diff']?></span><? endif ?>
						<? elseif ($staff): ?><span class="bdg bdg_warn">Бағалау</span><? endif ?>
					</div>
				</a>
			<? endforeach ?>
			<? if (!$list): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-chart-line"></i><p>Қатысушы жоқ</p></div><? endif ?>
		</div>
		<div style="height:40px"></div>
	</div>

<? include "../block/footer.php"; ?>
