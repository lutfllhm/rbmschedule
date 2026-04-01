
// Navbar toggle functionality
function toggleNavbar() {
    const navbar = document.getElementById('mainNavbar');
    const toggleIcon = document.getElementById('navToggleIcon');
    
    if (navbar) {
        navbar.classList.toggle('navbar-collapsed');
        
        // Update icon
        if (toggleIcon) {
            if (navbar.classList.contains('navbar-collapsed')) {
                toggleIcon.className = 'fas fa-chevron-down';
            } else {
                toggleIcon.className = 'fas fa-chevron-up';
            }
        }
        
        // Save state to localStorage
        localStorage.setItem('navbarCollapsed', navbar.classList.contains('navbar-collapsed'));
        
        // Update main content margin
        updateMainContentMargin();
    }
}

// Ensure every FormData submission carries the CSRF token
function appendCsrfToken(formData) {
    if (window.csrfToken && !formData.has('csrf_token')) {
        formData.append('csrf_token', window.csrfToken);
    }
}

function buildScheduleQueryParams(includeFinish = false) {
    const params = new URLSearchParams();
    const urlParams = new URLSearchParams(window.location.search);
    const keys = ['status', 'q', 'per_page', 'page', 'date_from', 'date_to'];

    keys.forEach(key => {
        const value = urlParams.get(key);
        if (value) {
            params.set(key, value);
        }
    });

    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        const formData = new FormData(filterForm);
        keys.forEach(key => {
            const value = formData.get(key);
            if (!params.has(key) && value) {
                params.set(key, value);
            }
        });
    }

    if (!params.has('status') && !includeFinish) {
        params.set('status', 'active');
    }
    if (!params.has('per_page')) {
        params.set('per_page', includeFinish ? '100' : '20');
    }
    if (!params.has('page')) {
        params.set('page', urlParams.get('page') || '1');
    }
    if (includeFinish) {
        params.set('include_finish', '1');
    }

    return params.toString();
}

function updateMainContentMargin() {
    const navbar = document.getElementById('mainNavbar');
    const mainContent = document.querySelector('.main-content');
    
    if (navbar && mainContent) {
        if (navbar.classList.contains('navbar-collapsed')) {
            mainContent.style.marginTop = '50px';
        } else {
            mainContent.style.marginTop = '70px';
        }
    }
}

// Restore navbar state from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('mainNavbar');
    const toggleIcon = document.getElementById('navToggleIcon');
    const savedState = localStorage.getItem('navbarCollapsed');
    
    if (navbar && savedState === 'true') {
        navbar.classList.add('navbar-collapsed');
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-chevron-down';
        }
        updateMainContentMargin();
    }
});

// Auto-hide navbar on scroll
let lastScrollTop = 0;
let scrollTimeout = null;
const navbar = document.querySelector('.navbar');

if (navbar) {
    let isScrolling = false;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Clear existing timeout
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        
        // Show navbar when scrolling up or at top
        if (scrollTop < lastScrollTop || scrollTop < 10) {
            navbar.classList.remove('navbar-hidden');
        } 
        // Hide navbar when scrolling down (after 100px)
        else if (scrollTop > lastScrollTop && scrollTop > 100) {
            navbar.classList.add('navbar-hidden');
        }
        
        lastScrollTop = scrollTop;
        
        // Auto-show navbar after 3 seconds of no scrolling
        scrollTimeout = setTimeout(function() {
            navbar.classList.remove('navbar-hidden');
        }, 3000);
    });
    
    // Show navbar on mouse move near top
    let mouseMoveTimeout = null;
    document.addEventListener('mousemove', function(e) {
        if (e.clientY < 100) {
            navbar.classList.remove('navbar-hidden');
            
            if (mouseMoveTimeout) {
                clearTimeout(mouseMoveTimeout);
            }
            
            mouseMoveTimeout = setTimeout(function() {
                if (window.pageYOffset > 100) {
                    navbar.classList.add('navbar-hidden');
                }
            }, 2000);
        }
    });
}

// Search functionality for both table and airport board
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const scheduleTable = document.getElementById('scheduleTable');
    const airportBoard = document.getElementById('airportBoard');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Search in traditional table
            if (scheduleTable) {
                const rows = scheduleTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const text = row.textContent.toLowerCase();
                    
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
            
            // Search in airport board
            if (airportBoard) {
                const boardRows = airportBoard.getElementsByClassName('board-row');
                for (let i = 0; i < boardRows.length; i++) {
                    const row = boardRows[i];
                    const text = row.textContent.toLowerCase();
                    
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        });
    }
});

// Manage page functions
function openAddModal() {
    const modal = document.getElementById('scheduleModal');
    const form = document.getElementById('scheduleForm');
    const modalTitle = document.getElementById('modalTitle');
    const editModeFields = document.getElementById('editModeFields');
    
    form.reset();
    document.getElementById('schedule_id').value = '';
    modalTitle.innerHTML = '<i class="fas fa-plus"></i> Tambah Schedule Baru';
    
    // Sembunyikan field edit mode saat tambah
    if (editModeFields) {
        editModeFields.style.display = 'none';
    }
    
    // Set status default ke Not Started (hidden field)
    document.getElementById('status_hidden').value = 'Not Started';
    
    modal.classList.add('active');
}

function openEditModal(schedule) {
    const modal = document.getElementById('scheduleModal');
    const form = document.getElementById('scheduleForm');
    const modalTitle = document.getElementById('modalTitle');
    const editModeFields = document.getElementById('editModeFields');
    
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Schedule';
    
    // Tampilkan field edit mode
    if (editModeFields) {
        editModeFields.style.display = 'block';
    }
    
    document.getElementById('schedule_id').value = schedule.id;
    document.getElementById('spk').value = schedule.spk;
    document.getElementById('nama_barang').value = schedule.nama_barang;
    document.getElementById('qty_order').value = schedule.qty_order;
    document.getElementById('customer').value = schedule.customer;
    document.getElementById('op_cetak').value = schedule.op_cetak || '';
    document.getElementById('op_slitting').value = schedule.op_slitting || '';
    document.getElementById('status').value = schedule.status;
    document.getElementById('catatan').value = schedule.catatan || '';
    
    // Format datetime for input dengan timezone Indonesia
    document.getElementById('tanggal_mulai_cetak').value = convertToDatetimeLocal(schedule.tanggal_mulai_cetak);
    document.getElementById('tanggal_mulai_slitting').value = convertToDatetimeLocal(schedule.tanggal_mulai_slitting);
    
    modal.classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('scheduleModal');
    const editModeFields = document.getElementById('editModeFields');
    
    // Reset edit mode fields visibility
    if (editModeFields) {
        editModeFields.style.display = 'none';
    }
    
    modal.classList.remove('active');
}

function confirmDelete(id, spk) {
    const modal = document.getElementById('deleteModal');
    document.getElementById('delete_schedule_id').value = id;
    document.getElementById('deleteSPK').textContent = spk;
    modal.classList.add('active');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('active');
}

