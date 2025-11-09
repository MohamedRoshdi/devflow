# DevFlow Pro

**Advanced Deployment Management System** - A comprehensive solution for managing servers, projects, deployments, and infrastructure with real-time monitoring.

## 🚀 Features

- **Server Management**: Connect and monitor multiple servers with SSH integration
- **Project Deployment**: Automated deployments with Docker integration
- **Real-time Monitoring**: Live server metrics and performance analytics
- **SSL Automation**: Automatic SSL certificate management with Let's Encrypt
- **GPS Discovery**: Location-based server and project discovery
- **Storage Management**: Track and manage storage usage across projects
- **Multi-project Dashboard**: Beautiful, responsive dashboard with Livewire 3
- **PWA Support**: Mobile-ready Progressive Web App
- **Webhook Integration**: Auto-deploy on git push (GitHub, GitLab, Bitbucket)

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Redis (for caching and queues)
- Composer
- Node.js 18+ and NPM
- Docker (optional, for containerized deployments)

## 🛠️ Installation

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database and service credentials:

```env
DB_DATABASE=devflow_pro
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

PUSHER_APP_ID=your_pusher_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
```

### 4. Database setup

```bash
php artisan migrate
```

### 5. Build assets

```bash
npm run build
```

### 6. Start queue worker

```bash
php artisan queue:work
```

### 7. Run development server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## 📱 PWA Installation

DevFlow Pro can be installed as a Progressive Web App on mobile devices:

1. Open the app in your mobile browser
2. Tap the "Add to Home Screen" option
3. Enjoy native-like experience!

## 🔧 Configuration

### Scheduled Tasks

Add to your crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

The following tasks run automatically:
- Server monitoring (every minute)
- SSL certificate checking (daily)
- Metrics cleanup (daily)

### Docker Integration

Ensure Docker is installed on your servers for containerized deployments:

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
```

### SSL Configuration

Configure Let's Encrypt in your `.env`:

```env
SSL_EMAIL=admin@yourdomain.com
SSL_STAGING=false
```

## 📊 Usage

### Adding a Server

1. Navigate to **Servers** → **Add Server**
2. Fill in server details (hostname, IP, SSH credentials)
3. Optionally add GPS coordinates for location tracking
4. Test connection and save

### Creating a Project

1. Go to **Projects** → **New Project**
2. Select a server
3. Configure repository, framework, and build settings
4. Enable auto-deploy for webhook-based deployments
5. Create project

### Deploying

**Manual Deployment:**
1. Open project details
2. Click "Deploy" button
3. Monitor deployment logs in real-time

**Automatic Deployment:**
1. Enable auto-deploy in project settings
2. Configure webhook in your Git repository:
   - URL: `https://your-domain.com/api/webhooks/deploy/{project-slug}`
3. Push to repository - deployment triggers automatically

### Monitoring

- **Dashboard**: Overview of all servers, projects, and deployments
- **Analytics**: Performance metrics and deployment statistics
- **Server Details**: Real-time CPU, memory, and disk usage

## 🔐 Security

- All SSH connections use secure key-based authentication
- API endpoints protected with Laravel Sanctum
- Policy-based authorization for all resources
- SSL/TLS encryption for all communications

## 📦 Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **Database**: MySQL
- **Cache/Queue**: Redis
- **Real-time**: Pusher (WebSockets)
- **Deployment**: Docker
- **SSL**: Let's Encrypt (Certbot)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- Laravel Team for the amazing framework
- Livewire Team for reactive components
- Tailwind CSS for beautiful styling

## 📞 Support

For issues and questions:
- GitHub Issues: [Create an issue](https://github.com/yourusername/devflow-pro/issues)
- Email: support@devflowpro.com

---

**Made with ❤️ by DevFlow Pro Team**

