<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config/db.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $software = trim($_POST["software"] ?? "");

    if ($username === "" || $password === "" || $software === "") {
        $erro = "Preenche todos os campos.";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                if ($software !== $user["role"]) {
                    $erro = "Este utilizador não pertence a este software.";
                } else {

                    // Sessão
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["name"] = $user["name"];

                    // Redirecionamento
                    switch ($user["role"]) {
                        case "admin":
                            header("Location: admin_dashboard.php");
                            break;
                        case "garcom":
                            header("Location: garcom_dashboard.php");
                            break;
                        case "cozinha":
                            header("Location: cozinha_dashboard.php");
                            break;
                        case "entrada":
                            header("Location: entrada_dashboard.php");
                            break;
                        case "mesa":
                            header("Location: mesa_dashboard.php");
                            break;
                    }
                    exit;
                }

            } else {
                $erro = "Password incorreta.";
            }

        } else {
            $erro = "Utilizador não encontrado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartTable - Login</title>

  <!-- CSS -->
 <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<main class="auth-page">

  <section class="brand">
    <div class="logo-box">S</div>
    <div>
      <p class="subtitle">RESTAURANT MANAGEMENT</p>
      <h1>SmartTable</h1>
      <p class="description">Acesso ao sistema</p>
    </div>
  </section>

  <section class="auth-card">

    <h2>Login</h2>
    <p class="card-text">Introduz os teus dados</p>

    <!-- ERRO -->
    <?php if (!empty($erro)): ?>
      <div class="msg erro">
        <?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="POST">

      <label>Utilizador</label>
      <input type="text" name="username" placeholder="Introduz o utilizador" required>

      <label>Palavra-passe</label>
      <input type="password" name="password" placeholder="Introduz a palavra-passe" required>

      <label>Software</label>
      <select name="software" required>
        <option value="">Seleciona o software</option>
        <option value="entrada">Tablet de Entrada</option>
        <option value="mesa">Tablets das Mesas</option>
        <option value="garcom">Garçom</option>
        <option value="cozinha">Cozinha</option>
        <option value="admin">Admin</option>
      </select>

      <button type="submit">Entrar</button>

    </form>

    <div class="switch-link">
      Não tens conta? <a href="registo.php">Registar</a>
    </div>

  </section>

</main>

</body>
</html>