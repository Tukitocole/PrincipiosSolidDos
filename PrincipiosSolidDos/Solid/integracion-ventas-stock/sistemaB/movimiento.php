<?php

// Simulacion del sistema B (ERP de stock).
// Para simular que esta caido, crear un archivo vacio llamado "caido.txt" en esta carpeta.

header("Content-Type: application/json");

if (file_exists(__DIR__ . "/caido.txt")) {
    http_response_code(503);
    echo json_encode(["error" => "Sistema B fuera de servicio"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Metodo no permitido"]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

if ($datos === null || !isset($datos["referencia"])) {
    http_response_code(400);
    echo json_encode(["error" => "JSON invalido"]);
    exit;
}

// Segunda barrera contra duplicados: B tambien recuerda las referencias que ya registro.
$archivo = __DIR__ . "/movimientos.json";
$movimientos = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];

if (isset($movimientos[$datos["referencia"]])) {
    echo json_encode([
        "mensaje" => "El movimiento ya habia sido registrado",
        "movimiento" => $movimientos[$datos["referencia"]]
    ]);
    exit;
}

$movimientos[$datos["referencia"]] = $datos;
file_put_contents($archivo, json_encode($movimientos, JSON_PRETTY_PRINT), LOCK_EX);

echo json_encode([
    "mensaje" => "Movimiento de stock recibido correctamente",
    "movimiento" => $datos
]);
