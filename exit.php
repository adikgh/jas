<? include "config/core.php";

	if ($user_id) fun::audit('logout', 'users', $user_id);
	$core->user_unset();
	session_regenerate_id(true);
	header('location: /sign/');
