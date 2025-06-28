const HOST = "https://auth.dough10.me";
const ME_URL = `${HOST}/me`;
const LOGOUT_URL = "/logout";

const DOWNLOAD_URL = "https://download.dough10.me";

const ICON_VIEWBOX = "0 -960 960 960";

/**
 * check if user have access to the given url
 * @param {String} url 
 * @param {Set} allowed 
 * @returns 
 */
const userHasAccess = (url, allowed) => (allowed && url) ? allowed.has(url) : false;

const icons = Object.freeze({
  account: {
    d: "M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z",
    viewBox: ICON_VIEWBOX,
  },
  accountSettings: {
    d: "M400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-8 18-13.5 37.5T404-360h-4q-71 0-127.5 18T180-306q-9 5-14.5 14t-5.5 20v32h252q6 21 16 41.5t22 38.5H80Zm560 40-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 11t21.5 15l58-20 40 70-46 40q2 12 2 25t-2 25l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80Zm40-120q33 0 56.5-23.5T760-320q0-33-23.5-56.5T680-400q-33 0-56.5 23.5T600-320q0 33 23.5 56.5T680-240ZM400-560q33 0 56.5-23.5T480-640q0-33-23.5-56.5T400-720q-33 0-56.5 23.5T320-640q0 33 23.5 56.5T400-560Zm0-80Zm12 400Z",
    viewBox: ICON_VIEWBOX,
  },
  download: {
    d: "M480-320 280-520l56-58 104 104v-326h80v326l104-104 56 58-200 200ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z",
    viewBox: ICON_VIEWBOX,
  },
  logout: {
    d: "M538-538ZM424-424Zm56 264q51 0 98-15.5t88-44.5q-41-29-88-44.5T480-280q-51 0-98 15.5T294-220q41 29 88 44.5t98 15.5Zm106-328-57-57q5-8 8-17t3-18q0-25-17.5-42.5T480-640q-9 0-18 3t-17 8l-57-57q19-17 42.5-25.5T480-720q58 0 99 41t41 99q0 26-8.5 49.5T586-488Zm228 228-58-58q22-37 33-78t11-84q0-134-93-227t-227-93q-43 0-84 11t-78 33l-58-58q49-32 105-49t115-17q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 59-17 115t-49 105ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-59 16.5-115T145-701L27-820l57-57L876-85l-57 57-615-614q-22 37-33 78t-11 84q0 57 19 109t55 95q54-41 116.5-62.5T480-360q38 0 76 8t74 22l133 133q-57 57-130 87T480-80Z",
    viewBox: ICON_VIEWBOX,
  },
});

const menuOptions = Object.freeze([
  {
    name: "me",
    icon: icons.accountSettings,
    url: ME_URL,
    action: link,
  },
  {
    name: "download",
    icon: icons.download,
    url: DOWNLOAD_URL,
    action: link,
    condition: userHasAccess,
  },
  {
    name: "logout",
    icon: icons.logout,
    url: LOGOUT_URL,
    action: link,
  },
]);

function link(href) {
  window.location = href;
}

function svg({ d, viewBox }) {
  const w3 = "http://www.w3.org/2000/svg";
  const path = document.createElementNS(w3, "path");
  path.setAttribute("d", d);
  path.setAttribute("fill", "currentColor");
  const svg = document.createElementNS(w3, "svg");
  svg.appendChild(path);
  svg.setAttribute("viewBox", viewBox);
  return svg;
}

function styleSheet(src) {
  const link = document.createElement("link");
  link.setAttribute("rel", "stylesheet");
  link.setAttribute("href", src);
  return link;
}

