<?php
$activeClients=$crmClients;
$workingProperties=$props;
$acceptedOffers=array_values(array_filter($crmData['offers'],static fn($offer)=>$offer['estado']==='Aceptada' && (!$offer['fecha_vencimiento'] || $offer['fecha_vencimiento']>=date('Y-m-d'))));
?>
<div class="crm-board">
    <div class="content-card crm-intro"><h2>Seguimiento comercial</h2><p class="content-subtitle">Registrá interacciones y visitas, organizá tareas y gestioná ofertas y reservas desde la cartera asignada.</p></div>

    <div class="crm-form-grid">
        <details class="content-card" open><summary><strong>Registrar interacción</strong></summary><p class="help-text">Cada contacto queda asociado al cliente y a una propiedad.</p>
            <form method="post" class="form-grid crm-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="interaccion">
                <div class="field"><label>Cliente</label><select name="cliente" required><option value="">Elegir cliente</option><?php person_options($activeClients); ?></select></div>
                <div class="field"><label>Propiedad</label><select name="propiedad" required><option value="">Elegir propiedad</option><?php foreach($workingProperties as $property): ?><option value="<?= (int)$property['id_propiedad'] ?>"><?= e('#'.$property['id_propiedad'].' · '.$property['titulo']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Tipo de interacción</label><select name="tipo_interaccion" required><?php foreach(['Llamada','WhatsApp','Correo','Envío de ficha','Seguimiento','Nota interna'] as $kind): ?><option><?= e($kind) ?></option><?php endforeach; ?></select></div>
                <div class="field full"><label>Observación</label><textarea name="observaciones" minlength="3" maxlength="5000" required placeholder="Resultado, próximo paso o nota para el equipo"></textarea></div>
                <div class="field full"><button class="btn">Guardar interacción</button></div>
            </form>
        </details>
        <details class="content-card"><summary><strong>Programar visita</strong></summary><p class="help-text">La visita queda vinculada al cliente, agente y propiedad.</p>
            <form method="post" class="form-grid crm-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="programar_visita">
                <div class="field"><label>Cliente</label><select name="cliente" required><option value="">Elegir cliente</option><?php person_options($activeClients); ?></select></div>
                <div class="field"><label>Propiedad</label><select name="propiedad" required><option value="">Elegir propiedad</option><?php foreach($workingProperties as $property): ?><option value="<?= (int)$property['id_propiedad'] ?>"><?= e('#'.$property['id_propiedad'].' · '.$property['titulo']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Fecha y hora</label><input type="datetime-local" name="fecha_hora" min="<?= e(date('Y-m-d\TH:i')) ?>" required></div>
                <div class="field full"><label>Observaciones</label><textarea name="observaciones" maxlength="3000" placeholder="Punto de encuentro o indicaciones"></textarea></div>
                <div class="field full"><button class="btn">Agendar visita</button></div>
            </form>
        </details>
        <details class="content-card"><summary><strong>Crear tarea de seguimiento</strong></summary><p class="help-text">Asignada a tu usuario<?= $admin?' (el administrador puede asignarla a su cuenta)':'' ?>.</p>
            <form method="post" class="form-grid crm-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="crear_tarea">
                <div class="field"><label>Cliente</label><select name="cliente" required><option value="">Elegir cliente</option><?php person_options($activeClients); ?></select></div>
                <div class="field"><label>Propiedad</label><select name="propiedad" required><option value="">Elegir propiedad</option><?php foreach($workingProperties as $property): ?><option value="<?= (int)$property['id_propiedad'] ?>"><?= e('#'.$property['id_propiedad'].' · '.$property['titulo']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Tarea</label><input name="titulo" maxlength="180" required placeholder="Ej.: volver a llamar"></div>
                <div class="field"><label>Fecha límite</label><input type="datetime-local" name="fecha_limite" min="<?= e(date('Y-m-d\TH:i')) ?>" required></div>
                <div class="field full"><label>Detalle</label><textarea name="detalle" maxlength="3000"></textarea></div>
                <div class="field full"><button class="btn">Crear tarea</button></div>
            </form>
        </details>
    </div>

    <div class="content-card"><div class="section-heading"><div><h2>Ofertas de clientes</h2><p><?= count($crmData['offers']) ?> ofertas relacionadas con tu cartera.</p></div></div>
        <?php if($crmData['offers']): ?><div class="table-wrap"><table class="table"><thead><tr><th>Cliente</th><th>Propiedad / operación</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
            <?php foreach($crmData['offers'] as $offer): $shownStatus=$offer['estado']==='Pendiente' && $offer['fecha_vencimiento'] && $offer['fecha_vencimiento']<date('Y-m-d')?'Vencida':$offer['estado']; ?>
                <tr><td><?= e($offer['cliente'].' '.$offer['apellido']) ?></td><td><?= e($offer['titulo'].' · '.$offer['operacion']) ?></td><td><?= e($offer['moneda'].' '.number_format((float)$offer['monto'],2,',','.')) ?></td><td><?= e($offer['fecha_vencimiento']?date('d/m/Y',strtotime($offer['fecha_vencimiento'])):'Sin fecha') ?></td><td><span class="status <?= $shownStatus==='Pendiente'?'amber':'blue' ?>"><?= e($shownStatus) ?></span></td><td>
                    <?php if($offer['estado']==='Pendiente'): ?><form method="post" class="crm-inline-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="revisar_oferta"><input type="hidden" name="id" value="<?= (int)$offer['id_oferta'] ?>"><button class="btn btn-soft" name="estado" value="Aceptada" <?= $shownStatus==='Vencida'?'disabled':'' ?>>Aceptar</button><button class="btn btn-outline" name="estado" value="Rechazada">Rechazar</button></form><?php elseif($offer['estado']==='Aceptada'): ?><span class="help-text">Podés crear una reserva debajo.</span><?php endif; ?>
                </td></tr>
            <?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="admin-empty">Todavía no hay ofertas de clientes.</p><?php endif; ?>
    </div>

    <details class="content-card"><summary><strong>Crear reserva desde una oferta aceptada</strong></summary><p class="help-text">La reserva queda pendiente hasta que un administrador la autorice.</p>
        <?php if($acceptedOffers): ?><form method="post" class="form-grid crm-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="crear_reserva"><div class="field full"><label>Oferta aceptada</label><select name="oferta" required><option value="">Elegir oferta</option><?php foreach($acceptedOffers as $offer): ?><option value="<?= (int)$offer['id_oferta'] ?>"><?= e('#'.$offer['id_oferta'].' · '.$offer['cliente'].' '.$offer['apellido'].' · '.$offer['titulo'].' · '.$offer['moneda'].' '.number_format((float)$offer['monto'],2,',','.')) ?></option><?php endforeach; ?></select></div><div class="field"><label>Fecha límite</label><input type="datetime-local" name="fecha_limite" min="<?= e(date('Y-m-d\TH:i',time()+3600)) ?>" required></div><div class="field"><label>Medio de pago</label><input name="medio_pago" maxlength="80" placeholder="Transferencia, efectivo…"></div><div class="field full"><label>Observaciones</label><textarea name="observaciones" maxlength="3000"></textarea></div><div class="field full"><button class="btn">Registrar reserva pendiente</button></div></form><?php else: ?><p class="admin-empty">Para reservar, primero aceptá una oferta vigente.</p><?php endif; ?>
    </details>

    <div class="content-card"><h2>Reservas y vencimientos</h2><p class="content-subtitle">Las reservas próximas a vencer o vencidas quedan destacadas.</p>
        <?php if($crmData['reservations']): ?><div class="table-wrap"><table class="table"><thead><tr><th>Cliente</th><th>Propiedad</th><th>Fecha límite</th><th>Pago</th><th>Estado</th><th>Gestión</th></tr></thead><tbody>
            <?php foreach($crmData['reservations'] as $reservation): $daysLeft=(int)floor((strtotime($reservation['fecha_limite'])-time())/86400); $warning=$reservation['estado']==='Activa' && $daysLeft<=3; ?>
                <tr><td><?= e($reservation['cliente'].' '.$reservation['apellido']) ?></td><td><?= e($reservation['titulo'].' · '.$reservation['operacion']) ?></td><td><?= e(date('d/m/Y H:i',strtotime($reservation['fecha_limite']))) ?><?php if($reservation['estado']==='Activa' && $daysLeft<0): ?> <span class="status amber">Vencida</span><?php elseif($warning): ?> <span class="status amber">Vence pronto</span><?php endif; ?></td><td><?= e($reservation['estado_pago'].' · '.($reservation['medio_pago']?:'Sin medio')) ?></td><td><span class="status <?= $reservation['estado']==='Pendiente'?'amber':'blue' ?>"><?= e($reservation['estado']) ?></span></td><td>
                    <?php if($admin && $reservation['estado']==='Pendiente'): ?><form method="post" class="crm-inline-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="actualizar_reserva"><input type="hidden" name="id" value="<?= (int)$reservation['id_reserva'] ?>"><button class="btn" name="reserva_accion" value="autorizar" <?= $daysLeft<0?'disabled':'' ?>>Autorizar</button><button class="btn btn-outline" name="reserva_accion" value="cancelar">Cancelar</button></form><?php elseif($admin && $reservation['estado']==='Activa'): ?><form method="post" class="crm-inline-form"><?php csrf_field(); ?><input type="hidden" name="accion" value="actualizar_reserva"><input type="hidden" name="id" value="<?= (int)$reservation['id_reserva'] ?>"><select name="estado_pago"><option>Pendiente</option><option>Pagado</option><option>Rechazado</option></select><button class="btn btn-soft" name="reserva_accion" value="pago">Actualizar pago</button><button class="btn btn-outline" name="reserva_accion" value="cancelar">Cancelar</button></form><?php endif; ?>
                </td></tr>
            <?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="admin-empty">No hay reservas registradas.</p><?php endif; ?>
    </div>

    <div class="crm-lists">
        <div class="content-card"><h2>Tareas pendientes</h2><?php foreach($crmData['tasks'] as $task): ?><div class="task-row"><span><strong><?= e($task['titulo']) ?></strong><small><?= e($task['cliente'].' '.$task['apellido'].' · '.date('d/m/Y H:i',strtotime($task['fecha_limite']))) ?></small></span><?php if($task['estado']==='Pendiente'): ?><form method="post"><?php csrf_field(); ?><input type="hidden" name="accion" value="completar_tarea"><input type="hidden" name="id" value="<?= (int)$task['id_tarea'] ?>"><button class="btn btn-soft">Completar</button></form><?php else: ?><span class="status blue"><?= e($task['estado']) ?></span><?php endif; ?></div><?php endforeach; if(!$crmData['tasks']): ?><p class="admin-empty">No hay tareas de seguimiento.</p><?php endif; ?></div>
        <div class="content-card"><h2>Próximas visitas</h2><?php foreach($crmData['visits'] as $visit): ?><div class="task-row"><span><strong><?= e($visit['titulo']) ?></strong><small><?= e($visit['cliente'].' '.$visit['apellido'].' · '.date('d/m/Y H:i',strtotime($visit['fecha_hora']))) ?></small></span><span class="status <?= $visit['estado']==='Programada'?'amber':'blue' ?>"><?= e($visit['estado']) ?></span></div><?php endforeach; if(!$crmData['visits']): ?><p class="admin-empty">No hay visitas agendadas.</p><?php endif; ?></div>
    </div>

    <div class="content-card"><h2>Historial de interacciones</h2><?php if($crmData['interactions']): ?><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Cliente</th><th>Propiedad</th><th>Medio</th><th>Observación</th></tr></thead><tbody><?php foreach($crmData['interactions'] as $interaction): ?><tr><td><?= e(date('d/m/Y H:i',strtotime($interaction['fecha']))) ?></td><td><?= e($interaction['cliente'].' '.$interaction['apellido']) ?></td><td><?= e($interaction['titulo']) ?></td><td><?= e($interaction['tipo']) ?></td><td><?= e($interaction['descripcion']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="admin-empty">Las interacciones que registres aparecerán acá.</p><?php endif; ?></div>
</div>
