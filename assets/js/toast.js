function showToast({
                       title = 'Notification',
                       message = '',
                       type = 'success', // success | danger | warning | info
                       image = '',
                       time = 'Just now'
                   } = {}) {
    const toastEl = document.getElementById('liveToast');
    const toastTitle = document.getElementById('toast-title');
    const toastBody = document.getElementById('toast-body');
    const toastTime = document.getElementById('toast-time');
    const toastImg = document.getElementById('toast-img');

    // Style by type
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;

    // Set content
    toastTitle.textContent = title;
    toastBody.textContent = message;
    toastTime.textContent = time;
    toastImg.src = image || 'assets/images/icons/default.png';

    // Bootstrap show
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    setTimeout(() => {
        toast.hide()
    }, 5000)
}
// document.addEventListener("DOMContentLoaded", function () {
//     showToast()
// })
