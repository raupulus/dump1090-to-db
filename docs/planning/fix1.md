# Mejoras y Correcciones Sugeridas (fix1)

Tras analizar el proyecto `dump1090-to-db`, he identificado varias áreas de mejora que incluyen correcciones de seguridad, corrección de bugs lógicos, mejores prácticas y deuda técnica.

## 1. Seguridad: Vulnerabilidad de Inyección SQL
- **Problema**: En la clase `Dbconnection`, los métodos `saveAirflight()` y `deleteAirflight()` construyen las sentencias SQL concatenando las variables directamente. Esto expone el sistema a inyecciones SQL si algún día el origen de los datos no fuera de entera confianza.
- **Solución**: Refactorizar estas funciones para que hagan uso de consultas preparadas (`prepared statements`) con PDO, utilizando bind parameters (`?` o `:nombre`).

## 2. Bug lógico: Pausa en reintentos de conexión a BD
- **Problema**: En el constructor de `Dbconnection` (`__construct`), hay un bucle `do/while` que intenta reconectar hasta 10 veces en caso de fallo. Sin embargo, usa la función `usleep(300);`. La función `usleep` recibe el tiempo en **microsegundos**, por lo que 300 microsegundos son tan solo 0.3 milisegundos. Los 10 reintentos se efectúan casi de manera instantánea.
- **Solución**: Cambiar a `usleep(300000)` para pausar 300ms, o simplemente usar `sleep(1)` para que haya un margen de 1 segundo entre reintentos.

## 3. Manejo de Errores Silencioso en Base de Datos
- **Problema**: En `Dbconnection.php`, hay varios bloques `try/catch` vacíos (por ejemplo en `connect()`, `executeCount()`, `close()`) que capturan la excepción (`catch (\Exception $e) {}`) pero no la registran (log) ni hacen nada. Esto dificulta mucho la detección de problemas y el debugging en producción (por ejemplo si la contraseña es incorrecta o no existe la BD).
- **Solución**: Añadir llamadas al logger (p.ej. `Log::error($e->getMessage())`) dentro de los bloques `catch` para tener constancia de los problemas cuando ocurran.

## 4. Fragilidad en la Integración de la API
- **Problema**: En `upload_data_to_api.php`, el éxito de la subida a la API se evalúa de manera extremadamente frágil, comprobando que la respuesta devuelva un texto exacto: `return $resp == '"Guardado Correctamente"';`. Si la API cambia ligeramente su mensaje, o se le añade un salto de línea, todo el proceso de borrado local fallará.
- **Solución**: Leer el código de estado HTTP (HTTP status code) devuelto por cURL mediante `curl_getinfo($curl, CURLINFO_HTTP_CODE)` y validar que sea un código de éxito (`200` o `201`).

## 5. Ausencia de Timeouts en cURL
- **Problema**: En la función `uploadToApi()` de `upload_data_to_api.php`, no hay un tiempo límite (`TIMEOUT`) estipulado para la conexión. Si el servidor de la API externa se cae o se queda colgado, el script se quedará bloqueado esperando una respuesta indefinidamente, deteniendo el flujo normal del exportador.
- **Solución**: Añadir la configuración `curl_setopt($curl, CURLOPT_TIMEOUT, 10);` (o un tiempo razonable) para evitar procesos bloqueados (zombies).

## 6. Variables de Entorno no implementadas
- **Problema**: Tal como se describe en `AGENTS.md`, el archivo `start_dump1090_exporter.sh` tiene valores hardcodeados para el tiempo de espera (`sleep 10`) y el límite de iteraciones para subir a la API (`count = 3`).
- **Solución**: Modificar el script Bash para que lea del archivo `.env` o del sistema las variables `T_INTERVAL_CHECK` y `T_INTERVAL_UPLOAD_API`, y así lograr que sea fácilmente parametrizable desde el exterior.

## 7. Código Muerto (Dead Code)
- **Problema**: Existen bloques de código inactivos o vacíos.
  - En `Dbconnection.php` el método `delete($id)` está completamente vacío.
  - En `upload_data_to_api.php` en la función `start()`, existe un bloque `try/catch` vacío para `if ($data) {}` que no hace absolutamente nada, con algo de código comentado.
- **Solución**: Limpiar el código eliminado las funciones sin uso y los bloques vacíos para mejorar la legibilidad.
