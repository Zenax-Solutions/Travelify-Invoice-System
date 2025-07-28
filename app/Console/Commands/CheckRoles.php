<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CheckRoles extends Command
{
    protected $signature = 'check:roles';
    protected $description = 'Check roles in database';

    public function handle()
    {
        $this->info('Checking roles in database:');

        $roles = Role::all(['id', 'name']);

        foreach ($roles as $role) {
            $this->line("ID: {$role->id} - Name: {$role->name}");
        }

        return 0;
    }
}
