<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function norm_avatar(?string $p): string {
  $img = trim((string)$p);
  if ($img==='') return '/images/no-photo.jpeg';
  $rel = '/' . ltrim($img, '/');
  $fs = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel), DIRECTORY_SEPARATOR);
  if (!is_file($fs)) return '/images/no-photo.jpeg';
  $v = @filemtime($fs) ?: time();
  return $rel . '?v=' . $v;
}

try{
  $con = (new Database())->conectar();
  $stmt = $con->query("SELECT id, nombre, email, rol, activo, avatar, creado_en FROM usuario ORDER BY id DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
  echo '<tr><td colspan="8">Error: '.htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').'</td></tr>';
  exit;
}

if (!$rows){
  echo '<tr><td colspan="8" class="text-center text-muted">No hay usuarios</td></tr>';
  exit;
}

foreach($rows as $r){
  $r2 = $r;
  $r2['avatar_url'] = norm_avatar($r['avatar'] ?? '');
  $r2['activo'] = (bool)$r['activo'];
  $json = htmlspecialchars(json_encode($r2), ENT_QUOTES, 'UTF-8');
  $badge = $r2['activo'] ? "<span class='badge bg-success'>Sí</span>" : "<span class='badge bg-secondary'>No</span>";
  echo "<tr data-user='{$json}'>
    <td>{$r['id']}</td>
    <td><img src='".norm_avatar($r['avatar']??'')."' style='width:40px;height:40px;object-fit:cover;border-radius:10px;'></td>
    <td>".htmlspecialchars($r['nombre'],ENT_QUOTES,'UTF-8')."</td>
    <td>".htmlspecialchars($r['email'],ENT_QUOTES,'UTF-8')."</td>
    <td><span class='badge badge-soft'>".htmlspecialchars($r['rol'],ENT_QUOTES,'UTF-8')."</span></td>
    <td>{$badge}</td>
    <td>".htmlspecialchars($r['creado_en'],ENT_QUOTES,'UTF-8')."</td>
    <td class='d-flex gap-2'>
      <button class='btn btn-sm btn-outline-primary btn-edit'>Editar</button>
      <button class='btn btn-sm btn-outline-warning btn-toggle' data-id='{$r['id']}'>".($r2['activo']?'Desactivar':'Activar')."</button>
      <button class='btn btn-sm btn-outline-danger btn-del' data-id='{$r['id']}'>Eliminar</button>
    </td>
  </tr>";
}
