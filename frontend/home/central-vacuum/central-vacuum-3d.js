/* LUFLY central vacuum: a true 3D cutaway of one home, drawn with three.js.

   The model follows how the real system is installed:
   - the power unit stands in the service room on the ground floor;
   - one riser runs up a shaft from the unit to the top floor;
   - each room has a branch that runs under the ceiling and drops down the
     wall into a single wall inlet;
   - dust travels the same route in reverse (inlet -> branch -> riser -> unit),
     and only ever inside the pipes;
   - the hose hangs from an inlet and lies on the floor of the room.

   Loaded only when the card is on screen. Falls back to the SVG drawing if
   WebGL or the module is unavailable. Units: 1 = one storey height. */
(function () {
  'use strict';

  var host = document.querySelector('[data-cv-3d]');
  if (!host) return;

  var visual = host.parentElement;
  var src = host.getAttribute('data-cv-3d-src');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var started = false;
  var visible = false;
  var loopRunning = false;
  var api = null;

  /* ------------------------------------------------------------------ model */

  function buildModel(THREE) {
    var group = new THREE.Group();
    var pipes = [];      /* { mesh, delay } for the draw-in reveal */
    var routes = [];     /* dust routes, inlet -> unit */
    var halos = [];

    var mat = function (color, opts) {
      return new THREE.MeshStandardMaterial(Object.assign({
        color: color, roughness: 0.7, metalness: 0.0
      }, opts || {}));
    };

    var box = function (w, h, d, material, x, y, z) {
      var m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), material);
      m.position.set(x, y, z);
      group.add(m);
      return m;
    };

    /* ----- building: slabs, walls, the cutaway front is left open ----- */
    var slabMat = mat('#e9e1d3', { roughness: 0.85 });
    var wallMat = mat('#f1ebe0', { roughness: 0.9 });
    var glassWall = mat('#dfeaf2', { roughness: 0.2, metalness: 0.1, transparent: true, opacity: 0.22, depthWrite: false });
    var partitionMat = mat('#d6bd98', { roughness: 0.8, transparent: true, opacity: 0.3, depthWrite: false });

    for (var g = 0; g <= 3; g++) {
      box(3.2, 0.08, 2.2, slabMat, 0, g - 0.04, 0);
    }
    for (var f = 0; f <= 2; f++) {
      box(3.2, 1.0, 0.06, wallMat, 0, f + 0.5, -1.1);           /* back wall */
      box(0.06, 1.0, 2.2, glassWall, -1.6, f + 0.5, 0);         /* left wall */
      box(0.06, 1.0, 2.2, glassWall, 1.6, f + 0.5, 0);          /* right wall */
      box(0.06, 1.0, 2.2, partitionMat, 0, f + 0.5, 0);         /* shaft wall */
    }

    /* ----- furniture, so the rooms read as rooms ----- */
    box(0.95, 0.32, 0.42, mat('#8f96a0', { roughness: 0.9 }), -0.75, 1.16, 0.3);   /* sofa */
    box(1.3, 0.9, 0.42, mat('#2f3538', { roughness: 0.35 }), 0.9, 1.45, -0.62);    /* kitchen */
    box(1.2, 0.35, 0.75, mat('#c9b9a4', { roughness: 0.9 }), -0.7, 2.18, -0.6);    /* bed */
    box(1.0, 0.42, 0.45, mat('#e7e2da', { roughness: 0.4 }), 0.9, 2.21, -0.7);    /* bath */

    /* ----- the power unit, service room, ground floor ----- */
    var unit = new THREE.Group();
    unit.position.set(-0.95, 0, -0.5);
    var body = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, 0.62, 40), mat('#1d2226', { roughness: 0.35, metalness: 0.4 }));
    body.position.y = 0.42;
    unit.add(body);
    var head = new THREE.Mesh(new THREE.CylinderGeometry(0.19, 0.2, 0.12, 40), mat('#15181b', { roughness: 0.3, metalness: 0.5 }));
    head.position.y = 0.79;
    unit.add(head);
    var canister = new THREE.Mesh(
      new THREE.CylinderGeometry(0.19, 0.19, 0.22, 40, 1, true),
      mat('#cfe9f2', { roughness: 0.05, metalness: 0.0, transparent: true, opacity: 0.28, depthWrite: false })
    );
    canister.position.y = 0.11;
    unit.add(canister);
    var dust = new THREE.Mesh(new THREE.CylinderGeometry(0.17, 0.17, 0.05, 32), mat('#6b6257', { roughness: 1 }));
    dust.position.y = 0.035;
    unit.add(dust);
    var ring = new THREE.Mesh(
      new THREE.TorusGeometry(0.205, 0.012, 12, 48),
      new THREE.MeshStandardMaterial({ color: '#5fd4e8', emissive: '#5fd4e8', emissiveIntensity: 1.4 })
    );
    ring.rotation.x = Math.PI / 2;
    ring.position.y = 0.25;
    unit.add(ring);
    var port = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 0.12, 20), mat('#2b3136', { metalness: 0.4 }));
    port.rotation.z = Math.PI / 2;
    port.position.set(0.2, 0.5, 0);
    unit.add(port);
    group.add(unit);

    var portPoint = new THREE.Vector3(-0.95 + 0.26, 0.5, -0.5);

    /* ----- the pipe network ----- */
    var pipeMat = new THREE.MeshStandardMaterial({
      color: '#8fdcff', emissive: '#2aa8ff', emissiveIntensity: 0.6, roughness: 0.25, metalness: 0.15
    });
    var hoseMat = mat('#1f2326', { roughness: 0.6 });
    var fittingMat = new THREE.MeshStandardMaterial({ color: '#cfd8df', roughness: 0.3, metalness: 0.6 });

    var pathFrom = function (points) {
      var path = new THREE.CurvePath();
      for (var i = 0; i < points.length - 1; i++) {
        path.add(new THREE.LineCurve3(points[i], points[i + 1]));
      }
      return path;
    };

    var addPipe = function (points, radius, delay) {
      var path = pathFrom(points);
      var geo = new THREE.TubeGeometry(path, Math.max(24, points.length * 24), radius, 14, false);
      var mesh = new THREE.Mesh(geo, pipeMat);
      group.add(mesh);
      pipes.push({ mesh: mesh, delay: delay });
      return mesh;
    };

    var fitting = function (point) {
      var f = new THREE.Mesh(new THREE.SphereGeometry(0.058, 18, 14), fittingMat);
      f.position.copy(point);
      group.add(f);
    };

    /* rooms: one inlet each, on the back wall. ceil = the pipe level under the ceiling */
    var rooms = [
      { x: -0.7, g: 1 }, /* living room */
      { x: 0.9, g: 1 },  /* kitchen */
      { x: -0.7, g: 2 }, /* bedroom */
      { x: 0.9, g: 2 }   /* bathroom */
    ];

    var riserX = 0;
    var riserZ = -0.9;
    var ceil = function (g) { return g + 0.82; };
    var inletY = function (g) { return g + 0.5; };

    /* riser: top floor down to the unit port, inside the shaft */
    var riserPoints = [
      new THREE.Vector3(riserX, ceil(2), riserZ),
      new THREE.Vector3(riserX, 0.5, riserZ),
      new THREE.Vector3(riserX, 0.5, -0.5),
      portPoint
    ];
    addPipe(riserPoints, 0.05, 0.25);
    fitting(new THREE.Vector3(riserX, 0.5, riserZ));
    fitting(new THREE.Vector3(riserX, ceil(1), riserZ));
    fitting(new THREE.Vector3(riserX, ceil(2), riserZ));

    var riserDown = [
      new THREE.Vector3(riserX, ceil(1), riserZ),
      new THREE.Vector3(riserX, 0.5, riserZ),
      new THREE.Vector3(riserX, 0.5, -0.5),
      portPoint
    ];

    rooms.forEach(function (room, i) {
      var inlet = new THREE.Vector3(room.x, inletY(room.g), -1.05);
      var c = ceil(room.g);
      var junction = new THREE.Vector3(riserX, c, riserZ);
      var branch = [
        inlet,
        new THREE.Vector3(room.x, inletY(room.g), -1.0),
        new THREE.Vector3(room.x, c, -1.0),
        new THREE.Vector3(room.x, c, riserZ),
        junction
      ];
      addPipe(branch, 0.035, 0.6 + i * 0.22);
      fitting(new THREE.Vector3(room.x, c, -1.0));

      /* the inlet: a flat white plate with a teal ring, plus a soft glow */
      var plate = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.09, 0.03, 32), mat('#f7f7f5', { roughness: 0.3 }));
      plate.rotation.x = Math.PI / 2;
      plate.position.copy(inlet);
      group.add(plate);
      var inletRing = new THREE.Mesh(
        new THREE.TorusGeometry(0.075, 0.008, 10, 36),
        new THREE.MeshStandardMaterial({ color: '#5fd4e8', emissive: '#5fd4e8', emissiveIntensity: 1.2 })
      );
      inletRing.position.set(room.x, inletY(room.g), -1.035);
      group.add(inletRing);
      halos.push({ position: inlet.clone().add(new THREE.Vector3(0, 0, 0.05)), scale: 0.42 });

      /* dust route: inlet -> branch -> riser -> unit */
      routes.push({
        points: branch.concat(riserDown.slice(1)),
        particles: []
      });
    });

    /* hose: from the living room inlet, lying on the floor */
    var hosePoints = [
      new THREE.Vector3(-0.7, 1.4, -0.98),
      new THREE.Vector3(-0.62, 1.2, -0.78),
      new THREE.Vector3(-0.45, 1.05, -0.5),
      new THREE.Vector3(-0.2, 1.03, -0.22),
      new THREE.Vector3(0.05, 1.03, 0.05),
      new THREE.Vector3(0.22, 1.03, 0.18)
    ];
    var hoseCurve = new THREE.CatmullRomCurve3(hosePoints, false, 'catmullrom', 0.4);
    var hoseGeo = new THREE.TubeGeometry(hoseCurve, 80, 0.024, 10, false);
    var hose = new THREE.Mesh(hoseGeo, hoseMat);
    group.add(hose);
    var nozzle = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.04, 0.09), hoseMat);
    nozzle.position.set(0.25, 1.03, 0.2);
    nozzle.rotation.y = -0.6;
    group.add(nozzle);

    /* dust particles: two per room, travelling inside the pipes only */
    var dustMat = new THREE.MeshBasicMaterial({ color: '#d7f6ff' });
    routes.forEach(function (route, r) {
      route.path = pathFrom(route.points);
      for (var k = 0; k < 2; k++) {
        var p = new THREE.Mesh(new THREE.SphereGeometry(0.03, 10, 8), dustMat);
        group.add(p);
        route.particles.push({ mesh: p, phase: (r * 0.37 + k * 0.5) % 1 });
      }
    });

    return { group: group, pipes: pipes, routes: routes, halos: halos, unit: unit, ring: ring };
  }

  /* ----------------------------------------------------------------- mount */

  function mount(THREE) {
    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (e) {
      return; /* no WebGL: keep the SVG drawing */
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    host.appendChild(renderer.domElement);

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(30, 1, 0.1, 100);
    camera.position.set(5.9, 4.9, 7.2);
    camera.lookAt(0, 1.45, -0.05);

    scene.add(new THREE.HemisphereLight('#ffffff', '#4a4034', 1.1));
    var sun = new THREE.DirectionalLight('#ffe6c4', 1.5);
    sun.position.set(4, 7, 5);
    scene.add(sun);
    var cool = new THREE.PointLight('#5fd4e8', 2.2, 6, 2);
    cool.position.set(-1.6, 1.3, 1.6);
    scene.add(cool);

    var model = buildModel(THREE);
    scene.add(model.group);
    model.group.position.set(0, -0.6, 0);

    /* soft ground glow under the house */
    var canvasTex = function (draw) {
      var c = document.createElement('canvas');
      c.width = c.height = 128;
      var ctx = c.getContext('2d');
      draw(ctx);
      var tex = new THREE.CanvasTexture(c);
      tex.colorSpace = THREE.SRGBColorSpace;
      return tex;
    };
    var haloTex = canvasTex(function (ctx) {
      var grd = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
      grd.addColorStop(0, 'rgba(95,212,232,0.95)');
      grd.addColorStop(0.35, 'rgba(95,212,232,0.35)');
      grd.addColorStop(1, 'rgba(95,212,232,0)');
      ctx.fillStyle = grd;
      ctx.fillRect(0, 0, 128, 128);
    });
    model.halos.forEach(function (h) {
      var sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: haloTex, color: '#ffffff', transparent: true, depthWrite: false, blending: THREE.AdditiveBlending
      }));
      sprite.position.copy(h.position);
      sprite.scale.set(h.scale, h.scale, 1);
      model.group.add(sprite);
    });
    var shadowTex = canvasTex(function (ctx) {
      var grd = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
      grd.addColorStop(0, 'rgba(0,0,0,0.35)');
      grd.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = grd;
      ctx.fillRect(0, 0, 128, 128);
    });
    var shadow = new THREE.Mesh(
      new THREE.PlaneGeometry(4.6, 3.4),
      new THREE.MeshBasicMaterial({ map: shadowTex, transparent: true, depthWrite: false })
    );
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.set(0, -0.62, 0);
    model.group.add(shadow);

    /* reveal: pipes draw themselves in, then dust starts moving */
    var revealStart = performance.now();
    var setReveal = function (now) {
      var elapsed = now - revealStart;
      model.pipes.forEach(function (p) {
        var t = Math.min(1, Math.max(0, (elapsed - p.delay * 1000) / 1000));
        var count = p.mesh.geometry.index ? p.mesh.geometry.index.count : 0;
        p.mesh.geometry.setDrawRange(0, Math.floor(count * (1 - Math.pow(1 - t, 3))));
      });
      return elapsed;
    };

    var resize = function () {
      var w = host.clientWidth || 1;
      var h = host.clientHeight || 1;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      /* keep the whole house in frame on narrow cards */
      camera.position.z = w / h < 1 ? 9.2 : 7.2;
      camera.position.y = w / h < 1 ? 5.6 : 4.9;
      camera.lookAt(0, 1.2, -0.05);
      camera.updateProjectionMatrix();
    };
    resize();
    if (window.ResizeObserver) new ResizeObserver(resize).observe(host);

    /* drag to turn the house; it sways gently on its own */
    var turn = 0;
    var dragging = false;
    var lastX = 0;
    var moved = 0;
    host.style.cursor = 'grab';
    host.addEventListener('pointerdown', function (e) {
      dragging = true;
      moved = 0;
      lastX = e.clientX;
      host.style.cursor = 'grabbing';
    });
    /* a drag turns the model; a plain click still follows the card link */
    host.addEventListener('click', function (e) {
      if (moved > 4) e.preventDefault();
    });
    window.addEventListener('pointerup', function () {
      dragging = false;
      host.style.cursor = 'grab';
    });
    window.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      moved += Math.abs(e.clientX - lastX);
      turn += (e.clientX - lastX) * 0.006;
      lastX = e.clientX;
    });

    var clock = new THREE.Clock();
    var render = function () {
      var now = performance.now();
      var elapsed = setReveal(now);
      var dt = Math.min(0.05, clock.getDelta());

      if (!reduce) {
        model.group.rotation.y = turn + Math.sin(now * 0.00018) * 0.22;
      } else {
        model.group.rotation.y = turn;
      }

      /* dust moves only after the pipes have drawn */
      if (!reduce && elapsed > 2600) {
        model.routes.forEach(function (route) {
          route.particles.forEach(function (p) {
            p.phase = (p.phase + dt * 0.11) % 1;
            p.mesh.position.copy(route.path.getPointAt(p.phase));
          });
        });
      }

      model.ring.material.emissiveIntensity = reduce ? 1.4 : 1.1 + Math.sin(now * 0.0025) * 0.35;
      renderer.render(scene, camera);
    };

    api = { render: render, reduce: reduce };
    render();

    var loop = function () {
      if (!visible || reduce) {
        loopRunning = false;
        return;
      }
      loopRunning = true;
      render();
      window.requestAnimationFrame(loop);
    };
    api.start = function () {
      if (!loopRunning) window.requestAnimationFrame(loop);
    };

    /* show the 3D drawing and hide the SVG fallback */
    visual.classList.add('is-3d');
    api.start();
  }

  /* -------------------------------------------------------------- loading */

  function init() {
    if (started || !src) return;
    started = true;
    import(src).then(function (THREE) {
      mount(THREE);
    }).catch(function () {
      /* keep the SVG drawing */
    });
  }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        visible = entry.isIntersecting;
        if (visible) {
          init();
          if (api) api.start();
        }
      });
    }, { threshold: 0.15 });
    io.observe(host);
  } else {
    visible = true;
    init();
  }
})();
