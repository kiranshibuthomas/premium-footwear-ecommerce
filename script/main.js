document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    const userIcon = document.getElementById('user-icon');
    const dropdownContent = userDropdown.querySelector('.dropdown-content');

    userIcon.addEventListener('click', function(e) {
        e.preventDefault();
        dropdownContent.style.display = (dropdownContent.style.display === 'block') ? 'none' : 'block';
    });

    document.addEventListener('click', function(e) {
        if (!userDropdown.contains(e.target)) {
            dropdownContent.style.display = 'none';
        }
    });
});
