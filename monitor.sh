#!/bin/bash

# SheerApps 4D - Security Monitoring Script
# Run this script regularly to monitor system security and health

set -e

# Configuration
LOG_FILE="/var/log/sheerapps-monitor.log"
ALERT_EMAIL="admin@yourdomain.com"
APP_DIR="/var/www/sheerapps4d"
DB_NAME="sheerapps_db"
DB_USER="sheerapps_user"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a $LOG_FILE
}

# Function to check if service is running
check_service() {
    local service_name=$1
    if systemctl is-active --quiet $service_name; then
        log_message "✓ $service_name is running"
        return 0
    else
        log_message "✗ $service_name is not running"
        return 1
    fi
}

# Function to check disk usage
check_disk_usage() {
    local usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ $usage -gt 80 ]; then
        log_message "⚠️  High disk usage: ${usage}%"
        return 1
    else
        log_message "✓ Disk usage: ${usage}%"
        return 0
    fi
}

# Function to check memory usage
check_memory_usage() {
    local usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [ $usage -gt 80 ]; then
        log_message "⚠️  High memory usage: ${usage}%"
        return 1
    else
        log_message "✓ Memory usage: ${usage}%"
        return 0
    fi
}

# Function to check failed login attempts
check_failed_logins() {
    local failed_count=$(grep "Invalid Telegram hash\|Invalid API token\|Rate limit exceeded" /var/log/nginx/error.log 2>/dev/null | wc -l)
    if [ $failed_count -gt 10 ]; then
        log_message "⚠️  High number of failed login attempts: $failed_count"
        return 1
    else
        log_message "✓ Failed login attempts: $failed_count"
        return 0
    fi
}

# Function to check suspicious IP addresses
check_suspicious_ips() {
    local suspicious_ips=$(grep "Invalid Telegram hash\|Invalid API token" /var/log/nginx/error.log 2>/dev/null | \
                         grep -oE "\b([0-9]{1,3}\.){3}[0-9]{1,3}\b" | sort | uniq -c | \
                         awk '$1 > 5 {print $2}' | head -5)
    
    if [ ! -z "$suspicious_ips" ]; then
        log_message "⚠️  Suspicious IP addresses detected:"
        echo "$suspicious_ips" | while read ip; do
            log_message "   - $ip (multiple failed attempts)"
        done
        return 1
    else
        log_message "✓ No suspicious IP addresses detected"
        return 0
    fi
}

# Function to check database connections
check_database() {
    if mysql -u$DB_USER -p -e "USE $DB_NAME; SELECT 1;" >/dev/null 2>&1; then
        log_message "✓ Database connection successful"
        
        # Check for recent logins
        local recent_logins=$(mysql -u$DB_USER -p -e "USE $DB_NAME; SELECT COUNT(*) FROM sheerapps_accounts WHERE last_login_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);" 2>/dev/null | tail -1)
        log_message "✓ Recent logins (last hour): $recent_logins"
        
        return 0
    else
        log_message "✗ Database connection failed"
        return 1
    fi
}

# Function to check SSL certificate
check_ssl_certificate() {
    local domain=$(grep "APP_URL" $APP_DIR/.env 2>/dev/null | cut -d'=' -f2 | sed 's/https:\/\///' | sed 's/"//g')
    
    if [ ! -z "$domain" ] && [ "$domain" != "yourdomain.com" ]; then
        local expiry_date=$(echo | openssl s_client -servername $domain -connect $domain:443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d'=' -f2)
        
        if [ ! -z "$expiry_date" ]; then
            local expiry_epoch=$(date -d "$expiry_date" +%s)
            local current_epoch=$(date +%s)
            local days_until_expiry=$(( ($expiry_epoch - $current_epoch) / 86400 ))
            
            if [ $days_until_expiry -lt 30 ]; then
                log_message "⚠️  SSL certificate expires in $days_until_expiry days"
                return 1
            else
                log_message "✓ SSL certificate expires in $days_until_expiry days"
                return 0
            fi
        else
            log_message "⚠️  Could not check SSL certificate"
            return 1
        fi
    else
        log_message "⚠️  Domain not configured in .env"
        return 1
    fi
}

# Function to check file permissions
check_file_permissions() {
    local critical_files=(
        "$APP_DIR/.env"
        "$APP_DIR/storage"
        "$APP_DIR/bootstrap/cache"
    )
    
    local has_issues=0
    
    for file in "${critical_files[@]}"; do
        if [ -e "$file" ]; then
            local perms=$(stat -c "%a" "$file")
            local owner=$(stat -c "%U" "$file")
            
            if [ "$owner" != "sheerapps" ]; then
                log_message "⚠️  $file has wrong owner: $owner (should be sheerapps)"
                has_issues=1
            fi
            
            if [ "$file" = "$APP_DIR/.env" ] && [ "$perms" != "640" ]; then
                log_message "⚠️  $file has wrong permissions: $perms (should be 640)"
                has_issues=1
            elif [ "$file" != "$APP_DIR/.env" ] && [ "$perms" != "755" ]; then
                log_message "⚠️  $file has wrong permissions: $perms (should be 755)"
                has_issues=1
            fi
        fi
    done
    
    if [ $has_issues -eq 0 ]; then
        log_message "✓ File permissions are correct"
        return 0
    else
        return 1
    fi
}

