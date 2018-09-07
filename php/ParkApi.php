<?php 
// get database connection 


//include_once  './DataAccess.php'; 
//$db = new DataAccess(); 
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

if(!isset($data)){
    $resp=array(
                'ERROR'=>'Request data not found.',
                'INFO'=>'You may be trying out something stupid.',
                'WARN'=>'Stand warned. Your days are numbered'
                );
        echo json_encode($resp);
    return;
}

if(!isset($data->action)){
    $resp=array(
                'ERROR'=>'Request Action not found.',
                'INFO'=>'You may be trying out something stupid.',
                'WARN'=>'Stand warned. Your days are numbered'
                );
    echo json_encode($resp);
    return;
}


$action = $data->action;
//we will be sending back only json
header('Content-Type: application/json');

//we need to know you first
$errorMsgs = authorize_request($db,$data,$action);
//error_log("...... ERROR Returned ...... 0.0 ".json_encode($errorMsgs), 0);  

if(count($errorMsgs)>0){
    //error_log("...... AUTH::ERROR ......  ".json_encode($errorMsgs), 0); 
    $resp=array('error'=>$errorMsgs);
    echo json_encode($resp);
    return;    
}
$cliCode='';
//error_log("...... ERROR Returned  CCCC ...... 0.1 ".json_encode($errorMsgs), 0); 
if($action != 'update.device'){
    $cliCode=$db->find_clicode($data->devid);
    if(StrLen($cliCode)==0){
        error_log("...... xxxx return clcode error ...... 0.01 ", 0);
        $errors[] ="ERROR: Client code not found.";
        $resp=array('error'=>$errors);
        echo json_encode($resp);
        return;   
    }
}

//error_log("...... ERROR Returned  switch ...... 0.12 ".json_encode($errorMsgs), 0); 

