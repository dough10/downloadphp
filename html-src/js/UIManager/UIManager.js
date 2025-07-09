import Terminal from "../Terminal/Terminal.js";
import Toast from "../Toast/Toast.js";
import selectors from "../utils/selectors.js";
import {initiateDialogs, destroy} from '../dialog/dialog.js';
import EventManager from "../utils/EventManager/EventManager.js";
import sleep from "../utils/sleep.js";

const em = new EventManager();
const term = new Terminal();

export default class UIManager {
  /**
   * creates html entry for download log
   * 
   * @param {Object} dl
   * @param {String} dl.name
   * @param {String} dl.path
   * 
   * @returns {HTMLElement}
   */
  createLogEntry(dl) {
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
   * creates download progress UI
   * 
   * @param {String} name filename
   * 
   * @returns {Object} ui
   */
  createDownloadUI(name, stop, ndx) {
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
    svgPath.setAttribute('fill', 'currentColor');

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
    row.id = `ndx${ndx}`;

    const dls = document.querySelector('#dls');
    dls.append(row);
    dls.setAttribute('open', true);

    cancelButton.addEventListener('click', _ => {
      cancelButton.setAttribute('disabled', true);
      stop();
    });
    return {dlSpeed, bar};
  } 

  /**
   * updates download progress bar and download speed text
   * 
   * @param {Object} update 
   * @param {HTMLElement} bar 
   * @param {HTMLElement} dlSpeed 
   */
  progressUpdated(update, bar, dlSpeed) {
    const {transform, speed, progress} = update.detail;
    requestAnimationFrame(_ => {
      bar.style.transform = `translateX(-${transform}%)`;
      dlSpeed.textContent = `${progress}% @ ${speed}`;
    });
  }

  /**
   * a download has ended (failed, canceled)
   * 
   * @param {HTMLElement} row 
   * @param {String} str 
   */
  downloadEnded(ndx, str) {
    new Toast(str, 2);
    this.setButtonDisabledState(selectors.clearHistoryButton, false);
    this.cleanupDownload(ndx);
  }
  
  /**
   * cleans up finished download
   * 
   * @param {HTMLElement} row the current download
  */
  async cleanupDownload(ndx) {
    await sleep(1000);
    const download = document.querySelector(`#ndx${ndx}`);
    download.remove();
    const dls = document.querySelector(selectors.activeDownloadList);
    const removingLast = dls.querySelectorAll('.row').length <= 0;
    if (removingLast) {
      document.querySelector(selectors.historySVG).classList.remove('spin');
      dls.removeAttribute('open');
    }
  }

  /**
   * document scrolled callback
   */
  documentScrolled() {
    const toTop = document.querySelector(selectors.toTop);
    const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
    const closeToTop = scrollTop < (window.innerHeight * 0.2);
    closeToTop ? toTop.setAttribute('disabled', true) : toTop.removeAttribute('disabled');
  }

  /**
   * toggles button disabled state based on function input
   * 
   * @param {String} buttonSelector 
   * @param {Boolean} state 
   */
  setButtonDisabledState(buttonSelector, state) {
    const button = document.querySelector(buttonSelector);
    state ? button.setAttribute('disabled', true) : button.removeAttribute('disabled');
  }

  /**
   * pushes an array of downloads to the history dialog
   * 
   * @param {Array} downloads 
   * 
   * @returns {String} newest elements index: (database id)
   */
  updateHistory(downloads) {
    if (typeof downloads !== 'object') {
      return;
    }
    const htmlElements = downloads.map(this.createLogEntry);
    htmlElements.reverse();
    const historyList = document.querySelector(selectors.historyList);
    historyList.replaceChildren(...htmlElements);
    return htmlElements[0]?.dataset.ndx;
  }

  /**
   * 
   */
  destroy() {
    destroy();
    em.removeAll();
  }

  async listdownloads() {
    await term.printline("Files:")
    for (let i = 0; i < window.files.length; i++) {
      const file = window.files[i];
      const str = `  ${i + 1}). ${file.name} (${file.size})`;
      await term.printHTML(str, `<div href='#' id='f${file.id}' data-name='${file.name}' data-path='files/${file.path}'>${str}</div>`);
    }
    await term.printline("  6). Clear")
    await term.promptInput('Choose:');
  }
 
  /**
   * makes file list interactive
   */
  async init(fileClicked) {
    initiateDialogs(this);
    const toTop = document.querySelector(selectors.toTop);
    em.add(toTop, 'click', _ => document.documentElement.scrollTo({
      top: 0,
      behavior: 'smooth'
    }));

    em.add(document, 'scroll', this.documentScrolled);
    
    term.inputNumbers(1);
    em.add(term, 'enter-pressed', async ev => {
      const type = ev.detail.type;
      const value = ev.detail.value;
      if (value > window.files.length + 1) {
        await term.printline(`Invalid choice. Try again.`);
        await term.separator();
        await this.listdownloads();
        return;
      }
      try {
        const fileEl = document.querySelector(`#f${window.files[value - 1].id}`);
        await fileClicked(fileEl);
        await term.printline(`Downloading ${fileEl.dataset.name}`);
        await term.separator();
        await this.listdownloads();
      } catch {
        await term.clear();
        await this.listdownloads();
        return;
      }
    });

    await this.listdownloads();
  }
}