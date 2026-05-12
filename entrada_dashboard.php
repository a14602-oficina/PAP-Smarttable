<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config/db.php";

$msg = "";

function minimoPessoas($capacity) {
    if ($capacity == 4) return 3;
    if ($capacity == 6) return 5;
    if ($capacity == 8) return 7;
    return 1;
}

/* Escolher mesa */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mesa_id = intval($_POST["mesa_id"] ?? 0);
    $pessoas = intval($_POST["pessoas"] ?? 0);

    if ($mesa_id <= 0 || $pessoas <= 0) {
        $msg = "Dados inválidos.";
    } else {
        $stmt = $conn->prepare("SELECT id, capacity, status FROM `tables` WHERE id = ?");
        $stmt->bind_param("i", $mesa_id);
        $stmt->execute();
        $mesa = $stmt->get_result()->fetch_assoc();

        if (!$mesa) {
            $msg = "Mesa não encontrada.";
        } elseif ($mesa["status"] !== "livre") {
            $msg = "Esta mesa já não está livre.";
        } else {
            $capacity = intval($mesa["capacity"]);
            $min = minimoPessoas($capacity);

            if ($pessoas < $min) {
                $msg = "Esta mesa só pode ser escolhida com pelo menos $min pessoas.";
            } elseif ($pessoas > $capacity) {
                $msg = "Número de pessoas superior à capacidade da mesa.";
            } else {
                $update = $conn->prepare("
                    UPDATE `tables`
                    SET occupied_seats = ?,
                        status = 'ocupada',
                        arrival_time = NOW()
                    WHERE id = ?
                ");

                $update->bind_param("ii", $pessoas, $mesa_id);

                if ($update->execute()) {
                    header("Location: entrada_dashboard.php?msg=Mesa escolhida com sucesso");
                    exit;
                } else {
                    $msg = "Erro ao atualizar mesa.";
                }
            }
        }
    }
}

if (isset($_GET["msg"])) {
    $msg = htmlspecialchars($_GET["msg"]);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SmartTable - Entrada</title>
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

.header{
  padding:28px 42px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid rgba(201,168,76,.18);
  background:rgba(13,6,0,.72);
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
  line-height:1;
}

.main{
  padding:42px;
}

.top{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:24px;
  margin-bottom:34px;
}

.subtitle{
  color:var(--gold-light);
  letter-spacing:8px;
  font-size:13px;
  margin-bottom:8px;
}

.top h2{
  font-size:48px;
  line-height:1;
}

.info-card{
  max-width:440px;
  padding:20px;
  border-radius:20px;
  border:1px solid rgba(201,168,76,.24);
  background:linear-gradient(145deg,rgba(38,21,8,.72),rgba(13,6,0,.9));
  color:var(--cream2);
  line-height:1.5;
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
  border-radius:24px;
  background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));
  box-shadow:0 18px 55px rgba(0,0,0,.35);
  overflow:hidden;
}

.panel-header{
  padding:24px 28px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid rgba(201,168,76,.16);
}

.panel-header h3{
  color:var(--gold-light);
  font-size:28px;
}

