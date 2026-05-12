<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["name"] ?? "Administrador";
$msg = "";

/* ADICIONAR MESA */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_table"])) {
    $number = intval($_POST["number"] ?? 0);
    $capacity = intval($_POST["capacity"] ?? 0);
    $status = trim($_POST["status"] ?? "livre");
    $type_table = trim($_POST["type_table"] ?? "quadrada");

    if ($number <= 0 || $capacity <= 0) {
        $msg = "Número e capacidade são obrigatórios.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO `tables` (`number`, `capacity`, `occupied_seats`, `status`, `type_table`)
            VALUES (?, ?, 0, ?, ?)
        ");

        $stmt->bind_param("iiss", $number, $capacity, $status, $type_table);

        if ($stmt->execute()) {
            $msg = "Mesa adicionada com sucesso.";
        } else {
            $msg = "Erro ao adicionar mesa. Verifica se o número já existe.";
        }
    }
}

/* EDITAR MESA */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_table"])) {
    $id = intval($_POST["id"] ?? 0);
    $number = intval($_POST["number"] ?? 0);
    $capacity = intval($_POST["capacity"] ?? 0);
    $occupied_seats = intval($_POST["occupied_seats"] ?? 0);
    $status = trim($_POST["status"] ?? "livre");
    $type_table = trim($_POST["type_table"] ?? "quadrada");

    if ($id <= 0 || $number <= 0 || $capacity <= 0) {
        $msg = "Dados inválidos.";
    } elseif ($occupied_seats < 0 || $occupied_seats > $capacity) {
        $msg = "Os lugares ocupados não podem ser superiores à capacidade.";
    } else {
        $stmt = $conn->prepare("
            UPDATE `tables`
            SET
                `number` = ?,
                `capacity` = ?,
                `occupied_seats` = ?,
                `status` = ?,
                `type_table` = ?
            WHERE `id` = ?
        ");

        $stmt->bind_param(
            "iiissi",
            $number,
            $capacity,
            $occupied_seats,
            $status,
            $type_table,
            $id
        );

        if ($stmt->execute()) {
            $msg = "Mesa atualizada com sucesso.";
        } else {
            $msg = "Erro ao atualizar mesa.";
        }
    }
}

/* REMOVER MESA */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_table"])) {
    $id = intval($_POST["id"] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM `tables` WHERE `id` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $msg = "Mesa removida com sucesso.";
        } else {
            $msg = "Erro ao remover mesa.";
        }
    }
}

