<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "garcom") {
    header("Location: login.php");
    exit;
}

$garcomName = $_SESSION["name"] ?? $_SESSION["username"] ?? "Garçom";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["finalizar_pedido"])) {
    $orderId = intval($_POST["order_id"] ?? 0);

    if ($orderId > 0) {
        $stmt = $conn->prepare("UPDATE orders SET status = 'finalizado' WHERE id = ?");
        $stmt->bind_param("i", $orderId);

        if ($stmt->execute()) {
            $movement = $conn->prepare("
                INSERT INTO order_movements (order_id, status, message)
                VALUES (?, 'finalizado', 'O garçom entregou/finalizou o pedido.')
            ");
            $movement->bind_param("i", $orderId);
            $movement->execute();

            $msg = "Pedido entregue com sucesso.";
        } else {
            $msg = "Erro ao finalizar pedido.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SmartTable - Garçom</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{
  --gold:#c9a84c;
  --gold-light:#e8c97a;
  --dark:#0d0600;
  --cream:#f5ead8;
  --cream2:#ede0c4;
}

*{margin:0;padding:0;box-sizing:border-box}

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

.header{
  padding:26px 42px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  background:rgba(13,6,0,.78);
  border-bottom:1px solid rgba(201,168,76,.25);
}

.brand{
  display:flex;
  align-items:center;
  gap:14px;
}

.logo{
  width:58px;
  height:58px;
  border-radius:16px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:28px;
  font-weight:bold;
}

.brand span{
  font-size:11px;
  letter-spacing:4px;
  color:var(--gold-light);
}

.brand h1{
  font-size:30px;
}

.session{
  display:flex;
  align-items:center;
  gap:14px;
}

.session-box{
  padding:13px 18px;
  border-radius:999px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.65);
  color:var(--cream2);
}

.logout{
  padding:13px 18px;
  border-radius:14px;
  text-decoration:none;
  color:#fca5a5;
  background:rgba(239,68,68,.10);
  border:1px solid rgba(239,68,68,.35);
  font-weight:bold;
}

.main{
  padding:38px;
}

.subtitle{
  color:var(--gold-light);
  letter-spacing:8px;
  font-size:13px;
  margin-bottom:8px;
}

.top h2{
  font-size:46px;
  line-height:1;
  margin-bottom:32px;
}

.msg{
  padding:14px 18px;
  margin-bottom:24px;
  border-radius:14px;
  background:rgba(34,197,94,.12);
  border:1px solid rgba(34,197,94,.35);
  color:#86efac;
}

.grid{
  display:grid;
  grid-template-columns:1fr 1.2fr;
  gap:28px;
}

.panel{
  border:1px solid rgba(201,168,76,.28);
  border-radius:24px;
  background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));
  box-shadow:0 18px 55px rgba(0,0,0,.35);
  overflow:hidden;
}

.panel-header{
  padding:22px 26px;
  border-bottom:1px solid rgba(201,168,76,.16);
}

.panel-header h3{
  color:var(--gold-light);
  font-size:27px;
}

.tables-grid{
  padding:24px;
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:16px;
}

.table-card{
  padding:18px;
  border-radius:18px;
  background:rgba(13,6,0,.45);
  border:1px solid rgba(201,168,76,.20);
}

.table-number{
  font-size:26px;
  color:var(--gold-light);
  font-weight:bold;
}

.table-info{
  margin-top:8px;
  color:var(--cream2);
  font-size:14px;
  line-height:1.6;
}

.status{
  display:inline-block;
  margin-top:12px;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  text-transform:uppercase;
  border:1px solid rgba(201,168,76,.25);
}

.status.livre{
  color:#86efac;
  background:rgba(34,197,94,.12);
  border-color:rgba(34,197,94,.35);
}

.status.ocupada{
  color:#fca5a5;
  background:rgba(239,68,68,.12);
  border-color:rgba(239,68,68,.35);
}

.status.reservada{
  color:#fbbf24;
  background:rgba(245,158,11,.12);
  border-color:rgba(245,158,11,.35);
}

.status.manutencao{
  color:#cbd5e1;
  background:rgba(100,116,139,.12);
  border-color:rgba(100,116,139,.35);
}

.status.enviado{
  color:#fbbf24;
  background:rgba(245,158,11,.12);
  border-color:rgba(245,158,11,.35);
}

