DROP TABLE IF EXISTS admintb;

CREATE TABLE `admintb` (
  `username` varchar(50) NOT NULL,
  `password` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO admintb VALUES("admin","admin123");



DROP TABLE IF EXISTS appointmenttb;

CREATE TABLE `appointmenttb` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `doctor` varchar(50) NOT NULL,
  `docFees` decimal(10,2) NOT NULL,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `userStatus` int(11) DEFAULT 1,
  `doctorStatus` int(11) DEFAULT 1,
  `appointmentStatus` varchar(20) DEFAULT 'active',
  `cancelledBy` varchar(20) DEFAULT NULL,
  `cancellationReason` text DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `pid` (`pid`),
  CONSTRAINT `appointmenttb_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `patreg` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO appointmenttb VALUES("1","1","NIC123456789","Ram","Kumar","Male","ram@gmail.com","0771234567","Ashok","500.00","2025-10-29","10:00:00","1","1","active","","","2026-01-12 22:34:11");
INSERT INTO appointmenttb VALUES("2","2","NIC987654321","Alia","Bhatt","Female","alia@gmail.com","0779876543","Arun","600.00","2025-10-30","11:00:00","1","1","active","","","2026-01-12 22:34:11");
INSERT INTO appointmenttb VALUES("3","3","NIC111222333","Shahrukh","Khan","Male","shahrukh@gmail.com","0712345678","Dinesh","700.00","2025-11-01","09:00:00","1","1","active","","","2026-01-12 22:34:11");
INSERT INTO appointmenttb VALUES("4","4","NIC200268403842","Dinuvi","Ranasinghe","Female","dinuvi153@gmail.com","0757872653","Arun","600.00","2026-01-14","02:30:00","1","1","active","","","2026-01-13 00:26:05");
INSERT INTO appointmenttb VALUES("5","5","NIC200268403845","kavee","lalitha","Female","kavee@gamil.com","0757872653","Ashok","500.00","2026-01-13","22:22:00","1","1","active","","","2026-01-13 10:10:50");
INSERT INTO appointmenttb VALUES("6","4","NIC200268403842","Dinuvi","Ranasinghe","Female","dinuvi153@gmail.com","0757872653","Arun","600.00","2026-01-14","10:30:00","0","1","cancelled","admin","","2026-01-13 11:57:35");
INSERT INTO appointmenttb VALUES("7","2","NIC987654321","Alia","Bhatt","Female","alia@gmail.com","0779876543","Ashok","500.00","2026-01-14","10:30:00","1","1","active","","","2026-01-13 17:31:28");
INSERT INTO appointmenttb VALUES("8","9","NIC2002684038","Dinuvi","Ranasinghe","Female","dinu@gmil.come","0757872653","Dinesh","700.00","2026-01-13","22:00:00","1","1","active","","","2026-01-13 21:21:24");



DROP TABLE IF EXISTS contact;

CREATE TABLE `contact` (
  `name` varchar(30) NOT NULL,
  `email` text NOT NULL,
  `contact` varchar(10) NOT NULL,
  `message` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO contact VALUES("Anu","anu@gmail.com","7896677554","Hey Admin");
INSERT INTO contact VALUES("Viki","viki@gmail.com","9899778865","Good Job, Pal");



DROP TABLE IF EXISTS doctb;

CREATE TABLE `doctb` (
  `id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `spec` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `docFees` decimal(10,2) NOT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO doctb VALUES("DOC001","Ashok","General","ashok@gmail.com","ashok123","500.00","0771110000","2026-01-12 22:34:11");
INSERT INTO doctb VALUES("DOC002","Arunna","Cardiologist","arun@gmail.com","123","600.00","0772220000","2026-01-12 22:34:11");
INSERT INTO doctb VALUES("DOC003","Dinesh","General","dinesh@gmail.com","dinesh123","700.00","0773330000","2026-01-12 22:34:11");
INSERT INTO doctb VALUES("DOC22","kalana","Orthopedic","aaaadsdefj@gml.com","123456","2000.00","0757872653","2026-01-13 21:15:55");
INSERT INTO doctb VALUES("DOC6","faranando","Psychiatrist","defj@gml.com","123","1000.00","0757872653","2026-01-13 12:37:34");



DROP TABLE IF EXISTS foodtb;

CREATE TABLE `foodtb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




DROP TABLE IF EXISTS hospital_settings;

CREATE TABLE `hospital_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

INSERT INTO hospital_settings VALUES("1","hospital_name","Healthcare Hospital","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("2","hospital_address","123 Medical Street, City, Country","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("3","hospital_phone","+94 11 234 5678","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("4","hospital_email","info@healthcarehospital.com","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("5","appointment_duration","30","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("6","working_hours_start","08:00","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("7","working_hours_end","18:00","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("8","enable_online_payment","0","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("9","sms_notifications","0","2026-01-13 21:06:25","2026-01-13 21:27:13");
INSERT INTO hospital_settings VALUES("10","email_notifications","0","2026-01-13 21:06:25","2026-01-13 21:27:13");



DROP TABLE IF EXISTS patreg;

CREATE TABLE `patreg` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `address` text DEFAULT NULL,
  `emergencyContact` varchar(15) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pid`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `national_id` (`national_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

INSERT INTO patreg VALUES("1","Ram","Kumar","Male","1990-05-15","ram@gmail.com","0771234567","123 Main St, Colombo","0779876543","NIC123456789","ram123","2026-01-12 22:34:11");
INSERT INTO patreg VALUES("2","Alia","Bhatt","Female","1995-08-22","alia@gmail.com","0779876543","456 Park Ave, Kandy","0771234567","NIC987654321","alia123","2026-01-12 22:34:11");
INSERT INTO patreg VALUES("3","Shahrukh","Khan","Male","1985-11-02","shahrukh@gmail.com","0712345678","789 Beach Rd, Galle","0718765432","NIC111222333","shahrukh123","2026-01-12 22:34:11");
INSERT INTO patreg VALUES("4","Dinuvi","Ranasinghe","Female","2000-07-02","dinuvi153@gmail.com","0757872653","48,thalwaththa,\ngonawaal","0757872653","NIC200268403842","$2y$10$KksxhjX/HQe41b4MC6F6AOFXi7qxHXGu3B6h8d.fhhfRrwa/TN7Yu","2026-01-12 23:01:00");
INSERT INTO patreg VALUES("5","kavee","lalitha","Female","1999-05-01","kavee@gamil.com","0757872653","48,thalwaththa,\ngonawaal","0757872653","NIC200268403845","k123456","2026-01-12 23:34:03");
INSERT INTO patreg VALUES("6","wathasala","perera","Female","1999-02-12","wathsala@gmail.com","0762912034","48,thalwaththa,\ngonawaal","0762912034","NIC20026840384579","w123","2026-01-13 02:21:05");
INSERT INTO patreg VALUES("7","karuna","wathi","Female","1985-02-01","karu@gmail.com","0757872653","48,thalwaththa,\ngonawaal","0757872653","NIC2002684038422","k123","2026-01-13 10:09:43");
INSERT INTO patreg VALUES("8","mallika","fernando","Female","2025-12-28","mal123@gmail.com","0757872653","48,thalwaththa,\ngonawaal","0762912034","NIC20026840384577","m123","2026-01-13 11:56:16");
INSERT INTO patreg VALUES("9","Dinuvi","Ranasinghe","Female","2026-01-14","dinu@gmil.come","0757872653","48,thalwaththa,\ngonawaal","0757872653","NIC2002684038","123","2026-01-13 16:30:25");
INSERT INTO patreg VALUES("10","Dinuvi","Ranasinghe","Female","2025-12-28","ashok@gmail.com","0757872653","48,thalwaththa,\ngonawaal","0757872653","NIC20026840384","123","2026-01-13 16:55:15");
INSERT INTO patreg VALUES("11","ddd","ttt","Male","0022-02-02","aasshok@mail","0757872653","48,thalwaththa,\ngonawaal","","NIC20026840384232","123456","2026-01-13 20:34:48");
INSERT INTO patreg VALUES("12","wihga","lalith","Male","1999-12-02","wihi@gmail.com","0757872653","48,thalwaththa,\ngonawaal","","NIC2002684052","123456","2026-01-13 21:20:50");



DROP TABLE IF EXISTS paymenttb;

CREATE TABLE `paymenttb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `doctor` varchar(50) NOT NULL,
  `fees` decimal(10,2) NOT NULL,
  `pay_date` date NOT NULL,
  `pay_status` varchar(20) DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  CONSTRAINT `paymenttb_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `patreg` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

INSERT INTO paymenttb VALUES("1","1","1","NIC123456789","Ram Kumar","Ashok","500.00","2025-10-29","Paid","Cash","REC001","2026-01-12 22:34:11");
INSERT INTO paymenttb VALUES("2","2","2","NIC987654321","Alia Bhatt","Arun","600.00","2025-10-30","Paid","Credit Card","REC002","2026-01-12 22:34:11");
INSERT INTO paymenttb VALUES("3","3","3","NIC111222333","Shahrukh Khan","Dinesh","700.00","2025-11-01","Pending","Pending","PENDING","2026-01-12 22:34:11");
INSERT INTO paymenttb VALUES("4","4","4","NIC200268403842","Dinuvi Ranasinghe","Arun","600.00","2026-01-14","Paid","Cash","REC004","2026-01-13 00:26:05");
INSERT INTO paymenttb VALUES("5","5","5","NIC200268403845","kavee lalitha","Ashok","500.00","2026-01-13","Pending","","","2026-01-13 10:10:50");
INSERT INTO paymenttb VALUES("6","4","6","NIC200268403842","Dinuvi Ranasinghe","Arun","600.00","2026-01-14","Paid","","REC006","2026-01-13 11:57:35");
INSERT INTO paymenttb VALUES("7","2","7","NIC987654321","Alia Bhatt","Ashok","500.00","2026-01-14","Pending","","","2026-01-13 17:31:28");
INSERT INTO paymenttb VALUES("8","9","8","NIC2002684038","Dinuvi Ranasinghe","Dinesh","700.00","2026-01-13","Pending","","","2026-01-13 21:21:24");



DROP TABLE IF EXISTS prestb;

CREATE TABLE `prestb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor` varchar(50) NOT NULL,
  `pid` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `appdate` date DEFAULT NULL,
  `apptime` time DEFAULT NULL,
  `disease` varchar(100) DEFAULT NULL,
  `allergy` varchar(100) DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `emailStatus` varchar(50) DEFAULT 'Not Sent',
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  CONSTRAINT `prestb_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `patreg` (`pid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO prestb VALUES("1","Ashok","1","1","Ram","Kumar","NIC123456789","2025-10-29","","Fever","None","Take paracetamol 500mg twice daily","Sent to Hospital Pharmacy","2026-01-12 22:34:11");
INSERT INTO prestb VALUES("2","Arun","2","2","Alia","Bhatt","NIC987654321","2025-10-30","","Cold","None","Take vitamin C and rest","Sent to Patient Contact (SMS)","2026-01-12 22:34:11");



DROP TABLE IF EXISTS reception;

CREATE TABLE `reception` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO reception VALUES("1","reception1","1234");
INSERT INTO reception VALUES("2","reception2","abcd");



DROP TABLE IF EXISTS roomtb;

CREATE TABLE `roomtb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_no` varchar(10) NOT NULL,
  `bed_no` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO roomtb VALUES("1","101","1","Normal","Available","2026-01-12 22:34:11");
INSERT INTO roomtb VALUES("2","101","2","Normal","Occupied","2026-01-12 22:34:11");
INSERT INTO roomtb VALUES("3","102","1","VIP","Available","2026-01-12 22:34:11");
INSERT INTO roomtb VALUES("4","102","2","VIP","Occupied","2026-01-12 22:34:11");
INSERT INTO roomtb VALUES("5","103","1","ICU","Available","2026-01-12 22:34:11");
INSERT INTO roomtb VALUES("6","10","1","ICU","Occupied","2026-01-13 17:34:21");



DROP TABLE IF EXISTS scheduletb;

CREATE TABLE `scheduletb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_name` varchar(50) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `role` varchar(50) NOT NULL,
  `day` varchar(20) NOT NULL,
  `shift` varchar(20) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `staff_type` varchar(10) NOT NULL DEFAULT 'staff',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;

INSERT INTO scheduletb VALUES("13","dinuvi","STF003","Receptionist","Monday","Morning","2026-01-13 21:07:58","staff");
INSERT INTO scheduletb VALUES("14","Arunna","DOC002","Doctor","Friday","Night","2026-01-13 21:24:56","staff");



DROP TABLE IF EXISTS stafftb;

CREATE TABLE `stafftb` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO stafftb VALUES("STF001","Ramesh","Nurse","ramesh@gmail.com","0771112222","ramesh123","2026-01-12 22:34:11");
INSERT INTO stafftb VALUES("STF002","Sita","Receptionist","sita@gmail.com","0773334444","sita123","2026-01-12 22:34:11");
INSERT INTO stafftb VALUES("STF003","dinuvi","Receptionist","adgaaa@gmail.come","0757872653","123","2026-01-13 12:43:04");
INSERT INTO stafftb VALUES("STF004","asok","Nurse","aaadga@gmail.come","0757872653","123","2026-01-13 20:29:57");
INSERT INTO stafftb VALUES("STF005","wimukthi","Admin","wimu@gmail.com","0757872653","123","2026-01-13 21:17:49");



