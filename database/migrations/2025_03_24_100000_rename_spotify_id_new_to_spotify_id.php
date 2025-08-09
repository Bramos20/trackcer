<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSpotifyIdNewToSpotifyId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if spotify_id_new exists
        if (Schema::hasColumn('playlists', 'spotify_id_new')) {
            // Check if spotify_id exists
            if (Schema::hasColumn('playlists', 'spotify_id')) {
                // If both exist, drop spotify_id first
                Schema::table('playlists', function (Blueprint $table) {
                    $table->dropColumn('spotify_id');
                });
            }

            // Direct SQL approach for renaming
            DB::statement('ALTER TABLE playlists CHANGE spotify_id_new spotify_id VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('playlists', 'spotify_id')) {
            // Direct SQL approach for renaming back
            DB::statement('ALTER TABLE playlists CHANGE spotify_id spotify_id_new VARCHAR(255) NULL');
        }
    }
}
