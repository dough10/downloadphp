const lsState = Number(localStorage.getItem('sound'));

let licenseDisplayed = false;

const soundLicense = '<--\nacess denied buzz by Jacco18\nhttps://freesound.org/s/419023/\nLicense: Creative Commons 0\n-->';

let sound = Boolean(lsState);

function toggleSoundFX() {
  sound = !sound;
  if (sound && !licenseDisplayed) {
    licenseDisplayed = true;
    console.log(soundLicense);
  }
  localStorage.setItem('sound', Number(sound));
  return `SoundFX: ${sound ? 'On':'Off'}`;
}

/**
 * List of files actively being downloaded
 */
let activedownloads = [];

/**
 * wait an ammout of time
 * 
 * 
 * @param {ms} milliseconds
 * 
 * @returns {Promise<Void>} Nothing 
 */
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * check if a string appears to be a URL
 * 
 * @param {String} str
 *  
 * @returns {Boolean}
 */
function looksLikeAUrl(str) {
  return str.startsWith('http://') || str.startsWith('https://');
}

/**
 * toast overflow
 */
const _toastCache = [];

/**
 * display a toast message
 *
 * @param {String} message - text to be displayed in the toast
 * @param {Number} _timeout - in seconds  || defualt 3.5 seconds  ** optional
 * @param {String} link - url to go to when toast is clicked
 * @param {String} linkText - yellow text
 */
class Toast {
  constructor(message, _timeout, link, linkText) {
    // push toast to cache if currently displaying a toast
    if (document.querySelector('#toast')) {
      _toastCache.push([
        message,
        _timeout,
        link,
        linkText
      ]);
      return;
    }
    // bind this to internal functions
    this._transitionEnd = this._transitionEnd.bind(this);
    this._cleanUp = this._cleanUp.bind(this);
    this._clicked = this._clicked.bind(this);

    // create the toast
    this._timer = false;
    this._timeout = _timeout * 1000 || 3500;
    this.toast = this._createToast();
    if (link && linkText) {
      this.toast.append(this._withLink(message, link, linkText));
    } else {
      this.toast.textContent = message;
    }
    console.log(message);
    document.querySelector('body').append(this.toast);
    sleep(25).then(_ => requestAnimationFrame(_ => {
      this.toast.toggleAttribute('opened');
    }));
  }

  /**
   * returns a new toast html element
   * 
   * @returns {HTMLElement} hot toast
   */
  _createToast() {
    const toast = document.createElement('div');
    toast.id = 'toast';
    toast.classList.add('toast');
    toast.addEventListener('transitionend', this._transitionEnd, true);
    toast.addEventListener('click', this._clicked, true);
    return toast;
  }

  /**
   * butter in the toast with some link info
   * @param {String} message - text string
   * @param {String} link - URL
   * @param {String} linkText - text string
   * 
   * @returns {HTMLElement} link wrapper
   */
  _withLink(message, link, linkText) {
    const mText = document.createElement('div');
    mText.textContent = message;
    
    if (typeof link === 'string' && !looksLikeAUrl(link)) {
      return mText;
    }
    
    const lText = document.createElement('div');
    lText.textContent = linkText;
    lText.classList.add('yellow-text');

    const wrapper = document.createElement('div');
    wrapper.classList.add('toast-wrapper');
    wrapper.append(mText, lText);

    this.link = link;

    return wrapper;
  }

  /**
   * event handler for toast click
   */
  _clicked(e) {
    if (this.link && typeof this.link === 'string' && isValidURL(this.link)) {
      window.open(this.link, "_blank");
    } else if (this.link && typeof this.link === 'function') {
      this.link();
    } else if (this.link) {
      console.error(`Toast "link" paramater must be a valid URL or function: Value=${this.link}, type=${typeof this.link}`);
    }
    this._cleanUp();
  }

  /**
   * play closing animation and remove element from document
   */
  _cleanUp() {
    if (this._timer) {
      clearTimeout(this._timer);
      this._timer = false;
    }
    this.toast.addEventListener('transitionend', _ => {
      if (this.toast) this.toast.remove();
    });
    requestAnimationFrame(_ => {
      this.toast.removeAttribute('opened');
    });
  }

