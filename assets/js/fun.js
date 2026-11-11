function mess(e) {
	$('.bl_mess').addClass('bl_mess_zak')
	$('.bl_mess_sam').text(e)
	setTimeout(function(){$('.bl_mess').removeClass('bl_mess_zak')},6000)
}
$('html, body').on('click', '.bl_mess', function(){
	$(this).removeClass('bl_mess_zak')
})

// 
function clcBack(e) {
	history.back();
}
