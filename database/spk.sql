-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2025 at 07:41 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spk`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` varchar(11) NOT NULL,
  `password` varchar(11) NOT NULL,
  `admin_Fname` varchar(40) NOT NULL,
  `admin_Lname` varchar(40) NOT NULL,
  `email` varchar(40) NOT NULL,
  `email_password` varchar(16) NOT NULL,
  `admin_address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `password`, `admin_Fname`, `admin_Lname`, `email`, `email_password`, `admin_address`) VALUES
('admin', '1234', 'พิมาน', 'รัตนโชติ', 'SPKdormitory@gmail.com', 'IdspkSPK', '209/19 ถนนประชาสุข แขวงรัชดาภิเษก เขตดินแดง กทม.10400');

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

CREATE TABLE `contract` (
  `contract_id` varchar(11) NOT NULL,
  `room_id` varchar(11) NOT NULL,
  `contract_name` varchar(40) NOT NULL,
  `contract_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `contract_start` date NOT NULL,
  `contract_end` date NOT NULL,
  `contract_img` blob NOT NULL,
  `deposit` varchar(11) NOT NULL,
  `contract_status` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contract`
--

INSERT INTO `contract` (`contract_id`, `room_id`, `contract_name`, `contract_detail`, `contract_start`, `contract_end`, `contract_img`, `deposit`, `contract_status`) VALUES
('contract01', '1A', 'contract01', 'test', '2567-11-01', '2568-10-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract02', '2A', 'contract02', 'test', '2567-11-01', '2568-10-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'สิ้นสุด'),
('contract03', '3A', 'contract03', 'test', '2567-01-01', '2567-12-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract04', '1B', 'contract03', 'test', '2567-02-01', '2568-01-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract05', '2B', 'contract05', 'test', '2567-02-01', '2568-01-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract06', '3B', 'contract06', 'test', '2567-03-01', '2568-02-28', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract07', '1C', 'contract07', 'test', '2567-01-01', '2567-12-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract08', '2C', 'contract08', 'test', '2567-02-01', '2568-01-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract09', '3C', 'contract09', 'test', '2567-04-01', '2568-03-31', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล'),
('contract10', '4D', 'contract010', 'test', '2567-12-01', '2568-11-30', 0x636f6e74726163742f39643330663634333139383736353063313736386338393763643831616339362e6a7067, '5000', 'กำลังมีผล');

-- --------------------------------------------------------

--
-- Table structure for table `contract_status`
--

CREATE TABLE `contract_status` (
  `contract_statusID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `contract_status` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contract_status`
--

INSERT INTO `contract_status` (`contract_statusID`, `contract_status`) VALUES
('status01', 'กำลังมีผล'),
('status02', 'สิ้นสุด');

-- --------------------------------------------------------

--
-- Table structure for table `contract_user`
--

CREATE TABLE `contract_user` (
  `id` int NOT NULL,
  `contract_id` varchar(20) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contract_user`
--

INSERT INTO `contract_user` (`id`, `contract_id`, `user_id`) VALUES
(17, 'contract03', 'user04'),
(18, 'contract03', 'user05'),
(19, 'contract04', 'user06'),
(22, 'contract05', 'user07'),
(23, 'contract06', 'user08'),
(24, 'contract01', 'user02'),
(25, 'contract01', 'user03'),
(27, 'contract07', 'user09'),
(28, 'contract07', 'user010'),
(29, 'contract08', 'user011'),
(30, 'contract09', 'user012'),
(31, 'contract10', 'user013'),
(32, 'contract02', 'user01');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `equipment_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `equipment_price` varchar(10) NOT NULL,
  `equipment_img` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_detail`, `equipment_price`, `equipment_img`) VALUES
