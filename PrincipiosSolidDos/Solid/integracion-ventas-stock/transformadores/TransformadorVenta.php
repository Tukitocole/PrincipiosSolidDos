<?php

class TransformadorVenta
{
    public function transformar(VentaDTO $venta)
    {
        $movimiento = [
            "tipoMovimiento" => "SALIDA",
            "fecha" => $venta->fecha,
            "referencia" => "VENTA-" . $venta->ventaId,
            "productos" => []
        ];

        foreach ($venta->productos as $producto) {

            $movimiento["productos"][] = [
                "codigoProducto" => $producto["codigo"],
                "cantidad" => $producto["cantidad"]
            ];
        }

        return $movimiento;
    }
}