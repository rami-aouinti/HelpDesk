<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation | HelpDesk</title>
    <meta name="description" content="Explore the HelpDesk REST API, review available endpoints and understand authentication and integration options.">
    <meta name="application-name" content="HelpDesk">
    <meta name="theme-color" content="#111827">
    <link rel="icon" type="image/png" href="{{ asset('helpdesk.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('helpdesk.png') }}">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body { margin: 0; background: #f8fafc; }
        .topbar { display: none; }
        .page-header { padding: 24px clamp(20px, 5vw, 64px); background: #111827; color: white; }
        .page-header h1 { margin: 0 0 6px; font-size: 24px; }
        .page-header p { margin: 0; color: #cbd5e1; }
        #swagger-ui { max-width: 1440px; margin: 24px auto; padding: 0 20px; }
    </style>
</head>
<body>
    <header class="page-header">
        <h1>HelpDesk REST API</h1>
        <p>Version 1 · Sanctum Bearer Authentication · OpenAPI 3.1</p>
    </header>
    <main id="swagger-ui"></main>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: @json(route('api.openapi')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                displayRequestDuration: true,
                persistAuthorization: true,
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout'
            });
        };
    </script>
</body>
</html>
