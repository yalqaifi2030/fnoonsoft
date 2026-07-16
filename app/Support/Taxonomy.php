<?php

namespace App\Support;

use App\Models\Software;

/**
 * The site's real taxonomy, derived from the actual catalogue (engineering /
 * design / simulation / GIS software). Categories are the single "where does it
 * live" bucket; tags are cross-cutting attributes (vendor, discipline, licence)
 * — deliberately NOT clones of the categories.
 *
 * classify() keys off the program's Latin name. Rules are ordered: the FIRST
 * matching category wins, so specific rules must precede general ones
 * (e.g. "AutoCAD Map 3D" → GIS, not CAD; "V-Ray for Revit" → 3D, not BIM).
 */
class Taxonomy
{
    /** slug => [ar, en, icon] */
    public const CATEGORIES = [
        'cad-mechanical' => ['التصميم الميكانيكي وCAD/CAM', 'Mechanical Design & CAD/CAM', 'fa-solid fa-compass-drafting'],
        'structural-civil' => ['الهندسة المدنية والإنشائية', 'Structural & Civil Engineering', 'fa-solid fa-bridge'],
        'bim-architecture' => ['العمارة ونمذجة BIM', 'Architecture & BIM', 'fa-solid fa-building-columns'],
        '3d-render' => ['التصميم ثلاثي الأبعاد والرندر', '3D Design & Rendering', 'fa-solid fa-cube'],
        'gis' => ['نظم المعلومات الجغرافية GIS', 'GIS & Mapping', 'fa-solid fa-earth-americas'],
        'cae-simulation' => ['المحاكاة والتحليل الهندسي', 'Simulation & Engineering Analysis', 'fa-solid fa-wave-square'],
        'graphics' => ['الجرافيك ومعالجة الصور', 'Graphics & Photo Editing', 'fa-solid fa-palette'],
        'video-audio' => ['المونتاج والفيديو والصوت', 'Video, Audio & Animation', 'fa-solid fa-clapperboard'],
        'science-stats' => ['الحوسبة العلمية والإحصاء', 'Scientific Computing & Statistics', 'fa-solid fa-square-root-variable'],
        'electronics-pcb' => ['الإلكترونيات ولوحات PCB', 'Electronics & PCB', 'fa-solid fa-microchip'],
        'os-servers' => ['أنظمة التشغيل والخوادم', 'Operating Systems & Servers', 'fa-brands fa-windows'],
        'office' => ['الحزم المكتبية والإنتاجية', 'Office & Productivity', 'fa-solid fa-file-lines'],
        'mobile-apps' => ['تطبيقات الجوال', 'Mobile Apps', 'fa-solid fa-mobile-screen-button'],
        'utilities' => ['أدوات مساعدة', 'Utilities', 'fa-solid fa-screwdriver-wrench'],
    ];

    /** slug => [ar, en] — cross-cutting attributes, not category clones. */
    public const TAGS = [
        'autodesk' => ['أوتوديسك', 'Autodesk'],
        'adobe' => ['أدوبي', 'Adobe'],
        'chaos' => ['كايوس', 'Chaos Group'],
        'csi' => ['CSI', 'CSI'],
        'bentley' => ['بنتلي', 'Bentley'],
        'cad' => ['CAD', 'CAD'],
        'bim' => ['BIM', 'BIM'],
        'gis-tag' => ['خرائط GIS', 'GIS'],
        'render' => ['رندر', 'Rendering'],
        'simulation' => ['محاكاة', 'Simulation'],
        'structural-analysis' => ['تحليل إنشائي', 'Structural Analysis'],
        '3d' => ['ثلاثي الأبعاد', '3D'],
        'animation' => ['تحريك', 'Animation'],
        'photo' => ['معالجة صور', 'Photo Editing'],
        'statistics' => ['إحصاء وتحليل', 'Statistics'],
        'windows' => ['ويندوز', 'Windows'],
        'free' => ['مجاني', 'Free'],
        'open-source' => ['مفتوح المصدر', 'Open Source'],
    ];

