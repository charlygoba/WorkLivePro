# WorkLivePro Laravel

Aplicación Laravel completa para reemplazar el dashboard React/API Node.js sin modificar la App Cliente.

Laravel usa las tablas existentes de la misma base MySQL (`companies`, `employees`, `devices`, `activity_events`, `daily_summaries`, `company_settings`, `authorized_admins` y políticas). La única migración nueva es `agent_tokens`, para guardar tokens de agentes.

## Contrato compatible

- `POST /api/agent/activate` conserva `clientKey` y `device`.
- `POST /api/agent/event` conserva el evento individual.
- `POST /api/agent/events` conserva lotes de hasta 500 eventos.
- La activación entrega `apiToken`; la App Cliente actual ya lo envía como `Authorization: Bearer`.

## Seguridad incluida

- Validación estricta de payloads y límites de tamaño.
- Rate limiting en activación, lectura y escritura.
- Tokens de agentes almacenados únicamente como SHA-256.
- Un token solo puede registrar eventos para su empleado.
- Las rutas administrativas y de lectura exigen `X-WorkLive-Admin-Key` configurada en `.env`.
- `APP_DEBUG=false` y credenciales únicamente en `.env`.
- MySQL compatible con cPanel; no requiere Node.js en producción.

## Instalación local

```powershell
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan route:list --path=api
php artisan serve
```

El dashboard Laravel está disponible en `/login`, `/dashboard`, `/employees`, `/reports`, `/policies`, `/hr/time-clock` y `/settings`.

El dashboard se sirve completamente con Blade/Laravel desde `/`. El CSS visual compilado del proyecto anterior se conserva en `public/css/worklive-original.css`. No se requiere Node.js para ejecutar ni desplegar el dashboard.

Para probar rutas administrativas localmente agrega `X-WorkLive-Admin-Key` con el valor de `WORKLIVE_ADMIN_API_KEY`.

## cPanel

Apunta el Document Root del subdominio a `work-live-pro/public`, configura PHP 8.2+ y crea el MySQL desde cPanel. Ejecuta `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` y `php artisan config:cache`.

No subas `.env`, `storage/logs` ni `vendor` generado en otro entorno si la versión de PHP no coincide.
