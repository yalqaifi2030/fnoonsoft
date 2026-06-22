<?php
$base = '/www/wwwroot/finunsoft.com';
require "$base/vendor/autoload.php";
$app = require "$base/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = App\Models\Software::find(87);
try {
    $html = view('model-preview-embed', ['software' => $s])->render();
    echo 'rendered OK, length='.strlen($html).PHP_EOL;
    echo 'has data-obj-viewer = '.(str_contains($html, 'data-obj-viewer') ? 'yes' : 'no').PHP_EOL;
    echo 'src present = '.(str_contains($html, $s->modelGlbUrl()) ? 'yes' : 'no').PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL;
}