switch ($action) {
    case 'hello':
        $resp=array(
                'info'=>'hello',
                'error'=>array(),
                'status'=>'alive'    
                );
        echo json_encode($resp);
        return;    
        break;
    case 'admit.vehicle':
        $errors = array();
        $resp=[];


        $veh = $data->vehicle;
        //server validations
        $errors = validate_arrival($db, $veh);
        if(count($errors)>0){
            error_log("...... ERROR Returned ...... 0.0", 0);  
            $resp=array('error'=>$errors);

            error_log(json_encode($resp), 0);  
            echo json_encode($resp);
            return;
        }

        //save to db
        //prepare data for insert
        $veh_image_file = ($veh->vehPhoto && strlen($veh->vehPhoto)>0) ? "img/veh-".$veh->regNo.'.png' :"";
        $driver_image_file = ($veh->driverPhoto && strlen($veh->driverPhoto)>0) ? "img/dr-".$veh->driverPhone.'.png' : "";

        //check duplicate sticker
        
        $row = array(
                    'table_name'=>'veh_details',
                    'regNo' => $veh->regNo, 
                    'stickerNo' => $veh->stickerNo, 
                    'pin' => $veh->pin,
                    'park_area'=>$veh->parkArea,
                    'category' => $veh->category, 
                    'make'=> $veh->category, 
                    'color' => $veh->color, 
                    'driver_name' => $veh->driver_name, 
                    'driver_id' => $veh->driver_id,
                    'driver_phone' => $veh->driver_phone,
                    'driver_photo' =>$driver_image_file,
                    'veh_photo' =>$veh_image_file,
                    'time_in' => $veh->time_in,
                    'dev_in' => $veh->dev_in,
                    'cli_code' => $cliCode,
                    'ver'=>0,
                    'foot' => $veh->foot
                );

       error_log("...... Done vehicle ......0.1", 0);  

        if($db->create($row)){

            
            if($veh->vehPhoto && strlen($veh->vehPhoto)>0){
		        file_put_contents($veh_image_file,base64_decode($veh->vehPhoto));
                error_log("...... Done vehicle photo ......0.2", 0);  
            }


            if($veh->driverPhoto && strlen($veh->driverPhoto)>0){

                file_put_contents($driver_image_file,base64_decode($veh->driverPhoto));  
                error_log("...... Done driver photo ......0.3", 0); 

            }


            //retrieve it
            $resp=array(
                'error'=>$errors,
                'info'=>'Vehicle was added successfully.'
                );

                error_log("...... Done driver photo ......0.4", 0); 


        }else{
            $errors[] = 'ERROR: Could not add Vehicle.';
             $resp=array('error'=>$errors);
        }

        error_log("-------- Return web call --------", 0); 
        error_log(json_encode($resp), 0);  
        echo json_encode($resp);

        break;

        
    
    case 'release.vehicle':
        $errors = array();
        $resp=[];
        $upsql = "UPDATE veh_details SET time_out=:time_out, dev_out=:dev_out , ver = ver+1 
                    WHERE regNo=:regNo and stickerNo=:stickerNo and time_out is null and cli_code=:cli_code";

        $upParams = array('time_out'=>$data->exitTime, 
                            'dev_out'=>$data->deviceIdOut,  
                            'regNo'=>$data->regNo,
                            'stickerNo'=>$data->stickerNo,
                            'cli_code'=>$cliCode);  

        $dupdata = $db->runUpdateQuery($upsql,$upParams);

        if($dupdata){
            //retrieve it
            $resp=array(
                'error'=>array(),
                'info'=>'Release  was completed successfully.'
                );
        }else{
            $errors[] = 'ERROR: Could not process release.';
             $resp=array('error'=>$errors);
        }
        echo json_encode($resp);
        break;
    case 'refresh.data':
    $errors=array();
    $resp=[];
    
    $sync_mark = $data->sync_mark->admin;
    $devId= $data->devid;

    $qparams = array('updatedon'=>$sync_mark, 'cli_code'=>$cliCode);  
    //tariff
    $tquery = "SELECT * FROM tariff WHERE  cli_code=:cli_code and updatedon > :updatedon" ;
    $rows = $db->runQuery($tquery,$qparams);
    $tariff_data=array();
    if(count($rows)>0){
        $tariff_data = $db->read(array('table_name'=>'tariff','fields'=>'*'));
    }

    //makes
    $mquery = "SELECT * FROM veh_makes WHERE updatedon > :updatedon" ;
    $make_rows=array();
    $rows = $db->runQuery($mquery,array('updatedon'=>$sync_mark));
    if(count($rows)>0){
        $make_rows=$db->read(array('table_name'=>'veh_makes','fields'=>'*'));
    }

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

    //sync_mark
    $tm = time()*1000;
    $sync_mark=array('admin'=>$tm, 'tariff'=>$tm, 'make'=>$tm);

    //
    $resp=array(
        'error'=>array(),
        'tariff'=>$tariff_data,
        'make'=>$make_rows,
        'dev'=>$dev_rows,
        'clicode'=>$cliCode,
        'sync_mark'=>$sync_mark
        );

    error_log(json_encode($resp), 0);  
    echo json_encode($resp);
    break;    

    //return exemption details for stick if available
    case 'find.exemption':
    $xrows =$db->read(array('table_name'=>'veh_stickers','fields'=>'*','where'=>array('sticker'=>$data->sticker,'cli_code'=>$cliCode)));
    $resp=array(
        'error'=>array(),
        'exemption'=>$xrows
        );

        error_log(json_encode($resp), 0);  
        echo json_encode($resp);

    break;
    //query database for vehicles
    case 'find.summary':
    case 'find.all':
    case 'find.vehicle':
    case 'find.vehicle.id':
    case 'find.vehicle.regno':
    case 'find.vehicle.sticker':
    case 'find.vehicle.regno.sticker':
    case 'find.vehicle.phone':
    case 'find.vehicle.range':
    case 'find.vehicle.held.sticker':
    case 'find.vehicle.held.regno':

        error_log("-------- action is  -------- ".$action, 0); 


        $rows = $db->run_vehicle_query($data,$cliCode);
        $resp=array();
        if(count($rows)>0){
            $resp=array('error'=>array(), 'info'=>'SUCCESS', 'data'=>$rows);
        }else{
            $resp=array('error'=>array("No data found using specified value."));
        }
        echo json_encode($resp);
        break;

    case 'pay.vehicle':
        error_log("-------- action is  -------- ".$action, 0); 
        $resp=[];
        $successful = 0;
        $conn =$db->getConnection();
        $conn->beginTransaction();    
        $rec=array('table_name'=>'veh_receipts', 
            'veh_id'=>$data->receipt->vehId, 
            'amount'=>$data->receipt->amount,
            'txn_no'=>$data->receipt->txnId,
            'txn_type'=>$data->receipt->txnType,
            'user_id'=>$data->receipt->userId,
            'txn_time'=>$data->receipt->txnTime,
            'cli_code' => $cliCode
            );
        
         $ins_id =   $db->create_row($conn, $rec);
         if($ins_id > 0)
            $successful = $successful + 1;

            error_log("-------- insid is  -------- ".$ins_id, 0); 
            error_log("-------- success -insid is  -------- ".$successful, 0); 

        $up_row = array('table_name'=>'veh_details',
            'update'=>array(
            'charge'=>$data->receipt->amount,
            'exemption'=>$data->receipt->exemption,
            'rcno'=>$data->receipt->txnId,
            'rctime'=>$data->receipt->txnTime),
            'where'=>array('id'=>$data->receipt->vehId, 'cli_code'=>$cliCode)
            );
        $updsta = $db->update_row($conn, $up_row);
        error_log("-------- upd - is  -------- ".$updsta, 0); 
        
        if($updsta)
           $successful = $successful + 1;
        else
            error_log("update failed xxxxxxxxxxxx ".$successful, 0); 

        error_log("-------- success is  -------- ".$successful, 0); 

        if($successful>1){
            $conn->commit();
            error_log("-------- commited  -------- ".$successful, 0); 
            
            //retrieve it
            $resp=array(
                'error'=>array(),
                'info'=>'Payment  was completed successfully.'
                );
        }else{
            $errors[] = 'ERROR: Could not process payment.';
            $resp=array('error'=>$errors);
        }
        echo json_encode($resp);
        break;
    case "update.device":
        error_log("--------  devv  -------- 00", 0); 
        $dupQuery = "SELECT dev_id FROM dev_map WHERE dev_id=:dev_id";
        $dupParams = array('dev_id'=>$data->devid); 
        $ct = $db->runQuery($dupQuery,$dupParams);
        $success = 0;
        $ct = count($ct);
        error_log("--------  devv  -------- 10", 0); 
        
        if($ct>0){
            //run update
            $supdate="UPDATE dev_map set cli_code=:cli_code,user_name=:user_name where dev_id=:dev_id";
            $sparam=array('cli_code'=>$data->clicode,'user_name'=>$data->username,'dev_id'=>$data->devid);
            $success = $db->runUpdateQuery($supdate,$sparam);
            error_log("--------  devv  -------- 20", 0); 
        }else{
            $msgs=array();
            $info='Device update  was completed successfully.';
            //run insert
            $row = array('table_name'=>'dev_map',
                'dev_id'=>$data->devid,
                'cli_code'=>$data->clicode,
                'user_name'=>$data->username,
                'qlimit'=>20,
                'active'=>'Y');
            $success = $db->create($row);   
            error_log("--------  devv  -------- 30", 0); 

        }
        if ($success == 0){
            $msgs[] = "ERROR: Unable to update device db details.";
            $resp=array(
                'error'=>$msgs
                );

            $info='';
        }

        error_log("--------  devv  -------- 40", 0); 

        $resp=array(
        'error'=>array(),
        'info'=>'Device updated successfully.'
        );

        echo json_encode($resp);
        break;

    case "report.vehicles.list":
        //error_log("...... ERROR Returned  find_clicode ...... 0.1 ".json_encode($devid), 0);

        $query = "SELECT regNo,stickerNo,category,time_in,ifnull(time_out,0) time_out,ifnull(charge,0) charge 
                    FROM veh_details WHERE cli_code=:cli_code and time_in between :fromtime and :totime" ;
        $params = array('cli_code'=>$cliCode, 'fromtime'=>$data->fromtime, 'totime'=>$data->totime);  
        $rows = $db->runQuery($query,$params);
        $resp=array(
            'error'=>array(),
            'data'=>$rows
            );
        echo json_encode($resp);
        
        break;
        
}


