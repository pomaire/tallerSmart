# README_DEV.md - Documentación del Desarrollo de TallerSmart

## 📋 Descripción General
Este documento detalla el proceso de desarrollo, decisiones técnicas y lecciones aprendidas durante la creación del sistema **TallerSmart**, una plataforma integral de gestión para talleres mecánicos en Chile, desarrollada con PHP 8.2 y Yii3.

## 🛠️ Stack Tecnológico

### Backend
- **Lenguaje:** PHP 8.2 (con type hints estrictos y atributos)
- **Framework:** Yii3 (Yet Another Framework v3)
- **ORM:** Yii3 ActiveRecord
- **Autenticación:** JWT (firebase/php-jwt ^7.0)
- **Base de Datos:** MySQL 8.0

### Frontend
- **Motor de Plantillas:** Twig
- **Interactividad:** Alpine.js (ligero y reactivo)
- **Estilos:** Tailwind CSS (vía CDN para desarrollo)
- **Íconos:** FontAwesome 6

### Infraestructura
- **Contenerización:** Docker + Docker Compose
- **Web Server:** Nginx
- **PHP-FPM:** PHP 8.2-FPM
- **Gestión de Dependencias:** Composer

## 📁 Estructura del Proyecto

```
tallersmart-yii3/
├── config/                  # Configuraciones de la aplicación
│   ├── params.php           # Parámetros globales
│   └── web.php              # Configuración web
├── controllers/             # Controladores de la aplicación
│   ├── api/                 # Controladores REST API
│   │   ├── AuthController.php
│   │   ├── UsuarioController.php
│   │   ├── ClienteController.php
│   │   └── ... (10 controladores)
│   └── web/                 # Controladores web (si aplica)
├── models/                  # Modelos ActiveRecord
│   ├── Usuario.php
│   ├── Cliente.php
│   ├── Vehiculo.php
│   └── ... (17 modelos)
├── views/                   # Vistas Twig
│   ├── layouts/             # Layouts principales
│   │   ├── main.twig
│   │   ├── _sidebar.php
│   │   ├── _header.php
│   │   └── _footer.php
│   ├── rbac/                # Módulo 1: RBAC
│   ├── categoria/           # Módulo 2: Categorías
│   ├── servicio/            # Módulo 3: Servicios
│   ├── cliente/             # Módulo 4: Clientes
│   ├── vehiculo/            # Módulo 5: Vehículos
│   ├── inventario/          # Módulo 6: Inventario
│   ├── tecnico/             # Módulo 7: Técnicos
│   ├── cita/                # Módulo 8: Citas
│   ├── orden-servicio/      # Módulo 9: Órdenes
│   ├── pago/                # Módulo 10: Pagos
│   ├── notificacion/        # Módulo 11: Notificaciones
│   ├── audit-log/           # Módulo 12: AuditLog
│   ├── dashboard/           # Módulo 13: Dashboard
│   └── manual/              # Módulo 14: Manual de Usuario
├── helpers/                 # Helpers personalizados
│   ├── ChileanHelper.php    # RUT, patentes, teléfonos, CLP
│   └── ...
├── assets/                  # AssetBundles
│   └── AppAsset.php
├── docker/                  # Configuración Docker
│   ├── php/Dockerfile
│   ├── nginx/default.conf
│   └── mysql/init.sql
├── docker-compose.yml       # Orquestación de contenedores
├── composer.json            # Dependencias PHP
├── README.md                # Documentación general
└── README_DEV.md            # Este archivo
```

## 🔧 Decisiones Arquitectónicas Clave

### 1. Elección de Yii3 sobre Laravel/Symfony
- **Razón:** Yii3 ofrece mejor rendimiento nativo, estructura más ligera y es ideal para APIs REST.
- **Ventaja:** Menor overhead, curva de aprendizaje suave para equipos que vienen de Yii2.

### 2. Twig como Motor de Plantillas
- **Razón:** Separación clara entre lógica y presentación, sintaxis limpia y segura.
- **Alternativa considerada:** PHP nativo con vistas, pero Twig ofrece mejor mantenibilidad.

### 3. Alpine.js sobre React/Vue
- **Razón:** Para la interactividad necesaria (modales, dropdowns, búsquedas), Alpine.js es suficiente y mucho más ligero.
- **Ventaja:** Sin build process, integración directa con Twig, ideal para proyectos monolíticos modernos.

### 4. Docker desde el Día 1
- **Razón:** Garantizar consistencia entre entornos (desarrollo, testing, producción).
- **Beneficio:** Cualquier desarrollador puede levantar el entorno con `docker-compose up -d`.

## 🇨🇱 Adaptaciones al Contexto Chileno

### Validación de RUT
```php
// Helper personalizado
ChileanHelper::validarRUT('12345678-K'); // true
ChileanHelper::formatearRUT('12345678K'); // '12.345.678-K'
```

