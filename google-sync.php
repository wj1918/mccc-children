<?php

$gas_url = getenv("OPENSHIFT_GAS_URL");
$table_name="???";
$gssid="???";

function http_get($url)
{
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$output = curl_exec($curl);
	curl_close($curl);
	return $output;
}

function http_post($url,$data)
{
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$server_output = curl_exec ($ch);
	
	curl_close ($ch);
	return $server_output;
}

function createSchema(){

    global $gas_url;
    global $table_name;
    global $gssid;
    $url = "$gas_url?action=get-header&json&gssid=$gssid";
    $json = http_get($url);
    $headers = json_decode($json, true);
    $sql="DROP TABLE IF EXISTS `$table_name`";
    echo "$sql;\n";
    $sql="CREATE TABLE `$table_name`"
    ." ("
    .	"`id` INTEGER PRIMARY KEY AUTO_INCREMENT";
    
    foreach ($headers as $header) {
        $sql=$sql . ",\n";
        $sql=$sql . "`" . $header . "`   varchar(100)";
    }
    $sql=$sql . ")";
    echo "$sql;\n";
    return $headers;
}


function importData(){

    global $gas_url;
    global $table_name;
    global $gssid;
    $url = "$gas_url?action=get-all&json&gssid=$gssid";
    $json = http_get($url);
    $rows = json_decode($json, true);

    foreach ($rows as $row) {
        $fields="";
        $values="";
        $first=True;
        foreach ($row as $key => $value) {
            if($first){
                $fields=$fields . "`" . addslashes($key) . "`";
                $values=$values . '"' . addslashes($value) . '"';
                $first=False;
            }else{
                $fields= $fields . "," . "`" . addslashes($key) . "`";
                $values= $values . "," . '"' . addslashes($value) . '"';
            }
        }
        $sql="insert into `$table_name` ($fields) values ($values);\n";
        echo "$sql";
    }

}

createSchema();
importData();

?>
