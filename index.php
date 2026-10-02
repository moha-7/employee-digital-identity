<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

$dataFile = __DIR__ . '/data/employees.json';
$rawData = @file_get_contents($dataFile);
$data = is_string($rawData) ? json_decode($rawData, true) : null;
if (!is_array($data)) { http_response_code(500); exit('Demo data is unavailable.'); }

$brand = isset($data['brand']) && is_array($data['brand']) ? $data['brand'] : [];
$employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function evalue(array $arr, string $key, mixed $default = ''): mixed { return array_key_exists($key, $arr) ? $arr[$key] : $default; }
function clean_tel(mixed $value): string { return (string)preg_replace('/[^\d+]/', '', (string)$value); }
function vcard_escape(mixed $value): string {
    $text = str_replace('\\', '\\\\', (string)$value);
    $text = str_replace("\n", '\\n', $text);
    $text = str_replace(',', '\\,', $text);
    return str_replace(';', '\\;', $text);
}
function profile_url(array $brand, string $slug): string {
    $base = rtrim((string)evalue($brand, 'base_url', ''), '/');
    return $base !== '' ? $base . '/' . rawurlencode($slug) : '/' . rawurlencode($slug);
}
function vcard_text(array $employee, array $brand): string {
    $slug = (string)evalue($employee, 'slug');
    $lines = [
        'BEGIN:VCARD', 'VERSION:3.0',
        'FN:' . vcard_escape(evalue($employee, 'name')),
        'ORG:' . vcard_escape(evalue($employee, 'company', evalue($brand, 'name', 'Northstar Group'))),
    ];
    if (evalue($employee, 'position')) $lines[] = 'TITLE:' . vcard_escape(evalue($employee, 'position'));
    if (evalue($employee, 'email')) $lines[] = 'EMAIL;TYPE=WORK:' . vcard_escape(evalue($employee, 'email'));
    if (evalue($employee, 'mobile')) $lines[] = 'TEL;TYPE=CELL:' . vcard_escape(evalue($employee, 'mobile'));
    if (evalue($employee, 'landline')) $lines[] = 'TEL;TYPE=WORK,VOICE:' . vcard_escape(evalue($employee, 'landline'));
    $lines[] = 'URL:' . vcard_escape(profile_url($brand, $slug));
    $lines[] = 'END:VCARD';
    return implode("\r\n", $lines) . "\r\n";
}
function icon(string $name): string {
    $icons = [
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        'refresh' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6v5h-5M4 18v-5h5"/><path d="M18 10a7 7 0 0 0-12-3L4 11M6 14a7 7 0 0 0 12 3l2-4"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        'card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h4M7 13h7"/></svg>',
        'qr' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v6h-6v-2M14 18h2"/></svg>',
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 4.5-1 10-10-3.5-3.5-10 10L4 20Z"/><path d="m13.5 7 3.5 3.5"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3h4a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.5 3 3.9 5.4 6.9 6.9l2.3-2.3c.3-.3.7-.4 1.1-.2 1.2.4 2.5.7 3.8.7.6 0 1 .4 1 1V21c0 .6-.4 1-1 1C10.4 22 2 13.6 2 3.3c0-.6.4-1 1-1h4.1c.6 0 1 .4 1 1 0 1.3.2 2.6.7 3.8.1.4 0 .8-.3 1.1l-1.9 2.6Z"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
        'download' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>',
        'copy' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>',
        'close' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>'
    ];
    return $icons[$name] ?? '';
}

if (isset($_GET['vcard'])) {
    $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['vcard']));
    if (!isset($employees[$slug]) || !is_array($employees[$slug])) { http_response_code(404); exit('Not found'); }
    header('Content-Type: text/vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.vcf"');
    echo vcard_text($employees[$slug], $brand);
    exit;
}

$slug = isset($_GET['employee']) ? strtolower((string)preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['employee'])) : '';
$brandName = (string)evalue($brand, 'name', 'Northstar Group');
$productName = (string)evalue($brand, 'product_name', 'Identity Operations');
$logo = (string)evalue($brand, 'logo');
$pageTitle = $slug && isset($employees[$slug]) ? (string)evalue($employees[$slug], 'name') . ' | ' . $brandName : 'Employee Identity Operations | ' . $brandName;
$departments = [];
foreach ($employees as $e) if (is_array($e) && evalue($e, 'department')) $departments[(string)evalue($e, 'department')] = true;
ksort($departments);

