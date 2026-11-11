// start jquery
$(document).ready(function() {

	// бағытқа қарай ата-ана өрістері және топтар
	function pf_dir(form) {
		var dir = form.find('.pf_dir').val()
		form.find('.pf_guard').toggle(dir != 'adult')
		form.find('.pf_ph_req').toggle(dir == 'adult')
		form.find('.pf_group option[data-dir]').each(function(){ $(this).toggle(!dir || $(this).attr('data-dir') == dir) })
		var sel = form.find('.pf_group option:selected')
		if (sel.attr('data-dir') && dir && sel.attr('data-dir') != dir) form.find('.pf_group').val('')
	}
	$('.pf_form').each(function(){ pf_dir($(this)) })
	$('html').on('change', '.pf_dir', function(){ pf_dir($(this).parents('form')) })

	// ?add — бірден форма ашылады
	if (location.search.indexOf('add') > -1 && $('.part_add').length) { $('.part_add').addClass('pop_bl_act'); $('#html').addClass('ovr_h') }


	// сақтау (қосу / өңдеу)
	$('html').on('click', '.pf_save', function(){
		var btn = $(this)
		var pop = btn.parents('.pop_bl')
		var form = pop.find('.pf_form')
		var id = form.attr('data-id')
		var data = {}
		form.find('[name]').each(function(){
			var v = $(this).val()
			if ($(this).hasClass('fr_phone')) v = $(this).cleanVal ? $(this).cleanVal() : v
			data[$(this).attr('name')] = v
		})
		if (id) data.id = id

		form.find('.form_err').removeClass('form_err')
		form.find('.form_e').remove()

		$.ajax({
			url: "/participants/get.php?" + (id ? 'edit' : 'add'),
			type: "POST",
			dataType: "json",
			data: data,
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) {
					mess('Cәтті сақталды!')
					setTimeout(function(){ location.href = '/participants/item.php?id=' + (r.id || id) }, 400)
				} else if (r.errors) {
					$.each(r.errors, function(k, v){
						var f = form.find('[name="' + k + '"]')
						f.addClass('form_err').after('<span class="form_e">' + $('<i>').text(v).html() + '</span>')
					})
					mess('Өрістерді тексеріңіз')
				} else mess(r.mess || 'Қате')
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})


	// топ ауыстыру
	$('.btn_set_group').on('click', function(){
		var btn = $(this)
		$.ajax({
			url: "/participants/get.php?set_group",
			type: "POST",
			dataType: "json",
			data: ({ id: btn.attr('data-id'), group_id: $('.sg_group').val(), reason: $('.sg_reason').val() }),
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) { mess('Топ ауыстырылды'); setTimeout(function(){ location.reload() }, 400) }
				else mess(r.mess || 'Қате')
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})


	// ата-ана қосу
	$('.btn_guard_add').on('click', function(){
		var btn = $(this)
		var pop = btn.parents('.pop_bl')
		$.ajax({
			url: "/participants/get.php?guardian_add",
			type: "POST",
			dataType: "json",
			data: ({ id: btn.attr('data-id'), name: pop.find('.ga_name').val(), phone: pop.find('.ga_phone').cleanVal(), relation: pop.find('.ga_rel').val() }),
			beforeSend: function(){ btn.addClass('btn_ds') },
			success: function(r){
				btn.removeClass('btn_ds')
				if (r.ok) { mess('Қосылды'); setTimeout(function(){ location.reload() }, 400) }
				else if (r.errors) mess(Object.values(r.errors).join('. '))
				else mess(r.mess || 'Қате')
			},
			error: function(){ btn.removeClass('btn_ds') }
		})
	})


	// архив / қалпына келтіру
	$('html').on('click', '.part_archive, .part_restore', function(){
		var arh = $(this).hasClass('part_archive')
		if (arh && !confirm('Қатысушыны архивке жіберу керек пе? Тарихы сақталады.')) return
		$.ajax({
			url: "/participants/get.php?" + (arh ? 'archive' : 'restore'),
			type: "POST",
			dataType: "json",
			data: ({ id: $(this).attr('data-id') }),
			success: function(r){ if (r.ok) location.reload(); else mess(r.mess || 'Қате') },
		})
	})


	// фото
	$('.prf_img_e').on('click', function(){ $('.prf_file').click() })
	$('.prf_file').on('change', function(){
		var fd = new FormData()
		fd.append('file', this.files[0])
		fd.append('id', $(this).attr('data-id'))
		$.ajax({
			url: "/participants/get.php?img",
			type: "POST",
			dataType: "json",
			data: fd, processData: false, contentType: false,
			beforeSend: function(){ mess('Жүктелуде...') },
			success: function(r){
				if (r.ok) { $('.prf_img').css('background-image', 'url(' + r.img + ')').find('.prf_img_l').remove(); mess('Фото сақталды') }
				else mess(r.mess || 'Қате')
			},
		})
	})

}) // end jquery
