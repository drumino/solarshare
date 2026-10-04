// Formulaire incident : triage IA (catégorie + gravité + conseil)
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('incident-form');
  const btn = document.getElementById('btn-ai-triage');
  if (!form || !btn) return;

  btn.addEventListener('click', async () => {
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    if (title.length < 3 || description.length < 10) { alert('Renseignez un titre et une description (10 caractères min.).'); return; }

    setLoading(btn, true);
    try {
      const d = await postJson(form.dataset.triageUrl, { title, description });
      document.getElementById('category').value = d.category;
      document.getElementById('severity').value = d.severity;
      const box = document.getElementById('ai-triage-box');
      box.classList.remove('d-none');
      box.innerHTML = `<span class="ai-chip">IA</span> <strong>${d.summary}</strong><br><small>Action recommandée : ${d.advice}</small>`;
    } catch (e) { alert(e.message); }
    setLoading(btn, false);
  });
});
