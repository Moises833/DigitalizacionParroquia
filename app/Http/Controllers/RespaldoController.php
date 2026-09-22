<?php

namespace App\Http\Controllers;

use App\Models\ActaBautizo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RespaldoController extends Controller
{
    /**
     * Descargar respaldo inmediato de la base de datos SQLite (.sqlite)
     */
    public function descargarSqlite(): BinaryFileResponse|JsonResponse
    {
        $dbPath = database_path('database.sqlite');

        if (!File::exists($dbPath)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el archivo de base de datos SQLite.'
            ], 404);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "respaldo_parroquia_bautizos_{$timestamp}.sqlite";
        $tempPath = storage_path("app/backups/{$backupName}");

        File::ensureDirectoryExists(storage_path('app/backups'));
        File::copy($dbPath, $tempPath);

        return response()->download($tempPath, $backupName)->deleteFileAfterSend(true);
    }

    /**
     * Restaurar la base de datos SQLite desde un archivo subido por el usuario.
     */
    public function restaurar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo_backup' => 'required|file|max:51200',
        ]);

        $file = $request->file('archivo_backup');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'sqlite' && $extension !== 'db') {
            return response()->json([
                'success' => false,
                'message' => 'El archivo debe tener extensión .sqlite o .db'
            ], 422);
        }

        try {
            $dbPath = database_path('database.sqlite');

            // Copia de seguridad preventiva
            if (File::exists($dbPath)) {
                $preventiveCopy = storage_path('app/backups/backup_preventivo_antes_de_restaurar.sqlite');
                File::ensureDirectoryExists(storage_path('app/backups'));
                File::copy($dbPath, $preventiveCopy);
            }

            File::copy($file->getRealPath(), $dbPath);

            return response()->json([
                'success' => true,
                'message' => 'Base de datos restaurada con éxito. La información se ha actualizado.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al restaurar base de datos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al reemplazar la base de datos.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar todos los registros de actas en formato CSV (Excel).
     */
    public function exportarCsv(): BinaryFileResponse
    {
        $actas = ActaBautizo::with(['bautizado', 'padre', 'madre', 'padrino', 'madrina', 'libro'])->get();

        $filename = "actas_bautizo_parroquia_" . date('Y-m-d') . ".csv";
        $filePath = storage_path("app/backups/{$filename}");
        File::ensureDirectoryExists(storage_path('app/backups'));

        $handle = fopen($filePath, 'w');
        fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

        fputcsv($handle, [
            'Tomo/Libro',
            'Pagina',
            'Acta',
            'Fecha Bautizo',
            'Nombres Bautizado',
            'Apellidos Bautizado',
            'Fecha Nacimiento',
            'Genero',
            'Padre Nombres',
            'Padre Apellidos',
            'Padre Cedula',
            'Madre Nombres',
            'Madre Apellidos',
            'Madre Cedula',
            'Padrino Nombres',
            'Padrino Apellidos',
            'Madrina Nombres',
            'Madrina Apellidos',
            'Sacerdote / Ministro',
            'Notas Marginales'
        ], ';');

        foreach ($actas as $acta) {
            fputcsv($handle, [
                $acta->libro ? $acta->libro->numero_libro : '',
                $acta->numero_pagina,
                $acta->numero_acta,
                $acta->fecha_bautizo ? $acta->fecha_bautizo->format('Y-m-d') : '',
                $acta->bautizado ? $acta->bautizado->nombres : '',
                $acta->bautizado ? $acta->bautizado->apellidos : '',
                ($acta->bautizado && $acta->bautizado->fecha_nacimiento) ? $acta->bautizado->fecha_nacimiento->format('Y-m-d') : '',
                $acta->bautizado ? $acta->bautizado->genero : '',
                $acta->padre ? $acta->padre->nombres : '',
                $acta->padre ? $acta->padre->apellidos : '',
                $acta->padre ? $acta->padre->cedula : '',
                $acta->madre ? $acta->madre->nombres : '',
                $acta->madre ? $acta->madre->apellidos : '',
                $acta->madre ? $acta->madre->cedula : '',
                $acta->padrino ? $acta->padrino->nombres : '',
                $acta->padrino ? $acta->padrino->apellidos : '',
                $acta->madrina ? $acta->madrina->nombres : '',
                $acta->madrina ? $acta->madrina->apellidos : '',
                $acta->ministro,
                $acta->notas_marginales
            ], ';');
        }

        fclose($handle);

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
