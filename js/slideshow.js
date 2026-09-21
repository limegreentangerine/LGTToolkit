class ComponentSlideshow extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.component-slideshow');
		if (!this.container) return;

		this.options = JSON.parse(this.container.dataset.options);
		console.log(this.options);

		this.track = this.container.querySelector('.component-slideshow__track');
		this.slides = this.track.querySelectorAll('.component-slideshow__slide');

		this.nav = this.container.querySelector('.component-slideshow__buttons');
		if (!this.nav) return;
		this.prevButton = this.nav.querySelector('.component-slideshow__buttons--prev');
		this.nextButton = this.nav.querySelector('.component-slideshow__buttons--next');

		this.pagination = this.container.querySelector('.component-slideshow__pagination');
		if (!this.pagination) return;
	}
}

if (!customElements.get('component-slideshow')) {
	customElements.define('component-slideshow', ComponentSlideshow);
}
