<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>404 - Pagina no encontrada</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
  <div class="text-center">
    <h1 class="display-4 fw-bold text-danger">404</h1>
    <p class="text-muted">La pagina que busca no existe.</p>
    <a href="<?= \App\Helpers\Helpers::url('/dashboard') ?>" class="btn btn-danger">Volver al inicio</a>
  </div>
</body>
</html>
