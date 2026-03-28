<?php

namespace App\View\Components\Sections;

use App\Services\Layouts\HomepageConfigurationService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Newsletter extends Component
{
    public string $title;

    public string $placeholder;

    public string $buttonText;

    public string $bgColor;

    /**
     * Create a new component instance.
     *
     * Loads defaults from the homepage configuration so edits in the CMS
     * are reflected everywhere the newsletter component is used.
     *
     * @param  string|null  $title  Section title/heading
     * @param  string|null  $placeholder  Input placeholder text
     * @param  string|null  $buttonText  Subscribe button text
     * @param  string|null  $bgColor  Background color (hex or Tailwind class)
     */
    public function __construct(
        ?string $title = null,
        ?string $placeholder = null,
        ?string $buttonText = null,
        ?string $bgColor = null,
    ) {
        $defaults = $this->getHomepageDefaults();

        $this->title = $title ?? $defaults['title'];
        $this->placeholder = $placeholder ?? $defaults['placeholder'];
        $this->buttonText = $buttonText ?? $defaults['buttonText'];
        $this->bgColor = $bgColor ?? $defaults['bgColor'];
    }

    /**
     * @return array{title: string, placeholder: string, buttonText: string, bgColor: string}
     */
    protected function getHomepageDefaults(): array
    {
        $config = app(HomepageConfigurationService::class)->getConfiguration();
        $section = collect($config['sections'] ?? [])
            ->firstWhere('type', 'newsletter');

        $data = $section['data'] ?? [];

        return [
            'title' => $data['title'] ?? 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
            'placeholder' => $data['placeholder'] ?? 'Enter your Email',
            'buttonText' => $data['buttonText'] ?? 'SUBSCRIBE',
            'bgColor' => $data['bgColor'] ?? '#F3F4F6',
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.newsletter');
    }
}
