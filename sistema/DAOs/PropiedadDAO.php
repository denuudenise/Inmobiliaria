<?php
declare(strict_types=1);

final class PropiedadDAO
{
    public function __construct(private PDO $db) {}

    public function findPublished(array $filters = [], int $limit = 40): array
    {
        $where=['p.id_estado_verificacion=3','p.id_estado_comercial=1']; $params=[];
        if (!empty($filters['location'])) { $where[]='(p.localidad LIKE ? OR p.direccion LIKE ? OR p.titulo LIKE ?)';$value='%'.trim($filters['location']).'%';array_push($params,$value,$value,$value); }
        if (!empty($filters['type'])) { $where[]='tp.nombre=?';$params[]=$filters['type']; }
        if (!empty($filters['bedrooms'])) { $where[]='p.dormitorios>=?';$params[]=(int)$filters['bedrooms']; }
        $offerWhere=['o.id_propiedad=p.id_propiedad'];$offerParams=[];
        if (!empty($filters['operation'])) { $offerWhere[]='t.nombre=?';$offerParams[]=$filters['operation']; }
        if (!empty($filters['currency']) && in_array($filters['currency'],['ARS','USD'],true)) { $offerWhere[]='o.moneda=?';$offerParams[]=$filters['currency']; }
        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float)$filters['max_price']>0) { $offerWhere[]='o.precio<=?';$offerParams[]=$filters['max_price']; }
        $where[]='EXISTS (SELECT 1 FROM propiedad_ofertas o JOIN tipos_transaccion t ON t.id_tipo_transaccion=o.id_tipo_transaccion WHERE '.implode(' AND ',$offerWhere).')';
        $sql='SELECT p.*,tp.nombre AS tipo,(SELECT url_archivo FROM propiedad_multimedia pm WHERE pm.id_propiedad=p.id_propiedad AND pm.tipo="Imagen" ORDER BY pm.orden,pm.id_multimedia LIMIT 1) AS imagen FROM propiedades p JOIN tipos_propiedad tp ON tp.id_tipo_propiedad=p.id_tipo_propiedad WHERE '.implode(' AND ',$where).' ORDER BY p.fecha_creacion DESC LIMIT '.max(1,min($limit,100));
        $q=$this->db->prepare($sql);$q->execute([...$params,...$offerParams]);$rows=$q->fetchAll();
        foreach($rows as &$row){
            $row['ofertas']=array_values(array_filter((new OfertaDAO($this->db))->read('propiedad',(int)$row['id_propiedad']),static function($o)use($filters){
                return (empty($filters['operation'])||$o['nombre']===$filters['operation']) && (empty($filters['currency'])||!in_array($filters['currency'],['ARS','USD'],true)||$o['moneda']===$filters['currency']) && (!isset($filters['max_price'])||!is_numeric($filters['max_price'])||(float)$filters['max_price']<=0||(float)$o['precio']<=(float)$filters['max_price']);
            }));
            $first=$row['ofertas'][0];$row['precio']=$first['precio'];$row['moneda']=$first['moneda'];$row['operacion']=implode(' · ',array_column($row['ofertas'],'nombre'));
        }
        return $rows;
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
            'SELECT p.titulo, p.localidad, pc.precio_final, pc.moneda, pc.fecha_cierre,
                    tt.nombre AS operacion, u.nombre AS agente_nombre, u.apellido AS agente_apellido
             FROM propiedades_cerradas pc
             INNER JOIN propiedades p ON p.id_propiedad = pc.id_propiedad
             INNER JOIN tipos_transaccion tt ON tt.id_tipo_transaccion = COALESCE(pc.id_tipo_transaccion,p.id_tipo_transaccion)
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
            'SELECT p.titulo, p.localidad, a.monto_mensual,
                    COALESCE((SELECT po.moneda FROM propiedad_ofertas po WHERE po.id_propiedad=a.id_propiedad AND po.id_tipo_transaccion IN (2,3) ORDER BY po.id_tipo_transaccion LIMIT 1),p.moneda) AS moneda,
                    a.fecha_inicio, a.fecha_fin,
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

