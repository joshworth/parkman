<?php 
class CloudDataAccess{ 

	// specify your own database credentials 
	//genesis-214216:southamerica-east1:genesis


	private $dsn = "mysql:host=localhost;dbname=parkmandb";
	private $user = "root";
	private $password = "root";

	
	//private $dsn = "mysql:unix_socket=/cloudsql/genesis-214216:southamerica-east1:genesis;dbname=parkman";
	//private $user = "root";
	//private $password = "mighty man of war";

	public $conn; 

    //constructor
    public function __construct(){
        $this->getConnection();
    }
	/*
	function getConnection_cp() {
		// Connect to CloudSQL from App Engine.
		$dsn = getenv('MYSQL_DSN');
		$user = getenv('MYSQL_USER');
		$password = getenv('MYSQL_PASSWORD');
		if (!isset($dsn, $user) || false === $password) {
			throw new Exception('Set MYSQL_DSN, MYSQL_USER, and MYSQL_PASSWORD environment variables');
		}
	
		$db = new PDO($dsn, $user, $password);
	
		return $db;
	}
*/
	
	// get the database connection 
	public function getConnection(){ 
        $this->conn = null;
		
		try{
			$this->conn = new PDO($this->dsn, $this->user, $this->password);
		}catch(PDOException $exception){
			echo "Connection error: " . $exception->getMessage();
		}
		
		return $this->conn;
    }

    private function fieldList($data_row){
        $lst = "";

    }

    private function sanitize($data_row){
        $clean_row=[];
         foreach ($data_row as $key => $value) {
            
            $clean_row[$key]=htmlspecialchars(strip_tags($value));
        }
        return $clean_row;
    }

    private function getFieldList($farr){
        $flst="";
        $nmax = count($farr);
        for($i=0;$i<$nmax;$i++){
            $flst .= $farr[$i];
            if($i+1 < $nmax){
                $flst .=",";
            }

        }
        return $flst;
    }

    private function getWhereList($farr){
        $flst="";
        $i=0;
        $nmax = count($farr);
        foreach ($farr as $key => $value) {
            $i++;
            $flst .= $key."=:".$key;
            if($i < $nmax){
                $flst .= " AND ";
            }
        }
        
        return $flst;
    }

    private function getUpdateList($farr){
        $flst="";
        $i=0;
        $nmax = count($farr);
        foreach ($farr as $key => $value) {
            $i++;
            $flst .= $key."=:".$key;
            if($i < $nmax){
                $flst .= ",";
            }
        }
        
        return $flst;
    }

    //generic function for creating table row using local transaction
    public function create( $data_row){
        $conn = $this->getConnection();
        $ret_id =  $this->create_row($conn, $data_row);
        return $ret_id;
    }

    //generic function for creating table row
    public function create_row($conn, $data_row){

        $clean_row = $this->sanitize($data_row);

        //get field list
        $flist = "";
        $bind_data=[];
        $table_name=$data_row["table_name"];

        $max=count($clean_row);
        $ct=0;
        foreach ($clean_row as $key => $value) {
            $ct++;
            
            //ignore table_name element
            if($key=="table_name"){
                continue;
            }

            $flist .= $key . "=:" . $key;
            $bind_data[$key] = $value;

            if($ct < $max ){
                $flist .= ", ";
            }
        }
        // query to insert record
        $query = "INSERT INTO " . $table_name . " SET " . $flist;
        // prepare query
        $stmt = $conn->prepare($query);

        $nbb = count($bind_data);
        //echo "<br>How many ? ".$nbb;
        // bind values
        foreach ($bind_data as $key => $value) {

            $bname = ":".$key;
            $bvalue = $value;
            //echo "<br>BIND - ".$bname." TO ".$bvalue;
            $stmt->bindValue ($bname, $bvalue);
        }
        //print_r($stmt);
        // execute query

        
        
        if(!$stmt->execute()){  
            $errors = $stmt->errorInfo();
            //echo '<br> ERRORS ::  :  '.json_encode($errors);
            throw new Exception('ERROR: '.json_encode($errors));
        }

        $ins_id = $conn->lastInsertId();

        //echo '<br> Insert ID  : '. $ins_id;
        return $ins_id;
    }

    //read records from table, select use direct sql
    public function runQuery($query, $params){

        //echo '<br>DEBUG QUERY .......<br>';
        //print_r($query);
		$params = $this->sanitize($params);
		
		//print_r($params);

        $this->getConnection();

        // prepare query statement
        $stmt = $this->conn->prepare( $query );

          
        error_log("--------  query  -------- ".$query, 0); 

        //bind variables
        foreach ($params as $key => $value) {
                $bname = ":".$key;
                $bvalue = $value;
                //echo "<br>BIND - ".$bname." TO ".$bvalue;
                $stmt->bindValue ($bname, $bvalue);
            }

          
        error_log("--------  params  -------- ".json_encode($params), 0);     

        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        
        // execute query
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return $rows;
	}
	

