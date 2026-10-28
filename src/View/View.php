<?php

declare(strict_types=1);

namespace App\View;

use App\Http\Request;
use App\Http\Response;
use App\Preferences\UserPreferences;

/** Plain-PHP templates from templates/, wrapped in layout.php for full pages. */
final class View
{
    public function __construct(
        private readonly string $templateDir,
        private readonly ViewHelpers $helpers,
    ) {
    }

    /**
     * Helpers bound to the current location. Without cookie consent the
     * location only lives in the URL, so internal links must carry it.
     */
    public function helpers(Request $request, UserPreferences $prefs, string $location): ViewHelpers
    {
        $persistInUrls = $prefs->consent !== true && $request->query('loc') !== '';

        return $this->helpers->withLocation($location, $persistInUrls);
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                require $__file;

                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })($file, $vars);
    }

    /** @param array<string, mixed> $vars must contain "h" (ViewHelpers) */
    public function page(string $template, array $vars, int $status = 200): Response
    {
        $vars['content'] = $this->render($template, $vars);

        return Response::html($this->render('layout', $vars), $status);
    }
}
