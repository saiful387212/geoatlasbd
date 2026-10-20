/* Leaflet init for the project detail page. */
(function () {
    'use strict';

    const el = document.querySelector('.project-map');
    if (!el || typeof L === 'undefined') return;

    const lat  = parseFloat(el.dataset.lat);
    const lng  = parseFloat(el.dataset.lng);
    const zoom = parseInt(el.dataset.zoom, 10) || 8;

    if (!isFinite(lat) || !isFinite(lng)) return;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return;

    const map = L.map(el, {
        center: [lat, lng],
        zoom: zoom,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" rel="noopener">OpenStreetMap</a> contributors',
    }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(el.dataset.title || 'Study area');
    if (zoom >= 5) marker.openPopup();

    map.on('focus', () => map.scrollWheelZoom.enable());
    map.on('blur',  () => map.scrollWheelZoom.disable());
})();