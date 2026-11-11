// start jquery
$(document).ready(function() {

	function form_data(form) { var d = {}; form.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() }); return d }
	function post(url, data, btn, ok) {
		$.ajax({
			url: url, type: "POST", dataType: "json", data: data,
			beforeSend: function(){ if (btn) btn.addClass('btn_ds') },
			success: function(r){
				if (btn) btn.removeClass('btn_ds')
				if (r.ok) { mess(ok); setTimeout(function(){ location.href = location.pathname + location.search.replace(/([?&])add=\d+&?/, '$1') }, 600) }
				else if (r.errors) mess(Object.values(r.errors).join('. '))
				else mess(r.mess || 'Қате')
			},
			error: function(){ if (btn) btn.removeClass('btn_ds') }
		})
	}
	// әр ашылған формаға жаңа кілт: екі рет басылса да бір төлем
	function idem() {
		var a = new Uint8Array(16); (window.crypto || window.msCrypto).getRandomValues(a)
		return Array.prototype.map.call(a, function(x){ return ('0' + x.toString(16)).slice(-2) }).join('')
	}
	function open_pay() {
		$('.pa_form [name=idem]').val(idem())
		$('.pay_add').addClass('pop_bl_act'); $('#html').addClass('ovr_h')
	}

	function load_subs(pid, sel) {
		var s = $('.pa_sub').html('<option value="">Жүктелуде...</option>')
		if (!pid) { s.html('<option value="">Алдымен қатысушыны таңдаңыз</option>'); return }
		$.ajax({
			url: '/payments/get.php?subs', type: 'POST', dataType: 'json', data: { participant_id: pid },
			success: function(r){
				if (!r.list.length) { s.html('<option value="">Қарызы бар абонемент жоқ</option>'); return }
				s.html('')
				r.list.forEach(function(x){ s.append($('<option>').val(x.id).attr('data-rest', x.rest).text(x.name + ' · қалды ' + Math.round(x.rest).toLocaleString('ru') + ' ₸')) })
				if (sel) s.val(sel)
				s.trigger('change')
			}
		})
	}
	$('.pa_part').on('change', function(){ load_subs($(this).val()) })
	$('.pa_sub').on('change', function(){ var r = $(this).find(':selected').attr('data-rest'); if (r) $('.pa_amount').val(Math.round(r)) })

	$('.pay_open').on('click', open_pay)
	if ($('.pay_add').attr('data-sub')) {
		$('.pa_part').val($('.pay_add').attr('data-part'))
		load_subs($('.pay_add').attr('data-part'), $('.pay_add').attr('data-sub'))
		open_pay()
	}
	$('.pa_save').on('click', function(){ post('/payments/get.php?add', form_data($('.pa_form')), $(this), 'Төлем сақталды') })

	$('.py_act').on('click', function(){
		var a = $(this).attr('data-a'), d = { id: $(this).attr('data-id') }
		if (a == 'confirm' && !confirm('Ақша түскенін тексердіңіз бе? Растау керек пе?')) return
		if (a != 'confirm') {
			var r = prompt(a == 'reverse' ? 'Сторно себебі (төлем өшірілмейді, кері жазба жасалады):' : 'Қабылдамау себебі:')
			if (r === null) return
			d.reason = r
		}
		post('/payments/get.php?' + a, d, null, 'Сақталды')
	})

	$('.ex_save').on('click', function(){ post('/payments/get.php?expense_add', form_data($('.ex_form')), $(this), 'Шығын қосылды') })

}) // end jquery
