// start jquery
$(document).ready(function() {

	function form_data(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, data, btn, ok) {
		$.ajax({
			url: url, type: "POST", dataType: "json", data: data,
			beforeSend: function(){ if (btn) btn.addClass('btn_ds') },
			success: function(r){
				if (btn) btn.removeClass('btn_ds')
				if (r.ok) { mess(ok); setTimeout(function(){ location.reload() }, 500) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds') }
		})
	}

	$('.aw_save').on('click', function(){ post('/rating/get.php?award', form_data($('.aw_form')), $(this), 'Марапатталды') })
	$('.pr_save').on('click', function(){ post('/rating/get.php?progress', form_data($('.pr_form')), $(this), '+20 ұпай қосылды') })
	$('.mn_save').on('click', function(){ post('/rating/get.php?manual', form_data($('.mn_form')), $(this), 'Түзетілді') })
	$('.rt_rev').on('click', function(){
		if (!confirm('Бұл ұпайды қайтарып алу керек пе?')) return
		post('/rating/get.php?revoke', { id: $(this).attr('data-id') }, null, 'Қайтарылды')
	})

}) // end jquery
