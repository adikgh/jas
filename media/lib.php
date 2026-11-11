<?
	// медиа көру құқығы: staff — бәрі; ата-ана/қатысушы — public, parents, tagged (өз баласы белгіленген)
	function media_where() {
		global $org_id;
		if (core::is(['owner', 'admin', 'coach'])) return ["m.organization_id = ?", [$org_id]];
		list($sw, $sp) = fun::scope();
		return ["m.organization_id = ? and (m.visibility in ('public', 'parents') or (m.visibility = 'tagged' and exists (select 1 from media_asset_participants mp join participants p on p.id = mp.participant_id where mp.media_asset_id = m.id and $sw)))", array_merge([$org_id], $sp)];
	}
	function media_src($m) {
		if (strpos($m['storage_key'], 'yt:') === 0) return 'https://img.youtube.com/vi/'.substr($m['storage_key'], 3).'/hqdefault.jpg';
		return '/media/file.php?id='.(int) $m['id'];
	}
