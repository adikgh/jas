// start jquery
$(document).ready(function() {
	function done(r, ok) { if (r.ok) { mess(ok); setTimeout(function(){ location.reload() }, 600) } else mess(r.mess || 'Қате') }
	$('.al_save').on('click', function(){
		var d = {}; $('.al_form [name]').each(function(){ d[$(this).attr('name')] = $(this).val() })
		$.post('/media/get.php?album_add', d, function(r){ done(r, 'Альбом құрылды') }, 'json')
	})
	$('.up_save').on('click', function(){
		var btn = $(this), fd = new FormData($('.up_form')[0])
		$.ajax({ url: '/media/get.php?upload', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){ btn.removeClass('btn_ds'); done(r, (r.count || 0) + ' файл жүктелді') },
			error: function(){ btn.removeClass('btn_ds') } })
	})
	$('.md_del').on('click', function(){
		if (!confirm('Өшіру керек пе?')) return
		$.post('/media/get.php?delete', { id: $(this).attr('data-id') }, function(r){ done(r, 'Өшірілді') }, 'json')
	})
	$('.md_cons').on('change', function(){
		var el = $(this)
		$.post('/media/get.php?consent', { id: el.attr('data-id'), scope: el.attr('data-s'), on: el.is(':checked') ? 1 : 0 }, function(r){ mess(r.ok ? 'Сақталды' : (r.mess || 'Қате')) }, 'json')
	})
}) // end jquery
