-- ============================================================
--  DentaHistoria · Datos de demostración
--  Importar DESPUÉS de schema.sql (una sola vez).
--
--  Acceso administrador:
--    admin@dentahistoria.com / Admin123!
--  Acceso odontólogo:
--    valentina@dentahistoria.com / Odonto2026!
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO usuarios (id, nombre, email, password_hash, rol, activo) VALUES
(1, 'Administrador DentaHistoria', 'admin@dentahistoria.com', '$2y$10$RNua7uam/gdEXCdkrxpc1.GDJl6ovWoTl91/ZN0NNZLIObbBkJzyu', 'admin', 1),
(2, 'Dra. Valentina Ríos', 'valentina@dentahistoria.com', '$2y$10$K8dCu8DTybkEhu.pKTfm7O6FLlKo/twwnTs8PlzJz.sFRTKabNerO', 'odontologo', 1);

INSERT INTO procedimientos (id, nombre, categoria, descripcion, precio, duracion_min, activo) VALUES
(1, 'Limpieza dental', 'Preventiva', 'Remoción de placa bacteriana y sarro con ultrasonido, pulido y aplicación de flúor.', 25.00, 30, 1),
(2, 'Blanqueamiento dental', 'Estética', 'Blanqueamiento con gel de peróxido de carbamida y activación LED.', 120.00, 90, 1),
(3, 'Extracción simple', 'Cirugía', 'Extracción de pieza dental sin complicaciones, con anestesia local.', 40.00, 30, 1),
(4, 'Extracción de muela del juicio', 'Cirugía', 'Cirugía de tercer molar con evaluación radiográfica previa.', 150.00, 60, 1),
(5, 'Tratamiento de conductos', 'Endodoncia', 'Endodoncia unirradicular o multirradicular con instrumentación rotatoria.', 180.00, 90, 1),
(6, 'Calza de resina', 'Restauración', 'Restauración estética con composite fotocurado.', 45.00, 45, 1),
(7, 'Calza de amalgama', 'Restauración', 'Restauración con amalgama de plata en piezas posteriores.', 35.00, 45, 1),
(8, 'Profilaxis dental', 'Preventiva', 'Limpieza profunda y control preventivo de encías.', 30.00, 30, 1),
(9, 'Aplicación de flúor', 'Preventiva', 'Aplicación tópica de flúor para fortalecer el esmalte.', 15.00, 20, 1),
(10, 'Corona de porcelana', 'Restauración', 'Corona fija de porcelana sobre implante o pieza tratada.', 250.00, 60, 1),
(11, 'Ortodoncia con brackets', 'Ortodoncia', 'Instalación de brackets metálicos y plan de controles mensuales.', 600.00, 60, 1),
(12, 'Radiografía panorámica', 'Diagnóstico', 'Estudio imagenológico panorámico de maxilares.', 35.00, 20, 1);

