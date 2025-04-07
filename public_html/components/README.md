# Slimmer met AI - Herbruikbare Web Components

Deze map bevat herbruikbare web components die gebruikt worden op de Slimmer met AI website. Deze componenten zijn gemaakt met behulp van de Web Components standaard, waardoor ze eenvoudig kunnen worden hergebruikt zonder afhankelijkheden van externe frameworks.

## Waarom Web Components?

- **Standaard**: Web Components maken gebruik van webstandaarden zonder externe afhankelijkheden
- **Herbruikbaar**: De componenten zijn volledig geïsoleerd en kunnen overal worden gebruikt
- **Onderhoudbaar**: Elke component heeft zijn eigen logica en styling
- **Toekomstbestendig**: Web Components zijn een webstandaard die door alle moderne browsers wordt ondersteund

## Beschikbare Componenten

### Button Component
Een herbruikbare button component met verschillende stijlen, maten en mogelijkheden.

```html
<slimmer-button>Standaard Button</slimmer-button>
<slimmer-button type="outline">Outline Button</slimmer-button>
<slimmer-button href="/pagina.html">Link Button</slimmer-button>
<slimmer-button size="large" full-width>Grote Button</slimmer-button>
```

### Card Component
Een veelzijdige card component voor het weergeven van content in een duidelijke, visueel aantrekkelijke manier.

```html
<slimmer-card title="Voorbeeld Card" image="/images/voorbeeld.jpg">
  <p>Hier komt de beschrijving tekst van de card.</p>
  <div slot="actions">
    <slimmer-button>Actie</slimmer-button>
  </div>
</slimmer-card>
```

### Navbar Component
Een responsieve navigatiebalk component met ondersteuning voor mobiele menu's, account dropdowns, en meer.

```html
<slimmer-navbar 
  active-page="index"
  user-logged-in
  user-name="Jan Jansen"
  user-avatar="/images/avatar.jpg"
  cart-count="3">
</slimmer-navbar>
```

### Hero Container Component
Een flexibele hero sectie voor bovenaan pagina's, met ondersteuning voor verschillende achtergrondstijlen.

```html
<slimmer-hero 
  title="Welkom bij Slimmer met AI" 
  subtitle="Praktische AI-tools voor Nederlandse professionals"
  background="gradient"
  centered>
  <div slot="actions">
    <slimmer-button href="/tools.html">Bekijk Tools</slimmer-button>
    <slimmer-button href="/e-learnings.html" type="outline">Ontdek E-learnings</slimmer-button>
  </div>
</slimmer-hero>
```

## Gebruik

Er zijn twee manieren om deze componenten te gebruiken:

### 1. Laad alle componenten tegelijk (aanbevolen)

Voeg de volgende regel toe aan de `<head>` sectie van je HTML-pagina:

```html
<script src="components/ComponentsLoader.js"></script>
```

Dit script laadt automatisch alle beschikbare componenten.

### 2. Laad componenten afzonderlijk

Als je slechts enkele componenten nodig hebt, kun je ze afzonderlijk laden:

```html
<script src="components/Button.js"></script>
<script src="components/Card.js"></script>
<!-- Voeg meer componenten toe zoals nodig -->
```

## Demo Pagina

Bekijk de `test-components.html` pagina voor werkende voorbeelden van alle componenten en hoe ze gebruikt kunnen worden. 