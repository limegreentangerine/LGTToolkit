class ComponentAjaxFileList extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.lgt__ajax-file-list');
		if (!this.container) return;

		this.list = this.container.querySelector('.lgt__ajax-file-list--items');
		this.button = this.container.querySelector('.lgt__ajax-file-list--load-more');
		if (!this.list || !this.button) return;

		this.page = this.button.dataset.page;
		this.loading = false;

		this.button.addEventListener('click', this.loadPage.bind(this));

		this.filters = this.container.querySelector('.lgt__ajax-file-list--filters');
		this.topic = null;

		if (this.filters) {
			this.filters.addEventListener('click', this.filterPage.bind(this));
		}
	}

	async loadPage() {
		if (this.loading) return;

		const currentText = this.button.innerHTML;
		const data = this.button.dataset;

		this.button.classList.remove('d-none');
		this.button.innerHTML = this.loader();
		this.loading = true;

		if (this.topic !== null) {
			data.topicId = this.topic;
		}

		try {
			const response = await fetch('/ajax/lgt/file-list', {
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
			console.error('Failed to load files:', error);
		}

		this.button.innerHTML = currentText;
		this.loading = false;
	}

	async filterPage(event) {
		event.preventDefault();

		const button = event.target.closest(`button[data-topic-id=*]`);
		if (!button) return;

		const topicId = button.dataset.topicId;
		if (!topicId) return;

		this.topic = topicId > 0 ? topicId : null;
		this.button.dataset.page = 1;
		this.list.innerHTML = '';

		await this.loadPage();
	}

	loader() {
		return '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
	}
}

if (!customElements.get('component-ajax-file-list')) {
	customElements.define('component-ajax-file-list', ComponentAjaxFileList);
}
