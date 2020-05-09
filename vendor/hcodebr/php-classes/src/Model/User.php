<?php

namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Mailer;
use \Hcode\Model;

class User extends Model
{

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";

    public static function getFromSession ()
    {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]["iduser"] > 0)
        {          

            $user->setData($_SESSION[User::SESSION]);
            
        }

        return $user;
    }

    public static function checkLogin($inadmin = true)
    {
        if 
        (
            !isset($_SESSION[User::SESSION]) 
            || 
            !$_SESSION[User::SESSION] 
            || 
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        )
        {
            return false;
        }
        else 
        {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true)
            {
                return true;
            }
            else if ($inadmin === false)
            {
                return true;
            }
            else 
            {
               return false; 
            }
        }
    }


    public static function login ($login , $password)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(

            ":LOGIN"=>$login
        ));

        if (count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida.",1);
        }

        $data = $results[0];

        if (password_verify($password,$data["despassword"]) === true)
        {

            $user = new User();

            $user->setData($data);            

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;

        } else {throw new \Exception("Usuário inexistente ou senha inválida.",1);}        

    } 

    public static function verifyLogin($inadmin = true)
    {

        if
        (!User::checkLogin($inadmin))
        {
            if ($inadmin)
            {
                header("Location: /admin/login/");
                exit;
            }
            else
            {
                header("Location: /login");
                exit;
            }           

        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll()
    {

        $sql = new Sql;

        return $sql->select("SELECT * FROM  tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");


    }

    public function get($iduser)
    {
 
    $sql = new Sql();
 
    $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser;", array(
    ":iduser"=>$iduser
    ));
 
    $data = $results[0];
 
    $this->setData($data);
 
    }

 public function save()
 {

    $sql = new Sql;

    $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail,
    :nrphone, :inadmin)", array(
       
        ":desperson"=>$this->getdesperson(),
        ":deslogin"=>$this->getdeslogin(),
        ":despassword"=>password_hash($this->getdespassword(),PASSWORD_DEFAULT,["cost"=>12]),
        ":desemail"=>$this->getdesemail(),
        ":nrphone"=>$this->getnrphone(),
        ":inadmin"=>$this->getinadmin(),          
        
    ));

     $this->setData($results[0]);

 }

 public function update()
 {

    $sql = new Sql;

    $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail,
    :nrphone, :inadmin)", array(
        ":iduser"=>$this->getiduser(),
        ":desperson"=>$this->getdesperson(),
        ":deslogin"=>$this->getdeslogin(),
        ":despassword"=>$this->getdespassword(),
        ":desemail"=>$this->getdesemail(),
        ":nrphone"=>$this->getnrphone(),
        ":inadmin"=>$this->getinadmin(),     
    ));

    $this->setData($results[0]);
 }

 public function delete()
 {
    $sql = new Sql;

    $sql->query("CALL sp_users_delete(:iduser)",array(
        "iduser"=>$this->getiduser()
    ));
 }

 public static function getForgot($email)
 {
    $sql = new SQL;
    
    $results = $sql->select(
       "SELECT * 
        FROM tb_persons a 
        INNER JOIN tb_users b USING(idperson)
        WHERE a.desemail = :email;", 
        array(
            ":email"=>$email
        )
    );   
    
    if (count($results)===0)
    {
        throw new \Exception("Não foi possível recuperar a senha.");
    }
    else
    {
        $data = $results[0];

        $resultsRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser,:desip)", array(
            ":iduser" => $data["iduser"],
            ":desip"  => $_SERVER["REMOTE_ADDR"]
        ));

        if (count($resultsRecovery)===0)
        {
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else
        {
            $dataRecovery = $resultsRecovery[0];

            $cipher="AES-128-ECB";
            $code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"],$cipher,USER::SECRET));

            $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

            $mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir senha","forgot",
        array(
            "name"=>$data["desperson"],
            "link"=> $link
        ));

        $mailer->send();

        return $data;

        }
    }    
 }

 public static function validForgotDecrypt($code)
 {    
    $cipher="AES-128-ECB";
    $idrecovery = openssl_decrypt(base64_decode($code),$cipher,USER::SECRET);

    $sql = new Sql;
    $results = $sql->select(
    "SELECT * 
    FROM tb_userspasswordsrecoveries a
    INNER JOIN tb_users b USING(iduser)
    INNER JOIN tb_persons c USING(idperson)
    WHERE 
    a.idrecovery = :idrecovery
    AND
    a.dtrecovery IS NULL
    AND 
    DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();",array(
    ":idrecovery"=>$idrecovery    
    ));

    if(count($results) === 0)
    {
        throw new \Exception("Não foi possível recuperar a senha.");
    }

    else
    {
        return $results[0];
    }
 }

 public static function setForgotUsed($idrecovery)
 {
    $sql = new Sql;

    $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
array(
    ":idrecovery"=>$idrecovery
));
 }

 public function setPassword($password)
 {
    $sql = new Sql;

    $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
        ":password"=>$password,
        ":iduser"=>$this->getiduser()
    ));

 }

 public static function setError($msg)
 {
     $_SESSION[USer::ERROR] = $msg;
 }

 public static function getError()
 {
     $msg =  (isset($_SESSION[USer::ERROR]) && $_SESSION[USer::ERROR]) ? $_SESSION[USer::ERROR] : "";

     USer::clearError();

     return $msg;

 }

 public static function clearError()
 {
     $_SESSION[USer::ERROR] = NULL;
 }


 public static function setRegisterError($msg)
 {
     $_SESSION[USer::ERROR_REGISTER] = $msg;
 }

 public static function getRegisterError()
 {
     $msg =  (isset($_SESSION[USer::ERROR_REGISTER]) && $_SESSION[USer::ERROR_REGISTER]) ? $_SESSION[USer::ERROR_REGISTER] : "";

     USer::clearRegisterError();

     return $msg;

 }

 public static function clearRegisterError()
 {
     $_SESSION[USer::ERROR_REGISTER] = NULL;
 }

 public static function checkLoginExist($login)
 {

     $sql = new Sql();

     $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
         ':deslogin'=>$login
     ]);

     return (count($results) > 0);

 }

 

}