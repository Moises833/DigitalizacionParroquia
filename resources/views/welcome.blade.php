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

        /* Panel de Recorte y Selección de Párrafo Interactivo */
        .crop-editor-wrapper {
            margin-top: 15px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .crop-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--card-border);
        }

        .crop-preset-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .crop-preset-btn {
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .crop-preset-btn:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
            background: rgba(99, 102, 241, 0.08);
        }

        .crop-preset-btn.active {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .crop-container-outer {
            width: 100%;
            display: flex;
            justify-content: center;
            background: #090d16;
            border-radius: 10px;
            border: 1px solid var(--card-border);
            padding: 10px;
            overflow: hidden;
        }

        .crop-container {
            position: relative;
            display: inline-block;
            user-select: none;
            touch-action: none;
            line-height: 0;
            max-width: 100%;
        }

        .crop-image {
            max-width: 100%;
            max-height: 520px;
            display: block;
            object-fit: contain;
            pointer-events: none;
            border-radius: 4px;
        }

        .crop-box {
            position: absolute;
            box-shadow: 0 0 0 9999px rgba(10, 15, 30, 0.65);
            border: 2px dashed #818cf8;
            cursor: move;
            box-sizing: border-box;
            line-height: normal;
            touch-action: none;
            transition: border-color 0.15s;
        }

        .crop-box:hover {
            border-color: #a855f7;
        }

        .crop-badge {
            position: absolute;
            top: 4px;
            left: 4px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            pointer-events: none;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            white-space: nowrap;
        }

        /* 8 Handles de Redimensionamiento */
        .crop-handle {
            position: absolute;
            width: 12px;
            height: 12px;
            background: #ffffff;
            border: 2px solid #6366f1;
            border-radius: 3px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.4);
            box-sizing: border-box;
            z-index: 10;
        }

        .crop-handle.handle-nw { top: -6px; left: -6px; cursor: nwse-resize; }
        .crop-handle.handle-n  { top: -6px; left: calc(50% - 6px); cursor: ns-resize; }
        .crop-handle.handle-ne { top: -6px; right: -6px; cursor: nesw-resize; }
        .crop-handle.handle-e  { top: calc(50% - 6px); right: -6px; cursor: ew-resize; }
        .crop-handle.handle-se { bottom: -6px; right: -6px; cursor: nwse-resize; }
        .crop-handle.handle-s  { bottom: -6px; left: calc(50% - 6px); cursor: ns-resize; }
        .crop-handle.handle-sw { bottom: -6px; left: -6px; cursor: nesw-resize; }
        .crop-handle.handle-w  { top: calc(50% - 6px); left: -6px; cursor: ew-resize; }

        /* Guías visuales de párrafos en la página */
        .crop-guide-slot {
            position: absolute;
            border: 2px dashed rgba(255, 255, 255, 0.35);
            background: rgba(99, 102, 241, 0.05);
            box-sizing: border-box;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 4px;
            z-index: 5;
        }

        .crop-guide-slot:hover {
            border-color: #a855f7;
            background: rgba(168, 85, 247, 0.15);
        }

        .crop-guide-slot.is-active {
            display: none;
        }

        .crop-guide-slot.is-saved {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.12);
        }

        .guide-slot-tag {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(15, 23, 42, 0.85);
            color: #94a3b8;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            letter-spacing: 0.3px;
        }

        .crop-guide-slot.is-saved .guide-slot-tag {
            background: #16a34a;
            color: #ffffff;
            border-color: #22c55e;
        }

        /* Indicador de progreso de la página */
        .crop-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.35);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .crop-progress-pill.completed {
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.4);
        }

        .slot-pill {
            font-size: 0.68rem;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 5px;
            background: rgba(255, 255, 255, 0.15);
            font-weight: 600;
        }

        .slot-pill.saved {
            background: #16a34a;
            color: #ffffff;
        }

        /* Banner de acción tras guardar un registro */
        .multi-record-banner {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.98));
            border: 1.5px solid #6366f1;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            animation: fadeInDown 0.3s ease;
        }

        body.light-mode .multi-record-banner {
            background: linear-gradient(135deg, #f8fafc, #edf2f7);
            border-color: #6366f1;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
        }

        .multi-record-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .multi-record-icon {
            font-size: 1.8rem;
            width: 44px;
            height: 44px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .multi-record-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        .crop-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--card-border);
        }

        .crop-preview-mini {
            display: flex;
            align-items: center;
            gap: 12px;
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

        /* Estilos de Impresión para Certificado */
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-certificate, #printable-certificate * {
                visibility: visible;
            }
            #printable-certificate {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                border: 4px double #1e3a8a !important;
                background: white !important;
                color: #0f172a !important;
                padding: 40px !important;
                box-sizing: border-box;
                z-index: 99999;
            }
            .modal-overlay, .modal-container {
                background: none !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
            .modal-header, .modal-footer, header, main, .toast-container {
                display: none !important;
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

            <!-- Botón de Configuración de IA Gemini -->
            <button class="btn btn-secondary" id="openAIKeyModal" title="Configurar Clave de Inteligencia Artificial (Gemini Vision)" style="display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5 2.5 2.5 0 0 0-2.5-2.5z"/>
                </svg>
                <span>IA Gemini</span>
                <span id="nav-ai-status-badge" style="font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; background: rgba(234, 179, 8, 0.2); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.4);">Demo</span>
            </button>

            <!-- Botón de Conexión Móvil QR -->
            <button class="btn btn-secondary" id="openQRModal" title="Vincular teléfono móvil mediante código QR" style="display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm13 0h3v3h-3v-3zm-3 3h3v3h-3v-3zm3 3h3v3h-3v-3zm-3-6h3v3h-3v-3z"/>
                </svg>
                <span>📱 Vincular Teléfono</span>
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
                        <div class="detail-row"><span class="detail-label">Lugar de Nacimiento:</span><span class="detail-value" id="d-lugar-nacimiento">-</span></div>
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
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <button class="btn btn-secondary" onclick="closeModal('detailModal')">Cerrar</button>
                <button class="btn" onclick="openCertificateModal()" style="background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24">
                        <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                    </svg>
                    <span>Imprimir Certificado</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Certificado de Bautismo (Vista previa e impresión) -->
    <div class="modal-overlay" id="certificateModal">
        <div class="modal-container" style="max-width: 800px; padding: 25px; background: white; color: #1e293b;">
            <div class="modal-header" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 15px;">
                <h3 style="color: #0f172a;">Certificado Oficial de Bautismo</h3>
                <button class="modal-close" onclick="closeModal('certificateModal')" style="color: #64748b;">&times;</button>
            </div>
            
            <!-- Documento Imprimible -->
            <div id="printable-certificate" style="border: 4px double #1e3a8a; padding: 35px 30px; background: #fafafa; font-family: 'Times New Roman', Times, serif;">
                <!-- Encabezado Parroquial -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="margin: 0; font-size: 1.5rem; text-transform: uppercase; color: #1e3a8a; letter-spacing: 1px; font-weight: bold;">
                        PARROQUIA ECLESIÁSTICA SAN JOSÉ
                    </h2>
                    <p style="margin: 4px 0 0 0; font-size: 0.95rem; color: #475569; font-style: italic;">
                        Diócesis Parroquial • Archivo Histórico de Bautismos
                    </p>
                    <div style="width: 140px; height: 2px; background: #1e3a8a; margin: 12px auto 0 auto;"></div>
                </div>

                <h3 style="text-align: center; text-transform: uppercase; font-size: 1.35rem; letter-spacing: 2px; color: #0f172a; margin: 25px 0; text-decoration: underline;">
                    FE DE BAUTISMO / CERTIFICADO DE BAUTISMO
                </h3>

                <p style="font-size: 1.05rem; line-height: 1.7; text-align: justify; color: #334155; margin-bottom: 20px;">
                    El suscrito Párroco / Secretario Parroquial hace constar que en el <strong><span id="cert-tomo">-</span></strong>, 
                    Folio/Página <strong><span id="cert-pagina">-</span></strong>, Acta Nº <strong><span id="cert-acta">-</span></strong> 
                    de Bautismos de esta Parroquia, se encuentra asentada la partida que a continuación se detalla:
                </p>

                <!-- Cuadro de Datos Formal -->
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 1rem;">
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold; width: 35%;">Nombre del Bautizado:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; font-weight: bold; color: #1e3a8a; font-size: 1.1rem;" id="cert-bautizado">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Fecha de Nacimiento:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-nacimiento">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Lugar de Nacimiento:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-lugar-nacimiento">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Fecha de Bautismo:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; font-weight: bold;" id="cert-bautizo">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Padre:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-padre">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Madre:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-madre">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Padrino:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-padrino">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Madrina:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-madrina">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Ministro / Sacerdote:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1;" id="cert-ministro">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; background: #f1f5f9; font-weight: bold;">Notas Marginales:</td>
                        <td style="padding: 8px 12px; border: 1px solid #cbd5e1; font-style: italic;" id="cert-notas">-</td>
                    </tr>
                </table>

                <p style="font-size: 0.95rem; line-height: 1.6; text-align: justify; color: #475569; margin-top: 25px;">
                    Es copia fiel y exacta tomada de su original a solicitud de la parte interesada. Se expide la presente certificación en la Sede Parroquial el día <strong id="cert-expedicion-fecha">-</strong>.
                </p>

                <!-- Pie con Firma y Sello -->
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 60px; padding: 0 20px;">
                    <div style="text-align: center; width: 40%;">
                        <div style="border-top: 1px solid #0f172a; padding-top: 6px; font-weight: bold; font-size: 0.95rem; color: #0f172a;">
                            PÁRROCO / SECRETARIO
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b;">Firma Autorizada</div>
                    </div>
                    <div style="text-align: center; width: 32%; border: 2px dashed #cbd5e1; padding: 18px 10px; border-radius: 50%; color: #94a3b8; font-size: 0.78rem;">
                        (SELLO PARROQUIAL)
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('certificateModal')">Cerrar</button>
                <button type="button" class="btn" onclick="window.print()" style="background: #1e3a8a; color: white; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                    <span>Imprimir / Guardar PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Conexión Móvil QR -->
    <div class="modal-overlay" id="qrModal">
        <div class="modal-container" style="max-width: 550px; text-align: center;">
            <div class="modal-header">
                <h3>📱 Conectar Teléfono Móvil</h3>
                <button class="modal-close" onclick="closeModal('qrModal')">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <p style="font-size: 0.9rem; color: var(--text-secondary); max-width: 450px;">
                    Escanea este código QR con la cámara de tu teléfono móvil para tomar fotos de las actas y enviarlas en tiempo real a la computadora.
                </p>

                <div id="qr-code-container" style="background: white; padding: 15px; border-radius: 16px; border: 1px solid var(--card-border); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <img id="qr-img" src="" alt="Código QR" style="width: 220px; height: 220px; display: block;">
                </div>

                <div style="font-size: 0.85rem; background: var(--input-bg); padding: 10px 16px; border-radius: 10px; color: var(--accent-color); font-weight: bold; word-break: break-all;" id="qr-url-text">
                    Cargando enlace de red local...
                </div>

                <div style="font-size: 0.8rem; color: var(--text-secondary); text-align: left; background: var(--bg-primary); padding: 14px; border-radius: 10px; border: 1px solid var(--border-color); width: 100%;">
                    <strong style="color: var(--text-primary);">📌 Instrucciones:</strong>
                    <ol style="margin-left: 20px; margin-top: 6px; line-height: 1.6;">
                        <li>Conecta tu teléfono a la misma red Wi-Fi de esta computadora.</li>
                        <li>Abre la cámara del teléfono y escanea el código QR.</li>
                        <li>Toma la foto del folio y presiona <strong>"Enviar a la Computadora"</strong>.</li>
                        <li>La foto aparecerá automáticamente en el formulario de registro.</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('qrModal')">Cerrar</button>
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

    <!-- Modal: Configuración de Clave IA Gemini -->
    <div class="modal-overlay" id="aiKeyModal">
        <div class="modal-container" style="max-width: 580px;">
            <div class="modal-header">
                <h3>Configuración de Inteligencia Artificial (Gemini)</h3>
                <button class="modal-close" onclick="closeModal('aiKeyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 10px; padding: 14px;">
                    <div style="font-size: 1.8rem; line-height: 1;">✨</div>
                    <div style="font-size: 0.88rem; color: var(--text-primary); line-height: 1.5;">
                        <strong>Reconocimiento de Letra Manuscrita con Google Gemini</strong><br>
                        <span style="color: var(--text-secondary); font-size: 0.82rem;">
                            Con tu clave API gratuita de Google Gemini, el sistema transcribirá con precisión exacta los libros de bautizo físicos a partir de las fotos. Sin clave, el sistema funciona en modo demostración.
                        </span>
                    </div>
                </div>

                <div id="ai-status-card" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; font-size: 0.88rem; display: flex; align-items: center; justify-content: space-between; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3);">
                    <span id="ai-status-text">Verificando estado...</span>
                    <span id="ai-status-badge" style="font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: 600;"></span>
                </div>

                <form id="ai-key-form">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="gemini_api_key" style="font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; display: block;">Clave API de Gemini (Google AI Studio)</label>
                        <input type="password" id="gemini_api_key" class="form-control" placeholder="Pega aquí tu clave AIzaSy..." style="font-family: monospace; font-size: 0.9rem;" autocomplete="off">
                        <small style="display: block; margin-top: 6px; color: var(--text-secondary); font-size: 0.78rem;">
                            ¿No tienes una clave? Puedes obtenerla gratis en <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" style="color: var(--accent-color); text-decoration: underline;">Google AI Studio</a>.
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('aiKeyModal')">Cerrar</button>
                        <button type="submit" class="btn" id="btn-save-ai-key" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <span>Guardar Clave</span>
                        </button>
                    </div>
                </form>
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
                    <!-- Banner de Acción Multi-Registro de la Misma Página -->
                    <div id="multi-record-banner" class="multi-record-banner" style="display: none;">
                        <div class="multi-record-content">
                            <div class="multi-record-icon">🎉</div>
                            <div>
                                <h4 id="multi-record-title" style="margin: 0; font-size: 1rem; font-weight: 700; color: #4ade80;">¡Registro Guardado con Éxito!</h4>
                                <p id="multi-record-msg" style="margin: 4px 0 0 0; font-size: 0.83rem; color: var(--text-secondary);">Has guardado 1 de 3 registros de esta página física.</p>
                            </div>
                        </div>
                        <div class="multi-record-actions">
                            <button type="button" class="btn" id="btn-next-paragraph" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                👉 Transcribir Siguiente Registro (2º)
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn-finish-page-modal" style="padding: 8px 14px; cursor: pointer;">
                                Terminar y Cerrar
                            </button>
                        </div>
                    </div>

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
                    
                    <div class="grid-3" style="margin-top: 15px;">
                        <div class="form-group">
                            <label for="f-bautizado-fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" id="f-bautizado-fecha_nacimiento" name="bautizado[fecha_nacimiento]" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="f-bautizado-lugar_nacimiento">Lugar de Nacimiento</label>
                            <input type="text" id="f-bautizado-lugar_nacimiento" name="bautizado[lugar_nacimiento]" class="form-control" placeholder="Ej: Caracas, Parroquia San Juan">
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
                        <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Foto de la Página del Libro Físico</span>
                            <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: normal;">Compresión automática WebP al guardar</span>
                        </label>
                        
                        <div class="drag-zone" id="drag-zone">
                            <svg viewBox="0 0 24 24">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                            </svg>
                            <p style="font-weight: 500;">Arrastra la foto aquí o haz clic para buscar</p>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 5px;">Formatos soportados: JPG, PNG, WEBP (Max: 10MB) • O envíala por QR desde el celular</p>
                            <input type="file" id="f-imagen_pagina" name="imagen_pagina" accept="image/*">
                        </div>

                        <!-- Editor de Recorte y Selección de Párrafo (1 de los 3 registros) -->
                        <div class="crop-editor-wrapper" id="crop-editor-wrapper" style="display: none;">
                            <div class="crop-toolbar">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2v14a2 2 0 0 0 2 2h14M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                                        Registros en la Página:
                                    </span>
                                    <div class="crop-progress-pill" id="crop-page-progress">
                                        <span id="crop-page-status-text">Página: 0 de 3 guardados</span>
                                    </div>
                                </div>
                                <div class="crop-preset-group">
                                    <button type="button" class="crop-preset-btn active" id="btn-crop-p1" title="Seleccionar el 1º tercio superior de la página">
                                        <span>1º Registro (Arriba)</span>
                                        <span class="slot-pill" id="pill-p1">Pendiente</span>
                                    </button>
                                    <button type="button" class="crop-preset-btn" id="btn-crop-p2" title="Seleccionar el 2º tercio medio de la página">
                                        <span>2º Registro (Medio)</span>
                                        <span class="slot-pill" id="pill-p2">Pendiente</span>
                                    </button>
                                    <button type="button" class="crop-preset-btn" id="btn-crop-p3" title="Seleccionar el 3º tercio inferior de la página">
                                        <span>3º Registro (Abajo)</span>
                                        <span class="slot-pill" id="pill-p3">Pendiente</span>
                                    </button>
                                    <button type="button" class="crop-preset-btn" id="btn-crop-full" title="Seleccionar toda la página completa">
                                        <span>Página Completa</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="btn-cambiar-foto" style="margin-left: 6px; padding: 5px 10px; font-size: 0.78rem;">
                                        Cambiar Foto
                                    </button>
                                </div>
                            </div>

                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0 0 10px 0;">
                                💡 <em>Cada página contiene hasta 3 registros. Haz clic en las guías o en los botones para seleccionar cada párrafo, transcribirlo con IA y guardarlo por separado sin perder la imagen.</em>
                            </p>

                            <!-- Contenedor con Recuadro Interactivo y Guías de los 3 Párrafos -->
                            <div class="crop-container-outer">
                                <div class="crop-container" id="crop-container">
                                    <img src="" alt="Folio del Libro" class="crop-image" id="image-crop-source">
                                    
                                    <!-- Guías visuales interactivas para cada uno de los 3 registros -->
                                    <div class="crop-guide-slot" id="guide-slot-1" data-slot="1" title="Clic para seleccionar el 1º Registro (Arriba)">
                                        <span class="guide-slot-tag" id="guide-tag-1">1º Registro</span>
                                    </div>
                                    <div class="crop-guide-slot" id="guide-slot-2" data-slot="2" title="Clic para seleccionar el 2º Registro (Medio)">
                                        <span class="guide-slot-tag" id="guide-tag-2">2º Registro</span>
                                    </div>
                                    <div class="crop-guide-slot" id="guide-slot-3" data-slot="3" title="Clic para seleccionar el 3º Registro (Abajo)">
                                        <span class="guide-slot-tag" id="guide-tag-3">3º Registro</span>
                                    </div>

                                    <!-- Recuadro interactivo activo con 8 puntos de ajuste -->
                                    <div class="crop-box" id="crop-box">
                                        <span class="crop-badge" id="crop-badge-text">1º Registro Seleccionado</span>
                                        <div class="crop-handle handle-nw" data-handle="nw"></div>
                                        <div class="crop-handle handle-n"  data-handle="n"></div>
                                        <div class="crop-handle handle-ne" data-handle="ne"></div>
                                        <div class="crop-handle handle-e"  data-handle="e"></div>
                                        <div class="crop-handle handle-se" data-handle="se"></div>
                                        <div class="crop-handle handle-s"  data-handle="s"></div>
                                        <div class="crop-handle handle-sw" data-handle="sw"></div>
                                        <div class="crop-handle handle-w"  data-handle="w"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pie del Editor: Miniatura de Recorte y Botón de Transcripción -->
                            <div class="crop-footer">
                                <div class="crop-preview-mini">
                                    <canvas id="crop-live-thumb" width="130" height="75" style="border: 1px solid var(--card-border); border-radius: 6px; background: #0f172a; object-fit: contain;"></canvas>
                                    <div>
                                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-primary);" id="crop-preview-label">Párrafo a procesar por IA (1º)</div>
                                        <label style="display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: var(--text-secondary); margin-top: 4px; cursor: pointer;">
                                            <input type="checkbox" id="f-guardar-solo-recorte" checked style="cursor: pointer;">
                                            <span>Guardar este recorte como la foto oficial del acta</span>
                                        </label>
                                    </div>
                                </div>

                                <button type="button" class="btn" id="btn-crop-transcribe" style="background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);">
                                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
                                    <span id="btn-crop-transcribe-text">Transcribir 1º Registro con IA</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 0.82rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                        📄 <span id="footer-status-text">Una sola foto permite registrar hasta 3 bautizos.</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('registerModal')">Cancelar</button>
                        <button type="submit" class="btn btn-secondary" id="btn-save-record-only" title="Guardar este registro en el sistema">
                            Guardar Registro
                        </button>
                        <button type="button" class="btn" id="btn-save-and-advance" style="background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35); border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer;" title="Guardar este registro y pasar directamente al siguiente párrafo de la página">
                            <span id="btn-advance-text">Guardar y Pasar al 2º Registro</span>
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M5 13h11.86l-5.43 5.43 1.42 1.42L21.14 12l-8.29-8.29-1.42 1.42L16.86 11H5v2z"/></svg>
                        </button>
                    </div>
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
            document.getElementById('openQRModal').addEventListener('click', showQRModal);
            document.getElementById('openAIKeyModal').addEventListener('click', () => openModal('aiKeyModal'));
            document.getElementById('ai-key-form').addEventListener('submit', handleSaveAiKey);
            document.getElementById('btn-search').addEventListener('click', () => fetchActas());
            document.getElementById('btn-ai-transcribe').addEventListener('click', handleAITranscription);
            document.getElementById('restore-form').addEventListener('submit', handleRestoreSubmit);

            // Verificar estado de la API de IA Gemini
            checkAiStatus();

            // Iniciar sincronización en segundo plano para recibir fotos del teléfono
            startMobilePhotoPolling();

            // Búsqueda Reactiva en Tiempo Real (Debounce 300ms)
            const debouncedSearch = debounce(() => fetchActas(), 300);
            document.getElementById('search-bautizado').addEventListener('input', debouncedSearch);
            document.getElementById('search-familiar').addEventListener('input', debouncedSearch);
            document.getElementById('search-anio').addEventListener('input', debouncedSearch);
            document.getElementById('search-libro').addEventListener('change', () => fetchActas());

            // Inicializar eventos del Editor de Recorte de Párrafo
            initCropEditorEvents();

            // Drag and Drop Zone handler
            const fileInput = document.getElementById('f-imagen_pagina');
            const dragZone = document.getElementById('drag-zone');

            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    loadCropImage(e.target.files[0]);
                }
            });

            dragZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dragZone.classList.add('dragover');
            });
            dragZone.addEventListener('dragleave', () => {
                dragZone.classList.remove('dragover');
            });
            dragZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dragZone.classList.remove('dragover');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    loadCropImage(e.dataTransfer.files[0]);
                }
            });

            const btnCambiar = document.getElementById('btn-cambiar-foto');
            if (btnCambiar) {
                btnCambiar.addEventListener('click', () => {
                    fileInput.value = '';
                    fileInput.click();
                });
            }

            const btnCropTranscribe = document.getElementById('btn-crop-transcribe');
            if (btnCropTranscribe) {
                btnCropTranscribe.addEventListener('click', handleAITranscription);
            }

            // Form Submit Handler
            const form = document.getElementById('add-acta-form');
            form.addEventListener('submit', (e) => handleFormSubmit(e, false));

            const btnSaveAdvance = document.getElementById('btn-save-and-advance');
            if (btnSaveAdvance) {
                btnSaveAdvance.addEventListener('click', (e) => handleFormSubmit(e, true));
            }

            const btnFinishModal = document.getElementById('btn-finish-page-modal');
            if (btnFinishModal) {
                btnFinishModal.addEventListener('click', () => closeModal('registerModal'));
            }

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
                originalImageFile = null;
                savedRecordsThisPage = 0;
                currentParagraph = 1;

                pageParagraphs = {
                    1: { x: 2, y: 3, w: 96, h: 29, saved: false, label: '1º Registro (Arriba)' },
                    2: { x: 2, y: 35, w: 96, h: 29, saved: false, label: '2º Registro (Medio)' },
                    3: { x: 2, y: 67, w: 96, h: 30, saved: false, label: '3º Registro (Abajo)' },
                    'full': { x: 0, y: 0, w: 100, h: 100, saved: false, label: 'Página Completa' }
                };

                const cropWrapper = document.getElementById('crop-editor-wrapper');
                if (cropWrapper) cropWrapper.style.display = 'none';
                const dragZone = document.getElementById('drag-zone');
                if (dragZone) dragZone.style.display = 'block';
                const imgCrop = document.getElementById('image-crop-source');
                if (imgCrop) imgCrop.src = '';
                hasLoadedCropImage = false;

                const banner = document.getElementById('multi-record-banner');
                if (banner) banner.style.display = 'none';

                const statusMsg = document.getElementById('ai-status-msg');
                if (statusMsg) statusMsg.style.display = 'none';
                const topBtn = document.getElementById('btn-ai-transcribe');
                if (topBtn) topBtn.querySelector('span').textContent = 'Auto-Transcribir con IA';

                updatePageProgressUI();
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
                        ${acta.bautizado.lugar_nacimiento ? `<div style="font-size:0.75rem; color:var(--text-secondary); margin-left: 28px; margin-top: 2px;">📍 ${acta.bautizado.lugar_nacimiento}</div>` : ''}
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

        let currentSelectedActa = null;

        // Cargar y mostrar modal con el detalle completo del acta
        function showActaDetail(acta) {
            currentSelectedActa = acta;

            document.getElementById('d-libro').textContent = acta.libro ? acta.libro.numero_libro : '-';
            document.getElementById('d-pag-acta').textContent = `Página ${acta.numero_pagina} • Acta ${acta.numero_acta}`;
            document.getElementById('d-fecha-bautizo').textContent = formatDate(acta.fecha_bautizo);
            document.getElementById('d-ministro').textContent = acta.ministro;
            
            document.getElementById('d-nombre').textContent = `${acta.bautizado.nombres} ${acta.bautizado.apellidos}`;
            document.getElementById('d-fecha-nacimiento').textContent = acta.bautizado.fecha_nacimiento ? formatDate(acta.bautizado.fecha_nacimiento) : '-';
            document.getElementById('d-lugar-nacimiento').textContent = acta.bautizado.lugar_nacimiento || 'Sin registrar';
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

        // Generar y poblar la plantilla del Certificado de Bautismo
        function openCertificateModal() {
            if (!currentSelectedActa) return;
            const a = currentSelectedActa;

            document.getElementById('cert-tomo').textContent = a.libro ? a.libro.numero_libro : 'Tomo No Registrado';
            document.getElementById('cert-pagina').textContent = a.numero_pagina || '-';
            document.getElementById('cert-acta').textContent = a.numero_acta || '-';
            
            document.getElementById('cert-bautizado').textContent = `${a.bautizado.nombres} ${a.bautizado.apellidos}`;
            document.getElementById('cert-nacimiento').textContent = a.bautizado.fecha_nacimiento ? formatDate(a.bautizado.fecha_nacimiento) : 'Sin registrar';
            document.getElementById('cert-lugar-nacimiento').textContent = a.bautizado.lugar_nacimiento || 'Sin registrar';
            document.getElementById('cert-bautizo').textContent = formatDate(a.fecha_bautizo);

            document.getElementById('cert-padre').textContent = a.padre ? `${a.padre.nombres} ${a.padre.apellidos}` : 'Sin registrar';
            document.getElementById('cert-madre').textContent = a.madre ? `${a.madre.nombres} ${a.madre.apellidos}` : 'Sin registrar';
            document.getElementById('cert-padrino').textContent = a.padrino ? `${a.padrino.nombres} ${a.padrino.apellidos}` : 'Sin registrar';
            document.getElementById('cert-madrina').textContent = a.madrina ? `${a.madrina.nombres} ${a.madrina.apellidos}` : 'Sin registrar';

            document.getElementById('cert-ministro').textContent = a.ministro || 'Sin registrar';
            document.getElementById('cert-notas').textContent = a.notas_marginales || 'Ninguna.';

            // Fecha de expedición actual
            const hoy = new Date();
            const opcionesFecha = { day: 'numeric', month: 'long', year: 'numeric' };
            document.getElementById('cert-expedicion-fecha').textContent = hoy.toLocaleDateString('es-ES', opcionesFecha);

            openModal('certificateModal');
        }

        // Mostrar Modal con Código QR detectando la IP real de Ethernet/Wi-Fi
        function showQRModal() {
            openModal('qrModal');
            
            const qrImg = document.getElementById('qr-img');
            const qrUrlText = document.getElementById('qr-url-text');
            
            qrUrlText.textContent = 'Detectando IP de Ethernet / Wi-Fi local...';

            fetch('/api/movil/network-ips')
                .then(res => res.json())
                .then(data => {
                    let targetIp = data.primary_ip || window.location.hostname;
                    if (targetIp === '127.0.0.1' || targetIp === 'localhost') {
                        targetIp = window.location.hostname;
                    }

                    const movilUrl = `http://${targetIp}:${data.port || 8000}/movil`;
                    
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(movilUrl)}`;
                    qrUrlText.innerHTML = `
                        <div style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; margin-bottom:2px;">Enlace de Red Local (Ethernet / Wi-Fi):</div>
                        <a href="${movilUrl}" target="_blank" style="color: var(--accent-color); font-size: 1rem; text-decoration: none;">${movilUrl}</a>
                    `;
                })
                .catch(err => {
                    console.error('Error al detectar IPs:', err);
                    const fallbackUrl = `http://${window.location.hostname}:8000/movil`;
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(fallbackUrl)}`;
                    qrUrlText.textContent = fallbackUrl;
                });
        }

        let lastReceivedPhotoTimestamp = 0;

        // Polling para detectar fotos enviadas desde el teléfono en tiempo real
        function startMobilePhotoPolling() {
            // Inicializar timestamp actual para detectar adecuadamente fotos nuevas al instante
            fetch('/api/movil/check-latest')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.has_photo && res.data) {
                        lastReceivedPhotoTimestamp = res.data.timestamp;
                    }
                })
                .catch(() => {});

            // Polling cada 2 segundos
            setInterval(() => {
                fetch('/api/movil/check-latest')
                    .then(res => res.json())
                    .then(res => {
                        if (res.success && res.has_photo && res.data) {
                            if (res.data.timestamp > lastReceivedPhotoTimestamp) {
                                lastReceivedPhotoTimestamp = res.data.timestamp;
                                fetchAndAttachMobilePhoto(res.data.url);
                            }
                        }
                    })
                    .catch(err => console.error('Error en polling móvil:', err));
            }, 2000);
        }

        // Adjuntar automáticamente la foto recibida desde el teléfono y activar el selector de párrafos
        function fetchAndAttachMobilePhoto(photoUrl) {
            closeModal('qrModal');

            fetch(photoUrl)
                .then(res => res.blob())
                .then(blob => {
                    const file = new File([blob], 'foto_movil.jpg', { type: blob.type || 'image/jpeg' });
                    const container = new DataTransfer();
                    container.items.add(file);
                    
                    const fileInput = document.getElementById('f-imagen_pagina');
                    fileInput.files = container.files;
                    loadCropImage(file);

                    // Abrir la pestaña de registro en la computadora
                    openModal('registerModal');

                    showToast('📱 ¡Foto del teléfono recibida! Selecciona el registro en el recuadro y pulsa Transcribir.');

                    // Desplazarse suavemente al panel del recuadro de recorte
                    setTimeout(() => {
                        const cropWrapper = document.getElementById('crop-editor-wrapper');
                        if (cropWrapper) cropWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 400);
                })
                .catch(err => console.error('Error al adjuntar foto móvil:', err));
        }

        // ========================================================
        // CONTROLADOR MULTI-REGISTRO Y RECUADRO DE PÁRRAFOS (HASTA 3 POR PÁGINA)
        // ========================================================
        let hasLoadedCropImage = false;
        let originalImageFile = null;
        let savedRecordsThisPage = 0;
        let currentParagraph = 1; // 1, 2, 3 o 'full'

        // Coordenadas y estado de guardado independiente para cada uno de los 3 registros de la página
        let pageParagraphs = {
            1: { x: 2, y: 3,  w: 96, h: 29, saved: false, label: '1º Registro (Arriba)' },
            2: { x: 2, y: 35, w: 96, h: 29, saved: false, label: '2º Registro (Medio)' },
            3: { x: 2, y: 67, w: 96, h: 30, saved: false, label: '3º Registro (Abajo)' },
            'full': { x: 0, y: 0, w: 100, h: 100, saved: false, label: 'Página Completa' }
        };

        let cropState = {
            x: 2,
            y: 3,
            w: 96,
            h: 29
        };

        let isDraggingCrop = false;
        let cropDragMode = null; // 'move', 'nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w', 'draw'
        let dragStartX = 0;
        let dragStartY = 0;
        let initialCropState = null;

        function loadCropImage(file) {
            if (!file) return;
            originalImageFile = file;
            savedRecordsThisPage = 0;

            // Restablecer posiciones y estados de guardado
            pageParagraphs = {
                1: { x: 2, y: 3,  w: 96, h: 29, saved: false, label: '1º Registro (Arriba)' },
                2: { x: 2, y: 35, w: 96, h: 29, saved: false, label: '2º Registro (Medio)' },
                3: { x: 2, y: 67, w: 96, h: 30, saved: false, label: '3º Registro (Abajo)' },
                'full': { x: 0, y: 0, w: 100, h: 100, saved: false, label: 'Página Completa' }
            };

            const banner = document.getElementById('multi-record-banner');
            if (banner) banner.style.display = 'none';

            updatePageProgressUI();

            const reader = new FileReader();
            reader.onload = function(evt) {
                const img = document.getElementById('image-crop-source');
                img.onload = () => {
                    hasLoadedCropImage = true;
                    document.getElementById('crop-editor-wrapper').style.display = 'block';
                    document.getElementById('drag-zone').style.display = 'none';
                    selectParagraph(1);
                };
                img.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        }

        // Seleccionar y activar uno de los párrafos (1, 2, 3 o 'full')
        function selectParagraph(slot) {
            // Guardar coordenadas actuales en el párrafo anterior si estaba activo
            if (pageParagraphs[currentParagraph]) {
                pageParagraphs[currentParagraph].x = cropState.x;
                pageParagraphs[currentParagraph].y = cropState.y;
                pageParagraphs[currentParagraph].w = cropState.w;
                pageParagraphs[currentParagraph].h = cropState.h;
            }

            currentParagraph = slot;
            const target = pageParagraphs[slot] || pageParagraphs[1];
            cropState = { x: target.x, y: target.y, w: target.w, h: target.h };

            // Actualizar botones de preset
            document.querySelectorAll('.crop-preset-btn').forEach(btn => btn.classList.remove('active'));
            const btnMap = {
                1: 'btn-crop-p1',
                2: 'btn-crop-p2',
                3: 'btn-crop-p3',
                'full': 'btn-crop-full'
            };
            if (btnMap[slot]) {
                const b = document.getElementById(btnMap[slot]);
                if (b) b.classList.add('active');
            }

            // Actualizar etiqueta del recuadro
            const badge = document.getElementById('crop-badge-text');
            if (badge) {
                const suffix = target.saved ? '✓ Guardado' : 'Seleccionado';
                badge.textContent = `${target.label} - ${suffix}`;
            }

            const previewLabel = document.getElementById('crop-preview-label');
            if (previewLabel) {
                previewLabel.textContent = `Párrafo a procesar por IA (${slot === 'full' ? 'Página Completa' : slot + 'º Registro'})`;
            }

            const transcribeBtnText = document.getElementById('btn-crop-transcribe-text');
            if (transcribeBtnText) {
                transcribeBtnText.textContent = `Transcribir ${slot === 'full' ? 'Página' : slot + 'º Registro'} con IA`;
            }

            const topBtn = document.getElementById('btn-ai-transcribe');
            if (topBtn) {
                topBtn.querySelector('span').textContent = `Auto-Transcribir ${slot === 'full' ? 'Página' : slot + 'º Reg'}`;
            }

            updateAdvanceButtonText();
            updateCropBoxUI();
        }

        function getNextUnsavedSlot() {
            if (currentParagraph === 1) {
                return !pageParagraphs[2].saved ? 2 : (!pageParagraphs[3].saved ? 3 : null);
            } else if (currentParagraph === 2) {
                return !pageParagraphs[3].saved ? 3 : (!pageParagraphs[1].saved ? 1 : null);
            } else if (currentParagraph === 3) {
                return !pageParagraphs[1].saved ? 1 : (!pageParagraphs[2].saved ? 2 : null);
            }
            return !pageParagraphs[1].saved ? 1 : (!pageParagraphs[2].saved ? 2 : (!pageParagraphs[3].saved ? 3 : null));
        }

        function updateAdvanceButtonText() {
            const btnAdvance = document.getElementById('btn-save-and-advance');
            const advanceText = document.getElementById('btn-advance-text');
            if (!btnAdvance || !advanceText) return;

            const nextSlot = getNextUnsavedSlot();
            if (nextSlot) {
                btnAdvance.style.display = 'inline-flex';
                advanceText.textContent = `Guardar y Pasar al ${nextSlot}º Registro`;
            } else {
                btnAdvance.style.display = 'none';
            }
        }

        function updatePageProgressUI() {
            const progressText = document.getElementById('crop-page-status-text');
            const progressPill = document.getElementById('crop-page-progress');
            const footerStatusText = document.getElementById('footer-status-text');

            if (progressText) {
                progressText.textContent = `Página: ${savedRecordsThisPage} de 3 guardados`;
            }
            if (progressPill) {
                if (savedRecordsThisPage >= 3) {
                    progressPill.classList.add('completed');
                    progressText.textContent = '🎉 Página Completa: 3 de 3 guardados';
                } else {
                    progressPill.classList.remove('completed');
                }
            }
            if (footerStatusText) {
                footerStatusText.textContent = savedRecordsThisPage > 0 
                    ? `${savedRecordsThisPage} de 3 bautizos guardados de esta página.` 
                    : 'Una sola foto permite registrar hasta 3 bautizos.';
            }

            [1, 2, 3].forEach(slot => {
                const pill = document.getElementById(`pill-p${slot}`);
                if (!pill) return;
                if (pageParagraphs[slot] && pageParagraphs[slot].saved) {
                    pill.textContent = '✓ Guardado';
                    pill.classList.add('saved');
                } else {
                    pill.textContent = 'Pendiente';
                    pill.classList.remove('saved');
                }
            });

            updateAdvanceButtonText();
        }

        function updateCropBoxUI() {
            const img = document.getElementById('image-crop-source');
            const box = document.getElementById('crop-box');
            if (!img || !box || !img.offsetWidth) return;

            const imgW = img.offsetWidth;
            const imgH = img.offsetHeight;

            // Clamping para garantizar que el recuadro siempre permanezca dentro de la foto
            cropState.w = Math.max(5, Math.min(100, cropState.w));
            cropState.h = Math.max(5, Math.min(100, cropState.h));
            cropState.x = Math.max(0, Math.min(100 - cropState.w, cropState.x));
            cropState.y = Math.max(0, Math.min(100 - cropState.h, cropState.y));

            // Actualizar coordenadas en el slot activo
            if (pageParagraphs[currentParagraph]) {
                pageParagraphs[currentParagraph].x = cropState.x;
                pageParagraphs[currentParagraph].y = cropState.y;
                pageParagraphs[currentParagraph].w = cropState.w;
                pageParagraphs[currentParagraph].h = cropState.h;
            }

            box.style.left = `${(cropState.x * imgW) / 100}px`;
            box.style.top = `${(cropState.y * imgH) / 100}px`;
            box.style.width = `${(cropState.w * imgW) / 100}px`;
            box.style.height = `${(cropState.h * imgH) / 100}px`;

            // Actualizar guías visuales de los 3 slots en la foto
            [1, 2, 3].forEach(slot => {
                const guide = document.getElementById(`guide-slot-${slot}`);
                const tag = document.getElementById(`guide-tag-${slot}`);
                if (!guide) return;

                const p = pageParagraphs[slot];
                guide.style.left = `${(p.x * imgW) / 100}px`;
                guide.style.top = `${(p.y * imgH) / 100}px`;
                guide.style.width = `${(p.w * imgW) / 100}px`;
                guide.style.height = `${(p.h * imgH) / 100}px`;

                if (slot === currentParagraph) {
                    guide.classList.add('is-active');
                } else {
                    guide.classList.remove('is-active');
                }

                if (p.saved) {
                    guide.classList.add('is-saved');
                    if (tag) tag.textContent = `✓ ${slot}º Reg Guardado`;
                } else {
                    guide.classList.remove('is-saved');
                    if (tag) tag.textContent = `${slot}º Registro`;
                }
            });

            updateCropLiveThumbnail();
        }

        function updateCropLiveThumbnail() {
            const img = document.getElementById('image-crop-source');
            const thumbCanvas = document.getElementById('crop-live-thumb');
            if (!img || !thumbCanvas || !img.naturalWidth) return;

            const ctx = thumbCanvas.getContext('2d');
            const sx = (cropState.x / 100) * img.naturalWidth;
            const sy = (cropState.y / 100) * img.naturalHeight;
            const sw = (cropState.w / 100) * img.naturalWidth;
            const sh = (cropState.h / 100) * img.naturalHeight;

            ctx.clearRect(0, 0, thumbCanvas.width, thumbCanvas.height);
            if (sw > 0 && sh > 0) {
                ctx.drawImage(img, sx, sy, sw, sh, 0, 0, thumbCanvas.width, thumbCanvas.height);
            }
        }

        // Obtener el recorte de alta resolución del párrafo seleccionado como archivo Blob JPEG
        function getActiveCropBlob(callback) {
            const img = document.getElementById('image-crop-source');
            if (!hasLoadedCropImage || !img || !img.naturalWidth) {
                callback(originalImageFile || null);
                return;
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            const sx = (cropState.x / 100) * img.naturalWidth;
            const sy = (cropState.y / 100) * img.naturalHeight;
            const sw = (cropState.w / 100) * img.naturalWidth;
            const sh = (cropState.h / 100) * img.naturalHeight;

            canvas.width = Math.max(20, Math.round(sw));
            canvas.height = Math.max(20, Math.round(sh));

            ctx.drawImage(img, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => {
                callback(blob);
            }, 'image/jpeg', 0.92);
        }

        function initCropEditorEvents() {
            const container = document.getElementById('crop-container');
            const box = document.getElementById('crop-box');
            const img = document.getElementById('image-crop-source');

            function onPointerDown(e) {
                if (!hasLoadedCropImage) return;

                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                if (e.target.classList.contains('crop-handle')) {
                    cropDragMode = e.target.dataset.handle;
                } else if (e.target === box || box.contains(e.target)) {
                    cropDragMode = 'move';
                } else if (e.target.classList.contains('crop-guide-slot') || e.target.closest('.crop-guide-slot')) {
                    const guideEl = e.target.classList.contains('crop-guide-slot') ? e.target : e.target.closest('.crop-guide-slot');
                    const slotNum = parseInt(guideEl.dataset.slot, 10);
                    if (slotNum) {
                        selectParagraph(slotNum);
                        return;
                    }
                } else if (e.target === container || e.target === img) {
                    cropDragMode = 'draw';
                    const rect = img.getBoundingClientRect();
                    const clickX = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
                    const clickY = Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100));
                    cropState = { x: clickX, y: clickY, w: 5, h: 5 };
                    document.querySelectorAll('.crop-preset-btn').forEach(btn => btn.classList.remove('active'));
                    document.getElementById('crop-badge-text').textContent = 'Párrafo Personalizado';
                } else {
                    return;
                }

                isDraggingCrop = true;
                dragStartX = clientX;
                dragStartY = clientY;
                initialCropState = { ...cropState };
                e.preventDefault();
            }

            function onPointerMove(e) {
                if (!isDraggingCrop || !hasLoadedCropImage) return;

                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                const imgW = img.offsetWidth;
                const imgH = img.offsetHeight;
                if (!imgW || !imgH) return;

                const deltaX = ((clientX - dragStartX) / imgW) * 100;
                const deltaY = ((clientY - dragStartY) / imgH) * 100;

                if (cropDragMode === 'move') {
                    cropState.x = Math.max(0, Math.min(100 - initialCropState.w, initialCropState.x + deltaX));
                    cropState.y = Math.max(0, Math.min(100 - initialCropState.h, initialCropState.y + deltaY));
                } else if (cropDragMode === 'se' || cropDragMode === 'draw') {
                    cropState.w = Math.max(5, Math.min(100 - initialCropState.x, initialCropState.w + deltaX));
                    cropState.h = Math.max(5, Math.min(100 - initialCropState.y, initialCropState.h + deltaY));
                } else if (cropDragMode === 'e') {
                    cropState.w = Math.max(5, Math.min(100 - initialCropState.x, initialCropState.w + deltaX));
                } else if (cropDragMode === 's') {
                    cropState.h = Math.max(5, Math.min(100 - initialCropState.y, initialCropState.h + deltaY));
                } else if (cropDragMode === 'nw') {
                    const newX = Math.min(initialCropState.x + initialCropState.w - 5, Math.max(0, initialCropState.x + deltaX));
                    cropState.w = initialCropState.w - (newX - initialCropState.x);
                    cropState.x = newX;
                    const newY = Math.min(initialCropState.y + initialCropState.h - 5, Math.max(0, initialCropState.y + deltaY));
                    cropState.h = initialCropState.h - (newY - initialCropState.y);
                    cropState.y = newY;
                } else if (cropDragMode === 'n') {
                    const newY = Math.min(initialCropState.y + initialCropState.h - 5, Math.max(0, initialCropState.y + deltaY));
                    cropState.h = initialCropState.h - (newY - initialCropState.y);
                    cropState.y = newY;
                } else if (cropDragMode === 'ne') {
                    const newY = Math.min(initialCropState.y + initialCropState.h - 5, Math.max(0, initialCropState.y + deltaY));
                    cropState.h = initialCropState.h - (newY - initialCropState.y);
                    cropState.y = newY;
                    cropState.w = Math.max(5, Math.min(100 - initialCropState.x, initialCropState.w + deltaX));
                } else if (cropDragMode === 'sw') {
                    const newX = Math.min(initialCropState.x + initialCropState.w - 5, Math.max(0, initialCropState.x + deltaX));
                    cropState.w = initialCropState.w - (newX - initialCropState.x);
                    cropState.x = newX;
                    cropState.h = Math.max(5, Math.min(100 - initialCropState.y, initialCropState.h + deltaY));
                } else if (cropDragMode === 'w') {
                    const newX = Math.min(initialCropState.x + initialCropState.w - 5, Math.max(0, initialCropState.x + deltaX));
                    cropState.w = initialCropState.w - (newX - initialCropState.x);
                    cropState.x = newX;
                }

                document.getElementById('crop-badge-text').textContent = `${pageParagraphs[currentParagraph] ? pageParagraphs[currentParagraph].label : 'Párrafo'} Ajustado`;

                updateCropBoxUI();
            }

            function onPointerUp() {
                isDraggingCrop = false;
                cropDragMode = null;
            }

            container.addEventListener('mousedown', onPointerDown);
            container.addEventListener('touchstart', onPointerDown, { passive: false });

            window.addEventListener('mousemove', onPointerMove);
            window.addEventListener('touchmove', onPointerMove, { passive: false });

            window.addEventListener('mouseup', onPointerUp);
            window.addEventListener('touchend', onPointerUp);

            window.addEventListener('resize', updateCropBoxUI);

            // Clic en las guías visuales de los párrafos
            [1, 2, 3].forEach(slot => {
                const guide = document.getElementById(`guide-slot-${slot}`);
                if (guide) {
                    guide.addEventListener('click', () => selectParagraph(slot));
                }
            });

            // Botones de presets
            document.getElementById('btn-crop-p1').addEventListener('click', () => selectParagraph(1));
            document.getElementById('btn-crop-p2').addEventListener('click', () => selectParagraph(2));
            document.getElementById('btn-crop-p3').addEventListener('click', () => selectParagraph(3));
            document.getElementById('btn-crop-full').addEventListener('click', () => selectParagraph('full'));
        }

        // Limpiar campos del formulario para el siguiente registro de la misma página
        function prepareFormForNextParagraph(nextSlot) {
            const banner = document.getElementById('multi-record-banner');
            if (banner) banner.style.display = 'none';

            // Limpiar datos personales del bautizo anterior, manteniendo Libro y Página
            document.getElementById('f-numero_acta').value = '';
            document.getElementById('f-fecha_bautizo').value = '';
            document.getElementById('f-ministro').value = '';

            document.getElementById('f-bautizado-nombres').value = '';
            document.getElementById('f-bautizado-apellidos').value = '';
            document.getElementById('f-bautizado-fecha_nacimiento').value = '';
            document.getElementById('f-bautizado-lugar_nacimiento').value = '';
            document.getElementById('f-bautizado-genero').value = 'M';

            document.querySelector('input[name="padre[nombres]"]').value = '';
            document.querySelector('input[name="padre[apellidos]"]').value = '';
            document.querySelector('input[name="padre[cedula]"]').value = '';

            document.querySelector('input[name="madre[nombres]"]').value = '';
            document.querySelector('input[name="madre[apellidos]"]').value = '';
            document.querySelector('input[name="madre[cedula]"]').value = '';

            document.querySelector('input[name="padrino[nombres]"]').value = '';
            document.querySelector('input[name="padrino[apellidos]"]').value = '';

            document.querySelector('input[name="madrina[nombres]"]').value = '';
            document.querySelector('input[name="madrina[apellidos]"]').value = '';

            const notas = document.getElementById('f-notas_marginales');
            if (notas) notas.value = '';

            // Activar el recuadro del siguiente registro
            selectParagraph(nextSlot);

            const statusMsg = document.getElementById('ai-status-msg');
            if (statusMsg) {
                statusMsg.style.display = 'block';
                statusMsg.style.background = 'rgba(99, 102, 241, 0.15)';
                statusMsg.style.color = '#818cf8';
                statusMsg.textContent = `📍 Recuadro ubicado en el ${nextSlot}º Registro. Pulsa "Auto-Transcribir con IA" o "Transcribir ${nextSlot}º Registro" para extraer sus datos.`;
            }

            showToast(`Listo para transcribir el ${nextSlot}º Registro de esta misma página.`);

            // Desplazar la vista al editor de recorte
            const cropWrapper = document.getElementById('crop-editor-wrapper');
            if (cropWrapper) {
                cropWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Manejo de envío de formulario para guardar un acta nueva (individual o pasar al siguiente)
        function handleFormSubmit(e, advanceToNext = false) {
            if (e) e.preventDefault();
            const form = document.getElementById('add-acta-form');
            const submitBtn = document.getElementById('btn-save-record-only');
            const advanceBtn = document.getElementById('btn-save-and-advance');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
            }
            if (advanceBtn) {
                advanceBtn.disabled = true;
            }

            const formData = new FormData(form);

            const guardarSoloRecorte = document.getElementById('f-guardar-solo-recorte');
            if (guardarSoloRecorte && guardarSoloRecorte.checked && hasLoadedCropImage) {
                getActiveCropBlob(blob => {
                    if (blob) {
                        formData.set('imagen_pagina', blob, `acta_recorte_p${currentParagraph}.jpg`);
                    } else if (originalImageFile) {
                        formData.set('imagen_pagina', originalImageFile);
                    }
                    sendActaFormData(formData, submitBtn, advanceBtn, advanceToNext);
                });
            } else {
                if (originalImageFile) {
                    formData.set('imagen_pagina', originalImageFile);
                }
                sendActaFormData(formData, submitBtn, advanceBtn, advanceToNext);
            }
        }

        function sendActaFormData(formData, submitBtn, advanceBtn, advanceToNext = false) {
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
                // Registrar este registro como guardado
                if (pageParagraphs[currentParagraph]) {
                    pageParagraphs[currentParagraph].saved = true;
                }
                savedRecordsThisPage++;
                updatePageProgressUI();
                updateCropBoxUI();
                fetchActas();

                showToast(data.message || `Registro #${currentParagraph} de esta página guardado con éxito.`);

                const nextSlot = getNextUnsavedSlot();

                if (advanceToNext && nextSlot) {
                    prepareFormForNextParagraph(nextSlot);
                } else {
                    // Mostrar banner de acción en la parte superior del formulario
                    const banner = document.getElementById('multi-record-banner');
                    const title = document.getElementById('multi-record-title');
                    const msg = document.getElementById('multi-record-msg');
                    const btnNext = document.getElementById('btn-next-paragraph');

                    if (banner) {
                        banner.style.display = 'flex';
                        if (title) title.textContent = `¡Registro #${currentParagraph} Guardado con Éxito!`;
                        if (msg) msg.textContent = `Has guardado ${savedRecordsThisPage} de 3 registros de esta misma página física.`;

                        if (nextSlot && btnNext) {
                            btnNext.style.display = 'inline-flex';
                            btnNext.textContent = `👉 Registrar Siguiente Párrafo (${nextSlot}º)`;
                            btnNext.onclick = () => prepareFormForNextParagraph(nextSlot);
                        } else if (btnNext) {
                            btnNext.style.display = 'none';
                            if (msg) msg.textContent = `🎉 ¡Los 3 registros de esta página física han sido guardados con éxito!`;
                        }

                        // Scroll suave al inicio del modal para ver la confirmación
                        const regModalContainer = document.querySelector('#registerModal .modal-container');
                        if (regModalContainer) regModalContainer.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            })
            .catch(err => {
                console.error(err);
                showToast(err.message || 'Error al intentar guardar el acta. Por favor valida los datos.', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Guardar Registro';
                }
                if (advanceBtn) {
                    advanceBtn.disabled = false;
                }
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

        // Manejo de Transcripción con IA de imágenes manuscritas (Párrafo Específico)
        function handleAITranscription() {
            const fileInput = document.getElementById('f-imagen_pagina');
            const btn = document.getElementById('btn-ai-transcribe');
            const btnCrop = document.getElementById('btn-crop-transcribe');
            const statusMsg = document.getElementById('ai-status-msg');

            if (!hasLoadedCropImage && (!fileInput.files || !fileInput.files[0]) && !originalImageFile) {
                showToast('Por favor selecciona o arrastra primero la imagen de la página en la sección 5.', 'error');
                const dragZone = document.getElementById('drag-zone');
                if (dragZone) {
                    dragZone.scrollIntoView({ behavior: 'smooth' });
                    dragZone.style.borderColor = 'var(--accent-color)';
                    setTimeout(() => dragZone.style.borderColor = '', 2000);
                }
                return;
            }

            const currentSlotName = currentParagraph === 'full' ? 'la página completa' : `el ${currentParagraph}º registro`;

            const setLoadingState = (loading) => {
                btn.disabled = loading;
                if (btnCrop) btnCrop.disabled = loading;

                if (loading) {
                    const spinner = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg>`;
                    btn.innerHTML = `${spinner} <span>Analizando ${currentSlotName}...</span>`;
                    if (btnCrop) btnCrop.innerHTML = `${spinner} <span>Transcribiendo...</span>`;
                    
                    statusMsg.style.display = 'block';
                    statusMsg.style.background = 'rgba(99, 102, 241, 0.15)';
                    statusMsg.style.color = '#818cf8';
                    statusMsg.textContent = `🧠 Analizando caligrafía de ${currentSlotName} con Visión por IA (evitando mezclar con los otros registros)...`;
                } else {
                    btn.innerHTML = `
                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
                        <span>Auto-Transcribir ${currentParagraph === 'full' ? 'Página' : currentParagraph + 'º Reg'}</span>
                    `;
                    if (btnCrop) btnCrop.innerHTML = `
                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
                        <span id="btn-crop-transcribe-text">Transcribir ${currentParagraph === 'full' ? 'Página' : currentParagraph + 'º Registro'} con IA</span>
                    `;
                }
            };

            setLoadingState(true);

            getActiveCropBlob(blobToSend => {
                const formData = new FormData();
                if (blobToSend) {
                    formData.append('imagen_pagina', blobToSend, `parrafo_${currentParagraph}.jpg`);
                } else if (originalImageFile) {
                    formData.append('imagen_pagina', originalImageFile);
                } else if (fileInput.files && fileInput.files[0]) {
                    formData.append('imagen_pagina', fileInput.files[0]);
                }

                formData.append('parrafo_index', currentParagraph);

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

                    const banner = document.getElementById('multi-record-banner');
                    if (banner) banner.style.display = 'none';

                    const d = resData.data;

                    // Auto-llenar campos del formulario
                    if (d.numero_pagina) document.getElementById('f-numero_pagina').value = d.numero_pagina;
                    if (d.numero_acta) document.getElementById('f-numero_acta').value = d.numero_acta;
                    if (d.fecha_bautizo) document.getElementById('f-fecha_bautizo').value = d.fecha_bautizo;
                    if (d.ministro) document.getElementById('f-ministro').value = d.ministro;

                    if (d.bautizado) {
                        if (d.bautizado.nombres) document.getElementById('f-bautizado-nombres').value = d.bautizado.nombres;
                        if (d.bautizado.apellidos) document.getElementById('f-bautizado-apellidos').value = d.bautizado.apellidos;
                        if (d.bautizado.fecha_nacimiento) document.getElementById('f-bautizado-fecha_nacimiento').value = d.bautizado.fecha_nacimiento.trim();
                        if (d.bautizado.lugar_nacimiento) document.getElementById('f-bautizado-lugar_nacimiento').value = d.bautizado.lugar_nacimiento;
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

                    if (resData.is_demo) {
                        statusMsg.style.background = 'rgba(234, 179, 8, 0.15)';
                        statusMsg.style.color = '#eab308';
                        statusMsg.innerHTML = `✨ ¡Registro #${currentParagraph} transcrito (Modo Demostración)! Puedes configurar tu clave de Google en el botón <strong>"IA Gemini"</strong> para transcripción en vivo de cualquier libro.`;
                        showToast(`Transcripción del ${currentParagraph}º registro completada (Modo Demostración). Revisa los datos.`);
                    } else {
                        statusMsg.style.background = 'rgba(34, 197, 94, 0.15)';
                        statusMsg.style.color = '#4ade80';
                        statusMsg.textContent = `✨ ¡Registro #${currentParagraph} transcrito con éxito por IA Gemini Vision! Revisa la información antes de guardar.`;
                        showToast(`Transcripción del ${currentParagraph}º registro completada con IA Gemini.`);
                    }

                    // Desplazarse suavemente arriba para ver los campos completados
                    const regModalContainer = document.querySelector('#registerModal .modal-container');
                    if (regModalContainer) regModalContainer.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(err => {
                    console.error(err);
                    statusMsg.style.background = 'rgba(239, 68, 68, 0.15)';
                    statusMsg.style.color = '#f87171';
                    statusMsg.textContent = '⚠️ Error en transcripción: ' + (err.message || 'No se pudo procesar el párrafo.');
                    showToast('Error al transcribir el párrafo.', 'error');
                })
                .finally(() => {
                    setLoadingState(false);
                });
            });
        }

        // Comprobar estado de la IA Gemini
        function checkAiStatus() {
            fetch('/api/config/ai-status')
                .then(res => res.json())
                .then(data => {
                    const navBadge = document.getElementById('nav-ai-status-badge');
                    const statusCard = document.getElementById('ai-status-card');
                    const statusText = document.getElementById('ai-status-text');
                    const statusBadge = document.getElementById('ai-status-badge');

                    if (data.has_key) {
                        if (navBadge) {
                            navBadge.textContent = 'En Vivo';
                            navBadge.style.background = 'rgba(34, 197, 94, 0.2)';
                            navBadge.style.color = '#22c55e';
                            navBadge.style.borderColor = 'rgba(34, 197, 94, 0.4)';
                        }
                        if (statusCard) {
                            statusCard.style.background = 'rgba(34, 197, 94, 0.1)';
                            statusCard.style.border = '1px solid rgba(34, 197, 94, 0.3)';
                        }
                        if (statusText) statusText.innerHTML = `Clave configurada: <code>${data.masked_key}</code>`;
                        if (statusBadge) {
                            statusBadge.textContent = 'Activa';
                            statusBadge.style.background = '#16a34a';
                            statusBadge.style.color = '#ffffff';
                        }
                    } else {
                        if (navBadge) {
                            navBadge.textContent = 'Demo';
                            navBadge.style.background = 'rgba(234, 179, 8, 0.2)';
                            navBadge.style.color = '#eab308';
                            navBadge.style.borderColor = 'rgba(234, 179, 8, 0.4)';
                        }
                        if (statusCard) {
                            statusCard.style.background = 'rgba(234, 179, 8, 0.1)';
                            statusCard.style.border = '1px solid rgba(234, 179, 8, 0.3)';
                        }
                        if (statusText) statusText.textContent = 'Modo Demostración (Sin clave configurada)';
                        if (statusBadge) {
                            statusBadge.textContent = 'Sin Clave';
                            statusBadge.style.background = '#ca8a04';
                            statusBadge.style.color = '#ffffff';
                        }
                    }
                })
                .catch(err => console.error('Error comprobando estado de IA:', err));
        }

        // Guardar Clave de la API Gemini
        function handleSaveAiKey(e) {
            e.preventDefault();
            const input = document.getElementById('gemini_api_key');
            const key = input ? input.value.trim() : '';
            const btn = document.getElementById('btn-save-ai-key');

            if (!key) {
                showToast('Por favor escribe o pega una clave API de Gemini.', 'error');
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>Guardando...</span>';
            }

            fetch('/api/config/ai-key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ gemini_api_key: key })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error al guardar clave');
                showToast(data.message || 'Clave de IA configurada correctamente.');
                if (input) input.value = '';
                checkAiStatus();
                closeModal('aiKeyModal');
            })
            .catch(err => {
                console.error(err);
                showToast(err.message || 'Error al guardar la clave de IA.', 'error');
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>Guardar Clave</span>';
                }
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
