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
      if (card.hidden) return;

      const copyTargets = [...card.querySelectorAll(".home-copy-target")];
      copyTargets.forEach((target) => target.classList.remove("home-copy-visible"));
      window.requestAnimationFrame(() => {
        copyTargets.forEach((target) => target.classList.add("home-copy-visible"));
      });
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

  const routeZoom = 5;
  const routeOrigin = map.project(factory, routeZoom);
  const routeTargets = locations.map((marker, index) => {
    const key = locationData[index]?.dataset.agentPin;
    const point = map.project(marker.getLatLng(), routeZoom);
    const dx = point.x - routeOrigin.x;
    const dy = point.y - routeOrigin.y;

    return {
      marker,
      key,
      point,
      distance: Math.hypot(dx, dy),
      angle: (Math.atan2(dy, dx) + Math.PI * 2) % (Math.PI * 2),
    };
  });
  const sortedTargets = [...routeTargets].sort((a, b) => a.angle - b.angle);
  let largestAngleGap = -1;
  let firstTargetIndex = 0;

  sortedTargets.forEach((target, index) => {
    const next = sortedTargets[(index + 1) % sortedTargets.length];
    const nextAngle = index === sortedTargets.length - 1
      ? next.angle + Math.PI * 2
      : next.angle;
    const gap = nextAngle - target.angle;

    if (gap > largestAngleGap) {
      largestAngleGap = gap;
      firstTargetIndex = (index + 1) % sortedTargets.length;
    }
  });

  const orderedTargets = Array.from(
    { length: sortedTargets.length },
    (_, index) => {
      const target = sortedTargets[(firstTargetIndex + index) % sortedTargets.length];
      const wrapsAround = firstTargetIndex + index >= sortedTargets.length;

      return { ...target, unwrappedAngle: target.angle + (wrapsAround ? Math.PI * 2 : 0) };
    },
  );
  const routeAngles = new Map(
    orderedTargets.map((target, index) => {
      const previousAngle = index === 0
        ? orderedTargets.at(-1).unwrappedAngle - Math.PI * 2
        : orderedTargets[index - 1].unwrappedAngle;
      const nextAngle = index === orderedTargets.length - 1
        ? orderedTargets[0].unwrappedAngle + Math.PI * 2
        : orderedTargets[index + 1].unwrappedAngle;
      const sectorCenter = (previousAngle + nextAngle) / 2;
      const bendAmount = target.key === "egypt" ? 0.12 : 0.38;

      return [
        target.key,
        {
          destination: target.unwrappedAngle,
          start: target.unwrappedAngle +
            (sectorCenter - target.unwrappedAngle) * bendAmount,
        },
      ];
    }),
  );

  routeTargets.forEach(({ marker, key, distance }) => {
    const destination = marker.getLatLng();
    const isHighlightedRoute = key === "uae" || key === "egypt";
    const { destination: destinationAngle, start: startAngle } = routeAngles.get(key);
    const curve = [];

    for (let step = 0; step <= 40; step += 1) {
      const progress = step / 40;
      const angle = startAngle + (destinationAngle - startAngle) * progress;
      const radius = distance * progress;
      const point = L.point(
        routeOrigin.x + Math.cos(angle) * radius,
        routeOrigin.y + Math.sin(angle) * radius,
      );
      curve.push(
        step === 40 ? destination : map.unproject(point, routeZoom),
      );
    }

    L.polyline(curve, {
      className: isHighlightedRoute
        ? `agents-map-route agents-map-route--${key}`
        : "agents-map-route",
      color: isHighlightedRoute ? "#b38345" : "#117d78",
      weight: isHighlightedRoute ? 3 : 2,
      opacity: isHighlightedRoute ? 0.95 : 0.7,
      dashArray: isHighlightedRoute ? "10 6" : "5 8",
      interactive: false,
    }).addTo(map);
  });

  const bounds = L.latLngBounds(locationsForBounds);
  const initialRegionKeys = new Set([
    "uae",
    "saudi_arabia",
    "oman",
    "qatar",
    "bahrain",
    "egypt",
    "jordan",
  ]);
  const initialRegionBounds = L.latLngBounds([
    factory,
    ...locations
      .filter((_, index) =>
        initialRegionKeys.has(locationData[index]?.dataset.agentPin),
      )
      .map((marker) => marker.getLatLng()),
  ]);
  const fitInitialRegion = () => {
    map.invalidateSize({ pan: false });
    map.fitBounds(initialRegionBounds, {
      padding: [32, 32],
      maxZoom: 5,
      animate: false,
    });
  };
  const fitLocations = (animate = true) => {
    map.invalidateSize({ pan: false });
    map.fitBounds(bounds, { padding: [32, 32], maxZoom: 5, animate });
  };

  let hasInteractedWithMap = false;
  const markMapAsInteracted = () => {
    hasInteractedWithMap = true;
  };
  mapElement.addEventListener("pointerdown", markMapAsInteracted, { once: true });
  mapElement.addEventListener("keydown", markMapAsInteracted, { once: true });

  if ("ResizeObserver" in window) {
    const mapResizeObserver = new ResizeObserver(() => {
      map.invalidateSize({ pan: false });
      if (!hasInteractedWithMap) {
        window.requestAnimationFrame(fitInitialRegion);
      }
    });
    mapResizeObserver.observe(mapElement);
  }

  window.addEventListener("load", fitInitialRegion, { once: true });
  document.fonts?.ready.then(() => {
    if (!hasInteractedWithMap) fitInitialRegion();
  });

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
  window.requestAnimationFrame(fitInitialRegion);
})();
