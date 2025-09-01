<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // runs: php artisan passport:client --personal --name="EdgeAuth PAT" --provider=users
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'EdgeAuth PAT',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }

    public function down(): void
    {
        // blunt rollback: delete clients named "EdgeAuth PAT"
        $ids = DB::table('oauth_clients')->where('name', 'EdgeAuth PAT')->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('oauth_personal_access_clients')->whereIn('client_id', $ids)->delete();
            DB::table('oauth_clients')->whereIn('id', $ids)->delete();
        }
    }
};
