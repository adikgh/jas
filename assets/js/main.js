// CSRF: әр POST сұранысқа токен қосылады
$.ajaxSetup({
	headers: { 'X-CSRF-Token': $('meta[name="csrf"]').attr('content') },
	statusCode: {
		401: function(){ location.href = '/sign/' },
		403: function(){ mess('Бұл әрекетке рұқсатыңыз жоқ') },
		419: function(){ mess('Сессия ескірді. Бетті жаңартыңыз') },
	}
});

// start jquery
$(document).ready(function() {

	// lazy img
	$('.lazy_img').lazy({effect:"fadeIn",effectTime:300,threshold:0})


	// pop: data-pop="add_part" батырмасы .pop_bl.add_part ашады
	$('html').on('click', '[data-pop]', function(){
		$('.' + $(this).attr('data-pop')).addClass('pop_bl_act');
		$('#html').addClass('ovr_h');
	})
	$('html').on('click', '.pop_back', function(){
		$(this).parents('.pop_bl').removeClass('pop_bl_act');
		$('#html').removeClass('ovr_h');
	})


	// мәзір (үш нүкте)
	$('html').on('click', '.uc_uibo', function(e){
		e.stopPropagation()
		var m = $(this).siblings('.menu_c')
		$('.uc_uib .menu_c').not(m).removeClass('menu_c_act')
		m.toggleClass('menu_c_act')
	})
	$('html').on('click', function(){ $('.uc_uib .menu_c').removeClass('menu_c_act') })


	// tabs
	$('html').on('click', '.tab_i', function(){
		var t = $(this).attr('data-tab')
		$(this).addClass('tab_i_act').siblings().removeClass('tab_i_act')
		$('.tab_c').removeClass('tab_c_act')
		$('.tab_c[data-tab="' + t + '"]').addClass('tab_c_act')
		try { history.replaceState(null, '', '#' + t) } catch (e) {}
	})
	if (location.hash && $('.tab_i[data-tab="' + location.hash.substr(1) + '"]').length) $('.tab_i[data-tab="' + location.hash.substr(1) + '"]').click()


	// сүзгі формасы: select өзгерсе жіберіледі
	$('html').on('change', '.flt_auto', function(){ $(this).parents('form').submit() })

	// мобильді «Мәзір» парағы
	function msheet(on){ $('.msheet').toggleClass('msheet_open', on).attr('aria-hidden', on ? 'false' : 'true'); $('body').toggleClass('msheet_lock', on) }
	$('.tbar_more').on('click', function(){ msheet(!$('.msheet').hasClass('msheet_open')) })
	$('.msheet_bg').on('click', function(){ msheet(false) })
	$(document).on('keydown', function(e){ if (e.key == 'Escape') msheet(false) })
	var msy = null
	$('.msheet_c').on('touchstart', function(e){ msy = this.scrollTop <= 0 ? e.originalEvent.touches[0].clientY : null })
	$('.msheet_c').on('touchend', function(e){ if (msy !== null && e.originalEvent.changedTouches[0].clientY - msy > 80) msheet(false); msy = null })

}) // end jquery
