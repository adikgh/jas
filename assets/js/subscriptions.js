// start jquery
$(document).ready(function() {

	function form_data(form) {
		var d = {}
		form.find('[name]').each(function(){
			if ($(this).is(':checkbox')) d[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0
			else d[$(this).attr('name')] = $(this).val()
		})
		return d
	}
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
	function pop(cl) { $('.' + cl).addClass('pop_bl_act'); $('#html').addClass('ovr_h') }

	// сату: тариф қатысушының бағытына қарай сүзіледі
	$('.ss_part').on('change', function(){
		var dir = $(this).find(':selected').attr('data-dir')
		$('.ss_plan option[data-dir]').each(function(){ $(this).toggle(!dir || $(this).attr('data-dir') == dir) })
		if ($('.ss_plan :selected').attr('data-dir') && $('.ss_plan :selected').attr('data-dir') != dir) $('.ss_plan').val('')
	})
	$('.ss_save').on('click', function(){ post('/subscriptions/get.php?sell', form_data($('.ss_form')), $(this), 'Абонемент құрылды. Енді төлем енгізіңіз.') })

	$('.sb_cancel').on('click', function(){
		if (!confirm('Абонементті тоқтату керек пе?')) return
		post('/subscriptions/get.php?cancel', { id: $(this).attr('data-id') }, null, 'Тоқтатылды')
	})

	// заморозка
	$('.sb_fz').on('click', function(){
		$('.fz_form [name=id]').val($(this).attr('data-id'))
		$('.fz_n').text($(this).attr('data-n'))
		$('.fz_max').text($(this).attr('data-max'))
		$('.fz_form [name=days]').attr('max', $(this).attr('data-max'))
		pop('sub_freeze')
	})
	$('.fz_save').on('click', function(){ post('/subscriptions/get.php?freeze_add', form_data($('.fz_form')), $(this), 'Заморозка сақталды') })
	$('.fz_dec').on('click', function(){ post('/subscriptions/get.php?freeze_decide', { id: $(this).attr('data-id'), d: $(this).attr('data-d') }, null, 'Сақталды') })

	// тарифтер
	$('.pl_save').on('click', function(){ post('/subscriptions/get.php?plan_add', form_data($('.pl_form')), $(this), 'Тариф қосылды') })
	$('.pl_fee').on('click', function(){
		var v = prompt('Kaspi RED комиссиясы, % (бос қалдырсаңыз — жалпы мән қолданылады)', $(this).attr('data-v'))
		if (v === null) return
		post('/subscriptions/get.php?plan_fee', { plan_id: $(this).attr('data-id'), fee: v.replace(',', '.').trim() }, null, 'Сақталды')
	})

}) // end jquery
