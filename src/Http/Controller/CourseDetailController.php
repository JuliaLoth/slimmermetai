<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;

final class CourseDetailController
{
    public function aiBasics(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/ai-basics', [
            'title' => 'AI Basics voor Professionals | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'ai-basics',
                'name' => 'AI Basics voor Professionals',
                'price' => '97.00',
                'originalPrice' => '149.00',
                'image' => '/images/ai-basics-course.svg',
                'level' => 'Beginner',
                'duration' => '4 uur',
                'description' => 'De perfecte startcursus voor iedereen die AI wil gaan gebruiken in hun werk. Leer de basis van ChatGPT, prompting en praktische toepassingen.',
                'features' => [
                    '8 praktische lessen',
                    'Hands-on oefeningen',
                    'Certificaat',
                    'Levenslange toegang',
                    'Community access',
                    'E-book inclusief'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function promptEngineering(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/prompt-engineering', [
            'title' => 'Advanced Prompt Engineering | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'prompt-engineering',
                'name' => 'Advanced Prompt Engineering',
                'price' => '197.00',
                'image' => '/images/prompt-engineering-course.svg',
                'level' => 'Gevorderd',
                'duration' => '6 uur',
                'description' => 'Ontdek geavanceerde prompt technieken om maximale resultaten uit AI te halen. Van chain-of-thought tot role-playing prompts.',
                'features' => [
                    '12 geavanceerde technieken',
                    '150+ prompt templates',
                    'Real-world cases',
                    'Expert feedback',
                    'Live Q&A sessies',
                    'Bonus prompts library'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function aiAutomation(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/ai-automation', [
            'title' => 'AI Workflow Automatisering | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'ai-automation',
                'name' => 'AI Workflow Automatisering',
                'price' => '247.00',
                'image' => '/images/ai-automation-course.svg',
                'level' => 'Gevorderd',
                'duration' => '8 uur',
                'description' => 'Leer hoe je repetitieve taken automatiseert met AI. Van email management tot rapport generatie - bespaar uren per week.',
                'features' => [
                    '10 automatisering recepten',
                    'Tool integraties',
                    'ROI calculatie',
                    'Implementatie support',
                    'Zapier & Make.com tutorials',
                    'Custom automation templates'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function aiStrategy(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/ai-strategy', [
            'title' => 'AI Strategie voor Organisaties | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'ai-strategy',
                'name' => 'AI Strategie voor Organisaties',
                'price' => '497.00',
                'image' => '/images/ai-strategy-course.svg',
                'level' => 'Expert',
                'duration' => '12 uur',
                'description' => 'Ontwikkel een complete AI-strategie voor je organisatie. Van risicomanagement tot change management en ROI-optimalisatie.',
                'features' => [
                    'Strategische frameworks',
                    'Change management',
                    'Risk assessment tools',
                    '1-op-1 consultatie',
                    'Implementatie roadmap',
                    'Executive presentation templates'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function aiContent(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/ai-content', [
            'title' => 'Content Creatie met AI | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'ai-content',
                'name' => 'Content Creatie met AI',
                'price' => '147.00',
                'image' => '/images/ai-content-course.svg',
                'level' => 'Beginner',
                'duration' => '5 uur',
                'description' => 'Maak professionele content met AI. Van blog posts tot social media, presentaties en marketing materiaal.',
                'features' => [
                    'Content templates',
                    'Brand consistency',
                    'SEO optimalisatie',
                    'Multi-platform publishing',
                    'Visual content creation',
                    'Content calendar templates'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function aiData(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('elearnings/ai-data', [
            'title' => 'Data Analyse met AI | E-learnings | Slimmer met AI',
            'course' => [
                'id' => 'ai-data',
                'name' => 'Data Analyse met AI',
                'price' => '197.00',
                'image' => '/images/ai-data-course.svg',
                'level' => 'Gevorderd',
                'duration' => '7 uur',
                'description' => 'Transformeer ruwe data naar actionable insights met AI. Leer data visualisatie, trend analyse en predictive modelling.',
                'features' => [
                    'Data cleaning technieken',
                    'Visualisatie tools',
                    'Predictive analytics',
                    'Dashboard creatie',
                    'SQL query automation',
                    'Business intelligence integratie'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 