<? include "../config/core.php";

	core::need_page(['owner', 'admin']);

	// мерзімі өткен / заморозкасы басталған абонементтерді жаңарту
	academy::subs_refresh_all($org_id);

	$f_st = isset($_GET['st']) ? $_GET['st'] : 'now';
	$f_dir = isset($_GET['dir']) && in_array($_GET['dir'], ['kids', 'adult']) ? $_GET['dir'] : '';
	$f_q = isset($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 60)) : '';

	$w = ["s.organization_id = ?"]; $wp = [$org_id];
	if ($f_st == 'now') $w[] = "s.status in ('pending', 'active', 'frozen')";
	elseif ($f_st == 'soon') { $w[] = "s.status = 'active' and s.end_date between ? and ?"; $wp[] = $date; $wp[] = date('Y-m-d', strtotime("$date +5 day")); }
	elseif ($f_st == 'debt') $w[] = "s.status <> 'cancelled' and s.price - s.discount > coalesce((select sum(pa.amount) from payment_allocations pa join payments py on py.id = pa.payment_id and py.status = 'confirmed' where pa.subscription_id = s.id), 0)";
	elseif (in_array($f_st, ['pending', 'active', 'frozen', 'expired', 'cancelled'])) { $w[] = "s.status = ?"; $wp[] = $f_st; }
	if ($f_dir) { $w[] = "p.direction = ?"; $wp[] = $f_dir; }
	if ($f_q !== '') { $w[] = "concat(p.last_name, ' ', p.first_name) like ?"; $wp[] = '%'.$f_q.'%'; }

	$subs = db::all("select s.*, p.first_name, p.last_name, p.direction, pl.name plan_name,
			coalesce((select sum(pa.amount) from payment_allocations pa join payments py on py.id = pa.payment_id and py.status = 'confirmed' where pa.subscription_id = s.id), 0) paid,
			coalesce((select sum(f.days) from freezes f where f.subscription_id = s.id and f.status in ('approved', 'requested')), 0) frozen_days
		from subscriptions s join participants p on p.id = s.participant_id join plans pl on pl.id = s.plan_id
		where ".implode(' and ', $w)." order by field(s.status, 'pending', 'frozen', 'active', 'expired', 'cancelled'), s.end_date, p.last_name limit 300", $wp);
	foreach ($subs as &$s) $s['used'] = academy::sub_used($s);
	unset($s);

	$freezes = db::all("select f.*, p.first_name, p.last_name, s.freeze_days_limit, u.name req_name,
			coalesce((select sum(x.days) from freezes x where x.subscription_id = f.subscription_id and x.status = 'approved'), 0) used_days
		from freezes f join subscriptions s on s.id = f.subscription_id join participants p on p.id = s.participant_id left join users u on u.id = f.requested_by
		where f.organization_id = ? and f.status = 'requested' order by f.start_date", [$org_id]);

	$plans = db::all("select pl.*, (select fee_percent from payment_method_fees f where f.plan_id = pl.id and f.method = 'kaspi_red') red_fee from plans pl where pl.organization_id = ? order by pl.active desc, pl.direction, pl.price", [$org_id]);
	$red_fee = db::val("select fee_percent from payment_method_fees where organization_id = ? and method = 'kaspi_red' and plan_id is null", [$org_id]);
	$parts = db::all("select id, first_name, last_name, direction from participants where organization_id = ? and archived_at is null and status <> 'left' order by last_name, first_name", [$org_id]);

	$cnt = db::row("select sum(status = 'active') a, sum(status = 'pending') p, sum(status = 'frozen') f, sum(status = 'active' and end_date between ? and ?) soon from subscriptions where organization_id = ?", [$date, date('Y-m-d', strtotime("$date +5 day")), $org_id]);
	$st_cls = ['active' => 'bdg_ok', 'pending' => 'bdg_warn', 'frozen' => '', 'expired' => 'bdg_err', 'cancelled' => 'bdg_dk'];


	// site setting
	$menu_name = 'subscriptions';
	$menu_title = 'Абонемент';
	$js = ['subscriptions'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4>Абонемент</h4><p>1 ай алғашқы расталған төлемнен · заморозка жалпы <?=7?> күнге дейін</p></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<div class="btn btn_sm btn_ghost" data-pop="plan_list"><i class="fal fa-tags"></i><span>Тарифтер</span></div>
				<div class="btn btn_sm" data-pop="sub_sell"><i class="fal fa-plus"></i><span>Абонемент сату</span></div>
			</div>
		</div>

		<div class="dsh_k">
			<a class="card dsh_ki dsh_ki_dk" href="?st=active"><div class="dsh_ki_t"><i class="fal fa-id-card"></i>Белсенді</div><div class="dsh_ki_n"><?=(int) $cnt['a']?></div></a>
			<a class="card dsh_ki" href="?st=soon"><div class="dsh_ki_t"><i class="fal fa-hourglass-half"></i>5 күнде бітеді</div><div class="dsh_ki_n"><?=(int) $cnt['soon']?></div></a>
			<a class="card dsh_ki" href="?st=pending"><div class="dsh_ki_t"><i class="fal fa-clock"></i>Төлем күтілуде</div><div class="dsh_ki_n"><?=(int) $cnt['p']?></div></a>
			<a class="card dsh_ki" href="?st=frozen"><div class="dsh_ki_t"><i class="fal fa-snowflake"></i>Заморозкада</div><div class="dsh_ki_n"><?=(int) $cnt['f']?></div></a>
		</div>

		<? if ($freezes): ?>
			<div class="card" style="margin-top:16px">
				<div class="card_h"><h6>Заморозка өтініштері</h6><span class="bdg bdg_warn"><?=count($freezes)?></span></div>
				<? foreach ($freezes as $f): ?>
					<div class="li">
						<div class="li_i"><i class="fal fa-snowflake"></i></div>
						<div class="li_c"><b><?=h($f['last_name'].' '.$f['first_name'])?> · <?=$f['days']?> күн</b><span><?=fun::date_short($f['start_date'])?> – <?=fun::date_short($f['end_date'])?><?=($f['reason']?' · '.h($f['reason']):'')?> · бұрын <?=$f['used_days']?>/<?=$f['freeze_days_limit']?> күн</span></div>
						<div class="li_r" style="display:flex;gap:6px">
							<span class="bdg bdg_ok fz_dec" data-id="<?=$f['id']?>" data-d="approve" style="cursor:pointer">Бекіту</span>
							<span class="bdg bdg_err fz_dec" data-id="<?=$f['id']?>" data-d="reject" style="cursor:pointer">Бас тарту</span>
						</div>
					</div>
				<? endforeach ?>
			</div>
		<? endif ?>

		<form class="flt" method="get" style="margin-top:16px">
			<div class="form_im"><input type="text" class="form_im_txt" name="q" value="<?=h($f_q)?>" placeholder="Аты-жөні"></div>
			<div class="form_im"><select class="form_im_txt flt_auto" name="st">
				<? foreach (['now' => 'Ағымдағы', 'active' => 'Белсенді', 'soon' => '5 күнде бітеді', 'pending' => 'Төлем күтілуде', 'frozen' => 'Заморозкада', 'debt' => 'Қарызы бар', 'expired' => 'Мерзімі өтті', 'cancelled' => 'Тоқтатылған', 'all' => 'Барлығы'] as $k => $v): ?>
					<option value="<?=$k?>" <?=($f_st==$k?'selected':'')?>><?=$v?></option>
				<? endforeach ?>
			</select></div>
			<div class="form_im"><select class="form_im_txt flt_auto" name="dir"><option value="">Барлық бағыт</option><option value="kids" <?=($f_dir=='kids'?'selected':'')?>>ZHASS KIDS</option><option value="adult" <?=($f_dir=='adult'?'selected':'')?>>ZHASS ADULT</option></select></div>
		</form>

		<div class="card" style="margin-top:12px">
			<div class="card_h"><h6>Абонементтер</h6><span class="bdg"><?=count($subs)?></span></div>
			<? if ($subs): ?>
				<div class="tbl_w"><table class="tbl">
					<tr><th>Қатысушы</th><th>Тариф</th><th>Мерзімі</th><th>Жаттығу</th><th>Заморозка</th><th>Төленді</th><th>Мәртебе</th><th></th></tr>
					<? foreach ($subs as $s): $total = $s['price'] - $s['discount']; ?>
						<tr>
							<td><a href="/participants/item.php?id=<?=$s['participant_id']?>#payments"><b><?=h($s['last_name'].' '.$s['first_name'])?></b></a><br><small><?=t::w($s['direction'])?></small></td>
							<td><?=h($s['plan_name'])?></td>
							<td><?=($s['start_date'] ? fun::date_short($s['start_date']).' – '.fun::date_short($s['end_date']) : '<small>төлемнен кейін</small>')?></td>
							<td><?=$s['used']?><?=($s['sessions_limit'] ? ' / '.$s['sessions_limit'] : ' / ∞')?></td>
							<td><?=$s['frozen_days']?> / <?=$s['freeze_days_limit']?> к.</td>
							<td class="<?=($s['paid'] < $total ? 'cl_err' : 'cl_ok')?>"><?=fun::money($s['paid'])?><br><small>/ <?=fun::money($total)?></small></td>
							<td><span class="bdg <?=$st_cls[$s['status']]?>"><?=t::w('sub_'.$s['status'])?></span></td>
							<td style="white-space:nowrap">
								<? if (in_array($s['status'], ['active', 'frozen'])): ?><span class="bdg sb_fz" data-id="<?=$s['id']?>" data-n="<?=h($s['last_name'].' '.$s['first_name'])?>" data-max="<?=max(0, $s['freeze_days_limit'] - $s['frozen_days'])?>" style="cursor:pointer" title="Заморозка"><i class="fal fa-snowflake"></i></span><? endif ?>
								<? if ($s['status'] != 'cancelled' && $s['paid'] < $total): ?><a class="bdg bdg_ok" href="/payments/?add=<?=$s['id']?>" title="Төлем енгізу"><i class="fal fa-wallet"></i></a><? endif ?>
								<? if ($s['status'] != 'cancelled' && $s['paid'] == 0): ?><span class="bdg bdg_err sb_cancel" data-id="<?=$s['id']?>" style="cursor:pointer" title="Тоқтату"><i class="fal fa-ban"></i></span><? endif ?>
							</td>
						</tr>
					<? endforeach ?>
				</table></div>
			<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-id-card"></i><p>Абонемент табылмады</p></div><? endif ?>
		</div>

		<div style="height:40px"></div>
	</div>

	<!-- сату -->
	<div class="pop_bl sub_sell">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Абонемент сату</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g ss_form" onsubmit="return false">
				<div class="form_im form_im_f"><label class="form_l">Қатысушы <em>*</em></label><select class="form_im_txt ss_part" name="participant_id"><option value="">Таңдаңыз</option>
					<? foreach ($parts as $p): ?><option value="<?=$p['id']?>" data-dir="<?=$p['direction']?>"><?=h($p['last_name'].' '.$p['first_name'])?> · <?=($p['direction']=='kids'?'KIDS':'ADULT')?></option><? endforeach ?>
				</select></div>
				<div class="form_im form_im_f"><label class="form_l">Тариф <em>*</em></label><select class="form_im_txt ss_plan" name="plan_id"><option value="">Таңдаңыз</option>
					<? foreach ($plans as $pl): if (!$pl['active']) continue; ?><option value="<?=$pl['id']?>" data-dir="<?=$pl['direction']?>" data-price="<?=(float) $pl['price']?>"><?=h($pl['name'])?> · <?=fun::money($pl['price'])?> · <?=($pl['sessions_limit'] ?: '∞')?> жаттығу</option><? endforeach ?>
				</select></div>
				<div class="form_im form_im_f"><label class="form_l">Жеңілдік, ₸</label><input type="number" class="form_im_txt" name="discount" min="0" value="0"></div>
				<div class="form_im form_im_f"><p class="form_e" style="color:#6e6e73">Абонемент алғашқы расталған төлем күнінен басталады және 1 күнтізбелік айға жарамды.</p></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ss_save">Сату</div></div>
		</div>
	</div>

	<!-- заморозка -->
	<div class="pop_bl sub_freeze">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Заморозка · <span class="fz_n"></span></h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g fz_form" onsubmit="return false">
				<input type="hidden" name="id">
				<div class="form_im"><label class="form_l">Басталуы</label><input type="date" class="form_im_txt" name="start" value="<?=$date?>"></div>
				<div class="form_im"><label class="form_l">Күн (<span class="fz_max"></span> қалды)</label><input type="number" class="form_im_txt" name="days" min="1" max="7" value="1"></div>
				<div class="form_im form_im_f"><label class="form_l">Себебі</label><input type="text" class="form_im_txt" name="reason" maxlength="255" placeholder="Ауырып қалды, сапар..."></div>
				<div class="form_im form_im_f"><label style="display:flex;gap:8px;align-items:center;cursor:pointer"><input type="checkbox" name="approve" value="1" checked> Бірден бекіту</label></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn fz_save">Сақтау</div></div>
		</div>
	</div>

	<!-- тарифтер -->
	<div class="pop_bl pop_bl2 plan_list">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c" style="padding:28px;overflow-y:auto">
			<div class="pop_h"><h5>Тарифтер және Kaspi RED</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<? foreach ($plans as $pl): ?>
				<div class="li" style="<?=($pl['active']?'':'opacity:.5')?>">
					<div class="li_i"><i class="fal fa-tag"></i></div>
					<div class="li_c"><b><?=h($pl['name'])?> · <?=fun::money($pl['price'])?></b><span><?=t::w($pl['direction'])?> · <?=($pl['sessions_limit'] ?: '∞')?> жаттығу · заморозка <?=$pl['freeze_days']?> күн</span></div>
					<div class="li_r"><span class="bdg pl_fee" data-id="<?=$pl['id']?>" data-v="<?=h($pl['red_fee'])?>" style="cursor:pointer">RED <?=($pl['red_fee'] !== null ? (float) $pl['red_fee'].'%' : 'жалпы')?></span></div>
				</div>
			<? endforeach ?>
			<div class="li"><div class="li_i"><i class="fal fa-percent"></i></div><div class="li_c"><b>Kaspi RED жалпы комиссиясы</b><span>тарифке бөлек көрсетілмесе қолданылады</span></div><div class="li_r"><span class="bdg bdg_dk pl_fee" data-id="0" data-v="<?=h($red_fee)?>" style="cursor:pointer"><?=(float) $red_fee?>%</span></div></div>
			<div class="form_sep" style="margin-top:16px">Жаңа тариф</div>
			<form class="form_g pl_form" onsubmit="return false" style="margin-top:12px">
				<div class="form_im form_im_f"><input type="text" class="form_im_txt" name="name" maxlength="80" placeholder="Атауы, мысалы KIDS 8"></div>
				<div class="form_im"><select class="form_im_txt" name="direction"><option value="kids">ZHASS KIDS</option><option value="adult">ZHASS ADULT</option></select></div>
				<div class="form_im"><input type="number" class="form_im_txt" name="price" min="0" placeholder="Бағасы, ₸"></div>
				<div class="form_im"><input type="number" class="form_im_txt" name="sessions_limit" min="1" max="100" placeholder="Жаттығу (бос = ∞)"></div>
				<div class="form_im"><input type="number" class="form_im_txt" name="freeze_days" min="0" max="7" value="7" placeholder="Заморозка күні"></div>
			</form>
			<div class="form_btns"><div class="btn pl_save">Қосу</div></div>
		</div>
	</div>

<? include "../block/footer.php"; ?>
