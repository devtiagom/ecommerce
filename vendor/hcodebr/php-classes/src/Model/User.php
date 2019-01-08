<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

/**************************************************************************************************************/

class User extends Model {
    const SESSION           = "User";
    const SECRET            = "HcodePhp7_secret";
    const IV                = "HcodePhp7_codeIV";
    const ERROR             = "UserError";
    const ERROR_REGISTER    = "UserErrorRegister";

    /**********************************************************************************************************/

    public static function getFromSession() {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0)
            $user->setData($_SESSION[User::SESSION]);

        return $user;
    }

    /**********************************************************************************************************/

    public static function login($login, $password) {
        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_users a
            INNER JOIN tb_persons b
            ON a.idperson = b.idperson
            WHERE a.deslogin = :LOGIN
        ", array(
            "LOGIN" =>  $login
        ));

        if (count($results) === 0) throw new \Exception("Usuário inexistente ou senha inválida.");

        $data = $results[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
            $data['desperson'] = utf8_encode($data['desperson']);
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else
            throw new \Exception("Usuário inexistente ou senha inválida.");
    }

    /**********************************************************************************************************/

    public static function checkLogin($inadmin = true) {
        if (
            !isset($_SESSION[User::SESSION])                ||
            !$_SESSION[User::SESSION]                       ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            // Não está logado
            return false;
        } else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                // Está logado, é um administrador e o parâmetro inadmin pede um adm
                return true;
            } else if ($inadmin === false) {
                // Está logado e o parâmetro inadmin não pede um adm
                return true;
            } else {
                return false;
            }
        }

    }

    /**********************************************************************************************************/

    public static function verifyLogin($inadmin = true) {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            
            exit();
        }
    }

    /**********************************************************************************************************/

    public static function logout() {
        $_SESSION[User::SESSION] = NULL;
    }

    /**********************************************************************************************************/

    public static function listAll() {
        $sql = new Sql();

        $results = $sql->select(
            "SELECT * FROM tb_users as u INNER JOIN tb_persons as p USING(idperson) ORDER BY p.desperson"
        );

        for ($i = 0; $i < count($results); $i ++)
            $results[$i]['desperson'] = utf8_encode($results[$i]['desperson']);

        return $results;
    }

    /**********************************************************************************************************/

    public function save() {
        $sql = new Sql();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":desperson"    =>  utf8_decode($values["desperson"]),
                ":deslogin"     =>  $values["deslogin"],
                ":despassword"  =>  User::getPasswordHash($values["despassword"]),
                ":desemail"     =>  $values["desemail"],
                ":nrphone"      =>  $values["nrphone"],
                ":inadmin"      =>  $values["inadmin"]
            )
        );

        $this->setData($results[0]);

    }

    /**********************************************************************************************************/

    public function get($iduser) {
        $sql = new SQL();

        $results = $sql->select(
            "SELECT * FROM tb_users as u INNER JOIN tb_persons as p USING(idperson) WHERE u.iduser = :iduser",
            array(
                ":iduser"   =>  $iduser
            )
        );

        $data = $results[0];
        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setData($data);
    }

    /**********************************************************************************************************/

    public function update() {
        $sql = new SQL();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_usersupdate_save (:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":iduser"       =>  $values["iduser"],
                ":desperson"    =>  utf8_decode($values["desperson"]),
                ":deslogin"     =>  $values["deslogin"],
                ":despassword"  =>  $values["despassword"],
                ":desemail"     =>  $values["desemail"],
                ":nrphone"      =>  $values["nrphone"],
                ":inadmin"      =>  $values["inadmin"]
            )
        );

        $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public function delete() {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete (:iduser)", array(
            ":iduser"   =>  $this->getiduser()
        ));
    }

    /**********************************************************************************************************/

    public static function getForgot($userEmail) {
        $sql = new SQL();

        $results = $sql->select(
            "SELECT * FROM tb_persons p INNER JOIN tb_users u USING(idperson) WHERE p.desemail = :email",
            array(
                ":email"    =>  $userEmail
            )
        );

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            $data = $results[0];

            $usersRecoveries = $sql->select("CALL sp_userspasswordsrecoveries_create (:iduser, :desip)", array(
                ":iduser"   =>  $data["iduser"],
                ":desip"    =>  $_SERVER["REMOTE_ADDR"]
            ));

            if (count($usersRecoveries) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                $dataRecovery = $usersRecoveries[0];

                $code = base64_encode(openssl_encrypt(
                    $dataRecovery["idrecovery"],
                    'AES-128-CBC',
                    User::SECRET,
                    0,
                    User::IV
                ));

                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer(
                    $data["desemail"],
                    $data["desperson"],
                    "Redefinir senha da Hcode Store",
                    "forgot",
                    array(
                        "name"  =>  $data["desperson"],
                        "link"  =>  $link
                    )
                );

                $mailer->send();

                return $data;
            }
        }
    }

    /**********************************************************************************************************/

    public static function validForgotDecrypt($code) {
        $idRecovery = openssl_decrypt(
            base64_decode($code),
            'AES-128-CBC',
            User::SECRET,
            0,
            User::IV
        );

        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM	tb_userspasswordsrecoveries	a
            INNER JOIN		tb_users					b	USING(iduser)
            INNER JOIN		tb_persons					c	USING(idperson)
            WHERE			a.idrecovery = :idrecovery	AND
                            a.dtrecovery IS NULL		AND
                            DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
        ", array(
            ":idrecovery"   =>  $idRecovery
        ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha.");
        } else {
            return $results[0];
        }
    }

    /**********************************************************************************************************/

    public static function setForgotUsed($idRecovery) {
        $sql = new Sql();

        $sql->query(
            "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
            array(":idrecovery" =>  $idRecovery)
        );
    }

    /**********************************************************************************************************/

    public function setPassword($password) {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password" =>  $password,
            ":iduser"   =>  $this->getiduser()
        ));
    }

    /**********************************************************************************************************/

    public static function setError($msg) {
        $_SESSION[User::ERROR] = $msg;
    }

    /**********************************************************************************************************/

    public static function getError() {
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

        User::clearError();

        return $msg;
    }

    /**********************************************************************************************************/

    public static function clearError() {
        $_SESSION[User::ERROR] = NULL;
    }

    /**********************************************************************************************************/

    public static function setErrorRegister() {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    /**********************************************************************************************************/

    public static function checkLoginExist($login) {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
            ':deslogin' =>  $login
        ]);

        return (count($results) > 0);
    }

    /**********************************************************************************************************/

    public static function getPasswordHash($password) {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost'  =>  12
        ]);
    }
    
}