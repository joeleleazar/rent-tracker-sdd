<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Rent Tracker — Documentación del Proyecto

Este repositorio es **Rent Tracker**, un sistema de gestión de contratos de locación, recibos
y medidores de consumo. La documentación específica del proyecto (no la del framework Laravel
de abajo) vive en:

- [`.specify/memory/constitution.md`](.specify/memory/constitution.md) — la constitución: fuente
  de verdad normativa de arquitectura, nomenclatura, pruebas, integridad de datos y sistema de
  diseño (Bootstrap 5).
- [`DESIGN.md`](DESIGN.md) — el sistema de diseño visual tal como está implementado hoy (paleta,
  tipografía, elevación, componentes), generado con el skill `impeccable`; descriptivo, no
  normativo — ver la constitución para lo vinculante.
- [`docs/referencias-diseno-bootstrap/`](docs/referencias-diseno-bootstrap/) — material de
  consulta histórico que originó el Principio VI de la constitución.
- [`specs/`](specs/) — especificaciones funcionales por feature (Spec Kit).

### Acceso y usuarios

El sistema no tiene auto-registro público. Las cuentas se administran desde la sección
**Usuarios**, visible y accesible **sólo para el perfil `master`** (`specs/040`). El `master`
puede crear cuentas, editarlas, restablecer contraseñas, cambiar perfiles y desactivar/reactivar
o eliminar cuentas; el perfil `administrador` tiene acceso operativo completo al resto del sistema
pero ninguna a esa sección. Toda cuenta nueva nace como `administrador` salvo que el `master` elija
otro perfil, y el sistema siempre conserva al menos un `master` activo.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
