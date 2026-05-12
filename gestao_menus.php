<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["name"] ?? "Administrador";
$msg = "";
$editItem = null;

/* ELIMINAR */
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: gestao_menus.php?msg=Item eliminado com sucesso");
        exit;
    } else {
        $msg = "Erro ao eliminar item.";
    }
}

/* BUSCAR PARA EDITAR */
if (isset($_GET["edit"])) {
    $id = intval($_GET["edit"]);

    $stmt = $conn->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editItem = $stmt->get_result()->fetch_assoc();
}

/* CRIAR / ATUALIZAR */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = floatval($_POST["price"] ?? 0);
    $status = trim($_POST["status"] ?? "disponivel");

    $categoriasPermitidas = ["entrada", "prato", "bebida", "sobremesa"];
    $statusPermitidos = ["disponivel", "indisponivel"];

    if ($name === "" || $category === "" || $price <= 0) {
        $msg = "Preenche o nome, categoria e preço corretamente.";
    } elseif (!in_array($category, $categoriasPermitidas)) {
        $msg = "Categoria inválida.";
    } elseif (!in_array($status, $statusPermitidos)) {
        $msg = "Estado inválido.";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE menus
                SET name = ?, description = ?, category = ?, price = ?, status = ?
                WHERE id = ?
            ");

            $stmt->bind_param("sssdsi", $name, $description, $category, $price, $status, $id);

            if ($stmt->execute()) {
                header("Location: gestao_menus.php?msg=Item atualizado com sucesso");
                exit;
            } else {
                $msg = "Erro ao atualizar item.";
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO menus (name, description, category, price, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("sssds", $name, $description, $category, $price, $status);

            if ($stmt->execute()) {
                header("Location: gestao_menus.php?msg=Item criado com sucesso");
                exit;
            } else {
                $msg = "Erro ao criar item.";
            }
        }
    }
}

if (isset($_GET["msg"])) {
    $msg = htmlspecialchars($_GET["msg"]);
}

$result = $conn->query("SELECT * FROM menus ORDER BY category ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gestão de Menus</title>
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

.brand{
  display:flex;
  align-items:center;
  gap:14px;
  margin-bottom:44px;
}

.logo{
  width:56px;
  height:56px;
  border-radius:16px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:28px;
  font-weight:bold;
}

.brand h2{font-size:22px;line-height:1}
.brand span{font-size:11px;color:rgba(245,234,216,.55);letter-spacing:3px}

.menu-title{
  font-size:12px;
  letter-spacing:4px;
  color:rgba(245,234,216,.5);
  margin-bottom:16px;
}

.menu{
  display:flex;
  flex-direction:column;
  gap:12px;
}

.menu a{
  padding:15px 18px;
  border-radius:14px;
  color:var(--cream2);
  text-decoration:none;
  background:rgba(201,168,76,.08);
  border:1px solid rgba(201,168,76,.12);
}

.menu a.active,
.menu a:hover{
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  font-weight:bold;
}

.logout{
  margin-top:38px;
  display:block;
  padding:15px;
  border-radius:14px;
  text-align:center;
  color:#fca5a5;
  background:rgba(239,68,68,.10);
  border:1px solid rgba(239,68,68,.35);
  text-decoration:none;
  font-weight:bold;
}

.content{
  flex:1;
  padding:34px 46px;
}

.topbar{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:24px;
  margin-bottom:36px;
}

.subtitle{
  color:var(--gold-light);
  letter-spacing:8px;
  font-size:13px;
  margin-bottom:6px;
}

.topbar h1{
  font-size:46px;
  line-height:1;
  color:var(--cream);
}

.session{
  padding:14px 22px;
  border-radius:999px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.65);
  color:var(--cream2);
  white-space:nowrap;
}

.msg{
  padding:14px 18px;
  margin-bottom:24px;
  border-radius:14px;
  background:rgba(34,197,94,.12);
  border:1px solid rgba(34,197,94,.35);
  color:#86efac;
}

.panel{
  border:1px solid rgba(201,168,76,.28);
  border-radius:22px;
  background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));
  box-shadow:0 18px 55px rgba(0,0,0,.35);
  overflow:hidden;
  margin-bottom:28px;
}

.panel-header{
  padding:24px 28px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid rgba(201,168,76,.16);
}

.panel-header h2{
  color:var(--gold-light);
  font-size:28px;
}

.form-body{
  padding:28px;
}

.form-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:18px;
}

.form-group{
  display:flex;
  flex-direction:column;
}

.form-group.full{
  grid-column:1 / -1;
}

label{
  font-size:14px;
  color:var(--cream2);
  margin-bottom:8px;
}

input,
select,
textarea{
  width:100%;
  padding:14px 15px;
  border-radius:13px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.72);
  color:var(--cream);
  outline:none;
  font-family:Georgia,"Times New Roman",serif;
}

textarea{
  min-height:90px;
  resize:vertical;
}

input:focus,
select:focus,
textarea:focus{
  border-color:var(--gold);
  box-shadow:0 0 0 3px rgba(201,168,76,.13);
}

.form-actions{
  margin-top:22px;
  display:flex;
  gap:12px;
}

.btn{
  padding:13px 18px;
  border-radius:14px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  text-decoration:none;
  font-weight:bold;
  border:none;
  cursor:pointer;
  display:inline-block;
}

