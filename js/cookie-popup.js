$(window).on('load', function () {
	const code = $('#cookie-popup-code').html();
	const element = $(code);
	var show = true;
	$('body').append(code);

	$.get('/ajax/check-cookies', function (response) {
		show = JSON.parse(response);

		if (element.hasClass('activate') && show) {
			setTimeout(function () {
				$('body').find('#lgt-cookie-popup').addClass('notify');
			}, 500);
		}
	});
});

$(function () {
	$(document).on('click', '#allow-cookies', function (e) {
		e.preventDefault();
		$.get('/ajax/allow-cookies', function (response) {
			if (JSON.parse(response) == true) {
				$('body').find('#lgt-cookie-popup').removeClass('notify');
				window.location.reload();
			}
		});
	});

	$(document).on('click', '#disallow-cookies', function (e) {
		e.preventDefault();
		$.get('/ajax/disallow-cookies', function (response) {
			if (JSON.parse(response) == true) {
				$('body').find('#lgt-cookie-popup').removeClass('notify');
				window.location.reload();
			}
		});
	});
});
