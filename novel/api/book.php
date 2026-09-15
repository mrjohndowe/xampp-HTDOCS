<?php
require_once __DIR__.'/../config.php';$b=find_book((string)($_GET['id']??''));if(!$b)json_response(['error'=>'Book not found.'],404);json_response(book_payload($b));
