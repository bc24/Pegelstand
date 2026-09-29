/* Admin-Bereich — Frank Panzer (Vanilla JS + Quill) */
(() => {
  'use strict';
  const d = document;
  const $ = (s, c = d) => c.querySelector(s);
  const $$ = (s, c = d) => Array.from(c.querySelectorAll(s));
  let cfg = {};
  try { cfg = JSON.parse($('#fp-admin-cfg').textContent); } catch (e) { /* ignore */ }
  const store = { get: (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } } };

  /* ------------------------------------------------------------ Helfer */
  function toast(msg, type = 'ok') {
    const box = $('#toasts');
    if (!box) return;
    const t = d.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }
  const post = async (url, data) => {
    const fd = data instanceof FormData ? data : new URLSearchParams(data);
    if (fd instanceof FormData) fd.set('_csrf', cfg.csrf); else fd.set('_csrf', cfg.csrf);
    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' }, credentials: 'same-origin' });
    let json = null;
    try { json = await res.json(); } catch (e) { /* ignore */ }
    return { ok: res.ok && json && json.ok !== false, json, status: res.status };
  };
  const svgIcon = (name, cls = 'i') => {
    const ns = 'http://www.w3.org/2000/svg';
    const s = d.createElementNS(ns, 'svg');
    s.setAttribute('class', cls + (name.startsWith('brand-') ? ' i-brand' : ''));
    s.setAttribute('aria-hidden', 'true');
    const u = d.createElementNS(ns, 'use');
    u.setAttribute('href', `${cfg.sprite.split('#')[0]}#${name}`);
    s.appendChild(u);
    return s;
  };
  const slugify = (t) => t.toLowerCase().replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
    .normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  const mediaUrl = (p) => (!p ? '' : /^(https?:)?\/\//.test(p) ? p : `${cfg.base}/${p.replace(/^\//, '')}`);

  /* ------------------------------------------------------------ Layout: Theme, Sidebar, Flash */
  $('#admin-theme')?.addEventListener('click', () => {
    const next = d.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    d.documentElement.setAttribute('data-theme', next);
    store.set('fp-admin-theme', next);
  });
  const sideToggle = (open) => d.body.classList.toggle('side-open', open);
  $('#side-toggle')?.addEventListener('click', () => sideToggle(!d.body.classList.contains('side-open')));
  $('#scrim')?.addEventListener('click', () => sideToggle(false));
  $$('.flash-x').forEach((b) => b.addEventListener('click', () => b.closest('.flash').remove()));
  setTimeout(() => $$('.flashes .flash-ok').forEach((f) => { f.style.transition = 'opacity .5s'; f.style.opacity = '0'; setTimeout(() => f.remove(), 500); }), 6000);
  $$('[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));
  $$('[data-color-picker]').forEach((c) => {
    const t = c.parentElement.querySelector('input:not([type=color])');
    c.addEventListener('input', () => { t.value = c.value.toUpperCase(); t.dispatchEvent(new Event('input', { bubbles: true })); });
    t.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(t.value)) c.value = t.value; });
  });

  /* ------------------------------------------------------------ Modal-Helfer */
  function modal(html, { large = false, xl = false } = {}) {
    const wrap = d.createElement('div');
    wrap.className = 'modal';
    wrap.innerHTML = `<div class="modal-box ${xl ? 'modal-xl' : large ? 'modal-lg' : ''}" role="dialog" aria-modal="true"><button class="modal-x icon-btn" type="button" data-close aria-label="Schließen">×</button>${html}</div>`;
    d.body.appendChild(wrap);
    const close = () => { wrap.remove(); d.removeEventListener('keydown', esc); };
    const esc = (e) => { if (e.key === 'Escape') close(); };
    d.addEventListener('keydown', esc);
    wrap.addEventListener('click', (e) => { if (e.target === wrap || e.target.closest('[data-close]')) close(); });
    return { el: wrap, close };
  }
  function confirmDialog(message) {
    return new Promise((resolve) => {
      const m = modal(`<h3>Bitte bestätigen</h3><p></p><div class="modal-actions"><button class="btn btn-ghost" data-close type="button">Abbrechen</button><button class="btn btn-danger" data-ok type="button">Bestätigen</button></div>`);
      $('p', m.el).textContent = message;
      let done = false;
      $('[data-ok]', m.el).addEventListener('click', () => { done = true; m.close(); resolve(true); });
      m.el.addEventListener('click', () => { if (!done) setTimeout(() => resolve(false), 0); });
      $('[data-ok]', m.el).focus();
    });
  }
  d.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (!form || form.dataset.confirmed) return;
    e.preventDefault();
    const submitter = e.submitter;
    if (await confirmDialog(form.dataset.confirm)) {
      form.dataset.confirmed = '1';
      form.requestSubmit ? form.requestSubmit(submitter) : form.submit();
    }
  }, true);

  /* ------------------------------------------------------------ Tabellen: Suche, Schalter, Sortierung */
  $$('[data-table-search]').forEach((inp) => {
    const rows = $$('tbody tr', inp.closest('.card'));
    inp.addEventListener('input', () => {
      const q = inp.value.trim().toLowerCase();
      rows.forEach((r) => { r.hidden = q !== '' && !r.textContent.toLowerCase().includes(q); });
    });
  });
  $$('input[data-toggle]').forEach((cb) => cb.addEventListener('change', async () => {
    const entity = cb.closest('table').dataset.entity;
    const r = await post(`${cfg.admin}?p=${entity}`, { do: 'toggle', id: cb.dataset.id, field: cb.dataset.toggle });
    if (!r.ok) { cb.checked = !cb.checked; toast('Speichern fehlgeschlagen', 'err'); } else toast('Gespeichert');
  }));
  $$('table[data-sortable]').forEach((table) => {
    const tbody = $('tbody', table);
    const entity = table.dataset.entity;
    let dragRow = null;
    const save = async () => {
      const ids = $$('tr[data-id]', tbody).map((r) => r.dataset.id);
      const fd = new URLSearchParams();
      fd.set('do', 'reorder');
      ids.forEach((i) => fd.append('ids[]', i));
      const r = await post(`${cfg.admin}?p=${entity}`, fd);
      toast(r.ok ? 'Reihenfolge gespeichert' : 'Speichern fehlgeschlagen', r.ok ? 'ok' : 'err');
    };
    $$('.grip', tbody).forEach((grip) => {
      const row = grip.closest('tr');
      grip.setAttribute('tabindex', '0');
      grip.setAttribute('role', 'button');
      grip.setAttribute('aria-label', 'Sortieren: Pfeiltasten hoch/runter');
      grip.addEventListener('dragstart', (e) => { dragRow = row; row.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', row.dataset.id); e.dataTransfer.setDragImage(row, 24, 20); });
      grip.addEventListener('dragend', () => { row.classList.remove('dragging'); $$('.drop-before,.drop-after', tbody).forEach((r) => r.classList.remove('drop-before', 'drop-after')); dragRow = null; });
      grip.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault();
        const other = e.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
        if (!other) return;
        e.key === 'ArrowUp' ? tbody.insertBefore(row, other) : tbody.insertBefore(other, row);
        grip.focus();
        clearTimeout(save.t); save.t = setTimeout(save, 500);
      });
    });
    tbody.addEventListener('dragover', (e) => {
      if (!dragRow) return;
      e.preventDefault();
      const over = e.target.closest('tr[data-id]');
      $$('.drop-before,.drop-after', tbody).forEach((r) => r.classList.remove('drop-before', 'drop-after'));
      if (over && over !== dragRow) {
        const r = over.getBoundingClientRect();
        over.classList.add(e.clientY < r.top + r.height / 2 ? 'drop-before' : 'drop-after');
      }
    });
    tbody.addEventListener('drop', (e) => {
      if (!dragRow) return;
      e.preventDefault();
      const over = e.target.closest('tr[data-id]');
      if (over && over !== dragRow) {
        const r = over.getBoundingClientRect();
        tbody.insertBefore(dragRow, e.clientY < r.top + r.height / 2 ? over : over.nextSibling);
        save();
      }
    });
  });

  /* ------------------------------------------------------------ Formulare: Sprache, Slug, ungespeichert, Strg+S */
  $$('[data-editor]').forEach((form) => {
    const card = $('.form-card', form);
    const tabs = $$('[data-lang-tab]', form);
    let dirty = false;
    let submitting = false;
    const setLang = (lg) => {
      card.dataset.lang = lg;
      tabs.forEach((t) => t.classList.toggle('is-active', t.dataset.langTab === lg));
      store.set('fp-admin-lang', lg);
    };
    tabs.forEach((t) => t.addEventListener('click', () => setLang(t.dataset.langTab)));
    if (tabs.length && store.get('fp-admin-lang') === 'en') setLang('en');
    // Hinweis, wenn die englische Fassung leer ist
    if (tabs.length) {
      const en = $$('.lang-en textarea, .lang-en input[type=text]', form);
      const filled = en.some((i) => i.value.trim() !== '');
      const enTab = tabs.find((t) => t.dataset.langTab === 'en');
      if (enTab && !filled) { const s = d.createElement('small'); s.textContent = ' · leer'; s.style.opacity = '.7'; enTab.appendChild(s); }
    }
    // Slug automatisch aus Titel
    $$('[data-slug-from]', form).forEach((slug) => {
      const src = $(`#f-${slug.dataset.slugFrom}`, form);
      if (!src) return;
      let auto = slug.value === '';
      slug.addEventListener('input', () => { auto = slug.value === ''; });
      src.addEventListener('input', () => { if (auto) slug.value = slugify(src.value); });
    });
    const note = $('.dirty-note', form);
    const markDirty = () => { dirty = true; if (note) note.hidden = false; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => { submitting = true; });
    addEventListener('beforeunload', (e) => { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });
    d.addEventListener('keydown', (e) => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); } });
    form._markDirty = markDirty;
  });

  /* ------------------------------------------------------------ Rich-Text (Quill) */
  const editors = [];
  $$('.rte').forEach((box) => {
    if (typeof Quill === 'undefined') return;
    const src = $('.rte-src', box);
    const el = $('.rte-editor', box);
    const full = box.dataset.toolbar === 'full';
    const toolbar = full
      ? [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline', 'strike'], ['link', 'blockquote', 'code-block'], [{ list: 'ordered' }, { list: 'bullet' }], [{ align: [] }], ['image'], ['clean']]
      : [['bold', 'italic', 'link'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']];
    const q = new Quill(el, {
      theme: 'snow',
      modules: { toolbar: { container: toolbar, handlers: { image() { pickMedia((path) => { const r = q.getSelection(true); q.insertEmbed(r.index, 'image', mediaUrl(path), 'user'); }); } } } },
    });
    q.setContents(q.clipboard.convert({ html: src.value }), 'silent');
    q.on('text-change', (delta, old, source) => { if (source === 'user') box.closest('form')?._markDirty?.(); });
    editors.push({ q, src });
  });
  $$('form[data-editor]').forEach((form) => form.addEventListener('submit', () => {
    editors.forEach(({ q, src }) => { if (form.contains(src)) src.value = q.getText().trim() === '' && !q.root.querySelector('img') ? '' : q.getSemanticHTML(); });
  }));

  /* ------------------------------------------------------------ Icon-Auswahl */
  $$('[data-icon-field]').forEach((field) => {
    const input = $('input', field);
    const preview = $('.icon-preview', field);
    const render = () => {
      const v = input.value.trim();
      preview.textContent = '';
      if (cfg.icons.includes(v)) preview.appendChild(svgIcon(v));
      else if (v) preview.textContent = v;
      else preview.appendChild(svgIcon('sparkles'));
    };
    input.addEventListener('input', render);
    $('[data-icon-pick]', field).addEventListener('click', () => {
      const m = modal(`<div class="picker-head"><h3>Icon wählen</h3><label class="search"><input type="search" placeholder="Suchen …" aria-label="Icon suchen"></label></div><div class="icon-grid"></div><p class="help">Tipp: Du kannst im Feld auch ein Emoji eintragen.</p>`, { large: true });
      const grid = $('.icon-grid', m.el);
      const list = (q = '') => {
        grid.textContent = '';
        cfg.icons.filter((n) => n.includes(q.toLowerCase())).forEach((n) => {
          const b = d.createElement('button');
          b.type = 'button'; b.className = 'icon-cell'; b.title = n;
          b.appendChild(svgIcon(n));
          const s = d.createElement('span'); s.textContent = n.replace('brand-', '');
          b.appendChild(s);
          b.addEventListener('click', () => { input.value = n; input.dispatchEvent(new Event('input', { bubbles: true })); m.close(); });
          grid.appendChild(b);
        });
      };
      list();
      const s = $('input[type=search]', m.el);
      s.addEventListener('input', () => list(s.value));
      s.focus();
    });
  });

  /* ------------------------------------------------------------ Medien-Auswahl + Upload */
  async function uploadFiles(files, onProgress) {
    const fd = new FormData();
    fd.set('do', 'upload');
    fd.set('_csrf', cfg.csrf);
    Array.from(files).forEach((f) => fd.append('files[]', f));
    return new Promise((resolve) => {
      const x = new XMLHttpRequest();
      x.open('POST', `${cfg.admin}?p=media`);
      x.setRequestHeader('X-Requested-With', 'fetch');
      x.upload.onprogress = (e) => { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
      x.onload = () => { try { resolve(JSON.parse(x.responseText)); } catch (e) { resolve({ ok: false, results: [{ ok: false, error: 'Serverfehler beim Upload (Dateigröße?).', name: '' }] }); } };
      x.onerror = () => resolve({ ok: false, results: [{ ok: false, error: 'Netzwerkfehler.', name: '' }] });
      x.send(fd);
    });
  }
  function pickMedia(onSelect, { imagesOnly = true } = {}) {
    const m = modal(`<div class="picker-head"><h3>Aus Medien wählen</h3><label class="search"><input type="search" placeholder="Suchen …" aria-label="Medien suchen"></label>
      <label class="btn btn-primary">Hochladen<input type="file" accept="image/jpeg,image/png,image/webp,image/gif${imagesOnly ? '' : ',application/pdf'}" multiple hidden></label></div>
      <div class="picker-grid" id="picker-grid"></div><div class="center" style="margin-top:1rem"><button class="btn btn-soft" type="button" data-more hidden>Mehr laden</button></div>`, { xl: true });
    const grid = $('#picker-grid', m.el);
    const more = $('[data-more]', m.el);
    let page = 1, q = '', pages = 1;
    const load = async (reset) => {
      if (reset) { page = 1; grid.textContent = ''; }
      const res = await fetch(`${cfg.admin}?p=media&a=json&page=${page}&q=${encodeURIComponent(q)}`, { credentials: 'same-origin' });
      const json = await res.json();
      pages = json.pages;
      json.items.filter((i) => !imagesOnly || i.mime.startsWith('image/')).forEach((i) => {
        const b = d.createElement('button');
        b.type = 'button'; b.className = 'media-item';
        b.innerHTML = `<img alt="" loading="lazy"><span class="media-name"></span>`;
        $('img', b).src = i.thumb;
        $('.media-name', b).textContent = i.name;
        b.addEventListener('click', () => { onSelect(i.path, i); m.close(); });
        grid.appendChild(b);
      });
      if (!grid.children.length) grid.innerHTML = '<p class="muted">Noch keine Bilder — lade eines hoch.</p>';
      more.hidden = page >= pages;
    };
    more.addEventListener('click', () => { page++; load(false); });
    const search = $('input[type=search]', m.el);
    let t;
    search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { q = search.value; load(true); }, 250); });
    $('input[type=file]', m.el).addEventListener('change', async (e) => {
      const r = await uploadFiles(e.target.files);
      const ok = (r.results || []).filter((x) => x.ok);
      (r.results || []).filter((x) => !x.ok).forEach((x) => toast(`${x.name}: ${x.error}`, 'err'));
      if (ok.length) { toast(`${ok.length} Datei(en) hochgeladen`); if (ok.length === 1 && imagesOnly) { onSelect(ok[0].path, ok[0]); m.close(); return; } load(true); }
    });
    load(true);
  }
  $$('[data-image-field]').forEach((field) => {
    const input = $('input', field);
    const preview = $('.image-preview', field);
    const render = () => {
      preview.textContent = '';
      if (input.value.trim()) { const img = d.createElement('img'); img.alt = ''; img.src = mediaUrl(input.value.trim()); preview.appendChild(img); preview.classList.add('has-img'); }
      else { preview.classList.remove('has-img'); preview.appendChild(svgIcon('image')); }
    };
    input.addEventListener('input', render);
    $('[data-image-pick]', field).addEventListener('click', () => pickMedia((path) => { input.value = path; input.dispatchEvent(new Event('input', { bubbles: true })); }));
    $('[data-image-clear]', field).addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('input', { bubbles: true })); });
  });

  /* ------------------------------------------------------------ Medien-Seite */
  const dz = $('[data-dropzone]');
  if (dz) {
    const bar = $('.upload-progress', dz);
    const run = async (files) => {
      if (!files.length) return;
      bar.hidden = false; bar.innerHTML = '<i></i>';
      const r = await uploadFiles(files, (p) => { $('i', bar).style.width = `${Math.round(p * 100)}%`; });
      (r.results || []).filter((x) => !x.ok).forEach((x) => toast(`${x.name}: ${x.error}`, 'err'));
      const n = (r.results || []).filter((x) => x.ok).length;
      if (n) { toast(`${n} Datei(en) hochgeladen`); setTimeout(() => location.reload(), 600); } else bar.hidden = true;
    };
    $('input[type=file]', dz).addEventListener('change', (e) => run(e.target.files));
    ['dragenter', 'dragover'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.remove('is-over'); }));
    dz.addEventListener('drop', (e) => run(e.dataTransfer.files));
  }
  const detail = $('#media-detail');
  if (detail) {
    let cur = null, curBtn = null;
    const fill = () => {
      $('.md-name', detail).textContent = cur.name;
      $('.md-meta', detail).textContent = `${cur.size}${cur.w ? ` · ${cur.w}×${cur.h} px` : ''} · ${cur.mime}`;
      $('.md-url', detail).value = `${location.origin}${mediaUrl(cur.path)}`;
      $('#md-alt', detail).value = cur.alt || '';
      const pv = $('.md-preview', detail);
      pv.textContent = '';
      if (cur.img) { const i = d.createElement('img'); i.src = cur.url; i.alt = ''; pv.appendChild(i); } else pv.appendChild(svgIcon('file-text', 'i'));
    };
    $$('.media-item[data-media]').forEach((b) => b.addEventListener('click', () => { cur = JSON.parse(b.dataset.media); curBtn = b; fill(); detail.hidden = false; }));
    detail.addEventListener('click', (e) => { if (e.target === detail || e.target.closest('[data-close]')) detail.hidden = true; });
    d.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !detail.hidden) detail.hidden = true; });
    $('[data-copy]', detail).addEventListener('click', async () => { try { await navigator.clipboard.writeText($('.md-url', detail).value); toast('Adresse kopiert'); } catch (e) { $('.md-url', detail).select(); } });
    $('[data-save-alt]', detail).addEventListener('click', async () => {
      const r = await post(`${cfg.admin}?p=media`, { do: 'alt', id: cur.id, alt: $('#md-alt', detail).value });
      if (r.ok) { cur.alt = $('#md-alt', detail).value; curBtn.dataset.media = JSON.stringify(cur); toast('Gespeichert'); } else toast('Fehler beim Speichern', 'err');
    });
    $('[data-delete-media]', detail).addEventListener('click', async () => {
      detail.hidden = true;
      if (!(await confirmDialog('Datei endgültig löschen? Wird sie noch irgendwo verwendet, fehlt dort das Bild.'))) return;
      const r = await post(`${cfg.admin}?p=media`, { do: 'delete', id: cur.id });
      if (r.ok) { curBtn.remove(); toast('Datei gelöscht'); } else toast('Fehler beim Löschen', 'err');
    });
  }

  /* ------------------------------------------------------------ 2FA: QR-Code */
  const qr = $('#qr');
  if (qr && typeof qrcode === 'function') {
    try {
      const code = qrcode(0, 'M');
      code.addData(qr.dataset.uri);
      code.make();
      qr.innerHTML = code.createSvgTag({ scalable: true, margin: 0 });
    } catch (e) { qr.textContent = 'QR-Code nicht verfügbar — bitte den Schlüssel manuell eintragen.'; }
  }
})();