INSERT INTO pacientes (id, codigo, nombres, apellidos, documento, fecha_nacimiento, sexo, telefono, email, direccion, ocupacion, alergias, antecedentes, notas, creado_por, creado_en) VALUES
(1, 'PAC-0001', 'María Fernanda', 'González Rivas', 'V-18456321', '1992-04-18', 'F', '+58 412 555-0181', 'maria.gonzalez@example.com', 'Av. Sucre, Res. Los Chaguaramos, Caracas', 'Diseñadora gráfica', 'Penicilina', 'Hipertensión controlada con losartán.', 'Prefiere citas en la mañana.', 2, DATE_SUB(NOW(), INTERVAL 185 DAY)),
(2, 'PAC-0002', 'Carlos Eduardo', 'Pérez Molina', 'V-14258963', '1985-11-02', 'M', '+58 414 555-0234', 'carlos.perez@example.com', 'Calle 5, Quinta Aurora, Baruta', 'Ingeniero civil', 'Ninguna conocida', 'Diabetes mellitus tipo 2 controlada.', 'Acude acompañado de su esposa.', 2, DATE_SUB(NOW(), INTERVAL 170 DAY)),
(3, 'PAC-0003', 'Ana Lucía', 'Rodríguez Silva', 'V-27894561', '2001-07-25', 'F', '+58 424 555-0310', 'ana.rodriguez@example.com', 'Av. Francisco de Miranda, Edif. Sol, Caracas', 'Estudiante universitaria', 'Ninguna conocida', 'Sin antecedentes relevantes.', 'Interesada en blanqueamiento.', 2, DATE_SUB(NOW(), INTERVAL 150 DAY)),
(4, 'PAC-0004', 'José Manuel', 'Martínez Ortega', 'V-11236874', '1978-01-30', 'M', '+58 416 555-0455', 'jose.martinez@example.com', 'Urb. La Trinidad, Calle 3, Baruta', 'Contador público', 'Látex', 'Bruxismo nocturno. Usa férula de descarga.', 'Muela del juicio inferior derecha incluida.', 2, DATE_SUB(NOW(), INTERVAL 145 DAY)),
(5, 'PAC-0005', 'Gabriela Sofía', 'Hernández Paz', 'V-20145789', '1996-09-12', 'F', '+58 412 555-0522', 'gabriela.hernandez@example.com', 'Av. Andrés Bello, Res. Ávila, Caracas', 'Administradora', 'Ibuprofeno', 'Sensibilidad dental en molares.', 'Seguimiento de blanqueamiento.', 2, DATE_SUB(NOW(), INTERVAL 120 DAY)),
(6, 'PAC-0006', 'Luis Alejandro', 'Díaz Contreras', 'V-9865471', '1969-03-08', 'M', '+58 426 555-0677', 'luis.diaz@example.com', 'Calle Real de Sabana Grande, Caracas', 'Comerciante', 'Ninguna conocida', 'Tratamiento anticoagulante (aspirina).', 'Requiere interconsulta antes de cirugía.', 2, DATE_SUB(NOW(), INTERVAL 100 DAY)),
(7, 'PAC-0007', 'Daniela Carolina', 'Torres Méndez', 'V-19563247', '1989-12-05', 'F', '+58 414 555-0788', 'daniela.torres@example.com', 'Av. Páez, Res. El Parque, Caracas', 'Docente', 'Sulfamidas', 'Migrañas frecuentes.', 'Muy colaboradora con la higiene.', 2, DATE_SUB(NOW(), INTERVAL 85 DAY)),
(8, 'PAC-0008', 'Andrés Felipe', 'Ramírez León', 'V-28945632', '2004-06-21', 'M', '+58 412 555-0899', 'andres.ramirez@example.com', 'Urb. El Cafetal, Calle 2, Baruta', 'Estudiante', 'Ninguna conocida', 'Sin antecedentes relevantes.', 'Pendiente control de ortodoncia.', 2, DATE_SUB(NOW(), INTERVAL 70 DAY)),
(9, 'PAC-0009', 'Sofía Isabel', 'Mendoza Vargas', 'V-31245789', '2010-02-14', 'F', '+58 424 555-0912', 'familia.mendoza@example.com', 'Av. Victoria, Res. La Esperanza, Caracas', 'Estudiante', 'Ninguna conocida', 'Paciente pediátrica. Acude con su madre.', 'Sellantes en piezas posteriores.', 2, DATE_SUB(NOW(), INTERVAL 55 DAY)),
(10, 'PAC-0010', 'Pedro José', 'Castillo Bravo', 'V-4569871', '1955-08-19', 'M', '+58 416 555-1024', 'pedro.castillo@example.com', 'Calle Los Mangos, El Paraíso, Caracas', 'Jubilado', 'Sulfamidas', 'Prótesis parcial removible superior.', 'Control periodontal cada 3 meses.', 2, DATE_SUB(NOW(), INTERVAL 40 DAY));

