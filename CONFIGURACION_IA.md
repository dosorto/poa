# Configuración del Módulo de IA para Actividades

## 📋 Descripción

El módulo de IA permite generar automáticamente los campos de una actividad basándose únicamente en el nombre de la actividad. Soporta múltiples proveedores de IA:

- ✅ **OpenAI** (GPT-4o-mini)
- ✅ **Google Gemini** (Gemini Pro)
- ✅ **Qwen 3.2B** (Servidor Local)
- ✅ **Ollama** (Servidor Local - Compatible OpenAI)

Genera automáticamente:
- ✅ Descripción detallada de la actividad
- ✅ Resultado esperado
- ✅ Población objetivo
- ✅ Medio de verificación

## 🔧 Configuración

### Opción 1: Usar OpenAI

#### Paso 1: Obtener API Key de OpenAI
1. Visita [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Inicia sesión o crea una cuenta
3. Haz clic en "Create new secret key"
4. Copia la clave (guárdala en un lugar seguro)

#### Paso 2: Configurar en .env
```env
IA_PROVIDER=openai
OPENAI_API_KEY=sk-proj-abc123xyz456...
```

### Opción 2: Usar Google Gemini (Recomendado)

#### Paso 1: Obtener API Key de Gemini
1. Visita [https://makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)
2. Inicia sesión con tu cuenta de Google
3. Haz clic en "Create API Key"
4. Copia la clave

#### Paso 2: Configurar en .env
```env
IA_PROVIDER=gemini
GEMINI_API_KEY=AIzaSyAbc123xyz456...
```
Opción 3: Usar Qwen 3.2B (Local) ⭐ NUEVO

#### Paso 1: Instalar Qwen en tu servidor local

**Requisitos previos:**
- Docker (recomendado) o Python 3.8+
- GPU (opcional, pero recomendado para mejor rendimiento)
- 16GB RAM mínimo

**Opción A: Con Docker (Recomendado)**
```bash
# Descargar y ejecutar Qwen con Ollama
docker run -d \
  --name qwen \
  -p 8000:8000 \
  -v ollama:/root/.ollama \
  ollama/ollama:latest

# En otra terminal, descargar el modelo
docker exec -it qwen ollama pull qwen2.5:32b

# Iniciar el servidor API
docker exec -it qwen ollama serve
```, Gemini y Qwen, simplemente modifica el `.env`:

```env
# Para usar OpenAI
IA_PROVIDER=openai

# Para usar Gemini
IA_PROVIDER=gemini

# Para usar Qwen (Local)
IA_PROVIDER=qwen:32b
```

4. Por defecto Ollama expone la API en `http://localhost:11434`

#### Paso 2: Configurar en .env
```env
IA_PROVIDER=qwen
QWEN_BASE_URL=http://localhost:8000
QWEN_MODEL=qwen2.5:32b
```

Si usas Ollama directamente:
```env
IA_PROVIDER=qwen
QWEN_BASE_URL=http://localhost:11434
QWEN_MODEL=qwen2.5:32b
```

### Configuración Adicional (Opcional)

```env
# Tiempo mínimo entre solicitudes (en segundos)
IA_THROTTLE_SECONDS=30

# Modelo específico de OpenAI (opcional)
OPENAI_MODEL=gpt-4o-mini

# Modelo específico de Gemini (opcional)
GEMINI_MODEL=gemini-pro

# Modelo específico de Qwen (opcional)
QWEN_MODEL=qwen2.5:32b

# Modelo específico de Ollama (opcional)
OLLAMA_MODEL=qwen3:32b
```

### Paso 3: Limpiar caché

```bash
php artisan config:clear
php artisan cache:clear
```

## 🔄 Cambiar de Proveedor

Para cambiar entre OpenAI, Gemini, Qwen y Ollama, simplemente modifica el `.env`:

```env
# Para usar OpenAI
IA_PROVIDER=openai

# Para usar Gemini
IA_PROVIDER=gemini

# Para usar Qwen (Local en localhost:8000)
IA_PROVIDER=qwen

# Para usar Ollama (Local o remoto)
IA_PROVIDER=ollama
```

Luego ejecuta:
```bash
php artisan config:clear
```

## 💰 Costos Estimados

### OpenAI (GPT-4o-mini)
- **Costo por generación**: ~$0.001 USD
- **500 actividades**: ~$0.50 USD
- **1000 actividades**: ~$1.00 USD
- **Límites (Tier Free)**: 3 requests/minuto, 200/día
- **Límites (Tier 1)**: 500 requests/minuto

### Google Gemini (Gemini Pro)
- **Costo**: **GRATIS** hasta 60 requests/minuto
- **Sin límite diario** en tier gratuito
- **Ideal para uso institucional**
- **Calidad comparable a GPT-4o-mini**

### Qwen 3.2B (Local) ⭐ NUEVO
- **Costo**: **$0 USD** - Completamente gratis
- **Límites**: Solo limitado por tu hardware
- **Ideal para**: Máximo control y privacidad
- **Requisitos**: Servidor con 16GB+ RAM o GPU

### Ollama (Local o Remoto) ⭐ NUEVO
- **Costo**: **$0 USD** - Completamente gratis
- **Límites**: Solo limitado por tu hardware
- **Ideal para**: Producción corporativa, multi-usuario
- **Requisitos**: Servidor con 8GB+ RAM (depende del modelo)
- **Ventaja**: Puede estar en servidor remoto en tu red (10.16.33.215:11434)

**💡 Recomendación**: 
- Sin presupuesto y máxima privacidad: Usa **Qwen** o **Ollama** (offline)
- Múltiples usuarios: Usa **Ollama** (mejor escalabilidad)
- Con internet fiable: Usa **Gemini** (gratuito)
- Máxima calidad: Usa **OpenAI** (de pago)

## 🚀 Ventajas de Cada Proveedor

### OpenAI
✅ Respuestas muy precisas
✅ Mejor comprensión de contexto complejo
❌ Límites estrictos en tier gratuito
❌ Costos por uso

### Gemini
✅ **GRATIS** con límites generosos
✅ 60 requests/minuto (vs 3 de OpenAI free)
✅ Respuestas rápidas y de calidad
✅ Sin costos para uso institucional
❌ Puede requerir más específicidad en prompts

### Qwen 3.2B (Local)
✅ **COMPLETAMENTE GRATIS** - Sin costos por uso
✅ Funciona **100% offline** - Sin conexión a internet necesaria
✅ **Control total** - Tu servidor, tus datos
✅ **Privacidad garantizada** - Nada se envía a terceros
✅ Excelente relación calidad-rendimiento
✅ Ideal para instituciones con datos sensibles
❌ Requiere servidor local con recursos
❌ Puede ser más lento que OpenAI en máquinas débiles
❌ Requiere configuración inicial

### Ollama (Local o Remoto)
✅ **COMPLETAMENTE GRATIS** - Sin costos por uso
✅ **100% offline** (si está en la red local)
✅ **API compatible con OpenAI** - Fácil de integrar
✅ **Múltiples modelos** - Qwen, Llama, Mistral, etc.
✅ **Flexible** - Puede estar en servidor remoto en tu red
✅ **Escalable** - Agrega recursos según necesites
✅ Ideal para producción corporativa
❌ Requiere servidor con recursos
❌ Mantenimiento del servidor Ollama
❌ Requiere configuración inicial

## 💡 Cómo Usar el Módulo de IA

Luego ejecuta:
```bash
php artisan config:clear
```

1. **Crear Nueva Actividad**: Haz clic en "Nueva Actividad"
2. **Activar IA**: En el modal, haz clic en el botón "Generar con IA" (esquina superior derecha)
3. **Ingresar Nombre**: Escribe el nombre de la actividad de forma clara y específica
   - ✅ Bueno: "Capacitación docente en metodologías activas de enseñanza"
   - ❌ Malo: "Capacitación"
4. **Generar**: Haz clic en "Generar Actividad"
5. **Revisar y Ajustar**: La IA completará los campos automáticamente. Revísalos y ajusta si es necesario
6. **Continuar**: Procede normalmente con los siguientes pasos del formulario

## 🎯 Consejos para Mejores Resultados

- **Sé específico**: Incluye el contexto y objetivo principal
- **Usa verbos de acción**: "Realizar", "Implementar", "Capacitar", etc.
- **Incluye el alcance**: Menciona para quién es la actividad
- **Mínimo 10 caracteres**: El nombre debe ser descriptivo

### Ejemplos de Buenos Nombres:

✅ "Taller de actualización pedagógica para docentes de matemáticas"
✅ "Programa de refuerzo académico para estudiantes de bajo rendimiento"
✅ "Campaña de sensibilización sobre cuidado ambiental en la comunidad educativa"
✅ "Implementación de laboratorio de ciencias con equipamiento moderno"

## 💰 Costos Estimados

### OpenAI (GPT-4o-mini)
- **Costo por generación**: ~$0.001 USD
- **500 actividades**: ~$0.50 USD
- **1000 actividades**: ~$1.00 USD
- **Límites (Tier Free)**: 3 requests/minuto, 200/día
- **Límites (Tier 1)**: 500 requests/minuto

### Google Gemini (Gemini Pro)
- **Costo**: **GRATIS** hasta 60 requests/minuto
- **Sin límite diario** en tier gratuito
- **Ideal para uso institucional**
- **Calidad comparable a GPT-4o-mini**

**💡 Recomendación**: Usa Gemini para evitar costos y límites más estrictos.

## 🚀 Ventajas de Cada Proveedor

### OpenAI
✅ Respuestas muy precisas
✅ Mejor comprensión de contexto complejo
❌ Límites estrictos en tier gratuito
❌ Costos por uso

### Gemini
✅ **GRATIS** con límites generosos
✅ 60 requests/minuto (vs 3 de OpenAI free)
✅ Respuestas rápidas y de calidad
✅ Sin costos para uso institucional
❌ Puede requerir más específicidad en prompts

## 🔧 Solución de Problemas

### Error: "Missing API Key"
- Verifica que hayas agregado la API key correcta en el `.env`
- Asegúrate de usar el nombre correcto: `OPENAI_API_KEY` o `GEMINI_API_KEY`
- Ejecuta `php artisan config:clear`

### Error: "Request rate limit has been exceeded" (OpenAI)
**Solución 1**: Cambia a Gemini
```env
IA_PROVIDER=gemini
GEMINI_API_KEY=tu-api-key-de-gemini
```

**Solución 2**: Espera 30-60 segundos entre solicitudes

**Solución 3**: Actualiza tu tier de OpenAI depositando $5

### Error: "Invalid API Key" (Gemini)
- Verifica que tu API key sea válida en [Google AI Studio](https://makersuite.google.com)
- Regenera la clave si es necesario
- Asegúrate de copiar la clave completa

### Error: "Connection refused" o "Could not connect to Qwen" (Qwen)
**Verifica que Qwen esté corriendo:**
```bash
# Si usas Docker:
docker ps | grep qwen

# Si usas Ollama:
curl http://localhost:11434/api/tags
curl http://localhost:8000/v1/models  # Si Ollama expone en 8000
```

**Si no está corriendo:**
```bash
# Con Docker:
docker run -d -p 8000:8000 ollama/ollama:latest

# Con OllamaConnection refused" o "Could not connect to Ollama" (Ollama)
**Verifica que Ollama esté corriendo:**
```bash
# Verifica que el servicio está disponible
curl -s http://10.16.33.215:11434/api/tags | head -20

# Si no responde, inicia Ollama
ollama serve

# O si está en Docker:
docker ps | grep ollama
```

**Verifica la URL en .env:**
```env
OLLAMA_HOST=http://10.16.33.215:11434
```

**Asegúrate de que el puerto está accesible:**
```bash
# Desde tu máquina
nc -zv 10.16.33.215 11434

# O con telnet
telnet 10.16.33.215 11434
```

### Error: "Modelo no encontrado" (Qwen/Ollama)
```bash
# Verifica qué modelos tienes instalados
ollama list

# Si no tienes qwen3:32b instalado, descárgalo:
ollama pull qwen3:32b

# Verifica que la variable en .env coincida
OLLAMA_MODEL=qwen3
# Verifica qué modelos tienes instalados
ollama list

# Si no tienes qwen2.5:32b instalado, descárgalo:
ollama pull qwen2.5:32b

# Verifica que la variable en .env coincida
QWEN_MODEL=qwen2.5:32b
```

### Qwen es muy lento
- Esto es normal en máquinas sin GPU
- Descarga un modelo más pequeño:
```bash
ollama pull qwen2.5:7b
# Luego en .env:
QWEN_MODEL=qwen2.5:7b
```

- O instala CUDA para GPU:
  - [NVIDIA CUDA](https://developer.nvidia.com/cuda-downloads)
  - Ollama lo detectará automáticamente

### La IA genera contenido en otro idioma (Qwen)
- Qwen a veces genera en chino. Esto mejora con modelos más grandes
- Prueba con: `qwen2.5:32b` en lugar de versiones más pequeñas
- O cambia a Gemini si necesitas garantizar español
- Sé más específico en el nombre de la actividad
- Incluye el objetivo y el público objetivo
- Ejemplo bueno: "Taller de capacitación en Excel avanzado para personal administrativo"
- Ejemplo malo: "Capacitación"

## 🚀 Funcionalidades Adicionales

### Modo Manual
- Puedes alternar entre "Generar con IA" y "Modo Manual"
- El modo manual te permite ingresar todos los campos manualmente
- Útil si prefieres tener control total sobre el contenido

### Edición de Actividades
- Al editar una actividad existente, el botón de IA no está disponible
- Esto previene sobrescribir datos existentes accidentalmente

## 📊 Modelo Utilizado

**GPT-4o-mini**
- Modelo optimizado y económico de OpenAI
- Excelente balance entre calidad y costo
- Respuestas rápidas (2-3 segundos típicamente)
- Capacidad de entender contexto institucional

## 🔐 Seguridad

- La API Key nunca se expone en el frontend
- Todas las llamadas se hacen desde el backend (Livewire)
- Los datos se procesan de forma segura
- No se almacena información sensible en los logs de OpenAI

## 📞 Soporte

Si tienes problemas con la configuración:

1. Verifica que tu API Key sea válida
2. Revisa los logs de Laravel: `storage/logs/laravel.log`
3. Consulta la documentación oficial de OpenAI: [https://platform.openai.com/docs](https://platform.openai.com/docs)
