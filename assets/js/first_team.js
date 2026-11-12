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


	// Instagram пост: мәтін және сурет
	$('.ms_ptab .tab_i').on('click', function(){
		var t = $(this).attr('data-t'); $('.ms_ptab .tab_i').removeClass('tab_i_act'); $(this).addClass('tab_i_act')
		$('.ms_ptxt').hide().filter('[data-t="' + t + '"]').show()
	})
	function ptab() { return $('.ms_ptab .tab_i_act').attr('data-t') }
	$('.ms_pcopy').on('click', function(){
		var ta = $('.ms_ptxt[data-t="' + ptab() + '"]')[0]
		if (navigator.clipboard) navigator.clipboard.writeText(ta.value).then(function(){ mess('Көшірілді') })
		else { ta.select(); document.execCommand('copy'); mess('Көшірілді') }
	})
	$('.ms_pimg').on('click', function(){
		var b = $('.ms_post'), end = ptab() == 'end' && b.attr('data-fin') == '1'
		var c = document.createElement('canvas'); c.width = 1080; c.height = 1350; var x = c.getContext('2d')
		var g = x.createLinearGradient(0, 0, 0, 1350); g.addColorStop(0, '#0b3c7a'); g.addColorStop(1, '#0a1b36'); x.fillStyle = g; x.fillRect(0, 0, 1080, 1350)
		x.fillStyle = '#ffc20e'; x.beginPath(); x.moveTo(0, 1130); x.lineTo(1080, 1060); x.lineTo(1080, 1078); x.lineTo(0, 1148); x.fill()
		x.fillStyle = '#ffc20e'; x.fillRect(80, 120, 12, 120)
		x.textAlign = 'left'; x.fillStyle = '#ffc20e'; x.font = '900 64px Nunito, Arial'; x.fillText(end ? 'МАТЧ ҚОРЫТЫНДЫСЫ' : 'MATCH DAY', 120, 175)
		x.fillStyle = 'rgba(255,255,255,.75)'; x.font = '600 34px Nunito, Arial'; x.fillText(b.attr('data-comp') || '', 120, 228)
		var logo = new Image(); logo.onload = function(){
			x.drawImage(logo, 100, 450, 190, 240)
			x.fillStyle = 'rgba(255,255,255,.1)'; x.beginPath(); x.arc(885, 570, 120, 0, Math.PI * 2); x.fill()
			x.fillStyle = '#fff'; x.textAlign = 'center'; x.font = '900 96px Nunito, Arial'; x.fillText((b.attr('data-opp') || '?').substr(0, 2).toUpperCase(), 885, 604)
			x.font = '800 34px Nunito, Arial'; x.fillText(b.attr('data-tm'), 195, 790); x.fillText(b.attr('data-opp').toUpperCase().substr(0, 16), 885, 790)
			x.fillStyle = '#ffc20e'; x.font = '900 ' + (end ? 130 : 110) + 'px Nunito, Arial'; x.fillText(end ? b.attr('data-score') : 'VS', 540, 620)
			if (!end) { x.fillStyle = '#fff'; x.font = '800 54px Nunito, Arial'; x.fillText(b.attr('data-score'), 540, 700) }
			x.fillStyle = '#fff'; x.font = '700 40px Nunito, Arial'; x.fillText('📅 ' + b.attr('data-date') + '   📍 ' + (b.attr('data-venue') || ''), 540, 1000)
			x.fillStyle = '#ffc20e'; x.font = '900 46px Nunito, Arial'; x.fillText('ZHASS ACADEMY · ШЫМКЕНТ', 540, 1240)
			var a = document.createElement('a'); a.download = 'zhass_' + (end ? 'result' : 'matchday') + '.png'; a.href = c.toDataURL('image/png'); a.click()
		}
		logo.src = '/assets/img/first_team/logo.png'
	})
}) // end jquery
