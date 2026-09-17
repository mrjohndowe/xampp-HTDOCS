<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
$payload=json_decode((string)file_get_contents('php://input'),true);$id=trim((string)($payload['id']??''));$active=!empty($payload['active'])?1:0;
$statement=db()->prepare('UPDATE videos SET active=? WHERE id=?');$statement->execute([$active,$id]);
if($statement->rowCount()===0){http_response_code(404);echo json_encode(['error'=>'Video not found.']);exit;}
echo json_encode(['success'=>true,'active'=>$active]);
