// start jquery
$(document).ready(function() {
	$('.as_r').on('input', function(){ $(this).parent().find('.as_v').text($(this).val()) })
	$('.as_save').on('click', function(){
		var btn = $(this), f = $('.as_form'), d = { id: f.attr('data-id') }
		f.find('[name]').each(function(){ d[$(this).attr('name')] = $(this).val() })
		$.ajax({
			url: '/progress/get.php?add', type: 'POST', dataType: 'json', data: d,
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) { mess('Сақталды. Орташа: ' + r.avg + (r.bonus ? ' · +20 прогресс ұпайы' : '')); setTimeout(function(){ location.reload() }, 900) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})
}) // end jquery
