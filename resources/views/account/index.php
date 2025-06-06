<?php /** @var string $title */ ?>

<a href="#main-content" class="skip-link">Direct naar inhoud</a>

<section class="hero-with-background" aria-labelledby="account-heading">
    <div class="container">
        <div class="hero-content">
            <h1 id="account-heading">Mijn Account</h1>
            <p>Welkom bij je persoonlijke dashboard. Beheer je account, bekijk je voortgang en krijg toegang tot alle tools en cursussen.</p>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="dashboard-title">
    <div class="container">
        <div class="dashboard-header">
            <h2 id="dashboard-title">Dashboard Overzicht</h2>
            <p>Bekijk je voortgang en activiteit in één oogopslag</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>0</h3>
                <p>Actieve cursussen</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Voltooide cursussen</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Gebruikte tools</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Opgeslagen favorieten</p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Mijn Cursussen</h3>
                </div>
                <div class="dashboard-card-body">
                    <p>Je hebt momenteel geen actieve cursussen. Ontdek ons uitgebreide aanbod aan AI-cursussen.</p>
                    <a href="/e-learnings" class="btn btn-primary">Bekijk cursussen</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>AI-Tools</h3>
                </div>
                <div class="dashboard-card-body">
                    <p>Krijg toegang tot exclusieve AI-tools en maak je werk slimmer en efficiënter.</p>
                    <a href="/tools" class="btn btn-primary">Bekijk tools</a>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Recente Activiteit</h3>
                </div>
                <div class="dashboard-card-body">
                    <ul class="activity-list">
                        <li class="activity-item">
                            <div class="activity-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                </svg>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Account aangemaakt</div>
                                <div class="activity-time">Vandaag</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                                </svg>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Ingelogd</div>
                                <div class="activity-time">Nu</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="account-management-title">
    <div class="container">
        <div class="section-header">
            <h2 id="account-management-title">Account Beheer</h2>
            <p>Beheer je persoonlijke gegevens en instellingen</p>
        </div>
        
        <div class="account-management-grid">
            <div class="account-card">
                <div class="account-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                    </svg>
                </div>
                <h3>Persoonlijke Gegevens</h3>
                <p>Bekijk en wijzig je profielinformatie, naam en contactgegevens.</p>
                <a href="/profiel" class="btn btn-outline">Profiel beheren</a>
            </div>
            
            <div class="account-card">
                <div class="account-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                    </svg>
                </div>
                <h3>Wachtwoord & Beveiliging</h3>
                <p>Wijzig je wachtwoord en beheer je beveiligingsinstellingen.</p>
                <a href="/profiel" class="btn btn-outline">Beveiliging beheren</a>
            </div>
            
            <div class="account-card">
                <div class="account-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
                    </svg>
                </div>
                <h3>E-mail Voorkeuren</h3>
                <p>Beheer je e-mail notificaties en nieuwsbrief instellingen.</p>
                <a href="/profiel" class="btn btn-outline">Voorkeuren instellen</a>
            </div>
            
            <div class="account-card">
                <div class="account-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8.5 1a6.5 6.5 0 1 0 0 13h4.5a.5.5 0 0 0 0-1h-4.5a5.5 5.5 0 1 1 0-11 6 6 0 0 1 6 6 .5.5 0 0 0 1 0 7 7 0 1 0-7 7Z"/>
                        <path d="M8.5 6.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0Z"/>
                    </svg>
                </div>
                <h3>Abonnementen</h3>
                <p>Bekijk en beheer je actieve abonnementen en factuurhistorie.</p>
                <a href="/dashboard" class="btn btn-outline">Abonnementen bekijken</a>
            </div>
        </div>
    </div>
</section>

<section class="section cta-section">
    <div class="container">
        <div class="cta-container">
            <h3>Hulp nodig?</h3>
            <p class="cta-text">Heb je vragen over je account of hulp nodig met onze tools? Neem contact met ons op voor persoonlijke ondersteuning.</p>
            <a href="/over-mij" class="btn btn-primary">Contact opnemen</a>
        </div>
    </div>
</section> 