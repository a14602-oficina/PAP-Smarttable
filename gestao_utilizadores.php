<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$msg = "";
$editUser = null;

/* ELIMINAR */
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    if ($id !== intval($_SESSION["user_id"])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $msg = "Utilizador eliminado com sucesso.";
    } else {
        $msg = "Não podes eliminar a tua própria conta.";
    }
}

/* BUSCAR PARA EDITAR */
if (isset($_GET["edit"])) {
    $id = intval($_GET["edit"]);
    $stmt = $conn->prepare("SELECT id, username, name, email, role, cofrfid FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
}

/* CRIAR / ATUALIZAR */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id       = intval($_POST["id"] ?? 0);
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $role     = trim($_POST["role"] ?? "");
    $cofrfid  = trim($_POST["cofrfid"] ?? "");

    if ($username === "" || $name === "" || $email === "" || $role === "" || $cofrfid === "") {
        $msg = "Preenche todos os campos obrigatórios.";
    } else {
        if ($id > 0) {
            if ($password !== "") {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username=?, password=?, name=?, email=?, role=?, cofrfid=? 
                    WHERE id=?
                ");
                $stmt->bind_param("ssssssi", $username, $hash, $name, $email, $role, $cofrfid, $id);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username=?, name=?, email=?, role=?, cofrfid=? 
                    WHERE id=?
                ");
                $stmt->bind_param("sssssi", $username, $name, $email, $role, $cofrfid, $id);
            }

            $stmt->execute();
            header("Location: gestao_utilizadores.php?msg=Utilizador atualizado com sucesso");
            exit;

        } else {
            if ($password === "") {
                $msg = "A password é obrigatória para criar utilizador.";
            } else {
                $check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
                $check->bind_param("ss", $username, $email);
                $check->execute();
                $exists = $check->get_result();

                if ($exists->num_rows > 0) {
                    $msg = "Username ou email já existe.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("
                        INSERT INTO users (username, password, role, name, email, cofrfid)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("ssssss", $username, $hash, $role, $name, $email, $cofrfid);
                    $stmt->execute();

                    header("Location: gestao_utilizadores.php?msg=Utilizador criado com sucesso");
                    exit;
                }
            }
        }
    }
}

if (isset($_GET["msg"])) {
    $msg = htmlspecialchars($_GET["msg"]);
}

