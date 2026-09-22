class ComponentSlideshow extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.component-slideshow');
		if (!this.container) return;

		this.options = JSON.parse(this.container.dataset.options);

		this.track = this.container.querySelector('.component-slideshow__track');
		this.slides = Array.from(this.track.querySelectorAll('.component-slideshow__slide'));
		if (!this.slides) return;

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
		this.currentSlide = 0;

		this.initSlideshow();

		const observer = new ResizeObserver(() => {
			this.initSlideshow();
		});
		observer.observe(this.container);

		this.prevButton.addEventListener('click', this.moveSlide.bind(this));
		this.nextButton.addEventListener('click', this.moveSlide.bind(this));
		this.track.addEventListener('scrollend', this.calculateCurrentSlide.bind(this));
	}

	moveSlide(event) {
		const target = event.currentTarget;
		const title = target.getAttribute('title');
		let nextSlideNumber = (title === 'Prev') ? this.currentSlide - 1 : this.currentSlide + 1;

		if (nextSlideNumber >= this.slides.length || nextSlideNumber < 0) {
			nextSlideNumber = 0;
		}

		const nextSlide = this.slides[nextSlideNumber];
		this.track.scrollTo({
			left: nextSlide.offsetLeft,
			behavior: 'smooth'
		});
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
		this.calculateCurrentSlide();
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

	calculateCurrentSlide() {
		const scrollLeft = this.track.scrollLeft;
		this.currentSlide = this.slides.reduce((closestIndex, slide, index) => {
			const currentDistance = Math.abs(slide.offsetLeft - scrollLeft);
			const closestDistance = Math.abs(this.slides[closestIndex].offsetLeft - scrollLeft);
			return currentDistance < closestDistance ? index : closestIndex;
		}, 0);

		// TODO: per page scrolling

		if (this.currentSlide === 0) {
			this.prevButton.setAttribute('disabled', 'disabled');
		} else {
			this.prevButton.removeAttribute('disabled');
		}

		if (this.currentSlide >= this.slides.length) {
			this.nextButton.setAttribute('disabled', 'disabled');
		} else {
			this.nextButton.removeAttribute('disabled');
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
