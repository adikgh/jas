// start jquery
$(document).ready(function() {
	$('.nt_i').on('click', function(e){
		var a = $(this), href = a.attr('href'); e.preventDefault()
		$.post('/notifications/get.php?read', { id: a.attr('data-id') }, function(){ if (href && href != '#') location.href = href; else location.reload() }, 'json')
	})
	$('.nt_all').on('click', function(){ $.post('/notifications/get.php?read_all', {}, function(r){ if (r.ok) location.reload() }, 'json') })
}) // end jquery
