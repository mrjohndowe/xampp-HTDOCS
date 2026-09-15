<?php
require_once __DIR__.'/../config.php';$items=books_all();usort($items,fn($a,$b)=>(int)($b['createdAt']??0)<=>(int)($a['createdAt']??0));json_response(array_map('book_payload',$items));
