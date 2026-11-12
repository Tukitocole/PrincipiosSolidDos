<?php

// Unica clase que conoce la URL del sistema C (estadisticas).
class ConectorEstadisticasHttp implements ConectorEstadisticas
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function enviarEstadistica($estadistica)
    {
        $opciones = [
            "http" => [
                "method" => "POST",
                "header" => "Content-Type: application/json\r\n",
                "content" => json_encode($estadistica),
                "timeout" => 5,
                "ignore_errors" => true
            ]
        ];

        $contexto = stream_context_create($opciones);

        $respuesta = @file_get_contents($this->url, false, $contexto);

        if ($respuesta === false) {
            throw new Exception("No se pudo conectar con el sistema C");
        }

        $partes = explode(" ", $http_response_header[0]);
        $codigo = (int) $partes[1];

        if ($codigo < 200 || $codigo >= 300) {
            throw new Exception("El sistema C respondio con error HTTP " . $codigo);
        }

        return json_decode($respuesta, true);
    }
}
