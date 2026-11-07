<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use SplFileInfo;

class StyleguideController extends Controller
{
    /**
     * Show every design token and UI component (local environment only).
     */
    public function __invoke(): Response
    {
        $tokens = json_decode(File::get(resource_path('theme/tokens.json')), true, flags: JSON_THROW_ON_ERROR);

        return Inertia::render('Styleguide', [
            'tokensPath' => 'resources/theme/tokens.json',
            'themeColours' => $this->themeColours($tokens),
            'palette' => $this->palette($tokens['color']),
            'fonts' => $this->fonts($tokens['font']),
            'icons' => $this->icons(),
        ]);
    }

    /**
     * Semantic colours with their light and dark values. A "-foreground" colour is folded into the colour it sits on.
     *
     * @param  array<string, mixed>  $tokens
     * @return list<array{name: string, foreground: ?string, light: array{name: string, hex: string}, dark: array{name: string, hex: string}}>
     */
    private function themeColours(array $tokens): array
    {
        $light = $this->entries($tokens['theme']['light']);
        $dark = $this->entries($tokens['theme']['dark']);
        $names = array_keys($light);

        return collect($names)
            ->reject(fn (string $name): bool => Str::endsWith($name, '-foreground') && in_array(Str::beforeLast($name, '-foreground'), $names, true))
            ->map(fn (string $name): array => [
                'name' => $name,
                'foreground' => in_array("{$name}-foreground", $names, true) ? "{$name}-foreground" : null,
                'light' => $this->resolveAlias($tokens, $light[$name]['$value']),
                'dark' => $this->resolveAlias($tokens, $dark[$name]['$value']),
            ])
            ->values()
            ->all();
    }

    /**
     * Raw palette colours, grouped by family (forest, sage…).
     *
     * @param  array<string, mixed>  $colors
     * @return list<array{group: string, shades: list<array{name: string, hex: string, description: ?string}>}>
     */
    private function palette(array $colors): array
    {
        return collect($this->entries($colors))
            ->map(fn (array $group, string $groupName): array => [
                'group' => $groupName,
                'shades' => isset($group['$value'])
                    ? [['name' => $groupName, 'hex' => $group['$value'], 'description' => $group['$description'] ?? null]]
                    : collect($this->entries($group))
                        ->map(fn (array $token, string $shade): array => [
                            'name' => "{$groupName}-{$shade}",
                            'hex' => $token['$value'],
                            'description' => $token['$description'] ?? null,
                        ])
                        ->values()
                        ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $fonts
     * @return list<array{name: string, family: string, description: ?string}>
     */
    private function fonts(array $fonts): array
    {
        return collect($this->entries($fonts))
            ->map(fn (array $token, string $name): array => [
                'name' => $name,
                'family' => $token['$value'][0],
                'description' => $token['$description'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Names of the exported brand icons in public/images/icons.
     *
     * @return list<string>
     */
    private function icons(): array
    {
        return collect(File::files(public_path('images/icons')))
            ->map(fn (SplFileInfo $file): string => $file->getBasename('.png'))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Follow a token reference such as "{color.stone.50}" to its palette name and hex value.
     *
     * @param  array<string, mixed>  $tokens
     * @return array{name: string, hex: string}
     */
    private function resolveAlias(array $tokens, string $alias): array
    {
        $path = explode('.', trim($alias, '{}'));

        return [
            'name' => implode('-', array_slice($path, 1)),
            'hex' => data_get($tokens, [...$path, '$value']),
        ];
    }

    /**
     * Child tokens of a group, without the "$type" / "$description" metadata keys.
     *
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private function entries(array $group): array
    {
        return array_filter($group, fn (string $key): bool => ! Str::startsWith($key, '$'), ARRAY_FILTER_USE_KEY);
    }
}
