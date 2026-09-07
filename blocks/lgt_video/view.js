class ComponentVideo extends HTMLElement {
	constructor() {
		super();

		this.container = this.querySelector('.block__lgt-video');
		if (!this.container) return;

		this.video = this.container.querySelector('.lgt-video');
		if (!this.video) return;

		this.videoPlayer = this.video.get(0);
		this.progress = this.container.querySelector('.block__lgt-video--controls--progress');
		this.playButton = this.container.querySelector('.block__lgt-video--controls--play');
		this.pauseButton = this.container.querySelector('.block__lgt-video--controls--pause');
		this.autoplay = this.video.classList.contains('lgt-video-autoplay');

		this.progressUpdateInterval;

		if (this.videoPlayer)
			this.progress.addEventListener('onplay', this.updateProgress.bind(this));
		if (this.videoPlayer)
			this.progress.addEventListener('onpause', this.pauseProgress.bind(this));
		if (this.playButton) this.playButton.addEventListener('click', this.playVideo.bind(this));
		if (this.pauseButton)
			this.pauseButton.addEventListener('click', this.pauseVideo.bind(this));
	}

	playVideo(event) {
		event.preventDefault();
		this.container.classList.add('playing');
		this.videoPlayer.play();
	}

	pauseVideo(event) {
		event.preventDefault();
		this.container.classList.remove('playing');
		this.videoPlayer.pause();
	}

	updateProgress() {
		this.progressUpdateInterval = setInterval(() => {
			const progressWrapper = this.progress.querySelector('.progress');
			const progressBar = this.progress.querySelector('.progress-bar');

			if (!progressWrapper || !progressBar) {
				clearInterval(this.progressUpdateInterval);
				return;
			}

			let percentPlayed = this.videoPlayer.currentTime / (this.videoPlayer.duration / 100);
			percentPlayed = percentPlayed.toFixed(2);

			if (percentPlayed < 100) {
				progressWrapper.attr('aria-valuenow', percentPlayed);
				progressBar.width(percentPlayed + '%');
			} else if (percentPlayed >= 100) {
				clearInterval(progressUpdateInterval);
				this.videoPlayer.currentTime = 0;
				this.videoParent.classList.remove('playing');
				progressWrapper.attr('aria-valuenow', 0);
				progressBar.width('0%');
			}
		}, 0);
	}

	pauseProgress() {
		clearInterval(this.progressUpdateInterval);
	}
}

if (!customElements.get('component-video')) {
	customElements.define('component-video', ComponentVideo);
}
