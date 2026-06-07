<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::published()
            ->with('category', 'author')
            ->latest('published_at')
            ->paginate(12);

        return view('blog.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->status === 'published', 404);

        $article->increment('views_count');

        return view('blog.show', compact('article'));
    }
}
