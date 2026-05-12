<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/db.php";

/* PROTEÇÃO */
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "mesa") {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"] ?? "";
$msg = "";

/* DETETAR NÚMERO DA MESA PELO USERNAME: mesa1, mesa2, mesa24 */
preg_match('/([0-9]+)/', $username, $matches);
$mesaNumero = isset($matches[1]) ? intval($matches[1]) : 0;

if ($mesaNumero <= 0) {
    die("Erro: o username da mesa deve conter o número da mesa. Exemplo: mesa1.");
}

/* BUSCAR MESA */
$stmt = $conn->prepare("SELECT * FROM `tables` WHERE `number` = ? LIMIT 1");
$stmt->bind_param("i", $mesaNumero);
$stmt->execute();
$mesa = $stmt->get_result()->fetch_assoc();

if (!$mesa) {
    die("Erro: Mesa " . htmlspecialchars($mesaNumero) . " não existe.");
}

/* CARRINHO */
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

/* ADICIONAR ITEM */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_item"])) {
    $menuId = intval($_POST["menu_id"]);

    $stmt = $conn->prepare("SELECT * FROM menus WHERE id = ? AND status = 'disponivel'");
    $stmt->bind_param("i", $menuId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($item) {
        if (!isset($_SESSION["cart"][$menuId])) {
            $_SESSION["cart"][$menuId] = [
                "id" => $item["id"],
                "name" => $item["name"],
                "price" => (float)$item["price"],
                "quantity" => 1
            ];
        } else {
            $_SESSION["cart"][$menuId]["quantity"]++;
        }
    }

    header("Location: mesa_dashboard.php");
    exit;
}

/* REMOVER ITEM */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_item"])) {
    $menuId = intval($_POST["menu_id"]);

    if (isset($_SESSION["cart"][$menuId])) {
        $_SESSION["cart"][$menuId]["quantity"]--;

        if ($_SESSION["cart"][$menuId]["quantity"] <= 0) {
            unset($_SESSION["cart"][$menuId]);
        }
    }

    header("Location: mesa_dashboard.php");
    exit;
}

/* LIMPAR CONTA */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["clear_cart"])) {
    $_SESSION["cart"] = [];
    header("Location: mesa_dashboard.php");
    exit;
}

/* ENVIAR PEDIDO PARA BD */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_order"])) {
    if (empty($_SESSION["cart"])) {
        $msg = "A conta está vazia.";
    } else {
        $total = 0;

        foreach ($_SESSION["cart"] as $item) {
            $total += $item["price"] * $item["quantity"];
        }

        $stmt = $conn->prepare("
            INSERT INTO orders (table_number, total, status)
            VALUES (?, ?, 'enviado')
        ");
        $stmt->bind_param("id", $mesaNumero, $total);
        $stmt->execute();

        $orderId = $conn->insert_id;

        foreach ($_SESSION["cart"] as $item) {
            $subtotal = $item["price"] * $item["quantity"];

            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, menu_id, name, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iisddd",
                $orderId,
                $item["id"],
                $item["name"],
                $item["price"],
                $item["quantity"],
                $subtotal
            );

            $stmt->execute();
        }

        $_SESSION["cart"] = [];
        $msg = "Pedido enviado com sucesso.";
    }
}

/* ACABAMOS */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acabamos"])) {

    $update = $conn->prepare("
        UPDATE `tables`
        SET 
            status = 'livre',
            occupied_seats = 0,
            arrival_time = NULL
        WHERE number = ?
    ");

    $update->bind_param("i", $mesaNumero);

    if ($update->execute()) {
        $_SESSION["cart"] = [];
        header("Location: login.php");
        exit;
    } else {
        $msg = "Erro ao libertar a mesa.";
    }
}

