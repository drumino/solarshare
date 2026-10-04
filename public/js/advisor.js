// Assistant énergie : lignes d'appareils dynamiques
document.addEventListener('DOMContentLoaded', () => {
  const body = document.getElementById('appliance-rows');
  if (!body) return;
  let index = body.querySelectorAll('tr').length;

  function addRow(name = '', watts = '', hours = '') {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="appliances[${index}][name]" class="form-control" value="${name}" placeholder="Appareil" required></td>
      <td><input type="number" name="appliances[${index}][watts]" class="form-control" value="${watts}" min="1" max="5000" placeholder="W" required></td>
      <td><input type="number" step="0.5" name="appliances[${index}][hours]" class="form-control" value="${hours}" min="0.1" max="24" placeholder="h/jour" required></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>`;
    body.appendChild(tr);
    index++;
  }

  document.querySelectorAll('.preset').forEach((b) => b.addEventListener('click', () => addRow(b.dataset.name, b.dataset.watts, b.dataset.hours)));
  document.getElementById('add-row').addEventListener('click', () => addRow());
  body.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-row');
    if (btn && body.querySelectorAll('tr').length > 1) btn.closest('tr').remove();
  });
});
