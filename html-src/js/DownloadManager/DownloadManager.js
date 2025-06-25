import Download from '../Download/Download.js';
import retry from '../utils/retry.js';

function getCookie(name) {
  return document.cookie.split('; ').find(row => row.startsWith(name + '='))
    ?.split('=')[1];
}

/** @type {Object} fetch POST request options */
export const _POST_OPTIONS = Object.freeze({
  method: 'POST',
  credentials: 'same-origin',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-Token': getCookie('csrf_token')
  }
});

/**
 * check response object is ok
 * 
 * @param {Object} res 
 * @param {String} error 
 */
function checkResponseOk(res, error) {
  if (!res.ok) throw new Error(error);
}


export default class DownloadManager {
  /** @type {Array<Download>} List of active downloads. */
  #downloads = [];

  constructor(downloader) {
    this._Download = downloader || Download
  }

  /**
   * Gets the count of active downloads.
   * 
   * @returns {Number} - The active download count.
   */
  get activeDownloads() {
    return this.#downloads.length;
  }

  /**
   * Gets the downloads array
   * 
   * @returns {Array} - the current downloads array
   */
  get downloads() {
    return [...this.#downloads];
  }

  /**
   * check if download exists for an index
   * 
   * @param {Number} ndx 
   * 
   * @returns {Boolean}
   */
  hasDownload(ndx) {
    return this.#downloads.some(dl => dl.ndx === ndx);
  }
  

  /**
   * removes a download object from 'this._downloads' array by its 'ndx'
   * 
   * @param {Number} ndx 
   */
  #removeNdx(ndx) {
    const ndxToRemove = this.#downloads.findIndex(item => item.ndx === ndx);
    if (ndxToRemove !== -1) this.#downloads.splice(ndxToRemove, 1);
  }

  /**
   * Logs the completion status of a download to the backend and removes it from the active list.
   * 
   * @param {string} name - The name of the file.
   * @param {number} ndx - The unique download identifier.
   * @param {string} status - The status to set (e.g. "completed", "failed", "canceled").
   * @returns {Array} - The updated download list from the backend.
   */
  async logCompleted(name, ndx, status) {
    try {
      const res = await retry(`file-status/${ndx}/${status}`, _POST_OPTIONS);
      checkResponseOk(res, `Failed updating ${name} completed status`);
      const updates = await res.json();
      this.#removeNdx(ndx);
      return updates;
    } catch (error) {
      throw new Error(`Error setting completed status: ${error.message}`);
    }
  }

  /**
   * 
   * @param {String} file 
   * 
   * @returns {Object}
   */
  async recordDownload(file) {
    const res = await retry(`request-file/${file}`, _POST_OPTIONS);
    checkResponseOk(res, 'Download record failed');
    const data = await res.json();
    return data;
  }

  /**
   * 
   * @param {String} path
   * @param {Number} ndx 
   * 
   * @returns {Object}
   */
  async get(path, ndx) {
    const abortController = new AbortController();
    const signal = abortController.signal;

    const res = await retry(path, { ..._POST_OPTIONS, signal });
    checkResponseOk(res, `Failed getting file: ${path}`);
    const contentLength = res.headers.get('Content-Length');
    const dl = new this._Download(res, contentLength, abortController, ndx);
    this.#downloads.push(dl);
    return dl;
  }

  /**
   * 
   * @returns {Object}
   */
  async clearHistory() {
    const res = await retry('reset', _POST_OPTIONS);
    checkResponseOk(res, 'download history clear failed');
    return await res.json();
  }

}