# Function to check for security updates
check_security_updates() {
    local updates_available=$(yum check-update --security 2>/dev/null | grep -c "security" || echo "0")
    
    if [ $updates_available -gt 0 ]; then
        log_message "⚠️  $updates_available security updates available"
        return 1
    else
        log_message "✓ No security updates available"
        return 0
    fi
}

# Function to check fail2ban status
check_fail2ban() {
    if command -v fail2ban-client >/dev/null 2>&1; then
        local banned_ips=$(fail2ban-client status | grep "Currently banned" | awk '{print $4}')
        log_message "✓ Fail2ban banned IPs: $banned_ips"
        
        # Check if fail2ban is actively blocking
        local recent_bans=$(tail -100 /var/log/fail2ban.log 2>/dev/null | grep "Ban" | wc -l)
        log_message "✓ Recent bans: $recent_bans"
        
        return 0
    else
        log_message "✗ Fail2ban not installed or not accessible"
        return 1
    fi
}

# Function to check application logs
check_application_logs() {
    local error_count=$(tail -1000 $APP_DIR/storage/logs/laravel.log 2>/dev/null | grep -c "ERROR\|CRITICAL" || echo "0")
    local warning_count=$(tail -1000 $APP_DIR/storage/logs/laravel.log 2>/dev/null | grep -c "WARNING" || echo "0")
    
    if [ $error_count -gt 10 ]; then
        log_message "⚠️  High number of errors in application logs: $error_count"
        return 1
    else
        log_message "✓ Application errors: $error_count"
    fi
    
    if [ $warning_count -gt 20 ]; then
        log_message "⚠️  High number of warnings in application logs: $warning_count"
        return 1
    else
        log_message "✓ Application warnings: $warning_count"
    fi
    
    return 0
}

# Function to generate security report
generate_security_report() {
    local report_file="/tmp/security-report-$(date +%Y%m%d-%H%M%S).txt"
    
    echo "=== SheerApps 4D Security Report ===" > $report_file
    echo "Generated: $(date)" >> $report_file
    echo "" >> $report_file
    
    echo "=== System Services ===" >> $report_file
    systemctl list-units --type=service --state=active | grep -E "(nginx|mysql|redis|fail2ban)" >> $report_file
    
    echo "" >> $report_file
    echo "=== Recent Security Events ===" >> $report_file
    tail -50 /var/log/fail2ban.log 2>/dev/null | grep -E "(Ban|Unban)" >> $report_file
    
    echo "" >> $report_file
    echo "=== Failed Login Attempts ===" >> $report_file
    grep "Invalid Telegram hash\|Invalid API token" /var/log/nginx/error.log 2>/dev/null | tail -20 >> $report_file
    
    echo "" >> $report_file
    echo "=== Application Errors ===" >> $report_file
    tail -20 $APP_DIR/storage/logs/laravel.log 2>/dev/null | grep -E "(ERROR|CRITICAL)" >> $report_file
    
    log_message "Security report generated: $report_file"
}

# Main monitoring function
main() {
    log_message "Starting security monitoring..."
    
    local issues_found=0
    
    # Check system services
    log_message "=== Checking System Services ==="
    check_service nginx || ((issues_found++))
    check_service mysqld || ((issues_found++))
    check_service redis || ((issues_found++))
    check_service fail2ban || ((issues_found++))
    
    # Check system resources
    log_message "=== Checking System Resources ==="
    check_disk_usage || ((issues_found++))
    check_memory_usage || ((issues_found++))
    
    # Check security status
    log_message "=== Checking Security Status ==="
    check_failed_logins || ((issues_found++))
    check_suspicious_ips || ((issues_found++))
    check_fail2ban || ((issues_found++))
    
    # Check application status
    log_message "=== Checking Application Status ==="
    check_database || ((issues_found++))
    check_ssl_certificate || ((issues_found++))
    check_file_permissions || ((issues_found++))
    check_application_logs || ((issues_found++))
    
    # Check system updates
    log_message "=== Checking System Updates ==="
    check_security_updates || ((issues_found++))
    
    # Generate security report
    generate_security_report
    
    # Summary
    if [ $issues_found -eq 0 ]; then
        log_message "🎉 All security checks passed successfully!"
    else
        log_message "⚠️  $issues_found security issues detected. Please review the log above."
        
        # Send alert email if configured
        if [ ! -z "$ALERT_EMAIL" ] && command -v mail >/dev/null 2>&1; then
            echo "Security monitoring detected $issues_found issues. Check the logs for details." | \
            mail -s "Security Alert - SheerApps 4D" $ALERT_EMAIL
        fi
    fi
    
    log_message "Security monitoring completed."
}

# Run main function
main "$@"
