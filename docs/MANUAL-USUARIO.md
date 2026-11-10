# Manual de usuario · DentaHistoria

DentaHistoria es un sistema web para registrar pacientes, consultar su historia clínica, mantener un odontograma y generar la historia en PDF.

## 1. Iniciar sesión

1. Abre la URL del sistema.
2. Escribe tu correo y contraseña.
3. Pulsa **Iniciar sesión**.

Con los datos de demostración puedes usar:

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@dentahistoria.com` | `Admin123!` |
| Odontóloga | `valentina@dentahistoria.com` | `Odonto2026!` |

> En producción cambia estas contraseñas y desactiva el bloque de demostración con `'demo' => false` en `includes/config.local.php`.

### Crear una cuenta propia

Si no tienes usuario, entra en **Crear una cuenta** desde la pantalla de inicio de sesión o abre directamente `registro.php`. Completa:

- Nombre completo.
- Correo electrónico personal o institucional.
- Contraseña de al menos 8 caracteres.
- Confirmación de la contraseña.
- Código de invitación entregado por el administrador.

Al terminar, la cuenta se crea con rol **odontólogo** y la sesión se inicia automáticamente. En los siguientes accesos podrás entrar con tu propio correo y contraseña, sin usar cuentas genéricas.

El administrador puede cambiar el código de invitación o desactivar el registro desde `includes/config.local.php`.

## 2. Panel principal

El panel muestra:

- Total de pacientes registrados.
- Atenciones del mes.
- Procedimientos activos.
- Próximas citas.
- Gráfico de actividad y distribución por especialidad.
- Últimas atenciones registradas.

Desde el menú lateral se accede a **Panel**, **Pacientes** y **Procedimientos**.

## 3. Pacientes

### Registrar un paciente

1. Entra en **Pacientes**.
2. Pulsa **Nuevo paciente**.
3. Completa nombres, apellidos y, si los tienes, documento, fecha de nacimiento, sexo, teléfono, correo, dirección, ocupación, alergias y antecedentes.
4. Guarda el formulario. El sistema asigna un código `PAC-XXXX`.

### Buscar y editar

- Usa el buscador por nombre, apellido, documento o código.
- Filtra por sexo o rango de edad si lo necesitas.
- Pulsa el botón de edición de la fila para actualizar los datos.
- Solo el rol **administrador** puede eliminar pacientes. Al eliminar un paciente se eliminan también sus atenciones y su odontograma.

## 4. Historia clínica

Haz clic en un paciente para abrir su ficha. La ficha tiene tres pestañas:

### Historia clínica

Línea de tiempo de las atenciones. Cada atención muestra fecha, hora, motivo, diagnóstico, tratamiento, observaciones, procedimientos realizados, piezas dentales, estado y próxima cita.

### Datos clínicos

Resumen de alergias, antecedentes médicos, notas internas y datos de contacto.

### Odontograma

Representación de las 32 piezas permanentes con numeración FDI.

1. Haz clic en una pieza.
2. Elige el estado: sano, caries, obturado/calza, corona, endodoncia, extracción, ausente, implante o sellante.
3. Añade una nota clínica opcional (máximo 180 caracteres).
4. Pulsa **Guardar pieza**.
5. Para dejar la pieza como sana, pulsa **Marcar como sana**.
6. El botón **Restablecer** borra todos los estados del odontograma del paciente y pide confirmación.

Los cambios se guardan mediante AJAX, sin recargar la página.

## 5. Registrar una atención

1. Abre la ficha del paciente.
2. Pulsa **Nueva atención**.
3. Completa fecha, hora, motivo de consulta, diagnóstico, tratamiento, observaciones y próxima cita.
4. Selecciona las piezas dentales afectadas.
5. Marca los procedimientos realizados.
6. Guarda. La atención aparece de inmediato en la línea de tiempo.

Cada odontólogo puede editar sus propias atenciones; el administrador puede gestionar todas.

## 6. Procedimientos

En **Procedimientos** puedes:

- Crear un procedimiento con nombre, descripción, precio y duración.
- Editar sus datos.
- Activar o desactivar procedimientos.
- Eliminar un procedimiento. Si ya tiene atenciones asociadas, el sistema solo lo desactiva para no romper el historial.

## 7. Historia clínica en PDF

Desde la ficha del paciente, abre el menú **Historia en PDF**:

- **Abrir PDF**: lo muestra en una pestaña nueva.
- **Descargar PDF**: lo guarda en el equipo.

El documento incluye datos del paciente, alergias, antecedentes, resumen de atención, odontograma, hallazgos y el detalle de cada atención, además de un espacio para la firma del odontólogo.

## 8. Cerrar sesión

Usa el menú de usuario en la barra superior y pulsa **Cerrar sesión**. Si dejas la sesión inactiva, el sistema te pedirá iniciar sesión de nuevo.

## 9. Recomendaciones de uso

- Registra las alergias del paciente antes de la primera atención.
- Actualiza el odontograma después de cada procedimiento.
- Programa la próxima cita para que aparezca en el panel principal.
- Descarga una copia del PDF cuando necesites compartir la historia con otro profesional.
- Realiza respaldos periódicos de la base de datos.
