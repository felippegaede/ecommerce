<?php


namespace Hcode;

use Rain\Tpl;
use \Hcode\Model\Cart;

class Page
{

    private $options = [];
    private $tpl; 
    private $defaults = [
        "header"=>true,
        "footer"=>true,
        "data"=>[],

    ];
    
    public function __construct($opts = array(), $tpl_dir = "/views/")
        {

            $this->options = array_merge($this->defaults , $opts);
            $config = array(
                "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
                "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",   
                "debug"         => false          
            );

            Tpl::configure( $config );

            $this->tpl = new Tpl;

            $cart = Cart::getFromSession();

            $this->setData($this->options["data"]);
            
            $this->setData(array(
                "cart"=>$cart->getValues(),                
            ));
 
            if ($this->options["header"]) $this->tpl->draw("header");

        }

        private function setData($data = array())
        {
            foreach ($data as $key => $value) {
                $this->tpl->assign($key , $value);
            } 
        }

        public function setTpl($name , $data = array() , $returnHTML = false)
        {

            $this->setData($data);

            return $this->tpl->draw($name, $returnHTML);

        }

        public function __destruct()
        {
            
            if ($this->options["footer"]) $this->tpl->draw("footer");

        }

}