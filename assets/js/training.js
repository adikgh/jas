// start jquery
$(document).ready(function() {
	function fd(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, d, ok) { $.post(url, d, function(r){ if (r.ok) { mess(ok); setTimeout(function(){ location.reload() }, 600) } else mess(r.mess || 'Қате') }, 'json') }
	$('.ls_save').on('click', function(){ post('/training/get.php?lesson_add', fd($('.ls_form')), 'Жаттығу қосылды') })
	$('.as2_save').on('click', function(){ post('/training/get.php?assign', fd($('.as2_form')), 'Тапсырма берілді') })
	$('.tr_done').on('click', function(){
		var c = prompt('«' + $(this).attr('data-t') + '» — қалай орындадыңыз? (қаласаңыз YouTube видео сілтемесін келесі терезеде қосыңыз)')
		if (c === null) return
		var v = prompt('Видео сілтемесі (міндетті емес):', '') || ''
		post('/training/get.php?submit', { id: $(this).attr('data-id'), participant_id: $(this).attr('data-pid'), comment: c, video: v }, 'Жіберілді')
	})
	$('.tr_rev').on('click', function(){
		var c = prompt('Пікір (міндетті емес):', '')
		if (c === null) return
		post('/training/get.php?review', { id: $(this).attr('data-id'), status: $(this).attr('data-s'), comment: c }, 'Сақталды')
	})
}) // end jquery
