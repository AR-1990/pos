<?php
// Find a category that has sub-categories
$c = App\Models\Category::whereHas('subCategories', function($q) {
    $q->where('status', 'Active');
})->first();

if($c) {
    echo "Found Category with SubCats: " . $c->name . " (ID: " . $c->id . ")\n";
    $controller = app()->make(App\Http\Controllers\ProductController::class);
    $response = $controller->getSubCategories($c->id);
    echo "Response: " . $response->getContent() . "\n";
} else {
    echo "No Category with Active SubCategories found.\n";
    echo "Total Categories: " . App\Models\Category::count() . "\n";
    echo "Total SubCategories: " . App\Models\SubCategory::count() . "\n";
    
    // Check if relationships are correct
    if(App\Models\SubCategory::count() > 0) {
        $sub = App\Models\SubCategory::first();
        echo "Sample SubCat: " . $sub->name . " has CategoryID: " . $sub->category_id . "\n";
    }
}
