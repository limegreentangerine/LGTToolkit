const loadButton = $('.ajax-file-list__load-more');

function debounce(func, timeout = 300) {
	let timer;
	return (...args) => {
		clearTimeout(timer);
		timer = setTimeout(() => {
			func.apply(this, args);
		}, timeout);
	};
}

function generateData(loadButton, filterButton) {
	let data = {};

	$.each(loadButton.data(), function (k, v) {
		data[k] = v;
	});

	if (filterButton !== false) {
		$.each(filterButton.data(), function (k, v) {
			if (v > 0) {
				data[k] = parseInt(v);
			}
		});
	}

	return data;
}

$(function () {
	loadButton.on(
		'click',
		debounce(function (e) {
			e.preventDefault();
			const $this = $(e.currentTarget);
			const data = generateData($this, false);
			getNextPage(data, false);
		}, 500)
	);

	$('.resource-filters button').on(
		'click',
		debounce(function (e) {
			e.preventDefault();
			const $this = $(e.currentTarget);
			const button = $('#ajax-file-list__load-more--' + $this.data('bid'));
			const data = generateData(button, $this);
			$('.resource-filters button').removeClass('active');
			$this.addClass('active');
			getNextPage(data, true);
		}, 500)
	);
});

$(window).on('load', function () {
	loadButton[0].click();
});

function getNextPage(data, clearHtml) {
	const button = $('#ajax-file-list__load-more--' + data.bid);
	const currentText = button.text();
	const loaderHtml =
		'<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
	const destination = $(data.target);

	$.ajax({
		url: '/ajax/file_list',
		dataType: 'json',
		data: data,
		beforeSend: function () {
			if (clearHtml) {
				destination.html('');
			}
			button.show();
			button.html(loaderHtml);
		},
		success: function (response) {
			if (response.files.length > 0) {
				destination.append(response.html);
			}

			if (response.hasNextPage) {
				var nextPage = parseInt(response.nextPage);
				button.data('page', nextPage);
			} else {
				button.hide();
			}
		},
		error: function (error) {
			console.error(error);
		},
		complete: function () {
			button.html(currentText);
		}
	});
}
