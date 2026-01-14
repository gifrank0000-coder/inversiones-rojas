// Copia de register.js a auth/register.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        // ...validaciones y fetch (igual que register.js)
    });
});