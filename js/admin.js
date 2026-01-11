// Funcionalidad del panel administrativo ONT
document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
        }
    });

    // File upload drag and drop functionality
    const fileUploads = document.querySelectorAll('.file-upload');
    
    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        
        upload.addEventListener('dragover', function(e) {
            e.preventDefault();
            upload.classList.add('dragover');
        });
        
        upload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            upload.classList.remove('dragover');
        });
        
        upload.addEventListener('drop', function(e) {
            e.preventDefault();
            upload.classList.remove('dragover');
            
            if (input) {
                input.files = e.dataTransfer.files;
                handleFileSelect(input);
            }
        });
        
        upload.addEventListener('click', function() {
            if (input) {
                input.click();
            }
        });
        
        if (input) {
            input.addEventListener('change', function() {
                handleFileSelect(this);
            });
        }
    });

    // Handle file selection
    function handleFileSelect(input) {
        const fileInfo = input.closest('.file-upload').querySelector('.file-info');
        if (input.files.length > 0) {
            const file = input.files[0];
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            if (fileInfo) {
                fileInfo.innerHTML = `
                    <div class="selected-file">
                        <i class="bi bi-file-earmark-check text-success me-2"></i>
                        <span>${fileName}</span>
                        <small class="text-muted ms-2">(${fileSize} MB)</small>
                    </div>
                `;
            }
        }
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert-ont');
    alerts.forEach(alert => {
        if (alert.classList.contains('auto-hide')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }
    });

    // Data tables functionality
    const tables = document.querySelectorAll('.data-table table');
    tables.forEach(table => {
        // Add hover effects and click handlers
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                if (!e.target.closest('button') && !e.target.closest('a')) {
                    // Handle row click if needed
                }
            });
        });
    });

    // Search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value, this.dataset.target);
            }, 300);
        });
    });

    function performSearch(query, target) {
        const targetElement = document.querySelector(target);
        if (!targetElement) return;

        const items = targetElement.querySelectorAll('.searchable-item');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(query.toLowerCase()) || query === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Notification system
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert-ont ${type} auto-hide`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        
        const icon = getNotificationIcon(type);
        notification.innerHTML = `
            <i class="bi ${icon}"></i>
            <div>
                <strong>${getNotificationTitle(type)}</strong>
                <p class="mb-0">${message}</p>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);
    };

    function getNotificationIcon(type) {
        const icons = {
            success: 'bi-check-circle',
            error: 'bi-exclamation-triangle',
            warning: 'bi-exclamation-circle',
            info: 'bi-info-circle'
        };
        return icons[type] || icons.info;
    }

    function getNotificationTitle(type) {
        const titles = {
            success: 'Éxito',
            error: 'Error',
            warning: 'Advertencia',
            info: 'Información'
        };
        return titles[type] || titles.info;
    }

    // Chart functionality (if needed)
    window.initChart = function(elementId, data, options = {}) {
        // This would integrate with Chart.js or similar library
        console.log('Chart initialized for', elementId, data, options);
    };

    // Export functionality
    window.exportData = function(format, data) {
        if (format === 'csv') {
            exportToCSV(data);
        } else if (format === 'excel') {
            exportToExcel(data);
        }
    };

    function exportToCSV(data) {
        const csv = convertToCSV(data);
        downloadFile(csv, 'export.csv', 'text/csv');
    }

    function convertToCSV(data) {
        if (!data || data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
        ].join('\n');
        
        return csvContent;
    }

    function downloadFile(content, filename, contentType) {
        const blob = new Blob([content], { type: contentType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Real-time updates (WebSocket simulation)
    function simulateRealTimeUpdates() {
        setInterval(() => {
            // Simulate receiving updates
            const updates = Math.floor(Math.random() * 5);
            if (updates > 0) {
                updateNotificationBadge(updates);
            }
        }, 30000); // Check every 30 seconds
    }

    function updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // Initialize real-time updates
    simulateRealTimeUpdates();

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + N for new item
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const newButton = document.querySelector('[href*="action=new"]');
            if (newButton) {
                window.location.href = newButton.href;
            }
        }
    });

    console.log('ONT Admin Panel - Scripts loaded successfully');
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .btn-close {
        background: none;
        border: none;
        color: inherit;
        opacity: 0.7;
        cursor: pointer;
        padding: 0.25rem;
        margin-left: auto;
    }
    
    .btn-close:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);
