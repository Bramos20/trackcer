<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DiagnosticController extends Controller
{
    /**
     * Check Sanctum configuration and database setup
     */
    public function checkSanctum()
    {
        $diagnostics = [];
        
        // Check if personal_access_tokens table exists
        $tableExists = Schema::hasTable('personal_access_tokens');
        $diagnostics['table_exists'] = $tableExists;
        
        if ($tableExists) {
            // Get table columns
            $columns = Schema::getColumnListing('personal_access_tokens');
            $diagnostics['columns'] = $columns;
            
            // Check for required columns
            $requiredColumns = ['id', 'tokenable_type', 'tokenable_id', 'name', 'token', 'abilities'];
            $missingColumns = array_diff($requiredColumns, $columns);
            $diagnostics['missing_columns'] = $missingColumns;
            
            // Get token count
            $tokenCount = DB::table('personal_access_tokens')->count();
            $diagnostics['token_count'] = $tokenCount;
        }
        
        // Check Sanctum configuration
        $diagnostics['sanctum_guard'] = config('sanctum.guard');
        $diagnostics['sanctum_stateful'] = config('sanctum.stateful');
        
        // Check if User model has HasApiTokens trait
        $userTraits = class_uses_recursive(User::class);
        $diagnostics['has_api_tokens_trait'] = in_array('Laravel\Sanctum\HasApiTokens', $userTraits);
        
        return response()->json([
            'status' => 'diagnostic',
            'diagnostics' => $diagnostics,
            'recommendations' => $this->getRecommendations($diagnostics),
        ]);
    }
    
    /**
     * Create a test token
     */
    public function createTestToken(Request $request)
    {
        try {
            // Create or get test user
            $user = User::firstOrCreate(
                ['email' => 'api-test@example.com'],
                [
                    'name' => 'API Test User',
                    'password' => bcrypt('test-password'),
                ]
            );
            
            // Try to create token
            $token = $user->createToken('api-test-token');
            $plainToken = $token->plainTextToken;
            
            // Verify token in database
            $dbToken = DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->orderBy('created_at', 'desc')
                ->first();
            
            return response()->json([
                'status' => 'success',
                'user_id' => $user->id,
                'token' => $plainToken,
                'token_in_db' => $dbToken ? true : false,
                'token_id' => $dbToken ? $dbToken->id : null,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
    
    private function getRecommendations($diagnostics)
    {
        $recommendations = [];
        
        if (!$diagnostics['table_exists']) {
            $recommendations[] = 'Run: php artisan migrate';
        }
        
        if (!empty($diagnostics['missing_columns'])) {
            $recommendations[] = 'Missing columns in personal_access_tokens table. Run: php artisan migrate';
        }
        
        if (!$diagnostics['has_api_tokens_trait']) {
            $recommendations[] = 'Add "use Laravel\Sanctum\HasApiTokens;" trait to User model';
        }
        
        return $recommendations;
    }
}