function validate_arrival($db, $row){
    $msgs=array();
    if(!isset($row->regNo) || !isset($row->stickerNo) ) 
        $msgs[] = "ERROR: Mandatory field [ RegNo, StickerNo] missing. ";

    error_log("ERROR::: ERROR ::::", 0);
    error_log(json_encode($row), 0);

    //duplicity
    $dupQuery = "SELECT regNo FROM veh_details WHERE regNo=:regNo AND time_out is null";
    $dupParams = array('regNo'=>$row->regNo);   
    $dupdata = $db->runQuery($dupQuery,$dupParams);
    $ct =count($dupdata);
    if($ct>0){
         $msgs[] = "ERROR: Vehicle was not exited properly. No : ".$row->regNo." .. CT:".$ct;
         $msgs[] = json_encode($dupdata);
    }

    $dupQuery = "SELECT regNo FROM veh_details WHERE stickerNo=:stickerNo AND time_out is null";
    $dump = array('regNo'=>$row->regNo, 'stickerNO'=>$row->stickerNo );
    error_log(json_encode($dump), 0);

    $dupParams = array('stickerNo'=>$row->stickerNo);   
    $dupdata = $db->runQuery($dupQuery,$dupParams);
    $ct =count($dupdata);
    if($ct>0){
         $msgs[] = "ERROR: This Sticker is in currently in use. : ".$row->stickerNo." -- ".$ct;
    }

    error_log("ERROR::: MSG ::::", 0);
    error_log(json_encode($msgs), 0);

    return $msgs;    

}



