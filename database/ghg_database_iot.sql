-- Table for general meters types (e.g., electric, water, gas)
CREATE TABLE tblmeter_types(
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Table for general meters (can be used for electric, water, gas, etc.)
CREATE TABLE tblmeter(
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50) UNIQUE NOT NULL,
    meter_type_id INT NOT NULL,
    campus VARCHAR(255) NOT NULL,
    location VARCHAR(255) NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meter_type_id) REFERENCES tblmeter_types(id)
);

-- Table for meter readings
CREATE TABLE tblmeter_readings(
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id INT NOT NULL,
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    previous_reading DOUBLE,
    current_reading DOUBLE,
    FOREIGN KEY (meter_id) REFERENCES tblmeter(id)
);

ALTER TABLE tblmeter ADD COLUMN api_address VARCHAR(255) NULL; -- future physical meter

INSERT INTO tblmeter_types (type_name, description) VALUES 
('Electric', 'Electricity consumption meter'),
('Water', 'Water consumption meter'),
('Gas', 'Gas consumption meter');

INSERT INTO tblmeter (meter_id, meter_type_id, campus, location) VALUES 
('MRT-02', 1, 'Alangilan', 'Building A'),
('MRT-03', 1, 'Alangilan', 'Building B'),
('MRT-04', 1, 'Pablo Borbon', 'Building A'),
('WTR-01', 2, 'Alangilan', 'Building D'),
('WTR-02', 2, 'Alangilan', 'Building E'),
('WTR-03', 2, 'Pablo Borbon', 'Building B');

-- Long strip
SELECT meter_id, campus, api_address, type_name, description FROM tblmeter LEFT JOIN tblmeter_types ON tblmeter.meter_type_id = tblmeter_types.id WHERE is_active = 1 AND meter_type_id = 1 AND campus = "Alangilan";

-- Readable
SELECT 
    meter_id, 
    campus, 
    api_address, 
    type_name, 
    description 
FROM tblmeter 
LEFT JOIN tblmeter_types ON tblmeter.meter_type_id = tblmeter_types.id 
WHERE is_active = 1 AND meter_type_id = 1 AND campus = 'Alangilan';
