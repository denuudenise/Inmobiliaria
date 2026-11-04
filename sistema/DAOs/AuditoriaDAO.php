<?php
declare(strict_types=1);

final class AuditoriaDAO
{
    public function __construct(private PDO $db) {}

    public function record(int $userId, string $action, string $entity, int $recordId, string $detail = '', array $changes = []): int
    {
        $q = $this->db->prepare('INSERT INTO auditoria (id_usuario,accion,entidad,id_registro,detalle) VALUES (?,?,?,?,?)');
        $q->execute([$userId, mb_substr($action, 0, 100), mb_substr($entity, 0, 40), $recordId, $detail !== '' ? $detail : null]);
        $eventId = (int)$this->db->lastInsertId();
        if ($changes) {
            $change = $this->db->prepare('INSERT INTO auditoria_cambios (id_evento,entidad,id_registro,campo,valor_anterior,valor_nuevo,motivo) VALUES (?,?,?,?,?,?,?)');
            foreach ($changes as $field => $values) {
                $change->execute([$eventId, mb_substr($entity, 0, 40), $recordId, mb_substr((string)$field, 0, 80), $values['anterior'] ?? null, $values['nuevo'] ?? null, isset($values['motivo']) ? mb_substr((string)$values['motivo'], 0, 500) : null]);
            }
        }
        return $eventId;
    }
}
