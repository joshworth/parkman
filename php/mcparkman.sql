use parkmandb;
drop table driver_photo;
CREATE TABLE driver_photo (
  doc_id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  regNo varchar(20) DEFAULT NULL,
  stickerNo varchar(150) DEFAULT NULL,
  photo text,
  ver tinyint(4) DEFAULT NULL,
  foot varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

drop table park_admin;     
CREATE TABLE park_admin (
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  dev_id varchar(150) DEFAULT NULL,
  keyid varchar(20) DEFAULT NULL,
  description varchar(150) DEFAULT NULL,
  val varchar(250) DEFAULT NULL,
  scope char(1) DEFAULT NULL,
  ver tinyint(4) DEFAULT NULL,
  foot varchar(150) DEFAULT NULL,
  cli_code varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


drop table veh_details;
CREATE TABLE veh_details (
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  regNo varchar(20) DEFAULT NULL,
  stickerNo varchar(150) DEFAULT NULL,
  pin varchar(50) DEFAULT NULL,
  driver_name varchar(150) DEFAULT NULL,
  driver_id varchar(50) DEFAULT NULL,
  driver_phone varchar(50) DEFAULT NULL,
  driver_photo varchar(150) DEFAULT NULL,
  veh_photo varchar(150) DEFAULT NULL,
  category varchar(20) DEFAULT NULL,
  color varchar(20) DEFAULT NULL,
  make varchar(20) DEFAULT NULL,
  model varchar(20) DEFAULT NULL,
  time_in bigint(15) DEFAULT NULL,
  time_out bigint(15) DEFAULT NULL,
  dev_in varchar(150) DEFAULT NULL,
  dev_out varchar(150) DEFAULT NULL,
  cli_code varchar(150) DEFAULT NULL,
  charge double, 
  rcno double,
  rctime bigint(15),
  exemption double,
  tar_code varchar(50),
  park_area varchar(50),
  ver tinyint(4) DEFAULT NULL,
  foot varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

drop table veh_kyc;
create table veh_kyc (
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  regNo varchar(20),
  pin varchar(50),
  driver_name varchar(150),
  driver_id varchar(50), 
  driver_phone varchar(50),
  driver_photo varchar(150), 
  veh_photo varchar(150), 
  owner varchar(150), 
  make varchar(50), 
  updatedon bigint(15), 
  updatedby int
  cli_code varchar(150) DEFAULT NULL
)ENGINE=InnoDB DEFAULT CHARSET=latin1;     


drop table veh_photo;
CREATE TABLE veh_photo (
  doc_id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  regNo varchar(20) DEFAULT NULL,
  stickerNo varchar(150) DEFAULT NULL,
  photo text,
  ver tinyint(4) DEFAULT NULL,
  foot varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

drop table veh_receipts;
CREATE TABLE veh_receipts(
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  veh_id int(11), 
  amount double,
  txn_no varchar(50),
  txn_type varchar(50), 
  user_id int(11), 
  cli_code varchar(150) DEFAULT NULL,
  txn_time bigint(15)
  )ENGINE=InnoDB DEFAULT CHARSET=latin1;


drop table tariff;
CREATE TABLE tariff (
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  grace_in varchar(50), 
  grace_out varchar(50), 
  psec double ,
  pmin double, 
  phour double, 
  pday double, 
  pweek double, 
  method varchar(50), 
  round varchar(50), 
  tar_code varchar(50),
  updatedon  bigint(15),
  cli_code varchar(150)
  )ENGINE=InnoDB DEFAULT CHARSET=latin1;

  --tariff
insert into tariff(grace_in,grace_out,psec,pmin,phour,pday,pweek,method,round,tar_code,updatedon,cli_code) values ('1M','10M',0,20,1000,0,0,'PM','CEIL','CYCLE',UNIX_TIMESTAMP()*1000,'URA');
insert into tariff(grace_in,grace_out,psec,pmin,phour,pday,pweek,method,round,tar_code,updatedon,cli_code) values ('1M','10M',0,50,2500,0,0,'PM','CEIL','SMALL',UNIX_TIMESTAMP()*1000,'URA');
insert into tariff(grace_in,grace_out,psec,pmin,phour,pday,pweek,method,round,tar_code,updatedon,cli_code) values ('1M','10M',0,90,5000,0,0,'PM','CEIL','MEDIUM',UNIX_TIMESTAMP()*1000,'URA');
insert into tariff(grace_in,grace_out,psec,pmin,phour,pday,pweek,method,round,tar_code,updatedon,cli_code) values ('1M','10M',0,120,7000,0,0,'PM','CEIL','BIG',UNIX_TIMESTAMP()*1000,'URA');


drop table veh_stickers;
CREATE TABLE veh_stickers(
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  sticker varchar(50), 
  regno varchar(50), 
  clientid varchar(50), 
  company varchar(50), 
  exemption double,
  cli_code varchar(150)
  )ENGINE=InnoDB DEFAULT CHARSET=latin1;

  drop table veh_makes;
  CREATE TABLE veh_makes(
  id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
  make_id varchar(50),
  tar_code varchar(50),
  updatedon  bigint(15)
  )ENGINE=InnoDB DEFAULT CHARSET=latin1;
  --makes
  insert into veh_makes(make_id,tar_code,updatedon) values('Bicycle','CYCLE',UNIX_TIMESTAMP()*1000);
  insert into veh_makes(make_id,tar_code,updatedon) values('Motor Cycle','CYCLE',UNIX_TIMESTAMP()*1000);
  insert into veh_makes(make_id,tar_code,updatedon) values('Car','SMALL',UNIX_TIMESTAMP()*1000);
  insert into veh_makes(make_id,tar_code,updatedon) values('Truck','MEDIUM',UNIX_TIMESTAMP()*1000);
  insert into veh_makes(make_id,tar_code,updatedon) values('Bus','BIG',UNIX_TIMESTAMP()*1000);


  drop table dev_map;
  CREATE TABLE dev_map(
    id int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT, 
    dev_id varchar(150), 
    cli_code varchar(150),
    user_id int(11),
    user_name varchar(150), 
    dev_stamp varchar(150), 
    qlimit int(11),
    active char(1)
  )ENGINE=InnoDB DEFAULT CHARSET=latin1;

  insert into dev_map(dev_id,cli_code,user_id,user_name,dev_stamp,qlimit,active)values('ffffffff-a306-e693-ffff-ffffab9ce8e8','URA',1,'JOSHUA WASE','',20,'Y');

  


--KYC samples
insert into veh_kyc(regNo,pin,driver_name,driver_id,driver_phone,owner,make,cli_code) values('UAN999H','999','JOSHUA WASEREKERE','100','0756221123','JOSHUA','Car','URA');      
insert into veh_kyc(regNo,pin,driver_name,driver_id,driver_phone,owner,make,cli_code) values('UAN888H','888','SAMUEL TOM','200','0756333333','SAMUEL','Car','URA');     
insert into veh_kyc(regNo,pin,driver_name,driver_id,driver_phone,owner,make,cli_code) values('UAN777H','777','KOLO SOSLO','200','0756244444','KOLO','Car','URA');       

--sticker samples
insert into veh_stickers(sticker,regno,clientid,company,exemption) values('999','UAN999H','001','URA',100);
insert into veh_stickers(sticker,regno,clientid,company,exemption) values('888','UAN888H','002','UNRA',100);
insert into veh_stickers(sticker,regno,clientid,company,exemption) values('777','UAN777H','003','UN',100);

SELECT * FROM veh_details WHERE id=8 AND cl_code='URA';
UPDATE veh_details SET charge=100,exemption=0,rcno='RXX',rctime=0 WHERE id=8 AND cl_code='URA';


SELECT dev_id FROM dev_map WHERE dev_id='ffffffff-a306-e693-ffff-ffffab9ce8e8' and active='Y';

{"dev_id":"ffffffff-a306-e693-ffff-ffffab9ce8e8","active":"Y"}

SELECT regNo,stickerNo,category,time_in,ifnull(time_out,0) time_out,charge 
FROM veh_details 
WHERE cli_code='URA' and time_in between 1534366800000 and :totime

{"cli_code":"URA","fromtime":"1535576400000","totime":"1535576400000"}

1535576400000
1535704915079
