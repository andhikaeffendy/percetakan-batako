/**
 * assets/js/app.js
 * Percetakan Batako Maros — JavaScript helpers
 */

// Sidebar mobile toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.add('show');
            if (backdrop) backdrop.classList.add('show');
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // Auto-hitung total penjualan di form input
    const jumlahTerjual = document.getElementById('jumlah_terjual');
    const hargaSatuan = document.getElementById('harga_satuan');
    const totalPenjualan = document.getElementById('total_penjualan');
    
    if (jumlahTerjual && hargaSatuan) {
        function hitungTotal() {
            const jml = parseInt(jumlahTerjual.value) || 0;
            const hrg = parseInt(hargaSatuan.value) || 0;
            if (totalPenjualan) totalPenjualan.value = jml * hrg;
        }
        jumlahTerjual.addEventListener('input', hitungTotal);
        hargaSatuan.addEventListener('input', hitungTotal);
        hitungTotal();
    }

    // Delete confirmation modal
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Tanggal hari ini sebagai default
    document.querySelectorAll('.date-today').forEach(el => {
        if (!el.value) {
            const now = new Date();
            const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
            el.value = localDate.toISOString().split('T')[0];
        }
    });
});
