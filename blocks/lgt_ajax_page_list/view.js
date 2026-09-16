class ComponentAjaxPageList extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.lgt__ajax-page-list');
		if (!this.container) return;

		this.list = this.container.querySelector('.lgt__ajax-page-list--items');
		this.button = this.container.querySelector('.lgt__ajax-page-list--load-more');
		if (!this.list || !this.button) return;

		this.loading = false;
		this.button.addEventListener('click', this.loadPage.bind(this));
	}

	async loadPage() {
		if (this.loading) return;

		const currentText = this.button.innerHTML;
		const data = this.button.dataset;

		this.button.classList.remove('d-none');
		this.button.innerHTML = this.loader();
		this.loading = true;

		try {
			const response = await fetch('/ajax/lgt/page-list', {
				method: 'post',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(data)
			});

			if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

			const res = await response.json();

			if (res.files.length > 0 && res.html) {
				this.list.insertAdjacentHTML('beforeend', res.html);
			}

			this.button.dataset.page = res.page;
			if (!res.hasNextPage) this.button.classList.add('d-none');
		} catch (error) {
			console.error('Failed to load pages:', error);
		}

		this.button.innerHTML = currentText;
		this.loading = false;
	}

	loader() {
		return '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
	}
}

if (!customElements.get('component-ajax-page-list')) {
	customElements.define('component-ajax-page-list', ComponentAjaxPageList);
}

// const ajaxPageLists = $('.ajax-page-list');

// $(function () {
// 	$.each(ajaxPageLists, function (_, v) {
// 		const ajaxLoadMore = $(v).find('.ajax-page-list__load-more');

// 		if (ajaxLoadMore.length) {
// 			ajaxLoadMore.on('click', function (e) {
// 				e.preventDefault();
// 				const $this = $(e.currentTarget);
// 				const data = $this.data();
// 				getNextPage(data);
// 			});
// 		}
// 	});
// });

// $(window).on('load', function () {
// 	$.each(ajaxPageLists, function (_, v) {
// 		const ajaxLoadMore = $(v).find('.ajax-page-list__load-more');
// 		if (ajaxLoadMore.length) {
// 			ajaxLoadMore.trigger('click');
// 		}
// 	});
// });

// function getNextPage(data) {
// 	const destination = $('#ajax-page-list__pages--' + data.bid);
// 	const button = $('#ajax-page-list__load-more--' + data.bid);
// 	const currentText = button.text();

// 	$.ajax({
// 		url: '/ajax/page_list',
// 		dataType: 'json',
// 		data: data,
// 		beforeSend: function () {
// 			console.log(this.url);
// 			button.html(
// 				'<div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div>'
// 			);
// 		},
// 		success: function (response) {
// 			if (response.hasNextPage) {
// 				var nextPage = parseInt(response.nextPage);
// 				button.data('page', nextPage);
// 			} else {
// 				button.hide();
// 			}

// 			if (response.html.length) {
// 				$(destination).append(response.html);
// 			}
// 		},
// 		error: function (error) {
// 			console.error(error);
// 		},
// 		complete: function () {
// 			button.html(currentText);
// 		}
// 	});
// }
