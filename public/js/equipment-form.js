// Formulaire équipement : champs conditionnels + assistant IA (description / prix)
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('equipment-form');
  if (!form) return;

  const $ = (id) => document.getElementById(id);
  const typeSel = $('type');

  // 1) Champs conditionnels selon le type
  function toggleFields() {
    const t = typeSel.value;
    $('wrap-power_watts').style.display = ['solar_panel', 'wind', 'inverter', 'other', ''].includes(t) ? '' : 'none';
    $('wrap-capacity_wh').style.display = ['battery', 'other', ''].includes(t) ? '' : 'none';
  }
  typeSel.addEventListener('change', toggleFields);
  toggleFields();

  const payload = () => ({
    type: typeSel.value,
    title: $('title').value,
    power_watts: $('power_watts').value || null,
    capacity_wh: $('capacity_wh').value || null,
    condition: $('condition').value,
    city: $('city').value,
  });

  // 2) Génération de description
  const btnDesc = $('btn-ai-desc');
  btnDesc?.addEventListener('click', async () => {
    if (!typeSel.value) { alert('Choisissez d\'abord un type d\'équipement.'); return; }
    setLoading(btnDesc, true);
    try {
      const data = await postJson(form.dataset.descUrl, payload());
      $('description').value = data.text;
      $('ai-desc-source').textContent = data.source === 'llm' ? 'Généré par l\'IA' : 'Généré (modèle local)';
    } catch (e) { alert(e.message); }
    setLoading(btnDesc, false);
  });

  // 3) Suggestion de prix
  const btnPrice = $('btn-ai-price');
  btnPrice?.addEventListener('click', async () => {
    if (!typeSel.value) { alert('Choisissez d\'abord un type d\'équipement.'); return; }
    setLoading(btnPrice, true);
    try {
      const d = await postJson(form.dataset.priceUrl, payload());
      const box = $('ai-price-box');
      box.classList.remove('d-none');
      box.innerHTML = `<strong>Prix conseillé : ${d.suggested} DT/jour</strong> (fourchette ${d.min} – ${d.max} DT)<br>
        Caution suggérée : ${d.deposit} DT<br><small class="text-muted">${d.explanation}</small><br>
        <button type="button" class="btn btn-sm btn-sun mt-2" id="apply-price">Appliquer</button>`;
      $('apply-price').addEventListener('click', () => {
        $('price_per_day').value = d.suggested;
        $('deposit').value = d.deposit;
      });
    } catch (e) { alert(e.message); }
    setLoading(btnPrice, false);
  });
});