    /**
     * Ordered category rules: first match wins.
     * NOTE: never use a bare 'cad' token — it would match KiCad / CSiXCAD / eDrawings.
     */
    private const RULES = [
        // Most specific disciplines first.
        'gis' => ['arcgis', 'esri', 'global mapper', 'mapinfo', 'map 3d', 'qgis', 'erdas', 'lidar', ' gis'],
        'structural-civil' => [
            'etabs', 'sap2000', 'csi safe', 'csibridge', 'csicol', 'csiplant', 'csixcad', 'csixrevit',
            'perform-3d', 'csi detail', 'staad', 'plaxis', 'autopipe', 'sofistik', 'dlubal',
            'plate-buckling', 'civil 3d', 'synchro', 'descartes',
        ],
        'electronics-pcb' => ['kicad', 'altium', 'orcad', 'proteus', 'multisim', 'pcb'],
        'science-stats' => ['matlab', 'maplesoft', 'maple flow', 'spss', 'mathematica', 'minitab', 'origin lab', 'labview'],
        'os-servers' => ['windows server', 'windows 11', 'windows 10', 'vmware', 'ubuntu', 'linux'],
        'office' => ['microsoft 365', 'office 365', 'microsoft office', 'acrobat', 'pdf-xchange', 'libreoffice', 'wps office'],
        'video-audio' => [
            'premiere', 'after effects', 'audition', 'media encoder', 'camtasia', 'fusion studio',
            'moho', 'toon boom', 'harmony', 'dp animation', 'textor', 'character animator',
            'davinci', 'vegas', 'edius',
        ],
        // 3D/render BEFORE bim/cad so "V-Ray for Revit" and "Corona for 3ds Max" land here.
        '3d-render' => [
            '3ds max', 'maya', 'motionbuilder', 'mudbox', 'cinema 4d', 'v-ray', 'vray', 'corona',
            'vantage', 'enscape', 'envision', 'phoenix', 'chaos bridge', 'marmoset', 'toolbag',
            'marvelous', '3dcoat', 'substance 3d', 'sketchup', 'lumion', 'blender', 'houdini',
            'zbrush', 'adobe dimension', 'insofta', 'keyshot', 'twinmotion', 'rhino',
        ],
        'bim-architecture' => ['revit', 'navisworks', 'archicad', 'autocad architecture', 'architecture addon', 'naviate', 'vectorworks', 'tekla', 'room arranger'],
        'cad-mechanical' => [
            'autocad', 'solidworks', 'inventor', 'creo', 'mastercam', 'edrawings',
            'alias autostudio', 'catia', 'fusion 360', 'bricscad', 'zwcad', 'draftsight',
            'plant 3d', 'mep addon', 'mechanical addon',
        ],
        'cae-simulation' => ['simsolid', 'altair', 'vmgsim', 'openvsp', 'ansys', 'abaqus', 'comsol', 'nastran', 'hypermesh', 'aspen'],
        'graphics' => [
            'photoshop', 'illustrator', 'lightroom', 'indesign', 'incopy', 'affinity', 'dxo', 'photolab',
            'topaz', 'coreldraw', 'capture one', 'master collection', 'kid pix',
        ],
    ];

    /** Ordered tag rules — a program can collect several. */
    private const TAG_RULES = [
        'autodesk' => ['autodesk', 'autocad', '3ds max', 'maya', 'revit', 'inventor', 'navisworks', 'mudbox', 'motionbuilder', 'civil 3d', 'alias autostudio'],
        'adobe' => ['adobe', 'photoshop', 'illustrator', 'premiere', 'after effects', 'indesign', 'lightroom', 'audition', 'substance 3d', 'acrobat', 'master collection'],
        'chaos' => ['chaos', 'v-ray', 'vray', 'corona', 'enscape', 'vantage', 'phoenix', 'envision'],
        'csi' => ['csi', 'etabs', 'sap2000', 'csibridge', 'csicol', 'csiplant', 'perform-3d'],
        'bentley' => ['staad', 'plaxis', 'autopipe', 'synchro', 'descartes', 'bentley'],
        'cad' => ['autocad', 'solidworks', 'inventor', 'creo', 'mastercam', 'edrawings', 'rhino', 'catia', 'csixcad'],
        'bim' => ['revit', 'navisworks', 'archicad', 'bim', 'naviate', 'autocad architecture'],
        'gis-tag' => ['arcgis', 'esri', 'global mapper', 'mapinfo', 'map 3d', 'lidar', ' gis'],
        'render' => ['v-ray', 'vray', 'corona', 'lumion', 'enscape', 'vantage', 'keyshot', 'marmoset', 'render'],
        'simulation' => ['simsolid', 'vmgsim', 'openvsp', 'ansys', 'abaqus', 'phoenix', 'simulation', 'plaxis'],
        'structural-analysis' => ['etabs', 'sap2000', 'staad', 'csi safe', 'perform-3d', 'sofistik', 'dlubal', 'csibridge', 'plate-buckling'],
        '3d' => ['3ds max', 'maya', 'cinema 4d', '3dcoat', 'substance 3d', 'sketchup', 'blender', 'zbrush', 'rhino', 'marvelous', 'mudbox', '3d'],
        'animation' => ['maya', 'motionbuilder', 'moho', 'toon boom', 'harmony', 'animation', 'character animator', 'after effects'],
        'photo' => ['photoshop', 'lightroom', 'dxo', 'photolab', 'topaz', 'capture one', 'raw'],
        'statistics' => ['spss', 'matlab', 'maple', 'minitab', 'statistics'],
        'windows' => ['windows'],
        'free' => ['kicad', 'openvsp', 'blender', 'qbittorrent', 'freecad'],
        'open-source' => ['kicad', 'blender', 'qbittorrent', 'freecad', 'openvsp'],
    ];

    /** @return array{category: string, tags: string[]} */
    public static function classify(Software $software): array
    {
        // Mobile apps are decided by their content type, not their name.
        if ($software->content_type->value === 'mobile_app') {
            return ['category' => 'mobile-apps', 'tags' => []];
        }

        $hay = ' '.mb_strtolower((string) $software->name).' ';

        $category = 'utilities';
        foreach (self::RULES as $slug => $needles) {
            foreach ($needles as $n) {
                if (str_contains($hay, $n)) {
                    $category = $slug;
                    break 2;
                }
            }
        }

        $tags = [];
        foreach (self::TAG_RULES as $slug => $needles) {
            foreach ($needles as $n) {
                if (str_contains($hay, $n)) {
                    $tags[] = $slug;
                    break;
                }
            }
        }

        return ['category' => $category, 'tags' => $tags];
    }
}
