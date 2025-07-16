
        // Simulazione database in memoria (in produzione usare PHP + MySQL)
        let mangaDatabase = [
            {
                id: 1,
                title: "Seraph of the End",
                image: "https://via.placeholder.com/200x300/667eea/white?text=Seraph+of+the+End",
                totalVolumes: 34,
                ownedVolumes: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32]
            }
        ];

        let nextId = 2;
        let mangaToDelete = null;

        // Funzioni di navigazione
        function showSection(sectionName) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(sectionName).classList.add('active');
            event.target.classList.add('active');
            
            if (sectionName === 'collezioneserie') {
                renderMangaList();
            } else if (sectionName === 'rimuovi') {
                renderDeleteList();
            }
        }

        // Rendering della collezione
        function renderMangaList() {
            const mangaList = document.getElementById('manga-list');
            mangaList.innerHTML = '';
            
            mangaDatabase.forEach(manga => {
                const missingVolumes = manga.totalVolumes - manga.ownedVolumes.length;
                const completionPercentage = (manga.ownedVolumes.length / manga.totalVolumes) * 100;
                
                const mangaCard = document.createElement('div');
                mangaCard.className = 'manga-card';
                mangaCard.onclick = () => showMangaDetails(manga.id);
                
                mangaCard.innerHTML = `
                    <img src="${manga.image}" alt="${manga.title}" onerror="this.src='https://via.placeholder.com/200x300/ccc/666?text=No+Image'">
                    <div class="manga-title">${manga.title}</div>
                    <div class="manga-progress">
                        ${missingVolumes === 0 ? 
                            '<span class="completed">✅ COMPLETA</span>' : 
                            `Mancano ${missingVolumes} volumi`
                        }
                    </div>
                    <div class="manga-progress">${manga.ownedVolumes.length}/${manga.totalVolumes} volumi</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${completionPercentage}%"></div>
                    </div>
                `;
                
                mangaList.appendChild(mangaCard);
            });
        }

        // Dettagli del manga
        function showMangaDetails(mangaId) {
            const manga = mangaDatabase.find(m => m.id === mangaId);
            if (!manga) return;
            
            const detailsDiv = document.getElementById('manga-details');
            const missingVolumes = manga.totalVolumes - manga.ownedVolumes.length;
            
            detailsDiv.innerHTML = `
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="${manga.image}" alt="${manga.title}" style="width: 200px; height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">
                    <h2>${manga.title}</h2>
                    <p style="font-size: 1.2em; margin-bottom: 10px;">
                        ${missingVolumes === 0 ? 
                            '<span class="completed">✅ COLLEZIONE COMPLETA</span>' : 
                            `Mancano ${missingVolumes} volumi per completare la serie`
                        }
                    </p>
                    <p>Posseduti: ${manga.ownedVolumes.length}/${manga.totalVolumes} volumi</p>
                </div>
                <h3>Volumi:</h3>
                <div class="volume-grid">
                    ${generateVolumeGrid(manga)}
                </div>
            `;
            
            showSection('details');
        }

        function generateVolumeGrid(manga) {
            let html = '';
            for (let i = 1; i <= manga.totalVolumes; i++) {
                const owned = manga.ownedVolumes.includes(i);
                html += `
                    <div class="volume-item ${owned ? 'owned' : 'missing'}" 
                         onclick="toggleVolume(${manga.id}, ${i})"
                         title="Volume ${i} - ${owned ? 'Posseduto' : 'Mancante'}">
                        ${i}
                    </div>
                `;
            }
            return html;
        }

        function toggleVolume(mangaId, volumeNumber) {
            const manga = mangaDatabase.find(m => m.id === mangaId);
            if (!manga) return;
            
            const volumeIndex = manga.ownedVolumes.indexOf(volumeNumber);
            if (volumeIndex > -1) {
                manga.ownedVolumes.splice(volumeIndex, 1);
            } else {
                manga.ownedVolumes.push(volumeNumber);
                manga.ownedVolumes.sort((a, b) => a - b);
            }
            
            showMangaDetails(mangaId);
        }

        // Gestione form aggiungi manga
        document.getElementById('totalVolumes').addEventListener('input', function() {
            const totalVolumes = parseInt(this.value);
            const checkboxDiv = document.getElementById('volumeCheckboxes');
            
            if (totalVolumes && totalVolumes > 0) {
                let html = '';
                for (let i = 1; i <= totalVolumes; i++) {
                    html += `
                        <div class="checkbox-item">
                            <input type="checkbox" id="vol${i}" name="volumes" value="${i}">
                            <label for="vol${i}">Vol ${i}</label>
                        </div>
                    `;
                }
                checkboxDiv.innerHTML = html;
            } else {
                checkboxDiv.innerHTML = '';
            }
        });

        document.getElementById('addMangaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const title = formData.get('title');
            const image = formData.get('image');
            const totalVolumes = parseInt(formData.get('totalVolumes'));
            
            const selectedVolumes = [];
            document.querySelectorAll('input[name="volumes"]:checked').forEach(checkbox => {
                selectedVolumes.push(parseInt(checkbox.value));
            });
            
            const newManga = {
                id: nextId++,
                title,
                image,
                totalVolumes,
                ownedVolumes: selectedVolumes
            };
            
            mangaDatabase.push(newManga);
            
            // Messaggio di successo
            document.getElementById('add-message').innerHTML = `
                <div class="alert alert-success">
                    ✅ Manga "${title}" aggiunto con successo!
                </div>
            `;
            
            // Reset form
            this.reset();
            document.getElementById('volumeCheckboxes').innerHTML = '';
            
            // Nascondi messaggio dopo 3 secondi
            setTimeout(() => {
                document.getElementById('add-message').innerHTML = '';
            }, 3000);
        });

        // Gestione eliminazione manga
        function renderDeleteList() {
            const deleteList = document.getElementById('deleteList');
            deleteList.innerHTML = '';
            
            mangaDatabase.forEach(manga => {
                const deleteItem = document.createElement('div');
                deleteItem.className = 'delete-item';
                deleteItem.innerHTML = `
                    <div>
                        <strong>${manga.title}</strong>
                        <br>
                        <small>${manga.ownedVolumes.length}/${manga.totalVolumes} volumi</small>
                    </div>
                    <button class="btn btn-danger" onclick="deleteManga(${manga.id})">
                        🗑️ Elimina
                    </button>
                `;
                deleteList.appendChild(deleteItem);
            });
        }

        function deleteManga(mangaId) {
            const manga = mangaDatabase.find(m => m.id === mangaId);
            if (manga) {
                mangaToDelete = mangaId;
                document.getElementById('confirmModal').style.display = 'block';
            }
        }

        function confirmDelete() {
            if (mangaToDelete) {
                const mangaIndex = mangaDatabase.findIndex(m => m.id === mangaToDelete);
                const mangaTitle = mangaDatabase[mangaIndex].title;
                
                mangaDatabase.splice(mangaIndex, 1);
                
                document.getElementById('delete-message').innerHTML = `
                    <div class="alert alert-success">
                        ✅ Manga "${mangaTitle}" eliminato con successo!
                    </div>
                `;
                
                renderDeleteList();
                closeModal();
                
                setTimeout(() => {
                    document.getElementById('delete-message').innerHTML = '';
                }, 3000);
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            mangaToDelete = null;
        }

        // Gestione modal
        document.querySelector('.close').onclick = closeModal;
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            renderMangaList();
        });