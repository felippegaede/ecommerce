<?php

use \Hcode\Model\Category;
use \Hcode\Model\User;
use \Hcode\PageAdmin;

$app->get("/admin/categories/",function(){

	User::verifyLogin();
	
	$categories = Category::listAll();

	$page = new PageAdmin();
	$page->setTpl("categories",array(
		"categories"=>$categories
	));
});

$app->get("/admin/categories/create/" , function(){

	User::verifyLogin();	

	$page = new PageAdmin();
	$page->setTpl("categories-create");

});

$app->post("/admin/categories/create/" , function(){

	User::verifyLogin();

	$categories = new Category;

	$categories->setData($_POST);

	$categories->save();

	header("Location: /admin/categories/");
	exit;
});

$app->get("/admin/categories/:idcategory/delete/" , function($idcategory){

	User::verifyLogin();
	
	$category = new Category;

	$category->get((int)$idcategory);

	$category->delete();

	header("Location: /admin/categories/");
	exit;

});

$app->get("/admin/categories/:idcategory/" , function($idcategory){

	User::verifyLogin();	

	$category = new Category;

	$category->get((int)$idcategory);

	$page = new PageAdmin();
	$page->setTpl("categories-update" , array(
		"category"=>$category->getValues()
	));
});

$app->post("/admin/categories/:idcategory/" , function($idcategory){

	User::verifyLogin();	

	$category = new Category;

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories/");
	exit;

});

