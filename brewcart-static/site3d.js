"use strict";
/* ==========================================================================
   BrewCart — STATIC EDITION — 3D extras (site3d.js)
   --------------------------------------------------------------------------
   1) An interactive 3D pouring scene in the home-page hero, built with
      Three.js from simple geometry (no model files): a steel kettle tips
      forward and pours a flowing stream into the cup — the coffee level
      rises, ripples spread and droplets splash at the impact point, then
      the kettle lifts and the loop restarts. Auto-rotates; drag to spin.
   2) A subtle 3D tilt on menu cards that follows the mouse (all pages).

   Everything degrades gracefully: no WebGL / no Three.js / reduced-motion
   simply leaves the page exactly as it was (reduced-motion gets one
   still frame, mid-pour).
   ========================================================================== */

(function () {
  var reducedMotion = window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ------------------------------------------------------------------ */
  /* 3D tilt on menu cards (no dependency on Three.js)                   */
  /* ------------------------------------------------------------------ */
  (function initTilt() {
    if (reducedMotion || !window.PointerEvent) return;
    var tilted = null;

    function reset(card) {
      if (!card) return;
      card.classList.remove("tilting");
      card.style.transform = "";
    }

    document.addEventListener("pointermove", function (e) {
      if (e.pointerType && e.pointerType !== "mouse" && e.pointerType !== "pen") return;
      var card = e.target && e.target.closest ? e.target.closest(".item-card") : null;
      if (card !== tilted) { reset(tilted); tilted = card; }
      if (!card) return;
      var r = card.getBoundingClientRect();
      if (!r.width || !r.height) return;
      var px = (e.clientX - r.left) / r.width - 0.5;   // -0.5 .. 0.5
      var py = (e.clientY - r.top) / r.height - 0.5;
      card.classList.add("tilting");
      card.style.transform =
        "perspective(720px) rotateX(" + (-py * 7).toFixed(2) + "deg)" +
        " rotateY(" + (px * 9).toFixed(2) + "deg) translateY(-5px)";
    }, { passive: true });

    document.addEventListener("pointerleave", function () { reset(tilted); tilted = null; });
  })();

  /* ------------------------------------------------------------------ */
  /* Interactive 3D pouring scene (home page hero only)                  */
  /* ------------------------------------------------------------------ */
  var canvas = document.getElementById("cup3d");
  if (!canvas || !window.THREE) { hide3d(); return; }

  function hide3d() {
    var wrap = document.getElementById("hero-3d");
    if (wrap) wrap.style.display = "none";
  }

  var renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
  } catch (err) { hide3d(); return; }

  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  if (THREE.sRGBEncoding) renderer.outputEncoding = THREE.sRGBEncoding;

  var scene = new THREE.Scene();
  var camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
  camera.position.set(0, 1.9, 8.8);
  camera.lookAt(0, 0.3, 0);

  scene.add(new THREE.AmbientLight(0xfff1de, 0.7));
  var key = new THREE.DirectionalLight(0xffffff, 0.95);
  key.position.set(3.5, 5.5, 4);
  scene.add(key);
  var rim = new THREE.PointLight(0xc89b5a, 0.55, 30);
  rim.position.set(-4.5, 2.2, -2.5);
  scene.add(rim);

  var stage = new THREE.Group();
  stage.scale.set(0.92, 0.92, 0.92);
  stage.position.y = -0.15;
  scene.add(stage);

  /* Soft shadow blob grounding the scene */
  var shadow = new THREE.Mesh(
    new THREE.CircleGeometry(1.95, 40),
    new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.16, depthWrite: false })
  );
  shadow.rotation.x = -Math.PI / 2;
  shadow.position.y = -1.0;
  stage.add(shadow);

  /* ---------------- Materials ---------------- */
  var ceramic = new THREE.MeshStandardMaterial({ color: 0xfff7ea, roughness: 0.32, metalness: 0.04, side: THREE.DoubleSide });
  var coffeeMat = new THREE.MeshStandardMaterial({ color: 0x2a1608, roughness: 0.15, metalness: 0.0 });
  var cremaMat = new THREE.MeshStandardMaterial({ color: 0x9a6a35, roughness: 0.5, metalness: 0.0 });
  var sleeveMat = new THREE.MeshStandardMaterial({ color: 0xb3402a, roughness: 0.6, metalness: 0.0, side: THREE.DoubleSide });
  var beanMat = new THREE.MeshStandardMaterial({ color: 0x3a2110, roughness: 0.45, metalness: 0.05 });
  var steelMat = new THREE.MeshStandardMaterial({ color: 0xcfd4d8, roughness: 0.3, metalness: 0.9 });
  var darkMat = new THREE.MeshStandardMaterial({ color: 0x1d1a17, roughness: 0.55, metalness: 0.2 });

  /* ---------------- The cup ---------------- */
  var cup = new THREE.Group();
  cup.position.y = 0.15;
  stage.add(cup);

  var body = new THREE.Mesh(new THREE.CylinderGeometry(1.02, 0.74, 1.5, 64, 1, true), ceramic);
  cup.add(body);
  var bottom = new THREE.Mesh(new THREE.CircleGeometry(0.74, 48), ceramic);
  bottom.rotation.x = Math.PI / 2;
  bottom.position.y = -0.75;
  cup.add(bottom);
  var rimRing = new THREE.Mesh(new THREE.TorusGeometry(1.02, 0.05, 16, 72), ceramic);
  rimRing.rotation.x = Math.PI / 2;
  rimRing.position.y = 0.75;
  cup.add(rimRing);
  var handle = new THREE.Mesh(new THREE.TorusGeometry(0.5, 0.1, 16, 48, Math.PI), ceramic);
  handle.position.set(1.0, 0.05, 0);
  handle.rotation.z = -Math.PI / 2;
  cup.add(handle);
  var sleeve = new THREE.Mesh(new THREE.CylinderGeometry(0.985, 0.9, 0.54, 64, 1, true), sleeveMat);
  sleeve.position.y = -0.14;
  cup.add(sleeve);

  var saucer = new THREE.Group();
  var plate = new THREE.Mesh(new THREE.CylinderGeometry(1.66, 1.18, 0.14, 64), ceramic);
  saucer.add(plate);
  var plateRim = new THREE.Mesh(new THREE.TorusGeometry(1.6, 0.045, 14, 72), ceramic);
  plateRim.rotation.x = Math.PI / 2;
  plateRim.position.y = 0.07;
  saucer.add(plateRim);
  saucer.position.y = -0.98;
  cup.add(saucer);

  var beanGeo = new THREE.SphereGeometry(0.13, 14, 10);
  [[1.28, 0.35], [1.42, -0.3], [1.05, -0.95], [0.45, -1.35],
   [-0.6, -1.25], [-1.25, -0.55], [-1.4, 0.28], [-0.95, 0.95], [0.2, 1.4]
  ].forEach(function (spot, i) {
    var bean = new THREE.Mesh(beanGeo, beanMat);
    bean.scale.set(1, 0.6, 0.78);
    bean.position.set(spot[0], -0.86, spot[1]);
    bean.rotation.y = i * 1.37;
    cup.add(bean);
  });

  /* Coffee surface (rises while pouring). Holds the liquid disc, a crema
     ring, and the ripple rings at the pour impact point (x = -0.15). */
  var LEVEL_LOW = 0.08, LEVEL_HIGH = 0.6;
  var surface = new THREE.Group();
  surface.position.y = LEVEL_LOW;
  cup.add(surface);
  var coffeeDisc = new THREE.Mesh(new THREE.CircleGeometry(0.94, 48), coffeeMat);
  coffeeDisc.rotation.x = -Math.PI / 2;
  surface.add(coffeeDisc);
  var crema = new THREE.Mesh(new THREE.TorusGeometry(0.86, 0.045, 10, 56), cremaMat);
  crema.rotation.x = Math.PI / 2;
  crema.position.y = 0.005;
  surface.add(crema);

  var ripples = [];
  for (var r = 0; r < 3; r++) {
    var ripple = new THREE.Mesh(
      new THREE.RingGeometry(0.07, 0.1, 32),
      new THREE.MeshBasicMaterial({ color: 0xd9a45b, transparent: true, opacity: 0, side: THREE.DoubleSide, depthWrite: false })
    );
    ripple.rotation.x = -Math.PI / 2;
    ripple.position.set(-0.15, 0.012, 0);
    ripple.userData.offset = r / 3;
    surface.add(ripple);
    ripples.push(ripple);
  }

  /* Splash droplets at the impact point */
  var droplets = [];
  var dropGeo = new THREE.SphereGeometry(0.03, 8, 6);
  for (var d = 0; d < 9; d++) {
    var drop = new THREE.Mesh(dropGeo, coffeeMat);
    drop.visible = false;
    drop.userData = { vx: 0, vy: 0, vz: 0, life: -Math.random() };
    cup.add(drop);
    droplets.push(drop);
  }

  /* Steam puffs above the cup */
  var steam = [];
  for (var s = 0; s < 7; s++) {
    var puff = new THREE.Mesh(
      new THREE.SphereGeometry(0.16, 10, 8),
      new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0, depthWrite: false })
    );
    puff.userData.phase = s / 7;
    cup.add(puff);
    steam.push(puff);
  }

  /* ---------------- The kettle (pivot = spout tip) ----------------
     Built around its spout tip at the group origin, so rotating the group
     tips the pot exactly like a real pour. Body sits up-left of the tip. */
  var kettle = new THREE.Group();
  kettle.position.set(-0.15, 1.98, 0);
  stage.add(kettle);

  var potBody = new THREE.Mesh(new THREE.CylinderGeometry(0.5, 0.62, 0.95, 48), steelMat);
  potBody.position.set(-0.55, 0.55, 0);
  kettle.add(potBody);
  var potBase = new THREE.Mesh(new THREE.CylinderGeometry(0.63, 0.63, 0.07, 48), darkMat);
  potBase.position.set(-0.55, 0.06, 0);
  kettle.add(potBase);
  var potLid = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.46, 0.09, 48), steelMat);
  potLid.position.set(-0.55, 1.06, 0);
  kettle.add(potLid);
  var potKnob = new THREE.Mesh(new THREE.SphereGeometry(0.09, 16, 12), darkMat);
  potKnob.position.set(-0.55, 1.17, 0);
  kettle.add(potKnob);
  var potHandle = new THREE.Mesh(new THREE.TorusGeometry(0.34, 0.055, 12, 36, Math.PI), darkMat);
  potHandle.position.set(-1.02, 0.55, 0);
  potHandle.rotation.z = Math.PI / 2;
  kettle.add(potHandle);
  var spoutCurve = new THREE.CatmullRomCurve3([
    new THREE.Vector3(-0.2, 0.92, 0),
    new THREE.Vector3(-0.03, 0.55, 0),
    new THREE.Vector3(0.03, 0.2, 0),
    new THREE.Vector3(0, -0.04, 0)
  ]);
  var spout = new THREE.Mesh(new THREE.TubeGeometry(spoutCurve, 20, 0.055, 10), steelMat);
  kettle.add(spout);

  /* ---------------- The pour stream ----------------
     Unit-height cylinder, geometry pre-shifted so it hangs DOWN from its
     origin (the spout tip). A scrolling canvas texture sells the flow. */
  var streamTex = null;
  try {
    var sc = document.createElement("canvas");
    sc.width = 32; sc.height = 128;
    var g = sc.getContext("2d");
    var grad = g.createLinearGradient(0, 0, 32, 0);
    grad.addColorStop(0, "#4a2a12");
    grad.addColorStop(0.5, "#8a5a26");
    grad.addColorStop(1, "#3c2110");
    g.fillStyle = grad;
    g.fillRect(0, 0, 32, 128);
    g.fillStyle = "rgba(230,180,110,0.55)";
    g.fillRect(6, 0, 4, 128);
    g.fillRect(20, 0, 3, 128);
    streamTex = new THREE.CanvasTexture(sc);
    streamTex.wrapS = streamTex.wrapT = THREE.RepeatWrapping;
    streamTex.repeat.set(1, 2);
  } catch (err) { streamTex = null; }

  var streamGeo = new THREE.CylinderGeometry(0.03, 0.05, 1, 12, 1, true);
  streamGeo.translate(0, -0.5, 0);
  var stream = new THREE.Mesh(streamGeo, new THREE.MeshBasicMaterial({
    map: streamTex || null, color: streamTex ? 0xffffff : 0x6b4420,
    transparent: true, opacity: 0.96
  }));
  stream.position.set(-0.15, 1.96, 0);
  stream.visible = false;
  stage.add(stream);

  /* ---------------- Pour cycle ----------------
     11s loop: kettle tips in (0-1.2s) -> pours, level rises (1.2-6.8s)
     -> kettle lifts, stream dies (6.8-8.2s) -> rest & steam (8.2-11s). */
  var CYCLE = 11;
  function ramp(x, a, b) { var p = (x - a) / (b - a); return p < 0 ? 0 : p > 1 ? 1 : p * p * (3 - 2 * p); }

  function pose(t, dt) {
    var cyc = t % CYCLE;
    var pour = Math.min(ramp(cyc, 0, 1.2), 1 - ramp(cyc, 6.8, 8.2)); // 0..1
    var fill = ramp(cyc, 1.4, 6.8);                                   // 0..1
    var level = LEVEL_LOW + (LEVEL_HIGH - LEVEL_LOW) * fill;

    kettle.rotation.z = 0.06 + (-0.52 - 0.06) * pour;
    surface.position.y = level;
    coffeeDisc.rotation.z = t * 0.25 * (0.3 + pour);                 // gentle swirl

    /* Stream: hangs from spout tip to the (rising) surface */
    var surfWorldY = cup.position.y + level;
    var len = Math.max(0.05, stream.position.y - surfWorldY);
    var thick = pour * (1 + 0.1 * Math.sin(t * 34));
    stream.visible = pour > 0.02;
    stream.scale.set(Math.max(0.001, thick), len, Math.max(0.001, thick));
    stream.position.x = -0.15 + Math.sin(t * 2.6) * 0.008 * pour;
    if (streamTex && dt > 0) streamTex.offset.y -= dt * 2.2 * pour;

    /* Ripples expand & fade while pouring */
    ripples.forEach(function (ripple) {
      var age = (t * 1.15 + ripple.userData.offset) % 1;
      var sc2 = 0.6 + age * 3.4;
      ripple.scale.set(sc2, sc2, sc2);
      ripple.material.opacity = pour > 0.5 ? 0.5 * (1 - age) : 0;
    });

    /* Droplets splash up and fall back at the impact point */
    droplets.forEach(function (drop) {
      var u = drop.userData;
      u.life -= dt;
      if (u.life <= 0) {
        if (pour > 0.5 && dt > 0) {
          u.life = 0.25 + Math.random() * 0.4;
          u.vx = (Math.random() - 0.5) * 0.7;
          u.vz = (Math.random() - 0.5) * 0.7;
          u.vy = 0.9 + Math.random() * 0.8;
          drop.position.set(-0.15, level + 0.02, 0);
          drop.visible = true;
        } else { drop.visible = false; u.life = 0.1; }
        return;
      }
      if (!drop.visible) return;
      u.vy -= 5.5 * dt;
      drop.position.x += u.vx * dt;
      drop.position.y += u.vy * dt;
      drop.position.z += u.vz * dt;
      if (drop.position.y < level) drop.visible = false;
    });

    /* Steam drifts up (a touch stronger right after the pour) */
    var steamBoost = 0.75 + 0.45 * (1 - pour) * fill;
    steam.forEach(function (puff) {
      var p = (t * 0.14 + puff.userData.phase) % 1;
      puff.position.set(Math.sin(p * 5.2 + puff.userData.phase * 9) * 0.22, level + 0.35 + p * 1.45, 0);
      var sc3 = 0.7 + p * 1.7;
      puff.scale.set(sc3, sc3 * 1.25, sc3);
      puff.material.opacity = 0.3 * Math.sin(p * Math.PI) * steamBoost;
    });

    cup.position.y = 0.15 + Math.sin(t * 0.9) * 0.03;
  }

  /* ---------------- Interaction: drag to spin the stage ---------------- */
  var targetRotY = 0.55, targetRotX = 0.05, curRotX = 0.05;
  var dragging = false, lastX = 0, lastY = 0;
  canvas.addEventListener("pointerdown", function (e) {
    dragging = true; lastX = e.clientX; lastY = e.clientY;
    if (canvas.setPointerCapture) canvas.setPointerCapture(e.pointerId);
  });
  canvas.addEventListener("pointermove", function (e) {
    if (!dragging) return;
    targetRotY += (e.clientX - lastX) * 0.008;
    targetRotX += (e.clientY - lastY) * 0.004;
    targetRotX = Math.max(-0.25, Math.min(0.4, targetRotX));
    lastX = e.clientX; lastY = e.clientY;
  });
  function endDrag() { dragging = false; }
  canvas.addEventListener("pointerup", endDrag);
  canvas.addEventListener("pointercancel", endDrag);

  function resize() {
    var w = canvas.clientWidth || 380, h = canvas.clientHeight || 380;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
  }
  window.addEventListener("resize", resize);
  resize();

  if (reducedMotion) {
    pose(4.2, 0); // one still frame, mid-pour
    stage.rotation.y = targetRotY;
    renderer.render(scene, camera);
    return;
  }

  var clock = new THREE.Clock();
  var elapsed = 0;
  (function frame() {
    var dt = Math.min(clock.getDelta(), 0.05);
    elapsed += dt;
    pose(elapsed, dt);
    if (!dragging) targetRotY += dt * 0.22;              // gentle auto-spin
    stage.rotation.y += (targetRotY - stage.rotation.y) * 0.08;
    curRotX += (targetRotX - curRotX) * 0.08;
    stage.rotation.x = curRotX + Math.sin(elapsed * 0.7) * 0.015;
    renderer.render(scene, camera);
    requestAnimationFrame(frame);
  })();
})();
