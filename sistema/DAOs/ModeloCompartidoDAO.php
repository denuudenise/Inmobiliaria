<?php
declare(strict_types=1);

/** Escrituras relacionadas: el llamador debe incluirlas en su transacción. */
final class ModeloCompartidoDAO
{
    public function __construct(private PDO $db) {}

    public function client(int $id, ?string $role = null): void
    {
        $this->db->prepare('INSERT INTO clientes (id_cliente,fecha_inicio_relacion) VALUES (?,CURRENT_DATE) ON DUPLICATE KEY UPDATE id_cliente=VALUES(id_cliente)')->execute([$id]);
        // El rol comercial se conoce al solicitar una publicación o cerrar una operación.
        if ($role !== null) $this->db->prepare('INSERT INTO roles_cliente (id_cliente,tipo_rol) VALUES (?,?) ON DUPLICATE KEY UPDATE tipo_rol=VALUES(tipo_rol)')->execute([$id,$role]);
    }

    public function property(int $id, int $applicant): void
    {
        $q=$this->db->prepare('SELECT * FROM propiedades WHERE id_propiedad=? FOR UPDATE');
        $q->execute([$id]); $p=$q->fetch();
        if (!$p) throw new RuntimeException('Propiedad inexistente.');
        // Se actualiza la oferta principal sin eliminar otras operaciones de la misma propiedad.
        $this->db->prepare('INSERT INTO propiedad_ofertas (id_propiedad,id_tipo_transaccion,precio,moneda) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE precio=VALUES(precio),moneda=VALUES(moneda)')->execute([$id,$p['id_tipo_transaccion'],$p['precio'],$p['moneda']]);
        $state=match((int)$p['id_estado_verificacion']) {3=>'Publicada',4=>'Rechazada',5=>'Inactiva',default=>'Pendiente'};
        $reviewer=$state==='Pendiente'?null:$p['id_admin_verificador'];
        $q=$this->db->prepare('SELECT id_publicacion FROM publicaciones WHERE id_propiedad=? ORDER BY id_publicacion DESC LIMIT 1 FOR UPDATE');
        $q->execute([$id]); $publication=$q->fetchColumn();
        if ($publication) {
            $this->db->prepare('UPDATE publicaciones SET estado=?,id_revisor=?,fecha_revision=IF(? IS NULL,NULL,NOW()) WHERE id_publicacion=?')->execute([$state,$reviewer,$reviewer,$publication]);
        } else {
            $this->db->prepare('INSERT INTO publicaciones (id_propiedad,id_solicitante,estado,id_revisor,fecha_revision) VALUES (?,?,?,?,IF(? IS NULL,NULL,NOW()))')->execute([$id,$applicant,$state,$reviewer,$reviewer]);
        }
    }
}
