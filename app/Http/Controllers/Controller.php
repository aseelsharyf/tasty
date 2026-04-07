<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Safely generate a route URL, returning '#' if route doesn't exist (CMS_ONLY mode).
     */
    protected function safeRoute(string $name, mixed $parameters = []): string
    {
        try {
            return route($name, $parameters);
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return '#';
        }
    }
}
