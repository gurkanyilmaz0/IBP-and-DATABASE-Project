<?php
// ============================================================
// PROJECTS.PHP — Proje Portföyü
// dept_id dolu  → departmana ait proje (bütçe + satış fiyatı)
// customer_id dolu → müşteriye ait proje (müşteri + tedarikçi)
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

// Sıralama parametresi
$sort = $_GET['sort'] ?? '';
$orderBy = match($sort) {
    'start_asc'   => 'pr.start_date ASC',
    'start_desc'  => 'pr.start_date DESC',
    'budget_desc' => 'pr.budget DESC',
    'profit_desc' => '(pr.sale_price - pr.budget) DESC',
    default       => 'pr.start_date DESC',
};

$personnelList = $conn->query("SELECT id, f_name, l_name FROM personnel_ ORDER BY f_name")->fetch_all(MYSQLI_ASSOC);

$projects = $conn->query("
    SELECT
        pr.id,
        pr.name,
        pr.start_date,
        pr.end_date,
        pr.budget,
        pr.sale_price,
        pr.description,
        COALESCE(c.name, c2.name)  AS company_name,
        d.name  AS dept_name,
        d.phn_ext AS dept_ext,
        cust.id                                      AS customer_id,
        CONCAT(cust.first_name, ' ', cust.last_name) AS customer_name,
        cust.phn_nmbr                                AS customer_phone,
        cust.email                                   AS customer_email,
        CONCAT(sup.first_name,  ' ', sup.last_name)  AS supplier_name,
        sup.email                                    AS supplier_email,
        sup.phn_nmbr                                 AS supplier_phone,
        sup.address                                  AS supplier_address,
        (
            SELECT GROUP_CONCAT(CONCAT(p_sub.f_name, ' ', p_sub.l_name) SEPARATOR ', ')
            FROM personnel_ p_sub
            WHERE p_sub.dept_id = p.dept_id
        ) AS team_names,
        (
            SELECT GROUP_CONCAT(p_sub.id SEPARATOR ',')
            FROM personnel_ p_sub
            WHERE p_sub.dept_id = p.dept_id
        ) AS team_ids
    FROM project_ pr
    LEFT JOIN departments_   d    ON pr.dept_id     = d.id
    LEFT JOIN company_       c    ON d.cmpny_id     = c.id
    LEFT JOIN customer_      cust ON pr.customer_id = cust.id
    LEFT JOIN supplier_      sup  ON cust.splr_id   = sup.id
    LEFT JOIN company_       c2   ON sup.cmpny_id   = c2.id
    LEFT JOIN personnel_     p    ON pr.prsnl_id    = p.id
    ORDER BY $orderBy
")->fetch_all(MYSQLI_ASSOC);

$total      = count($projects);
$hasDept    = count(array_filter($projects, fn($p) => $p['dept_name']));
$hasCust    = $total - $hasDept;
$topSale    = array_sum(array_column($projects, 'sale_price'));
$totalBudget = array_sum(array_column($projects, 'budget'));
$totalProfit = $topSale - $totalBudget;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanguard Logic — Projeler</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Share+Tech+Mono&display=swap');

  :root {
    --bg:      #0a0e14;
    --panel:   #111820;
    --border:  #1e3a4a;
    --accent:  #00d4ff;
    --text:    #c8d8e8;
    --muted:   #4a6070;
    --success: #00ff88;
    --warn:    #ffaa00;
    --error:   #ff4060;
    --purple:  #a855f7;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); font-family: 'Rajdhani', sans-serif; color: var(--text); min-height: 100vh; }
  body::before {
    content: ''; position: fixed; inset: 0;
    background-image: linear-gradient(rgba(0,212,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(0,212,255,0.02) 1px, transparent 1px);
    background-size: 40px 40px; pointer-events: none; z-index: 0;
  }

  nav {
    background: var(--panel); border-bottom: 1px solid var(--border);
    padding: 0 32px; display: flex; align-items: center; justify-content: space-between;
    height: 60px; position: sticky; top: 0; z-index: 100;
  }
  .nav-brand { font-size: 18px; font-weight: 700; letter-spacing: 3px; color: #fff; }
  .nav-brand span { color: var(--accent); }
  .nav-links { display: flex; gap: 4px; flex-wrap: wrap; }
  .nav-links a {
    color: var(--muted); text-decoration: none; font-size: 12px;
    letter-spacing: 1px; padding: 6px 12px; border: 1px solid transparent;
    transition: all .2s; text-transform: uppercase;
  }
  .nav-links a:hover, .nav-links a.active { color: var(--accent); border-color: var(--border); }
  .nav-sep { color: var(--border); align-self: center; font-size: 16px; padding: 0 4px; }
  .nav-user { display: flex; align-items: center; gap: 16px; font-family: 'Share Tech Mono', monospace; font-size: 12px; color: var(--muted); }
  .nav-user a { color: var(--error); text-decoration: none; font-size: 11px; letter-spacing: 1px; border: 1px solid var(--error); padding: 4px 10px; transition: all .2s; }
  .nav-user a:hover { background: var(--error); color: var(--bg); }

  main { padding: 32px; position: relative; z-index: 1; }
  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
  h2 { font-size: 24px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
  h2 span { color: var(--accent); }

  /* STATS */
  .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 28px; }
  .stat-box { background: var(--panel); border: 1px solid var(--border); padding: 18px 20px; position: relative; overflow: hidden; }
  .stat-box::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; }
  .s-blue::after   { background: var(--accent); }
  .s-warn::after   { background: var(--warn); }
  .s-green::after  { background: var(--success); }
  .s-purple::after { background: var(--purple); }
  .s-profit::after { background: linear-gradient(90deg, var(--success), var(--accent)); }
  .stat-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; font-family: 'Share Tech Mono', monospace; }
  .stat-val { font-size: 20px; font-weight: 700; font-family: 'Share Tech Mono', monospace; }
  .c-blue { color: var(--accent); } .c-warn { color: var(--warn); } .c-green { color: var(--success); } .c-purple { color: var(--purple); }
  .c-profit { color: var(--success); }

  /* KONTROLLER */
  .controls-bar { display: flex; gap: 16px; margin-bottom: 24px; align-items: flex-end; flex-wrap: wrap; }

  .search-box { flex: 1; min-width: 260px; max-width: 400px; display: flex; flex-direction: column; gap: 6px; }
  .search-box label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); font-family: 'Share Tech Mono', monospace; }
  .search-input-wrap { position: relative; }
  .search-input-wrap input {
    width: 100%; background: var(--panel); border: 1px solid var(--border);
    color: var(--text); font-family: 'Share Tech Mono', monospace; font-size: 13px;
    padding: 10px 14px 10px 38px; outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .search-input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,212,255,0.08); }
  .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; pointer-events: none; }
  .search-clear { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); background: none; border: none; cursor: pointer; font-size: 16px; line-height: 1; display: none; padding: 2px 6px; transition: color .2s; }
  .search-clear:hover { color: var(--error); }
  .search-clear.visible { display: block; }
  #searchCount { font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted); margin-top: 4px; min-height: 16px; }
  #searchCount.has-results { color: var(--accent); }
  #searchCount.no-results  { color: var(--error); }

  /* SORT + FİLTRE */
  .controls-right { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
  .sort-group { display: flex; flex-direction: column; gap: 6px; }
  .sort-group label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); font-family: 'Share Tech Mono', monospace; }
  .sort-group select {
    background: var(--panel); border: 1px solid var(--border); color: var(--text);
    font-family: 'Share Tech Mono', monospace; font-size: 12px;
    padding: 10px 14px; outline: none; transition: border-color .2s; min-width: 220px;
  }
  .sort-group select:focus { border-color: var(--accent); }

  .filter-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
  .filter-btn {
    padding: 10px 18px; font-family: 'Rajdhani', sans-serif; font-size: 12px;
    font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
    border: 1px solid var(--border); color: var(--muted);
    background: transparent; cursor: pointer; transition: all .2s;
  }
  .filter-btn:hover, .filter-btn.active { border-color: var(--accent); color: var(--accent); }

  /* ALERT */
  .alert { padding: 12px 16px; margin-bottom: 20px; font-size: 14px; border: 1px solid; }
  .alert-success { border-color: var(--success); color: var(--success); background: rgba(0,255,136,0.05); }
  .alert-error   { border-color: var(--error);   color: var(--error);   background: rgba(255,64,96,0.05); }

  .no-results-msg {
    display: none; text-align: center; padding: 60px 20px;
    font-family: 'Share Tech Mono', monospace; color: var(--muted); font-size: 13px; grid-column: 1 / -1;
  }
  .no-results-msg .nr-icon { font-size: 32px; margin-bottom: 12px; display: block; }

  /* KARTLAR */
  .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
  .card {
    background: var(--panel); border: 1px solid var(--border);
    display: flex; flex-direction: column; transition: border-color .2s, transform .2s; overflow: hidden;
  }
  .card:hover { border-color: var(--accent); transform: translateY(-2px); }
  .card-stripe { height: 3px; }
  .card[data-type="dept"] .card-stripe { background: linear-gradient(90deg, var(--warn), transparent); }
  .card[data-type="cust"] .card-stripe { background: linear-gradient(90deg, var(--success), transparent); }
  .card-body { padding: 22px 24px; flex: 1; display: flex; flex-direction: column; gap: 14px; }
  .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
  .card-title { font-size: 18px; font-weight: 700; letter-spacing: 1px; }
  .card-sub { font-family: 'Share Tech Mono', monospace; font-size: 10px; color: var(--muted); margin-top: 3px; }
  .badge {
    display: inline-flex; align-items: center; padding: 5px 12px;
    font-size: 10px; font-family: 'Share Tech Mono', monospace;
    letter-spacing: 2px; border: 1px solid; white-space: nowrap; flex-shrink: 0;
  }
  .badge-dept { border-color: var(--warn);    color: var(--warn);    background: rgba(255,170,0,0.08); }
  .badge-cust { border-color: var(--success); color: var(--success); background: rgba(0,255,136,0.08); }
  .badge-sold { border-color: #00ff88; color: #00ff88; background: rgba(0,255,136,0.15); font-weight:700; }
  .badge-open { border-color: var(--warn); color: var(--warn); background: rgba(255,170,0,0.15); font-weight:700; }
  .card-desc { font-size: 13px; color: var(--muted); line-height: 1.6; }
  .card-meta { display: flex; flex-wrap: wrap; gap: 8px; }
  .meta-tag { font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted); background: var(--bg); border: 1px solid var(--border); padding: 4px 10px; }
  .meta-tag span { color: var(--text); }
  hr { border: none; border-top: 1px solid var(--border); }
  .card-actions { display: flex; justify-content: flex-end; padding: 0 24px 18px; }
  .edit-btn {
    font-family: 'Share Tech Mono', monospace; font-size: 11px; letter-spacing: 1px;
    padding: 6px 16px; border: 1px solid var(--border); color: var(--muted);
    text-decoration: none; background: transparent; transition: all .2s; cursor: pointer;
  }
  .edit-btn:hover { border-color: var(--warn); color: var(--warn); background: rgba(255,170,0,0.05); }
  .del-btn {
    font-family: 'Share Tech Mono', monospace; font-size: 11px; letter-spacing: 1px;
    padding: 6px 16px; border: 1px solid var(--error); color: var(--error);
    text-decoration: none; background: transparent; transition: all .2s; cursor: pointer;
    margin-left: 8px;
  }
  .del-btn:hover { background: var(--error); color: var(--bg); }
  .view-btn {
    font-family: 'Share Tech Mono', monospace; font-size: 11px; letter-spacing: 1px;
    padding: 6px 16px; border: 1px solid var(--accent); color: var(--accent);
    text-decoration: none; background: transparent; transition: all .2s; cursor: pointer;
  }
  .view-btn:hover { background: var(--accent); color: var(--bg); }
  .price-row { display: flex; gap: 12px; }
  .price-box { flex: 1; background: var(--bg); border: 1px solid var(--border); padding: 12px 16px; }
  .price-label { font-size: 10px; letter-spacing: 1px; color: var(--muted); text-transform: uppercase; margin-bottom: 5px; font-family: 'Share Tech Mono', monospace; }
  .price-val { font-size: 17px; font-weight: 700; font-family: 'Share Tech Mono', monospace; }

  /* KÂR/ZARAR ÇUBUĞU */
  .profit-bar-wrap {
    background: var(--bg); border: 1px solid var(--border); padding: 14px 16px;
  }
  .profit-bar-label {
    display: flex; justify-content: space-between; align-items: center;
    font-family: 'Share Tech Mono', monospace; font-size: 11px; margin-bottom: 10px;
  }
  .profit-bar-label .p-title { color: var(--muted); letter-spacing: 1px; text-transform: uppercase; }
  .profit-bar-label .p-amount { font-size: 15px; font-weight: 700; }
  .profit-bar-label .p-amount.positive { color: var(--success); }
  .profit-bar-label .p-amount.negative { color: var(--error); }
  .profit-bar-track {
    height: 8px; background: rgba(255,64,96,0.2); border-radius: 0;
    position: relative; overflow: hidden;
  }
  .profit-bar-fill {
    height: 100%; position: absolute; left: 0; top: 0; transition: width .6s ease;
  }
  .profit-bar-fill.positive { background: linear-gradient(90deg, var(--success), #00ff88aa); }
  .profit-bar-fill.negative { background: linear-gradient(90deg, var(--error), #ff406080); }
  .profit-bar-pct {
    font-family: 'Share Tech Mono', monospace; font-size: 10px; color: var(--muted); margin-top: 6px; text-align: right;
  }

  .client-box { background: rgba(0,255,136,0.04); border: 1px solid rgba(0,255,136,0.15); padding: 16px; }
  .client-box-title { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--success); margin-bottom: 8px; font-family: 'Share Tech Mono', monospace; }
  .client-name { font-size: 15px; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
  .client-info { font-family: 'Share Tech Mono', monospace; font-size: 11px; color: var(--muted); line-height: 1.7; }
  .client-info span { color: var(--accent); }
  .supplier-box { background: rgba(0,212,255,0.04); border: 1px solid rgba(0,212,255,0.15); padding: 16px; }
  .supplier-box-title { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--accent); margin-bottom: 8px; font-family: 'Share Tech Mono', monospace; }
  .supplier-name { font-size: 15px; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
  .arac-tag { font-family: 'Share Tech Mono', monospace; font-size: 10px; letter-spacing: 1px; border: 1px solid var(--accent); color: var(--accent); padding: 2px 8px; background: rgba(0,212,255,0.08); }
  .search-highlight { background: rgba(0,212,255,0.2); color: var(--accent); border-radius: 2px; padding: 0 2px; }
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<nav>
  <div class="nav-brand">VANGUARD <span>LOGIC</span></div>
  <div class="nav-links">
    <a href="index.php">Personel</a>
    <a href="projects.php" class="active">Projeler</a>
    <a href="salary.php">Maaşlar</a>
    <a href="suppliers.php">Tedarikçiler</a>
    <span class="nav-sep">|</span>
    <a href="company.php">Şirket</a>
    <a href="departments.php">Departmanlar</a>
    <a href="customers.php">Müşteriler</a>
  </div>
  <div class="nav-user">
    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <a href="logout.php">ÇIKIŞ</a>
  </div>
</nav>

<main>
  <div class="page-header">
    <h2>Proje <span>Portföyü</span></h2>
    <a href="project_add.php" class="btn" style="border-color:var(--success); color:var(--success);">+ YENİ PROJE EKLE</a>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg'] === 'updated' || $_GET['msg'] === 'added' ? 'success' : ($_GET['msg'] === 'deleted' ? 'success' : 'error') ?>">
      <?php
        $msgs = [
            'updated' => 'Proje başarıyla güncellendi.',
            'added'   => 'Yeni proje başarıyla sisteme eklendi.',
            'deleted' => 'Proje sistemden silindi.',
            'error'   => 'Bir hata oluştu.'
        ];
        echo htmlspecialchars($msgs[$_GET['msg']] ?? 'İşlem tamamlandı.');
      ?>
    </div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-box s-blue">
      <div class="stat-label">Toplam Proje</div>
      <div class="stat-val c-blue"><?= $total ?></div>
    </div>
    <div class="stat-box s-warn">
      <div class="stat-label">Departman Projesi</div>
      <div class="stat-val c-warn"><?= $hasDept ?></div>
    </div>
    <div class="stat-box s-green">
      <div class="stat-label">Müşteri Projesi</div>
      <div class="stat-val c-green"><?= $hasCust ?></div>
    </div>
    <div class="stat-box s-purple">
      <div class="stat-label">Toplam Satış</div>
      <div class="stat-val c-purple">₺<?= number_format($topSale, 0, ',', '.') ?></div>
    </div>
    <div class="stat-box s-profit">
      <div class="stat-label">Toplam Kâr</div>
      <div class="stat-val c-profit">₺<?= number_format($totalProfit, 0, ',', '.') ?></div>
    </div>
  </div>

  <div class="controls-bar">
    <div class="search-box">
      <label for="projectSearch">Proje Adı Ara</label>
      <div class="search-input-wrap">
        <span class="search-icon">⌕</span>
        <input type="text" id="projectSearch" placeholder="Proje adı giriniz..." autocomplete="off">
        <button class="search-clear" id="searchClear" title="Temizle">×</button>
      </div>
      <div id="searchCount"></div>
    </div>

    <div class="controls-right">
      <div class="sort-group">
        <label>Personel</label>
        <select id="prsnlFilter" onchange="applyFilters()">
          <option value="">— Tümü —</option>
          <?php foreach($personnelList as $pers): ?>
          <option value="<?= $pers['id'] ?>"><?= htmlspecialchars($pers['f_name'].' '.$pers['l_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="sort-group">
        <label>Sıralama</label>
        <select onchange="location='?sort='+this.value">
          <option value="" <?= $sort==='' ?'selected':'' ?>>— Varsayılan (En Yeni) —</option>
          <option value="start_desc" <?= $sort==='start_desc' ?'selected':'' ?>>↓ En Yeni Başlayan</option>
          <option value="start_asc"  <?= $sort==='start_asc'  ?'selected':'' ?>>↑ En Eski Başlayan</option>
          <option value="budget_desc" <?= $sort==='budget_desc' ?'selected':'' ?>>↓ En Yüksek Bütçe</option>
          <option value="profit_desc" <?= $sort==='profit_desc' ?'selected':'' ?>>↓ En Yüksek Kâr</option>
        </select>
      </div>

      <div class="filter-group">
        <button class="filter-btn active" onclick="filterCards('all', this)">Tümü (<?= $total ?>)</button>
        <button class="filter-btn" onclick="filterCards('dept', this)">Departman (<?= $hasDept ?>)</button>
        <button class="filter-btn" onclick="filterCards('cust', this)">Müşteri (<?= $hasCust ?>)</button>
      </div>
    </div>
  </div>

  <div class="cards" id="cardGrid">
    <?php foreach ($projects as $p):
      $type   = $p['dept_name'] ? 'dept' : 'cust';
      $profit = $p['sale_price'] - $p['budget'];
      $pct    = $p['sale_price'] > 0 ? min(100, round(($profit / $p['sale_price']) * 100)) : 0;
      $profitPositive = $profit >= 0;
    ?>
    <div class="card" data-type="<?= $type ?>" data-name="<?= htmlspecialchars(mb_strtolower($p['name'])) ?>" data-team-ids="<?= htmlspecialchars($p['team_ids'] ?? '') ?>">
      <div class="card-stripe"></div>
      <div class="card-body">

        <div class="card-top">
          <div>
            <div class="card-title project-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="card-sub">#<?= $p['id'] ?> · <?= htmlspecialchars($p['company_name']) ?></div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <?php if ($type === 'dept'): ?>
              <span class="badge badge-dept">DEPARTMAN</span>
              <span class="badge badge-open">◈ SATIŞA AÇIK</span>
            <?php else: ?>
              <span class="badge badge-cust">MÜŞTERİ</span>
              <span class="badge badge-sold">✓ SATILDI</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($p['description']): ?>
          <div class="card-desc"><?= htmlspecialchars($p['description']) ?></div>
        <?php endif; ?>

        <div class="card-meta">
          <div class="meta-tag">Görev Alanlar: <span><strong style="color:var(--accent)"><?= htmlspecialchars($p['team_names'] ?? 'Atanmamış') ?></strong></span></div>
          <div class="meta-tag">Başlangıç: <span><?= $p['start_date'] ?></span></div>
          <div class="meta-tag">Bitiş: <span><?= $p['end_date'] ?? 'Devam ediyor' ?></span></div>
          <?php if ($p['dept_name']): ?>
            <div class="meta-tag">Departman: <span><?= htmlspecialchars($p['dept_name']) ?></span>
              <?php if ($p['dept_ext']): ?><span style="color:var(--muted)"> · Dahili: <?= htmlspecialchars($p['dept_ext']) ?></span><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <hr>

        <!-- FİNANSAL BLOK -->
        <?php if ($type === 'dept'): ?>
          <div class="price-row">
            <div class="price-box">
              <div class="price-label">Bütçe</div>
              <div class="price-val c-warn">₺<?= number_format($p['budget'], 0, ',', '.') ?></div>
            </div>
            <div class="price-box">
              <div class="price-label">Satış Fiyatı</div>
              <div class="price-val c-green">₺<?= number_format($p['sale_price'], 0, ',', '.') ?></div>
            </div>
          </div>
        <?php else: ?>
          <div class="price-row">
            <div class="price-box">
              <div class="price-label">Bütçe</div>
              <div class="price-val c-warn">₺<?= number_format($p['budget'], 0, ',', '.') ?></div>
            </div>
            <div class="price-box">
              <div class="price-label">Satış Fiyatı</div>
              <div class="price-val c-green">₺<?= number_format($p['sale_price'], 0, ',', '.') ?></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- KÂR / ZARAR ÇUBUĞU -->
        <div class="profit-bar-wrap">
          <div class="profit-bar-label">
            <span class="p-title"><?= $profitPositive ? 'Kâr' : 'Zarar' ?></span>
            <span class="p-amount <?= $profitPositive ? 'positive' : 'negative' ?>">
              <?= $profitPositive ? '+' : '' ?>₺<?= number_format($profit, 0, ',', '.') ?>
            </span>
          </div>
          <div class="profit-bar-track">
            <div class="profit-bar-fill <?= $profitPositive ? 'positive' : 'negative' ?>"
                 style="width: <?= abs($pct) ?>%"></div>
          </div>
          <div class="profit-bar-pct">
            Satış Fiyatının %<?= abs($pct) ?>'i
            · Bütçe: ₺<?= number_format($p['budget'], 0, ',', '.') ?>
          </div>
        </div>

        <?php if ($type === 'cust'): ?>
          <div class="client-box">
            <div class="client-box-title">Müşteri Bilgisi</div>
            <div class="client-name"><?= htmlspecialchars($p['customer_name'] ?? '—') ?></div>
            <div class="client-info">
              <?php if ($p['customer_email']): ?><span><?= htmlspecialchars($p['customer_email']) ?></span><br><?php endif; ?>
              <?= htmlspecialchars($p['customer_phone'] ?? '') ?>
            </div>
          </div>

          <?php if ($p['supplier_name']): ?>
          <div class="supplier-box">
            <div class="supplier-box-title">Tedarikçi (Aracı)</div>
            <div class="supplier-name"><?= htmlspecialchars($p['supplier_name']) ?> <span class="arac-tag">Aracılık</span></div>
            <div class="client-info">
              <?php if ($p['supplier_email']): ?><span><?= htmlspecialchars($p['supplier_email']) ?></span><br><?php endif; ?>
              <?php if ($p['supplier_phone']): ?><?= htmlspecialchars($p['supplier_phone']) ?><br><?php endif; ?>
              <?php if ($p['supplier_address']): ?><span style="color:var(--muted)"><?= htmlspecialchars($p['supplier_address']) ?></span><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>

      </div>

      <div class="card-actions">
        <a href="project_view.php?id=<?= $p['id'] ?>" class="view-btn">👁 İNCELE</a>
        <a href="project_edit.php?id=<?= $p['id'] ?>" class="edit-btn">✎ DÜZENLE</a>
        <a href="project_delete.php?id=<?= $p['id'] ?>" class="del-btn" onclick="return confirm('Bu projeyi silmek istediğinize emin misiniz?')">✕ SİL</a>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="no-results-msg" id="noResultsMsg">
      <span class="nr-icon">⊘</span>
      Arama kriterine uyan proje bulunamadı.
    </div>
  </div>
</main>

<script src="assets/js/projects_filter.js"></script>
</body>
</html>
