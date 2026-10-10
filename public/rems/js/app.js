(() => {
  'use strict';
  const body = document.body;
  const toggle = document.getElementById('menuToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const sidebar = document.getElementById('remsSidebar');
  function setOpen(open) {
    body.classList.toggle('sidebar-open', open);
    if (toggle) toggle.setAttribute('aria-expanded', String(open));
  }
  toggle?.addEventListener('click', () => setOpen(!body.classList.contains('sidebar-open')));
  overlay?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
  });
  sidebar?.querySelectorAll('a.sidebar-link').forEach(link => {
    link.addEventListener('click', () => setOpen(false));
  });
  window.matchMedia('(min-width: 992px)').addEventListener('change', event => {
    if (event.matches) setOpen(false);
  });
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!window.confirm(form.dataset.confirm || 'Are you sure?')) event.preventDefault();
    });
  });
})();
