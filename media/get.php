<? include "../config/core.php";

	core::need(['owner', 'admin', 'coach']);


	if (isset($_GET['album_add'])) {
		$name = fun::post('name'); $gid = (int) fun::post('group_id');
		if (mb_strlen($name) < 2) fun::json(['ok' => false, 'mess' => 'Атауын жазыңыз']);
		if ($gid && !db::val("select id from `groups` where id = ? and organization_id = ?", [$gid, $org_id])) fun::json(['ok' => false, 'mess' => 'Топ табылмады']);
		$id = db::ins("insert into media_albums (organization_id, name, group_id) values (?, ?, ?)", [$org_id, $name, $gid ?: null]);
		fun::json(['ok' => true, 'id' => $id]);
	}


	// жүктеу: белгіленген қатысушының келісімі тексеріледі
	if (isset($_GET['upload'])) {
		$album = (int) fun::post('album_id');
		$vis = fun::post('visibility'); $kind = fun::post('kind'); $title = fun::post('title');
		$tags = isset($_POST['tags']) && is_array($_POST['tags']) ? array_unique(array_map('intval', $_POST['tags'])) : [];
		$yt = fun::post('youtube') !== '' ? academy::yt_id(fun::post('youtube')) : null;
		if ($album && !db::val("select id from media_albums where id = ? and organization_id = ?", [$album, $org_id])) fun::json(['ok' => false, 'mess' => 'Альбом табылмады']);
		if (!in_array($vis, ['staff', 'tagged', 'parents', 'public'], true)) fun::json(['ok' => false, 'mess' => 'Көріну қате']);
		if (!in_array($kind, ['general', 'highlight', 'analysis'], true)) $kind = 'general';
		if (fun::post('youtube') !== '' && !$yt) fun::json(['ok' => false, 'mess' => 'YouTube сілтемесі қате']);
		if ($yt && db::val("select id from media_assets where organization_id = ? and storage_key = ?", [$org_id, 'yt:'.$yt])) fun::json(['ok' => false, 'mess' => 'Бұл видео медиатекада бар']);
		$files = [];
		if (!empty($_FILES['files']['name']) && is_array($_FILES['files']['name']))
			foreach ($_FILES['files']['name'] as $i => $n) if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE)
				$files[] = ['name' => $n, 'type' => $_FILES['files']['type'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'error' => $_FILES['files']['error'][$i], 'size' => $_FILES['files']['size'][$i]];
		if (!$files && !$yt) fun::json(['ok' => false, 'mess' => 'Фото таңдаңыз немесе YouTube сілтемесін қойыңыз']);
		if (count($files) > 30) fun::json(['ok' => false, 'mess' => 'Бір реттен 30 фотоға дейін']);

		if ($tags) {
			$valid = db::all("select id, first_name, last_name,
					(select count(*) from media_consents c where c.participant_id = p.id and c.scope = 'internal' and c.revoked_at is null) c_int,
					(select count(*) from media_consents c where c.participant_id = p.id and c.scope = 'social' and c.revoked_at is null) c_soc
				from participants p where p.organization_id = ? and p.id in (".implode(',', $tags).")", [$org_id]);
			if (count($valid) != count($tags)) fun::json(['ok' => false, 'mess' => 'Қатысушы табылмады']);
			$no = [];
			foreach ($valid as $v) if ($vis != 'staff' && (!$v['c_int'] || ($vis == 'public' && !$v['c_soc']))) $no[] = $v['first_name'].' '.$v['last_name'];
			if ($no) fun::json(['ok' => false, 'mess' => 'Ата-ана келісімі жоқ: '.implode(', ', $no).'. «Тек қызметкерлер» таңдаңыз немесе келісімді белгілеңіз.']);
		}

		$saved = [];
		foreach ($files as $f) {
			$name = fun::img_upload($f, __DIR__.'/../assets/uploads/media');
			if (!$name) fun::json(['ok' => false, 'mess' => 'Файл қате: '.$f['name'].' (jpg/png/webp, 5 МБ)', 'saved' => count($saved)]);
			$saved[] = ['photo', 'img:'.$name];
		}
		if ($yt) $saved[] = ['video', 'yt:'.$yt];
		db::tx(function () use ($saved, $album, $kind, $title, $vis, $tags, $org_id, $user_id) {
			foreach ($saved as $s) {
				$id = db::ins("insert into media_assets (organization_id, album_id, type, kind, storage_key, title, visibility, uploaded_by) values (?, ?, ?, ?, ?, ?, ?, ?)",
					[$org_id, $album ?: null, $s[0], $kind, $s[1], $title ?: null, $vis, $user_id]);
				foreach ($tags as $t) db::q("insert into media_asset_participants (media_asset_id, participant_id) values (?, ?)", [$id, $t]);
			}
		});
		fun::audit('upload', 'media_assets', null, null, ['count' => count($saved), 'visibility' => $vis]);
		fun::json(['ok' => true, 'count' => count($saved)]);
	}


	if (isset($_GET['delete'])) {
		core::need(['owner', 'admin']);
		$m = db::row("select * from media_assets where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		if (!$m) fun::json(['ok' => false, 'mess' => 'Табылмады']);
		db::tx(function () use ($m) {
			db::q("delete from media_asset_participants where media_asset_id = ?", [(int) $m['id']]);
			db::q("delete from media_assets where id = ?", [(int) $m['id']]);
		});
		if (strpos($m['storage_key'], 'img:') === 0) @unlink(__DIR__.'/../assets/uploads/media/'.basename(substr($m['storage_key'], 4)));
		fun::audit('delete', 'media_assets', $m['id'], ['key' => $m['storage_key']]);
		fun::json(['ok' => true]);
	}


	// келісім: беру / қайтарып алу (тарих сақталады)
	if (isset($_GET['consent'])) {
		core::need(['owner', 'admin']);
		$p = db::row("select id from participants where id = ? and organization_id = ?", [(int) fun::post('id'), $org_id]);
		$scope = fun::post('scope'); $on = fun::post('on') == '1';
		if (!$p || !in_array($scope, ['internal', 'social'], true)) fun::json(['ok' => false, 'mess' => 'Қате']);
		if ($on) {
			if (!db::val("select id from media_consents where participant_id = ? and scope = ? and revoked_at is null", [(int) $p['id'], $scope]))
				db::q("insert into media_consents (organization_id, participant_id, scope, granted_at, recorded_by) values (?, ?, ?, now(), ?)", [$org_id, (int) $p['id'], $scope, $user_id]);
		} else {
			db::q("update media_consents set revoked_at = now() where participant_id = ? and scope = ? and revoked_at is null", [(int) $p['id'], $scope]);
			// келісім алынса — ашық медиа «тек қызметкерлерге» ауысады
			if ($scope == 'internal') db::q("update media_assets m join media_asset_participants mp on mp.media_asset_id = m.id set m.visibility = 'staff' where mp.participant_id = ?", [(int) $p['id']]);
			else db::q("update media_assets m join media_asset_participants mp on mp.media_asset_id = m.id set m.visibility = 'parents' where mp.participant_id = ? and m.visibility = 'public'", [(int) $p['id']]);
		}
		fun::audit('consent_'.($on ? 'grant' : 'revoke'), 'participants', $p['id'], null, ['scope' => $scope]);
		fun::json(['ok' => true]);
	}

	fun::json(['ok' => false, 'mess' => 'action?']);