('ตู้เย็น', 'ตู้เย็น 458 ลิตร', '10000', 0x65717569706d656e742fe0b895e0b8b9e0b989e0b980e0b8a2e0b987e0b8992e6a7067),
('ตู้เสื้อผ้า', 'ตู้เสื้อผ้าขนาด 2 เมตร', '6500', 0x65717569706d656e742fe0b895e0b8b9e0b989e0b980e0b8aae0b8b7e0b989e0b8ade0b89ce0b989e0b8b22e6a7067),
('เตียง', 'เตียงนอน 6 ฟุต', '5000', 0x65717569706d656e742fe0b980e0b895e0b8b5e0b8a2e0b8872e6a7067),
('เร้าท์เตอร์', 'เร้าท์เตอร์ wifi', '1500', 0x65717569706d656e742fe0b980e0b8a3e0b989e0b8b2e0b897e0b98ce0b980e0b895e0b8ade0b8a3e0b98ce0b984e0b8a7e0b984e0b89f2e6a7067),
('โต๊ะทำงาน', 'โต๊ะทำงาน ikea', '1000', 0x65717569706d656e742fe0b982e0b895e0b98ae0b8b02e6a7067);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `room_id` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `water_cost` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `electric_cost` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `rent_cost` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `penalty_service_cost` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `cost_detail` text NOT NULL,
  `total_cost` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_img` blob,
  `payment_typeID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `payment_statusID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `invoice_month` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_id`, `room_id`, `water_cost`, `electric_cost`, `rent_cost`, `penalty_service_cost`, `cost_detail`, `total_cost`, `payment_date`, `payment_img`, `payment_typeID`, `invoice_date`, `payment_statusID`, `invoice_month`) VALUES
