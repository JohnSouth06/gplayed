function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "visibility_off"; // Change l'icône pour "œil barré"
    } else {
        input.type = "password";
        icon.textContent = "visibility"; // Revient à l'icône "œil"
    }
}