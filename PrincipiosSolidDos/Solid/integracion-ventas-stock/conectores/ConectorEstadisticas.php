<?php

// Abstraccion para el sistema C independiente de ConectorStock 
// cada integracion tiene su propia interfaz.
interface ConectorEstadisticas
{
    public function enviarEstadistica($estadistica);
}
