// start jquery
$(document).ready(function() {
	function fd(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, d, ok, reload) { $.post(url, d, function(r){ if (r.ok) { mess(ok); if (reload !== false) setTimeout(function(){ location.reload() }, 600) } else mess(r.mess || 'Қате') }, 'json') }
	$('.ld_save').on('click', function(){ post('/crm/get.php?lead_add', fd($('.ld_form')), 'Өтінім қосылды') })
	$('.cm_save').on('click', function(){ post('/crm/get.php?campaign_add', fd($('.cm_form')), 'Кампания қосылды') })
	$('.ct_save').on('click', function(){ post('/crm/get.php?content_add', fd($('.ct_form')), 'Қосылды') })
	$('.cr_st').on('change', function(){
		var s = $(this).val()
		post('/crm/get.php?lead_status', { id: $(this).attr('data-id'), status: s }, 'Сақталды', false)
		if (s == 'converted' && confirm('Қатысушы ретінде тіркейік пе?')) location.href = '/participants/?add'
	})
	$('.cr_cst').on('change', function(){ post('/crm/get.php?content_status', { id: $(this).attr('data-id'), status: $(this).val() }, 'Сақталды', false) })
}) // end jquery
