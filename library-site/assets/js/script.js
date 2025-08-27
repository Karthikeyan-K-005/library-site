// Simple live filters for tables
(function(){
  const bookSearch = document.getElementById('bookSearch');
  const historySearch = document.getElementById('historySearch');

  function filterTable(input, tableId) {
    const q = (input.value || '').toLowerCase();
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(tr => {
      tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  }

  if (bookSearch) bookSearch.addEventListener('input', () => filterTable(bookSearch, 'borrowTable'));
  if (historySearch) historySearch.addEventListener('input', () => filterTable(historySearch, 'historyTable'));
})();
