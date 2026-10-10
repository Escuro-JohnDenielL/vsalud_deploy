<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 Too many requests | Villa Salud Catering</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    @vite('resources/css/errors.css')
</head>
<body class="vs-error-page">
    <main class="vs-error-page__layout">
        <section class="vs-card" aria-labelledby="error-title">
            <span class="vs-badge vs-badge--warning">HTTP 429 | TOO MANY REQUESTS</span>
            <h1 class="vs-error-page__title" id="error-title">Too many requests</h1>
            <p class="vs-error-page__message">Please wait a moment before trying again.</p>
            <a class="vs-btn vs-btn--primary" href="/">Return to Villa Salud home</a>
        </section>
    </main>
</body>
</html>