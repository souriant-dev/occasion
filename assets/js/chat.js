(() => {
  'use strict';
  const box = document.getElementById('chat');
  if (!box) return;

  const me = Number(box.dataset.me);
  const pid = box.dataset.product;
  const other = box.dataset.with;
  const list = document.getElementById('messages');
  const form = document.getElementById('composer');
  const input = document.getElementById('msg-input');
  const qs = `product=${encodeURIComponent(pid)}&with=${encodeURIComponent(other)}`;
  let last = 0;
  let busy = false;

  function addMessage(m) {
    const d = document.createElement('div');
    d.className = 'msg ' + (Number(m.sender_id) === me ? 'me' : 'them');
    const p = document.createElement('p');
    p.textContent = m.body;            // textContent : pas d'injection HTML
    const t = document.createElement('time');
    t.textContent = m.created_at;
    d.append(p, t);
    list.append(d);
  }

  async function poll() {
    if (busy || document.hidden) return;
    busy = true;
    try {
      const r = await fetch(`api/chat.php?action=fetch&${qs}&after=${last}`, { credentials: 'same-origin' });
      const j = await r.json();
      if (j.ok && j.messages.length) {
        const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80 || last === 0;
        document.getElementById('empty-note')?.remove();
        j.messages.forEach((m) => { addMessage(m); last = Math.max(last, Number(m.id)); });
        if (nearBottom) list.scrollTop = list.scrollHeight;
        const badge = document.getElementById('badge-msg');
        if (badge) { // le fetch a marqué les messages comme lus
          fetch('api/chat.php?action=unread', { credentials: 'same-origin' }).then(r => r.json()).then(u => {
            if (u.ok) { badge.textContent = u.unread; badge.hidden = !u.unread; }
          });
        }
      } else if (j.ok && last === 0 && !list.dataset.empty) {
        list.dataset.empty = '1';
        const e = document.createElement('p');
        e.className = 'muted center';
        e.textContent = 'Aucun message. Dites bonjour 👋';
        e.id = 'empty-note';
        list.append(e);
      }
    } catch (e) { /* réseau indisponible : on réessaiera */ }
    busy = false;
  }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const body = input.value.trim();
    if (!body) return;
    input.disabled = true;
    const res = await api(`api/chat.php?action=send&${qs}`, { body });
    input.disabled = false;
    if (!res.ok) { toast(res.error || 'Envoi impossible'); return; }
    input.value = '';
    document.getElementById('empty-note')?.remove();
    await poll();
    list.scrollTop = list.scrollHeight;
    input.focus();
  });

  poll();
  setInterval(poll, 3000);
  document.addEventListener('visibilitychange', poll);
})();
