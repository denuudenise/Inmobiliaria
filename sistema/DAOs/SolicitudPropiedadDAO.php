<?php
declare(strict_types=1);

final class SolicitudPropiedadDAO
{
    public function __construct(private PDO $db) {}

    public function create(int $clientId, array $data, array $files = [], array $privateData = []): int
    {
        $planosErrors=$_FILES['planos']['error']??null;
        $escriturasErrors=$_FILES['escrituras']['error']??null;
        $hasPlanos=is_array($planosErrors) && in_array(UPLOAD_ERR_OK,$planosErrors,true);
        $hasEscritura=is_array($escriturasErrors) && in_array(UPLOAD_ERR_OK,$escriturasErrors,true);
        if (!$hasPlanos && empty($privateData['faltan_planos'])) throw new RuntimeException('Adjuntá los planos o indicá que todavía no los tenés.');
        if (!$hasEscritura && empty($privateData['falta_escritura'])) throw new RuntimeException('Adjuntá la escritura o indicá que todavía no la tenés.');
        $data=array_merge($data,array_intersect_key($privateData,array_flip(['ofertas_form','ofertas','caracteristicas_form','caracteristicas'])));
        $data=(new OfertaDAO($this->db))->normalize($data,true);
        $expediente=new ExpedienteDAO($this->db);
        $this->db->beginTransaction();
        try {
            $typeQuery = $this->db->prepare('SELECT id_tipo_propiedad FROM tipos_propiedad WHERE nombre = :nombre');
            $typeQuery->execute(['nombre' => $data['tipo']]);
            $typeId = $typeQuery->fetchColumn();
            $operationQuery = $this->db->prepare('SELECT id_tipo_transaccion FROM tipos_transaccion WHERE nombre = :nombre');
            $operationQuery->execute(['nombre' => $data['operacion']]);
            $operationId = $operationQuery->fetchColumn();
            if (!$typeId || !$operationId) {
                throw new RuntimeException('El tipo de propiedad o de operación no está configurado.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO solicitudes_publicacion
                 (id_cliente, id_tipo_propiedad, id_tipo_transaccion, id_estado_verificacion,
                  titulo, direccion, localidad,
                  descripcion, precio_estimado, superficie_total_m2, ambientes, dormitorios, banos,
                  antiguedad_anos)
                 VALUES (:cliente, :tipo, :operacion, 2, :titulo, :direccion, :localidad,
                  :descripcion, :precio, :superficie, :ambientes, :dormitorios, :banos, :antiguedad)'
            );
            $insert->execute([
                'cliente' => $clientId,
                'tipo' => $typeId,
                'operacion' => $operationId,
                'titulo' => trim($data['titulo']),
                'direccion' => trim($data['direccion']),
                'localidad' => trim($data['localidad']),
                'descripcion' => trim($data['descripcion']),
                'precio' => $data['precio'] === '' ? null : $data['precio'],
                'superficie' => $data['superficie'],
                'ambientes' => $data['ambientes'] === '' ? null : $data['ambientes'],
                'dormitorios' => $data['dormitorios'] === '' ? null : $data['dormitorios'],
                'banos' => $data['banos'] === '' ? null : $data['banos'],
                'antiguedad' => $data['antiguedad'] === '' ? 0 : $data['antiguedad'],
            ]);
            $requestId = (int)$this->db->lastInsertId();
            $currency=$this->db->prepare('UPDATE solicitudes_publicacion SET moneda=? WHERE id_solicitud=?');
            $currency->execute([$data['moneda']??'USD',$requestId]);

            if ($files) {
                $media = $this->db->prepare(
                    'INSERT INTO solicitud_multimedia (id_solicitud, url_archivo, tipo)
                     VALUES (:solicitud, :url, :tipo)'
                );
                foreach ($files as $file) {
                    $media->execute([
                        'solicitud' => $requestId,
                        'url' => $file['path'],
                        'tipo' => $file['type'],
                    ]);
                }
            }

            (new OfertaDAO($this->db))->save('solicitud',$requestId,$data);
            $expediente->save(null,$requestId,$clientId,$privateData);
            (new ModeloCompartidoDAO($this->db))->client($clientId,'Propietario');
            $this->db->commit();
            return $requestId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $expediente->cleanup();
            throw $error;
        }
    }

    public function findForClient(int $clientId): array
    {
        $statement = $this->db->prepare(
            'SELECT s.id_solicitud, s.titulo, s.localidad, ev.nombre AS estado, s.motivo_rechazo,
                    s.fecha_solicitud, tp.nombre AS tipo, tt.nombre AS operacion
             FROM solicitudes_publicacion s
             INNER JOIN tipos_propiedad tp ON tp.id_tipo_propiedad = s.id_tipo_propiedad
             INNER JOIN tipos_transaccion tt ON tt.id_tipo_transaccion = s.id_tipo_transaccion
             INNER JOIN estados_verificacion ev ON ev.id_estado_verificacion = s.id_estado_verificacion
             WHERE s.id_cliente = :id ORDER BY s.fecha_solicitud DESC'
        );
        $statement->execute(['id' => $clientId]);
        return $statement->fetchAll();
    }
}

