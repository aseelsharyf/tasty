<?php

namespace App\View\Components\Layout;

use App\Services\MenuService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    public array $menu;

    public array $topics;

    public array $office;

    public array $social;

    public string $company;

    public string $location;

    public int $year;

    public function __construct(
        ?array $menu = null,
        ?array $topics = null,
        ?array $office = null,
        ?array $social = null,
        ?string $company = null,
        ?string $location = null,
        ?int $year = null,
    ) {
        $menuService = app(MenuService::class);

        $this->menu = $menu ?? $menuService->getFooterMenu();
        $this->topics = $topics ?? $menuService->getFooterTopics();
        $this->office = $office ?? $menuService->getFooterOffice();
        $this->social = $social ?? $menuService->getFooterSocial();
        $this->company = $company ?? $menuService->getCompanyName();
        $this->location = $location ?? $menuService->getCompanyLocation();
        $this->year = $year ?? (int) date('Y');
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.footer');
    }
}
