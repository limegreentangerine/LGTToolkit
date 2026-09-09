class ComponentRedirectAttrForm extends HTMLElement {
	constructor() {
		super();

		this.typeSelect = this.querySelector('[data-redirect-type-select]');
		if (!this.typeSelect) return;

		this.external = this.querySelector('[data-redirect-external]');
		this.internal = this.querySelector('[data-redirect-page]');
		if (!this.external || !this.internal) return;

		this.typeSelect.addEventListener('change', this.toggleValueInput.bind(this));
	}

	toggleValueInput(event) {
		event.preventDefault();
		const target = event.currentTarget;
		if (!target) return;

		const value = target.value;
		if (value === 'page') {
			this.internal.classList.remove('d-none');
			this.external.classList.add('d-none');
		} else {
			this.internal.classList.add('d-none');
			this.external.classList.remove('d-none');
		}
	}
}

if (!customElements.get('component-redirect-attr-form')) {
	customElements.define('component-redirect-attr-form', ComponentRedirectAttrForm);
}