('invoice0001', '1A', '300', '2828', '5000', '300.00', 'ค่าซ่อมมุ้งลวด 300 บาท', '8428.00', '2567-12-03', 0x34383335363839333463653232363761386536333034366534353562663561642e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0002', '2A', '150', '3000', '5000', '200.00', 'ค่าคีย์การ์ด 100 บาท\r\nค่ากุญแจ 100 บาท', '8350.00', '2567-12-01', 0x64663335633266636336633239333131326530333730353730336633316466362e6a7067, 'PS_status01', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0003', '3A', '300', '2800', '5000', '', '', '8100', '2567-12-04', 0x66386663613266613931613932336361376365353538393137366138386661302e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0004', '1B', '150', '2205', '5000', '200.00', 'ค่าแม่บ้าน 200 บาท', '7555.00', '2567-12-03', 0x64663335633266636336633239333131326530333730353730336633316466362e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0005', '2B', '150', '1500', '5000', '300.00', 'ค่าแม่บ้านทำความสะอาด 300 บาท', '6950.00', '2567-12-02', 0x33376532366630623331623338396563663866346263626134336135643037652e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0006', '3B', '150', '1845', '5000', '', '', '6995', '2567-12-01', 0x64663335633266636336633239333131326530333730353730336633316466362e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0007', '1C', '300', '2954', '5000', '200.00', 'ค่าแม่บ้านทำความสะอาด', '8454.00', '2567-12-03', 0x34383335363839333463653232363761386536333034366534353562663561642e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0008', '2C', '150', '1754', '5000', '', '', '6904', '2567-12-03', 0x66386663613266613931613932336361376365353538393137366138386661302e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0009', '3C', '150', '2460', '5000', '200.00', 'ค่าซ่อมลูกบิดประตู 200 บาท', '7810.00', '2567-12-03', 0x64663335633266636336633239333131326530333730353730336633316466362e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0010', '4D', '150', '2330', '5000', '', '', '7480', '2567-12-02', 0x34383335363839333463653232363761386536333034366534353562663561642e6a7067, 'PS_status02', '2567-12-01', 'P_status02', 'พฤศจิกายน'),
('invoice0011', '1A', '300', '1680.00', '5000', '', '', '6980', '3111-01-01', 0x66386663613266613931613932336361376365353538393137366138386661302e6a7067, 'PS_status02', '2567-12-31', 'P_status01', 'ธันวาคม');

-- --------------------------------------------------------

--
-- Table structure for table `meter`
--

CREATE TABLE `meter` (
  `meter_id` varchar(11) NOT NULL,
  `room_id` varchar(11) NOT NULL,
  `meter_month` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `meter_value` varchar(11) NOT NULL,
  `meter_rate` varchar(11) NOT NULL,
  `meter_price` varchar(11) NOT NULL,
  `meter_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meter`
--

INSERT INTO `meter` (`meter_id`, `room_id`, `meter_month`, `meter_value`, `meter_rate`, `meter_price`, `meter_date`) VALUES
('meter010', '3C', 'พฤศจิกายน', '22569', '8', '2460', '2567-11-30'),
('meter011', '4D', 'พฤศจิกายน', '23144', '8', '2330', '2567-11-30'),
('meter02', '2A', 'พฤศจิกายน', '24088', '8', '3000', '2567-11-30'),
('meter03', '3A', 'พฤศจิกายน', '22051', '8', '2800', '2567-11-30'),
('meter04', '1A', 'พฤศจิกายน', '23448', '8', '2828', '2567-11-30'),
('meter05', '2B', 'พฤศจิกายน', '23222', '8', '1500', '2567-11-30'),
('meter06', '1B', 'พฤศจิกายน', '22358', '8', '2205', '2567-11-30'),
('meter07', '3B', 'พฤศจิกายน', '22565', '8', '1845', '2567-11-30'),
('meter08', '2C', 'พฤศจิกายน', '23784', '8', '1754', '2567-11-30'),
('meter09', '1C', 'พฤศจิกายน', '23414', '8', '2954', '2567-11-30'),
('meter10', '1A', 'ธันวาคม', '23658', '8', '1680.00', '2567-12-31'),
('meter11', '2A', 'ธันวาคม', '24355', '8', '2136.00', '2567-12-31'),
('meter12', '3A', 'ธันวาคม', '22324', '8', '2184.00', '2567-12-31'),
('meter13', '1B', 'ธันวาคม', '22654', '8', '2368.00', '2567-12-31'),
('meter14', '2B', 'ธันวาคม', '23398', '8', '1408.00', '2567-12-31'),
('meter15', '3B', 'ธันวาคม', '22788', '8', '1784.00', '2567-12-31'),
('meter16', '1C', 'ธันวาคม', '23852', '8', '3504.00', '2567-12-31'),
('meter17', '2C', 'ธันวาคม', '23988', '8', '1632.00', '2567-12-31'),
('meter18', '3C', 'ธันวาคม', '22788', '8', '1752.00', '2567-12-31'),
('meter19', '4D', 'ธันวาคม', '23321', '8', '1416.00', '2567-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `parcel`
--

CREATE TABLE `parcel` (
  `parcel_id` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `parcel_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `room_id` varchar(11) NOT NULL,
  `received_date` date NOT NULL,
  `parcel_statusID` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcel`
--

INSERT INTO `parcel` (`parcel_id`, `parcel_detail`, `room_id`, `received_date`, `parcel_statusID`) VALUES
('parcel01', 'test01', '1A', '2567-12-07', 'pa_status01'),
('parcel02', 'test02', '2A', '2567-12-07', 'pa_status01'),
('parcel03', 'test03', '3A', '2567-12-08', 'pa_status02'),
('parcel04', 'กล่องสีฟ้า', '1A', '2567-12-11', 'pa_status02'),
('parcel05', 'test', '1B', '2567-12-11', 'pa_status02');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_status`
--

CREATE TABLE `parcel_status` (
  `parcel_statusID` varchar(11) NOT NULL,
  `parcel_status_name` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcel_status`
--

INSERT INTO `parcel_status` (`parcel_statusID`, `parcel_status_name`) VALUES
('pa_status01', 'รับแล้ว'),
('pa_status02', 'ยังไม่รับ');

-- --------------------------------------------------------

--
-- Table structure for table `payment_status`
--

CREATE TABLE `payment_status` (
  `payment_statusID` varchar(11) NOT NULL,
  `payment_status_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_status`
--

INSERT INTO `payment_status` (`payment_statusID`, `payment_status_name`) VALUES
('P_status01', 'ยังไม่ชำระ'),
('P_status02', 'ชำระแล้ว'),
('P_status03', 'รอการตรวจสอบ');

-- --------------------------------------------------------

--
-- Table structure for table `payment_type`
--

CREATE TABLE `payment_type` (
  `payment_typeID` varchar(11) NOT NULL,
  `payment_type_name` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_type`
--

INSERT INTO `payment_type` (`payment_typeID`, `payment_type_name`) VALUES
('PS_status01', 'เงินสด'),
('PS_status02', 'เงินโอน');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` varchar(11) NOT NULL,
  `room_status` varchar(11) NOT NULL,
  `room_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `room_price` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_status`, `room_detail`, `room_price`) VALUES
('1A', 'ไม่ว่าง', 'ชั้น 1 ห้องด้านหน้า', '5000'),
('1B', 'ไม่ว่าง', 'ชั้น 2 ห้องหน้าสุด', '5000'),
('1C', 'ไม่ว่าง', 'ชั้น 3 ห้องด้านหน้า', '5000'),
('2A', 'ว่าง', 'ชั้น 1 ห้องกลาง ติดบันไดทางขึ้นชั้น 2', '5000'),
('2B', 'ไม่ว่าง', 'ชั้น 2 ห้องกลางติดบันไดทางขึ้นชั้น 3', '5000'),
('2C', 'ไม่ว่าง', 'ชั้น 3 ห้องกลางติดบันไดทางขึ้นชั้น 4', '5000'),
('3A', 'ไม่ว่าง', 'ชั้น 1 ห้องในสุด', '5000'),
('3B', 'ไม่ว่าง', 'ชั้น 2 ห้องในสุด', '5000'),
('3C', 'ไม่ว่าง', 'ชั้น 3 ห้องในสุด', '5000'),
('4D', 'ไม่ว่าง', 'ชั้น 4 ห้องด้านหน้า', '5000');

-- --------------------------------------------------------

--
-- Table structure for table `room_equipment`
--

CREATE TABLE `room_equipment` (
  `room_eqID` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `equipment_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_equipment`
--

INSERT INTO `room_equipment` (`room_eqID`, `room_id`, `equipment_id`) VALUES
('ตู้เย็น01', '1A', 'ตู้เย็น'),
('ตู้เย็น02', '2A', 'ตู้เย็น'),
('ตู้เย็น03', '3A', 'ตู้เย็น'),
('ตู้เย็น04', '1B', 'ตู้เย็น'),
('ตู้เย็น05', '2B', 'ตู้เย็น'),
('ตู้เย็น06', '3B', 'ตู้เย็น'),
('ตู้เย็น07', '1C', 'ตู้เย็น'),
('ตู้เย็น08', '2C', 'ตู้เย็น'),
('ตู้เย็น09', '3C', 'ตู้เย็น'),
('ตู้เย็น10', '4D', 'ตู้เย็น'),
('ตู้เสื้อผ้า01', '1A', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า02', '2A', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า03', '3A', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า04', '1B', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า05', '2B', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า06', '3B', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า07', '1C', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า08', '2C', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า09', '3C', 'ตู้เสื้อผ้า'),
('ตู้เสื้อผ้า10', '4D', 'ตู้เสื้อผ้า'),
('เตียง01', '1A', 'เตียง'),
('เตียง02', '2A', 'เตียง'),
('เตียง03', '3A', 'เตียง'),
('เตียง04', '1B', 'เตียง'),
('เตียง05', '2B', 'เตียง'),
('เตียง06', '3B', 'เตียง'),
('เตียง07', '1C', 'เตียง'),
('เตียง08', '2C', 'เตียง'),
('เตียง09', '3C', 'เตียง'),
('เตียง10', '4D', 'เตียง'),
('เร้าท์เตอร์01', '1A', 'เร้าท์เตอร์'),
('เร้าท์เตอร์02', '2A', 'เร้าท์เตอร์'),
('เร้าท์เตอร์03', '3A', 'เร้าท์เตอร์'),
('เร้าท์เตอร์04', '1B', 'เร้าท์เตอร์'),
('เร้าท์เตอร์05', '2B', 'เร้าท์เตอร์'),
('เร้าท์เตอร์06', '3B', 'เร้าท์เตอร์'),
('เร้าท์เตอร์07', '1C', 'เร้าท์เตอร์'),
('เร้าท์เตอร์08', '2C', 'เร้าท์เตอร์'),
('เร้าท์เตอร์09', '3C', 'เร้าท์เตอร์'),
('เร้าท์เตอร์10', '4D', 'เร้าท์เตอร์'),
('โต๊ะทำงาน01', '1A', 'โต๊ะทำงาน'),
('โต๊ะทำงาน02', '2A', 'โต๊ะทำงาน'),
('โต๊ะทำงาน03', '3A', 'โต๊ะทำงาน'),
('โต๊ะทำงาน04', '1B', 'โต๊ะทำงาน'),
('โต๊ะทำงาน05', '2B', 'โต๊ะทำงาน'),
('โต๊ะทำงาน06', '3B', 'โต๊ะทำงาน'),
('โต๊ะทำงาน07', '1C', 'โต๊ะทำงาน'),
('โต๊ะทำงาน08', '2C', 'โต๊ะทำงาน'),
('โต๊ะทำงาน09', '3C', 'โต๊ะทำงาน'),
('โต๊ะทำงาน10', '4D', 'โต๊ะทำงาน');

-- --------------------------------------------------------

--
-- Table structure for table `room_status`
--

CREATE TABLE `room_status` (
  `room_statusID` varchar(11) NOT NULL,
  `room_status` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `room_status`
--

INSERT INTO `room_status` (`room_statusID`, `room_status`) VALUES
('R_status01', 'ว่าง'),
('R_status02', 'ไม่ว่าง');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` varchar(11) NOT NULL,
  `contract_id` varchar(11) NOT NULL,
  `block_id` varchar(11) NOT NULL,
  `schedule_typeID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `schedule_detail` text NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_statusID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `service_statusID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`schedule_id`, `contract_id`, `block_id`, `schedule_typeID`, `schedule_detail`, `schedule_date`, `schedule_statusID`, `service_statusID`, `description`) VALUES
('schedule01', 'contract01', 'block01', 'sc_type01', 'ล้างแอร์', '2567-12-09', 'sc_status03', NULL, 'เลยวันมาแล้ว'),
('schedule02', 'contract02', 'block07', 'sc_type01', 'ล้างแอร์', '2567-12-10', 'sc_status02', 'sv_status02', ''),
('schedule03', 'contract01', 'block09', 'sc_type02', 'ทำความสะอาด', '2567-12-13', 'sc_status02', 'sv_status03', ''),
('schedule04', 'contract01', 'block05', 'sc_type02', 'นัดทำความสะอาด', '2567-12-27', 'sc_status02', 'sv_status02', 'เรียบร้อย'),
('schedule05', 'contract02', 'block04', 'sc_type02', 'ทำความสะอาด', '2567-12-13', 'sc_status01', 'sv_status03', ''),
('schedule06', 'contract01', 'block08', 'sc_type02', 'ทำความสะอาด', '2567-12-19', 'sc_status01', 'sv_status03', ''),
('schedule07', 'contract01', 'block07', 'sc_type01', 'ซ่อมแอร์', '2568-01-07', 'sc_status01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_status`
--

CREATE TABLE `schedule_status` (
  `schedule_statusID` varchar(11) NOT NULL,
  `schedule_status_name` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedule_status`
--

INSERT INTO `schedule_status` (`schedule_statusID`, `schedule_status_name`) VALUES
('sc_status01', 'รอการอนุมัติ'),
('sc_status02', 'อนุมัติ'),
('sc_status03', 'ไม่อนุมัติ');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_type`
--

CREATE TABLE `schedule_type` (
  `schedule_typeID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `schedule_type_name` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedule_type`
--

INSERT INTO `schedule_type` (`schedule_typeID`, `schedule_type_name`) VALUES
('sc_type01', 'ช่างซ่อมบำรุง'),
('sc_type02', 'แม่บ้านทำความสะอาด');

-- --------------------------------------------------------

--
-- Table structure for table `service_status`
--

CREATE TABLE `service_status` (
  `service_statusID` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `service_name` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_status`
--

INSERT INTO `service_status` (`service_statusID`, `service_name`) VALUES
('sv_status01', 'กำลังดำเนินการ'),
('sv_status02', 'ดำเนินการเรียบร้อย'),
('sv_status03', 'ยังไม่ดำเนินการ');

-- --------------------------------------------------------

--
-- Table structure for table `time_block`
--

CREATE TABLE `time_block` (
  `block_id` varchar(11) NOT NULL,
  `block_name` varchar(40) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_block`
--

INSERT INTO `time_block` (`block_id`, `block_name`, `start_time`, `end_time`) VALUES
('block01', '8 โมงเช้า', '08:00:00', '08:59:00'),
('block02', '9 โมงเช้า', '09:00:00', '09:59:00'),
('block03', '10 โมงเช้า', '10:00:00', '10:59:00'),
('block04', '11 โมงเช้า', '11:00:00', '11:59:00'),
('block05', 'เที่ยงวัน', '00:00:00', '00:59:00'),
('block06', 'บ่ายโมง', '13:00:00', '13:59:00'),
('block07', 'บ่าย 2 โมง', '14:00:00', '14:59:00'),
('block08', 'บ่าย 3 โมง', '15:00:00', '15:59:00'),
('block09', '4 โมงเย็น', '16:00:00', '16:59:00'),
('block10', '5 โมงเย็น', '17:00:00', '17:59:00'),
('block11', '6 โมงเย็น', '18:00:00', '18:59:00'),
('block12', 'หนึ่งทุ่ม', '19:00:00', '19:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `password` varchar(16) NOT NULL,
  `email` varchar(40) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `user_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `password`, `email`, `phone`, `user_address`) VALUES
('user01', 'สมศรี', 'กระจ่างจิต', '1111', 'prince38328@gmail.com', '0952684067', '21-29 ถ.นาคราช \r\nแขวงคลองมหานาคเขตป้อมปราบ \r\nจ.กรุงเทพฯ 10100'),
('user010', 'ภาภร', 'นพรัตน์พร', '1010', 'MaiiMoo@gmail.com', '0869998363', '69/29 หมู่ 7\r\nต.วังเย็น อ.แปลงยาว\r\nจ.ฉะเชิงเทรา 24190\r\n'),
('user011', 'นิยา', 'ธนรัชต์กุล', 'u1111', 'joobjiib1887@gmail.com', '0950553983', '144 ถ.สำราญราษฎร์\r\nต.โนนสูง อ.โนนสูง\r\nจ.นครราชสีมา 30160'),
('user012', 'กันสิตา', 'เจริญผลวัฒนา', 'u1212', 'fangkow321@gmail.com', '0869357998', '42/1 ม.1 \r\nถ.สุขุมวิท ต.พลิ้ว\r\nอ.แหลมสิงห์ จ.จันทบุรี\r\n22190'),
('user013', 'เมริสา', 'พิชัยยุทธสักดิ์', 'u1313', 'Merisa_Pichai@gmail.com', '0687167406', '49/2 หมู่ 4 \r\nถ.ปิ่นเกล้า-นครชัยศรี\r\nเขตตลิ่งชัน จ.กรุงเทพฯ 10170'),
('user02', 'กิตติพร', 'เจริญวัฒนา ', '2222', 'prince38328@gmail.com', '0974639161', '88 หมู่3 ถ.แจ้งวัฒนะ \r\nทุ่งสองห้อง เขตหลักสี่\r\nจ.กรุงเทพฯ 10210'),
('user03', 'ณิชาภา', 'สุขเสรี', '3333', 'dynastes.326@hotmail.com', '0924370908', '90 หมู่2 ต.ลำพะเนียง\r\nอ.บ้านแพรก\r\nจ.พระนครศรีอยุธยา\r\n13240\r\n'),
('user04', 'กันยา', 'รัตนกิจโกศล', '4444', 'lfee5r@gmail.com', '0924371234', '306 ม.1 ถ.ปากบาง-บางงา \r\nต.พรหมบุรีอ.พรหมบุรี\r\nจ.สิงห์บุรี 16160'),
('user05', 'ญาสิตา', 'ประเสริฐชัยวัฒน์ ', '5555', 'rayrees@gmail.com', '0820570706', '458 หมู่ 3 \r\nถ.รามอินทราอนุสาวรีย์\r\nเขตบางเขน จ.กรุงเทพฯ 10220'),
('user06', 'วรรณภา', 'ตันฑการุณ', '6666', 'alice0859@gmail.com', '0992025926', '110 หมู่ 2 \r\nถ.นิมิตรใหม่\r\nแขวงมีนบุรี เขตมีนบุรี\r\nจ.กรุงเทพฯ 10510'),
('user07', 'ธิติรัตน์', 'ศิริโกศล', '7777', 'kob1878@gmail.com', '0857392363', '323 หมู่1 ถ.รัตนราช \r\nต.บางบ่อ อ.บางบ่อ\r\nจ.สมุทรปราการ 10560'),
('user08', 'อภิญญา', 'พงษ์พัฒนโยธิน', '8888', 'april_blue08@gmail.com', '0929335161', '79 ถ.ขุนลุมประพาส\r\nต.จองคา อ.เมือง\r\nจ.แมฮ่องสอน 58000'),
('user09', 'ชุติกาญจน์', 'ก้องเกษมทรัพย์', '9999', 'beam1997@gmail.com', '0661918416', '406/1 ถ.เจริญเมือง \r\nต.วัดเกตุ อ.เมือง\r\nจ.เชียงใหม่ 50000');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `contract`
--
ALTER TABLE `contract`
  ADD PRIMARY KEY (`contract_id`),
  ADD KEY `contract_status` (`contract_status`),
  ADD KEY `FKroom_id` (`room_id`);

--
-- Indexes for table `contract_status`
--
ALTER TABLE `contract_status`
  ADD PRIMARY KEY (`contract_statusID`),
  ADD KEY `contract_status` (`contract_status`);

--
-- Indexes for table `contract_user`
--
ALTER TABLE `contract_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cu_contract` (`contract_id`),
  ADD KEY `cu_user` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `payment_status` (`payment_statusID`,`payment_typeID`),
  ADD KEY `invoice_payTYPE` (`payment_typeID`);

--
-- Indexes for table `meter`
--
ALTER TABLE `meter`
  ADD PRIMARY KEY (`meter_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `parcel`
--
ALTER TABLE `parcel`
  ADD PRIMARY KEY (`parcel_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `parcel_statusID` (`parcel_statusID`);

--
-- Indexes for table `parcel_status`
--
ALTER TABLE `parcel_status`
  ADD PRIMARY KEY (`parcel_statusID`);

--
-- Indexes for table `payment_status`
--
ALTER TABLE `payment_status`
  ADD PRIMARY KEY (`payment_statusID`);

--
-- Indexes for table `payment_type`
--
ALTER TABLE `payment_type`
  ADD PRIMARY KEY (`payment_typeID`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `room_status` (`room_status`);

--
-- Indexes for table `room_equipment`
--
ALTER TABLE `room_equipment`
  ADD PRIMARY KEY (`room_eqID`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `room_status`
--
ALTER TABLE `room_status`
  ADD PRIMARY KEY (`room_statusID`),
  ADD KEY `room_status` (`room_status`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `schedule_type` (`schedule_typeID`),
  ADD KEY `schedule_status` (`schedule_statusID`),
  ADD KEY `schedule_service` (`service_statusID`);

--
-- Indexes for table `schedule_status`
--
ALTER TABLE `schedule_status`
  ADD PRIMARY KEY (`schedule_statusID`);

--
-- Indexes for table `schedule_type`
--
ALTER TABLE `schedule_type`
  ADD PRIMARY KEY (`schedule_typeID`);

--
-- Indexes for table `service_status`
--
ALTER TABLE `service_status`
  ADD PRIMARY KEY (`service_statusID`);

--
-- Indexes for table `time_block`
--
ALTER TABLE `time_block`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contract_user`
--
ALTER TABLE `contract_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `FKcontract_status` FOREIGN KEY (`contract_status`) REFERENCES `contract_status` (`contract_status`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FKroom_id` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `contract_user`
--
ALTER TABLE `contract_user`
  ADD CONSTRAINT `cu_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`contract_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cu_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_paySTATUS` FOREIGN KEY (`payment_statusID`) REFERENCES `payment_status` (`payment_statusID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `invoice_payTYPE` FOREIGN KEY (`payment_typeID`) REFERENCES `payment_type` (`payment_typeID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `invoice_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `meter`
--
ALTER TABLE `meter`
  ADD CONSTRAINT `meter_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `parcel`
--
ALTER TABLE `parcel`
  ADD CONSTRAINT `parcel_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `parcel_status` FOREIGN KEY (`parcel_statusID`) REFERENCES `parcel_status` (`parcel_statusID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_status` FOREIGN KEY (`room_status`) REFERENCES `room_status` (`room_status`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `room_equipment`
--
ALTER TABLE `room_equipment`
  ADD CONSTRAINT `room_equipment_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract` (`contract_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_service` FOREIGN KEY (`service_statusID`) REFERENCES `service_status` (`service_statusID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_status` FOREIGN KEY (`schedule_statusID`) REFERENCES `schedule_status` (`schedule_statusID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_time` FOREIGN KEY (`block_id`) REFERENCES `time_block` (`block_id`),
  ADD CONSTRAINT `schedule_type` FOREIGN KEY (`schedule_typeID`) REFERENCES `schedule_type` (`schedule_typeID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
