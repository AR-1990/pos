<?php
$taxes = App\Models\Tax::whereIn('id', [1, 2])->get();
foreach($taxes as $t) {
    echo "Tax ID: " . $t->id . ", Type: " . $t->tax_type . ", Status: " . $t->status . ", Applied On: " . $t->applied_on . "\n";
}
