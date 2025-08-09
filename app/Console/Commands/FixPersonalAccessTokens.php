<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPersonalAccessTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:personal-access-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix personal access tokens table by adding missing columns';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking personal_access_tokens table...');
        
        if (!Schema::hasTable('personal_access_tokens')) {
            $this->error('personal_access_tokens table does not exist!');
            $this->info('Please run: php artisan migrate');
            return 1;
        }
        
        $columns = Schema::getColumnListing('personal_access_tokens');
        $this->info('Current columns: ' . implode(', ', $columns));
        
        // Add expires_at column if missing
        if (!in_array('expires_at', $columns)) {
            $this->info('Adding expires_at column...');
            DB::statement('ALTER TABLE personal_access_tokens ADD COLUMN expires_at TIMESTAMP NULL AFTER last_used_at');
            $this->info('✓ expires_at column added');
        } else {
            $this->info('✓ expires_at column already exists');
        }
        
        // Add abilities column if missing
        if (!in_array('abilities', $columns)) {
            $this->info('Adding abilities column...');
            DB::statement('ALTER TABLE personal_access_tokens ADD COLUMN abilities TEXT NULL AFTER name');
            $this->info('✓ abilities column added');
        } else {
            $this->info('✓ abilities column already exists');
        }
        
        // Check indexes
        $indexes = DB::select('SHOW INDEXES FROM personal_access_tokens');
        $indexNames = array_column($indexes, 'Key_name');
        
        if (!in_array('personal_access_tokens_token_index', $indexNames)) {
            $this->info('Adding token index...');
            DB::statement('CREATE INDEX personal_access_tokens_token_index ON personal_access_tokens(token)');
            $this->info('✓ token index added');
        }
        
        if (!in_array('personal_access_tokens_tokenable_type_tokenable_id_index', $indexNames)) {
            $this->info('Adding tokenable index...');
            DB::statement('CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens(tokenable_type, tokenable_id)');
            $this->info('✓ tokenable index added');
        }
        
        $this->info('✅ Personal access tokens table fixed successfully!');
        
        return 0;
    }
}