<?php
declare(strict_types=1);

final class Validacion
{
    public static function nombre(string $value): bool
    {
        $value=trim($value);
        return mb_strlen($value)>=2 && mb_strlen($value)<=100
            && preg_match("/^[\\p{L}\\p{M}]+(?:[ .'-][\\p{L}\\p{M}]+)*$/uD",$value)===1;
    }

    public static function dni(string $value): bool
    {
        return preg_match('/^[0-9]{7,10}$/D',trim($value))===1;
    }

    public static function telefono(string $value, bool $required=false): bool
    {
        $value=trim($value);
        if ($value==='') return !$required;
        return mb_strlen($value)<=30
            && preg_match('/^\\+?[0-9\\s().-]{6,30}$/D',$value)===1
            && preg_match_all('/[0-9]/',$value)>=7;
    }

    public static function direccion(string $value): bool
    {
        $value = trim($value);
        return mb_strlen($value) >= 3 && mb_strlen($value) <= 255
            && preg_match('/[\p{L}\p{M}]/u', $value) === 1
            && preg_match("/^[\p{L}\p{M}\p{N}\s.,#º°\/\'-]+$/uD", $value) === 1;
    }

    public static function localidad(string $value): bool
    {
        $value=trim($value);
        return mb_strlen($value)>=2 && mb_strlen($value)<=100
            && preg_match('/[\\p{L}\\p{M}]/u',$value)===1
            && preg_match("/^[\\p{L}\\p{M}\\p{N}\\s.,'-]+$/uD",$value)===1;
    }

    public static function fechaNacimiento(string $value): bool
    {
        $date=DateTime::createFromFormat('!Y-m-d',$value);
        return $date!==false && $date->format('Y-m-d')===$value && $value<=date('Y-m-d');
    }

    public static function email(string $value): bool
    {
        $value=trim($value);
        return mb_strlen($value)<=150 && filter_var($value,FILTER_VALIDATE_EMAIL)!==false;
    }

    public static function genero(string $value): bool
    {
        return in_array($value,['Femenino','Masculino','No binario','Otro','Prefiero no decirlo'],true);
    }

    public static function contrasena(string $value): bool
    {
        return preg_match('/^(?=.{8,255}$)(?=.*\\p{L})(?=.*\\d)(?=.*[^\\p{L}\\d\\s]).+$/uD',$value)===1;
    }
}