function createMenu(email, name, picture, allowed) {
  // left side of header
  const av = document.createElement("div");
  av.classList.add("avatar-wrapper");
  av.textContent = "?";
  if (picture) {
    const img = document.createElement("img");
    img.src = picture;
    img.onload = _ => {
      av.textContent = '';
      av.append(img);
    };
  }
  const hLeft = document.createElement("div");
  hLeft.append(av);

  // right side
  const nameEl = document.createElement("div");
  nameEl.textContent = name || "Unknown";
  nameEl.classList.add("name");
  const emailEl = document.createElement("div");
  emailEl.textContent = email;
  emailEl.classList.add("email");
  const hRight = document.createElement("div");
  hRight.classList.add("flex-col");
  hRight.append(emailEl, nameEl);

  const header = document.createElement("header");
  header.append(hLeft, hRight);

  // menu body
  const nav = document.createElement("nav");
  nav.setAttribute("role", "menu");

  const menu = document.createElement("ul");
  for (const menuOption of menuOptions) {
    if (
      menuOption.condition && 
      !menuOption.condition(menuOption.url, allowed)
    ) continue;
    const icon = svg(menuOption.icon);
    const text = document.createElement("span");
    text.textContent = menuOption.name;
    const li = document.createElement("li");
    li.setAttribute("role", "menuitem");
    li.addEventListener("click", _ => menuOption.action(menuOption.url));
    li.append(icon, text);
    menu.append(li);
  }

  nav.append(header, menu);
  return nav;
}

function showMenu(menu, button, shadowRoot) {
  button.setAttribute('disabled', true);
  button.setAttribute('aria-expanded', 'true');
  const backdrop = document.createElement('div');
  backdrop.classList.add('backdrop');
  backdrop.addEventListener( "click", _ => closeMenu(menu, shadowRoot), true);
  shadowRoot.append(backdrop);
  requestAnimationFrame(_ => {
    backdrop.classList.add('backdrop-shown');
    menu.classList.add("menu-open");
  });
}

function menuTransitionEnd(ev) {
  const menu = ev.target;
  const shadowRoot = menu.parentNode;
  const backdrop = shadowRoot.querySelector('.backdrop');
  const button = shadowRoot.querySelector('.small-button');
  menu.removeEventListener('transitionend', menuTransitionEnd, true);
  button.removeAttribute('disabled');
  requestAnimationFrame(_ => {
    backdrop.remove();
  });
}

function closeMenu(menu, shadowRoot) {
  menu.addEventListener('transitionend', menuTransitionEnd, true);
  const backdrop = shadowRoot.querySelector('.backdrop');
  backdrop.classList.remove('backdrop-shown');
  menu.classList.remove("menu-open");
  const button = shadowRoot.querySelector('.small-button');
  button.setAttribute('aria-expanded', 'false');
}

class UserMenu extends HTMLElement {
  static get observedAttributes() {
    return ["picture", "name", "email", "grantsaccess"];
  }

  constructor() {
    super();
  }

  connectedCallback() {
    const shadow = this.attachShadow({ mode: "open" });
    const menu_css = styleSheet(
      `${HOST}/public/customelement/user-menu.css`
    );
    menu_css.onload = _ => {
      const button = document.createElement("button");
      button.classList.add("small-button");
      button.append(svg(icons.account));
      button.setAttribute("aria-haspopup", "true");
      button.setAttribute("aria-expanded", "false");
      button.setAttribute("aria-label", "User menu");
      const menu = createMenu(this.email, this.name, this.picture, this.grantsaccess);
      button.addEventListener("click", _ => showMenu(menu, button, shadow), true);
      shadow.append(button, menu);
    };
    shadow.appendChild(menu_css);
  }

  attributeChangedCallback(name, oldVal, newVal) {
    if (!newVal || newVal === "None") return;
    if (name === "grantsaccess") {
      try {
        const parsed = JSON.parse(newVal);
        this.grantsaccess = new Set(parsed.filter(
          (url) => url !== HOST
        ));
      } catch(e) {
        console.error(e);
        this.grantsaccess = [];
      }
    } else {
      this[name] = newVal;
    }
  }
}

customElements.define("user-menu", UserMenu);
