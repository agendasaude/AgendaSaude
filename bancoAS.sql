CREATE DATABASE bancoAS;

USE bancoAS;

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    nascimento DATE NOT NULL,
    genero VARCHAR(20),
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO pacientes (nome, cpf, nascimento, genero, telefone, email, senha)
VALUES ('Admin', '00000000000', '2000-01-01', 'Outro', '00000000000', 'admin@agendasaude.com', '$2y$10$fy..1YxteR90RxpmhX2w/u8nuvd0KoMPUWIWporbbW3lUPR4jf5EW');

CREATE TABLE clinicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20) NOT NULL UNIQUE,
    especialidades VARCHAR(200) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, 
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cbo VARCHAR(6),
    rqe VARCHAR(100),
    especialidade VARCHAR(200) NOT NULL,
    clinica_id INT NOT NULL, 
    dias_semana VARCHAR(250) NOT NULL, 
    horarios VARCHAR(250) NOT NULL,  
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL, 
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id) ON DELETE CASCADE
);

CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    clinica_id INT NOT NULL,
    data_agendamento DATE NOT NULL,
    horario TIME NOT NULL,
    descricao TEXT,
    status ENUM('pendente', 'confirmado', 'cancelado') DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id) ON DELETE CASCADE
);