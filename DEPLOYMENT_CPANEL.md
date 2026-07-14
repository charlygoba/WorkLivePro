# Despliegue en cPanel sin Node.js ni Passenger

`work-live-pro` es una aplicación Laravel independiente. No se configura como
una aplicación Node.js ni como una aplicación Passenger: el subdominio debe
ser atendido por Apache/PHP-FPM y apuntar directamente a `public/`.

## 1. Dominio y servidor

1. Crea una base MySQL y un usuario desde **MySQL Databases**.
2. Sube el proyecto a una carpeta fuera de `public_html` si es posible, por
   ejemplo `/home/USUARIO/work-live-pro`.
3. Configura el subdominio para que su **Document Root** sea exactamente:

   ```text
   /home/USUARIO/work-live-pro/public
   ```

4. No apuntes el dominio a la raíz del proyecto: eso expone `app`, `config`,
   `routes` y `composer.json`.
5. Si aparece una página de Phusion Passenger, elimina o desactiva la
   aplicación Node/Passenger asociada a este subdominio. Laravel se ejecuta
   con PHP-FPM/Apache.
6. Selecciona PHP 8.2 o superior y activa `pdo_mysql`, `mbstring`, `openssl`,
   `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `dom` y `zip`.

## 2. Variables privadas

Copia `.env.example` como `.env` y cambia como mínimo:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-subdominio.example.com
APP_KEY=base64:GENERADA_EN_EL_SERVIDOR
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=replace_with_a_strong_password
WORKLIVE_ADMIN_API_KEY=generate_a_long_unique_admin_key
```

No publiques `.env` ni incluyas sus valores en capturas o repositorios.

## 3. Instalación

Desde Terminal cPanel, en la raíz de `work-live-pro`:

```bash
composer install --no-dev --optimize-autoloader
# Ejecuta este comando solo si APP_KEY está vacío:
# php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Si el hosting no ofrece Composer, ejecuta `composer install --no-dev
--optimize-autoloader` en un entorno con la misma versión mayor de PHP y sube
la carpeta `vendor` completa junto con el proyecto. `vendor` se mantiene fuera
de Git porque es una dependencia generada, no un archivo fuente de Laravel.

## 4. Permisos

El usuario de PHP debe poder escribir en `storage/` y `bootstrap/cache/`. En
cPanel normalmente basta con permisos 755 y que el propietario sea el usuario
de la cuenta. Evita 777.

## 5. Conexión de la App Cliente

Configura la URL de la API con el mismo dominio. Las rutas compatibles son:
`/api/agent/activate`, `/api/agent/event` y `/api/agent/events`.

## 6. Comprobación

```bash
curl https://tu-subdominio.example.com/up
curl https://tu-subdominio.example.com/api/health
```

La primera respuesta debe ser HTTP 200 y la segunda debe incluir `"ok":true`.
