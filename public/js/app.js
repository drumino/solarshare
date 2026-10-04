// Utilitaires partagés (front + back)
window.postJson = async function (url, payload) {
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Erreur serveur');
    throw new Error(msg);
  }
  return data;
};

window.setLoading = function (btn, loading) {
  if (!btn) return;
  btn.disabled = loading;
  btn.dataset.label = btn.dataset.label || btn.innerHTML;
  btn.innerHTML = loading ? '<span class="spinner-border spinner-border-sm me-1"></span>Analyse...' : btn.dataset.label;
};
