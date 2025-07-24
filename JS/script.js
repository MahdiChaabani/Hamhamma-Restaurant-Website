
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.contact-form');
    const inputs = form.querySelectorAll('input, textarea');

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            if (input.checkValidity()) {
                input.classList.add('valid');
                input.classList.remove('invalid');
            } else {
                input.classList.add('invalid');
                input.classList.remove('valid');
            }
        });
    });
});

document.getElementById('contact-form').addEventListener('input', function(event) {
    const input = event.target;

    // Validation rules
    if (input.type === 'text') {
        input.classList.toggle('success', input.value.trim().length > 2);
        input.classList.toggle('error', input.value.trim().length <= 2);
    }
    
    if (input.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        input.classList.toggle('success', emailRegex.test(input.value));
        input.classList.toggle('error', !emailRegex.test(input.value));
    }

    if (input.type === 'tel') {
        const phoneRegex = /^\+?[0-9]{7,14}$/;
        input.classList.toggle('success', phoneRegex.test(input.value));
        input.classList.toggle('error', !phoneRegex.test(input.value));
    }
});
