# SSH Server Access Guide

Complete guide for accessing the DevFlow Pro production server via SSH.

---

## 🔐 Server Connection Details

### Production Server
- **IP Address:** `31.220.90.121`
- **Hostname:** `nilestack.duckdns.org`
- **SSH Port:** `22` (default)
- **SSH User:** `root`
- **Authentication:** SSH Key (password auth also available)

---

## 🚀 Quick Access

### Basic SSH Connection
```bash
# Using IP address
ssh root@31.220.90.121

# Using hostname
ssh root@nilestack.duckdns.org
```

### SSH with Custom Port
```bash
# If using non-standard port
ssh -p 22 root@31.220.90.121
```

---

## 🔑 SSH Key Setup

### 1. Generate SSH Key (if you don't have one)
```bash
# Generate new SSH key pair
ssh-keygen -t ed25519 -C "your_email@example.com"

# For older systems, use RSA
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# Default location: ~/.ssh/id_ed25519 or ~/.ssh/id_rsa
```

### 2. Copy Public Key to Server
```bash
# Option 1: Using ssh-copy-id (recommended)
ssh-copy-id root@31.220.90.121

# Option 2: Manual copy
cat ~/.ssh/id_ed25519.pub | ssh root@31.220.90.121 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"

# Option 3: Copy and paste manually
cat ~/.ssh/id_ed25519.pub
# Then paste into server: /root/.ssh/authorized_keys
```

### 3. Set Correct Permissions on Server
```bash
ssh root@31.220.90.121
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

---

## ⚙️ SSH Config Setup (Recommended)

Create or edit `~/.ssh/config` on your local machine:

```bash
# DevFlow Pro Production Server
Host devflow
    HostName 31.220.90.121
    User root
    Port 22
    IdentityFile ~/.ssh/id_ed25519
    ServerAliveInterval 60
    ServerAliveCountMax 3

# Using hostname instead of IP
Host devflow-dns
    HostName nilestack.duckdns.org
    User root
    Port 22
    IdentityFile ~/.ssh/id_ed25519
```

Now you can connect simply with:
```bash
ssh devflow
# or
ssh devflow-dns
```

---

## 🛠️ Common SSH Tasks

### File Transfer with SCP

#### Upload file to server
```bash
# Upload single file
scp /local/path/file.txt root@31.220.90.121:/var/www/devflow-pro/

# Upload directory recursively
scp -r /local/directory root@31.220.90.121:/var/www/devflow-pro/
```

#### Download file from server
```bash
# Download single file
scp root@31.220.90.121:/var/www/devflow-pro/file.txt /local/path/

# Download directory
scp -r root@31.220.90.121:/var/www/devflow-pro/logs /local/path/
```

### File Transfer with SFTP
```bash
# Connect via SFTP
sftp root@31.220.90.121

# SFTP commands
sftp> pwd                    # Print working directory
sftp> ls                     # List files
sftp> cd /var/www/devflow-pro
sftp> get file.txt           # Download file
sftp> put local.txt          # Upload file
sftp> exit                   # Exit SFTP
```

### Rsync for Efficient Transfers
```bash
# Sync local to remote
rsync -avz /local/path/ root@31.220.90.121:/var/www/devflow-pro/

# Sync remote to local
rsync -avz root@31.220.90.121:/var/www/devflow-pro/ /local/backup/

# With progress and compression
rsync -avz --progress /local/path/ root@31.220.90.121:/remote/path/
```

---

## 🔧 Server Management Commands

### Check Server Status
```bash
# System information
ssh root@31.220.90.121 "uname -a"

# Disk usage
ssh root@31.220.90.121 "df -h"

# Memory usage
ssh root@31.220.90.121 "free -h"

# Running processes
ssh root@31.220.90.121 "top -bn1 | head -20"
```

### Application Management
```bash
# Navigate to application directory
ssh root@31.220.90.121
cd /var/www/devflow-pro

# Check application status
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql

# View Laravel logs
tail -f /var/www/devflow-pro/storage/logs/laravel.log

# View Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

### Database Access
```bash
# Connect to MySQL
ssh root@31.220.90.121 "mysql -u devflow_user -p"

# Export database
ssh root@31.220.90.121 "mysqldump -u devflow_user -p devflow_pro > backup.sql"

# Import database
scp backup.sql root@31.220.90.121:/tmp/
ssh root@31.220.90.121 "mysql -u devflow_user -p devflow_pro < /tmp/backup.sql"
```

### Docker Management
```bash
# List Docker containers
ssh root@31.220.90.121 "docker ps -a"

# View container logs
ssh root@31.220.90.121 "docker logs container_name"

# Execute command in container
ssh root@31.220.90.121 "docker exec -it container_name bash"

# Docker compose commands
ssh root@31.220.90.121 "cd /path/to/project && docker compose ps"
```

---

## 🔒 Security Best Practices

### 1. Disable Password Authentication (Recommended)
```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Set these values
PasswordAuthentication no
PubkeyAuthentication yes
PermitRootLogin prohibit-password

# Restart SSH service
sudo systemctl restart sshd
```

### 2. Change Default SSH Port
```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Change port (e.g., to 2299)
Port 2299

# Restart SSH
sudo systemctl restart sshd

# Connect with new port
ssh -p 2299 root@31.220.90.121
```

### 3. Use Fail2Ban
```bash
# Install fail2ban
sudo apt-get install fail2ban

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local

# Enable and start
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 4. Enable Firewall
```bash
# Install UFW
sudo apt-get install ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
sudo ufw status
```

---

## 🐛 Troubleshooting

### Connection Refused
```bash
# Check if SSH service is running
ssh root@31.220.90.121 "systemctl status sshd"

# Check firewall rules
ssh root@31.220.90.121 "sudo ufw status"