/* BUSCAR MENU POR CATEGORIA */
$menus = $conn->query("
    SELECT *
    FROM menus
    WHERE status = 'disponivel'
    ORDER BY category ASC, name ASC
");

$menuPorCategoria = [
    "entrada" => [],
    "prato" => [],
    "bebida" => [],
    "sobremesa" => []
];

while ($item = $menus->fetch_assoc()) {
    if (isset($menuPorCategoria[$item["category"]])) {
        $menuPorCategoria[$item["category"]][] = $item;
    }
}

/* TOTAL */
$totalConta = 0;
foreach ($_SESSION["cart"] as $item) {
    $totalConta += $item["price"] * $item["quantity"];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>SmartTable - Mesa <?= htmlspecialchars($mesa["number"]) ?></title>
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
  padding:25px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  background:rgba(13,6,0,.8);
  border-bottom:1px solid rgba(201,168,76,.25);
}

.logo-area{
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

.logo-area span{
  font-size:11px;
  letter-spacing:4px;
  color:var(--gold-light);
}

.logo-area h1{
  font-size:30px;
}

.mesa-info{
  padding:13px 20px;
  border-radius:999px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(13,6,0,.65);
  color:var(--cream2);
}

.main{
  padding:35px;
  display:grid;
  grid-template-columns:1fr 390px;
  gap:30px;
}

.title{
  margin-bottom:25px;
}

.subtitle{
  color:var(--gold-light);
  letter-spacing:8px;
  font-size:13px;
  margin-bottom:8px;
}

.title h2{
  font-size:44px;
}

.panel{
  border:1px solid rgba(201,168,76,.28);
  border-radius:24px;
  background:linear-gradient(145deg,rgba(38,21,8,.78),rgba(13,6,0,.94));
  overflow:hidden;
  box-shadow:0 18px 55px rgba(0,0,0,.35);
}

.panel-header{
  padding:22px 26px;
  border-bottom:1px solid rgba(201,168,76,.16);
}

.panel-header h3{
  color:var(--gold-light);
  font-size:27px;
}

.category-tabs{
  display:flex;
  gap:14px;
  padding:22px 26px;
  border-bottom:1px solid rgba(201,168,76,.16);
  flex-wrap:wrap;
}

.category-tabs button{
  width:auto;
  padding:13px 20px;
  border-radius:999px;
  border:1px solid rgba(201,168,76,.25);
  background:rgba(201,168,76,.08);
  color:var(--gold-light);
  font-weight:bold;
  cursor:pointer;
}

.category-tabs button.active,
.category-tabs button:hover{
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
}

.menu-grid{
  padding:26px;
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:20px;
}

.category-section{
  display:none;
}

.category-section.active{
  display:grid;
}

.menu-item{
  padding:20px;
  border-radius:18px;
  border:1px solid rgba(201,168,76,.22);
  background:rgba(13,6,0,.45);
}

.category{
  display:inline-block;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(201,168,76,.13);
  border:1px solid rgba(201,168,76,.25);
  color:var(--gold-light);
  font-size:12px;
  text-transform:uppercase;
  margin-bottom:12px;
}

.menu-item h4{
  color:var(--gold-light);
  font-size:21px;
  margin-bottom:8px;
}

.menu-item p{
  color:rgba(245,234,216,.65);
  font-size:14px;
  line-height:1.4;
  margin-bottom:14px;
}

.price{
  font-size:22px;
  font-weight:bold;
  margin-bottom:14px;
}

button{
  width:100%;
  padding:13px;
  border:none;
  border-radius:13px;
  background:linear-gradient(135deg,var(--gold),var(--gold-light));
  color:#140900;
  font-weight:bold;
  cursor:pointer;
}

.cart{
  position:sticky;
  top:25px;
  height:max-content;
}

.cart-body{
  padding:22px;
}

.cart-item{
  padding:14px 0;
  border-bottom:1px solid rgba(201,168,76,.15);
}

.cart-item strong{
  color:var(--gold-light);
}

.cart-row{
  display:flex;
  justify-content:space-between;
  gap:12px;
  margin-top:8px;
  color:var(--cream2);
}

.cart-actions{
  display:flex;
  gap:8px;
  margin-top:10px;
}

.cart-actions form{
  flex:1;
}

.cart-actions button{
  padding:8px;
  font-size:13px;
}

.total{
  margin-top:20px;
  padding:18px;
  border-radius:16px;
  background:rgba(201,168,76,.12);
  border:1px solid rgba(201,168,76,.25);
  display:flex;
  justify-content:space-between;
  font-size:24px;
  font-weight:bold;
  color:var(--gold-light);
}

.cart-footer{
  padding:22px;
  display:flex;
  flex-direction:column;
  gap:12px;
}

.clear{
  background:rgba(239,68,68,.18);
  color:#fca5a5;
  border:1px solid rgba(239,68,68,.35);
}

.finish{
  margin-top:25px;
  padding:25px;
  border-radius:22px;
  border:1px solid rgba(239,68,68,.35);
  background:rgba(239,68,68,.08);
}

.finish h3{
  color:#fca5a5;
  margin-bottom:10px;
}

.finish p{
  color:var(--cream2);
  margin-bottom:18px;
}

.finish button{
  background:linear-gradient(135deg,#ef4444,#fca5a5);
}

.msg{
  padding:14px 18px;
  margin-bottom:20px;
  border-radius:14px;
  background:rgba(34,197,94,.12);
  color:#86efac;
}

.empty{
  color:rgba(245,234,216,.6);
  padding:20px 0;
}

@media(max-width:1100px){
  .main{
    grid-template-columns:1fr;
  }

  .menu-grid{
    grid-template-columns:repeat(2,1fr);
  }

  .cart{
    position:relative;
    top:0;
  }
}

@media(max-width:650px){
  .header{
    flex-direction:column;
    align-items:flex-start;
    gap:16px;
  }

  .main{
    padding:20px;
  }

  .menu-grid{
    grid-template-columns:1fr;
  }

  .title h2{
    font-size:34px;
  }
}
</style>
</head>

<body>

<header class="header">
  <div class="logo-area">
    <div class="logo">S</div>
    <div>
      <span>RESTAURANT MANAGEMENT</span>
      <h1>SmartTable</h1>
    </div>
  </div>

  <div class="mesa-info">
    Mesa <?= htmlspecialchars($mesa["number"]) ?> ·
    <?= htmlspecialchars($mesa["occupied_seats"]) ?>/<?= htmlspecialchars($mesa["capacity"]) ?> pessoas
  </div>
</header>

<main class="main">

  <section>
    <div class="title">
      <p class="subtitle">TABLET DA MESA</p>
      <h2>Escolha o seu pedido</h2>
    </div>

    <?php if ($msg !== ""): ?>
      <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-header">
        <h3>Menu Disponível</h3>
      </div>

      <div class="category-tabs">
        <button type="button" class="tab-btn active" onclick="showCategory('entrada', this)">Entradas</button>
        <button type="button" class="tab-btn" onclick="showCategory('prato', this)">Pratos</button>
        <button type="button" class="tab-btn" onclick="showCategory('bebida', this)">Bebidas</button>
        <button type="button" class="tab-btn" onclick="showCategory('sobremesa', this)">Sobremesas</button>
      </div>

      <?php foreach ($menuPorCategoria as $categoria => $items): ?>
        <div class="menu-grid category-section <?= $categoria === 'entrada' ? 'active' : '' ?>" id="cat-<?= $categoria ?>">

          <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>

              <div class="menu-item">
                <span class="category"><?= htmlspecialchars($item["category"]) ?></span>
                <h4><?= htmlspecialchars($item["name"]) ?></h4>
                <p><?= htmlspecialchars($item["description"]) ?></p>

                <div class="price">
                  <?= number_format((float)$item["price"], 2, ",", ".") ?> €
                </div>

                <form method="POST">
                  <input type="hidden" name="menu_id" value="<?= htmlspecialchars($item["id"]) ?>">
                  <button type="submit" name="add_item">Adicionar à conta</button>
                </form>
              </div>

            <?php endforeach; ?>
          <?php else: ?>

            <p class="empty">Nenhum item disponível nesta categoria.</p>

          <?php endif; ?>

        </div>
      <?php endforeach; ?>

    </section>
  </section>

  <aside class="cart">
    <section class="panel">
      <div class="panel-header">
        <h3>Conta da Mesa</h3>
      </div>

      <div class="cart-body">
        <?php if (!empty($_SESSION["cart"])): ?>

          <?php foreach($_SESSION["cart"] as $item): ?>
            <?php $subtotal = $item["price"] * $item["quantity"]; ?>

            <div class="cart-item">
              <strong><?= htmlspecialchars($item["name"]) ?></strong>

              <div class="cart-row">
                <span><?= $item["quantity"] ?> x <?= number_format($item["price"], 2, ",", ".") ?> €</span>
                <span><?= number_format($subtotal, 2, ",", ".") ?> €</span>
              </div>

              <div class="cart-actions">
                <form method="POST">
                  <input type="hidden" name="menu_id" value="<?= htmlspecialchars($item["id"]) ?>">
                  <button type="submit" name="add_item">+</button>
                </form>

                <form method="POST">
                  <input type="hidden" name="menu_id" value="<?= htmlspecialchars($item["id"]) ?>">
                  <button type="submit" name="remove_item">-</button>
                </form>
              </div>
            </div>

          <?php endforeach; ?>

          <div class="total">
            <span>Total</span>
            <span><?= number_format($totalConta, 2, ",", ".") ?> €</span>
          </div>

        <?php else: ?>

          <p class="empty">A conta ainda está vazia.</p>

        <?php endif; ?>
      </div>

      <div class="cart-footer">
        <form method="POST">
          <button type="submit" name="send_order">Enviar pedido</button>
        </form>

        <form method="POST">
          <button type="submit" name="clear_cart" class="clear">Limpar conta</button>
        </form>
      </div>
    </section>

    <section class="finish">
      <h3>Acabamos</h3>
      <p>Ao clicar, esta mesa volta a ficar livre.</p>

      <form method="POST" onsubmit="return confirm('Confirmar que acabaram a refeição?')">
        <button type="submit" name="acabamos">Acabamos</button>
      </form>
    </section>
  </aside>

</main>

<script>
function showCategory(category, button){
  document.querySelectorAll('.category-section').forEach(section => {
    section.classList.remove('active');
  });

  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('active');
  });

  document.getElementById('cat-' + category).classList.add('active');
  button.classList.add('active');
}
</script>

</body>
</html>