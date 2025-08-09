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
        // Emergency fix for missing expires_at column
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                // Add expires_at column if it doesn't exist
                $columns = Schema::getColumnListing('personal_access_tokens');
                if (!in_array('expires_at', $columns)) {
                    $table->timestamp('expires_at')->nullable()->after('last_used_at');
                }
                
                // Add abilities column if it doesn't exist
                if (!in_array('abilities', $columns)) {
                    $table->text('abilities')->nullable()->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $columns = Schema::getColumnListing('personal_access_tokens');
            
            if (in_array('expires_at', $columns)) {
                $table->dropColumn('expires_at');
            }
            
            if (in_array('abilities', $columns)) {
                $table->dropColumn('abilities');
            }
        });
    }
};