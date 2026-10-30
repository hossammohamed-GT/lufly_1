(() => {
  const mapElement = document.querySelector("[data-agents-map]");

  if (!mapElement) return;

  const status = mapElement.querySelector("[data-map-status]");
  const setStatus = (message) => {
    if (!status) return;
    status.textContent = message;
    status.hidden = message === "";
  };

  if (!window.L) {
    setStatus(mapElement.dataset.mapError || "");
    return;
  }

  const section = mapElement.closest(".agents-map-section");
  const cards = [...(section?.querySelectorAll("[data-agent-card]") ?? [])];
  const locationData = [...mapElement.querySelectorAll("[data-agent-pin]")];
  const factoryData = mapElement.querySelector("[data-map-factory]");

  if (!section || !factoryData || locationData.length === 0) return;

  const factory = L.latLng(
    Number(factoryData.dataset.latitude),
    Number(factoryData.dataset.longitude),
  );
  const map = L.map(mapElement, {
    zoomControl: false,
    scrollWheelZoom: false,
    minZoom: 2,
    maxZoom: 19,
    zoomSnap: 1,
    worldCopyJump: true,
  });
  map.setView(factory, 3);
  const locations = [];
  const markerElements = new Map();
  const tileLayer = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>',
    minZoom: 2,
    maxZoom: 19,
    updateWhenIdle: true,
    keepBuffer: 2,
  });

  L.control.zoom({ position: "topright" }).addTo(map);
  L.control.scale({ position: "bottomleft", metric: true, imperial: false }).addTo(map);

  let loadingTiles = true;
  let tileErrors = 0;
  tileLayer.on("loading", () => {
    if (loadingTiles) setStatus(mapElement.dataset.mapLoading || "");
  });
  tileLayer.on("load", () => {
    loadingTiles = false;
    tileErrors = 0;
    setStatus("");
  });
  tileLayer.on("tileerror", () => {
    tileErrors += 1;
    if (tileErrors >= 3) setStatus(mapElement.dataset.mapError || "");
  });
  tileLayer.addTo(map);

  const selectAgent = (key) => {
    locationData.forEach((location) => {
      const selected = String(location.dataset.agentPin === key);
      location.setAttribute("aria-pressed", selected);
      markerElements
        .get(location.dataset.agentPin)
        ?.setAttribute("aria-pressed", selected);
    });

    cards.forEach((card) => {
      card.hidden = card.dataset.agentCard !== key;
    });
  };

  const locationsForBounds = [factory];

  locationData.forEach((location) => {
    const key = location.dataset.agentPin;
    const latitude = Number(location.dataset.latitude);
    const longitude = Number(location.dataset.longitude);
    const label = location.textContent.trim();
    const markerContent = document.createElement("span");
    markerContent.className = `agents-map-marker agents-map-marker--${key}`;
    const markerDot = document.createElement("span");
    markerDot.className = "agents-map-marker-dot";
    markerDot.setAttribute("aria-hidden", "true");

    const markerLabel = document.createElement("span");
    markerLabel.className = "agents-map-marker-label";
    markerLabel.textContent = label;
    markerContent.append(markerDot, markerLabel);

    const marker = L.marker([latitude, longitude], {
      icon: L.divIcon({
        className: "agents-map-icon agents-map-agent-icon",
        html: markerContent,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
      }),
      title: label,
      keyboard: true,
      riseOnHover: true,
      alt: location.getAttribute("aria-label") || label,
    }).addTo(map);

    const markerElement = marker.getElement();
    if (markerElement) {
      markerElement.setAttribute(
        "aria-label",
        location.getAttribute("aria-label") || label,
      );
      markerElement.setAttribute(
        "aria-controls",
        location.getAttribute("aria-controls") || "",
      );
      markerElement.setAttribute("aria-pressed", location.getAttribute("aria-pressed"));
      markerElements.set(key, markerElement);
    }

    marker.on("mouseover", () => selectAgent(key));
    marker.on("focus", () => selectAgent(key));
    marker.on("click", () => {
      selectAgent(key);
      map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 5), {
        duration: 0.65,
      });
    });

    locations.push(marker);
    locationsForBounds.push(marker.getLatLng());
  });

  const factoryMarker = L.marker(factory, {
    icon: L.divIcon({
      className: "agents-map-icon agents-map-factory-icon",
      html: '<span class="agents-map-factory-marker" aria-hidden="true"></span>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
    }),
    title: mapElement.dataset.mapFactoryLabel || "",
    interactive: false,
    keyboard: false,
  }).addTo(map);

  const routeStyle = {
    className: "agents-map-route",
    color: "#117d78",
    weight: 2,
    opacity: 0.7,
    dashArray: "5 8",
    interactive: false,
  };

  locations.forEach((marker) => {
    const destination = marker.getLatLng();
    const controlLatitude = Math.min(factory.lat, destination.lat) + 10;
    const controlLongitude = (factory.lng + destination.lng) / 2;
    const curve = [];

    for (let step = 0; step <= 24; step += 1) {
      const progress = step / 24;
      const inverse = 1 - progress;
      curve.push([
        inverse ** 2 * factory.lat +
          2 * inverse * progress * controlLatitude +
          progress ** 2 * destination.lat,
        inverse ** 2 * factory.lng +
          2 * inverse * progress * controlLongitude +
          progress ** 2 * destination.lng,
      ]);
    }

    L.polyline(curve, routeStyle).addTo(map);
  });

  const bounds = L.latLngBounds(locationsForBounds);
  const fitLocations = (animate = true) => {
    map.invalidateSize({ pan: false });
    map.fitBounds(bounds, { padding: [32, 32], maxZoom: 5, animate });
  };

  const ResetControl = L.Control.extend({
    onAdd() {
      const button = L.DomUtil.create(
        "button",
        "agents-map-reset",
      );
      button.type = "button";
      button.title = mapElement.dataset.mapResetLabel || "";
      button.setAttribute("aria-label", button.title);
      button.innerHTML =
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10V4m0 0h6M4 4l5 5a8 8 0 1 1-2 7"/></svg>';
      L.DomEvent.disableClickPropagation(button);
      L.DomEvent.on(button, "click", () => fitLocations());
      return button;
    },
  });

  new ResetControl({ position: "topright" }).addTo(map);
  window.requestAnimationFrame(() => fitLocations(false));
})();
