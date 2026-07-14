# Despliegue en cPanel sin Node.js

## 1. Crear la aplicación

1. Crea una base MySQL y un usuario desde **MySQL Databases**.
2. Sube el contenido de `work-live-pro` a una carpeta fuera del `public_html` si es posible.
3. Configura el subdominio para que su Document Root sea `work-live-pro/public`.
4. Selecciona PHP 8.2 o superior y activa `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json` y `fileinfo`.

No hay ningún paso de Node.js: el dashboard se sirve como Blade y CSS estático desde Laravel.

## 2. Variables privadas

Copia `.env.example` a `.env` y cambia como mínimo:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-subdominio.example.com
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=replace_with_a_strong_password
WORKLIVE_ADMIN_API_KEY=generate_a_long_unique_admin_key
```

No publiques `.env` ni incluyas sus valores en capturas o repositorios.

La migración no crea una base paralela: conserva las tablas WorkLive existentes y agrega únicamente `agent_tokens`.

## 3. Comandos de instalación

Desde Terminal cPanel:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si no hay Terminal, ejecuta esos comandos desde el selector de **PHP Composer** de cPanel o pide al proveedor que los ejecute en la carpeta raíz del proyecto.

## 4. Conexión de la App Cliente

Configura la URL de la API con el mismo subdominio, por ejemplo `https://tu-subdominio.example.com`. No cambies el binario: las rutas compatibles son `/api/agent/activate`, `/api/agent/event` y `/api/agent/events`.

## 5. Comprobación

```bash
curl https://tu-subdominio.example.com/api/health
```

La respuesta debe incluir `"ok":true`. Una petición administrativa sin `X-WorkLive-Admin-Key` debe responder `401`.
