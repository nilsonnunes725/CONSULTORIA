<?php
session_start();

// ⚠️ TROQUE ESSA SENHA POR UMA SUA (forte!)
define('JosePaulo', 'sss113@1');

// Logout
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

// Login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    }
    $error = 'Senha incorreta';
}
$logged = !empty($_SESSION['admin']);

// Upload / Delete
$msg = '';
if ($logged && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $jsonFile = __DIR__ . '/portfolio.json';
    $items = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
    if (!is_array($items)) $items = [];

    if ($_POST['action'] === 'upload' && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $isImage = @getimagesize($_FILES['image']['tmp_name']) !== false;

        if (in_array($ext, $allowed) && $isImage && $_FILES['image']['size'] <= 5*1024*1024) {
            if (!is_dir(__DIR__ . '/portfolio')) mkdir(__DIR__ . '/portfolio', 0755, true);
            $filename = uniqid('img_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/portfolio/' . $filename);
            array_unshift($items, [
                'file' => $filename,
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'])
            ]);
            file_put_contents($jsonFile, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $msg = '✅ Item adicionado!';
        } else {
            $msg = '❌ Arquivo inválido ou maior que 5MB.';
        }
    }

    if ($_POST['action'] === 'delete' && !empty($_POST['file'])) {
        $fileToDelete = basename($_POST['file']);
        @unlink(__DIR__ . '/portfolio/' . $fileToDelete);
        $items = array_values(array_filter($items, fn($i) => $i['file'] !== $fileToDelete));
        file_put_contents($jsonFile, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = '🗑 Item removido.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Portfólio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> body { background:#f4f6f9; } .card { border-radius:12px; } </style>
</head>
<body>

<?php if (!$logged): ?>
  <!-- LOGIN -->
  <div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card p-4 shadow" style="max-width:400px; width:100%;">
      <h3 class="text-center mb-4">🔐 Área Restrita</h3>
      <form method="POST">
        <input type="password" name="password" class="form-control mb-3" placeholder="Senha" required autofocus>
        <button name="login" value="1" class="btn btn-primary w-100">Entrar</button>
        <?php if ($error): ?><p class="text-danger mt-3 mb-0 small text-center"><?= $error ?></p><?php endif; ?>
      </form>
      <a href="index.html" class="text-center d-block mt-3 small">← Voltar ao site</a>
    </div>
  </div>

<?php else: ?>
  <!-- PAINEL -->
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="m-0">Gerenciar Portfólio</h2>
      <a href="?logout=1" class="btn btn-outline-danger">Sair</a>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Upload -->
    <div class="card p-4 shadow-sm mb-4">
      <h5 class="mb-3">➕ Adicionar novo item</h5>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" class="form-control mb-2" placeholder="Título" required maxlength="80">
        <textarea name="description" class="form-control mb-2" rows="2" placeholder="Descrição curta" required maxlength="200"></textarea>
        <input type="file" name="image" class="form-control mb-2" accept="image/*" required>
        <button name="action" value="upload" class="btn btn-success">Adicionar</button>
      </form>
    </div>

    <!-- Lista -->
    <h5 class="mb-3">📸 Itens cadastrados</h5>
    <div class="row g-3">
      <?php
        $list = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        if (!$list) echo '<div class="col-12 text-muted">Nenhum item cadastrado.</div>';
        foreach ($list as $item): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <img src="portfolio/<?= htmlspecialchars($item['file']) ?>" class="card-img-top"
                 style="height:180px; object-fit:cover;" alt="<?= htmlspecialchars($item['title']) ?>">
            <div class="card-body">
              <h6 class="fw-bold"><?= htmlspecialchars($item['title']) ?></h6>
              <p class="small mb-2"><?= htmlspecialchars($item['description']) ?></p>
              <form method="POST" onsubmit="return confirm('Excluir este item?')">
                <input type="hidden" name="file" value="<?= htmlspecialchars($item['file']) ?>">
                <button name="action" value="delete" class="btn btn-sm btn-danger">🗑 Excluir</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

</body>
</html>