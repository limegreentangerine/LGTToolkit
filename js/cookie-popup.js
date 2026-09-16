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

		this.consent = null;
		this.injected = false;
	}

	connectedCallback() {
		this.init();
	}

	init() {
		this.consent = this.getConsent();

		switch (this.consent) {
			case 'accepted':
				this.updateGoogleConsent(true);
				this.injectTrackingScripts();
				break;
			case 'declined':
			default:
				this.updateGoogleConsent(false);
				break;
		}

		if (
			this.container.classList.contains('activate') &&
			this.consent !== 'accepted' &&
			this.consent !== 'declined'
		) {
			setTimeout(() => {
				this.container.classList.add('notify');
			}, this.timeout);
		}
	}

	getConsent() {
		return (
			document.cookie
				.split('; ')
				.find((row) => row.startsWith('cookie_consent='))
				?.split('=')[1] ?? null
		);
	}

	updateGoogleConsent(accepted) {
		if (typeof gtag !== 'function') return;

		var state = accepted ? 'granted' : 'denied';

		gtag('consent', 'update', {
			ad_storage: state,
			ad_user_data: state,
			ad_personalization: state,
			analytics_storage: state,
			functionality_storage: state,
			personalization_storage: state
		});
	}

	injectTrackingScripts() {
		if (this.injected) return;
		this.injected = true;

		document
			.querySelectorAll('script[type="text/plain"][data-consent="analytics"]')
			.forEach(function (inert) {
				var code = inert.innerHTML.trim();
				if (!code) return;

				var tmp = document.createElement('div');
				tmp.innerHTML = code;

				tmp.querySelectorAll('script').forEach(function (original) {
					var s = document.createElement('script');
					if (original.src) {
						s.src = original.src;
						s.async = true;
					} else {
						s.innerHTML = original.innerHTML;
					}
					// Carry over any data attributes (e.g. GTM's data-layer-name)
					Array.from(original.attributes).forEach(function (attr) {
						if (attr.name !== 'src' && attr.name !== 'type') {
							s.setAttribute(attr.name, attr.value);
						}
					});
					document.head.appendChild(s);
				});

				// Handle noscript/pixel tags
				tmp.querySelectorAll('noscript, img').forEach(function (el) {
					document.body.appendChild(el.cloneNode(true));
				});
			});
	}

	async registerResponse(path) {
		const response = await fetch(path);
		if (response.ok) {
			this.container.classList.remove('notify');
			window.location.reload();
		} else {
			throw new Error(`Error allowing cookies: ${response.statusText}`);
		}
	}

	async allowCookies(event) {
		event.preventDefault();

		try {
			await this.registerResponse('/ajax/allow-cookies');
			this.updateGoogleConsent(true);
			this.injectTrackingScripts();
		} catch (error) {
			console.error('Cookie Popup Error:', error);
		}
	}

	async disallowCookies(event) {
		event.preventDefault();

		try {
			await this.registerResponse('/ajax/disallow-cookies');
			this.updateGoogleConsent(false);
		} catch (error) {
			console.error('Cookie Popup Error:', error);
		}
	}
}

if (!customElements.get('component-cookie-popup')) {
	customElements.define('component-cookie-popup', ComponentCookiePopup);
}
