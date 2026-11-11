<? include "../config/core.php";

	// барлық рөл кіре алады, бірақ тек өзіне рұқсат етілген қатысушыларды көреді
	core::need_page();
	$fin = core::is(['owner', 'admin']);
	list($sw, $sp) = fun::scope();


	// сүзгі
	$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
	$f_dir = isset($_GET['dir']) && in_array($_GET['dir'], ['kids', 'adult']) ? $_GET['dir'] : '';
	$f_group = isset($_GET['group']) ? (int) $_GET['group'] : 0;
	$f_st = isset($_GET['st']) && in_array($_GET['st'], ['active', 'paused', 'left', 'archive', 'all']) ? $_GET['st'] : 'active';
	$f_debt = !empty($_GET['debt']) && $fin;

	$w = ["p.organization_id = ?", $sw]; $wp = array_merge([$org_id], $sp);
	if ($f_st == 'archive') $w[] = "p.archived_at is not null";
	else {
		$w[] = "p.archived_at is null";
		if ($f_st != 'all') { $w[] = "p.status = ?"; $wp[] = $f_st; }
	}
	if ($f_dir) { $w[] = "p.direction = ?"; $wp[] = $f_dir; }
	if ($f_group) { $w[] = "e.group_id = ?"; $wp[] = $f_group; }
	if ($q !== '') {
		$qq = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
		$qd = preg_replace('/\D/', '', $q);
		$w[] = "(concat(p.last_name, ' ', p.first_name) like ? or concat(p.first_name, ' ', p.last_name) like ?".($qd ? " or p.phone like ? or exists (select 1 from participant_guardians pg2 join guardians g2 on g2.id = pg2.guardian_id where pg2.participant_id = p.id and g2.phone like ?)" : "").")";
		$wp[] = $qq; $wp[] = $qq;
		if ($qd) { $wp[] = '%'.$qd.'%'; $wp[] = '%'.$qd.'%'; }
	}
	if ($f_debt) $w[] = "dd.debt > 0";
	$where = implode(' and ', $w);

	$from = "from participants p
		left join enrollments e on e.participant_id = p.id and e.end_date is null
		left join `groups` g on g.id = e.group_id
		left join users u on u.id = g.coach_id
		left join (select participant_id, sum(debt) debt from (".fun::debt_sql().") d0 group by participant_id) dd on dd.participant_id = p.id";

	// page number
	$page_result = (int) db::val("select count(*) $from where $where", $wp);
	$page_age = 30;
	$page_all = max(1, (int) ceil($page_result / $page_age));
	$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
	if ($page > $page_all) $page = $page_all;
	$page_start = ($page - 1) * $page_age;
	$number = $page_start;

	$list = db::all("select p.*, g.name group_name, u.name coach_name, coalesce(dd.debt, 0) debt,
			(select gd.phone from participant_guardians pg join guardians gd on gd.id = pg.guardian_id where pg.participant_id = p.id order by pg.is_primary desc, pg.id limit 1) guardian_phone,
			(select concat(s.status, '|', coalesce(s.end_date, '')) from subscriptions s where s.participant_id = p.id order by s.id desc limit 1) sub
		$from where $where order by p.last_name, p.first_name limit $page_start, $page_age", $wp);

	// сүзгі үшін топтар
	$groups_all = db::all("select g.*, u.name coach_name from `groups` g left join users u on u.id = g.coach_id where g.organization_id = ? and g.active = 1".(core::is(['owner', 'admin']) ? "" : " and g.coach_id = ?")." order by g.direction, g.name",
		core::is(['owner', 'admin']) ? [$org_id] : [$org_id, $user_id]);

	$qs = $_GET; unset($qs['page'], $qs['add']);
	$page_link = '?'.http_build_query($qs).($qs ? '&' : '').'page=';


	// site setting
	$menu_name = 'participants';
	$menu_title = 'Қатысушылар';
	$js = ['participants'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="head_c">
			<div>
				<h4>Қатысушылар</h4>
				<p>JAS KIDS · JAS ADULT · FIRST TEAM</p>
			</div>
			<? if ($fin): ?>
				<div class="btn btn_sm" data-pop="part_add"><i class="fal fa-user-plus"></i><span>Қатысушы қосу</span></div>
			<? endif ?>
		</div>

		<!-- сүзгі -->
		<form class="flt" method="get">
			<div class="form_im form_im_s">
				<i class="fal fa-search form_icon"></i>
				<input type="text" class="form_im_txt" name="q" value="<?=h($q)?>" placeholder="Аты-жөні немесе телефон" />
			</div>
			<div class="form_im">
				<select class="form_im_txt flt_auto" name="dir">
					<option value="">Барлық бағыт</option>
					<option value="kids" <?=($f_dir=='kids'?'selected':'')?>>JAS KIDS</option>
					<option value="adult" <?=($f_dir=='adult'?'selected':'')?>>JAS ADULT</option>
				</select>
			</div>
			<div class="form_im">
				<select class="form_im_txt flt_auto" name="group">
					<option value="">Барлық топ</option>
					<? foreach ($groups_all as $g): ?><option value="<?=$g['id']?>" <?=($f_group==$g['id']?'selected':'')?>><?=h($g['name'])?></option><? endforeach ?>
				</select>
			</div>
			<div class="form_im">
				<select class="form_im_txt flt_auto" name="st">
					<option value="active" <?=($f_st=='active'?'selected':'')?>>Белсенді</option>
					<option value="paused" <?=($f_st=='paused'?'selected':'')?>>Үзіліс</option>
					<option value="left" <?=($f_st=='left'?'selected':'')?>>Кеткендер</option>
					<option value="archive" <?=($f_st=='archive'?'selected':'')?>>Архив</option>
					<option value="all" <?=($f_st=='all'?'selected':'')?>>Барлығы</option>
				</select>
			</div>
			<? if ($fin): ?>
				<div class="form_im">
					<select class="form_im_txt flt_auto" name="debt">
						<option value="">Қарыз: бәрі</option>
						<option value="1" <?=($f_debt?'selected':'')?>>Тек қарызы барлар</option>
					</select>
				</div>
			<? endif ?>
		</form>

		<div class="uc_tot">Барлығы: <?=$page_result?> қатысушы<?=($page_all>1?' · бет '.$page.'/'.$page_all:'')?></div>

		<!-- list -->
		<div class="uc_u">
			<div class="uc_uh">
				<div>#</div>
				<div>Аты-жөні</div>
				<div>Тобы</div>
				<div>Телефон</div>
				<div>Абонемент</div>
				<div><?=($fin?'Қарыз':'')?></div>
				<div></div>
			</div>
			<? if ($list): ?>
				<? foreach ($list as $p): ?>
					<? $number++; $sub = $p['sub'] ? explode('|', $p['sub']) : null; ?>
					<div class="uc_ui">
						<a class="uc_ui_img <?=($p['img']?'lazy_img':'')?>" href="item.php?id=<?=$p['id']?>" <?=($p['img']?'data-src="/assets/uploads/participants/'.h($p['img']).'"':'')?>><?=($p['img']?'':h(mb_substr($p['first_name'],0,1).mb_substr($p['last_name'],0,1)))?></a>
						<a class="uc_ui_n" href="item.php?id=<?=$p['id']?>">
							<b><?=h(fun::full_name($p))?> <?=($p['number']?'<span class="bdg" style="display:inline-flex">#'.(int)$p['number'].'</span>':'')?></b>
							<span><?=fun::age($p['birth_date'])?> жас · <span class="bdg bdg_<?=$p['direction']?>" style="display:inline-flex"><?=t::w($p['direction'])?></span> <?=($p['position']?'· '.t::w($p['position']):'')?></span>
						</a>
						<div class="uc_ui_o uc_ui_om"><?=h($p['group_name'] ?: '—')?><span><?=h($p['coach_name'])?></span></div>
						<div class="uc_ui_o"><?=h(fun::phone_view($p['phone'] ?: $p['guardian_phone']))?><span><?=($p['phone']?'өзі':($p['guardian_phone']?'ата-анасы':''))?></span></div>
						<div class="uc_ui_o">
							<? if ($sub): ?>
								<span class="bdg <?=($sub[0]=='active'?'bdg_ok':($sub[0]=='pending'?'bdg_warn':''))?>"><?=t::w('sub_'.$sub[0])?></span>
								<? if ($sub[1]): ?><span><?=date('d.m.Y', strtotime($sub[1]))?> дейін</span><? endif ?>
							<? else: ?> <span>жоқ</span> <? endif ?>
						</div>
						<div class="uc_ui_o"><?=($fin ? ($p['debt'] > 0 ? '<b class="cl_err">'.fun::money($p['debt']).'</b>' : '<span>—</span>') : '')?></div>
						<div class="uc_uib">
							<div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
							<div class="menu_c">
								<a class="menu_ci" href="item.php?id=<?=$p['id']?>"><i class="fal fa-user"></i>Профиль</a>
								<a class="menu_ci" href="item.php?id=<?=$p['id']?>#attendance"><i class="fal fa-clipboard-check"></i>Қатысу тарихы</a>
								<? if ($fin): ?>
									<a class="menu_ci" href="item.php?id=<?=$p['id']?>#payments"><i class="fal fa-wallet"></i>Төлем тарихы</a>
									<? if ($p['archived_at']): ?>
										<div class="menu_ci part_restore" data-id="<?=$p['id']?>"><i class="fal fa-undo"></i>Қалпына келтіру</div>
									<? else: ?>
										<div class="menu_ci menu_ci_red part_archive" data-id="<?=$p['id']?>"><i class="fal fa-archive"></i>Архивке жіберу</div>
									<? endif ?>
								<? endif ?>
							</div>
						</div>
					</div>
				<? endforeach ?>
			<? else: ?>
				<div class="ds_nr"><i class="fal fa-user-friends"></i><p><?=($q||$f_dir||$f_group?'Сүзгі бойынша ешкім табылмады':'Әзірге қатысушы жоқ')?></p>
					<? if ($fin && !$q): ?><div class="btn btn_sm" data-pop="part_add"><i class="fal fa-user-plus"></i><span>Алғашқы қатысушыны қосу</span></div><? endif ?>
				</div>
			<? endif ?>
		</div>

		<? include "../block/pagination.php"; ?>
		<div style="height:40px"></div>
	</div>

	<? if ($fin) { $fp = []; include "form.php"; } ?>

<? include "../block/footer.php"; ?>
