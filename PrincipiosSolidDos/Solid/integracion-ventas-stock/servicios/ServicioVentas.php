<?php

// Service: coordina el proceso de negocio no sabe de que sistema vino la venta (eso lo resuelven los adaptadores) ni como se habla con B o C (eso lo hacen los conectores).
class ServicioVentas
{
    const PENDIENTE = "PENDIENTE";
    const PROCESADO = "PROCESADO";
    const FALLIDO = "FALLIDO";

    private $repositorio;
    private $transformadorVenta;
    private $conectorStock;
    private $transformadorEstadistica;
    private $conectorEstadisticas;
    private $maxIntentos;

    public function __construct(
        RepositorioVentas $repositorio,
        $transformadorVenta,
        ConectorStock $conectorStock,
        $transformadorEstadistica,
        ConectorEstadisticas $conectorEstadisticas,
        $maxIntentos = 3
    ) {
        $this->repositorio = $repositorio;
        $this->transformadorVenta = $transformadorVenta;
        $this->conectorStock = $conectorStock;
        $this->transformadorEstadistica = $transformadorEstadistica;
        $this->conectorEstadisticas = $conectorEstadisticas;
        $this->maxIntentos = $maxIntentos;
    }

    public function procesarVenta(VentaDTO $venta)
    {
        $registro = [
            "ventaId" => $venta->ventaId,
            "origen" => $venta->origen,
            "venta" => $venta->aArray(),
            "fechaRecepcion" => date("c"),

            // Estado de la comunicacion con B (stock)
            "estado" => self::PENDIENTE,
            "intentos" => 0,
            "ultimoIntento" => null,
            "ultimoError" => null,

            // Estado de la comunicacion con C (estadisticas), separado del de B
            "estadoEstadisticas" => self::PENDIENTE,
            "intentosEstadisticas" => 0,
            "ultimoErrorEstadisticas" => null
        ];

        //primero se guarda la venta si ya existia no se procesa de nuevo.
        if (!$this->repositorio->registrarSiNoExiste($registro)) {

            $existente = $this->repositorio->buscar($venta->ventaId);

            return [
                "duplicada" => true,
                "mensaje" => "La venta ya fue recibida, no se vuelve a descontar stock",
                "registro" => $existente
            ];
        }

       // se intenta enviar a B y a C, cada uno por su lado.
        $registro = $this->intentarStock($registro);
        $registro = $this->intentarEstadisticas($registro);

        return [
            "duplicada" => false,
            "registro" => $registro
        ];
    }

    // Reintentos: vuelve a procesar todo lo que haya quedado pendiente.
    public function reintentarPendientes()
    {
        $reintentadas = [];

        foreach ($this->repositorio->listar() as $registro) {

            $tocado = false;

            if ($registro["estado"] === self::PENDIENTE) {
                $registro = $this->intentarStock($registro);
                $tocado = true;
            }

            if ($registro["estadoEstadisticas"] === self::PENDIENTE) {
                $registro = $this->intentarEstadisticas($registro);
                $tocado = true;
            }

            if ($tocado) {
                $reintentadas[] = $registro;
            }
        }

        return $reintentadas;
    }

    // Permite reintentar a mano una venta puntual, incluso si quedo FALLIDA.
    public function reintentarVenta($ventaId)
    {
        $registro = $this->repositorio->buscar((string) $ventaId);

        if ($registro === null) {
            throw new Exception("No existe la venta " . $ventaId);
        }

        if ($registro["estado"] !== self::PROCESADO) {
            $registro["estado"] = self::PENDIENTE;
            $registro["intentos"] = 0;
            $registro = $this->intentarStock($registro);
        }

        if ($registro["estadoEstadisticas"] !== self::PROCESADO) {
            $registro["estadoEstadisticas"] = self::PENDIENTE;
            $registro["intentosEstadisticas"] = 0;
            $registro = $this->intentarEstadisticas($registro);
        }

        return $registro;
    }

    // Consulta para el area de gestion de stock.
    public function consultarVentas($estado = null)
    {
        return $this->repositorio->listar($estado);
    }

    private function intentarStock($registro)
    {
        $venta = new VentaDTO($registro["venta"]);

        $registro["intentos"]++;
        $registro["ultimoIntento"] = date("c");

        try {
            $movimiento = $this->transformadorVenta->transformar($venta);

            $respuesta = $this->conectorStock->enviarMovimiento($movimiento);

            $registro["estado"] = self::PROCESADO;
            $registro["ultimoError"] = null;
            $registro["respuestaSistemaB"] = $respuesta;

        } catch (Exception $error) {

            $registro["ultimoError"] = $error->getMessage();

            if ($registro["intentos"] >= $this->maxIntentos) {
                $registro["estado"] = self::FALLIDO;
            } else {
                $registro["estado"] = self::PENDIENTE;
            }
        }

        $this->repositorio->actualizar($registro);

        return $registro;
    }

    private function intentarEstadisticas($registro)
    {
        $venta = new VentaDTO($registro["venta"]);

        $registro["intentosEstadisticas"]++;

        // Si C falla, solo se marca su estado. No afecta al stock.
        try {
            $estadistica = $this->transformadorEstadistica->transformar($venta);

            $this->conectorEstadisticas->enviarEstadistica($estadistica);

            $registro["estadoEstadisticas"] = self::PROCESADO;
            $registro["ultimoErrorEstadisticas"] = null;

        } catch (Exception $error) {

            $registro["ultimoErrorEstadisticas"] = $error->getMessage();

            if ($registro["intentosEstadisticas"] >= $this->maxIntentos) {
                $registro["estadoEstadisticas"] = self::FALLIDO;
            } else {
                $registro["estadoEstadisticas"] = self::PENDIENTE;
            }
        }

        $this->repositorio->actualizar($registro);

        return $registro;
    }
}