// Handle schedule form submission with AJAX
function handleScheduleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    appendCsrfToken(formData);
    const scheduleId = formData.get('schedule_id');
    const editModeFields = document.getElementById('editModeFields');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Jika mode tambah (tidak ada schedule_id), gunakan status dari hidden field
    if (!scheduleId && editModeFields && editModeFields.style.display === 'none') {
        // Hapus status dari select (jika ada) dan gunakan hidden field
        formData.delete('status');
        const statusHidden = formData.get('status_hidden') || 'Not Started';
        formData.set('status', statusHidden);
        formData.delete('status_hidden'); // Hapus hidden field agar tidak dikirim
    } else {
        // Mode edit: hapus hidden field
        formData.delete('status_hidden');
    }
    
    // Validasi frontend: jika ada op_slitting, harus ada op_cetak
    const opCetak = formData.get('op_cetak');
    const opSlitting = formData.get('op_slitting');
    if (opSlitting && !opCetak) {
        showAlert('error', 'Operator cetak harus diisi terlebih dahulu sebelum operator slitting');
        return;
    }
    
    // Konversi datetime-local ke format database dengan timezone Indonesia
    const tanggalCetak = formData.get('tanggal_mulai_cetak');
    const tanggalSlitting = formData.get('tanggal_mulai_slitting');
    
    // Validasi tanggal: tanggal_mulai_slitting harus setelah tanggal_mulai_cetak
    if (tanggalCetak && tanggalSlitting) {
        const dateCetak = new Date(tanggalCetak);
        const dateSlitting = new Date(tanggalSlitting);
        if (dateSlitting < dateCetak) {
            showAlert('error', 'Tanggal mulai slitting harus setelah tanggal mulai cetak');
            return;
        }
    }
    
    if (tanggalCetak) {
        formData.set('tanggal_mulai_cetak', convertFromDatetimeLocal(tanggalCetak));
    }
    if (tanggalSlitting) {
        formData.set('tanggal_mulai_slitting', convertFromDatetimeLocal(tanggalSlitting));
    }
    
    formData.append('action', 'save_schedule');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    
    fetch('/rbmschedule/api/schedule_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            closeModal();
            
            // Update timestamp to force sync on other clients
            // Kurangi 1 detik untuk memastikan perangkat lain mendeteksi update
            lastCheckTimestamp = Math.floor(Date.now() / 1000) - 1;
            
            // Trigger immediate sync check untuk perangkat lain
            if (typeof checkForUpdates === 'function') {
                setTimeout(() => {
                    checkForUpdates();
                }, 500);
            }
            
            if (data.action === 'created') {
                addScheduleToTable(data.schedule);
                showUpdateNotification('✅ Schedule baru ditambahkan!');
            } else if (data.action === 'updated') {
                updateScheduleInTable(data.schedule);
                showUpdateNotification('✅ Schedule berhasil diupdate!');
            }
            
            // Update stats immediately
            updateDashboardStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error saat menyimpan schedule:', error);
        showAlert('error', 'Terjadi kesalahan saat menyimpan schedule. Silakan coba lagi.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

// Handle delete form submission with AJAX
function handleDeleteFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    appendCsrfToken(formData);
    formData.append('action', 'delete_schedule');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    
    fetch('/rbmschedule/api/schedule_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                // Coba parse sebagai JSON dulu
                try {
                    const errorData = JSON.parse(text.trim());
                    throw new Error(errorData.message || `HTTP ${response.status}`);
                } catch (e) {
                    if (e.message && e.message.includes('HTTP')) {
                        throw e;
                    }
                    // Jika bukan JSON, gunakan text sebagai error
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                }
            });
        }
        
        return response.text().then(text => {
            // Trim whitespace yang mungkin ada
            text = text.trim();
            
            try {
                return JSON.parse(text);
            } catch (e) {
                // Jika bukan JSON, coba cari pesan error
                if (text.includes('error') || text.includes('Error') || text.includes('Warning') || text.includes('Fatal')) {
                    throw new Error('Server error: ' + text.substring(0, 200));
                }
                throw new Error('Response dari server tidak valid. Pastikan server berjalan dengan baik.');
            }
        });
    })
    .then(data => {
        if (data && data.success) {
            showAlert('success', data.message || 'Schedule berhasil dihapus!');
            
            // Update timestamp to force sync on other clients
            lastCheckTimestamp = Math.floor(Date.now() / 1000) - 1;
            
            // Trigger immediate sync check untuk perangkat lain
            if (typeof checkForUpdates === 'function') {
                setTimeout(() => {
                    checkForUpdates();
                }, 500);
            }
            
            const scheduleId = formData.get('schedule_id');
            if (scheduleId) {
                removeScheduleFromTable(scheduleId);
            }
            
            if (typeof showUpdateNotification === 'function') {
                showUpdateNotification('🗑️ Schedule berhasil dihapus!');
            }
            
            // Update stats immediately
            if (typeof updateDashboardStats === 'function') {
                updateDashboardStats();
            }
        } else {
            const errorMsg = (data && data.message) ? data.message : 'Gagal menghapus schedule';
            showAlert('error', errorMsg);
        }
    })
    .catch(error => {
        console.error('Delete Error:', error);
        
        // Tampilkan pesan error yang lebih spesifik
        let errorMessage = error.message || 'Terjadi kesalahan saat menghapus schedule';
        
        // Jika error terkait network, beri pesan yang lebih jelas
        if (error.message && (error.message.includes('Failed to fetch') || error.message.includes('NetworkError'))) {
            errorMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
        } else if (error.message && error.message.includes('HTTP')) {
            errorMessage = error.message;
        }
        
        showAlert('error', errorMessage + ' Silakan coba lagi atau refresh halaman.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        // Selalu tutup modal konfirmasi setelah proses selesai
        // agar form tidak tetap terbuka di layar
        closeDeleteModal();
    });
}

// Add new schedule row to table
function addScheduleToTable(schedule) {
    const airportBoard = document.getElementById('airportBoard');
    const tbody = document.querySelector('#scheduleTable tbody');
    
    // Update airport board jika ada
    if (airportBoard) {
        const emptyState = airportBoard.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
        
        const newRow = createAirportBoardRow(schedule);
        newRow.classList.add('new-entry');
        airportBoard.insertBefore(newRow, airportBoard.firstChild);
    }
    
    // Update table jika ada (untuk kompatibilitas)
    if (tbody) {
        const emptyRow = tbody.querySelector('td[colspan]');
        if (emptyRow) {
            emptyRow.parentElement.remove();
        }
        
        const newRow = createScheduleRow(schedule);
        tbody.insertBefore(newRow, tbody.firstChild);
    }
}

