<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Escanear Folio - Parroquia Móvil</title>
    <style>
        :root {
            --primary: #1e3a8a;
            --primary-accent: #2563eb;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #22c55e;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 16px;
        }

        header {
            text-align: center;
            padding: 12px 0 20px 0;
        }

        header h1 {
            font-size: 1.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        header p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .camera-trigger {
            border: 2px dashed #475569;
            border-radius: 12px;
            padding: 30px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.4);
        }

        .camera-trigger:active {
            transform: scale(0.98);
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--primary-accent);
        }

        .camera-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, var(--primary-accent), #a855f7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .camera-icon svg {
            width: 28px;
            height: 28px;
            fill: white;
        }

        .preview-box {
            display: none;
            width: 100%;
            max-height: 350px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--primary-accent);
            position: relative;
        }

        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-accent), #3b82f6);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .status-banner {
            display: none;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .status-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid var(--success);
            color: #4ade80;
        }

        .status-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger);
            color: #f87171;
        }

        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>

    <header>
        <h1>📱 Escáner Móvil Parroquial</h1>
        <p>Toma la foto del libro de bautizos y envíala en tiempo real a la computadora.</p>
    </header>

    <div class="card">
        <!-- Opción 1: Tomar Foto en Vivo con Cámara -->
        <label for="mobile-camera-input" class="camera-trigger">
            <div class="camera-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                </svg>
            </div>
            <strong style="font-size: 1.05rem; display: block; margin-bottom: 4px;">Tomar Foto Ahora (Cámara)</strong>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Abre la cámara del teléfono para capturar en vivo</span>
        </label>
        <input type="file" id="mobile-camera-input" accept="image/*" capture="environment">

        <!-- Opción 2: Elegir de la Galería de Fotos (Tomada Anteriormente) -->
        <label for="mobile-gallery-input" class="camera-trigger" style="border-style: solid; border-color: #334155; background: rgba(30, 41, 59, 0.6);">
            <div class="camera-icon" style="background: linear-gradient(135deg, #059669, #10b981);">
                <svg viewBox="0 0 24 24">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
            </div>
            <strong style="font-size: 1.05rem; display: block; margin-bottom: 4px;">Elegir de la Galería de Fotos</strong>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Selecciona una foto tomada previamente en tus álbumes</span>
        </label>
        <input type="file" id="mobile-gallery-input" accept="image/*">

        <div class="preview-box" id="preview-box">
            <img id="mobile-preview-img" src="" alt="Vista previa">
        </div>

        <button type="button" class="btn" id="btn-send-to-pc" style="display: none;">
            <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
            <span>Enviar a la Computadora</span>
        </button>

        <div id="status-banner" class="status-banner"></div>
    </div>

    <script>
        const cameraInput = document.getElementById('mobile-camera-input');
        const galleryInput = document.getElementById('mobile-gallery-input');
        const previewBox = document.getElementById('preview-box');
        const previewImg = document.getElementById('mobile-preview-img');
        const sendBtn = document.getElementById('btn-send-to-pc');
        const statusBanner = document.getElementById('status-banner');

        let selectedFile = null;

        function handleFileSelection(file) {
            if (!file) return;
            selectedFile = file;
            const reader = new FileReader();

            reader.onload = function(evt) {
                previewImg.src = evt.target.result;
                previewBox.style.display = 'block';
                sendBtn.style.display = 'flex';
                statusBanner.style.display = 'none';
            };

            reader.readAsDataURL(file);
        }

        cameraInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleFileSelection(e.target.files[0]);
            }
        });

        galleryInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleFileSelection(e.target.files[0]);
            }
        });

        sendBtn.addEventListener('click', () => {
            if (!selectedFile) return;

            const formData = new FormData();
            formData.append('imagen_pagina', selectedFile);

            sendBtn.disabled = true;
            sendBtn.textContent = 'Enviando foto...';

            fetch('/api/movil/upload', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error al enviar la imagen.');

                statusBanner.className = 'status-banner status-success';
                statusBanner.style.display = 'block';
                statusBanner.innerHTML = '✨ <strong>¡Foto enviada con éxito!</strong><br>Ya apareció en la pantalla de la computadora.';

                sendBtn.style.display = 'none';
            })
            .catch(err => {
                console.error(err);
                statusBanner.className = 'status-banner status-error';
                statusBanner.style.display = 'block';
                statusBanner.textContent = '⚠️ Error al enviar: ' + (err.message || 'Verifica la conexión Wi-Fi.');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Enviar a la Computadora';
            });
        });
    </script>
</body>
</html>
