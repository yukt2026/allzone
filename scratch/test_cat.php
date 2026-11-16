<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\Category::all() as $cat) {
    $firstProd = $cat->products()->first();
    echo $cat->slug . " => " . $cat->name . " | Img: " . $cat->image . " | Quick: " . ($cat->is_quick_eligible ? 'YES' : 'NO') . " | First Prod: " . ($firstProd ? $firstProd->title . ' (' . $firstProd->primary_image_url . ')' : 'None') . PHP_EOL;
}

$b1 = getimagesize('storage/app/public/banners/hero-electronics-sale.jpg');
echo "electronics: " . $b1[0] . 'x' . $b1[1] . PHP_EOL;
$b2 = getimagesize('storage/app/public/banners/hero-sk-refrigeration.jpg');
echo "refrig: " . $b2[0] . 'x' . $b2[1] . PHP_EOL;
$b3 = getimagesize('storage/app/public/banners/hero-smart-tvs.jpg');
echo "tvs: " . $b3[0] . 'x' . $b3[1] . PHP_EOL;
