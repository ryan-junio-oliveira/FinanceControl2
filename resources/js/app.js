import NProgress from 'nprogress';
import 'nprogress/nprogress.css';
import { gsap } from 'gsap';
import ScrollReveal from 'scrollreveal';
import IMask from 'imask';
import $ from 'jquery';

// jQuery disponível globalmente (plugins, console e helpers legados).
window.$ = window.jQuery = $;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ════════════════════════════════════════════════════════════
   NProgress — barra de progresso nas navegações (full page)
   ════════════════════════════════════════════════════════════ */
NProgress.configure({ showSpinner: false, trickleSpeed: 140, minimum: 0.15 });

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    if (a.target === '_blank' || a.hasAttribute('download') || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    try {
        const url = new URL(a.href, window.location.origin);
        if (url.origin !== window.location.origin) return;
        NProgress.start();
    } catch {
        /* URL inválida: segue o fluxo normal */
    }
});

document.addEventListener('submit', (e) => {
    if (e.target instanceof HTMLFormElement) NProgress.start();
});

window.addEventListener('pageshow', () => NProgress.done());
window.addEventListener('load', () => NProgress.done());

/* ════════════════════════════════════════════════════════════
   GSAP — animações de entrada (cabeçalho + telas de acesso)
   ════════════════════════════════════════════════════════════ */
if (!reducedMotion) {
    const heroItems = document.querySelectorAll('[data-hero]');
    if (heroItems.length) {
        gsap.from(heroItems, { y: 18, opacity: 0, duration: 0.6, stagger: 0.09, ease: 'power2.out' });
    }

    const authCard = document.querySelector('[data-auth-card]');
    if (authCard) {
        gsap.from(authCard, { y: 24, opacity: 0, duration: 0.65, ease: 'power3.out', delay: 0.08 });
    }

    const appHeader = document.querySelector('[data-app-header]');
    if (appHeader) {
        gsap.from(appHeader, { y: -12, opacity: 0, duration: 0.45, ease: 'power2.out' });
    }
}

/* ════════════════════════════════════════════════════════════
   ScrollReveal — cartões e seções surgem ao rolar
   ════════════════════════════════════════════════════════════ */
if (!reducedMotion && document.querySelector('[data-reveal]')) {
    ScrollReveal().reveal('[data-reveal]', {
        distance: '14px',
        duration: 480,
        easing: 'cubic-bezier(.22,.68,.36,1)',
        interval: 60,
        origin: 'bottom',
        reset: false,
        viewFactor: 0.08,
    });
}

/* ════════════════════════════════════════════════════════════
   IMask — máscaras pt-BR (moeda com vírgula, telefone)
   Aplica-se a todo input monetário (inputmode="decimal").
   O backend já normaliza "1.234,56" via normalizeMoney().
   ════════════════════════════════════════════════════════════ */
document.querySelectorAll('input[inputmode="decimal"]').forEach((el) => {
    if (el.dataset.masked) return;
    el.dataset.masked = '1';
    const mask = IMask(el, {
        mask: Number,
        scale: 2,
        thousandsSeparator: '.',
        radix: ',',
        mapToRadix: ['.'],
        padFractionalZeros: false,
        normalizeZeros: true,
        autofix: true,
    });
    // Envia o valor canônico ("7000.5") em vez do texto exibido ("7.000,5"):
    // o backend aceita ambos, mas o canônico elimina qualquer ambiguidade.
    const form = el.closest('form');
    if (form && !form.dataset.unmaskBound) {
        form.dataset.unmaskBound = '1';
        form.addEventListener('submit', () => {
            form.querySelectorAll('input[inputmode="decimal"][data-masked]').forEach((input) => {
                const m = input._moneyMask;
                if (!m) return;
                const v = m.typedValue;
                input.value = (v === null || v === undefined || Number.isNaN(v)) ? '' : String(v);
            });
        });
    }
    el._moneyMask = mask;
});

document.querySelectorAll('input[name="phone"]').forEach((el) => {
    if (el.dataset.masked) return;
    el.dataset.masked = '1';
    IMask(el, { mask: '(00) 00000-0000' });
});

/* ════════════════════════════════════════════════════════════
   Contadores animados nos KPIs (data-count + prefixo/sufixo)
   ════════════════════════════════════════════════════════════ */
function animateCount(el) {
    const target = parseFloat(el.getAttribute('data-count'));
    if (Number.isNaN(target)) return;
    const prefix = el.getAttribute('data-prefix') || '';
    const suffix = el.getAttribute('data-suffix') || '';
    const decimals = Number.isInteger(target) ? 0 : 2;
    const fmt = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
    if (reducedMotion) {
        el.textContent = prefix + fmt.format(target) + suffix;
        return;
    }
    const state = { v: 0 };
    gsap.to(state, {
        v: target,
        duration: 1.1,
        ease: 'power2.out',
        onUpdate: () => {
            el.textContent = prefix + fmt.format(state.v) + suffix;
        },
        onComplete: () => {
            el.textContent = prefix + fmt.format(target) + suffix;
        },
    });
}

document.querySelectorAll('[data-count]').forEach(animateCount);

/* ════════════════════════════════════════════════════════════
   Three.js — campo de partículas no banner do login
   Carregado sob demanda (só nas telas de acesso).
   ════════════════════════════════════════════════════════════ */
(async () => {
    const canvas = document.getElementById('auth-particles');
    if (!canvas || reducedMotion) return;

    let THREE;
    try {
        THREE = await import('three');
    } catch {
        return; // sem rede p/ o chunk: segue estático
    }

    try {
        initParticles(THREE, canvas);
    } catch {
        /* WebGL indisponível: fundo flat permanece */
    }
})();

function initParticles(THREE, canvas) {
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
    camera.position.z = 8;

    const COUNT = 140;
    const positions = new Float32Array(COUNT * 3);
    for (let i = 0; i < COUNT; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 16;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 10;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 6;
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const material = new THREE.PointsMaterial({
        color: 0x6ee7b7,
        size: 0.055,
        transparent: true,
        opacity: 0.65,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });
    const points = new THREE.Points(geometry, material);
    scene.add(points);

    let mx = 0;
    let my = 0;
    window.addEventListener('pointermove', (e) => {
        mx = e.clientX / window.innerWidth - 0.5;
        my = e.clientY / window.innerHeight - 0.5;
    }, { passive: true });

    const resize = () => {
        const w = canvas.clientWidth || canvas.parentElement.clientWidth || 1;
        const h = canvas.clientHeight || canvas.parentElement.clientHeight || 1;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    };
    resize();
    window.addEventListener('resize', resize);

    let visible = true;
    document.addEventListener('visibilitychange', () => {
        visible = !document.hidden;
    });

    let acc = 0;
    let last = performance.now();
    (function tick(now) {
        requestAnimationFrame(tick);
        if (!visible) return;
        const dt = (now - last) / 1000;
        last = now;
        acc += dt;
        points.rotation.y = acc * 0.03 + mx * 0.4;
        points.rotation.x = my * 0.25;
        const arr = geometry.attributes.position.array;
        for (let i = 0; i < COUNT; i++) {
            arr[i * 3 + 1] += Math.sin(acc * 0.6 + i) * 0.0012;
        }
        geometry.attributes.position.needsUpdate = true;
        renderer.render(scene, camera);
    })(performance.now());
}
