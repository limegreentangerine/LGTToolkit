const ajaxPageLists = $('.ajax-page-list');

$(function () {
	$.each(ajaxPageLists, function (_, v) {
		const ajaxLoadMore = $(v).find('.ajax-page-list__load-more');

		if (ajaxLoadMore.length) {
			ajaxLoadMore.on('click', function (e) {
				e.preventDefault();
				const $this = $(e.currentTarget);
				const data = $this.data();
				getNextPage(data);
			});
		}
	});
});

$(window).on('load', function () {
	$.each(ajaxPageLists, function (_, v) {
		const ajaxLoadMore = $(v).find('.ajax-page-list__load-more');
		if (ajaxLoadMore.length) {
			ajaxLoadMore.trigger('click');
		}
	});
});

function getNextPage(data) {
	const destination = $('#ajax-page-list__pages--' + data.bid);
	const button = $('#ajax-page-list__load-more--' + data.bid);
	const currentText = button.text();

	$.ajax({
		url: '/ajax/page_list',
		dataType: 'json',
		data: data,
		beforeSend: function () {
			console.log(this.url);
			button.html(
				'<div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div>'
			);
		},
		success: function (response) {
			if (response.hasNextPage) {
				var nextPage = parseInt(response.nextPage);
				button.data('page', nextPage);
			} else {
				button.hide();
			}

			if (response.html.length) {
				$(destination).append(response.html);
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