.status.processando{
  color:#93c5fd;
  background:rgba(59,130,246,.12);
  border-color:rgba(59,130,246,.35);
}

.status.pronto{
  color:#86efac;
  background:rgba(34,197,94,.12);
  border-color:rgba(34,197,94,.35);
}

.orders{
  padding:24px;
  display:flex;
  flex-direction:column;
  gap:18px;
}

.order-card{
  padding:20px;
  border-radius:20px;
  border:1px solid rgba(201,168,76,.22);
  background:rgba(13,6,0,.48);
}

.order-top{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:14px;
}

.order-top h4{
  color:var(--gold-light);
  font-size:24px;
}

.order-meta{
  color:rgba(245,234,216,.6);
  font-size:13px;
  margin-bottom:14px;
}

.items{
  margin-top:12px;
  border-top:1px solid rgba(201,168,76,.14);
  padding-top:12px;
}

.item-row{
  display:flex;
  justify-content:space-between;
  gap:16px;
  color:var(--cream2);
  padding:8px 0;
  border-bottom:1px solid rgba(201,168,76,.08);
}

.item-row strong{
  color:var(--cream);
}

button{
  margin-top:16px;
  width:100%;
  padding:13px;
  border:none;
  border-radius:14px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  font-weight:bold;
  cursor:pointer;
}

button:disabled{
  opacity:.5;
  cursor:not-allowed;
}

.empty{
  padding:24px;
  color:rgba(245,234,216,.65);
}

@media(max-width:1150px){
  .grid{grid-template-columns:1fr}
  .tables-grid{grid-template-columns:repeat(3,1fr)}
}

@media(max-width:700px){
  .header{
    flex-direction:column;
    align-items:flex-start;
    gap:18px;
    padding:24px 18px;
  }

  .session{
    flex-direction:column;
    align-items:flex-start;
  }

  .main{
    padding:22px 18px;
  }

  .top h2{
    font-size:34px;
  }

  .tables-grid{
    grid-template-columns:1fr 1fr;
  }
}

@media(max-width:480px){
  .tables-grid{grid-template-columns:1fr}
}
</style>
</head>

<body>

<header class="header">
  <div class="brand">
    <div class="logo">S</div>
    <div>
      <span>RESTAURANT MANAGEMENT</span>
      <h1>SmartTable</h1>
    </div>
  </div>

  <div class="session">
    <div class="session-box">
      Garçom: <strong><?= htmlspecialchars($garcomName) ?></strong>
    </div>

    <a href="logout.php" class="logout">Terminar Sessão</a>
  </div>
</header>

<main class="main">

  <div class="top">
    <p class="subtitle">PAINEL DO GARÇOM</p>
    <h2>Mesas e pedidos ativos</h2>
  </div>

  <?php if ($msg !== ""): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="grid">

    <section class="panel">
      <div class="panel-header">
        <h3>Estado das Mesas</h3>
      </div>

      <div class="tables-grid" id="tablesContainer">
        <p class="empty">A carregar mesas...</p>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <h3>Pedidos Recebidos</h3>
      </div>

      <div class="orders" id="ordersContainer">
        <p class="empty">A carregar pedidos...</p>
      </div>
    </section>

  </div>

</main>

