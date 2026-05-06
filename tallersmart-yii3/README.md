# TallerSmart API - Yii3 PHP 8.2

Sistema de gestión de talleres mecánicos convertido a PHP 8.2 + Yii3 con MySQL.

## 🚀 Inicio Rápido con Docker

### Prerrequisitos
- Docker y Docker Compose instalados
- Git

### Instalación

1. **Clonar o acceder al proyecto**
```bash
cd /workspace/tallersmart-yii3
```

2. **Copiar archivo de entorno**
```bash
cp .env.example .env
```

3. **Iniciar contenedores Docker**
```bash
docker-compose up -d
```

4. **Esperar a que MySQL esté listo (30 segundos)**
```bash
sleep 30
```

5. **Ejecutar migraciones de base de datos**
```bash
docker-compose exec php php yii migrate --interactive=0
```

6. **Cargar datos iniciales**
```bash
docker-compose exec php php yii fixture/load --interactive=0
```

### Acceder a la aplicación

- **API REST**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8081 (usuario: tallersmart, contraseña: tallersmart123)

## 📡 Endpoints de la API

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login de usuario |
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/auth/me` | Obtener usuario actual |
| POST | `/api/auth/change-password` | Cambiar contraseña |

### Usuarios
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/usuarios` | Listar usuarios |
| GET | `/api/usuarios/{id}` | Obtener usuario |
| POST | `/api/usuarios` | Crear usuario |
| PUT | `/api/usuarios/{id}` | Actualizar usuario |
| DELETE | `/api/usuarios/{id}` | Eliminar usuario |

### Clientes
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/clientes` | Listar clientes |
| GET | `/api/clientes/{id}` | Obtener cliente |
| POST | `/api/clientes` | Crear cliente |
| PUT | `/api/clientes/{id}` | Actualizar cliente |
| DELETE | `/api/clientes/{id}` | Eliminar cliente |

### Vehículos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/vehiculos` | Listar vehículos |
| GET | `/api/vehiculos/{id}` | Obtener vehículo |
| POST | `/api/vehiculos` | Crear vehículo |
| PUT | `/api/vehiculos/{id}` | Actualizar vehículo |
| DELETE | `/api/vehiculos/{id}` | Eliminar vehículo |

### Servicios
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/servicios` | Listar servicios |
| GET | `/api/servicios/{id}` | Obtener servicio |
| POST | `/api/servicios` | Crear servicio |
| PUT | `/api/servicios/{id}` | Actualizar servicio |
| DELETE | `/api/servicios/{id}` | Eliminar servicio |

### Citas
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/citas` | Listar citas |
| GET | `/api/citas/{id}` | Obtener cita |
| POST | `/api/citas` | Crear cita |
| PUT | `/api/citas/{id}` | Actualizar cita |
| POST | `/api/citas/{id}/cancel` | Cancelar cita |
| DELETE | `/api/citas/{id}` | Eliminar cita |

### Órdenes de Servicio
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/ordenes-servicio` | Listar órdenes |
| GET | `/api/ordenes-servicio/{id}` | Obtener orden |
| POST | `/api/ordenes-servicio` | Crear orden |
| PUT | `/api/ordenes-servicio/{id}` | Actualizar orden |
| POST | `/api/ordenes-servicio/{id}/finalizar` | Finalizar orden |
| DELETE | `/api/ordenes-servicio/{id}` | Eliminar orden |

### Inventario
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/inventario` | Listar items |
| GET | `/api/inventario/{id}` | Obtener item |
| POST | `/api/inventario` | Crear item |
| PUT | `/api/inventario/{id}` | Actualizar item |
| POST | `/api/inventario/{id}/adjust` | Ajustar stock |
| GET | `/api/inventario/{id}/movements` | Movimientos del item |
| DELETE | `/api/inventario/{id}` | Eliminar item |

### Dashboard
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/dashboard/stats` | Estadísticas generales |
| GET | `/api/dashboard/proximas-citas` | Citas próximas |
| GET | `/api/dashboard/ordenes-recientes` | Órdenes recientes |
| GET | `/api/dashboard/stock-bajo` | Items con stock bajo |
| GET | `/api/dashboard/ingresos-meses` | Ingresos por mes |

## 🔑 Autenticación

La API utiliza Bearer Token para autenticación.

### Ejemplo de Login
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tallersmart.com","password":"admin123"}'
```

### Respuesta
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "usuario": {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@tallersmart.com",
      "rol_id": 1
    }
  }
}
```

### Usar Token en Requests
```bash
curl -X GET http://localhost:8080/api/usuarios \
  -H "Authorization: Bearer abc123..."
```

## 🛠️ Comandos Útiles

### Ver logs de la aplicación
```bash
docker-compose logs -f php
```

### Acceder al contenedor PHP
```bash
docker-compose exec php bash
```

### Detener contenedores
```bash
docker-compose down
```

### Reiniciar contenedores
```bash
docker-compose restart
```

### Limpiar y reconstruir
```bash
docker-compose down -v
docker-compose up -d --build
```

## 📁 Estructura del Proyecto

```
tallersmart-yii3/
├── config/              # Configuración de la aplicación
│   └── main.php         # Configuración principal
├── controllers/         # Controladores de la API
│   └── api/             # Controladores REST
│       ├── BaseController.php
│       ├── AuthController.php
│       ├── UsuarioController.php
│       ├── ClienteController.php
│       ├── VehiculoController.php
│       ├── ServicioController.php
│       ├── CitaController.php
│       ├── OrdenServicioController.php
│       ├── InventarioController.php
│       └── DashboardController.php
├── models/              # Modelos ActiveRecord
│   ├── Usuario.php
│   ├── Sesion.php
│   ├── Rol.php
│   ├── Permiso.php
│   ├── Cliente.php
│   ├── Vehiculo.php
│   ├── Servicio.php
│   ├── Categoria.php
│   ├── Cita.php
│   ├── CitaServicio.php
│   ├── OrdenServicio.php
│   ├── OrdenServicioDetalle.php
│   ├── InventoryItem.php
│   ├── InventoryMovement.php
│   ├── Pago.php
│   └── AuditLog.php
├── docker/              # Configuración Docker
│   ├── php/Dockerfile
│   ├── nginx/default.conf
│   └── mysql/init.sql
├── web/                 # Punto de entrada web
│   └── index.php
├── docker-compose.yml
├── .env.example
└── README.md
```

## 🔧 Tecnologías

- **PHP 8.2** - Lenguaje de programación
- **Yii3** - Framework PHP
- **MySQL 8.0** - Base de datos
- **Nginx** - Servidor web
- **Docker** - Contenerización

## 📝 Notas

- Los datos de prueba se cargan automáticamente desde `docker/mysql/init.sql`
- El usuario admin por defecto es: `admin@tallersmart.com` / `admin123`
- La API sigue estándares RESTful
- Todas las respuestas están en formato JSON

## 🐛 Solución de Problemas

### Error de conexión a MySQL
```bash
docker-compose restart db
sleep 10
```

### Permisos de archivos
```bash
chmod -R 777 runtime/
chmod -R 777 web/assets/
```

### Limpiar caché
```bash
docker-compose exec php php yii cache/flush-all
```
