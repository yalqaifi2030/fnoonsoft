<?php

namespace App\Http\Controllers;

use App\Models\InteractiveLab;
use App\Models\LearningCategory;
use Illuminate\View\View;

/**
 * "Learn & Build" — an interactive hub for university students covering
 * engineering/programming, Arduino, AI and cybersecurity, with live in-browser
 * simulations, a code playground, snippets and tutorial videos. Learning tracks
 * and their videos are managed from the admin panel.
 */
class LearnController extends Controller
{
    public function index(): View
    {
        $categories = LearningCategory::active()
            ->withCount('activeVideos as videos_count')
            ->orderBy('sort_order')
            ->get();

        $labs = InteractiveLab::active()->orderBy('sort_order')->get();

        return view('learn', compact('categories', 'labs'));
    }

    public function lab(InteractiveLab $lab): View
    {
        abort_unless($lab->is_active, 404);

        return view('learn.lab', compact('lab'));
    }

    public function category(LearningCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $category->load('activeVideos');

        return view('learn.category', compact('category'));
    }

    public function videos(): View
    {
        $categories = LearningCategory::active()
            ->with('activeVideos')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($c) => $c->activeVideos->isNotEmpty());

        return view('learn.videos', compact('categories'));
    }
}
