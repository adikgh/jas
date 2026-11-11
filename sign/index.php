<? include "../config/core.php";

	// 
	if ($user_id) { header('location: /'); exit(); }

	// site setting
	$menu_name = 'sign';
	$menu_title = 'Кіру';
	$site_set['header'] = false;
	$site_set['menu'] = false;
	$site_set['pmenu'] = false;
	$css = ['sign'];
	$js = ['sign'];
?>
<? include "../block/header.php"; ?>

	<div class="u_sign">
		<div class="usign_c">

			<div class="usign_logo">
				<div class="usign_logo_i">JAS</div>
				<div class="usign_logo_t">ACADEMY</div>
			</div>
			<div class="usign_head">
				<h3 class="usign_h">Платформаға кіру</h3>
				<p>JAS KIDS · JAS ADULT · FIRST TEAM</p>
			</div>

			<form class="usign_cn" onsubmit="return false">
				<div class="form_im">
					<i class="far fa-mobile form_icon"></i>
					<input type="tel" class="form_im_txt fr_phone phone" placeholder="8 (700) 000-00-00" data-lenght="11" data-sel="0" autocomplete="username" />
				</div>
				<div class="form_im">
					<i class="far fa-lock form_icon"></i>
					<input type="password" class="form_im_txt password" placeholder="Құпия сөз" data-lenght="6" data-sel="0" data-eye="0" autocomplete="current-password" />
					<i class="far fa-eye-slash form_icon_pass"></i>
				</div>
				<div class="form_im">
					<button class="btn btn_sign_in" type="submit"><span>Кіру</span><i class="far fa-long-arrow-right"></i></button>
				</div>
				<div class="usign_help">Құпия сөзді ұмытсаңыз, академия әкімшісіне хабарласыңыз.</div>
			</form>

		</div>
	</div>

<? include "../block/footer.php"; ?>
