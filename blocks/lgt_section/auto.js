class LgtSectionLinkType extends HTMLElement {
	constructor() {
		super();

		this.select = this.querySelector('#linkType');
		this.linkTypes = this.querySelectorAll('[id^="LinkType-"]');

		this.select.addEventListener('change', this.changeLinkType.bind(this));
		this.initView();
	}

	initView() {
		const value = this.select.value;

		[...this.linkTypes].forEach((linkType) => {
			if (linkType.dataset.id == value) {
				linkType.classList.remove('visually-hidden');
			} else {
				linkType.classList.add('visually-hidden');
			}
		});
	}

	changeLinkType(event) {
		event.preventDefault();
		const value = event.target.value;

		[...this.linkTypes].forEach((linkType) => {
			if (linkType.dataset.id == value) {
				linkType.classList.remove('visually-hidden');
			} else {
				linkType.classList.add('visually-hidden');
			}
		});
	}
}

if (!customElements.get('lgt-section-link-type')) {
	customElements.define('lgt-section-link-type', LgtSectionLinkType);
}

// const lgtSectionLinkTypeSelect    = $('.lgt-section-link-select');
// const lgtSectionLinkTypes         = $('[data-id^=]');

// lgtSectionLinkTypeSelect.on('change', function(e) {
//     e.preventDefault();
//     const $this = $(e.currentTarget);
//     const value = $this.val();

//     lgtSectionLinkTypes.addClass('visually-hidden');

//     if (value.length) {
//         $('[data-id="' + value + '"]').removeClass('visually-hidden');
//     }
// });

// lgtSectionLinkTypeSelect.trigger('change');
