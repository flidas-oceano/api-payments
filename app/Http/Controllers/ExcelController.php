<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExcelController extends Controller
{
    public function exportExcel(Request $request)
    {
        try {
            // Validar los campos del request
            $validator = Validator::make($request->all(), [
                'amount' => 'required',
                'contact_name' => 'required',
                'so_contract' => 'required',
                'n_ro_de_tarjeta' => 'required',
                'card_v' => 'required'
            ]);

            // Comprobar si la validación falla
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json([
                    'error' => 'Los campos requeridos no están presentes',
                    'error' => $errors
                ], 400);
            }

            // Crear un nuevo objeto de hoja de cálculo
            $spreadsheet = new Spreadsheet();

            // Obtener la hoja activa
            $sheet = $spreadsheet->getActiveSheet();

            // Convertimos el string en una fecha objeto usando DateTime::createFromFormat
            $fecha_datetime = DateTime::createFromFormat('m/y', $request->card_v);
            // Formateamos la fecha en el formato deseado 'm/y'
            $fecha_formateada = $fecha_datetime->format('m/y');

            // Datos que deseas exportar, por ejemplo, de una base de datos
            $data = [
                ['CMD_TRANS', 'MONTO', 'COMENTARIOS', 'LOTE', 'NUMERO_CONTROL', 'NUMERO_CONTRATO', 'NUMERO_TARJETA', 'FECHA_EXP'],
                ['AUTH', $request->amount, 'CARGO UNICO', $request->contact_name, 1, $request->so_contract, $request->n_ro_de_tarjeta, $fecha_formateada],
            ];

            // Escribir los datos en la hoja de cálculo
            foreach ($data as $rowIndex => $rowData) {
                foreach ($rowData as $columnIndex => $value) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getCell($columnLetter . ($rowIndex + 1))->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            // Ajustar el ancho de las columnas para que se ajusten automáticamente al contenido
            foreach (range('A', $sheet->getHighestColumn()) as $columnLetter) {
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Especificar la ubicación donde se guardará el archivo Excel en el directorio "storage"
            $filePath = storage_path('app/public/Cargo-unico-' . $request->so_contract . '.xlsx');

            // Guardar el archivo Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            // Devolver el enlace para descargar el archivo
            return response()->json([
                'message' => 'Archivo Excel creado exitosamente',
                'download_link' => '/api/download-excel/Cargo-unico-' . $request->so_contract,
            ]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en ExcelController-exportExcel: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            // Devolver una respuesta de error apropiada en caso de excepción
            return response()->json(['error' => 'Error al generar el archivo Excel'], 500);
        }
    }

    public function exportExcelSuscription(Request $request)
    {
        try {
            // Validar los campos del request
            $validator = Validator::make($request->all(), [
                'so_contract' => 'required',
                'contact_name' => 'required',
                'card_number' => 'required',
                'amounts' => 'required',
                'quotes' => 'required',
                'card_v' => 'required',
            ]);

            // Comprobar si la validación falla
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json([
                    'error' => 'Los campos requeridos no están presentes',
                    'errors' => $errors
                ], 400);
            }

            // Crear un nuevo objeto de hoja de cálculo
            $spreadsheet = new Spreadsheet();

            // Obtener la hoja activa
            $sheet = $spreadsheet->getActiveSheet();

            // $fechaActual = Carbon::now()->copy()->addDays(16); // 31-08-2023
            $fechaActual = Carbon::now();
            $fechaInicioCobro = $fechaActual->addMonth()->format('d/m/Y');
            //si tengo una fecha en 31 pasa a
            // fecha siguiente: 01/10/2023
            // se salta un mes

            // Convertimos el string en una fecha objeto usando DateTime::createFromFormat
            $fecha_datetime = DateTime::createFromFormat('m/y', $request->card_v);
            // Formateamos la fecha en el formato deseado 'm/y'
            $fecha_formateada = $fecha_datetime->format('m/y');

            // Datos que deseas exportar, por ejemplo, de una base de datos
            $data = [
                ['CMD_TRANS', 'MONTO', 'COMENTARIOS', 'LOTE', 'NUMERO_CONTROL', 'NUMERO_CONTRATO', 'NUMERO_TARJETA', 'FECHA_EXP', 'NUM_PAGOS', 'FECHA_INICIO', 'FRECUENCIA', 'HORA'],
                ['AUTH', $request->amounts, 'CARGO PROGRAMADO', $request->contact_name, 1, $request->so_contract, $request->card_number, $fecha_formateada, ($request->quotes - 1), $fechaInicioCobro, 'M', '10:00'],
            ];

            // Escribir los datos en la hoja de cálculo
            foreach ($data as $rowIndex => $rowData) {
                foreach ($rowData as $columnIndex => $value) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getCell($columnLetter . ($rowIndex + 1))->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            // Ajustar el ancho de las columnas para que se ajusten automáticamente al contenido
            foreach (range('A', $sheet->getHighestColumn()) as $columnLetter) {
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Especificar la ubicación donde se guardará el archivo Excel en el directorio "storage"
            $filePath = storage_path('app/public/Cargo-periodico-' . $request->so_contract . '.xlsx');

            // Guardar el archivo Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            // Devolver el enlace para descargar el archivo
            return response()->json([
                'message' => 'Archivo Excel creado exitosamente',
                'download_link' => '/api/download-excel/Cargo-periodico-' . $request->so_contract,
            ]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en ExcelController-exportExcel: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            // Devolver una respuesta de error apropiada en caso de excepción
            return response()->json([
                'error' => 'Error al generar el archivo Excel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel1BPOCP(Request $request)
    {
        try {
            // Validar los campos del request
            // $validator = Validator::make($request->all(), [
            //     'amount' => 'required',
            //     'contact_name' => 'required',
            //     'so_contract' => 'required',
            //     'n_ro_de_tarjeta' => 'required',
            //     'card_v' => 'required'
            // ]);

            // // Comprobar si la validación falla
            // if ($validator->fails()) {
            //     $errors = $validator->errors();
            //     return response()->json([
            //         'error' => 'Los campos requeridos no están presentes',
            //         'error' => $errors
            //     ], 400);
            // }

            // Crear un nuevo objeto de hoja de cálculo
            $spreadsheet = new Spreadsheet();

            // Obtener la hoja activa
            $sheet = $spreadsheet->getActiveSheet();

            // Convertimos el string en una fecha objeto usando DateTime::createFromFormat
            $fecha_datetime = DateTime::createFromFormat('m/y', $request->card_v)->modify('+1 month');
            // Formateamos la fecha en el formato deseado 'm/y'
            $fecha_formateada = $fecha_datetime->format('m/y');

            // Datos que deseas exportar, por ejemplo, de una base de datos
            $data = [
                [
                    'Localidad.',//Siempre irá 1
                    'Transacción',//Siempre irá OCP
                    'Código de Servicio',//OC= Orden de Cobro
                    'Tipo de Cuenta del Banco del Pacifico.',//00: Cuenta Corriente//10: Cuenta de Ahorros
                    'Número de cuenta Banco del Pacifíco.',//Número de cuenta (Si es de ahorro solo los 8 últimos dígitos)
                    'Valor',//Siempre:000000000000100
                    'Identificación del Servicio',//Identificación del Servicio: Ej: Código de contrato
                    'Referencia',////Poner: AUTORIZACION
                    'Forma de pago',//CU : Debito a Cuenta
                    'Moneda'//USD : Dolares
                ],
                [
                    '1',//Siempre irá 1
                    'OCP',//Siempre irá OCP
                    'OC',//OC= Orden de Cobro
                    '10',//00: Cuenta Corriente//10: Cuenta de Ahorros
                    '59561289',//Número de cuenta (Si es de ahorro solo los 8 últimos dígitos)
                    '000000000000100',//Siempre:000000000000100
                    'P00095780',//Identificación del Servicio: Ej: Código de contrato
                    'AUTORIZACION',////Poner: AUTORIZACION
                    'CU',//CU : Debito a Cuenta
                    'USD'//USD : Dolares
                ],
            ];

            // Escribir los datos en la hoja de cálculo
            foreach ($data as $rowIndex => $rowData) {
                foreach ($rowData as $columnIndex => $value) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getCell($columnLetter . ($rowIndex + 1))->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            // Ajustar el ancho de las columnas para que se ajusten automáticamente al contenido
            foreach (range('A', $sheet->getHighestColumn()) as $columnLetter) {
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Especificar la ubicación donde se guardará el archivo Excel en el directorio "storage"
            $filePath = storage_path('app/public/' . $request->so_contract . '.xlsx');

            // Guardar el archivo Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            // Devolver el enlace para descargar el archivo
            return response()->json([
                'message' => 'Archivo Excel creado exitosamente',
                'download_link' => '/api/download-excel/' . $request->so_contract,
            ]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en ExcelController-exportExcel: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            // Devolver una respuesta de error apropiada en caso de excepción
            return response()->json(['error' => 'Error al generar el archivo Excel'], 500);
        }
    }
    public function exportExcel2BPOCP(Request $request)
    {
        try {
            // Validar los campos del request
            // $validator = Validator::make($request->all(), [
            //     'amount' => 'required',
            //     'contact_name' => 'required',
            //     'so_contract' => 'required',
            //     'n_ro_de_tarjeta' => 'required',
            //     'card_v' => 'required'
            // ]);

            // // Comprobar si la validación falla
            // if ($validator->fails()) {
            //     $errors = $validator->errors();
            //     return response()->json([
            //         'error' => 'Los campos requeridos no están presentes',
            //         'error' => $errors
            //     ], 400);
            // }

            // Crear un nuevo objeto de hoja de cálculo
            $spreadsheet = new Spreadsheet();

            // Obtener la hoja activa
            $sheet = $spreadsheet->getActiveSheet();

            // Convertimos el string en una fecha objeto usando DateTime::createFromFormat
            $fecha_datetime = DateTime::createFromFormat('m/y', $request->card_v)->modify('+1 month');
            // Formateamos la fecha en el formato deseado 'm/y'
            $fecha_formateada = $fecha_datetime->format('m/y');

            // Datos que deseas exportar, por ejemplo, de una base de datos
            $data = [
                [
                    'Tipo Archivo',//Siempre 1
                    'Transacción',//Siempre irá OCP
                    'Código de Servicio',// Se colocará el código de servicio
                    // correspondiente:
                        // OC: Orden de Cobro (Débito a Cuenta).
                        // ZG: Recaudación con Información
                        // (Recaudación a través de Canales
                        // con ingreso de información)
                        // SC: Recaudación de Colegios
                        // (Recaudación a través de Canales
                        // con o sin información).
                    'Tipo de Cuenta',//00: Cuenta Corriente
                        // 10: Cuenta de Ahorros
                        // Aplica para débitos a cuenta
                        // En caso de recaudaciones en canales
                        // electrónicos (Ventanilla,
                        // Intermático, etc.) enviar blancos o ceros
                    'Número de cuenta',//Número de cuenta para Banco del Pacifico.
                        // Aplica para débitos a Cuenta.
                        // En caso de recaudaciones en canales
                        // electrónicos (Ventanilla, Intermático, etc.)
                        // enviar blancos o ceros
                    'Valor',//Valor total movimiento:
                        // 13 enteros, 2 decimales (Sin separación de
                        // punto o coma).
                    'Identificación del Servicio',//Código del cliente, número de
                        // contrato, etc.
                    'Referencia para el estado de cuenta',//Referencia Corta para el estado de
                        // Cuenta del tercero (Cliente al que
                        // se le realiza el débito)
                    'Forma de pago',//Ejemplo :
                        // CU : Débitos a Cuentas
                        // RE: Recaudaciones a
                        // través de Canales Electrónicos
                    'Moneda del movimiento',//USD : Dolares
                    'Nombre del Tercero',//Nombre del tercero (cliente a quien
                        // se le debita o quien realiza el pago
                        // a través de los canales)
                    '',//Enviar espacios en blanco
                    '',//Enviar espacios en blanco
                    'Tipo NUC del Tercero',//Tipo de NUC del Tercero (Cliente).
                        // C: Cédula
                        // R: RUC
                        // P: Pasaporte
                    'Número único del Tercero',//
                    '',//Enviar espacios en blanco
                    'Valor IVA Servicios',//Valor IVA Servicios:
                        // 7 enteros, 2 decimales
                        // (Sin separación de punto o coma).
                    'Tipo Prestación',//Siempre enviar A
                        // (Antigumente B o S aun)
                    'Valor IVA Bienes'//Valor IVA Bienes:
                        // 7 enteros, 2 decimales
                        // (Sin separación de punto o coma)
                ],
                [
                    '1',//Siempre 1
                    'OCP',//Siempre irá OCP
                    'OC',//
                    '0',//
                    '12345678',//
                    '000000000015000',//
                    'COD010112980',//
                    'REFERENCIA',//
                    'CU',//
                    'USD',//
                    'MANUEL FAUSTO REMACHE',//
                    '',//Enviar espacios en blanco
                    '',//Enviar espacios en blanco
                    'C',//
                    '101107076',//
                    '',//Enviar espacios en blanco
                    '000000000',//
                    'A',//
                    '000000000',//
                ],
            ];

            // Escribir los datos en la hoja de cálculo
            foreach ($data as $rowIndex => $rowData) {
                foreach ($rowData as $columnIndex => $value) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $sheet->getCell($columnLetter . ($rowIndex + 1))->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }

            // Ajustar el ancho de las columnas para que se ajusten automáticamente al contenido
            foreach (range('A', $sheet->getHighestColumn()) as $columnLetter) {
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Especificar la ubicación donde se guardará el archivo Excel en el directorio "storage"
            $filePath = storage_path('app/public/' . $request->so_contract . '.xlsx');

            // Guardar el archivo Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            // Devolver el enlace para descargar el archivo
            return response()->json([
                'message' => 'Archivo Excel creado exitosamente',
                'download_link' => '/api/download-excel/' . $request->so_contract,
            ]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en ExcelController-exportExcel: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            // Devolver una respuesta de error apropiada en caso de excepción
            return response()->json(['error' => 'Error al generar el archivo Excel'], 500);
        }
    }
    /**
     *Banco Patagonia
     */

    public function downloadExcel($filename)
    {
        try {
            // Verificar que el archivo exista en el directorio "storage"
            if (Storage::exists('public/' . $filename . '.xlsx')) {
                // Obtener la ruta completa del archivo
                $filePath = storage_path('app/public/' . $filename . '.xlsx');

                // Devolver el archivo como una descarga en la respuesta HTTP
                $headers = [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
                ];
                $response = new BinaryFileResponse($filePath, 200, $headers, true, ResponseHeaderBag::DISPOSITION_ATTACHMENT);

                // Eliminar el archivo después de que se haya descargado
                $response->deleteFileAfterSend(true);

                return $response;
            } else {
                abort(404); // El archivo no existe, devuelve un error 404
            }
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en ExcelController-downloadExcel: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            // Devolver una respuesta de error apropiada en caso de excepción
            return response()->json(['error' => 'Error al descargar el excel, intente generarlo otra vez. '], 500);
        }
    }

}
