<?php

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

function http_get_2($url)
{
	return file_get_contents($url);
}

function test($db)
{

    $db->exec("DROP TABLE IF EXISTS `Dogs`");
    $db->exec("CREATE TABLE Dogs (Id INTEGER PRIMARY KEY, Breed TEXT, Name TEXT, Age INTEGER)");   
    
    
    //insert some data...
    $db->exec("INSERT INTO Dogs (Breed, Name, Age) VALUES ('Labrador', 'Tank', 2);".
    "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Husky', 'Glacier', 7); " .
    "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Golden-Doodle', 'Ellie', 4);");

}

function createSchema($db){

    $gas_url = "..";
    
    $url = "$gas_url?action=get-header&json";
    
    $json = http_get($url);
    
    $headers = json_decode($json, true);

    $db->exec("DROP TABLE IF EXISTS `CM_MASTER`");
    
    $sql="CREATE TABLE `CM_MASTER`"
    ." ("
    .	"`id` INTEGER PRIMARY KEY AUTOINCREMENT";
    
    foreach ($headers as $header) {
        $sql=$sql . ",\n";
        $sql=$sql . "`" . $header . "`   varchar(100)";
    }
    $sql=$sql . ")";
    
    // echo "<code>$sql</code>";
    
    $db->exec($sql);
    return $headers;
}


function importData($db){

    $gas_url = "..";

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
        $sql="insert into `CM_MASTER` ($fields) values ($values);\n";
        // echo $sql;
        $db->exec($sql);
    }

}


switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET': $the_request = &$_GET;
        break;
    case 'POST': $the_request = &$_POST;
            $data=json_encode($_REQUEST);
            http_post($gas_url,$data);
        break;
    default:
}


$dir = 'sqlite:mccc-children.db';
 
$db = new PDO($dir) or die("cannot open database");

echo "<code>";
createSchema($db);
importData($db);

$db=NULL;
echo "</code>";
echo "done!"
?>

