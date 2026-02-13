-- Database Schema for Medical Day Hospitalization System
-- Character set: UTF-8 for Romanian diacritics

CREATE DATABASE IF NOT EXISTS eessp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eessp;

-- Table: pacienti (Patients) - SIMPLIFIED
-- Primary Key: CNP (Romanian Personal Identification Number)
CREATE TABLE IF NOT EXISTS pacienti (
    cnp VARCHAR(13) PRIMARY KEY COMMENT 'Cod Numeric Personal - Romanian ID',
    nume VARCHAR(100) NOT NULL COMMENT 'Last name',
    prenume VARCHAR(100) NOT NULL COMMENT 'First name',
    sex ENUM('M', 'F') NOT NULL COMMENT 'Sex: M=Male, F=Female',
    data_nasterii DATE NOT NULL COMMENT 'Birth date',
    varsta INT COMMENT 'Age (calculated from CNP)',
    
    -- Metadata
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
    data_modificarii DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification date',
    
    INDEX idx_nume (nume, prenume),
    INDEX idx_data_adaugarii (data_adaugarii)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Patient records - basic info only';

-- Table: doctori (Doctors)
CREATE TABLE IF NOT EXISTS doctori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cnp VARCHAR(13) UNIQUE NOT NULL COMMENT 'Doctor CNP',
    nume VARCHAR(100) NOT NULL COMMENT 'Last name',
    prenume VARCHAR(100) NOT NULL COMMENT 'First name',
    specializari TEXT COMMENT 'Specializations (JSON array or comma-separated)',
    email VARCHAR(150) COMMENT 'Email address',
    telefon VARCHAR(20) COMMENT 'Phone number',
    activ BOOLEAN DEFAULT TRUE COMMENT 'Active status',
    data_angajarii DATE COMMENT 'Hire date',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modificarii DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nume (nume, prenume),
    INDEX idx_cnp (cnp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Doctor records';

-- Table: spitalizari_de_zi (Day Hospitalizations)
CREATE TABLE IF NOT EXISTS spitalizari_de_zi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Foreign keys
    cnp_pacient VARCHAR(13) NOT NULL COMMENT 'Patient CNP',
    id_doctor INT COMMENT 'Doctor who created the record',
    
    -- Administrative data
    data_spitalizare DATE NOT NULL COMMENT 'Hospitalization date',
    judet VARCHAR(50) COMMENT 'County',
    localitate VARCHAR(100) COMMENT 'City/Town',
    spital VARCHAR(200) COMMENT 'Hospital name',
    sectie VARCHAR(100) COMMENT 'Department/Section',
    
    -- Record data
    nr_registru VARCHAR(50) COMMENT 'Registry number',
    tip_servicii VARCHAR(100) COMMENT 'Type of services',
    status ENUM('draft', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft' COMMENT 'Record status',
    
    -- Patient detailed data (moved from pacienti table)
    grup_sanguin ENUM('A', 'B', 'AB', 'O', '') DEFAULT '' COMMENT 'Blood type',
    rh ENUM('pozitiv', 'negativ', '') DEFAULT '' COMMENT 'Rh factor',
    alergic_la TEXT COMMENT 'Allergies',
    
    -- Domiciliu (Permanent address)
    domiciliu_judet VARCHAR(50) COMMENT 'County',
    domiciliu_localitate VARCHAR(100) COMMENT 'City/Town',
    domiciliu_mediu ENUM('urban', 'rural', '') DEFAULT '' COMMENT 'Environment type',
    domiciliu_strada VARCHAR(200) COMMENT 'Street',
    domiciliu_numar VARCHAR(20) COMMENT 'Number',
    
    -- Resedinta (Residence address)
    resedinta_same_domiciliu BOOLEAN DEFAULT FALSE COMMENT 'Residence same as domicile',
    resedinta_judet VARCHAR(50) COMMENT 'Residence county',
    resedinta_localitate VARCHAR(100) COMMENT 'Residence city',
    resedinta_mediu ENUM('urban', 'rural', '') DEFAULT '' COMMENT 'Residence environment',
    resedinta_strada VARCHAR(200) COMMENT 'Residence street',
    resedinta_numar VARCHAR(20) COMMENT 'Residence number',
    
    -- Social data
    cetatenie VARCHAR(50) DEFAULT 'romana' COMMENT 'Citizenship',
    ocupatia VARCHAR(100) COMMENT 'Occupation',
    loc_de_munca VARCHAR(200) COMMENT 'Workplace',
    nivel_instruire ENUM('fara', 'primar', 'gimnazial', 'liceal', 'profesional', 'superior', '') DEFAULT '' COMMENT 'Education level',
    
    -- Insurance
    statut_asigurat ENUM('cnas', 'asigurare_privata', 'neasigurat', '') DEFAULT '' COMMENT 'Insurance status',
    categorie_asigurat VARCHAR(100) COMMENT 'Insurance category (pensionar, salariat, etc.)',
    
    -- Diagnosis
    diagnostic_principal TEXT COMMENT 'Principal diagnosis',
    cod_icd VARCHAR(20) COMMENT 'ICD-10 code',
    diagnostice_secundare JSON COMMENT 'Secondary diagnoses (JSON array)',
    
    -- Epicrisis
    epicriza TEXT COMMENT 'Medical epicrisis/summary',
    
    -- Metadata
    ultima_modificare VARCHAR(200) COMMENT 'Last modification info (date - doctor)',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modificarii DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cnp_pacient) REFERENCES pacienti(cnp) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_doctor) REFERENCES doctori(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_data_spitalizare (data_spitalizare),
    INDEX idx_cnp_pacient (cnp_pacient),
    INDEX idx_status (status),
    INDEX idx_sectie (sectie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Day hospitalization records';

-- Table: explorari_functionale (Functional explorations)
CREATE TABLE IF NOT EXISTS explorari_functionale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spitalizare INT NOT NULL COMMENT 'Hospitalization ID',
    denumire VARCHAR(200) NOT NULL COMMENT 'Procedure name',
    cod VARCHAR(50) COMMENT 'Procedure code',
    numar INT DEFAULT 1 COMMENT 'Number of times performed',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_spitalizare) REFERENCES spitalizari_de_zi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_spitalizare (id_spitalizare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Functional explorations';

-- Table: investigatii_radiologice (Radiological investigations)
CREATE TABLE IF NOT EXISTS investigatii_radiologice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spitalizare INT NOT NULL COMMENT 'Hospitalization ID',
    denumire VARCHAR(200) NOT NULL COMMENT 'Investigation name',
    cod VARCHAR(50) COMMENT 'Investigation code',
    numar INT DEFAULT 1 COMMENT 'Number of times performed',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_spitalizare) REFERENCES spitalizari_de_zi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_spitalizare (id_spitalizare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Radiological investigations';

-- Table: alte_proceduri (Other procedures)
CREATE TABLE IF NOT EXISTS alte_proceduri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spitalizare INT NOT NULL COMMENT 'Hospitalization ID',
    denumire VARCHAR(200) NOT NULL COMMENT 'Procedure name',
    cod VARCHAR(50) COMMENT 'Procedure code',
    numar INT DEFAULT 1 COMMENT 'Number of times performed',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_spitalizare) REFERENCES spitalizari_de_zi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_spitalizare (id_spitalizare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Other medical procedures';

-- Table: analize_laborator (Laboratory analyses)
CREATE TABLE IF NOT EXISTS analize_laborator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spitalizare INT NOT NULL COMMENT 'Hospitalization ID',
    denumire VARCHAR(200) NOT NULL COMMENT 'Analysis name',
    cod VARCHAR(50) COMMENT 'Analysis code',
    numar INT DEFAULT 1 COMMENT 'Number of times performed',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_spitalizare) REFERENCES spitalizari_de_zi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_spitalizare (id_spitalizare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laboratory analyses';

-- Table: tratamente (Treatments)
CREATE TABLE IF NOT EXISTS tratamente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_spitalizare INT NOT NULL COMMENT 'Hospitalization ID',
    data_tratament DATE NOT NULL COMMENT 'Treatment date',
    descriere TEXT NOT NULL COMMENT 'Treatment description',
    data_adaugarii DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_spitalizare) REFERENCES spitalizari_de_zi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_spitalizare (id_spitalizare),
    INDEX idx_data_tratament (data_tratament)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Treatment records';

-- Insert sample doctor data
INSERT INTO doctori (cnp, nume, prenume, specializari, email, activ) VALUES
('1800101123456', 'Ionescu', 'Alexandru', 'Cardiologie, Medicină Internă', 'a.ionescu@spital.ro', TRUE),
('2850215234567', 'Popescu', 'Maria', 'Oncologie', 'm.popescu@spital.ro', TRUE),
('1750320345678', 'Mihai', 'Georgeta', 'Neurologie', 'g.mihai@spital.ro', TRUE),
('1820512456789', 'Popa', 'Andrei', 'Chirurgie Generală', 'a.popa@spital.ro', TRUE);
