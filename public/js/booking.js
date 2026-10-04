// Fiche équipement : calcul du prix en direct
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('booking-form');
  if (!form) return;
  const price = parseFloat(form.dataset.price);
  const start = document.getElementById('start_date');
  const end = document.getElementById('end_date');
  const out = document.getElementById('booking-total');

  function update() {
    if (!start.value || !end.value) { out.textContent = '—'; return; }
    const days = Math.round((new Date(end.value) - new Date(start.value)) / 86400000) + 1;
    out.textContent = days > 0 ? `${days} jour(s) × ${price.toFixed(2)} = ${(days * price).toFixed(2)} DT` : 'Dates invalides';
  }
  start.addEventListener('change', () => { end.min = start.value; update(); });
  end.addEventListener('change', update);
  update();
});
