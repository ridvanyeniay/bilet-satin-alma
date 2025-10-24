document.addEventListener('DOMContentLoaded', function() {

    setupAutocomplete('departure', 'departure-suggestions');
    setupAutocomplete('destination', 'destination-suggestions');

    setupAutocomplete('departure_city', 'fap_departure_suggestions');
    setupAutocomplete('destination_city', 'fap_destination_suggestions');

    function setupAutocomplete(inputId, suggestionsId) {
        const input = document.getElementById(inputId);
        const suggestionsList = document.getElementById(suggestionsId);

        if (!input || !suggestionsList) {
            return; 
        }

        // Kullanıcı input'a bir şey yazdığında (her tuşa basışta) bu olay tetiklenir
        input.addEventListener('input', function() {
            const query = input.value.trim();
            
            if (query.length < 1) {
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';
                return;
            }

            // Backend'deki PHP script'ine istek at
            fetch(`/search_cities.php?term=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) { throw new Error('Network response was not ok'); }
                    return response.json();
                })
                .then(cities => {
                    suggestionsList.innerHTML = ''; // Eski önerileri temizle
                    
                    if (Array.isArray(cities) && cities.length > 0) {
                        suggestionsList.style.display = 'block'; // Listeyi görünür yap
                        cities.forEach(city => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.classList.add('suggestion-item');
                            suggestionItem.textContent = city;
                            
                            // Bir öneriye tıklandığında
                            suggestionItem.addEventListener('click', function() {
                                input.value = city; // Input'un değerini tıklanan şehir yap
                                suggestionsList.innerHTML = ''; // Listeyi temizle
                                suggestionsList.style.display = 'none'; // Listeyi gizle
                            });
                            
                            suggestionsList.appendChild(suggestionItem);
                        });
                    } else {
                        // Sonuç yoksa listeyi gizle
                        suggestionsList.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Şehirler alınırken hata oluştu:', error);
                    suggestionsList.style.display = 'none';
                });
        });

        // Sayfada input dışında bir yere tıklandığında öneri listesini kapat
        document.addEventListener('click', function(e) {
            if (e.target !== input) {
                suggestionsList.style.display = 'none';
            }
        });
    }
});