// Set the HTTP status before any HTML is emitted so missing profiles are reliably 404
// across PHP's built-in server and Apache deployments.
if ($slug !== '' && (!isset($employees[$slug]) || !is_array($employees[$slug]))) {
    http_response_code(404);
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Portfolio demo of an employee digital identity, QR and vCard operations platform.">
<title><?= h($pageTitle) ?></title>
<link rel="stylesheet" href="/assets/style.css">
<script src="/assets/app.js" defer></script>
</head>
<body class="<?= $slug === '' ? 'studio-page' : 'profile-page' ?>">
<?php if ($slug === ''): ?>
<div class="app-shell">
  <header class="topbar">
    <a class="brand-lockup" href="/" aria-label="<?= h($brandName) ?> identity studio">
      <?php if ($logo): ?><img src="/<?= h($logo) ?>" alt="<?= h($brandName) ?> mark"><?php endif; ?>
      <span><strong><?= h($brandName) ?></strong><small>Identity Operations Studio</small></span>
    </a>
    <nav class="top-actions" aria-label="Workspace actions">
      <span class="demo-mode"><span></span>Portfolio demo</span>
      <button class="ghost-button" type="button" data-modal-open="settings"><?= icon('settings') ?><span>Settings</span></button>
    </nav>
  </header>

  <main class="workspace">
    <section class="workspace-head">
      <div>
        <p class="eyebrow">Employee identity operations</p>
        <h1>QR & Contact Management Studio</h1>
        <p class="lead">Manage employee identity profiles, QR distribution and downloadable vCards from one operational workspace.</p>
      </div>
      <button class="primary-button" type="button" data-mutation="add"><?= icon('plus') ?><span>Add employee</span></button>
    </section>

    <section class="metric-grid" aria-label="Identity profile metrics">
      <article class="metric-card"><span class="metric-icon"><?= icon('user') ?></span><div><small>Employees</small><strong><?= count($employees) ?></strong><p>Identity records</p></div></article>
      <article class="metric-card"><span class="metric-icon"><?= icon('card') ?></span><div><small>Profile ready</small><strong><?= count($employees) ?></strong><p>Public profile URLs</p></div></article>
      <article class="metric-card"><span class="metric-icon"><?= icon('qr') ?></span><div><small>QR ready</small><strong><?= count($employees) ?></strong><p>Generated assets</p></div></article>
      <article class="metric-card"><span class="metric-icon"><?= icon('mail') ?></span><div><small>Contact ready</small><strong><?= count($employees) ?></strong><p>Email + vCard enabled</p></div></article>
    </section>

    <section class="control-bar" aria-label="Employee filters">
      <label class="search-box">
        <span><?= icon('search') ?></span>
        <input id="employeeSearch" type="search" placeholder="Search employees, roles or departments..." autocomplete="off">
      </label>
      <select id="departmentFilter" aria-label="Filter by department">
        <option value="">All departments</option>
        <?php foreach (array_keys($departments) as $department): ?><option value="<?= h(strtolower($department)) ?>"><?= h($department) ?></option><?php endforeach; ?>
      </select>
      <button id="refreshFilters" class="icon-button" type="button" title="Reset filters"><?= icon('refresh') ?><span>Reset</span></button>
    </section>

    <div class="section-row">
      <div><p class="eyebrow">Employee directory</p><h2>Active identity profiles</h2></div>
      <span id="resultCount" class="result-count"><?= count($employees) ?> profiles</span>
    </div>

    <section id="employeeGrid" class="employee-grid">
      <?php foreach ($employees as $employeeSlug => $employee): if (!is_array($employee)) continue; ?>
      <?php $searchText = strtolower(implode(' ', [(string)evalue($employee,'name'), (string)evalue($employee,'position'), (string)evalue($employee,'department'), (string)evalue($employee,'email')])); ?>
      <article class="employee-card" data-search="<?= h($searchText) ?>" data-department="<?= h(strtolower((string)evalue($employee,'department'))) ?>">
        <div class="employee-card-head">
          <img class="employee-avatar" src="/<?= h((string)evalue($employee,'photo',$logo)) ?>" alt="<?= h(evalue($employee,'name')) ?>">
          <div class="employee-primary">
            <div class="employee-title-row"><h3><?= h(evalue($employee,'name')) ?></h3><span class="ready-badge"><span></span><?= h(evalue($employee,'status','Ready')) ?></span></div>
            <p><?= h(evalue($employee,'position')) ?></p>
            <small><?= h(evalue($employee,'department')) ?></small>
          </div>
        </div>

        <div class="employee-contact">
          <div><span>Email</span><strong><?= h(evalue($employee,'email')) ?></strong></div>
          <div><span>Mobile</span><strong><?= h(evalue($employee,'mobile')) ?></strong></div>
        </div>

        <div class="employee-actions">
          <a class="action-button neutral" href="/<?= h($employeeSlug) ?>"><?= icon('user') ?><span>Profile</span></a>
          <a class="action-button neutral" href="/vcard/<?= h($employeeSlug) ?>"><?= icon('card') ?><span>vCard</span></a>
          <button class="action-button accent" type="button"
            data-qr-open
            data-name="<?= h(evalue($employee,'name')) ?>"
            data-role="<?= h(evalue($employee,'position')) ?>"
            data-profile="/<?= h($employeeSlug) ?>"
            data-qr="/assets/qr/<?= h($employeeSlug) ?>.svg"
            data-qr-download="/assets/qr/<?= h($employeeSlug) ?>.png"><?= icon('qr') ?><span>QR</span></button>
          <button class="action-button dark" type="button" data-mutation="edit" data-name="<?= h(evalue($employee,'name')) ?>"><?= icon('edit') ?><span>Edit</span></button>
          <button class="action-button danger" type="button" data-mutation="delete" data-name="<?= h(evalue($employee,'name')) ?>"><?= icon('trash') ?><span>Delete</span></button>
        </div>
      </article>
      <?php endforeach; ?>
    </section>

    <div id="emptyState" class="empty-state" hidden><strong>No matching profiles</strong><span>Try another name, role or department.</span></div>
  </main>

  <footer class="workspace-footer"><span>Fictional portfolio environment</span><span><?= h($brandName) ?> · Identity Operations · © <?= date('Y') ?></span></footer>
</div>

<div class="modal" id="qrModal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <section class="modal-panel qr-modal-panel" role="dialog" aria-modal="true" aria-labelledby="qrTitle">
    <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
    <div class="modal-kicker">Profile QR</div>
    <h2 id="qrTitle">Employee profile</h2>
    <p id="qrRole" class="modal-subtitle"></p>
    <div class="qr-frame"><img id="qrImage" src="" alt="Employee profile QR code"></div>
    <div id="qrUrl" class="profile-url"></div>
    <div class="modal-actions">
      <a id="qrDownload" class="primary-button compact" href="#" download><?= icon('download') ?><span>Download QR</span></a>
      <button id="copyProfileLink" class="secondary-button compact" type="button"><?= icon('copy') ?><span>Copy link</span></button>
      <a id="qrProfileLink" class="secondary-button compact" href="#"><?= icon('user') ?><span>Open profile</span></a>
    </div>
  </section>
</div>

<div class="modal" id="settingsModal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <section class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">
    <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
    <div class="modal-kicker">Demo configuration</div>
    <h2 id="settingsTitle">Identity operations settings</h2>
    <div class="settings-list">
      <div><span>Profile routing</span><strong>Clean employee URLs</strong></div>
      <div><span>Contact export</span><strong>vCard 3.0</strong></div>
      <div><span>QR delivery</span><strong>Profile-linked assets</strong></div>
      <div><span>Data mode</span><strong>Sanitized fictional records</strong></div>
    </div>
    <p class="modal-note">Production administration actions are intentionally disabled in the public portfolio edition.</p>
  </section>
</div>

<div class="modal" id="mutationModal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <section class="modal-panel mutation-panel" role="dialog" aria-modal="true" aria-labelledby="mutationTitle">
    <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
    <div class="modal-kicker">Read-only portfolio demo</div>
    <h2 id="mutationTitle">Employee administration</h2>
    <p id="mutationMessage" class="modal-note"></p>
    <div class="capability-strip"><span>Add profiles</span><span>Edit records</span><span>Retire identities</span><span>Regenerate QR</span></div>
  </section>
</div>

<?php elseif (!isset($employees[$slug]) || !is_array($employees[$slug])): ?>
<div class="profile-shell">
  <header class="profile-topbar"><a class="brand-lockup" href="/"><?php if ($logo): ?><img src="/<?= h($logo) ?>" alt="<?= h($brandName) ?> mark"><?php endif; ?><span><strong><?= h($brandName) ?></strong><small>Employee Digital Identity</small></span></a><span class="demo-mode"><span></span>Portfolio demo</span></header>
  <main class="notfound-card"><span class="notfound-code">404</span><h1>Profile not found</h1><p>The employee identity URL is invalid or no longer active.</p><a class="primary-button compact" href="/">Back to studio</a></main>
</div>

<?php else: $employee = $employees[$slug]; ?>
<div class="profile-shell">
  <header class="profile-topbar">
    <a class="brand-lockup" href="/"><?php if ($logo): ?><img src="/<?= h($logo) ?>" alt="<?= h($brandName) ?> mark"><?php endif; ?><span><strong><?= h($brandName) ?></strong><small>Employee Digital Identity</small></span></a>
    <span class="demo-mode"><span></span>Portfolio demo</span>
  </header>
  <main class="profile-stage">
    <article class="identity-card">
      <div class="identity-cover"><span>Official digital contact card</span><img src="/<?= h($logo) ?>" alt=""></div>
      <div class="identity-body">
        <img class="profile-avatar" src="/<?= h((string)evalue($employee,'photo',$logo)) ?>" alt="<?= h(evalue($employee,'name')) ?>">
        <span class="identity-label">Employee identity</span>
        <h1><?= h(evalue($employee,'name')) ?></h1>
        <p class="profile-role"><?= h(evalue($employee,'position')) ?></p>
        <p class="profile-department"><?= h(evalue($employee,'department')) ?></p>

        <div class="profile-details">
          <a href="mailto:<?= h(evalue($employee,'email')) ?>"><span class="detail-icon"><?= icon('mail') ?></span><span><small>Email</small><strong><?= h(evalue($employee,'email')) ?></strong></span></a>
          <a href="tel:<?= h(clean_tel(evalue($employee,'mobile'))) ?>"><span class="detail-icon"><?= icon('phone') ?></span><span><small>Mobile</small><strong><?= h(evalue($employee,'mobile')) ?></strong></span></a>
          <a href="tel:<?= h(clean_tel(evalue($employee,'landline'))) ?>"><span class="detail-icon"><?= icon('phone') ?></span><span><small>Office</small><strong><?= h(evalue($employee,'landline')) ?></strong></span></a>
          <div><span class="detail-icon">#</span><span><small>Extension</small><strong><?= h(evalue($employee,'extension')) ?></strong></span></div>
        </div>

        <div class="profile-actions">
          <a class="profile-button dark" href="tel:<?= h(clean_tel(evalue($employee,'mobile'))) ?>"><?= icon('phone') ?><span>Call</span></a>
          <a class="profile-button light" href="mailto:<?= h(evalue($employee,'email')) ?>"><?= icon('mail') ?><span>Email</span></a>
          <a class="profile-button accent full" href="/vcard/<?= h($slug) ?>"><?= icon('card') ?><span>Save contact</span></a>
        </div>
        <a class="back-link" href="/"><?= icon('arrow') ?><span>Return to management studio</span></a>
      </div>
    </article>
  </main>
  <footer class="profile-footer">Fictional portfolio environment · <?= h($brandName) ?> · © <?= date('Y') ?></footer>
</div>
<?php endif; ?>
</body>
</html>
