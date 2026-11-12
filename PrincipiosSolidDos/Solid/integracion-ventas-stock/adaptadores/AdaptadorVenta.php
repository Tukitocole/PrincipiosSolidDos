<?php

// ccda sistema que envia ventas tiene su propio adaptador.
// para sumar un proveedor nuevo se crea otro adaptador sin tocar el servicio.
interface AdaptadorVenta
{
    public function puedeAdaptar($datos);

    public function adaptar($datos);
}
