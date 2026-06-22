<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>3D preview</title>
    <style>
        html, body { margin: 0; height: 100%; background: #f1f5f9; overflow: hidden; }
        .wrap { position: relative; width: 100%; height: 100vh; }
        model-viewer, .obj-canvas { width: 100%; height: 100%; display: block; }
        .hint { position: absolute; inset-inline-start: 10px; bottom: 8px; font: 12px system-ui, sans-serif; color: #94a3b8; }
        .spin { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font: 13px system-ui; }
    </style>
</head>
<body>
@if ($software->is3dObj())
    <div class="wrap" data-obj-viewer data-src="{{ $software->modelGlbUrl() }}">
        <div class="obj-canvas"></div>
        <div class="spin" data-spin>…</div>
        <div class="hint">.obj — {{ $software->name }}</div>
    </div>
    <script type="importmap">
    { "imports": { "three": "https://cdn.jsdelivr.net/npm/three@0.161.0/build/three.module.js", "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.161.0/examples/jsm/" } }
    </script>
    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
        const wrap = document.querySelector('[data-obj-viewer]');
        const host = wrap.querySelector('.obj-canvas');
        const url = wrap.getAttribute('data-src');
        const w = () => host.clientWidth || 1, h = () => host.clientHeight || 1;
        const scene = new THREE.Scene(); scene.background = new THREE.Color(0xf1f5f9);
        const camera = new THREE.PerspectiveCamera(45, w() / h(), 0.01, 100000);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); renderer.setSize(w(), h());
        host.appendChild(renderer.domElement);
        scene.add(new THREE.HemisphereLight(0xffffff, 0x555555, 1.0));
        const d1 = new THREE.DirectionalLight(0xffffff, 1.1); d1.position.set(6, 10, 8); scene.add(d1);
        const d2 = new THREE.DirectionalLight(0xffffff, 0.5); d2.position.set(-6, -4, -8); scene.add(d2);
        const controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true; controls.autoRotate = true; controls.autoRotateSpeed = 1.4;
        new OBJLoader().load(url, (obj) => {
            obj.traverse((c) => { if (c.isMesh) c.material = new THREE.MeshStandardMaterial({ color: 0xc9ced8, metalness: 0.08, roughness: 0.78 }); });
            const box = new THREE.Box3().setFromObject(obj);
            const size = box.getSize(new THREE.Vector3()), center = box.getCenter(new THREE.Vector3());
            obj.position.sub(center);
            const maxDim = Math.max(size.x, size.y, size.z) || 1, dist = maxDim * 2.4;
            camera.position.set(dist * 0.5, dist * 0.35, dist);
            camera.near = maxDim / 200; camera.far = maxDim * 200; camera.updateProjectionMatrix();
            controls.target.set(0, 0, 0); controls.update();
            scene.add(obj);
            const sp = wrap.querySelector('[data-spin]'); if (sp) sp.remove();
        }, undefined, () => { const sp = wrap.querySelector('[data-spin]'); if (sp) sp.textContent = '⚠ تعذّر تحميل النموذج'; });
        window.addEventListener('resize', () => { renderer.setSize(w(), h()); camera.aspect = w() / h(); camera.updateProjectionMatrix(); });
        (function loop() { requestAnimationFrame(loop); controls.update(); renderer.render(scene, camera); })();
    </script>
@else
    <model-viewer
        src="{{ $software->modelGlbUrl() }}"
        @if ($software->modelPosterUrl()) poster="{{ $software->modelPosterUrl() }}" @endif
        alt="{{ $software->name }}"
        camera-controls auto-rotate shadow-intensity="1" exposure="1" environment-image="neutral"
        style="width:100%; height:100vh; background:#f1f5f9">
    </model-viewer>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@google/model-viewer@3.5.0/dist/model-viewer.min.js"></script>
@endif
</body>
</html>
