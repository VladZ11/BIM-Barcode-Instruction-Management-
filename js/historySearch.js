/**
 * BARC4 Search functionality for the history table
 * and comment formatting
 */
document.addEventListener('DOMContentLoaded', function() {
    // ===== Поиск по таблице истории =====
    var searchInput = document.getElementById('barc4SearchInput');
    var searchButton = document.getElementById('searchButton');
    var clearButton = document.getElementById('clearButton');
    var table = document.getElementById('historyTable');
    
    if (!searchInput || !table) return;
    
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    // Focus search input when page loads
    searchInput.focus();
    
    function filterTable() {
        var filterText = searchInput.value.trim().toLowerCase();
        var visibleCount = 0;
        
        if (rows.length > 0) {
            for (var i = 0; i < rows.length; i++) {
                var barc4Cell = rows[i].getElementsByTagName('td')[1]; // BARC4 is in the second column
                if (barc4Cell) {
                    var barc4Value = barc4Cell.textContent || barc4Cell.innerText;
                    if (barc4Value.toLowerCase().indexOf(filterText) > -1) {
                        rows[i].style.display = "";
                        visibleCount++;
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }
        
        // Show a message if no results
        var existingMessage = document.getElementById('noResultsMessage');
        if (visibleCount === 0 && filterText !== '') {
            if (!existingMessage) {
                var message = document.createElement('p');
                message.id = 'noResultsMessage';
                message.className = 'alert alert-info';
                message.textContent = 'Brak wyników dla "' + filterText + '"';
                table.parentNode.insertBefore(message, table.nextSibling);
            } else {
                existingMessage.style.display = 'block';
                existingMessage.textContent = 'Brak wyników dla "' + filterText + '"';
            }
        } else if (existingMessage) {
            existingMessage.style.display = 'none';
        }
    }
    
    // Search when button is clicked
    searchButton.addEventListener('click', filterTable);
    
    // Clear search and show all rows
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        for (var i = 0; i < rows.length; i++) {
            rows[i].style.display = "";
        }
        var existingMessage = document.getElementById('noResultsMessage');
        if (existingMessage) existingMessage.style.display = 'none';
        searchInput.focus();
    });
    
    // Search when Enter key is pressed
    searchInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter' || event.keyCode === 13) {
            filterTable();
        }
    });

    // ===== Форматирование комментариев истории =====
    // Получаем все элементы с комментариями
    const commentElements = document.querySelectorAll('.history-comment');
    
    // Обрабатываем каждый комментарий
    commentElements.forEach(function(element) {
        // Получаем HTML комментария
        let html = element.innerHTML;
        
        // Исправляем проблему с переносом строк после "Dodano uwagi" и "Usunięto uwagi"
        html = html.replace(/Dodano uwagi(\s*\(\d+\))?:\s*\n/g, function(match, p1) {
            return '<span style="color:#5CFF5C;"><b>Dodano uwagi' + (p1 || '') + ':</b></span> ';
        });
        
        html = html.replace(/Usunięto uwagi(\s*\(\d+\))?:\s*\n/g, function(match, p1) {
            return '<span style="color:#FF6B6B;"><b>Usunięto uwagi' + (p1 || '') + ':</b></span> ';
        });
        
        // Удаляем переносы строк внутри квадратных скобок
        html = html.replace(/\[\s*\n\s*/g, '[');
        html = html.replace(/\s*\n\s*]/g, ']');
        
        // Исправляем проблему перенос строк внутри записей уваг
        html = html.replace(/\[\s*([^[\]]+?)\s*]/g, function(match, content) {
            // Заменяем переносы строк внутри скобок на пробелы
            return '[' + content.replace(/\s*\n\s*/g, ' ').trim() + ']';
        });
        
        // Обновляем содержимое элемента
        element.innerHTML = html;
    });
});