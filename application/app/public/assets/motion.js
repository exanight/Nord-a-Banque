/* Effets visuels progressifs : aucun accès aux données de l'application. */
(() => {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const fine = matchMedia('(hover: hover) and (pointer: fine)');
  const scene = document.createElement('div');
  scene.className = 'liquid-scene';
  scene.setAttribute('aria-hidden', 'true');
  for (let i = 0; i < 3; i++) {
    const light = document.createElement('span');
    light.className = `liquid-light light-${i + 1}`;
    scene.append(light);
  }
  document.body.prepend(scene);
  const seen = new WeakSet();
  const observer = new IntersectionObserver(entries => {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      }
    }
  }, {threshold: 0.08});
  function enhance() {
    const elements = document.querySelectorAll('.hero-copy > *, .brand-panel, .public-main .card, .workspace-main .card, .login-intro, .login-card');
    elements.forEach((element, index) => {
      if (seen.has(element)) return;
      seen.add(element);
      if (!reduced.matches) {
        element.style.setProperty('--enter-delay', `${Math.min(index % 6, 4) * 70}ms`);
        observer.observe(element);
      }
    });
  }
  enhance();
  new MutationObserver(enhance).observe(document.querySelector('#application') || document.querySelector('main'), {childList: true, subtree: true});
  let frame = 0;
  let lastSurface = null;
  document.addEventListener('pointermove', event => {
    if (reduced.matches || !fine.matches) return;
    const surface = event.target.closest('.brand-panel, .public-main .card, .button, .topbar');
    if (!surface || frame) return;
    frame = requestAnimationFrame(() => {
      frame = 0;
      if (lastSurface && lastSurface !== surface) reset(lastSurface);
      const bounds = surface.getBoundingClientRect();
      const x = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
      const y = Math.max(0, Math.min(1, (event.clientY - bounds.top) / bounds.height));
      surface.style.setProperty('--light-x', `${x * 100}%`);
      surface.style.setProperty('--light-y', `${y * 100}%`);
      surface.style.setProperty('--tilt-x', `${(0.5 - y) * 5}deg`);
      surface.style.setProperty('--tilt-y', `${(x - 0.5) * 5}deg`);
      surface.classList.add('pointer-lit');
      lastSurface = surface;
    });
  }, {passive: true});
  function reset(surface) {
    surface.classList.remove('pointer-lit');
    surface.style.setProperty('--tilt-x', '0deg');
    surface.style.setProperty('--tilt-y', '0deg');
  }
  document.addEventListener('pointerout', event => {
    if (lastSurface && !lastSurface.contains(event.relatedTarget)) {reset(lastSurface);lastSurface = null;}
  });
  document.addEventListener('click', event => {
    if (reduced.matches) return;
    const button = event.target.closest('.button');
    if (!button || button.disabled) return;
    const ripple = document.createElement('span');
    ripple.className = 'liquid-ripple';
    ripple.setAttribute('aria-hidden', 'true');
    button.append(ripple);
    setTimeout(() => ripple.remove(), 750);
  });
  reduced.addEventListener('change', () => {
    if (reduced.matches) {
      observer.disconnect();
      if (lastSurface) reset(lastSurface);
    } else enhance();
  });
})();
