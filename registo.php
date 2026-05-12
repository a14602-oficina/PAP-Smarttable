<?php
require_once "config/db.php";

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role     = trim($_POST["role"] ?? "");
    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $cofrfid  = trim($_POST["cofrfid"] ?? "");

    if ($username === "" || $password === "" || $role === "" || $name === "" || $email === "" || $cofrfid === "") {
        $mensagem = "Preenche todos os campos.";
        $tipoMensagem = "erro";
    } else {

        // Verificar duplicados
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $mensagem = "Utilizador ou email já existe.";
            $tipoMensagem = "erro";
        } else {

            // Hash da password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (username, password, role, name, email, cofrfid)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("ssssss", $username, $hash, $role, $name, $email, $cofrfid);

            if ($stmt->execute()) {
                $mensagem = "Registo efetuado com sucesso.";
                $tipoMensagem = "sucesso";
            } else {
                $mensagem = "Erro ao registar.";
                $tipoMensagem = "erro";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>SmartTable - Registo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS UNIFICADO -->
 <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="auth-page">

  <!-- BRAND -->
  <section class="brand">
    <div class="logo-box">S</div>

    <div>
      <p class="subtitle">RESTAURANT MANAGEMENT</p>
      <h1>SmartTable</h1>
      <p class="description">Criar nova conta no sistema</p>
    </div>
  </section>

  <!-- CARD -->
  <section class="auth-card wide">

    <h2>Registo</h2>
    <p class="card-text">Preenche os dados abaixo</p>

    <?php if ($mensagem !== ""): ?>
      <div class="msg <?= $tipoMensagem ?>">
        <?= htmlspecialchars($mensagem) ?>
      </div>
    <?php endif; ?>

    <form method="POST">

      <div class="form-grid">

        <div class="form-group">
          <label>Utilizador</label>
          <input type="text" name="username" placeholder="Ex: garcom01" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="********" required>
        </div>

        <div class="form-group">
          <label>Nome</label>
          <input type="text" name="name" placeholder="Nome completo" required>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="email@email.com" required>
        </div>

        <div class="form-group">
          <label>Função</label>
          <select name="role" required>
            <option value="">Selecionar</option>
            <option value="entrada">Entrada</option>
            <option value="mesa">Mesa</option>
            <option value="garcom">Garçom</option>
            <option value="cozinha">Cozinha</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="form-group">
          <label>RFID / Código</label>
          <input type="text" name="cofrfid" placeholder="Ex: RFID001" required>
        </div>

      </div>

      <button type="submit">Criar Conta</button>

    </form>

    <div class="switch-link">
      Já tens conta? <a href="login.php">Entrar</a>
    </div>

  </section>

</main>

</body>
</html>