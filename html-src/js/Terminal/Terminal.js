/**
 * Terminal.js
 * 
 * Provides a Terminal class for emulating a retro terminal UI in the browser.
 * Handles input, output, animation, and event management.
 * 
 * @module Terminal
 */

import EventManager from "../utils/EventManager/EventManager.js";
import Input from "./Input.js";
import sleep from "./sleep.js";
import maskString from "./maskString.js";

/**
 * Configuration object for frame timings.
 * @readonly
 * @type {Object}
 * @property {Object} FRAME_TIMINGS - Timing values for blink and idle animations.
 * @property {number} FRAME_TIMINGS.blinkMin - Minimum blink frame time (ms).
 * @property {number} FRAME_TIMINGS.blinkMax - Maximum blink frame time (ms).
 * @property {number} FRAME_TIMINGS.idleMin - Minimum idle time (ms).
 * @property {number} FRAME_TIMINGS.idleMax - Maximum idle time (ms).
 */
const CONFIG = Object.freeze({
  FRAME_TIMINGS: {
    blinkMin: 40,
    blinkMax: 80,
    idleMin: 2000,
    idleMax: 8000,
  },
});

const em = new EventManager();

/**
 * Returns a random integer between min and max (inclusive).
 * @param {number} min - Minimum value.
 * @param {number} max - Maximum value.
 * @returns {number} Random integer.
 */
const randInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

/**
 * Maps a character to a span element with class "letter".
 * @param {string} letter - Character to map.
 * @returns {HTMLSpanElement} Span element for the character.
 */
function mapChar(letter) {
  const span = document.createElement("span");
  span.textContent = letter;
  span.classList.add("letter");
  return span;
}

/**
 * Converts a string to an array of span elements for animation.
 * @param {string} str - String to convert.
 * @returns {HTMLSpanElement[]} Array of span elements.
 */
function animatableText(str) {
  return str.split("").map(mapChar);
}

/**
 * Gets the last DOM node matching the selector.
 * @param {string} selector - CSS selector.
 * @returns {Element|null} Last matching element or null.
 */
function getLast(selector) {
  const nodes = document.querySelectorAll(selector);
  return nodes.length ? nodes[nodes.length - 1] : null;
}

/**
 * Scrolls to the bottom of the main container if already at the bottom.
 */
function scrollToBottomIfAtBottom() {
  const scrollContainer = document.querySelector("main");
  const isAtBottom =
    scrollContainer.scrollTop + scrollContainer.clientHeight >=
    scrollContainer.scrollHeight - 1;

  requestAnimationFrame(() => {
    if (isAtBottom) {
      // document.querySelector('footer').style.opacity = 0;
      scrollContainer.scrollTop = scrollContainer.scrollHeight;
    }
  });
}

/**
 * splits string it longer then a give max length
 * attemps to split at a space
 * 
 * @param {String} str 
 * @param {Number} maxLength 
 * @returns {String[]} 
 */
function splitAtLastSpace(str, maxLength) {
  if (str.length <= maxLength) return [str];
  const idx = str.lastIndexOf(' ', maxLength);
  if (idx === -1) {
    return [str.slice(0, maxLength), str.slice(maxLength)];
  }
  return [str.slice(0, idx), str.slice(idx + 1)];
}

// Encapsulated letterWidth with closure cache
const letterWidth = (() => {
  let lWidth = 0;
  return function letterWidth() {
    if (lWidth) return lWidth;
    const div = document.createElement('div');
    const span = document.createElement('span');
    span.classList.add('letter');
    span.textContent = 'A';
    div.append(span);
    const main = document.querySelector('main');
    if (!main) throw new Error('<main> element not found');
    main.append(div);
    const rect = span.getBoundingClientRect();
    div.remove();
    lWidth = rect.width;
    return lWidth;
  };
})();

/**
 * Terminal class for managing terminal UI, input, output, and animation.
 * @extends EventTarget
 */
export default class Terminal extends EventTarget {
  #input;
  #stopBlink = false;
  #masked = false;
  #busy = true;
  #cachedString;
  #passwordTimer = 0;

  /**
   * Creates a new Terminal instance.
   * @param {string} cachedString - Initial cached input string.
   */
  constructor(input = true) {
    super();

    if (input) this.#input = new Input(
      this.#userTyping.bind(this),
      this.#keypress.bind(this)
    );

    em.add(window, "beforeunload", _ => em.removeAll(), true);

