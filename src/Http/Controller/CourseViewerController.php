<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CourseViewerController
{
    public function aiBasics(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'title' => 'AI Basics | Slimmer met AI',
            'meta_description' => 'Ontdek de fundamenten van kunstmatige intelligentie en leer hoe je AI effectief kunt inzetten in je dagelijkse werkzaamheden.',
            'page' => 'course-viewer',
            'course_id' => 'ai-basics',
            'course_title' => 'AI Basics',
            'course_description' => 'Ontdek de fundamenten van kunstmatige intelligentie en leer hoe je AI effectief kunt inzetten in je dagelijkse werkzaamheden.',
            'course_level' => 'Beginners',
            'course_duration' => '6 weken (2-4 uur per week)',
            'course_author' => 'Julia Loth',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'E-learnings', 'url' => '/e-learnings'],
                ['label' => 'AI Basics', 'url' => null]
            ]
        ];

        return View::renderToResponse('e-learning/course-viewer', $data);
    }

    public function promptEngineering(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'title' => 'Prompt Engineering | Slimmer met AI',
            'meta_description' => 'Leer effectieve prompts schrijven voor AI tools en generatieve AI.',
            'page' => 'course-viewer',
            'course_id' => 'prompt-engineering',
            'course_title' => 'Prompt Engineering',
            'course_description' => 'Leer effectieve prompts schrijven voor AI tools en generatieve AI.',
            'course_level' => 'Gevorderd',
            'course_duration' => '4 weken (3-5 uur per week)',
            'course_author' => 'Julia Loth',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'E-learnings', 'url' => '/e-learnings'],
                ['label' => 'Prompt Engineering', 'url' => null]
            ]
        ];

        return View::renderToResponse('e-learning/course-viewer', $data);
    }
}
