<? include "../config/core.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);

	$list = db::all("select l.*, o.name opp from live_streams l left join matches m on m.id = l.match_id left join opponents o on o.id = m.opponent_id where l.organization_id = ? order by field(l.status, 'live', 'scheduled', 'ended'), l.starts_at desc limit 50", [$org_id]);
	$main = null;
	foreach ($list as $l) if ($l['status'] != 'ended') { $main = $l; break; }
	if (!$main && $list) $main = $list[0];
	$matches = $staff ? db::all("select m.id, m.match_at, o.name opp from matches m join opponents o on o.id = m.opponent_id where m.organization_id = ? and m.status in ('scheduled', 'live') order by m.match_at", [$org_id]) : [];
	$pl = ['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'];
	$st = ['scheduled' => ['Жоспарда', 'bdg_warn'], 'live' => ['● Эфирде', 'bdg_err'], 'ended' => ['Аяқталды', '']];


	// site setting
	$menu_name = 'live';
	$menu_title = 'JAS Live';
	$js = ['live'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>JAS Live</h4><p>Матчтар мен шаралардың тікелей эфирі</p></div>
			<? if ($staff): ?><div class="btn btn_sm" data-pop="lv_add"><i class="fal fa-plus"></i><span>Эфир қосу</span></div><? endif ?>
		</div>

		<? if ($main): ?>
			<div class="card" style="margin-bottom:16px">
				<div class="card_h"><h6><?=h($main['title'])?></h6><span class="bdg <?=$st[$main['status']][1]?>"><?=$st[$main['status']][0]?></span></div>
				<? if ($main['youtube_id']): ?>
					<div class="yt_w"><iframe src="https://www.youtube-nocookie.com/embed/<?=h($main['youtube_id'])?>" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
				<? else: ?>
					<a class="btn" href="<?=h($main['url'])?>" target="_blank" rel="noopener"><i class="fal fa-external-link"></i><span><?=$pl[$main['platform']]?>-да ашу</span></a>
				<? endif ?>
				<p style="font-size:13px;color:#6b766f;margin-top:10px"><?=fun::date_kz(substr($main['starts_at'], 0, 10))?> · <?=date('H:i', strtotime($main['starts_at']))?><?=($main['opp']?' · JAS — '.h($main['opp']):'')?></p>
			</div>
		<? endif ?>

		<div class="card">
			<div class="card_h"><h6>Барлық эфир</h6></div>
			<? foreach ($list as $l): ?>
				<div class="li">
					<div class="li_i"><i class="fab fa-<?=($l['platform']=='youtube'?'youtube':($l['platform']=='instagram'?'instagram':'tiktok'))?>"></i></div>
					<div class="li_c"><b><?=h($l['title'])?></b><span><?=date('d.m.Y H:i', strtotime($l['starts_at']))?> · <a href="<?=h($l['url'])?>" target="_blank" rel="noopener"><?=$pl[$l['platform']]?></a></span></div>
					<div class="li_r" style="display:flex;gap:6px">
						<span class="bdg <?=$st[$l['status']][1]?>"><?=$st[$l['status']][0]?></span>
						<? if ($staff && $l['status'] == 'scheduled'): ?><span class="bdg bdg_err lv_st" data-id="<?=$l['id']?>" data-s="live" style="cursor:pointer">Бастау</span><? endif ?>
						<? if ($staff && $l['status'] == 'live'): ?><span class="bdg lv_st" data-id="<?=$l['id']?>" data-s="ended" style="cursor:pointer">Аяқтау</span><? endif ?>
					</div>
				</div>
			<? endforeach ?>
			<? if (!$list): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-video"></i><p>Эфир жоқ</p></div><? endif ?>
		</div>
		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
		<div class="pop_bl lv_add">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Жаңа эфир</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g lv_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="title" maxlength="160" placeholder="ЛЛФ: JAS — ..."></div>
					<div class="form_im form_im_f"><label class="form_l">Сілтеме (YouTube, Instagram, TikTok)</label><input type="text" class="form_im_txt" name="url" placeholder="https://youtube.com/live/..."></div>
					<div class="form_im"><label class="form_l">Басталуы</label><input type="datetime-local" class="form_im_txt" name="starts_at"></div>
					<div class="form_im"><label class="form_l">Матч</label><select class="form_im_txt" name="match_id"><option value="">—</option><? foreach ($matches as $m): ?><option value="<?=$m['id']?>"><?=date('d.m H:i', strtotime($m['match_at']))?> · <?=h($m['opp'])?></option><? endforeach ?></select></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn lv_save">Қосу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
