<? include "../config/core.php";

	// sign in phone
	if (isset($_GET['sign_in'])) {
		$phone = fun::phone_clean(fun::post('phone'));
		$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		if (!$phone || $password === '') { echo 'phone'; exit(); }

		// brute-force: 15 минутта 5 қате (нөмір бойынша) немесе 30 қате (IP бойынша)
		$fail_ph = db::val("select count(*) from login_attempts where phone = ? and success = 0 and created_at > now() - interval 15 minute", [$phone]);
		$fail_ip = db::val("select count(*) from login_attempts where ip = ? and success = 0 and created_at > now() - interval 15 minute", [$ip]);
		if ($fail_ph >= 5 || $fail_ip >= 30) { echo 'limit'; exit(); }

		$user_d = db::row("select * from users where phone = ? and locked_at is null", [$phone]);
		$mem = $user_d ? db::row("select organization_id from memberships where user_id = ? and active = 1 order by id limit 1", [(int) $user_d['id']]) : null;
		if ($user_d && $mem && $user_d['password_hash'] && password_verify($password, $user_d['password_hash'])) {
			// басқа порталдың қолданушысы — дұрыс доменге жіберу
			if (core::$portal) {
				$all = array_column(db::all("select role from memberships where user_id = ? and organization_id = ? and active = 1", [(int) $user_d['id'], (int) $mem['organization_id']]), 'role');
				if (!array_intersect($all, core::$portals[core::$portal]['roles'])) { echo 'portal:'.core::portal_url(core::portal_for($all), '/sign/'); exit(); }
			}
			if (password_needs_rehash($user_d['password_hash'], PASSWORD_DEFAULT)) db::q("update users set password_hash = ? where id = ?", [password_hash($password, PASSWORD_DEFAULT), (int) $user_d['id']]);
			db::q("insert into login_attempts (phone, ip, success) values (?, ?, 1)", [$phone, $ip]);
			db::q("update users set last_login_at = now() where id = ?", [(int) $user_d['id']]);
			core::sign_in($user_d, (int) $mem['organization_id']);
			core::$user_data = $user_d; core::$org_id = (int) $mem['organization_id'];
			fun::audit('login', 'users', $user_d['id']);
			echo 'yes';
		} else {
			db::q("insert into login_attempts (phone, ip, success) values (?, ?, 0)", [$phone, $ip]);
			echo 'none';
		}
		exit();
	}