  /**
   * called after opening animation
   * sets up closing animation
   */
  _transitionEnd() {
    this._timer = setTimeout(this._cleanUp, this._timeout);
    this.toast.removeEventListener('transitionend', this._transitionEnd);
  }
}

/**
 * infinite loop to look if cached toast messages to be displayed
 */
setInterval(_ => {
  if (!_toastCache.length) {
    return;
  }
  if (document.querySelector('#toast')) {
    return;
  }
  new Toast(
    _toastCache[0][0],
    _toastCache[0][1],
    _toastCache[0][2],
    _toastCache[0][3]
  );
  _toastCache.splice(0, 1);
}, 500);

/**
 * creates html entry for download log
 * 
 * @param {Object} dl
 * @param {String} dl.name
 * @param {String} dl.path
 * 
 * @returns {HTMLElement}
 */
function createLogEntry(dl) {
  const name = document.createElement('strong');
  name.textContent = dl.name;

  const status = document.createElement('span');
  status.textContent = dl.status;

  const li = document.createElement('li');
  li.append(name, status);
  li.dataset.ndx = dl.id;
  return li;
}

/**
 * format bytes to be readable by humans
 * 
 * @param {Number} bytes
 * 
 * @returns {String}
 */
function formatBytes(bytes) {
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  if (bytes === 0) return '0 B';
  const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
  return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
}

/**
 * makes a pending file as complete in download logs
 * 
 * @param {String} name
 */
async function logCompleted(name, ndx, status) {
  const postBody = new FormData();
  postBody.append('file', name);
  postBody.append('ndx', ndx);
  const res = await fetch(`file-status/${ndx}/${status}`, {
    method: 'POST',
    body: postBody
  });
  if (!res.ok) {
    new Toast(`Failed updating ${name} completed status`);
    return;
  }
  const updates = await res.json();
  updates.reverse();
  const html = updates.map(createLogEntry);
  document.querySelector('#history>ul').replaceChildren(...html);      
}

/**
 * opens file save dialog
 * 
 * @param {Bytes} chunks 
 * @param {String} name 
 */
async function cueFileSave(chunks, name, ndx) {
  const fileBlob = new Blob(chunks);
  const link = document.createElement('a');
  link.href = URL.createObjectURL(fileBlob);
  link.download = name;
  link.click();
  URL.revokeObjectURL(link.href);
  logCompleted(name, ndx, true);
}

/**
 * cleans up finished download
 * 
 * @param {String} name filename
 * @param {HTMLElement} dls download list ui
 * @param {HTMLElement} row the current download
 */
function cleanupDownload(name, ndx, dls, row) {
  if ((dls.querySelectorAll('.row').length) <= 1) {
    dls.removeAttribute('open');
  }
  row.remove();
  const ndxToRemove = activedownloads.findIndex(item => item.name === name && item.ndx === ndx);
  if (ndx !== -1) activedownloads.splice(ndxToRemove, 1);
}

/**
 * creates download progress UI
 * 
 * @param {String} name filename
 * 
 * @returns {Object} ui
 */
