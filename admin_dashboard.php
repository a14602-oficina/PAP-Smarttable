<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["name"] ?? "Administrador";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>SmartTable - Administração</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="assets/style.css">

  <style>
    .admin-layout {
      width: 100%;
      min-height: 100vh;
      display: flex;
      background:
        linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px),
        linear-gradient(rgba(201,168,76,.03) 1px, transparent 1px),
        radial-gradient(circle at top left, rgba(201,168,76,.18), transparent 35%),
        linear-gradient(135deg, var(--dark), #080300 70%);
      background-size: 80px 80px, 80px 80px, cover, cover;
      color: var(--cream);
    }

    .sidebar {
      width: 285px;
      padding: 32px 24px;
      border-right: 1px solid rgba(201,168,76,.25);
      background: linear-gradient(180deg, rgba(38,21,8,.95), rgba(13,6,0,.98));
      box-shadow: 12px 0 45px rgba(0,0,0,.35);
    }

    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 42px;
    }

    .sidebar-logo .logo-box {
      width: 56px;
      height: 56px;
      border-radius: 18px;
      font-size: 28px;
    }

    .sidebar-logo h2 {
      color: var(--gold-light);
      font-size: 24px;
      line-height: 1;
    }

    .sidebar-logo span {
      font-size: 12px;
      color: rgba(245,234,216,.55);
      letter-spacing: 2px;
    }

    .menu-title {
      font-size: 12px;
      color: rgba(245,234,216,.45);
      letter-spacing: 3px;
      margin-bottom: 16px;
    }

    .side-menu {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .side-menu a {
      padding: 15px 16px;
      border-radius: 16px;
      color: var(--cream2);
      text-decoration: none;
      background: rgba(201,168,76,.06);
      border: 1px solid rgba(201,168,76,.12);
      transition: .25s ease;
    }

    .side-menu a:hover,
    .side-menu a.active {
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      color: #140900;
      transform: translateX(4px);
      box-shadow: 0 10px 30px rgba(201,168,76,.25);
    }

    .logout {
      margin-top: 40px;
      display: block;
      padding: 14px;
      border-radius: 14px;
      text-align: center;
      color: #fca5a5;
      text-decoration: none;
      border: 1px solid rgba(239,68,68,.35);
      background: rgba(239,68,68,.08);
    }

    .content {
      flex: 1;
      padding: 42px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 36px;
    }

    .topbar h1 {
      font-size: 42px;
    }

    .user-box {
      padding: 14px 18px;
      border-radius: 18px;
      border: 1px solid rgba(201,168,76,.25);
      background: rgba(13,6,0,.65);
      color: var(--cream2);
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    .dash-card {
      padding: 28px;
      border-radius: 28px;
      border: 1px solid rgba(201,168,76,.32);
      background: linear-gradient(145deg, rgba(38,21,8,.88), rgba(13,6,0,.94));
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      min-height: 190px;
    }

    .dash-card h3 {
      color: var(--gold-light);
      font-size: 25px;
      margin-bottom: 12px;
    }

    .dash-card p {
      color: rgba(245,234,216,.62);
      line-height: 1.5;
      margin-bottom: 24px;
    }

    .dash-card a {
      display: inline-block;
      padding: 12px 18px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      color: #140900;
      text-decoration: none;
      font-weight: bold;
    }

    @media (max-width: 900px) {
      .admin-layout {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid rgba(201,168,76,.25);
      }

      .dashboard-grid {
        grid-template-columns: 1fr;
      }

      .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }
    }
  </style>
</head>

<body>

<div class="admin-layout">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-box">S</div>
      <div>
        <h2>SmartTable</h2>
        <span>ADMIN PANEL</span>
      </div>
    </div>

    <p class="menu-title">MENU</p>

    <nav class="side-menu">
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
        <h1>Painel Administrativo</h1>
      </div>

      <div class="user-box">
        Sessão iniciada como: <strong><?= htmlspecialchars($name) ?></strong>
      </div>
    </div>

    <section class="dashboard-grid">

      <div class="dash-card">
        <h3>Gestão de Mesas</h3>
        <p>Adicionar, editar e controlar mesas, lugares e estados de ocupação.</p>
        <a href="gestao_mesas.php">Abrir</a>
      </div>

      <div class="dash-card">
        <h3>Gestão de Utilizadores</h3>
        <p>Criar e administrar contas para entrada, mesa, garçom, cozinha e admin.</p>
        <a href="gestao_utilizadores.php">Abrir</a>
      </div>

      <div class="dash-card">
        <h3>Gestão de Menus</h3>
        <p>Gerir pratos, bebidas, preços, categorias e disponibilidade.</p>
        <a href="gestao_menus.php">Abrir</a>
      </div>

    </section>

  </main>

</div>

</body>
</html>