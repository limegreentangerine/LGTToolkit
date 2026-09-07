$(function () {
	$('.anchor-nav__link').on('click tap', function (e) {
		e.preventDefault();
		const value = $(this).attr('href');

		if ($(value).length) {
			console.log('Scrolling to:', $(value).offset().top);
			$('html, body').animate(
				{
					scrollTop: $(value).offset().top
				},
				1200,
				'swing'
			);
		}
	});
});