function createDownloadUI(name, abortController, ndx) {
  const bar = document.createElement('div');
  bar.classList.add('bar');
  
  const barWapper = document.createElement('div');
  barWapper.classList.add('bar-wrapper');
  barWapper.append(bar);

  const filename = document.createElement('div');
  filename.textContent = name;
  const dlSpeed = document.createElement('div');
  dlSpeed.textContent = '0B/s';

  const dlInfo = document.createElement('div');
  dlInfo.classList.add('dl-info');
  dlInfo.append(filename, dlSpeed);
  
  const dlWrapper = document.createElement('div');
  dlWrapper.title = `downloading: ${name}`;
  dlWrapper.classList.add('dl-wrapper');
  dlWrapper.append(barWapper, dlInfo);

  const svgPath = document.createElementNS("http://www.w3.org/2000/svg", 'path');
  svgPath.setAttribute("d", "M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z");
  svgPath.setAttribute('fill', 'red');

  const svg = document.createElementNS("http://www.w3.org/2000/svg", 'svg');
  svg.append(svgPath);
  svg.setAttribute('viewBox', "0 0 24 24");

  const cancelButton = document.createElement('button');
  cancelButton.title = 'cancel download';
  cancelButton.classList.add('small-button', 'margin-t-minus');
  cancelButton.append(svg);

  const row = document.createElement('div');
  row.classList.add('row');
  row.append(dlWrapper, cancelButton);

  const dls = document.querySelector('#dls');
  dls.append(row);
  dls.setAttribute('open', true);

  cancelButton.addEventListener('click', _ => {
    abortController.abort();
    document.querySelector('#hist_but>svg').classList.remove('spin');
    bar.style.transform = `translateX(-100%)`;
    cleanupDownload(name, ndx, dls, row);
  });
  return {row, dlSpeed, bar};
}

/**
 * downloads a file, calculates % and rate and updates ui
 * 
 * @param {Object} res response
 * @param {Number} contentLength bytes to be downloaded
 * @param {Number} ndx index of download entry in sqlite
 * @param {Object} ui ui elements that need updating
 * 
 * @returns {bytes} 
 */
async function getFile(res, ui, ndx, contentLength) {
  const reader = res.body.getReader();
  const startTime = Date.now();
  let lastTime = startTime;
  const chunks = [];
  let loadedBytes = 0;
  let lastLoadedBytes = 0;
  const totalBytes = parseInt(contentLength, 10);
  let speed = 0;

  while (true) {
    const { done, value } = await reader.read();
    const currentTime = Date.now();
    const timeElapsed = currentTime - lastTime;
    if (done) {
      new Toast('Download Complete.', 2);
      ui.bar.style.transform = `translateX(-0%)`;
      const downloadSpeed = loadedBytes / (timeElapsed / 1000);
      const speed = formatBytes(downloadSpeed);
      ui.dlSpeed.textContent = `100% @ ${speed}/s`;
      await sleep(500);
      cleanupDownload(name, ndx, dls, ui.row);
      break;
    }
    loadedBytes += value.length;
    chunks.push(value);
    const progress = (loadedBytes / totalBytes) * 100;
    ui.bar.style.transform = `translateX(-${100 - progress}%)`;
    ui.dlSpeed.textContent = `${progress.toFixed(1)}% @ ${speed}/s`;
    if (timeElapsed >= 1000) {
      const bytesDownloaded = loadedBytes - lastLoadedBytes;
      const downloadSpeed = bytesDownloaded / (timeElapsed / 1000);
      speed = formatBytes(downloadSpeed);
      lastTime = currentTime;
      lastLoadedBytes = loadedBytes;
    }
  }
  return chunks;
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
  const clearButton = document.querySelector('#history>.clear');
  clearButton.setAttribute('disabled', true);
  const abortController = new AbortController();
  const signal = abortController.signal;
  new Toast(`Downloading: ${name}`, 1);
  let ui;
  try {
    const res = await fetch(path, { signal });
    if (!res.ok) {
      new Toast(`Failed to fetch ${path}`);
      return;
    }
    const contentLength = res.headers.get('Content-Length');
    ui = createDownloadUI(name, abortController, ndx);
    const chunks = await getFile(res, ui, ndx, contentLength);
    cueFileSave(chunks, name, ndx);
  } catch(error) {
    if (error.name === 'AbortError') {
      new Toast('Download canceled.');
      logCompleted(name, ndx, 'canceled');
    } else {
      new Toast(`Failed to fetch ${path}`);
      console.error('An error occurred during the fetch:', error);
      logCompleted(name, ndx, 'failed');
    }
    await sleep(1000);
    cleanupDownload(name, ndx, document.querySelector('#dls'), ui.row);
  }
  clearButton.removeAttribute('disabled');
}

