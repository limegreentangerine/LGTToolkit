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
		this.autoplay = this.options.autoplay.enabled;
		this.speed = this.options.autoplay.speed * 1000;
		this.useTimer = this.options.autoplay.useTimer;
		this.interval = null;

		this.#initSlideshow();

		const observer = new ResizeObserver(() => {
			this.#initSlideshow();
		});
		observer.observe(this.container);

		this.prevButton.addEventListener('click', this.nextPrevSlide.bind(this));
		this.nextButton.addEventListener('click', this.nextPrevSlide.bind(this));
		this.track.addEventListener('scrollend', this.calculateCurrentPage.bind(this));

		this.#initAutoplay();
	}

	#initSlideshow() {
		const breakpoint = this.#getCurrentBreakpoint();
		this.slideWidth = 100 / this.options[breakpoint];
		this.gap = this.options.gap[breakpoint];
		this.peek = this.options.peek[breakpoint];
		this.perPage = this.options[breakpoint];
		this.pages = Math.ceil(this.slides.length / this.perPage);
		this.showPagination = this.options.showPagination[breakpoint];
		this.showButtons = this.options.showButtons[breakpoint];

		this.#addContainerClasses();
		this.#buildNav();
		this.#toggleButtons();
		this.calculateCurrentPage();
	}

	#addContainerClasses() {
		this.container.style = `--slideWidth:${this.slideWidth}%;--snap:${this.options.snap};--padding:${this.gap}px;--peek:${this.peek}px;--speed:${this.speed}ms;`;
	}

	#goToCurrentPage() {
		const nextSlideNumber = this.currentPage * this.perPage;
		const nextSlide = this.slides[nextSlideNumber];
		if (!nextSlide) return;

		this.track.scrollTo({
			left: nextSlide.offsetLeft,
			behavior: 'smooth'
		});

		if (this.autoplay) {
			this.container.classList.remove('component-slideshow__autoplay');
			this.container.classList.add('component-slideshow__autoplay');
		}
	}

	#getCurrentBreakpoint() {
		const componentWidth = window.innerWidth;
		const desktopBreakpoint = this.#getBreakpointValue('lg') ?? 992;
		const hdBreakpoint = this.#getBreakpointValue('hd') ?? 1800;

		if (hdBreakpoint !== null && componentWidth >= hdBreakpoint) {
			return 'hd';
		} else if (desktopBreakpoint !== null && componentWidth >= desktopBreakpoint) {
			return 'desktop';
		}

		return 'mobile';
	}

	#getBreakpointValue(handle) {
		const bp = getComputedStyle(document.documentElement)
			.getPropertyValue(`--breakpoint-${handle}`)
			.trim();
		return bp !== '' ? bp : null;
	}

	#buildNav() {
		this.pagination.innerHTML = '';

		if (this.showPagination) {
			for (let i = 1; i <= this.pages; i++) {
				this.pagination.insertAdjacentHTML(
					'beforeend',
					`<button type="button" class="component-slideshow__pagination--item" data-page="${i}" aria-label="Page ${i}">
						<div class="component-slideshow__timer"></div>
					</button>`
				);
			}

			for (const button of this.pagination.children) {
				button.addEventListener('click', this.selectPage.bind(this));
			}
		}
	}

	#toggleButtons() {
		if (!this.showButtons) {
			this.nav.classList.add('d-none');
		} else {
			this.nav.classList.remove('d-none');
		}
	}

	#initAutoplay() {
		this.interval = null;

		if (this.autoplay) {
			this.interval = setInterval(() => {
				this.currentPage = this.currentPage === this.pages - 1 ? 0 : this.currentPage + 1;
				this.#goToCurrentPage();
			}, this.speed);

			this.container.classList.add('component-slideshow__autoplay');
		}

		console.log('useTimer', this.useTimer);

		if (this.autoplay && this.useTimer) {
			this.container.classList.add('component-slideshow__autoplay--timer');
		} else {
			this.container.classList.remove('component-slideshow__autoplay--timer');
		}
	}

	calculateCurrentPage() {
		for (const button of this.pagination.children) {
			button.classList.remove('active');
		}

		const activePage = this.pagination.querySelector(
			`button[data-page="${this.currentPage + 1}"]`
		);
		if (!activePage) return;
		activePage.classList.add('active');

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
	}

	nextPrevSlide(event) {
		const target = event.currentTarget;
		const title = target.getAttribute('title');

		let nextPageNumber = title === 'Prev' ? this.currentPage - 1 : this.currentPage + 1;
		if (nextPageNumber >= this.pages) nextPageNumber = 0;
		if (nextPageNumber < 0) nextPageNumber = this.pages - 1;
		this.currentPage = nextPageNumber;
		this.#goToCurrentPage();
		this.#initAutoplay();
	}

	selectPage(event) {
		const target = event.currentTarget;
		if (!target) return;

		this.currentPage = target.dataset.page - 1;
		this.#goToCurrentPage();
		this.#initAutoplay();
	}
}

if (!customElements.get('component-slideshow')) {
	customElements.define('component-slideshow', ComponentSlideshow);
}
