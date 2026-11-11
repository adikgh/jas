<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach', 'parent', 'participant']);


	// бір хабарламаны оқылды деп белгілеу (тек өзінікі)
	if (isset($_GET['read'])) {
		db::q("update notifications set read_at = now() where id = ? and user_id = ? and organization_id = ? and read_at is null", [(int) fun::post('id'), $user_id, $org_id]);
		fun::json(['ok' => true]);
	}

	if (isset($_GET['read_all'])) {
		db::q("update notifications set read_at = now() where user_id = ? and organization_id = ? and read_at is null", [$user_id, $org_id]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
