const config = window.CLASSILIVRE_CONFIG || {
  appUrl: 'https://classilivreautos.com',
  buildTime: '-',
};

const appUrlEl = document.getElementById('appUrl');
const buildTimeEl = document.getElementById('buildTime');
const openSiteButton = document.getElementById('openSiteButton');
const statusEl = document.getElementById('logs');

if (appUrlEl) appUrlEl.textContent = config.appUrl;
if (buildTimeEl) buildTimeEl.textContent = config.buildTime;

function setStatus(message) {
  if (!statusEl) return;
  statusEl.innerHTML = '';
  const line = document.createElement('div');
  line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
  statusEl.appendChild(line);
}

function openPortalNow() {
  if (!config.appUrl) return;
  const sep = config.appUrl.includes('?') ? '&' : '?';
  const target = `${config.appUrl}${sep}source=android_app`;
  window.location.replace(target);
}

openSiteButton?.addEventListener('click', openPortalNow);

setStatus('Abrindo portal automaticamente...');
setTimeout(openPortalNow, 250);
