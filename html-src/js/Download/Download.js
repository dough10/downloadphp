/**
 * Calculates the percentage of a value relative to a total.
 * 
 * @param {number} little - The smaller value.
 * @param {number} big - The total value.
 * @returns {string} - The percentage as a string with one decimal place.
 */
export function calculatePercent(little, big) {
  if (big === 0) return 0;
  const p = ((little / big) * 100).toFixed(1);
  return isNaN(p) ? 0 : p;
}

/**
 * Calculates the download speed.
 * 
 * @param {number} bytes - The number of bytes downloaded.
 * @param {number} time - The time elapsed in milliseconds.
 * @returns {string} - The speed in bps, kbps, or mbps.
 */
export function calculateSpeed(bytes, time) {
  if (time <= 0) return '0 bps';
  const speedInBps = (bytes * 8) / (time / 1000);
  if (isNaN(speedInBps) || speedInBps === 0) {
    return '0 bps';
  }

  if (speedInBps < 1000) {
    return `${speedInBps.toFixed(1)} bps`;
  } else if (speedInBps < 1000000) {
    return `${(speedInBps / 1000).toFixed(1)} kbps`;
  } else {
    return `${(speedInBps / 1000000).toFixed(1)} mbps`;
  }
}

/**
 * Handles file downloads and emits events for progress and completion.
 * 
 * @fires update - Emitted during the download process with progress updates.
 * @fires finished - Emitted when the download is complete.
 * @fires stopped - Emitted when the download is stopped.
 */
export default class Download extends EventTarget {
  _speed = 0;
  _chunks = [];
  _loadedBytes = 0;
  _lastLoadedBytes = 0;
  _lastSpeedUpdateTime = 0;
  _downloading = true;
  _progress = 0;

  /**
   * Creates a new Download instance.
   * 
   * @param {Response} response - The fetch response object.
   * @param {number} contentLength - The total size of the file in bytes.
   * @param {Object} abortControler
   * @param {Number} ndx  
   */
  constructor(response, contentLength, abortControler, ndx) {
    super();
    this._reader = response.body.getReader();
    this._length = contentLength;
    this._totalBytes = parseInt(contentLength, 10);
    this._abortControler = abortControler;
    this.ndx = ndx;
  }

  get downloading() {
    return this._downloading;
  }

  /**
   * Starts the download process.
   */
  async start() {
    this._startTime = Date.now();
    this._lastSpeedUpdateTime = this._startTime;

    while (this._downloading) {
      const { done, value } = await this._reader.read();

      // Check if the download was stopped
      if (!this._downloading) {
        break;
      }

      const currentTime = Date.now();

      this._loadedBytes += value?.length || 0;
      if (value) this._chunks.push(value);
      this._progress = calculatePercent(this._loadedBytes, this._totalBytes);

      if (currentTime - this._lastSpeedUpdateTime >= 1000) {
        const bytesDownloaded = this._loadedBytes - this._lastLoadedBytes;
        this._speed = calculateSpeed(bytesDownloaded, currentTime - this._lastSpeedUpdateTime);
        this._lastLoadedBytes = this._loadedBytes;
        this._lastSpeedUpdateTime = currentTime;
      }

      this._emitEvent('update', {
        speed: this._speed,
        transform: 100 - this._progress,
        progress: this._progress,
      });

      if (done) {
        this._emitEvent('finished', { chunks: this._chunks });
        break;
      }
    }
  }

  /**
   * Stops the download and cleans up resources.
   */
  stop() {
    this._downloading = false;
    this._reader.cancel();
    this._chunks = [];
    this._speed = 0;
    this._progress = 0;
    const stopped = new CustomEvent('stopped');
    this._abortControler.abort();
    this.dispatchEvent(stopped);
  }

  /**
   * Emits a custom event with the given name and data.
   * 
   * @param {string} name - The name of the event.
   * @param {Object} data - The data to include in the event.
   */
  _emitEvent(name, data) {
    this.dispatchEvent(new CustomEvent(name, { detail: data }));
  }
}