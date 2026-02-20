<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- START DEBUG ---\n";
try {
    $count = App\Models\Cashbook::count();
    var_dump($count);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "--- END DEBUG ---\n";
