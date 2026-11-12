<? // Қатысушы формасы (қосу / өңдеу). $fp — қатысушы немесе бос массив, $groups_all — топтар ?>
<? $fp_v = function ($k, $def = '') use ($fp) { return h(isset($fp[$k]) && $fp[$k] !== null ? $fp[$k] : $def); }; ?>
<div class="pop_bl part_form <?=($fp?'part_edit':'part_add')?>">
	<div class="pop_bl_a pop_back"></div>
	<div class="pop_bl_c">
		<div class="pop_h">
			<h5><?=($fp?'Профильді өңдеу':'Жаңа қатысушы')?></h5>
			<div class="pop_x pop_back"><i class="fal fa-times"></i></div>
		</div>

		<form class="form_g pf_form" onsubmit="return false" data-id="<?=($fp?(int)$fp['id']:'')?>">
			<div class="form_im"><label class="form_l">Тегі <em>*</em></label><input type="text" class="form_im_txt" name="last_name" value="<?=$fp_v('last_name')?>" maxlength="80"></div>
			<div class="form_im"><label class="form_l">Аты <em>*</em></label><input type="text" class="form_im_txt" name="first_name" value="<?=$fp_v('first_name')?>" maxlength="80"></div>
			<div class="form_im"><label class="form_l">Туған күні <em>*</em></label><input type="date" class="form_im_txt" name="birth_date" value="<?=$fp_v('birth_date')?>" max="<?=date('Y-m-d')?>"></div>
			<div class="form_im"><label class="form_l">Бағыты <em>*</em></label>
				<select class="form_im_txt pf_dir" name="direction">
					<option value="">Таңдаңыз</option>
					<option value="kids" <?=($fp_v('direction')=='kids'?'selected':'')?>>ZHASS KIDS</option>
					<option value="adult" <?=($fp_v('direction')=='adult'?'selected':'')?>>ZHASS ADULT</option>
				</select>
			</div>
			<div class="form_im"><label class="form_l">Телефон <span class="pf_ph_req" style="display:none"><em>*</em></span></label><input type="tel" class="form_im_txt fr_phone" name="phone" value="<?=h($fp && $fp['phone'] ? substr($fp['phone'], 1) : '')?>" placeholder="8 (700) 000-00-00"></div>
			<div class="form_im"><label class="form_l">Жынысы</label>
				<select class="form_im_txt" name="gender">
					<option value="m" <?=($fp_v('gender','m')=='m'?'selected':'')?>>Ұл / ер</option>
					<option value="f" <?=($fp_v('gender')=='f'?'selected':'')?>>Қыз / әйел</option>
				</select>
			</div>

			<? if (!$fp): ?>
				<div class="form_im form_im_f"><label class="form_l">Тобы</label>
					<select class="form_im_txt pf_group" name="group_id">
						<option value="">Кейін таңдаймын</option>
						<? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>" data-dir="<?=$g['direction']?>"><?=h($g['name'])?> · <?=h($g['coach_name'])?></option><? endforeach ?>
					</select>
				</div>
				<div class="form_sep pf_guard">Ата-анасы (ZHASS KIDS үшін міндетті)</div>
				<div class="form_im pf_guard"><label class="form_l">Аты-жөні <em>*</em></label><input type="text" class="form_im_txt" name="guardian_name" maxlength="120"></div>
				<div class="form_im pf_guard"><label class="form_l">Телефоны <em>*</em></label><input type="tel" class="form_im_txt fr_phone" name="guardian_phone" placeholder="8 (700) 000-00-00"></div>
				<div class="form_im pf_guard form_im_f"><label class="form_l">Кім болады</label>
					<select class="form_im_txt" name="guardian_relation"><option value="mother">Анасы</option><option value="father">Әкесі</option><option value="other">Басқа</option></select>
				</div>
			<? endif ?>

			<div class="form_sep">Футбол</div>
			<div class="form_im"><label class="form_l">Позиция</label>
				<select class="form_im_txt" name="position">
					<option value="">—</option>
					<? foreach (['gk', 'df', 'mf', 'fw'] as $x): ?><option value="<?=$x?>" <?=($fp_v('position')==$x?'selected':'')?>><?=t::w($x)?></option><? endforeach ?>
				</select>
			</div>
			<div class="form_im"><label class="form_l">Жетекші аяғы</label>
				<select class="form_im_txt" name="foot">
					<option value="">—</option>
					<? foreach (['right', 'left', 'both'] as $x): ?><option value="<?=$x?>" <?=($fp_v('foot')==$x?'selected':'')?>><?=t::w($x)?></option><? endforeach ?>
				</select>
			</div>
			<div class="form_im"><label class="form_l">Ойын нөмірі</label><input type="number" class="form_im_txt" name="number" value="<?=$fp_v('number')?>" min="1" max="99"></div>
			<div class="form_im"><label class="form_l">Тіркелген күні</label><input type="date" class="form_im_txt" name="joined_at" value="<?=$fp_v('joined_at', date('Y-m-d'))?>"></div>
			<? if ($fp): ?>
				<div class="form_im form_im_f"><label class="form_l">Мәртебесі</label>
					<select class="form_im_txt" name="status">
						<option value="active" <?=($fp_v('status')=='active'?'selected':'')?>>Белсенді</option>
						<option value="paused" <?=($fp_v('status')=='paused'?'selected':'')?>>Үзіліс</option>
						<option value="left" <?=($fp_v('status')=='left'?'selected':'')?>>Кетті</option>
					</select>
				</div>
			<? endif ?>
			<div class="form_im form_im_f"><label class="form_l">Ескерту</label><textarea class="form_im_txt" name="notes" maxlength="1000"><?=$fp_v('notes')?></textarea></div>
		</form>

		<div class="form_btns">
			<div class="btn btn_ghost pop_back">Болдырмау</div>
			<div class="btn pf_save"><span>Сақтау</span></div>
		</div>
	</div>
</div>
