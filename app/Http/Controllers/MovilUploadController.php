<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MovilUploadController extends Controller
{
    /**
     * Subir foto desde el teléfono móvil
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'imagen_pagina' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $file = $request->file('imagen_pagina');
        $filename = 'movil_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        
        // Guardar en la carpeta pública accesible desde la web
        $path = $file->storeAs('public/temp_movil', $filename);
        $publicUrl = 'storage/temp_movil/' . $filename;

        $lastUploadData = [
            'filename'       => $filename,
            'path'           => 'storage/temp_movil/' . $filename,
            'url'            => '/' . $publicUrl,
            'timestamp'      => time(),
            'formatted_time' => date('h:i:s A')
        ];

        File::ensureDirectoryExists(storage_path('app/temp_movil'));
        File::put(storage_path('app/temp_movil/last_upload.json'), json_encode($lastUploadData));

        return response()->json([
            'success' => true,
            'message' => '¡Foto enviada a la computadora con éxito!',
            'data'    => $lastUploadData
        ]);
    }

    /**
     * Consultar la última foto subida desde el teléfono (para polling desde la PC)
     */
    public function checkLatest(): JsonResponse
    {
        $jsonPath = storage_path('app/temp_movil/last_upload.json');

        if (!File::exists($jsonPath)) {
            return response()->json([
                'success'   => false,
                'has_photo' => false
            ]);
        }

        $data = json_decode(File::get($jsonPath), true);

        return response()->json([
            'success'   => true,
            'has_photo' => true,
            'data'      => $data
        ]);
    }
}
