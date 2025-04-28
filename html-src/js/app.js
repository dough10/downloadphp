import Toast from "./Toast/Toast.js";
import UIManager from "./UIManager/UIManager.js";
import DownloadManager from "./DownloadManager/DownloadManager.js";
import EventManager from "./utils/EventManager/EventManager.js";
import selectors from "./utils/selectors.js";
import init from './dialog/dialog.js';

const em = new EventManager();
const downloadManager = new DownloadManager();
const uiManager = new UIManager();


const eventNamespaces = {
  DOWNLOAD: 'download'
};

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
    document.querySelector(selectors.historyList).replaceChildren(...htmlElements);      
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
  try {
    const fileBlob = new Blob(chunks);
    const link = document.createElement('a');
    link.href = URL.createObjectURL(fileBlob);
    link.download = name;
    link.click();
    URL.revokeObjectURL(link.href);
  } catch (error) {
    console.error('Error saving file:', error);
    new Toast('Failed to save file.');
  }
}

/**
 * a download finished (failed, canceled, complete)
 * 
 * @param {Object} update
 * @param {Object} update.detail
 * @param {Array} update.detail.chunks 
 * @param {String} name 
 * @param {Number} ndx 
 */
function downloadFinished(update, name, ndx) {
  const {chunks} = update.detail;
  saveFile(chunks, name, ndx);
  em.removeByNamespace(ndx);
  uiManager.downloadEnded(ndx, 'Download complete.');
  logCompleted(name, ndx, true);
}

/**
 * user stopped the download
 * 
 * @param {String} name 
 * @param {Number} ndx 
 */
function userStopped(name, ndx) {
  em.removeByNamespace(ndx);
  uiManager.downloadEnded(ndx, 'Download stopped by user');
  logCompleted(name, ndx, 'canceled');
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
async function download({ path, name, ndx }) {
  uiManager.setButtonDisabledState(selectors.clearHistoryButton, true);
  new Toast(`Download began: ${name}`, 1);
  try {
    const fileDownload = await downloadManager.getFile(path, ndx);
    const { dlSpeed, bar } = uiManager.createDownloadUI(name, _ => fileDownload.stop(), ndx);
    em.add(fileDownload, 'update', update => uiManager.progressUpdated(update, bar, dlSpeed), ndx);
    em.add(fileDownload, 'finished', update => downloadFinished(update, name, ndx), ndx);
    em.add(fileDownload, 'stopped', _ => userStopped(name, ndx), ndx);
    fileDownload.start();
  } catch (error) {
    console.error('Error during download:', error);
    new Toast((error.name === 'AbortError') ? 'Download canceled.' : `Failed to fetch ${path}`);
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
  try {
    document.querySelector(selectors.historySVG).classList.add('spin');
    const { downloads } = await downloadManager.recordDownload(file);
    const elementList = downloads.map(uiManager.createLogEntry);
    elementList.reverse();
    const historyList = document.querySelector(selectors.historyList);
    historyList.replaceChildren(...elementList);
    console.log(`${downloads.length} download(s) logged`);
    return elementList[0].dataset.ndx;
  } catch (error) {
    console.error('Error recording download:', error);
    new Toast('Failed to record download.');
    return null;
  }
}

/**
 * file element clicked
 * 
 * @param {HTMLElement} file clicked element
 * 
 * @returns {void}
 */
async function fileClicked(file) {
  const exists = await recordDownload(file.dataset.name);
  if (!exists) {
    document.querySelector(selectors.historySVG).classList.remove('spin');
    return;
  }
  file.dataset.ndx = exists;
  await download({...file.dataset});
}

/**
 * load application
 */
function loaded() {
  init();
  uiManager.init(fileClicked);
}

em.add(window, 'load', loaded);
em.add(window, 'beforeunload', em.removeAll);