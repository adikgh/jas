// start jquery
$(document).ready(function() {

	function sum() {
		var c = { present: 0, absent: 0, none: 0 }
		$('.at_i').each(function(){
			var st = $(this).attr('data-st')
			if (!st) c.none++
			else if (st == 'present') c.present++
			else if (st == 'absent') c.absent++
		})
		$('.at_s_present').text(c.present); $('.at_s_absent').text(c.absent); $('.at_s_none').text(c.none)
	}
	sum()

	$('.at_bi').on('click', function(){
		if ($(this).hasClass('at_bi_ds')) return
		var it = $(this).closest('.at_i')
		it.find('.at_bi').removeClass('at_bi_act')
		$(this).addClass('at_bi_act')
		it.attr('data-st', $(this).attr('data-st'))
		sum()
	})

	// бәрі келді (заморозкадағылар өзгермейді)
	$('.at_all').on('click', function(){
		$('.at_i').each(function(){
			if ($(this).attr('data-st') == 'frozen') return
			$(this).find('.at_bi_present').trigger('click')
		})
	})

	$('.at_save').on('click', function(){
		var btn = $(this), data = { id: $('.at_list').attr('data-id') }, none = 0
		$('.at_i').each(function(){
			var st = $(this).attr('data-st')
			if (st) data['marks[' + $(this).attr('data-pid') + ']'] = st
			else none++
		})
		if (none && !confirm(none + ' қатысушы белгіленбеді. Сақтай берейік пе?')) return
		$.ajax({
			url: '/attendance/get.php?mark', type: 'POST', dataType: 'json', data: data,
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) mess(r.changed ? 'Сақталды (' + r.changed + ' өзгеріс)' : 'Өзгеріс жоқ')
				else mess(r.mess || 'Қате')
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})

}) // end jquery
