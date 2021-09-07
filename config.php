<?php
$pdo = $config['dsn']='mysql://root:voipinfotech@2019@127.0.0.1/bhawani_xbank';
//$pdo = $config['dsn']='mysql://root:voipinfotech@2019@108.62.122.47/bhawani_xbank';

/*SMSHUB*/
// $config['user']="BHAWANICREDIT";
// $config['password']="BHAWANI$123";
// $config['senderId']="BCCSLT";

/*DS COMMUNICATION*/
$config['user']="BCCSLT";
$config['password']="54321";
$config['senderId']="BCCSLT";


//$config['account_create_api_url']='http://bhawani.epan.in/api/v1/customer';
$config['account_create_api_url']='http://bhawani.voipinfotech.com/api/v1/customer';
