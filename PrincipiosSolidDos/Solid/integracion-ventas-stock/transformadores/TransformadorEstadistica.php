<?php

// Mapper: convierte la venta al formato que espera el sistema C.
class TransformadorEstadistica
{
    public function transformar(VentaDTO $venta)
    {
        $cantidadProductos = 0;
        $importeCalculado = 0;

        foreach ($venta->productos as $producto) {
            $cantidadProductos += $producto["cantidad"];
            $importeCalculado += $producto["cantidad"] * $producto["precio"];
        }

        // Si el sistema de origen manda el total, se usa ese y si no, se calcula con los precios de los productos.
        $importeTotal = $venta->importeTotal !== null ? $venta->importeTotal : $importeCalculado;

        return [
            "ventaId" => is_numeric($venta->ventaId) ? (int) $venta->ventaId : $venta->ventaId,
            "cantidadProductos" => $cantidadProductos,
            "importeTotal" => $importeTotal
        ];
    }
}
