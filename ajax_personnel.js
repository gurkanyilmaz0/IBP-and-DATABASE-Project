document.getElementById('deptFilter').addEventListener('change', function() {
  const deptId = this.value;
  const resultBox = document.getElementById('ajaxResult');

  if (!deptId) {
    location.reload();
    return;
  }

  resultBox.style.display = 'block';
  resultBox.textContent = 'Yükleniyor...';

  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'ajax/filter_personnel.php?dept_id=' + deptId, true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
      const data = JSON.parse(xhr.responseText);
      resultBox.textContent = data.dept_name + ' departmanında ' + data.count + ' personel bulundu.';
      renderTable(data.personnel);
    }
  };
  xhr.send();
});

function renderTable(list) {
  const tbody = document.getElementById('tableBody');
  if (!list.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">Bu departmanda personel yok.</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(function(p) {
    const yil = Math.floor(p.kidem_ay / 12);
    const ay  = p.kidem_ay % 12;
    const mgrHtml = p.manager_name
      ? `<span class="mgr-chip">▲ ${p.manager_name}</span>`
      : `<span class="mgr-none">—</span>`;
    return `<tr>
      <td><span style="font-family:'Share Tech Mono',monospace;color:var(--muted)">${p.id}</span></td>
      <td>${p.f_name} ${p.l_name}</td>
      <td style="font-family:'Share Tech Mono',monospace;font-size:12px">${p.email}</td>
      <td>${p.dept_name}</td>
      <td><span class="badge badge-tech">${p.staff_type}</span></td>
      <td>${mgrHtml}</td>
      <td style="font-family:'Share Tech Mono',monospace;font-size:12px">${p.hire_date}</td>
      <td style="font-family:'Share Tech Mono',monospace;font-size:12px;white-space:nowrap">${yil}y ${ay}a</td>
      <td style="text-align:center">${p.mnthly_hrs}h</td>
      <td><div class="actions">
        <a href="personnel_form.php?id=${p.id}" class="edit-btn">DÜZENLE</a>
        <a href="personnel_delete.php?id=${p.id}" class="del-btn"
           onclick="return confirm('Silmek istediğinize emin misiniz?')">SİL</a>
      </div></td>
    </tr>`;
  }).join('');
}

document.getElementById('searchInput').addEventListener('input', function() {
  const term = this.value.toLowerCase();
  document.querySelectorAll('#tableBody tr').forEach(function(row) {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(term) ? '' : 'none';
  });
});
