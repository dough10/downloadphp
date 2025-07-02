/**
 * Input.js
 * 
 * Provides an Input class for managing and validating a single input element
 * in the terminal UI, including password and email modes.
 * 
 * @module Input
 */

import EventManager from "../utils/EventManager/EventManager.js";

/**
 * Event manager instance for handling DOM events.
 * @type {EventManager}
 */
const em = new EventManager();

/**
 * Configuration constants for password validation.
 * @readonly
 * @type {Object}
 */
const CONFIG = Object.freeze({
  MIN_PASSWD_LENGTH: 8,
  PASSWORD_PATTERN: "^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)[a-zA-Z\\d@$!%*?&]{8,}$"
});

/**
 * Ensures the input element remains focused.
 * @param {HTMLElement} el - The input element to keep focused.
 */
function keepFocused(el) {
  const refocus = () => {
    if (document.activeElement !== el && document.body.contains(el) && !el.disabled && el.offsetParent !== null) {
      setTimeout(() => el.focus(), 0);
    }
  };
  em.add(el, "blur", refocus);
  em.add(document, "focusin", refocus, true);
  el.focus();
}

/**
 * Class for managing a single input element in the terminal UI.
 */
export default class Input {
  #input;

  /**
   * Creates a new Input instance.
   * @param {Function} userTyping - Handler for input events.
   * @param {Function} keypress - Handler for keydown events.
   * @param {string} [selector="input"] - CSS selector for the input element.
   * @throws {Error} If the input element is not found.
   */
  constructor(userTyping, keypress, selector = "input") {
    this.#input = document.querySelector(selector);
    if (!this.#input) throw new Error(`Input element not found for selector: ${selector}`);
    em.add(this.#input, "input", userTyping, true);
    em.add(window, "keydown", keypress, true);
    em.add(window, "beforeunload", _ => em.removeAll());
    keepFocused(this.#input);
    setInterval(_ => {
      if (document.activeElement === document.body) this.#input.focus();
    }, 1000);
  }

  /**
   * Sets the input element to password mode with validation.
   */
  setToPassword() {
    this.#input.type = "password";
    this.#input.setAttribute("required", true);
    this.#input.maxlength = 32;
    this.#input.setAttribute("pattern", CONFIG.PASSWORD_PATTERN);
  }

  /**
   * Sets the input element to email
   */
  setToEmail() {
    this.#input.type = "email";
    this.#input.maxlength = 32;
    this.#input.removeAttribute("pattern");
  }

  /**
   * sets the input element to number
   */
  setToNumber(length) {
    this.#input.type = "number";
    this.#input.maxlength = length;
    this.#input.removeAttribute("pattern");
  }

  /**
   * sets the input element to text
   */
  setToText() {
    this.#input.type = "text";
    this.#input.maxlength = 32;
    this.#input.removeAttribute("pattern");
  }

  /**
   * Clears the input value.
   */
  clear() {
    this.#input.value = "";
  }

  /**
   * Gets the current value of the input.
   * @returns {string}
   */
  get value() {
    return this.#input.value;
  }

  get maxlength() {
    return this.#input.maxlength;
  }

  /**
   * Checks if the input value is valid according to its type and pattern.
   * @returns {boolean}
   */
  get isValid() {
    const length = (this.#input.type === "password")
      ? CONFIG.MIN_PASSWD_LENGTH
      : 0;
    return this.#input.value.length >= length && this.#input.checkValidity();
  }
}
