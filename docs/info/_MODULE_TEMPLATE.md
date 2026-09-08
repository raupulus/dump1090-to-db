# Módulo: [Nombre del Módulo]

[Breve descripción de una línea sobre el propósito general del módulo dentro del sistema].

---

## 1. Qué hace y qué NO hace

### Qué hace
- [Responsabilidad 1]
- [Responsabilidad 2]

### Qué NO hace
- [Límite de alcance 1]
- [Límite de alcance 2]

---

## 2. Modelo de datos
- **Estructura interna / DTOs**: [Descripción de estructuras, objetos de valor o propiedades].
- **Esquema de persistencia**: [Tablas involucradas, columnas y tipos, o indicar si es en memoria/volátil].

---

## 3. Flujos principales
1. **[Nombre del Flujo 1]**:
   - [Paso 1]
   - [Paso 2]
2. **[Nombre del Flujo 2]**:
   - [Paso 1]
   - [Paso 2]

---

## 4. Puntos de entrada
- **Métodos públicos / CLI / Endpoints**:
  - `[Método o ruta]`: [Parámetros de entrada, tipos, autenticación o permisos requeridos y límites operativos].

---

## 5. Dependencias en ambos sentidos
- **Depende de (Hacia adentro)**:
  - `[Componente o librería externa]`
- **Consumido por (Hacia afuera)**:
  - `[Componente o script que lo invoca]`

---

## 6. Configuración
| Variable | Tipo | Valor por defecto | Efecto / Comportamiento |
|---|---|---|---|
| `VARIABLE_EJEMPLO` | `string` | `valor` | [Descripción del efecto en tiempo de ejecución] |

---

## 7. Trampas conocidas
- [Trampa o particularidad técnica no evidente que pueda inducir a error al modificar el módulo].

---

## 8. Tests que lo cubren
- [Ruta de tests o `⚠️ sin verificar` si no existen pruebas automatizadas].

---

## 9. Pendiente real
- [ ] [Tarea pendiente verificada]

---
> Creado: 2026-09-08 · Última revisión: 2026-09-08
