import '../styles/app.css';

document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-tag-remove]');
    if (!removeButton) return;

    removeButton.closest('[data-tag]')?.remove();
});
