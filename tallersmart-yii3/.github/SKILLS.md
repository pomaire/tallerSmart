# Skills y Competencias del Proyecto TallerSmart

## 🎯 Habilidades Técnicas Requeridas

### Backend (PHP 8.2 + Yii3)
- **PHP Moderno:** Type hints, attributes, match expressions, union types
- **Yii3 Framework:** ActiveRecord, REST API, middleware, dependency injection
- **MySQL Avanzado:** Joins complejos, transacciones, índices, optimización de queries
- **Seguridad:** JWT, RBAC, password hashing, SQL injection prevention
- **Patrones de Diseño:** Repository, Service Layer, Factory, Singleton

### Frontend (Twig + Alpine.js)
- **Twig Templating:** Herencia de layouts, macros, filters personalizados
- **Alpine.js:** Reactividad, x-data, x-model, eventos, stores
- **CSS Moderno:** Tailwind CSS utility classes, responsive design
- **JavaScript ES6+:** Arrow functions, async/await, destructuring, modules

### DevOps e Infraestructura
- **Docker:** Dockerfile multi-stage, docker-compose, networking, volumes
- **Nginx:** Reverse proxy, caching, SSL termination, rewrite rules
- **Git:** Branching strategies, rebase, merge conflicts resolution
- **CI/CD:** GitHub Actions (pendiente de implementación)

## 🇨🇱 Conocimientos Específicos de Chile

### Normativa Tributaria
- **RUT:** Algoritmo de validación módulo 11, formato con puntos y guión
- **IVA:** 19% vigente, cálculo sobre neto, exenciones específicas
- **Documentos Tributarios:** Boletas, facturas, notas de crédito/débito
- **SII:** Integración con Servicio de Impuestos Internos (futuro)

### Patentes de Vehículos
- **Formato Antiguo (pre-2007):** LLNNNN (ej: AA·BB·12)
- **Formato Nuevo (post-2007):** LLLLNN (ej: ABCD·12)
- **Validación:** Combinaciones válidas según año del vehículo

### Telecomunicaciones
- **Teléfonos Móviles:** +56 9 XXXX XXXX (8 dígitos después del 9)
- **Teléfonos Fijos:** +56 2 XXXX XXXX (Santiago) / +56 [región] XXXX XXXX
- **Portabilidad:** Reconocer que el prefijo no garantiza la compañía original

## 🏗️ Arquitectura del Sistema

### Patrón MVC Mejorado
```
Cliente → Controller → Service → Model → Database
              ↓
           View (Twig)
```

### Flujo de Autenticación
1. Cliente envía credenciales → `AuthController::login()`
2. Validación contra BD → Generación de JWT
3. Retorno de token → Cliente almacena en localStorage
4. Peticiones futuras incluyen `Authorization: Bearer <token>`
5. Middleware valida token y extrae usuario

### Gestión de Estados en Frontend
- **Componentes:** Cada vista tiene su propio `x-data()` con estado local
- **Comunicación:** Eventos personalizados para comunicación entre componentes
- **Persistencia:** localStorage para preferencias de usuario

## 📚 Recursos de Aprendizaje

### Documentación Oficial
- [Yii3 Guide](https://www.yiiframework.com/doc/guide/3.0)
- [Twig Templates](https://twig.symfony.com/doc/3.x/)
- [Alpine.js Documentation](https://alpinejs.dev/start-here)
- [Tailwind CSS](https://tailwindcss.com/docs)

### Tutoriales Recomendados
- PHP 8.2 New Features (YouTube: Laracasts)
- Yii3 from Scratch (Udemy)
- Building Reactive Interfaces with Alpine.js (Frontend Masters)

### Herramientas de Desarrollo
- **IDE:** PhpStorm (recomendado) o VS Code con extensiones PHP Intelephense
- **Debugging:** Xdebug + PhpStorm
- **API Testing:** Postman o Insomnia
- **Database:** phpMyAdmin o DBeaver

## 🔐 Mejores Prácticas de Seguridad

1. **Nunca confiar en input del usuario:** Validar y sanitizar todo
2. **Prepared Statements:** Siempre usar parameterized queries
3. **Password Hashing:** Argon2id con costo adecuado
4. **HTTPS Everywhere:** Forzar SSL en producción
5. **CORS Configurado:** Solo dominios autorizados
6. **Rate Limiting:** Prevenir brute force attacks
7. **Audit Logging:** Registrar todas las acciones críticas

## 🚀 Optimización de Rendimiento

### Backend
- **OpCache:** Habilitar y configurar correctamente
- **Query Optimization:** Usar explain plan, índices adecuados
- **Caching:** Redis/Memcached para datos frecuentes
- **Lazy Loading:** Cargar relaciones solo cuando se necesitan

### Frontend
- **CDN:** Servir assets estáticos desde CDN
- **Minificación:** CSS y JS minificados en producción
- **Lazy Loading:** Imágenes y componentes bajo demanda
- **Browser Caching:** Headers Cache-Control adecuados

### Base de Datos
- **Indexing:** Índices en columnas de búsqueda frecuente
- **Partitioning:** Tablas grandes particionadas por fecha
- **Query Cache:** Habilitar MySQL query cache
- **Connection Pooling:** Reutilizar conexiones

## 🧪 Testing Strategy

### Niveles de Testing
1. **Unit Tests:** Pruebas de funciones y métodos aislados
2. **Integration Tests:** Pruebas de módulos completos
3. **E2E Tests:** Flujos completos de usuario
4. **Performance Tests:** Load testing con Apache Bench

### Herramientas
- **PHPUnit:** Tests unitarios y de integración
- **Codeception:** Tests de aceptación
- **Playwright:** E2E testing moderno
- **Blackfire:** Profiling de rendimiento

## 📊 Métricas de Calidad de Código

- **PSR-12:** Coding standards cumplidos
- **PHPStan Level 8:** Análisis estático sin errores
- **Test Coverage:** >80% objetivo
- **Technical Debt:** <5% del código base
- **Code Duplication:** <3%

---

*Documento vivo - Actualizar con nuevas habilidades adquiridas*