.legend{
  display:flex;
  gap:16px;
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

.room{
  margin:28px;
  padding:34px;
  border-radius:22px;
  border:1px solid rgba(201,168,76,.22);
  background:
    radial-gradient(circle at 15% 20%,rgba(201,168,76,.10),transparent 22%),
    linear-gradient(145deg,rgba(13,6,0,.65),rgba(38,21,8,.55));
  min-height:600px;
}

.room-title{
  color:rgba(245,234,216,.6);
  font-size:14px;
  margin-bottom:28px;
  letter-spacing:3px;
}

.tables-grid{
  display:grid;
  grid-template-columns:repeat(8,1fr);
  gap:34px 28px;
}

.table-card{
  min-height:230px;
  padding:15px;
  border-radius:20px;
  border:1px solid rgba(201,168,76,.16);
  background:rgba(13,6,0,.35);
  display:flex;
  flex-direction:column;
  align-items:center;
}

.table-card.disabled{
  opacity:.48;
}

.table-visual{
  height:115px;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  margin-bottom:12px;
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

.table-card.redonda .table-shape{border-radius:50%}
.table-card.quadrada .table-shape{border-radius:12px}
.table-card.retangular .table-shape{width:105px;border-radius:14px}

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

.reserve-form{
  width:100%;
  display:flex;
  flex-direction:column;
  gap:8px;
}

.reserve-form label{
  font-size:12px;
  color:var(--cream2);
}

.reserve-form input{
  width:100%;
  padding:10px;
  border-radius:10px;
  border:1px solid rgba(201,168,76,.22);
  background:rgba(13,6,0,.75);
  color:var(--cream);
  outline:none;
}

.reserve-form small{
  color:rgba(245,234,216,.55);
  font-size:11px;
}

.reserve-form button{
  padding:11px;
  border:none;
  border-radius:11px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  font-weight:bold;
  cursor:pointer;
}

.status-text{
  text-align:center;
  font-size:13px;
  color:rgba(245,234,216,.65);
  margin-top:10px;
}

.call-manager{
  margin-top:28px;
  padding:22px;
  border-radius:20px;
  border:1px solid rgba(239,68,68,.35);
  background:rgba(239,68,68,.08);
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:20px;
}

.call-manager h4{
  color:#fca5a5;
  font-size:20px;
}

.call-manager p{
  color:var(--cream2);
  margin-top:6px;
}

.call-manager button{
  padding:13px 18px;
  border-radius:14px;
  border:1px solid rgba(239,68,68,.35);
  background:rgba(239,68,68,.18);
  color:#fca5a5;
  font-weight:bold;
  cursor:pointer;
}

.empty{
  color:rgba(245,234,216,.65);
  padding:20px;
}

@media(max-width:1400px){
  .tables-grid{grid-template-columns:repeat(6,1fr)}
}

@media(max-width:1100px){
  .tables-grid{grid-template-columns:repeat(4,1fr)}
}

@media(max-width:900px){
  .header,.main{padding:24px 18px}
  .top{flex-direction:column}
  .top h2{font-size:36px}
  .tables-grid{grid-template-columns:repeat(2,1fr)}
  .panel-header{flex-direction:column;align-items:flex-start;gap:14px}
}

@media(max-width:600px){
  .tables-grid{grid-template-columns:1fr}
  .room{margin:16px;padding:22px}
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
</header>

<main class="main">

  <div class="top">
    <div>
      <p class="subtitle">TABLET DE ENTRADA</p>
      <h2>Selecione a sua mesa</h2>
    </div>

    <div class="info-card">
      Escolha uma mesa livre, indique o número de pessoas e confirme.
      Mesas maiores exigem número mínimo de pessoas.
    </div>
  </div>

  <?php if ($msg !== ""): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <section class="panel">
    <div class="panel-header">
      <h3>Mapa da Sala</h3>

      <div class="legend">
        <span><i class="dot livre"></i> Livre</span>
        <span><i class="dot ocupada"></i> Ocupada</span>
        <span><i class="dot reservada"></i> Reservada</span>
        <span><i class="dot manutencao"></i> Manutenção</span>
      </div>
    </div>

    <div class="room">
      <p class="room-title">SALA PRINCIPAL · MESAS DISPONÍVEIS</p>

      <div class="tables-grid" id="tablesContainer">
        <p class="empty">A carregar mesas...</p>
      </div>

      <div class="call-manager">
        <div>
          <h4>Não encontrou mesa disponível?</h4>
          <p>Chame o gerente para organizar uma alternativa.</p>
        </div>

        <button onclick="alert('Gerente chamado com sucesso!')">Chamar Gerente</button>
      </div>

    </div>
  </section>

</main>

<script>
function minPessoas(capacity){
  capacity = parseInt(capacity);
  if (capacity === 4) return 3;
  if (capacity === 6) return 5;
  if (capacity === 8) return 7;
  return 1;
}

function escapeHtml(value){
  if (value === null || value === undefined) return "";
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function carregarMesasEntrada(){
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

      tables.forEach(mesa => {
        const livre = mesa.status === "livre";
        const min = minPessoas(mesa.capacity);
        const chairs = Math.min(parseInt(mesa.capacity), 8);

        let cadeiras = "";

        for (let i = 1; i <= chairs; i++) {
          cadeiras += `<span class="chair c${i}"></span>`;
        }

        html += `
          <div class="table-card ${escapeHtml(mesa.status)} ${escapeHtml(mesa.type_table)} ${!livre ? "disabled" : ""}">
            <div class="table-visual">
              <div class="table-shape">
                <span class="status-ring"></span>
                ${cadeiras}
                <span class="table-number">${escapeHtml(mesa.number)}</span>
                <span class="table-seats">${escapeHtml(mesa.occupied_seats)}/${escapeHtml(mesa.capacity)} ocupados</span>
              </div>
            </div>

            ${
              livre
              ? `
                <form class="reserve-form" method="POST">
                  <input type="hidden" name="mesa_id" value="${escapeHtml(mesa.id)}">

                  <label>Número de pessoas</label>
                  <input type="number" name="pessoas" min="${min}" max="${escapeHtml(mesa.capacity)}" required>

                  <small>Mínimo: ${min} · Máximo: ${escapeHtml(mesa.capacity)}</small>

                  <button type="submit">Escolher Mesa</button>
                </form>
              `
              : `<div class="status-text">Estado: ${escapeHtml(mesa.status)}</div>`
            }
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

carregarMesasEntrada();
setInterval(carregarMesasEntrada, 3000);
</script>

</body>
</html>