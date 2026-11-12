<?php

// Abstraccion para guardar el estado de las ventas.
// demomento se guarda en un archivo JSON, en otra implementacion podria ser una base de datos.
interface RepositorioVentas
{
    // Devuelve true si la venta se guardo, falso si ya existia.
    public function registrarSiNoExiste($registro);

    public function buscar($ventaId);

    public function actualizar($registro);

    public function listar($estado = null);
}
