<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manga Collection</title>
    <link rel="stylesheet" href="../css/stile.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Manga Collection</h1>
        </header>

        <div class="nav-buttons">
            <button class="nav-btn active" onclick="showSection('collezioneserie')">Collezione Serie</button>
            <button class="nav-btn" onclick="showSection('seriemancanti')">Serie Mancanti</button>
            <button class="nav-btn" onclick="showSection('variantmancanti')">Variant mancanti</button>
            <button class="nav-btn" onclick="showSection('aggiungi')">Aggiungi Manga/Variant</button>
            <button class="nav-btn" onclick="showSection('rimuovi')">Elimina Manga/Variant</button>
        </div>

        <!-- Sezione Collezione serie -->
        <div id="collezioneserie" class="section active">
            <h2>📖 La Mia Collezione</h2>
            <div id="manga-list" class="manga-grid"></div>
        </div>

        <!-- Sezione Serie mancanti-->
        <div id="seriemancanti" class="section">
            <h2>📖 Serie manga mancanti</h2>
            <div id="mangamancanti-list" class="manga-grid"></div>
        </div>

        <!-- Sezione variant -->
        <div id="variantmancanti" class="section">
            <h2>📖 Variant mancanti</h2>
            <div id="variant-list" class="manga-grid"></div>
        </div>

        <!-- Sezione Dettagli Manga -->
        <div id="details" class="section">
            <button class="back-btn" onclick="showSection('collezioneserie')">← Torna alla Collezione</button>
            <div id="manga-details"></div>
        </div>

        <!-- Sezione Aggiungi Manga -->
        <div id="aggiungi" class="section">
            <h2>➕ Aggiungi Nuovo Manga</h2>
            <div id="add-message"></div>
            <form id="addMangaForm">
                <div class="form-group">
                <label for="type">Tipo:</label>
                    <select class="form-select" required>
                        <option selected>Seleziona il tipo</option>
                        <option value="serie">Serie</option>
                        <option value="variant">Variant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title">Titolo:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="datarilascio">(Variant only) Data rilascio:</label>
                    <input type="text" id="datarilascio" name="datarilascio" required disabled>
                </div>
                <div class="form-group">
                    <label for="costomedio">(Variant only) Costo medio:</label>
                    <input type="text" id="costomedio" name="costomedio" required disabled>
                </div>
                <div class="form-group">
                    <label for="costomedio">(Variant only) Posseduto:</label>
                    <select class="form-select" required disabled>
                        <option selected>Seleziona</option>
                        <option value="si">Si</option>
                        <option value="no">No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">URL Immagine:</label>
                    <input type="url" id="image" name="image" required>
                </div>
                <div class="form-group">
                    <label for="totalVolumes">Volumi Totali:</label>
                    <input type="number" id="totalVolumes" name="totalVolumes" min="1" required>
                </div>
                <div class="form-group">
                    <label>Volumi Posseduti:</label>
                    <div id="volumeCheckboxes" class="checkbox-group"></div>
                </div>
                <button type="submit" class="btn">Aggiungi Manga</button>
            </form>
        </div>

        <!-- Sezione Elimina Manga -->
        <div id="rimuovi" class="section">
            <h2>🗑️ Elimina Manga</h2>
            <div id="delete-message"></div>
            <div id="deleteList" class="delete-list"></div>
        </div>
    </div>

    <!-- Modal di Conferma -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Conferma Eliminazione</h3>
            <p>Sei sicuro di voler eliminare questo manga?</p>
            <div style="margin-top: 20px;">
                <button class="btn btn-danger" onclick="confirmDelete()">Elimina</button>
                <button class="btn" onclick="closeModal()">Annulla</button>
            </div>
        </div>
    </div>
    <script src="../js/codice.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>