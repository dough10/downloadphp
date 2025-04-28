import EventManager from "../utils/EventManager/EventManager";
import UIManager from "../UIManager/UIManager";
import DownloadManager from "../DownloadManager/DownloadManager";
import selectors from "../utils/selectors";
import Toast from "../Toast/Toast";

const em = new EventManager();
const downloadManager = new DownloadManager();

/** @type {UIManager} passed in instance of UIManager */ 
let uiManager;

/**
 * click when dialog open
 * 
 * @param {Event} event 
 * @param {HTMLElement} dialog 
 */
function dialogClicked(event, dialog) {
  const closeButton = dialog.querySelector(selectors.closeButton);
  const animationend = _ => {
    dialog.removeEventListener('animationend', animationend);
    closeButton.classList.remove('attention');
    dialog.classList.remove('dialog-attention');
  };
  var rect = dialog.getBoundingClientRect();
  var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
    rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
  if (!isInDialog) {
    if (sound) document.querySelector('#error').play();
    dialog.addEventListener('animationend', animationend);
    closeButton.classList.add('attention');
    dialog.classList.add('dialog-attention');
  }
}

/**
 * clear history ui
 * 
 * @param {HTMLElement} clearButton button clicked
 * 
 * @returns {void}
 */
async function clearHistory(clearButton) {
  if (document.querySelectorAll('#history>ul>li').length < 1) {
    new Toast('Nothing to clear.');
    return;
  }
  clearButton.setAttribute('disabled', true);
  try {
    const data = await downloadManager.clearHistory();
    new Toast('History cleared.');
    uiManager.updateHistory(data);
  } catch(error) {
    console.error('Failed clearing download history:', error);
  } finally {
    clearButton.removeAttribute('disabled');
  }
}

export function destroy() {
  em.removeAll();
}


export function initiateDialogs(uim) {
  uiManager = uim
  const clearButton = document.querySelector('#history>.clear');
  em.add(clearButton, 'click', _ => {
    clearHistory(clearButton);
  });

  em.add(document.querySelector('#hist_but'), 'click', _ => {
    document.querySelector('#history').showModal();
  });

  const dialogs = document.querySelectorAll('dialog');
  dialogs.forEach(dialog => {
    em.add(dialog, 'click', event => dialogClicked(event, dialog));
  });

  document.querySelectorAll('dialog>.close').forEach(button => {
    em.add(button, 'click', _ => button.parentElement.close());
  });
}