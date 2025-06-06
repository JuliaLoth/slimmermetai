<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;
use App\Domain\Repository\CourseRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class CourseDetailController
{
    public function __construct(private CourseRepositoryInterface $courseRepository)
    {
    }

    public function aiBasics(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('ai-basics', $request);
    }

    public function promptEngineering(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('prompt-engineering', $request);
    }

    public function aiAutomation(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('ai-automation', $request);
    }

    public function aiStrategy(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('ai-strategy', $request);
    }

    public function aiContent(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('ai-content', $request);
    }

    public function aiData(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderCourse('ai-data', $request);
    }

    /**
     * Private method to render any course by ID
     */
    private function renderCourse(string $courseId, ServerRequestInterface $request): ResponseInterface
    {
        $course = $this->courseRepository->getCourseById($courseId);

        if (!$course) {
            return new Response(
                404,
                ['Content-Type' => 'text/html; charset=utf-8'],
                '<h1>404 - Cursus niet gevonden</h1><p>De opgevraagde cursus bestaat niet.</p>'
            );
        }

        $html = View::renderToString("elearnings/{$courseId}", [
            'title' => $course['name'] . ' | E-learnings | Slimmer met AI',
            'course' => $course
        ]);

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
