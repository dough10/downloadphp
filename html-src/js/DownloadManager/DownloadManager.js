// import Toast from '../Toast/Toast.js';
import Download from '../Download/Download.js';

export default class DownloadManager {
  /** @type {Array<Download>} List of active downloads. */
  _downloads = [];

  constructor(downloader) {
    this._Downloader = downloader || Download
  }

  /**
   * Gets the list of active downloads.
   * 
   * @returns {Array<Download>} - The active downloads.
   */
  get activeDownloads() {
    return this._downloads;
  }

  /**
   * Marks a pending download as completed.
   * 
   * @param {string} name - The name of the file.
   * @param {number} ndx - The index of the download.
   * @param {string} status - The status to set.
   * @returns {Array} - The updated download list.
   */
  async markCompleted(name, ndx, status) {
    try {
      const postBody = new FormData();
      postBody.append('file', name);
      postBody.append('ndx', ndx);
      const res = await fetch(`file-status/${ndx}/${status}`, {
        method: 'POST',
        body: postBody,
      });
      if (!res.ok) {
        throw new Error(`Failed updating ${name} completed status`);
      }
      const updates = await res.json();
      updates.reverse();
      return updates;
    } catch (error) {
      throw new Error(`Error marking ${name} as completed: ${error.message}`);
    }
  }

  /**
   * 
   * @param {String} file 
   * 
   * @returns {Object}
   */
  async recordDownload(file) {
    const postBody = new FormData();
    postBody.append('file', file);
    const res = await fetch(`request-file/${file}`, {
      method: 'POST',
      body: postBody
    });
    if (!res.ok) throw new Error('Download record failed');
    return await res.json();
  }

  /**
   * 
   * @param {String} path 
   * 
   * @returns {Object}
   */
  async getFile(path) {
    try {
      const abortController = new AbortController();
      const signal = abortController.signal;

      const res = await fetch(path, { signal });
      if (!res.ok) {
        console.error(res);
        throw new Error(`Failed getting file: ${path}`);
      }
      const contentLength = res.headers.get('Content-Length');
      const dl = new this._Downloader(res, contentLength, abortController);
      this._downloads.push(dl);
      return dl;
    } catch(error) {}
  }

  /**
   * 
   * @returns {Object}
   */
  async clearHistory() {
    const res = await fetch('reset', {method: 'POST'});
    if (!res.ok) throw new Error('download history clear failed');
    return await res.json();
  }

}