    //read records from table, select use direct sql
    public function runUpdateQuery($query, $params){

        $retval = 0;
        //echo '<br>DEBUG QUERY .......<br>';
        //print_r($query);
        $params = $this->sanitize($params);

        $this->getConnection();

        // prepare query statement
        $stmt = $this->conn->prepare( $query );

        //bind variables
        foreach ($params as $key => $value) {
                $bname = ":".$key;
                $bvalue = $value;
                //echo "<br>BIND - ".$bname." TO ".$bvalue;
                $stmt->bindValue ($bname, $bvalue);
            }

        // execute query
        $stmt->execute();

        return 1;
    }


    //read records from table, select
    public function read($options){

        $table_name=$options['table_name'];
        $fields= isset($options['fields']) ? $this->getFieldList($options['fields']) : " * "; // default alll
        $where=  " ";
        $order= isset($options['order']) ? " ORDER BY ". $this->getFieldList($options['order']) : " ORDER BY 1 ";
        $limit=isset($options['limit']) ? " LIMIT ".$options['limit'] : " ";

        //echo '<br>DEBUG INEDX .......<br>';
        //print_r($options);

        if(isset($options['where'])){
             $clean_where = $this->sanitize($options['where']);
             $where = " WHERE " . $this->getWhereList($clean_where);
        }

        
        // query to read single record
        $query = "SELECT ".$fields." FROM ".$table_name.$where.$order.$limit;
        //echo "<br> READ QRY: ".$query;

        $this->getConnection();

        // prepare query statement
        $stmt = $this->conn->prepare( $query );
        
        
         // bind values
         if(isset($options['where']) ){
            
             foreach ($clean_where as $key => $value) {
                $bname = ":".$key;
                $bvalue = $value;
                //echo "<br>BIND - ".$bname." TO ".$bvalue;
                $stmt->bindValue ($bname, $bvalue);
            }

            //echo '<br> WHERE ....';
            //print_r($clean_where);
         }

        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        
        // execute query
        $stmt->execute();

        $rows = $stmt->fetchAll();

        //echo "<br> RESULTS : ";
        //print_r($rows);

        //echo "<br> Total ROWS ".count($rows);

        return $rows;
    }


    //update use local transaction
    public function update($data){
        $conn = $this->getConnection();
        return $this->update_row($conn,$data);
    }

    //update
    public function update_row($conn, $data){
        $table_name= $data['table_name'];
        $clean_update = $this->sanitize($data['update']);
        $uplst = $this->getUpdateList($clean_update);

        $clean_where = $this->sanitize($data['where']);
        $where = " WHERE " . $this->getWhereList($clean_where);

        // update query
        $query = "UPDATE ".$table_name." SET ".$uplst.$where;

        error_log("QRY: ".$query, 0); 
        

        // prepare query statement
        $stmt = $conn->prepare($query);

        error_log("-------- params where : ".json_encode($clean_where), 0); 
        
        // bind new values
         foreach ($clean_update as $key => $value) {
            $bname = ":".$key;
            $bvalue = $value;
            //echo "<br>BIND - ".$bname." TO ".$bvalue;
            $stmt->bindValue ($bname, $bvalue);
         }

         //bind where params
         foreach ($clean_where as $key => $value) {
            $bname = ":".$key;
            $bvalue = $value;
            //echo "<br>BIND - ".$bname." TO ".$bvalue;
            $stmt->bindValue ($bname, $bvalue);

            error_log("-------- where bind ".$bname." : ".$bvalue, 0);
         }
        
        // execute the query
        if($stmt->execute()){
            error_log("-------- upd - is  execute() ", 0); 
            return true;
        }else{
            error_log("-------- upd - is  execute() failed xxxxx ", 0); 
            return false;
        }
    }

    private function getProcParamList($farr){
        $flst="";
        $i=0;
        $nmax = count($farr);
        foreach ($farr as $key => $value) {
            $i++;
            $flst .= $key."=:".$key;
            if($i < $nmax){
                $flst .= ",";
            }
        }
        
        return $flst;
    }

    function localprint($arr,$nest){
        $i=0;
        foreach ($arr as $key => $value) {
            echo '<br><br>' . ++$i . '..  '.$key.'  . <br>';
            if($nest){
                foreach ($value as $k => $v) {
                    echo '<br>';
                    print_r($v);
                }
            }else {
                print_r($value);
            }
            
        }
    }

