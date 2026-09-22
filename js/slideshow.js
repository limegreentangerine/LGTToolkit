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

		this.slideWidth = 100;
		this.gap = 0;
		this.peek = 0;
		this.pages = 0;
		this.showButtons = false;
		this.showPagination = false;

		this.initSlideshow();

		const observer = new ResizeObserver(() => {
			this.initSlideshow();
		});
		observer.observe(this.container);
	}

	initSlideshow() {
		const breakpoint = this.getCurrentBreakpoint();
		this.slideWidth = 100 / this.options[breakpoint];
		this.gap = this.options.gap[breakpoint];
		this.peek = this.options.peek[breakpoint];
		this.pages = Math.ceil(this.slides.length / this.options[breakpoint]);
		this.showPagination = this.options.showPagination[breakpoint];
		this.showButtons = this.options.showButtons[breakpoint];
		this.container.style = `--slideWidth:${this.slideWidth}%;--snap:${this.options.snap};--padding:${this.gap}px;--peek:${this.peek}px;`;

		this.buildNav();
		this.toggleButtons();
	}

	buildNav() {
		this.pagination.innerHTML = '';

		if (this.showPagination) {
			for (let i = 1; i < this.pages; i++) {
				this.pagination.insertAdjacentHTML('beforeend', `<button type="button" class="component-slideshow__nav--item" data-page="${i}" aria-label="Page ${i}">${i}</button>`);
			}
		}
	}

	toggleButtons() {
		if (!this.showButtons) {
			this.nav.classList.add('d-none');
		} else {
			this.nav.classList.remove('d-none');
		}
	}

	getCurrentBreakpoint() {
		const componentWidth = window.innerWidth;
		const desktopBreakpoint = this.getBreakpointValue('lg') ?? 992;
		const hdBreakpoint = this.getBreakpointValue('hd') ?? 1800;

		if (hdBreakpoint !== null && componentWidth >= hdBreakpoint) {
			return 'hd';
		} else if (desktopBreakpoint !== null && componentWidth >= desktopBreakpoint) {
			return 'desktop';
		}

		return 'mobile'
	}

	getBreakpointValue(handle) {
		const bp = getComputedStyle(document.documentElement).getPropertyValue(`--breakpoint-${handle}`).trim();
		return (bp !== '') ? bp : null;
	}
}

if (!customElements.get('component-slideshow')) {
	customElements.define('component-slideshow', ComponentSlideshow);
}
