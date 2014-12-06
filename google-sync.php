<?php

$gas_url = getenv("OPENSHIFT_GAS_URL");
$table_name="MCCC_CM_Master";

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

function createSchema($db){

    global $gas_url;
    global $table_name;
    $url = "$gas_url?action=get-header&json";
    $json = http_get($url);
    $headers = json_decode($json, true);
    $db->exec("DROP TABLE IF EXISTS `$table_name`");
    $sql="CREATE TABLE `$table_name`"
    ." ("
    .	"`id` INTEGER PRIMARY KEY AUTO_INCREMENT";
    
    foreach ($headers as $header) {
        $sql=$sql . ",\n";
        $sql=$sql . "`" . $header . "`   varchar(100)";
    }
    $sql=$sql . ")";
    //echo "$sql";
    $db->exec($sql);
    return $headers;
}


function importData($db){

    global $gas_url;
    global $table_name;
    $url = "$gas_url?action=get-all&json";
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
        $db->exec($sql);
    }

}

$dbname=getenv("OPENSHIFT_MYSQL_DB_NAME");
$dbhost=getenv("OPENSHIFT_MYSQL_DB_HOST");
$dbport=getenv("OPENSHIFT_MYSQL_DB_PORT");
$username=getenv("OPENSHIFT_MYSQL_DB_USERNAME");
$password=getenv("OPENSHIFT_MYSQL_DB_PASSWORD");
$dsn = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";
$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");

$db = new PDO($dsn, $username, $password, $options) or die("cannot open database");

echo "<code>";
createSchema($db);
importData($db);

$db=NULL;
echo "</code>";
echo "done!"
?>
