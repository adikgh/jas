// start jquery
$(document).ready(function() {

	function form_data(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, data, btn, ok, go) {
		$.ajax({
			url: url, type: "POST", dataType: "json", data: data,
			beforeSend: function(){ if (btn) btn.addClass('btn_ds') },
			success: function(r){
				if (btn) btn.removeClass('btn_ds')
				if (r.ok) { if (ok) mess(ok); setTimeout(function(){ if (go) go(r); else location.reload() }, 400) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds'); mess('Байланыс қатесі') }
		})
	}
	function copy(txt, el) {
		if (navigator.clipboard) navigator.clipboard.writeText(txt).then(function(){ mess('Көшірілді') })
		else { el.select(); document.execCommand('copy'); mess('Көшірілді') }
	}

	var tid = $('.tr_item').attr('data-id')
	var api = '/tournaments/get.php?'

	// жаңа турнир
	$('.tr_save').on('click', function(){ post(api + 'add', form_data($('.tr_form')), $(this), 'Турнир құрылды', function(r){ location.href = '/tournaments/item.php?id=' + r.id }) })

	// мәртебе, сілтеме
	$('.tr_st').on('change', function(){ post(api + 'status', { id: tid, status: $(this).val() }, null, 'Сақталды') })
	$('.tr_link_copy').on('click', function(){ copy($('.tr_link').val(), $('.tr_link')) })
	$('.tr_post_copy').on('click', function(){ copy($('.tr_post').val(), $('.tr_post')) })

	// командалар
	$('.tr_t_add').on('click', function(){ post(api + 'team_add', $.extend({ id: tid }, form_data($('.tr_tform'))), $(this), 'Команда қосылды') })
	$('.tr_t_paid').on('change', function(){ post(api + 'team_paid', { id: tid, team_id: $(this).closest('.tr_team').attr('data-t'), paid: $(this).is(':checked') ? 1 : 0 }, null, 'Сақталды', function(){}) })
	$('.tr_t_place').on('change', function(){ post(api + 'team_place', { id: tid, team_id: $(this).closest('.tr_team').attr('data-t'), place: $(this).val() }, null, 'Сақталды') })
	$('.tr_t_rm').on('click', function(){
		if (!confirm('Команданы өшіру керек пе?')) return
		post(api + 'team_remove', { id: tid, team_id: $(this).closest('.tr_team').attr('data-t') }, null, 'Өшірілді')
	})

	// ойындар
	$('.tr_g_add').on('click', function(){ post(api + 'game_add', $.extend({ id: tid }, form_data($('.tr_gform'))), $(this), 'Ойын қосылды') })
	$('.tr_g_save').on('click', function(){
		var g = $(this).closest('.tr_game')
		post(api + 'game_score', { id: tid, game_id: g.attr('data-g'), home_goals: g.find('.tr_hg').val(), away_goals: g.find('.tr_ag').val() }, null, 'Есеп сақталды')
	})
	$('.tr_hg, .tr_ag').on('keydown', function(e){ if (e.key == 'Enter') $(this).closest('.tr_game').find('.tr_g_save').click() })
	$('.tr_g_rm').on('click', function(){
		if (!confirm('Ойынды өшіру керек пе?')) return
		post(api + 'game_remove', { id: tid, game_id: $(this).closest('.tr_game').attr('data-g') }, null, 'Өшірілді')
	})

	// марапаттар
	$('.tr_a_add').on('click', function(){ post(api + 'award_add', $.extend({ id: tid }, form_data($('.tr_aform'))), $(this), 'Марапат қосылды') })
	$('.tr_a_rm').on('click', function(){
		if (!confirm('Марапатты өшіру керек пе?')) return
		post(api + 'award_remove', { id: tid, award_id: $(this).closest('.li').attr('data-a') }, null, 'Өшірілді')
	})

	// ашық тіркелу беті
	$('.tr_jform [name=phone]').mask && $('.tr_jform [name=phone]').mask('8 (000) 000-00-00')
	$('.tr_jsave').on('click', function(){
		post('/tournaments/join.php?join&c=' + $('.tr_join').attr('data-c'), form_data($('.tr_jform')), $(this), null, function(){ $('.tr_jform').hide(); $('.tr_join_ok').show() })
	})

})
// end jquery
