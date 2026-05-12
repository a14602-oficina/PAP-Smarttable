<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "cozinha") {
    header("Location: login.php");
    exit;
}

$cozinhaName = $_SESSION["name"] ?? $_SESSION["username"] ?? "Cozinha";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["processar"])) {
    $orderId = intval($_POST["order_id"] ?? 0);
    if ($orderId > 0) {
        $stmt = $conn->prepare("UPDATE orders SET status = 'processando' WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        if ($stmt->execute()) {
            $movement = $conn->prepare("INSERT INTO order_movements (order_id, status, message) VALUES (?, 'processando', 'A cozinha começou a preparar o pedido.')");
            $movement->bind_param("i", $orderId);
            $movement->execute();
            $msg = "Pedido marcado como a ser processado.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pronto"])) {
    $orderId = intval($_POST["order_id"] ?? 0);
    if ($orderId > 0) {
        $stmt = $conn->prepare("UPDATE orders SET status = 'pronto' WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        if ($stmt->execute()) {
            $movement = $conn->prepare("INSERT INTO order_movements (order_id, status, message) VALUES (?, 'pronto', 'A cozinha finalizou o pedido.')");
            $movement->bind_param("i", $orderId);
            $movement->execute();
            $msg = "Pedido marcado como prato finalizado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SmartTable - Cozinha</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{--gold:#c9a84c;--gold-light:#e8c97a;--dark:#0d0600;--cream:#f5ead8;--cream2:#ede0c4}*{margin:0;padding:0;box-sizing:border-box}body{min-height:100vh;font-family:Georgia,"Times New Roman",serif;background:linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(rgba(201,168,76,.03) 1px,transparent 1px),radial-gradient(circle at top left,rgba(201,168,76,.18),transparent 35%),linear-gradient(135deg,var(--dark),#080300 70%);background-size:80px 80px,80px 80px,cover,cover;color:var(--cream)}.header{padding:26px 42px;display:flex;justify-content:space-between;align-items:center;background:rgba(13,6,0,.78);border-bottom:1px solid rgba(201,168,76,.25)}.brand{display:flex;align-items:center;gap:14px}.logo{width:58px;height:58px;border-radius:16px;background:linear-gradient(135deg,var(--gold),var(--gold-light));color:#140900;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:bold}.brand span{font-size:11px;letter-spacing:4px;color:var(--gold-light)}.brand h1{font-size:30px}.session{display:flex;align-items:center;gap:14px}.session-box{padding:13px 18px;border-radius:999px;border:1px solid rgba(201,168,76,.25);background:rgba(13,6,0,.65);color:var(--cream2)}.logout{padding:13px 18px;border-radius:14px;text-decoration:none;color:#fca5a5;background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.35);font-weight:bold}.main{padding:38px}.subtitle{color:var(--gold-light);letter-spacing:8px;font-size:13px;margin-bottom:8px}.top h2{font-size:46px;margin-bottom:32px}.msg{padding:14px 18px;margin-bottom:24px;border-radius:14px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);color:#86efac}.orders-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}.order-card{border:1px solid rgba(201,168,76,.28);border-radius:24px;background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));box-shadow:0 18px 55px rgba(0,0,0,.35);overflow:hidden}.order-header{padding:22px 24px;border-bottom:1px solid rgba(201,168,76,.16);display:flex;justify-content:space-between;align-items:center}.order-header h3{color:var(--gold-light);font-size:28px}.badge{padding:8px 12px;border-radius:999px;text-transform:uppercase;font-size:12px;font-weight:bold}.badge.enviado{color:#fbbf24;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35)}.badge.processando{color:#93c5fd;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.35)}.order-body{padding:22px 24px}.meta{color:rgba(245,234,216,.6);font-size:14px;margin-bottom:18px}.item-row{display:flex;justify-content:space-between;gap:18px;padding:12px 0;border-bottom:1px solid rgba(201,168,76,.10);color:var(--cream2)}.item-row strong{color:var(--cream)}.total{margin-top:18px;padding:16px;border-radius:16px;background:rgba(201,168,76,.12);border:1px solid rgba(201,168,76,.25);display:flex;justify-content:space-between;color:var(--gold-light);font-size:22px;font-weight:bold}.actions{padding:22px 24px;display:grid;gap:12px}button{width:100%;padding:14px;border:none;border-radius:14px;background:linear-gradient(135deg,var(--gold),var(--gold-light));color:#140900;font-weight:bold;cursor:pointer}.btn-ready{background:linear-gradient(135deg,#22c55e,#86efac)}.empty{padding:28px;border:1px solid rgba(201,168,76,.28);border-radius:24px;background:rgba(13,6,0,.45);color:rgba(245,234,216,.65)}@media(max-width:1200px){.orders-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:750px){.header{flex-direction:column;align-items:flex-start;gap:18px;padding:24px 18px}.main{padding:22px 18px}.orders-grid{grid-template-columns:1fr}.top h2{font-size:34px}}
</style>
</head>
<body>
<header class="header"><div class="brand"><div class="logo">S</div><div><span>RESTAURANT MANAGEMENT</span><h1>SmartTable</h1></div></div><div class="session"><div class="session-box">Cozinha: <strong><?= htmlspecialchars($cozinhaName) ?></strong></div><a href="logout.php" class="logout">Terminar Sessão</a></div></header>
<main class="main"><div class="top"><p class="subtitle">PAINEL DA COZINHA</p><h2>Pedidos recebidos</h2></div><?php if($msg!==""):?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif;?><section class="orders-grid" id="kitchenOrders"></section></main>
<script>
function carregarPedidosCozinha(){fetch('api_orders.php').then(r=>r.json()).then(orders=>{const c=document.getElementById('kitchenOrders');const kitchenOrders=orders.filter(o=>o.status==='enviado'||o.status==='processando');if(!kitchenOrders.length){c.innerHTML='<div class="empty">Ainda não existem pedidos enviados para a cozinha.</div>';return;}let html='';kitchenOrders.forEach(order=>{html+=`<article class="order-card"><div class="order-header"><h3>Mesa ${order.table_number}</h3><span class="badge ${order.status}">${order.status}</span></div><div class="order-body"><div class="meta">Pedido #${order.id} · ${order.created_at}</div>`;order.items.forEach(item=>{html+=`<div class="item-row"><span><strong>${item.quantity}x</strong> ${item.name}</span><span>${parseFloat(item.subtotal).toFixed(2)} €</span></div>`;});html+=`<div class="total"><span>Total</span><span>${parseFloat(order.total).toFixed(2)} €</span></div></div><div class="actions">`;if(order.status==='enviado'){html+=`<form method="POST"><input type="hidden" name="order_id" value="${order.id}"><button type="submit" name="processar">A ser processado</button></form>`;}html+=`<form method="POST" onsubmit="return confirm('Marcar este pedido como finalizado?')"><input type="hidden" name="order_id" value="${order.id}"><button type="submit" name="pronto" class="btn-ready">Prato finalizado</button></form></div></article>`;});c.innerHTML=html;}).catch(e=>console.error('Erro cozinha:',e));}
carregarPedidosCozinha(); setInterval(carregarPedidosCozinha,3000);
</script>
</body>
</html>
