<?php
declare(strict_types=1);

final class RecuperacionDAO
{
    public function __construct(private PDO $db) {}

    public function solicitar(string $email, string $ip, callable $enviar): void
    {
        $email = mb_strtolower(trim($email));
        $this->db->beginTransaction();
        try {
            $permitido = true;
            $now = time();
            foreach (['ip:' . $ip => 20, 'email:' . $email => 3] as $key => $max) {
                $hash = hash('sha256', $key);
                $this->db->prepare('INSERT IGNORE INTO limites_recuperacion (clave,inicio,intentos) VALUES (?,?,0)')->execute([$hash,$now]);
                $q = $this->db->prepare('SELECT inicio,intentos FROM limites_recuperacion WHERE clave=? FOR UPDATE');
                $q->execute([$hash]);
                $row = $q->fetch(PDO::FETCH_ASSOC);
                $count = $now - (int)$row['inicio'] >= 3600 ? 0 : (int)$row['intentos'];
                $start = $count === 0 ? $now : (int)$row['inicio'];
                if ($count >= $max) $permitido = false;
                $this->db->prepare('UPDATE limites_recuperacion SET inicio=?,intentos=? WHERE clave=?')
                    ->execute([$start, min($count + 1, $max + 1), $hash]);
            }
            $this->db->commit();
            if (!$permitido) return;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }

        $q = $this->db->prepare('SELECT id_usuario,email,password_hash FROM usuarios WHERE email=? AND activo=1');
        $q->execute([$email]);
        $user = $q->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $this->db->prepare('INSERT INTO recuperaciones_password (id_usuario,token_hash,password_anterior_hash,expira) VALUES (?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))')
            ->execute([$user['id_usuario'], $hash, hash('sha256', $user['password_hash'])]);
        try {
            $enviar($user['email'], $token);
        } catch (Throwable $error) {
            $this->db->prepare('DELETE FROM recuperaciones_password WHERE token_hash=?')->execute([$hash]);
            // The public response is identical for registered and unknown addresses.
            error_log('No se pudo enviar un correo de recuperación. Revisar SMTP.');
        }
    }

    public function buscar(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $q = $this->db->prepare('SELECT r.*,u.password_hash FROM recuperaciones_password r JOIN usuarios u ON u.id_usuario=r.id_usuario WHERE r.token_hash=? AND r.usado IS NULL AND r.expira>UTC_TIMESTAMP() AND u.activo=1');
        $q->execute([hash('sha256', $token)]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row && hash_equals($row['password_anterior_hash'], hash('sha256',$row['password_hash'])) ? $row : null;
    }

    public function restablecer(string $token, string $password): bool
    {
        if (!Validacion::contrasena($password)) throw new InvalidArgumentException('Contraseña inválida.');
        $initial = $this->buscar($token);
        if (!$initial) return false;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->beginTransaction();
        try {
            // Serialize resets for the same account, including different valid links.
            $q = $this->db->prepare('SELECT password_hash,activo FROM usuarios WHERE id_usuario=? FOR UPDATE');
            $q->execute([$initial['id_usuario']]);
            $user = $q->fetch(PDO::FETCH_ASSOC);
            $q = $this->db->prepare('SELECT id FROM recuperaciones_password WHERE id=? AND usado IS NULL AND expira>UTC_TIMESTAMP() FOR UPDATE');
            $q->execute([$initial['id']]);
            if (!$q->fetchColumn() || !$user || !$user['activo']
                || !hash_equals($initial['password_anterior_hash'], hash('sha256',$user['password_hash']))) {
                $this->db->rollBack();
                return false;
            }
            $this->db->prepare('UPDATE usuarios SET password_hash=? WHERE id_usuario=?')->execute([$newHash,$initial['id_usuario']]);
            $this->db->prepare('UPDATE recuperaciones_password SET usado=UTC_TIMESTAMP() WHERE id_usuario=? AND usado IS NULL')->execute([$initial['id_usuario']]);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
