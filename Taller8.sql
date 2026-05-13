CREATE DATABASE taller8; 

USE taller8;

CREATE TABLE usuarios (
	usuario_id INT PRIMARY KEY AUTO_INCREMENT, 
	nombre VARCHAR(80) NOT NULL,
	avatar VARCHAR(10) DEFAULT '🎮',
	crated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE puntajes (
	idPuntajes INT PRIMARY KEY AUTO_INCREMENT,
	usuario_id INT NOT NULL,
	puntaje INT NOT NULL,
	total_preguntas INT DEFAULT 10,
	correctas INT NOT NULL,
	fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
);

CREATE TABLE logs_db (
	idLogs_db INT PRIMARY KEY AUTO_INCREMENT,
	usuario_id INT, 
	accion VARCHAR(50) NOT NULL, 
	detalle LONGTEXT,
	fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id)
);

-- Usuarios de prueba
INSERT INTO usuarios (nombre, avatar) VALUES
('Juan', '🎮'),
('Maria', '⭐'),
('Carlos', '🔥'),
('Ana', '🎯'),
('Pedro', '🚀');

SELECT * FROM usuarios;