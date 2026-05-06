# TallerSmart - Sistema de Gestión de Talleres Mecánicos

## 📖 Propósito del Programa

**TallerSmart** es un sistema integral de gestión para talleres mecánicos desarrollado en **PHP 8.2** con el framework **Yii3**. Este sistema permite administrar de manera eficiente todas las operaciones de un taller, incluyendo:

- **Gestión de Usuarios y Roles**: Control de acceso basado en roles (RBAC) con permisos granulares
- **Administración de Clientes y Vehículos**: Registro y seguimiento de clientes y sus vehículos
- **Gestión de Servicios**: Catálogo de servicios y categorías disponibles
- **Agendamiento de Citas**: Programación y seguimiento de citas para mantenimiento y reparaciones
- **Órdenes de Servicio**: Creación, seguimiento y finalización de órdenes de trabajo
- **Control de Inventario**: Gestión de repuestos y materiales con movimientos de stock
- **Pagos y Facturación**: Registro de pagos y asignación a órdenes de servicio
- **Dashboard y Reportes**: Estadísticas en tiempo real, alertas de stock bajo, ingresos mensuales
- **Auditoría**: Registro completo de actividades del sistema

El sistema fue convertido desde una arquitectura Next.js/TypeScript/React a PHP 8.2 + Yii3, manteniendo toda la funcionalidad original pero aprovechando las ventajas del ecosistema PHP para entornos empresariales.

---

## 🚀 Requisitos Previos

- **Docker** y **Docker Compose** instalados
- Opcional: **Git** para clonar el repositorio
- Puerto 8080, 8081, 3306 disponibles en tu sistema

---

## 🛠️ Instalación y Ejecución para Desarrollo

### 1. Clonar o Acceder al Proyecto

```bash
cd tallersmart-yii3
```

### 2. Configurar Variables de Entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` si necesitas personalizar alguna configuración (puertos, contraseñas, etc.).

### 3. Iniciar los Contenedores Docker

```bash
docker-compose up -d
```

Esto levantará los siguientes servicios:
- **Nginx**: Servidor web (puerto 8080)
- **PHP 8.2-FPM**: Procesador de aplicaciones PHP
- **MySQL 8.0**: Base de datos (puerto 3306)
- **phpMyAdmin**: Interfaz gráfica para MySQL (puerto 8081)

### 4. Esperar la Inicialización de MySQL

Espera aproximadamente **30 segundos** para que MySQL se inicialice correctamente antes de ejecutar las migraciones.

### 5. Ejecutar Migraciones (Opcional - ya incluidas en init.sql)

La base de datos se crea automáticamente con el script `docker/mysql/init.sql`. Si necesitas ejecutar migraciones adicionales:

```bash
docker-compose exec php php yii migrate --interactive=0
```

### 6. Verificar la Instalación

- **API REST**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8081
  - Usuario: `tallersmart`
  - Contraseña: `tallersmart123`
  - Base de datos: `tallersmart_db`

### 7. Probar la API

#### Login de prueba:
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tallersmart.com","password":"admin123"}'
```

#### Obtener estadísticas del dashboard:
```bash
curl http://localhost:8080/api/dashboard/stats \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### 8. Detener el Entorno de Desarrollo

```bash
docker-compose down
```

Para eliminar también volúmenes y datos:
```bash
docker-compose down -v
```

---

## 🏭 Configuración para Producción

### Consideraciones de Seguridad

1. **Cambiar credenciales por defecto**:
   - Modifica las contraseñas en `docker-compose.yml` y `.env`
   - Cambia la contraseña del usuario `root` de MySQL
   - Actualiza las credenciales de phpMyAdmin o deshabilítalo

2. **Configurar HTTPS**:
   - Usa un proxy inverso (Nginx/Apache) con certificados SSL
   - Considera usar Let's Encrypt para certificados gratuitos

3. **Variables de entorno seguras**:
   - Genera un `APP_SECRET` único y seguro
   - Usa contraseñas fuertes para la base de datos

### Pasos para Despliegue en Producción

#### 1. Preparar el Servidor

Asegúrate de tener Docker y Docker Compose instalados en tu servidor de producción.

#### 2. Clonar o Subir el Código

```bash
git clone <tu-repositorio> tallersmart-yii3
cd tallersmart-yii3
```

#### 3. Configurar Variables de Producción

Crea un archivo `.env` con valores seguros:

```bash
cp .env.example .env
nano .env
```