/* LISTAR MESAS */
$result = $conn->query("
    SELECT *
    FROM `tables`
    ORDER BY `number` ASC
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SmartTable - Gestão de Mesas</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{
  --gold:#c9a84c;
  --gold-light:#e8c97a;
  --dark:#0d0600;
  --dark2:#1a0e04;
  --dark3:#261508;
  --cream:#f5ead8;
  --cream2:#ede0c4;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

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

/* LAYOUT ADMIN */
.admin-layout{
  display:flex;
  min-height:100vh;
}

.sidebar{
  width:285px;
  min-width:285px;
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

.brand h2{
  font-size:22px;
  line-height:1;
}

.brand span{
  font-size:11px;
  color:rgba(245,234,216,.55);
  letter-spacing:3px;
}

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

/* CONTEÚDO */
.content{
  flex:1;
  padding:34px 46px;
  overflow-x:auto;
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
  padding:16px;
  border-radius:14px;
  margin-bottom:24px;
  background:rgba(34,197,94,.12);
  border:1px solid rgba(34,197,94,.35);
  color:#86efac;
}

/* PAINEL */
.panel{
  border:1px solid rgba(201,168,76,.25);
  border-radius:24px;
  background:linear-gradient(145deg,rgba(38,21,8,.82),rgba(13,6,0,.95));
  overflow:hidden;
  box-shadow:0 18px 55px rgba(0,0,0,.35);
  margin-bottom:28px;
}

.panel-header{
  padding:22px 28px;
  border-bottom:1px solid rgba(201,168,76,.15);
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:20px;
}

.panel-header h2{
  color:var(--gold-light);
  font-size:28px;
}

/* FORM ADICIONAR */
.form-grid{
  padding:28px;
  display:grid;
  grid-template-columns:repeat(4,1fr);
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
  margin-bottom:8px;
  color:var(--cream2);
  font-size:14px;
}

input,
select{
  padding:12px;
  border-radius:12px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.72);
  color:var(--cream);
  outline:none;
}

button{
  padding:12px;
  border:none;
  border-radius:12px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  font-weight:bold;
  cursor:pointer;
}

/* LEGENDA */
.legend{
  display:flex;
  gap:14px;
  flex-wrap:wrap;
  color:var(--cream2);
  font-size:14px;
}

.legend span{
  display:flex;
  align-items:center;
  gap:7px;
}

.dot{
  width:10px;
  height:10px;
  border-radius:50%;
  display:inline-block;
}

.dot.livre{background:#22c55e}
.dot.ocupada{background:#ef4444}
.dot.reservada{background:#f59e0b}
.dot.manutencao{background:#64748b}

/* MAPA */
.room{
  margin:28px;
  padding:34px;
  border-radius:22px;
  border:1px solid rgba(201,168,76,.22);
  background:
    radial-gradient(circle at 15% 20%,rgba(201,168,76,.10),transparent 22%),
    linear-gradient(145deg,rgba(13,6,0,.65),rgba(38,21,8,.55));
}

.room-title{
  color:rgba(245,234,216,.6);
  font-size:14px;
  margin-bottom:28px;
  letter-spacing:3px;
}

.tables-grid{
  display:grid;
  grid-template-columns:repeat(6,1fr);
  gap:34px 28px;
}

.table-card{
  min-height:320px;
  padding:16px;
  border-radius:20px;
  border:1px solid rgba(201,168,76,.16);
  background:rgba(13,6,0,.35);
  display:flex;
  flex-direction:column;
  align-items:center;
}

.table-visual{
  height:120px;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  margin-bottom:14px;
}

.table-shape{
  position:relative;
  width:78px;
  height:78px;
  border:2px solid rgba(201,168,76,.7);
  background:linear-gradient(145deg,rgba(38,21,8,.95),rgba(13,6,0,.95));
  box-shadow:0 12px 28px rgba(0,0,0,.45);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
}

.table-card.redonda .table-shape{
  border-radius:50%;
}

.table-card.quadrada .table-shape{
  border-radius:12px;
}

.table-card.retangular .table-shape{
  width:105px;
  border-radius:14px;
}

.table-number{
  font-size:28px;
  font-weight:bold;
  color:var(--cream);
  line-height:1;
}

.table-seats{
  font-size:12px;
  margin-top:6px;
}

.table-card.livre .table-seats{color:#86efac}
.table-card.ocupada .table-seats{color:#fca5a5}
.table-card.reservada .table-seats{color:#fbbf24}
.table-card.manutencao .table-seats{color:#cbd5e1}

.status-ring{
  position:absolute;
  inset:-7px;
  border-radius:inherit;
  pointer-events:none;
}

.table-card.livre .status-ring{
  border:2px solid rgba(34,197,94,.55);
  box-shadow:0 0 18px rgba(34,197,94,.18);
}

.table-card.ocupada .status-ring{
  border:2px solid rgba(239,68,68,.55);
  box-shadow:0 0 18px rgba(239,68,68,.18);
}

.table-card.reservada .status-ring{
  border:2px solid rgba(245,158,11,.65);
  box-shadow:0 0 18px rgba(245,158,11,.18);
}

.table-card.manutencao .status-ring{
  border:2px solid rgba(100,116,139,.75);
  box-shadow:0 0 18px rgba(100,116,139,.18);
}

/* CADEIRAS */
.chair{
  position:absolute;
  width:18px;
  height:18px;
  border-radius:5px;
  background:rgba(201,168,76,.18);
  border:1px solid rgba(201,168,76,.32);
}

.c1{top:-22px;left:50%;transform:translateX(-50%)}
.c2{bottom:-22px;left:50%;transform:translateX(-50%)}
.c3{left:-24px;top:50%;transform:translateY(-50%)}
.c4{right:-24px;top:50%;transform:translateY(-50%)}
.c5{top:-16px;left:-16px}
.c6{top:-16px;right:-16px}
.c7{bottom:-16px;left:-16px}
.c8{bottom:-16px;right:-16px}

/* FORM DO CARTÃO */
.table-form{
  width:100%;
  display:flex;
  flex-direction:column;
  gap:8px;
}

.table-form label{
  font-size:12px;
}

.table-form input,
.table-form select{
  width:100%;
  padding:9px 10px;
  border-radius:9px;
}

.actions{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:8px;
  margin-top:8px;
}

.btn-danger{
  background:rgba(239,68,68,.18);
  color:#fca5a5;
  border:1px solid rgba(239,68,68,.35);
}

@media(max-width:1400px){
  .tables-grid{
    grid-template-columns:repeat(4,1fr);
  }
}

@media(max-width:1000px){
  .admin-layout{
    flex-direction:column;
  }

  .sidebar{
    width:100%;
    min-width:100%;
  }

  .content{
    padding:28px 20px;
  }

  .tables-grid{
    grid-template-columns:repeat(2,1fr);
  }

  .form-grid{
    grid-template-columns:1fr 1fr;
  }

  .topbar{
    flex-direction:column;
  }
}

@media(max-width:650px){
  .tables-grid{
    grid-template-columns:1fr;
  }

  .form-grid{
    grid-template-columns:1fr;
  }

  .room{
    margin:16px;
    padding:20px;
  }

  .panel-header{
    flex-direction:column;
    align-items:flex-start;
  }

  .topbar h1{
    font-size:36px;
  }
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
      <a href="gestao_mesas.php" class="active">Gestão de Mesas</a>
      <a href="gestao_utilizadores.php">Gestão de Utilizadores</a>
      <a href="gestao_menus.php">Gestão de Menus</a>
    </nav>

    <a href="logout.php" class="logout">Terminar Sessão</a>
  </aside>

  <main class="content">

    <div class="topbar">
      <div>
        <p class="subtitle">RESTAURANT MANAGEMENT</p>
        <h1>Gestão de Mesas</h1>
      </div>

      <div class="session">
        Sessão iniciada como:
        <strong><?= htmlspecialchars($adminName) ?></strong>
      </div>
    </div>

    <?php if($msg !== ""): ?>
      <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-header">
        <h2>Adicionar Mesa</h2>
      </div>

      <form method="POST">
        <div class="form-grid">

          <div class="form-group">
            <label>Número da Mesa</label>
            <input type="number" name="number" required>
          </div>

          <div class="form-group">
            <label>Capacidade</label>
            <input type="number" name="capacity" min="1" required>
          </div>

          <div class="form-group">
            <label>Estado</label>
            <select name="status">
              <option value="livre">Livre</option>
              <option value="ocupada">Ocupada</option>
              <option value="reservada">Reservada</option>
              <option value="manutencao">Manutenção</option>
            </select>
          </div>

          <div class="form-group">
            <label>Tipo</label>
            <select name="type_table">
              <option value="quadrada">Quadrada</option>
              <option value="redonda">Redonda</option>
              <option value="retangular">Retangular</option>
            </select>
          </div>

          <div class="form-group full">
            <button type="submit" name="add_table">Adicionar Mesa</button>
          </div>

        </div>
      </form>
    </section>

    <section class="panel">
      <div class="panel-header">
        <h2>Mapa da Sala</h2>

        <div class="legend">
          <span><i class="dot livre"></i> Livre</span>
          <span><i class="dot ocupada"></i> Ocupada</span>
          <span><i class="dot reservada"></i> Reservada</span>
          <span><i class="dot manutencao"></i> Manutenção</span>
        </div>
      </div>

      <div class="room">
        <p class="room-title">SALA PRINCIPAL · MAPA DE MESAS</p>

        <div class="tables-grid">

          <?php if($result && $result->num_rows > 0): ?>
            <?php while($mesa = $result->fetch_assoc()): ?>
              <?php
                $capacity = intval($mesa["capacity"]);
                $occupied = intval($mesa["occupied_seats"]);
                $chairs = min($capacity, 8);
              ?>

              <div class="table-card <?= htmlspecialchars($mesa["status"]) ?> <?= htmlspecialchars($mesa["type_table"]) ?>">

                <div class="table-visual">
                  <div class="table-shape">
                    <span class="status-ring"></span>

                    <?php for($i=1; $i <= $chairs; $i++): ?>
                      <span class="chair c<?= $i ?>"></span>
                    <?php endfor; ?>

                    <span class="table-number">
                      <?= htmlspecialchars($mesa["number"]) ?>
                    </span>

                    <span class="table-seats">
                      <?= $occupied ?>/<?= $capacity ?> ocupados
                    </span>
                  </div>
                </div>

                <form class="table-form" method="POST">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($mesa["id"]) ?>">

                  <label>Número</label>
                  <input type="number" name="number" value="<?= htmlspecialchars($mesa["number"]) ?>" required>

                  <label>Capacidade</label>
                  <input type="number" name="capacity" min="1" value="<?= htmlspecialchars($mesa["capacity"]) ?>" required>

                  <label>Ocupados</label>
                  <input
                    type="number"
                    name="occupied_seats"
                    min="0"
                    max="<?= htmlspecialchars($mesa["capacity"]) ?>"
                    value="<?= htmlspecialchars($mesa["occupied_seats"]) ?>"
                    required
                  >

                  <label>Estado</label>
                  <select name="status">
                    <option value="livre" <?= $mesa["status"] === "livre" ? "selected" : "" ?>>Livre</option>
                    <option value="ocupada" <?= $mesa["status"] === "ocupada" ? "selected" : "" ?>>Ocupada</option>
                    <option value="reservada" <?= $mesa["status"] === "reservada" ? "selected" : "" ?>>Reservada</option>
                    <option value="manutencao" <?= $mesa["status"] === "manutencao" ? "selected" : "" ?>>Manutenção</option>
                  </select>

                  <label>Tipo</label>
                  <select name="type_table">
                    <option value="quadrada" <?= $mesa["type_table"] === "quadrada" ? "selected" : "" ?>>Quadrada</option>
                    <option value="redonda" <?= $mesa["type_table"] === "redonda" ? "selected" : "" ?>>Redonda</option>
                    <option value="retangular" <?= $mesa["type_table"] === "retangular" ? "selected" : "" ?>>Retangular</option>
                  </select>

                  <div class="actions">
                    <button type="submit" name="edit_table">Guardar</button>

                    <button
                      type="submit"
                      name="delete_table"
                      class="btn-danger"
                      onclick="return confirm('Remover esta mesa?')"
                    >
                      Remover
                    </button>
                  </div>
                </form>

              </div>

            <?php endwhile; ?>
          <?php else: ?>
            <p>Nenhuma mesa encontrada.</p>
          <?php endif; ?>

        </div>
      </div>
    </section>

  </main>

</div>

</body>
</html>