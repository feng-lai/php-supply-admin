[English](README.md)  [日本語](README-jp.md)[Español](README-es.md) 
[العربية](README-ar.md)  [Português](README-pt.md)
#### PHP Supply & Demand Admin Platform

**PHP Supply & Demand Admin Platform** es una plataforma de gestión de oferta y demanda ligera construida con PHP. Proporciona un sistema de administración backend para gestionar información de oferta y demanda de productos, envíos de usuarios y operaciones básicas de negocio. Diseñada para pequeñas y medianas empresas, esta plataforma ayuda a simplificar la coincidencia de recursos de oferta y demanda.

##### 🌟 Características

- 🔐 **Panel de Administración**
  Interfaz de backend simple y limpia para gestionar todas las operaciones de la plataforma.

- 📦 **Listados de Oferta y Demanda**
  Agregar, editar y eliminar entradas de oferta/demanda con categorización y etiquetas opcionales.

- 📝 **Envíos de Usuarios**
  Soporte para contenido enviado por usuarios (formularios de oferta o demanda), pendientes de aprobación por parte de los administradores.

- 🔍 **Búsqueda y Filtrado**
  Búsqueda básica por palabras clave y filtrado por categorías para mejorar la accesibilidad de los datos.

- 📊 **Resumen de Estadísticas**
  Resumen del total de oferta, demanda, datos de usuarios y tendencias de publicación.

- 🧩 **Arquitectura Modular**
  Fácilmente extensible con módulos adicionales o integraciones.

##### 🛠️ Pila Tecnológica

- **Backend:** PHP (Framework ThinkPHP)
- **Frontend:** HTML + CSS + JavaScript (plantillas de UI de administración)
- **Base de Datos:** MySQL
- **Otros:** jQuery, Bootstrap (uso de UI legado)

##### 🚀 Inicio Rápido

###### Requisitos Previos

- PHP >= 7.1
- MySQL >= 5.6
- Apache / Nginx
- Composer (opcional, si deseas gestionar paquetes)

###### Instalación

1. Clona el proyecto:

```bash
git clone https://github.com/feng-lai/php-supply-admin.git
cd php-supply-admin
```

2. Importa el esquema SQL a tu base de datos MySQL (por ejemplo, `supply_admin.sql`).

3. Configura tu base de datos en `/application/database.php` o `/config/database.php`.

```php
'hostname' => '127.0.0.1',
'database' => 'your_db_name',
'username' => 'your_db_user',
'password' => 'your_db_pass',
```

4. Despliega el proyecto en tu servidor web (Apache/Nginx) apuntando al directorio `/public` como raíz.

5. Accede al panel de administración a través de:

```
http://yourdomain.com/admin
```

Credenciales predeterminadas (si están disponibles en la semilla de la base de datos):
**Usuario:** admin
**Contraseña:** admin123 *(Por favor, cámbiala después del primer inicio de sesión)*

##### 📁 Estructura del Proyecto

```
php-supply-admin/
├── application/     # Lógica de aplicación principal (controllers, models, views)
├── addons/          # Directorio raíz de la web
├── extend/          # Archivos de configuración
├── public/          # Recursos estáticos (CSS, JS, imágenes)
└── vendor/          # (opcional) Esquemas de base de datos o archivos de semilla
```

##### 📌 Notas

* Este proyecto es adecuado para implementaciones internas o de nivel SME.
* Para mayor seguridad, habilita SSL y agrega validación de entrada si se usa en producción.
* El código heredado puede requerir actualizaciones para nuevas versiones de PHP o frameworks.

##### 📄 Licencia

Este proyecto es de código abierto para aprendizaje y personalización. Consulta el repositorio o contacta al autor para obtener los términos de licencia.

##### 🙋 Autor

Mantenido por [feng-lai](https://github.com/feng-lai)