function save_local_image($regNo, $path, $image){
		$path = "img/".$fname.".png";
		file_put_contents($path,base64_decode($image));
}

function authorize_request($db,$data,$action){
    $msgs=array();
    $query="";
    $params=array();
    

    switch($action){
        case "update.device":
            error_log("......  upd authorixe ......1...", 0); 
            return $msgs;
        case "refresh.data":
            if(!isset($data->devid))
                $msgs[] = "ERROR: Authorization failure. Data found.";
            $query="SELECT dev_id FROM dev_map WHERE dev_id=:dev_id and active=:active"; 
            $params=array('dev_id'=>$data->devid,'active'=>'Y'); 
            break;
        default:
            if(!isset($data->devid) && !isset($data->devstamp))
                $msgs[] = "ERROR: Authorization failure. Data found.";
            $query="SELECT dev_id FROM dev_map WHERE dev_id=:dev_id  and dev_stamp=:dev_stamp and active=:active"; 
            $params=array('dev_id'=>$data->devid,'dev_stamp'=>$data->devstamp,'active'=>'Y'); 

            error_log("......  authorixe ......2...", 0); 
            break;

    }


    $rows = $db->runQuery($query,$params);
    error_log("......  auth,, ...".json_encode($query), 0); 
    error_log("......  authww ...".json_encode($params), 0); 
    if(count($rows)==0){
        $msgs[] = "ERROR: Authorization failure. Unable to process request.";
    }

    return $msgs;
}
?>