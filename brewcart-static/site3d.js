"use strict";
/* ==========================================================================
   BrewCart — STATIC EDITION — 3D extras (site3d.js)
   --------------------------------------------------------------------------
   1) An interactive 3D coffee cup in the home-page hero, built with
      Three.js from simple geometry (no model files to download): cup,
      brick-red sleeve, coffee surface, saucer and scattered beans, with
      rising steam. Auto-rotates; drag to spin it yourself.
   2) A subtle 3D tilt on menu cards that follows the mouse (all pages).

   Everything degrades gracefully: no WebGL / no Three.js / reduced-motion
   simply leaves the page exactly as it was.
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
  /* Interactive 3D coffee cup (home page hero only)                     */
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
  camera.position.set(0, 1.7, 7.4);
  camera.lookAt(0, 0.05, 0);

  scene.add(new THREE.AmbientLight(0xfff1de, 0.75));
  var key = new THREE.DirectionalLight(0xffffff, 0.95);
  key.position.set(3.5, 5.5, 4);
  scene.add(key);
  var rim = new THREE.PointLight(0xc89b5a, 0.55, 30);
  rim.position.set(-4.5, 2.2, -2.5);
  scene.add(rim);

  /* Materials */
  var ceramic = new THREE.MeshStandardMaterial({ color: 0xfff7ea, roughness: 0.32, metalness: 0.04, side: THREE.DoubleSide });
  var coffeeMat = new THREE.MeshStandardMaterial({ color: 0x2a1608, roughness: 0.15, metalness: 0.0 });
  var sleeveMat = new THREE.MeshStandardMaterial({ color: 0xb3402a, roughness: 0.6, metalness: 0.0, side: THREE.DoubleSide });
  var beanMat = new THREE.MeshStandardMaterial({ color: 0x3a2110, roughness: 0.45, metalness: 0.05 });

  var cup = new THREE.Group();

  /* Cup body (open cylinder), bottom, coffee surface, rim */
  var body = new THREE.Mesh(new THREE.CylinderGeometry(1.02, 0.74, 1.5, 64, 1, true), ceramic);
  cup.add(body);
  var bottom = new THREE.Mesh(new THREE.CircleGeometry(0.74, 48), ceramic);
  bottom.rotation.x = Math.PI / 2;
  bottom.position.y = -0.75;
  cup.add(bottom);
  var coffee = new THREE.Mesh(new THREE.CircleGeometry(0.94, 48), coffeeMat);
  coffee.rotation.x = -Math.PI / 2;
  coffee.position.y = 0.66;
  cup.add(coffee);
  var rimRing = new THREE.Mesh(new THREE.TorusGeometry(1.02, 0.05, 16, 72), ceramic);
  rimRing.rotation.x = Math.PI / 2;
  rimRing.position.y = 0.75;
  cup.add(rimRing);

  /* Handle: half torus on the right side */
  var handle = new THREE.Mesh(new THREE.TorusGeometry(0.5, 0.1, 16, 48, Math.PI), ceramic);
  handle.position.set(1.0, 0.05, 0);
  handle.rotation.z = -Math.PI / 2;
  cup.add(handle);

  /* Brick-red sleeve around the middle */
  var sleeve = new THREE.Mesh(new THREE.CylinderGeometry(0.985, 0.9, 0.54, 64, 1, true), sleeveMat);
  sleeve.position.y = -0.14;
  cup.add(sleeve);

  /* Saucer */
  var saucer = new THREE.Group();
  var plate = new THREE.Mesh(new THREE.CylinderGeometry(1.66, 1.18, 0.14, 64), ceramic);
  saucer.add(plate);
  var plateRim = new THREE.Mesh(new THREE.TorusGeometry(1.6, 0.045, 14, 72), ceramic);
  plateRim.rotation.x = Math.PI / 2;
  plateRim.position.y = 0.07;
  saucer.add(plateRim);
  saucer.position.y = -0.98;
  cup.add(saucer);

  /* Coffee beans scattered on the saucer */
  var beanGeo = new THREE.SphereGeometry(0.13, 14, 10);
  var beanSpots = [
    [1.28, 0.35], [1.42, -0.3], [1.05, -0.95], [0.45, -1.35],
    [-0.6, -1.25], [-1.25, -0.55], [-1.4, 0.28], [-0.95, 0.95], [0.2, 1.4]
  ];
  beanSpots.forEach(function (spot, i) {
    var bean = new THREE.Mesh(beanGeo, beanMat);
    bean.scale.set(1, 0.6, 0.78);
    bean.position.set(spot[0], -0.86, spot[1]);
    bean.rotation.y = i * 1.37;
    cup.add(bean);
  });

  /* Steam: soft white puffs rising and fading above the cup */
  var steamMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.0, depthWrite: false });
  var steam = [];
  for (var s = 0; s < 7; s++) {
    var puff = new THREE.Mesh(new THREE.SphereGeometry(0.16, 10, 8), steamMat.clone());
    puff.userData.phase = s / 7;
    cup.add(puff);
    steam.push(puff);
  }

  cup.position.y = 0.15;
  scene.add(cup);

  /* Drag to spin */
  var targetRotY = 0.6, targetRotX = 0.08, curRotX = 0.08;
  var dragging = false, lastX = 0, lastY = 0;
  canvas.addEventListener("pointerdown", function (e) {
    dragging = true; lastX = e.clientX; lastY = e.clientY;
    if (canvas.setPointerCapture) canvas.setPointerCapture(e.pointerId);
  });
  canvas.addEventListener("pointermove", function (e) {
    if (!dragging) return;
    targetRotY += (e.clientX - lastX) * 0.008;
    targetRotX += (e.clientY - lastY) * 0.004;
    targetRotX = Math.max(-0.3, Math.min(0.45, targetRotX));
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

  var clock = new THREE.Clock();
  function frame() {
    var t = clock.getElapsedTime();
    if (!dragging) targetRotY += 0.0035;               // gentle auto-spin
    cup.rotation.y += (targetRotY - cup.rotation.y) * 0.08;
    curRotX += (targetRotX - curRotX) * 0.08;
    cup.rotation.x = curRotX + Math.sin(t * 0.7) * 0.02;
    cup.position.y = 0.15 + Math.sin(t * 0.9) * 0.05;  // soft float

    steam.forEach(function (puff) {
      var p = (t * 0.14 + puff.userData.phase) % 1;    // 0..1 rise cycle
      puff.position.set(Math.sin(p * 5.2 + puff.userData.phase * 9) * 0.22, 0.95 + p * 1.45, 0);
      var sc = 0.7 + p * 1.7;
      puff.scale.set(sc, sc * 1.25, sc);
      puff.material.opacity = 0.34 * Math.sin(p * Math.PI);
    });

    renderer.render(scene, camera);
    if (!reducedMotion) requestAnimationFrame(frame);
  }
  frame(); // one frame even with reduced motion (static render)

  if (reducedMotion) { cup.rotation.y = 0.6; renderer.render(scene, camera); }
})();
