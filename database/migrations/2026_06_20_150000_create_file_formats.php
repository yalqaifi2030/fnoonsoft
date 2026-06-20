<?php

use App\Models\FileFormat;
use App\Models\Software;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_formats', function (Blueprint $table) {
            $table->id();
            $table->string('extension', 16)->unique();   // lowercase, no dot
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('family', 24)->default('other'); // autodesk | adobe | rhino | lumion | other
            $table->string('color', 9)->default('#006C35');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('file_format_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_format_id')->constrained()->cascadeOnDelete();
            $table->unique(['software_id', 'file_format_id']);
        });

        $this->seedLibrary();
        $this->autoAttach();
    }

    public function down(): void
    {
        Schema::dropIfExists('file_format_software');
        Schema::dropIfExists('file_formats');
    }

    /** The starter library of common 3D / design formats. */
    private function seedLibrary(): void
    {
        $i = 0;
        foreach ($this->formats() as $ext => $f) {
            FileFormat::firstOrCreate(['extension' => $ext], [
                'name' => $f['name'],
                'description' => $f['desc'] ?? ['ar' => '', 'en' => ''],
                'family' => $f['family'],
                'color' => $f['color'],
                'is_active' => true,
                'sort_order' => $i++,
            ]);
        }
    }

    /** Link each format to the software whose slug mentions a matching product. */
    private function autoAttach(): void
    {
        $byExt = FileFormat::pluck('id', 'extension');

        $map = [
            'autocad' => ['dwg', 'dxf'],
            'revit' => ['rvt', 'rfa'],
            '3ds max' => ['max', 'fbx', '3ds'],
            '3dsmax' => ['max', 'fbx', '3ds'],
            'maya' => ['ma', 'mb', 'fbx'],
            'inventor' => ['ipt', 'iam'],
            'navisworks' => ['nwd'],
            'rhino' => ['3dm', '3ds'],
            'lumion' => ['ls'],
            'photoshop' => ['psd'],
            'illustrator' => ['ai'],
            'indesign' => ['indd'],
            'premiere' => ['prproj'],
            'after effects' => ['aep'],
            'aftereffects' => ['aep'],
            'acrobat' => ['pdf'],
        ];

        Software::query()->select(['id', 'slug'])->chunkById(200, function ($items) use ($map, $byExt) {
            foreach ($items as $s) {
                $hay = ' '.str_replace('-', ' ', Str::lower((string) $s->slug)).' ';
                $ids = [];
                foreach ($map as $keyword => $exts) {
                    if (str_contains($hay, $keyword)) {
                        foreach ($exts as $e) {
                            if (isset($byExt[$e])) {
                                $ids[$byExt[$e]] = true;
                            }
                        }
                    }
                }
                if ($ids) {
                    $s->fileFormats()->syncWithoutDetaching(array_keys($ids));
                }
            }
        });
    }

    /** @return array<string,array{name:array,desc?:array,family:string,color:string}> */
    private function formats(): array
    {
        return [
            // Autodesk
            'dwg' => ['name' => ['ar' => 'رسم أوتوكاد', 'en' => 'AutoCAD Drawing'], 'family' => 'autodesk', 'color' => '#C0392B'],
            'dxf' => ['name' => ['ar' => 'تبادل رسومات', 'en' => 'Drawing Exchange'], 'family' => 'autodesk', 'color' => '#C0392B'],
            'rvt' => ['name' => ['ar' => 'مشروع ريفيت', 'en' => 'Revit Project'], 'family' => 'autodesk', 'color' => '#1B6CB5'],
            'rfa' => ['name' => ['ar' => 'عائلة ريفيت', 'en' => 'Revit Family'], 'family' => 'autodesk', 'color' => '#1B6CB5'],
            'max' => ['name' => ['ar' => 'مشهد ثري دي ماكس', 'en' => '3ds Max Scene'], 'family' => 'autodesk', 'color' => '#2E8B57'],
            'ma' => ['name' => ['ar' => 'مايا (نصّي)', 'en' => 'Maya ASCII'], 'family' => 'autodesk', 'color' => '#00A4A6'],
            'mb' => ['name' => ['ar' => 'مايا (ثنائي)', 'en' => 'Maya Binary'], 'family' => 'autodesk', 'color' => '#00A4A6'],
            'ipt' => ['name' => ['ar' => 'قطعة إنفنتور', 'en' => 'Inventor Part'], 'family' => 'autodesk', 'color' => '#E07B00'],
            'iam' => ['name' => ['ar' => 'تجميع إنفنتور', 'en' => 'Inventor Assembly'], 'family' => 'autodesk', 'color' => '#E07B00'],
            'nwd' => ['name' => ['ar' => 'نافيس ووركس', 'en' => 'Navisworks'], 'family' => 'autodesk', 'color' => '#6A5ACD'],
            'fbx' => ['name' => ['ar' => 'تبادل ثلاثي الأبعاد', 'en' => 'FBX 3D Exchange'], 'family' => 'autodesk', 'color' => '#6E6E6E'],

            // Rhinoceros
            '3dm' => ['name' => ['ar' => 'نموذج راينو', 'en' => 'Rhino 3D Model'], 'family' => 'rhino', 'color' => '#2E7D32'],
            '3ds' => ['name' => ['ar' => 'ثري دي ستوديو', 'en' => '3D Studio'], 'family' => 'rhino', 'color' => '#2E8B57'],

            // Lumion
            'ls' => ['name' => ['ar' => 'مشهد لوميون', 'en' => 'Lumion Scene'], 'family' => 'lumion', 'color' => '#00B0F0'],

            // Adobe
            'psd' => ['name' => ['ar' => 'فوتوشوب', 'en' => 'Photoshop Document'], 'family' => 'adobe', 'color' => '#31A8FF'],
            'ai' => ['name' => ['ar' => 'إليستريتور', 'en' => 'Illustrator Artwork'], 'family' => 'adobe', 'color' => '#FF9A00'],
            'indd' => ['name' => ['ar' => 'إن ديزاين', 'en' => 'InDesign Document'], 'family' => 'adobe', 'color' => '#FF3366'],
            'prproj' => ['name' => ['ar' => 'بريمير برو', 'en' => 'Premiere Pro Project'], 'family' => 'adobe', 'color' => '#9999FF'],
            'aep' => ['name' => ['ar' => 'أفتر إفكتس', 'en' => 'After Effects Project'], 'family' => 'adobe', 'color' => '#9999FF'],
            'pdf' => ['name' => ['ar' => 'مستند PDF', 'en' => 'PDF Document'], 'family' => 'adobe', 'color' => '#B30B00'],
        ];
    }
};
