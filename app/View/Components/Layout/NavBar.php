<?php

namespace App\View\Components\Layout;

use App\Services\MenuService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NavBar extends Component
{
    public array $primaryLinks;

    public array $secondaryLinks;

    public array $mobileActions;

    public function __construct(
        ?array $primaryLinks = null,
        ?array $secondaryLinks = null,
        ?array $mobileActions = null,
    ) {
        $menuService = app(MenuService::class);

        $this->primaryLinks = $primaryLinks ?? $menuService->getHeaderPrimaryLinks();
        $this->secondaryLinks = $secondaryLinks ?? $menuService->getHeaderSecondaryLinks();
        $this->mobileActions = $mobileActions ?? $menuService->getMobileActions();
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.nav-bar');
    }
}
