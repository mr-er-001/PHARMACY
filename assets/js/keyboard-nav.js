document.addEventListener('DOMContentLoaded', function() {
    let selectedIndex = -1;
    let currentInput = null;
    let currentResults = null;
    
    document.addEventListener('keydown', function(e) {
        const target = e.target;
        const inputId = target.id;
        
        if (!(inputId === 'searchMedicine' || inputId === 'medicineSearch' || inputId === 'searchMedicinePurchase')) return;
        if (!target.matches('input[type="text"], input[type="search"]')) return;
        
        let resultsId = inputId === 'searchMedicine' ? 'searchResults' : 
                      inputId === 'searchMedicinePurchase' ? 'searchResults' : null;
        
        const resultsContainer = resultsId ? document.getElementById(resultsId) : document.querySelector('.search-suggestions');
        if (!resultsContainer) return;
        
        const listItems = resultsContainer.querySelectorAll('[data-index], .list-group-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (listItems.length > 0) {
                if (selectedIndex < listItems.length - 1) selectedIndex++;
                listItems.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
                listItems[selectedIndex]?.scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (listItems.length > 0) {
                if (selectedIndex > 0) selectedIndex--;
                listItems.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
                listItems[selectedIndex]?.scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && listItems[selectedIndex]) {
                listItems[selectedIndex].click();
            }
        } else if (e.key === 'Tab') {
            if (listItems.length > 0) {
                listItems.forEach(item => item.classList.remove('active'));
                selectedIndex = -1;
            }
        }
    });
});