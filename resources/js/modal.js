export function initModal() {
  document.addEventListener('click', (e) => {
    if (!(e.target instanceof Element)) return;

    const oeffner = e.target.closest('[data-ps-modal-open]');
    if (oeffner instanceof HTMLElement) {
      const dialog = document.getElementById(oeffner.dataset.psModalOpen ?? '');
      if (dialog instanceof HTMLDialogElement && !dialog.open) {
        dialog.showModal();
        document.documentElement.dataset.psModal = '';
      }
      return;
    }

    const schliesser = e.target.closest('[data-ps-modal-close]');
    if (schliesser) {
      schliesser.closest('dialog')?.close();
      return;
    }

    // Klick auf den abgedunkelten Hintergrund schließt das Fenster.
    if (e.target instanceof HTMLDialogElement && e.target.classList.contains('ps-modal')) e.target.close();
  });

  // close wird nicht weitergereicht, daher in der Erfassungsphase lauschen.
  document.addEventListener(
    'close',
    (e) => {
      if (e.target instanceof HTMLDialogElement && !document.querySelector('dialog[open]')) {
        delete document.documentElement.dataset.psModal;
      }
    },
    true,
  );
}
