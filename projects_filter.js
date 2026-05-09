let activeType = 'all';

function filterCards(type, btn) {
  activeType = type;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

const searchInput = document.getElementById('projectSearch');
const searchClear = document.getElementById('searchClear');
const searchCount = document.getElementById('searchCount');
const prsnlFilter = document.getElementById('prsnlFilter');
const noResultsMsg = document.getElementById('noResultsMsg');

if(searchInput) {
  searchInput.addEventListener('input', function() {
    searchClear.classList.toggle('visible', this.value.length > 0);
    applyFilters();
  });
}

if(searchClear) {
  searchClear.addEventListener('click', function() {
    searchInput.value = '';
    searchClear.classList.remove('visible');
    applyFilters();
    searchInput.focus();
  });
}

function applyFilters() {
  if(!searchInput) return;
  const term = searchInput.value.trim().toLowerCase();
  const prsnlId = prsnlFilter ? prsnlFilter.value : '';
  const cards = document.querySelectorAll('.card');
  let visibleCount = 0;

  cards.forEach(function(card) {
    const matchType = (activeType === 'all' || card.dataset.type === activeType);
    const matchSearch = !term || card.dataset.name.includes(term);
    
    let matchPrsnl = true;
    if (prsnlId) {
      const teamArray = card.dataset.teamIds ? card.dataset.teamIds.split(',') : [];
      matchPrsnl = teamArray.includes(prsnlId);
    }
    
    const visible = matchType && matchSearch && matchPrsnl;
    card.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;

    if (term && visible) {
      const nameEl = card.querySelector('.project-name');
      if (nameEl) {
        const original = nameEl.getAttribute('data-original') || nameEl.textContent;
        nameEl.setAttribute('data-original', original);
        const regex = new RegExp('(' + escapeRegex(term) + ')', 'gi');
        nameEl.innerHTML = original.replace(regex, '<span class="search-highlight">$1</span>');
      }
    } else {
      const nameEl = card.querySelector('.project-name');
      if (nameEl && nameEl.getAttribute('data-original')) {
        nameEl.textContent = nameEl.getAttribute('data-original');
      }
    }
  });

  if (term) {
    searchCount.textContent = visibleCount + ' sonuç bulundu';
    searchCount.className = visibleCount > 0 ? 'has-results' : 'no-results';
  } else {
    searchCount.textContent = '';
    searchCount.className = '';
  }

  if(noResultsMsg) {
    noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
  }
}

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
