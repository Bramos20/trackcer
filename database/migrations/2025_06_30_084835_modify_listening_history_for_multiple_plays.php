<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the missing column first
        Schema::table('listening_history', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_history', 'fetch_session_id')) {
                $table->string('fetch_session_id')->nullable()->after('source');
            }
        });

        // Since columns already exist, just handle indexes
        
        // Try to drop the unique constraint using raw SQL
        try {
            // Try different possible constraint names
            $constraintNames = [
                'listening_history_user_id_track_id_unique',
                'user_id_track_id_unique',
                'user_id_track_id',
                'PRIMARY'
            ];
            
            foreach ($constraintNames as $constraintName) {
                try {
                    DB::statement("ALTER TABLE listening_history DROP INDEX {$constraintName}");
                    \Log::info("Successfully dropped index: {$constraintName}");
                    break; // If successful, stop trying
                } catch (\Exception $e) {
                    // Continue to next possible name
                }
            }
        } catch (\Exception $e) {
            // If we can't drop the constraint, log it but continue
            \Log::warning('Could not drop unique constraint on listening_history: ' . $e->getMessage());
        }

        // Check if indexes already exist before adding them
        try {
            $existingIndexes = DB::select("SHOW INDEXES FROM listening_history");
            $indexNames = array_column($existingIndexes, 'Key_name');
            
            Schema::table('listening_history', function (Blueprint $table) use ($indexNames) {
                // Add new index that allows multiple plays of the same track
                if (!in_array('idx_user_track_played', $indexNames)) {
                    $table->index(['user_id', 'track_id', 'played_at'], 'idx_user_track_played');
                }
                
                if (!in_array('idx_user_source_session', $indexNames)) {
                    $table->index(['user_id', 'source', 'fetch_session_id'], 'idx_user_source_session');
                }
            });
            
            \Log::info('Successfully added new indexes to listening_history table');
        } catch (\Exception $e) {
            \Log::warning('Could not add indexes: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Get existing indexes
            $existingIndexes = DB::select("SHOW INDEXES FROM listening_history");
            $indexNames = array_column($existingIndexes, 'Key_name');
            
            Schema::table('listening_history', function (Blueprint $table) use ($indexNames) {
                // Drop the new indexes if they exist
                if (in_array('idx_user_track_played', $indexNames)) {
                    $table->dropIndex('idx_user_track_played');
                }
                
                if (in_array('idx_user_source_session', $indexNames)) {
                    $table->dropIndex('idx_user_source_session');
                }
            });
        } catch (\Exception $e) {
            \Log::warning('Could not drop indexes: ' . $e->getMessage());
        }

        // Try to restore the unique constraint
        try {
            Schema::table('listening_history', function (Blueprint $table) {
                $table->unique(['user_id', 'track_id']);
            });
        } catch (\Exception $e) {
            \Log::warning('Could not restore unique constraint: ' . $e->getMessage());
        }

        // Drop the column we added
        Schema::table('listening_history', function (Blueprint $table) {
            if (Schema::hasColumn('listening_history', 'fetch_session_id')) {
                $table->dropColumn('fetch_session_id');
            }
        });
    }
};