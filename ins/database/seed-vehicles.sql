INSERT OR IGNORE INTO vehicle_makes (name) VALUES
('Acura'),
('Alfa Romeo'),
('Aston Martin'),
('Audi'),
('Bentley'),
('BMW'),
('Buick'),
('Cadillac'),
('Chevrolet'),
('Chrysler'),
('Dodge'),
('Ferrari'),
('FIAT'),
('Ford'),
('Genesis'),
('GMC'),
('Honda'),
('Hyundai'),
('INFINITI'),
('Jaguar'),
('Jeep'),
('Kia'),
('Lamborghini'),
('Land Rover'),
('Lexus'),
('Lincoln'),
('Lucid'),
('Maserati'),
('Mazda'),
('McLaren'),
('Mercedes-Benz'),
('MINI'),
('Mitsubishi'),
('Nissan'),
('Porsche'),
('Ram'),
('Rivian'),
('Rolls-Royce'),
('Subaru'),
('Tesla'),
('Toyota'),
('Volkswagen'),
('Volvo');
INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'ILX' FROM vehicle_makes WHERE name = 'Acura';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Integra' FROM vehicle_makes WHERE name = 'Acura';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'MDX' FROM vehicle_makes WHERE name = 'Acura';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'RDX' FROM vehicle_makes WHERE name = 'Acura';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'TLX' FROM vehicle_makes WHERE name = 'Acura';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A4' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A5' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A6' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A7' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A8' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Q3' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Q5' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Q7' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Q8' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'R8' FROM vehicle_makes WHERE name = 'Audi';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Silverado 1500' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Silverado 2500HD' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Silverado 3500HD' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Colorado' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Equinox' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Blazer' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Traverse' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Tahoe' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Suburban' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Trailblazer' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Camaro' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Corvette' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Malibu' FROM vehicle_makes WHERE name = 'Chevrolet';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Charger' FROM vehicle_makes WHERE name = 'Dodge';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Challenger' FROM vehicle_makes WHERE name = 'Dodge';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Durango' FROM vehicle_makes WHERE name = 'Dodge';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Hornet' FROM vehicle_makes WHERE name = 'Dodge';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'F-150' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'F-250 Super Duty' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'F-350 Super Duty' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'F-450 Super Duty' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Maverick' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Ranger' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Bronco' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Bronco Sport' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Escape' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Edge' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Explorer' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Expedition' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Mustang' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Mustang Mach-E' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Transit' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Transit Connect' FROM vehicle_makes WHERE name = 'Ford';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Civic' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Accord' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'CR-V' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'HR-V' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Passport' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Pilot' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Odyssey' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Ridgeline' FROM vehicle_makes WHERE name = 'Honda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Elantra' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sonata' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Kona' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Tucson' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Santa Fe' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Palisade' FROM vehicle_makes WHERE name = 'Hyundai';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Frontier' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Titan' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sentra' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Altima' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Maxima' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Kicks' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Rogue' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Murano' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Pathfinder' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Armada' FROM vehicle_makes WHERE name = 'Nissan';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Tacoma' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Tundra' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Corolla' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Camry' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Prius' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Crown' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GR86' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Supra' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'RAV4' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Highlander' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Grand Highlander' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '4Runner' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sequoia' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sienna' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Corolla Cross' FROM vehicle_makes WHERE name = 'Toyota';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Crosstrek' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Forester' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Outback' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Impreza' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Legacy' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Ascent' FROM vehicle_makes WHERE name = 'Subaru';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Model 3' FROM vehicle_makes WHERE name = 'Tesla';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Model Y' FROM vehicle_makes WHERE name = 'Tesla';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Model S' FROM vehicle_makes WHERE name = 'Tesla';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Model X' FROM vehicle_makes WHERE name = 'Tesla';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Cybertruck' FROM vehicle_makes WHERE name = 'Tesla';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Jetta' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Passat' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Golf' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Tiguan' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Atlas' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Atlas Cross Sport' FROM vehicle_makes WHERE name = 'Volkswagen';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Wrangler' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Grand Cherokee' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Cherokee' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Compass' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Renegade' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Gladiator' FROM vehicle_makes WHERE name = 'Jeep';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '1500' FROM vehicle_makes WHERE name = 'Ram';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '2500' FROM vehicle_makes WHERE name = 'Ram';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '3500' FROM vehicle_makes WHERE name = 'Ram';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'ProMaster' FROM vehicle_makes WHERE name = 'Ram';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'ProMaster City' FROM vehicle_makes WHERE name = 'Ram';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sierra 1500' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sierra 2500HD' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sierra 3500HD' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Canyon' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Terrain' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Acadia' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Yukon' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Yukon XL' FROM vehicle_makes WHERE name = 'GMC';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Optima' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'K5' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Forte' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Soul' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sportage' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Sorento' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Telluride' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Carnival' FROM vehicle_makes WHERE name = 'Kia';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'CX-5' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'CX-30' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'CX-50' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'CX-90' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Mazda3' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Mazda6' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'MX-5 Miata' FROM vehicle_makes WHERE name = 'Mazda';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'ES' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'IS' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'LS' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'NX' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'RX' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GX' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'LX' FROM vehicle_makes WHERE name = 'Lexus';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'A-Class' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'C-Class' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'E-Class' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'S-Class' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GLA' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GLB' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GLC' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GLE' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'GLS' FROM vehicle_makes WHERE name = 'Mercedes-Benz';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '911' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '718 Cayman' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, '718 Boxster' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Panamera' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Macan' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'Cayenne' FROM vehicle_makes WHERE name = 'Porsche';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'S60' FROM vehicle_makes WHERE name = 'Volvo';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'S90' FROM vehicle_makes WHERE name = 'Volvo';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'XC40' FROM vehicle_makes WHERE name = 'Volvo';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'XC60' FROM vehicle_makes WHERE name = 'Volvo';

INSERT OR IGNORE INTO vehicle_models (make_id, name)
SELECT id, 'XC90' FROM vehicle_makes WHERE name = 'Volvo';
