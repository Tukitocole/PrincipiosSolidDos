<?php

// Unica clase que conoce la URL del sistema B (ERP).
class ConectorStockHttp implements ConectorStock
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function enviarMovimiento($movimiento)
    {
        $opciones = [
            "http" => [
                "method" => "POST",
                // La referencia viaja tambien como clave para asi el sistema B puede reconocer un movimiento repetido.
                "header" => "Content-Type: application/json\r\n" .
                            "Idempotency-Key: " . $movimiento["referencia"] . "\r\n",
                "content" => json_encode($movimiento),
                "timeout" => 5,
                "ignore_errors" => true
            ]
        ];

        $contexto = stream_context_create($opciones);

        $respuesta = @file_get_contents($this->url, false, $contexto);

        if ($respuesta === false) {
            throw new Exception("No se pudo conectar con el sistema B");
        }

        // $http_response_header lo completa PHP con los encabezados de la respuesta
    
        $partes = explode(" ", $http_response_header[0]);
        $codigo = (int) $partes[1];

        if ($codigo < 200 || $codigo >= 300) {
            throw new Exception("El sistema B respondio con error HTTP " . $codigo);
        }

        return json_decode($respuesta, true);
    }
}
