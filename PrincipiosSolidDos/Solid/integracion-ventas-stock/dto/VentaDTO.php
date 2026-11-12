<?php


class VentaDTO
{
    public $ventaId;
    public $fecha;
    public $cajeroId;
    public $productos;
    public $importeTotal;
    public $origen;

    public function __construct($datos)
    {
        // El id se guarda siempre como texto: A manda 154872 y A2 manda "154872" asi las dos son reconocidas como la misma venta.
        
        $this->ventaId = (string) $datos["ventaId"];
        $this->fecha = isset($datos["fecha"]) ? $datos["fecha"] : date("c");
        $this->cajeroId = isset($datos["cajeroId"]) ? $datos["cajeroId"] : null;
        $this->productos = $datos["productos"];
        $this->importeTotal = isset($datos["importeTotal"]) ? $datos["importeTotal"] : null;
        $this->origen = isset($datos["origen"]) ? $datos["origen"] : null;
    }

    // Se usa para guardar la venta en el repositorio y poder reintentarla despues.
    public function aArray()
    {
        return [
            "ventaId" => $this->ventaId,
            "fecha" => $this->fecha,
            "cajeroId" => $this->cajeroId,
            "productos" => $this->productos,
            "importeTotal" => $this->importeTotal,
            "origen" => $this->origen
        ];
    }
}
