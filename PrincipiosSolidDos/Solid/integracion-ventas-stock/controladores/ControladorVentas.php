<?php

// Controller: recibe la peticion, llama al servicio y devuelve la respuesta.
class ControladorVentas
{
    private $servicioVentas;
    private $adaptadores;

    public function __construct($servicioVentas, $adaptadores)
    {
        $this->servicioVentas = $servicioVentas;
        $this->adaptadores = $adaptadores;
    }

    // POST index.php
    public function recibirVenta()
    {
        try {
            $datos = json_decode(file_get_contents("php://input"), true);

            if ($datos === null) {
                $this->responder(400, ["error" => "El JSON recibido no es valido"]);
                return;
            }

            $adaptador = $this->buscarAdaptador($datos);

            if ($adaptador === null) {
                $this->responder(400, ["error" => "Formato de venta desconocido"]);
                return;
            }

            $venta = $adaptador->adaptar($datos);

            $resultado = $this->servicioVentas->procesarVenta($venta);

            if ($resultado["duplicada"]) {
                $this->responder(200, $resultado);
                return;
            }

            if ($resultado["registro"]["estado"] === ServicioVentas::PROCESADO) {
                $this->responder(200, [
                    "mensaje" => "Venta procesada correctamente",
                    "registro" => $resultado["registro"]
                ]);
                return;
            }

            // El ERP no respondio: la venta no se pierde, queda pendiente.
            $this->responder(202, [
                "mensaje" => "El sistema B no esta disponible. La venta quedo pendiente para reintentar",
                "registro" => $resultado["registro"]
            ]);

        } catch (Exception $error) {
            $this->responder(500, ["error" => $error->getMessage()]);
        }
    }

   
    public function consultarVentas()
    {
        $estado = isset($_GET["estado"]) ? strtoupper($_GET["estado"]) : null;

        $ventas = $this->servicioVentas->consultarVentas($estado);

        $this->responder(200, [
            "cantidad" => count($ventas),
            "ventas" => $ventas
        ]);
    }
    public function reintentar()
    {
        try {
            if (isset($_GET["ventaId"])) {
                $registro = $this->servicioVentas->reintentarVenta($_GET["ventaId"]);
                $this->responder(200, ["registro" => $registro]);
                return;
            }

            $reintentadas = $this->servicioVentas->reintentarPendientes();

            $this->responder(200, [
                "cantidadReintentadas" => count($reintentadas),
                "ventas" => $reintentadas
            ]);

        } catch (Exception $error) {
            $this->responder(404, ["error" => $error->getMessage()]);
        }
    }

    private function buscarAdaptador($datos)
    {
        foreach ($this->adaptadores as $adaptador) {
            if ($adaptador->puedeAdaptar($datos)) {
                return $adaptador;
            }
        }

        return null;
    }

    private function responder($codigo, $datos)
    {
        http_response_code($codigo);
        echo json_encode($datos);
    }
}