# Test connection
ping 31.220.90.121
telnet 31.220.90.121 22
```

### Permission Denied (publickey)
```bash
# Verify your public key is on the server
ssh root@31.220.90.121 "cat ~/.ssh/authorized_keys"

# Check local SSH key
ls -la ~/.ssh/

# Test with verbose output
ssh -v root@31.220.90.121

# Check file permissions on server
ssh root@31.220.90.121 "ls -la ~/.ssh/"
```

### Host Key Verification Failed
```bash
# Remove old host key
ssh-keygen -R 31.220.90.121

# Or clear known_hosts
> ~/.ssh/known_hosts

# Connect again (will prompt to add new key)
ssh root@31.220.90.121
```

### Connection Timeout
```bash
# Check if server is reachable
ping 31.220.90.121

# Try with verbose mode
ssh -vvv root@31.220.90.121

# Check DNS resolution
nslookup nilestack.duckdns.org

# Try direct IP
ssh root@31.220.90.121
```

---

## 📝 SSH Aliases and Shortcuts

### Bash Aliases
Add to `~/.bashrc` or `~/.zshrc`:

```bash
# SSH aliases
alias ssh-devflow='ssh root@31.220.90.121'
alias ssh-devflow-logs='ssh root@31.220.90.121 "tail -f /var/www/devflow-pro/storage/logs/laravel.log"'
alias ssh-devflow-nginx='ssh root@31.220.90.121 "tail -f /var/log/nginx/error.log"'

# SCP shortcuts
alias scp-to-devflow='scp -r . root@31.220.90.121:/var/www/devflow-pro/'
alias scp-from-devflow='scp -r root@31.220.90.121:/var/www/devflow-pro/ .'

# Quick commands
alias devflow-status='ssh root@31.220.90.121 "systemctl status nginx php8.2-fpm mysql"'
alias devflow-restart='ssh root@31.220.90.121 "systemctl restart nginx php8.2-fpm"'
```

---

## 🚀 Advanced SSH Techniques

### SSH Tunneling (Port Forwarding)
```bash
# Local port forwarding (access remote service locally)
ssh -L 3306:localhost:3306 root@31.220.90.121
# Now connect to localhost:3306 to access server's MySQL

# Remote port forwarding (expose local service to remote)
ssh -R 8080:localhost:8080 root@31.220.90.121

# Dynamic port forwarding (SOCKS proxy)
ssh -D 8080 root@31.220.90.121
```

### SSH Jump Host (ProxyJump)
```bash
# Connect through a jump host
ssh -J jumphost.example.com root@31.220.90.121

# In ~/.ssh/config
Host devflow
    HostName 31.220.90.121
    User root
    ProxyJump jumphost.example.com
```

### SSH Agent Forwarding
```bash
# Enable agent forwarding
ssh -A root@31.220.90.121

# In ~/.ssh/config
Host devflow
    HostName 31.220.90.121
    User root
    ForwardAgent yes
```

### Persistent Sessions with Screen/Tmux
```bash
# Start screen session
ssh root@31.220.90.121
screen -S devflow-session

# Detach: Ctrl+A then D
# Reattach later
screen -r devflow-session

# Using tmux
tmux new -s devflow
# Detach: Ctrl+B then D
tmux attach -t devflow
```

---

## 📊 Monitoring and Logging

### Real-time Log Monitoring
```bash
# Follow Laravel logs
ssh root@31.220.90.121 "tail -f /var/www/devflow-pro/storage/logs/laravel.log"

# Follow Nginx access log
ssh root@31.220.90.121 "tail -f /var/log/nginx/access.log"

# Follow Nginx error log
ssh root@31.220.90.121 "tail -f /var/log/nginx/error.log"

# Follow multiple logs
ssh root@31.220.90.121 "tail -f /var/www/devflow-pro/storage/logs/*.log"
```

### System Monitoring
```bash
# CPU and memory usage
ssh root@31.220.90.121 "htop"

# Disk I/O
ssh root@31.220.90.121 "iotop"

# Network connections
ssh root@31.220.90.121 "netstat -tulpn"

# Process tree
ssh root@31.220.90.121 "pstree -p"
```

---

## 🔐 Emergency Access

### If SSH is not working:

1. **Console Access via Hosting Provider**
   - Log in to Contabo control panel
   - Use VNC/Console access
   - Fix SSH configuration

2. **Recovery Mode**
   - Boot into recovery mode
   - Mount filesystem
   - Fix SSH config
   - Reset root password if needed

3. **Alternative Access Methods**
   - Use hosting provider's web console
   - Schedule server reboot if needed
   - Contact hosting support

---

## 📖 Quick Reference

### Essential Commands
```bash
# Connect
ssh root@31.220.90.121

# Copy file to server
scp file.txt root@31.220.90.121:/path/

# Copy file from server
scp root@31.220.90.121:/path/file.txt .

# Execute remote command
ssh root@31.220.90.121 "command"

# Interactive file transfer
sftp root@31.220.90.121

# Sync directories
rsync -avz local/ root@31.220.90.121:remote/
```

### Important Paths
- Application: `/var/www/devflow-pro`
- Logs: `/var/www/devflow-pro/storage/logs`
- Nginx Config: `/etc/nginx/sites-available/`
- PHP-FPM Config: `/etc/php/8.2/fpm/`
- MySQL Data: `/var/lib/mysql/`

---

## 🆘 Support

If you encounter issues:
1. Check this guide's troubleshooting section
2. Review system logs: `journalctl -xe`
3. Check application logs in `/var/www/devflow-pro/storage/logs/`
4. Contact system administrator
5. Open issue on GitHub repository

---

**Last Updated:** November 27, 2025
**Server IP:** 31.220.90.121
**Hostname:** nilestack.duckdns.org
