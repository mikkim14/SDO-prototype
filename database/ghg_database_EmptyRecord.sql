-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2026 at 03:09 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ghg_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `campus` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `electricity_consumption`
--

CREATE TABLE `electricity_consumption` (
  `id` int(11) NOT NULL,
  `campus` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `month` varchar(255) DEFAULT NULL,
  `quarter` varchar(255) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `prev_reading` double DEFAULT NULL,
  `current_reading` double DEFAULT NULL,
  `consumption_in_kwh` double DEFAULT NULL,
  `multiplier` double DEFAULT NULL,
  `total_amount` double DEFAULT NULL,
  `consumption` double DEFAULT NULL,
  `price_per_kwh` double DEFAULT NULL,
  `kg_co2_per_kwh` double DEFAULT NULL,
  `t_co2_per_kwh` double DEFAULT NULL,
  `tree_offset` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_emissions`
--

CREATE TABLE `fuel_emissions` (
  `id` int(11) NOT NULL,
  `campus` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `driver` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `vehicle_equipment` varchar(255) DEFAULT NULL,
  `plate_no` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `transaction_no` varchar(50) DEFAULT NULL,
  `odometer` int(11) DEFAULT NULL,
  `quantity_liters` float(5,2) DEFAULT NULL,
  `total_amount` float DEFAULT NULL,
  `co2_emission` float DEFAULT NULL,
  `nh4_emission` float DEFAULT NULL,
  `n2o_emission` float DEFAULT NULL,
  `total_emission` float DEFAULT NULL,
  `total_emission_t` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblaccommodation`
--

CREATE TABLE `tblaccommodation` (
  `id` int(11) NOT NULL,
  `Campus` varchar(255) NOT NULL,
  `Office` varchar(255) DEFAULT NULL,
  `YearTransact` year(4) NOT NULL,
  `TravellerName` varchar(255) DEFAULT NULL,
  `TravelPurpose` varchar(255) DEFAULT NULL,
  `TravelDateFrom` date DEFAULT NULL,
  `TravelDateTo` date DEFAULT NULL,
  `Country` varchar(255) DEFAULT NULL,
  `TravelType` varchar(255) DEFAULT NULL,
  `NumOccupiedRoom` int(11) DEFAULT NULL,
  `NumNightPerRoom` int(11) DEFAULT NULL,
  `Factor` decimal(10,2) DEFAULT NULL,
  `GHGEmissionKGC02e` decimal(10,2) DEFAULT NULL,
  `GHGEmissionTC02e` decimal(10,2) DEFAULT NULL,
  `Month` varchar(50) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblflight`
--

CREATE TABLE `tblflight` (
  `ID` int(11) NOT NULL,
  `Campus` varchar(255) DEFAULT NULL,
  `Office` char(20) DEFAULT NULL,
  `Year` year(4) DEFAULT NULL,
  `TravellerName` varchar(30) DEFAULT NULL,
  `TravelPurpose` varchar(30) DEFAULT NULL,
  `TravelDate` date DEFAULT NULL,
  `DomesticInternational` varchar(30) DEFAULT NULL,
  `Origin` varchar(30) DEFAULT NULL,
  `Destination` varchar(30) DEFAULT NULL,
  `Class` varchar(30) DEFAULT NULL,
  `OnewayRoundTrip` varchar(30) DEFAULT NULL,
  `GHGEmissionKGC02e` float DEFAULT NULL,
  `GHGEmissionTC02e` double DEFAULT NULL,
  `Month` varchar(50) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblfoodwaste`
--

CREATE TABLE `tblfoodwaste` (
  `id` int(11) NOT NULL,
  `Campus` varchar(255) DEFAULT NULL,
  `YearTransaction` varchar(4) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL,
  `Office` varchar(255) DEFAULT NULL,
  `TypeOfFoodServed` varchar(255) DEFAULT NULL,
  `QuantityOfServing` double DEFAULT NULL,
  `GHGEmissionKGCO2e` double DEFAULT NULL,
  `GHGEmissionTCO2e` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbllpg`
--

CREATE TABLE `tbllpg` (
  `id` int(11) NOT NULL,
  `Campus` char(20) DEFAULT NULL,
  `Office` char(20) DEFAULT NULL,
  `YearTransact` year(4) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL,
  `ConcessionariesType` varchar(20) DEFAULT NULL,
  `TankQuantity` int(11) DEFAULT NULL,
  `TankWeight` float DEFAULT NULL,
  `TankVolume` float DEFAULT NULL,
  `TotalTankVolume` float DEFAULT NULL,
  `GHGEmissionKGCO2e` float DEFAULT NULL,
  `GHGEmissionTCO2e` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblsignin`
--

CREATE TABLE `tblsignin` (
  `userID` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `office` varchar(255) NOT NULL,
  `campus` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblsignin`
--

INSERT INTO `tblsignin` (`userID`, `username`, `office`, `campus`, `email`, `password`) VALUES
(34, 'csd', 'Central Sustainable Office', 'Central', 'central@gmail.com', 'csd1'),
(45, 'sdo-alangilan', 'Sustainable Development Office', 'Alangilan', 'sdoalangilan@gmail.com', 'sdoalangilan1'),
(67, 'sdo-nasugbu', 'Sustainable Development Office', 'ARASOF-Nasugbu', 'sdonasugbuu@gmail.com', 'sdonasugbu1'),
(68, 'sdo-balayan', 'Sustainable Development Office', 'Balayan', 'sdobalayan@gmail.com', 'sdobalayan1'),
(69, 'sdo-central', 'Sustainable Development Office', 'Central', 'sdocentral@gmail.com', 'sdocentral1'),
(70, 'sdo-malvar', 'Sustainable Development Office', 'JPLPC-Malvar', 'sdomalvar@gmail.com', 'sdomalvar1'),
(72, 'sdo-lipa', 'Sustainable Development Office', 'Lipa', 'sdolipa@gmail.com', 'sdolipa1'),
(73, 'sdo-lobo', 'Sustainable Development Office', 'Lobo', 'sdolobo@gmail.com', 'sdolobo1'),
(74, 'sdo-mabini', 'Sustainable Development Office', 'Mabini', 'sdomabini@gmail.com', 'sdomabini1'),
(75, 'sdo-pabloborbon', 'Sustainable Development Office', 'Pablo Borbon', 'sdopabloborbon@gmail.com', 'sdopabloborbon1'),
(76, 'sdo-rosario', 'Sustainable Development Office', 'Rosario', 'sdorosario@gmail.com', 'sdorosario1'),
(77, 'sdo-sanjuan', 'Sustainable Development Office', 'San Juan', 'sdosanjuan@gmail.com', 'sdosanjuan1'),
(78, 'emu-alangilan', 'Environmental Management Unit', 'Alangilan', 'emualangilan@gmail.com', 'emualangilan1'),
(79, 'emu-nasugbu', 'Environmental Management Unit', 'ARASOF-Nasugbu', 'emunasugbu@gmail.com', 'emunasugbu1'),
(80, 'emu-balayan', 'Environmental Management Unit', 'Balayan', 'emubalayan@gmail.com', 'emubalayan1'),
(81, 'emu-central', 'Environmental Management Unit', 'Central', 'emucentral@gmail.com', 'emucentral1'),
(82, 'emu-malvar', 'Environmental Management Unit', 'JPLPC-Malvar', 'emumalvar@gmail.com', 'emumalvar1'),
(83, 'emu-lemery', 'Environmental Management Unit', 'Lemery', 'emulemery@gmail.com', 'emulemery1'),
(84, 'emu-lipa', 'Environmental Management Unit', 'Lipa', 'emulipa@gmail.com', 'emulipa1'),
(86, 'emu-lobo', 'Environmental Management Unit', 'Lobo', 'emulobo@gmail.com', 'emulobo1'),
(87, 'emu-mabini', 'Environmental Management Unit', 'Mabini', 'emumabini@gmail.com', 'emumabini1'),
(88, 'emu-pabloborbon', 'Environmental Management Unit', 'Pablo Borbon', 'emupabloborbon@gmail.com', 'emupabloborbon1'),
(89, 'emu-rosario', 'Environmental Management Unit', 'Rosario', 'emurosario@gmail.com', 'emurosario1'),
(90, 'emu-sanjuan', 'Environmental Management Unit', 'San Juan', 'emusanjuan@gmail.com', 'emusanjuan1'),
(91, 'po-alangilan', 'Procurement Office', 'Alangilan', 'poalangilan@gmail.com', 'poalangilan1'),
(92, 'po-nasugbu', 'Procurement Office', 'ARASOF-Nasugbu', 'ponasugbu@gmail.com', 'ponasugbu1'),
(93, 'po-balayan', 'Procurement Office', 'Balayan', 'pobalayan@gmail.com', 'pobalayan1'),
(94, 'po-central', 'Procurement Office', 'Central', 'pocentral@gmail.com', 'pocentral1'),
(95, 'po-malvar', 'Procurement Office', 'JPLPC-Malvar', 'pomalvar@gmail.com', 'pomalvar1'),
(96, 'po-lemery', 'Procurement Office', 'Lemery', 'polemery@gmail.com', 'polemery1'),
(97, 'po-lipa', 'Procurement Office', 'Lipa', 'polipa@gmail.com', 'polipa1'),
(98, 'po-lobo', 'Procurement Office', 'Lobo', 'polobo@gmail.com', 'polobo1'),
(99, 'po-mabini', 'Procurement Office', 'Mabini', 'pomabini@gmail.com', 'pomabini1'),
(100, 'po-pabloborbon', 'Procurement Office', 'Pablo Borbon', 'popabloborbon@gmail.com', 'popabloborbon1'),
(101, 'po-rosario', 'Procurement Office', 'Rosario', 'porosario@gmail.com', 'porosario1'),
(102, 'po-sanjuan', 'Procurement Office', 'San Juan', 'posanjuan@gmail.com', 'posanjuan1'),
(103, 'ea-alangilan', 'External Affair', 'Alangilan', 'eaalangilan@gmail.com', 'eaalangilan1'),
(104, 'ea-nasugbu', 'External Affair', 'ARASOF-Nasugbu', 'eanasugbu@gmail.com', 'eanasugbu1'),
(105, 'ea-balayan', 'External Affair', 'Balayan', 'eabalayan@gmail.com', 'eabalayan1'),
(106, 'ea-central', 'External Affair', 'Central', 'eacentral@gmail.com', 'eacentral1'),
(107, 'ea-malvar', 'External Affair', 'JPLPC-Malvar', 'eamalvar@gmail.com', 'eamalvar1'),
(108, 'ea-lemery', 'External Affair', 'Lemery', 'ealemery@gmail.com', 'ealemery1'),
(109, 'ea-lipa', 'External Affair', 'Lipa', 'ealipa@gmail.com', 'ealipa1'),
(110, 'ea-lobo', 'External Affair', 'Lobo', 'ealobo@gmail.com', 'ealobo1'),
(111, 'ea-mabini', 'External Affair', 'Mabini', 'eamabini@gmail.com', 'eamabini1'),
(112, 'ea-pabloborbon', 'External Affair', 'Pablo Borbon', 'eapabloborbon@gmail.com', 'eapabloborbon1'),
(113, 'ea-rosario', 'External Affair', 'Rosario', 'earosario@gmail.com', 'earosario1'),
(114, 'ea-sanjuan', 'External Affair', 'San Juan', 'easanjuan@gmail.com', 'easanjuan1'),
(119, 'sdo-lemery', 'Sustainable Development Office', 'Lemery', 'sdolemery@gmail.com', 'sdolemery1'),
(122, 'rgo-lipa', 'Resource Generation Office', 'Lipa', '', 'rgolipa1'),
(123, 'gso-lipa', 'General Services Office', 'Lipa', '', 'gsolipa1'),
(124, 'rgo-malvar', 'Resource Generation Office', 'JPLPC-Malvar', '', 'rgomalvar1');

-- --------------------------------------------------------

--
-- Table structure for table `tblsolidwastesegregated`
--

CREATE TABLE `tblsolidwastesegregated` (
  `id` int(11) NOT NULL,
  `Campus` char(20) DEFAULT NULL,
  `Year` int(11) DEFAULT NULL,
  `Quarter` varchar(20) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `MainCategory` varchar(30) DEFAULT NULL,
  `SubCategory` varchar(30) DEFAULT NULL,
  `QuantityInKG` float DEFAULT NULL,
  `GHGEmissionKGCO2e` float DEFAULT NULL,
  `GHGEmissionTCO2e` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblsolidwasteunsegregated`
--

CREATE TABLE `tblsolidwasteunsegregated` (
  `id` int(11) NOT NULL,
  `Campus` char(20) DEFAULT NULL,
  `Year` year(4) DEFAULT NULL,
  `Quarter` varchar(20) DEFAULT NULL,
  `Month` char(20) DEFAULT NULL,
  `WasteType` varchar(30) DEFAULT NULL,
  `QuantityInKG` float DEFAULT NULL,
  `SentToLandfillKG` float DEFAULT NULL,
  `SentToLandfillTONS` float DEFAULT NULL,
  `Percentage` float DEFAULT NULL,
  `GHGEmissionKGCO2e` float DEFAULT NULL,
  `GHGEmissionTCO2e` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbltreatedwater`
--

CREATE TABLE `tbltreatedwater` (
  `id` int(11) NOT NULL,
  `Campus` varchar(255) DEFAULT NULL,
  `Month` varchar(50) DEFAULT NULL,
  `TreatedWaterVolume` float(10,2) DEFAULT NULL,
  `ReusedTreatedWaterVolume` decimal(10,2) DEFAULT NULL,
  `EffluentVolume` decimal(10,2) DEFAULT NULL,
  `PricePerLiter` decimal(10,2) DEFAULT NULL,
  `FactorKGCO2e` decimal(10,5) DEFAULT NULL,
  `FactorTCO2e` decimal(10,5) DEFAULT NULL,
  `Year` int(11) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblwater`
--

CREATE TABLE `tblwater` (
  `id` int(11) NOT NULL,
  `Campus` varchar(255) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `Month` varchar(50) DEFAULT NULL,
  `Quarter` varchar(10) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Category` varchar(255) DEFAULT NULL,
  `PreviousReading` decimal(10,2) DEFAULT NULL,
  `CurrentReading` decimal(10,2) DEFAULT NULL,
  `Consumption` float(10,2) DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `PricePerLiter` decimal(10,2) DEFAULT NULL,
  `FactorKGCO2e` decimal(10,5) DEFAULT NULL,
  `FactorTCO2e` decimal(10,5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `electricity_consumption`
--
ALTER TABLE `electricity_consumption`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fuel_emissions`
--
ALTER TABLE `fuel_emissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblaccommodation`
--
ALTER TABLE `tblaccommodation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblflight`
--
ALTER TABLE `tblflight`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblfoodwaste`
--
ALTER TABLE `tblfoodwaste`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbllpg`
--
ALTER TABLE `tbllpg`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblsignin`
--
ALTER TABLE `tblsignin`
  ADD PRIMARY KEY (`userID`);

--
-- Indexes for table `tblsolidwastesegregated`
--
ALTER TABLE `tblsolidwastesegregated`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblsolidwasteunsegregated`
--
ALTER TABLE `tblsolidwasteunsegregated`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltreatedwater`
--
ALTER TABLE `tbltreatedwater`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblwater`
--
ALTER TABLE `tblwater`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `electricity_consumption`
--
ALTER TABLE `electricity_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_emissions`
--
ALTER TABLE `fuel_emissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblaccommodation`
--
ALTER TABLE `tblaccommodation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblflight`
--
ALTER TABLE `tblflight`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblfoodwaste`
--
ALTER TABLE `tblfoodwaste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbllpg`
--
ALTER TABLE `tbllpg`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsignin`
--
ALTER TABLE `tblsignin`
  MODIFY `userID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `tblsolidwastesegregated`
--
ALTER TABLE `tblsolidwastesegregated`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsolidwasteunsegregated`
--
ALTER TABLE `tblsolidwasteunsegregated`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltreatedwater`
--
ALTER TABLE `tbltreatedwater`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwater`
--
ALTER TABLE `tblwater`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