/**
 * records a download to the php session
 * 
 * @param {String} file
 * 
 * @returns {Boolean}
 */
async function recordDownload(file) {
  const postBody = new FormData();
  postBody.append('file', file);
  const res = await fetch(`request-file/${file}`, {
    method: 'POST',
    body: postBody
  });
  if (!res.ok) return false;
  const downloaded = await res.json();
  const liList = downloaded.downloads.map(createLogEntry);
  liList.reverse();
  const list = document.querySelector('#history>ul');
  list.replaceChildren(...liList);
  console.log(`${downloaded.downloads.length} download(s) logged`);
  return liList[0].dataset.ndx;
}

window.onload = () => {
  if (sound && !licenseDisplayed) {
    licenseDisplayed = true;
    console.log(soundLicense);
  }

  // file listing clicked
  const files = document.querySelectorAll('.file');
  files.forEach(file => {
    file.addEventListener('click', async _ => {
      document.querySelector('#hist_but>svg').classList.add('spin');
      const exists = await recordDownload(file.dataset.name);
      if (!exists) {
        return;
      }
      file.dataset.ndx = exists;
      activedownloads.push({
        name: file.dataset.name, 
        ndx: file.dataset.ndx
      });
      await download({...file.dataset});
      document.querySelector('#hist_but>svg').classList.remove('spin');
    });
    file.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === 'Space') {
        event.preventDefault();
        file.click();
      }
    });
  });

  // clear button clicked
  const clearButton = document.querySelector('#history>.clear');
  clearButton.addEventListener('click', async _ => {
    const none = document.querySelectorAll('#history>ul>li').length < 1;
    if (none) {
      new Toast('Nothing to clear.');
      return;
    }
    clearButton.setAttribute('disabled', true);
    const res = await fetch('reset', {method: 'POST'});
    clearButton.removeAttribute('disabled');
    if (!res.ok) {
      new Toast('Error: resetting history');
      return;
    }
    const data = await res.json();
    new Toast('History cleared.');
    const list = document.querySelector('#history>ul');
    list.innerHTML = '';
  });

  // history icon clicked
  document.querySelector('#hist_but').addEventListener('click', _ => {
    document.querySelector('#history').showModal();
  });

  // arrow clicked
  const toTop = document.querySelector('.to-top');
  toTop.addEventListener('click', _ => document.documentElement.scrollTo({
    top: 0,
    behavior: 'smooth'
  }));

  // scroll main document
  let lastTop = 0;
  document.onscroll = () => {
    const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
    if (scrollTop < lastTop) {
      toTop.setAttribute('disabled', true);
    } else if (scrollTop > 0) {
      toTop.removeAttribute('disabled');
    } else {
      toTop.setAttribute('disabled', true);
    }

    lastTop = scrollTop;
  };

  // clicked dialog close
  document.querySelectorAll('dialog>.close').forEach(button => {
    button.addEventListener('click', _ => {
      const dialog = button.parentElement;
      dialog.close();
    });
  });

  // clickd outsde dialog
  const dialogs = document.querySelectorAll('dialog');
  dialogs.forEach(dialog => {
    dialog.addEventListener('click', event => {
      const closeButton = dialog.querySelector('.small-button.close');
      const aniend = _ => {
        dialog.removeEventListener('animationend', aniend);
        closeButton.classList.remove('attention');
        dialog.classList.remove('dialog-attention');
      };
      var rect = dialog.getBoundingClientRect();
      var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
        rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
      if (!isInDialog) {
        if (sound) document.querySelector('#error').play();
        dialog.addEventListener('animationend', aniend);
        closeButton.classList.add('attention');
        dialog.classList.add('dialog-attention');
      }
    });
  });

  // navigating away from site
  window.addEventListener('beforeunload', event => {
    if (!activedownloads.length) {
      return;
    }
    // user has active downloads
    activedownloads.forEach(dl => logCompleted(dl.name, dl.ndx, 'canceled'));
    const message = 'Download(s) active. Are you sure you want to leave?';
    event.returnValue = message; 
    return message;
  });      
};