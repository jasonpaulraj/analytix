<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\MasterController;

Route::get('/', [MasterController::class, 'showDashboard'])->name('dashboard');

Route::get('/healthcheck', function () {
    $dbStatus = 'connected';
    $tables = [];

    // Helper function to format bytes to human-readable format
    $formatBytes = function ($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    };

    try {
        // Check DB connection
        DB::connection()->getPdo();

        // Get list of tables
        $rawTables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_" . $dbName;

        // Get detailed table information including size and row count
        $tables = [];
        foreach ($rawTables as $table) {
            $tableName = $table->$tableKey;

            // Get table status including data length and row count
            $tableInfo = DB::select("SHOW TABLE STATUS WHERE Name = ?", [$tableName])[0];

            // Calculate total size (data + index)
            $dataSize = $tableInfo->Data_length;
            $indexSize = $tableInfo->Index_length;
            $totalSize = $dataSize + $indexSize;

            // Format size in human-readable format
            $sizeFormatted = $formatBytes($totalSize);

            $tables[$tableName] = [
                'size' => $sizeFormatted,
                'rows' => (int)$tableInfo->Rows
            ];
        }
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    // Get version from version file
    $version = '1.2.0'; // Default fallback version
    $versionFile = base_path('.github/version.json');
    if (file_exists($versionFile)) {
        $versionData = json_decode(file_get_contents($versionFile), true);
        $version = $versionData['version'] ?? $version;
    }

    return response()->json([
        'name' => env('APP_NAME'),
        'version' => $version,
        'status' => 'running',
        'database' => [
            'status' => $dbStatus,
            'tables' => $tables
        ]
    ]);
})->name('healthcheck');
