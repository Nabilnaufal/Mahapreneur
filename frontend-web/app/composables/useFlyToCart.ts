/**
 * Animasi micro-interaction "terbang ke keranjang".
 * Menyalin gambar produk dari tombol menuju ikon keranjang di navbar
 * (elemen dengan atribut `data-cart-target`). Dilewati saat reduced motion.
 */
export function useFlyToCart() {
  function fly(source: HTMLElement | null, image?: string | null) {
    if (!import.meta.client || !source) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const target = document.querySelector<HTMLElement>('[data-cart-target]');
    if (!target) return;

    const from = source.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const startX = from.left + from.width / 2;
    const startY = from.top + from.height / 2;

    const ghost = document.createElement('div');
    ghost.className = 'fly-ghost';
    if (image) {
      const img = document.createElement('img');
      img.src = image;
      img.alt = '';
      ghost.appendChild(img);
    } else {
      ghost.textContent = '+1';
    }
    ghost.style.left = `${startX}px`;
    ghost.style.top = `${startY}px`;
    document.body.appendChild(ghost);

    const animation = ghost.animate(
      [
        { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
        {
          transform: `translate(${to.left + to.width / 2 - startX}px, ${to.top + to.height / 2 - startY}px) translate(-50%, -50%) scale(0.2)`,
          opacity: 0.25,
        },
      ],
      { duration: 620, easing: 'cubic-bezier(0.4, 0, 0.2, 1)' },
    );

    animation.finished
      .then(() => ghost.remove())
      .catch(() => ghost.remove());
  }

  return { fly };
}
