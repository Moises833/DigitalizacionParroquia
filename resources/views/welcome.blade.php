<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digitalización Parroquial - Actas de Bautizo</title>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Tema Oscuro por Defecto */
            --bg-color: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --modal-bg: #0f1420;
            --input-bg: rgba(255, 255, 255, 0.04);
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --accent-glow: rgba(59, 130, 246, 0.15);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --table-header-bg: rgba(255, 255, 255, 0.02);
            --table-row-hover: rgba(255, 255, 255, 0.01);
            --detail-row-border: rgba(255, 255, 255, 0.03);
            --detail-notes-bg: rgba(255, 255, 255, 0.02);
            --drag-zone-bg: rgba(255, 255, 255, 0.01);
            --toast-bg: #1e293b;
        }

        /* Tema Claro Opcional */
        body.light-mode {
            --bg-color: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(0, 0, 0, 0.06);
            --modal-bg: #ffffff;
            --input-bg: #ffffff;
            --accent-glow: rgba(59, 130, 246, 0.08);
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --table-header-bg: rgba(0, 0, 0, 0.02);
            --table-row-hover: rgba(0, 0, 0, 0.01);
            --detail-row-border: rgba(0, 0, 0, 0.04);
            --detail-notes-bg: #f1f5f9;
            --drag-zone-bg: #f8fafc;
            --toast-bg: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            scrollbar-width: thin;
            scrollbar-color: var(--accent-color) var(--bg-color);
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: 50px;
            transition: background-color 0.3s ease, color 0.3s ease, background-image 0.3s ease;
        }

        body.light-mode {
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.06) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.03) 0px, transparent 50%);
        }

        header {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--card-border);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section svg {
            width: 42px;
            height: 42px;
            fill: var(--accent-color);
            filter: drop-shadow(0 0 8px var(--accent-glow));
        }

        .logo-title h1 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-title span {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: block;
            margin-top: 2px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success-color);
            border-radius: 50%;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }

        main {
            max-width: 1400px;
            margin: 30px auto 0;
            padding: 0 20px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--border-radius);
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(12px);
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .metric-info h3 {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .metric-info p {
            font-size: 2rem;
            font-weight: 700;
        }

        .metric-icon {
            background: var(--detail-notes-bg);
            border-radius: 50%;
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--card-border);
        }

        .metric-icon svg {
            width: 26px;
            height: 26px;
            fill: var(--text-secondary);
        }

        /* Search Section */
        .search-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--border-radius);
            padding: 25px;
            backdrop-filter: blur(12px);
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 12px 16px;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition);
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: none;
        }

        body.light-mode .btn-secondary:hover {
            background: rgba(0, 0, 0, 0.02);
            border-color: rgba(0, 0, 0, 0.15);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        /* Results Table */
        .results-section {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--border-radius);
            overflow: hidden;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
        }

        .results-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-header h2 {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: var(--table-header-bg);
            padding: 16px 24px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--card-border);
        }

        td {
            padding: 16px 24px;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--detail-row-border);
            color: var(--text-primary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--table-row-hover);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        .badge-neutral {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--card-border);
        }

        body.light-mode .badge-neutral {
            background: rgba(0, 0, 0, 0.03);
        }

        .gender-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .gender-M {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        body.light-mode .gender-M {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            border-color: rgba(59, 130, 246, 0.2);
        }

        .gender-F {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        body.light-mode .gender-F {
            background: rgba(244, 63, 94, 0.1);
            color: #be123c;
            border-color: rgba(244, 63, 94, 0.2);
        }

        .action-cell {
            display: flex;
            gap: 8px;
        }

        .no-data {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-secondary);
        }

        .no-data svg {
            width: 64px;
            height: 64px;
            fill: rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        body.light-mode .no-data svg {
            fill: rgba(0, 0, 0, 0.1);
        }

        .pagination-container {
            padding: 15px 25px;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-container {
            background: var(--modal-bg);
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 900px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.95);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--table-header-bg);
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        body.light-mode .modal-close:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: var(--table-header-bg);
        }

        /* Form Layout inside Modal */
        .form-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--accent-color);
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--card-border);
        }

        .form-section-title:first-child {
            margin-top: 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        /* Drag & Drop File Zone */
        .drag-zone {
            border: 2px dashed var(--card-border);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--drag-zone-bg);
            position: relative;
        }

        .drag-zone:hover, .drag-zone.dragover {
            border-color: var(--accent-color);
            background: rgba(59, 130, 246, 0.02);
        }

        .drag-zone svg {
            width: 48px;
            height: 48px;
            fill: var(--text-secondary);
            margin-bottom: 12px;
            transition: var(--transition);
        }

        .drag-zone:hover svg {
            fill: var(--accent-color);
        }

        .drag-zone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .preview-container {
            margin-top: 15px;
            display: none;
            justify-content: center;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid var(--card-border);
        }

        /* Detail Modal layout */
        .detail-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
        }

        .detail-info-block {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--detail-row-border);
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 500;
        }

        .detail-notes {
            background: var(--detail-notes-bg);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--accent-color);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-top: 15px;
        }

        .detail-image-box {
            border: 1px solid var(--card-border);
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            height: 100%;
            min-height: 300px;
        }

        .detail-image-box img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            object-fit: contain;
            cursor: pointer;
            transition: var(--transition);
        }

        .detail-image-box img:hover {
            transform: scale(1.02);
        }

        .no-image-placeholder {
            color: var(--text-secondary);
            text-align: center;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .no-image-placeholder svg {
            width: 48px;
            height: 48px;
            fill: var(--text-secondary);
            opacity: 0.5;
        }

        /* Toasts for feedback */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: var(--toast-bg);
            color: var(--text-primary);
            border-left: 4px solid var(--accent-color);
            padding: 14px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            transform: translateX(120%);
            transition: var(--transition);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success {
            border-left-color: var(--success-color);
        }

        .toast-error {
            border-left-color: var(--danger-color);
        }

        /* Botón de cambio de tema */
        .theme-toggle-btn {
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        body.light-mode .theme-toggle-btn:hover {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(0, 0, 0, 0.15);
        }

        .theme-toggle-btn svg {
            width: 18px;
            height: 18px;
            fill: var(--text-primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .grid-2, .grid-3, .detail-grid {
                grid-template-columns: 1fr;
            }
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-section">
            <svg viewBox="0 0 24 24">
                <path d="M12 2L2 22h9v-6h2v6h9L12 2zm0 4.8L18.4 19H13v-5h-2v5H5.6L12 6.8zm-1 3.2h2v2h-2z"/>
            </svg>
            <div class="logo-title">
                <h1>Digitalización de Bautizos</h1>
                <span>Parroquia Eclesiástica • Sistema de Búsqueda y Registro</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="status-pill">
                <div class="status-dot"></div>
                SQLite Local Activo
            </div>
            
            <!-- Botón de cambio de Tema (Sol/Luna) -->
            <button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar Tema Visual">
                <!-- Icono de Sol (Modo Oscuro -> Cambiar a Claro) -->
                <svg id="theme-sun-icon" viewBox="0 0 24 24" style="display: none;">
                    <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
                </svg>
                <!-- Icono de Luna (Modo Claro -> Cambiar a Oscuro) -->
                <svg id="theme-moon-icon" viewBox="0 0 24 24">
                    <path d="M12.3 22h-.1c-5.4 0-9.8-4.4-9.8-9.8 0-5.4 4.4-9.8 9.8-9.8.5 0 .9.4.9.9 0 .1 0 .2-.1.3-1.1 1.9-.9 4.4.5 6.1 1.7 2.1 4.4 2.5 6.6 1.2.2-.1.4-.1.6 0 .3.2.4.5.3.8-1.2 5.5-6.1 9.3-11.7 9.3V22z"/>
                </svg>
            </button>

            <!-- Botón de Respaldo y Copias de Seguridad -->
            <button class="btn btn-secondary" id="openBackupModal" title="Opciones de Respaldo y Copias de Seguridad" style="display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/>
                </svg>
                <span>Respaldo BD</span>
            </button>

            <button class="btn" id="openRegisterModal">
                <svg width="18" height="18" fill="white" viewBox="0 0 24 24" style="margin-top:-2px">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Registrar Acta
            </button>
        </div>
    </header>

    <main>
        <!-- Métricas -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Total Actas Digitalizadas</h3>
                    <p id="metric-total-actas">0</p>
                </div>
                <div class="metric-icon">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Libros Digitalizados</h3>
                    <p id="metric-total-libros">0</p>
                </div>
                <div class="metric-icon">
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Última Actualización</h3>
                    <p style="font-size: 1.1rem; margin-top: 5px; color: var(--success-color);">Base de Datos Lista</p>
                </div>
                <div class="metric-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>
            </div>
        </section>

        <!-- Filtros de Búsqueda -->
        <section class="search-panel">
            <h3 style="font-size: 1.05rem; font-weight: 600; margin-bottom: 20px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Búsqueda y Filtros Rápidos</h3>
            <div class="search-grid">
                <div class="form-group">
                    <label for="search-bautizado">Bautizado (Nombre o Apellido)</label>
                    <input type="text" id="search-bautizado" class="form-control" placeholder="Ej: Juan Pérez">
                </div>
                <div class="form-group">
                    <label for="search-familiar">Familiar (Padre, Madre o Padrino)</label>
                    <input type="text" id="search-familiar" class="form-control" placeholder="Ej: Pedro Rodríguez">
                </div>
                <div class="form-group">
                    <label for="search-anio">Año de Bautizo</label>
                    <input type="number" id="search-anio" class="form-control" placeholder="Ej: 1998" min="1900" max="2026">
                </div>
                <div class="form-group">
                    <label for="search-libro">Libro Físico (Tomo)</label>
                    <select id="search-libro" class="form-control">
                        <option value="">Todos los tomos</option>
                    </select>
                </div>
                <div>
                    <button class="btn" id="btn-search" style="width: 100%;">
                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24" style="margin-top:-2px"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        Filtrar
                    </button>
                </div>
            </div>
        </section>

        <!-- Resultados -->
        <section class="results-section">
            <div class="results-header">
                <h2>Actas Encontradas</h2>
                <div id="results-count" style="font-size: 0.9rem; color: var(--text-secondary);">Cargando...</div>
            </div>
            
            <div class="table-responsive">
                <table id="actas-table">
                    <thead>
                        <tr>
                            <th>Libro / Pág / Acta</th>
                            <th>Bautizado</th>
                            <th>F. Bautizo</th>
                            <th>Padres</th>
                            <th>Ministro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="actas-tbody">
                        <tr>
                            <td colspan="6" class="no-data">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                <p>Buscando actas registradas...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container" id="pagination">
                <!-- Se poblará dinámicamente -->
            </div>
        </section>
    </main>

    <!-- Modal: Detalle de Acta -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-container" style="max-width: 950px;">
            <div class="modal-header">
                <h3 id="detail-title">Detalles del Acta</h3>
                <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-info-block">
                        <h4 class="form-section-title">Información del Acta</h4>
                        <div class="detail-row"><span class="detail-label">Tomo / Libro:</span><span class="detail-value" id="d-libro">-</span></div>
                        <div class="detail-row"><span class="detail-label">Página / Acta:</span><span class="detail-value" id="d-pag-acta">-</span></div>
                        <div class="detail-row"><span class="detail-label">Fecha de Bautizo:</span><span class="detail-value" id="d-fecha-bautizo">-</span></div>
                        <div class="detail-row"><span class="detail-label">Ministro / Sacerdote:</span><span class="detail-value" id="d-ministro">-</span></div>

                        <h4 class="form-section-title">Datos del Bautizado</h4>
                        <div class="detail-row"><span class="detail-label">Nombre Completo:</span><span class="detail-value" id="d-nombre" style="font-weight: 700; color: var(--accent-color);">-</span></div>
                        <div class="detail-row"><span class="detail-label">Fecha Nacimiento:</span><span class="detail-value" id="d-fecha-nacimiento">-</span></div>
                        <div class="detail-row"><span class="detail-label">Género:</span><span class="detail-value" id="d-genero">-</span></div>

                        <h4 class="form-section-title">Padres y Padrinos</h4>
                        <div class="detail-row"><span class="detail-label">Padre:</span><span class="detail-value" id="d-padre">-</span></div>
                        <div class="detail-row"><span class="detail-label">Madre:</span><span class="detail-value" id="d-madre">-</span></div>
                        <div class="detail-row"><span class="detail-label">Padrino:</span><span class="detail-value" id="d-padrino">-</span></div>
                        <div class="detail-row"><span class="detail-label">Madrina:</span><span class="detail-value" id="d-madrina">-</span></div>

                        <h4 class="form-section-title">Notas Marginales</h4>
                        <div class="detail-notes" id="d-notas">-</div>
                    </div>
                    <div>
                        <h4 class="form-section-title">Página Digitalizada</h4>
                        <div class="detail-image-box" id="detail-image-container">
                            <!-- Se poblará con la imagen o placeholder -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('detailModal')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Respaldo y Restauración -->
    <div class="modal-overlay" id="backupModal">
        <div class="modal-container" style="max-width: 650px;">
            <div class="modal-header">
                <h3>Respaldo y Seguridad de Datos</h3>
                <button class="modal-close" onclick="closeModal('backupModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px;">
                    Protege la información parroquial exportando una copia de la base de datos o restaurando un respaldo previo.
                </p>

                <!-- Opción 1: Descargar Copia de Seguridad -->
                <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; color: var(--accent-color); display: flex; align-items: center; gap: 8px;">
                        📥 Descargar Respaldo Completo (.sqlite)
                    </h4>
                    <p style="font-size: 0.83rem; color: var(--text-secondary); margin: 0 0 12px 0;">
                        Descarga el archivo de base de datos actual con todas las actas, libros y registros. Puedes guardarlo en una pendrive o disco externo.
                    </p>
                    <a href="/api/respaldos/descargar" class="btn" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                        Descargar Base de Datos (.sqlite)
                    </a>
                </div>

                <!-- Opción 2: Exportar a Excel (CSV) -->
                <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; color: var(--success-color); display: flex; align-items: center; gap: 8px;">
                        📊 Exportar a Excel (CSV)
                    </h4>
                    <p style="font-size: 0.83rem; color: var(--text-secondary); margin: 0 0 12px 0;">
                        Exporta todas las actas y relaciones familiares en una hoja de cálculo compatible con Microsoft Excel.
                    </p>
                    <a href="/api/respaldos/exportar-csv" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                        Exportar a Excel (CSV)
                    </a>
                </div>

                <!-- Opción 3: Restaurar Respaldo -->
                <div style="background: var(--bg-primary); border: 1px dashed var(--warning-color); border-radius: 10px; padding: 18px;">
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; color: var(--warning-color); display: flex; align-items: center; gap: 8px;">
                        ⚠️ Restaurar Copia de Seguridad
                    </h4>
                    <p style="font-size: 0.83rem; color: var(--text-secondary); margin: 0 0 12px 0;">
                        Selecciona un archivo <code>.sqlite</code> previamente respaldado para reemplazar la base de datos actual.
                    </p>
                    <form id="restore-form" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
                        <input type="file" id="f-archivo_backup" name="archivo_backup" accept=".sqlite,.db" class="form-control" required style="font-size: 0.85rem;">
                        <button type="submit" class="btn" style="background: var(--warning-color); color: white; white-space: nowrap;">
                            Restaurar
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('backupModal')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Registrar Nueva Acta -->
    <div class="modal-overlay" id="registerModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Registrar Nueva Acta de Bautizo</h3>
                <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
            </div>
            <form id="add-acta-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Banner Asistente IA -->
                    <div class="ai-transcribe-box" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(168, 85, 247, 0.12)); border: 1px solid rgba(168, 85, 247, 0.35); border-radius: 12px; padding: 16px; margin-bottom: 22px; display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                                    ✨
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 0.98rem; font-weight: 700; color: var(--text-primary);">Asistente de Transcripción IA (Letra Cursiva)</h4>
                                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--text-secondary);">Selecciona o arrastra la foto del folio manuscrito para auto-completar los campos con Inteligencia Artificial.</p>
                                </div>
                            </div>
                            <button type="button" class="btn" id="btn-ai-transcribe" style="background: linear-gradient(135deg, #6366f1, #a855f7); border: none; color: white; font-weight: 600; padding: 9px 16px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">
                                <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
                                <span>Auto-Transcribir con IA</span>
                            </button>
                        </div>
                        <div id="ai-status-msg" style="display: none; font-size: 0.85rem; padding: 10px 14px; border-radius: 8px; font-weight: 500;"></div>
                    </div>

                    <!-- Sección 1: Libro -->
                    <h4 class="form-section-title">1. Ubicación en Libro Físico</h4>
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="f-libro_id">Libro Físico (Tomo) *</label>
                            <select id="f-libro_id" name="libro_id" class="form-control" required>
                                <option value="">Seleccione Tomo...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="f-numero_pagina">Número Página *</label>
                            <input type="text" id="f-numero_pagina" name="numero_pagina" class="form-control" required placeholder="Ej: 140">
                        </div>
                        <div class="form-group">
                            <label for="f-numero_acta">Número Acta *</label>
                            <input type="text" id="f-numero_acta" name="numero_acta" class="form-control" required placeholder="Ej: 412">
                        </div>
                    </div>
                    
                    <div class="grid-2" style="margin-top: 15px;">
                        <div class="form-group">
                            <label for="f-fecha_bautizo">Fecha de Bautizo *</label>
                            <input type="date" id="f-fecha_bautizo" name="fecha_bautizo" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="f-ministro">Sacerdote / Ministro que Bautizó *</label>
                            <input type="text" id="f-ministro" name="ministro" class="form-control" required placeholder="Ej: Pbro. Juan Carlos">
                        </div>
                    </div>

                    <!-- Sección 2: Bautizado -->
                    <h4 class="form-section-title">2. Datos del Bautizado</h4>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="f-bautizado-nombres">Nombres *</label>
                            <input type="text" id="f-bautizado-nombres" name="bautizado[nombres]" class="form-control" required placeholder="Ej: Luis Carlos">
                        </div>
                        <div class="form-group">
                            <label for="f-bautizado-apellidos">Apellidos *</label>
                            <input type="text" id="f-bautizado-apellidos" name="bautizado[apellidos]" class="form-control" required placeholder="Ej: Mendoza Silva">
                        </div>
                    </div>
                    
                    <div class="grid-2" style="margin-top: 15px;">
                        <div class="form-group">
                            <label for="f-bautizado-fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="f-bautizado-fecha_nacimiento" name="bautizado[fecha_nacimiento]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="f-bautizado-genero">Género *</label>
                            <select id="f-bautizado-genero" name="bautizado[genero]" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sección 3: Padres -->
                    <h4 class="form-section-title">3. Padres de la Persona</h4>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nombre del Padre</label>
                            <input type="text" name="padre[nombres]" class="form-control" placeholder="Nombres del padre">
                        </div>
                        <div class="form-group">
                            <label>Apellidos del Padre</label>
                            <input type="text" name="padre[apellidos]" class="form-control" placeholder="Apellidos del padre">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px; margin-bottom: 15px;">
                        <label>Cédula del Padre</label>
                        <input type="text" name="padre[cedula]" class="form-control" placeholder="Cédula del padre">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nombre de la Madre</label>
                            <input type="text" name="madre[nombres]" class="form-control" placeholder="Nombres de la madre">
                        </div>
                        <div class="form-group">
                            <label>Apellidos de la Madre</label>
                            <input type="text" name="madre[apellidos]" class="form-control" placeholder="Apellidos de la madre">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Cédula de la Madre</label>
                        <input type="text" name="madre[cedula]" class="form-control" placeholder="Cédula de la madre">
                    </div>

                    <!-- Sección 4: Padrinos -->
                    <h4 class="form-section-title">4. Padrinos</h4>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nombres Padrino</label>
                            <input type="text" name="padrino[nombres]" class="form-control" placeholder="Nombres del padrino">
                        </div>
                        <div class="form-group">
                            <label>Apellidos Padrino</label>
                            <input type="text" name="padrino[apellidos]" class="form-control" placeholder="Apellidos del padrino">
                        </div>
                    </div>
                    
                    <div class="grid-2" style="margin-top: 15px;">
                        <div class="form-group">
                            <label>Nombres Madrina</label>
                            <input type="text" name="madrina[nombres]" class="form-control" placeholder="Nombres de la madrina">
                        </div>
                        <div class="form-group">
                            <label>Apellidos Madrina</label>
                            <input type="text" name="madrina[apellidos]" class="form-control" placeholder="Apellidos de la madrina">
                        </div>
                    </div>

                    <!-- Sección 5: Notas e Imagen -->
                    <h4 class="form-section-title">5. Notas Marginales y Foto</h4>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="f-notas_marginales">Notas Marginales</label>
                        <textarea id="f-notas_marginales" name="notas_marginales" class="form-control" style="height: 100px; resize: vertical;" placeholder="Anotaciones extra del acta de bautizo física..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Foto de la Página del Libro Físico (Compresión a WebP auto al guardar)</label>
                        <div class="drag-zone" id="drag-zone">
                            <svg viewBox="0 0 24 24">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                            </svg>
                            <p style="font-weight: 500;">Arrastra la foto aquí o haz clic para buscar</p>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 5px;">Formatos soportados: JPG, PNG, WEBP (Max: 10MB)</p>
                            <input type="file" id="f-imagen_pagina" name="imagen_pagina" accept="image/*">
                        </div>
                        <div class="preview-container" id="preview-container">
                            <img src="" alt="Vista previa" class="image-preview" id="image-preview">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('registerModal')">Cancelar</button>
                    <button type="submit" class="btn">
                        Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contenedor de Alertas Toast -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        // Variables globales
        let allLibros = [];

        // Inicializador de eventos
        document.addEventListener('DOMContentLoaded', () => {
            fetchLibros();
            fetchActas();

            // Configuración de eventos de los modales y búsqueda en tiempo real
            document.getElementById('openRegisterModal').addEventListener('click', () => openModal('registerModal'));
            document.getElementById('openBackupModal').addEventListener('click', () => openModal('backupModal'));
            document.getElementById('btn-search').addEventListener('click', () => fetchActas());
            document.getElementById('btn-ai-transcribe').addEventListener('click', handleAITranscription);
            document.getElementById('restore-form').addEventListener('submit', handleRestoreSubmit);

            // Búsqueda Reactiva en Tiempo Real (Debounce 300ms)
            const debouncedSearch = debounce(() => fetchActas(), 300);
            document.getElementById('search-bautizado').addEventListener('input', debouncedSearch);
            document.getElementById('search-familiar').addEventListener('input', debouncedSearch);
            document.getElementById('search-anio').addEventListener('input', debouncedSearch);
            document.getElementById('search-libro').addEventListener('change', () => fetchActas());

            // Drag and Drop Zone handler
            const fileInput = document.getElementById('f-imagen_pagina');
            const dragZone = document.getElementById('drag-zone');
            const previewContainer = document.getElementById('preview-container');
            const imagePreview = document.getElementById('image-preview');

            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        imagePreview.src = evt.target.result;
                        previewContainer.style.display = 'flex';
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            // Form Submit Handler
            const form = document.getElementById('add-acta-form');
            form.addEventListener('submit', handleFormSubmit);

            // Theme Toggle Handler
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const sunIcon = document.getElementById('theme-sun-icon');
            const moonIcon = document.getElementById('theme-moon-icon');

            // Cargar preferencia guardada en localStorage
            if (localStorage.getItem('theme') === 'light') {
                document.body.classList.add('light-mode');
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }

            themeToggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('light-mode');
                
                if (document.body.classList.contains('light-mode')) {
                    localStorage.setItem('theme', 'light');
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                    showToast('Modo claro activado.');
                } else {
                    localStorage.setItem('theme', 'dark');
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                    showToast('Modo oscuro activado.');
                }
            });
        });

        // Modales functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        // Modales close helper
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if(modalId === 'registerModal') {
                document.getElementById('add-acta-form').reset();
                document.getElementById('preview-container').style.display = 'none';
                document.getElementById('image-preview').src = '';
            }
        }

        // Mostrar Notificación Toast
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 
                '<svg width="18" height="18" fill="#10b981" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>' :
                '<svg width="18" height="18" fill="#ef4444" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';

            toast.innerHTML = `${icon} <span>${message}</span>`;
            container.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 50);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Consultar libros físicos desde la API
        function fetchLibros() {
            fetch('/api/libros')
                .then(res => res.json())
                .then(data => {
                    allLibros = data;
                    const searchSelect = document.getElementById('search-libro');
                    const formSelect = document.getElementById('f-libro_id');
                    
                    searchSelect.innerHTML = '<option value="">Todos los tomos</option>';
                    formSelect.innerHTML = '<option value="">Seleccione Tomo...</option>';

                    data.forEach(libro => {
                        const optionText = libro.numero_libro;
                        
                        const searchOpt = document.createElement('option');
                        searchOpt.value = optionText;
                        searchOpt.textContent = optionText;
                        searchSelect.appendChild(searchOpt);

                        const formOpt = document.createElement('option');
                        formOpt.value = libro.id;
                        formOpt.textContent = optionText;
                        formSelect.appendChild(formOpt);
                    });
                })
                .catch(err => {
                    console.error('Error al cargar libros:', err);
                    showToast('Error al conectar con la base de datos local SQLite.', 'error');
                });
        }

        // Consultar actas con filtros y paginación
        function fetchActas(pageUrl = '/api/actas') {
            const countLabel = document.getElementById('results-count');
            countLabel.textContent = 'Buscando...';

            const bautizado = document.getElementById('search-bautizado').value;
            const familiar = document.getElementById('search-familiar').value;
            const anio = document.getElementById('search-anio').value;
            const libro = document.getElementById('search-libro').value;

            const urlObj = new URL(pageUrl, window.location.origin);
            if (bautizado) urlObj.searchParams.append('bautizado', bautizado);
            if (familiar) urlObj.searchParams.append('familiar', familiar);
            if (anio) urlObj.searchParams.append('anio', anio);
            if (libro) urlObj.searchParams.append('libro', libro);

            fetch(urlObj)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        renderActas(res.data.data);
                        renderPagination(res.data);
                        
                        document.getElementById('metric-total-actas').textContent = res.data.total;
                        countLabel.textContent = `${res.data.total} acta(s) encontradas`;
                        document.getElementById('metric-total-libros').textContent = allLibros.length;
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error de red al consultar las actas de bautizo.', 'error');
                });
        }

        // Renderizar las actas en la tabla
        function renderActas(actas) {
            const tbody = document.getElementById('actas-tbody');
            tbody.innerHTML = '';

            if (actas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="no-data">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            <p>No se encontraron actas de bautizo que coincidan con los filtros aplicados.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            actas.forEach(acta => {
                const tr = document.createElement('tr');
                
                let padres = '-';
                if (acta.padre && acta.madre) {
                    padres = `${acta.padre.nombres} y ${acta.madre.nombres}`;
                } else if (acta.padre) {
                    padres = `Padre: ${acta.padre.nombres}`;
                } else if (acta.madre) {
                    padres = `Madre: ${acta.madre.nombres}`;
                }

                const generoSigno = acta.bautizado.genero === 'M' ? 'M' : 'F';

                tr.innerHTML = `
                    <td>
                        <span class="badge badge-info">${acta.libro ? acta.libro.numero_libro.split(' ')[1] : 'Libro'}</span>
                        <span class="badge badge-neutral">Pág. ${acta.numero_pagina}</span>
                        <span class="badge badge-neutral">Acta ${acta.numero_acta}</span>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="gender-icon gender-${acta.bautizado.genero}">${generoSigno}</span>
                            <strong>${acta.bautizado.nombres} ${acta.bautizado.apellidos}</strong>
                        </div>
                    </td>
                    <td>${formatDate(acta.fecha_bautizo)}</td>
                    <td style="font-size:0.85rem; color:var(--text-secondary); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${padres}
                    </td>
                    <td>${acta.ministro}</td>
                    <td class="action-cell">
                        <button class="btn btn-secondary btn-sm" onclick="showActaDetail(${JSON.stringify(acta).replace(/"/g, '&quot;')})">Ver Detalle</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Renderizar controles de paginación
        function renderPagination(meta) {
            const container = document.getElementById('pagination');
            container.innerHTML = '';

            if (!meta.links || meta.links.length <= 3) {
                return;
            }

            const infoText = document.createElement('div');
            infoText.textContent = `Mostrando registros del ${meta.from || 0} al ${meta.to || 0} (Total: ${meta.total})`;
            container.appendChild(infoText);

            const buttonsContainer = document.createElement('div');
            buttonsContainer.style.display = 'flex';
            buttonsContainer.style.gap = '5px';

            meta.links.forEach(link => {
                const btn = document.createElement('button');
                btn.className = `btn btn-secondary btn-sm ${link.active ? 'active' : ''}`;
                btn.style.padding = '6px 12px';
                
                let label = link.label;
                if (label.includes('Previous')) {
                    label = '« Ant';
                } else if (label.includes('Next')) {
                    label = 'Sig »';
                }
                
                btn.innerHTML = label;
                btn.disabled = !link.url;

                if (link.active) {
                    btn.style.background = 'var(--accent-color)';
                    btn.style.borderColor = 'var(--accent-color)';
                    btn.style.color = 'white';
                }

                btn.addEventListener('click', () => {
                    if (link.url) {
                        fetchActas(link.url);
                    }
                });

                buttonsContainer.appendChild(btn);
            });

            container.appendChild(buttonsContainer);
        }

        // Cargar y mostrar modal con el detalle completo del acta
        function showActaDetail(acta) {
            document.getElementById('d-libro').textContent = acta.libro ? acta.libro.numero_libro : '-';
            document.getElementById('d-pag-acta').textContent = `Página ${acta.numero_pagina} • Acta ${acta.numero_acta}`;
            document.getElementById('d-fecha-bautizo').textContent = formatDate(acta.fecha_bautizo);
            document.getElementById('d-ministro').textContent = acta.ministro;
            
            document.getElementById('d-nombre').textContent = `${acta.bautizado.nombres} ${acta.bautizado.apellidos}`;
            document.getElementById('d-fecha-nacimiento').textContent = acta.bautizado.fecha_nacimiento ? formatDate(acta.bautizado.fecha_nacimiento) : '-';
            document.getElementById('d-genero').textContent = acta.bautizado.genero === 'M' ? 'Masculino (M)' : 'Femenino (F)';

            document.getElementById('d-padre').textContent = acta.padre ? `${acta.padre.nombres} ${acta.padre.apellidos} ${acta.padre.cedula ? '(' + acta.padre.cedula + ')' : ''}` : 'Sin registrar';
            document.getElementById('d-madre').textContent = acta.madre ? `${acta.madre.nombres} ${acta.madre.apellidos} ${acta.madre.cedula ? '(' + acta.madre.cedula + ')' : ''}` : 'Sin registrar';
            document.getElementById('d-padrino').textContent = acta.padrino ? `${acta.padrino.nombres} ${acta.padrino.apellidos}` : 'Sin registrar';
            document.getElementById('d-madrina').textContent = acta.madrina ? `${acta.madrina.nombres} ${acta.madrina.apellidos}` : 'Sin registrar';
            
            document.getElementById('d-notas').textContent = acta.notas_marginales || 'Sin notas marginales físicas en este folio.';

            const imgContainer = document.getElementById('detail-image-container');
            imgContainer.innerHTML = '';

            if (acta.imagen_pagina_path) {
                const img = document.createElement('img');
                img.src = `/${acta.imagen_pagina_path}`;
                img.alt = 'Página del Libro';
                img.onclick = () => window.open(img.src, '_blank');
                imgContainer.appendChild(img);
                
                const helpText = document.createElement('div');
                helpText.style.fontSize = '0.75rem';
                helpText.style.color = 'var(--text-secondary)';
                helpText.style.marginTop = '8px';
                helpText.textContent = 'Haz clic sobre la imagen para abrir en tamaño completo.';
                imgContainer.appendChild(helpText);
            } else {
                imgContainer.innerHTML = `
                    <div class="no-image-placeholder">
                        <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        <span>Sin página digitalizada cargada</span>
                    </div>
                `;
            }

            openModal('detailModal');
        }

        // Manejo de envío de formulario para guardar un acta nueva
        function handleFormSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = new FormData(form);

            fetch('/api/actas', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Error en el servidor al guardar el acta.');
                }
                return data;
            })
            .then(data => {
                showToast(data.message || 'Acta de bautizo registrada con éxito.');
                closeModal('registerModal');
                fetchActas();
            })
            .catch(err => {
                console.error(err);
                showToast(err.message || 'Error al intentar guardar el acta. Por favor valida los datos.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar Registro';
            });
        }

        // Manejar restauración de respaldo de base de datos
        function handleRestoreSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');

            if (!confirm('⚠️ ¿Estás seguro de que deseas restaurar la base de datos? Se reemplazará la información actual por la copia del archivo seleccionado.')) {
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Restaurando...';

            const formData = new FormData(form);

            fetch('/api/respaldos/restaurar', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error al restaurar.');
                showToast(data.message || 'Base de datos restaurada con éxito.');
                closeModal('backupModal');
                fetchActas();
            })
            .catch(err => {
                console.error(err);
                showToast(err.message || 'Error al restaurar la base de datos.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Restaurar';
            });
        }

        // Manejo de Transcripción con IA de imágenes manuscritas
        function handleAITranscription() {
            const fileInput = document.getElementById('f-imagen_pagina');
            const btn = document.getElementById('btn-ai-transcribe');
            const statusMsg = document.getElementById('ai-status-msg');

            if (!fileInput.files || !fileInput.files[0]) {
                showToast('Por favor selecciona o arrastra primero la imagen de la página en la sección 5.', 'error');
                const dragZone = document.getElementById('drag-zone');
                if (dragZone) {
                    dragZone.scrollIntoView({ behavior: 'smooth' });
                    dragZone.style.borderColor = 'var(--accent-color)';
                    setTimeout(() => dragZone.style.borderColor = '', 2000);
                }
                return;
            }

            const formData = new FormData();
            formData.append('imagen_pagina', fileInput.files[0]);

            btn.disabled = true;
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg>
                <span>Analizando caligrafía...</span>
            `;

            statusMsg.style.display = 'block';
            statusMsg.style.background = 'rgba(99, 102, 241, 0.15)';
            statusMsg.style.color = '#818cf8';
            statusMsg.textContent = '🧠 Analizando trazos de letra cursiva manuscrita con Visión por IA...';

            fetch('/api/actas/transcribir-imagen', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            })
            .then(res => res.json())
            .then(resData => {
                if (!resData.success || !resData.data) {
                    throw new Error(resData.message || 'No se logró transcribir la imagen.');
                }

                const d = resData.data;

                // Auto-llenar campos del formulario
                if (d.numero_pagina) document.getElementById('f-numero_pagina').value = d.numero_pagina;
                if (d.numero_acta) document.getElementById('f-numero_acta').value = d.numero_acta;
                if (d.fecha_bautizo) document.getElementById('f-fecha_bautizo').value = d.fecha_bautizo;
                if (d.ministro) document.getElementById('f-ministro').value = d.ministro;

                if (d.bautizado) {
                    if (d.bautizado.nombres) document.getElementById('f-bautizado-nombres').value = d.bautizado.nombres;
                    if (d.bautizado.apellidos) document.getElementById('f-bautizado-apellidos').value = d.bautizado.apellidos;
                    if (d.bautizado.fecha_nacimiento) document.getElementById('f-bautizado-fecha_nacimiento').value = d.bautizado.fecha_nacimiento;
                    if (d.bautizado.genero) document.getElementById('f-bautizado-genero').value = d.bautizado.genero;
                }

                if (d.padre) {
                    if (d.padre.nombres) document.querySelector('input[name="padre[nombres]"]').value = d.padre.nombres;
                    if (d.padre.apellidos) document.querySelector('input[name="padre[apellidos]"]').value = d.padre.apellidos;
                    if (d.padre.cedula) document.querySelector('input[name="padre[cedula]"]').value = d.padre.cedula;
                }

                if (d.madre) {
                    if (d.madre.nombres) document.querySelector('input[name="madre[nombres]"]').value = d.madre.nombres;
                    if (d.madre.apellidos) document.querySelector('input[name="madre[apellidos]"]').value = d.madre.apellidos;
                    if (d.madre.cedula) document.querySelector('input[name="madre[cedula]"]').value = d.madre.cedula;
                }

                if (d.padrino) {
                    if (d.padrino.nombres) document.querySelector('input[name="padrino[nombres]"]').value = d.padrino.nombres;
                    if (d.padrino.apellidos) document.querySelector('input[name="padrino[apellidos]"]').value = d.padrino.apellidos;
                }

                if (d.madrina) {
                    if (d.madrina.nombres) document.querySelector('input[name="madrina[nombres]"]').value = d.madrina.nombres;
                    if (d.madrina.apellidos) document.querySelector('input[name="madrina[apellidos]"]').value = d.madrina.apellidos;
                }

                if (d.notas_marginales) {
                    const notasInput = document.querySelector('textarea[name="notas_marginales"]');
                    if (notasInput) notasInput.value = d.notas_marginales;
                }

                statusMsg.style.background = 'rgba(34, 197, 94, 0.15)';
                statusMsg.style.color = '#4ade80';
                statusMsg.textContent = '✨ ¡Campos auto-completados por IA con éxito! Revisa la información extraída antes de guardar.';
                
                showToast('Transcripción completada por IA. Revisa y confirma los campos.');
            })
            .catch(err => {
                console.error(err);
                statusMsg.style.background = 'rgba(239, 68, 68, 0.15)';
                statusMsg.style.color = '#f87171';
                statusMsg.textContent = '⚠️ Error en transcripción: ' + (err.message || 'No se pudo procesar la imagen.');
                showToast('Error al transcribir la imagen.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
                    <span>Auto-Transcribir con IA</span>
                `;
            });
        }

        // Helper Debounce para evitar sobrecargar la base de datos mientras el usuario escribe
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Helpers de Formateo
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    </script>
</body>
</html>
