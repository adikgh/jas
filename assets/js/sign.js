// start jquery
$(document).ready(function() {

	// sign in
	$('.btn_sign_in').on('click', function(){
		var btn = $(this)
		var phone = $('.phone').attr('data-val')
		var password = $('.password').val()

		if (!phone || phone.length < 11) mess('Телефон нөміріңізді толтырыңыз')
		else if (password.length < 6) mess('Құпия сөз кемінде 6 таңба')
		else {
			$.ajax({
				url: "/sign/get.php?sign_in",
				type: "POST",
				dataType: "html",
				data: ({ phone: phone, password: password }),
				beforeSend: function(){ btn.addClass('btn_ds') },
				success: function(data){
					btn.removeClass('btn_ds')
					if (data == 'yes') location.href = '/'
					else if (data == 'limit') mess('Тым көп әрекет. 15 минуттан кейін қайталаңыз')
					else mess('Нөмір немесе құпия сөз қате')
				},
				error: function(data){ btn.removeClass('btn_ds'); mess('Қате. Қайта көріңіз') }
			})
		}
	})

}) // end jquery
