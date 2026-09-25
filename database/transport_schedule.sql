CREATE TABLE IF NOT EXISTS TransportSchedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(150) NOT NULL,
    departure_location VARCHAR(100) NOT NULL,
    arrival_location VARCHAR(100) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 50,
    operating_days VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY unique_transport_schedule (route_name, departure_location, departure_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO TransportSchedule
    (route_name, departure_location, arrival_location, departure_time, arrival_time, vehicle_type, capacity, operating_days, status)
VALUES
    ('Kurunegala - Maharagama', 'Kurunegala', 'Maharagama', '07:00:00', '10:30:00', 'Semi-Luxury', 52, 'Daily', 'active'),
    ('Kurunegala - Maharagama', 'Kurunegala', 'Maharagama', '09:00:00', '12:15:00', 'Normal (CTB)', 54, 'Monday - Saturday', 'active'),
    ('Kandy - Maharagama', 'Kandy', 'Maharagama', '05:30:00', '09:45:00', 'Luxury', 45, 'Daily', 'active'),
    ('Colombo - Maharagama', 'Colombo', 'Maharagama', '08:00:00', '08:45:00', 'Normal (CTB)', 50, 'Daily', 'active'),
    ('Gampaha - Maharagama', 'Gampaha', 'Maharagama', '06:15:00', '08:00:00', 'Semi-Luxury', 50, 'Monday - Friday', 'active');