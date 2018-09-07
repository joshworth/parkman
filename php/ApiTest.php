<?php 
// get database connection 


include_once  './CloudDA.php'; 
$db = new CloudDataAccess(); 


$varr = $_GET;

// get posted data
$content = file_get_contents("php://input");
$data = json_decode($content); 

error_log("...... content ......1..", 0);
error_log(json_encode($content), 0);
error_log("...... content varr ......2..", 0);
error_log(json_encode($varr), 0);


error_log("...... Begining post intercept ......2...", 0);
error_log(json_encode($data), 0);
//error_log(json_encode($data->vehicle), 0);


//$tst = '{"error":"ERROR: Invalid userid or password.","login":{}}';

testTest($db);
return;

//testing cloud //////////////////////////////////////////////////////////
$rows = $db->testQuery();
$resp=array(
    'ERROR'=>'Warning, this is test data',
    'data'=>$rows
    );
echo json_encode($resp);
return;
//////////////////////////////////////////////////////////////////////////

if(!isset($data)){
    $resp=array(
                'ERROR'=>'Request data not found. 1.0',
                'INFO'=>'You may be trying out something stupid. 1',
                'WARN'=>'Stand warned. Your days are numbered 1'
                );
        echo json_encode($resp);
    return;
}

if(!isset($data->action)){
    $resp=array(
                'ERROR'=>'Request Action not found.2.0',
                'INFO'=>'You may be trying out something stupid.2',
                'WARN'=>'Stand warned. Your days are numbered 2'
                );
    echo json_encode($resp);
    return;
}

function testTest($db){

    $errors=array();
    $resp=[];
    
    $sync_mark = 0;
    $devId= 'ffffffff-a306-e693-ffff-ffffab9ce8e8';
    $cliCode='URA';
    $qparams = array('updatedon'=>$sync_mark, 'cli_code'=>$cliCode);  
    echo json_encode($devId);
    //tariff
    $tquery = "SELECT * FROM tariff WHERE  cli_code=:cli_code and updatedon > :updatedon" ;
    $rows = $db->runQuery($tquery,$qparams);
    $tariff_data=array();
    if(count($rows)>0){
        $tariff_data = $db->read(array('table_name'=>'tariff','fields'=>'*'));
    }

    echo json_encode('tariff ..... ');
    //makes
    $mquery = "SELECT * FROM veh_makes WHERE updatedon > :updatedon" ;
    $make_rows=array();
    $rows = $db->runQuery($mquery,$qparams);
    if(count($rows)>0){
        $make_rows=$db->read(array('table_name'=>'veh_makes','fields'=>'*'));
    }
    echo json_encode('makes ..... ');
    //device details
    //update dev_stamp, then send latest details
    $seed=rand(5,1710);
    $suprise=time()*1000;
    $dev_stamp = $devId.'D'.$suprise.'A'.$seed;

    error_log("-------- Suprise --------".$dev_stamp, 0); 

    $sql = "UPDATE dev_map SET dev_stamp=:dev_stamp WHERE dev_id=:dev_id and active=:active";
    $params = array('dev_stamp'=>$dev_stamp, 'dev_id'=>$devId, 'active'=>'Y');  
    $rows = $db->runUpdateQuery($sql,$params);
    $dev_rows=array();

    error_log("-------- Suprise rows ----devid--- ".$devId, 0); 

    if($rows){
        error_log("-------- Suprise rows --------x-0", 0); 
        $sql = "SELECT * FROM dev_map WHERE dev_id=:dev_id and active=:active" ;
        $params = array('dev_id'=>$devId, 'active'=>'Y'); 
        $dev_rows = $db->runQuery($sql,$params);
        if(count($dev_rows)==0){
            error_log("-------- Suprise rows --------x-0-zero", 0); 
            $errors[] = 'ERROR: Authorization error. Please register device.';
        }
    }else{
        $errors[] = 'ERROR: Authorization error. Please register device.';
    }
    echo json_encode('sync_mark ..... ');

    //sync_mark
    $tm = time()*1000;
    $sync_mark=array('admin'=>$tm, 'tariff'=>$tm, 'make'=>$tm);

    //
    $resp=array(
        'error'=>array(),
        'tariff'=>$tariff_data,
        'make'=>$make_rows,
        'dev'=>$dev_rows,
        'sync_mark'=>$sync_mark
        );

    error_log(json_encode($resp), 0);  
    echo json_encode($resp);
    

}

?>