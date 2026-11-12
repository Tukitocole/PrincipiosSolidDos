<?php

header("Content-Type: application/json");

require_once "dto/VentaDTO.php";
require_once "adaptadores/AdaptadorVenta.php";
require_once "adaptadores/AdaptadorSistemaA.php";
require_once "adaptadores/AdaptadorSistemaA2.php";
require_once "transformadores/TransformadorVenta.php";
require_once "transformadores/TransformadorEstadistica.php";
require_once "conectores/ConectorStock.php";
require_once "conectores/ConectorStockHttp.php";
require_once "conectores/ConectorEstadisticas.php";
require_once "conectores/ConectorEstadisticasHttp.php";
require_once "repositorios/RepositorioVentas.php";
require_once "repositorios/RepositorioVentasArchivo.php";
require_once "servicios/ServicioVentas.php";
require_once "controladores/ControladorVentas.php";


// aca se arma todo y cada clase recibe lo que necesita.

$repositorio = new RepositorioVentasArchivo(__DIR__ . "/datos/ventas.json");

$transformadorVenta = new TransformadorVenta();
$transformadorEstadistica = new TransformadorEstadistica();

$conectorStock = new ConectorStockHttp(
    "http://localhost/Solid/integracion-ventas-stock/sistemaB/movimiento.php"
);

$conectorEstadisticas = new ConectorEstadisticasHttp(
    "http://localhost/Solid/integracion-ventas-stock/sistemaC/estadisticas.php"
);

$servicioVentas = new ServicioVentas(
    $repositorio,
    $transformadorVenta,
    $conectorStock,
    $transformadorEstadistica,
    $conectorEstadisticas,
    3 // maximo de intentos antes de marcar como FALLIDO
);

// para sumar un proveedor nuevo, se agrega su adaptador a esta lista.
$adaptadores = [
    new AdaptadorSistemaA(),
    new AdaptadorSistemaA2()
];

$controladorVentas = new ControladorVentas($servicioVentas, $adaptadores);


$accion = isset($_GET["accion"]) ? $_GET["accion"] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && $accion === "reintentar") {

    $controladorVentas->reintentar();

} else if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controladorVentas->recibirVenta();

} else if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $controladorVentas->consultarVentas();

} else {

    http_response_code(405);

    echo json_encode([
        "error" => "Metodo no permitido"
    ]);
}