Modifica al menos:
- `MYSQL_ROOT_PASSWORD`: Contraseña segura para root
- `MYSQL_PASSWORD`: Contraseña segura para el usuario de la app
- `APP_SECRET`: Genera uno nuevo con `openssl rand -hex 32`

#### 4. Ajustar docker-compose.yml para Producción

- **Eliminar o proteger phpMyAdmin** (no exponerlo públicamente)
- **Configurar redes aisladas** para los contenedores
- **Agregar límites de recursos** (CPU, memoria)
- **Configurar restart policies**: `restart: unless-stopped`

Ejemplo de modificación para deshabilitar phpMyAdmin en producción:

```yaml
# Comentar o eliminar el servicio phpmyadmin en docker-compose.yml
# phpmyadmin:
#   image: phpmyadmin/phpmyadmin
#   ...
```

#### 5. Construir y Levantar Contenedores

```bash
docker-compose -f docker-compose.prod.yml up -d --build
```

*(Opcional: Crea un `docker-compose.prod.yml` específico para producción)*

#### 6. Ejecutar Migraciones

```bash
docker-compose exec php php yii migrate --interactive=0
```

#### 7. Configurar Backup Automático

Implementa un script para backups periódicos de la base de datos:

```bash
# Ejemplo: backup diario
0 2 * * * docker exec tallersmart-mysql mysqldump -u tallersmart -p'tallersmart123' tallersmart_db > /backups/tallersmart_$(date +\%Y\%m\%d).sql
```

#### 8. Monitoreo y Logs

Configura la rotación de logs y monitoreo:

```bash
# Ver logs en tiempo real
docker-compose logs -f

# Rotación de logs en docker-compose.yml
services:
  nginx:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

### Endpoints en Producción

- **API**: https://tudominio.com/api
- **Frontend** (si se implementa): https://tudominio.com
- **phpMyAdmin**: Solo accesible desde red interna o VPN

---

## 📡 Referencia Rápida de la API

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/auth/me` | Obtener usuario actual |
| PUT | `/api/auth/change-password` | Cambiar contraseña |

### Usuarios
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/usuarios` | Listar usuarios |
| POST | `/api/usuarios` | Crear usuario |
| GET | `/api/usuarios/{id}` | Obtener usuario |
| PUT | `/api/usuarios/{id}` | Actualizar usuario |
| DELETE | `/api/usuarios/{id}` | Eliminar usuario |

### Dashboard
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/dashboard/stats` | Estadísticas generales |
| GET | `/api/dashboard/proximas-citas` | Próximas citas |
| GET | `/api/dashboard/ordenes-recientes` | Órdenes recientes |
| GET | `/api/dashboard/stock-bajo` | Alertas de stock |
| GET | `/api/dashboard/ingresos-meses` | Ingresos por mes |

*(Consulta la documentación completa de cada controlador para más detalles)*

---

## 🔑 Credenciales por Defecto (Solo Desarrollo)

### Usuarios de Prueba
| Email | Contraseña | Rol |
|-------|------------|-----|
| admin@tallersmart.com | admin123 | Administrador |
| tecnico@tallersmart.com | tecnico123 | Técnico |
| recepcion@tallersmart.com | recepcion123 | Recepción |

### Base de Datos
- **Host**: localhost:3306
- **Usuario**: tallersmart
- **Contraseña**: tallersmart123
- **Base de datos**: tallersmart_db

### phpMyAdmin
- **URL**: http://localhost:8081
- **Usuario**: tallersmart
- **Contraseña**: tallersmart123

---

## 🧩 Arquitectura del Proyecto

```
tallersmart-yii3/
├── config/              # Configuración de la aplicación
├── controllers/         # Controladores REST API
├── models/              # Modelos ActiveRecord
├── views/               # Vistas (si se requieren)
├── migrations/          # Migraciones de base de datos
├── runtime/             # Archivos temporales
├── web/                 # Document root (archivos públicos)
├── docker/              # Configuración Docker
│   ├── php/
│   ├── nginx/
│   └── mysql/
├── docker-compose.yml   # Orquestación Docker
└── README.md            # Este archivo
```

---

## 🤝 Contribuciones

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/NuevaFuncionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/NuevaFuncionalidad`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está desarrollado como una solución personalizada para la gestión de talleres mecánicos.

---

## 🆘 Soporte

Para reportar errores o solicitar soporte, contacta al equipo de desarrollo o abre un issue en el repositorio.

---

**Desarrollado con ❤️ usando PHP 8.2 y Yii3**
