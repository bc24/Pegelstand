// Pegelstand Tracking-Script. Keine Cookies, kein Speicher, nur ein Request pro Aufruf.
(function (w, d, l) {
  var s = d.currentScript;
  var site = s && s.getAttribute('data-site');
  var api = s && (s.getAttribute('data-api') || s.src.replace(/[^/]*$/, '') + 'api/event');
  var h = s && s.hasAttribute('data-hash');
  var prev;
  var ref = d.referrer;

  function ignored() {
    try {
      if (w.localStorage.pegelstand_ignore === 'true') return 1;
    } catch (e) {}
    if (!s.hasAttribute('data-local') && /^(localhost|127(\.\d+){3}|\[::1\]|)$/.test(l.hostname)) return 1;
    return w.navigator.webdriver || /Headless/.test(w.navigator.userAgent) || w.phantom || w._phantom;
  }

  function send(n, p) {
    if (!site || ignored()) return;
    var u = l.protocol + '//' + l.host + l.pathname + l.search + (h ? l.hash : '');
    var body = JSON.stringify({ s: site, u: u, r: n ? '' : ref, n: n, p: p });
    if (!n) ref = '';
    w.fetch(api, { method: 'POST', body: body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'text/plain' } }).catch(function () {});
  }

  function view() {
    var u = l.pathname + l.search + (h ? l.hash : '');
    if (u === prev) return;
    if (prev !== undefined) ref = l.origin + prev;
    prev = u;
    send();
  }

  w.pegelstand = function (n, o) {
    send(n, o && o.props);
  };

  var push = w.history.pushState;
  w.history.pushState = function () {
    push.apply(this, arguments);
    setTimeout(view);
  };
  w.addEventListener('popstate', view);
  if (h) w.addEventListener('hashchange', view);

  if (s.hasAttribute('data-outbound')) {
    d.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a) return;
      if (a.host && a.host !== l.host) w.pegelstand('Outbound Link', { props: { url: a.href } });
      else if (/\.(pdf|zip|docx?|xlsx?|pptx?|csv|mp3|mp4|dmg|exe)$/i.test(a.pathname)) w.pegelstand('File Download', { props: { url: a.pathname } });
    });
  }
  if (s.hasAttribute('data-404') && /404|not found|nicht gefunden/i.test(d.title)) {
    w.pegelstand('404', { props: { path: l.pathname } });
  } else if (d.visibilityState === 'prerender') {
    d.addEventListener('visibilitychange', view, { once: true });
  } else {
    view();
  }
})(window, document, location);
