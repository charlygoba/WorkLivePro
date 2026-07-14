# WorkLive Pro

Panel administrativo de productividad y operación remota construido completamente con Laravel. Centraliza la actividad reportada por los agentes instalados en los equipos y ofrece seguimiento de colaboradores, expediente individual, reloj checador, políticas de uso, configuración corporativa y reportes exportables.

## Qué incluye

- Dashboard operativo con actividad reciente, estados y métricas calculadas desde MySQL.
- Gestión de empleados: alta, edición, clave de vinculación, perfil y dispositivos.
- Expediente del colaborador con métricas, timeline, aplicaciones, dominios y hardware.
- Reloj checador de RH: entradas, salidas, retardos, inactividad e incidencias.
- Políticas de uso para aplicaciones, dominios y bloqueo web.
- Configuración corporativa, incluida la zona horaria usada por el panel.
- Centro de reportes por periodo, empleado, departamento o país.
- Exportación de reportes a CSV, Excel (`.xlsx`) y PDF.
- API compatible con el Tracker ya instalado, sin requerir cambios en el cliente.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL / MariaDB
- Vite para los assets del panel
- PhpSpreadsheet para archivos Excel
- Dompdf para documentos PDF

> Los archivos compilados de `public/build` se incluyen en el repositorio. Esto permite desplegar el panel en cPanel o servidores compartidos sin instalar Node.js en producción.

## Requisitos

- PHP 8.2 o superior con extensiones `mbstring`, `xml`, `zip`, `gd` y `pdo_mysql`.
- MySQL 8+ o MariaDB compatible.
- Composer 2 para instalar dependencias en local o en el servidor.
- Node.js solo si se desea modificar y recompilar los recursos de Vite.

## Instalación local

```bash
git clone <url-del-repositorio>
cd work-live-pro
composer install
copy .env.example .env
php artisan key:generate
```

Edita `.env` con los valores de tu servidor MySQL. No subas ese archivo al repositorio.

```bash
php artisan migrate --force
npm install
npm run build
```

Para iniciar el panel localmente:

```bash
php artisan serve
```

Abre `http://127.0.0.1:8000` e inicia sesión con una cuenta administradora registrada en la base de datos.

## Variables de entorno

Usa `.env.example` como referencia. Las variables que deben configurarse para cada entorno incluyen:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=replace_with_a_strong_password

WORKLIVE_COMPANY_ID=worklivepro
WORKLIVE_COMPANY_NAME="WorkLivePro"
WORKLIVE_ADMIN_API_KEY=generate_a_long_unique_admin_key
```

Genera valores únicos y seguros para las credenciales, claves y contraseñas de cada ambiente. Nunca los agregues al código, issues, capturas o historial de Git.

## Despliegue en cPanel

1. Sube el contenido de este proyecto al directorio de la aplicación.
2. Apunta el *document root* del dominio hacia `public/`.
3. Crea `.env` en el servidor basándote en `.env.example` y completa únicamente valores propios del ambiente.
4. Instala dependencias de Composer si el hosting lo permite:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   ```

5. Da permisos de escritura al usuario web en `storage/` y `bootstrap/cache/`.
6. Los assets ya existen en `public/build`; no hace falta ejecutar Node.js ni Vite en el hosting para servirlos.

Consulta [DEPLOYMENT_CPANEL.md](DEPLOYMENT_CPANEL.md) para una guía más detallada.

## API del agente

Las rutas públicas para el Tracker se encuentran bajo `/api/agent`:

- `POST /api/agent/activate`
- `POST /api/agent/event`
- `POST /api/agent/events`
- `GET /api/agent/policy`
- `GET /api/agent/policy-version`

La API valida la vinculación mediante la clave generada para cada empleado y tokens de agente. Los eventos se almacenan en UTC y el panel los presenta usando la zona horaria corporativa configurada.

## Calidad y verificación

```bash
php artisan view:cache
php artisan route:list
npm run build
```

## Seguridad del repositorio

Se excluyen del control de versiones `.env`, `vendor/`, `node_modules/`, logs, caches, archivos de autenticación y datos locales. En cambio, `public/build` se conserva intencionalmente para que el despliegue funcione sin una compilación adicional.

## Licencia

Proyecto privado. Define la licencia antes de distribuirlo públicamente.
