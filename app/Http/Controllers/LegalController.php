<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function terms(): View
    {
        return $this->renderDoc('terms', 'Terms of Service', 'Last updated: June 17, 2025');
    }

    public function privacy(): View
    {
        return $this->renderDoc('privacy', 'Privacy Policy', 'Last updated: June 17, 2025');
    }

    private function renderDoc(string $doc, string $title, string $subtitle): View
    {
        $path = resource_path("legal/{$doc}.md");
        $html = Str::markdown(file_get_contents($path), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return view('legal.show', compact('title', 'subtitle', 'html'));
    }
}
