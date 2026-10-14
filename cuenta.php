<?php
require __DIR__.'/sistema/Nucleo/inicio.php';
$user=require_user(); $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 try {
  if (($_POST['accion']??'')==='clave') {
   $u=(new UsuarioDAO(Database::connection()))->findByEmail($user['email']);
   $password=(string)($_POST['nueva']??'');
   if (!$u || !password_verify((string)($_POST['actual']??''),$u['password_hash'])) throw new RuntimeException('La contraseña actual no es correcta.');
   if (strlen($password)<8 || $password!==($_POST['confirmar']??'')) throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres y coincidir con la confirmación.');
   execute_sql('UPDATE usuarios SET password_hash=? WHERE id_usuario=?',[password_hash($password,PASSWORD_DEFAULT),$user['id']]); session_regenerate_id(true);
  } else {
   $name=trim($_POST['nombre']??''); $surname=trim($_POST['apellido']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['telefono']??'');
   if (!$name || !$surname || mb_strlen($name)>100 || mb_strlen($surname)>100 || strlen($phone)>30 || strlen($email)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Revisá los datos del perfil.');
   (new UsuarioDAO(Database::connection()))->updateProfile($user['id'],$name,$surname,$phone,$email);
   foreach (['name'=>$name,'surname'=>$surname,'email'=>$email,'phone'=>$phone] as $key=>$value) $_SESSION['user'][$key]=$value;
  }
  flash('Tu cuenta se actualizó correctamente.'); redirect('/Inmobiliaria/cuenta.php');
 } catch (RuntimeException $e) { $error=$e instanceof PDOException?'No se pudo guardar: el correo puede estar asociado a otra cuenta.':$e->getMessage(); }
}
$pageTitle='Mi cuenta'; require __DIR__.'/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard narrow"><a class="text-link" href="<?= e(role_destination($user['role'])) ?>">← Volver a mi panel</a><h1>Mi perfil</h1><?php notice(); if($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?><div class="content-card"><div class="profile-head"><div class="profile-person"><div class="avatar"><?= e(mb_substr($user['name'],0,1)) ?></div><div><h3><?= e($user['name'].' '.$user['surname']) ?></h3><p><?= e($user['role']) ?> · <?= e($user['email']) ?></p></div></div></div><form class="form-grid" method="post"><?php csrf_field(); ?><input type="hidden" name="accion" value="perfil"><?php foreach(['nombre'=>['Nombre','name'],'apellido'=>['Apellido','surname'],'telefono'=>['Teléfono','phone'],'email'=>['Correo','email']] as $key=>$field): ?><div class="field"><label for="<?= $key ?>"><?= $field[0] ?></label><input id="<?= $key ?>" name="<?= $key ?>" type="<?= $key==='email'?'email':'text' ?>" value="<?= e((string)($_POST[$key]??$user[$field[1]])) ?>" <?= $key==='telefono'?'':'required' ?>></div><?php endforeach; ?><div class="field full"><button class="btn">Guardar datos</button></div></form></div><details class="content-card"><summary><strong>Cambiar contraseña</strong></summary><form method="post" class="modal-form security-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="clave"><?php foreach(['actual'=>'Contraseña actual','nueva'=>'Nueva contraseña','confirmar'=>'Confirmar nueva contraseña'] as $key=>$label): ?><div class="field"><label for="<?= $key ?>"><?= $label ?></label><input type="password" id="<?= $key ?>" name="<?= $key ?>" required minlength="<?= $key==='actual'?1:8 ?>" autocomplete="<?= $key==='actual'?'current-password':'new-password' ?>"></div><?php endforeach; ?><button class="btn">Actualizar contraseña</button></form></details></section>
<?php require __DIR__.'/sistema/Vistas/Diseno/pie.php'; ?>
