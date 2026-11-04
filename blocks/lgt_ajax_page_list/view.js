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
			const response = await fetch(`/ajax/lgt/page-list/${data.bid}/${data.cid}/${data.page}/${data.template}`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

			const res = await response.json();

			if (res.pages.length > 0 && res.html) {
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
