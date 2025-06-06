# 🚀 Lokaal CI/CD Systeem

## Overzicht

Dit project heeft een lokaal CI/CD systeem dat alle belangrijke checks uitvoert **voordat** je commit naar de repository. Dit voorkomt dat broken code naar GitHub wordt gepusht.

## Scripts

### Windows PowerShell: `scripts/local-ci.ps1`

Voor Windows gebruikers met PowerShell:

```powershell
# Voer alle checks uit (inclusief tests)
.\scripts\local-ci.ps1

# Voer checks uit zonder tests (sneller)
.\scripts\local-ci.ps1 -SkipTests
```

### Linux/Mac Bash: `scripts/local-ci.sh`

Voor Unix-systemen:

```bash
# Maak executable
chmod +x scripts/local-ci.sh

# Voer uit
./scripts/local-ci.sh
```

## Wat wordt gecontroleerd?

Het lokale CI/CD script voert de volgende checks uit:

### 1. 📦 Dependencies
- **Composer**: Validatie en installatie van PHP dependencies
- **NPM**: Installatie en security audit van JavaScript dependencies

### 2. 🏗️ Code Architectuur
- Check voor verboden PHP bestanden in `public_html/`
- Architectuur compliance checks

### 3. 🎨 Code Style
- **PHPCS**: PHP Code Sniffer volgens PSR-12 standaard
- **Auto-fix**: Probeert automatisch style issues op te lossen

### 4. 🔍 Static Analysis
- **PHPStan**: Statische analyse van PHP code (level 6)
- Type checking en error detectie

### 5. 🧪 Tests (optioneel)
- **PHPUnit**: PHP unit tests
- **Vitest**: JavaScript/frontend tests

### 6. 📝 Frontend
- **ESLint**: JavaScript linting
- **Build**: Frontend asset compilatie met Vite

### 7. 🔒 Security
- **Composer Audit**: Security vulnerabilities in PHP packages
- Dependency vulnerability scanning

### 8. 📊 Git Status
- Check voor uncommitted changes
- Overzicht van gewijzigde bestanden

## Automatische Git Hooks

### Pre-commit Hook

Het systeem installeert automatisch een pre-commit hook via Husky die:

- Het lokale CI/CD script uitvoert bij elke commit poging
- De commit **blokkeert** als er issues zijn
- Je forceert om issues op te lossen voordat je kunt committen

### Pre-commit Hook Locatie

```
.husky/pre-commit
```

## Workflow

### Standaard Development Workflow

```bash
# 1. Maak je changes
# ... edit files ...

# 2. Voer lokale CI/CD uit
.\scripts\local-ci.ps1 -SkipTests

# 3. Los eventuele issues op
# ... fix issues ...

# 4. Commit (pre-commit hook voert checks opnieuw uit)
git add .
git commit -m "feat: nieuwe functionaliteit"

# 5. Push naar GitHub
git push origin main
```

### Als er Issues zijn

Wanneer het script faalt:

```powershell
❌ Static analysis failed! Please fix the issues before committing.
```

1. **Lees de error messages zorgvuldig**
2. **Fix de issues in je code**
3. **Voer het script opnieuw uit**
4. **Herhaal tot alle checks slagen**

## Voordelen

### 🛡️ **Kwaliteit Borging**
- Voorkomt broken code in de repository
- Consistent code style across het team
- Early detection van bugs en issues

### ⚡ **Snelle Feedback**
- Krijg feedback binnen seconden, niet minuten
- Geen wachten op GitHub Actions
- Ontwikkel sneller met vertrouwen

### 🔄 **Consistentie**
- Zelfde checks lokaal als in CI/CD pipeline
- Geen verrassingen bij push naar GitHub
- Uniform development proces

### 💰 **Cost Saving**
- Minder GitHub Actions runs
- Efficiënter gebruik van CI/CD resources
- Snellere development cycle

## Troubleshooting

### PowerShell Execution Policy

Als je een execution policy error krijgt:

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Script Niet Gevonden

Zorg ervoor dat je in de project root directory bent:

```powershell
# Controleer of je in de juiste directory bent
Test-Path composer.json  # Moet True retourneren
Test-Path package.json   # Moet True retourneren
```

### Dependencies Installatie Mislukt

```powershell
# Handmatig dependencies installeren
composer install
npm ci
```

## Aanpassing

### Script Aanpassen

Je kunt het script aanpassen in `scripts/local-ci.ps1`:

- Stappen toevoegen/verwijderen
- Error handling aanpassen
- Nieuwe checks toevoegen

### Hook Uitschakelen (Tijdelijk)

```bash
# Commit zonder pre-commit hook (NIET AANBEVOLEN)
git commit --no-verify -m "emergency fix"
```

## Best Practices

### 🎯 **Gebruik Altijd**
- Voer lokale CI/CD uit voor elke commit
- Fix issues onmiddellijk, stack ze niet op
- Keep dependencies up to date

### 🚀 **Optimize for Speed**
- Gebruik `-SkipTests` voor snelle checks
- Voer volledige tests uit voor belangrijke changes
- Cache dependencies waar mogelijk

### 🔧 **Maintain Quality**
- Lees error messages zorgvuldig
- Begrijp waarom een check faalt
- Improve code quality, don't just fix errors

## Integration met IDE

### Visual Studio Code

Voeg taken toe aan `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Local CI/CD",
            "type": "shell",
            "command": ".\\scripts\\local-ci.ps1",
            "args": ["-SkipTests"],
            "group": "build",
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "new"
            }
        }
    ]
}
```

Run via: `Ctrl+Shift+P` → "Tasks: Run Task" → "Local CI/CD" 