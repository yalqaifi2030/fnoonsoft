<?php
require '/www/wwwroot/finunsoft.com/vendor/autoload.php';
$app = require '/www/wwwroot/finunsoft.com/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Software;
use Illuminate\Support\Facades\Storage;

$objPath = 'models/01KVR7A8N07W4F6W5XQJJRP4T1.obj';

$s = Software::find(87);
if (! $s) { echo "software 87 not found\n"; exit; }

echo 'before: model_glb='.$s->model_glb.' ext='.$s->modelExt().PHP_EOL;

if (Storage::disk('public')->exists($objPath)) {
    $s->forceFill(['model_glb' => $objPath])->saveQuietly();
    $s->refresh();
    echo 'after : model_glb='.$s->model_glb.' ext='.$s->modelExt().' is3dObj='.($s->is3dObj() ? 'YES (Three.js)' : 'no').PHP_EOL;
    echo 'url   : '.$s->modelGlbUrl().PHP_EOL;
} else {
    echo "obj file missing: $objPath\n";
}
echo 'OK'.PHP_EOL;