    public function run_query_proc($params){
        $clean_params = $this->sanitize($params);
        $proc_name= $clean_params['proc_name'];

        $paramdata=[];
        $parlist='';
        foreach ($clean_params as $key => $value) {
            if($key != 'proc_name'){
                $paramdata[$key]=$value;
            }
        }
        

        //build bind list
        $plist = "";
        $nmax = count($paramdata);
        $i=0;
        if($nmax > 0){
            foreach ($paramdata as $key => $value) {
                $i++;
                $plist .= ':'.$key;
                if($i < $nmax){
                    $plist .= ",";
                }
            }
        }
        
        // call procedure
        $query = "CALL ".$proc_name."(".$plist.")";
        //make connection_status
        $this->getConnection();

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        //bind where params
         foreach ($paramdata as $key => $value) {
            $bname = ":".$key;
            $bvalue = $value;
            //echo "<br>BIND - ".$bname." TO ".$bvalue;
            $stmt->bindValue ($bname, $bvalue);
         }

         $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        
        try{
            // execute query
            $stmt->execute();
        }catch(Exception $ex){
            echo "ERROR: An error has occured. ";
        }
        

        $dataset = array();

        do{
            
            $rows = $stmt->fetchAll();
            if($rows){
                //get table name
                $tab_name = $rows[0]['TAB'];
                $dataset[$tab_name] = $rows;

            }

        }while ($stmt->nextRowset());


        //$this->localprint($dataset,false);

        echo '<br> Howmany '.count($dataset);

       
       

        return $dataset;


    }

    //return an associative array of data
    public function run_dictionary_proc($params,$key_lab,$value_lab){
        $dataset = $this->run_query_proc($params);

        //echo '<br>printing dataset ... ';
        //print_r($dataset);

        $dict = array();
        //expect 1 item in dataset
        foreach ($dataset as $value) {
            foreach ($value as $k => $v) {
                //echo '<br> ... this one .. '.$k.' :  '.$v[$key_lab].' -> '.$v[$value_lab];
                $kk = $v[$key_lab];
                $vv = $v[$value_lab];
                $dict[$kk] = $vv;
            }
        }
        //echo '<br>printing dictionary ... ';
        //print_r($dict);
        return $dict;
    }

    //cli_code
    public function find_clicode($devid){
        //error_log("...... ERROR Returned  find_clicode ...... 0.1 ".json_encode($devid), 0);
        $query = "SELECT dev_id,cli_code FROM dev_map WHERE dev_id=:dev_id and  active=:active" ;
        $params = array('dev_id'=>$devid, 'active'=>'Y');  
		$rows = $this->runQuery($query,$params);
        if(count($rows)>0){
            $row = reset($rows);
            return $row['cli_code'];
        }
        //error_log("...... ERROR Returned  find_clicode ...... 0.1 ".json_encode($rows), 0);
        return "";
	}
	
	//test mydb
	public function testQuery(){
		$query ="SELECT * FROM TARIFF where id = 2";
		echo $query;
			
		$this->getConnection();
		$stmt = $this->conn->prepare( $query );
		echo "\nfetching: 0 .....";
		$result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
		$stmt->execute();
		
		echo "\nfetching: 1 .....";
		$rrr = $stmt->fetchAll();
		echo "\nfetching: DONE .....";

		//$params=array();
		//$rows = $this->runQuery($query,$params);
		echo "TOTAL: ".count($rrr);
		return $rrr;
	}

