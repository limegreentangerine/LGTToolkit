$(function () {
	const mapElement = $('.block__lgt-mapbox');

	if (mapElement.length) {
		$.ajax({
			url: '/ajax/mapbox',
			dataType: 'json',
			success: function (response) {
				if (response.apiKey) {
					mapboxgl.accessToken = response.apiKey;

					$.each(mapElement, function (k, v) {
						// set vars
						var map, mapCenter;
						var element = $(v);
						var mapId = element.attr('id');
						var config = element.data('config');

						// config map
						mapCenter = new mapboxgl.LngLat(
							config.centerLongitude,
							config.centerLatitude
						);
						map = new mapboxgl.Map({
							container: mapId,
							center: mapCenter,
							// zoom: config.zoom,
							// pitch: config.pitch,
							interactive: config.interactive,
							style: config.theme,
							antialias: true
						});

						if (config.show_controls) {
							var navOptions = {
								showCompass: true,
								showZoom: true
							};

							if (config.pitch > 0) {
								navOptions.visualizePitch = true;
							}

							const nav = new mapboxgl.NavigationControl(navOptions);
							map.addControl(nav, config.control_placement);
						}

						if (config.showBuildings) {
							// The 'building' layer in the mapbox-streets vector source contains building-height
							// data from OpenStreetMap.
							map.on('load', function () {
								// Insert the layer beneath any symbol layer.
								var layers = map.getStyle().layers;

								var labelLayerId;
								for (var i = 0; i < layers.length; i++) {
									if (
										layers[i].type === 'symbol' &&
										layers[i].layout['text-field']
									) {
										labelLayerId = layers[i].id;
										break;
									}
								}

								map.addLayer(
									{
										id: '3d-buildings',
										source: 'composite',
										'source-layer': 'building',
										filter: ['==', 'extrude', 'true'],
										type: 'fill-extrusion',
										minzoom: 15,
										paint: {
											'fill-extrusion-color': config.extrusionColor,

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
									},
									labelLayerId
								);
							});

							setTimeout(
								flyToLocation(
									map,
									mapCenter,
									config.zoom,
									config.pitch,
									config.markers
								),
								500
							);
						} else {
							setTimeout(
								flyToLocation(
									map,
									mapCenter,
									config.zoom,
									config.pitch,
									config.markers
								),
								500
							);
						}
					});
				} else {
					console.error('API Key required in LGT Toolkit Dashboard');
					mapElement.append(
						'<div class="alert alert-danger">API key missing in dashboard</div>'
					);
				}
			}
		});
	}
});

function addMarkers(map, markers) {
	$.each(markers, function (_, v) {
		var marker = v;
		var coords = new mapboxgl.LngLat(marker.longitude, marker.latitude);
		var uiMarker;

		if (marker.markerColor !== '') {
			uiMarker = new mapboxgl.Marker({
				color: marker.markerColor
			});
		} else {
			uiMarker = new mapboxgl.Marker();
		}

		uiMarker.setLngLat(coords).addTo(map);
	});
}

function flyToLocation(map, mapCenter, zoom, pitch, markers) {
	if (markers !== false) {
		addMarkers(map, markers);
	}

	map.flyTo({
		center: mapCenter,
		zoom: zoom,
		pitch: pitch
	});
}
