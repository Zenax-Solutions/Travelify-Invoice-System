<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--compress : Compress the backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = 'backup_' . Carbon::now()->format('Y_m_d_H_i_s') . '.sql';
        $compress = $this->option('compress');

        if ($compress) {
            $filename .= '.gz';
        }

        try {
            $this->info('Starting database backup...');

            // Get database configuration
            $database = config('database.connections.' . config('database.default'));

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s',
                $database['username'],
                $database['password'],
                $database['host'],
                $database['port'] ?? 3306,
                $database['database']
            );

            if ($compress) {
                $command .= ' | gzip';
            }

            // Execute backup
            $backupPath = storage_path('app/backups/' . $filename);

            // Ensure backup directory exists
            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            $command .= ' > ' . $backupPath;

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $size = number_format(filesize($backupPath) / 1024 / 1024, 2);
                $this->info("Backup completed successfully!");
                $this->info("File: {$filename}");
                $this->info("Size: {$size} MB");
                $this->info("Location: {$backupPath}");
            } else {
                $this->error('Backup failed! Check your database configuration and mysqldump availability.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
