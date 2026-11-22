<?php
declare(strict_types=1);

/**
 * processar.php — Tech Experience 2026
 * Fluxo: método HTTP -> normalização -> validação (whitelists) -> saída escapada.
 */

// Escape de saída: todo texto ecoado passa por aqui (proteção contra XSS).
function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// ---------- 1. Verificação do método HTTP ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>405 — Método não permitido</title><link rel="stylesheet" href="style.css"></head>'
       . '<body><main class="container"><section class="card alerta-erro">'
       . '<h2>405 — Method Not Allowed</h2>'
       . '<p>Esta página só aceita envios pelo formulário (método POST).</p>'
       . '<p><a class="btn" href="index.html">Ir para o formulário</a></p>'
       . '</section></main></body></html>';
    exit;
}

// ---------- Whitelists ----------
$niveisPermitidos = [
    'iniciante'     => 'Iniciante',
    'intermediario' => 'Intermediário',
    'avancado'      => 'Avançado',
];
$turnosPermitidos = [
    'manha' => 'Manhã',
    'noite' => 'Noite',
];
$oficinasPermitidas = [
    'html_css'    => 'HTML & CSS',
    'js_dom'      => 'JavaScript & DOM',
    'php_backend' => 'PHP Backend',
];

// ---------- 2. Leitura e normalização (?? + trim) ----------
// is_string evita TypeError caso alguém envie nome[]=x para um campo escalar.
$lerTexto = static fn(string $chave): string =>
    is_string($_POST[$chave] ?? null) ? trim($_POST[$chave]) : '';

$nome        = $lerTexto('nome');
$email       = $lerTexto('email');
$experiencia = $lerTexto('experiencia');
$turno       = $lerTexto('turno');
$mensagem    = $lerTexto('mensagem');
$oficinas    = $_POST['oficinas'] ?? [];

// ---------- 3. Validação ----------
$erros = [];

if (mb_strlen($nome, 'UTF-8') < 3) {
    $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
} elseif (mb_strlen($nome, 'UTF-8') > 100) {
    $erros[] = 'O nome deve ter no máximo 100 caracteres.';
}

if ($email === '') {
    $erros[] = 'O e-mail é obrigatório.';
} elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $erros[] = 'Informe um e-mail em formato válido.';
}

if ($experiencia === '') {
    $erros[] = 'Selecione o nível de experiência.';
} elseif (!in_array($experiencia, array_keys($niveisPermitidos), true)) {
    $erros[] = 'Nível de experiência inválido.';
}

if ($turno === '') {
    $erros[] = 'Selecione o turno de preferência.';
} elseif (!in_array($turno, array_keys($turnosPermitidos), true)) {
    $erros[] = 'Turno inválido.';
}

if (!is_array($oficinas)) {
    $erros[] = 'Formato inválido para as oficinas.';
    $oficinas = [];
} else {
    $oficinasValidas = [];
    $temInvalida = false;
    foreach ($oficinas as $item) {
        if (!is_string($item) || !in_array($item, array_keys($oficinasPermitidas), true)) {
            $erros[] = 'Foi enviada uma oficina inválida.';
            $temInvalida = true;
            break;
        }
        $oficinasValidas[] = $item;
    }
    $oficinas = array_values(array_unique($oficinasValidas));
    if (!$temInvalida && count($oficinas) === 0) {
        $erros[] = 'Selecione pelo menos uma oficina.';
    }
}

if (mb_strlen($mensagem, 'UTF-8') > 200) {
    $erros[] = 'A mensagem deve ter no máximo 200 caracteres.';
}

$sucesso = count($erros) === 0;
if (!$sucesso) {
    http_response_code(422); // dados recebidos, mas inválidos
}

// ---------- 4. Renderização segura ----------
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $sucesso ? 'Inscrição confirmada' : 'Corrija os dados' ?> — Tech Experience 2026</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="site-header">
    <h1>Tech Experience 2026</h1>
    <p>Inscrição nas oficinas técnicas do evento acadêmico</p>
  </header>

  <main class="container">
    <section class="card">
<?php if (!$sucesso): ?>
      <h2>Não foi possível concluir a inscrição</h2>
      <div class="alerta-erro" role="alert">
        <strong>Corrija os itens abaixo:</strong>
        <ul>
<?php foreach ($erros as $erro): ?>
          <li><?= e($erro) ?></li>
<?php endforeach; ?>
        </ul>
      </div>
      <p style="margin-top:1.5rem"><a class="btn" href="index.html">Voltar ao formulário</a></p>
<?php else: ?>
      <h2>Inscrição confirmada</h2>
      <p class="alerta-sucesso">Obrigado, <?= e($nome) ?>! Seus dados foram recebidos.</p>
      <dl class="dados">
        <dt>Nome</dt>        <dd><?= e($nome) ?></dd>
        <dt>E-mail</dt>      <dd><?= e($email) ?></dd>
        <dt>Experiência</dt> <dd><?= e($niveisPermitidos[$experiencia]) ?></dd>
        <dt>Turno</dt>       <dd><?= e($turnosPermitidos[$turno]) ?></dd>
        <dt>Oficinas</dt>
        <dd><?= e(implode(', ', array_map(static fn($o) => $oficinasPermitidas[$o], $oficinas))) ?></dd>
        <dt>Mensagem</dt>
        <dd><?= $mensagem !== '' ? nl2br(e($mensagem)) : '<em>(não informada)</em>' ?></dd>
      </dl>
      <a class="btn" href="index.html">Nova inscrição</a>
<?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2026 Tech Experience — Desenvolvimento Web</p>
  </footer>
</body>
</html>
