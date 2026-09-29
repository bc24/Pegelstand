/* Frank Panzer — Frontend-Skript (Vanilla JS, keine Abhängigkeiten) */
(() => {
  'use strict';

  const d = document;
  const root = d.documentElement;
  const body = d.body;
  const $ = (s, c = d) => c.querySelector(s);
  const $$ = (s, c = d) => Array.from(c.querySelectorAll(s));
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const fx = (body.dataset.fx || '').split(' ');
  const has = (f) => fx.includes(f);
  const lang = body.dataset.lang || 'de';
  let i18n = {};
  try { i18n = JSON.parse($('#fp-i18n')?.textContent || '{}'); } catch (e) { /* ignore */ }
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } },
  };
  const safe = (fn) => { try { fn(); } catch (e) { if (window.console) console.warn('[fp]', e); } };

  /* ------------------------------------------------------------ Toast */
  function toast(msg) {
    const t = d.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    t.setAttribute('role', 'status');
    body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('is-in'));
    setTimeout(() => { t.classList.remove('is-in'); setTimeout(() => t.remove(), 500); }, 2400);
  }

  /* ------------------------------------------------------------ Theme */
  safe(() => {
    $('#theme-toggle')?.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      root.setAttribute('data-theme', next);
      store.set('fp-theme', next);
      $('meta[name="theme-color"]')?.setAttribute('content', next === 'light' ? '#f5f6f9' : '#090c11');
    });
  });

  /* ------------------------------------------------------------ Text splitten */
  safe(() => {
    $$('[data-split-words]').forEach((el) => {
      const text = el.textContent.trim();
      el.setAttribute('aria-label', text);
      el.textContent = '';
      text.split(/\s+/).forEach((w, i) => {
        const outer = d.createElement('span');
        outer.className = 'w';
        outer.setAttribute('aria-hidden', 'true');
        const inner = d.createElement('span');
        inner.textContent = w;
        outer.style.setProperty('--wi', i);
        outer.appendChild(inner);
        el.appendChild(outer);
        el.appendChild(d.createTextNode(' '));
      });
    });
    $$('[data-split-chars]').forEach((el) => {
      const text = el.textContent.trim();
      el.textContent = '';
      let i = 0;
      text.split(/\s+/).forEach((word, wi, all) => {
        const wd = d.createElement('span');
        wd.className = 'wd';
        wd.setAttribute('aria-hidden', 'true');
        Array.from(word).forEach((c) => {
          const s = d.createElement('span');
          s.className = 'ch';
          s.textContent = c;
          s.style.setProperty('--ci', i++);
          wd.appendChild(s);
        });
        el.appendChild(wd);
        if (wi < all.length - 1) el.appendChild(d.createTextNode(' '));
      });
    });
    $$('[data-words-scroll]').forEach((el) => {
      const words = el.textContent.trim().split(/\s+/);
      el.setAttribute('aria-label', words.join(' '));
      el.textContent = '';
      words.forEach((w) => {
        const s = d.createElement('span');
        s.className = 'sw';
        s.setAttribute('aria-hidden', 'true');
        s.textContent = w;
        el.appendChild(s);
        el.appendChild(d.createTextNode(' '));
      });
    });
    // Staffelung: Kinder eines [data-stagger]-Containers verzögert einblenden
    $$('[data-stagger]').forEach((wrap) => {
      $$(':scope > [data-reveal]', wrap).forEach((c, i) => c.style.setProperty('--rd', `${Math.min(i, 9) * 80}ms`));
    });
    $$('[data-hero-step]').forEach((el, i) => el.style.setProperty('--hs', i));
  });

  /* ------------------------------------------------------------ Einblenden beim Scrollen */
  safe(() => {
    const targets = $$('[data-reveal], [data-split-words]');
    if (!('IntersectionObserver' in window) || reduced) {
      targets.forEach((t) => t.classList.add('in'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    targets.forEach((t) => io.observe(t));
  });

  /* ------------------------------------------------------------ Hero-Start (nach Preloader) */
  const hero = $('.hero');
  let heroStarted = false;
  function startHero() {
    if (heroStarted) return;
    heroStarted = true;
    hero?.classList.add('is-ready');
    $('[data-split-chars]')?.classList.add('in');
    safe(startTypewriter);
  }
  safe(() => {
    const pre = $('#preloader');
    if (!pre || (() => { try { return sessionStorage.getItem('fp-pre'); } catch (e) { return null; } })() || reduced) {
      pre?.remove();
      requestAnimationFrame(() => setTimeout(startHero, 60));
      return;
    }
    body.style.overflow = 'hidden';
    const minWait = new Promise((r) => setTimeout(r, 1500));
    const loaded = new Promise((r) => (d.readyState === 'complete' ? r() : addEventListener('load', r, { once: true })));
    Promise.race([Promise.all([minWait, loaded]), new Promise((r) => setTimeout(r, 4000))]).then(() => {
      pre.classList.add('is-done');
      try { sessionStorage.setItem('fp-pre', '1'); } catch (e) { /* ignore */ }
      body.style.overflow = '';
      setTimeout(startHero, 450);
      setTimeout(() => pre.remove(), 1200);
    });
  });
  // Notfall: falls der Preloader oder das Skript hängen
  setTimeout(() => { if (!heroStarted) startHero(); }, 6000);

  /* ------------------------------------------------------------ Typewriter */
  function startTypewriter() {
    const el = $('#typewriter');
    if (!el) return;
    let words = [];
    try { words = JSON.parse(el.dataset.words || '[]'); } catch (e) { /* ignore */ }
    if (!words.length || reduced) return;
    let wi = 0, ci = words[0].length, del = true;
    el.textContent = words[0];
    const tick = () => {
      const w = words[wi % words.length];
      if (!del) {
        ci++;
        el.textContent = w.slice(0, ci);
        if (ci >= w.length) { del = true; return setTimeout(tick, 1900); }
        return setTimeout(tick, 75);
      }
      ci--;
      el.textContent = w.slice(0, Math.max(ci, 0));
      if (ci <= 0) { del = false; wi++; return setTimeout(tick, 380); }
      return setTimeout(tick, 42);
    };
    setTimeout(tick, 1800);
  }

  /* ------------------------------------------------------------ Zahlen hochzählen */
  safe(() => {
    const els = $$('[data-count]');
    if (!els.length) return;
    const fmt = (n, dec) => n.toLocaleString(lang === 'en' ? 'en-US' : 'de-DE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    const run = (el) => {
      const to = parseFloat(el.dataset.count);
      const dec = parseInt(el.dataset.decimals || '0', 10);
      const pre = el.dataset.prefix || '';
      const suf = el.dataset.suffix || '';
      if (reduced || isNaN(to)) return;
      const t0 = performance.now();
      const dur = 1800;
      const step = (t) => {
        const p = clamp((t - t0) / dur, 0, 1);
        const e = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
        el.textContent = pre + fmt(to * e, dec) + suf;
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (e.isIntersecting) { run(e.target); io.unobserve(e.target); }
    }), { threshold: 0.6 });
    els.forEach((el) => {
      const dec = parseInt(el.dataset.decimals || '0', 10);
      el.textContent = (el.dataset.prefix || '') + (0).toLocaleString(lang === 'en' ? 'en-US' : 'de-DE', { minimumFractionDigits: dec }) + (el.dataset.suffix || '');
      io.observe(el);
    });
  });

  /* ------------------------------------------------------------ Header, Fortschritt, Nach-oben */
  const header = $('#header');
  const progress = $('#progress');
  const toTop = $('#to-top');
  let lastY = scrollY;
  let ticking = false;
  const words = $$('.sw');
  const motto = $('[data-words-scroll]');
  const timeline = $('[data-timeline]');
  const heroInner = $('.hero-inner');

  function onScroll() {
    const y = scrollY;
    const vh = innerHeight;
    const max = Math.max(1, d.documentElement.scrollHeight - vh);
    const p = clamp(y / max, 0, 1);
    progress?.style.setProperty('--p', p.toFixed(4));
    toTop?.style.setProperty('--p', p.toFixed(4));
    toTop?.classList.toggle('is-visible', y > 700);
    header?.classList.toggle('is-scrolled', y > 24);
    if (header && !body.classList.contains('menu-open')) {
      const goingDown = y > lastY + 4;
      const goingUp = y < lastY - 4;
      if (goingDown && y > 420 && !header.matches(':focus-within')) header.classList.add('is-hidden');
      else if (goingUp || y < 200) header.classList.remove('is-hidden');
    }
    lastY = y;

    if (timeline) {
      const r = timeline.getBoundingClientRect();
      timeline.style.setProperty('--tl', clamp((vh * 0.62 - r.top) / r.height, 0, 1).toFixed(4));
    }
    if (motto && words.length) {
      const r = motto.getBoundingClientRect();
      const prog = clamp((vh * 0.9 - r.top) / (vh * 0.55 + r.height * 0.4), 0, 1);
      const n = Math.round(prog * words.length);
      words.forEach((w, i) => w.classList.toggle('lit', i < n));
    }
    if (heroInner && !reduced && y < vh * 1.2) {
      heroInner.style.translate = `0 ${(y * 0.14).toFixed(1)}px`;
      heroInner.style.opacity = String(clamp(1 - y / (vh * 0.85), 0, 1).toFixed(3));
    }
    ticking = false;
  }
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  addEventListener('resize', onScroll, { passive: true });
  onScroll();
  toTop?.addEventListener('click', () => scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }));

  /* ------------------------------------------------------------ Mobiles Menü */
  safe(() => {
    const burger = $('#burger');
    const menu = $('#mobile-menu');
    if (!burger || !menu) return;
    const set = (open) => {
      body.classList.toggle('menu-open', open);
      burger.setAttribute('aria-expanded', String(open));
      burger.setAttribute('aria-label', open ? i18n.menuClose : i18n.menuOpen);
      menu.setAttribute('aria-hidden', String(!open));
      if (open) header.classList.remove('is-hidden');
    };
    burger.addEventListener('click', () => set(!body.classList.contains('menu-open')));
    $$('a', menu).forEach((a) => a.addEventListener('click', () => set(false)));
    d.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
    matchMedia('(min-width: 921px)').addEventListener('change', (m) => { if (m.matches) set(false); });
  });

  /* ------------------------------------------------------------ Scrollspy */
  safe(() => {
    const links = $$('[data-spy]');
    const secs = $$('[data-section]').filter((s) => s.id && links.some((l) => l.dataset.spy === s.id));
    if (!secs.length || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (!e.isIntersecting) return;
        links.forEach((l) => l.classList.toggle('is-active', l.dataset.spy === e.target.id));
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    secs.forEach((s) => io.observe(s));
    addEventListener('scroll', () => { if (scrollY < 200) links.forEach((l) => l.classList.remove('is-active')); }, { passive: true });
  });

  /* ------------------------------------------------------------ Partikel-Netz */
  safe(() => {
    const canvas = $('#particles');
    if (!canvas || !has('particles') || reduced) { canvas?.remove(); return; }
    const ctx = canvas.getContext('2d');
    let w = 0, h = 0, dpr = 1, pts = [], running = true;
    const mouse = { x: -9999, y: -9999 };
    const rgb = () => getComputedStyle(root).getPropertyValue('--accent-rgb').trim() || '249,115,22';
    let color = rgb();
    const resize = () => {
      dpr = Math.min(devicePixelRatio || 1, 2);
      const r = canvas.getBoundingClientRect();
      w = r.width; h = r.height;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = clamp(Math.round((w * h) / 15000), 26, 90);
      pts = Array.from({ length: n }, () => ({
        x: Math.random() * w, y: Math.random() * h,
        vx: (Math.random() - 0.5) * 0.35, vy: (Math.random() - 0.5) * 0.35, r: Math.random() * 1.6 + 0.7,
      }));
    };
    const draw = () => {
      if (!running) return;
      ctx.clearRect(0, 0, w, h);
      const light = root.getAttribute('data-theme') === 'light';
      const base = light ? 0.55 : 0.9;
      for (let i = 0; i < pts.length; i++) {
        const p = pts[i];
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > w) p.vx *= -1;
        if (p.y < 0 || p.y > h) p.vy *= -1;
        const dx = p.x - mouse.x, dy = p.y - mouse.y, md = Math.hypot(dx, dy);
        if (md < 130) { p.x += (dx / md) * 1.2; p.y += (dy / md) * 1.2; }
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, 6.283);
        ctx.fillStyle = `rgba(${color},${base * 0.75})`;
        ctx.fill();
        for (let j = i + 1; j < pts.length; j++) {
          const q = pts[j];
          const dist = Math.hypot(p.x - q.x, p.y - q.y);
          if (dist < 125) {
            ctx.strokeStyle = `rgba(${color},${(1 - dist / 125) * 0.28 * base})`;
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
          }
        }
        if (md < 190) {
          ctx.strokeStyle = `rgba(${color},${(1 - md / 190) * 0.55})`;
          ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(mouse.x, mouse.y); ctx.stroke();
        }
      }
      requestAnimationFrame(draw);
    };
    resize();
    addEventListener('resize', () => { resize(); color = rgb(); }, { passive: true });
    hero?.addEventListener('pointermove', (e) => {
      const r = canvas.getBoundingClientRect();
      mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top;
    }, { passive: true });
    hero?.addEventListener('pointerleave', () => { mouse.x = mouse.y = -9999; });
    if ('IntersectionObserver' in window && hero) {
      new IntersectionObserver((en) => {
        const vis = en[0].isIntersecting;
        if (vis && !running) { running = true; requestAnimationFrame(draw); } else if (!vis) running = false;
      }).observe(hero);
    }
    d.addEventListener('visibilitychange', () => {
      if (d.hidden) running = false; else if (!running) { running = true; requestAnimationFrame(draw); }
    });
    requestAnimationFrame(draw);
  });

  /* ------------------------------------------------------------ Hero-Parallax per Maus */
  safe(() => {
    if (!hero || !finePointer || reduced) return;
    const chips = $$('.float-chip');
    const blobs = $$('.blob');
    hero.addEventListener('pointermove', (e) => {
      const nx = (e.clientX / innerWidth - 0.5) * 2;
      const ny = (e.clientY / innerHeight - 0.5) * 2;
      chips.forEach((c, i) => {
        const k = 10 + i * 6;
        c.style.setProperty('--px', `${(nx * k).toFixed(1)}px`);
        c.style.setProperty('--py', `${(ny * k).toFixed(1)}px`);
      });
      blobs.forEach((b, i) => { b.style.translate = `${(nx * -22 * (i + 1)).toFixed(1)}px ${(ny * -18 * (i + 1)).toFixed(1)}px`; });
    }, { passive: true });
  });

  /* ------------------------------------------------------------ Tilt, Spotlight, Magnet, Cursor */
  safe(() => {
    if (!finePointer) return;
    if (has('tilt') && !reduced) {
      $$('[data-tilt]').forEach((el) => {
        const max = parseFloat(el.dataset.tiltMax || '6');
        el.addEventListener('pointermove', (e) => {
          const r = el.getBoundingClientRect();
          const x = (e.clientX - r.left) / r.width - 0.5;
          const y = (e.clientY - r.top) / r.height - 0.5;
          el.classList.add('is-tilting');
          el.style.setProperty('--rx', `${(-y * max).toFixed(2)}deg`);
          el.style.setProperty('--ry', `${(x * max).toFixed(2)}deg`);
        });
        el.addEventListener('pointerleave', () => {
          el.classList.remove('is-tilting');
          el.style.setProperty('--rx', '0deg');
          el.style.setProperty('--ry', '0deg');
        });
      });
    }
    if (has('tilt')) {
      $$('.spot').forEach((el) => el.addEventListener('pointermove', (e) => {
        const r = el.getBoundingClientRect();
        el.style.setProperty('--mx', `${e.clientX - r.left}px`);
        el.style.setProperty('--my', `${e.clientY - r.top}px`);
      }, { passive: true }));
    }
    if (!reduced) {
      $$('.magnetic').forEach((el) => {
        el.addEventListener('pointermove', (e) => {
          const r = el.getBoundingClientRect();
          const x = e.clientX - (r.left + r.width / 2);
          const y = e.clientY - (r.top + r.height / 2);
          el.style.translate = `${(x * 0.22).toFixed(1)}px ${(y * 0.3).toFixed(1)}px`;
        });
        el.addEventListener('pointerleave', () => { el.style.translate = ''; });
      });
    }
    const cur = $('#cursor');
    if (cur && has('cursor') && !reduced) {
      const dot = $('.cursor-dot', cur);
      const ring = $('.cursor-ring', cur);
      let x = 0, y = 0, rx = 0, ry = 0;
      addEventListener('pointermove', (e) => {
        x = e.clientX; y = e.clientY;
        cur.classList.add('is-on');
        dot.style.transform = `translate3d(${x}px,${y}px,0)`;
        cur.classList.toggle('is-hover', !!e.target.closest('a,button,[data-tilt],.chip-btn,input,textarea,label,summary'));
      }, { passive: true });
      addEventListener('pointerdown', () => cur.classList.add('is-down'));
      addEventListener('pointerup', () => cur.classList.remove('is-down'));
      d.addEventListener('mouseleave', () => cur.classList.remove('is-on'));
      const loop = () => {
        rx = lerp(rx, x, 0.17); ry = lerp(ry, y, 0.17);
        ring.style.transform = `translate3d(${rx.toFixed(1)}px,${ry.toFixed(1)}px,0)`;
        requestAnimationFrame(loop);
      };
      loop();
    } else {
      cur?.remove();
    }
  });

  /* ------------------------------------------------------------ Projekt-Filter */
  safe(() => {
    $$('.filters').forEach((bar) => {
      const grid = bar.parentElement.querySelector('[data-filter-grid]');
      if (!grid) return;
      $$('.chip-btn', bar).forEach((btn) => btn.addEventListener('click', () => {
        const f = btn.dataset.filter;
        $$('.chip-btn', bar).forEach((b) => { b.classList.toggle('is-active', b === btn); b.setAttribute('aria-selected', String(b === btn)); });
        $$('.pcard', grid).forEach((card) => {
          const show = f === '*' || card.dataset.cat === f;
          if (show) {
            if (card.hidden) { card.hidden = false; card.classList.add('is-out'); requestAnimationFrame(() => requestAnimationFrame(() => card.classList.remove('is-out'))); }
            else card.classList.remove('is-out');
          } else if (!card.hidden) {
            card.classList.add('is-out');
            setTimeout(() => { if (card.classList.contains('is-out')) card.hidden = true; }, 320);
          }
        });
      }));
    });
  });


  /* ------------------------------------------------------------ Songs: Karussell + TikTok-Player (erst nach Klick) */
  safe(() => {
    const root = $('[data-songs]');
    if (!root) return;
    const rail = $('[data-rail]', root);
    const prev = $('[data-rail-prev]', root);
    const next = $('[data-rail-next]', root);
    const step = () => Math.max(220, Math.round(rail.clientWidth * 0.8));
    const update = () => {
      const max = rail.scrollWidth - rail.clientWidth - 2;
      if (prev) prev.disabled = rail.scrollLeft <= 2;
      if (next) next.disabled = rail.scrollLeft >= max;
    };
    prev?.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: reduced ? 'auto' : 'smooth' }));
    next?.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: reduced ? 'auto' : 'smooth' }));
    rail.addEventListener('scroll', update, { passive: true });
    addEventListener('resize', update, { passive: true });
    update();
    // Mit der Maus ziehen (Touch scrollt nativ)
    let down = false, moved = false, sx = 0, sl = 0;
    rail.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; moved = false; sx = e.clientX; sl = rail.scrollLeft;
    });
    addEventListener('pointermove', (e) => {
      if (!down) return;
      const dx = e.clientX - sx;
      if (Math.abs(dx) > 6) { moved = true; rail.classList.add('is-dragging'); }
      if (moved) rail.scrollLeft = sl - dx;
    });
    addEventListener('pointerup', () => { if (!down) return; down = false; rail.classList.remove('is-dragging'); setTimeout(() => { moved = false; }, 0); });
    rail.addEventListener('click', (e) => { if (moved) { e.preventDefault(); e.stopPropagation(); } }, true);
    rail.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { rail.scrollBy({ left: step() / 2, behavior: 'smooth' }); e.preventDefault(); }
      if (e.key === 'ArrowLeft') { rail.scrollBy({ left: -step() / 2, behavior: 'smooth' }); e.preventDefault(); }
    });

    // Player: iframe wird erst beim Klick erzeugt (vorher keine Verbindung zu TikTok)
    const player = $('#player');
    if (!player) return;
    const frame = $('#player-frame', player);
    const title = $('#player-title', player);
    const link = $('#player-link', player);
    let opener = null;
    const close = () => {
      player.hidden = true;
      frame.textContent = '';
      body.classList.remove('player-open');
      d.removeEventListener('keydown', onKey);
      opener?.focus();
    };
    const onKey = (e) => {
      if (e.key === 'Escape') close();
      if (e.key === 'Tab') {
        const f = $$('button,a[href]', player);
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && d.activeElement === first) { last.focus(); e.preventDefault(); }
        else if (!e.shiftKey && d.activeElement === last) { first.focus(); e.preventDefault(); }
      }
    };
    $$('[data-tiktok]', root).forEach((card) => card.addEventListener('click', () => {
      const id = card.dataset.tiktok;
      if (!/^\d{8,25}$/.test(id)) return;
      opener = card;
      frame.textContent = '';
      const f = d.createElement('iframe');
      f.src = `https://www.tiktok.com/player/v1/${id}?autoplay=1&loop=0&rel=0&music_info=1&description=0`;
      f.allow = 'autoplay; fullscreen; encrypted-media; picture-in-picture';
      f.setAttribute('allowfullscreen', '');
      f.title = card.dataset.title || 'TikTok';
      f.referrerPolicy = 'strict-origin-when-cross-origin';
      frame.appendChild(f);
      title.textContent = card.dataset.title || '';
      link.href = card.dataset.url || '#';
      player.hidden = false;
      body.classList.add('player-open');
      d.addEventListener('keydown', onKey);
      $('[data-player-close]', player).focus();
    }));
    player.addEventListener('click', (e) => { if (e.target === player || e.target.closest('[data-player-close]')) close(); });
  });

  /* ------------------------------------------------------------ FAQ */
  safe(() => {
    const items = $$('.faq-item');
    items.forEach((item) => {
      const btn = $('.faq-q', item);
      btn?.addEventListener('click', () => {
        const open = !item.classList.contains('is-open');
        items.forEach((o) => { o.classList.remove('is-open'); $('.faq-q', o)?.setAttribute('aria-expanded', 'false'); });
        if (open) { item.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); }
      });
    });
  });

  /* ------------------------------------------------------------ Formulare */
  async function postForm(form, onOk, onErr, button) {
    const label = button ? $('.btn-label', button) : null;
    const original = label ? label.textContent : '';
    if (button) { button.disabled = true; if (label) label.textContent = i18n.sending; }
    try {
      const res = await fetch(form.action, {
        method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' }, credentials: 'same-origin',
      });
      let data = null;
      try { data = await res.json(); } catch (e) { /* ignore */ }
      if (res.ok && data && data.ok) onOk(data);
      else onErr((data && data.message) || i18n.errGeneric);
    } catch (e) {
      onErr(i18n.errGeneric);
    } finally {
      if (button) { button.disabled = false; if (label) label.textContent = original; }
    }
  }
  safe(() => {
    // Kontaktformular sowie Projekt- und Booking-Anfrage laufen über dasselbe AJAX-Muster
    [['#contact-form', '#form-error', '#form-success'], ['#inquiry-form', '#inq-error', '#inq-success']].forEach(([fs, es, os]) => {
      const form = $(fs);
      if (!form) return;
      const err = $(es);
      const ok = $(os);
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        err.hidden = true;
        $$('.field', form).forEach((f) => f.classList.remove('has-error'));
        let bad = false;
        $$('[required]', form).forEach((inp) => {
          if (!inp.value.trim() || (inp.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(inp.value))) {
            inp.closest('.field')?.classList.add('has-error'); bad = true;
          }
        });
        if (bad) { err.textContent = form.dataset.required || (lang === 'en' ? 'Please fill in all required fields.' : 'Bitte alle Pflichtfelder ausfüllen.'); err.hidden = false; return; }
        postForm(form, () => { form.hidden = true; ok.hidden = false; ok.scrollIntoView({ block: 'center', behavior: reduced ? 'auto' : 'smooth' }); },
          (m) => { err.textContent = m; err.hidden = false; }, $('button[type=submit]', form));
      });
    });
    const form = $('#contact-form');
    if (!form) return;
    // Betreff per Link vorbelegen (Partner-Programm)
    $$('[data-subject]').forEach((a) => a.addEventListener('click', (e) => {
      const subj = $('#c-subject');
      if (!subj) return;
      e.preventDefault();
      subj.value = a.dataset.subject;
      $('#kontakt')?.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
      setTimeout(() => $('#c-name')?.focus({ preventScroll: true }), 700);
    }));
  });
  safe(() => {
    const form = $('#comment-form');
    if (!form) return;
    const err = $('#comment-error');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      err.hidden = true;
      postForm(form, (data) => {
        const p = d.createElement('p');
        p.className = 'notice notice-ok';
        p.setAttribute('role', 'status');
        p.textContent = data.message;
        form.replaceWith(p);
      }, (m) => { err.textContent = m; err.hidden = false; }, $('button[type=submit]', form));
    });
  });

  /* ------------------------------------------------------------ Blog: Like, Link kopieren, Inhaltsverzeichnis */
  safe(() => {
    const btn = $('#like-btn');
    if (btn) {
      const slug = btn.dataset.slug;
      let liked = [];
      try { liked = JSON.parse(store.get('fp-liked') || '[]'); } catch (e) { /* ignore */ }
      const mark = () => { btn.classList.add('is-liked'); btn.setAttribute('aria-pressed', 'true'); };
      if (liked.includes(slug)) mark();
      btn.addEventListener('click', async () => {
        if (btn.classList.contains('is-liked')) return;
        mark();
        try {
          const res = await fetch(btn.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' } });
          const data = await res.json();
          if (data.ok) { $('#like-count').textContent = data.likes; liked.push(slug); store.set('fp-liked', JSON.stringify(liked)); if (!data.already) toast(i18n.liked); }
        } catch (e) { /* ignore */ }
      });
    }
    $('#copy-link')?.addEventListener('click', async (e) => {
      try { await navigator.clipboard.writeText(e.currentTarget.dataset.url); toast(i18n.copied); } catch (err) { /* ignore */ }
    });
    const heads = $$('#post-body h2[id], #post-body h3[id]');
    const tocLinks = $$('.toc a');
    if (heads.length && tocLinks.length && 'IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => entries.forEach((en) => {
        if (en.isIntersecting) tocLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === `#${en.target.id}`));
      }), { rootMargin: '-15% 0px -75% 0px' });
      heads.forEach((h) => io.observe(h));
    }
  });

  /* ------------------------------------------------------------ Hinweis-Banner */
  safe(() => {
    const box = $('#consent');
    if (!box) return;
    if (!store.get('fp-consent')) box.hidden = false;
    $$('[data-consent]', box).forEach((b) => b.addEventListener('click', () => {
      store.set('fp-consent', b.dataset.consent);
      box.style.transition = 'opacity .3s, translate .4s';
      box.style.opacity = '0';
      box.style.translate = '-50% 20px';
      setTimeout(() => { box.hidden = true; }, 350);
    }));
  });

  /* ------------------------------------------------------------ Easter Egg: Konami-Code */
  safe(() => {
    if (!has('konami')) return;
    const seq = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
    let pos = 0;
    d.addEventListener('keydown', (e) => {
      const k = e.key.length === 1 ? e.key.toLowerCase() : e.key;
      pos = k === seq[pos] ? pos + 1 : (k === seq[0] ? 1 : 0);
      if (pos === seq.length) { pos = 0; party(); }
    });
    function party() {
      const tank = d.createElement('div');
      tank.className = 'konami-tank';
      tank.innerHTML = '<svg class="i" aria-hidden="true"><use href="' + (($('.brand-mark use')?.getAttribute('href') || '').split('#')[0]) + '#tank"/></svg>';
      const msg = d.createElement('div');
      msg.className = 'konami-msg';
      msg.textContent = i18n.konami || 'Panzer marsch!';
      const cv = d.createElement('canvas');
      cv.className = 'confetti';
      body.append(tank, msg, cv);
      const ctx = cv.getContext('2d');
      const W = cv.width = innerWidth, H = cv.height = innerHeight;
      const col = ['#F97316', '#fb9a52', '#fde68a', '#ffffff', '#c25a12'];
      const bits = Array.from({ length: 140 }, () => ({
        x: W * 0.5, y: H * 0.42, vx: (Math.random() - 0.5) * 16, vy: Math.random() * -14 - 3,
        s: Math.random() * 7 + 3, c: col[(Math.random() * col.length) | 0], r: Math.random() * 6, vr: (Math.random() - 0.5) * 0.4,
      }));
      let t = 0;
      (function frame() {
        ctx.clearRect(0, 0, W, H);
        bits.forEach((b) => {
          b.vy += 0.32; b.x += b.vx; b.y += b.vy; b.r += b.vr;
          ctx.save(); ctx.translate(b.x, b.y); ctx.rotate(b.r); ctx.fillStyle = b.c; ctx.fillRect(-b.s / 2, -b.s / 4, b.s, b.s / 2); ctx.restore();
        });
        if (++t < 220) requestAnimationFrame(frame); else cv.remove();
      })();
      setTimeout(() => { tank.remove(); msg.remove(); }, 5800);
    }
  });

  // Toast-Stil (klein gehalten, hier inline, damit die CSS-Datei schlank bleibt)
  const st = d.createElement('style');
  st.textContent = '.toast{position:fixed;left:50%;bottom:5.5rem;z-index:10003;translate:-50% 20px;opacity:0;padding:.7rem 1.2rem;border-radius:999px;background:var(--accent);color:var(--on-accent);font:600 .9rem var(--font-body);box-shadow:0 14px 34px -10px rgba(var(--accent-rgb),.8);transition:opacity .35s,translate .45s var(--ease)}.toast.is-in{opacity:1;translate:-50% 0}';
  d.head.appendChild(st);
})();
