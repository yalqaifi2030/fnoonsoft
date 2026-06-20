<?php

namespace App\Http\Controllers;

use App\Models\FileFormat;
use Illuminate\View\View;

class FormatController extends Controller
{
    /** A reference guide: design/3D file formats grouped by program family. */
    public function index(): View
    {
        $groups = FileFormat::query()
            ->where('is_active', true)
            ->with(['software' => fn ($q) => $q->published()->orderByDesc('downloads_count')])
            ->orderBy('sort_order')
            ->get()
            ->groupBy('family')
            ->sortKeys();

        return view('formats.index', ['groups' => $groups]);
    }
}