// Update existing schedule row in table
function updateScheduleInTable(schedule) {
    const airportBoard = document.getElementById('airportBoard');
    const tbody = document.querySelector('#scheduleTable tbody');
    
    // Update airport board jika ada
    if (airportBoard) {
        const existingRow = airportBoard.querySelector(`[data-schedule-id="${schedule.id}"]`);
        if (existingRow) {
            // Update content
            updateScheduleInAirportBoard(schedule);
            // Pindahkan ke paling atas
            const updatedRow = airportBoard.querySelector(`[data-schedule-id="${schedule.id}"]`);
            if (updatedRow && updatedRow !== airportBoard.firstChild) {
                airportBoard.insertBefore(updatedRow, airportBoard.firstChild);
                updatedRow.classList.add('updated');
                setTimeout(() => updatedRow.classList.remove('updated'), 1000);
            }
        } else {
            // Jika tidak ada, tambahkan di paling atas
            const newRow = createAirportBoardRow(schedule);
            newRow.classList.add('new-entry');
            airportBoard.insertBefore(newRow, airportBoard.firstChild);
        }
    }
    
    // Update table jika ada (untuk kompatibilitas)
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        
        for (let row of rows) {
            const editBtn = row.querySelector('button[onclick*="openEditModal"]');
            if (editBtn) {
                const onclickAttr = editBtn.getAttribute('onclick');
                const match = onclickAttr.match(/openEditModal\((.*?)\)/);
                if (match) {
                    try {
                        const currentSchedule = JSON.parse(match[1].replace(/&quot;/g, '"'));
                        if (currentSchedule.id == schedule.id) {
                            const newRow = createScheduleRow(schedule);
                            // Pindahkan ke paling atas
                            tbody.insertBefore(newRow, tbody.firstChild);
                            row.remove();
                            break;
                        }
                    } catch (e) {
                        console.error('Error parsing schedule:', e);
                    }
                }
            }
        }
    }
}

// Remove schedule row from table
function removeScheduleFromTable(scheduleId) {
    const airportBoard = document.getElementById('airportBoard');
    const tbody = document.querySelector('#scheduleTable tbody');
    
    // Remove from airport board jika ada
    if (airportBoard) {
        const boardRow = airportBoard.querySelector(`[data-schedule-id="${scheduleId}"]`);
        if (boardRow) {
            boardRow.style.animation = 'slideOut 0.5s ease';
            setTimeout(() => {
                boardRow.remove();
                
                // Check if board is empty
                const remainingRows = airportBoard.querySelectorAll('.board-row');
                if (remainingRows.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = '<i class="fas fa-inbox"></i><p>No schedules available</p>';
                    airportBoard.appendChild(emptyState);
                }
            }, 500);
        }
    }
    
    // Remove from table jika ada (untuk kompatibilitas)
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        
        for (let row of rows) {
            const deleteBtn = row.querySelector('button[onclick*="confirmDelete"]');
            if (deleteBtn) {
                const onclickAttr = deleteBtn.getAttribute('onclick');
                const match = onclickAttr.match(/confirmDelete\((\d+)/);
                if (match && match[1] == scheduleId) {
                    row.remove();
                    break;
                }
            }
        }
        
        // Check if table is empty
        const remainingRows = tbody.querySelectorAll('tr');
        if (remainingRows.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="11" class="text-center">Tidak ada data schedule</td>';
            tbody.appendChild(emptyRow);
        }
    }
}

// Create schedule table row HTML
function createScheduleRow(schedule) {
    const row = document.createElement('tr');
    
    const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner"></i>' :
                      schedule.status === 'Finish' ? '<i class="fas fa-check"></i>' :
                      '<i class="fas fa-clock"></i>';
    
    const scheduleJson = JSON.stringify(schedule).replace(/"/g, '&quot;');
    
    row.innerHTML = `
        <td><strong>${escapeHtml(schedule.spk)}</strong></td>
        <td>${escapeHtml(schedule.nama_barang)}</td>
        <td>${Number(schedule.qty_order).toLocaleString()}</td>
        <td>${escapeHtml(schedule.customer)}</td>
        <td>${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.tanggal_mulai_cetak ? formatDateTime(schedule.tanggal_mulai_cetak) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.tanggal_mulai_slitting ? formatDateTime(schedule.tanggal_mulai_slitting) : '<span class="text-muted">-</span>'}</td>
        <td>
            <span class="status-badge status-${schedule.status.toLowerCase()}">
                ${statusIcon} ${schedule.status}
            </span>
        </td>
        <td>${schedule.catatan ? escapeHtml(schedule.catatan) : '<span class="text-muted">-</span>'}</td>
        <td>
            <div class="action-buttons">
                <button class="btn btn-sm btn-info" onclick='openEditModal(${scheduleJson})'>
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete(${schedule.id}, '${escapeHtml(schedule.spk)}')">
                    <i class="fas fa-trash"></i>
                </button>
                ${schedule.status !== 'Finish' ? `
                <button class="btn btn-sm btn-success" onclick="markScheduleFinish(${schedule.id})">
                    <i class="fas fa-flag-checkered"></i>
                </button>` : ''}
            </div>
        </td>
    `;
    
    return row;
}

/**
 * Show alert message with better UX
 * 
 * @param {string} type - Alert type: 'success', 'error', 'warning', 'info'
 * @param {string} message - Alert message
 * @param {number} duration - Auto-hide duration in milliseconds (default: 5000)
 */
function showAlert(type, message, duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        alert.style.animation = 'slideOutUp 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    });
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('role', 'alert');
    alert.setAttribute('aria-live', 'polite');
    
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    alert.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'}" aria-hidden="true"></i>
        <span>${escapeHtml(message)}</span>
        <button class="alert-close" onclick="this.parentElement.remove()" aria-label="Tutup">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const container = document.querySelector('.manage-container') || 
                     document.querySelector('.dashboard-container') || 
                     document.querySelector('.main-content') ||
                     document.body;
    
    if (container) {
        // Insert at the beginning for better visibility
        const firstChild = container.firstElementChild;
        if (firstChild) {
            container.insertBefore(alert, firstChild);
        } else {
            container.appendChild(alert);
        }
        
        // Trigger animation
        setTimeout(() => {
            alert.style.animation = 'slideInDown 0.3s ease';
        }, 10);
        
        // Auto-hide after duration
        if (duration > 0) {
            setTimeout(() => {
                alert.style.animation = 'slideOutUp 0.3s ease';
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.remove();
                    }
                }, 300);
            }, duration);
        }
    }
}

/**
 * Show loading indicator
 * 
 * @param {HTMLElement} element - Element to show loading on
 * @param {string} message - Loading message
 */
function showLoading(element, message = 'Memproses...') {
    if (!element) return;
    
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
    
    element.style.position = 'relative';
    element.appendChild(loading);
    
    return loading;
}

/**
 * Hide loading indicator
 * 
 * @param {HTMLElement} element - Element with loading indicator
 */
