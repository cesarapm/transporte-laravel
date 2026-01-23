<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;

class TestStripeConfig extends Command
{
    protected $signature = 'stripe:test-config';
    protected $description = 'Probar la configuración de Stripe';

    public function handle()
    {
        $this->info('=== Probando Configuración de Stripe ===');

        // Verificar variables de entorno
        $publicKey = env('STRIPE_PUBLIC_KEY');
        $secretKey = env('STRIPE_SECRET_KEY');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        $this->info('Variables de entorno:');
        $this->line("STRIPE_PUBLIC_KEY: " . ($publicKey ? 'Configurada (' . substr($publicKey, 0, 7) . '...)' : 'NO CONFIGURADA'));
        $this->line("STRIPE_SECRET_KEY: " . ($secretKey ? 'Configurada (' . substr($secretKey, 0, 7) . '...)' : 'NO CONFIGURADA'));
        $this->line("STRIPE_WEBHOOK_SECRET: " . ($webhookSecret ? 'Configurada' : 'NO CONFIGURADA'));

        if (!$secretKey) {
            $this->error('ERROR: STRIPE_SECRET_KEY no está configurada!');
            return 1;
        }

        // Probar configuración de Stripe
        try {
            Stripe::setApiKey($secretKey);
            $this->info('✓ API Key de Stripe configurada exitosamente');

            // Probar una consulta simple
            $balance = \Stripe\Balance::retrieve();
            $this->info('✓ Conexión con Stripe API exitosa');
            $this->line("Balance disponible: $" . ($balance->available[0]->amount / 100));

        } catch (\Exception $e) {
            $this->error('ERROR al conectar con Stripe: ' . $e->getMessage());
            return 1;
        }

        $this->info('=== Configuración de Stripe OK ===');
        return 0;
    }
}
