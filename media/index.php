<? include "../config/core.php";
	include "lib.php";

	core::need_page();
	$staff = core::is(['owner', 'admin', 'coach']);
	$admin = core::is(['owner', 'admin']);

	$tab = isset($_GET['tab']) && $_GET['tab'] == 'consent' && $admin ? 'consent' : 'media';
	$albums = db::all("select a.*, (select count(*) from media_assets m where m.album_id = a.id) cnt from media_albums a where a.organization_id = ? order by a.id desc", [$org_id]);
	$aid = isset($_GET['album']) ? (int) $_GET['album'] : 0;

	list($w, $wp) = media_where();
	if ($aid) { $w .= " and m.album_id = ?"; $wp[] = $aid; }
	$items = db::all("select m.*, (select group_concat(concat(p.first_name, ' ', p.last_name) separator ', ') from media_asset_participants mp join participants p on p.id = mp.participant_id where mp.media_asset_id = m.id) tags
		from media_assets m where $w order by m.id desc limit 200", $wp);

	$parts = $staff ? db::all("select p.id, p.first_name, p.last_name,
			(select count(*) from media_consents c where c.participant_id = p.id and c.scope = 'internal' and c.revoked_at is null) c_int,
			(select count(*) from media_consents c where c.participant_id = p.id and c.scope = 'social' and c.revoked_at is null) c_soc
		from participants p where p.organization_id = ? and p.archived_at is null order by p.last_name, p.first_name", [$org_id]) : [];
	$groups = $staff ? db::all("select id, name from `groups` where organization_id = ? and active = 1 order by name", [$org_id]) : [];
	$vis = ['staff' => 'Тек қызметкерлер', 'tagged' => 'Белгіленгендердің отбасы', 'parents' => 'Барлық ата-ана', 'public' => 'Ашық (әлеуметтік желі)'];


	// site setting
	$menu_name = 'media';
	$menu_title = 'Медиа';
	$js = ['media'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<div class="head_c">
			<div><h4>Медиа</h4><p>Фото мен видео · балалардың суреті тек ата-ана келісімімен</p></div>
			<? if ($staff): ?>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<div class="btn btn_sm btn_ghost" data-pop="md_album"><i class="fal fa-folder-plus"></i><span>Альбом</span></div>
					<div class="btn btn_sm" data-pop="md_up"><i class="fal fa-cloud-upload"></i><span>Жүктеу</span></div>
				</div>
			<? endif ?>
		</div>

		<div class="tab">
			<a class="tab_i <?=($tab=='media'&&!$aid?'tab_i_act':'')?>" href="/media/">Барлығы</a>
			<? foreach ($albums as $a): ?><a class="tab_i <?=($tab=='media'&&$aid==$a['id']?'tab_i_act':'')?>" href="?album=<?=$a['id']?>"><?=h($a['name'])?></a><? endforeach ?>
			<? if ($admin): ?><a class="tab_i <?=($tab=='consent'?'tab_i_act':'')?>" href="?tab=consent"><i class="fal fa-file-signature"></i> Келісімдер</a><? endif ?>
		</div>

		<? if ($tab == 'media'): ?>
			<div class="md_g">
				<? foreach ($items as $m): $yt = strpos($m['storage_key'], 'yt:') === 0; ?>
					<div style="position:relative">
						<a class="md_i" href="<?=($yt ? 'https://www.youtube.com/watch?v='.h(substr($m['storage_key'], 3)) : media_src($m))?>" target="_blank" rel="noopener">
							<img src="<?=h(media_src($m))?>" alt="" loading="lazy">
							<? if ($yt): ?><i class="fal fa-play-circle md_play"></i><? endif ?>
							<span><?=h($m['title'] ?: ($yt ? 'Видео' : 'Фото'))?><?=($m['tags'] && $staff ? ' · '.h($m['tags']) : '')?></span>
						</a>
						<? if ($staff): ?><span class="bdg" style="position:absolute;top:8px;left:8px"><?=$vis[$m['visibility']]?></span><? endif ?>
						<? if ($admin): ?><span class="bdg bdg_err md_del" data-id="<?=$m['id']?>" style="position:absolute;top:8px;right:8px;cursor:pointer"><i class="fal fa-trash"></i></span><? endif ?>
					</div>
				<? endforeach ?>
			</div>
			<? if (!$items): ?><div class="card"><div class="ds_nr"><i class="fal fa-images"></i><p>Медиа жоқ</p></div></div><? endif ?>
		<? else: ?>
			<div class="card">
				<div class="card_h"><h6>Ата-ана келісімі</h6><small>ішкі — платформада көрсету, әлеуметтік — Instagram/TikTok</small></div>
				<div class="tbl_w"><table class="tbl">
					<tr><th>Қатысушы</th><th>Платформа ішінде</th><th>Әлеуметтік желі</th></tr>
					<? foreach ($parts as $p): ?>
						<tr>
							<td><a href="/participants/item.php?id=<?=$p['id']?>#media"><b><?=h($p['last_name'].' '.$p['first_name'])?></b></a></td>
							<td><input type="checkbox" class="md_cons" data-id="<?=$p['id']?>" data-s="internal" <?=($p['c_int']?'checked':'')?>></td>
							<td><input type="checkbox" class="md_cons" data-id="<?=$p['id']?>" data-s="social" <?=($p['c_soc']?'checked':'')?>></td>
						</tr>
					<? endforeach ?>
				</table></div>
			</div>
		<? endif ?>
		<div style="height:40px"></div>
	</div>

	<? if ($staff): ?>
		<div class="pop_bl md_album">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c">
				<div class="pop_h"><h5>Жаңа альбом</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g al_form" onsubmit="return false">
					<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="name" maxlength="120" placeholder="KIDS U8 · қараша"></div>
					<div class="form_im form_im_f"><label class="form_l">Топ</label><select class="form_im_txt" name="group_id"><option value="">—</option><? foreach ($groups as $g): ?><option value="<?=$g['id']?>"><?=h($g['name'])?></option><? endforeach ?></select></div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn al_save">Құру</div></div>
			</div>
		</div>
		<div class="pop_bl pop_bl2 md_up">
			<div class="pop_bl_a pop_back"></div>
			<div class="pop_bl_c" style="padding:28px;overflow-y:auto">
				<div class="pop_h"><h5>Медиа жүктеу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
				<form class="form_g up_form" onsubmit="return false" enctype="multipart/form-data">
					<div class="form_im form_im_f"><label class="form_l">Альбом</label><select class="form_im_txt" name="album_id"><option value="">—</option><? foreach ($albums as $a): ?><option value="<?=$a['id']?>" <?=($aid==$a['id']?'selected':'')?>><?=h($a['name'])?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Фото (jpg, png, webp · 5 МБ дейін, бірнешеуін таңдауға болады)</label><input type="file" class="form_im_txt" name="files[]" accept="image/jpeg,image/png,image/webp" multiple></div>
					<div class="form_im form_im_f"><label class="form_l">немесе YouTube сілтемесі</label><input type="text" class="form_im_txt" name="youtube" placeholder="https://youtu.be/..."></div>
					<div class="form_im form_im_f"><label class="form_l">Атауы</label><input type="text" class="form_im_txt" name="title" maxlength="160"></div>
					<div class="form_im"><label class="form_l">Түрі</label><select class="form_im_txt" name="kind"><option value="general">Жалпы</option><option value="highlight">Үздік сәттер</option><option value="analysis">Талдау</option></select></div>
					<div class="form_im"><label class="form_l">Кім көреді</label><select class="form_im_txt" name="visibility"><? foreach ($vis as $k => $v): ?><option value="<?=$k?>" <?=($k=='tagged'?'selected':'')?>><?=$v?></option><? endforeach ?></select></div>
					<div class="form_im form_im_f"><label class="form_l">Суреттегі қатысушылар</label>
						<select class="form_im_txt" name="tags[]" multiple size="6" style="height:auto"><? foreach ($parts as $p): ?><option value="<?=$p['id']?>"><?=h($p['last_name'].' '.$p['first_name'])?><?=(!$p['c_int']?' (келісім жоқ)':'')?></option><? endforeach ?></select>
					</div>
				</form>
				<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn up_save">Жүктеу</div></div>
			</div>
		</div>
	<? endif ?>

<? include "../block/footer.php"; ?>
