/* LockHub front-end behaviour. No dependencies. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var CSRF = document.body.getAttribute('data-csrf');

  /* ---------- Toasts ---------- */

  function dismissLater(el, ms) {
    setTimeout(function () {
      el.classList.add('leaving');
      setTimeout(function () { el.remove(); }, 300);
    }, ms);
  }

  function toast(message, type) {
    var box = $('#toasts');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'toast toast-' + (type || 'success');
    el.textContent = message;
    box.appendChild(el);
    dismissLater(el, 3200);
  }

  $$('#toasts .toast').forEach(function (el, i) { dismissLater(el, 4500 + i * 600); });

  /* ---------- Mobile navigation ---------- */

  var navToggle = $('[data-nav-toggle]');
  if (navToggle) {
    navToggle.addEventListener('click', function () {
      var open = $('[data-nav]').classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open);
      navToggle.innerHTML = open ? '<i class="fa fa-times"></i>' : '<i class="fa fa-bars"></i>';
    });
  }

  /* ---------- Clipboard ---------- */

  function copyText(text, label) {
    var done = function () { toast((label || 'Copied') + ' to clipboard'); };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
    } else {
      fallbackCopy(text);
      done();
    }
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) { /* ignore */ }
    ta.remove();
  }

  $$('[data-copy-text]').forEach(function (btn) {
    btn.addEventListener('click', function () { copyText(btn.getAttribute('data-copy-text'), 'Username copied'); });
  });

  /* ---------- Show / hide password inputs ---------- */

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-visibility]');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input');
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  /* ---------- Password strength (mirrors password_strength() in PHP) ---------- */

  var COMMON = ['password', '123456', '12345678', '123456789', 'qwerty', '111111', 'iloveyou', 'admin',
                'welcome', 'letmein', 'abc123', 'password1', 'qwerty123', 'monkey', 'dragon', 'lockhub'];
  var LABELS = ['Very weak', 'Weak', 'Fair', 'Strong', 'Very strong'];

  function scoreStrength(p) {
    var len = Array.from(p).length;
    if (!len || COMMON.indexOf(p.toLowerCase()) !== -1 || /^(.)\1*$/u.test(p)) return 0;
    var classes = (/[a-z]/.test(p) ? 1 : 0) + (/[A-Z]/.test(p) ? 1 : 0) + (/\d/.test(p) ? 1 : 0) + (/[^a-zA-Z\d]/.test(p) ? 1 : 0);
    var score = 0;
    if (len >= 8) score++;
    if (len >= 12) score++;
    if (len >= 16) score++;
    if (classes >= 3) score++;
    if (classes <= 1 && len < 20) score = Math.min(score, 1);
    return Math.min(score, 4);
  }

  function paintMeter(meter, value, emptyText) {
    if (!meter) return;
    var label = meter.querySelector('.strength-label');
    if (!value) {
      meter.removeAttribute('data-score');
      label.textContent = emptyText || '';
      return;
    }
    var s = scoreStrength(value);
    meter.setAttribute('data-score', s);
    label.textContent = LABELS[s];
  }

  $$('[data-strength-input]').forEach(function (input) {
    var meter = input.closest('.field').querySelector('[data-strength-meter]');
    var emptyText = meter ? meter.querySelector('.strength-label').textContent : '';
    var update = function () { paintMeter(meter, input.value, emptyText); };
    input.addEventListener('input', update);
    update();
  });

  /* ---------- Secure random generation ---------- */

  function randomInt(max) {
    // Rejection sampling avoids modulo bias.
    var limit = Math.floor(0x100000000 / max) * max;
    var buf = new Uint32Array(1);
    do { crypto.getRandomValues(buf); } while (buf[0] >= limit);
    return buf[0] % max;
  }

  var SETS = {
    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lower: 'abcdefghijklmnopqrstuvwxyz',
    digits: '0123456789',
    symbols: '!@#$%^&*()-_=+[]{};:,.?'
  };
  var AMBIGUOUS = /[O0Il1|`'"]/g;

  function generatePassword(opts) {
    var pools = [];
    Object.keys(SETS).forEach(function (k) {
      if (opts[k]) pools.push(opts.avoidAmbiguous ? SETS[k].replace(AMBIGUOUS, '') : SETS[k]);
    });
    if (!pools.length) pools.push(SETS.lower);
    var all = pools.join('');
    var chars = pools.map(function (pool) { return pool[randomInt(pool.length)]; }); // one from each set
    while (chars.length < opts.length) chars.push(all[randomInt(all.length)]);
    for (var i = chars.length - 1; i > 0; i--) {
      var j = randomInt(i + 1);
      var t = chars[i]; chars[i] = chars[j]; chars[j] = t;
    }
    return chars.join('');
  }

  var WORDS = ('acorn acid amber anchor apple arrow atlas autumn bamboo banjo barley beacon berry bison blossom ' +
    'bramble breeze bridge bronze bubble cabin cactus candle canyon carbon castle cedar cherry cider cinder ' +
    'citrus clover cobalt comet copper coral cosmic cotton crane crater crystal cypress dagger dancer delta ' +
    'desert dolphin dragon drift eagle echo ember emerald falcon fable feather fern fiesta flint forest fossil ' +
    'fox frost galaxy garden garnet geyser ginger glacier globe granite gravel harbor hazel heron hollow honey ' +
    'horizon iceberg indigo island ivory jaguar jasmine jelly jungle kayak kernel kettle kiwi lagoon lantern ' +
    'lava lemon lilac lily lotus lunar magnet mango maple marble meadow meteor mint mirror mocha monsoon mosaic ' +
    'nectar nimbus noble nomad nova oasis ocean olive onyx opal orbit orchid otter oyster panda papaya parrot ' +
    'pebble pepper pine pixel planet plasma plum polar pollen poppy prairie prism pumpkin quartz quill rabbit ' +
    'radar rain raven reef river rocket ruby saffron salmon sapphire satin savanna shadow shell sierra silver ' +
    'sky slate snow solar sparrow spice spiral spruce squid star storm summit sunset swan tango thunder tiger ' +
    'timber topaz torch trail tulip tundra turtle valley vapor velvet violet volcano walnut wave willow winter ' +
    'wolf yarrow zebra zenith zephyr').split(' ');

  function generatePassphrase(opts) {
    var words = [];
    for (var i = 0; i < opts.words; i++) {
      var w = WORDS[randomInt(WORDS.length)];
      words.push(opts.capitalize ? w.charAt(0).toUpperCase() + w.slice(1) : w);
    }
    if (opts.number) {
      var k = randomInt(words.length);
      words[k] += String(randomInt(10));
    }
    return words.join(opts.separator);
  }

  var defaultPassword = function () {
    return generatePassword({ length: 20, upper: true, lower: true, digits: true, symbols: true, avoidAmbiguous: true });
  };

  /* ---------- Generator page ---------- */

  var gen = $('[data-generator]');
  if (gen) {
    var mode = 'password';
    var out = $('[data-gen-output]', gen);
    var meter = $('[data-gen-strength]', gen);
    var lengthInput = $('[data-gen-length]', gen);
    var wordsInput = $('[data-gen-words]', gen);

    var render = function () {
      var value;
      if (mode === 'password') {
        var opts = { length: +lengthInput.value, avoidAmbiguous: $('[data-gen-ambiguous]', gen).checked };
        $$('[data-gen-set]', gen).forEach(function (cb) { opts[cb.getAttribute('data-gen-set')] = cb.checked; });
        $('[data-gen-length-label]', gen).textContent = lengthInput.value;
        value = generatePassword(opts);
      } else {
        $('[data-gen-words-label]', gen).textContent = wordsInput.value;
        value = generatePassphrase({
          words: +wordsInput.value,
          capitalize: $('[data-gen-capitalize]', gen).checked,
          number: $('[data-gen-number]', gen).checked,
          separator: $('[data-gen-separator]', gen).value
        });
      }
      out.textContent = value;
      paintMeter(meter, value);
    };

    // Keep at least one character set switched on.
    $$('[data-gen-set]', gen).forEach(function (cb) {
      cb.addEventListener('change', function () {
        if (!$$('[data-gen-set]', gen).some(function (c) { return c.checked; })) cb.checked = true;
      });
    });

    $$('.tab', gen).forEach(function (tab) {
      tab.addEventListener('click', function () {
        mode = tab.getAttribute('data-mode');
        $$('.tab', gen).forEach(function (t) {
          t.classList.toggle('active', t === tab);
          t.setAttribute('aria-selected', t === tab);
        });
        $$('[data-options]', gen).forEach(function (o) { o.hidden = o.getAttribute('data-options') !== mode; });
        render();
      });
    });

    gen.addEventListener('input', render);
    gen.addEventListener('change', render);
    $('[data-gen-refresh]', gen).addEventListener('click', render);
    $('[data-gen-copy]', gen).addEventListener('click', function () { copyText(out.textContent, 'Password copied'); });

    var save = $('[data-gen-save]', gen);
    if (save) {
      save.addEventListener('click', function () {
        try { sessionStorage.setItem('lh_pending_password', out.textContent); } catch (e) { /* ignore */ }
      });
    }
    render();
  }

  /* ---------- Dialogs & confirmations ---------- */

  $$('dialog').forEach(function (dlg) {
    dlg.addEventListener('click', function (e) {
      if (e.target.closest('[data-close]')) return dlg.close();
      if (e.target !== dlg) return;
      // A click on the dialog element itself is either its padding or the backdrop.
      var r = dlg.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) dlg.close();
    });
  });

  $$('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* ---------- Vault page ---------- */

  var list = $('[data-entries]');
  var entryDialog = $('#entry-dialog');

  function api(payload) {
    return fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify(payload),
      credentials: 'same-origin'
    }).then(function (res) {
      var type = res.headers.get('Content-Type') || '';
      if (type.indexOf('application/json') === -1) {
        // Session ended and we were redirected to the login page.
        window.location.reload();
        throw new Error('Session ended');
      }
      return res.json().then(function (body) {
        if (!res.ok) throw new Error(body.error || 'Something went wrong');
        return body;
      });
    });
  }

  var revealCache = {};
  function getPassword(id) {
    if (revealCache[id]) return Promise.resolve(revealCache[id]);
    return api({ action: 'reveal', id: id }).then(function (body) {
      revealCache[id] = body.password;
      return body.password;
    });
  }

  function openEntryDialog(entry) {
    var form = $('[data-entry-form]', entryDialog);
    form.reset();
    form.elements.id.value = entry ? entry.getAttribute('data-id') : '';
    form.elements.website.value = entry ? entry.getAttribute('data-website') : '';
    form.elements.url.value = entry ? entry.getAttribute('data-url') : '';
    form.elements.username.value = entry ? entry.getAttribute('data-username') : '';
    form.elements.notes.value = entry ? entry.getAttribute('data-notes') : '';
    form.elements.password.value = '';
    form.elements.password.type = 'password';
    $('[data-entry-title]', entryDialog).textContent = entry ? 'Edit ' + entry.getAttribute('data-website') : 'Add password';
    form.elements.password.dispatchEvent(new Event('input'));
    entryDialog.showModal();

    if (entry) {
      form.elements.password.placeholder = 'Loading…';
      getPassword(entry.getAttribute('data-id')).then(function (pw) {
        form.elements.password.value = pw;
        form.elements.password.placeholder = '';
        form.elements.password.dispatchEvent(new Event('input'));
      }, function (err) { toast(err.message, 'error'); });
    } else {
      form.elements.website.focus();
    }
    return form;
  }

  if (entryDialog) {
    $$('[data-open-entry]').forEach(function (btn) {
      btn.addEventListener('click', function () { openEntryDialog(null); });
    });

    $('[data-fill-generated]', entryDialog).addEventListener('click', function () {
      var input = entryDialog.querySelector('input[name="password"]');
      input.value = defaultPassword();
      input.type = 'text';
      input.dispatchEvent(new Event('input'));
    });

    // Arriving from the generator's "Save as a new vault entry" button.
    var pending = null;
    try {
      pending = sessionStorage.getItem('lh_pending_password');
      sessionStorage.removeItem('lh_pending_password');
    } catch (e) { /* ignore */ }
    if (pending) {
      var form = openEntryDialog(null);
      form.elements.password.value = pending;
      form.elements.password.type = 'text';
      form.elements.password.dispatchEvent(new Event('input'));
    }
  }

  if (list) {
    var entries = $$('.entry', list);
    var search = $('[data-search]');
    var sortSelect = $('[data-sort]');
    var noResults = $('[data-no-results]');
    var filter = 'all';

    var applyFilters = function () {
      var q = search.value.trim().toLowerCase();
      var shown = 0;
      entries.forEach(function (el) {
        var hay = [el.getAttribute('data-website'), el.getAttribute('data-username'), el.getAttribute('data-url')].join(' ').toLowerCase();
        var ok = (!q || hay.indexOf(q) !== -1) &&
                 (filter === 'all' || el.getAttribute('data-' + filter) === '1');
        el.hidden = !ok;
        if (ok) shown++;
      });
      noResults.hidden = shown > 0;
    };

    search.addEventListener('input', applyFilters);
    $$('[data-filter]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        filter = chip.getAttribute('data-filter');
        $$('[data-filter]').forEach(function (c) { c.classList.toggle('active', c === chip); });
        applyFilters();
      });
    });

    sortSelect.addEventListener('change', function () {
      var byName = sortSelect.value === 'name';
      entries.sort(function (a, b) {
        return byName
          ? a.getAttribute('data-website').localeCompare(b.getAttribute('data-website'), undefined, { sensitivity: 'base' })
          : b.getAttribute('data-updated') - a.getAttribute('data-updated');
      });
      entries.forEach(function (el) { list.appendChild(el); });
    });

    // Press "/" to jump to search.
    document.addEventListener('keydown', function (e) {
      if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && !$('dialog[open]')) {
        e.preventDefault();
        search.focus();
      }
    });

    list.addEventListener('click', function (e) {
      var entry = e.target.closest('.entry');
      if (!entry) return;
      var id = entry.getAttribute('data-id');

      if (e.target.closest('[data-reveal]')) {
        var btn = e.target.closest('[data-reveal]');
        var secret = $('[data-secret]', entry);
        if (secret.classList.contains('revealed')) {
          secret.textContent = '••••••••••';
          secret.classList.remove('revealed');
          btn.innerHTML = '<i class="fa fa-eye"></i>';
          return;
        }
        getPassword(id).then(function (pw) {
          secret.textContent = pw;
          secret.classList.add('revealed');
          btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
          clearTimeout(secret._hide);
          secret._hide = setTimeout(function () {
            if (secret.classList.contains('revealed')) btn.click();
          }, 20000);
        }, function (err) { toast(err.message, 'error'); });
      }

      if (e.target.closest('[data-copy-secret]')) {
        getPassword(id).then(function (pw) { copyText(pw, 'Password copied'); },
                             function (err) { toast(err.message, 'error'); });
      }

      if (e.target.closest('[data-edit]')) {
        openEntryDialog(entry);
      }

      if (e.target.closest('[data-delete]')) {
        var dlg = $('#delete-dialog');
        dlg.querySelector('input[name="id"]').value = id;
        $('[data-delete-name]', dlg).textContent = entry.getAttribute('data-website');
        dlg.showModal();
      }
    });
  }
})();
