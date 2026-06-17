<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        abort_if(!in_array($locale, ['id', 'en']), 404);
        session(['locale' => $locale]);
        return back();
    }
}
