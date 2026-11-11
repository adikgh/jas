<? include "../config/core.php";

	core::need_page(['owner', 'admin']);

	$ym = isset($_GET['m']) && preg_match('/^\d{4}-\d{2}$/', $_GET['m']) ? $_GET['m'] : date('Y-m');
	$m_start = "$ym-01"; $m_end = date('Y-m-t', strtotime($m_start));
	$f_st = isset($_GET['st']) && in_array($_GET['st'], ['pending', 'confirmed', 'rejected']) ? $_GET['st'] : '';
	$f_method = isset($_GET['method']) && in_array($_GET['method'], ['kaspi', 'kaspi_red', 'bank', 'cash']) ? $_GET['method'] : '';
	$f_q = isset($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 60)) : '';

	// расталмағандар айға қарамай әрқашан көрінеді
	$w = ["py.organization_id = ?"]; $wp = [$org_id];
	if ($f_st == 'pending') $w[] = "py.status = 'pending'";
	else { $w[] = "py.paid_at between ? and ?"; $wp[] = "$m_start 00:00:00"; $wp[] = "$m_end 23:59:59"; if ($f_st) { $w[] = "py.status = ?"; $wp[] = $f_st; } }
	if ($f_method) { $w[] = "py.method = ?"; $wp[] = $f_method; }
	if ($f_q !== '') { $w[] = "(concat(p.last_name, ' ', p.first_name) like ? or py.external_ref like ?)"; $wp[] = '%'.$f_q.'%'; $wp[] = '%'.$f_q.'%'; }
	$pays = db::all("select py.*, p.first_name, p.last_name, ue.name entered_name, uc.name confirmed_name,
			(select r.id from payments r where r.reversal_of_id = py.id) reversed_id,
			(select group_concat(pl.name separator ', ') from payment_allocations pa join subscriptions s on s.id = pa.subscription_id join plans pl on pl.id = s.plan_id where pa.payment_id = py.id) plans
		from payments py join participants p on p.id = py.participant_id left join users ue on ue.id = py.entered_by left join users uc on uc.id = py.confirmed_by
		where ".implode(' and ', $w)." order by py.paid_at desc, py.id desc limit 300", $wp);

	// айлық есеп
	$inc = db::row("select coalesce(sum(amount), 0) amount, coalesce(sum(fee_amount), 0) fee from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ?", [$org_id, "$m_start 00:00:00", "$m_end 23:59:59"]);
	$by_m = [];
	foreach (db::all("select method, sum(amount) s from payments where organization_id = ? and status = 'confirmed' and paid_at between ? and ? group by method", [$org_id, "$m_start 00:00:00", "$m_end 23:59:59"]) as $r) $by_m[$r['method']] = (float) $r['s'];
	$expenses = db::all("select e.*, u.name user_name from expenses e left join users u on u.id = e.created_by where e.organization_id = ? and e.spent_at between ? and ? order by e.spent_at desc, e.id desc", [$org_id, $m_start, $m_end]);
	$exp = array_sum(array_column($expenses, 'amount'));
	$net = $inc['amount'] - $inc['fee'] - $exp;
	$pending = db::row("select count(*) c, coalesce(sum(amount), 0) s from payments where organization_id = ? and status = 'pending'", [$org_id]);

	$parts = db::all("select id, first_name, last_name, direction from participants where organization_id = ? and archived_at is null order by last_name, first_name", [$org_id]);
	$add_sub = isset($_GET['add']) ? db::row("select id, participant_id from subscriptions where id = ? and organization_id = ?", [(int) $_GET['add'], $org_id]) : null;
	$exp_cat = ['rent' => 'Алаң жалдау', 'salary' => 'Айлық', 'equipment' => 'Құрал-жабдық', 'marketing' => 'Маркетинг', 'tournament' => 'Турнир', 'other' => 'Басқа'];
	$kind = ['payment' => '', 'refund' => 'қайтару', 'reversal' => 'сторно', 'adjustment' => 'түзету'];
	$qs = $_GET; unset($qs['m']);
	$ml = function ($m) use ($qs) { return '?'.http_build_query(array_merge($qs, ['m' => $m])); };


	// site setting
	$menu_name = 'payments';
	$menu_title = 'Төлемдер';
	$css = ['schedule'];
	$js = ['payments'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div><h4>Төлемдер</h4><p><?=fun::$months[(int) substr($ym, 5)]?> <?=substr($ym, 0, 4)?> · қолмен енгізу (банк интеграциясы кейін)</p></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<div class="btn btn_sm btn_ghost" data-pop="exp_add"><i class="fal fa-minus-circle"></i><span>Шығын</span></div>
				<div class="btn btn_sm pay_open"><i class="fal fa-plus"></i><span>Төлем енгізу</span></div>
			</div>
		</div>

		<div class="sch_top">
			<div class="sch_nav">
				<a class="btn btn_sm btn_ghost" href="<?=h($ml(date('Y-m', strtotime("$m_start -1 month"))))?>"><i class="fal fa-angle-left"></i></a>
				<a class="btn btn_sm btn_ghost" href="<?=h($ml(date('Y-m')))?>">Осы ай</a>
				<a class="btn btn_sm btn_ghost" href="<?=h($ml(date('Y-m', strtotime("$m_start +1 month"))))?>"><i class="fal fa-angle-right"></i></a>
			</div>
		</div>

		<div class="dsh_k">
			<div class="card dsh_ki dsh_ki_dk"><div class="dsh_ki_t"><i class="fal fa-arrow-down"></i>Кіріс</div><div class="dsh_ki_n"><?=fun::money($inc['amount'])?></div><div class="dsh_ki_s"><? foreach ($by_m as $k => $v): ?><?=t::w($k)?> <?=fun::money($v)?> · <? endforeach ?></div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-percent"></i>RED комиссия</div><div class="dsh_ki_n"><?=fun::money($inc['fee'])?></div><div class="dsh_ki_s">академия шығыны</div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-arrow-up"></i>Шығын</div><div class="dsh_ki_n"><?=fun::money($exp)?></div><div class="dsh_ki_s"><?=count($expenses)?> жазба</div></div>
			<div class="card dsh_ki"><div class="dsh_ki_t"><i class="fal fa-coins"></i>Таза нәтиже</div><div class="dsh_ki_n <?=($net>=0?'cl_ok':'cl_err')?>"><?=fun::money($net)?></div><a class="dsh_ki_s" href="?st=pending"><?=$pending['c']?> расталмаған · <?=fun::money($pending['s'])?></a></div>
		</div>

		<div class="tab" style="margin-top:16px">
			<div class="tab_i tab_i_act" data-tab="pays">Төлемдер</div>
			<div class="tab_i" data-tab="exp">Шығындар</div>
		</div>

		<div class="tab_c tab_c_act" data-tab="pays">
			<form class="flt" method="get">
				<input type="hidden" name="m" value="<?=h($ym)?>">
				<div class="form_im"><input type="text" class="form_im_txt" name="q" value="<?=h($f_q)?>" placeholder="Аты-жөні немесе операция №"></div>
				<div class="form_im"><select class="form_im_txt flt_auto" name="st"><option value="">Барлық мәртебе</option><? foreach (['pending', 'confirmed', 'rejected'] as $k): ?><option value="<?=$k?>" <?=($f_st==$k?'selected':'')?>><?=t::w('pay_'.$k)?></option><? endforeach ?></select></div>
				<div class="form_im"><select class="form_im_txt flt_auto" name="method"><option value="">Барлық әдіс</option><? foreach (['kaspi', 'kaspi_red', 'bank', 'cash'] as $k): ?><option value="<?=$k?>" <?=($f_method==$k?'selected':'')?>><?=t::w($k)?></option><? endforeach ?></select></div>
			</form>
			<div class="card" style="margin-top:12px">
				<? if ($pays): ?>
					<div class="tbl_w"><table class="tbl">
						<tr><th>Күні</th><th>Қатысушы</th><th>Сома</th><th>Әдісі / №</th><th>Мәртебе</th><th>Енгізген</th><th></th></tr>
						<? foreach ($pays as $py): ?>
							<tr style="<?=($py['status']=='rejected'||$py['reversed_id']?'opacity:.55':'')?>">
								<td><?=date('d.m.Y H:i', strtotime($py['paid_at']))?></td>
								<td><a href="/participants/item.php?id=<?=$py['participant_id']?>#payments"><b><?=h($py['last_name'].' '.$py['first_name'])?></b></a><br><small><?=h($py['plans'])?></small></td>
								<td class="<?=($py['amount']<0?'cl_err':'')?>"><b><?=fun::money($py['amount'])?></b><?=($kind[$py['kind']]?' <span class="bdg">'.$kind[$py['kind']].'</span>':'')?><?=($py['fee_amount']!=0?'<br><small>комиссия '.fun::money($py['fee_amount']).'</small>':'')?></td>
								<td><?=t::w($py['method'])?><br><small><?=h($py['external_ref'] ?: '—')?></small></td>
								<td><span class="bdg <?=($py['status']=='confirmed'?'bdg_ok':($py['status']=='pending'?'bdg_warn':'bdg_err'))?>"><?=t::w('pay_'.$py['status'])?></span><?=($py['reversed_id']?'<br><small>сторноланған</small>':'')?></td>
								<td><?=h($py['entered_name'])?><br><small><?=date('d.m H:i', strtotime($py['entered_at']))?><?=($py['confirmed_name']?' · ✓ '.h($py['confirmed_name']):'')?></small><?=($py['comment']?'<br><small>'.h($py['comment']).'</small>':'')?></td>
								<td style="white-space:nowrap">
									<? if ($py['status'] == 'pending'): ?>
										<span class="bdg bdg_ok py_act" data-a="confirm" data-id="<?=$py['id']?>" style="cursor:pointer">Растау</span>
										<span class="bdg bdg_err py_act" data-a="reject" data-id="<?=$py['id']?>" style="cursor:pointer">Қабылдамау</span>
									<? elseif ($py['status'] == 'confirmed' && $py['kind'] == 'payment' && !$py['reversed_id']): ?>
										<span class="bdg py_act" data-a="reverse" data-id="<?=$py['id']?>" style="cursor:pointer" title="Қате енгізілсе — сторно (өшірілмейді)"><i class="fal fa-undo"></i> Сторно</span>
									<? endif ?>
								</td>
							</tr>
						<? endforeach ?>
					</table></div>
				<? else: ?><div class="ds_nr ds_nr_sm"><i class="fal fa-wallet"></i><p>Төлем жоқ</p></div><? endif ?>
			</div>
		</div>

		<div class="tab_c" data-tab="exp">
			<div class="card">
				<div class="card_h"><h6>Шығындар · <?=fun::$months[(int) substr($ym, 5)]?></h6><b><?=fun::money($exp)?></b></div>
				<? foreach ($expenses as $e): ?>
					<div class="li">
						<div class="li_i"><?=date('d.m', strtotime($e['spent_at']))?></div>
						<div class="li_c"><b><?=$exp_cat[$e['category']]?></b><span><?=h($e['comment'])?> · <?=h($e['user_name'])?></span></div>
						<div class="li_r cl_err"><?=fun::money($e['amount'])?></div>
					</div>
				<? endforeach ?>
				<? if (!$expenses): ?><div class="ds_nr ds_nr_sm"><i class="fal fa-receipt"></i><p>Шығын жоқ</p></div><? endif ?>
			</div>
		</div>

		<div style="height:40px"></div>
	</div>

	<!-- төлем енгізу -->
	<div class="pop_bl pay_add" data-sub="<?=($add_sub?$add_sub['id']:'')?>" data-part="<?=($add_sub?$add_sub['participant_id']:'')?>">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Төлем енгізу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g pa_form" onsubmit="return false">
				<input type="hidden" name="idem">
				<div class="form_im form_im_f"><label class="form_l">Қатысушы <em>*</em></label><select class="form_im_txt pa_part" name="participant_id"><option value="">Таңдаңыз</option>
					<? foreach ($parts as $p): ?><option value="<?=$p['id']?>"><?=h($p['last_name'].' '.$p['first_name'])?></option><? endforeach ?>
				</select></div>
				<div class="form_im form_im_f"><label class="form_l">Абонемент <em>*</em></label><select class="form_im_txt pa_sub" name="subscription_id"><option value="">Алдымен қатысушыны таңдаңыз</option></select></div>
				<div class="form_im"><label class="form_l">Сома, ₸ <em>*</em></label><input type="number" class="form_im_txt pa_amount" name="amount" min="1" step="1"></div>
				<div class="form_im"><label class="form_l">Әдісі <em>*</em></label><select class="form_im_txt" name="method"><? foreach (['kaspi', 'kaspi_red', 'bank', 'cash'] as $k): ?><option value="<?=$k?>"><?=t::w($k)?></option><? endforeach ?></select></div>
				<div class="form_im"><label class="form_l">Операция №</label><input type="text" class="form_im_txt" name="external_ref" maxlength="80" placeholder="Kaspi чек нөмірі"></div>
				<div class="form_im"><label class="form_l">Төленген уақыты</label><input type="datetime-local" class="form_im_txt" name="paid_at" value="<?=date('Y-m-d\TH:i')?>"></div>
				<div class="form_im form_im_f"><label class="form_l">Мәртебе</label><select class="form_im_txt" name="status"><option value="confirmed">Расталды (ақша түскенін тексердім)</option><option value="pending">Расталмаған (кейін тексеремін)</option></select></div>
				<div class="form_im form_im_f"><label class="form_l">Түсініктеме</label><input type="text" class="form_im_txt" name="comment" maxlength="255"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn pa_save">Сақтау</div></div>
		</div>
	</div>

	<!-- шығын -->
	<div class="pop_bl exp_add">
		<div class="pop_bl_a pop_back"></div>
		<div class="pop_bl_c">
			<div class="pop_h"><h5>Шығын қосу</h5><div class="pop_x pop_back"><i class="fal fa-times"></i></div></div>
			<form class="form_g ex_form" onsubmit="return false">
				<div class="form_im"><label class="form_l">Санаты</label><select class="form_im_txt" name="category"><? foreach ($exp_cat as $k => $v): ?><option value="<?=$k?>"><?=$v?></option><? endforeach ?></select></div>
				<div class="form_im"><label class="form_l">Сома, ₸</label><input type="number" class="form_im_txt" name="amount" min="1"></div>
				<div class="form_im form_im_f"><label class="form_l">Күні</label><input type="date" class="form_im_txt" name="spent_at" value="<?=$date?>"></div>
				<div class="form_im form_im_f"><label class="form_l">Түсініктеме</label><input type="text" class="form_im_txt" name="comment" maxlength="255"></div>
			</form>
			<div class="form_btns"><div class="btn btn_ghost pop_back">Болдырмау</div><div class="btn ex_save">Қосу</div></div>
		</div>
	</div>

<? include "../block/footer.php"; ?>
