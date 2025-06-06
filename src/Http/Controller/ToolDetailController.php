<?php

namespace App\Http\Controller;

use App\Infrastructure\View\View;

final class ToolDetailController
{
    public function emailAssistant(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('tools/email-assistant', [
            'title' => 'Email Assistent Plus | AI Tools | Slimmer met AI',
            'tool' => [
                'id' => 'email-assistant',
                'name' => 'Email Assistent Plus',
                'price' => '29.99',
                'image' => '/images/email-assistant.svg',
                'description' => 'Onze meest geavanceerde email assistent met ondersteuning voor meerdere talen en integratie met populaire email-clients.',
                'features' => [
                    'Automatische e-mail concepten',
                    'Meertalige ondersteuning',
                    'Toon aanpassing',
                    'Template library',
                    'Integratie met Gmail, Outlook',
                    'Smart reply suggesties'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function documentAnalyzer(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('tools/document-analyzer', [
            'title' => 'Document Analyzer 2.0 | AI Tools | Slimmer met AI',
            'tool' => [
                'id' => 'document-analyzer',
                'name' => 'Document Analyzer 2.0',
                'price' => '39.99',
                'image' => '/images/rapport-generator.svg',
                'description' => 'Analyseer contracten en juridische documenten met AI en krijg direct de belangrijkste punten uitgelicht.',
                'features' => [
                    'Contract analyse',
                    'Risico detectie',
                    'Samenvatting generatie',
                    'Juridische compliance check',
                    'PDF upload ondersteuning',
                    'Uitgebreide rapportage'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function meetingSummarizer(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('tools/meeting-summarizer', [
            'title' => 'Meeting Summarizer | AI Tools | Slimmer met AI',
            'tool' => [
                'id' => 'meeting-summarizer',
                'name' => 'Meeting Summarizer',
                'price' => '34.99',
                'image' => '/images/meeting-summarizer.svg',
                'description' => 'Automatisch samenvattingen maken van vergaderingen en belangrijke actiepunten extraheren.',
                'features' => [
                    'Automatische transcriptie',
                    'Actiepunten extractie',
                    'Beslissingen overzicht',
                    'Follow-up reminders',
                    'Audio/video upload',
                    'Export naar verschillende formaten'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function rapportGenerator(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $html = View::renderToString('tools/rapport-generator', [
            'title' => 'Rapport Generator | AI Tools | Slimmer met AI',
            'tool' => [
                'id' => 'rapport-generator',
                'name' => 'Rapport Generator',
                'price' => '44.99',
                'image' => '/images/rapport-generator.svg',
                'description' => 'Genereer professionele rapporten op basis van jouw data en input.',
                'features' => [
                    'Automatische rapportage',
                    'Data visualisatie',
                    'Template aanpassing',
                    'Export opties',
                    'Real-time collaboration',
                    'Business intelligence integratie'
                ]
            ]
        ]);
        return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
} 