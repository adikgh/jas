
	<!-- app end -->
	</div>

	<? include "modal.php"; ?>

	<!-- main js -->
	<? foreach ($sjs as $i): ?><script src="/assets/js/<?=$i?>.js?v=<?=$ver?>"></script><? endforeach ?>
	<? foreach ($js as $i): ?><script src="/assets/js/<?=$i?>.js?v=<?=$ver?>"></script><? endforeach ?>

</body>
</html>
