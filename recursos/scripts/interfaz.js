document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.getElementById('buyMenuButton');
  const buyMenu = document.getElementById('buyMenu');
  const accountButton = document.getElementById('accountButton');
  const accountMenu = document.getElementById('accountMenu');
  const mobileToggle = document.getElementById('mobileToggle');
  const mainNav = document.getElementById('mainNav');

  menuButton?.addEventListener('click', () => {
    const open = buyMenu?.classList.toggle('open') ?? false;
    menuButton.setAttribute('aria-expanded', String(open));
  });
  accountButton?.addEventListener('click', () => accountMenu?.classList.toggle('open'));
  mobileToggle?.addEventListener('click', () => mainNav?.classList.toggle('mobile-open'));
  document.addEventListener('click', (event) => {
    if (!event.target.closest('#buyNav')) buyMenu?.classList.remove('open');
    if (!event.target.closest('#accountWrap')) accountMenu?.classList.remove('open');
  });

  document.querySelectorAll('[data-favorite]').forEach((button) => {
    const key = `nuevo-hogar-favorite-${button.dataset.favorite}`;
    const saved = localStorage.getItem(key) === '1';
    button.classList.toggle('saved', saved);
    button.textContent = saved ? '♥' : '♡';
    button.addEventListener('click', () => {
      const next = localStorage.getItem(key) !== '1';
      localStorage.setItem(key, next ? '1' : '0');
      button.classList.toggle('saved', next);
      button.textContent = next ? '♥' : '♡';
    });
  });
});
