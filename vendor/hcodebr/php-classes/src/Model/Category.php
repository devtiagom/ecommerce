<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

/**************************************************************************************************************/

class Category extends Model {
    
    /**********************************************************************************************************/

    public static function listAll() {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    /**********************************************************************************************************/

    public function save() {
        $sql = new Sql();

        $values = $this->getValues();

        $results = $sql->select(
            "CALL sp_categories_save (:idcategory, :descategory)",
            array(
                ":idcategory"   =>  $values["idcategory"] ?? NULL,
                ":descategory"  =>  $values["descategory"]
            )
        );

        $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public function get($idCategory) {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory"   =>  $idCategory
        ));

        $this->setData($results[0]);
    }

    /**********************************************************************************************************/

    public function delete() {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory"   =>  $this->getidcategory()
        ));
    }

}