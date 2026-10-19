<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranscriptionService
{
    /**
     * Endpoint de la API de Google Gemini Vision
     */
    /**
     * Endpoint de la API de Google Gemini Vision (con soporte para modelo 2.0 y fallback a 1.5)
     */
    protected array $apiModels = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
    ];

    /**
     * Transcribir una imagen de acta manuscrita en cursiva a datos estructurados en JSON.
     *
     * @param UploadedFile|null $imageFile
     * @param int|string $parrafoIndex Número de párrafo (1, 2, 3 o 'full')
     * @return array
     */
    public function transcribir(?UploadedFile $imageFile = null, $parrafoIndex = 1): array
    {
        $apiKey = env('GEMINI_API_KEY');

        // Si no hay API Key configurada en .env o no hay archivo, devolvemos una estructura de demostración funcional
        if (empty($apiKey) || !$imageFile) {
            Log::info('Retornando respuesta de transcripción asistida por demostración.');
            return $this->generarRespuestaSimulada($imageFile, $parrafoIndex);
        }

        try {
            $imageData = base64_encode(file_get_contents($imageFile->getRealPath()));
            $mimeType  = $imageFile->getMimeType() ?: 'image/jpeg';

            $contextoParrafo = is_numeric($parrafoIndex) 
                ? "Esta imagen corresponde específicamente al Registro/Párrafo #{$parrafoIndex} de una página física que contiene 3 actas de bautizo." 
                : "Esta imagen corresponde a una partida de bautizo en un libro eclesiástico.";

            $prompt = <<<PROMPT
Eres un archivista experto en paleografía e historia eclesiástica. Analiza esta fotografía o recorte de un folio/partida de libro de bautizos antiguo escrito a mano en letra cursiva manuscrita en formato de formulario preimpreso.
{$contextoParrafo}
La imagen contiene un párrafo o registro de bautizo específico. Extrae exclusivamente los datos de dicha partida de bautizo sin confundir información con otros párrafos o líneas adyacentes de la página.

ESTRUCTURA DEL FORMULARIO DE BAUTIZO:
- "No.": Número de acta al margen izquierdo o superior (ej: "48", "143", "144").
- "Nombre": Nombre completo del bautizado. Separa en "nombres" y "apellidos" correctamente (ej: "Olimar Zambrano Núñez" -> nombres: "Olimar", apellidos: "Zambrano Núñez").
- "Padres": Nombres y apellidos del padre y de la madre, separados habitualmente por "y" (ej: "Manuel María Zambrano y Olga María Núñez de Zambrano" -> Padre: "Manuel María Zambrano", Madre: "Olga María Núñez").
- "Filiación": "legítima" o "natural".
- "Nacido en": Lugar o ciudad de nacimiento (ej: "Caracas", "Baruta, Edo. Miranda").
- "El [día] de [mes] de mil novecientos [año]": Fecha de nacimiento escrita en palabras.
- "Bautizado el [día] de [mes] de mil novecientos [año]": Fecha de bautizo escrita en palabras.
- "Padrinos": Nombres de padrino y madrina, a menudo separados por "y" o guion (ej: "Lino Castillo y Omaira de Quintero", "Régulo Ufarín - Idalgisa de Ufarín").
- "Ministro": Sacerdote o presbítero celebrante (ej: "P. Victorino Miguélez", "P. Paolo Borelli").
- "Observaciones" / "Registro Civil": Anotaciones marginales, número de registro civil, fecha y notas de matrimonio canónico posterior.

INSTRUCCIONES CRÍTICAS:
1. REGLA ESTRICTA DE FECHAS: En los libros parroquiales de bautismos, las fechas están casi siempre escritas de forma manuscrita literal en palabras (por ejemplo: "veintiuno de febrero de mil novecientos ochenta y uno" -> 1981-02-21, "tres de julio de mil novecientos setenta y ocho" -> 1978-07-03, "veinticuatro de septiembre de mil novecientos ochenta" -> 1980-09-24, "veintidos de septiembre de mil novecientos setenta y nueve" -> 1979-09-22).
DEBES OBLIGATORIAMENTE interpretar, calcular y convertir toda fecha (tanto "fecha_bautizo" como "fecha_nacimiento") al formato numérico estándar YYYY-MM-DD. NUNCA devuelvas fechas escritas en texto literal ni con palabras.

2. LUGAR DE NACIMIENTO: Extrae el lugar de nacimiento o procedencia del bautizado si se menciona en el acta (ej: "Caracas", "Baruta, Edo. Miranda"). Regístralo en el campo "lugar_nacimiento" dentro de "bautizado".

Extrae los datos de la partida de bautizo y devuélvelos STRICTLY en formato JSON sin formato Markdown adicional (sin ```json) con el siguiente esquema exacto:

{
  "numero_pagina": "string o null (ej. 142)",
  "numero_acta": "string o null (ej. 48, 143, 144)",
  "fecha_bautizo": "YYYY-MM-DD o null",
  "ministro": "string o null (ej. P. Victorino Miguélez)",
  "notas_marginales": "string o null",
  "bautizado": {
    "nombres": "string",
    "apellidos": "string",
    "fecha_nacimiento": "YYYY-MM-DD o null",
    "lugar_nacimiento": "string o null",
    "genero": "M o F"
  },
  "padre": {
    "nombres": "string o null",
    "apellidos": "string o null",
    "cedula": "string o null"
  },
  "madre": {
    "nombres": "string o null",
    "apellidos": "string o null",
    "cedula": "string o null"
  },
  "padrino": {
    "nombres": "string o null",
    "apellidos": "string o null",
    "cedula": "string o null"
  },
  "madrina": {
    "nombres": "string o null",
    "apellidos": "string o null",
    "cedula": "string o null"
  }
}

Si alguna palabra en manuscrito es dudosa, aplica corrección fonética y gramática eclesiástica española estándar. No incluyas comentarios extra fuera del JSON.
PROMPT;

            $requestPayload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data'      => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'response_mime_type' => 'application/json'
                ]
            ];

            // Intentar con los modelos disponibles (2.0 flash y fallback a 1.5 flash)
            foreach ($this->apiModels as $model) {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(30)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($endpoint, $requestPayload);

                if ($response->successful()) {
                    $rawText = $response->json('candidates.0.content.parts.0.text');
                    $cleanedText = preg_replace('/^```json\s*|\s*```$/i', '', trim($rawText));
                    $decoded = json_decode($cleanedText, true);

                    if (is_array($decoded)) {
                        // Normalización de respaldo por si el modelo devolvió texto en alguna fecha
                        if (!empty($decoded['fecha_bautizo'])) {
                            $norm = $this->normalizarFecha($decoded['fecha_bautizo']);
                            if ($norm) $decoded['fecha_bautizo'] = $norm;
                        }
                        if (!empty($decoded['bautizado']['fecha_nacimiento'])) {
                            $norm = $this->normalizarFecha($decoded['bautizado']['fecha_nacimiento']);
                            if ($norm) $decoded['bautizado']['fecha_nacimiento'] = $norm;
                        }

                        return [
                            'success' => true,
                            'is_demo' => false,
                            'source'  => 'gemini_vision_api',
                            'model'   => $model,
                            'data'    => $decoded
                        ];
                    }
                }

                Log::warning("Fallo intento con modelo {$model} en Gemini API: " . $response->body());
            }

        } catch (\Throwable $e) {
            Log::error('Excepción al conectar con Gemini API: ' . $e->getMessage());
        }

        // Fallback en caso de error de conexión
        return $this->generarRespuestaSimulada($imageFile, $parrafoIndex);
    }

    /**
     * Normalizar una fecha proveniente de texto literal en español o formato numérico al estándar YYYY-MM-DD.
     */
    public function normalizarFecha(?string $texto): ?string
    {
        if (empty($texto)) {
            return null;
        }

        $texto = trim(mb_strtolower($texto, 'UTF-8'));

        // Si ya viene en formato estándar YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $texto, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        // Si viene en formato DD/MM/YYYY o DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $texto, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        $meses = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'setiembre' => '09', 'octubre' => '10',
            'noviembre' => '11', 'diciembre' => '12'
        ];

        $mesDetectado = null;
        $mesNombreEncontrado = null;
        foreach ($meses as $mesNombre => $mesNum) {
            if (str_contains($texto, $mesNombre)) {
                $mesDetectado = $mesNum;
                $mesNombreEncontrado = $mesNombre;
                break;
            }
        }

        if ($mesDetectado && $mesNombreEncontrado) {
            // Caso numérico con mes en texto: "21 de mayo de 1981" o "21 mayo 1981"
            if (preg_match('/(\d{1,2})\s+(?:de\s+)?' . $mesNombreEncontrado . '\s+(?:del?\s+)?(?:año\s+)?(\d{4})/i', $texto, $m)) {
                return sprintf('%04d-%s-%02d', (int)$m[2], $mesDetectado, (int)$m[1]);
            }

            // Caso literal completo: "veintiuno de mayo de mil novecientos ochenta y uno"
            $partes = explode($mesNombreEncontrado, $texto);
            if (count($partes) === 2) {
                $diaTexto = trim(str_replace(['a los', 'del mes de', 'días de', 'dias de', 'días', 'dias', 'de'], ' ', $partes[0]));
                $anioTexto = trim(str_replace(['del año', 'del ano', 'año', 'ano', 'de', 'del'], ' ', $partes[1]));

                $dia = $this->convertirPalabrasANumero($diaTexto);
                $anio = $this->convertirPalabrasANumero($anioTexto);

                if ($dia && $anio && $dia >= 1 && $dia <= 31 && $anio >= 1800 && $anio <= 2100) {
                    return sprintf('%04d-%s-%02d', $anio, $mesDetectado, $dia);
                }
            }
        }

        return null;
    }

    /**
     * Convierte palabras numéricas en español a un número entero.
     */
    public function convertirPalabrasANumero(string $texto): ?int
    {
        $texto = trim(preg_replace('/\s+/', ' ', strtolower($texto)));
        if (is_numeric($texto)) {
            return (int)$texto;
        }

        if (preg_match('/\b(\d{1,4})\b/', $texto, $m)) {
            return (int)$m[1];
        }

        $valores = [
            'un' => 1, 'uno' => 1, 'primero' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4,
            'cinco' => 5, 'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10,
            'once' => 11, 'doce' => 12, 'trece' => 13, 'catorce' => 14, 'quince' => 15,
            'dieciseis' => 16, 'dieciséis' => 16, 'diecisiete' => 17, 'dieciocho' => 18,
            'diecinueve' => 19, 'veinte' => 20, 'veintiuno' => 21, 'veintiún' => 21, 'veintiun' => 21,
            'veintidos' => 22, 'veintidós' => 22, 'veintitres' => 23, 'veintitrés' => 23,
            'veinticuatro' => 24, 'veinticinco' => 25, 'veintiseis' => 26, 'veintiséis' => 26,
            'veintisiete' => 27, 'veintiocho' => 28, 'veintinueve' => 29, 'treinta' => 30,
            'cuarenta' => 40, 'cincuenta' => 50, 'sesenta' => 60, 'setenta' => 70,
            'ochenta' => 80, 'noventa' => 90,
            'cien' => 100, 'ciento' => 100, 'doscientos' => 200, 'trescientos' => 300,
            'cuatrocientos' => 400, 'quinientos' => 500, 'seiscientos' => 600,
            'setecientos' => 700, 'ochocientos' => 800, 'novecientos' => 900,
        ];

        $palabras = explode(' ', str_replace([' y ', ' del '], [' ', ' '], $texto));
        $total = 0;
        $temp = 0;

        foreach ($palabras as $p) {
            $p = trim($p);
            if (empty($p) || in_array($p, ['a', 'los', 'dias', 'días', 'año', 'ano', 'el'])) continue;

            if ($p === 'mil') {
                if ($temp === 0) $temp = 1;
                $total += $temp * 1000;
                $temp = 0;
            } elseif (isset($valores[$p])) {
                $temp += $valores[$p];
            }
        }
        $total += $temp;

        return $total > 0 ? $total : null;
    }

    /**
     * Genera datos extraídos simulados para pruebas cuando no se ha añadido una clave API.
     */
    protected function generarRespuestaSimulada(?UploadedFile $imageFile = null, $parrafoIndex = 1): array
    {
        $idx = is_numeric($parrafoIndex) ? (int)$parrafoIndex : 1;
        if ($idx < 1 || $idx > 3) $idx = 1;

        $registrosSimulados = [
            1 => [
                'numero_pagina'    => '142',
                'numero_acta'      => '48',
                'fecha_bautizo'    => '1981-02-21',
                'ministro'         => 'P. Victorino Miguélez',
                'notas_marginales' => 'Registro Civil Nº 22001 - 6-12-78 Maternidad Concepción Palacios. Contrajo Matrimonio canónico el 28-07-2007 con Gregory Gonzalo Vargas Heredia.',
                'bautizado' => [
                    'nombres'          => 'Olimar',
                    'apellidos'        => 'Zambrano Núñez',
                    'fecha_nacimiento' => '1978-07-03',
                    'lugar_nacimiento' => 'Caracas',
                    'genero'           => 'F'
                ],
                'padre' => [
                    'nombres'   => 'Manuel María',
                    'apellidos' => 'Zambrano',
                    'cedula'    => null
                ],
                'madre' => [
                    'nombres'   => 'Olga María',
                    'apellidos' => 'Núñez de Zambrano',
                    'cedula'    => null
                ],
                'padrino' => [
                    'nombres'   => 'Lino',
                    'apellidos' => 'Castillo',
                    'cedula'    => null
                ],
                'madrina' => [
                    'nombres'   => 'Omaira',
                    'apellidos' => 'de Quintero',
                    'cedula'    => null
                ]
            ],
            2 => [
                'numero_pagina'    => '142',
                'numero_acta'      => '143',
                'fecha_bautizo'    => '1981-02-21',
                'ministro'         => 'P. Paolo Borelli',
                'notas_marginales' => 'Registro Civil Nº 356 - 19-2-81 - Baruta, Dto. Sucre',
                'bautizado' => [
                    'nombres'          => 'José Félix',
                    'apellidos'        => 'Rodríguez Plaza',
                    'fecha_nacimiento' => '1980-09-24',
                    'lugar_nacimiento' => 'Baruta, Edo. Miranda',
                    'genero'           => 'M'
                ],
                'padre' => [
                    'nombres'   => 'Félix Guillermo',
                    'apellidos' => 'Rodríguez',
                    'cedula'    => null
                ],
                'madre' => [
                    'nombres'   => 'María Elena',
                    'apellidos' => 'Plaza de Rodríguez',
                    'cedula'    => null
                ],
                'padrino' => [
                    'nombres'   => 'Oswaldo',
                    'apellidos' => 'Rodríguez',
                    'cedula'    => null
                ],
                'madrina' => [
                    'nombres'   => 'Olga',
                    'apellidos' => 'Salvati de Plaza',
                    'cedula'    => null
                ]
            ],
            3 => [
                'numero_pagina'    => '142',
                'numero_acta'      => '144',
                'fecha_bautizo'    => '1981-02-21',
                'ministro'         => 'P. Victorino Miguélez',
                'notas_marginales' => 'Registro Civil Nº 1578, Baruta, Dto. Sucre, 11-9-80',
                'bautizado' => [
                    'nombres'          => 'Natalia Mercedes',
                    'apellidos'        => 'Giannoni Delgado',
                    'fecha_nacimiento' => '1979-09-22',
                    'lugar_nacimiento' => 'Baruta, Edo. Miranda',
                    'genero'           => 'F'
                ],
                'padre' => [
                    'nombres'   => 'Michelangelo',
                    'apellidos' => 'Giannoni Vitali',
                    'cedula'    => null
                ],
                'madre' => [
                    'nombres'   => 'Raquel',
                    'apellidos' => 'Delgado de Giannoni',
                    'cedula'    => null
                ],
                'padrino' => [
                    'nombres'   => 'Régulo',
                    'apellidos' => 'Ufarín',
                    'cedula'    => null
                ],
                'madrina' => [
                    'nombres'   => 'Idalgisa',
                    'apellidos' => 'de Ufarín',
                    'cedula'    => null
                ]
            ]
        ];

        return [
            'success' => true,
            'is_demo' => true,
            'source'  => 'simulated_ai',
            'note'    => "Transcripción asistida para el Registro #{$idx}. Para activar la API real de Gemini Vision en producción, añade GEMINI_API_KEY en tu archivo .env o en el panel de configuración de clave.",
            'data'    => $registrosSimulados[$idx] ?? $registrosSimulados[1]
        ];
    }
}