$result = $conn->query("SELECT id, username, name, email, role, cofrfid FROM users ORDER BY id DESC");
$adminName = $_SESSION["name"] ?? "Administrador";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gestão de Utilizadores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{
  --gold:#c9a84c;
  --gold-light:#e8c97a;
  --dark:#0d0600;
  --cream:#f5ead8;
  --cream2:#ede0c4;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  font-family:Georgia,"Times New Roman",serif;
  background:
    linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px),
    linear-gradient(rgba(201,168,76,.03) 1px,transparent 1px),
    radial-gradient(circle at top left,rgba(201,168,76,.18),transparent 35%),
    linear-gradient(135deg,var(--dark),#080300 70%);
  background-size:80px 80px,80px 80px,cover,cover;
  color:var(--cream);
}
.admin-layout{display:flex;min-height:100vh}
.sidebar{
  width:285px;
  padding:26px 24px;
  background:linear-gradient(180deg,rgba(26,14,4,.98),rgba(13,6,0,.96));
  border-right:1px solid rgba(201,168,76,.22);
}
.brand{display:flex;align-items:center;gap:14px;margin-bottom:44px}
.logo{
  width:56px;height:56px;border-radius:16px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;display:flex;align-items:center;justify-content:center;
  font-size:28px;font-weight:bold;
}
.brand h2{font-size:22px;line-height:1}
.brand span{font-size:11px;color:rgba(245,234,216,.55);letter-spacing:3px}
.menu-title{font-size:12px;letter-spacing:4px;color:rgba(245,234,216,.5);margin-bottom:16px}
.menu{display:flex;flex-direction:column;gap:12px}
.menu a{
  padding:15px 18px;border-radius:14px;color:var(--cream2);
  text-decoration:none;background:rgba(201,168,76,.08);
  border:1px solid rgba(201,168,76,.12);
}
.menu a.active,.menu a:hover{
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;font-weight:bold;
}
.logout{
  margin-top:38px;display:block;padding:15px;border-radius:14px;text-align:center;
  color:#fca5a5;background:rgba(239,68,68,.10);
  border:1px solid rgba(239,68,68,.35);text-decoration:none;font-weight:bold;
}
.content{flex:1;padding:34px 46px}
.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;margin-bottom:36px}
.subtitle{color:var(--gold-light);letter-spacing:8px;font-size:13px;margin-bottom:6px}
.topbar h1{font-size:46px;line-height:1;color:var(--cream)}
.session{
  padding:14px 22px;border-radius:999px;border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.65);color:var(--cream2);white-space:nowrap;
}
.panel{
  border:1px solid rgba(201,168,76,.28);border-radius:22px;
  background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));
  box-shadow:0 18px 55px rgba(0,0,0,.35);overflow:hidden;margin-bottom:28px;
}
.panel-header{
  padding:24px 28px;display:flex;justify-content:space-between;align-items:center;
  border-bottom:1px solid rgba(201,168,76,.16);
}
.panel-header h2{color:var(--gold-light);font-size:28px}
.form-body{padding:28px}
.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.form-group{display:flex;flex-direction:column}
label{font-size:14px;color:var(--cream2);margin-bottom:8px}
input,select{
  padding:14px 15px;border-radius:13px;border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.72);color:var(--cream);outline:none;
}
input:focus,select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,168,76,.13)}
.btn{
  padding:13px 18px;border-radius:14px;background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;text-decoration:none;font-weight:bold;border:none;cursor:pointer;display:inline-block;
}
.btn-secondary{
  padding:13px 18px;border-radius:14px;background:rgba(201,168,76,.09);
  color:var(--gold-light);text-decoration:none;border:1px solid rgba(201,168,76,.22);display:inline-block;
}
.form-actions{margin-top:22px;display:flex;gap:12px}
.msg{
  padding:14px 18px;margin-bottom:24px;border-radius:14px;
  background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);
  color:#86efac;
}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:1000px}
th,td{padding:17px 20px;text-align:left;border-bottom:1px solid rgba(201,168,76,.12);font-size:15px}
th{color:var(--gold-light);background:rgba(201,168,76,.10);font-size:14px;letter-spacing:1px}
td{color:var(--cream2)}
tr:hover td{background:rgba(201,168,76,.045)}
.badge{
  display:inline-block;padding:7px 11px;border-radius:999px;
  background:rgba(201,168,76,.13);border:1px solid rgba(201,168,76,.25);
  color:var(--gold-light);text-transform:uppercase;font-size:12px;
}
.actions{white-space:nowrap}
.actions a{
  display:inline-block;padding:8px 11px;margin-right:7px;border-radius:10px;text-decoration:none;
  color:var(--gold-light);background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.18);
}
.actions a:hover{background:linear-gradient(135deg,var(--gold),var(--gold-light));color:#140900}
.delete-link{color:#fca5a5!important;border-color:rgba(239,68,68,.28)!important;background:rgba(239,68,68,.08)!important}
.empty{padding:28px;color:rgba(245,234,216,.65)}
@media(max-width:1100px){.form-grid{grid-template-columns:1fr 1fr}}
@media(max-width:900px){
  .admin-layout{flex-direction:column}
  .sidebar{width:100%}
  .content{padding:28px 20px}
  .topbar{flex-direction:column}
  .topbar h1{font-size:36px}
  .session{white-space:normal}
  .form-grid{grid-template-columns:1fr}
}
</style>
</head>

<body>

<div class="admin-layout">

  <aside class="sidebar">
    <div class="brand">
      <div class="logo">S</div>
      <div>
        <h2>SmartTable</h2>
        <span>ADMIN PANEL</span>
      </div>
    </div>

    <p class="menu-title">MENU</p>

    <nav class="menu">
      <a href="gestao_mesas.php">Gestão de Mesas</a>
      <a href="gestao_utilizadores.php" class="active">Gestão de Utilizadores</a>
      <a href="gestao_menus.php">Gestão de Menus</a>
    </nav>

    <a href="logout.php" class="logout">Terminar Sessão</a>
  </aside>

  <main class="content">

    <div class="topbar">
      <div>
        <p class="subtitle">RESTAURANT MANAGEMENT</p>
        <h1>Gestão de Utilizadores</h1>
      </div>

      <div class="session">
        Sessão iniciada como: <strong><?= htmlspecialchars($adminName) ?></strong>
      </div>
    </div>

    <?php if ($msg !== ""): ?>
      <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-header">
        <h2><?= $editUser ? "Editar Utilizador" : "Criar Utilizador" ?></h2>
      </div>

      <div class="form-body">
        <form method="POST" action="gestao_utilizadores.php">
          <input type="hidden" name="id" value="<?= htmlspecialchars($editUser["id"] ?? "") ?>">

          <div class="form-grid">
            <div class="form-group">
              <label>Utilizador</label>
              <input type="text" name="username" required value="<?= htmlspecialchars($editUser["username"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label>Password <?= $editUser ? "(deixa vazio para manter)" : "" ?></label>
              <input type="password" name="password" <?= $editUser ? "" : "required" ?>>
            </div>

            <div class="form-group">
              <label>Nome</label>
              <input type="text" name="name" required value="<?= htmlspecialchars($editUser["name"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required value="<?= htmlspecialchars($editUser["email"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label>Função</label>
              <select name="role" required>
                <?php
                $roles = ["entrada", "mesa", "garcom", "cozinha", "admin"];
                foreach ($roles as $role):
                    $selected = (($editUser["role"] ?? "") === $role) ? "selected" : "";
                ?>
                  <option value="<?= $role ?>" <?= $selected ?>><?= ucfirst($role) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>RFID / Código</label>
              <input type="text" name="cofrfid" required value="<?= htmlspecialchars($editUser["cofrfid"] ?? "") ?>">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn">
              <?= $editUser ? "Guardar Alterações" : "Criar Utilizador" ?>
            </button>

            <?php if ($editUser): ?>
              <a href="gestao_utilizadores.php" class="btn-secondary">Cancelar edição</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <h2>Lista de Utilizadores</h2>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Utilizador</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Função</th>
                <th>RFID</th>
                <th>Ações</th>
              </tr>
            </thead>

            <tbody>
              <?php while($u = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($u["id"]) ?></td>
                <td><?= htmlspecialchars($u["username"]) ?></td>
                <td><?= htmlspecialchars($u["name"]) ?></td>
                <td><?= htmlspecialchars($u["email"]) ?></td>
                <td><span class="badge"><?= htmlspecialchars($u["role"]) ?></span></td>
                <td><?= htmlspecialchars($u["cofrfid"]) ?></td>
                <td class="actions">
                  <a href="gestao_utilizadores.php?edit=<?= urlencode($u["id"]) ?>">Editar</a>
                  <a class="delete-link" href="gestao_utilizadores.php?delete=<?= urlencode($u["id"]) ?>" onclick="return confirm('Eliminar este utilizador?')">Eliminar</a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">Nenhum utilizador encontrado.</p>
      <?php endif; ?>
    </section>

  </main>

</div>

</body>
</html>