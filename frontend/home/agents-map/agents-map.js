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
  // Exact marker coordinates, keyed by location id. Route endpoints are always
  // resolved from this map so a line and its marker share one single source of truth.
  const markerCoordinates = new Map();
  // Every map label, so they can be laid out against each other on each view change.
  const labelEntries = [];
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

    markerCoordinates.set(key, L.latLng(latitude, longitude));
    labelEntries.push({
      key,
      latlng: L.latLng(latitude, longitude),
      label: markerLabel,
    });

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

  // Czechia is a hub, not a dealer location: it renders as a blue hub circle
  // (same circle shape as the Türkiye factory marker) instead of a location pin.
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
            poland: { id: "poland", name: "Poland", latitude: 52.2297, longitude: 21.0122, style: "europe", curve_bend: -0.16, marker_color: "blue" },
            italy: { id: "italy", name: "Italy", latitude: 41.9028, longitude: 12.4964, style: "europe", curve_bend: 0.14, marker_color: "blue" },
            spain: { id: "spain", name: "Spain", latitude: 40.4168, longitude: -3.7038, style: "europe", curve_bend: 0.18, marker_color: "blue" },
          },
        },
        turkey: {
          id: "turkey",
          latitude: 37.0662,
          longitude: 37.3833,
          routes: {
            egypt: { id: "egypt", name: "Egypt", latitude: 30.0444, longitude: 31.2357, style: "highlighted", is_dealer: true, curve_bend: 0.16 },
            uae: { id: "uae", name: "Dubai", latitude: 25.2048, longitude: 55.2708, style: "highlighted", is_dealer: true, curve_bend: -0.13 },
            morocco: { id: "morocco", name: "Morocco", latitude: 33.5731, longitude: -7.5898, style: "europe", curve_bend: -0.14, marker_color: "green" },
            algeria: { id: "algeria", name: "Algeria", latitude: 36.7538, longitude: 3.0588, style: "europe", curve_bend: 0.22, marker_color: "green" },
            libya: { id: "libya", name: "Libya", latitude: 32.8872, longitude: 13.1913, style: "europe", curve_bend: 0.14, marker_color: "green" },
            jordan: { id: "jordan", name: "Jordan", latitude: 31.9539, longitude: 35.9106, style: "europe", is_dealer: true, curve_bend: 0.03, marker_color: "green" },
            saudi_arabia: { id: "saudi_arabia", name: "Saudi Arabia", latitude: 24.7136, longitude: 46.6753, style: "europe", is_dealer: true, curve_bend: 0.22, marker_color: "green" },
            bahrain: { id: "bahrain", name: "Bahrain", latitude: 26.2285, longitude: 50.5860, style: "europe", is_dealer: true, curve_bend: 0.06, marker_color: "green" },
            qatar: { id: "qatar", name: "Qatar", latitude: 25.2854, longitude: 51.5310, style: "europe", is_dealer: true, curve_bend: 0.21, marker_color: "green" },
            oman: { id: "oman", name: "Oman", latitude: 23.5880, longitude: 58.3829, style: "europe", is_dealer: true, curve_bend: -0.22, marker_color: "green" },
            kuwait: { id: "kuwait", name: "Kuwait", latitude: 29.3759, longitude: 47.9774, style: "europe", curve_bend: -0.03, marker_color: "green" },
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

  // Create a destination pin marker for non-dealer markets. It reuses the exact
  // location-pin design of the dealer markers (icon box 32x32, anchor 16,16 so the
  // pin centre sits on the coordinate) and only differs by its colour.
  const createDestinationPinMarker = (destLatLng, route, pinColor) => {
    const label = route.name || route.city || route.id;
    const variant = pinColor === "blue" ? "europe" : "middle-east";

    const content = document.createElement("span");
    content.className = [
      "agents-map-marker",
      "agents-map-marker--destination",
      `agents-map-marker--${variant}`,
      `agents-map-marker--${route.id}`,
    ].join(" ");

    const dot = document.createElement("span");
    dot.className = "agents-map-marker-dot";
    dot.setAttribute("aria-hidden", "true");

    const text = document.createElement("span");
    text.className = "agents-map-marker-label";
    text.textContent = label;

    content.append(dot, text);
    labelEntries.push({ key: route.id, latlng: destLatLng, label: text });

    return L.marker(destLatLng, {
      icon: L.divIcon({
        className: `agents-map-icon agents-map-destination-icon agents-map-destination-icon--${variant}`,
        html: content,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
      }),
      title: label,
      keyboard: false,
      riseOnHover: false,
      interactive: false,
      alt: label,
    });
  };

  // Render multi-origin directional routes
  const origins = routesConfig.origins || {};
  Object.values(origins).forEach((origin) => {
    // Start the line on the real origin marker whenever one is rendered.
    const originLatLng =
      markerCoordinates.get(origin.id) ??
      L.latLng(Number(origin.latitude), Number(origin.longitude));
    const routes = Object.values(origin.routes || {});

    routes.forEach((route) => {
      // Use the exact destination marker coordinates so the line always terminates
      // in the centre of the marker, with no gap and no offset.
      const destLatLng =
        markerCoordinates.get(route.id) ??
        L.latLng(Number(route.latitude), Number(route.longitude));
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

      // If the destination is not a dealer marker, render a pin on the very same
      // coordinate as the line endpoint (green for the Middle East, blue for Europe).
      if (!route.is_dealer) {
        const pinColor = route.marker_color === "blue" ? "blue" : "green";
        createDestinationPinMarker(destLatLng, route, pinColor).addTo(map);
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

  /*
   * Label layout
   * -------------
   * Labels sit right next to their own pin (no more fixed 30–60px offsets) and each
   * one is pushed to the first free side of its pin, so a label never covers another
   * location marker, another label, the EU hub badge, or runs outside the map.
   * The layout is recomputed whenever the view settles, so it stays tidy at any zoom.
   */
  const LABEL_PRIORITY = [
    "egypt", "uae", "czechia", "jordan", "saudi_arabia", "kuwait", "qatar",
    "bahrain", "oman", "poland", "italy", "spain", "morocco", "algeria", "libya",
  ];
  const IMPORTANT_LABELS = 5; // always keep the first labels of the priority list
  const PIN_HALF = 16; // iconSize 32 / iconAnchor 16,16 -> pin centre at (16,16)
  const LABEL_GAP_X = 11; // distance from the pin centre to a side label
  const LABEL_GAP_Y = 12; // distance from the pin centre to a top/bottom label
  const MAP_MARGIN = 4;

  const overlapArea = (a, b) => {
    const width = Math.min(a.x + a.w, b.x + b.w) - Math.max(a.x, b.x);
    const height = Math.min(a.y + a.h, b.y + b.h) - Math.max(a.y, b.y);
    return width > 0 && height > 0 ? width * height : 0;
  };

  const placeLabels = () => {
    if (labelEntries.length === 0) return;

    const mapSize = map.getSize();
    const pinPoints = labelEntries.map((entry) =>
      map.latLngToContainerPoint(entry.latlng),
    );
    const pinBoxes = pinPoints.map((point) => ({
      x: point.x - 10,
      y: point.y - 10,
      w: 20,
      h: 20,
    }));

    const order = labelEntries
      .map((entry, index) => ({
        index,
        rank: LABEL_PRIORITY.indexOf(entry.key),
      }))
      .sort(
        (a, b) =>
          (a.rank < 0 ? LABEL_PRIORITY.length : a.rank) -
            (b.rank < 0 ? LABEL_PRIORITY.length : b.rank) || a.index - b.index,
      );

    const placed = [];

    order.forEach(({ index, rank }) => {
      const entry = labelEntries[index];
      const label = entry.label;
      const width =
        label.offsetWidth || Math.max(24, label.textContent.length * 5.4 + 12);
      const height = label.offsetHeight || 14;
      const pin = pinPoints[index];

      const candidates = [
        { left: PIN_HALF + LABEL_GAP_X, top: PIN_HALF - height / 2 }, // right
        { left: PIN_HALF - LABEL_GAP_X - width, top: PIN_HALF - height / 2 }, // left
        { left: PIN_HALF - width / 2, top: PIN_HALF - LABEL_GAP_Y - height }, // top
        { left: PIN_HALF - width / 2, top: PIN_HALF + LABEL_GAP_Y }, // bottom
        { left: PIN_HALF + 8, top: PIN_HALF - height - 6 }, // top-right
        { left: PIN_HALF + 8, top: PIN_HALF + 8 }, // bottom-right
        { left: PIN_HALF - 8 - width, top: PIN_HALF - height - 6 }, // top-left
        { left: PIN_HALF - 8 - width, top: PIN_HALF + 8 }, // bottom-left
        // last resort on cramped maps: the same four sides, one step further out
        { left: PIN_HALF + 20, top: PIN_HALF - height / 2 },
        { left: PIN_HALF - 20 - width, top: PIN_HALF - height / 2 },
        { left: PIN_HALF - width / 2, top: PIN_HALF - 21 - height },
        { left: PIN_HALF - width / 2, top: PIN_HALF + 21 },
      ];

      const obstacles = [];
      pinBoxes.forEach((box, pinIndex) => {
        if (pinIndex !== index) obstacles.push({ ...box, weight: 1 });
      });
      placed.forEach((box) => obstacles.push({ ...box, weight: 2 }));

      const badge = label
        .closest(".leaflet-marker-icon")
        ?.querySelector(".agents-map-hub-badge");
      if (badge && badge.offsetWidth) {
        obstacles.push({
          x: pin.x + PIN_HALF + 12 - badge.offsetWidth,
          y: pin.y - PIN_HALF - 12,
          w: badge.offsetWidth,
          h: badge.offsetHeight || 12,
          weight: 2,
        });
      }

      let best = null;
      candidates.forEach((candidate) => {
        const rect = {
          x: pin.x - PIN_HALF + candidate.left,
          y: pin.y - PIN_HALF + candidate.top,
          w: width,
          h: height,
        };

        const overlap = obstacles.reduce(
          (total, box) => total + overlapArea(rect, box) * box.weight,
          0,
        );

        const overflow =
          Math.max(0, MAP_MARGIN - rect.x) +
          Math.max(0, rect.x + rect.w - (mapSize.x - MAP_MARGIN)) +
          Math.max(0, MAP_MARGIN - rect.y) +
          Math.max(0, rect.y + rect.h - (mapSize.y - MAP_MARGIN));

        const cost = overlap + overflow * 80;

        // keep the candidate order as the tie-breaker so the result is stable
        if (!best || cost < best.cost - 0.01) {
          best = { cost, overlap, candidate, rect };
        }
      });

      label.style.display = "";
      // On cramped screens the low-priority labels step aside instead of piling up.
      const important = rank >= 0 && rank < IMPORTANT_LABELS;
      if (!important && best.overlap > width * height * 0.25) {
        label.style.display = "none";
        return;
      }

      label.style.left = `${best.candidate.left}px`;
      label.style.top = `${best.candidate.top}px`;
      label.style.right = "auto";
      label.style.transform = "none";
      placed.push(best.rect);
    });
  };

  let labelFrame = 0;
  const refreshLabels = () => {
    window.cancelAnimationFrame(labelFrame);
    labelFrame = window.requestAnimationFrame(placeLabels);
  };
  map.on("zoomend moveend", refreshLabels);

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
      refreshLabels();
    });
    mapResizeObserver.observe(mapElement);
  }

  window.addEventListener("load", () => {
    fitWorldMap(false);
    refreshLabels();
  }, { once: true });
  document.fonts?.ready.then(() => {
    if (!hasInteractedWithMap) fitWorldMap(false);
    refreshLabels();
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
  window.requestAnimationFrame(() => {
    fitWorldMap(false);
    refreshLabels();
  });
})();
