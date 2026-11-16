/* Latar "liquid gradient" (live wallpaper) untuk website & panel.
   Shader WebGL dengan domain warping: warna mengalir seperti cairan. Palet dari CSS (--live-c0..c4)
   per tema, berganti halus saat tema diubah. Dirender di resolusi rendah (gradasi tetap mulus) & ~30 fps.
   Tanpa WebGL: latar CSS bawaan tetap tampil. Mode "hemat animasi": satu frame diam. */
(function () {
    const canvas = document.querySelector('.live-bg canvas');
    if (!canvas) return;
    const gl = canvas.getContext('webgl', { antialias: false, alpha: false, preserveDrawingBuffer: false, powerPreference: 'low-power' });
    if (!gl) return;

    const root = document.documentElement;
    const SCALE = 0.35; // resolusi render relatif layar
    const FRAME_MS = 1000 / 30;

    const vert = 'attribute vec2 p; void main(){ gl_Position = vec4(p, 0.0, 1.0); }';
    const frag = `
        precision mediump float;
        uniform vec2 uRes; uniform float uTime;
        uniform vec3 uC0; uniform vec3 uC1; uniform vec3 uC2; uniform vec3 uC3; uniform vec3 uC4;
        float hash(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
        float noise(vec2 p){
            vec2 i = floor(p), f = fract(p);
            vec2 u = f * f * (3.0 - 2.0 * f);
            return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x), mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);
        }
        float fbm(vec2 p){
            float v = 0.0, a = 0.5;
            mat2 m = mat2(1.6, 1.2, -1.2, 1.6);
            for (int i = 0; i < 4; i++) { v += a * noise(p); p = m * p; a *= 0.5; }
            return v;
        }
        void main(){
            vec2 uv = gl_FragCoord.xy / uRes;
            vec2 p = uv * vec2(uRes.x / uRes.y, 1.0) * 1.6;
            float t = uTime * 0.11;
            vec2 q = vec2(fbm(p + vec2(0.0, t)), fbm(p + vec2(5.2, 1.3) - t * 0.8));
            vec2 r = vec2(fbm(p + 3.2 * q + vec2(1.7, 9.2) + t * 1.2), fbm(p + 3.2 * q + vec2(8.3, 2.8) - t));
            float f = fbm(p + 2.6 * r);
            vec3 col = mix(uC0, uC1, smoothstep(0.15, 0.85, f));
            col = mix(col, uC2, smoothstep(0.25, 1.1, length(q)));
            col = mix(col, uC3, smoothstep(0.35, 0.95, r.x) * 0.9);
            col = mix(col, uC4, smoothstep(0.55, 1.0, r.y) * 0.7);
            gl_FragColor = vec4(col, 1.0);
        }`;

    const compile = (type, src) => { const s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    const prog = gl.createProgram();
    gl.attachShader(prog, compile(gl.VERTEX_SHADER, vert));
    gl.attachShader(prog, compile(gl.FRAGMENT_SHADER, frag));
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return; // gagal: tetap latar CSS
    gl.useProgram(prog);

    const buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    const loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

    const u = (name) => gl.getUniformLocation(prog, name);
    const uRes = u('uRes'), uTime = u('uTime');
    const uCols = [0, 1, 2, 3, 4].map((i) => u(`uC${i}`));

    // Palet dari CSS tema aktif; berpindah halus ke palet baru saat tema diganti
    const hex = (h) => { const n = parseInt(h.trim().replace('#', ''), 16); return [(n >> 16 & 255) / 255, (n >> 8 & 255) / 255, (n & 255) / 255]; };
    const readPalette = () => [0, 1, 2, 3, 4].map((i) => hex(getComputedStyle(root).getPropertyValue(`--live-c${i}`) || '#000000'));
    let from = readPalette(), to = from, blendStart = 0;
    const BLEND_MS = 1400;
    window.addEventListener('themechange', () => {
        from = currentPalette(performance.now());
        requestAnimationFrame(() => { to = readPalette(); blendStart = performance.now(); });
    });
    const currentPalette = (now) => {
        const k = Math.min(1, (now - blendStart) / BLEND_MS);
        const e = k * k * (3 - 2 * k);
        return to.map((c, i) => c.map((v, j) => from[i][j] + (v - from[i][j]) * e));
    };

    const resize = () => {
        const w = Math.max(1, Math.round(innerWidth * SCALE));
        const h = Math.max(1, Math.round(innerHeight * SCALE));
        if (canvas.width !== w || canvas.height !== h) { canvas.width = w; canvas.height = h; gl.viewport(0, 0, w, h); }
    };
    addEventListener('resize', resize);
    resize();

    const still = root.classList.contains('reduce-motion');
    const seed = Math.random() * 100; // tiap kunjungan pola awal berbeda
    let last = 0;
    function draw(now) {
        resize();
        gl.uniform2f(uRes, canvas.width, canvas.height);
        gl.uniform1f(uTime, seed + (still ? 0 : now / 1000));
        currentPalette(now).forEach((c, i) => gl.uniform3fv(uCols[i], c));
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    }
    function loop(now) {
        if (now - last >= FRAME_MS) { last = now; draw(now); }
        requestAnimationFrame(loop);
    }

    root.classList.add('live-gl'); // CSS menyembunyikan latar cadangan
    if (still) {
        draw(performance.now());
        // tetap gambar ulang saat tema berganti (lihat listener themechange)
        const blendLoop = (now) => { draw(now); if (now - blendStart < BLEND_MS) requestAnimationFrame(blendLoop); };
        window.addEventListener('themechange', () => requestAnimationFrame(blendLoop));
    } else {
        requestAnimationFrame(loop);
    }
})();
