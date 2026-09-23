class ComponentSlideshow extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.component-slideshow');
		if (!this.container) return;

		this.options = JSON.parse(this.container.dataset.options);

		this.track = this.container.querySelector('.component-slideshow__track');
		if (!this.track) return;

		this.slides = Array.from(this.track.querySelectorAll('.component-slideshow__slide') ?? []);
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
		this.perPage = 1;
		this.pages = 0;
		this.showButtons = false;
		this.showPagination = false;
		this.currentPage = 0;

		this.initSlideshow();

		const observer = new ResizeObserver(() => {
			this.initSlideshow();
		});
		observer.observe(this.container);

		this.prevButton.addEventListener('click', this.moveSlide.bind(this));
		this.nextButton.addEventListener('click', this.moveSlide.bind(this));
		this.track.addEventListener('scrollend', this.calculateCurrentPage.bind(this));
	}

	initSlideshow() {
		const breakpoint = this.getCurrentBreakpoint();
		this.slideWidth = 100 / this.options[breakpoint];
		this.gap = this.options.gap[breakpoint];
		this.peek = this.options.peek[breakpoint];
		this.perPage = this.options[breakpoint];
		this.pages = Math.ceil(this.slides.length / this.perPage);
		this.showPagination = this.options.showPagination[breakpoint];
		this.showButtons = this.options.showButtons[breakpoint];
		this.container.style = `--slideWidth:${this.slideWidth}%;--snap:${this.options.snap};--padding:${this.gap}px;--peek:${this.peek}px;`;

		this.buildNav();
		this.toggleButtons();
		this.calculateCurrentPage();
	}

	moveSlide(event) {
		const target = event.currentTarget;
		const title = target.getAttribute('title');

		let nextPageNumber = title === 'Prev' ? this.currentPage - 1 : this.currentPage + 1;
		if (nextPageNumber >= this.pages) nextPageNumber = 0;
		if (nextPageNumber < 0) nextPageNumber = this.pages - 1;

		const nextSlideNumber = nextPageNumber * this.perPage;
		const nextSlide = this.slides[nextSlideNumber];
		if (!nextSlide) return;

		this.currentPage = nextPageNumber;
		this.track.scrollTo({
			left: nextSlide.offsetLeft,
			behavior: 'smooth'
		});
	}

	selectPage(event) {
		const target = event.currentTarget;
		if (!target) return;

		const page = target.dataset.page - 1;
		const nextSlideNumber = page * this.perPage;
		const nextSlide = this.slides[nextSlideNumber];
		if (!nextSlide) return;

		this.currentPage = page;
		this.track.scrollTo({
			left: nextSlide.offsetLeft,
			behavior: 'smooth'
		});
	}

	buildNav() {
		this.pagination.innerHTML = '';

		if (this.showPagination) {
			for (let i = 1; i <= this.pages; i++) {
				this.pagination.insertAdjacentHTML(
					'beforeend',
					`<button type="button" class="component-slideshow__pagination--item" data-page="${i}" aria-label="Page ${i}">${i}</button>`
				);
			}

			for (const button of this.pagination.children) {
				button.addEventListener('click', this.selectPage.bind(this));
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

	calculateCurrentPage() {
		const scrollLeft = this.track.scrollLeft;
		const currentSlide = this.slides.reduce((closestIndex, slide, index) => {
			const currentDistance = Math.abs(slide.offsetLeft - scrollLeft);
			const closestDistance = Math.abs(this.slides[closestIndex].offsetLeft - scrollLeft);

			return currentDistance < closestDistance ? index : closestIndex;
		}, 0);
		const roundedPage = Math.round(currentSlide / this.perPage);

		if (roundedPage !== this.currentPage) {
			const nextSlideNumber = roundedPage * this.perPage;
			const nextSlide = this.slides[nextSlideNumber];
			if (!nextSlide) return;

			this.currentPage = roundedPage;
			this.track.scrollTo({
				left: nextSlide.offsetLeft,
				behavior: 'smooth'
			});
		}

		if (this.currentPage === 0) {
			this.prevButton.setAttribute('disabled', 'disabled');
		} else {
			this.prevButton.removeAttribute('disabled');
		}

		if (this.currentPage >= this.pages - 1) {
			this.nextButton.setAttribute('disabled', 'disabled');
		} else {
			this.nextButton.removeAttribute('disabled');
		}

		for (const button of this.pagination.children) {
			button.classList.remove('active');
		}

		const activePage = this.pagination.querySelector(
			`button[data-page="${this.currentPage + 1}"]`
		);
		if (!activePage) return;
		activePage.classList.add('active');
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

		return 'mobile';
	}

	getBreakpointValue(handle) {
		const bp = getComputedStyle(document.documentElement)
			.getPropertyValue(`--breakpoint-${handle}`)
			.trim();
		return bp !== '' ? bp : null;
	}
}

if (!customElements.get('component-slideshow')) {
	customElements.define('component-slideshow', ComponentSlideshow);
}
