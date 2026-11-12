<?php
class AdaptadorSistemaA implements AdaptadorVenta
{
    public function puedeAdaptar($datos)
    {
        return isset($datos["ventaId"]) && isset($datos["productos"]);
    }

    public function adaptar($datos)
    {
        $productos = [];

        foreach ($datos["productos"] as $producto) {
            $productos[] = [
                "codigo" => $producto["codigo"],
                "cantidad" => $producto["cantidad"],
                "precio" => isset($producto["precio"]) ? $producto["precio"] : 0
            ];
        }

        return new VentaDTO([
            "ventaId" => $datos["ventaId"],
            "fecha" => isset($datos["fecha"]) ? $datos["fecha"] : null,
            "cajeroId" => isset($datos["cajeroId"]) ? $datos["cajeroId"] : null,
            "productos" => $productos,
            "importeTotal" => isset($datos["importeTotal"]) ? $datos["importeTotal"] : null,
            "origen" => "A"
        ]);
    }
}
