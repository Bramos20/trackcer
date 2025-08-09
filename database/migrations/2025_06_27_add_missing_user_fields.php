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
        Schema::table('users', function (Blueprint $table) {
            $columns = Schema::getColumnListing('users');
            
            // Add custom_profile_image if it doesn't exist
            if (!in_array('custom_profile_image', $columns)) {
                $table->string('custom_profile_image')->nullable()->after('profile_image');
            }
            
            // Add terms_accepted_at if it doesn't exist
            if (!in_array('terms_accepted_at', $columns)) {
                $table->timestamp('terms_accepted_at')->nullable()->after('custom_profile_image');
            }
            
            // Add initial_data_fetched if it doesn't exist
            if (!in_array('initial_data_fetched', $columns)) {
                $table->boolean('initial_data_fetched')->default(false)->after('terms_accepted_at');
            }
            
            // Add last_login_at if it doesn't exist
            if (!in_array('last_login_at', $columns)) {
                $table->timestamp('last_login_at')->nullable()->after('initial_data_fetched');
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
        Schema::table('users', function (Blueprint $table) {
            $columns = Schema::getColumnListing('users');
            
            if (in_array('custom_profile_image', $columns)) {
                $table->dropColumn('custom_profile_image');
            }
            
            if (in_array('terms_accepted_at', $columns)) {
                $table->dropColumn('terms_accepted_at');
            }
            
            if (in_array('initial_data_fetched', $columns)) {
                $table->dropColumn('initial_data_fetched');
            }
            
            if (in_array('last_login_at', $columns)) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};