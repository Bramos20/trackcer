<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listening_history', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_history', 'position_in_fetch')) {
                $table->integer('position_in_fetch')->nullable()->after('fetch_session_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listening_history', function (Blueprint $table) {
            if (Schema::hasColumn('listening_history', 'position_in_fetch')) {
                $table->dropColumn('position_in_fetch');
            }
        });
    }
};
