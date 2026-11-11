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
				else if (r.errors) mess(Object.values(r.errors).join('. '))
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds') }
		})
	}

	// қосымша жаттығу
	$('.sa_save').on('click', function(){ post('/schedule/get.php?session_add', form_data($('.sa_form')), $(this), 'Жаттығу қосылды') })

	// жаттығуды ашу
	var sess = null
	$('html').on('click', '[data-sess]', function(){
		sess = JSON.parse($(this).attr('data-sess'))
		$('.se_title').text(sess.title)
		$('.se_att').attr('href', '/attendance/session.php?id=' + sess.id)
		$('.se_date').val(sess.date); $('.se_start').val(sess.start); $('.se_end').val(sess.end)
		if (sess.venue) $('.se_venue').val(sess.venue)
		$('.se_edit').toggle(!sess.marked)
		$('.sess_act').addClass('pop_bl_act'); $('#html').addClass('ovr_h')
	})
	$('.se_move').on('click', function(){
		post('/schedule/get.php?session_move', { id: sess.id, date: $('.se_date').val(), start: $('.se_start').val(), end: $('.se_end').val(), venue_id: $('.se_venue').val() }, $(this), 'Ауыстырылды')
	})
	$('.se_cancel').on('click', function(){
		if (!confirm('Жаттығуды болдырмау керек пе?')) return
		post('/schedule/get.php?session_cancel', { id: sess.id, reason: $('.se_reason').val() }, $(this), 'Болдырылмады')
	})

	// тұрақты кесте
	$('.tp_save').on('click', function(){ post('/schedule/get.php?tpl_add', form_data($('.tp_form')), $(this), 'Кесте қосылды') })
	$('.tpl_stop').on('click', function(){
		if (!confirm('Бұл уақыттағы тұрақты жаттығуды тоқтату керек пе? Алдағы жаттығулар болдырылмайды.')) return
		post('/schedule/get.php?tpl_stop', { id: $(this).attr('data-id') }, null, 'Тоқтатылды')
	})

}) // end jquery
