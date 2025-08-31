// Admin dashboard charts

document.addEventListener('DOMContentLoaded', () => {
    const data = window.dashboardData || {};

    const salesCtx = document.getElementById('salesChart');
    if (salesCtx && data.sales) {
        const labels = data.sales.map(r => r.inv_date);
        const quantities = data.sales.map(r => r.qty);
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Products Sold',
                    data: quantities,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.2)',
                    tension: 0.4
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const couponCtx = document.getElementById('couponChart');
    if (couponCtx) {
        const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const values = labels.map(() => Math.floor(Math.random() * 10) + 1);
        new Chart(couponCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Coupon Usage',
                    data: values,
                    backgroundColor: 'rgba(255,193,7,0.6)'
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx && data.payments) {
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: [data.payments.paid, data.payments.unpaid],
                    backgroundColor: ['#0d6efd', '#ced4da']
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
