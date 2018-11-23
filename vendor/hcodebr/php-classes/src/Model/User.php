<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model {
    const SESSION = "User";

    public static function login($login, $password) {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            "LOGIN" =>  $login
        ));

        if (count($results) === 0) throw new \Exception("Usuário inexistente ou senha inválida.");

        $data = $results[0];

        if (password_verify($password, $data["despassword"])) {
            $user = new User();
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else
            throw new \Exception("Usuário inexistente ou senha inválida.");
    }

    public static function verifyLogin($inadmin = true) {
        if (
            !isset($_SESSION[User::SESSION])                ||
            !$_SESSION[User::SESSION]                       ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0    ||
            (bool)$_SESSION[User::SESSION]["inadmin"] != $inadmin
        ) {
            header("Location: /admin/login");
            exit();
        }
    }

    public static function logout() {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll() {
        $sql = new Sql();
        return $sql->select(
            "SELECT * FROM tb_users as u INNER JOIN tb_persons as p USING(idperson) ORDER BY p.desperson"
        );
    }

    public function save() {
        $sql = new Sql();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":desperson"    =>  $values["desperson"],
                ":deslogin"     =>  $values["deslogin"],
                ":despassword"  =>  $values["despassword"],
                ":desemail"     =>  $values["desemail"],
                ":nrphone"      =>  $values["nrphone"],
                ":inadmin"      =>  $values["inadmin"]
            )
        );

        $this->setData($results[0]);

    }

    public function get($iduser) {
        $sql = new SQL();

        $results = $sql->select(
            "SELECT * FROM tb_users as u INNER JOIN tb_persons as p USING(idperson) WHERE u.iduser = :iduser",
            array(
                ":iduser"   =>  $iduser
            )
        );

        $this->setData($results[0]);
    }

    public function update() {
        $sql = new SQL();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_usersupdate_save (:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":iduser"       =>  $values["iduser"],
                ":desperson"    =>  $values["desperson"],
                ":deslogin"     =>  $values["deslogin"],
                ":despassword"  =>  $values["despassword"],
                ":desemail"     =>  $values["desemail"],
                ":nrphone"      =>  $values["nrphone"],
                ":inadmin"      =>  $values["inadmin"]
            )
        );

        $this->setData($results[0]);
    }

    public function delete() {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete (:iduser)", array(
            ":iduser"   =>  $this->getiduser()
        ));
    }
}