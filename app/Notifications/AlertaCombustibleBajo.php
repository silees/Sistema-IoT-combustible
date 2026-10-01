<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertaCombustibleBajo extends Notification
{
    use Queueable;

    public $estacion;
    public $lectura;

    public function __construct($estacion, $lectura)
    {
        $this->estacion = $estacion;
        $this->lectura = $lectura;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $porcentaje = number_format($this->lectura->porcentaje, 1);
        $litros = number_format($this->lectura->litros, 0);
        $esCritico = $this->lectura->estado === 'Crítico';

        $color = $esCritico ? '#ef4444' : '#f59e0b';
        $titulo = $esCritico 
            ? "🚨 ¡ALERTA CRÍTICA! Combustible casi agotado en {$this->estacion->nombre}"
            : "⚠️ ADVERTENCIA: Nivel bajo de combustible en {$this->estacion->nombre}";

        return (new MailMessage)
            ->subject($titulo)
            ->greeting("Atención, Administrador")
            ->line("Se ha detectado una reducción importante en el nivel de combustible de la estación:")
            ->line("**Estación:** {$this->estacion->nombre}")
            ->line("**Ubicación:** {$this->estacion->ubicacion}")
            ->line("**Nivel Actual:** {$porcentaje}% ({$litros} Litros)")
            ->line("**Estado:** {$this->lectura->estado}")
            ->action('Ver Dashboard en Vivo', url("/estacion/{$this->estacion->id}"))
            ->line("Por favor, coordine el reabastecimiento a la brevedad.");
    }
}