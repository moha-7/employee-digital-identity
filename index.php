<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'none'");

$dataFile = __DIR__ . '/data/employees.json';
$rawData = @file_get_contents($dataFile);
$data = is_string($rawData) ? json_decode($rawData, true) : null;

if (!is_array($data)) {
    http_response_code(500);
    exit('Demo data is unavailable.');
}

$brand = isset($data['brand']) && is_array($data['brand']) ? $data['brand'] : [];
$employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

function h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function clean_tel(mixed $value): string {
    return (string) preg_replace('/[^\d+]/', '', (string) $value);
}

function evalue(array $arr, string $key, mixed $default = ''): mixed {
    return array_key_exists($key, $arr) ? $arr[$key] : $default;
}

function vcard_escape(mixed $value): string {
    $text = (string) $value;
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace("\n", '\\n', $text);
    $text = str_replace(',', '\\,', $text);
    return str_replace(';', '\\;', $text);
}

function profile_url(array $brand, string $slug): string {
    $base = rtrim((string) evalue($brand, 'base_url', ''), '/');
    return $base !== '' ? $base . '/' . rawurlencode($slug) : '/' . rawurlencode($slug);
}

function vcard_text(array $employee, array $brand): string {
    $slug = (string) evalue($employee, 'slug');
    $lines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
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

if (isset($_GET['vcard'])) {
    $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_GET['vcard']));
    if (!isset($employees[$slug]) || !is_array($employees[$slug])) {
        http_response_code(404);
        exit('Not found');
    }

    header('Content-Type: text/vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.vcf"');
    echo vcard_text($employees[$slug], $brand);
    exit;
}

$slug = isset($_GET['employee'])
    ? strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_GET['employee']))
    : '';

$brandName = (string) evalue($brand, 'name', 'Northstar Group');
$productName = (string) evalue($brand, 'product_name', 'Digital Identity');
$logo = (string) evalue($brand, 'logo');
$pageTitle = $productName . ' | ' . $brandName;

if ($slug !== '' && isset($employees[$slug]) && is_array($employees[$slug])) {
    $pageTitle = (string) evalue($employees[$slug], 'name') . ' | ' . $brandName;
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="description" content="Fictional portfolio demo of an employee digital identity and smart contact platform.">
<title><?= h($pageTitle) ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="wrap">
  <header class="brand">
    <?php if ($logo): ?><img src="/<?= h($logo) ?>" alt="<?= h($brandName) ?> mark"><?php endif; ?>
    <div>
      <strong><?= h($brandName) ?></strong>
      <small><?= h($productName) ?></small>
    </div>
  </header>

  <div class="demo-banner">Portfolio demo · fictional people and contact data</div>

<?php if ($slug === ''): ?>
  <section class="directory-head">
    <p class="eyebrow">Employee directory</p>
    <h1>Digital profiles built for QR access.</h1>
    <p>Open a profile to call, email, or save a standards-based vCard contact in one tap.</p>
  </section>

  <div class="list">
    <?php foreach ($employees as $employeeSlug => $employee): ?>
      <?php if (!is_array($employee)) continue; ?>
      <a href="/<?= h($employeeSlug) ?>">
        <span><?= h(evalue($employee, 'name')) ?></span>
        <small><?= h(evalue($employee, 'position')) ?></small>
      </a>
    <?php endforeach; ?>
  </div>
<?php elseif (!isset($employees[$slug]) || !is_array($employees[$slug])): ?>
  <div class="notfound">
    <p class="eyebrow">404</p>
    <h1>Profile not found</h1>
    <p>Check the profile link and try again.</p>
    <a class="text-link" href="/">Back to demo directory</a>
  </div>
<?php else: $employee = $employees[$slug]; ?>
  <article class="card">
    <div class="top"></div>
    <?php
      $photo = (string) evalue($employee, 'photo');
      $image = $photo !== '' ? $photo : $logo;
    ?>
    <?php if ($image): ?><img class="avatar" src="/<?= h($image) ?>" alt="<?= h(evalue($employee, 'name')) ?>"><?php endif; ?>

    <div class="content">
      <p class="eyebrow">Digital profile</p>
      <h1 class="name"><?= h(evalue($employee, 'name')) ?></h1>
      <?php if (evalue($employee, 'position')): ?><p class="role"><?= h(evalue($employee, 'position')) ?></p><?php endif; ?>
      <?php if (evalue($employee, 'department')): ?><p class="dept"><?= h(evalue($employee, 'department')) ?></p><?php endif; ?>

      <div class="info">
        <?php if (evalue($employee, 'email')): ?>
        <div class="row"><div class="label">Email</div><div class="value"><a href="mailto:<?= h(evalue($employee, 'email')) ?>"><?= h(evalue($employee, 'email')) ?></a></div></div>
        <?php endif; ?>
        <?php if (evalue($employee, 'mobile')): ?>
        <div class="row"><div class="label">Mobile</div><div class="value"><a href="tel:<?= h(clean_tel(evalue($employee, 'mobile'))) ?>"><?= h(evalue($employee, 'mobile')) ?></a></div></div>
        <?php endif; ?>
        <?php if (evalue($employee, 'landline')): ?>
        <div class="row"><div class="label">Office</div><div class="value"><a href="tel:<?= h(clean_tel(evalue($employee, 'landline'))) ?>"><?= h(evalue($employee, 'landline')) ?></a></div></div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <?php if (evalue($employee, 'mobile')): ?><a class="btn primary" href="tel:<?= h(clean_tel(evalue($employee, 'mobile'))) ?>">Call</a><?php endif; ?>
        <?php if (evalue($employee, 'email')): ?><a class="btn light" href="mailto:<?= h(evalue($employee, 'email')) ?>">Email</a><?php endif; ?>
        <a class="btn full" href="/vcard/<?= h($slug) ?>">Save Contact</a>
      </div>

      <a class="text-link back-link" href="/">View demo directory</a>
    </div>
  </article>
<?php endif; ?>

  <footer class="footer">
    <span>Fictional portfolio environment</span>
    <span>·</span>
    <span>© <?= date('Y') ?> <?= h($brandName) ?></span>
  </footer>
</div>
</body>
</html>
