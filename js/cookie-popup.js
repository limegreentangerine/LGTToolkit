class ComponentCookiePopup extends HTMLElement {
	constructor() {
		super();

		this.timeout = 500;

		this.container = this.querySelector('.lgt-cookie-popup');
		if (!this.container) return;

		this.allowButton = this.container.querySelector('.lgt-cookie-popup__button--allow');
		this.disallowButton = this.container.querySelector('.lgt-cookie-popup__button--disallow');
		if (!this.allowButton || !this.disallowButton) return;

		this.allowButton.addEventListener('click', this.allowCookies.bind(this));
		this.disallowButton.addEventListener('click', this.disallowCookies.bind(this));
	}

	async connectedCallback() {
		try {
			await this.init();
		} catch (error) {
			console.error('Cookie Popup Init Error:', error);
		}
	}

	async init() {
		const response = await fetch('/ajax/check-cookies');
		if (!response.ok) throw new Error(`Error checking cookie status: ${response.statusText}`);

		const allow = await response.json();

		if (this.container.classList.contains('activate') && allow === true) {
			setTimeout(() => {
				this.container.classList.add('notify');
			}, this.timeout);
		}
	}

	async registerResponse(path) {
		const response = await fetch(path);
		if (!response.ok) throw new Error(`Error allowing cookies: ${response.statusText}`);

		const success = await response.json();
		if (success) {
			this.container.classList.remove('notify');
			window.location.reload();
		} else {
			throw new Error('Error registering cookie response');
		}
		try {
		} catch (error) {
			console.error('Cookie Popup Error:', error);
		}
	}

	async allowCookies(event) {
		event.preventDefault();

		try {
			await this.registerResponse('/ajax/allow-cookies');
		} catch (error) {
			console.error('Cookie Popup Error:', error);
		}
	}

	async disallowCookies(event) {
		event.preventDefault();

		try {
			await this.registerResponse('/ajax/disallow-cookies');
		} catch (error) {
			console.error('Cookie Popup Error:', error);
		}
	}
}

if (!customElements.get('component-cookie-popup')) {
	customElements.define('component-cookie-popup', ComponentCookiePopup);
}