<script>
function escapeHtml(value){
  if (value === null || value === undefined) return "";
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function tempoSentado(arrivalTime){
  if (!arrivalTime) return "--";

  const chegada = new Date(String(arrivalTime).replace(" ", "T"));
  const agora = new Date();

  if (isNaN(chegada.getTime())) return "--";

  const diffMin = Math.max(0, Math.floor((agora - chegada) / 60000));

  if (diffMin < 60) return diffMin + "min";

  const h = Math.floor(diffMin / 60);
  const m = diffMin % 60;

  return h + "h " + m + "min";
}

function horaChegada(arrivalTime){
  if (!arrivalTime) return "--:--";
  return String(arrivalTime).substring(11, 16);
}

function carregarMesasGarcom(){
  fetch("api_tables.php", { cache: "no-store" })
    .then(response => response.json())
    .then(payload => {
      const container = document.getElementById("tablesContainer");

      if (!payload.success) {
        container.innerHTML = `<p class="empty">Erro API: ${escapeHtml(payload.error)}</p>`;
        return;
      }

      const tables = payload.data;

      if (!tables.length) {
        container.innerHTML = `<p class="empty">Nenhuma mesa encontrada.</p>`;
        return;
      }

      let html = "";

      tables.forEach(table => {
        html += `
          <div class="table-card">
            <div class="table-number">Mesa ${escapeHtml(table.number)}</div>

            <div class="table-info">
              Pessoas: ${escapeHtml(table.occupied_seats)}/${escapeHtml(table.capacity)}<br>
              Tipo: ${escapeHtml(table.type_table)}<br>
              Chegada: ${horaChegada(table.arrival_time)}<br>
              Tempo sentado: ${tempoSentado(table.arrival_time)}
            </div>

            <span class="status ${escapeHtml(table.status)}">
              ${escapeHtml(table.status)}
            </span>
          </div>
        `;
      });

      container.innerHTML = html;
    })
    .catch(error => {
      document.getElementById("tablesContainer").innerHTML =
        `<p class="empty">Erro ao carregar mesas. Verifica api_tables.php.</p>`;
      console.error(error);
    });
}

function carregarPedidosGarcom(){
  fetch("api_orders.php", { cache: "no-store" })
    .then(response => response.json())
    .then(payload => {
      const container = document.getElementById("ordersContainer");

      if (!payload.success) {
        container.innerHTML = `<p class="empty">Erro API: ${escapeHtml(payload.error)}</p>`;
        return;
      }

      const orders = payload.data;

      if (!orders.length) {
        container.innerHTML = `<p class="empty">Ainda não existem pedidos ativos.</p>`;
        return;
      }

      let html = "";

      orders.forEach(order => {
        html += `
          <div class="order-card">
            <div class="order-top">
              <h4>Mesa ${escapeHtml(order.table_number)}</h4>
              <span class="status ${escapeHtml(order.status)}">${escapeHtml(order.status)}</span>
            </div>

            <div class="order-meta">
              Pedido #${escapeHtml(order.id)} · ${escapeHtml(order.created_at)}
            </div>

            <div class="items">
              <strong style="color:var(--gold-light);display:block;margin-bottom:10px;">
                Itens do Pedido
              </strong>
        `;

        if (order.items && order.items.length > 0) {
          order.items.forEach(item => {
            html += `
              <div class="item-row">
                <span><strong>${escapeHtml(item.quantity)}x</strong> ${escapeHtml(item.name)}</span>
                <span>${parseFloat(item.subtotal || 0).toFixed(2)} €</span>
              </div>
            `;
          });
        } else {
          html += `
            <div class="item-row">
              <span>Sem itens.</span>
              <span>--</span>
            </div>
          `;
        }

        html += `
            </div>

            <div class="items">
              <strong style="color:var(--gold-light);display:block;margin:14px 0 10px;">
                Movimentos da Cozinha
              </strong>
        `;

        if (order.movements && order.movements.length > 0) {
          order.movements.forEach(mov => {
            html += `
              <div class="item-row">
                <span>${escapeHtml(mov.message)}</span>
                <span>${String(mov.created_at).substring(11,16)}</span>
              </div>
            `;
          });
        } else {
          html += `
            <div class="item-row">
              <span>Ainda sem movimentos.</span>
              <span>--:--</span>
            </div>
          `;
        }

        html += `
            </div>

            <div class="items">
              <div class="item-row">
                <strong>Total</strong>
                <strong>${parseFloat(order.total || 0).toFixed(2)} €</strong>
              </div>
            </div>
        `;

        if (order.status === "pronto") {
          html += `
            <form method="POST" onsubmit="return confirm('Confirmar entrega do pedido?')">
              <input type="hidden" name="order_id" value="${escapeHtml(order.id)}">
              <button type="submit" name="finalizar_pedido">Pedido entregue</button>
            </form>
          `;
        } else {
          html += `<button disabled>Aguardar cozinha</button>`;
        }

        html += `</div>`;
      });

      container.innerHTML = html;
    })
    .catch(error => {
      document.getElementById("ordersContainer").innerHTML =
        `<p class="empty">Erro ao carregar pedidos. Verifica api_orders.php.</p>`;
      console.error(error);
    });
}

carregarMesasGarcom();
carregarPedidosGarcom();

setInterval(carregarMesasGarcom, 3000);
setInterval(carregarPedidosGarcom, 3000);
</script>

</body>
</html>