// start jquery
$(document).ready(function() {
	$('.cb_fz').on('click', function(){
		$('.cf_form [name=id]').val($(this).attr('data-id'))
		$('.cf_n').text($(this).attr('data-n')); $('.cf_max').text($(this).attr('data-max'))
		$('.cf_form [name=days]').attr('max', $(this).attr('data-max'))
		$('.cb_freeze').addClass('pop_bl_act'); $('#html').addClass('ovr_h')
	})
	$('.cf_save').on('click', function(){
		var btn = $(this), d = {}
		$('.cf_form [name]').each(function(){ d[$(this).attr('name')] = $(this).val() })
		$.ajax({ url: '/cabinet/get.php?freeze_request', type: 'POST', dataType: 'json', data: d,
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){ btn.removeClass('btn_ds'); if (r.ok) { mess('Өтініш жіберілді'); setTimeout(function(){ location.reload() }, 700) } else mess(r.mess || 'Қате') },
			error: function(){ btn.removeClass('btn_ds') } })
	})
}) // end jquery
