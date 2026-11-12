<?php

// Simulacion del sistema C (estadisticas).
// Para simular que esta caido, crear un archivo vacio llamado "caido.txt" en esta carpeta.

header("Content-Type: application/json");

if (file_exists(__DIR__ . "/caido.txt")) {
    http_response_code(503);
    echo json_encode(["error" => "Sistema C fuera de servicio"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Metodo no permitido"]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

if ($datos === null) {
    http_response_code(400);
    echo json_encode(["error" => "JSON invalido"]);
    exit;
}

// Se guarda lo recibido para poder verlo.
$archivo = __DIR__ . "/recibidas.json";
$recibidas = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];
$recibidas[] = $datos;
file_put_contents($archivo, json_encode($recibidas, JSON_PRETTY_PRINT), LOCK_EX);

echo json_encode([
    "mensaje" => "Estadistica recibida correctamente",
    "datos" => $datos
]);
