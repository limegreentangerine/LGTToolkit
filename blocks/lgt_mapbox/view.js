class ComponentMapbox extends HTMLElement {
	static instanceCount = 0;

	constructor() {
		super();

		this.map = null;
		this.mapCenter = null;

		this.instanceId = ++ComponentMapbox.instanceCount;
        this.buildingsLayerId = `3d-buildings-${this.instanceId}`;
	}

	connectedCallback() {
		if (this.map) return;

		this.container = this.querySelector('.block__lgt-mapbox');
		if (!this.container) return;

		this.config = JSON.parse(this.container.dataset.config);

		this.init();
	}

	disconnectedCallback() {
		this.map?.remove();
		this.map = null;
	}

	async loadApiKey() {
		const response = await fetch('/mapbox/init');
		if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

		const data = await response.json();
		if (!data.apiKey)
			throw new Error(
				`No Mapbox API key in site settings, see ${window.location.host}/dashboard/lgt_toolkit/mapbox`
			);

		this.apiKey = data.apiKey;
	}

	async init() {
		try {
			await this.loadApiKey();

			mapboxgl.accessToken = this.apiKey;

			this.mapCenter = new mapboxgl.LngLat(
				this.config.centerLongitude,
				this.config.centerLatitude
			);

			this.map = new mapboxgl.Map({
				container: this.container,
				center: this.mapCenter,
				interactive: this.config.interactive,
				style: this.config.theme,
				antialias: true
			});

			if (this.config.show_controls) {
				const navOptions = {
					showCompass: true,
					showZoom: true
				};

				if (this.config.pitch > 0) {
					navOptions.visualizePitch = true;
				}

				const nav = new mapboxgl.NavigationControl(navOptions);
				this.map.addControl(nav, this.config.control_placement);
			}

			if (this.config.showBuildings) {
				this.map.on('load', this.showBuildings.bind(this));
			} else {
				this.map.on('load', this.flyToLocation.bind(this));
			}
		} catch (error) {
			console.error('Failed to initialise Mapbox:', error);
		}
	}

	showBuildings() {
		// The 'building' layer in the mapbox-streets vector source contains building-height
		// data from OpenStreetMap.
		const layers = this.map.getStyle().layers;

		let labelLayerId;
		for (const layer of layers) {
			if (layer.type === 'symbol' && layer.layout?.['text-field']) {
				labelLayerId = layer.id;
				break;
			}
		}

		const layerConfig = {
			id: this.buildingsLayerId,
			source: 'composite',
			'source-layer': 'building',
			filter: ['==', 'extrude', 'true'],
			type: 'fill-extrusion',
			minzoom: 15,
			paint: {
				'fill-extrusion-color': this.config.extrusionColor,
				// use an 'interpolate' expression to add a smooth transition effect to the
				// buildings as the user zooms in
				'fill-extrusion-height': [
					'interpolate',
					['linear'],
					['zoom'],
					15,
					0,
					15.05,
					['get', 'height']
				],
				'fill-extrusion-base': [
					'interpolate',
					['linear'],
					['zoom'],
					15,
					0,
					15.05,
					['get', 'min_height']
				],
				'fill-extrusion-opacity': 0.9
			}
		};

		if (labelLayerId) {
			this.map.addLayer(layerConfig, labelLayerId);
		} else {
			this.map.addLayer(layerConfig);
		}

		this.flyToLocation();
	}

	addMarkers() {
		for (const marker of this.config.markers ?? []) {
			const coords = new mapboxgl.LngLat(marker.longitude, marker.latitude);
			const options = marker.markerColor ? { color: marker.markerColor } : undefined;
			const uiMarker = new mapboxgl.Marker(options);
			uiMarker.setLngLat(coords).addTo(this.map);
		}
	}

	flyToLocation() {
		this.addMarkers();

		this.map.flyTo({
			center: this.mapCenter,
			zoom: this.config.zoom,
			pitch: this.config.pitch
		});
	}
}

if (!customElements.get('component-mapbox')) {
	customElements.define('component-mapbox', ComponentMapbox);
}
