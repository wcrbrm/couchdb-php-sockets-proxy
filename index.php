<?php

	$host = '127.0.0.1';
        $db = 'test';
        $query = $_REQUEST['REQUEST_URI'];

// end of config

        $url = 'http://'.$host.'/'.$db.'/'.$query;
        $uri = '/'.$db.'/'.$query;
        $dsn = parse_url( $url );
        try{
            header('Content-Type: application/json');
            $fp = fsockopen($dsn['host'], isset( $dsn['port'] ) ? $dsn['port'] : 5984, $errno, $errstr, 30);
            if (!$fp) {
                echo json_encode( array('error'=> $errstr, 'code' => $errno));
            } else {

                $out = $_SERVER['REQUEST_METHOD']." $uri HTTP/1.0\r\n";
                $out .= "Host: ".$dsn['host']."\r\n";

                if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                  $out .= "Connection: Close\r\n";
                  $out .= "\r\n";
                  fwrite($fp, $out);
                } else {
                  $request_body = file_get_contents('php://input');
                  $out .= "Content-Length: ".strlen($request_body)."\r\n";
                  $out .= "Accept: application/json\r\n";
                  $out .= "Content-Type: application/json\r\n";
                  $out .= "Connection: Close\r\n";
                  $out .= "\r\n";
                  fwrite($fp, $out);
                  fwrite($fp, $request_body);
                }
                
		$headers = true;
                $remain = 0;
                while (!feof($fp)) {
                    if($headers == true){
                      $line = fgets($fp);
                      if($line == "\r\n"){
                        $headers = false;
                      }else{
                        if(strpos($line, "HTTP/") === false){
                           $exp = explode(":",$line);
                           $n = strtolower(trim($exp[0]));
                           $ignore = explode(",","connection,content-encoding,transfer-encoding,access-control");
                           if(!in_array($n, $ignore)){
                              header(trim($line));
                           }
                        }
                      }
                    } else {
                        $buf = fread($fp, 30);
                        echo $buf;
                    }
                }
                fclose($fp);
            }
