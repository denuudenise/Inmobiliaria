<?php
declare(strict_types=1);

final class CorreoRecuperacion
{
    public function __construct(private array $config) {}

    public function comprobar(): void
    {
        $url = $this->config['base_url'];
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $local = in_array($host, ['localhost', '127.0.0.1'], true);
        if (!filter_var($this->config['username'], FILTER_VALIDATE_EMAIL)
            || $this->config['password'] === ''
            || !filter_var($url, FILTER_VALIDATE_URL)
            || ($scheme !== 'https' && !($scheme === 'http' && $local))
            || parse_url($url, PHP_URL_QUERY) || parse_url($url, PHP_URL_FRAGMENT)
            || parse_url($url, PHP_URL_USER)) {
            throw new RuntimeException('Configuración de correo incompleta.');
        }
    }

    public function enviar(string $email, string $token, string $idioma): void
    {
        $this->comprobar();
        foreach (['Exception', 'PHPMailer', 'SMTP'] as $class) {
            require_once __DIR__ . '/../Externos/PHPMailer/' . $class . '.php';
        }
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = (int)$this->config['port'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($this->config['username'], $this->config['from_name']);
        $mail->addAddress($email);
        $url = rtrim($this->config['base_url'], '/') . '/restablecer-password.php?token=' . rawurlencode($token);
        $english = $idioma === 'en';
        $mail->Subject = $english ? 'Reset your password — Nuevo Hogar' : 'Restablecer contraseña — Nuevo Hogar';
        $mail->Body = ($english
            ? "Use this link to choose a new password. It expires in 30 minutes and can be used once.\n\n"
            : "Usá este enlace para elegir una nueva contraseña. Vence en 30 minutos y se puede usar una sola vez.\n\n")
            . $url . "\n\n" . ($english
                ? 'If you did not request this, you can ignore this email.'
                : 'Si no solicitaste este cambio, podés ignorar este correo.');
        $mail->send();
    }
}
