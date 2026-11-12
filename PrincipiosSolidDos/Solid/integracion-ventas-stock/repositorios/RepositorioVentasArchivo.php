<?php

class RepositorioVentasArchivo implements RepositorioVentas
{
    private $archivo;

    public function __construct($archivo)
    {
        $this->archivo = $archivo;

        if (!file_exists($this->archivo)) {
            file_put_contents($this->archivo, "{}");
        }
    }

    public function registrarSiNoExiste($registro)
    {
        $guardado = false;

        $this->modificar(function ($ventas) use ($registro, &$guardado) {

            if (isset($ventas[$registro["ventaId"]])) {
                return $ventas; // ya existe, no se toca
            }

            $ventas[$registro["ventaId"]] = $registro;
            $guardado = true;

            return $ventas;
        });

        return $guardado;
    }

    public function buscar($ventaId)
    {
        $ventas = $this->leer();

        return isset($ventas[$ventaId]) ? $ventas[$ventaId] : null;
    }

    public function actualizar($registro)
    {
        $this->modificar(function ($ventas) use ($registro) {
            $ventas[$registro["ventaId"]] = $registro;
            return $ventas;
        });
    }

    public function listar($estado = null)
    {
        $resultado = [];

        foreach ($this->leer() as $registro) {
            if ($estado === null || $registro["estado"] === $estado) {
                $resultado[] = $registro;
            }
        }

        return $resultado;
    }

    private function leer()
    {
        $manejador = fopen($this->archivo, "r");
        flock($manejador, LOCK_SH);

        $contenido = stream_get_contents($manejador);

        flock($manejador, LOCK_UN);
        fclose($manejador);

        $ventas = json_decode($contenido, true);

        return is_array($ventas) ? $ventas : [];
    }

    // Lee, modifica y escribe el archivo con un bloqueo exclusivo, si llegan dos pedidos iguales al mismo tiempo, solo uno puede registrar la venta
    private function modificar($funcion)
    {
        $manejador = fopen($this->archivo, "c+");
        flock($manejador, LOCK_EX);

        $ventas = json_decode(stream_get_contents($manejador), true);

        if (!is_array($ventas)) {
            $ventas = [];
        }

        $ventas = $funcion($ventas);

        ftruncate($manejador, 0);
        rewind($manejador);
        fwrite($manejador, json_encode($ventas, JSON_PRETTY_PRINT));
        fflush($manejador);

        flock($manejador, LOCK_UN);
        fclose($manejador);
    }
}
