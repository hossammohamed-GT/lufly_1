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
    const pinType = location.dataset.pinType || "agent";
    const latitude = Number(location.dataset.latitude);
    const longitude = Number(location.dataset.longitude);
    const label = location.textContent.trim();
    const markerContent = document.createElement("span");
    markerContent.className = `agents-map-marker agents-map-marker--${pinType} agents-map-marker--${key}`;
    const markerDot = document.createElement("span");
    markerDot.className = "agents-map-marker-dot";
    markerDot.setAttribute("aria-hidden", "true");

    const markerLabel = document.createElement("span");
    markerLabel.className = "agents-map-marker-label";
    markerLabel.textContent = label;
    markerContent.append(markerDot, markerLabel);

    const marker = L.marker([latitude, longitude], {
      icon: L.divIcon({
        className: `agents-map-icon agents-map-${pinType}-icon`,
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

  // European Hub visual indicator on Czechia dealer marker
  const czechiaPinEl = markerElements.get("czechia");
  if (czechiaPinEl) {
    czechiaPinEl.classList.add("agents-map-marker--europe-hub");
    const hubBadge = document.createElement("span");
    hubBadge.className = "agents-map-hub-badge";
    hubBadge.textContent = "EU Hub";
    hubBadge.setAttribute("aria-hidden", "true");
    czechiaPinEl.appendChild(hubBadge);
  }

  // Parse directional routes configuration
  let routesConfig = null;
  try {
    if (mapElement.dataset.mapRoutes) {
      routesConfig = JSON.parse(mapElement.dataset.mapRoutes);
    }
  } catch (err) {
    console.warn("Failed to parse map routes config:", err);
  }

  if (!routesConfig || !routesConfig.origins) {
    routesConfig = {
      origins: {
        czechia: {
          id: "czechia",
          latitude: 50.0755,
          longitude: 14.4378,
          routes: {
            poland: { id: "poland", name: "Poland", latitude: 52.2297, longitude: 21.0122, style: "europe", curve_bend: -0.16 },
            italy: { id: "italy", name: "Italy", latitude: 41.9028, longitude: 12.4964, style: "europe", curve_bend: 0.14 },
            spain: { id: "spain", name: "Spain", latitude: 40.4168, longitude: -3.7038, style: "europe", curve_bend: 0.18 },
          },
        },
        turkey: {
          id: "turkey",
          latitude: 37.0662,
          longitude: 37.3833,
          routes: {
            egypt: { id: "egypt", name: "Egypt", latitude: 30.0444, longitude: 31.2357, style: "highlighted", is_dealer: true, curve_bend: -0.16 },
            uae: { id: "uae", name: "Dubai", latitude: 25.2048, longitude: 55.2708, style: "highlighted", is_dealer: true, curve_bend: -0.18 },
            morocco: { id: "morocco", name: "Morocco", latitude: 33.5731, longitude: -7.5898, style: "europe", curve_bend: 0.20 },
            algeria: { id: "algeria", name: "Algeria", latitude: 36.7538, longitude: 3.0588, style: "europe", curve_bend: 0.16 },
            libya: { id: "libya", name: "Libya", latitude: 32.8872, longitude: 13.1913, style: "europe", curve_bend: 0.12 },
            jordan: { id: "jordan", name: "Jordan", latitude: 31.9539, longitude: 35.9106, style: "europe", is_dealer: true, curve_bend: 0.08 },
            saudi_arabia: { id: "saudi_arabia", name: "Saudi Arabia", latitude: 24.7136, longitude: 46.6753, style: "europe", is_dealer: true, curve_bend: -0.12 },
            bahrain: { id: "bahrain", name: "Bahrain", latitude: 26.2285, longitude: 50.5860, style: "europe", is_dealer: true, curve_bend: -0.14 },
            qatar: { id: "qatar", name: "Qatar", latitude: 25.2854, longitude: 51.5310, style: "europe", is_dealer: true, curve_bend: -0.16 },
            oman: { id: "oman", name: "Oman", latitude: 23.5880, longitude: 58.3829, style: "europe", is_dealer: true, curve_bend: -0.20 },
            kuwait: { id: "kuwait", name: "Kuwait", latitude: 29.3759, longitude: 47.9774, style: "europe", curve_bend: -0.10 },
          },
        },
      },
    };
  }

  // Quadratic Bezier curve calculation between two geographic coordinates
  const calculateCurvedPoints = (originLatLng, destLatLng, bend = 0.15) => {
    const z = 6;
    const p1 = map.project(originLatLng, z);
    const p2 = map.project(destLatLng, z);
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const dist = Math.hypot(dx, dy);

    if (dist === 0) {
      return { points: [originLatLng, destLatLng], angle: 0, arrowPos: destLatLng };
    }

    const nx = -dy / dist;
    const ny = dx / dist;
    const cx = (p1.x + p2.x) / 2 + nx * (dist * bend);
    const cy = (p1.y + p2.y) / 2 + ny * (dist * bend);

    const points = [];
    const steps = 36;
    for (let i = 0; i <= steps; i += 1) {
      const t = i / steps;
      const it = 1 - t;
      const x = it * it * p1.x + 2 * it * t * cx + t * t * p2.x;
      const y = it * it * p1.y + 2 * it * t * cy + t * t * p2.y;
      points.push(map.unproject(L.point(x, y), z));
    }

    const endDx = p2.x - cx;
    const endDy = p2.y - cy;
    const angle = (Math.atan2(endDy, endDx) * 180) / Math.PI;

    // Arrowhead position slightly before final marker center
    const arrowT = 0.94;
    const arrowIt = 1 - arrowT;
    const ax = arrowIt * arrowIt * p1.x + 2 * arrowIt * arrowT * cx + arrowT * arrowT * p2.x;
    const ay = arrowIt * arrowIt * p1.y + 2 * arrowIt * arrowT * cy + arrowT * arrowT * p2.y;
    const arrowPos = map.unproject(L.point(ax, ay), z);

    return { points, angle, arrowPos, end: destLatLng };
  };

  // Create crisp directional arrowhead SVG icon
  const createArrowheadIcon = (style, angle) => {
    let size = 12;
    let svg = "";

    if (style === "highlighted") {
      size = 18;
      svg = `<svg viewBox="0 0 20 20" class="agents-map-arrowhead agents-map-arrowhead--highlighted" style="transform: rotate(${angle}deg); width: ${size}px; height: ${size}px;" aria-hidden="true">
        <polygon points="3,4 17,10 3,16 6,10" fill="#c9974c" stroke="#ffffff" stroke-width="0.8"/>
      </svg>`;
    } else if (style === "europe") {
      size = 14;
      svg = `<svg viewBox="0 0 20 20" class="agents-map-arrowhead agents-map-arrowhead--europe" style="transform: rotate(${angle}deg); width: ${size}px; height: ${size}px;" aria-hidden="true">
        <polygon points="4,4 16,10 4,16 7,10" fill="#2563eb" stroke="#ffffff" stroke-width="0.6"/>
      </svg>`;
    } else {
      size = 11;
      svg = `<svg viewBox="0 0 20 20" class="agents-map-arrowhead agents-map-arrowhead--subtle" style="transform: rotate(${angle}deg); width: ${size}px; height: ${size}px;" aria-hidden="true">
        <polygon points="5,5 15,10 5,15 7,10" fill="#64748b"/>
      </svg>`;
    }

    return L.divIcon({
      className: "agents-map-arrow-icon",
      html: svg,
      iconSize: [size, size],
      iconAnchor: [size / 2, size / 2],
    });
  };

  // Create discrete destination node marker for non-dealer markets
  const createMarketNodeMarker = (dest, style) => {
    const label = dest.name || dest.city;
    const content = document.createElement("span");
    content.className = `agents-map-market-node agents-map-market-node--${style}`;

    const dot = document.createElement("span");
    dot.className = `agents-map-market-dot agents-map-market-dot--${style}`;
    dot.setAttribute("aria-hidden", "true");

    const text = document.createElement("span");
    text.className = `agents-map-market-label agents-map-market-label--${style}`;
    text.textContent = label;

    content.append(dot, text);

    return L.marker([dest.latitude, dest.longitude], {
      icon: L.divIcon({
        className: "agents-map-icon agents-map-market-icon",
        html: content,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
      }),
      title: `${label} · Trade Destination`,
      keyboard: false,
      riseOnHover: false,
      interactive: false,
    });
  };

  // Render multi-origin directional routes
  const origins = routesConfig.origins || {};
  Object.values(origins).forEach((origin) => {
    const originLatLng = L.latLng(Number(origin.latitude), Number(origin.longitude));
    const routes = Object.values(origin.routes || {});

    routes.forEach((route) => {
      const destLatLng = L.latLng(Number(route.latitude), Number(route.longitude));
      const style = route.style || "subtle";
      const bend = typeof route.curve_bend === "number" ? route.curve_bend : 0.15;
      const curve = calculateCurvedPoints(originLatLng, destLatLng, bend);

      let color = "#64748b";
      let weight = 1.2;
      let opacity = 0.52;
      let dashArray = "4 6";

      if (style === "highlighted") {
        color = "#c9974c";
        weight = 3.5;
        opacity = 0.95;
        dashArray = "9 6";
      } else if (style === "europe") {
        color = "#2563eb";
        weight = 2.1;
        opacity = 0.88;
        dashArray = "6 5";
      }

      // Add polyline route
      L.polyline(curve.points, {
        className: `agents-map-route agents-map-route--${style} agents-map-route--${route.id}`,
        color,
        weight,
        opacity,
        dashArray,
        interactive: false,
      }).addTo(map);

      // Arrowhead removed for cleaner lines

      // If destination is not an existing dealer, add discrete destination node
      if (!route.is_dealer) {
        createMarketNodeMarker(route, style).addTo(map);
        locationsForBounds.push(destLatLng);
      }
    });
  });

  // Comprehensive world view covering Europe, North Africa, Middle East, and Gulf
  const worldBounds = L.latLngBounds([
    [19.0, -11.0], // South-West corner (South of Morocco/Saudi/Oman, West of Spain)
    [54.5, 61.0],  // North-East corner (North of Poland, East of Oman)
  ]);

  const fitWorldMap = (animate = false) => {
    map.invalidateSize({ pan: false });
    map.fitBounds(worldBounds, {
      padding: [32, 32],
      maxZoom: 6,
      animate: Boolean(animate),
    });
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
        window.requestAnimationFrame(() => fitWorldMap(false));
      }
    });
    mapResizeObserver.observe(mapElement);
  }

  window.addEventListener("load", () => fitWorldMap(false), { once: true });
  document.fonts?.ready.then(() => {
    if (!hasInteractedWithMap) fitWorldMap(false);
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
      L.DomEvent.on(button, "click", () => fitWorldMap(true));
      return button;
    },
  });

  new ResetControl({ position: "topright" }).addTo(map);
  window.requestAnimationFrame(() => fitWorldMap(false));
})();
