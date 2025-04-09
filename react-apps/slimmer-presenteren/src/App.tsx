import React, { useState } from 'react';

// We importeren geen App.css meer, omdat we globale stijlen gebruiken

function SlimmerPresenterenTool() {
  const [reactCode, setReactCode] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [downloadUrl, setDownloadUrl] = useState<string | null>(null);

  const handleCodeChange = (event: React.ChangeEvent<HTMLTextAreaElement>) => {
    setReactCode(event.target.value);
    setError(null);
    setDownloadUrl(null);
  };

  const handleConvertClick = async () => {
    if (!reactCode.trim()) {
      setError('Voer alstublieft React code in.');
      return;
    }

    setIsLoading(true);
    setError(null);
    setDownloadUrl(null);

    try {
      const response = await fetch('/api/slimmer-presenteren/convert.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          // Voeg hier eventueel CSRF token of andere benodigde headers toe
          // Afhankelijk van de beveiliging van de hoofdwebsite
        },
        body: JSON.stringify({ reactCode }),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || `Fout ${response.status}`);
      }

      if (result.status === 'success' && result.downloadUrl) {
        setDownloadUrl(result.downloadUrl);
      } else {
        throw new Error(result.message || 'Conversie mislukt. Ongeldige response.');
      }
    } catch (err: any) { // Gebruik 'any' of een specifieker Error type
      console.error("Conversie fout:", err);
      setError(err.message || 'Er is een onbekende fout opgetreden.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    // Gebruik een div die de container van de tool voorstelt.
    // We gaan er vanuit dat de omringende PHP pagina al de .container class heeft.
    <div className="tool-interface"> 
      {/* Gebruik form-control of een vergelijkbare class voor textareas */}
      <textarea
        className="form-control" // Voorbeeld class, controleer /css/style.css
        rows={10} // Geef een indicatie van hoogte
        value={reactCode}
        onChange={handleCodeChange}
        placeholder="Plak hier uw React code..."
        disabled={isLoading}
        style={{ marginBottom: '1rem', fontFamily: 'monospace', fontSize: '0.9rem' }} // Extra inline stijl voor duidelijkheid
      />
      
      {/* Gebruik btn en btn-primary klassen voor de knop */}
      <button
        className={`btn btn-primary ${isLoading ? 'disabled' : ''}`}
        onClick={handleConvertClick}
        disabled={isLoading}
        style={{ marginRight: '0.5rem' }} // Kleine marge rechts
      >
        {isLoading ? 'Bezig met converteren...' : 'Converteer naar PowerPoint'}
      </button>

      {/* Resultaat weergave */}
      {(error || downloadUrl || isLoading) && (
         // Gebruik 'auth-message' als basis container voor status
         <div className="auth-message" style={{ marginTop: '1.5rem', display: 'block' }}> 
          {isLoading && <p><i>Een moment geduld, de presentatie wordt gegenereerd...</i></p>}
          
          {/* Gebruik 'auth-message--error' voor fouten */}
          {error && <p className="auth-message--error" style={{ display: 'block' }}>Fout: {error}</p>}
          
          {/* Gebruik 'auth-message--success' voor succes */}
          {downloadUrl && (
            <div className="auth-message--success" style={{ display: 'block' }}>
              <p>✅ Uw presentatie is klaar!</p>
              {/* Gebruik 'btn-primary' voor de downloadknop */}
              <a href={downloadUrl} className="btn btn-primary" download style={{ marginTop: '0.5rem' }}>
                Download Presentatie (.pptx)
              </a>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default SlimmerPresenterenTool;
