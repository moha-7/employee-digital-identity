(() => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  const setModal = (modal, open) => {
    if (!modal) return;
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.classList.toggle('modal-open', open || $$('.modal.is-open').length > 0);
  };

  $$('[data-modal-close]').forEach(el => el.addEventListener('click', () => {
    const modal = el.closest('.modal');
    setModal(modal, false);
  }));

  $$('[data-modal-open="settings"]').forEach(btn => btn.addEventListener('click', () => setModal($('#settingsModal'), true)));

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') $$('.modal.is-open').forEach(m => setModal(m, false));
  });

  const search = $('#employeeSearch');
  const dept = $('#departmentFilter');
  const cards = $$('.employee-card');
  const count = $('#resultCount');
  const empty = $('#emptyState');

  const applyFilters = () => {
    if (!cards.length) return;
    const q = (search?.value || '').trim().toLowerCase();
    const d = (dept?.value || '').trim().toLowerCase();
    let visible = 0;
    cards.forEach(card => {
      const matchesQ = !q || (card.dataset.search || '').includes(q);
      const matchesD = !d || (card.dataset.department || '') === d;
      const show = matchesQ && matchesD;
      card.hidden = !show;
      if (show) visible += 1;
    });
    if (count) count.textContent = `${visible} profile${visible === 1 ? '' : 's'}`;
    if (empty) empty.hidden = visible !== 0;
  };

  search?.addEventListener('input', applyFilters);
  dept?.addEventListener('change', applyFilters);
  $('#refreshFilters')?.addEventListener('click', () => {
    if (search) search.value = '';
    if (dept) dept.value = '';
    applyFilters();
    search?.focus();
  });

  const qrModal = $('#qrModal');
  $$('[data-qr-open]').forEach(btn => btn.addEventListener('click', () => {
    const profilePath = btn.dataset.profile || '/';
    const profileUrl = new URL(profilePath, window.location.origin).toString();
    $('#qrTitle').textContent = btn.dataset.name || 'Employee profile';
    $('#qrRole').textContent = btn.dataset.role || '';
    $('#qrImage').src = btn.dataset.qr || '';
    $('#qrUrl').textContent = profileUrl;
    $('#qrDownload').href = btn.dataset.qrDownload || '#';
    $('#qrProfileLink').href = profilePath;
    $('#copyProfileLink').dataset.copy = profileUrl;
    setModal(qrModal, true);
  }));

  $('#copyProfileLink')?.addEventListener('click', async e => {
    const btn = e.currentTarget;
    const value = btn.dataset.copy || '';
    const label = $('span', btn);
    try {
      await navigator.clipboard.writeText(value);
      if (label) label.textContent = 'Copied';
      setTimeout(() => { if (label) label.textContent = 'Copy link'; }, 1300);
    } catch {
      if (label) label.textContent = 'Copy unavailable';
    }
  });

  $$('[data-mutation]').forEach(btn => btn.addEventListener('click', () => {
    const action = btn.dataset.mutation || 'manage';
    const name = btn.dataset.name ? ` for ${btn.dataset.name}` : '';
    const labels = { add: 'Create employee profile', edit: 'Edit employee profile', delete: 'Retire employee identity' };
    $('#mutationTitle').textContent = labels[action] || 'Employee administration';
    $('#mutationMessage').textContent = `${labels[action] || 'This action'}${name} is part of the production workflow. It is disabled here so the public portfolio remains read-only and safe to explore.`;
    setModal($('#mutationModal'), true);
  }));
})();
