<?php
namespace App\Application\Service;

use App\Infrastructure\Config\Config;
use App\Domain\Logging\ErrorLoggerInterface;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Shape\RichText;

use function container;

final class PresentationConvertService
{
    public function __construct(private Config $config, private ErrorLoggerInterface $logger) {}

    /**
     * Converteer React-code naar een PowerPoint-presentatie.
     * Geeft een array terug met ten minste een downloadUrl.
     *
     * @param string $reactCode
     * @return array{downloadUrl:string}
     */
    public function convert(string $reactCode): array
    {
        $claudeApiKey = getenv('CLAUDE_API_KEY');
        if (!$claudeApiKey) {
            throw new \RuntimeException('CLAUDE_API_KEY ontbreekt.');
        }

        // 1. Call Claude om structuur op te halen
        $structuredData = $this->askClaudeForStructure($reactCode, $claudeApiKey);

        // 2. Genereer PowerPoint
        $fileRelPath = $this->generatePptx($structuredData);

        return ['downloadUrl' => $fileRelPath];
    }

    /**
     * @return array<int, array{type:string,content:mixed,level?:int}>
     */
    private function askClaudeForStructure(string $reactCode, string $apiKey): array
    {
        $systemPrompt = <<<PROMPT
Je bent een assistent die React code analyseert en zet in een JSON-structuur voor een PowerPoint-presentatie.
Geef ALLEEN een JSON-array terug met objecten die keys bevatten: type, content, level (optioneel).
PROMPT;

        $userMessage = "Analyseer de volgende React code en genereer de JSON output:\n\n```jsx\n{$reactCode}\n```";
        $payload = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 2048,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage]
            ]
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('cURL fout: '.$err);
        }
        if ($httpCode >= 400) {
            throw new \RuntimeException('Claude API fout (HTTP '.$httpCode.'): '.substr((string)$response,0,200));
        }

        $json = json_decode((string)$response, true);
        if (!isset($json['content'][0]['text'])) {
            throw new \RuntimeException('Onverwacht Claude-response-formaat');
        }
        $structured = json_decode($json['content'][0]['text'], true);
        if (!is_array($structured)) {
            throw new \RuntimeException('JSON parse mislukt op Claude-output');
        }
        return $structured;
    }

    /**
     * @param array<int,array<string,mixed>> $structuredData
     * @return string relative url path
     */
    private function generatePptx(array $structuredData): string
    {
        $uploadRelDir = '/uploads/presentations';
        $uploadAbsDir = PUBLIC_ROOT.$uploadRelDir;
        if (!is_dir($uploadAbsDir) && !mkdir($uploadAbsDir, 0755, true)) {
            throw new \RuntimeException('Kan upload-map niet aanmaken');
        }

        $presentation = new PhpPresentation();
        $slide = $presentation->getActiveSlide();
        $y = 50; $margin = 30;
        $slideWidth = $presentation->getLayout()->getCX(PhpPresentation::LAYOUT_SCREEN_16X9);
        $slideHeight = $presentation->getLayout()->getCY(PhpPresentation::LAYOUT_SCREEN_16X9);

        $textShape = static function(string $text,int $size,bool $bold,int $height,int $offsetY) use ($slide,$slideWidth,$margin) {
            $run = new RichText\Run();
            $run->setText($text);
            $run->getFont()->setName('Arial')->setSize($size)->setBold($bold);
            $shape = new RichText();
            $shape->setHeight($height);
            $shape->setWidth($slideWidth - 2*$margin);
            $shape->setOffsetX($margin);
            $shape->setOffsetY($offsetY);
            $shape->getActiveParagraph()->addRun($run);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $slide->addShape($shape);
        };

        foreach ($structuredData as $item) {
            $type = $item['type'] ?? 'text';
            $content = $item['content'] ?? '';
            $level = $item['level'] ?? 1;

            if ($type === 'title') {
                if ($level === 1 && $y !== 50) { // nieuwe slide voor hoofdtitel
                    $slide = $presentation->createSlide();
                    $y = 50;
                }
                $size = match($level) {1=>44,2=>32,default=>28};
                $textShape($content,$size,true,$size*1.5,$y);
                $y += $size*1.5 + 10;
            } elseif ($type === 'text') {
                $textShape($content,18,false,18*3,$y);
                $y += 18*3 + 5;
            } elseif ($type === 'list' && is_array($content)) {
                $listShape = new RichText();
                $listShape->setWidth($slideWidth - 2*$margin);
                $listShape->setOffsetX($margin);
                $listShape->setOffsetY($y);
                foreach ($content as $li) {
                    $p = $listShape->createParagraph();
                    $p->getBulletStyle()->setBulletType(Bullet::TYPE_BULLET);
                    $run = $p->createRun($li);
                    $run->getFont()->setSize(18);
                }
                $listShape->setHeight(25*count($content));
                $slide->addShape($listShape);
                $y += $listShape->getHeight() + 5;
            } elseif ($type === 'section_start') {
                $slide = $presentation->createSlide();
                $y = 50;
                $size = match($level) {1=>44,2=>32,default=>28};
                $textShape($content,$size,true,$size*1.5,$y);
                $y += $size*1.5 + 10;
            }
            if ($y > ($slideHeight - 100)) {
                $slide = $presentation->createSlide();
                $y = 50;
            }
        }

        $filename = 'presentatie_'.time().'_'.uniqid().'.pptx';
        $abs = $uploadAbsDir.'/'.$filename;
        $rel = $uploadRelDir.'/'.$filename;

        $writer = \PhpOffice\PhpPresentation\IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($abs);

        return $rel;
    }
} 