    //run query
    public function run_vehicle_query($data, $cliCode){
        $action = $data->action;
        $query ="";
        $params=array();

        switch ($action) {
            case 'find.summary':
                $query = "SELECT id,regNo,stickerNo,driver_name,driver_id,driver_phone,driver_photo,veh_photo,
                category,color,make,model,ifnull(rcno,'') rcno,rctime,time_in,ifnull(time_out,0) time_out,dev_in,dev_out,ver,foot 
                FROM veh_details where cli_code=:cli_code order by id desc";
                $params = array('cli_code'=>$cliCode);  
                break;
            case 'find.held.summary':
                    $query = "SELECT vd.id, vd.regNo,vd.stickerNo,vd.pin,vd.driver_phone,vd.category,vd.make, vd.time_in,ifnull(vs.company,'') company,ifnull(vs.exemption,0) exemption 
                    FROM veh_details vd left outer join veh_stickers vs on vd.stickerno=vs.sticker and vd.regno=vs.regno 
                    where cli_code=:cli_code  order by id desc";
                $params = array('cli_code'=>$cliCode);  
                break;
            case 'find.all':
                $query = "SELECT * FROM veh_details where cli_code=:cli_code   order by id desc";
                $params = array('cli_code'=>$cliCode);  
                break;
            case 'find.vehicle':
                $query = "SELECT * FROM veh_details WHERE regNo=:regNo and  stickerNo=:stickerNo and cli_code=:cli_code  order by id desc" ;
                $params = array('regNo'=>$data->regNo, 'stickerNo'=>$data->stickerNo,'cli_code'=>$cliCode);  
                break;
            case 'find.vehicle.id':
                $query = "SELECT vd.id,vd.regNo,vd.stickerNo,vd.pin,vd.driver_name,vd.driver_id,vd.driver_phone,vd.driver_photo,vd.veh_photo,vd.category,
                vd.color,vd.make,vd.model,ifnull(vd.rcno,'') rcno,ifnull(vd.rctime,0) rctime,charge,vd.time_in,ifnull(vd.time_out,0) time_out,vd.dev_in,
                vd.dev_out,vd.ver,vd.foot,ifnull(vs.exemption,0) exemption ,park_area 
                FROM veh_details vd left outer join veh_stickers vs on vd.stickerno=vs.sticker and vd.regno=vs.regno 
                WHERE vd.id=:id and vd.cli_code=:cli_code" ;
                
                $params = array('id'=>$data->id,'cli_code'=>$cliCode);   
                break;
            case 'find.vehicle.regno':
                $query = "SELECT * FROM veh_details WHERE regNo=:regNo and cli_code=:cli_code order by id desc" ;
                $params = array('regNo'=>$data->regNo,'cli_code'=>$cliCode);   
                break;
            case 'find.vehicle.sticker':
                $query = "SELECT * FROM veh_details WHERE stickerNo=:stickerNo and  cli_code=:cli_code   order by id desc" ;
                $params = array('stickerNo'=>$data->stickerNo,'cli_code'=>$cliCode); 
                break;   
            case 'find.vehicle.regno.sticker':  
                $query = "SELECT * FROM veh_details WHERE regNo=:regNo  and stickerNo=:stickerNo and cli_code=:cli_code    order by id desc" ;
                $params = array('regNo'=>$data->regNo, 'stickerNo'=>$data->stickerNo,'cli_code'=>$cliCode); 
                break; 
            case 'find.vehicle.phone':
                $query = "SELECT * FROM veh_details WHERE driver_phone=:driverPhone and cli_code=:cli_code order by id desc" ;
                $params = array('driverPhone'=>$data->driverPhone,'cli_code'=>$cliCode); 
                break;     
            case 'find.vehicle.range':
                $query = "SELECT * FROM veh_details WHERE time_in  between :fromDt AND :toDt  and cli_code=:cli_code order by id desc" ;
                $params = array('fromDt'=>$data->fromDt, 'toDt'=>$data->toDt); 
                break;
            case 'find.vehicle.held.sticker':
                $query = "SELECT vd.id,vd.regNo,vd.stickerNo,vd.pin,vd.driver_name,vd.driver_id,vd.driver_phone,vd.driver_photo,vd.veh_photo,vd.category,
                vd.color,vd.make,vd.model,ifnull(vd.rcno,'') rcno,ifnull(vd.rctime,0) rctime,charge,vd.time_in,ifnull(time_out,0) time_out,vd.dev_in,
                vd.dev_out,vd.ver,vd.foot,ifnull(vs.exemption,0) exemption ,park_area 
                FROM veh_details vd left outer join veh_stickers vs on vd.stickerno=vs.sticker and vd.regno=vs.regno 
                WHERE vd.stickerNo=:stickerNo  and vd.time_out is null and vd.cli_code=:cli_code" ;
                $params = array('stickerNo'=>$data->stickerNo,'cli_code'=>$cliCode);   
                break; 
            
            case 'find.vehicle.held.regno':
                $query = "SELECT vd.id,vd.regNo,vd.stickerNo,vd.pin,vd.driver_name,vd.driver_id,vd.driver_phone,vd.driver_photo,vd.veh_photo,vd.category,
                vd.color,vd.make,vd.model,ifnull(vd.rcno,'') rcno,ifnull(vd.rctime,0) rctime,charge,vd.time_in,ifnull(vd.time_out,0) time_out,vd.dev_in,
                vd.dev_out,vd.ver,vd.foot,ifnull(vs.exemption,0) exemption ,park_area 
                FROM veh_details vd left outer join veh_stickers vs on vd.stickerno=vs.sticker and vd.regno=vs.regno 
                WHERE vd.regNo=:regNo and vd.time_out is null and vd.cli_code=:cli_code" ;
                
                $params = array('regNo'=>$data->regNo,'cli_code'=>$cliCode);   
                break;                   
        }
        $rows = $this->runQuery($query,$params);
        return $rows;
    }
}
?>