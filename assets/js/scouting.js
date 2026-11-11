// start jquery
$(document).ready(function() {
	function form_data(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, data, btn, ok, go) {
		$.ajax({
			url: url, type: 'POST', dataType: 'json', data: data,
			beforeSend: function(){ if (btn) btn.addClass('btn_ds') },
			success: function(r){
				if (btn) btn.removeClass('btn_ds')
				if (r.ok) { mess(typeof ok == 'function' ? ok(r) : ok); setTimeout(function(){ location.href = go ? go(r) : location.href }, 600) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds') }
		})
	}
	$('.cp_save').on('click', function(){ post('/scouting/get.php?campaign_add', form_data($('.cp_form')), $(this), 'Құрылды', function(r){ return '/scouting/?id=' + r.id }) })
	$('.cd_save').on('click', function(){ post('/scouting/get.php?candidate_add', form_data($('.cd_form')), $(this), 'Қосылды') })
	$('.sc_st').on('change', function(){
		var st = $(this).val()
		if (st == 'accepted' && !confirm('Қабылдау керек пе? Бірінші құрам іріктеуі болса, футболшы құрамға қосылады.')) return
		post('/scouting/get.php?candidate_status', { id: $(this).attr('data-id'), status: st }, null, function(r){ return r.roster ? 'Қабылданды, құрамға қосылды' : 'Сақталды' })
	})
	$('.sc_close').on('click', function(e){ e.preventDefault(); post('/scouting/get.php?campaign_status', { id: $(this).attr('data-id'), status: $(this).attr('data-st') }, null, 'Сақталды') })
}) // end jquery