function hideLoading(element) {
    if (!element) return;
    
    const loading = element.querySelector('.loading-overlay');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(() => {
            loading.remove();
        }, 300);
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format datetime (menggunakan timezone Indonesia)
function formatDateTime(datetime) {
    if (!datetime) return '-';
    const date = new Date(datetime);
    
    // Konversi ke timezone Indonesia (WIB = UTC+7)
    const indonesiaTime = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
    
    const day = String(indonesiaTime.getDate()).padStart(2, '0');
    const month = String(indonesiaTime.getMonth() + 1).padStart(2, '0');
    const year = indonesiaTime.getFullYear();
    const hours = String(indonesiaTime.getHours()).padStart(2, '0');
    const minutes = String(indonesiaTime.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Helper function untuk konversi datetime ke format datetime-local (timezone Indonesia)
function convertToDatetimeLocal(datetime) {
    if (!datetime) return '';
    
    const date = new Date(datetime);
    // Konversi ke timezone Indonesia
    const indonesiaTime = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
    
    const year = indonesiaTime.getFullYear();
    const month = String(indonesiaTime.getMonth() + 1).padStart(2, '0');
    const day = String(indonesiaTime.getDate()).padStart(2, '0');
    const hours = String(indonesiaTime.getHours()).padStart(2, '0');
    const minutes = String(indonesiaTime.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Helper function untuk konversi datetime-local ke format database (timezone Indonesia)
function convertFromDatetimeLocal(datetimeLocal) {
    if (!datetimeLocal) return null;
    
    // datetime-local sudah dalam timezone lokal browser, kita anggap itu WIB
    // Konversi ke format Y-m-d H:i:s untuk database
    const date = new Date(datetimeLocal);
    
    // Pastikan menggunakan timezone Indonesia
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// Dashboard operator modal functions
function openOperatorModal(schedule) {
    const modal = document.getElementById('operatorModal');
    
    document.getElementById('schedule_id').value = schedule.id;
    document.getElementById('modal_spk').value = schedule.spk;
    document.getElementById('modal_nama_barang').value = schedule.nama_barang;
    
    // Set OP Cetak - disable jika sudah ada value
    const opCetakSelect = document.getElementById('op_cetak');
    if (opCetakSelect) {
        opCetakSelect.value = schedule.op_cetak || '';
        // Disable jika sudah ada value (tidak bisa diubah)
        if (schedule.op_cetak && schedule.op_cetak.trim() !== '') {
            opCetakSelect.disabled = true;
            opCetakSelect.title = 'Operator cetak sudah dipilih dan tidak bisa diubah';
        } else {
            opCetakSelect.disabled = false;
            opCetakSelect.title = '';
        }
    }
    
    // Set OP Slitting - disable jika sudah ada value
    const opSlittingSelect = document.getElementById('op_slitting');
    if (opSlittingSelect) {
        opSlittingSelect.value = schedule.op_slitting || '';
        // Disable jika sudah ada value (tidak bisa diubah)
        if (schedule.op_slitting && schedule.op_slitting.trim() !== '') {
            opSlittingSelect.disabled = true;
            opSlittingSelect.title = 'Operator slitting sudah dipilih dan tidak bisa diubah';
        } else {
            opSlittingSelect.disabled = false;
            opSlittingSelect.title = '';
        }
    }
    
    // Display current status
    let statusHTML = '<span class="status-badge status-' + schedule.status.toLowerCase() + '">';
    if (schedule.status === 'Running') {
        statusHTML += '<i class="fas fa-spinner"></i> ';
    } else if (schedule.status === 'Finish') {
        statusHTML += '<i class="fas fa-check"></i> ';
    } else {
        statusHTML += '<i class="fas fa-clock"></i> ';
    }
    statusHTML += schedule.status + '</span>';
    document.getElementById('current_status').innerHTML = statusHTML;
    
    // Show finish checkbox only if status is Running
    const finishSection = document.getElementById('finish_section');
    if (schedule.status === 'Running') {
        finishSection.style.display = 'block';
    } else {
        finishSection.style.display = 'none';
    }
    document.getElementById('mark_finish').checked = false;
    
    modal.classList.add('active');
}

function closeOperatorModal() {
    const modal = document.getElementById('operatorModal');
    modal.classList.remove('active');
}

// Handle operator form submission with AJAX
function handleOperatorFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    
    fetch('/rbmschedule/api/schedule_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            closeOperatorModal();
            
            // Update timestamp to force sync on other clients
            // Kurangi 1 detik untuk memastikan perangkat lain mendeteksi update
            lastCheckTimestamp = Math.floor(Date.now() / 1000) - 1;
            
            // Trigger immediate sync check untuk perangkat lain
            if (typeof checkForUpdates === 'function') {
                setTimeout(() => {
                    checkForUpdates();
                }, 500);
            }
            
            // Jika operator dan status menjadi Finish, hapus dari tampilan
            const isOperator = document.getElementById('operatorModal') !== null;
            if (isOperator && data.schedule.status === 'Finish') {
                // Operator: hapus dari tampilan karena Finish tidak ditampilkan
                removeScheduleFromTable(data.schedule.id);
                updateDashboardStats();
                showUpdateNotification('✅ Schedule diset ke Finish dan dihapus dari tampilan!');
            } else {
                // Update schedule normal
                updateScheduleInDashboard(data.schedule);
                updateScheduleInTable(data.schedule); // Update di manage page juga
                updateDashboardStats();
                showUpdateNotification('✅ Status berhasil diupdate!');
            }
            
            // Force refresh untuk memastikan semua perangkat terupdate
            if (typeof refreshScheduleTable === 'function') {
                setTimeout(() => {
                    refreshScheduleTable();
                }, 1000);
            }
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Terjadi kesalahan. Silakan coba lagi!');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

// Update schedule row in dashboard
function updateScheduleInDashboard(schedule) {
    const tbody = document.querySelector('#scheduleTable tbody');
    const airportBoard = document.getElementById('airportBoard');
    
    // Update traditional table
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        
        for (let row of rows) {
            const updateBtn = row.querySelector('button[onclick*="openOperatorModal"]');
            if (updateBtn) {
                const onclickAttr = updateBtn.getAttribute('onclick');
                const match = onclickAttr.match(/openOperatorModal\((.*?)\)/);
                if (match) {
                    try {
                        const currentSchedule = JSON.parse(match[1].replace(/&quot;/g, '"'));
                        if (currentSchedule.id == schedule.id) {
                            const newRow = createDashboardScheduleRow(schedule);
                            // Pindahkan ke paling atas saat diupdate
                            tbody.insertBefore(newRow, tbody.firstChild);
                            row.remove();
                            break;
                        }
                    } catch (e) {
                        console.error('Error parsing schedule:', e);
                    }
                }
            }
        }
    }
    
    // Update airport board
    if (airportBoard) {
        updateScheduleInAirportBoard(schedule);
    }
}

// Create dashboard schedule table row HTML
function createDashboardScheduleRow(schedule) {
    const row = document.createElement('tr');
    
    const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner"></i>' :
                      schedule.status === 'Finish' ? '<i class="fas fa-check"></i>' :
                      '<i class="fas fa-clock"></i>';
    
    const scheduleJson = JSON.stringify(schedule).replace(/"/g, '&quot;');
    const isOperator = document.getElementById('operatorModal') !== null;
    
    row.innerHTML = `
        <td><strong>${escapeHtml(schedule.spk)}</strong></td>
        <td>${escapeHtml(schedule.nama_barang)}</td>
        <td>${Number(schedule.qty_order).toLocaleString()}</td>
        <td>${escapeHtml(schedule.customer)}</td>
        <td>${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.tanggal_mulai_cetak ? formatDateTime(schedule.tanggal_mulai_cetak) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '<span class="text-muted">-</span>'}</td>
        <td>${schedule.tanggal_mulai_slitting ? formatDateTime(schedule.tanggal_mulai_slitting) : '<span class="text-muted">-</span>'}</td>
        <td>
            <span class="status-badge status-${schedule.status.toLowerCase()}">
                ${statusIcon} ${schedule.status}
            </span>
        </td>
        <td>${schedule.catatan ? escapeHtml(schedule.catatan) : '<span class="text-muted">-</span>'}</td>
        ${isOperator ? `
        <td>
            <button class="btn btn-sm btn-info" onclick='openOperatorModal(${scheduleJson})'>
                <i class="fas fa-edit"></i> Update
            </button>
        </td>
        ` : ''}
    `;
    
    return row;
}

// Update dashboard statistics
function updateDashboardStats() {
    fetch('/rbmschedule/api/get_stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('.stat-total h3').textContent = data.stats.total;
            document.querySelector('.stat-pending h3').textContent = data.stats['not started'];
            document.querySelector('.stat-running h3').textContent = data.stats.running;
            document.querySelector('.stat-finish h3').textContent = data.stats.finish;
        }
    })
    .catch(error => {
        console.error('Error updating stats:', error);
    });
}

// Helper function to format datetime for input
function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Tandai schedule sebagai Finish (admin & operator)
function markScheduleFinish(scheduleId) {
    const formData = new FormData();
    formData.append('action', 'mark_finish');
    formData.append('schedule_id', scheduleId);
    appendCsrfToken(formData);
    
    fetch('/rbmschedule/api/schedule_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // Paksa update timestamp supaya client lain ikut sync
            // Kurangi 1 detik untuk memastikan perangkat lain mendeteksi update
            lastCheckTimestamp = Math.floor(Date.now() / 1000) - 1;
            
            // Trigger immediate sync check untuk perangkat lain
            if (typeof checkForUpdates === 'function') {
                setTimeout(() => {
                    checkForUpdates();
                }, 500);
            }
            
            // Jika operator: hapus dari tampilan (karena Finish tidak ditampilkan untuk operator)
            // Jika admin: update schedule (karena admin bisa lihat Finish di manage/report)
            const isOperator = document.getElementById('operatorModal') !== null;
            
            if (isOperator) {
                // Operator: hapus dari tampilan karena Finish tidak ditampilkan
                removeScheduleFromTable(scheduleId);
                updateDashboardStats();
                showUpdateNotification('✅ Schedule diset ke Finish dan dihapus dari tampilan!');
            } else {
                // Admin: update schedule (bisa lihat di manage/report)
                updateScheduleInTable(data.schedule);
                updateScheduleInDashboard(data.schedule);
                updateDashboardStats();
                showUpdateNotification('✅ Schedule diset ke Finish!');
            }
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Terjadi kesalahan. Silakan coba lagi!');
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
});

// Real-time sync variables
let lastCheckTimestamp = Math.floor(Date.now() / 1000);
let lastScheduleCount = null; // Store previous schedule count to detect deletions
let syncInterval = null;
let isSyncing = false;
let updateNotificationTimeout = null;
let eventSource = null;
let sseReconnectTimeout = null;

// Start real-time synchronization
function startRealtimeSync() {
    // Check for updates every 2 seconds for faster sync
    syncInterval = setInterval(checkForUpdates, 2000);
    console.log('🔄 Real-time sync started');
}

// Stop real-time synchronization
function stopRealtimeSync() {
    if (syncInterval) {
        clearInterval(syncInterval);
        syncInterval = null;
        console.log('⏸️ Real-time sync stopped');
    }
}

function stopEventStream() {
    if (eventSource) {
        eventSource.close();
        eventSource = null;
        console.log('⏸️ SSE stream stopped');
    }
}

function scheduleSseReconnect() {
    if (sseReconnectTimeout) {
        return;
    }
    sseReconnectTimeout = setTimeout(() => {
        sseReconnectTimeout = null;
        startEventStream();
    }, 8000);
}

function startEventStream() {
    if (!window.EventSource) {
        console.warn('SSE not supported, fallback to polling');
        startRealtimeSync();
        return;
    }

    if (sseReconnectTimeout) {
        clearTimeout(sseReconnectTimeout);
        sseReconnectTimeout = null;
    }

    stopEventStream();
    stopRealtimeSync();

        eventSource = new EventSource('/rbmschedule/api/updates_stream.php');
    console.log('📡 SSE stream connected');

    eventSource.addEventListener('schedule-update', (event) => {
        try {
            const payload = JSON.parse(event.data);
            lastCheckTimestamp = payload.timestamp;
            lastScheduleCount = payload.total_schedules;
            refreshScheduleTable();
        } catch (err) {
            console.error('Failed parsing SSE payload', err);
        }
    });

    eventSource.addEventListener('heartbeat', () => {
        // no-op heartbeat just to keep connection alive
    });

    eventSource.onerror = () => {
        console.warn('SSE stream error, fallback to polling');
        stopEventStream();
        startRealtimeSync();
        scheduleSseReconnect();
    };
}

function initRealtimeUpdates() {
    if (window.EventSource) {
        startEventStream();
    } else {
        startRealtimeSync();
    }
}

// Show update notification
function showUpdateNotification(message) {
    // Remove existing notification
    const existingNotif = document.getElementById('updateNotification');
    if (existingNotif) {
        existingNotif.remove();
    }
    
    // Clear existing timeout
    if (updateNotificationTimeout) {
        clearTimeout(updateNotificationTimeout);
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.id = 'updateNotification';
    notification.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        background: linear-gradient(135deg, rgba(0, 212, 255, 0.95), rgba(0, 184, 230, 0.95));
        color: var(--airport-bg);
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 212, 255, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-sync-alt fa-spin"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    updateNotificationTimeout = setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Check for updates from server
function checkForUpdates() {
    if (isSyncing) return; // Prevent multiple simultaneous checks
    
    isSyncing = true;
    
    console.log(`🔍 Checking for updates... (last_check: ${lastCheckTimestamp}, last_count: ${lastScheduleCount})`);
    
    // Build URL with last_check and last_count parameters
    let url = `/rbmschedule/api/check_updates.php?last_check=${lastCheckTimestamp}`;
    if (lastScheduleCount !== null) {
        url += `&last_count=${lastScheduleCount}`;
    }
    
    fetch(url)
    .then(response => {
        console.log('📡 Response received from check_updates.php');
        return response.json();
    })
    .then(data => {
        console.log('📊 Update check result:', data);
        
        if (data.success && data.has_updates) {
            console.log('🔔 NEW UPDATES DETECTED!');
            lastCheckTimestamp = data.timestamp;
            lastScheduleCount = data.total_schedules; // Update count
            refreshScheduleTable();
        } else {
            // Update count even if no updates (to keep it in sync)
            if (data.total_schedules !== undefined) {
                lastScheduleCount = data.total_schedules;
            }
            console.log('✓ No updates - data is current');
        }
    })
    .catch(error => {
        console.error('❌ Error checking updates:', error);
    })
    .finally(() => {
        isSyncing = false;
    });
}

// Refresh schedule table with latest data
function refreshScheduleTable() {
    // Check if we're on report page (need all schedules including Finish)
    const isReportPage = window.location.pathname.includes('report.php');
    const queryString = buildScheduleQueryParams(isReportPage);
    const url = queryString
        ? `/rbmschedule/api/get_schedules.php?${queryString}`
        : '/rbmschedule/api/get_schedules.php';
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTableWithSchedules(data.schedules);
            
            // Update count after getting schedules
            if (data.meta && typeof data.meta.total !== 'undefined') {
                lastScheduleCount = data.meta.total;
            } else if (data.schedules) {
                lastScheduleCount = data.schedules.length;
            }
            
            // Update stats if on dashboard
            const statsGrid = document.querySelector('.stats-grid');
            if (statsGrid) {
                updateDashboardStats();
            }
            
            console.log('✅ Schedule data updated successfully');
        }
    })
    .catch(error => {
        console.error('❌ Error refreshing table:', error);
    });
}

// Store previous schedules for comparison
let previousSchedules = new Map();

// Initialize previous schedules on page load
document.addEventListener('DOMContentLoaded', function() {
    const airportBoard = document.getElementById('airportBoard');
    if (airportBoard) {
        const existingRows = airportBoard.querySelectorAll('.board-row');
        existingRows.forEach(row => {
            const scheduleId = row.getAttribute('data-schedule-id');
            if (scheduleId) {
                const status = row.getAttribute('data-status') || '';
                
                // Extract Production Info from DOM
                const productionInfoCell = row.querySelectorAll('.board-cell')[3]; // Production Info is 4th cell
                let op_cetak = '';
                let op_slitting = '';
                let tanggal_mulai_cetak = '';
                let tanggal_mulai_slitting = '';
                
                if (productionInfoCell) {
                    // Extract OP Cetak
                    const cetakText = productionInfoCell.querySelector('.board-value.small');
                    if (cetakText && cetakText.textContent.includes('Cetak:')) {
                        const cetakMatch = cetakText.textContent.match(/Cetak:\s*(.+)/);
                        if (cetakMatch && cetakMatch[1] && cetakMatch[1].trim() !== '-') {
                            op_cetak = cetakMatch[1].trim();
                        }
                    }
                    
                    // Extract Tanggal Mulai Cetak
                    const cetakTimeElements = productionInfoCell.querySelectorAll('.digital-time');
                    if (cetakTimeElements.length > 0 && cetakTimeElements[0].textContent.trim() !== '-') {
                        tanggal_mulai_cetak = cetakTimeElements[0].textContent.trim();
                    }
                    
                    // Extract OP Slitting
                    const slittingTexts = productionInfoCell.querySelectorAll('.board-value.small');
                    if (slittingTexts.length > 1 && slittingTexts[1].textContent.includes('Slitting:')) {
                        const slittingMatch = slittingTexts[1].textContent.match(/Slitting:\s*(.+)/);
                        if (slittingMatch && slittingMatch[1] && slittingMatch[1].trim() !== '-') {
                            op_slitting = slittingMatch[1].trim();
                        }
                    }
                    
                    // Extract Tanggal Mulai Slitting
                    if (cetakTimeElements.length > 1 && cetakTimeElements[1].textContent.trim() !== '-') {
                        tanggal_mulai_slitting = cetakTimeElements[1].textContent.trim();
                    }
                }
                
                previousSchedules.set(parseInt(scheduleId), {
                    status: status,
                    spk: row.querySelector('.board-value.large')?.textContent || '',
                    op_cetak: op_cetak,
                    op_slitting: op_slitting,
                    tanggal_mulai_cetak: tanggal_mulai_cetak,
                    tanggal_mulai_slitting: tanggal_mulai_slitting
                });
            }
        });
    }
});

// Update table with new schedule data
function updateTableWithSchedules(schedules) {
    const tbody = document.querySelector('#scheduleTable tbody');
    const airportBoard = document.getElementById('airportBoard');
    
    // Update traditional table if exists
    if (tbody) {
        const isManagePage = document.getElementById('scheduleModal') !== null;
        const isOperator = document.getElementById('operatorModal') !== null;
        
        tbody.innerHTML = '';
        
        if (schedules.length === 0) {
            const colspan = isManagePage ? 11 : (isOperator ? 11 : 10);
            tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center">Tidak ada data schedule</td></tr>`;
        } else {
            // Sort schedules: Running > Not Started > Finish, lalu yang lebih baru di atas
            schedules.sort((a, b) => {
                const statusOrder = { 'Running': 1, 'Not Started': 2, 'Finish': 3 };
                const aOrder = statusOrder[a.status] || 4;
                const bOrder = statusOrder[b.status] || 4;
                if (aOrder !== bOrder) {
                    return aOrder - bOrder;
                }
                // Jika status sama, yang lebih baru di atas
                const aTime = new Date(a.updated_at || a.created_at || 0);
                const bTime = new Date(b.updated_at || b.created_at || 0);
                return bTime - aTime;
            });
            
            schedules.forEach(schedule => {
                // Untuk operator: skip schedule yang Finish
                if (isOperator && schedule.status === 'Finish') {
                    return; // Skip schedule Finish untuk operator
                }
                
                let row;
                if (isManagePage) {
                    row = createScheduleRow(schedule);
                } else {
                    row = createDashboardScheduleRow(schedule);
                }
                // Taruh di paling atas (insertBefore dengan firstChild)
                tbody.insertBefore(row, tbody.firstChild);
            });
        }
    }
    
    // Update airport board with animations
    if (airportBoard) {
        const currentSchedules = new Map();
        schedules.forEach(schedule => {
            currentSchedules.set(parseInt(schedule.id), schedule);
        });
        
        // Remove deleted schedules atau schedule yang menjadi Finish (untuk operator)
        const isOperator = document.getElementById('operatorModal') !== null;
        previousSchedules.forEach((value, id) => {
            if (!currentSchedules.has(id)) {
                // Schedule dihapus dari database
                const row = airportBoard.querySelector(`[data-schedule-id="${id}"]`);
                if (row) {
                    row.style.animation = 'slideOut 0.5s ease';
                    setTimeout(() => row.remove(), 500);
                }
            } else {
                // Cek jika schedule menjadi Finish dan user adalah operator
                const schedule = currentSchedules.get(id);
                if (isOperator && schedule && schedule.status === 'Finish') {
                    const row = airportBoard.querySelector(`[data-schedule-id="${id}"]`);
                    if (row) {
                        row.style.animation = 'slideOut 0.5s ease';
                        setTimeout(() => row.remove(), 500);
                    }
                }
            }
        });
        
        if (schedules.length === 0) {
            airportBoard.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No schedules available</p>
                </div>
            `;
        } else {
            // Sort schedules: Running > Not Started > Finish, lalu yang lebih baru di atas
            schedules.sort((a, b) => {
                const statusOrder = { 'Running': 1, 'Not Started': 2, 'Finish': 3 };
                const aOrder = statusOrder[a.status] || 4;
                const bOrder = statusOrder[b.status] || 4;
                if (aOrder !== bOrder) {
                    return aOrder - bOrder;
                }
                // Jika status sama, yang lebih baru di atas (berdasarkan updated_at atau created_at)
                const aTime = new Date(a.updated_at || a.created_at || 0);
                const bTime = new Date(b.updated_at || b.created_at || 0);
                return bTime - aTime;
            });
            
            schedules.forEach(schedule => {
                const existingRow = airportBoard.querySelector(`[data-schedule-id="${schedule.id}"]`);
                const prevSchedule = previousSchedules.get(parseInt(schedule.id));
                const isNew = !prevSchedule;
                const isUpdated = prevSchedule && (
                    prevSchedule.status !== schedule.status.toLowerCase() ||
                    prevSchedule.op_cetak !== (schedule.op_cetak || '') ||
                    prevSchedule.op_slitting !== (schedule.op_slitting || '') ||
                    prevSchedule.tanggal_mulai_cetak !== (schedule.tanggal_mulai_cetak || '') ||
                    prevSchedule.tanggal_mulai_slitting !== (schedule.tanggal_mulai_slitting || '')
                );
                const statusChanged = prevSchedule && prevSchedule.status !== schedule.status.toLowerCase();
                
                // Jika operator dan status menjadi Finish, hapus dari tampilan
                const isOperator = document.getElementById('operatorModal') !== null;
                if (isOperator && schedule.status === 'Finish') {
                    if (existingRow) {
                        existingRow.style.animation = 'slideOut 0.5s ease';
                        setTimeout(() => existingRow.remove(), 500);
                    }
                    return; // Skip update untuk schedule Finish pada operator
                }
                
                if (existingRow) {
                    // Update existing row
                    if (isUpdated) {
                        existingRow.classList.add('updated');
                        if (statusChanged) {
                            existingRow.classList.add('status-change');
                            const badge = existingRow.querySelector('.status-badge');
                            if (badge) {
                                badge.classList.add('status-update');
                            }
                        }
                        updateBoardRowContent(existingRow, schedule);
                        
                        // Pindahkan ke paling atas jika diupdate
                        if (existingRow !== airportBoard.firstChild) {
                            airportBoard.insertBefore(existingRow, airportBoard.firstChild);
                        }
                        
                        setTimeout(() => {
                            existingRow.classList.remove('updated', 'status-change');
                            const badge = existingRow.querySelector('.status-badge');
                            if (badge) {
                                badge.classList.remove('status-update');
                            }
                        }, 1000);
                    }
                } else {
                    // Create new row with animation - taruh di paling atas
                    // Jangan tambahkan jika operator dan status Finish
                    if (!(isOperator && schedule.status === 'Finish')) {
                        const boardRow = createAirportBoardRow(schedule);
                        boardRow.classList.add('new-entry');
                        airportBoard.insertBefore(boardRow, airportBoard.firstChild);
                    }
                }
            });
        }
        
        // Update previous schedules (hanya yang tidak Finish untuk operator)
        const isOperatorForPrev = document.getElementById('operatorModal') !== null;
        previousSchedules.clear();
        schedules.forEach(schedule => {
            // Untuk operator: jangan simpan schedule Finish
            if (isOperatorForPrev && schedule.status === 'Finish') {
                return; // Skip schedule Finish untuk operator
            }
            
            previousSchedules.set(parseInt(schedule.id), {
                status: schedule.status.toLowerCase(),
                spk: schedule.spk,
                op_cetak: schedule.op_cetak || '',
                op_slitting: schedule.op_slitting || '',
                tanggal_mulai_cetak: schedule.tanggal_mulai_cetak || '',
                tanggal_mulai_slitting: schedule.tanggal_mulai_slitting || ''
            });
        });
    }
}

// Update board row content
function updateBoardRowContent(row, schedule) {
    const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner fa-spin"></i>' :
                      schedule.status === 'Finish' ? '<i class="fas fa-check-circle"></i>' :
                      '<i class="fas fa-clock"></i>';
    
    // Update status badge
    const statusBadge = row.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge status-${schedule.status.toLowerCase()}`;
        statusBadge.innerHTML = `${statusIcon} ${schedule.status}`;
    }
    
    // Update Production Info - OP Cetak, Tanggal, OP Slitting, Tanggal
    const productionInfoCell = row.querySelectorAll('.board-cell')[3]; // Production Info is 4th cell (index 3)
    if (productionInfoCell) {
        const cetakTime = schedule.tanggal_mulai_cetak 
            ? formatDateTime(schedule.tanggal_mulai_cetak) 
            : '-';
        const slittingTime = schedule.tanggal_mulai_slitting 
            ? formatDateTime(schedule.tanggal_mulai_slitting) 
            : '-';
        
        // Update OP Cetak - cari semua .board-value.small dan update yang pertama (Cetak)
        const allSmallValues = productionInfoCell.querySelectorAll('.board-value.small');
        if (allSmallValues.length > 0) {
            const cetakValue = allSmallValues[0];
            if (cetakValue.textContent.includes('Cetak:')) {
                cetakValue.innerHTML = `<i class="fas fa-print"></i> Cetak: ${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '<span class="text-muted">-</span>'}`;
            }
        }
        
        // Update Tanggal Mulai Cetak
        const cetakTimeElements = productionInfoCell.querySelectorAll('.digital-time');
        if (cetakTimeElements.length > 0) {
            cetakTimeElements[0].textContent = cetakTime;
        }
        
        // Update OP Slitting - cari yang kedua (Slitting)
        if (allSmallValues.length > 1) {
            const slittingValue = allSmallValues[1];
            if (slittingValue.textContent.includes('Slitting:')) {
                slittingValue.innerHTML = `<i class="fas fa-cut"></i> Slitting: ${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '<span class="text-muted">-</span>'}`;
            }
        }
        
        // Update Tanggal Mulai Slitting
        if (cetakTimeElements.length > 1) {
            cetakTimeElements[1].textContent = slittingTime;
        }
    }
    
    // Update other fields if needed
    row.setAttribute('data-status', schedule.status.toLowerCase());
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    // Attach AJAX handlers to forms
    const scheduleForm = document.getElementById('scheduleForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', handleScheduleFormSubmit);
    }
    
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', handleDeleteFormSubmit);
    }
    
    const operatorForm = document.getElementById('operatorForm');
    if (operatorForm) {
        operatorForm.addEventListener('submit', handleOperatorFormSubmit);
    }
    
    // Start real-time sync jika ada tabel schedule atau airport board
    const scheduleTableEl = document.getElementById('scheduleTable');
    const airportBoardEl = document.getElementById('airportBoard');
    
    if (scheduleTableEl || airportBoardEl) {
        // Initialize schedule count on page load
        const isReportPage = window.location.pathname.includes('report.php');
        const initialParams = buildScheduleQueryParams(isReportPage);
        const initialUrl = initialParams
            ? `/rbmschedule/api/get_schedules.php?${initialParams}`
            : '/rbmschedule/api/get_schedules.php';
        
        fetch(initialUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.meta && typeof data.meta.total !== 'undefined') {
                    lastScheduleCount = data.meta.total;
                } else if (data.schedules) {
                    lastScheduleCount = data.schedules.length;
                }
                console.log(`📊 Initial schedule count: ${lastScheduleCount}`);
            }
        })
        .catch(error => {
            console.error('❌ Error initializing schedule count:', error);
        });
        
        console.log('🚀 Starting real-time updates...');
        initRealtimeUpdates();
        
        // Pause/resume saat tab tidak aktif
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('👁️ Page hidden - pausing realtime channel');
                stopRealtimeSync();
                stopEventStream();
            } else {
                console.log('👁️ Page visible - resuming realtime channel');
                lastCheckTimestamp = Math.floor(Date.now() / 1000);
                initRealtimeUpdates();
                checkForUpdates();
            }
        });
    }
});

