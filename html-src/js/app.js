import Toast from "./Toast/Toast.js";
import UIManager from "./UIManager/UIManager.js";
import DownloadManager from "./DownloadManager/DownloadManager.js";
import EventManager from "./utils/EventManager/EventManager.js";

import sleep from "./utils/sleep.js";
import selectors from "./utils/selectors.js";

import init from "./dialog/dialog.js";

const uiManager = new UIManager();
const downloadManager = new DownloadManager();
const em = new EventManager();


const eventNamespaces = {
  DOWNLOAD: 'download'
}

/**
 * List of files actively being downloaded
 */
let activedownloads = []; 

/**
 * makes a pending file as complete in download logs
 * 
 * @param {String} name
 * @param {Number} ndx
 * @param {String} status
 */
async function logCompleted(name, ndx, status) {
  try {
    const updates = await downloadManager.markCompleted(name, ndx, status);
    const htmlElements = updates.map(uiManager.createLogEntry);
    document.querySelector('#history>ul').replaceChildren(...htmlElements);      
  } catch(error) {
    console.error(error)
  }
}

/**
 * opens file save dialog
 * 
 * @param {Bytes} chunks 
 * @param {String} name 
 */
async function saveFile(chunks, name, ndx) {
  const fileBlob = new Blob(chunks);
  const link = document.createElement('a');
  link.href = URL.createObjectURL(fileBlob);
  link.download = name;
  link.click();
  URL.revokeObjectURL(link.href);
  logCompleted(name, ndx, true);
}

function downloadFinished(update, name, ndx, row) {
  const {chunks} = update.detail;
  saveFile(chunks, name, ndx);
  em.removeByNamespace(eventNamespaces.DOWNLOAD);
  uiManager.downloadEnded(row, 'Download complete.');
}

function userStopped(row) {
  em.removeByNamespace(eventNamespaces.DOWNLOAD);
  uiManager.downloadEnded(row, 'Download stopped by user');
}

/**
 * download a file
 * 
 * @param {Object} obj
 * @param {String} obj.path
 * @param {String} obj.name
 * 
 * @returns {Boolean}
 */
async function download({path, name, ndx}) {
  // event namespace
  const ns = eventNamespaces.DOWNLOAD;

  // disable clear button
  uiManager.setButtonDisabledState(selectors.clearHistoryButton, true);

  // Toast message
  new Toast(`Downloading: ${name}`, 1);

  try {
    const fileDownload = await downloadManager.getFile(path);

    const {row, dlSpeed, bar} = uiManager.createDownloadUI(name, _ => {
      em.removeByNamespace(ns);
      fileDownload.stop();
      uiManager.setButtonDisabledState(selectors.clearHistoryButton, false);
      uiManager.cleanupDownload(row);
    }, ndx);
    
    em.add(fileDownload, 'update', update => uiManager.progressUpdated(update, bar, dlSpeed), ns);
    em.add(fileDownload, 'finished', update => downloadFinished(update, name, ndx, row), ns);
    em.add(fileDownload, 'stopped', _ => userStopped(row), ns);
    
    fileDownload.start();
  } catch(error) {
    const errorMessage = (error.name === 'AbortError') ? 'Download canceled.' : `Failed to fetch ${path}`;
    new Toast(errorMessage);
    // console.error(error);
    await sleep(2000);
  }
}

/**
 * records a download to the php session
 * 
 * @param {String} file
 * 
 * @returns {Boolean}
 */
async function recordDownload(file) {
  const {downloads} = await downloadManager.recordDownload(file);
  const elementList = downloads.map(uiManager.createLogEntry);
  elementList.reverse();
  const historyList = document.querySelector('#history>ul');
  historyList.replaceChildren(...elementList);
  console.log(`${downloads.length} download(s) logged`);
  return elementList[0].dataset.ndx;
}

/**
 * file element clicked
 * 
 * @param {HTMLElement} file clicked element
 * 
 * @returns {void}
 */
async function fileClicked(file) {
  document.querySelector('#hist_but>svg').classList.add('spin');
  const exists = await recordDownload(file.dataset.name);
  if (!exists) {
    return;
  }
  file.dataset.ndx = exists;
  await download({...file.dataset});
  document.querySelector('#hist_but>svg').classList.remove('spin');
}





/**
 * checks for avtive downloads before navigating away from page
 * 
 * @param {Event} event 
 * 
 * @returns {String}
 */
function checkActiveDownloads(event) {
  event.preventDefault();
  event.returnValue = '';

  if (!activedownloads.length) {
    em.removeAll();
    return;
  }

  const confirmed = confirm('Download(s) active. Are you sure you want to leave?');
  console.log(confirmed);
  if (confirmed) {
    activedownloads.forEach(dl => logCompleted(dl.name, dl.ndx, 'canceled'));
    activedownloads = [];
    window.location.href = event.target.href || window.location.href;
    em.removeAll();
  }
}

/**
 * app loaded callback
 */
function appLoaded() {
  init();

  const files = document.querySelectorAll(selectors.file);
  files.forEach(file => {
    em.add(file, 'click', _ => fileClicked(file))
    em.add(file, 'keydown', (event) => {
      if (event.key === 'Enter' || event.key === 'Space') {
        event.preventDefault();
        file.click();
      }
    });
  });

  const toTop = document.querySelector(selectors.toTop);
  em.add(toTop, 'click', _ => document.documentElement.scrollTo({
    top: 0,
    behavior: 'smooth'
  }));
  
  em.add(document, 'scroll', uiManager.documentScrolled);
}

em.add(window, 'load', appLoaded);
// em.add(window, 'beforeunload', checkActiveDownloads); 