<?php
$sub = App\Models\SubCategory::first();
if ($sub) {
    echo "SubCategory Found: " . $sub->name . " (ID: " . $sub->id . ")\n";
    $count = $sub->taxes()->count();
    echo "Tax Count: " . $count . "\n";
    
    if ($count > 0) {
        foreach($sub->taxes as $t) {
            echo " - Tax ID: " . $t->id . ", Type: " . $t->tax_type . ", Rate: " . $t->pivot->tax_rate . "\n";
        }
    } else {
        echo "No taxes assigned to this sub-category.\n";
    }
} else {
    echo "No SubCategories found.\n";
}