### Patentes de Vehículos
- **Formato Antiguo:** LLNNNN (ej: AA·BB·12)
- **Formato Nuevo:** LLLLNN (ej: ABCD·12)
- **Validación:** Expresiones regulares específicas para cada formato.

### Moneda y Tributación
- **Moneda:** CLP (Peso Chileno) sin decimales.
- **IVA:** 19% configurado en parámetros del sistema.
- **Formato:** `$ 1.234.567` (punto como separador de miles).

### Teléfonos
- **Formato:** +56 9 XXXX XXXX (móviles) / +56 2 XXXX XXXX (fijos).

## 🚀 Flujo de Desarrollo

### 1. Configuración Inicial
```bash
git clone https://github.com/pomaire/tallerSmart.git
cd tallersmart-yii3
cp .env.example .env
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php php yii migrate --interactive=0
```

### 2. Desarrollo de un Nuevo Módulo
1. Crear modelo ActiveRecord: `php yii gii/model --tableName=tabla --modelClass=Modelo`
2. Crear controlador REST: `php yii gii/api-controller --modelClass=app\models\Modelo --controllerClass=app\controllers\api\ModeloController`
3. Crear vista Twig en `views/modulo/index.twig`
4. Agregar ruta en menú lateral (`_sidebar.php`)
5. Probar con Postman/cURL y navegador

### 3. Testing
- **Unit Tests:** PHPUnit (pendiente de implementación completa)
- **Integration Tests:** Codeception (pendiente)
- **Manual:** Verificación de flujos completos en entorno Docker

## 🐛 Desafíos Superados

### 1. Conflictos de Dependencias en Composer
**Problema:** `yiisoft/app dev-master` requería `yiisoft/session ^3.0.1`, pero el `composer.json` inicial tenía `^2.0`.
**Solución:** Actualizar a `^3.0.1` y verificar compatibilidad con todos los paquetes.

### 2. Validación de RUT con Dígito Verificador
**Problema:** Algoritmo de módulo 11 específico de Chile.
**Solución:** Implementar helper `ChileanHelper::validarRUT()` con el algoritmo oficial.

### 3. Integración de Twig con Yii3
**Problema:** Yii3 no incluye Twig por defecto.
**Solución:** Configurar `ViewRenderer` en `config/web.php` para usar `twig/twig`.

### 4. Gestión de Estados en Frontend sin Build Process
**Problema:** Necesidad de reactividad sin Webpack/Vite.
**Solución:** Alpine.js permite estado reactivo directamente en el HTML con `x-data`.

## 📊 Métricas del Proyecto

- **Líneas de Código:** ~15,000+ (PHP + Twig + JS)
- **Módulos:** 14 completamente funcionales
- **Controladores REST:** 10
- **Modelos ActiveRecord:** 17
- **Vistas Twig:** 14 (una por módulo)
- **Helpers Personalizados:** 1 (ChileanHelper con 4 métodos principales)
- **Tablas en BD:** 20+

## 🔒 Seguridad Implementada

1. **Autenticación JWT:** Tokens con expiración configurable.
2. **RBAC:** Roles y permisos granulares por módulo/acción.
3. **AuditLog:** Registro inmutable de todas las acciones críticas.
4. **Validación de Inputs:** Todos los inputs validados y sanitizados.
5. **HTTPS:** Configurado en producción (certificado SSL obligatorio).
6. **Password Hashing:** `password_hash()` con `PASSWORD_ARGON2ID`.

## 📈 Roadmap Futuro

### Corto Plazo (1-3 meses)
- [ ] Implementar tests unitarios con PHPUnit (cobertura >80%)
- [ ] Integrar facturación electrónica con SII (Servicio de Impuestos Internos)
- [ ] Portal de clientes para seguimiento de órdenes
- [ ] App móvil para técnicos (React Native)

### Mediano Plazo (3-6 meses)
- [ ] Módulo de compras y proveedores
- [ ] Integración con pasarelas de pago (Webpay, MercadoPago)
- [ ] Reportes avanzados con exportación a Excel/PDF
- [ ] Multi-sucursal (gestión de varios talleres)

### Largo Plazo (6-12 meses)
- [ ] IA para predicción de fallas basada en historial
- [ ] Integración con marketplaces de repuestos
- [ ] API pública para partners
- [ ] Versión SaaS multi-tenant

## 🤝 Contribuciones

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commitear cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Pull Request describiendo los cambios

## 📄 Licencia

Copyright © 2026 TallerSmart. Todos los derechos reservados.

## 📞 Soporte

- **Email:** soporte@tallersmart.cl
- **Teléfono:** +56 2 2345 6789
- **Horario:** Lunes a Viernes, 9:00 - 18:00 (hora chilena)

---

**Desarrollado con ❤️ para los talleres mecánicos de Chile**  
*Última actualización: Mayo 2026*
