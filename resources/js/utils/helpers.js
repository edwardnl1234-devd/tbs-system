// Format number with thousand separator
export function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

// Format currency (IDR)
export function formatCurrency(amount) {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

// Format date
export function formatDate(date, format = 'short') {
    if (!date) return '-';
    const d = new Date(date);
    
    if (format === 'short') {
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    }
    
    if (format === 'long') {
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }
    
    if (format === 'datetime') {
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }
    
    if (format === 'time') {
        return d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }
    
    return d.toLocaleDateString('id-ID');
}

// Format weight (kg)
export function formatWeight(weight) {
    if (weight === null || weight === undefined) return '0 kg';
    return `${formatNumber(weight)} kg`;
}

// Get status color
export function getStatusColor(status) {
    const colors = {
        // Queue status
        waiting: 'yellow',
        processing: 'blue',
        completed: 'green',
        cancelled: 'red',
        
        // Weighing status
        pending: 'yellow',
        weigh_in: 'blue',
        weigh_out: 'indigo',
        
        // Stock status
        available: 'green',
        reserved: 'yellow',
        sold: 'gray',
        ready: 'green',
        
        // Sales status
        pending: 'yellow',
        delivered: 'blue',
        paid: 'green',
        
        // General
        active: 'green',
        inactive: 'gray',
        approved: 'green',
        rejected: 'red',
    };
    
    return colors[status?.toLowerCase()] || 'gray';
}

// Get status label in Indonesian
export function getStatusLabel(status) {
    const labels = {
        // Queue
        waiting: 'Menunggu',
        processing: 'Diproses',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
        
        // Weighing
        pending: 'Pending',
        weigh_in: 'Timbang Masuk',
        weigh_out: 'Timbang Keluar',
        
        // Stock
        available: 'Tersedia',
        reserved: 'Direservasi',
        sold: 'Terjual',
        ready: 'Siap',
        
        // Sales
        delivered: 'Terkirim',
        paid: 'Lunas',
        
        // General
        active: 'Aktif',
        inactive: 'Tidak Aktif',
        approved: 'Disetujui',
        rejected: 'Ditolak',
    };
    
    return labels[status?.toLowerCase()] || status;
}

// Debounce function
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Generate queue number
export function generateQueueNumber(bank, sequence) {
    return `${bank}${String(sequence).padStart(3, '0')}`;
}

// Calculate netto weight with decimal precision
export function calculateNetto(bruto, tara) {
    const brutoNum = parseFloat(bruto) || 0;
    const taraNum = parseFloat(tara) || 0;
    const netto = brutoNum - taraNum;
    // Round to 2 decimal places to avoid floating point issues
    return Math.max(0, Math.round(netto * 100) / 100);
}

// Calculate extraction rate
export function calculateExtractionRate(output, input) {
    if (!input || input === 0) return 0;
    return (output / input) * 100;
}

// Truncate text
export function truncate(text, length = 50) {
    if (!text) return '';
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}

// Capitalize first letter
export function capitalize(text) {
    if (!text) return '';
    return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
}

// Check if user has role
export function hasRole(user, roles) {
    if (!user || !user.role) return false;
    if (typeof roles === 'string') {
        return user.role === roles;
    }
    return roles.includes(user.role);
}

// Download file from blob
export function downloadFile(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