// Create airport board row HTML
function createAirportBoardRow(schedule) {
    const row = document.createElement('div');
    row.className = 'board-row';
    row.setAttribute('data-schedule-id', schedule.id);
    row.setAttribute('data-status', schedule.status.toLowerCase());
    
    const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner fa-spin"></i>' :
                      schedule.status === 'Finish' ? '<i class="fas fa-check-circle"></i>' :
                      '<i class="fas fa-clock"></i>';
    
    const scheduleJson = JSON.stringify(schedule).replace(/"/g, '&quot;');
    const isOperator = document.getElementById('operatorModal') !== null;
    const isAdmin = document.getElementById('scheduleModal') !== null; // Manage page has scheduleModal
    
    const cetakTime = schedule.tanggal_mulai_cetak ? formatDateTime(schedule.tanggal_mulai_cetak) : '-';
    const slittingTime = schedule.tanggal_mulai_slitting ? formatDateTime(schedule.tanggal_mulai_slitting) : '-';
    
    // Build action buttons based on user role
    let actionButtons = '';
    if (isAdmin) {
        // Admin can edit, delete, and mark finish
        actionButtons = `
            <button class="btn btn-sm btn-info" onclick='openEditModal(${scheduleJson})'>
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-sm btn-danger" onclick="confirmDelete(${schedule.id}, '${escapeHtml(schedule.spk)}')">
                <i class="fas fa-trash"></i> Delete
            </button>
            ${schedule.status !== 'Finish' ? `
            <button class="btn btn-sm btn-success" onclick="markScheduleFinish(${schedule.id})">
                <i class="fas fa-flag-checkered"></i> Finish
            </button>` : ''}
        `;
    } else if (isOperator) {
        // Operator can update and mark finish
        actionButtons = `
            <button class="btn btn-sm btn-info" onclick='openOperatorModal(${scheduleJson})'>
                <i class="fas fa-edit"></i> Update
            </button>
            ${schedule.status !== 'Finish' ? `
            <button class="btn btn-sm btn-success" onclick="markScheduleFinish(${schedule.id})">
                <i class="fas fa-flag-checkered"></i> Finish
            </button>` : ''}
        `;
    }
    
    row.innerHTML = `
        <div class="board-cell">
            <div class="board-label">SPK Number</div>
            <div class="board-value large">${escapeHtml(schedule.spk)}</div>
        </div>
        
        <div class="board-cell">
            <div class="board-label">Item & Customer</div>
            <div class="board-value">${escapeHtml(schedule.nama_barang)}</div>
            <div class="board-value small" style="color: var(--airport-text-dim);">
                <i class="fas fa-building"></i> ${escapeHtml(schedule.customer)}
            </div>
        </div>
        
        <div class="board-cell">
            <div class="board-label">Quantity</div>
            <div class="board-value">${Number(schedule.qty_order).toLocaleString()} pcs</div>
        </div>
        
        <div class="board-cell">
            <div class="board-label">Production Info</div>
            <div class="board-value small">
                <i class="fas fa-print"></i> Cetak: 
                ${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '<span class="text-muted">-</span>'}
            </div>
            <div class="digital-time">${cetakTime}</div>
            <div class="board-value small" style="margin-top: 0.5rem;">
                <i class="fas fa-cut"></i> Slitting: 
                ${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '<span class="text-muted">-</span>'}
            </div>
            <div class="digital-time">${slittingTime}</div>
        </div>
        
        <div class="board-cell">
            <div class="board-label">Status & Action</div>
            <span class="status-badge status-${schedule.status.toLowerCase()}">
                ${statusIcon} ${schedule.status}
            </span>
            ${actionButtons ? `
            <div class="board-actions" style="margin-top: 0.75rem;">
                ${actionButtons}
            </div>
            ` : ''}
            ${schedule.catatan ? `
            <div class="board-value small" style="margin-top: 0.5rem; color: var(--airport-text-dim);">
                <i class="fas fa-sticky-note"></i> ${escapeHtml(schedule.catatan)}
            </div>
            ` : ''}
        </div>
    `;
    
    return row;
}

// Update schedule row in airport board
function updateScheduleInAirportBoard(schedule) {
    const airportBoard = document.getElementById('airportBoard');
    if (!airportBoard) return;
    
    const existingRow = airportBoard.querySelector(`[data-schedule-id="${schedule.id}"]`);
    
    if (existingRow) {
        const newRow = createAirportBoardRow(schedule);
        newRow.classList.add('flip-animation', 'updated');
        
        // Pindahkan ke paling atas saat diupdate
        if (existingRow !== airportBoard.firstChild) {
            airportBoard.insertBefore(newRow, airportBoard.firstChild);
            existingRow.remove();
        } else {
            existingRow.replaceWith(newRow);
        }
        
        // Remove animation class after animation completes
        setTimeout(() => {
            newRow.classList.remove('flip-animation', 'updated');
        }, 1000);
    } else {
        // Jika tidak ada, tambahkan di paling atas
        const newRow = createAirportBoardRow(schedule);
        newRow.classList.add('new-entry');
        airportBoard.insertBefore(newRow, airportBoard.firstChild);
    }
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopRealtimeSync();
});