    this.#blink();
  }

  /**
   * Gets the last line element in the terminal.
   * @returns {Element|null} Last line element or null.
   */
  get lastLine() {
    return getLast("main>div");
  }

  /**
   * Gets the last user input element in the terminal.
   * @returns {Element|null} Last user input element or null.
   */
  get lastInput() {
    return getLast(".user-input");
  }

  /**
   * Gets the cached input string.
   * @returns {string} Cached input string.
   */
  get cachedString() {
    return this.#cachedString;
  }

  /**
   * Sets the cached input string.
   * @param {string} str - String to cache.
   */
  set cachedString(str) {
    this.#cachedString = str;
  }

  /**
   * gets the terminals estimated line length that can fit on screen
   */
  get lineWidth() {
    // window width - padding
    const w = window.innerWidth - 32;
    const charCount = w / letterWidth();
    const min = Math.min(charCount, 80);
    return Math.round(min);
  }

  /**
   * Handles the CRT blink animation loop.
   * @private
   * @returns {Promise<void>}
   */
  async #blink() {
    const frameEls = [
      document.getElementById("frame1"),
      document.getElementById("frame2"),
      document.getElementById("frame3"),
    ];

    frameEls[0].textContent = `
                              ...',;;:cccccccc:;,..
                          ..,;:cccc::::ccccclloooolc;'.
                        .',;:::;;;;:dough10kkxxkxxdocccc;;'..
                      .,;;;,,;:coxldKNWWWMMMMWNNWWNNKkdolcccc:,.
                  .',;;,',;lxo:...dXWMMMMMMMMNkloOXNNNX0koc:coo;.
                ..,;:;,,,:ldl'   .kWMMMWXXNWMMMMXd..':d0XWWN0d:;lkd,
              ..,;;,,'':loc.     lKMMMNl. .c0KNWNK:  ..';lx00X0l,cxo,.
            ..''....'cooc.       c0NMMX;   .l0XWN0;       ,ddx00occl:.
          ..'..  .':odc.         .x0KKKkolcld000xc.       .cxxxkkdl:,..
        ..''..   ;dxolc;'         .lxx000kkxx00kc.      .;looolllol:'..
      ..'..    .':lloolc:,..       'lxkkkkk0kd,   ..':clc:::;,,;:;,'..
      ......   ....',;;;:ccc::;;,''',:loddol:,,;:clllolc:;;,'........
          .     ....'''',,,;;:cccccclllloooollllccc:c:::;,'..
                  .......'',,,,,,,,;;::::ccccc::::;;;,,''...
                    ...............''',,,;;;,,''''''......
                          ............................`;
    frameEls[1].textContent = `
                                ...'',;;;;::;;;,'..
                          ..,;:cloodddxxxkkkkkkkkxol;..
                        .';codxxkkk0dough10-000kkkkkkxdoc,..
                      .,codxk0000000000000000000000000kkxddoc,..
                  .':ldxk00000000000000000000000000000000kkxxol:'
                .,:ldxkk000000000K000000000000000K0000000000kkxkkx:.
            ..,coxkk000000000000000kk000000000000000000000000kxxxxl'
            .,;codxxkk00000000kkk0KK0XNWWWWWWWWWNX0kkkkk00000kkxdool;.
          .';::ccldk00KKKK00oc;..,x00KNNXXXXXNNX0000000000kkkkkkxoc:,..
        ..,;,'..,o00000kkxo,       ,lkKKKKKK0K0d,.;ldk000KK0kxxxdoc:'..
      ..,,'.  .,lk0xxxdol:,..       .,ldddl:,.   .,codkk00kxdollc:,...
      ..'.......',;:c::cclccc::;,,,',,;::::;,;;:clodddxdol:;::;'......
          .....  ...''',,,;;;:ccllloooooooooooooolllcccc:;;,....
                  .......'',,,,,,;;;:::ccclllccc:::;;;,''...
                    ..............'''',,;;;;;,,,''''......
                          .............................`;
    frameEls[2].textContent = `
                                ...'',;;;;;;;,,...
                          ..,:loxkk000000KKKKKK00xdc,..
                        .,cox000KXXXXdough10XXXXXXXXK00xo:,..
                    ..;lx000KKKKK000000000000000KKKKXXXXK00xl;..
                  ..,:oxk00000000000000000000000000000000KKKKKK0d:.
              ..;codxkk000kkkkkkkxxxxxxxxxxxxxxxxkkkkkk000000KK0kl'
            ..;ldxkkkkkkxxxxxddddddddddddddddddddddddxxxxxxkkk000xc.
          ..,:oxxkkkkkxxxxdddddddddddddddddddddddddddddddddxxkkkkxl;.
        ..,;codxxkkkxxddddddddddddddddddddddddddddddddxdxxxk000kxdo:..
        .';::::cldk000kkkxxxxxxdddddddddddddddddddddxxxxxkk0000xkddl;..
      .';:;,..,ckXXXKKK0KK000kxk0doddxxdddddddxxxxxxkk0000kkkkkxdoc,..
      .',,''..,:oxxxxxxxkkxkkxk00xxk000000000000KKKKKKK000kxdllll:,..
        .........',,,:ccllllooooxkxxx000kk0000000000000000kxdoc,'...
                ......',;;::cc::clllloddoox0xdxxkxxxxddollllc:'.
                    .....'',,,,,,,;;;;;::cllc::ccc::;;,,,'...
                        ..................'''..'''......`;
    const frameSequence = [0, 1, 2, 1, 0];
    const min = CONFIG.FRAME_TIMINGS.blinkMin;
    const max = CONFIG.FRAME_TIMINGS.blinkMax;
    let frameTime = randInt(min, max);

    while (!this.#stopBlink) {
      for (const index of frameSequence) {
        if (this.#stopBlink) return;
        frameEls.forEach((el, i) => {
          el.style.opacity = i === index ? 1 : 0;
        });
        await sleep(frameTime);
      }
      await sleep(
        randInt(CONFIG.FRAME_TIMINGS.idleMin, CONFIG.FRAME_TIMINGS.idleMax)
      );
      frameTime = randInt(min, max);
    }
  }

  /**
   * Handles the end of the power-up animation.
   * @private
   * @param {Element} crtEl - The CRT element.
   */
  #powerUpEnd(crtEl) {
    em.removeByNamespace('power-up-animation');
    const off = document.querySelectorAll(".crt-state");
    off.forEach((el) => el.classList.remove("off"));
    crtEl.classList.remove("crt-on");
  }

  /**
   * Plays the power-up animation.
   * @returns {Promise<void>} Resolves when animation completes.
   */
  powerUpAnimation() {
    return new Promise(async resolve => {
      const crtEl = document.querySelector(".power-effect");
      await sleep(300);
      em.add(crtEl, 'animationend', _ => {
        this.#powerUpEnd(crtEl);
        resolve();
      }, true, 'power-up-animation');
      crtEl.classList.add("crt-on");
    });
  }

  /**
   * Plays the power-down animation.
   * @returns {Promise<void>} Resolves when animation completes.
   */
  powerDownAnimation() {
    return new Promise(async resolve => {
      this.#stopBlink = true;
      const crtEl = document.querySelector(".power-effect");
      const on = document.querySelectorAll(".crt-state");
      on.forEach((el) => el.classList.add("off"));
      em.add(crtEl, 'animationend', _ => {
        em.removeByNamespace('power-down-animation');
        resolve();
      }, true, 'power-down-animation')
      await sleep(300);
      crtEl.classList.add("crt-off");
    });
  }

  /**
   * Animates the appearance of typed text.
   * @private
   * @returns {Promise<void>} Resolves when animation completes.
   */
  async #emulateTyped() {
    const current = this.lastLine;
    const letters = current.querySelectorAll("span");
    for (const letter of letters) {
      letter.style.opacity = 1;
      await sleep(randInt(5, 20));
    }
  }

  /**
   * Handles user typing events.
   * @private
   * @param {Event} ev - Input event.
   */
  #userTyping(ev) {
    if (document.activeElement !== ev.target && this.#busy) return;
    const current = this.lastInput;
    const inputValue = ev.target.value;
    this.#cachedString = inputValue;
    if (ev.target.type === "password" && this.#masked) {
      current.textContent = maskString(inputValue);
    } else {
      current.textContent = inputValue;
    }
  }

  /**
   * Handles keypress events for the input.
   * @private
   * @param {KeyboardEvent} ev - Keypress event.
   */
  async #keypress(ev) {
    const blockedKeys = ["ArrowLeft", "ArrowRight", "tab"];
    const keyBlocked = blockedKeys.includes(ev.code);

    const keyIsNonChar = ev.key === "Enter" || ev.key === "Backspace";
    const tooLong = this.#input.value.length >= this.#input.maxlength;

    if (keyBlocked || this.#busy || tooLong && !keyIsNonChar) {
      ev.preventDefault();
      return;
    }

    if (ev.altKey && ev.code === "KeyS") {
      ev.preventDefault();
      this.#showPassword();
    } else if (ev.key === "Enter") {
      ev.preventDefault();
      await this.#enterPressed(ev);
    }
  }

  /**
   * Handles the Enter key press event.
   * @private
   * @param {KeyboardEvent} ev - Keypress event.
   * @returns {Promise<void>}
   */
  async #enterPressed(ev) {
    this.#busy = true;
    const input = ev.target;
    const type = input.type;

    const blank = document.createElement('div');
    document.querySelector('main').append(blank);
  
    if (this.#input.isValid)
      this.#emit("enter-pressed", {
        value: input.value,
        inputType: type,
      });

    this.#input.clear();
    this.#cachedString = "";
  }

  /**
   * Temporarily shows the password in plain text for 5 seconds.
   * @private
   */
  #showPassword() {
    const current = this.lastInput;
    const input = document.querySelector("input");
    if (input.type !== "password") return;
    clearTimeout(this.#passwordTimer);
    this.#masked = false;
    current.textContent = this.#cachedString;
    this.#passwordTimer = setTimeout((_) => {
      this.#masked = true;
      current.textContent = maskString(current.textContent);
    }, 5000);
  }

  /**
   * Sets the input to email mode.
   */
  inputEmail() {
    this.#input.setToEmail();
    this.#masked = false;
  }

  /**
   * Sets the input to password mode.
   */
  inputPasswd() {
    this.#input.setToPassword();
    this.#masked = true;
  }

  /**
   * Sets the input to number mode.
   * @param {number} length - Number of digits to accept.
   */
  inputNumbers(length) {
    this.#input.setToNumber(length || 6);
    this.#masked = false;
  }

  /**
   * Sets the input to text
   */
  inputText() {
    this.#input.setToText();
    this.#masked = false;
  }

  /**
   * Pushes an element to the terminal and animates its appearance.
   * @private
   * @param {Element} el - Element to push.
   * @returns {Promise<void>} Resolves when animation completes.
   */
  async #push(el) {
    try {
      const main = document.querySelector("main");
      scrollToBottomIfAtBottom();
      main.append(el);
      await this.#emulateTyped();
    } catch (error) {
      console.error("Error in #push:", error);
      await this.printline("An error occurred. Please try again.");
    }
  }

  /**
   * Prints a countdown in the terminal.
   * @param {number} count - Number to count down from.
   * @returns {Promise<void>}
   */
  async countDown(count) {
    while (count) {
      await this.printline(`${count}`);
      await sleep(1000);
      count--;
    }
  }

  /**
   * Clears the terminal output.
   */
  clear() {
    const main = document.querySelector("main");
    main.replaceChildren();
  }

  /**
   * Creates a visual separator using the '-' character.
   * @returns {Promise<void>}
   */
  async separator() {
    await this.printline('-'.repeat(this.lineWidth));
  }

  /**
   * Prints HTML content to the terminal.
   * @param {string} txt - Text to print before HTML.
   * @param {string} html - HTML content to print.
   * @returns {Promise<void>}
   */
  async printHTML(txt, html) {
    const replacement = document.createElement("div");
    replacement.innerHTML = html;
    await this.printline(txt);
    this.lastLine.remove();
    document.querySelector("main").append(replacement);
  }

  /**
   * Prints a link to the terminal.
   * @param {string} txt - Link text.
   * @param {string} href - Link URL.
   * @returns {Promise<void>}
   */
  async printLink(txt, href) {
    const link = document.createElement("a");
    link.href = href;
    link.textContent = txt;
    await this.printline(txt);
    this.lastLine.remove();
    document.querySelector("main").append(link);
  }

  /**
   * Prints a line of text to the terminal.
   * Splitting it into parts if needed
   * @param {string} str - Text to print.
   * @returns {Promise<void>}
   */
  async printline(str) {
    const lines = splitAtLastSpace(str, this.lineWidth);
    for (const line of lines) {
      await this.#printAndSub(line);
    }
  }

  /**
   * Prompts the user for input in the terminal.
   * @param {string} str - Prompt text.
   * @returns {Promise<void>}
   */
  async promptInput(str) {
    const replacement = document.createElement("span");
    replacement.textContent = str;
    const inputPrompt = document.createElement("span");
    inputPrompt.classList.add("user-input");
    const row = document.createElement("div");
    row.append(...animatableText(str), inputPrompt);
    await this.#push(row);
    const letters = this.lastLine.querySelectorAll(".letter");
    letters.forEach((letter) => letter.remove());
    row.prepend(replacement);
    this.#busy = false;
  }

  /**
   * Prints a line of text to the terminal.
   * @param {string} str - Text to print.
   * @returns {Promise<void>}
   */
  async #printAndSub(str) {
    const replacement = document.createElement("div");
    replacement.textContent = str;
    const row = document.createElement("div");
    row.append(...animatableText(str));
    await this.#push(row);
    this.lastLine.remove();
    document.querySelector("main").append(replacement);
  }

  /**
   * Emits a custom event from the terminal.
   * @private
   * @param {string} name - Event name.
   * @param {Object} [detail={}] - Event detail object.
   */
  #emit(name, detail = {}) {
    this.dispatchEvent(new CustomEvent(name, { detail }));
  }
}
