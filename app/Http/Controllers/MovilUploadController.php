<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
            'formatted_time' => date('Y-m-d h:i:s A'),
            'client_ip'      => $request->ip(),
            'file_size_bytes'=> $file->getSize(),
        ];

        File::ensureDirectoryExists(storage_path('app/temp_movil'));
        File::put(storage_path('app/temp_movil/last_upload.json'), json_encode($lastUploadData, JSON_PRETTY_PRINT));

        // Registrar en los logs de Laravel para diagnóstico
        Log::info("📱 [DEBUG MÓVIL] Foto recibida con éxito desde la IP {$request->ip()}: {$filename} (" . round($file->getSize() / 1024, 2) . " KB)");

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
                'has_photo' => false,
                'message'   => 'Aún no se ha recibido ninguna foto desde el teléfono.'
            ]);
        }

        $data = json_decode(File::get($jsonPath), true);

        return response()->json([
            'success'   => true,
            'has_photo' => true,
            'data'      => $data
        ]);
    }

    /**
     * Endpoint de Diagnóstico / Debug Status
     */
    public function debugStatus(): JsonResponse
    {
        $jsonPath = storage_path('app/temp_movil/last_upload.json');
        $hasUploadJson = File::exists($jsonPath);
        $lastUpload = $hasUploadJson ? json_decode(File::get($jsonPath), true) : null;

        $files = [];
        $tempDir = storage_path('app/public/temp_movil');
        if (File::exists($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return response()->json([
            'success'          => true,
            'has_upload_json'  => $hasUploadJson,
            'last_upload'      => $lastUpload,
            'total_temp_files' => count($files),
            'files'            => array_slice(array_reverse($files), 0, 5),
            'server_time'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtener las direcciones IP locales de red (Ethernet y Wi-Fi) filtrando VPNs
     */
    public function getNetworkIps(): JsonResponse
    {
        $ips = [];
        $primaryIp = null;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('powershell -Command "Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.IPAddress -notlike \'127.*\' -and $_.IPAddress -notlike \'169.*\'} | Select-Object IPAddress, InterfaceAlias | ConvertTo-Json"');
            $decoded = json_decode($output, true);

            if ($decoded) {
                if (isset($decoded['IPAddress'])) {
                    $decoded = [$decoded];
                }

                foreach ($decoded as $item) {
                    $ip = $item['IPAddress'] ?? '';
                    $alias = $item['InterfaceAlias'] ?? '';

                    $isVpn = (bool) preg_match('/vpn|radmin|hamachi|virtual|vbox|vmware|loopback/i', $alias);
                    
                    if ($ip && !$isVpn) {
                        $ips[] = [
                            'ip'    => $ip,
                            'alias' => $alias,
                            'is_lan' => true
                        ];
                        if (!$primaryIp) $primaryIp = $ip;
                    } else if ($ip) {
                        $ips[] = [
                            'ip'    => $ip,
                            'alias' => $alias,
                            'is_lan' => false
                        ];
                    }
                }
            }
        }

        if (empty($primaryIp) && isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $primaryIp = $_SERVER['SERVER_ADDR'];
        }

        return response()->json([
            'success'     => true,
            'primary_ip'  => $primaryIp ?: gethostbyname(gethostname()),
            'all_ips'     => $ips,
            'port'        => 8000
        ]);
    }
}
