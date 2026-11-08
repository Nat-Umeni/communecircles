<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the home page with the circles feed.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'circles' => $this->placeholderCircles(),
        ]);
    }

    /**
     * Circles from the mockups.
     *
     * TODO: load them from the database once circles are stored.
     *
     * @return list<array{name: string, description: string, channels: list<string>}>
     */
    private function placeholderCircles(): array
    {
        return [
            [
                'name' => 'Commune Circles',
                'description' => 'Announcements, help and feedback about the site itself.',
                'channels' => ['Announcements', 'Introductions', 'Feedback', 'guides'],
            ],
            [
                'name' => 'Permaculture & Farming',
                'description' => 'Growing food, soil, seeds and food systems.',
                'channels' => ['general-discussion', 'seed-saving', 'food-forests', 'soil-and-composting', 'seed-swaps'],
            ],
            [
                'name' => 'Sustainable Building',
                'description' => 'Tiny homes, natural materials and off-grid systems.',
                'channels' => ['general-discussion', 'tiny-homes', 'natural-building', 'off-grid-systems', 'questions-and-advice'],
            ],
            [
                'name' => 'Intentional Communities',
                'description' => 'Living together: finding, starting and running communities.',
                'channels' => ['general-discussion', 'finding-a-community', 'governance', 'finances', 'directory'],
            ],
            [
                'name' => 'Wellness & Healthy Living',
                'description' => 'Nutrition, well-being and mindful living.',
                'channels' => ['general-discussion', 'nutrition', 'mindful-living'],
            ],
            [
                'name' => 'Exchange',
                'description' => 'Share skills, lend tools, give things away. No buying or selling.',
                'channels' => ['skills', 'volunteering', 'tools-to-lend', 'give-away', 'help-wanted'],
            ],
        ];
    }
}