.btn-secondary{
  padding:13px 18px;
  border-radius:14px;
  background:rgba(201,168,76,.09);
  color:var(--gold-light);
  text-decoration:none;
  border:1px solid rgba(201,168,76,.22);
  display:inline-block;
}

.table-wrap{
  overflow-x:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:950px;
}

th,
td{
  padding:17px 20px;
  text-align:left;
  border-bottom:1px solid rgba(201,168,76,.12);
  font-size:15px;
  vertical-align:top;
}

th{
  color:var(--gold-light);
  background:rgba(201,168,76,.10);
  font-size:14px;
  letter-spacing:1px;
}

td{
  color:var(--cream2);
}

tr:hover td{
  background:rgba(201,168,76,.045);
}

.badge{
  display:inline-block;
  padding:7px 11px;
  border-radius:999px;
  background:rgba(201,168,76,.13);
  border:1px solid rgba(201,168,76,.25);
  color:var(--gold-light);
  text-transform:uppercase;
  font-size:12px;
}

.badge.off{
  color:#fca5a5;
  border-color:rgba(239,68,68,.35);
  background:rgba(239,68,68,.10);
}

.price{
  color:var(--gold-light);
  font-weight:bold;
}

.actions{
  white-space:nowrap;
}

.actions a{
  display:inline-block;
  padding:8px 11px;
  margin-right:7px;
  border-radius:10px;
  text-decoration:none;
  color:var(--gold-light);
  background:rgba(201,168,76,.08);
  border:1px solid rgba(201,168,76,.18);
}

.actions a:hover{
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
}

.delete-link{
  color:#fca5a5!important;
  border-color:rgba(239,68,68,.28)!important;
  background:rgba(239,68,68,.08)!important;
}

.empty{
  padding:28px;
  color:rgba(245,234,216,.65);
}

@media(max-width:1100px){
  .form-grid{grid-template-columns:1fr 1fr}
}

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
      <a href="gestao_utilizadores.php">Gestão de Utilizadores</a>
      <a href="gestao_menus.php" class="active">Gestão de Menus</a>
    </nav>

    <a href="logout.php" class="logout">Terminar Sessão</a>
  </aside>

  <main class="content">

    <div class="topbar">
      <div>
        <p class="subtitle">RESTAURANT MANAGEMENT</p>
        <h1>Gestão de Menus</h1>
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
        <h2><?= $editItem ? "Editar Item" : "Criar Item do Menu" ?></h2>
      </div>

      <div class="form-body">
        <form method="POST" action="gestao_menus.php">
          <input type="hidden" name="id" value="<?= htmlspecialchars($editItem["id"] ?? "") ?>">

          <div class="form-grid">

            <div class="form-group">
              <label>Nome do item</label>
              <input type="text" name="name" required value="<?= htmlspecialchars($editItem["name"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label>Categoria</label>
              <select name="category" required>
                <?php
                $categorias = ["entrada", "prato", "bebida", "sobremesa"];
                foreach ($categorias as $cat):
                    $selected = (($editItem["category"] ?? "") === $cat) ? "selected" : "";
                ?>
                  <option value="<?= $cat ?>" <?= $selected ?>><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Preço (€)</label>
              <input type="number" step="0.01" min="0.01" name="price" required value="<?= htmlspecialchars($editItem["price"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label>Estado</label>
              <select name="status" required>
                <option value="disponivel" <?= (($editItem["status"] ?? "") === "disponivel") ? "selected" : "" ?>>Disponível</option>
                <option value="indisponivel" <?= (($editItem["status"] ?? "") === "indisponivel") ? "selected" : "" ?>>Indisponível</option>
              </select>
            </div>

            <div class="form-group full">
              <label>Descrição</label>
              <textarea name="description"><?= htmlspecialchars($editItem["description"] ?? "") ?></textarea>
            </div>

          </div>

          <div class="form-actions">
            <button type="submit" class="btn">
              <?= $editItem ? "Guardar Alterações" : "Criar Item" ?>
            </button>

            <?php if ($editItem): ?>
              <a href="gestao_menus.php" class="btn-secondary">Cancelar edição</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <h2>Lista de Itens</h2>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estado</th>
                <th>Ações</th>
              </tr>
            </thead>

            <tbody>
              <?php while($item = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($item["id"]) ?></td>
                <td><?= htmlspecialchars($item["name"]) ?></td>
                <td><?= htmlspecialchars($item["description"]) ?></td>
                <td><span class="badge"><?= htmlspecialchars($item["category"]) ?></span></td>
                <td class="price"><?= number_format((float)$item["price"], 2, ",", ".") ?> €</td>
                <td>
                  <span class="badge <?= $item["status"] === "indisponivel" ? "off" : "" ?>">
                    <?= htmlspecialchars($item["status"]) ?>
                  </span>
                </td>
                <td class="actions">
                  <a href="gestao_menus.php?edit=<?= urlencode($item["id"]) ?>">Editar</a>
                  <a class="delete-link" href="gestao_menus.php?delete=<?= urlencode($item["id"]) ?>" onclick="return confirm('Eliminar este item do menu?')">Eliminar</a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">Nenhum item encontrado.</p>
      <?php endif; ?>
    </section>

  </main>

</div>

</body>
</html>