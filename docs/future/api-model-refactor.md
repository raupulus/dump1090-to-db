# Futuro: Refactorización o Eliminación de `src/Models/Api.php`

Decisión técnica acordada pero aplazada respecto a la clase stub `src/Models/Api.php`.

---

## 1. Contexto y Estado Actual
En la versión inicial del repositorio existía un archivo `Models/Api.php` (actualmente `src/Models/Api.php`) con una clase completamente vacía (`class Api {}`). La comunicación con la API externa está centralizada directamente en `src/upload_data_to_api.php` mediante funciones imperativas de cURL.

---

## 2. Decisión Aplazada
Se pospone la decisión de refactorizar la subida a una clase orientada a objetos (p. ej. `App\Services\AirflightApiClient`) o purgar definitivamente el archivo `src/Models/Api.php` del repositorio.

- **Alternativa A:** Convertir `src/Models/Api.php` en una clase cliente HTTP robusta orientada a objetos que encapsule cURL, validaciones y serialización JSON.
- **Alternativa B:** Eliminar definitivamente `src/Models/Api.php` como código muerto si `src/upload_data_to_api.php` continúa funcionando de forma estable.

---

## 3. Criterio de Activación
Se abordará cuando se introduzca una segunda API externa o cuando se acometa la suite de pruebas automatizadas que requiera inyección de dependencias para mockear el cliente HTTP.

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
