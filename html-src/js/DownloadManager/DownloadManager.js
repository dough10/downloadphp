import Download from '../Download/Download.js';

export default class DownloadManager {
  /** @type {Array<Download>} List of active downloads. */
  _downloads = [];

  constructor(downloader) {
    this._Downloader = downloader || Download
  }

  /**
   * Gets the count of active downloads.
   * 
   * @returns {Number} - The active download count.
   */
  get activeDownloads() {
    return this._downloads.length;
  }

  /**
   * removes a download object from 'this._download' array by it's ndx
   * 
   * @param {Number} ndx 
   */
  _removeNdx(ndx) {
    const ndxToRemove = this._downloads.findIndex(item => item.ndx === ndx);
    if (ndx !== -1) this._downloads.splice(ndxToRemove, 1);
  }

  /**
   * Marks a pending download as completed. (failed, calceled, completed)
   * 
   * @param {string} name - The name of the file.
   * @param {number} ndx - The index of the download.
   * @param {string} status - The status to set.
   * 
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
      this._removeNdx(ndx);
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
  async getFile(path, ndx) {
    const abortController = new AbortController();
    const signal = abortController.signal;

    const res = await fetch(path, { signal });
    if (!res.ok) {
      console.error(res);
      throw new Error(`Failed getting file: ${path}`);
    }
    const contentLength = res.headers.get('Content-Length');
    const dl = new this._Downloader(res, contentLength, abortController, ndx);
    this._downloads.push(dl);
    return dl;
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