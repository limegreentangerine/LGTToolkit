class ComponentAnchorNav extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.block__anchor-nav');
		if (!this.container) return;

		this.container.addEventListener('click', this.scrollToAnchor.bind(this));
	}

	scrollToAnchor(event) {
		event.preventDefault();

		const link = event.target.closest('.anchor-nav__link');
		if (!link) return;

		const value = link.getAttribute('href');
		if (!value) return;

		const target = document.querySelector(value);
		if (!target) return;

		target.scrollIntoView({
			behavior: 'smooth',
			block: 'start'
		});
	}
}

if (!customElements.get('component-anchor-nav')) {
	customElements.define('component-anchor-nav', ComponentAnchorNav);
}
