<?php

namespace App\View\Components\Sections;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Subscribe extends Component
{
    public function __construct()
    {
        //
    }

    public function render(): View|Closure|string
    {
        return view('components.sections.subscribe');
    }
}
