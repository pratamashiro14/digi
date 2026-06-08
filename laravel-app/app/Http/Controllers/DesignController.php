<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function index()
    {
        $designs = Design::where('status', 'approved')->paginate(12);
        $categories = Design::where('status', 'approved')->distinct()->pluck('kategori');

        return view('designs.index', [
            'designs' => $designs,
            'categories' => $categories
        ]);
    }

    public function show($id_design)
    {
        $design = Design::findOrFail($id_design);
        return view('designs.show', ['design' => $design]);
    }

    public function byCategory($kategori)
    {
        $designs = Design::where('kategori', $kategori)
            ->where('status', 'approved')
            ->paginate(12);
        $categories = Design::where('status', 'approved')->distinct()->pluck('kategori');

        return view('designs.index', [
            'designs' => $designs,
            'categories' => $categories,
            'activeCategory' => $kategori
        ]);
    }

    public function search(Request $request)
    {
        $keyword = $request->input('keyword');
        $designs = Design::where('judul', 'LIKE', "%$keyword%")
            ->orWhere('deskripsi', 'LIKE', "%$keyword%")
            ->where('status', 'approved')
            ->paginate(12);
        
        $categories = Design::where('status', 'approved')->distinct()->pluck('kategori');

        return view('designs.index', [
            'designs' => $designs,
            'categories' => $categories,
            'keyword' => $keyword
        ]);
    }
}
