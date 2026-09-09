class ComponentShareSheet extends HTMLElement {
	constructor() {
		super();

		this.list = this.querySelector('.block__lgt-social-share--list');
		this.button = this.querySelector('.block__lgt-social-share--sheet');
		if (!this.list || !this.button) return;

		this.init();
	}

	init() {
		if ('share' in navigator) {
			this.list.classList.add('d-none');
			this.button.classList.remove('d-none');
			this.button.addEventListener('click', this.handleShareSheet.bind(this));
		} else {
			this.list.classList.remove('d-none');
			this.button.classList.add('d-none');
			this.button.removeEventListener('click', this.handleShareSheet.bind(this));
		}
	}

	async handleShareSheet(event) {
		event.preventDefault();

		if (navigator.share) {
			try {
				await navigator.share({
					title: document.title,
					url: window.location.href
				});
			} catch (error) {
				if (error.name !== 'AbortError') {
					console.error(error);
				}
			}
		} else {
			// Fallback
			await navigator.clipboard.writeText(window.location.href);
			window.alert('URL copied to clipboard');
		}
	}
}

if (!customElements.get('component-share-sheet')) {
	customElements.define('component-share-sheet', ComponentShareSheet);
}

class ComponentMobileShareSheet extends HTMLElement {
	constructor() {
		super();

		this.button = this.querySelector('.block__lgt-social-share--sheet');
		if (!this.button) return;

		this.button.addEventListener('click', this.handleShareSheet.bind(this));
	}

	async handleShareSheet(event) {
		event.preventDefault();

		if (navigator.share) {
			try {
				await navigator.share({
					title: document.title,
					url: window.location.href
				});
			} catch (error) {
				if (error.name !== 'AbortError') {
					console.error(error);
				}
			}
		} else {
			// Fallback
			await navigator.clipboard.writeText(window.location.href);
			window.alert('URL copied to clipboard');
		}
	}
}

if (!customElements.get('component-mobile-share-sheet')) {
	customElements.define('component-mobile-share-sheet', ComponentMobileShareSheet);
}
