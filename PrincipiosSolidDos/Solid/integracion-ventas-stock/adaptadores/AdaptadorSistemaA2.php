<?php

class AdaptadorSistemaA2 implements AdaptadorVenta
{
    public function puedeAdaptar($datos)
    {
        return isset($datos["id"]) && isset($datos["items"]);
    }

    public function adaptar($datos)
    {
        $productos = [];

        foreach ($datos["items"] as $item) {
            $productos[] = [
                "codigo" => $item["sku"],
                "cantidad" => $item["units"],
                "precio" => isset($item["price"]) ? $item["price"] : 0
            ];
        }

        return new VentaDTO([
            "ventaId" => $datos["id"],
            "fecha" => isset($datos["timestamp"]) ? $datos["timestamp"] : null,
            "cajeroId" => null,
            "productos" => $productos,
            "importeTotal" => isset($datos["total"]) ? $datos["total"] : null,
            "origen" => "A2"
        ]);
    }
}
