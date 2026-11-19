<?php
declare(strict_types=1);
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lab\Application\Services\BootstrapAdminService;

final class BootstrapAdmin extends Command
{
    protected $signature = 'lab:bootstrap-admin';
    protected $description = 'Prepara el administrador inicial desde la configuración del entorno.';

    public function handle(BootstrapAdminService $service): int
    {
        try {
            if (strlen((string) config('lab.jwt_secret')) < 32) {
                throw new \RuntimeException();
            }
            $service->execute((string) config('lab.admin_username'), (string) config('lab.admin_password'));
            $this->info('Administrador inicial verificado.');
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('No se pudo preparar el administrador. Revisa el entorno, la conexión y la cuenta existente.');
            return self::FAILURE;
        }
    }
}
