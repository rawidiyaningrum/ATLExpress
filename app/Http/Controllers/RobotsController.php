<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $content = implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: ' . url('sitemap.xml'),
        ]);

        return response($content)
            ->header('Content-Type', 'text/plain');
    }
}