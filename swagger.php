<?php
// Página simple para cargar Swagger UI apuntando a openapi.yaml
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Swagger — TriviaScore API</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4/swagger-ui.css" />
  <style>body{margin:0;background:#0b1220;color:#fff} .swagger-ui .topbar{background:linear-gradient(90deg,#7c3aed,#06b6d4)}</style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
  <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: 'openapi.yaml',
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis],
        layout: 'BaseLayout',
        docExpansion: 'none'
      });
    };
  </script>
</body>
</html>
