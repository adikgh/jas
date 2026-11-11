<? include "config/core.php";

	if (!$user_id) header('location: /sign/');
	elseif (core::is(['owner', 'admin', 'coach'])) header('location: /dashboard/');
	else header('location: /cabinet/');
	exit();
