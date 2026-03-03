import { createDialogModal } from '../../../scripts/utils/modal.js';

const api = createDialogModal({
  modalSelector: '#callback-modal',
  innerSelector: '.modal-callback__inner',
  closeSelector: '.modal-callback__close',
  openersSelector: '.open-callback-btn, [data-modal-open="callback"]',
});

export function openCallbackModal() {
  api?.open();
}

export function closeCallbackModal() {
  api?.close();
}

window.termopabOpenCallbackModal = openCallbackModal;
window.termopabCloseCallbackModal = closeCallbackModal;