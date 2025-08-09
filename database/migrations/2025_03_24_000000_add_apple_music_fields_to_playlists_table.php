<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppleMusicFieldsToPlaylistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->string('apple_music_id')->nullable()->after('spotify_id');
            $table->string('apple_music_global_id')->nullable()->after('apple_music_id');
            $table->string('service')->nullable()->after('apple_music_global_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn([
                'apple_music_id',
                'apple_music_global_id',
                'service'
            ]);
        });
    }
}
