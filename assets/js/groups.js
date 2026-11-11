// start jquery
$(document).ready(function() {

	$('.gr_save').on('click', function(){
		var btn = $(this)
		var form = $('.gr_form')
		var data = {}
		form.find('[name]').each(function(){ data[$(this).attr('name')] = $(this).val() })
		form.find('.form_err').removeClass('form_err'); form.find('.form_e').remove()
		$.ajax({
			url: "/groups/get.php?add",
			type: "POST",
			dataType: "json",
			data: data,
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) { mess('Cәтті сақталды!'); setTimeout(function(){ location.reload() }, 400) }
				else if (r.errors) $.each(r.errors, function(k, v){ form.find('[name="' + k + '"]').addClass('form_err').after('<span class="form_e">' + $('<i>').text(v).html() + '</span>') })
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})

}) // end jquery
