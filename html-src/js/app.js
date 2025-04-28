import Toast from "./Toast/Toast.js";
import UIManager from "./UIManager/UIManager.js";
import DownloadManager from "./DownloadManager/DownloadManager.js";
import EventManager from "./utils/EventManager/EventManager.js";
import selectors from "./utils/selectors.js";


const em = new EventManager();
const downloadManager = new DownloadManager();
const uiManager = new UIManager();

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
    uiManager.updateHistory(updates);      
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
async function downloadFinished(update, name, ndx) {
  try {
    const {chunks} = update.detail;
    await saveFile(chunks, name, ndx);
    em.removeByNamespace(ndx);
    uiManager.downloadEnded(ndx, 'Download complete.');
    await logCompleted(name, ndx, true);
  } catch (error) {
    console.error(`Error finishing download for ${name}:`, error);
    em.removeByNamespace(ndx);
    uiManager.downloadEnded(ndx, 'Download failed.');
    await logCompleted(name, ndx, 'failed');
  }
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
 * Downloads a file and manages its UI representation
 * 
 * @param {Object} options - Download options
 * @param {string} options.path - File path to download
 * @param {string} options.name - Display name of the file
 * @param {number} options.ndx - Unique identifier for this download
 * @returns {Promise<void>}
 * @throws {Error} When download fails or is aborted
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
    em.removeByNamespace(ndx);
    uiManager.downloadEnded(ndx, 'Download failed.');
    await logCompleted(name, ndx, 'failed');
    console.error(`Error during download of ${name} (${path}):`, error);
    new Toast((error.name === 'AbortError') ? 'Download canceled.' : `Failed to fetch ${path}`);
  }
}

/**
 * records a started download to the php session
 * downloadManager responds with a list of current downloads. downloads[0] if newest download
 * uiManager updates history dialog
 * 
 * @param {String} file
 * 
 * @returns {Boolean}
 */
async function initateDownload(file) {
  try {
    document.querySelector(selectors.historySVG).classList.add('spin');
    const { downloads } = await downloadManager.recordDownload(file);
    console.log(`${downloads.length} download(s) logged`);
    return uiManager.updateHistory(downloads);
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
  const exists = await initateDownload(file.dataset.name);
  if (!exists) {
    document.querySelector(selectors.historySVG).classList.remove('spin');
    return;
  }
  file.dataset.ndx = exists;
  try {
    await download({ ...file.dataset });
  } catch (error) {
    console.error(`Error handling file click for ${file.dataset.name}:`, error);
  } finally {
    document.querySelector(selectors.historySVG).classList.remove('spin');
  }
}

/**
 * browesr support
 */
function checkBrowserSupport() {
  const requirements = {
    fetch: 'fetch' in window,
    streams: 'ReadableStream' in window,
    blob: 'Blob' in window
  };
  
  const missing = Object.entries(requirements)
    .filter(([, supported]) => !supported)
    .map(([feature]) => feature);
    
  if (missing.length > 0) {
    throw new Error(`Browser missing required features: ${missing.join(', ')}`);
  }
}

em.add(window, 'load', async () => {
  try {
    checkBrowserSupport();
    await uiManager.init(fileClicked);
  } catch (error) {
    console.error(`Error in initialization:`, error);
  }
});

em.add(window, 'beforeunload', _ => {
  em.removeAll();
  uiManager.destroy();
});