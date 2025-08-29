<img src="https://avatars.githubusercontent.com/u/56885001?s=200&v=4" alt="logo" width="130" height="130" align="right"/>

# **V2Board - Laravel 11 Edition**

A modern, high-performance VPN management panel built with Laravel 11 and PHP 8.2. This is an upgraded and fully English-translated version of the popular v2board VPN management system.

## ✨ Features

- **Modern Laravel 11** framework with PHP 8.2 support
- **Multi-protocol support**: V2Ray, Shadowsocks, Trojan, TUIC, Hysteria, VLESS, VMess
- **Advanced client support**: Clash, ClashMeta, V2rayN, Surge, Shadowrocket, and more
- **Complete admin panel** with user management, server management, and analytics
- **Payment integration**: Stripe, Alipay, WeChat Pay, and multiple cryptocurrency options
- **Queue system** with Laravel Horizon for background job processing
- **Multi-language support** (English translation completed)
- **API-driven architecture** with V1/V2 API versioning
- **Telegram integration** for notifications and bot support

## 🚀 Requirements

- **PHP 8.2+** (upgraded from PHP 7.3)
- **Composer 2.x**
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Redis** (for caching and queue management)
- **Laravel 11.x** (automatically managed)

## 📦 Installation

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/fakerrorx/v2board.git
   cd v2board
   ```

2. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up database** (configure your `.env` file first)
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Install v2board**
   ```bash
   php artisan v2board:install
   ```

### Production Deployment

1. **Configure cache and queue drivers**
   ```bash
   # Set Redis as cache and queue driver in .env
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   ```

2. **Optimize for production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Start queue worker**
   ```bash
   php artisan horizon
   ```

## 🔧 Migration from Original Version

If you're migrating from the original Chinese v2board:

1. **Update repository**
   ```bash
   git remote set-url origin https://github.com/fakerrorx/v2board  
   git checkout master  
   ./update.sh  
   ```

2. **Configure Redis cache**
   ```bash
   sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
   php artisan config:clear
   php artisan config:cache
   php artisan horizon:terminate
   ```

3. **Refresh theme settings**
   - Enter backend → Theme Configuration
   - Select default theme → Theme Settings  
   - Confirm and Save

## 🆕 Laravel 11 Upgrade Features

This version includes major improvements:

- **Laravel 11.45.2** - Latest stable framework
- **PHP 8.2 compatibility** - Modern PHP features and performance
- **Improved middleware system** - Streamlined request handling
- **Enhanced routing** - Preserved custom V1/V2 API structure
- **Modern dependency management** - Updated all packages to latest versions
- **Better error handling** - Improved debugging and logging
- **Enhanced security** - Latest security features and patches

## 🛠️ Development

### Available Commands

**V2Board specific commands:**
```bash
php artisan v2board:install      # Initial installation
php artisan v2board:update       # Update system
php artisan v2board:statistics   # Generate statistics

php artisan check:server         # Server health check
php artisan check:order          # Order processing check
php artisan check:commission     # Commission calculation
php artisan check:renewal        # Auto renewal check
php artisan check:ticket         # Ticket system check

php artisan reset:traffic        # Reset user traffic
php artisan reset:password       # Reset user password
php artisan traffic:update       # Update traffic statistics
```

**Queue management:**
```bash
php artisan horizon              # Start queue worker
php artisan horizon:status       # Check queue status
php artisan horizon:terminate    # Stop queue worker
```

### API Endpoints

The system provides comprehensive APIs:

- **V1 API**: `/api/v1/` - Stable production API
- **V2 API**: `/api/v2/` - Enhanced features API

Main endpoint categories:
- `guest/*` - Public endpoints
- `user/*` - User management  
- `admin/*` - Administrative functions
- `client/*` - Client application integration
- `server/*` - Server management

## 🔗 Links

- **Demo**: [https://demo.v2board.com](https://demo.v2board.com)
- **Documentation**: [https://v2board.com](https://v2board.com)
- **Original Repository**: [https://github.com/v2board/v2board](https://github.com/v2board/v2board)

## 🐛 Support & Issues

For bug reports and feature requests, please use the GitHub issue tracker. Follow the issue template to ensure your question is addressed promptly.

## 📄 License

This project is licensed under the MIT License. See the LICENSE file for details.

## 🎯 Status

- ✅ **Laravel 11 Upgrade**: Complete
- ✅ **English Translation**: Complete  
- ✅ **PHP 8.2 Compatibility**: Complete
- ✅ **All Features Working**: Verified
- ✅ **Production Ready**: Yes

---

**Note**: This is a modernized and English-translated version of v2board, upgraded to Laravel 11 with PHP 8.2 support while maintaining full backward compatibility with existing installations and features.
