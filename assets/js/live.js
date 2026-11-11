// start jquery
$(document).ready(function() {
	function post(url, d, ok) { $.post(url, d, function(r){ if (r.ok) { mess(ok); setTimeout(function(){ location.reload() }, 600) } else mess(r.mess || 'Қате') }, 'json') }
	$('.lv_save').on('click', function(){ var d = {}; $('.lv_form [name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); post('/live/get.php?add', d, 'Эфир қосылды') })
	$('.lv_st').on('click', function(){ post('/live/get.php?status', { id: $(this).attr('data-id'), status: $(this).attr('data-s') }, 'Сақталды') })
}) // end jquery