INSERT INTO atenciones (id, paciente_id, usuario_id, fecha, hora, motivo, diagnostico, tratamiento, observaciones, dientes, estado, proxima_cita) VALUES
(1, 1, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '09:00:00', 'Control de rutina y limpieza', 'Placa bacteriana generalizada, encías sin sangrado.', 'Limpieza con ultrasonido, pulido y aplicación de flúor.', 'Se indicó cepillado con técnica de Bass y uso de hilo dental.', '18,17,16,11,21,26,27,36,37,46,47', 'realizado', DATE_ADD(CURDATE(), INTERVAL 21 DAY)),
(2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 12 DAY), '10:30:00', 'Dolor en molar inferior derecho', 'Pulpitis irreversible en pieza 46.', 'Tratamiento de conductos en pieza 46, primera sesión.', 'Paciente refiere dolor al frío desde hace dos semanas.', '46', 'seguimiento', DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
(3, 3, 2, DATE_SUB(CURDATE(), INTERVAL 18 DAY), '11:00:00', 'Blanqueamiento dental', 'Tinción extrínseca leve.', 'Blanqueamiento en consultorio con peróxido de carbamida al 35%.', 'Se entregaron indicaciones de dieta blanca por 48 horas.', '13,12,11,21,22,23', 'realizado', NULL),
(4, 4, 2, DATE_SUB(CURDATE(), INTERVAL 25 DAY), '14:00:00', 'Evaluación de muela del juicio', 'Tercer molar 48 incluido, sin signos de infección.', 'Programación de extracción quirúrgica de pieza 48.', 'Se solicitó radiografía panorámica preoperatoria.', '48', 'pendiente', DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
(5, 5, 2, DATE_SUB(CURDATE(), INTERVAL 32 DAY), '08:30:00', 'Sensibilidad en molar superior', 'Caries oclusal en pieza 26.', 'Calza de resina en pieza 26.', 'Se recomienda pasta dental para sensibilidad.', '26', 'realizado', NULL),
(6, 6, 2, DATE_SUB(CURDATE(), INTERVAL 45 DAY), '15:30:00', 'Limpieza y evaluación general', 'Sarro supragingival moderado.', 'Limpieza dental profunda y radiografía panorámica.', 'Se solicitó interconsulta por anticoagulación antes de cirugía.', '17,16,15,14,24,25,26,27,37,36,35,34,44,45,46,47', 'realizado', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
(7, 7, 2, DATE_SUB(CURDATE(), INTERVAL 52 DAY), '09:30:00', 'Profilaxis semestral', 'Encías levemente inflamadas.', 'Profilaxis dental y aplicación de flúor.', 'Se reforzó técnica de cepillado.', '18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28', 'realizado', NULL),
(8, 8, 2, DATE_SUB(CURDATE(), INTERVAL 63 DAY), '16:00:00', 'Extracción de pieza dental', 'Restos radiculares en pieza 36.', 'Extracción simple de pieza 36.', 'Evolución favorable. Se indicó analgesia.', '36', 'realizado', DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
(9, 9, 2, DATE_SUB(CURDATE(), INTERVAL 71 DAY), '10:00:00', 'Caries en molar de leche', 'Caries en pieza 74.', 'Calza de resina en pieza 74.', 'Se indicó control pediátrico en 6 meses.', '74', 'realizado', NULL),
(10, 10, 2, DATE_SUB(CURDATE(), INTERVAL 80 DAY), '11:30:00', 'Rehabilitación de pieza 16', 'Pieza 16 con tratamiento de conductos previo y corona fracturada.', 'Radiografía panorámica y planificación de corona de porcelana.', 'Se tomaron impresiones digitales.', '16', 'pendiente', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(11, 1, 2, DATE_SUB(CURDATE(), INTERVAL 95 DAY), '09:00:00', 'Dolor al masticar', 'Caries interproximal en pieza 36.', 'Calza de resina en pieza 36.', 'Sin complicaciones.', '36', 'realizado', NULL),
(12, 2, 2, DATE_SUB(CURDATE(), INTERVAL 110 DAY), '10:00:00', 'Limpieza semestral', 'Placa bacteriana moderada.', 'Limpieza dental y aplicación de flúor.', 'Se recomendó control de glucemia antes de la próxima cita.', '17,16,15,14,24,25,26,27,37,36,35,34,44,45,46,47', 'realizado', NULL),
(13, 3, 2, DATE_SUB(CURDATE(), INTERVAL 125 DAY), '12:00:00', 'Revisión general', 'Sin caries activas.', 'Profilaxis dental.', 'Paciente solicita presupuesto de ortodoncia.', NULL, 'realizado', NULL),
(14, 4, 2, DATE_SUB(CURDATE(), INTERVAL 140 DAY), '14:30:00', 'Tratamiento de conductos', 'Necrosis pulpar en pieza 37.', 'Endodoncia en pieza 37, sesión única.', 'Se indicó corona posterior.', '37', 'realizado', NULL),
(15, 5, 2, DATE_SUB(CURDATE(), INTERVAL 150 DAY), '15:00:00', 'Blanqueamiento', 'Tinción por café.', 'Blanqueamiento dental con gel y lámpara LED.', 'Se indicó evitar café y vino por una semana.', '13,12,11,21,22,23', 'realizado', NULL),
(16, 6, 2, DATE_SUB(CURDATE(), INTERVAL 160 DAY), '09:30:00', 'Rehabilitación de molar', 'Pieza 26 desvitalizada con pérdida de pared.', 'Corona de porcelana en pieza 26.', 'Control radiográfico a los 6 meses.', '26', 'realizado', NULL),
(17, 7, 2, DATE_SUB(CURDATE(), INTERVAL 170 DAY), '10:30:00', 'Caries en premolar', 'Caries oclusal en pieza 25.', 'Calza de amalgama en pieza 25.', 'Se recomendó cambiar a resina en próxima oportunidad.', '25', 'realizado', NULL),
(18, 8, 2, DATE_SUB(CURDATE(), INTERVAL 180 DAY), '11:00:00', 'Limpieza', 'Sarro leve.', 'Profilaxis dental y aplicación de flúor.', 'Buen estado general.', NULL, 'realizado', NULL),
(19, 1, 2, DATE_SUB(CURDATE(), INTERVAL 210 DAY), '08:30:00', 'Evaluación inicial', 'Paciente con apiñamiento leve.', 'Radiografía panorámica y plan de tratamiento.', 'Se explicaron opciones de ortodoncia.', NULL, 'realizado', NULL),
(20, 9, 2, DATE_SUB(CURDATE(), INTERVAL 220 DAY), '09:00:00', 'Prevención pediátrica', 'Riesgo de caries moderado.', 'Aplicación de flúor y sellantes.', 'Se educó a la madre en higiene oral.', '16,26,36,46', 'realizado', NULL),
(21, 2, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:30:00', 'Segunda sesión de endodoncia', 'Pendiente de evaluación.', 'Continuación del tratamiento de conductos en pieza 46.', 'Cita programada.', '46', 'pendiente', NULL),
(22, 10, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:30:00', 'Colocación de corona', 'Pendiente de evaluación.', 'Prueba y cementado de corona de porcelana en pieza 16.', 'Cita programada.', '16', 'pendiente', NULL),
(23, 8, 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), '16:00:00', 'Control post-extracción', 'Pendiente de evaluación.', 'Revisión de alvéolo y retiro de puntos.', 'Cita programada.', '36', 'pendiente', NULL);

INSERT INTO atencion_procedimientos (atencion_id, procedimiento_id) VALUES
(1, 1), (1, 9),
(2, 5),
(3, 2),
(4, 12),
(5, 6),
(6, 1), (6, 12),
(7, 8), (7, 9),
(8, 3),
(9, 6),
(10, 12), (10, 10),
(11, 6),
(12, 1),
(13, 8),
(14, 5),
(15, 2),
(16, 10),
(17, 7),
(18, 8), (18, 9),
(19, 12),
(20, 9),
(21, 5),
(22, 10),
(23, 3);

-- ------------------------------------------------------------
-- Odontograma de demostración (estados actuales por pieza)
-- ------------------------------------------------------------
INSERT INTO odontograma (paciente_id, pieza, estado, notas) VALUES
(1, '36', 'obturado', 'Calza de resina realizada el 06/08/2026.'),
(1, '18', 'sellante', 'Sellante preventivo de control.'),
(2, '46', 'endodoncia', 'Tratamiento de conductos en curso.'),
(2, '36', 'caries', 'Caries oclusal detectada en control.'),
(4, '48', 'extraccion', 'Tercer molar incluido, extracción programada.'),
(5, '26', 'obturado', 'Calza de resina por sensibilidad.'),
(6, '26', 'corona', 'Corona de porcelana cementada.'),
(6, '16', 'ausente', 'Pieza ausente desde la infancia.'),
(7, '25', 'obturado', 'Restauración con amalgama.'),
(8, '36', 'ausente', 'Extracción realizada, alvéolo en control.'),
(9, '46', 'sellante', 'Sellante en primer molar permanente.'),
(10, '16', 'corona', 'Corona de porcelana en proceso.'),
(10, '26', 'endodoncia', 'Endodoncia previa, control radiográfico.');

SET FOREIGN_KEY_CHECKS = 1;
