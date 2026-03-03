import { createDialogModal } from '../../../scripts/utils/modal.js';

const api = createDialogModal({
  modalSelector: '#success-modal',
  innerSelector: '.modal-success__inner',
  closeSelector: '.modal-success__close',
  openersSelector: '.open-success-btn, [data-modal-open="success"]',
});

export function openSuccessModal() {
  api?.open();
}

export function closeSuccessModal() {
  api?.close();
}

window.termopabOpenSuccessModal = openSuccessModal;
window.termopabCloseSuccessModal = closeSuccessModal;