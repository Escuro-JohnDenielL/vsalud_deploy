<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Dump MySQL database and upload to Cloudflare R2';

    public function handle()
    {
        $this->info('Dumping database...');

        $filename = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql.gz';
        $tempPath = storage_path("app/temp-backup/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $dump = new Process([
            'mysqldump',
            '--host=' . env('DB_HOST'),
            '--port=' . env('DB_PORT'),
            '--user=' . env('DB_USERNAME'),
            '--password=' . env('DB_PASSWORD'),
            '--skip-ssl',
            '--single-transaction',
            '--routines',
            '--triggers',
            env('DB_DATABASE'),
        ]);
        $dump->setTimeout(300);
        $dump->mustRun();

        file_put_contents($tempPath, gzencode($dump->getOutput(), 9));
        $this->info('✓ Database dumped');

        $this->info('Uploading to R2...');
        Storage::disk('r2')->put("database-backups/{$filename}", file_get_contents($tempPath));
        $this->info('✓ Uploaded to R2: database-backups/' . $filename);

        unlink($tempPath);

        $this->info('Backup complete!');
    }
}
