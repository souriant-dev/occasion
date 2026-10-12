(() => {
  'use strict';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  /** POST AJAX avec jeton CSRF. data = objet simple. */
  window.api = async (url, data = {}) => {
    try {
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        body: new URLSearchParams(data),
        credentials: 'same-origin',
      });
      const j = await r.json();
      if (!r.ok) j.ok = false;
      return j;
    } catch (e) {
      return { ok: false, error: 'Erreur réseau, réessayez.' };
    }
  };

  window.toast = (msg) => {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.append(t);
    setTimeout(() => t.remove(), 2800);
  };

  const setBadge = (id, n) => {
    const b = document.getElementById(id);
    if (!b) return;
    b.textContent = n;
    b.hidden = !n;
  };

  // Menu mobile
  document.getElementById('burger')?.addEventListener('click', () =>
    document.getElementById('menu').classList.toggle('open'));

  // Ajouter au panier
  document.querySelectorAll('[data-add-cart]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      const res = await api('api/cart.php', { action: 'add', product_id: btn.dataset.addCart });
      if (res.ok) {
        btn.textContent = 'Dans le panier ✓';
        setBadge('badge-cart', res.count);
        toast('Article ajouté au panier');
      } else {
        btn.disabled = false;
        toast(res.error || 'Erreur');
      }
    });
  });

  // Retirer du panier
  document.querySelectorAll('[data-remove-cart]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.removeCart;
      const res = await api('api/cart.php', { action: 'remove', product_id: id });
      if (!res.ok) return toast(res.error || 'Erreur');
      document.querySelector(`[data-row="${id}"]`)?.remove();
      setBadge('badge-cart', res.count);
      const n = document.getElementById('cart-n');
      const t = document.getElementById('cart-total');
      if (n) n.textContent = res.count;
      if (t) t.textContent = res.total;
      if (res.count === 0) location.reload();
    });
  });

  // Notation par étoiles
  const rate = document.querySelector('[data-rate]');
  if (rate) {
    const stars = [...rate.querySelectorAll('button')];
    const paint = (n) => stars.forEach((s) => s.classList.toggle('on', +s.dataset.v <= n));
    let mine = +rate.dataset.mine || 0;
    paint(mine);
    stars.forEach((s) => {
      s.addEventListener('mouseenter', () => paint(+s.dataset.v));
      s.addEventListener('focus', () => paint(+s.dataset.v));
      s.addEventListener('click', async () => {
        const res = await api('api/rate.php', { product_id: rate.dataset.rate, stars: s.dataset.v });
        if (!res.ok) return toast(res.error || 'Erreur');
        mine = res.mine;
        paint(mine);
        document.querySelector('#avg-stars .stars')?.style.setProperty('--rating', res.avg);
        const txt = document.getElementById('avg-text');
        if (txt) txt.textContent = `${String(res.avg).replace('.', ',')}/5 · ${res.count} avis`;
        const my = document.getElementById('my-rating-text');
        if (my) my.textContent = `Vous avez donné ${mine}/5`;
        toast('Merci pour votre note !');
      });
    });
    rate.addEventListener('mouseleave', () => paint(mine));
  }

  // Aperçu de l'image à la publication
  const file = document.getElementById('image');
  const prev = document.getElementById('preview');
  if (file && prev) {
    file.addEventListener('change', () => {
      const f = file.files[0];
      if (!f) { prev.hidden = true; return; }
      if (f.size > 3 * 1024 * 1024) { toast('Image trop lourde (3 Mo max)'); file.value = ''; prev.hidden = true; return; }
      prev.src = URL.createObjectURL(f);
      prev.hidden = false;
    });
  }

  // Rafraîchit le badge des messages non lus
  if (document.getElementById('badge-msg')) {
    const refresh = async () => {
      if (document.hidden) return;
      try {
        const r = await fetch('api/chat.php?action=unread', { credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) setBadge('badge-msg', j.unread);
      } catch (e) { /* ignore */ }
    };
    setInterval(refresh, 15000);
  }
})();
