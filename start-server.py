import http.server
import socketserver

PORT = 8000

Handler = http.server.SimpleHTTPRequestHandler

with socketserver.TCPServer(("", PORT), Handler) as httpd:
    print(f"Server draait op http://localhost:{PORT}")
    print("Druk Ctrl+C om de server te stoppen")
    httpd.serve_forever() 