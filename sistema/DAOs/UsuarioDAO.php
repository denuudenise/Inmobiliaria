<?php
declare(strict_types=1);

final class UsuarioDAO
{
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT u.id_usuario, u.nombre, u.apellido, u.dni, u.telefono, u.email,
                    u.password_hash, u.activo, r.nombre AS rol
             FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.email = :email LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id_usuario, nombre, apellido, dni, telefono, email, fecha_registro
             FROM usuarios WHERE id_usuario = :id AND activo = 1 LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function updateProfile(int $userId, string $name, string $surname, string $phone, string $email): void
    {
        $statement = $this->db->prepare(
            'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, telefono = :telefono, email = :email
             WHERE id_usuario = :id'
        );
        $statement->execute([
            'nombre' => trim($name),
            'apellido' => trim($surname),
            'telefono' => trim($phone),
            'email' => mb_strtolower(trim($email)),
            'id' => $userId,
        ]);
    }

    public function createClient(string $name, string $surname, string $dni, string $phone, string $email, string $passwordHash): int
    {
        $role = $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'Cliente'")->fetchColumn();
        if (!$role) {
            throw new RuntimeException('No está configurado el rol Cliente.');
        }
        $statement = $this->db->prepare(
            'INSERT INTO usuarios (nombre, apellido, dni, telefono, email, password_hash, id_rol, activo)
             VALUES (:nombre, :apellido, :dni, :telefono, :email, :password, :rol, 1)'
        );
        $statement->execute([
            'nombre' => trim($name),
            'apellido' => trim($surname),
            'dni' => trim($dni),
            'telefono' => trim($phone),
            'email' => mb_strtolower(trim($email)),
            'password' => $passwordHash,
            'rol' => $role,
        ]);
        return (int)$this->db->lastInsertId();
    }
}

