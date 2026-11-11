// start jquery
$(document).ready(function() {

	function form_data(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, data, btn, ok, go) {
		$.ajax({
			url: url, type: "POST", dataType: "json", data: data,
			beforeSend: function(){ if (btn) btn.addClass('btn_ds') },
			success: function(r){
				if (btn) btn.removeClass('btn_ds')
				if (r.ok) { mess(ok); setTimeout(function(){ if (go) location.href = go(r); else location.reload() }, 500) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds') }
		})
	}

	$('.ft_team').on('click', function(){ post('/first_team/get.php?team_add', {}, $(this), 'Құрылды') })
	$('.ra_save').on('click', function(){ post('/first_team/get.php?roster_add', form_data($('.ra_form')), $(this), 'Құрамға қосылды') })
	$('.ft_rm').on('click', function(){
		if (!confirm('Құрамнан шығару керек пе? Статистика сақталады.')) return
		post('/first_team/get.php?roster_remove', { id: $(this).attr('data-id') }, null, 'Шығарылды')
	})

	$('.ma_comp').on('change', function(){ $('.ma_comp_new').toggle($(this).val() == 'new') })
	$('.ma_opp').on('change', function(){ $('.ma_opp_new').toggle($(this).val() == 'new') })
	$('.ma_save').on('click', function(){ post('/first_team/get.php?match_add', form_data($('.ma_form')), $(this), 'Матч қосылды', function(r){ return '/first_team/match.php?id=' + r.id }) })

	// матч статистикасы
	function row_state(tr) { tr.find('.ms_v').prop('disabled', !tr.find('.ms_pl').is(':checked')); tr.css('opacity', tr.find('.ms_pl').is(':checked') ? 1 : .5) }
	$('.ms_p').each(function(){ row_state($(this)) })
	$('.ms_pl').on('change', function(){ row_state($(this).closest('tr')) })

	$('.ms_save').on('click', function(){
		var d = { id: $('.ms_form').attr('data-id'), status: $('.ms_st').val(), goals_for: $('.ms_gf').val(), goals_against: $('.ms_ga').val(), mvp: $('.ms_mvp').val() }
		$('.ms_p').each(function(){
			if (!$(this).find('.ms_pl').is(':checked')) return
			var pid = $(this).attr('data-pid')
			$(this).find('.ms_v').each(function(){
				var k = $(this).attr('data-k')
				d['players[' + pid + '][' + k + ']'] = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val()
			})
		})
		post('/first_team/get.php?match_save', d, $(this), 'Сақталды')
	})

}) // end jquery
