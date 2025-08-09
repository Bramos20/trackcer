<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add expires_at column if it doesn't exist
            if (!Schema::hasColumn('personal_access_tokens', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('last_used_at');
            }
            
            // Add abilities column if it doesn't exist (for scope management)
            if (!Schema::hasColumn('personal_access_tokens', 'abilities')) {
                $table->text('abilities')->nullable()->after('name');
            }
            
            // Add an index on the token column for better performance
            if (!Schema::hasIndex('personal_access_tokens', 'personal_access_tokens_token_index')) {
                $table->index('token', 'personal_access_tokens_token_index');
            }
            
            // Add an index on tokenable for better performance
            if (!Schema::hasIndex('personal_access_tokens', 'personal_access_tokens_tokenable_type_tokenable_id_index')) {
                $table->index(['tokenable_type', 'tokenable_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the indexes first
            $table->dropIndex(['token']);
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            
            // Drop the columns
            $table->dropColumn(['expires_at', 'abilities']);
        });
    }
};