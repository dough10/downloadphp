import Toast from "../Toast/Toast.js";
import selectors from "../utils/selectors.js";

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
      stop();
      document.querySelector('#hist_but>svg').classList.remove('spin');
      bar.style.transform = `translateX(-100%)`;
    });
    return {row, dlSpeed, bar};
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

  downloadEnded(row, str) {
    new Toast(str, 2);
    this.setButtonDisabledState(selectors.clearHistoryButton, false);
    this.cleanupDownload(row);
  }

  /**
   * cleans up finished download
   * 
   * @param {HTMLElement} row the current download
   */
  cleanupDownload(row) {
    const dls = document.querySelector(selectors.activeDownloadList);
    if ((dls.querySelectorAll('.row').length) <= 1) {
      dls.removeAttribute('open');
    }
    row.remove();
    // const ndxToRemove = activedownloads.findIndex(item => item.name === name && item.ndx === ndx);
    // if (ndx !== -1) activedownloads.splice(ndxToRemove, 1);
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

  setButtonDisabledState(buttonSelector, state) {
    const button = document.querySelector(buttonSelector);
    state ? button.setAttribute('disabled', true) : button.removeAttribute('disabled');
  }
}