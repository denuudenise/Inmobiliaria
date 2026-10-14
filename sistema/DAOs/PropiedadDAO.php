<?php
declare(strict_types=1);

final class PropiedadDAO
{
    public function __construct(private PDO $db) {}

    public function findPublished(array $filters = [], int $limit = 40): array
    {
        $where = [
            'p.id_estado_verificacion = 3',
            'p.id_estado_comercial = 1',
        ];
        $params = [];

        if (!empty($filters['location'])) {
            $where[] = '(p.localidad LIKE :location_city OR p.direccion LIKE :location_address OR p.titulo LIKE :location_title)';
            $params['location_city'] = '%' . trim($filters['location']) . '%';
            $params['location_address'] = $params['location_city'];
            $params['location_title'] = $params['location_city'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'tp.nombre = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['operation'])) {
            $where[] = 'tt.nombre = :operation';
            $params['operation'] = $filters['operation'];
        }

        if (!empty($filters['currency']) && in_array($filters['currency'], ['USD','ARS'], true)) {
            $where[] = 'p.moneda = :currency'; $params['currency'] = $filters['currency'];
        }
        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float)$filters['max_price'] > 0) {
            $where[] = 'p.precio <= :max_price'; $params['max_price'] = $filters['max_price'];
        }
        if (!empty($filters['bedrooms'])) { $where[] = 'p.dormitorios >= :bedrooms'; $params['bedrooms'] = (int)$filters['bedrooms']; }
        $limit = max(1, min($limit, 100));
        $sql = 'SELECT p.id_propiedad, p.titulo, p.direccion, p.localidad, p.precio,
                       p.moneda, p.descripcion, p.superficie_total_m2, p.ambientes,
                       p.dormitorios, p.banos, tp.nombre AS tipo, tt.nombre AS operacion,
                       (SELECT pm.url_archivo FROM propiedad_multimedia pm
                        WHERE pm.id_propiedad = p.id_propiedad AND pm.tipo = "Imagen"
                        ORDER BY pm.orden, pm.id_multimedia LIMIT 1) AS imagen
                FROM propiedades p
                INNER JOIN tipos_propiedad tp ON tp.id_tipo_propiedad = p.id_tipo_propiedad
                INNER JOIN tipos_transaccion tt ON tt.id_tipo_transaccion = p.id_tipo_transaccion
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.fecha_creacion DESC LIMIT ' . $limit;
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findById(int $propertyId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id_propiedad, p.titulo, p.direccion, p.localidad, p.precio, p.moneda,
                    p.descripcion, p.superficie_total_m2, p.superficie_cubierta_m2,
                    p.antiguedad_anos, p.ambientes, p.dormitorios, p.banos,
                    tp.nombre AS tipo, tt.nombre AS operacion
             FROM propiedades p
             INNER JOIN tipos_propiedad tp ON tp.id_tipo_propiedad = p.id_tipo_propiedad
             INNER JOIN tipos_transaccion tt ON tt.id_tipo_transaccion = p.id_tipo_transaccion
             WHERE p.id_propiedad = :id AND p.id_estado_verificacion = 3
               AND p.id_estado_comercial = 1 LIMIT 1'
        );
        $statement->execute(['id' => $propertyId]);
        $property = $statement->fetch();
        return $property ?: null;
    }

    public function findImages(int $propertyId): array
    {
        $statement = $this->db->prepare(
            'SELECT url_archivo, tipo FROM propiedad_multimedia
             WHERE id_propiedad = :id ORDER BY orden, id_multimedia'
        );
        $statement->execute(['id' => $propertyId]);
        return $statement->fetchAll();
    }

    public function findClosedOperationsForClient(int $clientId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.titulo, p.localidad, pc.precio_final, pc.fecha_cierre,
                    tt.nombre AS operacion, u.nombre AS agente_nombre, u.apellido AS agente_apellido
             FROM propiedades_cerradas pc
             INNER JOIN propiedades p ON p.id_propiedad = pc.id_propiedad
             INNER JOIN tipos_transaccion tt ON tt.id_tipo_transaccion = p.id_tipo_transaccion
             INNER JOIN usuarios u ON u.id_usuario = pc.id_agente_cierre
             WHERE pc.id_cliente_comprador = :id
             ORDER BY pc.fecha_cierre DESC'
        );
        $statement->execute(['id' => $clientId]);
        return $statement->fetchAll();
    }

    public function findRentalsForClient(int $clientId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.titulo, p.localidad, a.monto_mensual, a.fecha_inicio, a.fecha_fin,
                    a.estado, u.nombre AS agente_nombre, u.apellido AS agente_apellido
             FROM alquileres a
             INNER JOIN propiedades p ON p.id_propiedad = a.id_propiedad
             INNER JOIN usuarios u ON u.id_usuario = a.id_agente
             WHERE a.id_cliente = :id ORDER BY a.fecha_inicio DESC'
        );
        $statement->execute(['id' => $clientId]);
        return $statement->fetchAll();
    }
}

