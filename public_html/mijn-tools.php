<?php
// Definieer paden voor de productieomgeving
define('PUBLIC_INCLUDES', __DIR__ . '/includes');

// Helper functie voor includes
function include_public($file) {
    return include PUBLIC_INCLUDES . '/' . $file;
}

// Helper functie voor asset URLs
function asset_url($path) {
    return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
}

// Stel paginatitel en beschrijving in
$page_title = ' $args[0].ToString().ToUpper() ijn tools | Slimmer met AI';
$page_description = 'Slimmer met AI -  $args[0].ToString().ToUpper() ijn tools';
?>
<!DOCTYPE html>
<html lang="nl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Tools | Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Gebruik je gekochte AI tools bij Slimmer met AI. Al je tools op Ã©Ã©n centrale plaats.">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <!-- Laad alle componenten -->
    <script src="<?php echo asset_url('components/ComponentsLoader.js'); ?>"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">Direct naar inhoud</a>
    
    <slimmer-navbar user-logged-in user-name="Gebruiker" active-page="tools"></slimmer-navbar>

    <slimmer-hero 
        title="Mijn Tools" 
        subtitle="Alle AI tools die je hebt gekocht op Ã©Ã©n plek. Gebruik ze direct vanuit deze pagina."
        background="image"
        image-url="images/hero-background.svg"
        centered>
    </slimmer-hero>

    <main id="main-content" class="my-tools-container" role="main">
        <div class="container">
            <div class="tools-list" id="my-tools-container">
                <!-- Tools worden hier dynamisch ingeladen -->
                <div class="no-tools-message" id="no-tools-message">
                    <div class="message-content">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>Nog geen tools gekocht</h3>
                        <p>Je hebt nog geen AI tools gekocht. Ontdek ons aanbod in de <a href="tools">tools sectie</a>.</p>
                        <slimmer-button href="tools" type="primary">Bekijk beschikbare tools</slimmer-button>
                    </div>
                </div>
            </div>
            
            <!-- Email Assistant Tool Template (wordt getoond als gekocht) -->
            <template id="tool-email-assistant">
                <div class="tool-card active" data-tool-id="email-assistant-1">
                    <div class="tool-header">
                        <div class="tool-icon">
                            <img src="<?php echo asset_url('images/email-assistant.svg'); ?>" alt="Email Assistent icoon">
                        </div>
                        <div class="tool-info">
                            <h3>AI Email Assistent</h3>
                            <span class="tool-status">Actief</span>
                        </div>
                    </div>
                    <div class="tool-content">
                        <p>Laat AI je helpen bij het schrijven, beantwoorden en categoriseren van e-mails. Bespaar uren per week.</p>
                        
                        <div class="tool-interface">
                            <div class="form-group">
                                <label for="email-assistant-task">Wat wil je doen?</label>
                                <select id="email-assistant-task" class="form-control">
                                    <option value="write">Email opstellen</option>
                                    <option value="reply">Email beantwoorden</option>
                                    <option value="summarize">Email samenvatten</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="email-assistant-input">Jouw input:</label>
                                <textarea id="email-assistant-input" class="form-control" rows="5" placeholder="Voer hier je verzoek in, bijvoorbeeld: 'Stel een professionele email op voor een potentiÃ«le klant om mijn diensten aan te bieden'"></textarea>
                            </div>
                            
                            <div class="tool-actions">
                                <slimmer-button type="primary" id="email-assistant-generate">Genereren</slimmer-button>
                                <slimmer-button type="outline" id="email-assistant-clear">Wissen</slimmer-button>
                            </div>
                            
                            <div class="tool-result" id="email-assistant-result" style="display: none;">
                                <h4>Resultaat:</h4>
                                <div class="result-content"></div>
                                <div class="result-actions">
                                    <slimmer-button type="outline" id="email-assistant-copy">KopiÃ«ren</slimmer-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Rapport Generator Tool Template (wordt getoond als gekocht) -->
            <template id="tool-rapport-generator">
                <div class="tool-card active" data-tool-id="rapport-generator-1">
                    <div class="tool-header">
                        <div class="tool-icon">
                            <img src="<?php echo asset_url('images/rapport-generator.svg'); ?>" alt="Rapport Generator icoon">
                        </div>
                        <div class="tool-info">
                            <h3>Rapport Generator</h3>
                            <span class="tool-status">Actief</span>
                        </div>
                    </div>
                    <div class="tool-content">
                        <p>Genereer in enkele minuten professionele rapporten op basis van jouw data en input.</p>
                        
                        <div class="tool-interface">
                            <div class="form-group">
                                <label for="rapport-type">Type rapport:</label>
                                <select id="rapport-type" class="form-control">
                                    <option value="sales">Verkooprapport</option>
                                    <option value="marketing">Marketingrapport</option>
                                    <option value="financial">Financieel rapport</option>
                                    <option value="project">Projectrapport</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="rapport-data">Jouw gegevens en instructies:</label>
                                <textarea id="rapport-data" class="form-control" rows="5" placeholder="Voer je gegevens en specifieke instructies in voor het rapport, bijvoorbeeld verkoopdata, doelgroep of periode"></textarea>
                            </div>
                            
                            <div class="tool-actions">
                                <slimmer-button type="primary" id="rapport-generator-generate">Genereren</slimmer-button>
                                <slimmer-button type="outline" id="rapport-generator-clear">Wissen</slimmer-button>
                            </div>
                            
                            <div class="tool-result" id="rapport-generator-result" style="display: none;">
                                <h4>Resultaat:</h4>
                                <div class="result-content"></div>
                                <div class="result-actions">
                                    <slimmer-button type="outline" id="rapport-generator-copy">KopiÃ«ren</slimmer-button>
                                    <slimmer-button type="outline" id="rapport-generator-download">Downloaden</slimmer-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Meeting Summarizer Tool Template (wordt getoond als gekocht) -->
            <template id="tool-meeting-summarizer">
                <div class="tool-card active" data-tool-id="meeting-summarizer-1">
                    <div class="tool-header">
                        <div class="tool-icon">
                            <img src="<?php echo asset_url('images/meeting-summarizer.svg'); ?>" alt="Meeting Summarizer icoon">
                        </div>
                        <div class="tool-info">
                            <h3>Meeting Summarizer</h3>
                            <span class="tool-status">Actief</span>
                        </div>
                    </div>
                    <div class="tool-content">
                        <p>Krijg automatisch gestructureerde samenvattingen en actiepunten uit je vergaderingen.</p>
                        
                        <div class="tool-interface">
                            <div class="form-group">
                                <label for="meeting-content">Vergadernotities of transcript:</label>
                                <textarea id="meeting-content" class="form-control" rows="8" placeholder="Plak hier je vergadernotities, transcript of voer het gespreksonderwerp en de belangrijkste punten in"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="summary-format">Gewenst formaat:</label>
                                <select id="summary-format" class="form-control">
                                    <option value="bullet">Bullet points</option>
                                    <option value="narrative">Verhalende samenvatting</option>
                                    <option value="action">Alleen actiepunten</option>
                                </select>
                            </div>
                            
                            <div class="tool-actions">
                                <slimmer-button type="primary" id="meeting-summarizer-generate">Samenvatten</slimmer-button>
                                <slimmer-button type="outline" id="meeting-summarizer-clear">Wissen</slimmer-button>
                            </div>
                            
                            <div class="tool-result" id="meeting-summarizer-result" style="display: none;">
                                <h4>Samenvatting:</h4>
                                <div class="result-content"></div>
                                <div class="result-actions">
                                    <slimmer-button type="outline" id="meeting-summarizer-copy">KopiÃ«ren</slimmer-button>
                                    <slimmer-button type="outline" id="meeting-summarizer-email">Versturen per e-mail</slimmer-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <slimmer-footer></slimmer-footer>

    <!-- Scripts -->
    <script src="<?php echo asset_url('js/main.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMyToolsPage();
        });
        
        function initMyToolsPage() {
            // In een echte applicatie zou dit data van een backend API halen
            // Nu voor de demo simuleren we het ophalen van gekochte tools vanuit localStorage
            
            const purchasedTools = getPurchasedTools();
            renderPurchasedTools(purchasedTools);
            
            // Voeg event listeners toe aan de tool interfaces
            setupToolEventListeners();
        }
        
        function getPurchasedTools() {
            // In een echte applicatie zou dit van de backend API komen
            // Voor de demo halen we het uit localStorage of gebruiken we vooraf ingestelde demo-data
            
            try {
                const cartData = localStorage.getItem('cart');
                if (cartData) {
                    const cart = JSON.parse(cartData);
                    const purchasedItems = cart.filter(item => item.type === 'Tool');
                    
                    if (purchasedItems.length > 0) {
                        return purchasedItems.map(item => ({
                            id: item.id,
                            name: item.name,
                            templateId: `tool-${item.id.split('-')[0]}-${item.id.split('-')[1]}`
                        }));
                    }
                }
                
                // Demo data voor testdoeleinden
                return [
                    { id: 'email-assistant-1', name: 'AI Email Assistent', templateId: 'tool-email-assistant' },
                    { id: 'rapport-generator-1', name: 'Rapport Generator', templateId: 'tool-rapport-generator' },
                    { id: 'meeting-summarizer-1', name: 'Meeting Summarizer', templateId: 'tool-meeting-summarizer' }
                ];
                
            } catch (error) {
                console.error('Fout bij ophalen van gekochte tools:', error);
                return [];
            }
        }
        
        function renderPurchasedTools(tools) {
            const container = document.getElementById('my-tools-container');
            const noToolsMessage = document.getElementById('no-tools-message');
            
            if (tools.length === 0) {
                // Toon bericht dat er geen tools zijn gekocht
                noToolsMessage.style.display = 'block';
                return;
            }
            
            // Verberg het 'geen tools' bericht
            noToolsMessage.style.display = 'none';
            
            // Toon elke gekochte tool
            tools.forEach(tool => {
                const template = document.getElementById(tool.templateId);
                if (template) {
                    const clone = document.importNode(template.content, true);
                    container.appendChild(clone);
                }
            });
        }
        
        function setupToolEventListeners() {
            // Email Assistant
            setupEmailAssistantListeners();
            
            // Rapport Generator
            setupRapportGeneratorListeners();
            
            // Meeting Summarizer
            setupMeetingSummarizerListeners();
        }
        
        function setupEmailAssistantListeners() {
            const generateBtn = document.getElementById('email-assistant-generate');
            const clearBtn = document.getElementById('email-assistant-clear');
            const copyBtn = document.getElementById('email-assistant-copy');
            const resultContainer = document.getElementById('email-assistant-result');
            
            if (generateBtn) {
                generateBtn.addEventListener('click', function() {
                    const task = document.getElementById('email-assistant-task').value;
                    const input = document.getElementById('email-assistant-input').value;
                    
                    if (!input.trim()) {
                        alert('Voer eerst wat tekst in om te verwerken.');
                        return;
                    }
                    
                    // Simuleer verwerking (in een echte app, API call naar AI service)
                    simulateAIProcessing(resultContainer, 'Email', task, input);
                });
            }
            
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    document.getElementById('email-assistant-input').value = '';
                    resultContainer.style.display = 'none';
                });
            }
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const resultContent = resultContainer.querySelector('.result-content');
                    navigator.clipboard.writeText(resultContent.textContent)
                        .then(() => alert('Tekst gekopieerd naar clipboard!'))
                        .catch(err => console.error('Kon tekst niet kopiÃ«ren:', err));
                });
            }
        }
        
        function setupRapportGeneratorListeners() {
            const generateBtn = document.getElementById('rapport-generator-generate');
            const clearBtn = document.getElementById('rapport-generator-clear');
            const copyBtn = document.getElementById('rapport-generator-copy');
            const downloadBtn = document.getElementById('rapport-generator-download');
            const resultContainer = document.getElementById('rapport-generator-result');
            
            if (generateBtn) {
                generateBtn.addEventListener('click', function() {
                    const rapportType = document.getElementById('rapport-type').value;
                    const data = document.getElementById('rapport-data').value;
                    
                    if (!data.trim()) {
                        alert('Voer eerst wat gegevens in om te verwerken.');
                        return;
                    }
                    
                    // Simuleer verwerking (in een echte app, API call naar AI service)
                    simulateAIProcessing(resultContainer, 'Rapport', rapportType, data);
                });
            }
            
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    document.getElementById('rapport-data').value = '';
                    resultContainer.style.display = 'none';
                });
            }
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const resultContent = resultContainer.querySelector('.result-content');
                    navigator.clipboard.writeText(resultContent.textContent)
                        .then(() => alert('Tekst gekopieerd naar clipboard!'))
                        .catch(err => console.error('Kon tekst niet kopiÃ«ren:', err));
                });
            }
            
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    const resultContent = resultContainer.querySelector('.result-content').textContent;
                    
                    // CreÃ«er een downloadbaar bestand
                    const blob = new Blob([resultContent], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `rapport-${new Date().toISOString().split('T')[0]}.txt`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            }
        }
        
        function setupMeetingSummarizerListeners() {
            const generateBtn = document.getElementById('meeting-summarizer-generate');
            const clearBtn = document.getElementById('meeting-summarizer-clear');
            const copyBtn = document.getElementById('meeting-summarizer-copy');
            const emailBtn = document.getElementById('meeting-summarizer-email');
            const resultContainer = document.getElementById('meeting-summarizer-result');
            
            if (generateBtn) {
                generateBtn.addEventListener('click', function() {
                    const content = document.getElementById('meeting-content').value;
                    const format = document.getElementById('summary-format').value;
                    
                    if (!content.trim()) {
                        alert('Voer eerst vergadernotities in om te verwerken.');
                        return;
                    }
                    
                    // Simuleer verwerking (in een echte app, API call naar AI service)
                    simulateAIProcessing(resultContainer, 'Meeting', format, content);
                });
            }
            
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    document.getElementById('meeting-content').value = '';
                    resultContainer.style.display = 'none';
                });
            }
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const resultContent = resultContainer.querySelector('.result-content');
                    navigator.clipboard.writeText(resultContent.textContent)
                        .then(() => alert('Tekst gekopieerd naar clipboard!'))
                        .catch(err => console.error('Kon tekst niet kopiÃ«ren:', err));
                });
            }
            
            if (emailBtn) {
                emailBtn.addEventListener('click', function() {
                    const summary = resultContainer.querySelector('.result-content').textContent;
                    const subject = 'Vergadering Samenvatting - ' + new Date().toLocaleDateString('nl-NL');
                    const mailtoLink = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(summary)}`;
                    window.open(mailtoLink);
                });
            }
        }
        
        function simulateAIProcessing(resultContainer, toolType, option, input) {
            const resultContent = resultContainer.querySelector('.result-content');
            
            // Toon een 'laden' indicator
            resultContent.innerHTML = '<div class="loading">AI verwerkt je verzoek...</div>';
            resultContainer.style.display = 'block';
            
            // In een echte applicatie zou hier een API call naar een backend AI-service plaatsvinden
            setTimeout(() => {
                let generatedContent = '';
                
                if (toolType === 'Email') {
                    if (option === 'write') {
                        generatedContent = generateEmailTemplate(input);
                    } else if (option === 'reply') {
                        generatedContent = generateEmailReply(input);
                    } else {
                        generatedContent = summarizeEmail(input);
                    }
                } else if (toolType === 'Rapport') {
                    generatedContent = generateRapport(option, input);
                } else if (toolType === 'Meeting') {
                    generatedContent = generateMeetingSummary(option, input);
                }
                
                resultContent.innerHTML = `<div class="ai-result">${generatedContent}</div>`;
            }, 1500); // Simuleer verwerking voor 1.5 seconden
        }
        
        // Helper functies voor het genereren van voorbeeld content
        
        function generateEmailTemplate(input) {
            // Voorbeeldfunctie om een e-mail te genereren op basis van input
            return `<p>Beste [Naam],</p>
            <p>Ik hoop dat deze e-mail je in goede gezondheid bereikt.</p>
            <p>${input}</p>
            <p>Ik kijk uit naar je reactie.</p>
            <p>Met vriendelijke groet,<br>
            [Jouw naam]</p>`;
        }
        
        function generateEmailReply(input) {
            // Voorbeeldfunctie om een antwoord op een e-mail te genereren
            return `<p>Beste [Afzender],</p>
            <p>Dank voor je e-mail.</p>
            <p>In reactie op je vraag over "${input}":</p>
            <p>Ik heb dit intern besproken en we kunnen je het volgende aanbieden...</p>
            <p>Laat me weten of dit aansluit bij je verwachtingen.</p>
            <p>Met vriendelijke groet,<br>
            [Jouw naam]</p>`;
        }
        
        function summarizeEmail(input) {
            // Voorbeeldfunctie om een e-mail samen te vatten
            return `<h4>Samenvatting van e-mail:</h4>
            <ul>
                <li>Hoofdonderwerp: ${input.split(' ').slice(0, 5).join(' ')}...</li>
                <li>Belangrijkste punten:
                    <ul>
                        <li>Punt 1</li>
                        <li>Punt 2</li>
                    </ul>
                </li>
                <li>Urgentie: Gemiddeld</li>
                <li>Vereiste actie: Antwoord binnen 2 werkdagen</li>
            </ul>`;
        }
        
        function generateRapport(type, input) {
            // Voorbeeldfunctie om een rapport te genereren
            const rapportTypes = {
                'sales': 'Verkooprapport',
                'marketing': 'Marketingrapport',
                'financial': 'Financieel rapport',
                'project': 'Projectrapport'
            };
            
            return `<h3>${rapportTypes[type]}</h3>
            <p><strong>Datum:</strong> ${new Date().toLocaleDateString('nl-NL')}</p>
            <p><strong>Opgesteld door:</strong> AI Rapport Generator</p>
            
            <h4>Samenvatting</h4>
            <p>Dit rapport geeft een overzicht van ${input.split(' ').slice(0, 10).join(' ')}...</p>
            
            <h4>Resultaten</h4>
            <ul>
                <li>Belangrijke bevinding 1</li>
                <li>Belangrijke bevinding 2</li>
                <li>Belangrijke bevinding 3</li>
            </ul>
            
            <h4>Aanbevelingen</h4>
            <ol>
                <li>Eerste aanbeveling op basis van de data</li>
                <li>Tweede aanbeveling op basis van de markttrends</li>
                <li>Derde aanbeveling op basis van de voorspellingen</li>
            </ol>`;
        }
        
        function generateMeetingSummary(format, input) {
            // Voorbeeldfunctie om een vergadering samen te vatten
            if (format === 'bullet') {
                return `<h4>Vergadersamenvatting</h4>
                <p><strong>Datum:</strong> ${new Date().toLocaleDateString('nl-NL')}</p>
                <p><strong>Onderwerp:</strong> ${input.split(' ').slice(0, 5).join(' ')}</p>
                
                <h5>Belangrijkste punten:</h5>
                <ul>
                    <li>Punt 1 uit de vergadering</li>
                    <li>Punt 2 uit de vergadering</li>
                    <li>Punt 3 uit de vergadering</li>
                </ul>
                
                <h5>Actiepunten:</h5>
                <ul>
                    <li><strong>[Naam]:</strong> Actie 1 (deadline: volgende week)</li>
                    <li><strong>[Naam]:</strong> Actie 2 (deadline: eind van de maand)</li>
                    <li><strong>[Naam]:</strong> Actie 3 (deadline: zo spoedig mogelijk)</li>
                </ul>`;
            } else if (format === 'narrative') {
                return `<h4>Verhalende samenvatting van de vergadering</h4>
                <p>De vergadering begon met een introductie over ${input.split(' ').slice(0, 10).join(' ')}...</p>
                <p>Tijdens de discussie werden verschillende standpunten naar voren gebracht. [Naam] benadrukte het belang van [punt], terwijl [Andere naam] aangaf dat [ander punt] ook aandacht verdient.</p>
                <p>Er werd besloten dat [besluit], met als concrete vervolgstappen: [stappen].</p>
                <p>De vergadering werd afgesloten met de afspraak dat iedereen zijn actiepunten uitvoert voor de volgende bijeenkomst op [datum].</p>`;
            } else {
                return `<h4>Actiepunten uit vergadering</h4>
                <p>Op basis van de vergadering over '${input.split(' ').slice(0, 5).join(' ')}' zijn de volgende actiepunten vastgesteld:</p>
                
                <ol>
                    <li><strong>[Naam]:</strong> Actie 1 (deadline: volgende week)</li>
                    <li><strong>[Naam]:</strong> Actie 2 (deadline: eind van de maand)</li>
                    <li><strong>[Naam]:</strong> Actie 3 (deadline: zo spoedig mogelijk)</li>
                    <li><strong>Allen:</strong> Voorbereiding voor volgende vergadering</li>
                </ol>
                
                <p><strong>Volgende vergadering:</strong> [datum en tijd]</p>`;
            }
        }
    </script>
</body>
</html> 
